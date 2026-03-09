<div class="space-y-6">
  <x-header :title="__('Suppliers')" :subtitle="__('Manage your product suppliers — contacts, balances, and purchase history.')" separator>
    <x-slot:actions>
      @can('suppliers.create')
        <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('Add Supplier') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats --}}
  <div class="grid grid-cols-3 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-primary"><x-icon name="o-building-office" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Suppliers') }}</div>
        <div class="stat-value text-lg">{{ $this->stats['total'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-success"><x-icon name="o-check-circle" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Active') }}</div>
        <div class="stat-value text-lg text-success">{{ $this->stats['active'] }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-figure text-error"><x-icon name="o-banknotes" class="w-5 h-5" /></div>
        <div class="stat-title text-xs">{{ __('Total Due') }}</div>
        <div class="stat-value text-lg text-error">৳{{ number_format($this->stats['total_due'], 0) }}</div>
      </div>
    </div>
  </div>

  <x-card>
    <div class="grid sm:grid-cols-3 gap-3 mb-4">
      <div class="sm:col-span-2">
        <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Name, company, phone...')" clearable />
      </div>
      <x-select wire:model.live="statusFilter" :options="[
        ['id' => '', 'name' => __('All Status')],
        ['id' => 'active', 'name' => __('Active')],
        ['id' => 'inactive', 'name' => __('Inactive')],
      ]" />
    </div>

    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th>{{ __('Name') }}</th>
            <th>{{ __('Contact') }}</th>
            <th class="text-right">{{ __('Purchases') }}</th>
            <th class="text-right">{{ __('Total Due') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            <th class="text-right">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($this->suppliers as $supplier)
            <tr class="hover:bg-base-200/30 transition-colors group">
              <td>
                <div class="font-semibold">{{ $supplier->name }}</div>
                @if($supplier->company_name)
                  <div class="text-xs text-base-content/50">{{ $supplier->company_name }}</div>
                @endif
              </td>
              <td class="text-sm">
                @if($supplier->phone)
                  <div class="flex items-center gap-1 text-base-content/70"><x-icon name="o-phone" class="w-3 h-3" /> {{ $supplier->phone }}</div>
                @endif
                @if($supplier->email)
                  <div class="flex items-center gap-1 text-base-content/50 text-xs"><x-icon name="o-envelope" class="w-3 h-3" /> {{ $supplier->email }}</div>
                @endif
              </td>
              <td class="text-right font-mono">{{ $supplier->purchases_count }}</td>
              <td class="text-right font-mono {{ $supplier->total_due > 0 ? 'text-error font-semibold' : 'text-base-content/50' }}">
                ৳{{ number_format($supplier->total_due, 0) }}
              </td>
              <td class="text-center">
                <input type="checkbox" class="toggle toggle-sm toggle-success" @checked($supplier->is_active)
                  wire:click="toggleActive({{ $supplier->id }})" @cannot('suppliers.edit') disabled @endcannot />
              </td>
              <td class="text-right">
                <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                  <x-button class="btn-ghost btn-xs" icon="o-eye" wire:click="showSupplierDetail({{ $supplier->id }})" title="{{ __('View') }}" />
                  @can('suppliers.edit')
                    <x-button class="btn-ghost btn-xs" icon="o-pencil-square" wire:click="edit({{ $supplier->id }})" title="{{ __('Edit') }}" />
                  @endcan
                  @can('suppliers.delete')
                    <x-button class="btn-ghost btn-xs text-error" icon="o-trash" wire:click="confirmDelete({{ $supplier->id }})" title="{{ __('Delete') }}" />
                  @endcan
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-12 text-base-content/50">
                <x-icon name="o-building-office" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                <p>{{ __('No suppliers found.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $this->suppliers->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Create/Edit Modal --}}
  <x-modal wire:model="showForm" :title="$editingId ? __('Edit Supplier') : __('New Supplier')" class="backdrop-blur">
    <div class="space-y-4">
      <x-input :label="__('Name')" wire:model="name" required icon="o-user" />
      <x-input :label="__('Company Name')" wire:model="company_name" icon="o-building-office" />
      <div class="grid grid-cols-2 gap-4">
        <x-input :label="__('Phone')" wire:model="phone" icon="o-phone" />
        <x-input :label="__('Email')" wire:model="email" type="email" icon="o-envelope" />
      </div>
      <x-textarea :label="__('Address')" wire:model="address" rows="2" />
      <x-input :label="__('Opening Balance (৳)')" wire:model="opening_balance" type="number" step="0.01" min="0" icon="o-banknotes" />
      <x-textarea :label="__('Note')" wire:model="note" rows="2" />
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showForm', false)" icon="o-x-mark">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" wire:click="save" spinner="save" icon="o-check">{{ __('Save') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Detail Modal --}}
  <x-modal wire:model="showDetail" :title="__('Supplier Details')" class="max-w-2xl backdrop-blur">
    @if($detailSupplier = $this->detailSupplier)
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-base-content/50">{{ __('Name') }}</p>
            <p class="font-semibold">{{ $detailSupplier->name }}</p>
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Company') }}</p>
            <p>{{ $detailSupplier->company_name ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Phone') }}</p>
            <p>{{ $detailSupplier->phone ?? '—' }}</p>
          </div>
          <div>
            <p class="text-xs text-base-content/50">{{ __('Email') }}</p>
            <p>{{ $detailSupplier->email ?? '—' }}</p>
          </div>
        </div>

        <div class="divider text-xs">{{ __('Financial Summary') }}</div>

        <div class="grid grid-cols-3 gap-3">
          <div class="bg-base-200/50 rounded-lg p-3 text-center">
            <p class="text-xs text-base-content/50">{{ __('Total Purchase') }}</p>
            <p class="font-bold text-lg">৳{{ number_format($detailSupplier->total_purchase, 0) }}</p>
          </div>
          <div class="bg-base-200/50 rounded-lg p-3 text-center">
            <p class="text-xs text-base-content/50">{{ __('Total Paid') }}</p>
            <p class="font-bold text-lg text-success">৳{{ number_format($detailSupplier->total_paid, 0) }}</p>
          </div>
          <div class="bg-base-200/50 rounded-lg p-3 text-center">
            <p class="text-xs text-base-content/50">{{ __('Total Due') }}</p>
            <p class="font-bold text-lg text-error">৳{{ number_format($detailSupplier->total_due, 0) }}</p>
          </div>
        </div>

        @if($detailSupplier->address)
          <div>
            <p class="text-xs text-base-content/50">{{ __('Address') }}</p>
            <p class="text-sm">{{ $detailSupplier->address }}</p>
          </div>
        @endif

        @if($detailSupplier->purchases->count())
          <div class="divider text-xs">{{ __('Recent Purchases') }}</div>
          <div class="overflow-x-auto">
            <table class="table table-sm">
              <thead><tr><th>{{ __('Invoice') }}</th><th>{{ __('Date') }}</th><th class="text-right">{{ __('Total') }}</th><th class="text-right">{{ __('Due') }}</th></tr></thead>
              <tbody>
                @foreach($detailSupplier->purchases as $p)
                  <tr class="hover:bg-base-200/30">
                    <td><code class="text-xs">{{ $p->invoice_number }}</code></td>
                    <td class="text-sm">{{ $p->purchase_date->format('d M Y') }}</td>
                    <td class="text-right font-mono">৳{{ number_format($p->grand_total, 0) }}</td>
                    <td class="text-right font-mono {{ $p->due_amount > 0 ? 'text-error' : 'text-success' }}">৳{{ number_format($p->due_amount, 0) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    @endif
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showDetail', false)" icon="o-x-mark">{{ __('Close') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Delete Confirm --}}
  <x-modal wire:model="showDelete" :title="__('Delete Supplier')" class="backdrop-blur">
    <p>{{ __('Are you sure? This action cannot be undone.') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('showDelete', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" spinner="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
