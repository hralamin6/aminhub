<?php

use App\Models\Sale;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Title('Sale Detail')]
#[Layout('layouts.app')]
class extends Component
{
    public int $saleId;

    public function mount(int $sale): void
    {
        $this->authorize('sales.view');
        $this->saleId = $sale;
    }

    #[Computed]
    public function sale()
    {
        return Sale::with([
            'customer',
            'seller',
            'items.variant.product',
            'items.unit',
        ])->findOrFail($this->saleId);
    }
};
