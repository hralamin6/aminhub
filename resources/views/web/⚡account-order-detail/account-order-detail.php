<?php

use App\Models\Order;
use App\Services\OrderService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Order Detail')]
#[Layout('layouts.shop')]
class extends Component
{
    use Toast;

    public Order $order;

    public function mount(Order $order): void
    {
        if (!auth()->check() || $order->customer_id !== auth()->id()) {
            abort(403);
        }
        $this->order = $order->load(['items.variant.product', 'items.unit', 'statusLogs' => fn($q) => $q->latest()]);
    }

    public function cancelOrder(): void
    {
        try {
            app(OrderService::class)->cancelOrder($this->order, 'Cancelled by customer');
            $this->order->refresh();
            $this->success('Order cancelled successfully');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }
};
