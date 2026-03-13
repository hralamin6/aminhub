<div class="container mx-auto px-4 py-8 max-w-3xl">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold">{{ __('Track Your Order') }}</h1>
        <p class="text-base-content/60 mt-2 text-sm">{{ __('Enter your order number and phone to track your order') }}</p>
    </div>

    {{-- Search Form --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-8">
        <div class="card-body items-center">
            <form wire:submit="trackOrder" class="flex flex-col sm:flex-row gap-3 w-full max-w-xl">
                <x-input class="flex-1" wire:model="order_number" placeholder="{{ __('Order Number (e.g. ORD-2026-0001)') }}" icon="o-document-text" required />
                <x-input class="flex-1" wire:model="phone" placeholder="{{ __('Phone Number') }}" icon="o-phone" required />
                <button type="submit" class="btn btn-primary">
                    <x-icon name="o-magnifying-glass" class="w-4 h-4" /> {{ __('Track') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Results --}}
    @if($searched && $order)
        <div class="space-y-6">
            {{-- Status Progress --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-bold text-lg">{{ $order->order_number }}</h2>
                            <p class="text-sm opacity-60">{{ $order->created_at->format('d M Y, h:i A') }}</p>
                        </div>
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
                        <span class="badge {{ $color }} badge-lg">{{ ucfirst($order->status) }}</span>
                    </div>

                    @php
                        $statuses = ['pending', 'confirmed', 'processing', 'packed', 'shipped', 'delivered'];
                        $currentIdx = array_search($order->status, $statuses);
                        $isCancelled = in_array($order->status, ['cancelled', 'returned']);
                    @endphp

                    @if($isCancelled)
                        <div class="alert alert-error text-sm">
                            <x-icon name="o-x-circle" class="w-5 h-5" />
                            {{ __('This order has been') }} {{ $order->status }}.
                        </div>
                    @else
                        <ul class="steps steps-horizontal w-full text-xs mt-4">
                            @foreach($statuses as $idx => $s)
                                <li class="step {{ $currentIdx !== false && $idx <= $currentIdx ? 'step-primary' : '' }}">
                                    {{ ucfirst($s) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6">
                {{-- Order Items --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <h3 class="font-bold mb-3"><x-icon name="o-cube" class="w-4 h-4 inline" /> {{ __('Items') }}</h3>
                        <div class="divide-y divide-base-200 text-sm">
                            @foreach($order->items as $item)
                                <div class="flex justify-between py-2">
                                    <div>
                                        <span class="font-medium">{{ $item->product_name }}</span>
                                        @if($item->variant_name) <span class="opacity-60 text-xs block">{{ $item->variant_name }}</span> @endif
                                        <span class="opacity-50 text-xs">{{ number_format($item->quantity, 0) }} {{ $item->unit?->short_name }}</span>
                                    </div>
                                    <span class="font-mono">৳{{ number_format($item->subtotal, 0) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t-2 border-base-200 pt-3 mt-2">
                            <div class="flex justify-between font-bold">
                                <span>{{ __('Total') }}</span>
                                <span class="font-mono text-primary">৳{{ number_format($order->grand_total, 0) }}</span>
                            </div>
                            <div class="flex justify-between text-sm opacity-70 mt-1">
                                <span>{{ __('Payment') }}</span>
                                <span class="badge badge-sm {{ $order->payment_status === 'paid' ? 'badge-success' : 'badge-warning' }}">{{ strtoupper($order->payment_method) }} — {{ ucfirst($order->payment_status) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timeline + Shipping --}}
                <div class="space-y-6">
                    @if($order->tracking_number)
                    <div class="card bg-base-100 shadow-sm border border-base-200">
                        <div class="card-body p-4">
                            <h3 class="font-bold mb-2"><x-icon name="o-truck" class="w-4 h-4 inline" /> {{ __('Shipping') }}</h3>
                            <div class="text-sm">
                                <p class="font-bold">{{ __('Tracking') }}: {{ $order->tracking_number }}</p>
                                @if($order->courier_name)
                                    <p class="opacity-70">{{ __('Courier') }}: {{ $order->courier_name }}</p>
                                @endif
                                @if($order->delivery_date)
                                    <p class="opacity-70 mt-1">{{ __('Est. Delivery') }}: {{ $order->delivery_date->format('d M Y') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="card bg-base-100 shadow-sm border border-base-200">
                        <div class="card-body p-4">
                            <h3 class="font-bold mb-3"><x-icon name="o-clock" class="w-4 h-4 inline" /> {{ __('Timeline') }}</h3>
                            <ul class="timeline timeline-vertical timeline-compact text-xs -ml-4">
                                <li>
                                    <div class="timeline-start timeline-box bg-base-200/50">
                                        <span class="font-bold block">{{ __('Order Placed') }}</span>
                                        <span class="opacity-60">{{ $order->created_at->format('d M Y H:i') }}</span>
                                    </div>
                                    <div class="timeline-middle"><x-icon name="o-check-circle" class="w-3 h-3 text-success" /></div>
                                    <hr class="bg-success" />
                                </li>
                                @foreach($order->statusLogs as $log)
                                    <li>
                                        <hr class="bg-success" />
                                        <div class="timeline-start timeline-box bg-base-200/50">
                                            <span class="font-bold block">{{ ucfirst($log->to_status) }}</span>
                                            <span class="opacity-60">{{ $log->created_at->format('d M Y H:i') }}</span>
                                            @if($log->note)<span class="opacity-50 block">{{ $log->note }}</span>@endif
                                        </div>
                                        <div class="timeline-middle"><x-icon name="o-check-circle" class="w-3 h-3 text-success" /></div>
                                        <hr />
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif($searched && !$order)
        <div class="text-center py-12">
            <x-icon name="o-face-frown" class="w-16 h-16 mx-auto mb-4 opacity-20" />
            <h3 class="text-lg font-bold text-base-content/50">{{ __('Order not found') }}</h3>
            <p class="text-sm text-base-content/40 mt-1">{{ __('Please check your order number and phone number') }}</p>
        </div>
    @endif
</div>
