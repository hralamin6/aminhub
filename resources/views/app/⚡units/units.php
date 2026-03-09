<?php

use App\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Units')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast;

    public string $search = '';
    public bool $showForm = false;
    public bool $isEditing = false;
    public ?int $selectedId = null;
    public ?int $confirmingDeleteId = null;

    // Form
    public string $name = '';
    public string $short_name = '';
    public string $unit_type = 'weight';
    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('units.view');
    }

    #[Computed]
    public function units()
    {
        return Unit::query()
            ->withCount('productUnits')
            ->when($this->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('short_name', 'like', "%{$s}%")
            )
            ->orderBy('unit_type')
            ->orderBy('name')
            ->get();
    }

    public function getUnitTypeOptionsProperty(): array
    {
        return [
            ['id' => 'weight', 'name' => __('Weight')],
            ['id' => 'volume', 'name' => __('Volume')],
            ['id' => 'length', 'name' => __('Length')],
            ['id' => 'piece', 'name' => __('Piece')],
            ['id' => 'pack', 'name' => __('Pack')],
        ];
    }

    public function create(): void
    {
        $this->authorize('units.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('units.edit');
        $unit = Unit::findOrFail($id);
        $this->selectedId = $unit->id;
        $this->name = $unit->name;
        $this->short_name = $unit->short_name;
        $this->unit_type = $unit->unit_type;
        $this->is_active = $unit->is_active;
        $this->isEditing = true;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->isEditing ? $this->authorize('units.edit') : $this->authorize('units.create');

        $this->validate([
            'name' => 'required|string|max:100',
            'short_name' => ['required', 'string', 'max:20', Rule::unique('units', 'short_name')->ignore($this->selectedId)],
            'unit_type' => 'required|in:weight,volume,length,piece,pack',
        ]);

        $data = [
            'name' => $this->name,
            'short_name' => $this->short_name,
            'unit_type' => $this->unit_type,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing && $this->selectedId) {
            Unit::findOrFail($this->selectedId)->update($data);
        } else {
            Unit::create($data);
        }

        $this->success(__('Unit saved successfully.'), position: 'toast-bottom');
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('units.edit');
        $unit = Unit::findOrFail($id);
        $unit->update(['is_active' => ! $unit->is_active]);
        $this->success(__('Status updated.'), position: 'toast-bottom');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('units.delete');
        if (! $this->confirmingDeleteId) return;

        $unit = Unit::withCount('productUnits')->findOrFail($this->confirmingDeleteId);

        if ($unit->product_units_count > 0) {
            $this->error(__('Cannot delete unit — it is used by products.'));
            $this->confirmingDeleteId = null;
            return;
        }

        $unit->delete();
        $this->confirmingDeleteId = null;
        $this->success(__('Unit deleted.'), position: 'toast-bottom');
    }

    private function resetForm(): void
    {
        $this->reset(['selectedId', 'name', 'short_name', 'unit_type', 'showForm', 'isEditing']);
        $this->is_active = true;
    }
};
