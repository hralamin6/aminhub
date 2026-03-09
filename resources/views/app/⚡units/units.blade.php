<div class="space-y-6">
  <x-header :title="__('Units')" :subtitle="__('Manage measurement units for products (kg, liter, piece, bag, etc.).')" separator>
    <x-slot:actions>
      @can('units.create')
        <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('New Unit') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  <x-card>
    <div class="mb-4">
      <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Search units...')" clearable class="max-w-md" />
    </div>

    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr>
            <th>{{ __('Short Name') }}</th>
            <th>{{ __('Full Name') }}</th>
            <th>{{ __('Type') }}</th>
            <th class="text-center">{{ __('Used By') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            @canany(['units.edit', 'units.delete'])
              <th class="text-right">{{ __('Actions') }}</th>
            @endcanany
          </tr>
        </thead>
        <tbody>
          @forelse($this->units as $unit)
            <tr class="hover:bg-base-200/50 transition-colors {{ ! $unit->is_active ? 'opacity-50' : '' }}">
              <td>
                <span class="badge badge-outline badge-lg font-mono font-bold">{{ $unit->short_name }}</span>
              </td>
              <td class="font-medium">{{ $unit->name }}</td>
              <td>
                @php
                  $typeColors = [
                    'weight' => 'badge-primary',
                    'volume' => 'badge-info',
                    'length' => 'badge-warning',
                    'piece' => 'badge-success',
                    'pack' => 'badge-secondary',
                  ];
                @endphp
                <span class="badge {{ $typeColors[$unit->unit_type] ?? 'badge-ghost' }} badge-sm gap-1">
                  {{ ucfirst($unit->unit_type) }}
                </span>
              </td>
              <td class="text-center">
                <span class="text-sm text-base-content/60">
                  {{ $unit->product_units_count }} {{ __('products') }}
                </span>
              </td>
              <td class="text-center">
                @can('units.edit')
                  <input type="checkbox" class="toggle toggle-success toggle-sm" {{ $unit->is_active ? 'checked' : '' }}
                    wire:click="toggleActive({{ $unit->id }})" />
                @else
                  <span class="badge {{ $unit->is_active ? 'badge-success' : 'badge-error' }} badge-sm">
                    {{ $unit->is_active ? __('Active') : __('Inactive') }}
                  </span>
                @endcan
              </td>
              @canany(['units.edit', 'units.delete'])
                <td class="text-right space-x-1">
                  @can('units.edit')
                    <x-button class="btn-ghost btn-sm" icon="o-pencil-square" wire:click="edit({{ $unit->id }})">{{ __('Edit') }}</x-button>
                  @endcan
                  @can('units.delete')
                    <x-button class="btn-ghost btn-sm text-error" icon="o-trash" wire:click="confirmDelete({{ $unit->id }})" />
                  @endcan
                </td>
              @endcanany
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-12 text-base-content/50">
                <x-icon name="o-scale" class="w-10 h-10 mx-auto mb-2 opacity-30" />
                <p>{{ __('No units found.') }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-card>

  {{-- Create/Edit Modal --}}
  <x-modal wire:model="showForm" :title="$isEditing ? __('Edit Unit') : __('New Unit')" :subtitle="__('Define a measurement unit.')" class="backdrop-blur">
    <div class="space-y-4">
      <div class="grid md:grid-cols-2 gap-4">
        <x-input :label="__('Full Name')" wire:model="name" :placeholder="__('e.g. Kilogram')" required icon="o-scale" />
        <x-input :label="__('Short Name')" wire:model="short_name" :placeholder="__('e.g. kg')" required icon="o-hashtag" />
      </div>
      <div class="grid md:grid-cols-2 gap-4">
        <x-select :label="__('Unit Type')" wire:model="unit_type" :options="$this->unitTypeOptions" required icon="o-adjustments-horizontal" />
        <div class="flex items-end pb-1">
          <x-toggle :label="__('Active')" wire:model="is_active" />
        </div>
      </div>
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" icon="o-x-mark" wire:click="$set('showForm', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" icon="o-check" wire:click="save" spinner="save">{{ __('Save') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Delete Modal --}}
  <x-modal wire:model="confirmingDeleteId" :title="__('Delete Unit')" :subtitle="__('This action cannot be undone.')">
    <p>{{ __('Are you sure you want to delete this unit?') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('confirmingDeleteId', null)" icon="o-x-mark">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
