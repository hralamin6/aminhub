<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Customers')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 20;

    // Filter
    public bool $hasDueFilter = false;

    // Form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public float $opening_balance = 0;
    public string $note = '';

    // Detail
    public bool $showDetail = false;
    public ?int $detailId = null;

    // Delete
    public bool $showDelete = false;
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('customers.view');
    }

    #[Computed]
    public function customers()
    {
        return User::role('customer')
            ->with('detail')
            ->withCount('sales')
            ->when($this->search, fn ($q, $s) => clone $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhereHas('detail', fn ($sq) => clone $sq->where('phone', 'like', "%{$s}%")))
            ->when($this->hasDueFilter, fn ($q) => clone $q->whereHas('detail', fn ($dq) => clone $dq->where('total_due', '>', 0)))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => User::role('customer')->count(),
            'total_due' => (float) \App\Models\UserDetail::whereHas('user', fn($q) => clone $q->role('customer'))->sum('total_due'),
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorize('customers.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('customers.edit');
        $customer = User::with('detail')->findOrFail($id);
        $this->editingId = $id;
        $this->name = $customer->name;
        $this->email = $customer->email ?? '';
        $this->phone = $customer->detail?->phone ?? '';
        $this->address = $customer->detail?->address ?? '';
        $this->opening_balance = (float) ($customer->detail?->opening_balance ?? 0);
        $this->note = $customer->detail?->bio ?? ''; // Using bio for notes
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'customers.edit' : 'customers.create');

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ];

        // Ensure email isn't taken by someone else
        if ($this->email) {
            $rules['email'][] = Rule::unique('users')->ignore($this->editingId);
        }

        $this->validate($rules);

        // A fake email fallback if none
        $userEmail = $this->email ?: $this->phone . '@walkin.local';

        DB::transaction(function () use ($userEmail) {
            if ($this->editingId) {
                $user = User::findOrFail($this->editingId);
                $user->update([
                    'name' => $this->name,
                    'email' => $userEmail,
                ]);

                $user->detail()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone' => $this->phone,
                        'address' => $this->address ?: null,
                        'opening_balance' => $this->opening_balance,
                        'bio' => $this->note ?: null,
                    ]
                );
                $msg = __('Customer updated.');
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $userEmail,
                    'password' => bcrypt('password'), // default password for manual creation
                ]);

                $user->assignRole('customer');

                $user->detail()->create([
                    'phone' => $this->phone,
                    'address' => $this->address ?: null,
                    'opening_balance' => $this->opening_balance,
                    'bio' => $this->note ?: null,
                    'is_active' => true,
                ]);

                $msg = __('Customer created.');
            }

            $this->showForm = false;
            $this->success($msg, position: 'toast-bottom');
        });
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('customers.delete');
        $customer = User::findOrFail($this->deletingId);

        if ($customer->sales()->exists()) {
            $this->error(__('Cannot delete — customer has sales.'));
            $this->showDelete = false;
            return;
        }

        $customer->delete();
        $this->showDelete = false;
        $this->success(__('Customer deleted.'), position: 'toast-bottom');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'address', 'opening_balance', 'note']);
    }
};
