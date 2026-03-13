<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private string $sessionKey = 'ecommerce_cart';

    public function add(int $variantId, float $qty, int $unitId): void
    {
        $cart = $this->getCart();

        // Check if item already exists with the same unit
        $key = $variantId . '_' . $unitId;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $qty;
        } else {
            $variant = ProductVariant::with(['product', 'product.productUnits.unit'])->findOrFail($variantId);
            $unitPrice = $variant->retail_price; // Or fetch unit specific pricing logic if exists
            
            $cart[$key] = [
                'variant_id' => $variantId,
                'unit_id' => $unitId,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'image' => $variant->product->getFirstMediaUrl('product_image', 'thumb'),
            ];
        }

        $this->saveCart($cart);
    }

    public function update(string $cartKey, float $qty): void
    {
        $cart = $this->getCart();
        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] = max(0.01, $qty); // ensure at least 0.01
            $this->saveCart($cart);
        }
    }

    public function remove(string $cartKey): void
    {
        $cart = $this->getCart();
        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            $this->saveCart($cart);
        }
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    public function getItems(): Collection
    {
        return collect($this->getCart());
    }

    public function getTotal(): float
    {
        return $this->getItems()->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });
    }

    public function getItemCount(): int
    {
        return count($this->getCart());
    }

    public function validateStock(): array
    {
        // Future logic: check out-of-stock items comparing requested quantity vs $variant->available_stock
        return [];
    }

    private function getCart(): array
    {
        return Session::get($this->sessionKey, []);
    }

    private function saveCart(array $cart): void
    {
        Session::put($this->sessionKey, $cart);
    }
}
