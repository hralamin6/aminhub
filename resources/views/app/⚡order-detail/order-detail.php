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
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    public Order $order;
    public string $tracking_number = '';
    public string $courier_name = '';

    public function mount(Order $order): void
    {
        $this->authorize('orders.view');
        $this->order = $order->load(['customer', 'items.variant.product', 'items.unit', 'statusLogs' => fn($q) => $q->latest()]);
        $this->tracking_number = $this->order->tracking_number ?? '';
        $this->courier_name = $this->order->courier_name ?? '';
    }

    public function confirmOrder(): void
    {
        $this->authorize('orders.manage');
        try {
            app(OrderService::class)->confirmOrder($this->order);
            $this->order->refresh();
            $this->success('Order confirmed');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function processOrder(): void
    {
        $this->authorize('orders.manage');
        try {
            app(OrderService::class)->processOrder($this->order);
            $this->order->refresh();
            $this->success('Order is now processing');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function packOrder(): void
    {
        $this->authorize('orders.manage');
        try {
            app(OrderService::class)->packOrder($this->order);
            $this->order->refresh();
            $this->success('Order packed');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function shipOrder(): void
    {
        $this->authorize('orders.manage');
        try {
            app(OrderService::class)->shipOrder($this->order, $this->courier_name, $this->tracking_number);
            $this->order->refresh();
            $this->success('Order shipped');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function deliverOrder(): void
    {
        $this->authorize('orders.manage');
        try {
            app(OrderService::class)->deliverOrder($this->order);
            $this->order->refresh();
            $this->success('Order delivered');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function cancelOrder(): void
    {
        $this->authorize('orders.cancel');
        try {
            app(OrderService::class)->cancelOrder($this->order);
            $this->order->refresh();
            $this->success('Order cancelled');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function markAsPaid(): void
    {
        $this->authorize('orders.manage');
        try {
            app(OrderService::class)->markAsPaid($this->order);
            $this->order->refresh();
            $this->success('Marked as paid');
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
    }

    public function updateShipping(): void
    {
        $this->authorize('orders.manage');
        $this->order->update([
            'tracking_number' => $this->tracking_number,
            'courier_name' => $this->courier_name,
        ]);
        $this->success('Shipping info updated');
    }
};
