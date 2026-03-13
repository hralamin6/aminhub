<div class="space-y-6">
  <x-header :title="__('Customers')" :subtitle="__('Manage your walk-in and registered customers.')" separator>
    <x-slot:actions>
      @can('customers.create')
        <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('New Customer') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats --}}
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-primary"><x-icon name="o-users" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Customers') }}</div>
        <div class="stat-value text-lg">{{ $this->stats['total'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-error"><x-icon name="o-banknotes" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Receivables') }}</div>
        <div class="stat-value text-lg text-error">৳{{ number_format($this->stats['total_due'], 0) }}</div>
      </div>
    </div>
  </div>

  <x-card>
    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
      <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Name, phone, email...')" clearable class="flex-1" />
      <x-toggle wire:model.live="hasDueFilter" label="{{ __('Has Due Balance') }}" class="sm:my-auto" />
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Name') }}</th>
            <th>{{ __('Contact') }}</th>
            <th class="text-right">{{ __('Sales') }}</th>
            <th class="text-right">{{ __('Total Bill') }}</th>
            <th class="text-right text-error">{{ __('Due Balance') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-right">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->customers as $customer)
            <tr class="hover:bg-base-200/30 transition-colors group">
              <td>
                <div class="font-bold text-sm">{{ $customer->name }}</div>
                @if(str_contains($customer->email, '@walkin.local'))
                   <div class="text-xs text-base-content/50">{{ __('Walk-in (POS)') }}</div>
                @else
                   <div class="text-xs text-info">{{ __('Registered') }}</div>
                @endif
              </td>
              <td>
                <div class="text-sm font-medium">{{ $customer->detail?->phone ?? '—' }}</div>
                <div class="text-xs text-base-content/50">{{ str_contains($customer->email, '@walkin.local') ? '—' : $customer->email }}</div>
              </td>
              <td class="text-right font-mono">{{ $customer->sales_count }}</td>
              <td class="text-right font-mono">৳{{ number_format($customer->detail?->total_purchase ?? 0, 0) }}</td>
              <td class="text-right font-mono {{ ($customer->detail?->total_due ?? 0) > 0 ? 'text-error font-bold' : 'text-success' }}">
                ৳{{ number_format($customer->detail?->total_due ?? 0, 0) }}
              </td>
              <td class="text-center">
                <span class="badge {{ $customer->detail?->is_active ? 'badge-success' : 'badge-ghost' }} badge-sm">
                  {{ $customer->detail?->is_active ? __('Active') : __('Inactive') }}
                </span>
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <x-button class="btn-ghost btn-xs" icon="o-eye" link="/app/customers/{{ $customer->id }}" wire:navigate title="{{ __('View') }}" />
                  @can('customers.edit')
                    <x-button class="btn-ghost btn-xs" icon="o-pencil-square" wire:click="edit({{ $customer->id }})" title="{{ __('Edit') }}" />
                  @endcan
                  @can('customers.delete')
                    <x-button class="btn-ghost btn-xs text-error" icon="o-trash" wire:click="$set('deletingId', {{ $customer->id }}); $set('showDelete', true);" title="{{ __('Delete') }}" />
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-12 text-base-content/50">
                <x-icon name="o-users" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No customers found.') }}</p>
                @can('customers.create')
                  <x-button class="btn-outline btn-sm mt-3" wire:click="create">{{ __('Add Customer') }}</x-button>
                @endcan
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $this->customers->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Form Modal --}}
  <x-modal wire:model="showForm" :title="$editingId ? __('Edit Customer') : __('New Customer')" class="backdrop-blur">
    <div class="space-y-4">
      <x-input :label="__('Name')" wire:model="name" required icon="o-user" />
      <div class="grid grid-cols-2 gap-4">
        <x-input :label="__('Phone')" wire:model="phone" icon="o-phone" required />
        <x-input :label="__('Email (Optional)')" wire:model="email" type="email" icon="o-envelope" />
      </div>
      <x-textarea :label="__('Address')" wire:model="address" rows="2" />
      @if(!$editingId)
        <x-input :label="__('Opening Balance (Due)')" wire:model="opening_balance" type="number" step="0.01" min="0" prefix="৳" />
      @endif
      <x-textarea :label="__('Note / Bio')" wire:model="note" rows="2" />
    </div>

    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showForm', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" wire:click="save" spinner="save" icon="o-check">{{ __('Save') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Delete Confirm --}}
  <x-modal wire:model="showDelete" :title="__('Delete Customer')" class="backdrop-blur">
    <p>{{ __('Are you sure? This cannot be undone.') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showDelete', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" spinner="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
