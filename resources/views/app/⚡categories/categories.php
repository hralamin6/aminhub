<?php

use App\Models\Category;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Title('Categories')]
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
    public string $slug = '';
    public ?int $parent_id = null;
    public string $description = '';
    public string $icon = '';
    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('categories.view');
    }

    #[Computed]
    public function categories()
    {
        return Category::query()
            ->root()
            ->with('children.children', 'children.products')
            ->withCount('products')
            ->when($this->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")
                ->orWhereHas('children', fn ($q2) => $q2->where('name', 'like', "%{$s}%"))
            )
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function parentOptions()
    {
        return Category::query()
            ->root()
            ->where('id', '!=', $this->selectedId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->prepend(['id' => null, 'name' => __('— None (Root Category) —')])
            ->toArray();
    }

    public function create(?int $parentId = null): void
    {
        $this->authorize('categories.create');
        $this->resetForm();
        $this->parent_id = $parentId;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('categories.edit');
        $category = Category::findOrFail($id);
        $this->selectedId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->parent_id = $category->parent_id;
        $this->description = (string) $category->description;
        $this->icon = (string) $category->icon;
        $this->is_active = $category->is_active;
        $this->isEditing = true;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->isEditing ? $this->authorize('categories.edit') : $this->authorize('categories.create');

        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($this->selectedId)],
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
        ]);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'description' => $this->description ?: null,
            'icon' => $this->icon ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing && $this->selectedId) {
            Category::findOrFail($this->selectedId)->update($data);
        } else {
            Category::create($data);
        }

        $this->success(__('Category saved successfully.'), position: 'toast-bottom');
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('categories.edit');
        $cat = Category::findOrFail($id);
        $cat->update(['is_active' => ! $cat->is_active]);
        $this->success(__('Status updated.'), position: 'toast-bottom');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('categories.delete');
        if (! $this->confirmingDeleteId) return;

        $category = Category::withCount('products', 'children')->findOrFail($this->confirmingDeleteId);

        if ($category->products_count > 0) {
            $this->error(__('Cannot delete category with existing products.'));
            $this->confirmingDeleteId = null;
            return;
        }

        if ($category->children_count > 0) {
            $this->error(__('Cannot delete category with sub-categories. Delete children first.'));
            $this->confirmingDeleteId = null;
            return;
        }

        $category->delete();
        $this->confirmingDeleteId = null;
        $this->success(__('Category deleted.'), position: 'toast-bottom');
    }

    private function resetForm(): void
    {
        $this->reset(['selectedId', 'name', 'slug', 'parent_id', 'description', 'icon', 'is_active', 'showForm', 'isEditing']);
        $this->is_active = true;
    }

    public function updatedName(): void
    {
        if (! $this->isEditing) {
            $this->slug = \Illuminate\Support\Str::slug($this->name);
        }
    }
};
