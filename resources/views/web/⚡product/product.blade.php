<div class="container mx-auto px-4 py-8">
    {{-- Breadcrumbs --}}
    <div class="text-sm breadcrumbs text-base-content/60 mb-6">
        <ul>
            <li><a href="/" wire:navigate>{{ __('Home') }}</a></li>
            <li><a href="/shop" wire:navigate>{{ __('Shop') }}</a></li>
            <li><a href="/shop/{{ $this->product->category?->slug }}" wire:navigate>{{ $this->product->category?->name ?? 'Category' }}</a></li>
            <li class="font-bold text-base-content">{{ $this->product->name }}</li>
        </ul>
    </div>

    {{-- Main Product Area --}}
    <div class="grid md:grid-cols-2 gap-8 lg:gap-12 bg-base-100 rounded-box md:p-8">
        {{-- Left: Image Gallery --}}
        <div>
            @if($this->product->hasMedia('product_image'))
                <figure class="rounded-xl overflow-hidden border border-base-200 bg-white mb-4 aspect-square flex items-center justify-center">
                    <img src="{{ $this->product->getFirstMediaUrl('product_image', 'large') }}" alt="{{ $this->product->name }}" class="object-contain h-full w-full max-h-[500px]" />
                </figure>
                {{-- Thumbnails --}}
                <div class="flex gap-2 overflow-x-auto pb-2">
                    @foreach($this->product->getMedia('product_image') as $media)
                        <button class="flex-shrink-0 w-20 h-20 border-2 rounded-lg overflow-hidden {{ $loop->first ? 'border-primary' : 'border-base-200 opacity-70' }}">
                            <img src="{{ $media->getUrl('thumb') }}" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-base-200 bg-base-200 aspect-square flex flex-col items-center justify-center text-base-content/30 mb-4">
                    <x-icon name="o-photo" class="w-24 h-24 mb-2 opacity-50" />
                    <p>{{ __('No Image Available') }}</p>
                </div>
            @endif
        </div>

        {{-- Right: Product Details --}}
        <div class="flex flex-col">
            <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold mb-2">{{ $this->product->name }}</h1>
            
            <div class="flex flex-wrap gap-4 text-sm text-base-content/70 mb-4 pb-4 border-b border-base-200">
                <p><strong>{{ __('Brand') }}:</strong> {{ $this->product->brand?->name ?? __('Generic') }}</p>
                <p><strong>{{ __('Category') }}:</strong> {{ $this->product->category?->name ?? __('Uncategorized') }}</p>
            </div>

            {{-- Price Display --}}
            <div class="mb-6">
                @if($this->selectedVariant)
                    <div class="text-3xl md:text-4xl font-extrabold text-primary">
                        ৳{{ number_format($this->selectedVariant->retail_price, 0) }}
                    </div>
                    @if($this->selectedVariant->wholesale_price > 0 && auth()->check() && auth()->user()->hasRole('wholesale_customer'))
                        <div class="text-sm mt-1 text-success">{{ __('Wholesale Price') }}: ৳{{ number_format($this->selectedVariant->wholesale_price, 0) }}</div>
                    @endif
                @else
                    <div class="text-2xl font-bold text-base-content/50">{{ __('Select a variant') }}</div>
                @endif
            </div>

            {{-- Description Snippet --}}
            @if($this->product->description)
                <div class="mb-6 prose prose-sm max-w-none text-base-content/80">
                    <p>{{ Str::limit(strip_tags($this->product->description), 200) }}</p>
                </div>
            @endif

            {{-- Options & Add To Cart --}}
            <div class="mt-auto p-4 bg-base-200/50 rounded-xl space-y-4 border border-base-200">
                {{-- Variant Selector --}}
                @if($this->product->variants->count() > 1)
                    <div>
                        <label class="font-semibold block mb-2">{{ __('Select Option / Size') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($this->product->variants as $variant)
                                <button type="button" 
                                    wire:click="$set('selectedVariantId', {{ $variant->id }})"
                                    class="border transition-all px-4 py-2 rounded-lg text-sm {{ $selectedVariantId === $variant->id ? 'border-primary bg-primary/10 text-primary font-bold shadow-sm' : 'border-base-300 bg-base-100 hover:border-primary/50' }}">
                                    {{ $variant->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Action Row --}}
                <div class="flex flex-col sm:flex-row gap-4 items-end sm:items-center">
                    {{-- Quantity Selector --}}
                    <div>
                        <label class="font-semibold block text-sm mb-1">{{ __('Quantity') }}</label>
                        <div class="join border border-base-300 rounded-lg">
                            <button class="join-item btn btn-sm btn-ghost w-10 text-lg hover:bg-base-300" wire:click="decrement">−</button>
                            <input type="text" class="join-item input input-sm w-16 text-center bg-base-100 font-mono font-bold border-x border-base-300 focus:outline-none" wire:model.live="quantity" />
                            <button class="join-item btn btn-sm btn-ghost w-10 text-lg hover:bg-base-300" wire:click="increment">+</button>
                        </div>
                    </div>

                    {{-- Add Button --}}
                    <button class="btn btn-primary flex-1 h-12" wire:click="addToCart" wire:loading.attr="disabled" {{ !$selectedVariantId ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="addToCart">
                            <x-icon name="o-shopping-bag" class="w-5 h-5 mr-1" />
                            {{ __('Add to Cart') }}
                            @if($this->selectedVariant)
                            <span class="opacity-70 ml-1 font-normal text-xs md:inline hidden">— ৳{{ number_format($this->selectedVariant->retail_price * max(1, $quantity), 0) }}</span>
                            @endif
                        </span>
                        <span wire:loading wire:target="addToCart" class="loading loading-spinner"></span>
                    </button>
                </div>
            </div>
            
            {{-- Extra info snippets --}}
            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-base-content/60 font-medium">
                <div class="flex items-center gap-1"><x-icon name="o-check-circle" class="w-4 h-4 text-success" /> {{ __('Secure Checkout') }}</div>
                <div class="flex items-center gap-1"><x-icon name="o-truck" class="w-4 h-4 text-info" /> {{ __('Fast Delivery') }}</div>
                <div class="flex items-center gap-1"><x-icon name="o-arrow-uturn-left" class="w-4 h-4" /> {{ __('Easy Returns') }}</div>
            </div>
        </div>
    </div>

    {{-- Tabs Content (Description / Specs) --}}
    @if($this->product->description)
        <div class="mt-12 bg-base-100 rounded-box p-6 border border-base-200">
            <h2 class="text-xl font-bold mb-4 pb-2 border-b border-base-200">{{ __('Product Information') }}</h2>
            <div class="prose max-w-none text-base-content/80">
                {!! $this->product->description !!}
            </div>
        </div>
    @endif

    {{-- Related Products --}}
    @if(count($this->relatedProducts) > 0)
        <div class="mt-16">
            <h2 class="text-2xl font-bold mb-6 text-center">{{ __('Related Products') }}</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                @foreach($this->relatedProducts as $relProduct)
                    <div class="card bg-base-100 shadow hover:shadow-lg transition-all border border-base-200 cursor-pointer" wire:click="$navigate('/product/{{ $relProduct->slug }}')">
                        <figure class="px-4 pt-4 h-40 bg-white">
                            @if($relProduct->hasMedia('product_image'))
                                <img src="{{ $relProduct->getFirstMediaUrl('product_image', 'medium') }}" alt="{{ $relProduct->name }}" class="h-full object-contain" loading="lazy" />
                            @else
                                <x-icon name="o-photo" class="w-12 h-12 opacity-10" />
                            @endif
                        </figure>
                        <div class="card-body p-4 text-center">
                            <h3 class="card-title text-sm min-h-[40px] leading-tight justify-center hover:text-primary transition-colors">
                                {{ $relProduct->name }}
                            </h3>
                            <p class="font-bold text-primary mt-2">
                                ৳{{ number_format($relProduct->variants->first()?->retail_price ?? 0, 0) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
