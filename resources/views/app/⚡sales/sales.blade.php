<div class="space-y-6">
  <x-header :title="__('Sales History')" :subtitle="__('All sales — POS and online orders.')" separator>
    <x-slot:actions>
      <x-button class="btn-outline" icon="o-document-arrow-down" wire:click="downloadReport" spinner="downloadReport">{{ __('PDF Report') }}</x-button>
      @can('pos.access')
        <x-button class="btn-primary" icon="o-computer-desktop" link="/app/pos" wire:navigate>{{ __('Open POS') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-primary"><x-icon name="o-shopping-bag" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __("Today's Sales") }}</div>
        <div class="stat-value text-lg">{{ $this->stats['today_sales'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-success"><x-icon name="o-banknotes" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __("Today's Revenue") }}</div>
        <div class="stat-value text-lg text-success">৳{{ number_format($this->stats['today_revenue'], 0) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-error"><x-icon name="o-exclamation-circle" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __("Today's Due") }}</div>
        <div class="stat-value text-lg text-error">৳{{ number_format($this->stats['today_due'], 0) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-info"><x-icon name="o-calendar" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('This Month') }}</div>
        <div class="stat-value text-lg text-info">৳{{ number_format($this->stats['month_revenue'], 0) }}</div>
      </div>
    </div>
  </div>

  <x-card>
    {{-- Filters --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-6 gap-3 mb-4">
      <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Invoice, customer...')" clearable class="lg:col-span-2" />
      <x-select wire:model.live="paymentFilter" :options="[
        ['id' => null, 'name' => __('All Payments')],
        ['id' => 'paid', 'name' => __('Paid')],
        ['id' => 'partial', 'name' => __('Partial')],
        ['id' => 'unpaid', 'name' => __('Unpaid')],
      ]" />
      <x-select wire:model.live="statusFilter" :options="[
        ['id' => null, 'name' => __('All Status')],
        ['id' => 'completed', 'name' => __('Completed')],
        ['id' => 'draft', 'name' => __('Draft')],
        ['id' => 'void', 'name' => __('Void')],
      ]" />
      <x-input wire:model.live="dateFrom" type="date" />
      <x-input wire:model.live="dateTo" type="date" />
    </div>

    @if($search || $paymentFilter || $statusFilter || $typeFilter || $methodFilter || $dateFrom || $dateTo)
      <div class="mb-3">
        <x-button class="btn-ghost btn-xs" icon="o-x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</x-button>
      </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto">
      <table class="table table-sm table-zebra w-full whitespace-nowrap">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Invoice') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Customer') }}</th>
            <th class="text-center">{{ __('Type') }}</th>
            <th class="text-right">{{ __('Items') }}</th>
            <th class="text-right">{{ __('Total') }}</th>
            <th class="text-right">{{ __('Paid') }}</th>
            <th class="text-center">{{ __('Payment') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th>{{ __('By') }}</th>
            <th class="text-right">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->sales as $sale)
            <tr class="hover:bg-base-200/30 transition-colors group {{ $sale->status === 'void' ? 'opacity-50' : '' }}">
              <td><code class="text-xs bg-base-200 px-1.5 py-0.5 rounded font-mono">{{ $sale->invoice_number }}</code></td>
              <td class="text-sm">{{ $sale->created_at->format('d M Y H:i') }}</td>
              <td class="text-sm">{{ $sale->customer_display }}</td>
              <td class="text-center">
                <span class="badge badge-xs {{ $sale->sale_type === 'pos' ? 'badge-info' : 'badge-accent' }}">
                  {{ strtoupper($sale->sale_type) }}
                </span>
              </td>
              <td class="text-right font-mono text-sm">{{ $sale->items_count }}</td>
              <td class="text-right font-mono font-semibold">৳{{ number_format($sale->grand_total, 0) }}</td>
              <td class="text-right font-mono text-success">৳{{ number_format($sale->paid_amount, 0) }}</td>
              <td class="text-center"><span class="badge {{ $sale->payment_status_badge }} badge-sm">{{ ucfirst($sale->payment_status) }}</span></td>
              <td class="text-center"><span class="badge {{ $sale->status_badge }} badge-sm">{{ ucfirst($sale->status) }}</span></td>
              <td class="text-sm text-base-content/60">{{ $sale->seller->name ?? '—' }}</td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1">
                  <x-button class="btn-ghost btn-xs text-primary" icon="o-document-arrow-down" wire:click="downloadReceipt({{ $sale->id }})" spinner="downloadReceipt({{ $sale->id }})" title="{{ __('Receipt PDF') }}" />
                  <x-button class="btn-ghost btn-xs" icon="o-eye" link="/app/sales/{{ $sale->id }}" wire:navigate title="{{ __('View') }}" />
                  @if($sale->status === 'completed')
                    @can('sales.void')
                      <x-button class="btn-ghost btn-xs text-error" icon="o-x-circle" wire:click="confirmVoid({{ $sale->id }})" title="{{ __('Void') }}" />
                    @endcan
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="11" class="text-center py-12 text-base-content/50">
                <x-icon name="o-shopping-bag" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No sales found.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $this->sales->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Void Confirm --}}
  <x-modal wire:model="showVoid" :title="__('Void Sale')" class="backdrop-blur">
    <p class="text-sm text-base-content/70 mb-3">{{ __('This will reverse stock movements and mark the sale as void. This cannot be undone.') }}</p>
    <x-textarea :label="__('Reason')" wire:model="voidReason" required rows="3" placeholder="{{ __('Why are you voiding this sale?') }}" />
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showVoid', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="voidSale" spinner="voidSale" icon="o-x-circle">{{ __('Void Sale') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
