<?php

use App\Models\Supplier;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Suppliers')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 20;
    public string $statusFilter = '';

    // Form
    public bool $showForm = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $company_name = '';
    public string $phone = '';
    public string $email = '';
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
        $this->authorize('suppliers.view');
    }

    #[Computed]
    public function suppliers()
    {
        return Supplier::query()
            ->withCount('purchases')
            ->when($this->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('company_name', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%"))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function stats()
    {
        return [
            'total' => Supplier::count(),
            'active' => Supplier::active()->count(),
            'total_due' => (float) \App\Models\Purchase::sum('due_amount'),
        ];
    }

    #[Computed]
    public function detailSupplier()
    {
        if (! $this->detailId) return null;
        return Supplier::with(['purchases' => fn ($q) => $q->latest()->limit(10)])
            ->withCount('purchases')
            ->find($this->detailId);
    }

    public function updatingSearch(): void { $this->resetPage(); }

    // ─── Create/Edit ─────────────────────────────────

    public function create(): void
    {
        $this->authorize('suppliers.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('suppliers.edit');
        $supplier = Supplier::findOrFail($id);
        $this->editingId = $id;
        $this->name = $supplier->name;
        $this->company_name = $supplier->company_name ?? '';
        $this->phone = $supplier->phone ?? '';
        $this->email = $supplier->email ?? '';
        $this->address = $supplier->address ?? '';
        $this->opening_balance = (float) $supplier->opening_balance;
        $this->note = $supplier->note ?? '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'suppliers.edit' : 'suppliers.create');

        $rules = [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'opening_balance' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ];

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'company_name' => $this->company_name ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'opening_balance' => $this->opening_balance,
            'note' => $this->note ?: null,
        ];

        if ($this->editingId) {
            Supplier::findOrFail($this->editingId)->update($data);
            $msg = __('Supplier updated.');
        } else {
            Supplier::create($data);
            $msg = __('Supplier created.');
        }

        $this->showForm = false;
        $this->success($msg, position: 'toast-bottom');
    }

    // ─── Toggle Active ───────────────────────────────

    public function toggleActive(int $id): void
    {
        $this->authorize('suppliers.edit');
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => ! $supplier->is_active]);
        $this->success(__('Status updated.'), position: 'toast-bottom');
    }

    // ─── Detail ──────────────────────────────────────

    public function showSupplierDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    // ─── Delete ──────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->authorize('suppliers.delete');
        $this->deletingId = $id;
        $this->showDelete = true;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('suppliers.delete');
        $supplier = Supplier::findOrFail($this->deletingId);

        if ($supplier->purchases()->exists()) {
            $this->error(__('Cannot delete — supplier has purchases.'));
            $this->showDelete = false;
            return;
        }

        $supplier->delete();
        $this->showDelete = false;
        $this->success(__('Supplier deleted.'), position: 'toast-bottom');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'company_name', 'phone', 'email', 'address', 'opening_balance', 'note']);
    }
};
