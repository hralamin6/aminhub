<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Title('Products')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithPagination;

    public string $search = '';
    public int $perPage = 25;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public ?int $categoryFilter = null;
    public ?int $brandFilter = null;
    public ?string $statusFilter = null;
    public ?string $typeFilter = null;
    public ?int $confirmingDeleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 25],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'categoryFilter' => ['except' => null],
        'brandFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'typeFilter' => ['except' => null],
    ];

    public function mount(): void
    {
        $this->authorize('products.view');
    }

    #[Computed]
    public function products()
    {
        $query = Product::query()
            ->with(['category', 'brand', 'baseUnit', 'variants', 'media']);

        // Search
        if ($this->search) {
            $s = "%{$this->search}%";
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                    ->orWhere('sku', 'like', $s)
                    ->orWhere('barcode', 'like', $s)
                    ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'like', $s)->orWhere('barcode', 'like', $s));
            });
        }

        // Filters
        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }
        if ($this->brandFilter) {
            $query->where('brand_id', $this->brandFilter);
        }
        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('is_active', false);
        }
        if ($this->typeFilter) {
            $query->where('product_type', $this->typeFilter);
        }

        // Sort
        $allowedSorts = ['name', 'sku', 'created_at', 'product_type'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $dir = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($field, $dir)->paginate($this->perPage);
    }

    #[Computed]
    public function categoryOptions()
    {
        return Category::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_path])
            ->prepend(['id' => null, 'name' => __('All Categories')])
            ->toArray();
    }

    #[Computed]
    public function brandOptions()
    {
        return Brand::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
            ->prepend(['id' => null, 'name' => __('All Brands')])
            ->toArray();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }
    public function updatingBrandFilter(): void { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }
    public function updatingTypeFilter(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function toggleActive(int $id): void
    {
        $this->authorize('products.edit');
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        $this->success($product->is_active ? __('Product activated.') : __('Product deactivated.'), position: 'toast-bottom');
    }

    public function toggleFeatured(int $id): void
    {
        $this->authorize('products.edit');
        $product = Product::findOrFail($id);
        $product->update(['is_featured' => ! $product->is_featured]);
        $this->success(__('Featured status updated.'), position: 'toast-bottom');
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('products.delete');
        if (! $this->confirmingDeleteId) return;

        $product = Product::findOrFail($this->confirmingDeleteId);
        $product->clearMediaCollection('product-images');
        $product->delete(); // soft delete
        $this->confirmingDeleteId = null;
        $this->success(__('Product deleted.'), position: 'toast-bottom');
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'categoryFilter', 'brandFilter', 'statusFilter', 'typeFilter']);
        $this->resetPage();
    }
};
