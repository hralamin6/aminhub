<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex items-center justify-between mb-8 pb-4 border-b border-base-200">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold">{{ __('My Orders') }}</h1>
            <p class="text-sm text-base-content/60 mt-1">{{ __('Track and manage your orders') }}</p>
        </div>
        <a href="/shop" wire:navigate class="btn btn-primary btn-sm">
            <x-icon name="o-shopping-bag" class="w-4 h-4" /> {{ __('Continue Shopping') }}
        </a>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="tabs tabs-boxed mb-6 bg-base-200/50">
        <button wire:click="$set('statusFilter', '')" class="tab {{ $statusFilter === '' ? 'tab-active' : '' }}">{{ __('All') }}</button>
        <button wire:click="$set('statusFilter', 'pending')" class="tab {{ $statusFilter === 'pending' ? 'tab-active' : '' }}">{{ __('Pending') }}</button>
        <button wire:click="$set('statusFilter', 'confirmed')" class="tab {{ $statusFilter === 'confirmed' ? 'tab-active' : '' }}">{{ __('Confirmed') }}</button>
        <button wire:click="$set('statusFilter', 'shipped')" class="tab {{ $statusFilter === 'shipped' ? 'tab-active' : '' }}">{{ __('Shipped') }}</button>
        <button wire:click="$set('statusFilter', 'delivered')" class="tab {{ $statusFilter === 'delivered' ? 'tab-active' : '' }}">{{ __('Delivered') }}</button>
    </div>

    {{-- Orders List --}}
    <div class="space-y-4">
        @forelse($this->orders as $order)
            <div class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow">
                <div class="card-body p-4 md:p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="font-mono font-bold text-base">{{ $order->order_number }}</span>
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
                                <span class="badge {{ $color }} badge-sm">{{ ucfirst($order->status) }}</span>
                                <span class="badge {{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-ghost' }} badge-sm">{{ ucfirst($order->payment_status) }}</span>
                            </div>
                            <div class="text-sm text-base-content/60">
                                <span><x-icon name="o-calendar" class="w-3 h-3 inline" /> {{ $order->created_at->format('d M Y, h:i A') }}</span>
                                <span class="mx-2">•</span>
                                <span>{{ $order->items()->count() }} {{ __('items') }}</span>
                            </div>
                            @if($order->tracking_number)
                                <div class="text-xs mt-2 text-primary">
                                    <x-icon name="o-truck" class="w-3 h-3 inline" /> {{ __('Tracking') }}: {{ $order->tracking_number }}
                                    @if($order->courier_name)
                                        ({{ $order->courier_name }})
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="font-mono font-bold text-lg text-primary">৳{{ number_format($order->grand_total, 0) }}</div>
                            <a href="/account/orders/{{ $order->id }}" wire:navigate class="btn btn-ghost btn-sm mt-2">
                                {{ __('View Details') }} <x-icon name="o-arrow-right" class="w-4 h-4" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <x-icon name="o-shopping-bag" class="w-16 h-16 mx-auto mb-4 opacity-20" />
                <h3 class="text-lg font-semibold text-base-content/50">{{ __('No orders yet') }}</h3>
                <p class="text-sm text-base-content/40 mt-1">{{ __('Your orders will appear here') }}</p>
                <a href="/shop" wire:navigate class="btn btn-primary mt-4">{{ __('Start Shopping') }}</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $this->orders->links() }}
    </div>
</div>
