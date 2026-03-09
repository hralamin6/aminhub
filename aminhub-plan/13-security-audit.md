# 🔐 Module 13: Security & Audit System

## Overview

সিস্টেমের নিরাপত্তা — role-based access, activity logging, edit history, audit trail। বেশিরভাগ infrastructure ইতোমধ্যে আছে (Spatie Permission, Activity Logging)।

---

## Existing Infrastructure

| Feature                | Status  | Package/System                   |
| ---------------------- | ------- | -------------------------------- |
| Role & Permission      | ✅ Done  | Spatie Laravel Permission 6.x    |
| Activity Logging       | ✅ Done  | Custom `LogsActivity` trait      |
| Activity Feed          | ✅ Done  | `activities` table + Livewire    |
| User Management        | ✅ Done  | Admin panel                      |
| Session Management     | ✅ Done  | Database sessions                |

---

## New Permissions to Add

### Product Module
```
products.view, products.create, products.edit, products.delete
categories.view, categories.create, categories.edit, categories.delete
brands.view, brands.create, brands.edit, brands.delete
units.view, units.create, units.edit, units.delete
```

### Inventory Module
```
inventory.view, inventory.adjust, inventory.movements, inventory.batches
```

### Purchase Module
```
suppliers.view, suppliers.create, suppliers.edit, suppliers.delete
purchases.view, purchases.create, purchases.edit, purchases.delete, purchases.payment
purchase_returns.view, purchase_returns.create
```

### Sales & POS Module
```
pos.access
sales.view, sales.create, sales.void, sales.print
```

### Order Module
```
orders.view, orders.manage, orders.cancel, orders.return, orders.print
```

### Customer Module
```
customers.view, customers.create, customers.edit, customers.delete, customers.due
```

### Delivery Module
```
delivery.view, delivery.manage
```

### Payment Module
```
payments.view, payments.confirm, payments.refund, payments.collect
```

### Report Module
```
reports.sales, reports.purchases, reports.stock, reports.profit, reports.export
```

---

## Role Definitions

### Admin (Super Admin)
- All permissions
- System settings
- User management
- Full reports access

### Manager
- All product, purchase, sales, inventory permissions
- Reports (all)
- Customer management
- Order management
- Cannot manage users/roles/settings

### Staff (POS Operator)
```
pos.access
sales.view, sales.create, sales.print
products.view
inventory.view
customers.view, customers.create
```

### Customer (Ecommerce)
- No admin panel access
- Self-service: view orders, manage addresses, account settings

---

## Activity Logging (Enhanced)

The existing `LogsActivity` trait should be applied to new models:

```php
// Models that should log activities:
Product::class      → created, updated, deleted
Category::class     → created, updated, deleted
Brand::class        → created, updated, deleted
Purchase::class     → created, updated, received, payment added
Sale::class         → created, voided
Order::class        → created, status changed, cancelled
StockAdjustment::class → created
Payment::class      → recorded, confirmed, refunded
```

### Activity Log Fields
```
user: who performed the action
action: what they did (created, updated, deleted, etc.)
subject: what model was affected
properties: old values vs new values (for edits)
timestamp: when it happened
```

---

## Audit Trail for Critical Operations

### Price Change History
- When purchase_price or retail_price changes → log old and new values
- Accessible from product detail page

### Stock Adjustment Audit
- Every adjustment requires a reason
- Cannot be deleted (soft delete only)
- Linked to user who created it

### Payment Audit
- Who confirmed? When?
- Who refunded? Why?
- All payment changes logged

---

## Security Middleware

### Existing
```php
// Uses Spatie Permission middleware
Route::middleware('permission:products.view')
```

### New Middleware for API
```php
// For POS and ecommerce API routes
Route::middleware(['auth', 'throttle:60,1'])
```

---

## PermissionSeeder Update

```php
// database/seeders/PermissionSeeder.php — Add new permissions

$permissions = [
    // Products
    'products.view', 'products.create', 'products.edit', 'products.delete',
    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
    'brands.view', 'brands.create', 'brands.edit', 'brands.delete',
    'units.view', 'units.create', 'units.edit', 'units.delete',

    // Inventory
    'inventory.view', 'inventory.adjust', 'inventory.movements', 'inventory.batches',

    // Purchase
    'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
    'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete', 'purchases.payment',
    'purchase_returns.view', 'purchase_returns.create',

    // Sales
    'pos.access',
    'sales.view', 'sales.create', 'sales.void', 'sales.print',

    // Orders
    'orders.view', 'orders.manage', 'orders.cancel', 'orders.return', 'orders.print',

    // Customers
    'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.due',

    // Delivery
    'delivery.view', 'delivery.manage',

    // Payments
    'payments.view', 'payments.confirm', 'payments.refund', 'payments.collect',

    // Reports
    'reports.sales', 'reports.purchases', 'reports.stock', 'reports.profit', 'reports.export',
];

$roles = [
    'admin'   => $permissions,  // All permissions
    'manager' => array_filter($permissions, fn($p) => !str_starts_with($p, 'users.') && !str_starts_with($p, 'roles.')),
    'staff'   => ['pos.access', 'sales.view', 'sales.create', 'sales.print', 'products.view', 'inventory.view', 'customers.view', 'customers.create'],
    'customer' => [],
];
```

---

## Login Security

- Rate limiting on login (already via Laravel)
- Password policies (min 8 chars)
- Email verification (already implemented via MustVerifyEmail)
- Session timeout (configurable)

---

## Files to Modify

```
database/seeders/PermissionSeeder.php  — Add new permissions & roles
resources/views/layouts/app.blade.php  — Update sidebar with permission checks
```
