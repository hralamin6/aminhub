<div class="container mx-auto px-4 py-8">
    <div class="mb-8 border-b border-base-200 pb-4">
        <h1 class="text-2xl md:text-3xl font-bold">{{ __('Checkout') }}</h1>
    </div>

    <form wire:submit="placeOrder" class="grid lg:grid-cols-2 gap-8 lg:gap-12">
        {{-- Left: Billing and Delivery Details --}}
        <div class="space-y-8">
            {{-- Addresses --}}
            <section class="bg-base-100 p-6 rounded-xl border border-base-200 shadow-sm">
                <h2 class="text-lg font-bold mb-4 flex items-center gap-2"><x-icon name="o-map-pin" class="w-5 h-5 text-primary" /> {{ __('Delivery Address') }}</h2>
                
                @if(count($this->addresses) > 0)
                    <div class="space-y-3 mb-4">
                        @foreach($this->addresses as $address)
                            <label class="flex items-start gap-3 p-4 border rounded-lg cursor-pointer hover:bg-base-200/50 transition-colors {{ $address_id == $address->id && !$use_new_address ? 'border-primary bg-primary/5' : 'border-base-200' }}">
                                <input type="radio" wire:model.live="address_id" value="{{ $address->id }}" class="radio radio-primary mt-1" wire:click="$set('use_new_address', false)" />
                                <div>
                                    <div class="font-bold flex items-center gap-2">
                                        {{ $address->full_name }}
                                        @if($address->label)<span class="badge badge-sm badge-ghost">{{ $address->label }}</span>@endif
                                        @if($address->is_default)<span class="badge badge-sm badge-primary badge-outline">{{ __('Default') }}</span>@endif
                                    </div>
                                    <div class="text-sm text-base-content/70 mt-1">{{ $address->phone }}</div>
                                    <div class="text-sm text-base-content/70 mt-1">{{ $address->address_line }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer font-medium text-sm text-primary mb-4 p-2">
                        <input type="radio" wire:model.live="use_new_address" value="true" class="radio radio-sm radio-primary" />
                        {{ __('Add a new address') }}
                    </label>
                @endif

                {{-- New Address Form --}}
                @if($use_new_address || count($this->addresses) == 0)
                    <div class="space-y-4 p-4 bg-base-200/30 rounded-lg border border-base-200">
                        <x-input label="Full Name" wire:model="new_address.full_name" required icon="o-user" />
                        <x-input label="Phone Number" wire:model="new_address.phone" required icon="o-phone" />
                        <x-textarea label="Delivery Address" wire:model="new_address.address_line" rows="3" required placeholder="House, Street, Area..." />
                    </div>
                @endif
            </section>

            {{-- Delivery Method --}}
            <section class="bg-base-100 p-6 rounded-xl border border-base-200 shadow-sm">
                <h2 class="text-lg font-bold mb-4 flex items-center gap-2"><x-icon name="o-truck" class="w-5 h-5 text-primary" /> {{ __('Delivery Method') }}</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <label class="flex flex-col p-4 border rounded-lg cursor-pointer hover:bg-base-200/50 transition-colors {{ $delivery_method === 'courier' ? 'border-primary bg-primary/5' : 'border-base-200' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <input type="radio" wire:model="delivery_method" value="courier" class="radio radio-primary radio-sm" />
                            <span class="font-bold">{{ __('Courier Delivery') }}</span>
                        </div>
                        <span class="text-xs text-base-content/60 ml-6">{{ __('Home delivery via courier') }}</span>
                    </label>
                    <label class="flex flex-col p-4 border rounded-lg cursor-pointer hover:bg-base-200/50 transition-colors {{ $delivery_method === 'shop' ? 'border-primary bg-primary/5' : 'border-base-200' }}">
                        <div class="flex items-center gap-2 mb-2">
                            <input type="radio" wire:model="delivery_method" value="shop" class="radio radio-primary radio-sm" />
                            <span class="font-bold">{{ __('Shop Pickup') }}</span>
                        </div>
                        <span class="text-xs text-base-content/60 ml-6">{{ __('Collect from our store') }}</span>
                    </label>
                </div>
            </section>

            {{-- Payment Method --}}
            <section class="bg-base-100 p-6 rounded-xl border border-base-200 shadow-sm">
                <h2 class="text-lg font-bold mb-4 flex items-center gap-2"><x-icon name="o-credit-card" class="w-5 h-5 text-primary" /> {{ __('Payment Method') }}</h2>
                <div class="space-y-3">
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-base-200/50 transition-colors {{ $payment_method === 'cash' ? 'border-primary bg-primary/5' : 'border-base-200' }}">
                        <input type="radio" wire:model="payment_method" value="cash" class="radio radio-primary mr-3" />
                        <div>
                            <div class="font-bold">{{ __('Cash on Delivery') }}</div>
                            <div class="text-xs text-base-content/60">{{ __('Pay when you receive the order') }}</div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-base-200/50 transition-colors {{ $payment_method === 'bkash' ? 'border-primary bg-primary/5' : 'border-base-200' }}">
                        <input type="radio" wire:model="payment_method" value="bkash" class="radio radio-primary mr-3" />
                        <div>
                            <div class="font-bold">{{ __('bKash Payment') }}</div>
                            <div class="text-xs text-base-content/60">{{ __('Pay online via bKash') }}</div>
                        </div>
                    </label>
                </div>
            </section>
        </div>

        {{-- Right: Order Summary --}}
        <div>
            <div class="bg-base-100 border border-base-200 rounded-xl p-6 shadow-sm sticky top-24">
                <h2 class="font-bold text-lg mb-4 border-b border-base-200 pb-2 flex items-center gap-2"><x-icon name="o-clipboard-document-list" class="w-5 h-5 text-primary" /> {{ __('Order Summary') }}</h2>
                
                {{-- Items --}}
                <div class="max-h-60 overflow-y-auto pr-2 mb-4 space-y-3 border-b border-base-200 pb-4">
                    @foreach($this->cartItems as $item)
                        <div class="flex gap-3 text-sm">
                            <div class="w-12 h-12 bg-base-200 rounded flex items-center justify-center flex-shrink-0">
                                @if($item['image'])
                                    <img src="{{ $item['image'] }}" class="w-10 h-10 object-contain" />
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $item['name'] }}</div>
                                <div class="text-[10px] text-base-content/60">{{ $item['variant_name'] }} x {{ $item['quantity'] }}</div>
                            </div>
                            <div class="font-mono font-bold">৳{{ number_format($item['quantity'] * $item['unit_price'], 0) }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-base-content/70">
                        <span>{{ __('Subtotal') }}</span>
                        <span class="font-mono">৳{{ number_format($this->cartTotal, 0) }}</span>
                    </div>
                    <div class="flex justify-between text-base-content/70">
                        <span>{{ __('Delivery Charge') }}</span>
                        <span class="font-mono text-sm border-b border-dashed border-base-300">
                            {{ $delivery_method === 'shop' ? '৳0' : __('TBD') }}
                        </span>
                    </div>
                    <div class="divider my-1"></div>
                    <div class="flex justify-between text-lg font-bold">
                        <span>{{ __('Total') }}</span>
                        <span class="text-primary font-mono">৳{{ number_format($this->cartTotal, 0) }}</span>
                    </div>
                </div>

                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-block text-base h-12 gap-2 shadow-lg shadow-primary/30">
                    <span wire:loading.remove wire:target="placeOrder">{{ __('Place Order') }}</span>
                    <span wire:loading wire:target="placeOrder" class="loading loading-spinner"></span>
                </button>
            </div>
        </div>
    </form>
</div>
