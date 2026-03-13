{{-- Compact Cart Header --}}
<div class="flex items-center justify-between px-2 py-1.5 border-b border-base-300 bg-primary/5">
  <h3 class="font-bold text-xs sm:text-sm flex items-center gap-1.5">
    <x-icon name="o-shopping-cart" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
    {{ __('Cart') }}
    @if(count($cart) > 0)
      <span class="badge badge-primary badge-xs">{{ count($cart) }}</span>
    @endif
  </h3>
  @if(count($cart) > 0)
    <button wire:click="clearCart" wire:confirm="{{ __('Clear cart?') }}" class="btn btn-ghost btn-xs text-error p-0 h-5 min-h-0">
      <x-icon name="o-trash" class="w-3 h-3 sm:w-3.5 sm:h-3.5" />
    </button>
  @endif
</div>

{{-- Compact Cart Items --}}
<div class="flex-1 overflow-y-auto">
  @forelse($cart as $i => $item)
    <div class="p-1.5 sm:p-2 border-b border-base-200 hover:bg-base-200/40" wire:key="cart-{{ $i }}">
      {{-- Product Info Row --}}
      <div class="flex items-start justify-between gap-1 mb-1">
        <div class="flex-1 min-w-0">
          <p class="text-xs font-semibold truncate leading-tight">{{ $item['name'] }}</p>
          <p class="text-[10px] text-base-content/50 truncate">{{ $item['variant_name'] }}</p>
        </div>
        <button wire:click="removeFromCart({{ $i }})" class="btn btn-ghost btn-xs p-0 h-4 min-h-0 text-error">
          <x-icon name="o-x-mark" class="w-3 h-3" />
        </button>
      </div>

      {{-- Compact Controls Row: Qty | Unit | Price | Total --}}
      <div class="grid grid-cols-12 gap-1 items-center">
        {{-- Qty with +/- --}}
        <div class="col-span-3">
          <div class="flex items-center gap-0.5">
            <button wire:click="decrementQty({{ $i }})" class="btn btn-xs btn-square btn-ghost p-0 h-5 w-5 min-h-0">
              <x-icon name="o-minus" class="w-2.5 h-2.5" />
            </button>
            <input type="number" wire:change="updateQty({{ $i }}, $event.target.value)"
              value="{{ $item['quantity'] }}"
              class="input input-xs w-full text-center font-mono text-[10px] px-0.5 h-5 min-h-0 py-0"
              step="0.001" min="0.001"
              onfocus="this.select()" />
            <button wire:click="incrementQty({{ $i }})" class="btn btn-xs btn-square btn-ghost p-0 h-5 w-5 min-h-0">
              <x-icon name="o-plus" class="w-2.5 h-2.5" />
            </button>
          </div>
        </div>

        {{-- Unit Selector --}}
        <div class="col-span-2">
          <select wire:change="switchUnit({{ $i }}, $event.target.value)" class="select select-xs w-full text-[10px] h-5 min-h-0 py-0 px-1">
            @foreach($item['available_units'] ?? [] as $unit)
              <option value="{{ $unit['unit_id'] }}" {{ $item['unit_id'] == $unit['unit_id'] ? 'selected' : '' }}>
                {{ $unit['unit_name'] }}
              </option>
            @endforeach
          </select>
        </div>

        {{-- Price Input --}}
        <div class="col-span-3">
          <input type="number" wire:change="updatePrice({{ $i }}, $event.target.value)"
            value="{{ $item['unit_price'] }}"
            class="input input-xs w-full text-right font-mono text-[10px] px-1 h-5 min-h-0 py-0"
            step="0.01" min="0"
            onfocus="this.select()" />
        </div>

        {{-- Total (calculated) --}}
        <div class="col-span-4 text-right">
          <span class="text-xs font-mono font-semibold text-primary">৳{{ number_format($item['quantity'] * $item['unit_price'], 0) }}</span>
        </div>
      </div>

      {{-- Batch Selector (if batches exist) --}}
      @if(count($item['available_batches'] ?? []) > 0)
        <div class="mt-1 flex items-center gap-1">
          <span class="text-[9px] text-base-content/50 whitespace-nowrap">{{ __('Batch') }}:</span>
          <select wire:change="selectBatch({{ $i }}, $event.target.value)"
            class="select select-xs w-full text-[9px] h-4 min-h-0 py-0 px-1 {{ $item['batch_id'] ? 'border-primary/50 bg-primary/5' : '' }}">
            <option value="0">{{ __('Auto (FIFO)') }}</option>
            @foreach($item['available_batches'] as $batch)
              <option value="{{ $batch['id'] }}" {{ $item['batch_id'] == $batch['id'] ? 'selected' : '' }}>
                {{ $batch['batch_number'] }} ({{ number_format($batch['current_stock'], 0) }} {{ $item['unit_name'] }}{{ $batch['is_expired'] ? ' - EXPIRED' : '' }})
              </option>
            @endforeach
          </select>
        </div>
      @endif
    </div>
  @empty
    <div class="flex flex-col items-center justify-center h-full py-8 sm:py-12">
      <x-icon name="o-shopping-cart" class="w-10 h-10 sm:w-12 sm:h-12 mb-2 opacity-20" />
      <p class="text-xs text-base-content/40">{{ __('Cart is empty') }}</p>
    </div>
  @endforelse
</div>

{{-- Compact Checkout Section --}}
@if(count($cart) > 0)
  <div class="border-t border-base-300 bg-base-100">
    <div class="p-2 space-y-1.5">
      {{-- Subtotal & Discount --}}
      <div class="flex items-center justify-between gap-2">
        <span class="text-xs text-base-content/70">{{ __('Subtotal') }}</span>
        <span class="font-mono font-semibold text-xs">৳{{ number_format($this->subtotal, 0) }}</span>
      </div>

      {{-- Discount Row --}}
      <div class="flex items-center gap-2">
        <select wire:model.live="discount_type" class="select select-xs w-12 text-[10px] h-5 min-h-0 py-0 px-0.5">
          <option value="flat">৳</option>
          <option value="percent">%</option>
        </select>
        <input type="number" wire:model.live="discount_value"
          class="input input-xs flex-1 text-right font-mono text-[10px] h-5 min-h-0 py-0 px-1"
          step="0.01" min="0" placeholder="0"
          onfocus="this.select()" />
        @if($this->discountAmount > 0)
          <span class="text-[10px] text-error font-mono whitespace-nowrap">-৳{{ number_format($this->discountAmount, 0) }}</span>
        @endif
      </div>

      {{-- Grand Total --}}
      <div class="flex justify-between items-center border-t border-base-200 pt-1">
        <span class="text-sm font-bold">{{ __('Total') }}</span>
        <span class="font-mono text-primary font-bold text-lg">৳{{ number_format($this->grandTotal, 0) }}</span>
      </div>

      {{-- Customer --}}
      <div class="space-y-1">
        @if($customer_id)
          <div class="flex items-center gap-2 p-1.5 bg-success/10 border border-success/30 rounded text-xs">
            <div class="flex-1 min-w-0">
              <p class="font-medium truncate text-[11px]">{{ $customer_name }}</p>
              <p class="text-[9px] text-base-content/50">{{ $customer_phone }}</p>
            </div>
            <button wire:click="clearCustomer" class="btn btn-ghost btn-xs p-0 h-4 min-h-0 text-error">
              <x-icon name="o-x-mark" class="w-3 h-3" />
            </button>
          </div>
        @else
          <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="customer_search"
              class="input input-xs w-full text-[10px] h-6"
              placeholder="{{ __('Search customer...') }}"
              onfocus="this.select()" />
            @if(count($this->customers) > 0)
              <div class="absolute top-full left-0 right-0 mt-0.5 bg-base-100 border border-base-300 rounded shadow-xl z-50 max-h-32 overflow-y-auto">
                @foreach($this->customers as $cust)
                  <button wire:click="selectCustomer({{ $cust['id'] }})"
                    class="w-full text-left px-2 py-1 hover:bg-base-200 border-b border-base-200 last:border-0">
                    <p class="text-[10px] font-medium">{{ $cust['name'] }}</p>
                    <p class="text-[9px] text-base-content/50">{{ $cust['phone'] }}</p>
                  </button>
                @endforeach
              </div>
            @endif
          </div>
          @if(!$customer_search || count($this->customers) == 0)
            <div class="grid grid-cols-2 gap-1">
              <input type="text" wire:model="customer_name"
                class="input input-xs text-[10px] h-5" placeholder="{{ __('Name') }}"
                onfocus="this.select()" />
              <input type="text" wire:model="customer_phone"
                class="input input-xs text-[10px] h-5" placeholder="{{ __('Phone') }}"
                onfocus="this.select()" />
            </div>
          @endif
        @endif
      </div>

      {{-- Payment --}}
      <div class="grid grid-cols-2 gap-1.5">
        <select wire:model="payment_method" class="select select-xs text-[10px] h-6">
          <option value="cash">💵 {{ __('Cash') }}</option>
          <option value="bkash">📱 bKash</option>
          <option value="nagad">📱 Nagad</option>
          <option value="card">💳 {{ __('Card') }}</option>
          <option value="mixed">🔄 {{ __('Mixed') }}</option>
        </select>
        <input type="number" wire:model.live="paid_amount"
          class="input input-xs text-right font-mono text-[10px] h-6"
          step="0.01" min="0" placeholder="{{ __('Amount') }}"
          onfocus="this.select()" />
      </div>

      {{-- Change/Due --}}
      @if($this->changeAmount > 0)
        <div class="flex justify-between text-xs text-success bg-success/10 px-2 py-1 rounded">
          <span class="font-medium">{{ __('Change') }}</span>
          <span class="font-mono font-bold">৳{{ number_format($this->changeAmount, 0) }}</span>
        </div>
      @endif
      @if($this->dueAmount > 0 && $this->paid_amount > 0)
        <div class="flex justify-between text-xs text-error bg-error/10 px-2 py-1 rounded">
          <span class="font-medium">{{ __('Due') }}</span>
          <span class="font-mono font-bold">৳{{ number_format($this->dueAmount, 0) }}</span>
        </div>
      @endif

      {{-- Action Buttons --}}
      <div class="grid grid-cols-2 gap-1.5 pt-1">
        <button wire:click="holdSale" class="btn btn-outline btn-warning btn-xs h-7">
          <x-icon name="o-pause-circle" class="w-3 h-3 mr-1" />
          {{ __('Hold') }}
        </button>
        <button wire:click="checkout" wire:loading.attr="disabled"
          class="btn btn-primary btn-xs h-7 shadow">
          <span wire:loading.remove wire:target="checkout" class="flex items-center">
            <x-icon name="o-check-circle" class="w-3 h-3 mr-1" />
            {{ __('Pay') }}
          </span>
          <span wire:loading wire:target="checkout" class="flex items-center">
            <span class="loading loading-spinner loading-xs mr-1"></span>
            {{ __('Processing') }}
          </span>
        </button>
      </div>
    </div>
  </div>
@endif
