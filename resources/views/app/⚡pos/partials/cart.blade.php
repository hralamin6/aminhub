{{-- Hide number spinners --}}
<style>
  .pos-cart input[type=number]::-webkit-inner-spin-button,
  .pos-cart input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
  .pos-cart input[type=number] { -moz-appearance: textfield; }
  .pos-cart .ci { transition: border-color .12s; }
  .pos-cart .ci:focus { border-color: oklch(var(--p) / .6); background: oklch(var(--b1)); outline: none; }
</style>

<div class="pos-cart flex flex-col h-full overflow-hidden">

  {{-- ── Header ─────────────────────────────── --}}
  <div class="flex items-center justify-between px-3 py-2 border-b border-base-300 bg-gradient-to-r from-primary/5 to-transparent shrink-0">
    <h3 class="font-bold text-sm flex items-center gap-2">
      <x-icon name="o-shopping-cart" class="w-4 h-4 text-primary" />
      {{ __('Cart') }}
      @if(count($cart) > 0)
        <span class="badge badge-primary badge-sm">{{ count($cart) }}</span>
      @endif
    </h3>
    @if(count($cart) > 0)
      <button wire:click="clearCart" wire:confirm="{{ __('Start a new sale? Cart will be cleared.') }}"
        class="btn btn-ghost btn-xs text-error gap-1 h-6 min-h-0 hover:bg-error/10 text-[10px]">
        <x-icon name="o-arrow-path" class="w-3 h-3" />
        {{ __('New Sale') }}
      </button>
    @endif
  </div>

  {{-- ── Column Labels ─────────────────────── --}}
  @if(count($cart) > 0)
    <div class="flex items-center gap-1 px-2.5 py-1 bg-base-200/40 border-b border-base-200 shrink-0">
      <span class="flex-1 text-[9px] text-base-content/35 font-medium uppercase tracking-wide">{{ __('Item') }}</span>
      <span class="text-[9px] text-base-content/35 font-medium uppercase tracking-wide text-center w-[88px]">{{ __('Qty') }}</span>
      <span class="text-[9px] text-base-content/35 font-medium text-center" style="width:68px">{{ __('Unit') }}</span>
      <span class="text-[9px] text-base-content/35 font-medium text-right" style="width:62px">{{ __('Price') }}</span>
      <span class="text-[9px] text-base-content/35 font-medium text-center w-4">=</span>
      <span class="text-[9px] text-base-content/35 font-medium text-right" style="width:62px">{{ __('Total') }}</span>
      <span style="width:28px"></span>
    </div>
  @endif

  {{-- ── Scrollable Items ──────────────────── --}}
  <div class="flex-1 overflow-y-auto min-h-0 divide-y divide-base-200/60">
    @forelse($cart as $i => $item)
      @php
        $isLocked  = $item['price_locked'] ?? false;
        $hasUnits  = count($item['available_units'] ?? []) > 1;
        $lineTotal = round($item['quantity'] * $item['unit_price'], 2);
        $convRate  = (float) ($item['conversion_rate'] ?? 1);
        $baseCost  = (float) ($item['purchase_price'] ?? 0);
        $unitCost  = $baseCost * $convRate;
        $profit    = round(($item['unit_price'] - $unitCost) * $item['quantity'], 2);
        $baseUnitName = $item['available_units'][0]['unit_name'] ?? 'pc';
      @endphp

      {{-- Alpine purely handles DOM calculation for instant feedback, leaving Livewire to own true state --}}
      <div wire:key="cart-{{ $i }}"
        class="group hover:bg-primary/3 transition-colors"
        x-data="{
          locked: {{ $isLocked ? 'true' : 'false' }},
          cost: {{ $unitCost }},
          
          calcTotal() {
            let q = parseFloat(this.$refs.qi.value) || 0;
            let p = parseFloat(this.$refs.pi.value) || 0;
            this.$refs.ti.value = (Math.round(q * p * 100) / 100).toFixed(2);
            this.updateProfit(q, p);
          },
          
          calcReverse() {
            let t = parseFloat(this.$refs.ti.value) || 0;
            if (this.locked) {
              let p = parseFloat(this.$refs.pi.value) || 0;
              if (p > 0) {
                let q = Math.round((t / p) * 1000) / 1000;
                this.$refs.qi.value = (q % 1 === 0 ? q : q.toFixed(3));
                this.updateProfit(q, p);
              }
            } else {
              let q = parseFloat(this.$refs.qi.value) || 0;
              if (q > 0) {
                let p = Math.round((t / q) * 100) / 100;
                this.$refs.pi.value = p.toFixed(2);
                this.updateProfit(q, p);
              }
            }
          },

          updateProfit(q, p) {
            if (this.cost > 0 && this.$refs.profitLabel) {
              let pr = Math.round((p - this.cost) * q);
              this.$refs.profitLabel.innerText = pr >= 0 ? '+৳' + pr : '-৳' + Math.abs(pr);
              this.$refs.profitLabel.className = pr >= 0 ? 'text-success/60' : 'text-error/60';
            }
          }
        }">

        {{-- Row 1: Name + batch + lock + delete --}}
        <div class="flex items-start gap-1 px-2.5 pt-1.5 pb-0.5">
          <div class="flex-1 min-w-0 pt-0.5">
            <p class="text-[11px] font-semibold leading-tight text-base-content truncate">{{ $item['name'] }}</p>
            <p class="text-[9px] text-base-content/35 truncate mt-0.5">
              <span>{{ $item['sku'] }}</span>
              @if($item['variant_name'] && $item['variant_name'] !== $item['name'])
                <span>· {{ $item['variant_name'] }}</span>
              @endif
              @if(($item['purchase_price'] ?? 0) > 0)
                · <span x-ref="profitLabel" class="{{ $profit >= 0 ? 'text-success/60' : 'text-error/60' }}">{{ $profit >= 0 ? '+' : '' }}৳{{ number_format($profit, 0) }}</span>
              @endif
            </p>
          </div>
          
          {{-- Batch --}}
          @if(count($item['available_batches'] ?? []) > 0)
            <div class="shrink-0 w-28">
              <select wire:change="selectBatch({{ $i }}, $event.target.value)"
                class="select select-xs w-full text-[9px] h-6 min-h-0 py-0 px-1 bg-base-200/60 border-base-300 rounded
                  {{ $item['batch_id'] ? 'text-primary border-primary/30' : '' }}">
                @if(count($item['available_batches']) > 1)
                  <option value="">{{ __('Select Batch...') }}</option>
                @endif
                @foreach($item['available_batches'] as $batch)
                  @php
                    $stockStr = number_format($batch['current_stock'], 0) . ' ' . $baseUnitName;
                    if($convRate != 1) {
                        $unitStock = floor($batch['current_stock'] / $convRate);
                        $stockStr .= ' · ' . $unitStock . ' ' . $item['unit_name'];
                    }
                  @endphp
                  <option value="{{ $batch['id'] }}" {{ $item['batch_id'] == $batch['id'] ? 'selected' : '' }} title="{{ $stockStr }}">
                    {{ $batch['batch_number'] }} ({{ $stockStr }}{{ $batch['is_expired'] ? ' ⚠' : '' }})
                  </option>
                @endforeach
              </select>
            </div>
          @endif

          {{-- Lock --}}
          <button wire:click="togglePriceLock({{ $i }})" @click.prevent=""
            title="{{ $isLocked ? __('Locked: editing total changes qty') : __('Unlocked: editing total changes price') }}"
            class="shrink-0 w-6 h-6 flex items-center justify-center rounded transition-colors
              {{ $isLocked ? 'text-warning bg-warning/10 hover:bg-warning/20' : 'text-base-content/25 hover:text-base-content/60 hover:bg-base-200' }}">
            <x-icon name="{{ $isLocked ? 'o-lock-closed' : 'o-lock-open' }}" class="w-3 h-3" />
          </button>
          
          {{-- Delete (ALWAYS visible) --}}
          <button wire:click="removeFromCart({{ $i }})"
            class="shrink-0 w-6 h-6 flex items-center justify-center rounded text-error opacity-70 hover:opacity-100 transition-colors hover:bg-error/10">
            <x-icon name="o-x-mark" class="w-4 h-4" />
          </button>
        </div>

        {{-- Row 2: Controls --}}
        <div class="flex items-center gap-1 px-2.5 pb-1.5">

          {{-- Qty: [-] [input] [+] --}}
          <div class="flex items-center bg-base-200/80 rounded-lg border border-base-300 overflow-hidden h-7 flex-1">
            <button wire:click="decrementQty({{ $i }})" 
              @click="let q = parseFloat($refs.qi.value) || 0; if(q > 1) { $refs.qi.value = Math.round((q-1)*1000)/1000; calcTotal(); }"
              class="w-6 h-full shrink-0 flex items-center justify-center hover:bg-base-300 text-base-content/40 hover:text-primary transition-colors border-r border-base-200">
              <x-icon name="o-minus" class="w-2.5 h-2.5" />
            </button>
            <input x-ref="qi" 
              type="number"
              value="{{ (float) $item['quantity'] }}"
              @input="calcTotal()"
              wire:change="updateQty({{ $i }}, $event.target.value)"
              @focus="$el.select()"
              class="ci flex-1 w-0 text-center text-xs font-mono font-bold bg-transparent border-0 h-full py-0"
              step="0.001" min="0.001" tabindex="{{ $i * 5 + 1 }}" />
            <button wire:click="incrementQty({{ $i }})" 
              @click="let q = parseFloat($refs.qi.value) || 0; $refs.qi.value = Math.round((q+1)*1000)/1000; calcTotal();"
              class="w-6 h-full shrink-0 flex items-center justify-center hover:bg-base-300 text-base-content/40 hover:text-primary transition-colors border-l border-base-200">
              <x-icon name="o-plus" class="w-2.5 h-2.5" />
            </button>
          </div>

          {{-- Unit --}}
          <div style="width:68px; flex-shrink:0">
            @if($hasUnits)
              <select wire:change="switchUnit({{ $i }}, $event.target.value)"
                class="select select-xs w-full h-7 min-h-0 py-0 px-1.5 text-[10px] font-semibold bg-base-200/80 border-base-300 rounded-lg">
                @foreach($item['available_units'] ?? [] as $unit)
                  <option value="{{ $unit['unit_id'] }}" {{ $item['unit_id'] == $unit['unit_id'] ? 'selected' : '' }}>
                    {{ $unit['unit_name'] }}
                  </option>
                @endforeach
              </select>
            @else
              <div class="flex items-center justify-center h-7 text-[10px] font-semibold text-base-content/45
                bg-base-200/40 rounded-lg border border-base-200 px-1 truncate">
                {{ $item['unit_name'] }}
              </div>
            @endif
          </div>

          {{-- Unit Price --}}
          <div style="width:62px; flex-shrink:0">
            <input x-ref="pi" 
              type="number"
              value="{{ (float) $item['unit_price'] }}"
              @input="calcTotal()"
              wire:change="updatePrice({{ $i }}, $event.target.value)"
              @focus="$el.select()"
              {{ $isLocked ? 'readonly' : '' }}
              class="ci w-full h-7 text-right text-[11px] font-mono px-1.5 rounded-lg border border-base-300 bg-base-200/80 block {{ $isLocked ? 'opacity-40 cursor-not-allowed' : '' }}"
              step="0.01" min="0" tabindex="{{ $i * 5 + 2 }}" />
          </div>

          {{-- = --}}
          <span class="text-[9px] text-base-content/25 font-bold shrink-0 w-4 text-center">=</span>

          {{-- Total --}}
          <div style="width:62px; flex-shrink:0">
            <input x-ref="ti" 
              type="number"
              value="{{ number_format($lineTotal, 2, '.', '') }}"
              @input="calcReverse()"
              wire:change="updateTotalPrice({{ $i }}, $event.target.value)"
              @focus="$el.select()"
              class="ci w-full h-7 text-right text-[11px] font-mono font-bold px-1.5 rounded-lg border border-primary/20 bg-primary/6 text-primary block"
              step="0.01" min="0" tabindex="{{ $i * 5 + 3 }}" />
          </div>

          {{-- Spacer to align with header --}}
          <div style="width:28px; flex-shrink:0"></div>

        </div>

        {{-- End Row 2 --}}

      </div>{{-- end item --}}
    @empty
      <div class="flex flex-col items-center justify-center h-full min-h-[120px] py-10">
        <div class="w-14 h-14 rounded-full bg-base-200/50 flex items-center justify-center mb-3">
          <x-icon name="o-shopping-cart" class="w-7 h-7 opacity-20" />
        </div>
        <p class="text-sm font-medium text-base-content/25">{{ __('Cart is empty') }}</p>
        <p class="text-xs text-base-content/20 mt-0.5">{{ __('Tap a product to add') }}</p>
      </div>
    @endforelse
  </div>

  {{-- ── Checkout — always pinned ─────────────────── --}}
  @if(count($cart) > 0)
    <div class="border-t-2 border-base-300 bg-base-100 shrink-0">
      <div class="px-3 py-2 space-y-1.5">

        {{-- Summary --}}
        <div class="space-y-0.5">
          <div class="flex justify-between text-xs">
            <span class="text-base-content/50">{{ __('Subtotal') }}</span>
            <span class="font-mono font-semibold">৳{{ number_format($this->subtotal, 0) }}</span>
          </div>
          @if($this->totalProfit > 0)
            <div class="flex justify-between text-xs">
              <span class="text-success/70 flex items-center gap-1">
                <x-icon name="o-arrow-trending-up" class="w-3 h-3" />{{ __('Est. Profit') }}
              </span>
              <span class="font-mono font-semibold text-success/70">+৳{{ number_format($this->totalProfit, 0) }}</span>
            </div>
          @endif
        </div>

        {{-- Discount --}}
        <div class="flex items-center gap-1.5">
          <select wire:model.live="discount_type"
            class="select select-xs h-7 min-h-0 py-0 px-1.5 text-[10px] border-base-300 bg-base-200/60 rounded-lg shrink-0"
            style="width:72px">
            <option value="flat">৳ Flat</option>
            <option value="percent">% Off</option>
          </select>
          <input type="number" wire:model.live.debounce.600ms="discount_value"
            class="input input-xs flex-1 text-right font-mono text-xs h-7 min-h-0 bg-base-200/60 border-base-300 rounded-lg"
            step="0.01" min="0" placeholder="{{ __('0') }}" onfocus="this.select()" />
          @if($this->discountAmount > 0)
            <span class="text-[10px] text-error font-mono font-bold whitespace-nowrap shrink-0">
              -৳{{ number_format($this->discountAmount, 0) }}
            </span>
          @endif
        </div>

        {{-- Grand Total --}}
        <div class="flex justify-between items-center bg-primary/8 border border-primary/15 rounded-xl px-3 py-2">
          <div>
            <p class="text-sm font-black text-base-content leading-none">{{ __('Total') }}</p>
            <p class="text-[10px] text-base-content/35 mt-0.5">{{ count($cart) }} {{ Str::plural('item', count($cart)) }}</p>
          </div>
          <span class="font-mono text-primary font-black text-2xl tracking-tight">৳{{ number_format($this->grandTotal, 0) }}</span>
        </div>

        {{-- Customer --}}
        <div>
          @if($customer_id)
            <div class="flex items-center gap-2 px-2.5 py-1.5 bg-success/8 border border-success/20 rounded-xl">
              <div class="w-6 h-6 rounded-full bg-success/20 flex items-center justify-center shrink-0">
                <x-icon name="o-user" class="w-3 h-3 text-success" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold truncate">{{ $customer_name }}</p>
                @if($customer_phone)<p class="text-[9px] text-base-content/35">{{ $customer_phone }}</p>@endif
              </div>
              <button wire:click="clearCustomer" class="btn btn-ghost btn-xs p-0 h-5 w-5 min-h-0 text-error hover:bg-error/10 rounded-full shrink-0">
                <x-icon name="o-x-mark" class="w-3 h-3" />
              </button>
            </div>
          @else
            <div class="space-y-1">
              <div class="relative">
                <div class="absolute inset-y-0 left-2 flex items-center pointer-events-none">
                  <x-icon name="o-user" class="w-3 h-3 text-base-content/25" />
                </div>
                <input type="text" wire:model.live.debounce.300ms="customer_search"
                  class="input input-xs w-full text-xs h-7 pl-6 bg-base-200/60 border-base-300 rounded-lg"
                  placeholder="{{ __('Search customer by name or phone...') }}" onfocus="this.select()" />
                @if(count($this->customers) > 0)
                  <div class="absolute bottom-full left-0 right-0 mb-1 bg-base-100 border border-base-300 rounded-xl shadow-2xl z-50 max-h-36 overflow-y-auto">
                    @foreach($this->customers as $cust)
                      <button wire:click="selectCustomer({{ $cust['id'] }})"
                        class="w-full text-left px-3 py-1.5 hover:bg-primary/5 border-b border-base-100 last:border-0 transition-colors first:rounded-t-xl last:rounded-b-xl">
                        <p class="text-xs font-semibold">{{ $cust['name'] }}</p>
                        <p class="text-[9px] text-base-content/40">{{ $cust['phone'] }}</p>
                      </button>
                    @endforeach
                  </div>
                @endif
              </div>
              @if(!$customer_search || count($this->customers) == 0)
                <div class="grid grid-cols-2 gap-1">
                  <input type="text" wire:model="customer_name"
                    class="input input-xs text-xs h-7 bg-base-200/60 border-base-300 rounded-lg"
                    placeholder="{{ __('Name') }}" onfocus="this.select()" />
                  <input type="text" wire:model="customer_phone"
                    class="input input-xs text-xs h-7 bg-base-200/60 border-base-300 rounded-lg"
                    placeholder="{{ __('Phone') }}" onfocus="this.select()" />
                </div>
              @endif
            </div>
          @endif
        </div>

        {{-- Payment --}}
        <div class="grid grid-cols-2 gap-1.5">
          <select wire:model="payment_method" class="select select-xs text-[11px] h-8 bg-base-200/60 border-base-300 rounded-lg">
            <option value="cash">💵 {{ __('Cash') }}</option>
            <option value="bkash">📱 bKash</option>
            <option value="nagad">📱 Nagad</option>
            <option value="card">💳 {{ __('Card') }}</option>
            <option value="mixed">🔄 {{ __('Mixed') }}</option>
          </select>
          <input type="number" wire:model.live.debounce.900ms="paid_amount"
            class="input input-xs text-right font-mono font-bold text-xs h-8 bg-base-200/60 border-base-300 rounded-lg placeholder:font-normal"
            step="0.01" min="0" placeholder="{{ __('Paid amount') }}" onfocus="this.select()" />
        </div>

        {{-- Change / Due --}}
        @if($this->changeAmount > 0)
          <div class="flex justify-between items-center text-xs px-3 py-1.5 bg-success/10 border border-success/20 rounded-xl">
            <span class="font-semibold text-success flex items-center gap-1">
              <x-icon name="o-arrow-down-circle" class="w-3.5 h-3.5" />{{ __('Change') }}
            </span>
            <span class="font-mono font-bold text-success text-base">৳{{ number_format($this->changeAmount, 0) }}</span>
          </div>
        @endif
        @if($this->dueAmount > 0 && $this->paid_amount > 0)
          <div class="flex justify-between items-center text-xs px-3 py-1.5 bg-error/10 border border-error/20 rounded-xl">
            <span class="font-semibold text-error flex items-center gap-1">
              <x-icon name="o-exclamation-circle" class="w-3.5 h-3.5" />{{ __('Due') }}
            </span>
            <span class="font-mono font-bold text-error text-base">৳{{ number_format($this->dueAmount, 0) }}</span>
          </div>
        @endif

        {{-- Actions --}}
        <div class="grid grid-cols-2 gap-2 pt-0.5">
          <button wire:click="holdSale" class="btn btn-outline btn-warning btn-sm h-10 gap-2">
            <x-icon name="o-pause-circle" class="w-4 h-4" />
            {{ __('Hold') }}
          </button>
          <button wire:click="checkout" wire:loading.attr="disabled"
            class="btn btn-primary btn-sm h-10 gap-2 shadow hover:shadow-md transition-shadow font-bold">
            <span wire:loading.remove wire:target="checkout" class="flex items-center gap-1.5">
              <x-icon name="o-check-circle" class="w-4 h-4" />
              {{ __('Pay Now') }}
            </span>
            <span wire:loading wire:target="checkout" class="flex items-center gap-1.5">
              <span class="loading loading-spinner loading-xs"></span>
              {{ __('Processing…') }}
            </span>
          </button>
        </div>

      </div>
    </div>
  @endif

</div>{{-- .pos-cart --}}
