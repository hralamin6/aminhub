<div class="space-y-4">
  {{-- Header --}}
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
      <h2 class="text-2xl font-bold">{{ __('Batch Profitability') }}</h2>
      <p class="text-sm text-base-content/60">{{ __('Track profit per batch with purchase vs sale price analysis') }}</p>
    </div>
    <div class="flex gap-2">
      <x-button class="btn-sm" icon="o-document-arrow-down" wire:click="exportCsv" spinner>
        {{ __('Export CSV') }}
      </x-button>
    </div>
  </div>

  {{-- Variant Selector --}}
  <x-card class="bg-base-100">
    <div class="flex items-end gap-4">
      <div class="flex-1">
        <label class="label label-text text-sm">{{ __('Select Product Variant') }}</label>
        <select wire:model.live="variantFilter" class="select select-sm w-full">
          <option value="">{{ __('Choose a variant...') }}</option>
          @foreach($this->variants as $variant)
            <option value="{{ $variant->id }}">{{ $variant->product->name }} — {{ $variant->name }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </x-card>

  @if($this->variantFilter)
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <x-card class="bg-base-100">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-primary/10 rounded-lg">
            <x-icon name="o-shopping-cart" class="w-6 h-6 text-primary" />
          </div>
          <div>
            <p class="text-sm text-base-content/60">{{ __('Total Sold') }}</p>
            <p class="text-2xl font-bold">{{ number_format($this->totalSoldQty, 2) }}</p>
          </div>
        </div>
      </x-card>

      <x-card class="bg-base-100">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-info/10 rounded-lg">
            <x-icon name="o-archive-box" class="w-6 h-6 text-info" />
          </div>
          <div>
            <p class="text-sm text-base-content/60">{{ __('Current Stock') }}</p>
            <p class="text-2xl font-bold">{{ number_format($this->totalCurrentStock, 2) }}</p>
          </div>
        </div>
      </x-card>

      <x-card class="bg-base-100">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-success/10 rounded-lg">
            <x-icon name="o-currency-dollar" class="w-6 h-6 text-success" />
          </div>
          <div>
            <p class="text-sm text-base-content/60">{{ __('Total Profit') }}</p>
            <p class="text-2xl font-bold {{ $this->totalProfit >= 0 ? 'text-success' : 'text-error' }}">
              ৳{{ number_format($this->totalProfit, 0) }}
            </p>
          </div>
        </div>
      </x-card>

      <x-card class="bg-base-100">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-warning/10 rounded-lg">
            <x-icon name="o-banknotes" class="w-6 h-6 text-warning" />
          </div>
          <div>
            <p class="text-sm text-base-content/60">{{ __('Stock Value') }}</p>
            <p class="text-2xl font-bold">৳{{ number_format($this->totalStockValue, 0) }}</p>
          </div>
        </div>
      </x-card>
    </div>

    {{-- Profitability Table --}}
    <x-card class="bg-base-100">
      <div class="overflow-x-auto">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>{{ __('Batch Number') }}</th>
              <th class="text-right">{{ __('Purchased') }}</th>
              <th class="text-right">{{ __('Sold') }}</th>
              <th class="text-right">{{ __('Current Stock') }}</th>
              <th class="text-right">{{ __('Purchase Price') }}</th>
              <th class="text-right">{{ __('Sale Price') }}</th>
              <th class="text-right">{{ __('Profit/Unit') }}</th>
              <th class="text-right">{{ __('Total Profit') }}</th>
              <th class="text-right">{{ __('Stock Value') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($this->batchProfitability as $item)
              @php
                $batch = $item['batch'];
                $profitPositive = $item['profit_per_unit'] >= 0;
              @endphp
              <tr class="hover:bg-base-200/50">
                <td class="font-mono font-medium">{{ $batch->batch_number }}</td>
                <td class="text-right">{{ number_format($item['purchase_quantity'], 2) }}</td>
                <td class="text-right">{{ number_format($item['sold_quantity'], 2) }}</td>
                <td class="text-right font-semibold">{{ number_format($item['current_stock'], 2) }}</td>
                <td class="text-right">৳{{ number_format($item['avg_purchase_price'], 2) }}</td>
                <td class="text-right">৳{{ number_format($item['avg_sale_price'], 2) }}</td>
                <td class="text-right {{ $profitPositive ? 'text-success' : 'text-error' }}">
                  {{ $profitPositive ? '+' : '' }}৳{{ number_format($item['profit_per_unit'], 2) }}
                </td>
                <td class="text-right {{ $profitPositive ? 'text-success' : 'text-error' }} font-semibold">
                  {{ $profitPositive ? '+' : '' }}৳{{ number_format($item['total_profit'], 0) }}
                </td>
                <td class="text-right">৳{{ number_format($item['stock_value'], 0) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center py-8 text-base-content/40">
                  <x-icon name="o-inbox" class="w-8 h-8 mx-auto mb-2 opacity-40" />
                  {{ __('No batch data found for this variant') }}
                </td>
              </tr>
            @endforelse
          </tbody>
          <tfoot class="border-t-2 border-base-300">
            <tr class="font-bold bg-base-200/50">
              <td>{{ __('TOTAL') }}</td>
              <td class="text-right">—</td>
              <td class="text-right">{{ number_format($this->totalSoldQty, 2) }}</td>
              <td class="text-right">{{ number_format($this->totalCurrentStock, 2) }}</td>
              <td class="text-right">—</td>
              <td class="text-right">—</td>
              <td class="text-right">—</td>
              <td class="text-right {{ $this->totalProfit >= 0 ? 'text-success' : 'text-error' }}">
                ৳{{ number_format($this->totalProfit, 0) }}
              </td>
              <td class="text-right">৳{{ number_format($this->totalStockValue, 0) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </x-card>
  @else
    <x-card class="bg-base-100">
      <div class="text-center py-12 text-base-content/40">
        <x-icon name="o-chart-bar" class="w-16 h-16 mx-auto mb-4 opacity-30" />
        <p class="text-lg">{{ __('Select a product variant to view batch profitability') }}</p>
      </div>
    </x-card>
  @endif
</div>
