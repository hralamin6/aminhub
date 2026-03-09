<?php

namespace App\Services;

use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
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
