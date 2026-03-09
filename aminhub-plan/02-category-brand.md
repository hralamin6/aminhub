# 🏷️ Module 02: Category & Brand System

## Overview

প্রোডাক্ট অর্গানাইজ করার জন্য Category ও Brand সিস্টেম। Category হায়ারার্কিক্যাল (parent → child) এবং Brand ফ্ল্যাট।

---

## Database Schema

### `categories` Table

```sql
CREATE TABLE categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) UNIQUE NOT NULL,
    parent_id   BIGINT UNSIGNED NULL,
    description TEXT NULL,
    icon        VARCHAR(100) NULL,
    sort_order  INT DEFAULT 0,
    is_active   BOOLEAN DEFAULT true,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX (parent_id),
    INDEX (slug),
    INDEX (is_active)
);
```

### `brands` Table

```sql
CREATE TABLE brands (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) UNIQUE NOT NULL,
    description TEXT NULL,
    logo_path   VARCHAR(500) NULL,
    website     VARCHAR(500) NULL,
    is_active   BOOLEAN DEFAULT true,
    sort_order  INT DEFAULT 0,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,

    INDEX (slug),
    INDEX (is_active)
);
```

---

## Category Hierarchy Example

```
Fertilizer (সার)
 ├── Organic (জৈব সার)
 │   ├── Vermicompost
 │   └── Compost
 └── Chemical (রাসায়নিক সার)
     ├── Urea
     ├── TSP
     └── DAP

Pesticide (কীটনাশক)
 ├── Insecticide
 │   ├── Contact
 │   └── Systemic
 ├── Fungicide
 ├── Herbicide
 └── Miticide

Seeds (বীজ)
 ├── Vegetable Seeds
 ├── Rice Seeds
 └── Flower Seeds

Other Supplies
 ├── Polythene (পলিথিন)
 ├── Rope (রশি)
 └── Tools
```

---

## Laravel Files to Create

### Models

```
app/Models/Category.php
app/Models/Brand.php
```

### Migrations

```
database/migrations/xxxx_create_categories_table.php
database/migrations/xxxx_create_brands_table.php
```

### Livewire Components

```
resources/views/app/⚡categories/page.blade.php    — Category Management
resources/views/app/⚡categories/page.php
resources/views/app/⚡brands/page.blade.php         — Brand Management
resources/views/app/⚡brands/page.php
```

### Routes

```php
Route::livewire('/app/categories/', 'app::categories')->name('app.categories');
Route::livewire('/app/brands/', 'app::brands')->name('app.brands');
```

### Seeders

```
database/seeders/CategorySeeder.php
database/seeders/BrandSeeder.php
```

---

## Features — Categories Page

### UI Layout
- **Left:** Tree view showing category hierarchy (collapsible)
- **Right:** Category form (create/edit)

### Actions
- Create root category
- Create sub-category under parent
- Edit category (name, slug, description, icon)
- Move category (change parent via drag-and-drop or dropdown)
- Toggle active/inactive
- Delete (only if no products attached)

### Tree View Component
```
📁 Fertilizer
   📁 Organic
      📄 Vermicompost
      📄 Compost
   📁 Chemical
      📄 Urea
      📄 TSP
```

---

## Features — Brands Page

### UI Layout
- Full-width table/grid view
- Modal-based create/edit form

### Table Columns
| Logo | Name | Products Count | Status | Actions |
|------|------|---------------|--------|---------|

### Actions
- Create brand (name, slug, logo, website, description)
- Edit brand
- Toggle active/inactive
- Delete (only if no products attached)

---

## Model Relationships

### Category Model

```php
class Category extends Model
{
    public function parent()    → belongsTo(Category::class, 'parent_id')
    public function children()  → hasMany(Category::class, 'parent_id')
    public function products()  → hasMany(Product::class)

    // Recursive children
    public function allChildren() → hasMany(Category::class, 'parent_id')->with('allChildren')

    // Scope
    public function scopeRoot($q)   → $q->whereNull('parent_id')
    public function scopeActive($q) → $q->where('is_active', true)

    // Computed
    public function getFullPathAttribute() → "Pesticide > Insecticide > Systemic"
    public function getProductCountAttribute() → products count including children
}
```

### Brand Model

```php
class Brand extends Model
{
    public function products() → hasMany(Product::class)

    public function scopeActive($q) → $q->where('is_active', true)
}
```

---

## Permissions

```
categories.view     — View categories
categories.create   — Create category
categories.edit     — Edit category
categories.delete   — Delete category

brands.view         — View brands
brands.create       — Create brand
brands.edit         — Edit brand
brands.delete       — Delete brand
```

---

## Validation Rules

### Category

```php
'name'      => 'required|string|max:255',
'slug'      => 'required|string|unique:categories,slug,' . $id,
'parent_id' => 'nullable|exists:categories,id',
'icon'      => 'nullable|string|max:100',
```

### Brand

```php
'name'      => 'required|string|max:255',
'slug'      => 'required|string|unique:brands,slug,' . $id,
'logo'      => 'nullable|image|max:2048',
'website'   => 'nullable|url|max:500',
```

---

## Default Seed Data

### Categories

```php
$categories = [
    ['name' => 'সার (Fertilizer)', 'children' => [
        ['name' => 'জৈব সার (Organic)'],
        ['name' => 'রাসায়নিক সার (Chemical)'],
    ]],
    ['name' => 'কীটনাশক (Pesticide)', 'children' => [
        ['name' => 'Insecticide'],
        ['name' => 'Fungicide'],
        ['name' => 'Herbicide'],
    ]],
    ['name' => 'বীজ (Seeds)'],
    ['name' => 'পলিথিন (Polythene)'],
    ['name' => 'রশি (Rope)'],
    ['name' => 'অন্যান্য (Others)'],
];
```

### Brands

```php
$brands = [
    'Syngenta', 'BASF', 'Bayer CropScience', 'ACI Formulations',
    'Auto Crop Care', 'Haychem', 'McDonald Bangladesh',
    'Karnaphuli Fertilizer', 'Chittagong Urea Fertilizer',
];
```
