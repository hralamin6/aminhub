<?php

use App\Models\Category;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Services\UnitConversionService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('POS')]
#[Layout('layouts.pos')]
class extends Component
{
    use Toast;

    // Search & filter
    public string $search = '';
    public ?int $categoryFilter = null;

    // Cart
    public array $cart = [];

    // Checkout
    public string $customer_name = '';
    public string $customer_phone = '';
    public ?int $customer_id = null;
    public string $discount_type = 'flat';
    public float $discount_value = 0;
    public string $payment_method = 'cash';
    public float $paid_amount = 0;
    public string $note = '';

    // Held sales
    public array $heldSales = [];
    public bool $showHeldSales = false;

    // Last sale for receipt
    public ?int $lastSaleId = null;
    public bool $showReceipt = false;

    public function mount(): void
    {
        $this->authorize('pos.access');
    }

    #[Computed]
    public function products()
    {
        return ProductVariant::query()
            ->with(['product.media', 'product.category'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->when($this->search, fn ($q, $s) => $q->where(fn ($sq) =>
                $sq->where('sku', 'like', "%{$s}%")
                    ->orWhere('barcode', $s)
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$s}%"))
            ))
            ->when($this->categoryFilter, fn ($q, $id) =>
                $q->whereHas('product', fn ($pq) => $pq->where('category_id', $id)))
            ->orderBy('product_id')
            ->limit(24)
            ->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::active()->whereNull('parent_id')
            ->orderBy('name')->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();
    }

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->cart)->sum(fn ($item) => (float) $item['quantity'] * (float) $item['unit_price'] - (float) $item['discount']);
    }

    #[Computed]
    public function discountAmount(): float
    {
        if ($this->discount_type === 'percent') {
            return round($this->subtotal * $this->discount_value / 100, 2);
        }
        return (float) $this->discount_value;
    }

    #[Computed]
    public function grandTotal(): float
    {
        return max(0, $this->subtotal - $this->discountAmount);
    }

    #[Computed]
    public function changeAmount(): float
    {
        return max(0, $this->paid_amount - $this->grandTotal);
    }

    #[Computed]
    public function dueAmount(): float
    {
        return max(0, $this->grandTotal - $this->paid_amount);
    }

    // ─── Cart ────────────────────────────────────────

    public function addToCart(int $variantId): void
    {
        $variant = ProductVariant::with('product')->findOrFail($variantId);

        // Check if already in cart
        foreach ($this->cart as $i => $item) {
            if ($item['variant_id'] == $variantId) {
                $this->cart[$i]['quantity']++;
                return;
            }
        }

        // Get default unit
        $defaultUnit = null;
        if ($variant->product->productUnits) {
            $saleUnit = $variant->product->productUnits->where('is_sale_unit', true)->first();
            $defaultUnit = $saleUnit ?? $variant->product->productUnits->first();
        }

        $this->cart[] = [
            'variant_id' => $variant->id,
            'name' => $variant->product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_id' => $defaultUnit?->unit_id ?? null,
            'unit_name' => $defaultUnit?->unit?->short_name ?? 'pc',
            'unit_price' => (float) $variant->retail_price,
            'discount' => 0,
            'available' => $variant->available_stock,
        ];
    }

    public function updateQty(int $index, float $qty): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity'] = max(0.01, $qty);
        }
    }

    public function incrementQty(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity']++;
        }
    }

    public function decrementQty(int $index): void
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['quantity'] > 1) {
            $this->cart[$index]['quantity']--;
        }
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->customer_id = null;
        $this->discount_type = 'flat';
        $this->discount_value = 0;
        $this->paid_amount = 0;
        $this->note = '';
    }

    // ─── Hold/Resume ─────────────────────────────────

    public function holdSale(): void
    {
        if (empty($this->cart)) {
            $this->error(__('Cart is empty.'));
            return;
        }

        $this->heldSales[] = [
            'cart' => $this->cart,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'note' => $this->note,
            'held_at' => now()->format('H:i'),
        ];

        $this->clearCart();
        $this->success(__('Sale held. You can resume it later.'), position: 'toast-bottom');
    }

    public function resumeSale(int $index): void
    {
        if (! isset($this->heldSales[$index])) return;

        // Hold current cart if not empty
        if (! empty($this->cart)) {
            $this->holdSale();
        }

        $held = $this->heldSales[$index];
        $this->cart = $held['cart'];
        $this->customer_name = $held['customer_name'];
        $this->customer_phone = $held['customer_phone'];
        $this->discount_type = $held['discount_type'];
        $this->discount_value = $held['discount_value'];
        $this->note = $held['note'];

        unset($this->heldSales[$index]);
        $this->heldSales = array_values($this->heldSales);
        $this->showHeldSales = false;
    }

    // ─── Checkout ────────────────────────────────────

    public function checkout(): void
    {
        $this->authorize('sales.create');

        if (empty($this->cart)) {
            $this->error(__('Cart is empty.'));
            return;
        }

        // Default paid_amount to grand_total if cash and 0
        if ($this->paid_amount <= 0) {
            $this->paid_amount = $this->grandTotal;
        }

        DB::transaction(function () {
            // Resolve or create customer
            $customerId = $this->customer_id;
            if (! $customerId && $this->customer_phone) {
                $customer = Customer::firstOrCreate(
                    ['phone' => $this->customer_phone],
                    ['name' => $this->customer_name ?: __('Walk-in'), 'type' => 'walk_in']
                );
                $customerId = $customer->id;
            }

            $paidAmt = $this->paid_amount;
            $grandTotal = $this->grandTotal;
            $dueAmt = max(0, $grandTotal - $paidAmt);
            $changeAmt = max(0, $paidAmt - $grandTotal);

            $paymentStatus = match (true) {
                $dueAmt <= 0 => 'paid',
                $paidAmt > 0 => 'partial',
                default => 'unpaid',
            };

            $sale = Sale::create([
                'sale_type' => 'pos',
                'customer_id' => $customerId,
                'customer_name' => $this->customer_name ?: null,
                'customer_phone' => $this->customer_phone ?: null,
                'subtotal' => $this->subtotal,
                'discount_type' => $this->discount_type,
                'discount_value' => $this->discount_value,
                'discount_amount' => $this->discountAmount,
                'tax' => 0,
                'grand_total' => $grandTotal,
                'paid_amount' => min($paidAmt, $grandTotal),
                'change_amount' => $changeAmt,
                'due_amount' => $dueAmt,
                'payment_method' => $this->payment_method,
                'payment_status' => $paymentStatus,
                'status' => 'completed',
                'note' => $this->note ?: null,
                'sold_by' => auth()->id(),
            ]);

            $converter = app(UnitConversionService::class);

            foreach ($this->cart as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $itemDiscount = (float) $item['discount'];
                $lineTotal = ($qty * $unitPrice) - $itemDiscount;

                // Convert to base unit
                $baseQty = $qty;
                $variant = ProductVariant::with('product')->find($item['variant_id']);
                if ($item['unit_id']) {
                    try {
                        $baseQty = $converter->toBaseUnit($variant->product_id, (int) $item['unit_id'], $qty);
                    } catch (\Exception $e) {
                        // 1:1 fallback
                    }
                }

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $qty,
                    'unit_id' => $item['unit_id'] ?? Unit::first()?->id ?? 1,
                    'base_quantity' => $baseQty,
                    'unit_price' => $unitPrice,
                    'discount' => $itemDiscount,
                    'subtotal' => $lineTotal,
                ]);

                // Stock movement — sale (out)
                StockMovement::create([
                    'product_variant_id' => $item['variant_id'],
                    'type' => 'sale',
                    'direction' => 'out',
                    'quantity' => $baseQty,
                    'unit_id' => $item['unit_id'] ?? null,
                    'original_quantity' => $qty,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'created_by' => auth()->id(),
                ]);
            }

            // Update customer totals
            if ($customerId) {
                $customer = Customer::find($customerId);
                if ($customer) {
                    $customer->increment('total_purchase', $grandTotal);
                    $customer->increment('total_due', $dueAmt);
                }
            }

            $this->lastSaleId = $sale->id;
        });

        $this->clearCart();
        $this->showReceipt = true;
        $this->success(__('Sale completed!'), position: 'toast-bottom');
    }

    // ─── Receipt ─────────────────────────────────────

    #[Computed]
    public function lastSale()
    {
        if (! $this->lastSaleId) return null;
        return Sale::with(['items.variant.product', 'items.unit', 'seller'])->find($this->lastSaleId);
    }
};
