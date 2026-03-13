<?php

use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Orders')]
#[Layout('layouts.app')]
class extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    public string $statusFilter = '';

    public function mount(): void
    {
        $this->authorize('orders.view');
        $this->statusFilter = request()->query('status', '');
    }

    #[Computed]
    public function orders()
    {
        return Order::with('customer')
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%")
                ->orWhere('shipping_phone', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(15);
    }
    
    #[Computed]
    public function statPending()
    {
        return Order::where('status', 'pending')->count();
    }
    
    #[Computed]
    public function statProcessing()
    {
        return Order::whereIn('status', ['confirmed', 'processing', 'packed'])->count();
    }
};
