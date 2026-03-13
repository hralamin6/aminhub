<?php

namespace App\Services;

use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Deduct stock using FIFO batch method.
     * Automatically deducts from oldest batches first.
     *
     * @return array Array of created stock movements with batch info
     * @throws \RuntimeException if insufficient stock
     */
    public function deductSaleStockWithBatches(
        int $variantId,
        float $quantity,
        int $unitId,
        int $saleItemId,
        int $saleId
    ): array {
        $baseQty = $this->convertToBaseUnit($variantId, $unitId, $quantity);

        if (! $this->hasStock($variantId, $baseQty)) {
            throw new \RuntimeException(__('Insufficient stock for this sale.'));
        }

        $movements = [];
        $remainingQty = $baseQty;

        // Get batches with remaining stock, ordered by creation date (FIFO)
        $batches = ProductBatch::where('product_variant_id', $variantId)
            ->whereHas('stockMovements', function ($q) {
                $q->where('direction', 'in');
            })
            ->withSum(['stockMovements as total_in' => fn($q) => $q->where('direction', 'in')], 'quantity')
            ->withSum(['stockMovements as total_out' => fn($q) => $q->where('direction', 'out')], 'quantity')
            ->orderBy('created_at')
            ->get();

        foreach ($batches as $batch) {
            if ($remainingQty <= 0) break;

            $batchStock = (float) ($batch->total_in - $batch->total_out);

            if ($batchStock > 0) {
                $deductQty = min($remainingQty, $batchStock);

                $movement = StockMovement::create([
                    'product_variant_id' => $variantId,
                    'batch_id' => $batch->id,
                    'type' => 'sale',
                    'direction' => 'out',
                    'quantity' => $deductQty,
                    'unit_id' => $unitId,
                    'original_quantity' => $quantity * ($deductQty / $baseQty), // Proportional original qty
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'created_by' => Auth::id(),
                ]);

                $movements[] = [
                    'movement' => $movement,
                    'batch' => $batch,
                    'quantity' => $deductQty,
                ];

                $remainingQty -= $deductQty;
            }
        }

        // If there's still remaining quantity, create movement without batch
        if ($remainingQty > 0) {
            $movement = StockMovement::create([
                'product_variant_id' => $variantId,
                'type' => 'sale',
                'direction' => 'out',
                'quantity' => $remainingQty,
                'unit_id' => $unitId,
                'original_quantity' => $quantity * ($remainingQty / $baseQty),
                'reference_type' => 'sale',
                'reference_id' => $saleId,
                'created_by' => Auth::id(),
            ]);

            $movements[] = [
                'movement' => $movement,
                'batch' => null,
                'quantity' => $remainingQty,
            ];
        }

        return $movements;
    }

    /**
     * Get batch-wise stock for a variant.
     */
    public function getBatchWiseStock(int $variantId): Collection
    {
        return ProductBatch::where('product_variant_id', $variantId)
            ->withSum(['stockMovements as total_in' => fn($q) => $q->where('direction', 'in')], 'quantity')
            ->withSum(['stockMovements as total_out' => fn($q) => $q->where('direction', 'out')], 'quantity')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($batch) => [
                'batch' => $batch,
                'current_stock' => (float) ($batch->total_in - $batch->total_out),
                'initial_quantity' => (float) $batch->initial_quantity,
            ])
            ->filter(fn ($item) => $item['current_stock'] > 0 || $item['batch']->expiry_date)
            ->values();
    }

    /**
     * Get batch profitability report.
     */
    public function getBatchProfitability(int $variantId): Collection
    {
        return ProductBatch::where('product_variant_id', $variantId)
            ->with(['stockMovements' => fn($q) => $q->whereIn('type', ['purchase', 'sale'])])
            ->orderBy('created_at')
            ->get()
            ->map(function ($batch) {
                $purchaseMovements = $batch->stockMovements->where('type', 'purchase');
                $saleMovements = $batch->stockMovements->where('type', 'sale');

                $purchaseQty = $purchaseMovements->sum('quantity');
                $saleQty = $saleMovements->sum('quantity');

                // Calculate average purchase price from purchase movements
                $avgPurchasePrice = $purchaseMovements->avg(fn($m) =>
                    $m->original_quantity > 0 ? $m->reference?->unit_price ?? 0 : 0
                ) ?: 0;

                // Calculate average sale price from sale movements
                $avgSalePrice = $saleMovements->avg(fn($m) =>
                    $m->original_quantity > 0 ? ($m->quantity / $m->original_quantity) * ($m->reference?->unit_price ?? 0) : 0
                ) ?: 0;

                $currentStock = $purchaseQty - $saleQty;
                $soldQty = $saleQty;

                return [
                    'batch' => $batch,
                    'purchase_quantity' => $purchaseQty,
                    'sold_quantity' => $soldQty,
                    'current_stock' => max(0, $currentStock),
                    'avg_purchase_price' => $avgPurchasePrice,
                    'avg_sale_price' => $avgSalePrice,
                    'profit_per_unit' => $avgSalePrice - $avgPurchasePrice,
                    'total_profit' => $soldQty * ($avgSalePrice - $avgPurchasePrice),
                    'stock_value' => $currentStock * $avgPurchasePrice,
                ];
            });
    }

    /**
     * Get all batch-wise stock across all variants.
     */
    public function getAllBatchWiseStock(): Collection
    {
        return ProductBatch::with(['variant.product.baseUnit'])
            ->withSum(['stockMovements as total_in' => fn($q) => $q->where('direction', 'in')], 'quantity')
            ->withSum(['stockMovements as total_out' => fn($q) => $q->where('direction', 'out')], 'quantity')
            ->orderBy('expiry_date')
            ->get()
            ->map(fn ($batch) => [
                'batch' => $batch,
                'variant' => $batch->variant,
                'product' => $batch->variant?->product,
                'current_stock' => (float) ($batch->total_in - $batch->total_out),
                'initial_quantity' => (float) $batch->initial_quantity,
                'days_until_expiry' => $batch->days_until_expiry,
                'is_expired' => $batch->is_expired,
                'is_expiring_soon' => $batch->is_expiring_soon,
            ]);
    }
    /**
     * Add stock from a purchase.
     */
    public function addPurchaseStock(
        int $variantId,
        float $quantity,
        int $unitId,
        int $purchaseItemId,
        ?int $batchId = null
    ): StockMovement {
        $baseQty = $this->convertToBaseUnit($variantId, $unitId, $quantity);

        return StockMovement::create([
            'product_variant_id' => $variantId,
            'type' => 'purchase',
            'direction' => 'in',
            'quantity' => $baseQty,
            'unit_id' => $unitId,
            'original_quantity' => $quantity,
            'reference_type' => 'purchase_item',
            'reference_id' => $purchaseItemId,
            'batch_id' => $batchId,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Deduct stock from a sale.
     *
     * @throws \RuntimeException if insufficient stock
     */
    public function deductSaleStock(
        int $variantId,
        float $quantity,
        int $unitId,
        int $saleItemId
    ): StockMovement {
        $baseQty = $this->convertToBaseUnit($variantId, $unitId, $quantity);

        if (! $this->hasStock($variantId, $baseQty)) {
            throw new \RuntimeException(__('Insufficient stock for this sale.'));
        }

        return StockMovement::create([
            'product_variant_id' => $variantId,
            'type' => 'sale',
            'direction' => 'out',
            'quantity' => $baseQty,
            'unit_id' => $unitId,
            'original_quantity' => $quantity,
            'reference_type' => 'sale_item',
            'reference_id' => $saleItemId,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Reserve stock for an online order.
     */
    public function reserveStock(int $variantId, float $quantity): void
    {
        $variant = ProductVariant::findOrFail($variantId);
        $available = $this->getAvailableStock($variantId);

        if ($quantity > $available) {
            throw new \RuntimeException(__('Insufficient available stock to reserve.'));
        }

        $variant->increment('reserved_stock', $quantity);
    }

    /**
     * Release reserved stock (order cancelled/delivered).
     */
    public function releaseReservedStock(int $variantId, float $quantity): void
    {
        $variant = ProductVariant::findOrFail($variantId);
        $release = min($quantity, (float) $variant->reserved_stock);
        $variant->decrement('reserved_stock', $release);
    }

    /**
     * Get current stock of a variant (in base unit).
     * Current Stock = SUM(in) - SUM(out)
     */
    public function getCurrentStock(int $variantId): float
    {
        $in = StockMovement::forVariant($variantId)->stockIn()->sum('quantity');
        $out = StockMovement::forVariant($variantId)->stockOut()->sum('quantity');

        return (float) ($in - $out);
    }

    /**
     * Get available stock = total - reserved.
     */
    public function getAvailableStock(int $variantId): float
    {
        $total = $this->getCurrentStock($variantId);
        $reserved = (float) ProductVariant::where('id', $variantId)->value('reserved_stock');

        return max(0, $total - $reserved);
    }

    /**
     * Check if variant has enough stock.
     */
    public function hasStock(int $variantId, float $requiredQty): bool
    {
        return $this->getAvailableStock($variantId) >= $requiredQty;
    }

    /**
     * Get all low stock variants (stock <= product.min_stock).
     */
    public function getLowStockItems(): Collection
    {
        return ProductVariant::select('product_variants.*')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->whereRaw('(
                SELECT COALESCE(SUM(CASE WHEN direction = "in" THEN quantity ELSE -quantity END), 0)
                FROM stock_movements
                WHERE product_variant_id = product_variants.id
            ) <= products.min_stock')
            ->where('products.is_active', true)
            ->where('product_variants.is_active', true)
            ->with(['product.baseUnit', 'product.category'])
            ->get();
    }

    /**
     * Get expiring batches within N days.
     */
    public function getExpiringBatches(int $days = 30): Collection
    {
        return ProductBatch::expiringSoon($days)
            ->with(['variant.product'])
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Create a stock adjustment + corresponding stock movement.
     */
    public function createAdjustment(
        int $variantId,
        string $type,
        float $quantity,
        string $reason,
        ?string $note = null
    ): StockAdjustment {
        return DB::transaction(function () use ($variantId, $type, $quantity, $reason, $note) {
            // Validate subtraction doesn't cause negative stock
            if ($type === 'subtraction') {
                $current = $this->getCurrentStock($variantId);
                if ($quantity > $current) {
                    throw new \RuntimeException(__('Adjustment would cause negative stock. Current stock: :stock', ['stock' => $current]));
                }
            }

            $adjustment = StockAdjustment::create([
                'product_variant_id' => $variantId,
                'type' => $type,
                'quantity' => $quantity,
                'reason' => $reason,
                'note' => $note,
                'created_by' => Auth::id(),
            ]);

            StockMovement::create([
                'product_variant_id' => $variantId,
                'type' => 'adjustment',
                'direction' => $type === 'addition' ? 'in' : 'out',
                'quantity' => $quantity,
                'reference_type' => 'stock_adjustment',
                'reference_id' => $adjustment->id,
                'note' => "Adjustment: {$reason}" . ($note ? " — {$note}" : ''),
                'created_by' => Auth::id(),
            ]);

            return $adjustment;
        });
    }

    /**
     * Get stock value for a variant (purchase price × current stock).
     */
    public function getStockValue(int $variantId): float
    {
        $stock = $this->getCurrentStock($variantId);
        $variant = ProductVariant::findOrFail($variantId);

        return $stock * (float) $variant->purchase_price;
    }

    /**
     * Get total stock value across all variants.
     */
    public function getTotalStockValue(): float
    {
        return ProductVariant::where('is_active', true)
            ->get()
            ->sum(fn ($v) => $this->getStockValue($v->id));
    }

    /**
     * Convert a quantity to base unit, using the unit conversion service.
     */
    private function convertToBaseUnit(int $variantId, int $unitId, float $quantity): float
    {
        $variant = ProductVariant::with('product')->findOrFail($variantId);
        $productId = $variant->product_id;

        return app(UnitConversionService::class)->toBaseUnit($productId, $unitId, $quantity);
    }
}
