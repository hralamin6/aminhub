<div class="space-y-6 max-w-7xl mx-auto">
    <x-header title="Order #{{ $order->order_number }}" subtitle="Received {{ $order->created_at->format('M d, Y H:i') }}" separator>
        <x-slot:actions>
            <x-button class="btn-ghost btn-sm" icon="o-arrow-left" link="/app/orders" wire:navigate>{{ __('Back') }}</x-button>
        </x-slot:actions>
    </x-header>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Left: Order details & items --}}
        <div class="lg:col-span-2 space-y-6">
            <x-card title="{{ __('Order Items') }}" icon="o-cube">
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('Item') }}</th>
                                <th class="text-right">{{ __('Price') }}</th>
                                <th class="text-center">{{ __('Qty') }}</th>
                                <th class="text-right">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="font-bold">{{ $item->product_name }}</div>
                                        @if($item->variant_name)
                                            <div class="text-xs text-base-content/60">{{ $item->variant_name }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right font-mono text-sm">৳{{ number_format($item->unit_price, 0) }}</td>
                                    <td class="text-center font-mono">{{ number_format($item->quantity, 0) }} {{ $item->unit?->short_name }}</td>
                                    <td class="text-right font-bold font-mono">৳{{ number_format($item->subtotal, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right text-base-content/70">{{ __('Subtotal') }}</td>
                                <td class="text-right font-mono font-bold">৳{{ number_format($order->subtotal, 0) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right text-base-content/70">{{ __('Delivery') }}</td>
                                <td class="text-right font-mono">৳{{ number_format($order->delivery_charge, 0) }}</td>
                            </tr>
                            @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="text-right text-base-content/70">{{ __('Discount') }}</td>
                                <td class="text-right font-mono text-error">-৳{{ number_format($order->discount_amount, 0) }}</td>
                            </tr>
                            @endif
                            <tr class="bg-base-200/50">
                                <td colspan="3" class="text-right font-bold">{{ __('Grand Total') }}</td>
                                <td class="text-right font-mono font-bold text-primary text-lg">৳{{ number_format($order->grand_total, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-card>

            <x-card title="{{ __('Shipping Details') }}" icon="o-truck">
                <form wire:submit="updateShipping" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2 bg-base-200/40 rounded-lg p-4">
                            <p class="font-bold text-base">{{ $order->shipping_name }}</p>
                            <p class="text-sm mt-1"><x-icon name="o-phone" class="w-3 h-3 inline" /> {{ $order->shipping_phone }}</p>
                            <p class="text-sm mt-1 opacity-70">
                                {{ $order->shipping_address }}
                                @if($order->shipping_upazila || $order->shipping_district || $order->shipping_division)
                                    , {{ implode(', ', array_filter([$order->shipping_upazila, $order->shipping_district, $order->shipping_division])) }}
                                @endif
                            </p>
                            <p class="text-xs mt-2 uppercase tracking-wide font-semibold text-primary">
                                {{ __('Delivery Method') }}: {{ str_replace('_', ' ', ucfirst($order->delivery_method)) }}
                            </p>
                        </div>

                        <x-input label="{{ __('Courier Name') }}" wire:model="courier_name" placeholder="Steadfast, Pathao..." />
                        <x-input label="{{ __('Tracking Number') }}" wire:model="tracking_number" />
                    </div>

                    <div class="flex justify-end">
                        <x-button type="submit" class="btn-primary btn-sm" icon="o-check">{{ __('Save Shipping Info') }}</x-button>
                    </div>
                </form>
            </x-card>

            @if($order->customer_note)
            <x-card title="{{ __('Customer Note') }}" icon="o-chat-bubble-left">
                <p class="text-sm opacity-80">{{ $order->customer_note }}</p>
            </x-card>
            @endif
        </div>

        {{-- Right: Status & Action --}}
        <div class="space-y-6">
            {{-- Status --}}
            <x-card title="{{ __('Order Status') }}" icon="o-flag">
                @php
                    $statusColor = match($order->status) {
                        'pending' => 'badge-warning',
                        'confirmed', 'processing', 'packed' => 'badge-info',
                        'shipped' => 'badge-primary',
                        'delivered' => 'badge-success',
                        'cancelled', 'returned' => 'badge-error',
                        default => ''
                    };
                @endphp
                <div class="flex items-center gap-3 mb-6">
                    <span class="badge badge-lg {{ $statusColor }} text-sm px-4 py-3">{{ ucfirst($order->status) }}</span>
                </div>

                @if(!in_array($order->status, ['delivered', 'cancelled', 'returned']))
                <div class="space-y-2 border-t border-base-200 pt-4">
                    <p class="text-xs font-bold text-base-content/50 uppercase mb-3">{{ __('Change Status') }}</p>
                    <div class="grid grid-cols-2 gap-2">
                        @if($order->status === 'pending')
                            <x-button class="col-span-2 btn-info" wire:click="confirmOrder" wire:confirm="{{ __('Confirm this order?') }}" icon="o-check">{{ __('Confirm') }}</x-button>
                        @endif
                        @if(in_array($order->status, ['confirmed']))
                            <x-button class="btn-info btn-outline btn-sm" wire:click="processOrder" wire:confirm="{{ __('Mark as processing?') }}">{{ __('Processing') }}</x-button>
                        @endif
                        @if(in_array($order->status, ['confirmed', 'processing']))
                            <x-button class="btn-info btn-outline btn-sm" wire:click="packOrder" wire:confirm="{{ __('Mark as packed?') }}">{{ __('Pack') }}</x-button>
                        @endif
                        @if(in_array($order->status, ['packed', 'confirmed', 'processing']))
                            <x-button class="btn-primary btn-sm" wire:click="shipOrder" wire:confirm="{{ __('Ship this order?') }}" icon="o-truck">{{ __('Ship') }}</x-button>
                        @endif
                        @if(in_array($order->status, ['shipped', 'packed', 'confirmed', 'processing']))
                            <x-button class="col-span-2 btn-success" wire:click="deliverOrder" wire:confirm="{{ __('Mark as delivered? Stock will be deducted.') }}" icon="o-check-circle">{{ __('Mark Delivered') }}</x-button>
                        @endif
                    </div>
                    <div class="mt-4 pt-3 border-t border-base-200">
                        <x-button class="btn-error btn-outline btn-sm btn-block" wire:click="cancelOrder" wire:confirm="{{ __('Cancel this order? Reserved stock will be released.') }}" icon="o-x-mark">{{ __('Cancel Order') }}</x-button>
                    </div>
                </div>
                @endif
            </x-card>

            {{-- Payment --}}
            <x-card title="{{ __('Payment') }}" icon="o-credit-card">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm">
                        <div class="font-bold uppercase">{{ $order->payment_method }}</div>
                        <div class="opacity-70">{{ __('Method') }}</div>
                    </div>
                    <div>
                        <span class="badge {{ $order->payment_status === 'paid' ? 'badge-success' : ($order->payment_status === 'refunded' ? 'badge-error' : 'badge-warning') }}">
                            {{ ucfirst($order->payment_status) }}
                        </span>
                    </div>
                </div>
                <div class="divider my-1"></div>
                <div class="flex justify-between text-sm">
                    <span class="opacity-70">{{ __('Grand Total') }}</span>
                    <span class="font-mono font-bold">৳{{ number_format($order->grand_total, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="opacity-70">{{ __('Paid') }}</span>
                    <span class="font-mono text-success">৳{{ number_format($order->paid_amount, 0) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold">
                    <span>{{ __('Due') }}</span>
                    <span class="font-mono text-error">৳{{ number_format(max(0, $order->grand_total - $order->paid_amount), 0) }}</span>
                </div>
                @if($order->payment_status !== 'paid' && $order->payment_status !== 'refunded')
                    <x-button class="btn-success btn-block mt-4" icon="o-check-circle" wire:click="markAsPaid" wire:confirm="{{ __('Confirm payment received?') }}">{{ __('Mark as Paid') }}</x-button>
                @endif
            </x-card>

            {{-- Timeline --}}
            <x-card title="{{ __('Timeline') }}" icon="o-clock">
                <ul class="timeline timeline-vertical timeline-compact -ml-4">
                    <li>
                        <div class="timeline-start timeline-box text-xs bg-base-200/50">
                            <span class="font-bold block">{{ __('Order Placed') }}</span>
                            <span class="opacity-60">{{ $order->created_at->format('d M y H:i') }}</span>
                        </div>
                        <div class="timeline-middle"><x-icon name="o-check-circle" class="w-4 h-4 text-success" /></div>
                        <hr class="bg-success" />
                    </li>
                    @foreach($order->statusLogs as $log)
                        <li>
                            <hr class="bg-success" />
                            <div class="timeline-start timeline-box text-xs bg-base-200/50">
                                <span class="font-bold block">{{ ucfirst($log->to_status) }}</span>
                                <span class="opacity-60">{{ $log->created_at->format('d M y H:i') }}</span>
                                @if($log->note)
                                    <span class="block opacity-50 mt-0.5">{{ $log->note }}</span>
                                @endif
                                @if($log->user)
                                    <span class="text-primary mt-0.5 block">by {{ $log->user->name }}</span>
                                @endif
                            </div>
                            <div class="timeline-middle"><x-icon name="o-check-circle" class="w-4 h-4 text-success" /></div>
                            <hr class="{{ $loop->last ? '' : 'bg-success' }}" />
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>
    </div>
</div>
