<?php

use App\Models\StockMovement;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Stock Movements')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 30;
    public ?string $typeFilter = null;
    public ?string $directionFilter = null;
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->authorize('inventory.movements');
    }

    #[Computed]
    public function movements()
    {
        return StockMovement::query()
            ->with(['variant.product.baseUnit', 'unit', 'creator', 'batch'])
            ->when($this->search, fn ($q, $s) => $q->whereHas('variant', fn ($vq) =>
                $vq->where('name', 'like', "%{$s}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"))
            ))
            ->when($this->typeFilter, fn ($q, $t) => $q->where('type', $t))
            ->when($this->directionFilter, fn ($q, $d) => $q->where('direction', $d))
            ->when($this->dateFrom, fn ($q) => $q->where('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->where('created_at', '<=', $this->dateTo . ' 23:59:59'))
            ->latest()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function summaryStats()
    {
        $query = StockMovement::query()
            ->when($this->dateFrom, fn ($q) => $q->where('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->where('created_at', '<=', $this->dateTo . ' 23:59:59'));

        return [
            'total_in' => (float) (clone $query)->stockIn()->sum('quantity'),
            'total_out' => (float) (clone $query)->stockOut()->sum('quantity'),
            'total_movements' => (clone $query)->count(),
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingDirectionFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'directionFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }
};
