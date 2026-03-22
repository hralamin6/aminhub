<div class="space-y-6">
  <x-header :title="$productId ? __('Edit Product') : __('New Product')"
    :subtitle="$productId ? __('Update product details, variants, and images.') : __('Create a new product in your catalog.')" separator>
    <x-slot:actions>
      <x-button class="btn-ghost" icon="o-arrow-left" link="/app/products" wire:navigate>{{ __('Back') }}</x-button>
    </x-slot:actions>
  </x-header>

  {{-- Tabs --}}
  <div class="tabs tabs-boxed bg-base-200/50 p-1 rounded-xl max-w-fit">
    <button class="tab {{ $activeTab === 'basic' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'basic')">
      <x-icon name="o-information-circle" class="w-4 h-4 mr-1.5" /> {{ __('Basic Info') }}
    </button>
    <button class="tab {{ $activeTab === 'variants' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'variants')">
      <x-icon name="o-squares-2x2" class="w-4 h-4 mr-1.5" /> {{ __('Variants') }}
      <span class="badge badge-sm badge-primary ml-1.5">{{ count($variants) }}</span>
    </button>
    <button class="tab {{ $activeTab === 'images' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'images')">
      <x-icon name="o-photo" class="w-4 h-4 mr-1.5" /> {{ __('Images') }}
    </button>
    <button class="tab {{ $activeTab === 'settings' ? 'tab-active' : '' }}" wire:click="$set('activeTab', 'settings')">
      <x-icon name="o-cog-6-tooth" class="w-4 h-4 mr-1.5" /> {{ __('Settings') }}
    </button>
  </div>

  {{-- ═══════════ TAB: Basic Info ═══════════ --}}
  @if($activeTab === 'basic')
    <x-card title="{{ __('Basic Information') }}" subtitle="{{ __('Product name, category, type, and description.') }}" shadow>
      <div class="space-y-4">
        <div class="grid md:grid-cols-2 gap-4">
          <x-input :label="__('Product Name')" wire:model.live.debounce.500ms="name" required icon="o-cube" placeholder="{{ __('e.g. Urea Fertilizer') }}" />
          <x-input :label="__('Slug')" wire:model="slug" icon="o-link" />
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <x-input :label="__('SKU')" wire:model="sku" icon="o-hashtag" placeholder="{{ __('Auto-generated if empty') }}" />
          <x-input :label="__('Barcode')" wire:model="barcode" icon="o-qr-code" />
          <x-input :label="__('Tax Rate (%)')" wire:model="tax_rate" type="number" step="0.01" min="0" max="100" icon="o-receipt-percent" />
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <x-select :label="__('Category')" wire:model="category_id" :options="$this->categoryOptions" icon="o-tag" placeholder="{{ __('Select category') }}" placeholder-value="" />
          <x-select :label="__('Brand')" wire:model="brand_id" :options="$this->brandOptions" icon="o-building-storefront" placeholder="{{ __('Select brand') }}" placeholder-value="" />
          <x-select :label="__('Product Type')" wire:model="product_type" :options="$this->productTypeOptions" required icon="o-beaker" />
        </div>

        <x-select :label="__('Base Unit')" wire:model="base_unit_id" :options="$this->unitOptions" required icon="o-scale"
          hint="{{ __('The fundamental unit for stock tracking. Cannot be changed after stock movements.') }}" />

        <x-textarea :label="__('Description')" wire:model="description" rows="4" :placeholder="__('Product details and specifications...')" />
      </div>
    </x-card>
  @endif

  {{-- ═══════════ TAB: Variants ═══════════ --}}
  @if($activeTab === 'variants')
    <x-card title="{{ __('Product Variants') }}" subtitle="{{ __('Different sizes, quantities, or packagings of this product.') }}" shadow>
      <div class="space-y-4">
        @foreach($variants as $i => $variant)
          <div class="border border-base-300 rounded-xl p-4 relative hover:border-primary/20 transition-colors bg-base-100">
            {{-- Remove button --}}
            @if(count($variants) > 1)
              <button class="absolute top-2 right-2 btn btn-ghost btn-xs btn-circle text-error" wire:click="removeVariant({{ $i }})" title="{{ __('Remove') }}">
                <x-icon name="o-x-mark" class="w-4 h-4" />
              </button>
            @endif

            <div class="flex items-center gap-2 mb-3">
              <span class="badge badge-primary badge-sm font-mono">#{{ $i + 1 }}</span>
              <span class="text-sm font-medium text-base-content/70">{{ $variant['name'] ?: __('New Variant') }}</span>
            </div>

            <div class="grid md:grid-cols-3 gap-3">
              <x-input :label="__('Variant Name')" wire:model="variants.{{ $i }}.name" required placeholder="{{ __('e.g. 50kg bag, 100ml bottle') }}" />
              <x-input :label="__('SKU')" wire:model="variants.{{ $i }}.sku" placeholder="{{ __('Optional') }}" />
              <x-input :label="__('Barcode')" wire:model="variants.{{ $i }}.barcode" placeholder="{{ __('Optional') }}" />
            </div>

            <div class="grid md:grid-cols-4 gap-4 mt-4 bg-base-200/50 p-3 rounded-lg border border-base-200">
              <div class="md:col-span-1 border-r border-base-300 pr-4">
                <div class="text-sm font-semibold mb-3 text-base-content/80 flex items-center justify-between">
                  <div class="flex items-center"><x-icon name="o-photo" class="w-4 h-4 mr-1.5"/> {{ __('Variant Image') }}</div>
                  <button type="button" wire:click="openAiModal('{{ $i }}')" class="btn btn-xs btn-ghost text-primary px-1 min-h-0 h-6" title="{{ __('Generate with AI') }}"><x-icon name="o-sparkles" class="w-4 h-4" /></button>
                </div>
                <div class="relative w-24">
                  <x-avatar-upload
                      model="variants.{{ $i }}.new_image"
                      :image="(!empty($variant['ai_image_path']) && file_exists($variant['ai_image_path'])) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($variant['ai_image_path'])) : (!empty($variant['new_image']) ? $variant['new_image']->temporaryUrl() : (!empty($variant['existing_image_url']) ? $variant['existing_image_url'] : ''))"
                      size="w-24 h-24"
                      accept="image/*"
                  />
                  @if(!empty($variant['new_image']) || !empty($variant['existing_image_url']) || !empty($variant['ai_image_path']))
                    <div class="absolute -top-2 -right-2 flex gap-1 z-10">
                      <button type="button" wire:click="openAiModal('{{ $i }}', '{{ !empty($variant['ai_image_path']) ? $variant['ai_image_path'] : (!empty($variant['new_image']) ? $variant['new_image']->getRealPath() : (!empty($variant['existing_image_url']) ? $variant['existing_image_url'] : '')) }}')" class="btn btn-circle btn-primary btn-xs w-6 h-6 min-h-0 shadow" title="{{ __('Edit with AI') }}">
                        <x-icon name="o-pencil" class="w-3 h-3"/>
                      </button>
                      <button type="button" wire:click="removeVariantImage({{ $i }})" wire:confirm.prompt="{{ __('Are you sure? Type YES to confirm.') }}|YES" class="absolute -top-2 -right-2 btn btn-circle btn-error btn-xs w-6 h-6 min-h-0 shadow" title="{{ __('Remove Image') }}">
                        <x-icon name="o-trash" class="w-3 h-3"/>
                      </button>
                    </div>
                  @endif
                </div>
              </div>

              <div class="md:col-span-3">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                  <x-input :label="__('Purchase Price (৳)')" wire:model="variants.{{ $i }}.purchase_price" type="number" step="0.01" min="0" />
                  <x-input :label="__('Retail Price (৳)')" wire:model="variants.{{ $i }}.retail_price" type="number" step="0.01" min="0" />
                  <x-input :label="__('Online Price (৳)')" wire:model="variants.{{ $i }}.online_price" type="number" step="0.01" min="0" placeholder="{{ __('Optional') }}" />
                  <x-input :label="__('Wholesale Price (৳)')" wire:model="variants.{{ $i }}.wholesale_price" type="number" step="0.01" min="0" placeholder="{{ __('Optional') }}" />
                </div>
                <div class="grid md:grid-cols-3 gap-3 mt-3">
                  <x-input :label="__('Weight (base unit)')" wire:model="variants.{{ $i }}.weight" type="number" step="0.001" min="0" placeholder="{{ __('Optional') }}" />
                  <div class="flex items-end pb-1 md:col-span-2">
                    <x-toggle :label="__('Active Variant')" wire:model="variants.{{ $i }}.is_active" class="mt-2" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        @endforeach

        <x-button class="btn-outline btn-primary btn-sm" icon="o-plus" wire:click="addVariant">
          {{ __('Add Variant') }}
        </x-button>
      </div>
    </x-card>

    {{-- Unit Conversions --}}
    <x-card title="{{ __('Unit Conversions') }}" subtitle="{{ __('Define how this product converts between units (e.g. 1 bag = 50 kg).') }}" shadow class="mt-4">
      <div class="space-y-3">
        @forelse($unitConversions as $i => $uc)
          <div class="grid md:grid-cols-5 gap-3 items-end border border-base-300 rounded-lg p-3 bg-base-100 relative">
            <x-select :label="__('Unit')" wire:model="unitConversions.{{ $i }}.unit_id" :options="$this->unitOptions" />
            <x-input :label="__('= how many base units')" wire:model="unitConversions.{{ $i }}.conversion_rate" type="number" step="0.0001" min="0.0001" />
            <div class="flex items-end pb-1">
              <x-toggle :label="__('Purchase')" wire:model="unitConversions.{{ $i }}.is_purchase_unit" />
            </div>
            <div class="flex items-end pb-1">
              <x-toggle :label="__('Sale')" wire:model="unitConversions.{{ $i }}.is_sale_unit" />
            </div>
            <div class="flex items-end">
              <x-button class="btn-ghost btn-sm text-error" icon="o-trash" wire:click="removeUnitConversion({{ $i }})" />
            </div>
          </div>
        @empty
          <div class="text-center py-6 text-base-content/40">
            <x-icon name="o-scale" class="w-8 h-8 mx-auto mb-2 opacity-30" />
            <p class="text-sm">{{ __('No extra unit conversions. Base unit will be used for all operations.') }}</p>
          </div>
        @endforelse

        <x-button class="btn-outline btn-sm" icon="o-plus" wire:click="addUnitConversion">
          {{ __('Add Unit Conversion') }}
        </x-button>
      </div>
    </x-card>
  @endif

  {{-- ═══════════ TAB: Images ═══════════ --}}
  @if($activeTab === 'images')
    <x-card title="{{ __('Product Images') }}" subtitle="{{ __('Upload product photos. First image is used as primary.')  }}" shadow>
      {{-- Existing images --}}
      @if(count($existingImages))
        <div class="mb-6">
          <h4 class="text-sm font-medium mb-3 text-base-content/70">{{ __('Current Images') }}</h4>
          <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
            @foreach($existingImages as $img)
              <div class="relative group">
                <div class="aspect-square rounded-xl overflow-hidden border-2 border-base-300 hover:border-primary/30 transition-all">
                  <img src="{{ $img['url'] }}" alt="{{ $img['name'] }}" class="w-full h-full object-cover" />
                </div>
                <div class="absolute -top-2 -right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                  <button type="button" class="btn btn-circle btn-primary btn-xs shadow-lg"
                    wire:click="openAiModal('product', '{{ $img['url'] }}')" title="{{ __('Edit with AI') }}">
                    <x-icon name="o-pencil" class="w-3 h-3" />
                  </button>
                  <button type="button" class="btn btn-circle btn-error btn-xs shadow-lg"
                    wire:click="removeExistingImage({{ $img['id'] }})" wire:confirm="{{ __('Remove this image?') }}">
                    <x-icon name="o-x-mark" class="w-3 h-3" />
                  </button>
                </div>
                @if($loop->first)
                  <span class="absolute bottom-1 left-1 badge badge-primary badge-xs">{{ __('Primary') }}</span>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endif

      {{-- Upload new --}}
      <div>
        <div class="flex items-center justify-between mb-3">
          <h4 class="text-sm font-medium text-base-content/70">{{ __('Upload New Images') }}</h4>
          <button type="button" wire:click="openAiModal('product')" class="btn btn-sm btn-ghost text-primary font-bold"><x-icon name="o-sparkles" class="w-4 h-4 mr-1.5" /> {{ __('Generate with AI') }}</button>
        </div>
        <div class="relative w-full h-36 border-2 border-dashed border-primary/30 hover:border-primary bg-primary/5 hover:bg-primary/10 rounded-xl flex items-center justify-center transition-colors group cursor-pointer">
          <div class="text-center text-primary group-hover:scale-105 transition-transform pointer-events-none">
            <x-icon name="o-arrow-up-tray" class="w-10 h-10 mx-auto mb-2 opacity-80" />
            <p class="font-bold text-sm">{{ __('Click to upload multiple images') }}</p>
            <p class="text-[11px] opacity-70 mt-1 uppercase tracking-wider">{{ __('Max 5MB each. JPG, PNG, WebP.') }}</p>
          </div>
          <input type="file" wire:model="newImages" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10 title-none" title="" />
          
          {{-- Progress --}}
          <div wire:loading wire:target="newImages" class="absolute inset-0 flex items-center justify-center bg-base-100/90 backdrop-blur-sm z-20 rounded-xl">
            <div class="text-center text-primary">
              <span class="loading loading-ring loading-lg"></span>
              <p class="text-xs font-bold tracking-widest mt-1">{{ __('UPLOADING...') }}</p>
            </div>
          </div>
        </div>

        @if(count($newImages))
          <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 mt-6 p-4 bg-success/5 border border-success/20 rounded-xl">
            <div class="col-span-full">
               <h4 class="text-xs font-bold text-success flex items-center"><x-icon name="o-check-circle" class="w-4 h-4 mr-1.5"/> {{ count($newImages) }} {{ __('new image(s) ready to be saved.') }}</h4>
            </div>
            @foreach($newImages as $k => $img)
              <div class="aspect-square rounded-xl overflow-hidden shadow-sm relative group bg-white">
                <img src="{{ $img->temporaryUrl() }}" class="w-full h-full object-cover" />
                <button class="absolute -top-1.5 -right-1.5 btn btn-circle btn-error min-h-0 w-6 h-6 shadow opacity-0 group-hover:opacity-100 transition-opacity z-10"
                  wire:click="removeNewImage({{ $k }})" title="{{ __('Remove') }}">
                  <x-icon name="o-x-mark" class="w-3 h-3" />
                </button>
              </div>
            @endforeach
          </div>
        @endif

        @if(count($newAiImages))
          <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3 mt-6 p-4 bg-primary/5 border border-primary/20 rounded-xl">
            <div class="col-span-full">
               <h4 class="text-xs font-bold text-primary flex items-center"><x-icon name="o-sparkles" class="w-4 h-4 mr-1.5"/> {{ count($newAiImages) }} {{ __('AI generated image(s) ready to be saved.') }}</h4>
            </div>
            @foreach($newAiImages as $k => $aiImg)
              @if(file_exists($aiImg))
              <div class="aspect-square rounded-xl overflow-hidden shadow-sm relative group bg-white">
                <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents($aiImg)) }}" class="w-full h-full object-cover" />
                <div class="absolute -top-1.5 -right-1.5 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-20">
                  <button type="button" class="btn btn-circle btn-primary btn-xs w-6 h-6 min-h-0 shadow"
                    wire:click="openAiModal('product', '{{ $aiImg }}')" title="{{ __('Edit with AI') }}">
                    <x-icon name="o-pencil" class="w-3 h-3" />
                  </button>
                  <button type="button" class="btn btn-circle btn-error min-h-0 w-6 h-6 shadow"
                    wire:click="removeAiImage({{ $k }})" title="{{ __('Remove') }}">
                    <x-icon name="o-x-mark" class="w-3 h-3" />
                  </button>
                </div>
              </div>
              @endif
            @endforeach
          </div>
        @endif
      </div>
    </x-card>
  @endif

  {{-- ═══════════ TAB: Settings ═══════════ --}}
  @if($activeTab === 'settings')
    <x-card title="{{ __('Product Settings') }}" subtitle="{{ __('Visibility, stock alerts, and toggles.') }}" shadow>
      <div class="space-y-6">
        <div class="grid md:grid-cols-3 gap-6">
          <div class="p-4 rounded-xl border border-base-300 bg-base-100 hover:border-primary/20 transition-colors">
            <x-toggle :label="__('Active')" wire:model="is_active" />
            <p class="text-xs text-base-content/50 mt-1">{{ __('Inactive products won\'t appear in POS or ecommerce.') }}</p>
          </div>
          <div class="p-4 rounded-xl border border-base-300 bg-base-100 hover:border-warning/20 transition-colors">
            <x-toggle :label="__('Featured')" wire:model="is_featured" />
            <p class="text-xs text-base-content/50 mt-1">{{ __('Featured products are highlighted on the homepage.') }}</p>
          </div>
          <div class="p-4 rounded-xl border border-base-300 bg-base-100 hover:border-info/20 transition-colors">
            <x-toggle :label="__('Show in Ecommerce')" wire:model="show_in_ecommerce" />
            <p class="text-xs text-base-content/50 mt-1">{{ __('Toggle visibility on the online shop.') }}</p>
          </div>
        </div>

        <div class="max-w-md">
          <x-input :label="__('Minimum Stock Level')" wire:model="min_stock" type="number" step="0.01" min="0" icon="o-exclamation-triangle"
            hint="{{ __('Alert when stock falls below this quantity (in base units).') }}" />
        </div>
      </div>
    </x-card>
  @endif

  {{-- ═══════════ Save Bar ═══════════ --}}
  <div class="sticky bottom-0 z-10 bg-base-100/90 backdrop-blur border-t border-base-300 -mx-4 px-4 py-3 mt-6">
    <div class="flex items-center justify-between max-w-7xl mx-auto">
      <x-button class="btn-ghost" icon="o-arrow-left" link="/app/products" wire:navigate>{{ __('Cancel') }}</x-button>
      <div class="flex items-center gap-3">
        <span class="text-sm text-base-content/50 hidden sm:block">
          @if($productId) {{ __('Editing') }}: <strong>{{ $name }}</strong> @else {{ __('Creating new product') }} @endif
        </span>
        <x-button class="btn-primary btn-md" icon="o-check" wire:click="save" spinner="save">
          {{ $productId ? __('Update Product') : __('Create Product') }}
        </x-button>
      </div>
    </div>
  </div>

  {{-- AI Image Generation Modal --}}
  <x-modal wire:model="showAiImageModal" title="{{ $aiImageSourcePath ? __('Edit Image with AI') : __('Generate AI Image') }}">
    <div class="space-y-4 pt-2">
      @if($aiImageSourcePath && file_exists($aiImageSourcePath))
        <div class="flex items-center gap-4 p-3 bg-base-200 rounded-xl mb-4">
          <div class="w-20 h-20 rounded-lg overflow-hidden flex-shrink-0 border border-base-300">
            <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents($aiImageSourcePath)) }}" class="w-full h-full object-cover" />
          </div>
          <div class="text-xs">
            <p class="font-bold text-base-content/70">{{ __('Source Image') }}</p>
            <p class="mt-1 opacity-50">{{ __('You are editing this image. Describe the changes you want below.') }}</p>
          </div>
        </div>
      @endif

      <x-textarea :label="__('Prompt')" wire:model="aiImagePrompt" rows="3" placeholder="{{ $aiImageSourcePath ? __('Describe what to change...') : __('e.g. A realistic bag of fertilizer on a clean white background...') }}" required />
      
      <div class="flex justify-end gap-2 mt-4">
        <x-button :label="__('Cancel')" wire:click="$set('showAiImageModal', false)" class="btn-ghost btn-sm" />
        <x-button :label="__('Process')" wire:click="generateAiImage" class="btn-primary w-32 btn-sm" spinner="generateAiImage" icon="o-sparkles" />
      </div>
    </div>
  </x-modal>
</div>
