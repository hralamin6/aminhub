<?php

use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Unit;
use App\Services\InventoryService;
use App\Services\UnitConversionService;
use Illuminate\Support\Facades\DB;
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
            $this->addItem();
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

        $this->items = $p->items->map(fn ($item) => [
            'id' => $item->id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => (float) $item->quantity,
            'unit_id' => $item->unit_id,
            'unit_price' => (float) $item->unit_price,
            'batch_number' => $item->batch_number ?? '',
            'expiry_date' => $item->expiry_date?->format('Y-m-d') ?? '',
        ])->toArray();

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    #[Computed]
    public function providerOptions()
    {
        return User::role('provider')->active()->orderBy('name')->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();
    }

    #[Computed]
    public function variantOptions()
    {
        return ProductVariant::with('product')
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->orderBy('product_id')
            ->get()
            ->map(fn ($v) => ['id' => $v->id, 'name' => "{$v->product->name} — {$v->name}"])
            ->toArray();
    }

    #[Computed]
    public function unitOptions()
    {
        return Unit::active()->orderBy('name')->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => "{$u->name} ({$u->short_name})"])
            ->toArray();
    }

    // ─── Items ───────────────────────────────────────

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'product_variant_id' => null,
            'quantity' => 1,
            'unit_id' => null,
            'unit_price' => 0,
            'batch_number' => '',
            'expiry_date' => '',
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        if (empty($this->items)) $this->addItem();
    }

    public function getItemSubtotal(int $index): float
    {
        $item = $this->items[$index] ?? null;
        if (! $item) return 0;
        return (float) $item['quantity'] * (float) $item['unit_price'];
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

    // ─── Save ────────────────────────────────────────

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
        ]);

        DB::transaction(function () use ($status) {
            $purchase = $this->purchaseId
                ? Purchase::findOrFail($this->purchaseId)
                : new Purchase();

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

            // Clear old items if editing
            if ($this->purchaseId) {
                $purchase->items()->delete();
            }

            $subtotal = 0;
            $converter = app(UnitConversionService::class);
            $inventory = app(InventoryService::class);

            foreach ($this->items as $itemData) {
                $qty = (float) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $lineTotal = $qty * $unitPrice;

                // Convert to base unit
                $variant = ProductVariant::with('product')->find($itemData['product_variant_id']);
                $baseQty = $qty;
                try {
                    $baseQty = $converter->toBaseUnit($variant->product_id, (int) $itemData['unit_id'], $qty);
                } catch (\Exception $e) {
                    // If conversion fails, assume 1:1
                }

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'quantity' => $qty,
                    'unit_id' => $itemData['unit_id'],
                    'unit_price' => $unitPrice,
                    'base_quantity' => $baseQty,
                    'subtotal' => $lineTotal,
                    'batch_number' => $itemData['batch_number'] ?: null,
                    'expiry_date' => $itemData['expiry_date'] ?: null,
                ]);

                $subtotal += $lineTotal;

                // On receive: create stock movement + optional batch
                if ($status === 'received') {
                    $batchId = null;

                    if (! empty($itemData['batch_number'])) {
                        $batch = ProductBatch::create([
                            'product_variant_id' => $itemData['product_variant_id'],
                            'batch_number' => $itemData['batch_number'],
                            'expiry_date' => $itemData['expiry_date'] ?: null,
                            'initial_quantity' => $baseQty,
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

                    // Update variant purchase price to base unit price
                    // If buying 1 bag at 2000 (50kg), store 40/kg (2000/50)
                    $baseUnitPrice = $baseQty > 0 ? ($unitPrice * $qty) / $baseQty : $unitPrice;
                    $variant->update(['purchase_price' => round($baseUnitPrice, 2)]);
                }
            }

            // Recalculate totals
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
};
