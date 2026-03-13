<div>
    {{-- ═══════ SHOP HEADER ═══════ --}}
    <div class="bg-gradient-to-r from-green-700 to-emerald-600 text-white">
        <div class="container mx-auto px-4 py-8 md:py-12">
            <h1 class="text-3xl md:text-4xl font-bold">
                @if($this->selectedCategory)
                    {{ $this->selectedCategory->name }}
                @else
                    {{ __('সব পণ্য') }}
                @endif
            </h1>
            <p class="text-white/70 mt-2 text-sm md:text-base">
                @if($this->selectedCategory && $this->selectedCategory->description)
                    {{ $this->selectedCategory->description }}
                @else
                    {{ __('কৃষি পণ্যসমূহ ব্রাউজ করুন') }}
                @endif
            </p>

            {{-- Breadcrumb --}}
            <div class="flex flex-wrap items-center gap-1 mt-4 text-xs text-white/60">
                <a href="/" wire:navigate class="hover:text-white">{{ __('Home') }}</a>
                <span>/</span>
                <a href="/shop" wire:navigate class="hover:text-white">{{ __('Shop') }}</a>
                @if($this->selectedCategory)
                    <span>/</span>
                    <span class="text-white/90 font-medium">{{ $this->selectedCategory->name }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 md:py-8">
        {{-- ═══════ TOOLBAR ═══════ --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3 flex-1">
                {{-- Search --}}
                <div class="relative flex-1 max-w-md">
                    <x-icon name="o-magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40" />
                    <input type="text" wire:model.live.debounce.400ms="search"
                           class="input input-bordered input-sm w-full pl-10 bg-base-100"
                           placeholder="{{ __('Search products, brands...') }}" />
                    @if($search)
                        <button class="absolute right-2 top-1/2 -translate-y-1/2" wire:click="$set('search', '')">
                            <x-icon name="o-x-mark" class="w-4 h-4 text-base-content/40 hover:text-error" />
                        </button>
                    @endif
                </div>

                {{-- Mobile filter toggle --}}
                <button class="btn btn-sm btn-outline gap-1 md:hidden" wire:click="$toggle('showFilters')">
                    <x-icon name="o-funnel" class="w-4 h-4" />
                    {{ __('Filters') }}
                    @if($this->activeFilterCount > 0)
                        <span class="badge badge-primary badge-xs">{{ $this->activeFilterCount }}</span>
                    @endif
                </button>
            </div>

            <div class="flex items-center gap-3">
                {{-- Active filters summary --}}
                @if($this->activeFilterCount > 0)
                    <button class="btn btn-ghost btn-xs text-error gap-1" wire:click="clearFilters">
                        <x-icon name="o-x-mark" class="w-3 h-3" />
                        {{ __('Clear all') }}
                    </button>
                @endif

                {{-- Sort --}}
                <select wire:model.live="sortBy" class="select select-bordered select-sm bg-base-100 min-w-[180px]">
                    <option value="newest">{{ __('Newest First') }}</option>
                    <option value="price_asc">{{ __('Price: Low → High') }}</option>
                    <option value="price_desc">{{ __('Price: High → Low') }}</option>
                    <option value="name_asc">{{ __('Name: A → Z') }}</option>
                    <option value="popular">{{ __('Popular') }}</option>
                </select>

                {{-- Product count --}}
                <span class="text-xs text-base-content/50 hidden md:block whitespace-nowrap">
                    {{ $this->products->total() }} {{ __('products') }}
                </span>
            </div>
        </div>

        <div class="flex gap-6">
            {{-- ═══════ SIDEBAR FILTERS ═══════ --}}
            <aside class="w-64 flex-shrink-0 hidden md:block {{ $showFilters ? '!block fixed inset-0 z-50 bg-base-100 p-6 overflow-y-auto md:relative md:inset-auto md:z-auto md:p-0' : '' }}"
                   @if($showFilters) x-data @click.self="$wire.set('showFilters', false)" @endif>

                @if($showFilters)
                    <div class="flex justify-between items-center mb-4 md:hidden">
                        <h3 class="font-bold text-lg">{{ __('Filters') }}</h3>
                        <button class="btn btn-ghost btn-sm btn-circle" wire:click="$set('showFilters', false)"><x-icon name="o-x-mark" class="w-5 h-5" /></button>
                    </div>
                @endif

                {{-- Categories --}}
                <div class="mb-6">
                    <h3 class="font-bold text-sm uppercase tracking-wider text-base-content/50 mb-3">{{ __('Categories') }}</h3>
                    <ul class="space-y-0.5">
                        <li>
                            <button wire:click="setCategory(null)"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ !$category ? 'bg-primary/10 text-primary' : 'hover:bg-base-200' }}">
                                {{ __('All Categories') }}
                            </button>
                        </li>
                        @foreach($this->categories as $cat)
                            <li>
                                <button wire:click="setCategory({{ $cat->id }})"
                                        class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between {{ $category === $cat->id ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-base-200' }}">
                                    <span>{{ $cat->name }}</span>
                                    <span class="badge badge-ghost badge-xs">{{ $cat->products_count }}</span>
                                </button>
                                @if($cat->children->isNotEmpty())
                                    <ul class="ml-4 mt-0.5 space-y-0.5 border-l-2 border-base-200 pl-2">
                                        @foreach($cat->children as $child)
                                            <li>
                                                <button wire:click="setCategory({{ $child->id }})"
                                                        class="w-full text-left px-2 py-1.5 rounded text-xs transition-colors flex items-center justify-between {{ $category === $child->id ? 'text-primary font-semibold' : 'text-base-content/70 hover:bg-base-200' }}">
                                                    <span>{{ $child->name }}</span>
                                                    <span class="text-base-content/30">{{ $child->products_count }}</span>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Brands --}}
                @if($this->brands->isNotEmpty())
                <div class="mb-6">
                    <h3 class="font-bold text-sm uppercase tracking-wider text-base-content/50 mb-3">{{ __('Brands') }}</h3>
                    <ul class="space-y-0.5">
                        <li>
                            <button wire:click="setBrand(null)"
                                    class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors {{ !$brand ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-base-200' }}">
                                {{ __('All Brands') }}
                            </button>
                        </li>
                        @foreach($this->brands as $b)
                            <li>
                                <button wire:click="setBrand({{ $b->id }})"
                                        class="w-full text-left px-3 py-2 rounded-lg text-sm transition-colors flex items-center justify-between {{ $brand === $b->id ? 'bg-primary/10 text-primary font-semibold' : 'hover:bg-base-200' }}">
                                    <span>{{ $b->name }}</span>
                                    <span class="badge badge-ghost badge-xs">{{ $b->products_count }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Price Range --}}
                <div class="mb-6">
                    <h3 class="font-bold text-sm uppercase tracking-wider text-base-content/50 mb-3">{{ __('Price Range') }}</h3>
                    <div class="flex items-center gap-2">
                        <input type="number" wire:model.live.debounce.500ms="minPrice" class="input input-bordered input-sm w-full" placeholder="৳ {{ __('Min') }}" min="0" />
                        <span class="text-base-content/30">—</span>
                        <input type="number" wire:model.live.debounce.500ms="maxPrice" class="input input-bordered input-sm w-full" placeholder="৳ {{ __('Max') }}" min="0" />
                    </div>
                </div>

                @if($showFilters)
                    <button class="btn btn-primary btn-block btn-sm md:hidden mt-4" wire:click="$set('showFilters', false)">{{ __('Apply Filters') }}</button>
                @endif
            </aside>

            {{-- ═══════ PRODUCT GRID ═══════ --}}
            <div class="flex-1 min-w-0">
                {{-- Active filter chips --}}
                @if($category || $brand || $search || $minPrice || $maxPrice)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($search)
                            <span class="badge badge-outline gap-1"> "{{ $search }}"
                                <button wire:click="$set('search', '')"><x-icon name="o-x-mark" class="w-3 h-3" /></button>
                            </span>
                        @endif
                        @if($this->selectedCategory)
                            <span class="badge badge-primary badge-outline gap-1"> {{ $this->selectedCategory->name }}
                                <button wire:click="setCategory(null)"><x-icon name="o-x-mark" class="w-3 h-3" /></button>
                            </span>
                        @endif
                        @if($brand)
                            @php $brandName = $this->brands->firstWhere('id', $brand)?->name; @endphp
                            <span class="badge badge-secondary badge-outline gap-1"> {{ $brandName }}
                                <button wire:click="setBrand(null)"><x-icon name="o-x-mark" class="w-3 h-3" /></button>
                            </span>
                        @endif
                        @if($minPrice || $maxPrice)
                            <span class="badge badge-outline gap-1">
                                ৳{{ $minPrice ?? '0' }} — ৳{{ $maxPrice ?? '∞' }}
                                <button wire:click="$set('minPrice', null); $set('maxPrice', null)"><x-icon name="o-x-mark" class="w-3 h-3" /></button>
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Products --}}
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 md:gap-5" wire:loading.class="opacity-50" wire:target="search, sortBy, category, brand, minPrice, maxPrice">
                    @forelse($this->products as $product)
                        @php $variant = $product->variants->first(); @endphp
                        @if($variant)
                        <div class="group card bg-base-100 shadow-sm border border-base-200 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-300 overflow-hidden">
                            {{-- Image --}}
                            <figure class="relative h-44 md:h-52 bg-base-200/30 overflow-hidden cursor-pointer" wire:click="$navigate('/product/{{ $product->slug }}')">
                                @if($product->primaryImageUrl)
                                    <img src="{{ $product->primaryImageUrl }}" alt="{{ $product->name }}"
                                         class="h-full w-full object-contain p-3 group-hover:scale-110 transition-transform duration-500" loading="lazy" />
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <x-icon name="o-photo" class="w-14 h-14 opacity-10" />
                                    </div>
                                @endif

                                {{-- Badges --}}
                                <div class="absolute top-2 left-2 flex flex-col gap-1">
                                    @if($product->is_featured)
                                        <span class="badge badge-warning badge-xs shadow">⭐</span>
                                    @endif
                                    @if($product->created_at >= now()->subDays(7))
                                        <span class="badge badge-accent badge-xs shadow">{{ __('New') }}</span>
                                    @endif
                                </div>

                                {{-- Quick add --}}
                                <button class="absolute bottom-2 right-2 btn btn-primary btn-xs btn-circle shadow-lg opacity-0 group-hover:opacity-100 translate-y-2 group-hover:translate-y-0 transition-all duration-300"
                                        wire:click.stop="addToCart({{ $variant->id }})" title="{{ __('Quick Add') }}">
                                    <x-icon name="o-plus" class="w-3.5 h-3.5" />
                                </button>
                            </figure>

                            {{-- Info --}}
                            <div class="card-body p-3 md:p-4 gap-1">
                                @if($product->brand)
                                    <span class="text-[10px] uppercase tracking-wider text-base-content/40 font-bold">{{ $product->brand->name }}</span>
                                @endif
                                <a href="/product/{{ $product->slug }}" wire:navigate>
                                    <h3 class="font-bold text-sm leading-tight line-clamp-2 min-h-[2.25rem] hover:text-primary transition-colors">{{ $product->name }}</h3>
                                </a>
                                @if($product->category)
                                    <span class="text-[10px] text-base-content/40">{{ $product->category->name }}</span>
                                @endif
                                <div class="flex items-end justify-between mt-1.5 pt-1.5 border-t border-base-200/50">
                                    <div>
                                        <span class="text-base md:text-lg font-bold text-primary font-mono leading-none">৳{{ number_format($variant->retail_price, 0) }}</span>
                                        <span class="text-[10px] text-base-content/40 block mt-0.5">/{{ $variant->name }}</span>
                                    </div>
                                    <button class="btn btn-primary btn-xs gap-1 hidden md:inline-flex opacity-0 group-hover:opacity-100 transition-opacity"
                                            wire:click="addToCart({{ $variant->id }})">
                                        <x-icon name="o-shopping-cart" class="w-3 h-3" /> {{ __('Add') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    @empty
                        <div class="col-span-full py-20 text-center">
                            <x-icon name="o-inbox" class="w-20 h-20 mx-auto mb-4 opacity-10" />
                            <h3 class="font-bold text-lg text-base-content/40">{{ __('No products found') }}</h3>
                            <p class="text-sm text-base-content/30 mt-1 mb-4">{{ __('Try adjusting your search or filters') }}</p>
                            @if($this->activeFilterCount > 0)
                                <button class="btn btn-primary btn-sm" wire:click="clearFilters">{{ __('Clear All Filters') }}</button>
                            @endif
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($this->products->hasPages())
                    <div class="mt-8 flex justify-center">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
