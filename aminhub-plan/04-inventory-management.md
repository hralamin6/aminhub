# 📊 Module 04: Inventory Management

## Overview

ইনভেন্টরি সিস্টেম সরাসরি stock update করবে না। প্রতিটি stock পরিবর্তন **movement log** আকারে রেকর্ড হবে — purchase, sale, adjustment, return সব log হবে এবং stock movement থেকে calculate হবে।

---

## Core Principle

```
Current Stock = SUM(stock_in) - SUM(stock_out)
```

Stock কখনো সরাসরি UPDATE হবে না। সব `stock_movements` টেবিলে INSERT হবে।

---

## Database Schema

### `stock_movements` Table

```sql
CREATE TABLE stock_movements (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_variant_id  BIGINT UNSIGNED NOT NULL,
    type                ENUM('purchase', 'sale', 'adjustment', 'return_in', 'return_out', 'transfer') NOT NULL,
    direction           ENUM('in', 'out') NOT NULL,
    quantity            DECIMAL(12,4) NOT NULL,         -- Always in base unit
    unit_id             BIGINT UNSIGNED NULL,            -- Original unit used
    original_quantity   DECIMAL(12,4) NULL,              -- Original quantity before conversion
    reference_type      VARCHAR(100) NULL,               -- 'purchase_item', 'sale_item', etc.
    reference_id        BIGINT UNSIGNED NULL,             -- ID of the reference record
    batch_id            BIGINT UNSIGNED NULL,
    note                TEXT NULL,
    created_by          BIGINT UNSIGNED NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (batch_id) REFERENCES product_batches(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (product_variant_id),
    INDEX (type),
    INDEX (direction),
    INDEX (reference_type, reference_id),
    INDEX (created_at)
);
```

### `product_batches` Table

```sql
CREATE TABLE product_batches (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_variant_id  BIGINT UNSIGNED NOT NULL,
    batch_number        VARCHAR(100) NULL,
    manufacturing_date  DATE NULL,
    expiry_date         DATE NULL,
    initial_quantity    DECIMAL(12,4) NOT NULL DEFAULT 0,  -- in base unit
    note                TEXT NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id) ON DELETE CASCADE,
    INDEX (product_variant_id),
    INDEX (batch_number),
    INDEX (expiry_date)
);
```

### `stock_adjustments` Table

```sql
CREATE TABLE stock_adjustments (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    adjustment_number   VARCHAR(50) UNIQUE NOT NULL,     -- ADJ-2026-001
    product_variant_id  BIGINT UNSIGNED NOT NULL,
    type                ENUM('addition', 'subtraction') NOT NULL,
    quantity            DECIMAL(12,4) NOT NULL,           -- In base unit
    reason              VARCHAR(255) NOT NULL,
    note                TEXT NULL,
    created_by          BIGINT UNSIGNED NOT NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

---

## Stock Reservation (for Online Orders)

```sql
ALTER TABLE product_variants ADD COLUMN reserved_stock DECIMAL(12,4) DEFAULT 0;
```

### Stock Types

```
Available Stock = Total Stock - Reserved Stock

Where:
  Total Stock    = SUM(stock_in) - SUM(stock_out)
  Reserved Stock = SUM(pending online orders)
```

---

## Laravel Files to Create

### Models

```
app/Models/StockMovement.php
app/Models/ProductBatch.php
app/Models/StockAdjustment.php
```

### Migrations

```
database/migrations/xxxx_create_stock_movements_table.php
database/migrations/xxxx_create_product_batches_table.php
database/migrations/xxxx_create_stock_adjustments_table.php
```

### Service

```
app/Services/InventoryService.php
```

### Livewire Components

```
resources/views/app/⚡inventory/page.blade.php          — Stock Overview
resources/views/app/⚡inventory/page.php
resources/views/app/⚡stock-adjustments/page.blade.php  — Adjustments
resources/views/app/⚡stock-adjustments/page.php
resources/views/app/⚡stock-movements/page.blade.php    — Movement Log
resources/views/app/⚡stock-movements/page.php
```

### Routes

```php
Route::livewire('/app/inventory/', 'app::inventory')->name('app.inventory');
Route::livewire('/app/stock-adjustments/', 'app::stock-adjustments')->name('app.stock-adjustments');
Route::livewire('/app/stock-movements/', 'app::stock-movements')->name('app.stock-movements');
```

---

## Features — Stock Overview Page

### Dashboard Cards (Top)
- Total Products count
- Total Stock Value (purchase price × quantity)
- Low Stock Items count
- Expiring Soon Items count (within 30 days)

### Stock Table

| Product | Variant | Total Stock | Reserved | Available | Value | Status |
|---------|---------|-------------|----------|-----------|-------|--------|

### Filters
- Category filter
- Brand filter
- Low stock only
- Out of stock only
- Near expiry only

### Actions
- View stock details → shows movement history
- Quick adjustment → opens adjustment modal
- View batches → shows batch details with expiry

---

## Features — Stock Adjustments Page

### Why Adjustments?
- Physical audit এ difference পাওয়া গেলে
- Damage/expired products
- Sample distribution
- Opening stock entry

### Form Fields
- Product/Variant (searchable select)
- Adjustment Type: Addition / Subtraction
- Quantity (select unit, auto-convert to base)
- Reason (select from common + custom text):
  - Damage
  - Expired
  - Stock Count Mismatch
  - Opening Stock
  - Sample/Giveaway
  - Other
- Note (optional)

### Adjustment → Stock Movement
Every adjustment creates a corresponding `stock_movements` entry.

---

## Features — Movement History Page

### Table

| Date | Product | Variant | Type | Direction | Qty | Unit | Reference | By |
|------|---------|---------|------|-----------|-----|------|-----------|----|

### Filters
- Date range
- Product
- Movement type (purchase, sale, adjustment, return)
- Direction (in/out)

---

## InventoryService

```php
class InventoryService
{
    /**
     * Add stock from purchase
     */
    public function addPurchaseStock(
        int $variantId,
        float $quantity,
        int $unitId,
        int $purchaseItemId,
        ?int $batchId = null
    ): StockMovement

    /**
     * Deduct stock from sale
     */
    public function deductSaleStock(
        int $variantId,
        float $quantity,
        int $unitId,
        int $saleItemId
    ): StockMovement

    /**
     * Reserve stock for online order
     */
    public function reserveStock(int $variantId, float $quantity): void

    /**
     * Release reserved stock (order cancelled)
     */
    public function releaseReservedStock(int $variantId, float $quantity): void

    /**
     * Get current stock of a variant (in base unit)
     */
    public function getCurrentStock(int $variantId): float
    {
        $in  = StockMovement::where('product_variant_id', $variantId)
                ->where('direction', 'in')->sum('quantity');
        $out = StockMovement::where('product_variant_id', $variantId)
                ->where('direction', 'out')->sum('quantity');
        return $in - $out;
    }

    /**
     * Get available stock (total - reserved)
     */
    public function getAvailableStock(int $variantId): float

    /**
     * Check if variant has enough stock
     */
    public function hasStock(int $variantId, float $requiredQty): bool

    /**
     * Get all low stock variants
     */
    public function getLowStockItems(): Collection

    /**
     * Get expiring batches
     */
    public function getExpiringBatches(int $days = 30): Collection

    /**
     * Create stock adjustment
     */
    public function createAdjustment(
        int $variantId,
        string $type,
        float $quantity,
        string $reason,
        ?string $note = null
    ): StockAdjustment
}
```

---

## Low Stock Alert System

### Configuration
প্রতিটি Product এ `min_stock` ফিল্ড থাকবে (base unit-এ)।

### Alert Logic
```php
// In InventoryService
public function getLowStockItems(): Collection
{
    return ProductVariant::select('product_variants.*')
        ->join('products', 'products.id', '=', 'product_variants.product_id')
        ->whereRaw('(
            SELECT COALESCE(SUM(CASE WHEN direction = "in" THEN quantity ELSE -quantity END), 0)
            FROM stock_movements
            WHERE product_variant_id = product_variants.id
        ) <= products.min_stock')
        ->where('products.is_active', true)
        ->get();
}
```

### Dashboard Widget
- Badge count in sidebar: "Low Stock (5)"
- Dashboard card with red warning
- Optional: Push notification when stock goes below minimum

---

## Permissions

```
inventory.view          — View stock overview
inventory.adjust        — Make stock adjustments
inventory.movements     — View movement log
inventory.batches       — Manage batches
```

---

## ⚠️ Critical Rules

1. **NEVER directly update stock** — always create a `stock_movement` record.
2. **All quantities in stock_movements are in base unit** — conversion happens before insertion.
3. **Stock cannot go negative** — validate before each sale/adjustment.
4. **Reserved stock** releases automatically when order is delivered or cancelled.
5. **Batch tracking** is optional per product — not all products need it.
6. **Expiry alerts** checked via scheduled command (daily cron job).
