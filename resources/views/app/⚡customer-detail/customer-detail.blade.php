@php $customer = $this->customer; @endphp

<div class="space-y-6">
  <x-header :title="$customer->name" :subtitle="__('Customer details, sales history, and dues')" separator>
    <x-slot:actions>
      <x-button class="btn-ghost btn-sm" icon="o-arrow-left" link="/app/customers" wire:navigate>{{ __('Back') }}</x-button>
      @can('customers.edit')
        <x-button class="btn-primary btn-sm" icon="o-pencil-square" link="/app/customers" wire:navigate>{{ __('Edit') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats Row --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat px-4 py-3">
        <div class="stat-figure text-primary"><x-icon name="o-shopping-bag" class="w-6 h-6" /></div>
        <div class="stat-title text-xs font-semibold">{{ __('Total Purchases') }}</div>
        <div class="stat-value text-xl">{{ $customer->sales->count() }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat px-4 py-3">
        <div class="stat-figure text-info"><x-icon name="o-banknotes" class="w-6 h-6" /></div>
        <div class="stat-title text-xs font-semibold">{{ __('Total Spend') }}</div>
        <div class="stat-value text-xl font-mono">৳{{ number_format($customer->detail?->total_purchase ?? 0, 0) }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat px-4 py-3">
        <div class="stat-figure text-success"><x-icon name="o-check-circle" class="w-6 h-6" /></div>
        <div class="stat-title text-xs font-semibold">{{ __('Total Paid') }}</div>
        <div class="stat-value text-xl font-mono text-success">
           ৳{{ number_format(($customer->detail?->total_purchase ?? 0) - ($customer->detail?->total_due ?? 0), 0) }}
        </div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat px-4 py-3">
        <div class="stat-figure text-error"><x-icon name="o-exclamation-circle" class="w-6 h-6" /></div>
        <div class="stat-title text-xs font-semibold">{{ __('Current Due') }}</div>
        <div class="stat-value text-xl font-mono text-error">৳{{ number_format($customer->detail?->total_due ?? 0, 0) }}</div>
      </div>
    </div>
  </div>

  {{-- Details & Recent Sales --}}
  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Left: Details --}}
    <div class="space-y-6">
      <x-card :title="__('Customer Profile')">
        <div class="space-y-4 text-sm">
          <div class="flex items-center gap-3 border-b border-base-200 pb-2">
            <x-icon name="o-phone" class="w-5 h-5 text-base-content/50" />
            <div>
              <p class="text-xs text-base-content/50">{{ __('Phone') }}</p>
              <p class="font-medium">{{ $customer->detail?->phone ?? '—' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3 border-b border-base-200 pb-2">
            <x-icon name="o-envelope" class="w-5 h-5 text-base-content/50" />
            <div>
              <p class="text-xs text-base-content/50">{{ __('Email') }}</p>
              <p class="font-medium">{{ str_contains($customer->email, '@walkin.local') ? '—' : $customer->email }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3 border-b border-base-200 pb-2">
            <x-icon name="o-map-pin" class="w-5 h-5 text-base-content/50" />
            <div>
              <p class="text-xs text-base-content/50">{{ __('Address') }}</p>
              <p class="font-medium">{{ $customer->detail?->address ?? '—' }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3 border-b border-base-200 pb-2">
            <x-icon name="o-user-circle" class="w-5 h-5 text-base-content/50" />
            <div>
              <p class="text-xs text-base-content/50">{{ __('Registration Type') }}</p>
              <p class="font-medium">
                @if(str_contains($customer->email, '@walkin.local'))
                   <span class="badge badge-sm">{{ __('Walk-in') }}</span>
                @else
                   <span class="badge badge-sm badge-info">{{ __('Registered') }}</span>
                @endif
              </p>
            </div>
          </div>
          <div class="flex items-center gap-3 border-b border-base-200 pb-2">
            <x-icon name="o-calendar" class="w-5 h-5 text-base-content/50" />
            <div>
              <p class="text-xs text-base-content/50">{{ __('Joined') }}</p>
              <p class="font-medium">{{ $customer->created_at->format('M d, Y') }}</p>
            </div>
          </div>
        </div>
      </x-card>

      @if($customer->detail?->bio)
        <x-card :title="__('Notes')">
          <p class="text-sm text-base-content/70 whitespace-pre-line">{{ $customer->detail->bio }}</p>
        </x-card>
      @endif
    </div>

    {{-- Right: Recent Sales --}}
    <div class="lg:col-span-2">
      <x-card :title="__('Recent Sales')">
        <div class="overflow-x-auto">
          <table class="table w-full">
            <thead>
              <tr class="bg-base-200/50">
                <th>{{ __('Invoice') }}</th>
                <th>{{ __('Date') }}</th>
                <th class="text-right">{{ __('Total') }}</th>
                <th class="text-right">{{ __('Paid') }}</th>
                <th class="text-center">{{ __('Status') }}</th>
                <th class="w-10"></th>
              </tr>
            </thead>
            <tbody>
              @forelse($this->latestSales as $sale)
                <tr class="hover:bg-base-200/30 transition-colors group {{ $sale->status === 'void' ? 'opacity-50' : '' }}">
                  <td>
                    <code class="text-xs bg-base-200 px-1.5 py-0.5 rounded font-mono">{{ $sale->invoice_number }}</code>
                    <div class="text-[10px] text-base-content/50 mt-1 uppercase">{{ $sale->sale_type }}</div>
                  </td>
                  <td class="text-sm">{{ $sale->created_at->format('d M y, H:i') }}</td>
                  <td class="text-right font-mono font-bold">৳{{ number_format($sale->grand_total, 0) }}</td>
                  <td class="text-right font-mono text-success">৳{{ number_format($sale->paid_amount, 0) }}</td>
                  <td class="text-center">
                    <span class="badge {{ $sale->payment_status_badge }} badge-sm">{{ ucfirst($sale->payment_status) }}</span>
                    @if($sale->status === 'void')
                      <span class="badge badge-error badge-sm block mt-1">{{ __('Void') }}</span>
                    @endif
                  </td>
                  <td>
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity">
                      <x-button class="btn-ghost btn-xs" icon="o-eye" link="/app/sales/{{ $sale->id }}" wire:navigate />
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-8 text-base-content/50">
                    <x-icon name="o-shopping-cart" class="w-8 h-8 mx-auto mb-2 opacity-20" />
                    <p>{{ __('No recent sales.') }}</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if(count($this->latestSales) === 10)
          <div class="mt-4 text-center">
            <a href="{{ route('app.sales', ['search' => $customer->detail?->phone]) }}" class="btn btn-ghost btn-sm">{{ __('View All Sales') }}</a>
          </div>
        @endif
      </x-card>
    </div>
  </div>
</div>
