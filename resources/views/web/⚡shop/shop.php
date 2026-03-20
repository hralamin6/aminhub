<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Shop')]
#[Layout('layouts.shop')]
class extends Component
{
    use WithPagination, Toast;

    public string $slug = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public ?int $category = null;

    #[Url]
    public ?int $brand = null;

    #[Url(as: 'sort')]
    public string $sortBy = 'newest';

    #[Url(as: 'min')]
    public ?float $minPrice = null;

    #[Url(as: 'max')]
    public ?float $maxPrice = null;

    public bool $showFilters = false;

    public function mount(string $slug = ''): void
    {
        $this->slug = $slug;
        if ($slug) {
            $cat = Category::where('slug', $slug)->where('is_active', true)->first();
            if ($cat) {
                $this->category = $cat->id;
            }
        }
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['products' => fn ($q) => $q->active()])
            ->with(['children' => fn ($q) => $q->where('is_active', true)->withCount(['products' => fn ($q2) => $q2->active()])])
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->active()])
            ->whereHas('products', fn ($q) => $q->active())
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function selectedCategory()
    {
        return $this->category ? Category::find($this->category) : null;
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->active()
            ->ecommerce()
            ->with(['media', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('retail_price'), 'category', 'brand', 'baseUnit'])
            ->when($this->search, function ($q) {
                $s = $this->search;
                $q->where(fn ($sq) =>
                    $sq->where('name', 'like', "%{$s}%")
                       ->orWhere('sku', 'like', "%{$s}%")
                       ->orWhere('description', 'like', "%{$s}%")
                       ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$s}%"))
                );
            })
            ->when($this->category, function ($q) {
                // Include child categories
                $categoryIds = Category::where('id', $this->category)
                    ->orWhere('parent_id', $this->category)
                    ->pluck('id');
                $q->whereIn('category_id', $categoryIds);
            })
            ->when($this->brand, fn ($q) => $q->where('brand_id', $this->brand))
            ->when($this->minPrice, fn ($q) =>
                $q->whereHas('variants', fn ($vq) => $vq->where('retail_price', '>=', $this->minPrice)))
            ->when($this->maxPrice, fn ($q) =>
                $q->whereHas('variants', fn ($vq) => $vq->where('retail_price', '<=', $this->maxPrice)))
            ->when($this->sortBy === 'newest', fn ($q) => $q->latest())
            ->when($this->sortBy === 'price_asc', fn ($q) =>
                $q->orderByRaw('(SELECT MIN(retail_price) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_active = 1) ASC'))
            ->when($this->sortBy === 'price_desc', fn ($q) =>
                $q->orderByRaw('(SELECT MIN(retail_price) FROM product_variants WHERE product_variants.product_id = products.id AND product_variants.is_active = 1) DESC'))
            ->when($this->sortBy === 'name_asc', fn ($q) => $q->orderBy('name'))
            ->when($this->sortBy === 'popular', fn ($q) => $q->where('is_featured', true)->latest())
            ->paginate(24);
    }

    public function setCategory(?int $id): void
    {
        $this->category = $id;
        $this->resetPage();
    }

    public function setBrand(?int $id): void
    {
        $this->brand = $id;
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category', 'brand', 'minPrice', 'maxPrice', 'sortBy']);
        $this->sortBy = 'newest';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function addToCart(int $variantId): void
    {
        $variant = ProductVariant::with('product.productUnits')->findOrFail($variantId);
        $saleUnit = $variant->product->productUnits->where('is_sale_unit', true)->first();
        $defaultUnitId = $saleUnit?->unit_id ?? $variant->product->productUnits->first()?->unit_id ?? 1;

        app(\App\Services\CartService::class)->add($variantId, 1, $defaultUnitId);
        $this->dispatch('cart-updated');
        $this->success(__('Added to cart'), position: 'toast-bottom');
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        $count = 0;
        if ($this->category) $count++;
        if ($this->brand) $count++;
        if ($this->minPrice) $count++;
        if ($this->maxPrice) $count++;
        if ($this->search) $count++;
        return $count;
    }
};
