<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Inventory')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 25;
    public ?int $categoryFilter = null;
    public ?int $brandFilter = null;
    public string $stockFilter = ''; // low, out, all
    public string $expiryFilter = ''; // expiring, expired

    // Quick adjustment modal
    public bool $showAdjustment = false;
    public ?int $adjustVariantId = null;
    public string $adjustType = 'addition';
    public float $adjustQty = 0;
    public string $adjustReason = '';
    public string $adjustNote = '';

    // Movement detail modal
    public bool $showMovements = false;
    public ?int $detailVariantId = null;

    public function mount(): void
    {
        $this->authorize('inventory.view');
    }

    #[Computed]
    public function stats()
    {
        $inventory = app(InventoryService::class);

        $totalProducts = ProductVariant::whereHas('product', fn ($q) => $q->where('is_active', true))->where('is_active', true)->count();
        $lowStock = $inventory->getLowStockItems()->count();
        $expiringSoon = ProductBatch::expiringSoon(30)->count();

        // Rough total stock value calculation (cached per request)
        $totalValue = 0;
        ProductVariant::where('is_active', true)->chunk(100, function ($variants) use (&$totalValue) {
            foreach ($variants as $v) {
                $in = StockMovement::forVariant($v->id)->stockIn()->sum('quantity');
                $out = StockMovement::forVariant($v->id)->stockOut()->sum('quantity');
                $stock = (float) ($in - $out);
                $totalValue += $stock * (float) $v->purchase_price;
            }
        });

        return [
            'total_products' => $totalProducts,
            'total_value' => $totalValue,
            'low_stock' => $lowStock,
            'expiring_soon' => $expiringSoon,
        ];
    }

    #[Computed]
    public function variants()
    {
        $query = ProductVariant::query()
            ->with(['product.category', 'product.brand', 'product.baseUnit'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true);

        // Search
        if ($this->search) {
            $s = "%{$this->search}%";
            $query->where(function ($q) use ($s) {
                $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', $s)->orWhere('sku', 'like', $s))
                    ->orWhere('name', 'like', $s)
                    ->orWhere('sku', 'like', $s);
            });
        }

        // Category
        if ($this->categoryFilter) {
            $query->whereHas('product', fn ($q) => $q->where('category_id', $this->categoryFilter));
        }

        // Brand
        if ($this->brandFilter) {
            $query->whereHas('product', fn ($q) => $q->where('brand_id', $this->brandFilter));
        }

        return $query->orderBy('product_id')->paginate($this->perPage);
    }

    #[Computed]
    public function categoryOptions()
    {
        return Category::active()->orderBy('name')->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->prepend(['id' => null, 'name' => __('All Categories')])
            ->toArray();
    }

    #[Computed]
    public function brandOptions()
    {
        return Brand::active()->orderBy('name')->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->prepend(['id' => null, 'name' => __('All Brands')])
            ->toArray();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }
    public function updatingBrandFilter(): void { $this->resetPage(); }

    /**
     * Compute stock for a variant (used in view).
     */
    public function getStock(int $variantId): array
    {
        $in = StockMovement::forVariant($variantId)->stockIn()->sum('quantity');
        $out = StockMovement::forVariant($variantId)->stockOut()->sum('quantity');
        $total = (float) ($in - $out);
        $reserved = (float) ProductVariant::where('id', $variantId)->value('reserved_stock');
        $available = max(0, $total - $reserved);

        return [
            'total' => $total,
            'reserved' => $reserved,
            'available' => $available,
        ];
    }

    /**
     * Get stock status label.
     */
    public function getStockStatus(int $variantId, float $minStock): string
    {
        $stock = $this->getStock($variantId);
        if ($stock['total'] <= 0) return 'out';
        if ($stock['total'] <= $minStock) return 'low';
        return 'ok';
    }

    // ─── Quick Adjustment ────────────────────────────

    public function openAdjustment(int $variantId): void
    {
        $this->authorize('inventory.adjust');
        $this->adjustVariantId = $variantId;
        $this->adjustType = 'addition';
        $this->adjustQty = 0;
        $this->adjustReason = '';
        $this->adjustNote = '';
        $this->showAdjustment = true;
    }

    public function saveAdjustment(): void
    {
        $this->authorize('inventory.adjust');

        $this->validate([
            'adjustVariantId' => 'required|exists:product_variants,id',
            'adjustType' => 'required|in:addition,subtraction',
            'adjustQty' => 'required|numeric|min:0.0001',
            'adjustReason' => 'required|string|max:255',
            'adjustNote' => 'nullable|string|max:1000',
        ]);

        try {
            app(InventoryService::class)->createAdjustment(
                $this->adjustVariantId,
                $this->adjustType,
                $this->adjustQty,
                $this->adjustReason,
                $this->adjustNote ?: null,
            );

            $this->showAdjustment = false;
            $this->success(__('Stock adjustment saved.'), position: 'toast-bottom');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    // ─── Movement Detail ─────────────────────────────

    public function showVariantMovements(int $variantId): void
    {
        $this->detailVariantId = $variantId;
        $this->showMovements = true;
    }

    #[Computed]
    public function recentMovements()
    {
        if (! $this->detailVariantId) return collect();

        return StockMovement::forVariant($this->detailVariantId)
            ->with(['unit', 'creator'])
            ->latest()
            ->limit(20)
            ->get();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'brandFilter', 'stockFilter', 'expiryFilter']);
        $this->resetPage();
    }
};
