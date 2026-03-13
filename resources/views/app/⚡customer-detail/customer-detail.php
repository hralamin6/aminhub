<?php

use App\Models\User;
use App\Models\Sale;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Customer Detail')]
#[Layout('layouts.app')]
class extends Component
{
    public int $customerId;

    public function mount(int $customer): void
    {
        $this->authorize('customers.view');
        $this->customerId = $customer;
    }

    #[Computed]
    public function customer()
    {
        return User::role('customer')->with(['detail', 'sales.items'])->findOrFail($this->customerId);
    }

    #[Computed]
    public function latestSales()
    {
        return Sale::where('customer_id', $this->customerId)
            ->with(['seller'])
            ->withCount('items')
            ->latest()
            ->limit(10)
            ->get();
    }
};
