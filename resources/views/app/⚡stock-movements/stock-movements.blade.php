<div class="space-y-6">
  <x-header :title="__('Stock Movement Log')" :subtitle="__('Complete audit trail of all stock changes — purchases, sales, adjustments, returns.')" separator>
    <x-slot:actions>
      <x-button class="btn-ghost btn-sm" icon="o-arrow-left" link="/app/inventory" wire:navigate>{{ __('Back to Inventory') }}</x-button>
    </x-slot:actions>
  </x-header>

  {{-- Summary Row --}}
  <div class="grid grid-cols-3 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-title text-xs">{{ __('Total Movements') }}</div>
        <div class="stat-value text-lg">{{ number_format($this->summaryStats['total_movements']) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-success"><x-icon name="o-arrow-down-tray" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total In') }}</div>
        <div class="stat-value text-lg text-success">{{ number_format($this->summaryStats['total_in'], 2) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-error"><x-icon name="o-arrow-up-tray" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Out') }}</div>
        <div class="stat-value text-lg text-error">{{ number_format($this->summaryStats['total_out'], 2) }}</div>
      </div>
    </div>
  </div>

  <x-card>
    {{-- Filters --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
      <div class="lg:col-span-2">
        <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Product, variant, SKU...')" clearable />
      </div>
      <x-select wire:model.live="typeFilter" :options="[
        ['id' => null, 'name' => __('All Types')],
        ['id' => 'purchase', 'name' => __('Purchase')],
        ['id' => 'sale', 'name' => __('Sale')],
        ['id' => 'adjustment', 'name' => __('Adjustment')],
        ['id' => 'return_in', 'name' => __('Return In')],
        ['id' => 'return_out', 'name' => __('Return Out')],
        ['id' => 'transfer', 'name' => __('Transfer')],
      ]" />
      <x-select wire:model.live="directionFilter" :options="[
        ['id' => null, 'name' => __('All Directions')],
        ['id' => 'in', 'name' => __('Stock In ↓')],
        ['id' => 'out', 'name' => __('Stock Out ↑')],
      ]" />
      <div class="flex gap-2">
        <x-input wire:model.live="dateFrom" type="date" :label="__('From')" class="flex-1" />
        <x-input wire:model.live="dateTo" type="date" :label="__('To')" class="flex-1" />
      </div>
    </div>

    @if($search || $typeFilter || $directionFilter || $dateFrom || $dateTo)
      <div class="mb-3">
        <x-button class="btn-ghost btn-xs" icon="o-x-mark" wire:click="clearFilters">{{ __('Clear all filters') }}</x-button>
      </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Date') }}</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('Variant') }}</th>
            <th class="text-center">{{ __('Type') }}</th>
            <th class="text-center">{{ __('Direction') }}</th>
            <th class="text-right">{{ __('Qty') }}</th>
            <th class="text-right">{{ __('Original') }}</th>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Note') }}</th>
            <th>{{ __('By') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->movements as $mv)
            <tr class="hover:bg-base-200/30 transition-colors text-sm">
              <td class="text-xs whitespace-nowrap">{{ $mv->created_at->format('d M Y') }}<br>{{ $mv->created_at->format('H:i') }}</td>
              <td class="font-medium">{{ $mv->variant->product->name ?? '—' }}</td>
              <td class="text-base-content/70">{{ $mv->variant->name }}</td>
              <td class="text-center">
                <span class="badge {{ $mv->type_badge_class }} badge-xs">{{ $mv->type_label }}</span>
              </td>
              <td class="text-center">
                <span class="inline-flex items-center gap-1 {{ $mv->direction === 'in' ? 'text-success' : 'text-error' }}">
                  <x-icon :name="$mv->direction_icon" class="w-4 h-4" />
                  {{ strtoupper($mv->direction) }}
                </span>
              </td>
              <td class="text-right font-mono font-semibold {{ $mv->direction === 'in' ? 'text-success' : 'text-error' }}">
                {{ $mv->direction === 'in' ? '+' : '-' }}{{ number_format($mv->quantity, 2) }}
                <span class="text-xs text-base-content/40">{{ $mv->variant->product->baseUnit?->short_name }}</span>
              </td>
              <td class="text-right font-mono text-xs text-base-content/50">
                @if($mv->original_quantity && $mv->unit)
                  {{ number_format($mv->original_quantity, 2) }} {{ $mv->unit->short_name }}
                @else
                  —
                @endif
              </td>
              <td class="text-xs text-base-content/60">
                @if($mv->reference_type)
                  <span class="badge badge-ghost badge-xs">{{ str_replace('_', ' ', ucfirst($mv->reference_type)) }}</span>
                  <span class="text-base-content/30">#{{ $mv->reference_id }}</span>
                @else
                  —
                @endif
              </td>
              <td class="text-xs max-w-[180px] truncate" title="{{ $mv->note }}">{{ $mv->note ?? '—' }}</td>
              <td class="text-xs text-base-content/60">{{ $mv->creator?->name ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-12 text-base-content/50">
                <x-icon name="o-clock" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No stock movements found.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex items-center justify-between">
      <x-select wire:model.live="perPage" :options="[
        ['id' => 15, 'name' => '15'],
        ['id' => 30, 'name' => '30'],
        ['id' => 50, 'name' => '50'],
      ]" class="w-20" />
      <div>{{ $this->movements->onEachSide(1)->links() }}</div>
    </div>
  </x-card>
</div>
