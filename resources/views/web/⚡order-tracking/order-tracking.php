<?php

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Track Your Order')]
#[Layout('layouts.shop')]
class extends Component
{
    use Toast;

    public string $order_number = '';
    public string $phone = '';
    public ?Order $order = null;
    public bool $searched = false;

    public function mount(): void
    {
        // Allow pre-filling via query string
        $this->order_number = request()->query('order', '');
        $this->phone = request()->query('phone', '');
    }

    public function trackOrder(): void
    {
        $this->validate([
            'order_number' => 'required|string',
            'phone' => 'required|string|min:5',
        ]);

        $this->order = Order::where('order_number', $this->order_number)
            ->where('shipping_phone', $this->phone)
            ->with(['items.variant.product', 'items.unit', 'statusLogs' => fn($q) => $q->latest()])
            ->first();

        $this->searched = true;

        if (!$this->order) {
            $this->error(__('No order found. Please check your order number and phone number.'));
        }
    }
};
