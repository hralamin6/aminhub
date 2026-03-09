<div class="space-y-6">
  <x-header :title="__('Purchase Returns')" :subtitle="__('Return items to suppliers — creates reverse stock movements.')" separator>
    <x-slot:actions>
      <x-button class="btn-ghost btn-sm" icon="o-arrow-left" link="/app/purchases" wire:navigate>{{ __('Purchases') }}</x-button>
      @can('purchase_returns.create')
        <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('New Return') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  <x-card>
    <div class="mb-4">
      <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Return #, invoice, supplier...')" clearable />
    </div>

    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Return #') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Purchase Invoice') }}</th>
            <th>{{ __('Supplier') }}</th>
            <th class="text-right">{{ __('Items') }}</th>
            <th class="text-right">{{ __('Amount') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th>{{ __('By') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->returns as $ret)
            <tr class="hover:bg-base-200/30 transition-colors">
              <td><code class="text-xs bg-base-200 px-1.5 py-0.5 rounded font-mono">{{ $ret->return_number }}</code></td>
              <td class="text-sm">{{ $ret->return_date->format('d M Y') }}</td>
              <td><code class="text-xs">{{ $ret->purchase->invoice_number }}</code></td>
              <td class="font-medium text-sm">{{ $ret->purchase->supplier->name }}</td>
              <td class="text-right font-mono">{{ $ret->items_count }}</td>
              <td class="text-right font-mono font-semibold text-error">৳{{ number_format($ret->total_amount, 2) }}</td>
              <td class="text-center"><span class="badge {{ $ret->status_badge }} badge-sm">{{ ucfirst($ret->status) }}</span></td>
              <td class="text-sm text-base-content/60">{{ $ret->creator?->name ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-12 text-base-content/50">
                <x-icon name="o-arrow-uturn-left" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No purchase returns found.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $this->returns->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Create Return Modal --}}
  <x-modal wire:model="showForm" :title="__('New Purchase Return')" class="max-w-3xl backdrop-blur">
    <div class="space-y-4">
      <x-select :label="__('Purchase Invoice')" wire:model.live="selectedPurchaseId" :options="$this->purchaseOptions"
        icon="o-clipboard-document-list" required placeholder="{{ __('Select invoice') }}" placeholder-value="" />

      <div class="grid grid-cols-2 gap-4">
        <x-input :label="__('Return Date')" wire:model="return_date" type="date" required />
        <x-textarea :label="__('Reason')" wire:model="reason" rows="1" />
      </div>

      @if($selectedPurchaseId && count($this->purchaseItems) > 0)
        <div class="divider text-xs">{{ __('Select Items to Return') }}</div>
        <div class="overflow-x-auto">
          <table class="table table-sm">
            <thead>
              <tr>
                <th class="w-8"></th>
                <th>{{ __('Product') }}</th>
                <th class="text-right">{{ __('Max Qty') }}</th>
                <th class="w-24">{{ __('Return Qty') }}</th>
                <th class="text-right">{{ __('Price') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($returnItems as $ri => $rItem)
                @php $piInfo = $this->purchaseItems[$ri] ?? null; @endphp
                @if($piInfo)
                  <tr class="{{ $rItem['selected'] ? 'bg-primary/5' : '' }}">
                    <td><input type="checkbox" wire:model.live="returnItems.{{ $ri }}.selected" class="checkbox checkbox-sm checkbox-primary" /></td>
                    <td class="text-sm">{{ $piInfo['label'] }}</td>
                    <td class="text-right font-mono text-xs">{{ number_format($piInfo['max_qty'], 2) }} {{ $piInfo['unit_name'] }}</td>
                    <td>
                      <input type="number" wire:model.live="returnItems.{{ $ri }}.quantity" class="input input-sm input-bordered w-full text-right"
                        step="0.01" min="0" max="{{ $piInfo['max_qty'] }}" @disabled(!$rItem['selected']) />
                    </td>
                    <td class="text-right font-mono text-sm">৳{{ number_format($rItem['unit_price'], 2) }}</td>
                    <td class="text-right font-mono font-semibold text-sm">
                      ৳{{ number_format((float)$rItem['quantity'] * (float)$rItem['unit_price'], 2) }}
                    </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showForm', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="save" spinner="save" icon="o-arrow-uturn-left">{{ __('Create Return') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
