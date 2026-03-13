<?php

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Purchase Returns')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 20;

    // Form
    public bool $showForm = false;
    public ?int $selectedPurchaseId = null;
    public string $return_date = '';
    public string $reason = '';
    public array $returnItems = [];

    public function mount(): void
    {
        $this->authorize('purchase_returns.view');
        $this->return_date = now()->format('Y-m-d');
    }

    #[Computed]
    public function returns()
    {
        return PurchaseReturn::query()
            ->with(['purchase.provider', 'creator'])
            ->withCount('items')
            ->when($this->search, fn ($q, $s) => $q->where('return_number', 'like', "%{$s}%")
                ->orWhereHas('purchase', fn ($pq) => $pq->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('provider', fn ($sq) => $sq->where('name', 'like', "%{$s}%"))))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function purchaseOptions()
    {
        return Purchase::where('status', 'received')
            ->with('provider')
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => "{$p->invoice_number} — {$p->provider->name}"])
            ->toArray();
    }

    #[Computed]
    public function purchaseItems()
    {
        if (! $this->selectedPurchaseId) return [];

        return PurchaseItem::where('purchase_id', $this->selectedPurchaseId)
            ->with(['variant.product', 'unit'])
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'label' => "{$item->variant->product->name} — {$item->variant->name}",
                'max_qty' => (float) $item->base_quantity,
                'unit_price' => (float) $item->unit_price,
                'unit_name' => $item->unit->short_name ?? '',
            ])
            ->toArray();
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function updatedSelectedPurchaseId(): void
    {
        $this->returnItems = [];
        foreach ($this->purchaseItems as $pi) {
            $this->returnItems[] = [
                'purchase_item_id' => $pi['id'],
                'quantity' => 0,
                'unit_price' => $pi['unit_price'],
                'selected' => false,
            ];
        }
    }

    public function create(): void
    {
        $this->authorize('purchase_returns.create');
        $this->selectedPurchaseId = null;
        $this->return_date = now()->format('Y-m-d');
        $this->reason = '';
        $this->returnItems = [];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('purchase_returns.create');

        $this->validate([
            'selectedPurchaseId' => 'required|exists:purchases,id',
            'return_date' => 'required|date',
            'reason' => 'nullable|string|max:1000',
        ]);

        // Filter selected items with qty > 0
        $selectedItems = collect($this->returnItems)->filter(fn ($i) => $i['selected'] && (float) $i['quantity'] > 0);

        if ($selectedItems->isEmpty()) {
            $this->error(__('Select at least one item to return.'));
            return;
        }

        DB::transaction(function () use ($selectedItems) {
            $return = PurchaseReturn::create([
                'purchase_id' => $this->selectedPurchaseId,
                'return_date' => $this->return_date,
                'reason' => $this->reason ?: null,
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;

            foreach ($selectedItems as $item) {
                $purchaseItem = PurchaseItem::with('variant')->findOrFail($item['purchase_item_id']);
                $qty = (float) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $lineTotal = $qty * $unitPrice;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineTotal,
                ]);

                // Create reverse stock movement
                StockMovement::create([
                    'product_variant_id' => $purchaseItem->product_variant_id,
                    'type' => 'return_out',
                    'direction' => 'out',
                    'quantity' => $qty,
                    'reference_type' => 'purchase_return_item',
                    'reference_id' => $return->id,
                    'note' => "Purchase return: {$return->return_number}",
                    'created_by' => auth()->id(),
                ]);

                $totalAmount += $lineTotal;
            }

            $return->update(['total_amount' => $totalAmount]);
        });

        $this->showForm = false;
        $this->success(__('Purchase return created — stock deducted.'), position: 'toast-bottom');
        $this->resetPage();
    }
};
