<div class="space-y-6">
  <x-header :title="__('Providers')" :subtitle="__('Manage your suppliers / providers.')" separator>
    <x-slot:actions>
      @can('suppliers.create')
        <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('New Provider') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-primary"><x-icon name="o-building-office" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Providers') }}</div>
        <div class="stat-value text-lg">{{ $this->stats['total'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-error"><x-icon name="o-exclamation-circle" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Payables') }}</div>
        <div class="stat-value text-lg text-error">৳{{ number_format($this->stats['total_due'], 0) }}</div>
      </div>
    </div>
  </div>

  <x-card>
    {{-- Filters --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
      <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Name, phone, company...')" clearable />
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Company / Name') }}</th>
            <th>{{ __('Contact') }}</th>
            <th class="text-right">{{ __('Purchases') }}</th>
            <th class="text-right">{{ __('Opening Bal.') }}</th>
            <th class="text-right">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->providers as $provider)
            <tr class="hover:bg-base-200/30 transition-colors group">
              <td>
                <div class="font-bold text-sm">{{ $provider->detail?->occupation ?: $provider->name }}</div>
                @if($provider->detail?->occupation)<div class="text-xs text-base-content/50">{{ $provider->name }}</div>@endif
              </td>
              <td>
                <div class="text-sm font-medium">{{ $provider->detail?->phone ?? '—' }}</div>
                <div class="text-xs text-base-content/50">{{ $provider->email }}</div>
              </td>
              <td class="text-right font-mono">{{ $provider->purchases_count }}</td>
              <td class="text-right font-mono text-error">৳{{ number_format($provider->detail?->opening_balance ?? 0, 0) }}</td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <x-button class="btn-ghost btn-xs" icon="o-eye" wire:click="showProviderDetail({{ $provider->id }})" title="{{ __('View') }}" />
                  @can('suppliers.edit')
                    <x-button class="btn-ghost btn-xs" icon="o-pencil-square" wire:click="edit({{ $provider->id }})" title="{{ __('Edit') }}" />
                  @endcan
                  @can('suppliers.delete')
                    <x-button class="btn-ghost btn-xs text-error" icon="o-trash" wire:click="confirmDelete({{ $provider->id }})" title="{{ __('Delete') }}" />
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-12 text-base-content/50">
                <x-icon name="o-building-office" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No providers found.') }}</p>
                <x-button class="btn-outline btn-sm mt-3" wire:click="create">{{ __('Add Provider') }}</x-button>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $this->providers->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Provider Form Modal --}}
  <x-modal wire:model="showForm" :title="$editingId ? __('Edit Provider') : __('New Provider')" class="backdrop-blur">
    <div class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <x-input :label="__('Name')" wire:model="name" required icon="o-user" />
        <x-input :label="__('Company Name')" wire:model="company_name" icon="o-building-office" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <x-input :label="__('Phone')" wire:model="phone" icon="o-phone" />
        <x-input :label="__('Email')" wire:model="email" type="email" icon="o-envelope" />
      </div>

      <x-input :label="__('Opening Balance')" wire:model="opening_balance" type="number" step="0.01" min="0" prefix="৳" />
      <x-textarea :label="__('Address')" wire:model="address" rows="2" />
      <x-textarea :label="__('Note')" wire:model="note" rows="2" />
    </div>

    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showForm', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" wire:click="save" spinner="save" icon="o-check">{{ __('Save') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Detail Modal --}}
  <x-modal wire:model="showDetail" :title="__('Provider Details')" class="max-w-2xl backdrop-blur">
    @if($dp = $this->detailProvider)
      <div class="grid sm:grid-cols-2 gap-6 mb-6">
        <div>
          <h3 class="font-bold text-lg">{{ $dp->detail?->occupation ?: $dp->name }}</h3>
          @if($dp->detail?->occupation)<p class="text-base-content/60">{{ $dp->name }}</p>@endif

          <div class="mt-4 space-y-2 text-sm">
            @if($dp->detail?->phone)<div class="flex items-center gap-2"><x-icon name="o-phone" class="w-4 h-4 text-base-content/50" /> {{ $dp->detail->phone }}</div>@endif
            @if($dp->email)<div class="flex items-center gap-2"><x-icon name="o-envelope" class="w-4 h-4 text-base-content/50" /> {{ $dp->email }}</div>@endif
            @if($dp->detail?->address)<div class="flex items-center gap-2"><x-icon name="o-map-pin" class="w-4 h-4 text-base-content/50" /> {{ $dp->detail->address }}</div>@endif
          </div>
        </div>
        <div class="bg-base-200/50 p-4 rounded-lg space-y-3">
          <div class="flex justify-between text-sm">
            <span class="text-base-content/60">{{ __('Total Purchases') }}</span>
            <span class="font-bold">{{ $dp->purchases_count }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-base-content/60">{{ __('Opening Balance') }}</span>
            <span class="font-mono text-error">৳{{ number_format($dp->detail?->opening_balance ?? 0, 2) }}</span>
          </div>
        </div>
      </div>

      @if($dp->detail?->bio)
        <div class="mb-6 p-3 bg-base-200/30 rounded text-sm text-base-content/80 border-l-2 border-primary">
          {{ $dp->detail->bio }}
        </div>
      @endif

      @if($dp->purchases->count())
        <div class="divider text-xs">{{ __('Recent Purchases') }}</div>
        <table class="table table-sm">
          <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th class="text-right">{{ __('Amount') }}</th><th>{{ __('Status') }}</th></tr></thead>
          <tbody>
            @foreach($dp->purchases as $pur)
              <tr>
                <td><code class="text-xs">{{ $pur->invoice_number }}</code></td>
                <td class="text-sm border-0">{{ $pur->purchase_date->format('d/m/y') }}</td>
                <td class="text-right font-mono">৳{{ number_format($pur->grand_total, 2) }}</td>
                <td><span class="badge {{ $pur->status_badge }} badge-xs">{{ ucfirst($pur->status) }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showDetail', false)">{{ __('Close') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Delete Confirm --}}
  <x-modal wire:model="showDelete" :title="__('Delete Provider')" class="backdrop-blur">
    <p>{{ __('Are you sure? This cannot be undone.') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showDelete', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" spinner="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
