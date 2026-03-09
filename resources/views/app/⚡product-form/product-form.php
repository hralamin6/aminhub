<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Unit;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Title('Product Form')]
#[Layout('layouts.app')]
class extends Component
{
    use Toast, WithFileUploads;

    public ?int $productId = null;

    // Tab control
    public string $activeTab = 'basic';

    // Basic info
    public string $name = '';
    public string $slug = '';
    public ?string $sku = null;
    public ?int $category_id = null;
    public ?int $brand_id = null;
    public ?int $base_unit_id = null;
    public string $product_type = 'packaged';
    public string $description = '';
    public ?string $barcode = null;
    public float $tax_rate = 0;
    public float $min_stock = 0;
    public bool $is_active = true;
    public bool $is_featured = false;
    public bool $show_in_ecommerce = true;

    // Variants
    public array $variants = [];

    // Unit conversions
    public array $unitConversions = [];

    // Images
    public array $newImages = [];
    public array $existingImages = [];

    public function mount(?int $product = null): void
    {
        if ($product) {
            $this->authorize('products.edit');
            $this->loadProduct($product);
        } else {
            $this->authorize('products.create');
            // Start with one empty variant
            $this->variants = [$this->emptyVariant()];
        }
    }

    private function loadProduct(int $id): void
    {
        $product = Product::with(['variants' => fn ($q) => $q->orderBy('sort_order'), 'unitConversions.unit', 'media'])->findOrFail($id);

        $this->productId = $product->id;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->sku = $product->sku;
        $this->category_id = $product->category_id;
        $this->brand_id = $product->brand_id;
        $this->base_unit_id = $product->base_unit_id;
        $this->product_type = $product->product_type;
        $this->description = (string) $product->description;
        $this->barcode = $product->barcode;
        $this->tax_rate = (float) $product->tax_rate;
        $this->min_stock = (float) $product->min_stock;
        $this->is_active = $product->is_active;
        $this->is_featured = $product->is_featured;
        $this->show_in_ecommerce = $product->show_in_ecommerce;

        // Load variants
        $this->variants = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'name' => $v->name,
            'sku' => $v->sku,
            'barcode' => $v->barcode,
            'purchase_price' => (float) $v->purchase_price,
            'retail_price' => (float) $v->retail_price,
            'online_price' => $v->online_price ? (float) $v->online_price : null,
            'wholesale_price' => $v->wholesale_price ? (float) $v->wholesale_price : null,
            'weight' => $v->weight ? (float) $v->weight : null,
            'is_active' => $v->is_active,
        ])->toArray();

        if (empty($this->variants)) {
            $this->variants = [$this->emptyVariant()];
        }

        // Load unit conversions
        $this->unitConversions = $product->unitConversions->map(fn ($uc) => [
            'id' => $uc->id,
            'unit_id' => $uc->unit_id,
            'conversion_rate' => (float) $uc->conversion_rate,
            'is_purchase_unit' => $uc->is_purchase_unit,
            'is_sale_unit' => $uc->is_sale_unit,
        ])->toArray();

        // Load existing images
        $this->existingImages = $product->getMedia('product-images')->map(fn ($m) => [
            'id' => $m->id,
            'url' => $m->getUrl('thumb'),
            'name' => $m->name,
        ])->toArray();
    }

    private function emptyVariant(): array
    {
        return [
            'id' => null,
            'name' => '',
            'sku' => '',
            'barcode' => '',
            'purchase_price' => 0,
            'retail_price' => 0,
            'online_price' => null,
            'wholesale_price' => null,
            'weight' => null,
            'is_active' => true,
        ];
    }

    #[Computed]
    public function categoryOptions()
    {
        return Category::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->full_path])
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
            ->toArray();
    }

    #[Computed]
    public function unitOptions()
    {
        return Unit::query()
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => "{$u->name} ({$u->short_name})"])
            ->toArray();
    }

    public function getProductTypeOptionsProperty(): array
    {
        return [
            ['id' => 'liquid', 'name' => __('Liquid (তরল)')],
            ['id' => 'powder', 'name' => __('Powder (গুঁড়া)')],
            ['id' => 'solid', 'name' => __('Solid (কঠিন)')],
            ['id' => 'packaged', 'name' => __('Packaged (প্যাকেজড)')],
        ];
    }

    public function updatedName(): void
    {
        if (! $this->productId) {
            $this->slug = Str::slug($this->name);
        }
    }

    // ─── Variant Management ─────────────────────────────

    public function addVariant(): void
    {
        $this->variants[] = $this->emptyVariant();
    }

    public function removeVariant(int $index): void
    {
        if (count($this->variants) <= 1) {
            $this->error(__('At least one variant is required.'));
            return;
        }
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    // ─── Unit Conversion Management ─────────────────────

    public function addUnitConversion(): void
    {
        $this->unitConversions[] = [
            'id' => null,
            'unit_id' => null,
            'conversion_rate' => 1,
            'is_purchase_unit' => false,
            'is_sale_unit' => true,
        ];
    }

    public function removeUnitConversion(int $index): void
    {
        unset($this->unitConversions[$index]);
        $this->unitConversions = array_values($this->unitConversions);
    }

    // ─── Image Management ───────────────────────────────

    public function removeExistingImage(int $mediaId): void
    {
        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $media = $product->media()->find($mediaId);
            if ($media) {
                $media->delete();
            }
            $this->existingImages = array_values(array_filter($this->existingImages, fn ($img) => $img['id'] !== $mediaId));
            $this->success(__('Image removed.'), position: 'toast-bottom');
        }
    }

    // ─── Save ───────────────────────────────────────────

    public function save(): void
    {
        $this->productId ? $this->authorize('products.edit') : $this->authorize('products.create');

        $this->validate([
            'name' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($this->productId)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->productId)],
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'base_unit_id' => 'required|exists:units,id',
            'product_type' => 'required|in:liquid,powder,solid,packaged',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|max:100',
            'tax_rate' => 'numeric|min:0|max:100',
            'min_stock' => 'numeric|min:0',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.purchase_price' => 'numeric|min:0',
            'variants.*.retail_price' => 'numeric|min:0',
            'newImages.*' => 'nullable|image|max:5120',
        ]);

        $productData = [
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku ?: null,
            'category_id' => $this->category_id,
            'brand_id' => $this->brand_id,
            'base_unit_id' => $this->base_unit_id,
            'product_type' => $this->product_type,
            'description' => $this->description ?: null,
            'barcode' => $this->barcode ?: null,
            'tax_rate' => $this->tax_rate,
            'min_stock' => $this->min_stock,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'show_in_ecommerce' => $this->show_in_ecommerce,
        ];

        if ($this->productId) {
            $product = Product::findOrFail($this->productId);
            $product->update($productData);
        } else {
            $product = Product::create($productData);
            $this->productId = $product->id;
        }

        // ── Save variants ──
        $existingIds = [];
        foreach ($this->variants as $i => $v) {
            $variantData = [
                'name' => $v['name'],
                'sku' => $v['sku'] ?: null,
                'barcode' => $v['barcode'] ?: null,
                'purchase_price' => $v['purchase_price'] ?? 0,
                'retail_price' => $v['retail_price'] ?? 0,
                'online_price' => $v['online_price'] ?: null,
                'wholesale_price' => $v['wholesale_price'] ?: null,
                'weight' => $v['weight'] ?: null,
                'is_active' => $v['is_active'] ?? true,
                'sort_order' => $i,
            ];

            if (! empty($v['id'])) {
                $variant = $product->variants()->find($v['id']);
                $variant?->update($variantData);
                $existingIds[] = $v['id'];
            } else {
                $newVariant = $product->variants()->create($variantData);
                $existingIds[] = $newVariant->id;
            }
        }
        // Remove deleted variants
        $product->variants()->whereNotIn('id', $existingIds)->delete();

        // ── Save unit conversions ──
        $existingUcIds = [];
        foreach ($this->unitConversions as $uc) {
            if (! $uc['unit_id'] || $uc['conversion_rate'] <= 0) continue;
            $ucData = [
                'unit_id' => $uc['unit_id'],
                'conversion_rate' => $uc['conversion_rate'],
                'is_purchase_unit' => $uc['is_purchase_unit'] ?? false,
                'is_sale_unit' => $uc['is_sale_unit'] ?? true,
            ];

            if (! empty($uc['id'])) {
                $existing = $product->unitConversions()->find($uc['id']);
                $existing?->update($ucData);
                $existingUcIds[] = $uc['id'];
            } else {
                $new = $product->unitConversions()->create($ucData);
                $existingUcIds[] = $new->id;
            }
        }
        $product->unitConversions()->whereNotIn('id', $existingUcIds)->delete();

        // ── Upload new images ──
        if (! empty($this->newImages)) {
            foreach ($this->newImages as $image) {
                $product->addMedia($image->getRealPath())
                    ->usingFileName(time() . '_' . Str::random(6) . '.' . $image->getClientOriginalExtension())
                    ->toMediaCollection('product-images');
            }
            $this->newImages = [];
        }

        $this->success(__('Product saved successfully!'), position: 'toast-bottom');

        // Redirect to list
        $this->redirect(route('app.products'), navigate: true);
    }
};
