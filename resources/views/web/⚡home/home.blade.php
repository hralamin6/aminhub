<div>
    {{-- ═══════ HERO SECTION ═══════ --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-green-800 via-green-700 to-emerald-600 text-white">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none"><defs><pattern id="grain" width="4" height="4" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="0.5" fill="white"/></pattern></defs><rect width="100" height="100" fill="url(#grain)"/></svg>
        </div>
        <div class="container mx-auto px-4 py-16 md:py-24 relative z-10">
            <div class="max-w-3xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1.5 text-sm mb-6 border border-white/20">
                    <span class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></span>
                    {{ __('কৃষি পণ্যের বিশ্বস্ত দোকান') }}
                </div>
                <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-6 tracking-tight">
                    {{ setting('site_name', 'AminHub') }}
                    <span class="block text-green-200 text-2xl md:text-3xl font-medium mt-2">{{ setting('tagline', 'Agro Retail Store') }}</span>
                </h1>
                <p class="text-lg md:text-xl text-white/80 mb-8 max-w-xl mx-auto leading-relaxed">
                    {{ __('সার, কীটনাশক, বীজ — কৃষি কাজের জন্য সব কিছু এক জায়গায়। ভালো মানের পণ্য, সঠিক দামে।') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="/shop" wire:navigate class="btn btn-lg bg-white text-green-800 hover:bg-green-50 border-0 shadow-lg shadow-black/20 font-bold px-8">
                        <x-icon name="o-shopping-bag" class="w-5 h-5" /> {{ __('Shop Now') }}
                    </a>
                    <a href="/order-tracking" wire:navigate class="btn btn-lg btn-outline border-white/30 text-white hover:bg-white/10 hover:border-white/60">
                        <x-icon name="o-magnifying-glass" class="w-5 h-5" /> {{ __('Track Order') }}
                    </a>
                </div>
            </div>
        </div>
        {{-- Bottom wave --}}
        <div class="absolute bottom-0 left-0 right-0">
            <svg viewBox="0 0 1440 100" fill="none" class="w-full"><path d="M0,60 C480,100 960,20 1440,60 L1440,100 L0,100 Z" class="fill-base-100"/></svg>
        </div>
    </section>

    {{-- ═══════ CATEGORIES ═══════ --}}
    @if($this->categories->isNotEmpty())
    <section class="container mx-auto px-4 py-12 md:py-16">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold">{{ __('Browse Categories') }}</h2>
                <p class="text-base-content/60 text-sm mt-1">{{ __('পণ্য ক্যাটাগরি অনুযায়ী খুঁজুন') }}</p>
            </div>
            <a href="/shop" wire:navigate class="btn btn-ghost btn-sm">{{ __('View All') }} <x-icon name="o-arrow-right" class="w-4 h-4" /></a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($this->categories as $cat)
                <a href="/shop/{{ $cat->slug }}" wire:navigate
                   class="group flex flex-col items-center p-5 rounded-2xl border border-base-200 bg-base-100 hover:shadow-xl hover:border-primary/30 hover:-translate-y-1 transition-all duration-300">
                    <div class="w-14 h-14 rounded-full bg-primary/10 flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                        <x-icon name="{{ $cat->icon ?? 'o-tag' }}" class="w-7 h-7 text-primary" />
                    </div>
                    <span class="font-semibold text-sm text-center leading-tight">{{ $cat->name }}</span>
                    <span class="text-xs text-base-content/50 mt-1">{{ $cat->products_count }} {{ __('items') }}</span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══════ FEATURED PRODUCTS ═══════ --}}
    @if($this->featuredProducts->isNotEmpty())
    <section class="bg-base-200/40 py-12 md:py-16">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold">⭐ {{ __('Featured Products') }}</h2>
                    <p class="text-base-content/60 text-sm mt-1">{{ __('আমাদের সেরা পণ্যগুলো') }}</p>
                </div>
                <a href="/shop" wire:navigate class="btn btn-ghost btn-sm">{{ __('See All') }} <x-icon name="o-arrow-right" class="w-4 h-4" /></a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
                @foreach($this->featuredProducts as $product)
                    @php $variant = $product->variants->first(); @endphp
                    @if($variant)
                    <div class="group card bg-base-100 shadow-sm border border-base-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                        <figure class="relative h-48 md:h-56 bg-base-200/50 overflow-hidden cursor-pointer" wire:click="$navigate('/product/{{ $product->slug }}')">
                            @if($product->primaryImageUrl)
                                <img src="{{ $product->primaryImageUrl }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-4 group-hover:scale-110 transition-transform duration-500" loading="lazy" />
                            @else
                                <div class="flex items-center justify-center h-full"><x-icon name="o-photo" class="w-16 h-16 opacity-10" /></div>
                            @endif
                            @if($product->is_featured)
                                <div class="absolute top-2 left-2 badge badge-warning badge-sm gap-1 shadow">⭐ {{ __('Featured') }}</div>
                            @endif
                        </figure>
                        <div class="card-body p-3 md:p-4">
                            <a href="/product/{{ $product->slug }}" wire:navigate class="hover:text-primary transition-colors">
                                <h3 class="font-bold text-sm md:text-base line-clamp-2 min-h-[2.5rem] leading-tight">{{ $product->name }}</h3>
                            </a>
                            @if($product->brand)
                                <p class="text-xs text-base-content/50">{{ $product->brand->name }}</p>
                            @endif
                            <div class="flex items-end justify-between mt-2 gap-2">
                                <div>
                                    <span class="text-lg md:text-xl font-bold text-primary font-mono">৳{{ number_format($variant->retail_price, 0) }}</span>
                                    <span class="text-xs text-base-content/50 block">/{{ $variant->name }}</span>
                                </div>
                                <button class="btn btn-primary btn-sm btn-circle opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                                        wire:click.stop="addToCart({{ $variant->id }})" title="{{ __('Add to Cart') }}">
                                    <x-icon name="o-shopping-cart" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════ NEW ARRIVALS ═══════ --}}
    @if($this->newArrivals->isNotEmpty())
    <section class="container mx-auto px-4 py-12 md:py-16">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold">🆕 {{ __('New Arrivals') }}</h2>
                <p class="text-base-content/60 text-sm mt-1">{{ __('সদ্য যোগ হওয়া পণ্যগুলো') }}</p>
            </div>
            <a href="/shop" wire:navigate class="btn btn-ghost btn-sm">{{ __('See All') }} <x-icon name="o-arrow-right" class="w-4 h-4" /></a>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            @foreach($this->newArrivals as $product)
                @php $variant = $product->variants->first(); @endphp
                @if($variant)
                <div class="group card bg-base-100 shadow-sm border border-base-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                    <figure class="relative h-48 md:h-56 bg-base-200/50 overflow-hidden cursor-pointer" wire:click="$navigate('/product/{{ $product->slug }}')">
                        @if($product->primaryImageUrl)
                            <img src="{{ $product->primaryImageUrl }}" alt="{{ $product->name }}" class="h-full w-full object-contain p-4 group-hover:scale-110 transition-transform duration-500" loading="lazy" />
                        @else
                            <div class="flex items-center justify-center h-full"><x-icon name="o-photo" class="w-16 h-16 opacity-10" /></div>
                        @endif
                        <div class="absolute top-2 left-2 badge badge-accent badge-sm gap-1 shadow">{{ __('New') }}</div>
                    </figure>
                    <div class="card-body p-3 md:p-4">
                        <a href="/product/{{ $product->slug }}" wire:navigate class="hover:text-primary transition-colors">
                            <h3 class="font-bold text-sm md:text-base line-clamp-2 min-h-[2.5rem] leading-tight">{{ $product->name }}</h3>
                        </a>
                        @if($product->category)
                            <p class="text-xs text-base-content/50">{{ $product->category->name }}</p>
                        @endif
                        <div class="flex items-end justify-between mt-2 gap-2">
                            <div>
                                <span class="text-lg md:text-xl font-bold text-primary font-mono">৳{{ number_format($variant->retail_price, 0) }}</span>
                                <span class="text-xs text-base-content/50 block">/{{ $variant->name }}</span>
                            </div>
                            <button class="btn btn-primary btn-sm btn-circle opacity-0 group-hover:opacity-100 transition-opacity shadow-lg"
                                    wire:click.stop="addToCart({{ $variant->id }})" title="{{ __('Add to Cart') }}">
                                <x-icon name="o-shopping-cart" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══════ BRANDS ═══════ --}}
    @if($this->brands->isNotEmpty())
    <section class="bg-base-200/40 py-12 md:py-16">
        <div class="container mx-auto px-4">
            <div class="text-center mb-8">
                <h2 class="text-2xl md:text-3xl font-bold">{{ __('Our Brands') }}</h2>
                <p class="text-base-content/60 text-sm mt-1">{{ __('বিশ্বস্ত ব্র্যান্ড পণ্য') }}</p>
            </div>
            <div class="flex flex-wrap justify-center gap-4">
                @foreach($this->brands as $brand)
                    <a href="/shop?brand={{ $brand->id }}" wire:navigate
                       class="flex items-center gap-2 px-5 py-3 bg-base-100 rounded-full border border-base-200 hover:border-primary/30 hover:shadow-md transition-all text-sm font-medium">
                        {{ $brand->name }}
                        <span class="badge badge-ghost badge-xs">{{ $brand->products_count }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- ═══════ INFO STRIP ═══════ --}}
    <section class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
            <div class="flex flex-col items-center text-center p-5 rounded-xl bg-base-100 border border-base-200">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-3"><x-icon name="o-truck" class="w-6 h-6 text-primary" /></div>
                <span class="font-bold text-sm">{{ __('দ্রুত ডেলিভারি') }}</span>
                <span class="text-xs text-base-content/50 mt-1">{{ __('সারাদেশে পৌঁছে দেওয়া হয়') }}</span>
            </div>
            <div class="flex flex-col items-center text-center p-5 rounded-xl bg-base-100 border border-base-200">
                <div class="w-12 h-12 rounded-full bg-success/10 flex items-center justify-center mb-3"><x-icon name="o-shield-check" class="w-6 h-6 text-success" /></div>
                <span class="font-bold text-sm">{{ __('আসল পণ্য') }}</span>
                <span class="text-xs text-base-content/50 mt-1">{{ __('100% অরিজিনাল') }}</span>
            </div>
            <div class="flex flex-col items-center text-center p-5 rounded-xl bg-base-100 border border-base-200">
                <div class="w-12 h-12 rounded-full bg-warning/10 flex items-center justify-center mb-3"><x-icon name="o-phone" class="w-6 h-6 text-warning" /></div>
                <span class="font-bold text-sm">{{ __('সাপোর্ট') }}</span>
                <span class="text-xs text-base-content/50 mt-1">{{ __('সকাল ৮টা - রাত ১০টা') }}</span>
            </div>
            <div class="flex flex-col items-center text-center p-5 rounded-xl bg-base-100 border border-base-200">
                <div class="w-12 h-12 rounded-full bg-info/10 flex items-center justify-center mb-3"><x-icon name="o-banknotes" class="w-6 h-6 text-info" /></div>
                <span class="font-bold text-sm">{{ __('ক্যাশ অন ডেলিভারি') }}</span>
                <span class="text-xs text-base-content/50 mt-1">{{ __('bKash / Nagad / COD') }}</span>
            </div>
        </div>
    </section>
</div>
