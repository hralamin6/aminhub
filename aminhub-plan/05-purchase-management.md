# 🛒 Module 05: Purchase Management

## Overview

সাপ্লায়ার থেকে পণ্য ক্রয়ের পূর্ণাঙ্গ সিস্টেম — supplier management, purchase invoice, payment tracking, purchase return সব।

---

## Database Schema

### `suppliers` Table

```sql
CREATE TABLE suppliers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    company_name    VARCHAR(255) NULL,
    phone           VARCHAR(20) NULL,
    email           VARCHAR(255) NULL,
    address         TEXT NULL,
    opening_balance DECIMAL(12,2) DEFAULT 0,
    is_active       BOOLEAN DEFAULT true,
    note            TEXT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    INDEX (name),
    INDEX (phone)
);
```

### `purchases` Table

```sql
CREATE TABLE purchases (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number      VARCHAR(50) UNIQUE NOT NULL,     -- PUR-2026-0001
    supplier_id         BIGINT UNSIGNED NOT NULL,
    purchase_date       DATE NOT NULL,
    subtotal            DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount            DECIMAL(12,2) DEFAULT 0,
    tax                 DECIMAL(12,2) DEFAULT 0,
    shipping_cost       DECIMAL(12,2) DEFAULT 0,
    grand_total         DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount         DECIMAL(12,2) DEFAULT 0,
    due_amount          DECIMAL(12,2) DEFAULT 0,
    payment_status      ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    status              ENUM('draft', 'received', 'returned') DEFAULT 'draft',
    note                TEXT NULL,
    created_by          BIGINT UNSIGNED NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (invoice_number),
    INDEX (supplier_id),
    INDEX (purchase_date),
    INDEX (payment_status)
);
```

### `purchase_items` Table

```sql
CREATE TABLE purchase_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id         BIGINT UNSIGNED NOT NULL,
    product_variant_id  BIGINT UNSIGNED NOT NULL,
    quantity            DECIMAL(12,4) NOT NULL,
    unit_id             BIGINT UNSIGNED NOT NULL,
    unit_price          DECIMAL(12,2) NOT NULL,
    base_quantity       DECIMAL(12,4) NOT NULL,          -- Converted to base unit
    subtotal            DECIMAL(12,2) NOT NULL,
    batch_number        VARCHAR(100) NULL,
    expiry_date         DATE NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
```

### `purchase_payments` Table

```sql
CREATE TABLE purchase_payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id     BIGINT UNSIGNED NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    payment_method  ENUM('cash', 'bank_transfer', 'bkash', 'check', 'other') DEFAULT 'cash',
    payment_date    DATE NOT NULL,
    reference       VARCHAR(255) NULL,
    note            TEXT NULL,
    created_by      BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### `purchase_returns` Table

```sql
CREATE TABLE purchase_returns (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id         BIGINT UNSIGNED NOT NULL,
    return_number       VARCHAR(50) UNIQUE NOT NULL,     -- PRET-2026-0001
    return_date         DATE NOT NULL,
    total_amount        DECIMAL(12,2) NOT NULL DEFAULT 0,
    reason              TEXT NULL,
    status              ENUM('pending', 'completed') DEFAULT 'pending',
    created_by          BIGINT UNSIGNED NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (purchase_id) REFERENCES purchases(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### `purchase_return_items` Table

```sql
CREATE TABLE purchase_return_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_return_id  BIGINT UNSIGNED NOT NULL,
    purchase_item_id    BIGINT UNSIGNED NOT NULL,
    quantity            DECIMAL(12,4) NOT NULL,          -- In base unit
    unit_price          DECIMAL(12,2) NOT NULL,
    subtotal            DECIMAL(12,2) NOT NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id)
);
```

---

## Laravel Files to Create

### Models

```
app/Models/Supplier.php
app/Models/Purchase.php
app/Models/PurchaseItem.php
app/Models/PurchasePayment.php
app/Models/PurchaseReturn.php
app/Models/PurchaseReturnItem.php
```

### Migrations

```
database/migrations/xxxx_create_suppliers_table.php
database/migrations/xxxx_create_purchases_table.php
database/migrations/xxxx_create_purchase_items_table.php
database/migrations/xxxx_create_purchase_payments_table.php
database/migrations/xxxx_create_purchase_returns_table.php
database/migrations/xxxx_create_purchase_return_items_table.php
```

### Livewire Components

```
resources/views/app/⚡suppliers/page.blade.php          — Supplier List
resources/views/app/⚡suppliers/page.php
resources/views/app/⚡purchases/page.blade.php          — Purchase List
resources/views/app/⚡purchases/page.php
resources/views/app/⚡purchase-form/page.blade.php      — Create/Edit Purchase
resources/views/app/⚡purchase-form/page.php
resources/views/app/⚡purchase-returns/page.blade.php   — Purchase Returns
resources/views/app/⚡purchase-returns/page.php
```

### Routes

```php
Route::livewire('/app/suppliers/', 'app::suppliers')->name('app.suppliers');
Route::livewire('/app/purchases/', 'app::purchases')->name('app.purchases');
Route::livewire('/app/purchases/create', 'app::purchase-form')->name('app.purchases.create');
Route::livewire('/app/purchases/{purchase}/edit', 'app::purchase-form')->name('app.purchases.edit');
Route::livewire('/app/purchase-returns/', 'app::purchase-returns')->name('app.purchase-returns');
```

---

## Features — Supplier Page

### Table

| Name | Company | Phone | Total Purchase | Due Amount | Status | Actions |
|------|---------|-------|---------------|------------|--------|---------|

### Modal Form
- Name (required)
- Company Name
- Phone
- Email
- Address
- Opening Balance
- Note

### Supplier Detail View
- Purchase history
- Payment history
- Total due amount
- Ledger view

---

## Features — Purchase Invoice Page

### Purchase List (Main Page)

| Invoice # | Date | Supplier | Items | Total | Paid | Due | Status | Actions |
|-----------|------|----------|-------|-------|------|-----|--------|---------|

### Filters
- Date range
- Supplier
- Payment status (unpaid, partial, paid)
- Status (draft, received, returned)

### Actions
- View details
- Edit (if draft)
- Add payment
- Create return
- Print invoice

---

## Features — Purchase Form (Create/Edit)

### Header Section
- Supplier (searchable dropdown, required)
- Purchase Date (date picker)
- Invoice Reference (from supplier)
- Note

### Items Section (Dynamic table)

```
┌──────────────┬─────────┬──────┬──────────┬──────────┬─────────┬────────┐
│ Product      │ Variant │ Qty  │ Unit     │ Price/U  │ Batch   │ Total  │
├──────────────┼─────────┼──────┼──────────┼──────────┼─────────┼────────┤
│ [search]     │ [select]│ [10] │ [bag ▼]  │ [1200]   │ [B001]  │ 12000  │
│ [search]     │ [select]│ [5]  │ [ltr ▼]  │ [800]    │ [--]    │ 4000   │
│ + Add Item   │         │      │          │          │         │        │
├──────────────┴─────────┴──────┴──────────┴──────────┴─────────┼────────┤
│                                                    Subtotal   │ 16000  │
│                                                    Discount   │  -500  │
│                                                    Tax        │     0  │
│                                                    Shipping   │   200  │
│                                                    Grand Total│ 15700  │
└───────────────────────────────────────────────────────────────┴────────┘
```

### On Save (status = "received")
1. Purchase record created
2. For each item:
   - Convert quantity to base unit
   - Create `stock_movement` (type: purchase, direction: in)
   - Optionally create `product_batch`
   - Update variant's purchase_price

---

## Features — Payment Modal

- Amount (cannot exceed due)
- Payment method (cash, bank, bKash, check)
- Payment date
- Reference number
- Note

Each payment updates `paid_amount` and `due_amount` on purchase, and recalculates `payment_status`.

---

## Features — Purchase Return

### Form
- Select purchase invoice
- Select items to return
- Quantity (cannot exceed purchased qty)
- Reason
- Creates reverse `stock_movement` (direction: out)

---

## Sidebar Menu

```blade
<x-menu-sub title="Purchase" icon="o-shopping-cart">
    <x-menu-item title="Suppliers"    icon="o-building-office" link="/app/suppliers" />
    <x-menu-item title="Purchases"    icon="o-clipboard-document-list" link="/app/purchases" />
    <x-menu-item title="New Purchase" icon="o-plus" link="/app/purchases/create" />
    <x-menu-item title="Returns"      icon="o-arrow-uturn-left" link="/app/purchase-returns" />
</x-menu-sub>
```

---

## Permissions

```
suppliers.view       — View suppliers
suppliers.create     — Create supplier
suppliers.edit       — Edit supplier
suppliers.delete     — Delete supplier

purchases.view       — View purchases
purchases.create     — Create purchase
purchases.edit       — Edit purchase
purchases.delete     — Delete purchase
purchases.payment    — Record payment

purchase_returns.view   — View returns
purchase_returns.create — Create return
```

---

## Auto-Generated Invoice Number

```php
// In Purchase model or service
public static function generateInvoiceNumber(): string
{
    $year = now()->format('Y');
    $last = self::whereYear('created_at', $year)->max('id') ?? 0;
    return sprintf('PUR-%s-%04d', $year, $last + 1);
}
```
