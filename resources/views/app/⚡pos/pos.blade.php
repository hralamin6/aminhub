<div class="flex h-full flex-col lg:flex-row relative"
     x-data="{ showCart: @entangle('showMobileCart') }"
     @keydown.f1.prevent="$wire.clearCart()"
     @keydown.f2.prevent="$wire.holdSale()"
     @keydown.f9.prevent="$wire.$set('showHeldSales', true)"
     @keydown.f12.prevent="$wire.checkout()"
     @keydown.escape.prevent="document.activeElement.blur()">

  {{-- LEFT: Products --}}
  <div class="flex-1 flex flex-col overflow-hidden bg-base-100">

    {{-- Top Bar: Search + Actions --}}
    <div class="flex items-center gap-2 px-3 py-2 border-b border-base-300 bg-base-100 shadow-sm">
      <div class="relative flex-1">
        <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none">
          <x-icon name="o-magnifying-glass" class="w-4 h-4 text-base-content/40" />
        </div>
        <input
          wire:model.live.debounce.300ms="search"
          x-ref="searchInput"
          autofocus
          x-init="$nextTick(() => $refs.searchInput.focus())"
          type="text"
          placeholder="{{ __('Search products or scan barcode...') }}"
          class="input input-sm w-full pl-9 pr-8 input-bordered bg-base-200/50 focus:bg-base-100 transition-colors"
        />
        @if($search)
          <button wire:click="$set('search', '')" class="absolute inset-y-0 right-2 flex items-center text-base-content/30 hover:text-base-content/70">
            <x-icon name="o-x-mark" class="w-3.5 h-3.5" />
          </button>
        @endif
        <div wire:loading wire:target="search" class="absolute inset-y-0 right-2 flex items-center">
          <span class="loading loading-spinner loading-xs text-primary"></span>
        </div>
      </div>

      {{-- Held Sales Badge --}}
      @if(count($heldSales) > 0)
        <button wire:click="$set('showHeldSales', true)" class="btn btn-warning btn-sm gap-1.5">
          <x-icon name="o-pause-circle" class="w-4 h-4" />
          <span class="hidden sm:inline">{{ __('Held') }}</span>
          <span class="badge badge-sm badge-ghost bg-warning-content/20">{{ count($heldSales) }}</span>
        </button>
      @endif
    </div>

    {{-- Category Navigation --}}
    <div class="border-b border-base-300 bg-base-50">
      {{-- Parent Categories --}}
      <div class="flex gap-1 px-2 pt-2 overflow-x-auto scrollbar-thin scrollbar-thumb-base-300">
        <button
          wire:click="selectCategory(null)"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-t-lg text-xs font-medium whitespace-nowrap transition-all border-b-2
            {{ !$categoryFilter ? 'bg-primary text-primary-content border-primary shadow-sm' : 'text-base-content/60 border-transparent hover:text-base-content hover:bg-base-200' }}"
        >
          <x-icon name="o-squares-2x2" class="w-3.5 h-3.5" />
          {{ __('All') }}
        </button>

        @foreach($this->categories as $cat)
          <button
            wire:click="selectCategory({{ $cat->id }})"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-t-lg text-xs font-medium whitespace-nowrap transition-all border-b-2
              {{ $categoryFilter == $cat->id ? 'bg-primary text-primary-content border-primary shadow-sm' : 'text-base-content/60 border-transparent hover:text-base-content hover:bg-base-200' }}"
          >
            @if($cat->icon)
              <span class="text-sm">{{ $cat->icon }}</span>
            @endif
            {{ $cat->name }}
            @if($cat->children->isNotEmpty())
              <x-icon name="o-chevron-down" class="w-3 h-3 opacity-60" />
            @endif
          </button>
        @endforeach
      </div>

      {{-- Subcategories (shown when parent is selected and has children) --}}
      @if($categoryFilter && $this->activeSubcategories->isNotEmpty())
        <div class="flex gap-1 px-2 pb-1.5 pt-1 overflow-x-auto scrollbar-thin scrollbar-thumb-base-300 bg-primary/5 border-t border-primary/10">
          <button
            wire:click="selectSubcategory(null)"
            class="flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium whitespace-nowrap transition-all
              {{ !$subCategoryFilter ? 'bg-primary/20 text-primary border border-primary/30' : 'text-base-content/50 hover:text-base-content hover:bg-base-200 border border-transparent' }}"
          >
            <x-icon name="o-list-bullet" class="w-3 h-3" />
            {{ __('All') }}
          </button>
          @foreach($this->activeSubcategories as $sub)
            <button
              wire:click="selectSubcategory({{ $sub->id }})"
              class="flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-medium whitespace-nowrap transition-all
                {{ $subCategoryFilter == $sub->id ? 'bg-primary/20 text-primary border border-primary/30' : 'text-base-content/50 hover:text-base-content hover:bg-base-200 border border-transparent' }}"
            >
              @if($sub->icon)
                <span>{{ $sub->icon }}</span>
              @endif
              {{ $sub->name }}
            </button>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Product Grid --}}
    <div class="flex-1 overflow-y-auto p-2 sm:p-3 bg-base-50/50 pb-20 lg:pb-2">
      <div wire:loading.class="opacity-40 pointer-events-none" wire:target="categoryFilter,subCategoryFilter,search,selectCategory,selectSubcategory">
        <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2 sm:gap-2.5">
          @forelse($this->products as $variant)
            <button
              wire:click="addToCart({{ $variant->id }})"
              class="group relative flex flex-col bg-base-100 border border-base-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md hover:border-primary/40 transition-all duration-200 cursor-pointer active:scale-[0.97] text-left"
              wire:key="product-{{ $variant->id }}"
            >
              {{-- Product Image --}}
              <div class="relative w-full aspect-square bg-gradient-to-br from-base-200 to-base-300/50 overflow-hidden">
                @php
                  $imageUrl = $variant->product->getPrimaryImageUrlAttribute();
                @endphp
                @if($imageUrl)
                  <img
                    src="{{ $imageUrl }}"
                    alt="{{ $variant->product->name }}"
                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    loading="lazy"
                  />
                  <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                @else
                  <div class="w-full h-full flex flex-col items-center justify-center gap-1">
                    <x-icon name="o-photo" class="w-8 h-8 sm:w-10 sm:h-10 text-base-content/20" />
                    <span class="text-[9px] text-base-content/20">No image</span>
                  </div>
                @endif

                {{-- Stock Badge --}}
                @if($variant->available_stock <= 0)
                  <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                    <span class="badge badge-error badge-sm font-bold">{{ __('Out of Stock') }}</span>
                  </div>
                @elseif($variant->available_stock < 10)
                  <div class="absolute top-1 right-1">
                    <span class="badge badge-warning badge-xs shadow">{{ __('Low') }}</span>
                  </div>
                @endif

                {{-- Add icon overlay on hover --}}
                @if($variant->available_stock > 0)
                  <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="bg-primary/90 rounded-full p-2 shadow-lg">
                      <x-icon name="o-plus" class="w-5 h-5 text-white" />
                    </div>
                  </div>
                @endif
              </div>

              {{-- Product Info --}}
              <div class="p-2 flex-1 flex flex-col gap-0.5">
                <p class="text-[11px] sm:text-xs font-semibold leading-tight line-clamp-2 text-base-content" title="{{ $variant->product->name }}">
                  {{ $variant->product->name }}
                </p>
                @if($variant->name && $variant->name !== $variant->product->name)
                  <p class="text-[9px] sm:text-[10px] text-base-content/40 truncate">{{ $variant->name }}</p>
                @endif
                <div class="flex items-center justify-between mt-auto pt-1">
                  <p class="text-xs sm:text-sm font-bold text-primary">৳{{ number_format($variant->retail_price, 0) }}</p>
                  @if($variant->available_stock > 0)
                    <span class="text-[9px] text-base-content/35 font-mono">{{ number_format($variant->available_stock, 0) }}</span>
                  @endif
                </div>
              </div>
            </button>
          @empty
            <div class="col-span-full flex flex-col items-center justify-center py-16 sm:py-24">
              <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-base-200 flex items-center justify-center mb-4">
                @if($search)
                  <x-icon name="o-magnifying-glass" class="w-8 h-8 opacity-30" />
                @else
                  <x-icon name="o-cube" class="w-8 h-8 opacity-30" />
                @endif
              </div>
              <p class="text-base-content/50 text-sm font-medium">
                {{ $search ? __('No products found for ":query"', ['query' => $search]) : __('No products in this category') }}
              </p>
              @if($search || $categoryFilter)
                <button wire:click="selectCategory(null)" class="btn btn-ghost btn-xs mt-3 text-primary">
                  {{ __('Clear filters') }}
                </button>
              @endif
            </div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Mobile Bottom Bar --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-base-100 border-t-2 border-base-300 px-3 py-2 z-20 shadow-2xl">
      <div class="flex items-center gap-2">
        <button wire:click="$toggle('showMobileCart')" class="btn btn-primary flex-1 gap-2 btn-sm">
          <x-icon name="o-shopping-cart" class="w-4 h-4" />
          <span>{{ __('Cart') }}</span>
          @if(count($cart) > 0)
            <span class="badge badge-sm badge-ghost bg-primary-content/20">{{ count($cart) }}</span>
          @endif
          @if(count($cart) > 0)
            <span class="ml-auto font-mono font-bold text-xs">৳{{ number_format($this->grandTotal, 0) }}</span>
          @endif
        </button>
        <button wire:click="clearCart" class="btn btn-ghost btn-sm btn-square" title="{{ __('New Sale') }}">
          <x-icon name="o-arrow-path" class="w-4 h-4" />
        </button>
      </div>
    </div>

    {{-- Desktop Shortcut Bar --}}
    <div class="hidden lg:flex items-center gap-3 px-3 py-1.5 bg-base-100 border-t border-base-200 text-[11px] text-base-content/50">
      <span><kbd class="kbd kbd-xs">F1</kbd> {{ __('New') }}</span>
      <span><kbd class="kbd kbd-xs">F2</kbd> {{ __('Hold') }}</span>
      <span><kbd class="kbd kbd-xs">F9</kbd> {{ __('Held') }}</span>
      <span><kbd class="kbd kbd-xs bg-primary/10 text-primary border-primary/20">F12</kbd> {{ __('Pay') }}</span>
    </div>
  </div>

  {{-- RIGHT: Cart (Desktop) --}}
  <div class="hidden lg:flex w-[380px] xl:w-[420px] flex-col bg-base-100 border-l border-base-300 shadow-xl overflow-hidden">
    @include('app.⚡pos.partials.cart')
  </div>

  {{-- Mobile Cart Drawer --}}
  <div x-show="showCart"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="translate-y-full"
       x-transition:enter-end="translate-y-0"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="translate-y-0"
       x-transition:leave-end="translate-y-full"
       class="lg:hidden fixed inset-x-0 bottom-0 top-12 bg-base-100 z-30 flex flex-col"
       style="display: none;">
    
    <div class="flex-1 overflow-hidden flex flex-col" @click.outside="showCart = false">
      @include('app.⚡pos.partials.cart')
    </div>
  </div>

  {{-- Held Sales Modal --}}
  <x-modal wire:model="showHeldSales" :title="__('Held Sales')" class="backdrop-blur">
    @if(count($heldSales) > 0)
      <div class="space-y-2">
        @foreach($heldSales as $hi => $held)
          <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-xl border border-base-300 hover:border-warning/40 transition-colors">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="badge badge-warning badge-sm">{{ __('Held') }}</span>
                <span class="font-semibold text-sm">{{ count($held['cart']) }} {{ __('items') }}</span>
              </div>
              <p class="text-xs text-base-content/70 truncate">{{ $held['customer_name'] ?: __('Walk-in Customer') }}</p>
              <p class="text-[10px] text-base-content/40 mt-0.5">{{ $held['held_at'] }}</p>
            </div>
            <button wire:click="resumeSale({{ $hi }})" class="btn btn-primary btn-sm ml-3">
              <x-icon name="o-play" class="w-3.5 h-3.5" />
              {{ __('Resume') }}
            </button>
          </div>
        @endforeach
      </div>
    @else
      <div class="text-center py-10">
        <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-3">
          <x-icon name="o-pause-circle" class="w-8 h-8 opacity-30" />
        </div>
        <p class="text-base-content/50 text-sm">{{ __('No held sales') }}</p>
      </div>
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showHeldSales', false)">{{ __('Close') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Receipt Modal --}}
  <x-modal wire:model="showReceipt" :title="__('Sale Completed!')" class="backdrop-blur">
    @if($lastSale = $this->lastSale)
      <div class="bg-white rounded-xl p-4 sm:p-6 font-mono text-xs sm:text-sm space-y-3 text-black shadow-inner" id="receipt" style="max-width: 80mm; margin: 0 auto;">
        <div class="text-center border-b-2 border-dashed border-gray-300 pb-3">
          <p class="font-bold text-base sm:text-lg">{{ setting('name', 'AminHub') }}</p>
          <p class="text-[10px] sm:text-xs text-gray-500">{{ setting('address', '') }}</p>
          <p class="text-[10px] sm:text-xs text-gray-500">{{ setting('phone', '') }}</p>
        </div>

        <div class="border-b border-dashed border-gray-300 pb-2 text-[10px] sm:text-xs">
          <div class="grid grid-cols-2 gap-1">
            <div><span class="text-gray-500">{{ __('Invoice') }}:</span> <b>{{ $lastSale->invoice_number }}</b></div>
            <div class="text-right text-gray-500">{{ $lastSale->created_at->format('d/m/Y h:i A') }}</div>
            <div class="col-span-2"><span class="text-gray-500">{{ __('Cashier') }}:</span> {{ $lastSale->seller->name ?? 'Admin' }}</div>
            @if($lastSale->customer_name)
              <div class="col-span-2"><span class="text-gray-500">{{ __('Customer') }}:</span> {{ $lastSale->customer_name }}</div>
            @endif
          </div>
        </div>

        <div class="border-b border-dashed border-gray-300 pb-2">
          <table class="w-full text-[10px] sm:text-xs">
            <thead>
              <tr class="border-b border-gray-300">
                <th class="text-left py-0.5">{{ __('Item') }}</th>
                <th class="text-center py-0.5">{{ __('Qty') }}</th>
                <th class="text-right py-0.5">{{ __('Price') }}</th>
                <th class="text-right py-0.5">{{ __('Total') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lastSale->items as $sItem)
                <tr class="border-b border-gray-100">
                  <td class="py-1">
                    <div class="leading-tight">{{ $sItem->variant->product->name ?? 'Product' }}</div>
                    <div class="text-[9px] text-gray-400">{{ $sItem->variant->name ?? '' }} {{ $sItem->unit->short_name ?? '' }}</div>
                  </td>
                  <td class="text-center">{{ number_format($sItem->quantity, 3) }}</td>
                  <td class="text-right">{{ number_format($sItem->unit_price, 2) }}</td>
                  <td class="text-right font-semibold">{{ number_format($sItem->subtotal, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="space-y-0.5 text-[10px] sm:text-xs">
          <div class="flex justify-between"><span class="text-gray-500">{{ __('Subtotal') }}</span><span>{{ number_format($lastSale->subtotal, 2) }}</span></div>
          @if($lastSale->discount_amount > 0)
            <div class="flex justify-between text-red-500"><span>{{ __('Discount') }}</span><span>-{{ number_format($lastSale->discount_amount, 2) }}</span></div>
          @endif
          <div class="flex justify-between font-bold text-sm sm:text-base border-t-2 border-gray-300 pt-1">
            <span>{{ __('TOTAL') }}</span><span>{{ number_format($lastSale->grand_total, 2) }}</span>
          </div>
          <div class="flex justify-between"><span class="text-gray-500 capitalize">{{ $lastSale->payment_method ?? 'Cash' }}</span><span>{{ number_format($lastSale->paid_amount, 2) }}</span></div>
          @if($lastSale->change_amount > 0)
            <div class="flex justify-between text-green-600"><span>{{ __('Change') }}</span><span class="font-bold">{{ number_format($lastSale->change_amount, 2) }}</span></div>
          @endif
          @if($lastSale->due_amount > 0)
            <div class="flex justify-between text-red-600"><span>{{ __('Due') }}</span><span class="font-bold">{{ number_format($lastSale->due_amount, 2) }}</span></div>
          @endif
          {{-- Internal profit line — shown on screen only, not printed --}}
          @php
            $receiptCost = $lastSale->items->sum(fn($si) => ($si->variant->purchase_price ?? 0) * $si->quantity);
            $receiptProfit = max(0, $lastSale->grand_total - $receiptCost);
          @endphp
          @if($receiptProfit > 0)
            <div class="flex justify-between text-green-700 border-t border-dashed border-gray-200 pt-1 no-print">
              <span>{{ __('Est. Profit') }}</span>
              <span class="font-bold">+{{ number_format($receiptProfit, 2) }}</span>
            </div>
          @endif
        </div>

        <div class="text-center border-t-2 border-dashed border-gray-300 pt-2 text-[10px]">
          <p class="font-medium">{{ __('Thank you!') }} {{ __('আবার আসবেন!') }}</p>
          <p class="text-[8px] mt-1 text-gray-400">Powered by AminHub</p>
        </div>
      </div>
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost btn-sm" wire:click="$set('showReceipt', false)">{{ __('Close') }}</x-button>
      <x-button class="btn-primary btn-sm" icon="o-printer" onclick="printReceipt()">
        {{ __('Print') }}
      </x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Hidden Print Section --}}
  <div id="print-area" class="hidden-print" style="display: none;"></div>

  {{-- Print Script & Styles --}}
  <script>
    function printReceipt() {
      const receipt = document.getElementById('receipt');
      const printArea = document.getElementById('print-area');

      if (receipt && printArea) {
        printArea.innerHTML = receipt.outerHTML;
        setTimeout(() => { window.print(); }, 100);
      }
    }
  </script>

  <style>
    @media screen {
      .hidden-print { display: none !important; }
    }

    @media print {
      @page { size: 80mm auto; margin: 0; }

      body { background: white !important; }
      body > * { display: none !important; }
      #print-area {
        display: block !important;
        position: fixed !important;
        left: 0 !important; top: 0 !important;
        width: 100% !important; height: 100% !important;
        background: white !important; z-index: 99999 !important;
      }
      #print-area #receipt {
        display: block !important; width: 80mm !important;
        max-width: 80mm !important; margin: 0 auto !important;
        padding: 5mm !important; background: white !important;
        color: black !important; font-family: 'Courier New', monospace !important;
        font-size: 10pt !important; line-height: 1.2 !important;
        box-shadow: none !important; border-radius: 0 !important;
      }
      #print-area #receipt * { visibility: visible !important; color: black !important; background: white !important; }
      #print-area table { width: 100% !important; border-collapse: collapse !important; }
      #print-area th, #print-area td { padding: 2px 0 !important; border: none !important; text-align: left !important; }
      #print-area th:last-child, #print-area td:last-child { text-align: right !important; }
      #print-area .text-center { text-align: center !important; }
      #print-area .text-right { text-align: right !important; }
      #print-area .border-b, #print-area .border-t { border-color: #000 !important; border-style: dashed !important; }
      .no-print { display: none !important; }
    }
  </style>

</div>
