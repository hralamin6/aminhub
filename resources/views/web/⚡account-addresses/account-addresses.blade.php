<div class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="flex items-center justify-between mb-8 pb-4 border-b border-base-200">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold">{{ __('My Addresses') }}</h1>
            <p class="text-sm text-base-content/60 mt-1">{{ __('Manage your delivery addresses') }}</p>
        </div>
        <button class="btn btn-primary btn-sm" wire:click="create">
            <x-icon name="o-plus" class="w-4 h-4" /> {{ __('Add New') }}
        </button>
    </div>

    @if($showForm)
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
        <div class="card-body space-y-4">
            <h3 class="font-bold text-lg">{{ $editingId ? __('Edit Address') : __('New Address') }}</h3>
            <form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-select label="{{ __('Label') }}" wire:model="label" :options="[
                        ['id' => 'Home', 'name' => __('Home')],
                        ['id' => 'Office', 'name' => __('Office')],
                        ['id' => 'Other', 'name' => __('Other')],
                    ]" />
                    <x-input label="{{ __('Full Name') }}" wire:model="full_name" required />
                    <x-input label="{{ __('Phone') }}" wire:model="phone" required />
                    <x-input label="{{ __('Postal Code') }}" wire:model="postal_code" />
                    <div class="md:col-span-2">
                        <x-textarea label="{{ __('Full Address') }}" wire:model="address_line" rows="2" required />
                    </div>
                    <x-checkbox label="{{ __('Set as default address') }}" wire:model="is_default" />
                </div>
                <div class="flex gap-2 justify-end mt-4">
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="$set('showForm', false)">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Save Address') }}</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="grid gap-4">
        @forelse($this->addresses as $address)
            <div class="card bg-base-100 shadow-sm border {{ $address->is_default ? 'border-primary' : 'border-base-200' }}">
                <div class="card-body p-4 md:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="badge badge-outline badge-sm">{{ $address->label }}</span>
                                @if($address->is_default)
                                    <span class="badge badge-primary badge-sm">{{ __('Default') }}</span>
                                @endif
                            </div>
                            <p class="font-bold text-base">{{ $address->full_name }}</p>
                            <p class="text-sm opacity-70"><x-icon name="o-phone" class="w-3 h-3 inline" /> {{ $address->phone }}</p>
                            <p class="text-sm opacity-60 mt-1">{{ $address->address_line }}
                                @if($address->postal_code), {{ $address->postal_code }}@endif
                            </p>
                        </div>
                        <div class="flex gap-1">
                            @if(!$address->is_default)
                                <button class="btn btn-ghost btn-xs" wire:click="setDefault({{ $address->id }})" title="{{ __('Set as default') }}">
                                    <x-icon name="o-star" class="w-4 h-4" />
                                </button>
                            @endif
                            <button class="btn btn-ghost btn-xs" wire:click="edit({{ $address->id }})">
                                <x-icon name="o-pencil" class="w-4 h-4" />
                            </button>
                            <button class="btn btn-ghost btn-xs text-error" wire:click="delete({{ $address->id }})" wire:confirm="{{ __('Delete this address?') }}">
                                <x-icon name="o-trash" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-16">
                <x-icon name="o-map-pin" class="w-16 h-16 mx-auto mb-4 opacity-20" />
                <h3 class="text-lg font-semibold text-base-content/50">{{ __('No addresses yet') }}</h3>
                <p class="text-sm text-base-content/40 mt-1">{{ __('Add your delivery address to make checkout faster') }}</p>
                <button class="btn btn-primary mt-4" wire:click="create">{{ __('Add New Address') }}</button>
            </div>
        @endforelse
    </div>
</div>
