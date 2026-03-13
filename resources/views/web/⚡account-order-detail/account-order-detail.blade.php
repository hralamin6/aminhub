<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="/account/orders" wire:navigate class="btn btn-ghost btn-sm">
            <x-icon name="o-arrow-left" class="w-4 h-4" /> {{ __('Back') }}
        </a>
        <h1 class="text-xl font-bold">{{ __('Order') }} #{{ $order->order_number }}</h1>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Main --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Status Bar --}}
            <div class="card bg-base-100 shadow-sm border border-base-200 p-4">
                @php
                    $statuses = ['pending', 'confirmed', 'processing', 'packed', 'shipped', 'delivered'];
                    $currentIdx = array_search($order->status, $statuses);
                    $isCancelled = in_array($order->status, ['cancelled', 'returned']);
                @endphp

                @if($isCancelled)
                    <div class="alert alert-error">
                        <x-icon name="o-x-circle" class="w-5 h-5" />
                        <span>{{ __('This order has been') }} {{ $order->status }}</span>
                    </div>
                @else
                    <ul class="steps steps-horizontal w-full text-xs">
                        @foreach($statuses as $idx => $s)
                            <li class="step {{ $currentIdx !== false && $idx <= $currentIdx ? 'step-primary' : '' }}">
                                {{ ucfirst($s) }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Order Items --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-bold mb-3"><x-icon name="o-cube" class="w-4 h-4 inline" /> {{ __('Items') }}</h3>
                    <div class="divide-y divide-base-200">
                        @foreach($order->items as $item)
                            <div class="flex items-center gap-4 py-3">
                                <div class="flex-1">
                                    <div class="font-semibold">{{ $item->product_name }}</div>
                                    @if($item->variant_name)
                                        <div class="text-xs text-base-content/60">{{ $item->variant_name }}</div>
                                    @endif
                                    <div class="text-xs text-base-content/50 mt-1">
                                        {{ number_format($item->quantity, 0) }} {{ $item->unit?->short_name }} × ৳{{ number_format($item->unit_price, 0) }}
                                    </div>
                                </div>
                                <div class="font-mono font-bold">৳{{ number_format($item->subtotal, 0) }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t-2 border-base-200 mt-2 pt-3 space-y-1">
                        <div class="flex justify-between text-sm"><span class="opacity-70">{{ __('Subtotal') }}</span><span class="font-mono">৳{{ number_format($order->subtotal, 0) }}</span></div>
                        <div class="flex justify-between text-sm"><span class="opacity-70">{{ __('Delivery') }}</span><span class="font-mono">৳{{ number_format($order->delivery_charge, 0) }}</span></div>
                        @if($order->discount_amount > 0)
                            <div class="flex justify-between text-sm"><span class="opacity-70">{{ __('Discount') }}</span><span class="font-mono text-error">-৳{{ number_format($order->discount_amount, 0) }}</span></div>
                        @endif
                        <div class="flex justify-between font-bold text-lg pt-2 border-t border-base-200"><span>{{ __('Total') }}</span><span class="font-mono text-primary">৳{{ number_format($order->grand_total, 0) }}</span></div>
                    </div>
                </div>
            </div>

            {{-- Shipping Info --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-bold mb-3"><x-icon name="o-truck" class="w-4 h-4 inline" /> {{ __('Shipping Info') }}</h3>
                    <div class="text-sm space-y-1">
                        <p class="font-bold">{{ $order->shipping_name }}</p>
                        <p><x-icon name="o-phone" class="w-3 h-3 inline" /> {{ $order->shipping_phone }}</p>
                        <p class="opacity-70">{{ $order->shipping_address }}, {{ implode(', ', array_filter([$order->shipping_upazila, $order->shipping_district, $order->shipping_division])) }}</p>
                    </div>
                    @if($order->tracking_number)
                        <div class="alert alert-info mt-4 py-2">
                            <x-icon name="o-truck" class="w-4 h-4" />
                            <div>
                                <span class="text-xs">{{ __('Tracking') }}</span>
                                <span class="font-mono font-bold block">{{ $order->tracking_number }}</span>
                                @if($order->courier_name)
                                    <span class="text-xs opacity-70">{{ $order->courier_name }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Payment --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-bold mb-3"><x-icon name="o-credit-card" class="w-4 h-4 inline" /> {{ __('Payment') }}</h3>
                    <div class="text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="opacity-70">{{ __('Method') }}</span>
                            <span class="badge badge-ghost badge-sm">{{ strtoupper($order->payment_method) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="opacity-70">{{ __('Status') }}</span>
                            <span class="badge {{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }} badge-sm">{{ ucfirst($order->payment_status) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-bold mb-3"><x-icon name="o-clock" class="w-4 h-4 inline" /> {{ __('Timeline') }}</h3>
                    <ul class="timeline timeline-vertical timeline-compact text-xs -ml-4 mt-2">
                        <li>
                            <div class="timeline-start timeline-box bg-base-200/50">
                                <span class="font-bold">{{ __('Order Placed') }}</span>
                                <div class="opacity-60">{{ $order->created_at->format('d M Y H:i') }}</div>
                            </div>
                            <div class="timeline-middle"><x-icon name="o-check-circle" class="w-3 h-3 text-success" /></div>
                            <hr class="bg-success" />
                        </li>
                        @foreach($order->statusLogs as $log)
                            <li>
                                <hr class="bg-success" />
                                <div class="timeline-start timeline-box bg-base-200/50">
                                    <span class="font-bold">{{ ucfirst($log->to_status) }}</span>
                                    <div class="opacity-60">{{ $log->created_at->format('d M Y H:i') }}</div>
                                    @if($log->note)<div class="opacity-50 mt-0.5">{{ $log->note }}</div>@endif
                                </div>
                                <div class="timeline-middle"><x-icon name="o-check-circle" class="w-3 h-3 text-success" /></div>
                                <hr />
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            {{-- Actions --}}
            @if($order->status === 'pending')
                <button class="btn btn-error btn-outline btn-block" wire:click="cancelOrder" wire:confirm="{{ __('Are you sure you want to cancel this order?') }}">
                    <x-icon name="o-x-mark" class="w-4 h-4" /> {{ __('Cancel Order') }}
                </button>
            @endif
        </div>
    </div>
</div>
