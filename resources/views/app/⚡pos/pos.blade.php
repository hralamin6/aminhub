<div class="flex h-full" x-data="{}" @keydown.f1.prevent="$wire.clearCart()" @keydown.f2.prevent="$wire.holdSale()" @keydown.f12.prevent="$wire.checkout()">

  {{-- LEFT: Products --}}
  <div class="flex-1 flex flex-col overflow-hidden border-r border-base-300">
    {{-- Search bar --}}
    <div class="p-3 border-b border-base-300 bg-base-100">
      <x-input wire:model.live.debounce.300ms="search" icon="o-magnifying-glass"
        placeholder="{{ __('Search product, SKU, or scan barcode...') }}" clearable
        x-ref="searchInput" autofocus class="input-sm" />
    </div>

    {{-- Category tabs --}}
    <div class="flex gap-1 p-2 border-b border-base-300 bg-base-100 overflow-x-auto">
      <button wire:click="$set('categoryFilter', null)"
        class="btn btn-xs {{ !$categoryFilter ? 'btn-primary' : 'btn-ghost' }}">{{ __('All') }}</button>
      @foreach($this->categories as $cat)
        <button wire:click="$set('categoryFilter', {{ $cat['id'] }})"
          class="btn btn-xs {{ $categoryFilter == $cat['id'] ? 'btn-primary' : 'btn-ghost' }} whitespace-nowrap">
          {{ $cat['name'] }}
        </button>
      @endforeach
    </div>

    {{-- Product Grid --}}
    <div class="flex-1 overflow-y-auto p-3">
      <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2">
        @forelse($this->products as $variant)
          <button wire:click="addToCart({{ $variant->id }})"
            class="card bg-base-100 border border-base-300 hover:border-primary hover:shadow-lg transition-all cursor-pointer active:scale-95 group">
            <figure class="h-16 bg-base-200/50 flex items-center justify-center overflow-hidden rounded-t-lg">
              @if($variant->product->hasMedia('product_image'))
                <img src="{{ $variant->product->getFirstMediaUrl('product_image', 'thumb') }}"
                  alt="{{ $variant->product->name }}" class="h-full w-full object-cover" loading="lazy" />
              @else
                <x-icon name="o-cube" class="w-8 h-8 opacity-20" />
              @endif
            </figure>
            <div class="p-2">
              <p class="text-xs font-semibold truncate">{{ $variant->product->name }}</p>
              <p class="text-[10px] text-base-content/50 truncate">{{ $variant->name }}</p>
              <p class="text-sm font-bold text-primary mt-1">৳{{ number_format($variant->retail_price, 0) }}</p>
            </div>
          </button>
        @empty
          <div class="col-span-full text-center py-20 text-base-content/40">
            <x-icon name="o-cube" class="w-12 h-12 mx-auto mb-2 opacity-20" />
            <p>{{ $search ? __('No products found.') : __('Search or browse products.') }}</p>
          </div>
        @endforelse
      </div>
    </div>

    {{-- Bottom bar --}}
    <div class="flex items-center gap-2 px-3 py-2 bg-base-100 border-t border-base-300 text-xs text-base-content/50">
      <kbd class="kbd kbd-xs">F1</kbd> {{ __('New') }}
      <kbd class="kbd kbd-xs">F2</kbd> {{ __('Hold') }}
      <kbd class="kbd kbd-xs">F12</kbd> {{ __('Checkout') }}
      @if(count($heldSales) > 0)
        <button wire:click="$set('showHeldSales', true)" class="btn btn-ghost btn-xs ml-auto gap-1">
          <x-icon name="o-pause-circle" class="w-3 h-3" />
          {{ __('Held') }} <span class="badge badge-warning badge-xs">{{ count($heldSales) }}</span>
        </button>
      @endif
    </div>
  </div>

  {{-- RIGHT: Cart --}}
  <div class="w-[380px] flex flex-col bg-base-100">
    {{-- Cart header --}}
    <div class="flex items-center justify-between px-4 py-2 border-b border-base-300">
      <h3 class="font-bold text-sm flex items-center gap-1">
        <x-icon name="o-shopping-cart" class="w-4 h-4" /> {{ __('Cart') }}
        @if(count($cart) > 0)
          <span class="badge badge-primary badge-xs">{{ count($cart) }}</span>
        @endif
      </h3>
      @if(count($cart) > 0)
        <button wire:click="clearCart" class="btn btn-ghost btn-xs text-error">
          <x-icon name="o-trash" class="w-3 h-3" /> {{ __('Clear') }}
        </button>
      @endif
    </div>

    {{-- Cart items --}}
    <div class="flex-1 overflow-y-auto divide-y divide-base-200">
      @forelse($cart as $i => $item)
        <div class="px-3 py-2 hover:bg-base-200/30 transition-colors group" wire:key="cart-{{ $i }}">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate">{{ $item['name'] }}</p>
              <p class="text-[10px] text-base-content/50">{{ $item['variant_name'] }} · {{ $item['sku'] }}</p>
            </div>
            <button wire:click="removeFromCart({{ $i }})" class="btn btn-ghost btn-xs opacity-0 group-hover:opacity-100 text-error">
              <x-icon name="o-x-mark" class="w-3 h-3" />
            </button>
          </div>
          <div class="flex items-center justify-between mt-1">
            <div class="flex items-center gap-1">
              <button wire:click="decrementQty({{ $i }})" class="btn btn-ghost btn-xs btn-square">−</button>
              <input type="number" wire:change="updateQty({{ $i }}, $event.target.value)"
                value="{{ $item['quantity'] }}" class="input input-xs input-bordered w-14 text-center" step="0.01" min="0.01" />
              <button wire:click="incrementQty({{ $i }})" class="btn btn-ghost btn-xs btn-square">+</button>
              <span class="text-[10px] text-base-content/40">{{ $item['unit_name'] }}</span>
            </div>
            <span class="font-mono text-sm font-semibold">
              ৳{{ number_format(($item['quantity'] * $item['unit_price']) - $item['discount'], 0) }}
            </span>
          </div>
        </div>
      @empty
        <div class="flex flex-col items-center justify-center h-full text-base-content/30 py-12">
          <x-icon name="o-shopping-cart" class="w-10 h-10 mb-2 opacity-30" />
          <p class="text-sm">{{ __('Cart is empty') }}</p>
        </div>
      @endforelse
    </div>

    {{-- Checkout section --}}
    @if(count($cart) > 0)
      <div class="border-t border-base-300 p-3 space-y-2 bg-base-100">
        {{-- Subtotal --}}
        <div class="flex justify-between text-sm">
          <span>{{ __('Subtotal') }}</span>
          <span class="font-mono">৳{{ number_format($this->subtotal, 0) }}</span>
        </div>

        {{-- Discount --}}
        <div class="flex items-center gap-2">
          <select wire:model.live="discount_type" class="select select-xs select-bordered w-20">
            <option value="flat">৳</option>
            <option value="percent">%</option>
          </select>
          <input type="number" wire:model.live="discount_value" class="input input-xs input-bordered flex-1 text-right"
            step="0.01" min="0" placeholder="{{ __('Discount') }}" />
          @if($this->discountAmount > 0)
            <span class="text-xs text-error font-mono">-৳{{ number_format($this->discountAmount, 0) }}</span>
          @endif
        </div>

        {{-- Grand total --}}
        <div class="flex justify-between text-lg font-bold border-t border-base-200 pt-2">
          <span>{{ __('Total') }}</span>
          <span class="font-mono text-primary">৳{{ number_format($this->grandTotal, 0) }}</span>
        </div>

        {{-- Customer --}}
        <div class="grid grid-cols-2 gap-2">
          <input type="text" wire:model="customer_name" class="input input-xs input-bordered" placeholder="{{ __('Customer name') }}" />
          <input type="text" wire:model="customer_phone" class="input input-xs input-bordered" placeholder="{{ __('Phone') }}" />
        </div>

        {{-- Payment --}}
        <div class="grid grid-cols-2 gap-2">
          <select wire:model="payment_method" class="select select-xs select-bordered">
            <option value="cash">{{ __('Cash') }}</option>
            <option value="bkash">{{ __('bKash') }}</option>
            <option value="nagad">{{ __('Nagad') }}</option>
            <option value="card">{{ __('Card') }}</option>
            <option value="mixed">{{ __('Mixed') }}</option>
          </select>
          <input type="number" wire:model.live="paid_amount" class="input input-xs input-bordered text-right"
            step="0.01" min="0" placeholder="{{ __('Paid amount') }}" />
        </div>

        @if($this->changeAmount > 0)
          <div class="flex justify-between text-sm text-success">
            <span>{{ __('Change') }}</span>
            <span class="font-mono font-bold">৳{{ number_format($this->changeAmount, 0) }}</span>
          </div>
        @endif
        @if($this->dueAmount > 0 && $this->paid_amount > 0)
          <div class="flex justify-between text-sm text-error">
            <span>{{ __('Due') }}</span>
            <span class="font-mono font-bold">৳{{ number_format($this->dueAmount, 0) }}</span>
          </div>
        @endif

        {{-- Checkout button --}}
        <button wire:click="checkout" wire:loading.attr="disabled" class="btn btn-primary btn-block gap-2">
          <span wire:loading.remove wire:target="checkout">
            <x-icon name="o-check" class="w-5 h-5" /> {{ __('Checkout') }} <kbd class="kbd kbd-xs ml-1">F12</kbd>
          </span>
          <span wire:loading wire:target="checkout" class="loading loading-spinner loading-sm"></span>
        </button>
      </div>
    @endif
  </div>

  {{-- Held Sales Drawer --}}
  <x-modal wire:model="showHeldSales" :title="__('Held Sales')" class="backdrop-blur">
    @if(count($heldSales) > 0)
      <div class="space-y-2">
        @foreach($heldSales as $hi => $held)
          <div class="flex items-center justify-between p-3 bg-base-200/50 rounded-lg">
            <div>
              <div class="font-medium text-sm">{{ count($held['cart']) }} {{ __('items') }} · {{ $held['customer_name'] ?: __('Walk-in') }}</div>
              <div class="text-xs text-base-content/50">{{ __('Held at') }} {{ $held['held_at'] }}</div>
            </div>
            <button wire:click="resumeSale({{ $hi }})" class="btn btn-primary btn-xs">{{ __('Resume') }}</button>
          </div>
        @endforeach
      </div>
    @else
      <p class="text-center text-base-content/50 py-8">{{ __('No held sales.') }}</p>
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showHeldSales', false)">{{ __('Close') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Receipt Modal --}}
  <x-modal wire:model="showReceipt" :title="__('Sale Complete')" class="max-w-sm backdrop-blur">
    @if($lastSale = $this->lastSale)
      <div class="bg-base-200/50 rounded-lg p-4 font-mono text-xs space-y-2" id="receipt">
        <div class="text-center">
          <p class="font-bold text-sm">{{ setting('name', 'AminHub') }}</p>
          <p class="text-[10px]">{{ setting('address', '') }}</p>
        </div>
        <div class="border-t border-dashed border-base-300 pt-2">
          <p>{{ __('Invoice') }}: {{ $lastSale->invoice_number }}</p>
          <p>{{ __('Date') }}: {{ $lastSale->created_at->format('d/m/Y h:i A') }}</p>
          <p>{{ __('Cashier') }}: {{ $lastSale->seller->name }}</p>
          @if($lastSale->customer_display !== __('Walk-in Customer'))
            <p>{{ __('Customer') }}: {{ $lastSale->customer_display }}</p>
          @endif
        </div>
        <div class="border-t border-dashed border-base-300 pt-2">
          @foreach($lastSale->items as $sItem)
            <div class="flex justify-between">
              <span>{{ $sItem->variant->product->name }} ×{{ number_format($sItem->quantity, 0) }}</span>
              <span>৳{{ number_format($sItem->subtotal, 0) }}</span>
            </div>
          @endforeach
        </div>
        <div class="border-t border-dashed border-base-300 pt-2">
          <div class="flex justify-between"><span>{{ __('Subtotal') }}</span><span>৳{{ number_format($lastSale->subtotal, 0) }}</span></div>
          @if($lastSale->discount_amount > 0)
            <div class="flex justify-between text-error"><span>{{ __('Discount') }}</span><span>-৳{{ number_format($lastSale->discount_amount, 0) }}</span></div>
          @endif
          <div class="flex justify-between font-bold text-sm border-t border-dashed border-base-300 pt-1">
            <span>{{ __('TOTAL') }}</span><span>৳{{ number_format($lastSale->grand_total, 0) }}</span>
          </div>
          <div class="flex justify-between"><span>{{ $lastSale->payment_method_label }}</span><span>৳{{ number_format($lastSale->paid_amount, 0) }}</span></div>
          @if($lastSale->change_amount > 0)
            <div class="flex justify-between text-success"><span>{{ __('Change') }}</span><span>৳{{ number_format($lastSale->change_amount, 0) }}</span></div>
          @endif
        </div>
        <div class="text-center border-t border-dashed border-base-300 pt-2 text-[10px]">
          {{ __('Thank you! আবার আসবেন!') }}
        </div>
      </div>
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showReceipt', false)">{{ __('Close') }}</x-button>
      <x-button class="btn-primary btn-sm" icon="o-printer" onclick="window.print()">{{ __('Print') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
