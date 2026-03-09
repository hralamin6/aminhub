# ⚖️ Module 03: Unit Conversion System (CRITICAL)

## Overview

এটি সিস্টেমের **সবচেয়ে গুরুত্বপূর্ণ** অংশ। কৃষি দোকানে প্রোডাক্ট এক ইউনিটে কেনা হয় (bag), অন্য ইউনিটে বিক্রি হয় (kg)। সব স্টক হিসাব **base unit** এ হবে।

---

## Core Concept

প্রতিটি প্রোডাক্টের একটি **base unit** থাকবে। Base unit হলো সেই ইউনিট যাতে স্টক রাখা হয়।

```
Example:
  Product: Urea (ইউরিয়া)
  Base Unit: kg

  Purchase: 10 bag × 50kg/bag = 500kg added to stock
  Sale: 5kg sold → stock becomes 495kg
  Sale: 1 bag → converted to 50kg → stock becomes 445kg
```

---

## Database Schema

### `units` Table (Global Units)

```sql
CREATE TABLE units (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,          -- "Kilogram"
    short_name      VARCHAR(20) NOT NULL,           -- "kg"
    unit_type       ENUM('weight', 'volume', 'length', 'piece', 'pack') NOT NULL,
    is_active       BOOLEAN DEFAULT true,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    UNIQUE (short_name)
);
```

### `product_units` Table (Per-Product Conversion)

```sql
CREATE TABLE product_units (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      BIGINT UNSIGNED NOT NULL,
    unit_id         BIGINT UNSIGNED NOT NULL,
    conversion_rate DECIMAL(12,4) NOT NULL,         -- How many base units = 1 of this unit
    is_purchase_unit BOOLEAN DEFAULT false,          -- Can be used in purchase
    is_sale_unit    BOOLEAN DEFAULT true,            -- Can be used in POS/sale
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id),
    UNIQUE (product_id, unit_id)
);
```

---

## Conversion Examples

### Urea (সার)

| Unit    | Conversion Rate | Meaning               | Purchase? | Sale? |
| ------- | --------------- | --------------------- | --------- | ----- |
| kg      | 1               | Base unit              | ✅         | ✅     |
| bag     | 50              | 1 bag = 50 kg          | ✅         | ✅     |
| gram    | 0.001           | 1 gram = 0.001 kg     | ❌         | ✅     |

### Confidor (কীটনাশক — liquid)

| Unit    | Conversion Rate | Meaning                | Purchase? | Sale? |
| ------- | --------------- | ---------------------- | --------- | ----- |
| ml      | 1               | Base unit              | ❌         | ✅     |
| liter   | 1000            | 1 liter = 1000 ml     | ✅         | ✅     |
| bottle  | 100             | 1 bottle = 100 ml     | ✅         | ✅     |

### Rope (রশি)

| Unit    | Conversion Rate | Meaning               | Purchase? | Sale? |
| ------- | --------------- | --------------------- | --------- | ----- |
| meter   | 1               | Base unit              | ❌         | ✅     |
| roll    | 100             | 1 roll = 100 meter    | ✅         | ✅     |

### Polythene (পলিথিন)

| Unit    | Conversion Rate | Meaning               | Purchase? | Sale? |
| ------- | --------------- | --------------------- | --------- | ----- |
| piece   | 1               | Base unit              | ✅         | ✅     |
| dozen   | 12              | 1 dozen = 12 piece    | ✅         | ✅     |
| pack    | 100             | 1 pack = 100 piece    | ✅         | ✅     |

---

## Stock Conversion Formula

### Purchase → Stock IN

```
stock_in_base_unit = purchase_quantity × conversion_rate
```

**Example:**
```
Purchase: 10 bags of Urea
Conversion rate: 1 bag = 50 kg
Stock added: 10 × 50 = 500 kg
```

### Sale → Stock OUT

```
stock_out_base_unit = sale_quantity × conversion_rate
```

**Example:**
```
Sale: 2 bags + 5 kg of Urea
= (2 × 50) + (5 × 1)
= 100 + 5
= 105 kg deducted
```

---

## Laravel Files to Create

### Models

```
app/Models/Unit.php
app/Models/ProductUnit.php
```

### Migration

```
database/migrations/xxxx_create_units_table.php
database/migrations/xxxx_create_product_units_table.php
```

### Livewire Component

```
resources/views/app/⚡units/page.blade.php      — Unit Management
resources/views/app/⚡units/page.php
```

### Route

```php
Route::livewire('/app/units/', 'app::units')->name('app.units');
```

### Seeder

```
database/seeders/UnitSeeder.php
```

---

## Features — Units Page

### UI Layout
- Simple table view with modal create/edit
- Show which products use each unit

### Table Columns
| Short Name | Full Name | Type | Products Using | Status | Actions |
|-----------|-----------|------|---------------|--------|---------|

### Input Fields
- Full Name (e.g., "Kilogram")
- Short Name (e.g., "kg")
- Unit Type (weight / volume / length / piece / pack)
- Active toggle

---

## Unit Conversion on Product Form

প্রোডাক্ট ফর্মের "Units" ট্যাবে:

```
Base Unit: [kg] (selected)

Additional Units:
┌──────────┬──────────────────┬──────────┬──────────┐
│ Unit     │ Conversion Rate  │ Purchase │ Sale     │
├──────────┼──────────────────┼──────────┼──────────┤
│ bag      │ 50               │ ✅       │ ✅       │
│ gram     │ 0.001            │ ❌       │ ✅       │
│ [+ Add]  │                  │          │          │
└──────────┴──────────────────┴──────────┴──────────┘
```

---

## Helper Service

### `UnitConversionService.php`

```php
class UnitConversionService
{
    /**
     * Convert quantity from one unit to base unit
     */
    public function toBaseUnit(int $productId, int $unitId, float $quantity): float
    {
        $productUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->firstOrFail();

        return $quantity * $productUnit->conversion_rate;
    }

    /**
     * Convert quantity from base unit to another unit
     */
    public function fromBaseUnit(int $productId, int $unitId, float $baseQuantity): float
    {
        $productUnit = ProductUnit::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->firstOrFail();

        return $baseQuantity / $productUnit->conversion_rate;
    }

    /**
     * Get display string for stock
     * Returns: "500 kg (10 bags)"
     */
    public function formatStock(int $productId, float $baseQuantity): string
    {
        // ...logic to show in most relevant unit
    }
}
```

---

## Default Seed Data

```php
$units = [
    // Weight
    ['name' => 'Kilogram',  'short_name' => 'kg',    'unit_type' => 'weight'],
    ['name' => 'Gram',      'short_name' => 'g',     'unit_type' => 'weight'],
    ['name' => 'Ton',       'short_name' => 'ton',   'unit_type' => 'weight'],

    // Volume
    ['name' => 'Liter',     'short_name' => 'ltr',   'unit_type' => 'volume'],
    ['name' => 'Milliliter','short_name' => 'ml',    'unit_type' => 'volume'],

    // Length
    ['name' => 'Meter',     'short_name' => 'm',     'unit_type' => 'length'],
    ['name' => 'Feet',      'short_name' => 'ft',    'unit_type' => 'length'],

    // Piece / Pack
    ['name' => 'Piece',     'short_name' => 'pcs',   'unit_type' => 'piece'],
    ['name' => 'Dozen',     'short_name' => 'dz',    'unit_type' => 'piece'],
    ['name' => 'Bag',       'short_name' => 'bag',   'unit_type' => 'pack'],
    ['name' => 'Bottle',    'short_name' => 'btl',   'unit_type' => 'pack'],
    ['name' => 'Packet',    'short_name' => 'pkt',   'unit_type' => 'pack'],
    ['name' => 'Roll',      'short_name' => 'roll',  'unit_type' => 'pack'],
    ['name' => 'Box',       'short_name' => 'box',   'unit_type' => 'pack'],
    ['name' => 'Carton',    'short_name' => 'ctn',   'unit_type' => 'pack'],
];
```

---

## Permissions

```
units.view     — View units
units.create   — Create units
units.edit     — Edit units
units.delete   — Delete units
```

---

## ⚠️ Critical Rules

1. **Stock is ALWAYS stored in base units** — never in bags, bottles, etc.
2. **Conversion rate must never be 0** — validation required.
3. **Changing conversion rate** of an existing product unit should show a warning (it affects all past calculations).
4. **Base unit cannot be changed** once products have stock movements.
5. **Purchase and sale units** can differ — e.g., buy in bags, sell in kg.
