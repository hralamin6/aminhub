<?php

use App\Models\Category;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Services\UnitConversionService;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('POS')]
#[Layout('layouts.app')]
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
    public string $customer_search = '';
    public string $discount_type = 'flat';
    public float $discount_value = 0;
    public string $payment_method = 'cash';
    public float $paid_amount = 0;
    public string $note = '';

    // Mobile
    public bool $showMobileCart = false;

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
    public function customers()
    {
        if (strlen($this->customer_search) < 2) return [];

        return User::whereHas('detail')
            ->where(fn ($q) => $q->where('name', 'like', "%{$this->customer_search}%")
                ->orWhereHas('detail', fn ($dq) => $dq->where('phone', 'like', "%{$this->customer_search}%")))
            ->limit(10)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->detail->phone ?? '',
                'display' => $u->name . ($u->detail->phone ? ' - ' . $u->detail->phone : '')
            ])
            ->toArray();
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = User::with('detail')->find($customerId);
        if ($customer) {
            $this->customer_id = $customer->id;
            $this->customer_name = $customer->name;
            $this->customer_phone = $customer->detail->phone ?? '';
            $this->customer_search = '';
        }
    }

    public function clearCustomer(): void
    {
        $this->customer_id = null;
        $this->customer_name = '';
        $this->customer_phone = '';
        $this->customer_search = '';
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
        $variant = ProductVariant::with(['product.productUnits.unit', 'product.baseUnit'])->findOrFail($variantId);

        // Check if already in cart
        foreach ($this->cart as $i => $item) {
            if ($item['variant_id'] == $variantId) {
                $this->cart[$i]['quantity'] = round($this->cart[$i]['quantity'] + 1, 3);
                return;
            }
        }

        $product = $variant->product;

        // Default to base unit and base (variant) price
        $baseUnit = $product->baseUnit;
        $unitId = $baseUnit?->id;
        $unitName = $baseUnit?->short_name ?? $baseUnit?->name ?? 'pc';
        $unitPrice = (float) $variant->retail_price; // This is the base price per base unit
        $conversionRate = 1;

        // Get available units - always include base unit first, then product units
        $availableUnits = [];
        $productUnits = $product->productUnits;

        // Always add base unit as first option
        if ($baseUnit) {
            $availableUnits[] = [
                'unit_id' => $baseUnit->id,
                'unit_name' => $baseUnit->short_name ?? $baseUnit->name,
                'conversion_rate' => 1,
                'is_sale_unit' => true,
            ];
        }

        // Add product units (excluding base unit to avoid duplicates)
        if ($productUnits && $productUnits->isNotEmpty()) {
            foreach ($productUnits as $pu) {
                $unit = $pu->unit;
                // Skip if this is the base unit (already added)
                if ($unit && $unit->id !== $baseUnit?->id) {
                    $availableUnits[] = [
                        'unit_id' => $pu->unit_id,
                        'unit_name' => $unit->short_name ?? $unit->name,
                        'conversion_rate' => (float) $pu->conversion_rate,
                        'is_sale_unit' => $pu->is_sale_unit,
                    ];
                }
            }
        }

        $this->cart[] = [
            'variant_id' => $variant->id,
            'name' => $product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_id' => $unitId,
            'unit_name' => $unitName,
            'unit_price' => $unitPrice,
            'base_price' => $unitPrice,
            'conversion_rate' => $conversionRate,
            'available_units' => $availableUnits,
            'batch_id' => null,
            'batch_number' => null,
            'available_batches' => $this->getAvailableBatches($variant->id),
            'discount' => 0,
            'available' => $variant->available_stock,
        ];
    }

    public function switchUnit(int $index, int $newUnitId): void
    {
        if (!isset($this->cart[$index])) return;

        $item = &$this->cart[$index];
        $availableUnits = $item['available_units'] ?? [];

        // Find the selected unit
        $selectedUnit = null;
        foreach ($availableUnits as $unit) {
            if ($unit['unit_id'] == $newUnitId) {
                $selectedUnit = $unit;
                break;
            }
        }

        if (!$selectedUnit) return;

        $oldConversionRate = (float) ($item['conversion_rate'] ?? 1);
        $newConversionRate = (float) $selectedUnit['conversion_rate'];
        $basePrice = (float) ($item['base_price'] ?? $item['unit_price'] * $oldConversionRate);

        $item['unit_id'] = $newUnitId;
        $item['unit_name'] = $selectedUnit['unit_name'];
        $item['conversion_rate'] = $newConversionRate;
        $item['unit_price'] = round($basePrice * $newConversionRate, 3);
        $item['base_price'] = $basePrice;
    }

    public function getAvailableBatches(int $variantId): array
    {
        return ProductBatch::where('product_variant_id', $variantId)
            ->whereHas('stockMovements', fn($q) => $q->where('direction', 'in'))
            ->withSum(['stockMovements as total_in' => fn($q) => $q->where('direction', 'in')], 'quantity')
            ->withSum(['stockMovements as total_out' => fn($q) => $q->where('direction', 'out')], 'quantity')
            ->orderBy('created_at')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'current_stock' => (float) ($b->total_in - $b->total_out),
                'expiry_date' => $b->expiry_date?->format('d M Y'),
                'is_expired' => $b->is_expired,
            ])
            ->filter(fn($b) => $b['current_stock'] > 0)
            ->values()
            ->toArray();
    }

    public function selectBatch(int $index, ?int $batchId): void
    {
        if (!isset($this->cart[$index])) return;

        $item = &$this->cart[$index];

        if ($batchId === null || $batchId === 0) {
            $item['batch_id'] = null;
            $item['batch_number'] = null;
            return;
        }

        $batch = ProductBatch::find($batchId);
        if ($batch) {
            $item['batch_id'] = $batch->id;
            $item['batch_number'] = $batch->batch_number;
        }
    }

    public function updateQty(int $index, float $qty): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity'] = round(max(0.001, $qty), 3);
        }
    }

    public function updatePrice(int $index, float $price): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['unit_price'] = round(max(0, $price), 3);
        }
    }

    public function updateTotalPrice(int $index, float $totalPrice): void
    {
        if (isset($this->cart[$index])) {
            $unitPrice = (float) $this->cart[$index]['unit_price'];
            if ($unitPrice > 0) {
                // Calculate quantity based on total price and unit price
                $newQty = $totalPrice / $unitPrice;
                $this->cart[$index]['quantity'] = round(max(0.001, $newQty), 3);
            }
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
        $this->customer_search = '';
        $this->discount_type = 'flat';
        $this->discount_value = 0;
        $this->paid_amount = 0;
        $this->note = '';
        $this->showMobileCart = false;
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
                $customer = User::whereHas('detail', fn($q) => $q->where('phone', $this->customer_phone))->first();
                if (! $customer) {
                    $customer = User::create([
                        'name' => $this->customer_name ?: __('Walk-in'),
                        'email' => $this->customer_phone . '@walkin.local',
                        'password' => bcrypt('password')
                    ]);
                    $customer->assignRole('customer');
                    $customer->detail()->create([
                        'phone' => $this->customer_phone,
                        'is_active' => true,
                    ]);
                }
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

                // Stock movement — sale (out) with batch deduction
                // Use selected batch if specified, otherwise use FIFO
                $inventory = app(InventoryService::class);
                
                if (!empty($item['batch_id'])) {
                    // Deduct from specific batch
                    StockMovement::create([
                        'product_variant_id' => $item['variant_id'],
                        'batch_id' => $item['batch_id'],
                        'type' => 'sale',
                        'direction' => 'out',
                        'quantity' => $baseQty,
                        'unit_id' => $item['unit_id'] ?? null,
                        'original_quantity' => $qty,
                        'reference_type' => 'sale',
                        'reference_id' => $sale->id,
                        'created_by' => auth()->id(),
                    ]);
                } else {
                    // Use FIFO batch deduction
                    $inventory->deductSaleStockWithBatches(
                        $item['variant_id'],
                        $qty,
                        $item['unit_id'] ?? Unit::first()?->id ?? 1,
                        $saleItem->id,
                        $sale->id
                    );
                }
            }

            // Update customer totals
            if ($customerId) {
                $customer = User::with('detail')->find($customerId);
                if ($customer && $customer->detail) {
                    $customer->detail->increment('total_purchase', $grandTotal);
                    $customer->detail->increment('total_due', $dueAmt);
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
