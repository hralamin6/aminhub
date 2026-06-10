<div class="min-h-screen bg-base-200/50" x-data="purchaseForm()" x-init="init()">

    {{-- Scoped styles --}}
    <style>
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }

        /* Compact choices */
        .choices__inner { min-height: 28px !important; padding: 3px 8px !important; font-size: 12px !important; border-radius: 0.5rem !important; }
        .choices__input { font-size: 12px !important; padding: 2px !important; }
        .choices__list--single { padding: 0 !important; }
        .choices__list--dropdown { max-height: 220px !important; border-radius: 0.5rem !important; box-shadow: 0 8px 24px -4px rgba(0,0,0,.15) !important; }
        .choices__list--dropdown .choices__item { padding: 5px 10px !important; min-height: 26px !important; font-size: 12px !important; }
        .choices__list--dropdown .choices__group .choices__heading { padding: 4px 10px !important; font-size: 10px !important; font-weight: 700 !important; letter-spacing: .05em !important; text-transform: uppercase !important; opacity: .5; }

        /* Item row hover */
        .purchase-item { transition: box-shadow .15s, border-color .15s; }
        .purchase-item:hover { box-shadow: 0 2px 12px -2px rgba(var(--color-primary) / .12); }

        /* Qty/price inputs */
        .num-input:focus { outline: 2px solid oklch(var(--p)); outline-offset: 1px; }
    </style>

    {{-- ══════════════════════ STICKY HEADER ══════════════════════ --}}
    <div class="sticky top-0 z-30 bg-base-100/95 backdrop-blur border-b border-base-200 shadow-sm">
        <div class="max-w-6xl mx-auto px-2 sm:px-4 py-2 sm:py-3 flex items-center justify-between gap-2 sm:gap-3">

            {{-- Left: title + badge --}}
            <div class="flex items-center gap-3 min-w-0">
                <div class="bg-primary/10 text-primary rounded-xl p-2 shrink-0">
                    <x-icon name="o-shopping-bag" class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                    <h1 class="font-bold text-base leading-tight truncate">
                        {{ $purchaseId ? __('Edit Purchase') : __('New Purchase') }}
                    </h1>
                    <p class="text-[11px] text-base-content/40 leading-none mt-0.5">
                        {{ now()->format('d M Y') }} &middot; {{ count($items) }} {{ __('item(s)') }}
                    </p>
                </div>
            </div>

            {{-- Right: actions --}}
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('app.purchases') }}" class="btn btn-ghost btn-sm gap-1.5 hidden sm:flex">
                    <x-icon name="o-arrow-left" class="w-4 h-4" />
                    {{ __('Back') }}
                </a>
                <x-button
                    label="{{ __('Draft') }}"
                    wire:click="saveDraft"
                    icon="o-document-text"
                    class="btn-warning btn-sm"
                    spinner
                />
                <x-button
                    label="{{ __('Receive') }}"
                    wire:click="saveReceived"
                    icon="o-check-circle"
                    class="btn-primary btn-sm"
                    spinner
                />
            </div>
        </div>
    </div>

    {{-- ══════════════════════ MAIN CONTENT ══════════════════════ --}}
    <div class="max-w-6xl mx-auto px-2 sm:px-4 py-3 sm:py-5 space-y-3 sm:space-y-4">

        {{-- ── PURCHASE INFO ── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200 rounded-2xl">
            <div class="card-body p-3 sm:p-4">
                <div class="flex items-center gap-2 mb-3">
                    <x-icon name="o-clipboard-document-list" class="w-4 h-4 text-primary" />
                    <span class="text-sm font-semibold text-base-content/70 uppercase tracking-wide">{{ __('Purchase Details') }}</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    {{-- Provider --}}
                    <div class="col-span-2 sm:col-span-1 lg:col-span-2">
                        <x-select
                            label="{{ __('Provider') }}"
                            wire:model.live="provider_id"
                            :options="$this->providerOptions"
                            option-value="id"
                            option-label="name"
                            placeholder="{{ __('Select provider') }}"
                            class="select-sm"
                            required
                        />
                    </div>

                    {{-- Date --}}
                    <div>
                        <x-input
                            type="date"
                            label="{{ __('Date') }}"
                            wire:model="purchase_date"
                            class="input-sm"
                            required
                        />
                    </div>

                    {{-- Discount --}}
                    <div>
                        <x-input
                            type="number"
                            label="{{ __('Discount') }}"
                            wire:model.live="discount"
                            placeholder="0"
                            step="0.01"
                            min="0"
                            class="input-sm"
                            suffix="৳"
                            x-on:focus="$el.querySelector('input')?.select()"
                        />
                    </div>

                    {{-- Tax --}}
                    <div>
                        <x-input
                            type="number"
                            label="{{ __('Tax') }}"
                            wire:model.live="tax"
                            placeholder="0"
                            step="0.01"
                            min="0"
                            class="input-sm"
                            suffix="৳"
                            x-on:focus="$el.querySelector('input')?.select()"
                        />
                    </div>

                    {{-- Shipping --}}
                    <div>
                        <x-input
                            type="number"
                            label="{{ __('Shipping') }}"
                            wire:model.live="shipping_cost"
                            placeholder="0"
                            step="0.01"
                            min="0"
                            class="input-sm"
                            suffix="৳"
                            x-on:focus="$el.querySelector('input')?.select()"
                        />
                    </div>

                    {{-- Note --}}
                    <div class="col-span-2 sm:col-span-3 lg:col-span-6">
                        <x-input
                            label="{{ __('Note') }}"
                            wire:model="note"
                            placeholder="{{ __('Optional note...') }}"
                            class="input-sm"
                            icon="o-chat-bubble-left-ellipsis"
                        />
                    </div>
                </div>
            </div>
        </div>

        {{-- ── PRODUCT SEARCH ── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200 rounded-2xl">
            <div class="card-body p-3 sm:p-4">
                <div class="flex items-center gap-2 mb-3">
                    <x-icon name="o-magnifying-glass" class="w-4 h-4 text-primary" />
                    <span class="text-sm font-semibold text-base-content/70 uppercase tracking-wide">{{ __('Add Products') }}</span>
                    <div class="badge badge-primary badge-sm ml-auto">{{ __('Click to add') }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- Category Filter --}}
                    <div>
                        <x-choices-offline
                            label="{{ __('Filter by Category') }}"
                            wire:model.live="category_filter_id"
                            :options="$this->categoryOptions"
                            option-value="id"
                            option-label="name"
                            option-group="group"
                            placeholder="{{ __('All categories') }}"
                            single
                            searchable
                            clearable
                            height="max-h-56"
                        >
                            @scope('item', $category)
                                <div class="flex items-center gap-2 py-0.5">
                                    <x-icon name="o-tag" class="w-3 h-3 opacity-40 shrink-0" />
                                    <span class="text-xs leading-tight truncate">{{ $category['name'] }}</span>
                                </div>
                            @endscope
                        </x-choices-offline>
                    </div>

                    {{-- Product Selector --}}
                    <div class="sm:col-span-2">
                        <x-choices-offline
                            label="{{ __('Search & Add Product') }}"
                            wire:model.live="selectedVariantId"
                            :options="$this->variantOptions"
                            option-value="id"
                            option-label="name"
                            option-group="group"
                            placeholder="{{ __('Type to search products...') }}"
                            single
                            searchable
                            clearable
                            height="max-h-56"
                        >
                            @scope('item', $variant)
                                <div class="flex items-center justify-between gap-2 py-0.5">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs font-medium leading-tight truncate">{{ $variant['name'] }}</div>
                                        <div class="text-[10px] text-base-content/40 leading-tight">
                                            <span class="font-mono">{{ $variant['sku'] }}</span>
                                            <span class="opacity-50"> &middot; </span>
                                            {{ $variant['group'] }}
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0 leading-tight space-y-0.5">
                                        @if($variant['last_price'])
                                            <div class="text-[11px] font-mono font-semibold text-success">৳{{ number_format($variant['last_price'], 0) }}</div>
                                        @endif
                                        <div class="text-[9px] text-base-content/35 bg-base-200 rounded px-1">
                                            {{ number_format($variant['stock'], 0) }} stk
                                        </div>
                                    </div>
                                </div>
                            @endscope
                        </x-choices-offline>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── ITEMS LIST ── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200 rounded-2xl">
            <div class="card-body p-0">

                {{-- Card header --}}
                <div class="flex items-center justify-between px-3 sm:px-4 py-2 sm:py-3 border-b border-base-200">
                    <div class="flex items-center gap-2">
                        <x-icon name="o-cube" class="w-4 h-4 text-primary" />
                        <span class="font-semibold text-sm">{{ __('Items') }}</span>
                        @if(count($items))
                            <span class="badge badge-primary badge-sm">{{ count($items) }}</span>
                        @endif
                    </div>
                    @if(count($items))
                        <div class="flex items-center gap-3 text-xs text-base-content/60">
                            <span>{{ __('Sub') }}: <strong class="font-mono text-base-content">৳{{ number_format($this->subtotal, 0) }}</strong></span>
                            <span class="text-primary font-bold text-sm">৳{{ number_format($this->grandTotal, 0) }}</span>
                        </div>
                    @endif
                </div>

                {{-- Empty state --}}
                @if(count($items) === 0)
                    <div class="flex flex-col items-center justify-center py-10 sm:py-16 px-4 text-center">
                        <div class="bg-base-200 rounded-full p-5 mb-4">
                            <x-icon name="o-shopping-cart" class="w-10 h-10 text-base-content/30" />
                        </div>
                        <p class="font-semibold text-base-content/50">{{ __('No products added yet') }}</p>
                        <p class="text-sm text-base-content/30 mt-1">{{ __('Search and select a product above to get started') }}</p>
                    </div>

                @else
                    {{-- Desktop column headers --}}
                    <div class="hidden md:grid grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] gap-2 px-4 py-2 bg-base-200/50 text-[10px] font-bold uppercase tracking-widest text-base-content/40">
                        <span>{{ __('Product') }}</span>
                        <span class="text-center">{{ __('Qty / Unit') }}</span>
                        <span class="text-right">{{ __('Price') }}</span>
                        <span class="text-right">{{ __('Landed') }}</span>
                        <span class="text-right">{{ __('Total') }}</span>
                        <span></span>
                    </div>

                    {{-- Item rows --}}
                    <div class="divide-y divide-base-200">
                        @foreach($items as $index => $item)
                            <div
                                wire:key="item-{{ $index }}"
                                x-data="{
                                    index: {{ $index }},
                                    handleQtyKeydown(e) {
                                        if (e.key === 'ArrowUp') {
                                            e.preventDefault();
                                            const curr = parseFloat($refs.qtyInput.value) || 0;
                                            $refs.qtyInput.value = Math.round((curr + 1) * 1000) / 1000;
                                            $refs.qtyInput.dispatchEvent(new Event('input'));
                                        } else if (e.key === 'ArrowDown') {
                                            e.preventDefault();
                                            const curr = parseFloat($refs.qtyInput.value) || 0;
                                            if (curr > 0.001) {
                                                $refs.qtyInput.value = Math.round((curr - 1) * 1000) / 1000;
                                                $refs.qtyInput.dispatchEvent(new Event('input'));
                                            }
                                        }
                                    }
                                }"
                                class="purchase-item px-2 sm:px-4 py-2.5 sm:py-3 hover:bg-base-50 group"
                            >
                                {{-- ── MOBILE layout ── --}}
                                <div class="md:hidden space-y-2">
                                    {{-- Row 1: Number + Name + Remove --}}
                                    <div class="flex items-start gap-2">
                                        <span class="shrink-0 w-5 h-5 rounded-full bg-primary/10 text-primary text-[10px] font-bold flex items-center justify-center mt-0.5">{{ $index + 1 }}</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-semibold text-sm leading-tight truncate">{{ $item['product_info']['name'] ?? 'Unknown' }}</p>
                                            <p class="text-[11px] text-base-content/40 truncate">
                                                {{ $item['product_info']['variant_name'] ?? '' }}
                                                @if($item['product_info']['sku'] ?? false)
                                                    &middot; <span class="font-mono">{{ $item['product_info']['sku'] }}</span>
                                                @endif
                                            </p>
                                        </div>
                                        <button wire:click="removeItem({{ $index }})" class="btn btn-ghost btn-xs text-error shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <x-icon name="o-trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    {{-- Row 2: Batch + Expiry --}}
                                    <div class="flex gap-1.5 pl-6 sm:pl-7">
                                        <input
                                            type="text"
                                            wire:model="items.{{ $index }}.batch_number"
                                            class="input input-xs flex-1 font-mono text-[11px] {{ $errors->has('items.'.$index.'.batch_number') ? 'input-error' : '' }}"
                                            placeholder="{{ $this->generateBatchNumberForIndex($index, $item['product_variant_id']) }}"
                                            title="{{ __('Batch #') }}"
                                        />
                                        <input
                                            type="date"
                                            wire:model="items.{{ $index }}.expiry_date"
                                            class="input input-xs w-36 text-[11px]"
                                            min="{{ now()->format('Y-m-d') }}"
                                            title="{{ __('Expiry') }}"
                                        />
                                    </div>
                                    {{-- Row 3: Qty + Unit + Price + Total --}}
                                    <div class="flex items-center gap-1.5 pl-6 sm:pl-7">
                                        <input
                                            type="number"
                                            x-ref="qtyInput"
                                            wire:model.live.debounce.200ms="items.{{ $index }}.quantity"
                                            step="0.001" min="0.001"
                                            class="input input-xs w-20 text-center font-mono num-input"
                                            x-on:focus="$el.select()"
                                            x-on:keydown="handleQtyKeydown($event)"
                                        />
                                        <select
                                            x-on:change="$wire.updateUnitPrice({{ $index }}, parseInt($event.target.value))"
                                            class="select select-xs w-20 text-[11px]"
                                        >
                                            @foreach($item['unit_options'] ?? [] as $unit)
                                                <option value="{{ $unit['id'] }}" {{ $unit['id'] == ($item['unit_id'] ?? null) ? 'selected' : '' }}>{{ $unit['short_name'] }}</option>
                                            @endforeach
                                        </select>
                                        <span class="text-base-content/30 text-xs">@</span>
                                        <div class="relative flex-1">
                                            <input
                                                type="number"
                                                wire:model.live.debounce.200ms="items.{{ $index }}.unit_price"
                                                step="0.01" min="0"
                                                class="input input-xs w-full text-right font-mono num-input pr-5"
                                                placeholder="{{ $item['last_purchase_price'] ?? 0 }}"
                                                x-on:focus="$el.select()"
                                            />
                                            <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[10px] text-base-content/30">৳</span>
                                        </div>
                                        @if($item['last_purchase_price'] ?? null)
                                            <button
                                                wire:click="$set('items.{{ $index }}.unit_price', {{ $item['last_purchase_price'] }})"
                                                class="btn btn-ghost btn-xs text-success shrink-0 px-1 tooltip"
                                                data-tip="৳{{ number_format($item['last_purchase_price'], 0) }}"
                                            >
                                                <x-icon name="o-arrow-uturn-left" class="w-3 h-3" />
                                            </button>
                                        @endif
                                        <div class="shrink-0 text-right">
                                            <span class="text-sm font-mono font-bold text-primary">৳{{ number_format($item['quantity'] * $item['unit_price'], 0) }}</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- ── DESKTOP layout ── --}}
                                <div class="hidden md:grid grid-cols-[2fr_1fr_1fr_1fr_1fr_auto] gap-2 items-center">
                                    {{-- Product --}}
                                    <div class="flex items-start gap-2 min-w-0">
                                        <span class="shrink-0 w-5 h-5 rounded-full bg-primary/10 text-primary text-[10px] font-bold flex items-center justify-center mt-0.5">{{ $index + 1 }}</span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-sm leading-tight truncate">{{ $item['product_info']['name'] ?? 'Unknown' }}</p>
                                            <p class="text-[10px] text-base-content/40 truncate">
                                                @if($item['product_info']['sku'] ?? false)
                                                    <span class="font-mono">{{ $item['product_info']['sku'] }}</span> &middot;
                                                @endif
                                                {{ $item['product_info']['variant_name'] ?? '' }}
                                            </p>
                                            {{-- Batch + Expiry inline --}}
                                            <div class="flex gap-1.5 mt-1">
                                                <input
                                                    type="text"
                                                    wire:model="items.{{ $index }}.batch_number"
                                                    class="input input-xs w-28 font-mono text-[10px] {{ $errors->has('items.'.$index.'.batch_number') ? 'input-error' : '' }}"
                                                    placeholder="{{ $this->generateBatchNumberForIndex($index, $item['product_variant_id']) }}"
                                                    title="{{ __('Batch #') }}"
                                                />
                                                <input
                                                    type="date"
                                                    wire:model="items.{{ $index }}.expiry_date"
                                                    class="input input-xs w-32 text-[10px]"
                                                    min="{{ now()->format('Y-m-d') }}"
                                                    title="{{ __('Expiry date') }}"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Qty + Unit --}}
                                    <div class="flex items-center gap-1 justify-center">
                                        <input
                                            type="number"
                                            x-ref="qtyInput"
                                            wire:model.live.debounce.200ms="items.{{ $index }}.quantity"
                                            step="0.001" min="0.001"
                                            class="input input-xs w-16 text-center font-mono num-input"
                                            x-on:focus="$el.select()"
                                            x-on:keydown="handleQtyKeydown($event)"
                                        />
                                        <select
                                            x-on:change="$wire.updateUnitPrice({{ $index }}, parseInt($event.target.value))"
                                            class="select select-xs w-18 text-[11px]"
                                        >
                                            @foreach($item['unit_options'] ?? [] as $unit)
                                                <option value="{{ $unit['id'] }}" {{ $unit['id'] == ($item['unit_id'] ?? null) ? 'selected' : '' }}>{{ $unit['short_name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Price --}}
                                    <div class="flex items-center justify-end gap-1">
                                        <div class="relative">
                                            <input
                                                type="number"
                                                wire:model.live.debounce.200ms="items.{{ $index }}.unit_price"
                                                step="0.01" min="0"
                                                class="input input-xs w-24 text-right font-mono num-input pr-4"
                                                placeholder="{{ $item['last_purchase_price'] ?? 0 }}"
                                                x-on:focus="$el.select()"
                                            />
                                            <span class="absolute right-1.5 top-1/2 -translate-y-1/2 text-[10px] text-base-content/30">৳</span>
                                        </div>
                                        @if($item['last_purchase_price'] ?? null)
                                            <button
                                                wire:click="$set('items.{{ $index }}.unit_price', {{ $item['last_purchase_price'] }})"
                                                class="btn btn-ghost btn-xs text-success px-1 tooltip"
                                                data-tip="{{ __('Last: ৳') }}{{ number_format($item['last_purchase_price'], 0) }}"
                                            >
                                                <x-icon name="o-arrow-uturn-left" class="w-3 h-3" />
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Landed --}}
                                    <div class="text-right">
                                        <span class="text-xs font-mono text-success">৳{{ number_format($item['landed_cost'] ?? $item['unit_price'], 2) }}</span>
                                    </div>

                                    {{-- Total --}}
                                    <div class="text-right">
                                        <span class="text-sm font-mono font-bold text-primary">৳{{ number_format($item['quantity'] * $item['unit_price'], 0) }}</span>
                                    </div>

                                    {{-- Remove --}}
                                    <div>
                                        <button
                                            wire:click="removeItem({{ $index }})"
                                            class="btn btn-ghost btn-xs text-error opacity-0 group-hover:opacity-100 transition-opacity"
                                            title="{{ __('Remove') }}"
                                        >
                                            <x-icon name="o-trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>

                                @error("items.{$index}.batch_number")
                                    <p class="text-[10px] text-error mt-1 pl-6 sm:pl-7">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    {{-- ── SUMMARY FOOTER ── --}}
                    <div class="border-t border-base-200 bg-base-200/40 rounded-b-2xl px-3 sm:px-4 py-3 sm:py-4">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            {{-- Breakdown --}}
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-base-content/60">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ __('Subtotal') }}</span>
                                    <strong class="font-mono text-base-content">৳{{ number_format($this->subtotal, 2) }}</strong>
                                </div>
                                @if($this->discount > 0)
                                    <div class="flex items-center gap-1.5 text-error">
                                        <x-icon name="o-minus-circle" class="w-3 h-3" />
                                        <span>{{ __('Discount') }}</span>
                                        <strong class="font-mono">৳{{ number_format($this->discount, 2) }}</strong>
                                    </div>
                                @endif
                                @if($this->tax + $this->shipping_cost > 0)
                                    <div class="flex items-center gap-1.5 text-warning">
                                        <x-icon name="o-plus-circle" class="w-3 h-3" />
                                        <span>{{ __('Tax + Ship') }}</span>
                                        <strong class="font-mono">৳{{ number_format($this->tax + $this->shipping_cost, 2) }}</strong>
                                    </div>
                                @endif
                                <div class="flex items-center gap-1.5 text-success">
                                    <x-icon name="o-arrow-trending-up" class="w-3 h-3" />
                                    <span>{{ __('Landed') }}</span>
                                    <strong class="font-mono">৳{{ number_format($this->totalLandedCost, 0) }}</strong>
                                </div>
                            </div>

                            {{-- Grand total --}}
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-base-content/50 uppercase tracking-wide font-semibold">{{ __('Grand Total') }}</span>
                                <span class="text-2xl font-bold font-mono text-primary">৳{{ number_format($this->grandTotal, 0) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

    </div>{{-- /max-w --}}
</div>

<script>
function purchaseForm() {
    return {
        init() {}
    }
}
</script>
