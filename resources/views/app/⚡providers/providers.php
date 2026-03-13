<?php

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Providers')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 20;

    // Form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $company_name = ''; // Mapped to occupation/bio or use directly
    public float $opening_balance = 0;
    public string $note = ''; // Mapped to bio

    // Detail
    public bool $showDetail = false;
    public ?int $detailId = null;

    // Delete
    public bool $showDelete = false;
    public ?int $deletingId = null;

    public function mount(): void
    {
        $this->authorize('suppliers.view');
    }

    #[Computed]
    public function providers()
    {
        return User::role('provider')
            ->with(['detail'])
            ->withCount('purchases')
            ->when($this->search, fn ($q, $s) => clone $q->where('name', 'like', "%{$s}%")
                ->orWhereHas('detail', fn ($dq) => $dq->where('phone', 'like', "%{$s}%")
                    ->orWhere('occupation', 'like', "%{$s}%")))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => User::role('provider')->count(),
            'total_due' => (float) \App\Models\Purchase::sum('due_amount'),
        ];
    }

    #[Computed]
    public function detailProvider()
    {
        if (! $this->detailId) return null;
        return User::with(['purchases' => fn ($q) => $q->latest()->limit(10), 'detail'])
            ->withCount('purchases')
            ->find($this->detailId);
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorize('suppliers.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('suppliers.edit');
        $provider = User::with('detail')->findOrFail($id);
        $this->editingId = $id;
        $this->name = $provider->name;
        $this->email = $provider->email;
        $this->phone = $provider->detail?->phone ?? '';
        $this->address = $provider->detail?->address ?? '';
        $this->company_name = $provider->detail?->occupation ?? ''; // Map company to occupation
        $this->opening_balance = (float) ($provider->detail?->opening_balance ?? 0);
        $this->note = $provider->detail?->bio ?? ''; // Map note to bio
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'suppliers.edit' : 'suppliers.create');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->editingId)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'company_name' => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ];

        $this->validate($rules);

        DB::transaction(function () {
            if ($this->editingId) {
                $user = User::findOrFail($this->editingId);
                $user->update([
                    'name' => $this->name,
                    'email' => $this->email,
                ]);

                $user->detail()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone' => $this->phone ?: null,
                        'address' => $this->address ?: null,
                        'occupation' => $this->company_name ?: null,
                        'opening_balance' => $this->opening_balance,
                        'bio' => $this->note ?: null,
                    ]
                );

                $msg = __('Provider updated.');
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => bcrypt('password'), // default password
                ]);

                $user->assignRole('provider');

                $user->detail()->create([
                    'phone' => $this->phone ?: null,
                    'address' => $this->address ?: null,
                    'occupation' => $this->company_name ?: null,
                    'opening_balance' => $this->opening_balance,
                    'bio' => $this->note ?: null,
                    'is_active' => true,
                ]);

                $msg = __('Provider created.');
            }

            $this->showForm = false;
            $this->success($msg, position: 'toast-bottom');
        });
    }

    public function showProviderDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('suppliers.delete');
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('suppliers.delete');
        $provider = User::findOrFail($this->deletingId);

        if ($provider->purchases()->exists()) {
            $this->error(__('Cannot delete — provider has purchases.'));
            $this->showDelete = false;
            return;
        }

        $provider->delete();
        $this->showDelete = false;
        $this->success(__('Provider deleted.'), position: 'toast-bottom');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'email', 'phone', 'address', 'company_name', 'opening_balance', 'note']);
    }
};
