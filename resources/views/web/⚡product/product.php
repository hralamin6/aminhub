<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Product Detail')]
#[Layout('layouts.shop')]
class extends Component
{
    use Toast;

    public string $slug;
    public ?int $selectedVariantId = null;
    public int $quantity = 1;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
        $product = Product::where('slug', $slug)->firstOrFail();
        
        $this->selectedVariantId = $product->variants->first()?->id;
    }

    #[Computed]
    public function product()
    {
        return Product::with(['variants', 'category', 'brand', 'media', 'productUnits.unit'])
            ->where('slug', $this->slug)
            ->firstOrFail();
    }

    #[Computed]
    public function selectedVariant()
    {
        if (!$this->selectedVariantId) return null;
        return ProductVariant::find($this->selectedVariantId);
    }
    
    #[Computed]
    public function relatedProducts()
    {
        return Product::with(['variants', 'media'])
            ->where('category_id', $this->product->category_id)
            ->where('id', '!=', $this->product->id)
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(4)
            ->get();
    }

    public function addToCart(): void
    {
        if (!$this->selectedVariantId) return;

        $saleUnit = $this->product->productUnits->where('is_sale_unit', true)->first();
        $defaultUnitId = $saleUnit?->unit_id ?? $this->product->productUnits->first()?->unit_id ?? 1;

        app(\App\Services\CartService::class)->add($this->selectedVariantId, max(1, $this->quantity), $defaultUnitId);
        $this->dispatch('cart-updated');
        
        $this->success('Added to cart', position: 'toast-bottom');
        $this->quantity = 1;
    }
    
    public function increment(): void
    {
        $this->quantity++;
    }
    
    public function decrement(): void
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }
};
