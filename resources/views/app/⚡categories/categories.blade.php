<div class="space-y-6">
  <x-header :title="__('Categories')" :subtitle="__('Manage product categories and sub-categories.')" separator>
    <x-slot:actions>
      @can('categories.create')
        <x-button class="btn-primary" icon="o-plus" wire:click="create">{{ __('New Category') }}</x-button>
      @endcan
    </x-slot:actions>
  </x-header>

  <div class="grid lg:grid-cols-3 gap-6">
    {{-- Category Tree (Left) --}}
    <div class="lg:col-span-2 space-y-3">
      <x-card>
        <div class="mb-4">
          <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Search categories...')" clearable />
        </div>

        @forelse($this->categories as $category)
          <div class="border border-base-300 rounded-xl mb-3 overflow-hidden">
            {{-- Parent Category --}}
            <div class="flex items-center justify-between px-4 py-3 bg-base-200/50 hover:bg-base-200 transition-colors">
              <div class="flex items-center gap-3">
                @if($category->icon)
                  <x-icon :name="$category->icon" class="w-5 h-5 text-primary" />
                @else
                  <x-icon name="o-folder" class="w-5 h-5 text-primary" />
                @endif
                <div>
                  <h4 class="font-semibold text-base-content">{{ $category->name }}</h4>
                  <span class="text-xs text-base-content/50">
                    {{ $category->products_count }} {{ __('products') }}
                    @if($category->children->count())
                      · {{ $category->children->count() }} {{ __('sub-categories') }}
                    @endif
                  </span>
                </div>
                @if(! $category->is_active)
                  <span class="badge badge-error badge-sm">{{ __('Inactive') }}</span>
                @endif
              </div>
              <div class="flex items-center gap-1">
                @can('categories.create')
                  <x-button class="btn-ghost btn-xs" icon="o-plus" wire:click="create({{ $category->id }})" title="{{ __('Add Sub-category') }}" />
                @endcan
                @can('categories.edit')
                  <x-button class="btn-ghost btn-xs" icon="o-pencil-square" wire:click="edit({{ $category->id }})" />
                @endcan
                @can('categories.delete')
                  <x-button class="btn-ghost btn-xs text-error" icon="o-trash" wire:click="confirmDelete({{ $category->id }})" />
                @endcan
              </div>
            </div>

            {{-- Children --}}
            @if($category->children->count())
              <div class="divide-y divide-base-300">
                @foreach($category->children as $child)
                  <div class="flex items-center justify-between px-4 py-2.5 pl-10 hover:bg-base-100/50 transition-colors">
                    <div class="flex items-center gap-2">
                      <x-icon name="o-chevron-right" class="w-3 h-3 text-base-content/30" />
                      <span class="text-sm font-medium">{{ $child->name }}</span>
                      <span class="text-xs text-base-content/40">({{ $child->products->count() }})</span>
                      @if(! $child->is_active)
                        <span class="badge badge-error badge-xs">{{ __('Inactive') }}</span>
                      @endif
                    </div>
                    <div class="flex items-center gap-1">
                      @can('categories.edit')
                        <x-button class="btn-ghost btn-xs" icon="o-pencil-square" wire:click="edit({{ $child->id }})" />
                      @endcan
                      @can('categories.delete')
                        <x-button class="btn-ghost btn-xs text-error" icon="o-trash" wire:click="confirmDelete({{ $child->id }})" />
                      @endcan
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        @empty
          <div class="text-center py-12 text-base-content/50">
            <x-icon name="o-folder-open" class="w-12 h-12 mx-auto mb-3 opacity-30" />
            <p>{{ __('No categories found.') }}</p>
          </div>
        @endforelse
      </x-card>
    </div>

    {{-- Stats Card (Right) --}}
    <div class="space-y-3">
      <x-card title="{{ __('Overview') }}" shadow>
        <div class="space-y-3">
          <div class="flex justify-between items-center p-3 bg-primary/5 rounded-lg">
            <span class="text-sm text-base-content/70">{{ __('Total Categories') }}</span>
            <span class="text-lg font-bold text-primary">{{ \App\Models\Category::count() }}</span>
          </div>
          <div class="flex justify-between items-center p-3 bg-success/5 rounded-lg">
            <span class="text-sm text-base-content/70">{{ __('Active') }}</span>
            <span class="text-lg font-bold text-success">{{ \App\Models\Category::active()->count() }}</span>
          </div>
          <div class="flex justify-between items-center p-3 bg-warning/5 rounded-lg">
            <span class="text-sm text-base-content/70">{{ __('Root Categories') }}</span>
            <span class="text-lg font-bold text-warning">{{ \App\Models\Category::root()->count() }}</span>
          </div>
        </div>
      </x-card>
    </div>
  </div>

  {{-- Create/Edit Modal --}}
  <x-modal wire:model="showForm" :title="$isEditing ? __('Edit Category') : __('New Category')" :subtitle="__('Create or update a product category.')" class="backdrop-blur">
    <div class="space-y-4">
      <div class="grid md:grid-cols-2 gap-4">
        <x-input :label="__('Category Name')" wire:model="name" wire:change="$refresh" required icon="o-tag" />
        <x-input :label="__('Slug')" wire:model="slug" icon="o-link" />
      </div>
      <x-select :label="__('Parent Category')" wire:model="parent_id" :options="$this->parentOptions" icon="o-folder" />
      <div class="grid md:grid-cols-2 gap-4">
        <x-input :label="__('Icon')" wire:model="icon" :placeholder="__('e.g. o-beaker')" icon="o-paint-brush" />
        <div class="flex items-end pb-1">
          <x-toggle :label="__('Active')" wire:model="is_active" />
        </div>
      </div>
      <x-textarea :label="__('Description')" wire:model="description" rows="3" />
    </div>
    <x-slot:actions>
      <x-button class="btn-ghost" icon="o-x-mark" wire:click="$set('showForm', false)">{{ __('Cancel') }}</x-button>
      <x-button class="btn-primary" icon="o-check" wire:click="save" spinner="save">{{ __('Save') }}</x-button>
    </x-slot:actions>
  </x-modal>

  {{-- Delete Modal --}}
  <x-modal wire:model="confirmingDeleteId" :title="__('Delete Category')" :subtitle="__('This action cannot be undone.')">
    <p>{{ __('Are you sure you want to delete this category?') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('confirmingDeleteId', null)" icon="o-x-mark">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
