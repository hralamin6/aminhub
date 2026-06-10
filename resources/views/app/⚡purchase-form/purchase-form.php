<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\UnitConversionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Purchase Form')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    public ?int $purchaseId = null;

    // Header
    public ?int $provider_id = null;
    public string $purchase_date = '';
    public string $note = '';
    public float $discount = 0;
    public float $tax = 0;
    public float $shipping_cost = 0;

    // Category filter (combined with subcategory)
    public ?int $category_filter_id = null;

    // Product selector
    public ?int $selectedVariantId = null;

    // Items
    public array $items = [];

    public function mount(?int $purchase = null): void
    {
        if ($purchase) {
            $this->authorize('purchases.edit');
            $this->purchaseId = $purchase;
            $this->loadPurchase();
        } else {
            $this->authorize('purchases.create');
            $this->purchase_date = now()->format('Y-m-d');
        }
    }

    private function loadPurchase(): void
    {
        $p = Purchase::with('items')->findOrFail($this->purchaseId);

        if ($p->status !== 'draft') {
            abort(403, 'Only draft purchases can be edited.');
        }

        $this->provider_id = $p->provider_id;
        $this->purchase_date = $p->purchase_date->format('Y-m-d');
        $this->note = $p->note ?? '';
        $this->discount = (float) $p->discount;
        $this->tax = (float) $p->tax;
        $this->shipping_cost = (float) $p->shipping_cost;

        $this->items = $p->items->map(function ($item) {
            $productInfo = $this->getProductInfo($item->product_variant_id);

            return [
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => (float) $item->quantity,
                'unit_id' => $item->unit_id,
                'unit_price' => (float) $item->unit_price,
                'landed_cost' => (float) $item->landed_cost,
                'batch_number' => $item->batch_number ?? '',
                'expiry_date' => $item->expiry_date?->format('Y-m-d') ?? '',
                'product_info' => $productInfo,
                'unit_options' => $this->getProductUnitOptions($productInfo['product_id'] ?? null, $productInfo['base_unit_id'] ?? null),
            ];
        })->toArray();
    }

    private function getProductInfo(int $variantId): array
    {
        $variant = ProductVariant::with('product.category', 'product.baseUnit')->find($variantId);
        if (! $variant) {
            return [];
        }

        return [
            'name' => $variant->product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'category_id' => $variant->product->category_id,
            'product_id' => $variant->product_id,
            'base_unit_id' => $variant->product->base_unit_id,
        ];
    }

    /**
     * Return units available for a product: base unit + all configured product units.
     *
     * @return array<int, array{id: int, name: string, short_name: string}>
     */
    private function getProductUnitOptions(?int $productId, ?int $baseUnitId): array
    {
        if (! $productId || ! $baseUnitId) {
            return [];
        }

        // Always include the base unit first
        $baseUnit = Unit::find($baseUnitId);
        $options = $baseUnit
            ? [['id' => $baseUnit->id, 'name' => $baseUnit->name, 'short_name' => $baseUnit->short_name]]
            : [];

        // Append units explicitly configured for this product
        $configured = ProductUnit::where('product_id', $productId)
            ->where('unit_id', '!=', $baseUnitId)
            ->with('unit')
            ->get();

        foreach ($configured as $pu) {
            $options[] = [
                'id' => $pu->unit->id,
                'name' => $pu->unit->name,
                'short_name' => $pu->unit->short_name,
            ];
        }

        return $options;
    }

    #[Computed]
    public function providerOptions()
    {
        return User::role('provider')->active()->orderBy('name')->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();
    }

    #[Computed]
    public function categoryOptions()
    {
        // Hierarchical categories for choices
        return Category::with('children')
            ->whereNull('parent_id')
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->flatMap(function ($category) {
                $options = [];
                // Parent category as group header
                $options[] = [
                    'id' => $category->id,
                    'name' => '▸ ' . $category->name,
                    'group' => $category->name,
                ];
                // Children
                foreach ($category->children as $child) {
                    $options[] = [
                        'id' => $child->id,
                        'name' => '   ' . $child->name,
                        'group' => $category->name,
                    ];
                }
                return $options;
            })
            ->toArray();
    }

    #[Computed]
    public function unitOptions()
    {
        return Unit::active()->orderBy('name')->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'short_name' => $u->short_name])
            ->toArray();
    }

    #[Computed]
    public function variantOptions()
    {
        $query = ProductVariant::with(['product.category', 'product.baseUnit'])
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true);

        // Apply category filter — include children of parent categories
        if ($this->category_filter_id) {
            $filterCategory = Category::with('children')->find($this->category_filter_id);
            $categoryIds = [$this->category_filter_id];
            if ($filterCategory && $filterCategory->children->isNotEmpty()) {
                $categoryIds = array_merge($categoryIds, $filterCategory->children->pluck('id')->toArray());
            }
            $query->whereHas('product', fn ($q) => $q->whereIn('category_id', $categoryIds));
        }

        $variants = $query->orderBy('product_id')->limit(100)->get();

        // Group by category for hierarchical display
        $grouped = $variants->groupBy(fn ($v) => $v->product->category?->name ?? 'Uncategorized');

        $options = [];
        foreach ($grouped as $categoryName => $groupVariants) {
            foreach ($groupVariants as $v) {
                $options[] = [
                    'id' => $v->id,
                    'name' => $v->product->name . ($v->name !== $v->product->name ? ' — ' . $v->name : ''),
                    'sku' => $v->sku,
                    'group' => $categoryName,
                    'product_id' => $v->product_id,
                    'last_price' => $v->purchase_price,
                    'stock' => $v->current_stock,
                    'base_unit_id' => $v->product->base_unit_id,
                ];
            }
        }

        return $options;
    }



    public function addItem(int $variantId): void
    {
        // Check if already in items
        foreach ($this->items as $item) {
            if ($item['product_variant_id'] === $variantId) {
                $this->warning(__('Item already added'), position: 'toast-bottom');
                return;
            }
        }

        $variant = ProductVariant::with('product.baseUnit')->find($variantId);
        if (!$variant) {
            return;
        }

        $lastPrice = $variant->purchase_price;
        $baseUnitId = $variant->product->base_unit_id;

        $productInfo = [
            'name' => $variant->product->name,
            'variant_name' => $variant->name,
            'sku' => $variant->sku,
            'product_id' => $variant->product_id,
            'base_unit_id' => $baseUnitId,
        ];

        $this->items[] = [
            'id' => null,
            'product_variant_id' => $variantId,
            'product_info' => $productInfo,
            'unit_options' => $this->getProductUnitOptions($variant->product_id, $baseUnitId),
            'quantity' => 1,
            'unit_id' => $baseUnitId,
            'unit_price' => $lastPrice ?? 0,
            'landed_cost' => 0,
            'batch_number' => $this->generateBatchNumber($variantId),
            'expiry_date' => '',
            'last_purchase_price' => $lastPrice,
        ];

        $this->calculateLandedCosts();
    }

    public function updatedSelectedVariantId(): void
    {
        if ($this->selectedVariantId) {
            $this->addItem($this->selectedVariantId);
            $this->selectedVariantId = null;
        }
    }

    private function generateBatchNumber(int $variantId): string
    {
        $providerCode = 'UNK';
        if ($this->provider_id) {
            $provider = User::find($this->provider_id);
            $providerCode = $provider ? strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $provider->name), 0, 3)) : 'UNK';
        }
        $date = now()->format('Ymd');
        // Count how many times this specific product already appears in items
        $productCount = 0;
        foreach ($this->items as $item) {
            if ($item['product_variant_id'] === $variantId) {
                $productCount++;
            }
        }
        $productCount++; // Increment for the new item being added

        return "{$date}-{$providerCode}-{$productCount}";
    }

    public function updatedProviderId(): void
    {
        foreach ($this->items as $key => $item) {
            $this->items[$key]['batch_number'] = $this->generateBatchNumberForIndex($key, $item['product_variant_id']);
        }
    }

    private function generateBatchNumberForIndex(int $index, int $variantId): string
    {
        $providerCode = 'UNK';
        if ($this->provider_id) {
            $provider = User::find($this->provider_id);
            $providerCode = $provider ? strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $provider->name), 0, 3)) : 'UNK';
        }
        $date = now()->format('Ymd');
        // Count occurrences of this product in items up to and including this index
        $productCount = 0;
        for ($i = 0; $i <= $index; $i++) {
            if (isset($this->items[$i]) && $this->items[$i]['product_variant_id'] === $variantId) {
                $productCount++;
            }
        }

        return "{$date}-{$providerCode}-{$productCount}";
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        foreach ($this->items as $key => $item) {
            $this->items[$key]['batch_number'] = $this->generateBatchNumberForIndex($key, $item['product_variant_id']);
        }

        $this->calculateLandedCosts();
    }

    public function updateItemUnitPrice(int $index): void
    {
        $this->calculateLandedCosts();
    }

    public function updateUnitPrice(int $index, int $newUnitId): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        $item = $this->items[$index];
        $oldUnitId = (int) $item['unit_id'];
        $currentPrice = (float) $item['unit_price'];
        $productId = (int) $item['product_info']['product_id'];

        $converter = app(UnitConversionService::class);

        try {
            $oldToBase = $converter->toBaseUnit($productId, $oldUnitId, 1);
            $newToBase = $converter->toBaseUnit($productId, $newUnitId, 1);

            $newPrice = $oldToBase > 0
                ? round($currentPrice * ($newToBase / $oldToBase), 2)
                : $currentPrice;
        } catch (Exception $e) {
            $newPrice = $currentPrice;
        }

        $this->items[$index]['unit_id'] = $newUnitId;
        $this->items[$index]['unit_price'] = $newPrice;
        $this->calculateLandedCosts();
    }

    public function updatedItems($value, $key): void
    {
        if (str_contains($key, '.quantity') || str_contains($key, '.unit_price')) {
            $this->calculateLandedCosts();
        }
    }

    public function updatedDiscount(): void
    {
        $this->calculateLandedCosts();
    }

    public function updatedTax(): void
    {
        $this->calculateLandedCosts();
    }

    public function updatedShippingCost(): void
    {
        $this->calculateLandedCosts();
    }

    private function calculateLandedCosts(): void
    {
        $totalSubtotal = $this->subtotal;

        if ($totalSubtotal <= 0) {
            foreach ($this->items as $key => $item) {
                $this->items[$key]['landed_cost'] = (float) $item['unit_price'];
            }
            return;
        }

        foreach ($this->items as $key => $item) {
            $itemSubtotal = (float) $item['quantity'] * (float) $item['unit_price'];
            $proportion = $itemSubtotal / $totalSubtotal;

            $shippingShare = round($this->shipping_cost * $proportion, 4);
            $taxShare = round($this->tax * $proportion, 4);

            $landedCost = $item['unit_price'] + ($shippingShare / $item['quantity']) + ($taxShare / $item['quantity']);

            $this->items[$key]['landed_cost'] = round($landedCost, 4);
        }
    }

    #[Computed]
    public function subtotal(): float
    {
        return collect($this->items)->sum(fn ($i) => (float) $i['quantity'] * (float) $i['unit_price']);
    }

    #[Computed]
    public function grandTotal(): float
    {
        return max(0, $this->subtotal - $this->discount + $this->tax + $this->shipping_cost);
    }

    #[Computed]
    public function totalLandedCost(): float
    {
        return collect($this->items)->sum(fn ($i) => (float) $i['quantity'] * (float) ($i['landed_cost'] ?? $i['unit_price']));
    }

    public function saveDraft(): void
    {
        $this->savePurchase('draft');
    }

    public function saveReceived(): void
    {
        $this->savePurchase('received');
    }

    private function savePurchase(string $status): void
    {
        $batchNumbers = [];
        foreach ($this->items as $index => $item) {
            if (!empty($item['batch_number'])) {
                $key = $item['product_variant_id'] . '|' . $item['batch_number'];
                if (in_array($key, $batchNumbers)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.batch_number" => __('Duplicate batch number in this purchase.'),
                    ]);
                }
                $batchNumbers[] = $key;

                if (!$this->validateBatchNumber($item['batch_number'], $item['product_variant_id'])) {
                    throw ValidationException::withMessages([
                        "items.{$index}.batch_number" => __('This batch number already exists for this product.'),
                    ]);
                }
            }
        }

        $this->validate([
            'provider_id' => 'required|exists:users,id',
            'purchase_date' => 'required|date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_id' => 'required|exists:units,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date|after:today',
        ]);

        DB::transaction(function () use ($status) {
            $purchase = $this->purchaseId
                ? Purchase::findOrFail($this->purchaseId)
                : new Purchase;

            $purchase->fill([
                'provider_id' => $this->provider_id,
                'purchase_date' => $this->purchase_date,
                'discount' => $this->discount,
                'tax' => $this->tax,
                'shipping_cost' => $this->shipping_cost,
                'note' => $this->note ?: null,
                'status' => $status,
                'created_by' => $purchase->created_by ?? auth()->id(),
            ]);
            $purchase->save();

            if ($this->purchaseId) {
                foreach ($purchase->items as $oldItem) {
                    StockMovement::where('reference_type', 'purchase_item')
                        ->where('reference_id', $oldItem->id)
                        ->delete();
                }
                $purchase->items()->delete();
            }

            $subtotal = 0;
            $converter = app(UnitConversionService::class);

            foreach ($this->items as $itemData) {
                $qty = (float) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $lineTotal = $qty * $unitPrice;

                $variant = ProductVariant::with('product')->find($itemData['product_variant_id']);
                $baseQty = $qty;
                try {
                    $baseQty = $converter->toBaseUnit($variant->product_id, (int) $itemData['unit_id'], $qty);
                } catch (Exception $e) {
                }

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'quantity' => $qty,
                    'unit_id' => $itemData['unit_id'],
                    'unit_price' => $unitPrice,
                    'landed_cost' => $itemData['landed_cost'] ?? $unitPrice,
                    'base_quantity' => $baseQty,
                    'subtotal' => $lineTotal,
                    'batch_number' => $itemData['batch_number'] ?: null,
                    'expiry_date' => $itemData['expiry_date'] ?: null,
                ]);

                $subtotal += $lineTotal;

                if ($status === 'received') {
                    $batchId = null;
                    $baseUnitPrice = $baseQty > 0 ? ($unitPrice * $qty) / $baseQty : $unitPrice;
                    $baseLandedCost = $baseQty > 0 ? ($itemData['landed_cost'] * $qty) / $baseQty : $itemData['landed_cost'];

                    if (! empty($itemData['batch_number'])) {
                        $batch = ProductBatch::create([
                            'product_variant_id' => $itemData['product_variant_id'],
                            'batch_number' => $itemData['batch_number'],
                            'expiry_date' => $itemData['expiry_date'] ?: null,
                            'initial_quantity' => $baseQty,
                            'purchase_price' => round($baseLandedCost, 4),
                            'landed_cost' => round($baseLandedCost, 4),
                        ]);
                        $batchId = $batch->id;
                    }

                    StockMovement::create([
                        'product_variant_id' => $itemData['product_variant_id'],
                        'type' => 'purchase',
                        'direction' => 'in',
                        'quantity' => $baseQty,
                        'unit_id' => $itemData['unit_id'],
                        'original_quantity' => $qty,
                        'reference_type' => 'purchase_item',
                        'reference_id' => $purchaseItem->id,
                        'batch_id' => $batchId,
                        'created_by' => auth()->id(),
                    ]);

                    $variant->update(['purchase_price' => round($baseLandedCost, 2)]);
                }
            }

            $purchase->subtotal = $subtotal;
            $purchase->grand_total = max(0, $subtotal - $this->discount + $this->tax + $this->shipping_cost);
            $purchase->due_amount = $purchase->grand_total - $purchase->paid_amount;
            $purchase->payment_status = match (true) {
                $purchase->due_amount <= 0 => 'paid',
                $purchase->paid_amount > 0 => 'partial',
                default => 'unpaid',
            };
            $purchase->save();
        });

        $this->success(
            $status === 'draft' ? __('Purchase saved as draft.') : __('Purchase received — stock updated.'),
            position: 'toast-bottom'
        );

        $this->redirect(route('app.purchases'), navigate: true);
    }

    private function validateBatchNumber(string $batchNumber, int $variantId): bool
    {
        return !ProductBatch::where('batch_number', $batchNumber)
            ->where('product_variant_id', $variantId)
            ->exists();
    }
};
?>
