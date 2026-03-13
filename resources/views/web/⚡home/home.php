<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Home')]
#[Layout('layouts.shop')]
class extends Component
{
    use Toast;

    #[Computed]
    public function featuredProducts()
    {
        return Product::active()->ecommerce()->featured()
            ->with(['media', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('retail_price'), 'category', 'brand', 'baseUnit'])
            ->take(8)
            ->get();
    }

    #[Computed]
    public function newArrivals()
    {
        return Product::active()->ecommerce()
            ->with(['media', 'variants' => fn ($q) => $q->where('is_active', true)->orderBy('retail_price'), 'category', 'brand'])
            ->latest()
            ->take(8)
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)
            ->whereNull('parent_id')
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function brands()
    {
        return Brand::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->take(12)
            ->get();
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
};
