<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\UnitConversionService;
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

    public ?int $subCategoryFilter = null;

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
        // The active category filter: prefer sub-category, fallback to parent
        $activeCategoryId = $this->subCategoryFilter ?? $this->categoryFilter;

        if ($this->search && strlen($this->search) >= 2) {
            // Use Scout/Meilisearch for fuzzy search
            $productIds = Product::search($this->search)
                ->where('is_active', 1)
                ->keys();

            $query = ProductVariant::query()
                ->with(['product.media', 'product.category'])
                ->where('is_active', true)
                ->whereHas('product', function ($q) use ($productIds, $activeCategoryId) {
                    $q->whereIn('id', $productIds);
                    if ($activeCategoryId) {
                        $q->where('category_id', $activeCategoryId);
                    }
                })
                ->orderBy('product_id')
                ->limit(30);
        } else {
            $query = ProductVariant::query()
                ->with(['product.media', 'product.category'])
                ->whereHas('product', fn ($q) => $q->where('is_active', true))
                ->where('is_active', true)
                ->when($activeCategoryId, fn ($q, $id) => $q->whereHas('product', fn ($pq) => $pq->where('category_id', $id)))
                ->orderBy('product_id')
                ->limit(30);
        }

        return $query->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::active()->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->active()->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function activeSubcategories()
    {
        if (! $this->categoryFilter) {
            return collect();
        }

        return Category::active()
            ->where('parent_id', $this->categoryFilter)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function selectCategory(?int $id): void
    {
        $this->categoryFilter = $id;
        $this->subCategoryFilter = null;
        $this->search = '';
    }

    public function selectSubcategory(?int $id): void
    {
        $this->subCategoryFilter = $id;
        $this->search = '';
    }

    #[Computed]
    public function customers()
    {
        if (strlen($this->customer_search) < 2) {
            return [];
        }

        return User::whereHas('detail')
            ->where(fn ($q) => $q->where('name', 'like', "%{$this->customer_search}%")
                ->orWhereHas('detail', fn ($dq) => $dq->where('phone', 'like', "%{$this->customer_search}%")))
            ->limit(10)
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->detail->phone ?? '',
                'display' => $u->name.($u->detail->phone ? ' - '.$u->detail->phone : ''),
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

    #[Computed]
    public function totalCost(): float
    {
        return collect($this->cart)->sum(fn ($item) => (float) $item['quantity'] * (float) ($item['conversion_rate'] ?? 1) * (float) ($item['base_cost'] ?? $item['purchase_price'] ?? 0)
        );
    }

    #[Computed]
    public function totalProfit(): float
    {
        return max(0, $this->grandTotal - $this->totalCost);
    }

    // ─── Cart ────────────────────────────────────────

    public function addToCart(int $variantId): void
    {
        $variant = ProductVariant::with(['product.productUnits.unit', 'product.baseUnit'])->findOrFail($variantId);

        // Prevent adding if no stock at all
        if ($variant->available_stock <= 0) {
            $this->error(__('Product out of stock.'));
            return;
        }

        $product = $variant->product;

        // Check if already in cart (find rows with this variant and no manually selected batch, or same batch)
        foreach ($this->cart as $i => $item) {
            if ($item['variant_id'] == $variantId) {
                $this->incrementQty($i);

                return;
            }
        }

        // Default to base unit
        $baseUnit = $product->baseUnit;
        $unitId = $baseUnit?->id;
        $unitName = $baseUnit?->short_name ?? $baseUnit?->name ?? 'pc';
        $unitPrice = (float) $variant->retail_price;

        // Get available units
        $availableUnits = [];
        if ($baseUnit) {
            $availableUnits[] = [
                'unit_id' => $baseUnit->id,
                'unit_name' => $baseUnit->short_name ?? $baseUnit->name,
                'conversion_rate' => 1,
                'is_sale_unit' => true,
            ];
        }

        foreach ($product->productUnits as $pu) {
            if ($pu->unit && $pu->unit_id !== $baseUnit?->id) {
                $availableUnits[] = [
                    'unit_id' => $pu->unit_id,
                    'unit_name' => $pu->unit->short_name ?? $pu->unit->name,
                    'conversion_rate' => (float) $pu->conversion_rate,
                    'is_sale_unit' => $pu->is_sale_unit,
                ];
            }
        }

        // Auto-select oldest batch
        $availableBatches = $this->getAvailableBatches($variant->id);
        $selectedBatch = $availableBatches[0] ?? null;

        $batchCost = ($selectedBatch['purchase_price'] ?? 0) > 0
            ? (float) $selectedBatch['purchase_price']
            : (float) ($variant->purchase_price ?? 0);

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
            'purchase_price' => $batchCost,
            'base_cost' => $batchCost,
            'conversion_rate' => 1,
            'available_units' => $availableUnits,
            'batch_id' => $selectedBatch['id'] ?? null,
            'batch_number' => $selectedBatch['batch_number'] ?? null,
            'batch_stock' => $selectedBatch['current_stock'] ?? 0,
            'available_batches' => $availableBatches,
            'discount' => 0,
            'available' => $variant->available_stock,
            'price_locked' => false,
        ];
    }

    public function switchUnit(int $index, int $newUnitId): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

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

        if (! $selectedUnit) {
            return;
        }

        $oldConversionRate = (float) ($item['conversion_rate'] ?? 1);
        $newConversionRate = (float) $selectedUnit['conversion_rate'];
        $basePrice = (float) ($item['base_price'] ?? $item['unit_price'] * $oldConversionRate);

        $item['unit_id'] = $newUnitId;
        $item['unit_name'] = $selectedUnit['unit_name'];
        $item['conversion_rate'] = $newConversionRate;
        $item['unit_price'] = round($basePrice * $newConversionRate, 3);
        $item['base_price'] = $basePrice;
    }

    public function getAvailableBatches(int $variantId, ?int $excludeIndex = null): array
    {
        $batches = ProductBatch::where('product_variant_id', $variantId)
            ->withSum(['stockMovements as total_in' => fn($q) => $q->where('direction', 'in')], 'quantity')
            ->withSum(['stockMovements as total_out' => fn($q) => $q->where('direction', 'out')], 'quantity')
            ->orderBy('created_at')
            ->get();

        // Calculate usage from other cart items
        $otherUsage = [];
        foreach ($this->cart as $idx => $item) {
            if ($idx === $excludeIndex) continue;
            if ($item['variant_id'] == $variantId && $item['batch_id']) {
                $itemBaseQty = $item['quantity'] * ($item['conversion_rate'] ?? 1);
                $otherUsage[$item['batch_id']] = ($otherUsage[$item['batch_id']] ?? 0) + $itemBaseQty;
            }
        }

        return $batches->map(fn ($b) => [
                'id' => $b->id,
                'batch_number' => $b->batch_number,
                'current_stock' => (float) ($b->total_in - $b->total_out) - ($otherUsage[$b->id] ?? 0),
                'purchase_price' => (float) ($b->purchase_price ?? 0),
                'expiry_date' => $b->expiry_date?->format('d M Y'),
                'is_expired' => $b->is_expired,
            ])
            ->filter(fn ($b) => $b['current_stock'] > 0)
            ->values()
            ->toArray();
    }

    public function selectBatch(int $index, ?int $batchId): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $item = &$this->cart[$index];
        $variant = ProductVariant::find($item['variant_id']);

        if ($batchId === null || $batchId === 0) {
            $item['batch_id'] = null;
            $item['batch_number'] = null;
            $item['purchase_price'] = (float) ($variant->purchase_price ?? 0);
            $item['base_cost'] = (float) ($variant->purchase_price ?? 0);

            return;
        }

        $batch = ProductBatch::find($batchId);
        if ($batch) {
            $item['batch_id'] = $batch->id;
            $item['batch_number'] = $batch->batch_number;
            // Use batch purchase_price if > 0, otherwise fall back to variant's purchase_price
            $batchCost = ($batch->purchase_price ?? 0) > 0
                ? (float) $batch->purchase_price
                : (float) ($variant->purchase_price ?? 0);
            $item['purchase_price'] = $batchCost;
            $item['base_cost'] = $batchCost;
        }
    }

    public function togglePriceLock(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['price_locked'] = ! ($this->cart[$index]['price_locked'] ?? false);
        }
    }

    public function updatePrice(int $index, mixed $price): void
    {
        if (isset($this->cart[$index])) {
            $price = max(0, (float) ($price ?: 0));
            $this->cart[$index]['unit_price'] = round($price, 3);
        }
    }

    public function updateTotalPrice(int $index, mixed $totalPrice): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $totalPrice = max(0, (float) ($totalPrice ?: 0));
        $item = &$this->cart[$index];
        $qty = (float) $item['quantity'];

        if ($item['price_locked'] ?? false) {
            // Price is locked → adjust quantity
            $unitPrice = (float) $item['unit_price'];
            if ($unitPrice > 0) {
                $item['quantity'] = round(max(0.001, $totalPrice / $unitPrice), 3);
            }
        } else {
            // Price is not locked → adjust unit price
            if ($qty > 0 && $totalPrice > 0) {
                $item['unit_price'] = round($totalPrice / $qty, 3);
            } elseif ($totalPrice === 0.0) {
                $item['unit_price'] = 0;
            }
        }
    }

    public function updateQty(int $index, mixed $qty): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $qty = max(0, (float) ($qty ?: 0));
        if ($qty <= 0) {
            $this->removeFromCart($index);
            return;
        }

        $item = $this->cart[$index];
        $variantId = $item['variant_id'];
        $unitId = (int) ($item['unit_id'] ?: 0);
        $variant = ProductVariant::find($variantId);

        try {
            // Calculate base quantity
            $baseQty = app(UnitConversionService::class)->toBaseUnit($variant->product_id, $unitId, $qty);

            // Get splits (do NOT allow negative/overselling as requested)
            $splits = app(InventoryService::class)->getFIFOSplit($variantId, $baseQty, $item['batch_id'] ?: null, false);

            if (count($splits) > 1) {
                // Determine current state of new items
                $newItems = [];
                foreach ($splits as $split) {
                    $splitItem = $item;
                    $splitItem['batch_id'] = $split['batch_id'];
                    
                    $batchInfo = collect($item['available_batches'])->firstWhere('id', $split['batch_id']);
                    $splitItem['batch_number'] = $batchInfo['batch_number'] ?? null;
                    $splitItem['batch_stock'] = $batchInfo['current_stock'] ?? 0;
                    
                    $splitQtyPart = app(UnitConversionService::class)->fromBaseUnit($variant->product_id, $unitId, $split['quantity']);
                    $splitItem['quantity'] = round($splitQtyPart, 3);
                    
                    $batchCost = $split['unit_cost'] > 0 ? $split['unit_cost'] : (float) ($variant->purchase_price ?? 0);
                    $splitItem['purchase_price'] = $batchCost;
                    $splitItem['base_cost'] = $batchCost;

                    $newItems[] = $splitItem;
                }

                // Replace the original with the new splits
                array_splice($this->cart, $index, 1, $newItems);
            } else {
                // Single batch (completely within or preferred batch found)
                $this->cart[$index]['quantity'] = round($qty, 3);
                
                if ($item['batch_id']) {
                    $batchInfo = collect($item['available_batches'])->firstWhere('id', $item['batch_id']);
                    $this->cart[$index]['batch_stock'] = $batchInfo['current_stock'] ?? 0;
                }
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage(), position: 'toast-bottom');
            // Revert back to original or if adding new, just don't add.
            // Since this is wire:change, the input field will revert on next render.
        }
    }

    public function incrementQty(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->updateQty($index, $this->cart[$index]['quantity'] + 1);
        }
    }

    public function decrementQty(int $index): void
    {
        if (isset($this->cart[$index]) && $this->cart[$index]['quantity'] > 0.001) {
            $newQty = max(0.001, $this->cart[$index]['quantity'] - 1);
            $this->updateQty($index, $newQty);
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
        if (! isset($this->heldSales[$index])) {
            return;
        }

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

        if ($this->paid_amount <= 0) {
            $this->paid_amount = $this->grandTotal;
        }

        try {
            DB::transaction(function () {
                $customerId = $this->customer_id;
                if (! $customerId && $this->customer_phone) {
                    $customer = User::whereHas('detail', fn ($q) => $q->where('phone', $this->customer_phone))->first();
                    if (! $customer) {
                        $customer = User::create([
                            'name' => $this->customer_name ?: __('Walk-in'),
                            'email' => $this->customer_phone.'@walkin.local',
                            'password' => bcrypt('password'),
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
                $totalCost = $this->totalCost;
                $totalProfit = $this->totalProfit;

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
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
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
                $inventory = app(InventoryService::class);

                foreach ($this->cart as $item) {
                    $variantId = $item['variant_id'];
                    $qty = (float) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $itemDiscount = (float) $item['discount'];
                    $totalLineSubtotal = ($qty * $unitPrice) - $itemDiscount;
                    $unitId = $item['unit_id'] ?? Unit::first()?->id ?? 1;

                    $variant = ProductVariant::with('product')->find($variantId);
                    $baseQty = $qty;
                    if ($unitId) {
                        try {
                            $baseQty = $converter->toBaseUnit($variant->product_id, (int) $unitId, $qty);
                        } catch (Exception $e) {
                        }
                    }

                    // Determine splits (RE-VALIDATE STOCK HERE)
                    try {
                        $splits = $inventory->getFIFOSplit($variantId, $baseQty, $item['batch_id'] ?? null, false);
                    } catch (\Exception $e) {
                        // Rollback transaction by re-throwing
                        throw new \RuntimeException($variant->product->name . ': ' . $e->getMessage());
                    }

                    foreach ($splits as $split) {
                        $splitRatio = $baseQty > 0 ? $split['quantity'] / $baseQty : 1;
                        $splitQty = $qty * $splitRatio;
                        $splitDiscount = round($itemDiscount * $splitRatio, 2);
                        $splitSubtotal = round($totalLineSubtotal * $splitRatio, 2);
                        $splitBaseQty = $split['quantity'];
                        $splitUnitCost = $split['unit_cost'];
                        $splitProfit = max(0, $splitSubtotal - ($splitBaseQty * $splitUnitCost));

                        $saleItem = SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_variant_id' => $variantId,
                            'batch_id' => $split['batch_id'],
                            'quantity' => $splitQty,
                            'unit_id' => $unitId,
                            'base_quantity' => $splitBaseQty,
                            'unit_price' => $unitPrice,
                            'unit_cost' => $splitUnitCost,
                            'discount' => $splitDiscount,
                            'subtotal' => $splitSubtotal,
                            'profit' => $splitProfit,
                        ]);

                        StockMovement::create([
                            'product_variant_id' => $variantId,
                            'batch_id' => $split['batch_id'],
                            'type' => 'sale',
                            'direction' => 'out',
                            'quantity' => $splitBaseQty,
                            'unit_id' => $unitId,
                            'original_quantity' => $splitQty,
                            'reference_type' => 'sale_item',
                            'reference_id' => $saleItem->id,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }

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
            $this->search = '';
            $this->categoryFilter = null;
            $this->subCategoryFilter = null;
            $this->showReceipt = true;
            $this->success(__('Sale completed!'), position: 'toast-bottom');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    // ─── Receipt ─────────────────────────────────────

    #[Computed]
    public function lastSale()
    {
        if (! $this->lastSaleId) {
            return null;
        }

        return Sale::with(['items.variant.product', 'items.unit', 'seller'])->find($this->lastSaleId);
    }
};
