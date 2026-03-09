<div class="space-y-6">
  <x-header :title="__('Products')" :subtitle="__('Manage your product catalog — search, filter, and organize.')" separator>
    <x-slot:actions>
      @can('products.create')
        <x-button label="{{ __('Add Product') }}" class="btn-primary" icon="o-plus" link="/app/products/create" wire:navigate />
      @endcan
    </x-slot:actions>
  </x-header>

  {{-- Stats Row --}}
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-title text-xs">{{ __('Total') }}</div>
        <div class="stat-value text-xl text-primary">{{ \App\Models\Product::count() }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-title text-xs">{{ __('Active') }}</div>
        <div class="stat-value text-xl text-success">{{ \App\Models\Product::active()->count() }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-title text-xs">{{ __('Featured') }}</div>
        <div class="stat-value text-xl text-warning">{{ \App\Models\Product::featured()->count() }}</div>
      </div>
    </div>
    <div class="stats shadow bg-base-100 border border-base-300">
      <div class="stat py-3 px-4">
        <div class="stat-title text-xs">{{ __('Ecommerce') }}</div>
        <div class="stat-value text-xl text-info">{{ \App\Models\Product::ecommerce()->count() }}</div>
      </div>
    </div>
  </div>

  <x-card>
    {{-- Filters --}}
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
      <div class="lg:col-span-2">
        <x-input wire:model.live.debounce.400ms="search" icon="o-magnifying-glass" :placeholder="__('Name, SKU, barcode...')" clearable />
      </div>
      <x-select wire:model.live="categoryFilter" :options="$this->categoryOptions" icon="o-tag" />
      <x-select wire:model.live="brandFilter" :options="$this->brandOptions" icon="o-building-storefront" />
      <div class="flex gap-2">
        <x-select wire:model.live="statusFilter" :options="[
          ['id' => null, 'name' => __('All Status')],
          ['id' => 'active', 'name' => __('Active')],
          ['id' => 'inactive', 'name' => __('Inactive')],
        ]" class="flex-1" />
        @if($search || $categoryFilter || $brandFilter || $statusFilter || $typeFilter)
          <x-button class="btn-ghost btn-sm self-end" icon="o-x-mark" wire:click="clearFilters" title="{{ __('Clear filters') }}" />
        @endif
      </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center justify-between mb-3">
      <div class="text-sm text-base-content/60">
        {{ __('Showing') }} {{ $this->products->firstItem() ?? 0 }}–{{ $this->products->lastItem() ?? 0 }}
        {{ __('of') }} {{ $this->products->total() }}
      </div>
      <x-select wire:model.live="perPage" :options="[
        ['id' => 10, 'name' => '10'],
        ['id' => 25, 'name' => '25'],
        ['id' => 50, 'name' => '50'],
      ]" class="w-20" />
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th class="w-14"></th>
            <th class="cursor-pointer hover:text-primary transition-colors" wire:click="sortBy('name')">
              <div class="flex items-center gap-1">
                {{ __('Product') }}
                @if($sortField === 'name')
                  <x-icon name="{{ $sortDirection === 'asc' ? 'o-chevron-up' : 'o-chevron-down' }}" class="w-3 h-3" />
                @endif
              </div>
            </th>
            <th class="cursor-pointer hover:text-primary transition-colors" wire:click="sortBy('sku')">{{ __('SKU') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Brand') }}</th>
            <th class="text-center">{{ __('Variants') }}</th>
            <th class="text-center">{{ __('Price') }}</th>
            <th class="text-center">{{ __('Status') }}</th>
            @canany(['products.edit', 'products.delete'])
              <th class="text-right">{{ __('Actions') }}</th>
            @endcanany
          </tr>
        </thead>
        <tbody>
          @forelse($this->products as $product)
            <tr class="hover:bg-base-200/30 transition-colors group {{ ! $product->is_active ? 'opacity-50' : '' }}">
              {{-- Image --}}
              <td>
                <div class="w-10 h-10 rounded-lg overflow-hidden bg-base-200 border border-base-300 flex-shrink-0">
                  @if($product->primary_image_url)
                    <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover" />
                  @else
                    <div class="w-full h-full flex items-center justify-center">
                      <x-icon name="o-photo" class="w-5 h-5 text-base-content/20" />
                    </div>
                  @endif
                </div>
              </td>

              {{-- Name + Type --}}
              <td>
                <div>
                  <a href="/app/products/{{ $product->id }}/edit" wire:navigate class="font-semibold text-base-content hover:text-primary transition-colors">
                    {{ $product->name }}
                  </a>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    @php
                      $typeColors = ['liquid' => 'badge-info', 'powder' => 'badge-warning', 'solid' => 'badge-accent', 'packaged' => 'badge-ghost'];
                    @endphp
                    <span class="badge {{ $typeColors[$product->product_type] ?? 'badge-ghost' }} badge-xs">{{ ucfirst($product->product_type) }}</span>
                    @if($product->is_featured)
                      <span class="badge badge-warning badge-xs gap-0.5">
                        <x-icon name="o-star" class="w-2.5 h-2.5" /> {{ __('Featured') }}
                      </span>
                    @endif
                  </div>
                </div>
              </td>

              {{-- SKU --}}
              <td>
                <code class="text-xs bg-base-200 px-1.5 py-0.5 rounded font-mono">{{ $product->sku ?? '—' }}</code>
              </td>

              {{-- Category --}}
              <td class="text-sm text-base-content/70">
                {{ $product->category?->name ?? '—' }}
              </td>

              {{-- Brand --}}
              <td class="text-sm text-base-content/70">
                {{ $product->brand?->name ?? '—' }}
              </td>

              {{-- Variants Count --}}
              <td class="text-center">
                <span class="badge badge-outline badge-sm">{{ $product->variants->count() }}</span>
              </td>

              {{-- Price Range --}}
              <td class="text-center">
                @php
                  $prices = $product->variants->pluck('retail_price')->filter();
                  $min = $prices->min();
                  $max = $prices->max();
                @endphp
                @if($prices->isNotEmpty())
                  <span class="text-sm font-medium">
                    @if($min === $max)
                      ৳{{ number_format($min, 0) }}
                    @else
                      ৳{{ number_format($min, 0) }}–{{ number_format($max, 0) }}
                    @endif
                  </span>
                @else
                  <span class="text-xs text-base-content/40">{{ __('No price') }}</span>
                @endif
              </td>

              {{-- Status --}}
              <td class="text-center">
                @can('products.edit')
                  <input type="checkbox" class="toggle toggle-success toggle-sm" {{ $product->is_active ? 'checked' : '' }}
                    wire:click="toggleActive({{ $product->id }})" />
                @else
                  <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-error' }} badge-sm">
                    {{ $product->is_active ? __('Active') : __('Inactive') }}
                  </span>
                @endcan
              </td>

              {{-- Actions --}}
              @canany(['products.edit', 'products.delete'])
                <td class="text-right">
                  <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    @can('products.edit')
                      <x-button class="btn-ghost btn-sm" icon="o-pencil-square" link="/app/products/{{ $product->id }}/edit" wire:navigate />
                      <x-button class="btn-ghost btn-sm {{ $product->is_featured ? 'text-warning' : '' }}" icon="{{ $product->is_featured ? 's-star' : 'o-star' }}"
                        wire:click="toggleFeatured({{ $product->id }})" title="{{ __('Toggle featured') }}" />
                    @endcan
                    @can('products.delete')
                      <x-button class="btn-ghost btn-sm text-error" icon="o-trash" wire:click="confirmDelete({{ $product->id }})" />
                    @endcan
                  </div>
                </td>
              @endcanany
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-16">
                <x-icon name="o-cube" class="w-12 h-12 mx-auto mb-3 text-base-content/15" />
                <p class="text-base-content/50 mb-4">{{ __('No products found.') }}</p>
                @can('products.create')
                  <x-button class="btn-primary btn-sm" icon="o-plus" link="/app/products/create" wire:navigate>{{ __('Add your first product') }}</x-button>
                @endcan
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="mt-4">{{ $this->products->onEachSide(1)->links() }}</div>
  </x-card>

  {{-- Delete Modal --}}
  <x-modal wire:model="confirmingDeleteId" :title="__('Delete Product')" :subtitle="__('The product will be soft-deleted and can be recovered.')">
    <p>{{ __('Are you sure you want to delete this product?') }}</p>
    <x-slot:actions>
      <x-button class="btn-ghost" wire:click="$set('confirmingDeleteId', null)" icon="o-x-mark">{{ __('Cancel') }}</x-button>
      <x-button class="btn-error" wire:click="deleteConfirmed" icon="o-trash">{{ __('Delete') }}</x-button>
    </x-slot:actions>
  </x-modal>
</div>
