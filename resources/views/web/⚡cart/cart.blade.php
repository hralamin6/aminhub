<div class="container mx-auto px-4 py-10 max-w-5xl">
    <div class="flex items-center gap-3 mb-8 pb-4 border-b border-base-200">
        <x-icon name="o-shopping-cart" class="w-8 h-8 text-primary" />
        <h1 class="text-2xl md:text-3xl font-bold">{{ __('Shopping Cart') }}</h1>
        <div class="ml-auto text-sm font-medium badge badge-primary">{{ app(\App\Services\CartService::class)->getItemCount() }} {{ __('Items') }}</div>
    </div>

    @if(app(\App\Services\CartService::class)->getItemCount() > 0)
        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Cart Items --}}
            <div class="lg:col-span-2 space-y-4">
                @foreach($this->cartItems as $key => $item)
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 bg-base-100 p-4 border border-base-200 rounded-xl relative group" wire:key="{{ $key }}">
                        {{-- Image --}}
                        <div class="w-20 h-20 flex-shrink-0 bg-white border border-base-200 rounded-lg overflow-hidden flex items-center justify-center">
                            @if($item['image'])
                                <img src="{{ $item['image'] }}" class="object-contain w-full h-full" alt="" />
                            @else
                                <x-icon name="o-photo" class="w-8 h-8 opacity-20" />
                            @endif
                        </div>
                        
                        {{-- Details --}}
                        <div class="flex-1 min-w-0 pr-8 sm:pr-0">
                            <a href="/product/{{ $item['variant_id'] }}" wire:navigate class="font-bold text-base block hover:text-primary transition-colors">{{ $item['name'] }}</a>
                            <p class="text-sm text-base-content/60">{{ $item['variant_name'] }} · {{ $item['sku'] }}</p>
                            <div class="mt-2 text-primary font-bold hidden sm:block">৳{{ number_format($item['unit_price'], 0) }}</div>
                        </div>

                        {{-- Quantity & Price --}}
                        <div class="flex items-center sm:items-end flex-row justify-between w-full sm:w-auto mt-2 sm:mt-0 gap-4 sm:flex-col">
                            <div class="text-primary font-bold sm:hidden block">৳{{ number_format($item['unit_price'], 0) }}</div>
                            
                            <div class="join border border-base-300 rounded-lg bg-base-100">
                                <button class="join-item btn btn-sm btn-ghost w-8 hover:bg-base-300" wire:click="decrement('{{ $key }}', {{ $item['quantity'] }})">−</button>
                                <input type="text" readonly class="join-item input input-sm w-12 text-center bg-transparent font-mono font-bold border-x border-base-300 pointer-events-none" value="{{ $item['quantity'] }}" />
                                <button class="join-item btn btn-sm btn-ghost w-8 hover:bg-base-300" wire:click="increment('{{ $key }}', {{ $item['quantity'] }})">+</button>
                            </div>

                            <div class="font-bold text-lg font-mono text-right sm:min-w-[80px]">
                                ৳{{ number_format($item['quantity'] * $item['unit_price'], 0) }}
                            </div>
                        </div>

                        {{-- Remove button --}}
                        <button class="btn btn-sm btn-circle btn-ghost text-error absolute top-2 right-2 sm:opacity-50 sm:group-hover:opacity-100" title="{{ __('Remove Item') }}" wire:click="removeItem('{{ $key }}')">
                            <x-icon name="o-trash" class="w-4 h-4" />
                        </button>
                    </div>
                @endforeach

                <div class="flex justify-start">
                    <button class="btn btn-ghost btn-sm text-error" wire:click="clearCart">
                        <x-icon name="o-x-circle" class="w-4 h-4" /> {{ __('Clear Cart') }}
                    </button>
                </div>
            </div>

            {{-- Checkout Summary --}}
            <div class="lg:col-span-1">
                <div class="bg-base-100 border border-base-200 rounded-xl p-6 shadow-sm sticky top-24">
                    <h2 class="font-bold text-lg mb-4 border-b border-base-200 pb-2">{{ __('Order Summary') }}</h2>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-base-content/70">
                            <span>{{ __('Subtotal') }}</span>
                            <span class="font-mono">৳{{ number_format($this->cartTotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-base-content/70">
                            <span>{{ __('Delivery Charge') }}</span>
                            <span class="font-mono text-sm border-b border-dashed border-base-300 cursor-help" title="Calculated at checkout">{{ __('Calculated next') }}</span>
                        </div>
                        <div class="divider my-1"></div>
                        <div class="flex justify-between text-lg font-bold">
                            <span>{{ __('Estimated Total') }}</span>
                            <span class="text-primary font-mono">৳{{ number_format($this->cartTotal, 0) }}</span>
                        </div>
                    </div>

                    @auth
                        <a href="/checkout" wire:navigate class="btn btn-primary btn-block text-base h-12 gap-2">
                            {{ __('Proceed to Checkout') }} <x-icon name="o-arrow-right" class="w-5 h-5" />
                        </a>
                    @else
                        <div class="bg-warning/10 border border-warning/20 p-4 rounded-lg text-sm text-warning-content mb-4 text-center">
                            {{ __('Please login or register to proceed to checkout.') }}
                        </div>
                        <div class="flex flex-col gap-2">
                            <a href="{{ route('login') }}" wire:navigate class="btn btn-primary btn-block text-base">{{ __('Login to Checkout') }}</a>
                            <a href="{{ route('register') ?? '#' }}" wire:navigate class="btn btn-outline btn-block text-base">{{ __('Create Account') }}</a>
                        </div>
                    @endauth

                    <div class="mt-6 text-center">
                        <a href="/shop" wire:navigate class="text-sm font-medium text-base-content/60 hover:text-primary transition-colors">
                            <x-icon name="o-arrow-left" class="w-3 h-3 inline pb-0.5" /> {{ __('Continue Shopping') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Empty Cart --}}
        <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
            <div class="w-32 h-32 bg-base-200 rounded-full flex items-center justify-center mb-6">
                <x-icon name="o-shopping-bag" class="w-16 h-16 opacity-30" />
            </div>
            <h2 class="text-2xl font-bold mb-2">{{ __('Your cart is empty') }}</h2>
            <p class="text-base-content/60 mb-8 max-w-sm">{{ __('Looks like you haven\'t added anything to your cart yet. Discover some amazing products in our shop.') }}</p>
            <a href="/shop" wire:navigate class="btn btn-primary px-8">{{ __('Start Shopping') }}</a>
        </div>
    @endif
</div>
