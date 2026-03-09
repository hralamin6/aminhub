<?php

use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Stock Adjustments')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 20;
    public ?string $typeFilter = null;

    // Form
    public bool $showForm = false;
    public ?int $variantId = null;
    public string $type = 'addition';
    public float $quantity = 0;
    public string $reason = '';
    public string $note = '';
    public string $variantSearch = '';

    public function mount(): void
    {
        $this->authorize('inventory.adjust');
    }

    #[Computed]
    public function adjustments()
    {
        return StockAdjustment::query()
            ->with(['variant.product.baseUnit', 'creator'])
            ->when($this->search, fn ($q, $s) => $q->where('adjustment_number', 'like', "%{$s}%")
                ->orWhere('reason', 'like', "%{$s}%")
                ->orWhereHas('variant', fn ($vq) => $vq->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$s}%")))
            )
            ->when($this->typeFilter, fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function variantOptions()
    {
        $query = ProductVariant::query()
            ->with('product')
            ->whereHas('product', fn ($q) => $q->where('is_active', true))
            ->where('is_active', true);

        if ($this->variantSearch) {
            $s = "%{$this->variantSearch}%";
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', $s));
            });
        }

        return $query->limit(30)->get()
            ->map(fn ($v) => ['id' => $v->id, 'name' => "{$v->product->name} — {$v->name}"])
            ->toArray();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('inventory.adjust');

        $this->validate([
            'variantId' => 'required|exists:product_variants,id',
            'type' => 'required|in:addition,subtraction',
            'quantity' => 'required|numeric|min:0.0001',
            'reason' => 'required|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            app(InventoryService::class)->createAdjustment(
                $this->variantId,
                $this->type,
                $this->quantity,
                $this->reason,
                $this->note ?: null,
            );

            $this->showForm = false;
            $this->success(__('Stock adjustment recorded successfully.'), position: 'toast-bottom');
            $this->resetPage();
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    private function resetForm(): void
    {
        $this->reset(['variantId', 'type', 'quantity', 'reason', 'note', 'variantSearch']);
        $this->type = 'addition';
    }
};
