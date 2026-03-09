# 🖥️ Module 06: POS (Point of Sale) System

## Overview

POS ইন্টারফেস হবে দ্রুত, সিম্পল, কিবোর্ড-ফ্রেন্ডলি। দোকানদার দ্রুত বিক্রি করতে পারবে — barcode scan, product search, quick checkout সব সাপোর্ট করবে।

---

## Database Schema

### `sales` Table

```sql
CREATE TABLE sales (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(50) UNIQUE NOT NULL,     -- INV-2026-0001
    sale_type       ENUM('pos', 'online') DEFAULT 'pos',
    customer_id     BIGINT UNSIGNED NULL,
    customer_name   VARCHAR(255) NULL,               -- Walk-in customer name
    customer_phone  VARCHAR(20) NULL,                -- Walk-in customer phone
    subtotal        DECIMAL(12,2) NOT NULL DEFAULT 0,
    discount_type   ENUM('flat', 'percent') DEFAULT 'flat',
    discount_value  DECIMAL(12,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    tax             DECIMAL(12,2) DEFAULT 0,
    grand_total     DECIMAL(12,2) NOT NULL DEFAULT 0,
    paid_amount     DECIMAL(12,2) DEFAULT 0,
    change_amount   DECIMAL(12,2) DEFAULT 0,
    due_amount      DECIMAL(12,2) DEFAULT 0,
    payment_method  ENUM('cash', 'bkash', 'nagad', 'card', 'mixed') DEFAULT 'cash',
    payment_status  ENUM('paid', 'partial', 'unpaid') DEFAULT 'paid',
    status          ENUM('completed', 'draft', 'void') DEFAULT 'completed',
    note            TEXT NULL,
    sold_by         BIGINT UNSIGNED NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    deleted_at      TIMESTAMP NULL,

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (sold_by) REFERENCES users(id),
    INDEX (invoice_number),
    INDEX (sale_type),
    INDEX (customer_id),
    INDEX (created_at),
    INDEX (payment_status)
);
```

### `sale_items` Table

```sql
CREATE TABLE sale_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id             BIGINT UNSIGNED NOT NULL,
    product_variant_id  BIGINT UNSIGNED NOT NULL,
    quantity            DECIMAL(12,4) NOT NULL,
    unit_id             BIGINT UNSIGNED NOT NULL,
    base_quantity       DECIMAL(12,4) NOT NULL,          -- Converted to base unit
    unit_price          DECIMAL(12,2) NOT NULL,
    discount            DECIMAL(12,2) DEFAULT 0,
    subtotal            DECIMAL(12,2) NOT NULL,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP,

    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
```

---

## POS Interface Design

### Full-Screen Layout (No Sidebar)

```
┌──────────────────────────────────────────────────────────────────────────┐
│  🏪 AminHub POS           INV-2026-0042        🔍 Search / Scan         │
├────────────────────────────────────────────┬─────────────────────────────┤
│                                            │                             │
│  Product Grid / Search Results             │  Cart                       │
│                                            │                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐   │  ┌─────────────────────┐   │
│  │ Urea     │ │ DAP      │ │ TSP      │   │  │ Urea 50kg bag ×2    │   │
│  │ 50kg bag │ │ 50kg bag │ │ 50kg bag │   │  │ ৳2,400    [−][+][×] │   │
│  │ ৳1,200   │ │ ৳1,500   │ │ ৳1,100   │   │  ├─────────────────────┤   │
│  └──────────┘ └──────────┘ └──────────┘   │  │ Confidor 100ml ×5    │   │
│                                            │  │ ৳1,750    [−][+][×] │   │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐   │  ├─────────────────────┤   │
│  │Confidor  │ │ Rope     │ │Polythene │   │  │                     │   │
│  │ 100ml    │ │ 100m     │ │ dozen    │   │  │                     │   │
│  │ ৳350     │ │ ৳200     │ │ ৳120     │   │  │                     │   │
│  └──────────┘ └──────────┘ └──────────┘   │  │                     │   │
│                                            │  ├─────────────────────┤   │
│  Category Tabs:                            │  │ Subtotal:  ৳4,150   │   │
│  [All] [সার] [কীটনাশক] [পলিথিন] [রশি]     │  │ Discount:    -৳150   │   │
│                                            │  │ Total:     ৳4,000   │   │
│                                            │  ├─────────────────────┤   │
│                                            │  │ [Customer Name/Phone]│   │
│                                            │  │ [Payment: Cash ▼   ] │   │
│                                            │  │ [Paid: ______     ] │   │
│                                            │  │ Change: ৳0          │   │
│                                            │  │                     │   │
│                                            │  │ ┌─────────────────┐ │   │
│                                            │  │ │   💰 CHECKOUT    │ │   │
│                                            │  │ │   (F12)         │ │   │
│                                            │  │ └─────────────────┘ │   │
│                                            │  └─────────────────────┘   │
├────────────────────────────────────────────┴─────────────────────────────┤
│  F1:New Sale  F2:Hold  F3:Customer  F5:Discount  F8:Print  F12:Checkout │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## Keyboard Shortcuts

| Key     | Action                           |
| ------- | -------------------------------- |
| F1      | New Sale (clear cart)            |
| F2      | Hold/Park current sale           |
| F3      | Focus customer name field        |
| F5      | Apply discount                   |
| F8      | Print last receipt               |
| F12     | Checkout / Complete sale         |
| Enter   | Add selected product to cart     |
| ↑ ↓     | Navigate product list            |
| Tab     | Move between fields              |
| Escape  | Close modal / cancel             |
| Ctrl+F  | Focus search bar                 |

---

## Laravel Files to Create

### Models

```
app/Models/Sale.php
app/Models/SaleItem.php
```

### Migration

```
database/migrations/xxxx_create_sales_table.php
database/migrations/xxxx_create_sale_items_table.php
```

### Livewire Components

```
resources/views/app/⚡pos/page.blade.php         — POS Interface
resources/views/app/⚡pos/page.php
resources/views/app/⚡sales/page.blade.php        — Sales History
resources/views/app/⚡sales/page.php
resources/views/app/⚡sale-detail/page.blade.php  — Sale Detail / Receipt
resources/views/app/⚡sale-detail/page.php
```

### Routes

```php
Route::livewire('/app/pos/', 'app::pos')->name('app.pos');
Route::livewire('/app/sales/', 'app::sales')->name('app.sales');
Route::livewire('/app/sales/{sale}', 'app::sale-detail')->name('app.sales.detail');
```

---

## Features — POS Interface

### Product Search
- Type to search by: name, SKU, barcode
- Auto-focus on search bar when page loads
- Show matching products in real-time (Livewire search)
- Barcode scanner acts as keyboard input → auto-search → auto-add

### Product Grid
- Category-based tab filtering
- Product cards with: image, name, variant, price
- Click card or press Enter to add to cart

### Cart
- Item list with: name, variant, quantity, unit, price, subtotal
- Quantity adjustment: +/- buttons, direct input
- Unit selector (if product has multiple sale units)
- Per-item discount
- Remove item (×) button

### Checkout Section
- Subtotal (auto-calculated)
- Discount (flat amount or percentage)
- Grand Total
- Customer Name/Phone (optional for walk-in)
- Payment Method selector
- Paid Amount input
- Change calculation
- Due amount (if partial payment)

### Checkout Process
1. Validate stock availability for all items
2. Create `sales` record
3. Create `sale_items` records
4. For each item: create `stock_movement` (type: sale, direction: out)
5. Auto-print receipt (optional)
6. Clear cart, show success toast
7. Ready for next sale

---

## Features — Sales History Page

### Table

| Invoice # | Date | Customer | Items | Total | Payment | Status | Actions |
|-----------|------|----------|-------|-------|---------|--------|---------|

### Filters
- Date range
- Payment method
- Payment status
- Customer search
- Sale type (POS / Online)

### Actions
- View detail / receipt
- Void sale (with reason — reverse stock movements)
- Print receipt

---

## Receipt Design (Thermal Printer Format)

```
================================
       🌾 আমিন এগ্রো স্টোর
       Amin Agro Store
   মোবাইল: 01XXXXXXXXX
================================
Invoice: INV-2026-0042
Date: 09/03/2026 03:30 PM
Cashier: Admin
--------------------------------
Item        Qty   Price   Total
--------------------------------
Urea 50kg   2    1,200   2,400
Confidor     5      350   1,750
100ml
--------------------------------
Subtotal:              ৳4,150
Discount:               -৳150
================================
GRAND TOTAL:           ৳4,000
================================
Payment: Cash
Paid:                  ৳5,000
Change:                ৳1,000
================================
  Thank you! আবার আসবেন!
================================
```

---

## POS Layout (Livewire)

POS page uses a **separate layout** without admin sidebar:

```php
// In page.php for POS
use function Livewire\Volt\layout;
layout('layouts.pos');
```

### `layouts/pos.blade.php`
- Full screen, no sidebar
- Minimal header (logo + back to admin link)
- Maximized space for products and cart

---

## Sidebar Menu (in Admin)

```blade
<x-menu-sub title="Sales" icon="o-banknotes">
    <x-menu-item title="POS"            icon="o-computer-desktop" link="/app/pos" />
    <x-menu-item title="Sales History"   icon="o-receipt-percent"  link="/app/sales" />
</x-menu-sub>
```

---

## Permissions

```
pos.access          — Access POS interface
sales.view          — View sales history
sales.create        — Create sale (POS)
sales.void          — Void/cancel a sale
sales.print         — Print receipt
```

---

## Hold/Park Sale Feature

- Staff can "hold" an incomplete sale and start a new one
- Held sales stored temporarily (session or DB)
- Can resume held sale later
- Shows badge: "Held Sales (2)"

```sql
-- Optional: held_sales table or use sales with status='draft'
```
