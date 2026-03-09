<div class="space-y-6">
  <x-header :title="__('Inventory')" :subtitle="__('Stock overview — track quantities, values, and alerts across all products.')" separator>
    <x-slot:actions>
      @can('inventory.movements')
        <x-button class="btn-outline btn-sm" icon="o-clock" link="/app/stock-movements" wire:navigate>{{ __('Movement Log') }}</x-button>
      @endcan
      @can('inventory.adjust')
        <x-button class="btn-outline btn-sm" icon="o-adjustments-horizontal" link="/app/stock-adjustments" wire:navigate>{{ __('Adjustments') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats Cards --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-primary"><x-icon name="o-cube" class="w-6 h-6" /></div>
        <div class="stat-title text-xs">{{ __('Total Variants') }}</div>
        <div class="stat-value text-xl text-primary">{{ $this->stats['total_products'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-success"><x-icon name="o-banknotes" class="w-6 h-6" /></div>
        <div class="stat-title text-xs">{{ __('Stock Value') }}</div>
        <div class="stat-value text-xl text-success">৳{{ number_format($this->stats['total_value'], 0) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-warning"><x-icon name="o-exclamation-triangle" class="w-6 h-6" /></div>
        <div class="stat-title text-xs">{{ __('Low Stock') }}</div>
        <div class="stat-value text-xl text-warning">{{ $this->stats['low_stock'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-error"><x-icon name="o-clock" class="w-6 h-6" /></div>
        <div class="stat-title text-xs">{{ __('Expiring Soon') }}</div>
        <div class="stat-value text-xl text-error">{{ $this->stats['expiring_soon'] }}</div>
      </div>
    </div>
  </div>

  <x-card>
    {{-- Filters --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
      <div class="lg:col-span-2">
        <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Product name, SKU...')" clearable />
      </div>
      <x-select wire:model.live="categoryFilter" :options="$this->categoryOptions" icon="o-tag" />
      <div class="flex gap-2">
        <x-select wire:model.live="brandFilter" :options="$this->brandOptions" icon="o-building-storefront" class="flex-1" />
        @if($search || $categoryFilter || $brandFilter)
          <x-button class="btn-ghost btn-sm self-end" icon="o-x-mark" wire:click="clearFilters" title="{{ __('Clear') }}" />
        @endif
      </div>
    </div>

    {{-- Stock Table --}}
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Product') }}</th>
            <th>{{ __('Variant') }}</th>
            <th class="text-right">{{ __('Total Stock') }}</th>
            <th class="text-right">{{ __('Reserved') }}</th>
            <th class="text-right">{{ __('Available') }}</th>
            <th class="text-right">{{ __('Value') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-right">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->variants as $variant)
            @php
              $stock = $this->getStock($variant->id);
              $status = $this->getStockStatus($variant->id, $variant->product->min_stock ?? 0);
              $value = $stock['total'] * (float)$variant->purchase_price;
            @endphp
            <tr class="hover:bg-base-200/30 transition-colors group">
              <td>
                <div class="font-medium">{{ $variant->product->name }}</div>
                <div class="text-xs text-base-content/50">
                  {{ $variant->product->category?->name ?? '—' }}
                  @if($variant->product->sku)
                    · <code class="text-xs">{{ $variant->product->sku }}</code>
                  @endif
                </div>
              </td>
              <td>
                <span class="text-sm font-medium">{{ $variant->name }}</span>
                @if($variant->sku)
                  <br><code class="text-xs text-base-content/40">{{ $variant->sku }}</code>
                @endif
              </td>
              <td class="text-right font-mono">
                {{ number_format($stock['total'], 2) }}
                <span class="text-xs text-base-content/40">{{ $variant->product->baseUnit?->short_name }}</span>
              </td>
              <td class="text-right font-mono text-base-content/60">
                {{ $stock['reserved'] > 0 ? number_format($stock['reserved'], 2) : '—' }}
              </td>
              <td class="text-right font-mono font-semibold">
                {{ number_format($stock['available'], 2) }}
              </td>
              <td class="text-right font-mono text-sm">
                ৳{{ number_format($value, 0) }}
              </td>
              <td class="text-center">
                @if($status === 'out')
                  <span class="badge badge-error badge-sm gap-1">
                    <x-icon name="o-x-circle" class="w-3 h-3" /> {{ __('Out') }}
                  </span>
                @elseif($status === 'low')
                  <span class="badge badge-warning badge-sm gap-1">
                    <x-icon name="o-exclamation-triangle" class="w-3 h-3" /> {{ __('Low') }}
                  </span>
                @else
                  <span class="badge badge-success badge-sm gap-1">
                    <x-icon name="o-check-circle" class="w-3 h-3" /> {{ __('OK') }}
                  </span>
                @endif
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <x-button class="btn-ghost btn-xs" icon="o-clock" wire:click="showVariantMovements({{ $variant->id }})" title="{{ __('Movements') }}" />
                  @can('inventory.adjust')
                    <x-button class="btn-ghost btn-xs text-primary" icon="o-adjustments-horizontal"
                      wire:click="openAdjustment({{ $variant->id }})" title="{{ __('Adjust') }}" />
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-12 text-base-content/50">
                <x-icon name="o-archive-box" class="w-12 h-12 mx-auto mb-3 opacity-20" />
                <p>{{ __('No inventory items found.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $this->variants->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Quick Adjustment Modal --}}
  <x-modal wire:model="showAdjustment" :title="__('Quick Stock Adjustment')" :subtitle="__('Record a stock increase or decrease.')" class="backdrop-blur">
    @if($adjustVariantId)
      @php
        $av = \App\Models\ProductVariant::with('product.baseUnit')->find($adjustVariantId);
      @endphp
      @if($av)
        <div class="mb-4 p-3 bg-base-200/50 rounded-lg">
          <span class="font-semibold">{{ $av->product->name }}</span> — {{ $av->name }}
          <span class="text-xs text-base-content/50 block">{{ __('Base unit') }}: {{ $av->product->baseUnit?->short_name ?? '—' }}</span>
        </div>
      @endif
    @endif

    <div class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <x-select :label="__('Adjustment Type')" wire:model="adjustType" :options="[
          ['id' => 'addition', 'name' => __('Addition (+)')],
          ['id' => 'subtraction', 'name' => __('Subtraction (-)')],
        ]" required />
        <x-input :label="__('Quantity (base unit)')" wire:model="adjustQty" type="number" step="0.0001" min="0.0001" required icon="o-scale" />
      </div>
      <x-select :label="__('Reason')" wire:model="adjustReason" :options="[
        ['id' => 'Damage', 'name' => __('Damage')],
        ['id' => 'Expired', 'name' => __('Expired')],
        ['id' => 'Stock Count Mismatch', 'name' => __('Stock Count Mismatch')],
        ['id' => 'Opening Stock', 'name' => __('Opening Stock')],
        ['id' => 'Sample/Giveaway', 'name' => __('Sample / Giveaway')],
        ['id' => 'Other', 'name' => __('Other')],
      ]" required />
      <x-textarea :label="__('Note')" wire:model="adjustNote" rows="2" />
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showAdjustment', false)" icon="o-x-mark">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" wire:click="saveAdjustment" spinner="saveAdjustment" icon="o-check">{{ __('Save Adjustment') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Movement Detail Modal --}}
  <x-modal wire:model="showMovements" :title="__('Recent Stock Movements')" class="max-w-3xl backdrop-blur">
    @if($detailVariantId)
      <div class="overflow-x-auto">
        <table class="table table-sm w-full">
          <thead>
            <tr>
              <th>{{ __('Date') }}</th>
              <th>{{ __('Type') }}</th>
              <th>{{ __('Dir') }}</th>
              <th class="text-right">{{ __('Qty') }}</th>
              <th>{{ __('Note') }}</th>
              <th>{{ __('By') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($this->recentMovements as $mv)
              <tr class="hover:bg-base-200/30">
                <td class="text-xs">{{ $mv->created_at->format('d M Y H:i') }}</td>
                <td><span class="badge {{ $mv->type_badge_class }} badge-xs">{{ $mv->type_label }}</span></td>
                <td>
                  <span class="{{ $mv->direction === 'in' ? 'text-success' : 'text-error' }}">
                    <x-icon :name="$mv->direction_icon" class="w-4 h-4 inline" />
                    {{ strtoupper($mv->direction) }}
                  </span>
                </td>
                <td class="text-right font-mono">{{ number_format($mv->quantity, 2) }}</td>
                <td class="text-xs max-w-[200px] truncate">{{ $mv->note ?? '—' }}</td>
                <td class="text-xs">{{ $mv->creator?->name ?? '—' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center py-6 text-base-content/40">{{ __('No movements yet.') }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showMovements', false)" icon="o-x-mark">{{ __('Close') }}</x-button>
      @can('inventory.movements')
        <x-button class="btn-outline btn-sm" icon="o-arrow-top-right-on-square" link="/app/stock-movements" wire:navigate>{{ __('Full Log') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-modal>
</div>
