# 📦 Module 01: Product Management

## Overview

প্রোডাক্ট ম্যানেজমেন্ট হলো সিস্টেমের মূল ভিত্তি। প্রতিটি বিক্রি, ক্রয়, স্টক — সব কিছু এই মডিউলের উপর নির্ভর করে।

---

## Database Schema

### `products` Table

```sql
CREATE TABLE products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) UNIQUE NOT NULL,
    sku             VARCHAR(100) UNIQUE,
    category_id     BIGINT UNSIGNED NULL,
    brand_id        BIGINT UNSIGNED NULL,
    base_unit_id    BIGINT UNSIGNED NOT NULL,
    product_type    ENUM('liquid', 'powder', 'solid', 'packaged') DEFAULT 'packaged',
    description     TEXT NULL,
    min_stock       DECIMAL(10,2) DEFAULT 0,
    is_active       BOOLEAN DEFAULT true,
    is_featured     BOOLEAN DEFAULT false,
    show_in_ecommerce BOOLEAN DEFAULT true,
    barcode         VARCHAR(100) NULL,
    tax_rate        DECIMAL(5,2) DEFAULT 0,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (base_unit_id) REFERENCES units(id),
    INDEX (slug),
    INDEX (sku),
    INDEX (barcode),
    INDEX (is_active)
);
```

### `product_variants` Table

প্রতিটা প্রোডাক্টের একাধিক ভ্যারিয়েন্ট থাকতে পারে (100ml, 250ml, 500ml ইত্যাদি)।

```sql
CREATE TABLE product_variants (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255) NOT NULL,          -- e.g. "100ml", "50kg bag"
    sku             VARCHAR(100) UNIQUE,
    barcode         VARCHAR(100) NULL,
    purchase_price  DECIMAL(12,2) NOT NULL DEFAULT 0,
    retail_price    DECIMAL(12,2) NOT NULL DEFAULT 0,
    online_price    DECIMAL(12,2) NULL,
    wholesale_price DECIMAL(12,2) NULL,
    weight          DECIMAL(10,3) NULL,             -- weight in base unit
    is_active       BOOLEAN DEFAULT true,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX (product_id),
    INDEX (sku),
    INDEX (barcode)
);
```

### `product_images` Table

```sql
CREATE TABLE product_images (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      BIGINT UNSIGNED NOT NULL,
    variant_id      BIGINT UNSIGNED NULL,
    image_path      VARCHAR(500) NOT NULL,
    is_primary      BOOLEAN DEFAULT false,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);
```

> **Note:** Alternatively, use Spatie Media Library (already installed) for image management.

---

## Laravel Files to Create

### Model

```
app/Models/Product.php
app/Models/ProductVariant.php
```

### Migration

```
database/migrations/xxxx_create_products_table.php
database/migrations/xxxx_create_product_variants_table.php
database/migrations/xxxx_create_product_images_table.php
```

### Livewire Components

```
resources/views/app/⚡products/page.blade.php        — Product List (Admin)
resources/views/app/⚡products/page.php               — Livewire Logic
resources/views/app/⚡product-form/page.blade.php     — Product Create/Edit Form
resources/views/app/⚡product-form/page.php            — Livewire Logic
```

### Routes

```php
Route::livewire('/app/products/', 'app::products')->name('app.products');
Route::livewire('/app/products/create', 'app::product-form')->name('app.products.create');
Route::livewire('/app/products/{product}/edit', 'app::product-form')->name('app.products.edit');
```

---

## Features & Functionality

### Product List Page

- Table view with columns: Image, Name, SKU, Category, Brand, Price, Stock, Status
- Search by name, SKU, barcode
- Filter by category, brand, status, product type
- Sort by name, price, stock, date
- Bulk actions: Activate, Deactivate, Delete
- "Low Stock" badge indicator
- Pagination (25 per page)

### Product Create/Edit Form

**Main Fields (Tab 1 — Basic Info):**
- Product Name (required)
- Slug (auto-generated from name)
- SKU (auto-generated or manual)
- Category (select dropdown with search)
- Brand (select dropdown with search)
- Product Type (liquid/powder/solid/packaged)
- Base Unit (select from units table)
- Barcode (manual or auto-generate)
- Description (rich text editor)
- Tax Rate (%)

**Variants (Tab 2 — Variants):**
- Add multiple variants dynamically
- Each variant: Name, SKU, Barcode, Purchase Price, Retail Price, Online Price, Wholesale Price, Weight
- Drag-and-drop reorder
- Delete variant (with confirmation if stock exists)

**Images (Tab 3 — Images):**
- Upload multiple images (use Spatie Media Library)
- Set primary image
- Drag to reorder
- Image preview

**Settings (Tab 4 — Settings):**
- Active / Inactive toggle
- Featured toggle
- Show in Ecommerce toggle
- Minimum Stock level
- Unit conversions (link to unit system)

---

## Product Model Relationships

```php
class Product extends Model
{
    // Belongs to
    public function category()    → belongsTo(Category::class)
    public function brand()       → belongsTo(Brand::class)
    public function baseUnit()    → belongsTo(Unit::class, 'base_unit_id')

    // Has many
    public function variants()    → hasMany(ProductVariant::class)
    public function images()      → hasMany(ProductImage::class) // or use Spatie Media
    public function unitConversions() → hasMany(ProductUnit::class)
    public function stockMovements()  → hasManyThrough(StockMovement, ProductVariant)

    // Computed
    public function getTotalStockAttribute()  → sum of all variant stocks
    public function getIsLowStockAttribute()  → total stock < min_stock
}
```

---

## Permissions

```
products.view       — View product list
products.create     — Create new product
products.edit       — Edit existing product
products.delete     — Delete product
```

---

## Sidebar Menu

```blade
<x-menu-sub title="Products" icon="o-cube">
    <x-menu-item title="All Products" icon="o-list-bullet" link="/app/products" />
    <x-menu-item title="Add Product"  icon="o-plus"        link="/app/products/create" />
    <x-menu-item title="Categories"   icon="o-tag"         link="/app/categories" />
    <x-menu-item title="Brands"       icon="o-building-storefront" link="/app/brands" />
    <x-menu-item title="Units"        icon="o-scale"       link="/app/units" />
</x-menu-sub>
```

---

## Validation Rules

```php
'name'          => 'required|string|max:255',
'slug'          => 'required|string|unique:products,slug,' . $id,
'sku'           => 'nullable|string|unique:products,sku,' . $id,
'category_id'   => 'nullable|exists:categories,id',
'brand_id'      => 'nullable|exists:brands,id',
'base_unit_id'  => 'required|exists:units,id',
'product_type'  => 'required|in:liquid,powder,solid,packaged',
'min_stock'     => 'numeric|min:0',
'tax_rate'      => 'numeric|min:0|max:100',
```

---

## API / Data Flow

```
Admin creates product
    → Assigns category, brand
    → Sets base unit (kg, liter, piece, etc.)
    → Adds variants (100ml, 250ml, etc.)
    → Uploads images
    → Sets prices (retail, online, wholesale)
    → Configures min stock alert
    → Product is ready for purchase & sale
```
