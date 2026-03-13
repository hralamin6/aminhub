<div class="flex h-full flex-col lg:flex-row relative"
     x-data="{ showCart: @entangle('showMobileCart') }"
     @keydown.f1.prevent="$wire.clearCart()"
     @keydown.f2.prevent="$wire.holdSale()"
     @keydown.f9.prevent="$wire.$set('showHeldSales', true)"
     @keydown.f12.prevent="$wire.checkout()"
     @keydown.escape.prevent="document.activeElement.blur()">

  {{-- LEFT: Products --}}
  <div class="flex-1 flex flex-col overflow-hidden bg-base-100">
    {{-- Search bar --}}
    <div class="p-2 sm:p-3 border-b border-base-300 bg-gradient-to-b from-base-100 to-base-200/30">
      <div class="relative">
        <x-input wire:model.live.debounce.300ms="search" icon="o-magnifying-glass"
          placeholder="{{ __('Search or scan barcode...') }}" clearable
          x-ref="searchInput" autofocus
          class="input-sm sm:input-md input-bordered shadow-sm"
          x-init="$nextTick(() => $refs.searchInput.focus())" />
        <div wire:loading wire:target="search" class="absolute right-2 top-2">
          <span class="loading loading-spinner loading-xs sm:loading-sm text-primary"></span>
        </div>
      </div>
    </div>

    {{-- Category tabs --}}
    <div class="flex gap-1 p-1.5 sm:p-2 border-b border-base-300 bg-base-50 overflow-x-auto scrollbar-thin">
      <button wire:click="$set('categoryFilter', null)"
        class="btn btn-xs sm:btn-sm {{ !$categoryFilter ? 'btn-primary' : 'btn-ghost' }} whitespace-nowrap">
        <x-icon name="o-squares-2x2" class="w-3 h-3 sm:w-4 sm:h-4" />
        <span class="hidden xs:inline">{{ __('All') }}</span>
      </button>
      @foreach($this->categories as $cat)
        <button wire:click="$set('categoryFilter', {{ $cat['id'] }})"
          class="btn btn-xs sm:btn-sm {{ $categoryFilter == $cat['id'] ? 'btn-primary' : 'btn-ghost' }} whitespace-nowrap">
          {{ $cat['name'] }}
        </button>
      @endforeach
    </div>

    {{-- Product Grid --}}
    <div class="flex-1 overflow-y-auto p-1.5 sm:p-3 bg-base-50 pb-20 lg:pb-2">
      <div wire:loading.class="opacity-50 pointer-events-none" wire:target="categoryFilter,search">
        <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-1.5 sm:gap-2">
          @forelse($this->products as $variant)
            <button wire:click="addToCart({{ $variant->id }})"
              class="card bg-base-100 border border-base-300 hover:border-primary hover:shadow-lg transition-all cursor-pointer active:scale-95 group"
              wire:key="product-{{ $variant->id }}">
              <figure class="h-16 xs:h-20 sm:h-24 bg-gradient-to-br from-base-200/50 to-base-300/30 flex items-center justify-center overflow-hidden relative">
                @if($variant->product->hasMedia('product_image'))
                  <img src="{{ $variant->product->getFirstMediaUrl('product_image', 'thumb') }}"
                    alt="{{ $variant->product->name }}"
                    class="h-full w-full object-cover group-hover:scale-105 transition-transform"
                    loading="lazy" />
                @else
                  <x-icon name="o-cube" class="w-6 h-6 sm:w-8 sm:h-8 opacity-20" />
                @endif
                @if($variant->available_stock <= 0)
                  <div class="absolute inset-0 bg-black/70 flex items-center justify-center">
                    <span class="badge badge-error badge-xs">{{ __('Out') }}</span>
                  </div>
                @elseif($variant->available_stock < 10)
                  <div class="absolute top-0.5 right-0.5">
                    <span class="badge badge-warning badge-xs">!</span>
                  </div>
                @endif
              </figure>
              <div class="p-1.5 sm:p-2">
                <p class="text-[11px] sm:text-xs font-semibold truncate leading-tight" title="{{ $variant->product->name }}">
                  {{ $variant->product->name }}
                </p>
                <p class="text-[9px] sm:text-[10px] text-base-content/50 truncate">{{ $variant->name }}</p>
                <div class="flex items-center justify-between mt-1">
                  <p class="text-xs sm:text-sm font-bold text-primary">৳{{ number_format($variant->retail_price, 0) }}</p>
                  @if($variant->available_stock > 0)
                    <span class="text-[9px] text-base-content/40 font-mono hidden sm:inline">{{ number_format($variant->available_stock, 0) }}</span>
                  @endif
                </div>
              </div>
            </button>
          @empty
            <div class="col-span-full text-center py-12 sm:py-20">
              <x-icon name="o-magnifying-glass" class="w-12 h-12 mx-auto mb-2 opacity-20" />
              <p class="text-base-content/50 text-xs sm:text-sm">
                {{ $search ? __('No products found') : __('Search products') }}
              </p>
            </div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Mobile Bottom Bar --}}
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-base-100 border-t-2 border-base-300 p-2 z-20 shadow-2xl">
      <div class="flex items-center gap-2">
        <button wire:click="$toggle('showMobileCart')" class="btn btn-primary flex-1 gap-2">
          <x-icon name="o-shopping-cart" class="w-5 h-5" />
          <span>{{ __('Cart') }}</span>
          @if(count($cart) > 0)
            <span class="badge badge-sm">{{ count($cart) }}</span>
          @endif
        </button>
        @if(count($heldSales) > 0)
          <button wire:click="$set('showHeldSales', true)" class="btn btn-warning btn-square">
            <x-icon name="o-pause-circle" class="w-5 h-5" />
          </button>
        @endif
        <button wire:click="clearCart" class="btn btn-ghost btn-square">
          <x-icon name="o-arrow-path" class="w-5 h-5" />
        </button>
      </div>
    </div>

    {{-- Desktop Bottom bar --}}
    <div class="hidden lg:flex items-center gap-2 px-3 py-2 bg-base-100 border-t border-base-300 text-xs">
      <kbd class="kbd kbd-xs">F1</kbd> {{ __('New') }}
      <kbd class="kbd kbd-xs">F2</kbd> {{ __('Hold') }}
      <kbd class="kbd kbd-xs">F9</kbd> {{ __('Held') }}
      <kbd class="kbd kbd-xs bg-primary text-primary-content">F12</kbd> {{ __('Pay') }}
      @if(count($heldSales) > 0)
        <button wire:click="$set('showHeldSales', true)" class="btn btn-warning btn-xs ml-auto">
          <x-icon name="o-pause-circle" class="w-3 h-3" />
          {{ __('Held') }} <span class="badge badge-xs">{{ count($heldSales) }}</span>
        </button>
      @endif
    </div>
  </div>

  {{-- RIGHT: Cart (Desktop) --}}
  <div class="hidden lg:flex w-[380px] xl:w-[420px] flex-col bg-base-100 border-l border-base-300 shadow-2xl">
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
    <div class="flex items-center justify-between p-3 border-b border-base-300 bg-primary/5">
      <h3 class="font-bold">{{ __('Cart') }}</h3>
      <button @click="showCart = false" class="btn btn-ghost btn-sm btn-square">
        <x-icon name="o-x-mark" class="w-5 h-5" />
      </button>
    </div>
    <div class="flex-1 overflow-hidden flex flex-col">
      @include('app.⚡pos.partials.cart')
    </div>
  </div>

  {{-- Held Sales Modal --}}
  <x-modal wire:model="showHeldSales" :title="__('Held Sales')" class="backdrop-blur">
    @if(count($heldSales) > 0)
      <div class="space-y-2">
        @foreach($heldSales as $hi => $held)
          <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg border border-base-300">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="badge badge-warning badge-xs">{{ __('Held') }}</span>
                <span class="font-semibold text-sm">{{ count($held['cart']) }} {{ __('items') }}</span>
              </div>
              <p class="text-xs text-base-content/70 truncate">{{ $held['customer_name'] ?: __('Walk-in') }}</p>
              <p class="text-[10px] text-base-content/50">{{ $held['held_at'] }}</p>
            </div>
            <button wire:click="resumeSale({{ $hi }})" class="btn btn-primary btn-xs">
              {{ __('Resume') }}
            </button>
          </div>
        @endforeach
      </div>
    @else
      <div class="text-center py-8">
        <x-icon name="o-pause-circle" class="w-12 h-12 mx-auto mb-2 opacity-20" />
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
      <div class="bg-white rounded-lg p-4 sm:p-6 font-mono text-xs sm:text-sm space-y-2 sm:space-y-3 text-black" id="receipt" style="max-width: 80mm; margin: 0 auto;">
        <div class="text-center border-b-2 border-dashed border-gray-400 pb-2">
          <p class="font-bold text-base sm:text-lg">{{ setting('name', 'AminHub') }}</p>
          <p class="text-[10px] sm:text-xs">{{ setting('address', '') }}</p>
          <p class="text-[10px] sm:text-xs">{{ setting('phone', '') }}</p>
        </div>

        <div class="border-b border-dashed border-gray-400 pb-2 text-[10px] sm:text-xs">
          <div class="grid grid-cols-2 gap-1">
            <div><span class="text-gray-600">{{ __('Invoice') }}:</span> <b>{{ $lastSale->invoice_number }}</b></div>
            <div class="text-right">{{ $lastSale->created_at->format('d/m/Y h:i A') }}</div>
            <div class="col-span-2"><span class="text-gray-600">{{ __('Cashier') }}:</span> {{ $lastSale->seller->name ?? 'Admin' }}</div>
            @if($lastSale->customer_name)
              <div class="col-span-2"><span class="text-gray-600">{{ __('Customer') }}:</span> {{ $lastSale->customer_name }}</div>
            @endif
          </div>
        </div>

        <div class="border-b border-dashed border-gray-400 pb-2">
          <table class="w-full text-[10px] sm:text-xs">
            <thead>
              <tr class="border-b border-gray-400">
                <th class="text-left py-0.5">{{ __('Item') }}</th>
                <th class="text-center py-0.5">{{ __('Qty') }}</th>
                <th class="text-right py-0.5">{{ __('Price') }}</th>
                <th class="text-right py-0.5">{{ __('Total') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lastSale->items as $sItem)
                <tr class="border-b border-gray-200">
                  <td class="py-1">
                    <div class="leading-tight">{{ $sItem->variant->product->name ?? 'Product' }}</div>
                    <div class="text-[9px] text-gray-500">{{ $sItem->variant->name ?? '' }} {{ $sItem->unit->short_name ?? '' }}</div>
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
          <div class="flex justify-between"><span class="text-gray-600">{{ __('Subtotal') }}</span><span>{{ number_format($lastSale->subtotal, 2) }}</span></div>
          @if($lastSale->discount_amount > 0)
            <div class="flex justify-between text-red-600"><span>{{ __('Discount') }}</span><span>-{{ number_format($lastSale->discount_amount, 2) }}</span></div>
          @endif
          <div class="flex justify-between font-bold text-sm sm:text-base border-t-2 border-gray-400 pt-1">
            <span>{{ __('TOTAL') }}</span><span>{{ number_format($lastSale->grand_total, 2) }}</span>
          </div>
          <div class="flex justify-between"><span class="text-gray-600">{{ $lastSale->payment_method ?? 'Cash' }}</span><span>{{ number_format($lastSale->paid_amount, 2) }}</span></div>
          @if($lastSale->change_amount > 0)
            <div class="flex justify-between text-green-600"><span>{{ __('Change') }}</span><span class="font-bold">{{ number_format($lastSale->change_amount, 2) }}</span></div>
          @endif
          @if($lastSale->due_amount > 0)
            <div class="flex justify-between text-red-600"><span>{{ __('Due') }}</span><span class="font-bold">{{ number_format($lastSale->due_amount, 2) }}</span></div>
          @endif
        </div>

        <div class="text-center border-t-2 border-dashed border-gray-400 pt-2 text-[10px]">
          <p>{{ __('Thank you!') }} {{ __('আবার আসবেন!') }}</p>
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
        // Clone receipt to print area
        printArea.innerHTML = receipt.outerHTML;
        
        // Wait a moment for DOM update then print
        setTimeout(() => {
          window.print();
        }, 100);
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
      
      /* Hide everything except print area */
      body > * { display: none !important; }
      #print-area { 
        display: block !important; 
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        height: 100% !important;
        background: white !important;
        z-index: 99999 !important;
      }
      #print-area #receipt {
        display: block !important;
        width: 80mm !important;
        max-width: 80mm !important;
        margin: 0 auto !important;
        padding: 5mm !important;
        background: white !important;
        color: black !important;
        font-family: 'Courier New', monospace !important;
        font-size: 10pt !important;
        line-height: 1.2 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
      }
      #print-area #receipt * {
        visibility: visible !important;
        color: black !important;
        background: white !important;
      }
      #print-area table { width: 100% !important; border-collapse: collapse !important; }
      #print-area th, #print-area td { 
        padding: 2px 0 !important; 
        border: none !important;
        text-align: left !important;
      }
      #print-area th:last-child, #print-area td:last-child { text-align: right !important; }
      #print-area .text-center { text-align: center !important; }
      #print-area .text-right { text-align: right !important; }
      #print-area .border-b, #print-area .border-t { 
        border-color: #000 !important; 
        border-style: dashed !important;
      }
    }
  </style>
</div>
