<?php

use App\Models\UserAddress;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('My Addresses')]
#[Layout('layouts.shop')]
class extends Component
{
    use Toast;

    public bool $showForm = false;
    public ?int $editingId = null;
    public string $label = 'Home';
    public string $full_name = '';
    public string $phone = '';
    public string $address_line = '';
    public ?string $postal_code = '';
    public bool $is_default = false;

    public function mount(): void
    {
        if (!auth()->check()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    #[Computed]
    public function addresses()
    {
        return UserAddress::where('user_id', auth()->id())
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'label', 'full_name', 'phone', 'address_line', 'postal_code', 'is_default']);
        $this->full_name = auth()->user()->name;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $address = UserAddress::where('user_id', auth()->id())->findOrFail($id);
        $this->editingId = $address->id;
        $this->label = $address->label ?? 'Home';
        $this->full_name = $address->full_name;
        $this->phone = $address->phone;
        $this->address_line = $address->address_line;
        $this->postal_code = $address->postal_code;
        $this->is_default = (bool) $address->is_default;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'label' => 'required|string|max:100',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address_line' => 'required|string',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $data = [
            'user_id' => auth()->id(),
            'label' => $this->label,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'address_line' => $this->address_line,
            'postal_code' => $this->postal_code,
            'is_default' => $this->is_default,
        ];

        if ($this->is_default) {
            UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        if ($this->editingId) {
            UserAddress::where('user_id', auth()->id())->where('id', $this->editingId)->update($data);
        } else {
            UserAddress::create($data);
        }

        $this->showForm = false;
        $this->success(__('Address saved'), position: 'toast-bottom');
        unset($this->addresses);
    }

    public function delete(int $id): void
    {
        UserAddress::where('user_id', auth()->id())->where('id', $id)->delete();
        $this->success(__('Address deleted'), position: 'toast-bottom');
        unset($this->addresses);
    }

    public function setDefault(int $id): void
    {
        UserAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        UserAddress::where('user_id', auth()->id())->where('id', $id)->update(['is_default' => true]);
        $this->success(__('Default address updated'), position: 'toast-bottom');
        unset($this->addresses);
    }
};
