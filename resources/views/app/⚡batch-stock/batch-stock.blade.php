<div class="space-y-4">
  {{-- Header --}}
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
      <h2 class="text-2xl font-bold">{{ __('Batch Stock Report') }}</h2>
      <p class="text-sm text-base-content/60">{{ __('Track stock by batch with FIFO and expiry tracking') }}</p>
    </div>
    <div class="flex gap-2">
      <x-button class="btn-sm" icon="o-document-arrow-down" wire:click="exportCsv" spinner>
        {{ __('Export CSV') }}
      </x-button>
    </div>
  </div>

  {{-- Summary Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <x-card class="bg-base-100">
      <div class="flex items-center gap-3">
        <div class="p-3 bg-primary/10 rounded-lg">
          <x-icon name="o-archive-box" class="w-6 h-6 text-primary" />
        </div>
        <div>
          <p class="text-sm text-base-content/60">{{ __('Total Batches') }}</p>
          <p class="text-2xl font-bold">{{ $this->batchStock->count() }}</p>
        </div>
      </div>
    </x-card>

    <x-card class="bg-base-100">
      <div class="flex items-center gap-3">
        <div class="p-3 bg-warning/10 rounded-lg">
          <x-icon name="o-clock" class="w-6 h-6 text-warning" />
        </div>
        <div>
          <p class="text-sm text-base-content/60">{{ __('Expiring Soon') }}</p>
          <p class="text-2xl font-bold text-warning">{{ $this->expiringSoonCount }}</p>
        </div>
      </div>
    </x-card>

    <x-card class="bg-base-100">
      <div class="flex items-center gap-3">
        <div class="p-3 bg-error/10 rounded-lg">
          <x-icon name="o-x-circle" class="w-6 h-6 text-error" />
        </div>
        <div>
          <p class="text-sm text-base-content/60">{{ __('Expired') }}</p>
          <p class="text-2xl font-bold text-error">{{ $this->expiredCount }}</p>
        </div>
      </div>
    </x-card>

    <x-card class="bg-base-100">
      <div class="flex items-center gap-3">
        <div class="p-3 bg-success/10 rounded-lg">
          <x-icon name="o-currency-dollar" class="w-6 h-6 text-success" />
        </div>
        <div>
          <p class="text-sm text-base-content/60">{{ __('Stock Value') }}</p>
          <p class="text-2xl font-bold text-success">৳{{ number_format($this->totalStockValue, 0) }}</p>
        </div>
      </div>
    </x-card>
  </div>

  {{-- Filters --}}
  <x-card class="bg-base-100">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div>
        <label class="label label-text text-sm">{{ __('Search Batch') }}</label>
        <input type="text" wire:model.live.debounce.300ms="batchSearch" class="input input-sm w-full" placeholder="Batch number..." />
      </div>

      <div>
        <label class="label label-text text-sm">{{ __('Variant') }}</label>
        <select wire:model.live="variantFilter" class="select select-sm w-full">
          <option value="">{{ __('All Variants') }}</option>
          @foreach($this->variants as $variant)
            <option value="{{ $variant->id }}">{{ $variant->product->name }} — {{ $variant->name }}</option>
          @endforeach
        </select>
      </div>

      <div>
        <label class="label label-text text-sm">{{ __('Expiry Status') }}</label>
        <select wire:model.live="expiryFilter" class="select select-sm w-full">
          <option value="all">{{ __('All') }}</option>
          <option value="expiring_soon">{{ __('Expiring Soon (30 days)') }}</option>
          <option value="expired">{{ __('Expired') }}</option>
        </select>
      </div>
    </div>
  </x-card>

  {{-- Batch Table --}}
  <x-card class="bg-base-100">
    <div class="overflow-x-auto">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>{{ __('Batch Number') }}</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Variant') }}</th>
            <th class="text-right">{{ __('Initial Qty') }}</th>
            <th class="text-right">{{ __('Current Stock') }}</th>
            <th>{{ __('Unit') }}</th>
            <th>{{ __('Expiry Date') }}</th>
            <th>{{ __('Status') }}</th>
            <th class="text-right">{{ __('Stock Value') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->batchStock as $item)
            @php
              $batch = $item['batch'];
              $product = $item['product'];
              $variant = $item['variant'];
              $isExpired = $item['is_expired'];
              $isExpiringSoon = $item['is_expiring_soon'];
              $daysLeft = $item['days_until_expiry'];
            @endphp
            <tr class="hover:bg-base-200/50">
              <td class="font-mono font-medium">{{ $batch->batch_number }}</td>
              <td>{{ $product?->name ?? 'N/A' }}</td>
              <td>{{ $variant?->name ?? 'N/A' }}</td>
              <td class="text-right">{{ number_format($item['initial_quantity'], 2) }}</td>
              <td class="text-right font-semibold">{{ number_format($item['current_stock'], 2) }}</td>
              <td>{{ $product?->baseUnit?->short_name ?? 'pc' }}</td>
              <td>
                @if($batch->expiry_date)
                  <span class="{{ $isExpired ? 'text-error' : ($isExpiringSoon ? 'text-warning' : '') }}">
                    {{ $batch->expiry_date->format('d M Y') }}
                  </span>
                @else
                  <span class="text-base-content/40">—</span>
                @endif
              </td>
              <td>
                @if($isExpired)
                  <span class="badge badge-error badge-sm">{{ __('Expired') }}</span>
                @elseif($isExpiringSoon)
                  <span class="badge badge-warning badge-sm">{{ $daysLeft }} {{ __('days left') }}</span>
                @else
                  <span class="badge badge-success badge-sm">{{ __('OK') }}</span>
                @endif
              </td>
              <td class="text-right">
                ৳{{ number_format($item['current_stock'] * ($variant?->purchase_price ?? 0), 0) }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-8 text-base-content/40">
                <x-icon name="o-inbox" class="w-8 h-8 mx-auto mb-2 opacity-40" />
                {{ __('No batches found') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-card>
</div>
