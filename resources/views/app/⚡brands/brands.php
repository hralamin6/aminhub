<?php

use App\Models\Brand;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Brands')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination, WithFileUploads;

    public string $search = '';
    public int $perPage = 15;
    public bool $showForm = false;
    public bool $isEditing = false;
    public ?int $selectedId = null;
    public ?int $confirmingDeleteId = null;

    // Form
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $website = '';
    public bool $is_active = true;
    public $logo = null; // file upload

    public function mount(): void
    {
        $this->authorize('brands.view');
    }

    #[Computed]
    public function brands()
    {
        return Brand::query()
            ->withCount('products')
            ->when($this->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('sort_order')
            ->paginate($this->perPage);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('brands.create');
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('brands.edit');
        $brand = Brand::findOrFail($id);
        $this->selectedId = $brand->id;
        $this->name = $brand->name;
        $this->slug = $brand->slug;
        $this->description = (string) $brand->description;
        $this->website = (string) $brand->website;
        $this->is_active = $brand->is_active;
        $this->logo = null;
        $this->isEditing = true;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->isEditing ? $this->authorize('brands.edit') : $this->authorize('brands.create');

        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($this->selectedId)],
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:500',
            'logo' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'website' => $this->website ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing && $this->selectedId) {
            $brand = Brand::findOrFail($this->selectedId);
            $brand->update($data);
        } else {
            $brand = Brand::create($data);
        }

        // Handle logo upload via Spatie Media Library
        if ($this->logo) {
            $brand->clearMediaCollection('logo');
            $brand->addMedia($this->logo->getRealPath())
                ->usingFileName(time() . '.' . $this->logo->getClientOriginalExtension())
                ->toMediaCollection('logo');
        }

        $this->success(__('Brand saved successfully.'), position: 'toast-bottom');
        $this->resetForm();
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('brands.edit');
        $brand = Brand::findOrFail($id);
        $brand->update(['is_active' => ! $brand->is_active]);
        $this->success(__('Status updated.'), position: 'toast-bottom');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('brands.delete');
        if (! $this->confirmingDeleteId) return;

        $brand = Brand::withCount('products')->findOrFail($this->confirmingDeleteId);

        if ($brand->products_count > 0) {
            $this->error(__('Cannot delete brand with existing products.'));
            $this->confirmingDeleteId = null;
            return;
        }

        $brand->clearMediaCollection('logo');
        $brand->delete();
        $this->confirmingDeleteId = null;
        $this->success(__('Brand deleted.'), position: 'toast-bottom');
        $this->resetPage();
    }

    public function removeLogo(int $id): void
    {
        $this->authorize('brands.edit');
        $brand = Brand::findOrFail($id);
        $brand->clearMediaCollection('logo');
        $this->success(__('Logo removed.'), position: 'toast-bottom');
    }

    private function resetForm(): void
    {
        $this->reset(['selectedId', 'name', 'slug', 'description', 'website', 'logo', 'showForm', 'isEditing']);
        $this->is_active = true;
    }

    public function updatedName(): void
    {
        if (! $this->isEditing) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
    }
};
