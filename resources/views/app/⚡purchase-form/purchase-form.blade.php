<div class="space-y-6">
  <x-header :title="$purchaseId ? __('Edit Purchase') : __('New Purchase')"
    :subtitle="$purchaseId ? __('Update a draft purchase invoice.') : __('Create a new purchase invoice from a supplier.')" separator>
    <x-slot:actions>
      <x-button class="btn-ghost btn-sm" icon="o-arrow-left" link="/app/purchases" wire:navigate>{{ __('Back') }}</x-button>
    </x-slot:actions>
  </x-header>

  {{-- Header Section --}}
  <x-card :title="__('Purchase Information')">
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <x-select :label="__('Supplier')" wire:model="supplier_id" :options="$this->supplierOptions"
        icon="o-building-office" required placeholder="{{ __('Select supplier') }}" placeholder-value="" />
      <x-input :label="__('Purchase Date')" wire:model="purchase_date" type="date" required />
      <x-textarea :label="__('Note')" wire:model="note" rows="1" />
    </div>
  </x-card>

  {{-- Items Section --}}
  <x-card :title="__('Purchase Items')">
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr class="bg-base-200/50">
            <th class="min-w-[250px]">{{ __('Product / Variant') }}</th>
            <th class="w-20">{{ __('Qty') }}</th>
            <th class="w-36">{{ __('Unit') }}</th>
            <th class="w-28">{{ __('Unit Price') }}</th>
            <th class="w-28">{{ __('Batch #') }}</th>
            <th class="w-32">{{ __('Expiry') }}</th>
            <th class="w-28 text-right">{{ __('Total') }}</th>
            <th class="w-10"></th>
          </tr>
        </thead>
        <tbody>
          @foreach($items as $i => $item)
            <tr class="border-b border-base-200" wire:key="item-{{ $i }}">
              <td>
                <select wire:model="items.{{ $i }}.product_variant_id" class="select select-sm select-bordered w-full" required>
                  <option value="">{{ __('Select...') }}</option>
                  @foreach($this->variantOptions as $opt)
                    <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="number" wire:model.live="items.{{ $i }}.quantity" class="input input-sm input-bordered w-full text-right"
                  step="0.01" min="0.01" required />
              </td>
              <td>
                <select wire:model="items.{{ $i }}.unit_id" class="select select-sm select-bordered w-full" required>
                  <option value="">{{ __('Unit') }}</option>
                  @foreach($this->unitOptions as $opt)
                    <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                  @endforeach
                </select>
              </td>
              <td>
                <input type="number" wire:model.live="items.{{ $i }}.unit_price" class="input input-sm input-bordered w-full text-right"
                  step="0.01" min="0" required />
              </td>
              <td>
                <input type="text" wire:model="items.{{ $i }}.batch_number" class="input input-sm input-bordered w-full" placeholder="—" />
              </td>
              <td>
                <input type="date" wire:model="items.{{ $i }}.expiry_date" class="input input-sm input-bordered w-full" />
              </td>
              <td class="text-right font-mono font-semibold text-sm">
                ৳{{ number_format($this->getItemSubtotal($i), 2) }}
              </td>
              <td>
                @if(count($items) > 1)
                  <x-button class="btn-ghost btn-xs text-error" icon="o-x-mark" wire:click="removeItem({{ $i }})" />
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="mt-3">
      <x-button class="btn-ghost btn-sm" icon="o-plus" wire:click="addItem">{{ __('Add Item') }}</x-button>
    </div>

    {{-- Totals --}}
    <div class="mt-6 flex justify-end">
      <div class="w-full max-w-sm space-y-2">
        <div class="flex justify-between text-sm">
          <span>{{ __('Subtotal') }}</span>
          <span class="font-mono">৳{{ number_format($this->subtotal, 2) }}</span>
        </div>
        <div class="flex justify-between items-center gap-2">
          <label class="text-sm">{{ __('Discount') }}</label>
          <input type="number" wire:model.live="discount" class="input input-sm input-bordered w-28 text-right" step="0.01" min="0" />
        </div>
        <div class="flex justify-between items-center gap-2">
          <label class="text-sm">{{ __('Tax') }}</label>
          <input type="number" wire:model.live="tax" class="input input-sm input-bordered w-28 text-right" step="0.01" min="0" />
        </div>
        <div class="flex justify-between items-center gap-2">
          <label class="text-sm">{{ __('Shipping') }}</label>
          <input type="number" wire:model.live="shipping_cost" class="input input-sm input-bordered w-28 text-right" step="0.01" min="0" />
        </div>
        <div class="divider my-1"></div>
        <div class="flex justify-between text-lg font-bold">
          <span>{{ __('Grand Total') }}</span>
          <span class="font-mono text-primary">৳{{ number_format($this->grandTotal, 2) }}</span>
        </div>
      </div>
    </div>
  </x-card>

  {{-- Action Bar --}}
  <div class="sticky bottom-0 z-10 bg-base-100/95 backdrop-blur border-t border-base-300 -mx-4 px-4 py-3 flex items-center justify-between">
    <x-button class="btn-ghost" icon="o-arrow-left" link="/app/purchases" wire:navigate>{{ __('Cancel') }}</x-button>
    <div class="flex gap-2">
      <x-button class="btn-outline" wire:click="saveDraft" spinner="saveDraft" icon="o-document">{{ __('Save as Draft') }}</x-button>
      <x-button class="btn-primary" wire:click="saveReceived" spinner="saveReceived" icon="o-check">{{ __('Save & Receive Stock') }}</x-button>
    </div>
  </div>
</div>
