<div class="space-y-6">
  <x-header :title="__('Brands')" :subtitle="__('Manage product brands and manufacturers.')" separator>
    <x-slot:actions>
      @can('brands.create')
        <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('New Brand') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  <x-card>
    <div class="grid md:grid-cols-3 gap-3 mb-4">
      <div class="md:col-span-2">
        <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Search brands...')" clearable />
      </div>
      <div class="flex items-end justify-end">
        <x-select wire:model.live="perPage" :options="[['id' => 15, 'name' => '15'], ['id' => 30, 'name' => '30'], ['id' => 50, 'name' => '50']]" :label="__('Per page')" />
      </div>
    </div>

    {{-- Brands Grid --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      @forelse($this->brands as $brand)
        <div class="border border-base-300 rounded-xl p-4 hover:border-primary/30 hover:shadow-lg transition-all duration-200 group relative
          {{ ! $brand->is_active ? 'opacity-60' : '' }}">

          {{-- Status Badge --}}
          @if(! $brand->is_active)
            <div class="absolute top-2 right-2">
              <span class="badge badge-error badge-sm gap-1">
                <x-icon name="o-eye-slash" class="w-3 h-3" />
                {{ __('Inactive') }}
              </span>
            </div>
          @endif

          <div class="flex items-start gap-4">
            {{-- Logo --}}
            <div class="w-14 h-14 rounded-xl bg-base-200 flex items-center justify-center overflow-hidden flex-shrink-0 border border-base-300">
              @if($brand->logo_url)
                <img src="{{ $brand->logo_url }}" alt="{{ $brand->name }}" class="w-full h-full object-cover" />
              @else
                <span class="text-xl font-bold text-primary/60">{{ substr($brand->name, 0, 1) }}</span>
              @endif
            </div>

            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-base-content truncate">{{ $brand->name }}</h3>
              <p class="text-xs text-base-content/50 mt-0.5">
                {{ $brand->products_count }} {{ __('products') }}
              </p>
              @if($brand->website)
                <a href="{{ $brand->website }}" target="_blank" class="text-xs text-primary hover:underline truncate block mt-0.5">
                  {{ parse_url($brand->website, PHP_URL_HOST) }}
                </a>
              @endif
            </div>
          </div>

          {{-- Actions (visible on hover) --}}
          <div class="flex items-center justify-end gap-1 mt-3 pt-3 border-t border-base-300/50 opacity-0 group-hover:opacity-100 transition-opacity">
            @can('brands.edit')
              <x-button class="btn-ghost btn-xs" icon="o-pencil-square" wire:click="edit({{ $brand->id }})">{{ __('Edit') }}</x-button>
              <x-button class="btn-ghost btn-xs" wire:click="toggleActive({{ $brand->id }})">
                {{ $brand->is_active ? __('Deactivate') : __('Activate') }}
              </x-button>
            @endcan
            @can('brands.delete')
              <x-button class="btn-ghost btn-xs text-error" icon="o-trash" wire:click="confirmDelete({{ $brand->id }})" />
            @endcan
          </div>
        </div>
      @empty
        <div class="col-span-full text-center py-12 text-base-content/50">
          <x-icon name="o-building-storefront" class="w-12 h-12 mx-auto mb-3 opacity-30" />
          <p>{{ __('No brands found.') }}</p>
        </div>
      @endforelse
    </div>

    <div class="mt-6">{{ $this->brands->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Create/Edit Modal --}}
  <x-modal wire:model="showForm" :title="$isEditing ? __('Edit Brand') : __('New Brand')" :subtitle="__('Create or update a product brand.')" class="backdrop-blur">
    <div class="space-y-4">
      <div class="grid md:grid-cols-2 gap-4">
        <x-input :label="__('Brand Name')" wire:model="name" wire:change="$refresh" required icon="o-building-storefront" />
        <x-input :label="__('Slug')" wire:model="slug" icon="o-link" />
      </div>
      <div class="grid md:grid-cols-2 gap-4">
        <x-input :label="__('Website')" wire:model="website" :placeholder="__('https://...')" icon="o-globe-alt" />
        <div class="flex items-end pb-1">
          <x-toggle :label="__('Active')" wire:model="is_active" />
        </div>
      </div>
      <x-file :label="__('Logo')" wire:model="logo" accept="image/*" hint="{{ __('Max 2MB. JPG, PNG, WebP') }}" />
      @if($logo)
        <div class="mt-2">
          <img src="{{ $logo->temporaryUrl() }}" class="w-20 h-20 object-cover rounded-lg border border-base-300" />
        </div>
      @endif
      <x-textarea :label="__('Description')" wire:model="description" rows="3" />
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" icon="o-x-mark" wire:click="$set('showForm', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" icon="o-check" wire:click="save" spinner="save">{{ __('Save') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Delete Modal --}}
  <x-modal wire:model="confirmingDeleteId" :title="__('Delete Brand')" :subtitle="__('This action cannot be undone.')">
    <p>{{ __('Are you sure you want to delete this brand?') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('confirmingDeleteId', null)" icon="o-x-mark">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
