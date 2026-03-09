<div class="space-y-6">
  <x-header :title="__('Purchases')" :subtitle="__('Purchase invoices — track orders, payments, and supplier dues.')" separator>
    <x-slot:actions>
      @can('purchases.create')
        <x-button class="btn-primary" icon="o-plus" link="/app/purchases/create" wire:navigate>{{ __('New Purchase') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-primary"><x-icon name="o-clipboard-document-list" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Purchases') }}</div>
        <div class="stat-value text-lg">{{ $this->stats['total'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-info"><x-icon name="o-banknotes" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Value') }}</div>
        <div class="stat-value text-lg">৳{{ number_format($this->stats['total_value'], 0) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-error"><x-icon name="o-exclamation-circle" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Due') }}</div>
        <div class="stat-value text-lg text-error">৳{{ number_format($this->stats['total_due'], 0) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-success"><x-icon name="o-calendar" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('This Month') }}</div>
        <div class="stat-value text-lg text-success">৳{{ number_format($this->stats['this_month'], 0) }}</div>
      </div>
    </div>
  </div>

  <x-card>
    {{-- Filters --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
      <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Invoice #, supplier...')" clearable />
      <x-select wire:model.live="supplierFilter" :options="$this->supplierOptions" icon="o-building-office" />
      <x-select wire:model.live="paymentFilter" :options="[
        ['id' => null, 'name' => __('All Payments')],
        ['id' => 'unpaid', 'name' => __('Unpaid')],
        ['id' => 'partial', 'name' => __('Partial')],
        ['id' => 'paid', 'name' => __('Paid')],
      ]" />
      <x-select wire:model.live="statusFilter" :options="[
        ['id' => null, 'name' => __('All Status')],
        ['id' => 'draft', 'name' => __('Draft')],
        ['id' => 'received', 'name' => __('Received')],
        ['id' => 'returned', 'name' => __('Returned')],
      ]" />
      <div class="flex gap-2">
        <x-input wire:model.live="dateFrom" type="date" class="flex-1" />
        <x-input wire:model.live="dateTo" type="date" class="flex-1" />
      </div>
    </div>

    @if($search || $supplierFilter || $paymentFilter || $statusFilter || $dateFrom || $dateTo)
      <div class="mb-3">
        <x-button class="btn-ghost btn-xs" icon="o-x-mark" wire:click="clearFilters">{{ __('Clear filters') }}</x-button>
      </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Invoice') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Supplier') }}</th>
            <th class="text-right">{{ __('Items') }}</th>
            <th class="text-right">{{ __('Total') }}</th>
            <th class="text-right">{{ __('Paid') }}</th>
            <th class="text-right">{{ __('Due') }}</th>
            <th class="text-center">{{ __('Payment') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-right">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->purchases as $purchase)
            <tr class="hover:bg-base-200/30 transition-colors group">
              <td><code class="text-xs bg-base-200 px-1.5 py-0.5 rounded font-mono">{{ $purchase->invoice_number }}</code></td>
              <td class="text-sm">{{ $purchase->purchase_date->format('d M Y') }}</td>
              <td class="font-medium text-sm">{{ $purchase->supplier->name }}</td>
              <td class="text-right font-mono text-sm">{{ $purchase->items_count }}</td>
              <td class="text-right font-mono font-semibold">৳{{ number_format($purchase->grand_total, 0) }}</td>
              <td class="text-right font-mono text-success">৳{{ number_format($purchase->paid_amount, 0) }}</td>
              <td class="text-right font-mono {{ $purchase->due_amount > 0 ? 'text-error font-semibold' : 'text-base-content/40' }}">
                ৳{{ number_format($purchase->due_amount, 0) }}
              </td>
              <td class="text-center"><span class="badge {{ $purchase->payment_status_badge }} badge-sm">{{ ucfirst($purchase->payment_status) }}</span></td>
              <td class="text-center"><span class="badge {{ $purchase->status_badge }} badge-sm">{{ ucfirst($purchase->status) }}</span></td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <x-button class="btn-ghost btn-xs" icon="o-eye" wire:click="showPurchaseDetail({{ $purchase->id }})" title="{{ __('View') }}" />
                  @if($purchase->status === 'draft')
                    @can('purchases.edit')
                      <x-button class="btn-ghost btn-xs" icon="o-pencil-square" link="/app/purchases/{{ $purchase->id }}/edit" wire:navigate title="{{ __('Edit') }}" />
                    @endcan
                  @endif
                  @if($purchase->due_amount > 0)
                    @can('purchases.payment')
                      <x-button class="btn-ghost btn-xs text-success" icon="o-banknotes" wire:click="openPayment({{ $purchase->id }})" title="{{ __('Pay') }}" />
                    @endcan
                  @endif
                  @if($purchase->status === 'draft')
                    @can('purchases.delete')
                      <x-button class="btn-ghost btn-xs text-error" icon="o-trash" wire:click="confirmDelete({{ $purchase->id }})" title="{{ __('Delete') }}" />
                    @endcan
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-12 text-base-content/50">
                <x-icon name="o-clipboard-document-list" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No purchases found.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $this->purchases->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Payment Modal --}}
  <x-modal wire:model="showPayment" :title="__('Record Payment')" class="backdrop-blur">
    @if($payPurchaseId)
      @php $payPurchase = \App\Models\Purchase::find($payPurchaseId); @endphp
      @if($payPurchase)
        <div class="mb-4 p-3 bg-base-200/50 rounded-lg text-sm">
          <span class="font-semibold">{{ $payPurchase->invoice_number }}</span> —
          {{ __('Due') }}: <span class="text-error font-bold">৳{{ number_format($payPurchase->due_amount, 2) }}</span>
        </div>
      @endif
    @endif
    <div class="space-y-4">
      <x-input :label="__('Amount (৳)')" wire:model="payAmount" type="number" step="0.01" min="0.01" required icon="o-banknotes" />
      <div class="grid grid-cols-2 gap-4">
        <x-select :label="__('Method')" wire:model="payMethod" :options="[
          ['id' => 'cash', 'name' => __('Cash')],
          ['id' => 'bank_transfer', 'name' => __('Bank Transfer')],
          ['id' => 'bkash', 'name' => __('bKash')],
          ['id' => 'check', 'name' => __('Check')],
          ['id' => 'other', 'name' => __('Other')],
        ]" required />
        <x-input :label="__('Payment Date')" wire:model="payDate" type="date" required />
      </div>
      <x-input :label="__('Reference')" wire:model="payReference" icon="o-hashtag" />
      <x-textarea :label="__('Note')" wire:model="payNote" rows="2" />
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showPayment', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-success" wire:click="savePayment" spinner="savePayment" icon="o-banknotes">{{ __('Record Payment') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Detail Modal --}}
  <x-modal wire:model="showDetail" :title="__('Purchase Details')" class="max-w-3xl backdrop-blur">
    @if($dp = $this->detailPurchase)
      <div class="space-y-4">
        <div class="grid grid-cols-3 gap-4 text-sm">
          <div><p class="text-xs text-base-content/50">{{ __('Invoice') }}</p><code>{{ $dp->invoice_number }}</code></div>
          <div><p class="text-xs text-base-content/50">{{ __('Supplier') }}</p><p class="font-medium">{{ $dp->supplier->name }}</p></div>
          <div><p class="text-xs text-base-content/50">{{ __('Date') }}</p><p>{{ $dp->purchase_date->format('d M Y') }}</p></div>
        </div>

        <div class="divider text-xs">{{ __('Items') }}</div>
        <table class="table table-sm">
          <thead><tr><th>{{ __('Product') }}</th><th class="text-right">{{ __('Qty') }}</th><th>{{ __('Unit') }}</th><th class="text-right">{{ __('Price') }}</th><th class="text-right">{{ __('Total') }}</th></tr></thead>
          <tbody>
            @foreach($dp->items as $item)
              <tr>
                <td class="text-sm">{{ $item->variant->product->name ?? '—' }} — {{ $item->variant->name }}</td>
                <td class="text-right font-mono">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-sm">{{ $item->unit->short_name ?? '—' }}</td>
                <td class="text-right font-mono">৳{{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right font-mono font-semibold">৳{{ number_format($item->subtotal, 2) }}</td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr><td colspan="4" class="text-right text-sm">{{ __('Subtotal') }}</td><td class="text-right font-mono">৳{{ number_format($dp->subtotal, 2) }}</td></tr>
            @if($dp->discount > 0)<tr><td colspan="4" class="text-right text-sm">{{ __('Discount') }}</td><td class="text-right font-mono text-error">-৳{{ number_format($dp->discount, 2) }}</td></tr>@endif
            @if($dp->tax > 0)<tr><td colspan="4" class="text-right text-sm">{{ __('Tax') }}</td><td class="text-right font-mono">৳{{ number_format($dp->tax, 2) }}</td></tr>@endif
            @if($dp->shipping_cost > 0)<tr><td colspan="4" class="text-right text-sm">{{ __('Shipping') }}</td><td class="text-right font-mono">৳{{ number_format($dp->shipping_cost, 2) }}</td></tr>@endif
            <tr class="border-t-2"><td colspan="4" class="text-right font-bold">{{ __('Grand Total') }}</td><td class="text-right font-mono font-bold text-lg">৳{{ number_format($dp->grand_total, 2) }}</td></tr>
          </tfoot>
        </table>

        @if($dp->payments->count())
          <div class="divider text-xs">{{ __('Payments') }}</div>
          <table class="table table-sm">
            <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Method') }}</th><th class="text-right">{{ __('Amount') }}</th><th>{{ __('By') }}</th></tr></thead>
            <tbody>
              @foreach($dp->payments as $pay)
                <tr>
                  <td class="text-sm">{{ $pay->payment_date->format('d M Y') }}</td>
                  <td><span class="badge badge-ghost badge-xs">{{ $pay->method_label }}</span></td>
                  <td class="text-right font-mono text-success">৳{{ number_format($pay->amount, 2) }}</td>
                  <td class="text-sm">{{ $pay->creator?->name ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showDetail', false)">{{ __('Close') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Delete Confirm --}}
  <x-modal wire:model="showDelete" :title="__('Delete Purchase')" class="backdrop-blur">
    <p>{{ __('Are you sure? This will delete the draft purchase and all its items.') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showDelete', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" spinner="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
