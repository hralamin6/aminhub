<?php

use App\Models\Order;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new
#[Title('My Orders')]
#[Layout('layouts.shop')]
class extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function mount(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    #[Computed]
    public function orders()
    {
        return Order::where('customer_id', auth()->id())
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);
    }
};
