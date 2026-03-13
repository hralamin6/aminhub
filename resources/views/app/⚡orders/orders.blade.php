<div class="space-y-6">
    <x-header title="Orders" subtitle="Manage online orders and tracking" separator>
        <x-slot:actions>
            <x-input icon="o-magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search orders..." class="w-full md:w-64" clearable />
            <x-select :options="[
                ['id' => '', 'name' => 'All Status'],
                ['id' => 'pending', 'name' => 'Pending'],
                ['id' => 'confirmed', 'name' => 'Confirmed'],
                ['id' => 'processing', 'name' => 'Processing'],
                ['id' => 'packed', 'name' => 'Packed'],
                ['id' => 'shipped', 'name' => 'Shipped'],
                ['id' => 'delivered', 'name' => 'Delivered'],
                ['id' => 'cancelled', 'name' => 'Cancelled'],
            ]" wire:model.live="statusFilter" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-stat title="Pending Orders" :value="$this->statPending" icon="o-clock" color="text-warning" class="bg-base-100 shadow" />
        <x-stat title="Processing/Packed" :value="$this->statProcessing" icon="o-arrow-path" color="text-info" class="bg-base-100 shadow" />
        <x-stat title="Today Revenue" value="0.00" icon="o-banknotes" class="bg-base-100 shadow" />
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="table table-zebra">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->orders as $order)
                        <tr>
                            <td>
                                <div class="font-mono font-bold">{{ $order->order_number }}</div>
                                <div class="text-xs text-base-content/60">{{ $order->created_at->format('M d, Y H:i') }}</div>
                            </td>
                            <td>
                                <div class="font-semibold">{{ $order->shipping_name }}</div>
                                <div class="text-xs text-base-content/60"><x-icon name="o-phone" class="w-3 h-3 inline" /> {{ $order->shipping_phone }}</div>
                            </td>
                            <td>
                                @php
                                    $color = match($order->status) {
                                        'pending' => 'badge-warning',
                                        'confirmed', 'processing', 'packed' => 'badge-info',
                                        'shipped' => 'badge-primary',
                                        'delivered' => 'badge-success',
                                        'cancelled', 'returned' => 'badge-error',
                                        default => ''
                                    };
                                @endphp
                                <span class="badge {{ $color }}">{{ ucfirst($order->status) }}</span>
                            </td>
                            <td class="font-mono font-bold">
                                ৳{{ number_format($order->grand_total, 0) }}
                            </td>
                            <td>
                                <div class="flex flex-col gap-1 text-xs">
                                    <span class="uppercase tracking-wide font-bold">{{ $order->payment_method }}</span>
                                    <span class="badge badge-sm {{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-ghost' }}">{{ ucfirst($order->payment_status) }}</span>
                                </div>
                            </td>
                            <td>
                                <x-button class="btn-ghost btn-sm" icon="o-eye" link="/app/orders/{{ $order->id }}" wire:navigate />
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-8 text-base-content/50">
                                <x-icon name="o-inbox" class="w-12 h-12 mx-auto mb-2 opacity-20" />
                                <p>No orders found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $this->orders->links() }}
        </div>
    </x-card>
</div>
