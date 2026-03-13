<div class="space-y-4">
  {{-- Header --}}
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
      <h2 class="text-2xl font-bold">{{ __('Expiry Alerts') }}</h2>
      <p class="text-sm text-base-content/60">{{ __('Track products nearing expiry or already expired') }}</p>
    </div>
    <div class="flex gap-2">
      <x-button class="btn-sm" icon="o-document-arrow-down" wire:click="exportCsv" spinner>
        {{ __('Export CSV') }}
      </x-button>
    </div>
  </div>

  {{-- Summary Cards --}}
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <x-card class="bg-warning/10 border-warning/30">
      <div class="flex items-center gap-3">
        <div class="p-3 bg-warning/20 rounded-lg">
          <x-icon name="o-clock" class="w-6 h-6 text-warning" />
        </div>
        <div>
          <p class="text-sm text-base-content/70">{{ __('Expiring Soon Value') }}</p>
          <p class="text-2xl font-bold text-warning">৳{{ number_format($this->totalExpiringValue, 0) }}</p>
          <p class="text-xs text-base-content/50">{{ __('Next 30 days') }}</p>
        </div>
      </div>
    </x-card>

    <x-card class="bg-error/10 border-error/30">
      <div class="flex items-center gap-3">
        <div class="p-3 bg-error/20 rounded-lg">
          <x-icon name="o-x-circle" class="w-6 h-6 text-error" />
        </div>
        <div>
          <p class="text-sm text-base-content/70">{{ __('Expired Stock Value') }}</p>
          <p class="text-2xl font-bold text-error">৳{{ number_format($this->totalExpiredValue, 0) }}</p>
          <p class="text-xs text-base-content/50">{{ __('Immediate action required') }}</p>
        </div>
      </div>
    </x-card>
  </div>

  {{-- Filters --}}
  <x-card class="bg-base-100">
    <div class="flex flex-wrap gap-4">
      <div class="flex-1 min-w-[200px]">
        <label class="label label-text text-sm">{{ __('Status Filter') }}</label>
        <select wire:model.live="statusFilter" class="select select-sm w-full">
          <option value="all">{{ __('All') }}</option>
          <option value="expiring_soon">{{ __('Expiring Soon (30 days)') }}</option>
          <option value="expired">{{ __('Expired') }}</option>
        </select>
      </div>
    </div>
  </x-card>

  {{-- Alerts Table --}}
  <x-card class="bg-base-100">
    <div class="overflow-x-auto">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>{{ __('Batch Number') }}</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Variant') }}</th>
            <th class="text-right">{{ __('Current Stock') }}</th>
            <th>{{ __('Unit') }}</th>
            <th>{{ __('Expiry Date') }}</th>
            <th>{{ __('Days Left') }}</th>
            <th>{{ __('Status') }}</th>
            <th class="text-right">{{ __('Stock Value') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->expiringBatches as $item)
            @php
              $batch = $item['batch'];
              $product = $item['product'];
              $variant = $item['variant'];
              $isExpired = $item['is_expired'];
              $daysLeft = $item['days_until_expiry'];
            @endphp
            <tr class="hover:bg-base-200/50 {{ $isExpired ? 'bg-error/5' : '' }}">
              <td class="font-mono font-medium">{{ $batch->batch_number }}</td>
              <td>{{ $product?->name ?? 'N/A' }}</td>
              <td>{{ $variant?->name ?? 'N/A' }}</td>
              <td class="text-right font-semibold">{{ number_format($item['current_stock'], 2) }}</td>
              <td>{{ $product?->baseUnit?->short_name ?? 'pc' }}</td>
              <td>{{ $batch->expiry_date?->format('d M Y') }}</td>
              <td class="{{ $isExpired ? 'text-error' : ($daysLeft <= 7 ? 'text-warning' : 'text-success') }}">
                @if($isExpired)
                  {{ abs($daysLeft) }} {{ __('days ago') }}
                @else
                  {{ $daysLeft }} {{ __('days') }}
                @endif
              </td>
              <td>
                @if($isExpired)
                  <span class="badge badge-error badge-sm">{{ __('Expired') }}</span>
                @elseif($daysLeft <= 7)
                  <span class="badge badge-error badge-sm">{{ __('Critical') }}</span>
                @elseif($daysLeft <= 30)
                  <span class="badge badge-warning badge-sm">{{ __('Warning') }}</span>
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
                <x-icon name="o-check-circle" class="w-8 h-8 mx-auto mb-2 opacity-40" />
                {{ __('No expiry alerts found') }}
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-card>
</div>
