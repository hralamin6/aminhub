<?php

use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;
use Livewire\Attributes\On;

new
#[Title('Shopping Cart')]
#[Layout('layouts.shop')]
class extends Component
{
    use Toast;

    public function mount(): void
    {
    }

    #[Computed]
    public function cartItems()
    {
        return app(\App\Services\CartService::class)->getItems();
    }

    #[Computed]
    public function cartTotal()
    {
        return app(\App\Services\CartService::class)->getTotal();
    }

    public function updateQuantity(string $cartKey, float $qty): void
    {
        app(\App\Services\CartService::class)->update($cartKey, $qty);
        $this->dispatch('cart-updated');
    }
    
    public function increment(string $cartKey, float $qty): void
    {
        $this->updateQuantity($cartKey, $qty + 1);
    }
    
    public function decrement(string $cartKey, float $qty): void
    {
        if ($qty > 1) {
            $this->updateQuantity($cartKey, $qty - 1);
        }
    }

    public function removeItem(string $cartKey): void
    {
        app(\App\Services\CartService::class)->remove($cartKey);
        $this->dispatch('cart-updated');
        $this->info('Item removed', position: 'toast-bottom');
    }

    public function clearCart(): void
    {
        app(\App\Services\CartService::class)->clear();
        $this->dispatch('cart-updated');
        $this->success('Cart cleared', position: 'toast-bottom');
    }
    
    #[On('cart-updated')]
    public function refreshCart()
    {
        // just to trigger re-render
    }
};
