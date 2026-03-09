<div class="space-y-6">
  <x-header :title="__('Stock Adjustments')" :subtitle="__('Record manual stock corrections — damage, expired goods, opening stock, audits.')" separator>
    <x-slot:actions>
      <x-button class="btn-ghost btn-sm" icon="o-arrow-left" link="/app/inventory" wire:navigate>{{ __('Back to Inventory') }}</x-button>
      <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('New Adjustment') }}</x-button>
    </x-slot:actions>
  </x-header>

  <x-card>
    <div class="grid sm:grid-cols-3 gap-3 mb-4">
      <div class="sm:col-span-2">
        <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Search by number, reason, product...')" clearable />
      </div>
      <x-select wire:model.live="typeFilter" :options="[
        ['id' => null, 'name' => __('All Types')],
        ['id' => 'addition', 'name' => __('Addition')],
        ['id' => 'subtraction', 'name' => __('Subtraction')],
      ]" />
    </div>

    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Adj #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Product / Variant') }}</th>
            <th class="text-center">{{ __('Type') }}</th>
            <th class="text-right">{{ __('Quantity') }}</th>
            <th>{{ __('Reason') }}</th>
            <th>{{ __('By') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->adjustments as $adj)
            <tr class="hover:bg-base-200/30 transition-colors">
              <td>
                <code class="text-xs bg-base-200 px-1.5 py-0.5 rounded font-mono">{{ $adj->adjustment_number }}</code>
              </td>
              <td class="text-sm text-base-content/70">{{ $adj->created_at->format('d M Y, H:i') }}</td>
              <td>
                <div class="font-medium text-sm">{{ $adj->variant->product->name ?? '—' }}</div>
                <div class="text-xs text-base-content/50">{{ $adj->variant->name }}</div>
              </td>
              <td class="text-center">
                <span class="badge {{ $adj->type_badge_class }} badge-sm gap-1">
                  <x-icon :name="$adj->type === 'addition' ? 'o-plus' : 'o-minus'" class="w-3 h-3" />
                  {{ $adj->type_label }}
                </span>
              </td>
              <td class="text-right font-mono font-semibold {{ $adj->type === 'addition' ? 'text-success' : 'text-error' }}">
                {{ $adj->type === 'addition' ? '+' : '-' }}{{ number_format($adj->quantity, 2) }}
                <span class="text-xs text-base-content/40">{{ $adj->variant->product->baseUnit?->short_name }}</span>
              </td>
              <td class="text-sm">
                {{ $adj->reason }}
                @if($adj->note)
                  <p class="text-xs text-base-content/40 mt-0.5 max-w-[200px] truncate" title="{{ $adj->note }}">{{ $adj->note }}</p>
                @endif
              </td>
              <td class="text-sm text-base-content/60">{{ $adj->creator?->name ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-12 text-base-content/50">
                <x-icon name="o-adjustments-horizontal" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No adjustments recorded yet.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $this->adjustments->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Create Adjustment Modal --}}
  <x-modal wire:model="showForm" :title="__('New Stock Adjustment')" :subtitle="__('Record a stock increase or decrease.')" class="backdrop-blur">
    <div class="space-y-4">
      <x-select :label="__('Product / Variant')" wire:model="variantId" :options="$this->variantOptions" icon="o-cube" required
        placeholder="{{ __('Select product variant') }}" placeholder-value="" />

      <div class="grid grid-cols-2 gap-4">
        <x-select :label="__('Adjustment Type')" wire:model="type" :options="[
          ['id' => 'addition', 'name' => __('Addition (+)')],
          ['id' => 'subtraction', 'name' => __('Subtraction (-)')],
        ]" required icon="o-arrows-right-left" />
        <x-input :label="__('Quantity (base unit)')" wire:model="quantity" type="number" step="0.0001" min="0.0001" required icon="o-scale" />
      </div>

      <x-select :label="__('Reason')" wire:model="reason" :options="[
        ['id' => 'Damage', 'name' => __('Damage')],
        ['id' => 'Expired', 'name' => __('Expired')],
        ['id' => 'Stock Count Mismatch', 'name' => __('Stock Count Mismatch')],
        ['id' => 'Opening Stock', 'name' => __('Opening Stock')],
        ['id' => 'Sample/Giveaway', 'name' => __('Sample / Giveaway')],
        ['id' => 'Other', 'name' => __('Other')],
      ]" required icon="o-document-text" />

      <x-textarea :label="__('Note (optional)')" wire:model="note" rows="2" />
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showForm', false)" icon="o-x-mark">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" wire:click="save" spinner="save" icon="o-check">{{ __('Save Adjustment') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
