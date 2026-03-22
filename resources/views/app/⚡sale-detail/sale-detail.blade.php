@php $sale = $this->sale; @endphp

<div class="space-y-6">
  <x-header :title="$sale->invoice_number" :subtitle="__('Sale detail and receipt')" separator>
    <x-slot:actions>
      <x-button class="btn-ghost btn-sm" icon="o-arrow-left" link="/app/sales" wire:navigate>{{ __('Back') }}</x-button>
      <x-button class="btn-outline btn-primary btn-sm" icon="o-document-text" wire:click="downloadReceipt" spinner="downloadReceipt">{{ __('Invoice PDF') }}</x-button>
      <x-button class="btn-outline btn-sm" icon="o-printer" onclick="window.print()">{{ __('Print') }}</x-button>
    </x-slot:actions>
  </x-header>

  <div class="grid md:grid-cols-3 gap-6">
    {{-- Left: Invoice Details --}}
    <div class="md:col-span-2 space-y-4">
      <x-card>
        <div class="grid sm:grid-cols-3 gap-4 text-sm">
          <div>
            <p class="text-xs text-base-content/50">{{ __('Invoice') }}</p>
            <code class="font-mono font-bold">{{ $sale->invoice_number }}</code>
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Date') }}</p>
            <p>{{ $sale->created_at->format('d M Y, h:i A') }}</p>
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Type') }}</p>
            <span class="badge badge-sm {{ $sale->sale_type === 'pos' ? 'badge-info' : 'badge-accent' }}">{{ strtoupper($sale->sale_type) }}</span>
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Customer') }}</p>
            <p class="font-medium">{{ $sale->customer_display }}</p>
            @if($sale->customer_phone)<p class="text-xs text-base-content/50">{{ $sale->customer_phone }}</p>@endif
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Sold By') }}</p>
            <p>{{ $sale->seller->name ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Status') }}</p>
            <span class="badge {{ $sale->status_badge }} badge-sm">{{ ucfirst($sale->status) }}</span>
            <span class="badge {{ $sale->payment_status_badge }} badge-sm ml-1">{{ ucfirst($sale->payment_status) }}</span>
          </div>
        </div>
      </x-card>

      {{-- Items --}}
      <x-card :title="__('Items')">
        <div class="overflow-x-auto">
          <table class="table w-full">
            <thead>
              <tr class="bg-base-200/50">
                <th>{{ __('Product') }}</th>
                <th class="text-right">{{ __('Qty') }}</th>
                <th>{{ __('Unit') }}</th>
                <th class="text-right">{{ __('Price') }}</th>
                <th class="text-right">{{ __('Discount') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($sale->items as $item)
                <tr class="hover:bg-base-200/30">
                  <td>
                    <div class="flex items-center gap-3">
                      <div class="avatar">
                        <div class="w-10 h-10 rounded bg-base-100 border border-base-200 flex items-center justify-center shadow-sm">
                          @if($item->variant->getFirstMediaUrl('images', 'thumb'))
                            <img src="{{ $item->variant->getFirstMediaUrl('images', 'thumb') }}" alt="" class="object-cover w-full h-full rounded" />
                          @elseif($item->variant->getFirstMediaUrl('images'))
                            <img src="{{ $item->variant->getFirstMediaUrl('images') }}" alt="" class="object-cover w-full h-full rounded" />
                          @elseif($item->variant->product && $item->variant->product->getFirstMediaUrl('product-images', 'thumb'))
                            <img src="{{ $item->variant->product->getFirstMediaUrl('product-images', 'thumb') }}" alt="{{ $item->variant->product->name }}" class="object-cover w-full h-full rounded" />
                          @elseif($item->variant->product && $item->variant->product->getFirstMediaUrl('product-images'))
                            <img src="{{ $item->variant->product->getFirstMediaUrl('product-images') }}" alt="{{ $item->variant->product->name }}" class="object-cover w-full h-full rounded" />
                          @else
                            <x-icon name="o-photo" class="w-5 h-5 opacity-20" />
                          @endif
                        </div>
                      </div>
                      <div>
                        <div class="font-medium text-sm">{{ $item->variant->product->name ?? '—' }}</div>
                        <div class="text-xs text-base-content/50">{{ $item->variant->name }} · {{ $item->variant->sku }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="text-right font-mono">{{ number_format($item->quantity, 2) }}</td>
                  <td class="text-sm">{{ $item->unit->short_name ?? '—' }}</td>
                  <td class="text-right font-mono">৳{{ number_format($item->unit_price, 2) }}</td>
                  <td class="text-right font-mono {{ $item->discount > 0 ? 'text-error' : 'text-base-content/30' }}">
                    ৳{{ number_format($item->discount, 2) }}
                  </td>
                  <td class="text-right font-mono font-semibold">৳{{ number_format($item->subtotal, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot>
              <tr><td colspan="5" class="text-right">{{ __('Subtotal') }}</td><td class="text-right font-mono">৳{{ number_format($sale->subtotal, 2) }}</td></tr>
              @if($sale->discount_amount > 0)
                <tr><td colspan="5" class="text-right text-error">{{ __('Discount') }} ({{ $sale->discount_type === 'percent' ? $sale->discount_value.'%' : __('Flat') }})</td>
                  <td class="text-right font-mono text-error">-৳{{ number_format($sale->discount_amount, 2) }}</td></tr>
              @endif
              @if($sale->tax > 0)
                <tr><td colspan="5" class="text-right">{{ __('Tax') }}</td><td class="text-right font-mono">৳{{ number_format($sale->tax, 2) }}</td></tr>
              @endif
              <tr class="border-t-2">
                <td colspan="5" class="text-right font-bold text-lg">{{ __('Grand Total') }}</td>
                <td class="text-right font-mono font-bold text-lg text-primary">৳{{ number_format($sale->grand_total, 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </x-card>
    </div>

    {{-- Right: Payment Summary --}}
    <div class="space-y-4">
      <x-card :title="__('Payment Summary')">
        <div class="space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-base-content/60">{{ __('Method') }}</span>
            <span class="badge badge-ghost badge-sm">{{ $sale->payment_method_label }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-base-content/60">{{ __('Grand Total') }}</span>
            <span class="font-mono font-bold">৳{{ number_format($sale->grand_total, 2) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-base-content/60">{{ __('Paid') }}</span>
            <span class="font-mono text-success">৳{{ number_format($sale->paid_amount, 2) }}</span>
          </div>
          @if($sale->change_amount > 0)
            <div class="flex justify-between text-sm">
              <span class="text-base-content/60">{{ __('Change') }}</span>
              <span class="font-mono text-info">৳{{ number_format($sale->change_amount, 2) }}</span>
            </div>
          @endif
          @if($sale->due_amount > 0)
            <div class="divider my-1"></div>
            <div class="flex justify-between font-bold text-error">
              <span>{{ __('Due Amount') }}</span>
              <span class="font-mono">৳{{ number_format($sale->due_amount, 2) }}</span>
            </div>
          @endif
        </div>
      </x-card>

      {{-- Receipt Preview --}}
      <x-card :title="__('Receipt Preview')">
        <div class="bg-base-200/50 rounded-lg p-4 font-mono text-xs space-y-2 print:bg-white">
          <div class="text-center">
            <p class="font-bold text-sm">{{ setting('name', 'AminHub') }}</p>
            <p class="text-[10px]">{{ setting('address', '') }}</p>
          </div>
          <div class="border-t border-dashed border-base-300 pt-2">
            <p>{{ $sale->invoice_number }}</p>
            <p>{{ $sale->created_at->format('d/m/Y h:i A') }}</p>
            <p>{{ __('Cashier') }}: {{ $sale->seller->name ?? '—' }}</p>
          </div>
          <div class="border-t border-dashed border-base-300 pt-2">
            @foreach($sale->items as $sItem)
              <div class="flex justify-between">
                <span class="truncate flex-1">{{ $sItem->variant->product->name }} ×{{ number_format($sItem->quantity, 0) }}</span>
                <span class="ml-2">৳{{ number_format($sItem->subtotal, 0) }}</span>
              </div>
            @endforeach
          </div>
          <div class="border-t border-dashed border-base-300 pt-2">
            <div class="flex justify-between font-bold">
              <span>{{ __('TOTAL') }}</span>
              <span>৳{{ number_format($sale->grand_total, 0) }}</span>
            </div>
          </div>
          <div class="text-center border-t border-dashed border-base-300 pt-2 text-[10px]">
            {{ __('Thank you!') }}
          </div>
        </div>
      </x-card>

      @if($sale->note)
        <x-card :title="__('Note')">
          <p class="text-sm text-base-content/70">{{ $sale->note }}</p>
        </x-card>
      @endif
    </div>
  </div>
</div>
