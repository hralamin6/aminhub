# 👤 Module 07: Customer System

## Overview

কাস্টমার সিস্টেমে দুই ধরনের কাস্টমার — **Walk-in** (POS থেকে) এবং **Registered** (ই-কমার্স থেকে রেজিস্ট্রেশন করে)।

---

## Database Schema

### `customers` Table

```sql
CREATE TABLE customers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NULL,              -- Linked to users table if registered
    name            VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NULL,
    email           VARCHAR(255) NULL,
    type            ENUM('walk_in', 'registered') DEFAULT 'walk_in',
    total_purchase  DECIMAL(14,2) DEFAULT 0,
    total_due       DECIMAL(14,2) DEFAULT 0,
    is_active       BOOLEAN DEFAULT true,
    note            TEXT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    INDEX (phone),
    INDEX (email),
    INDEX (user_id),
    INDEX (type)
);
```

### `customer_addresses` Table

```sql
CREATE TABLE customer_addresses (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     BIGINT UNSIGNED NOT NULL,
    label           VARCHAR(100) DEFAULT 'Home',       -- Home, Office, etc.
    full_name       VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NOT NULL,
    division_id     BIGINT UNSIGNED NULL,
    district_id     BIGINT UNSIGNED NULL,
    upazila_id      BIGINT UNSIGNED NULL,
    union_id        BIGINT UNSIGNED NULL,
    address_line    TEXT NOT NULL,
    postal_code     VARCHAR(20) NULL,
    is_default      BOOLEAN DEFAULT false,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (division_id) REFERENCES divisions(id) ON DELETE SET NULL,
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (upazila_id) REFERENCES upazilas(id) ON DELETE SET NULL,
    FOREIGN KEY (union_id) REFERENCES unions(id) ON DELETE SET NULL
);
```

> **Note:** Division, District, Upazila, Union tables already exist in the codebase!

---

## Laravel Files to Create

### Models

```
app/Models/Customer.php
app/Models/CustomerAddress.php
```

### Migrations

```
database/migrations/xxxx_create_customers_table.php
database/migrations/xxxx_create_customer_addresses_table.php
```

### Livewire Components (Admin)

```
resources/views/app/⚡customers/page.blade.php        — Customer List
resources/views/app/⚡customers/page.php
resources/views/app/⚡customer-detail/page.blade.php  — Customer Detail
resources/views/app/⚡customer-detail/page.php
```

### Livewire Components (Ecommerce — Customer Self-Service)

```
resources/views/web/⚡account/page.blade.php       — My Account
resources/views/web/⚡account/page.php
resources/views/web/⚡orders/page.blade.php        — My Orders
resources/views/web/⚡orders/page.php
resources/views/web/⚡addresses/page.blade.php     — My Addresses
resources/views/web/⚡addresses/page.php
```

### Routes

```php
// Admin routes
Route::livewire('/app/customers/', 'app::customers')->name('app.customers');
Route::livewire('/app/customers/{customer}', 'app::customer-detail')->name('app.customers.detail');

// Customer-facing routes
Route::livewire('/account/', 'web::account')->name('web.account');
Route::livewire('/account/orders/', 'web::orders')->name('web.orders');
Route::livewire('/account/addresses/', 'web::addresses')->name('web.addresses');
```

---

## Features — Admin Customer List

### Table

| Name | Phone | Email | Type | Total Purchase | Due | Status | Actions |
|------|-------|-------|------|---------------|-----|--------|---------|

### Filters
- Type (walk_in / registered)
- Has due
- Search by name/phone

### Actions
- View detail → purchase history, payment history, due balance
- Edit customer info
- Create POS customer manually

---

## Features — Customer Detail Page

### Cards (Top Row)
- Total Purchases (count + amount)
- Total Paid
- Total Due
- Last Purchase Date

### Tabs
1. **Purchase History** — All sales linked to this customer
2. **Payments** — All payments made by customer
3. **Addresses** — Saved addresses
4. **Activity** — Order status changes, returns

---

## Features — Customer Self-Service (Ecommerce)

### My Account Page
- Name, email, phone
- Avatar upload
- Change password

### My Orders Page
- Order list with status badges
- Order detail with items, tracking info

### My Addresses Page
- Add/edit/delete addresses
- Set default address
- Uses existing Division/District/Upazila/Union dropdowns (cascading)

---

## Customer Registration Flow

```
Ecommerce Homepage
  → Register Button
    → Registration Form (name, email, phone, password)
      → Email verification (optional, configurable)
        → Customer record auto-created
          → Redirect to account page
```

### Auto-create Customer record on Registration

```php
// In User observer or event listener
public function created(User $user)
{
    if ($user->hasRole('customer')) {
        Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'type' => 'registered',
        ]);
    }
}
```

---

## POS Walk-in Customer

- POS-এ কাস্টমারের নাম/ফোন দেওয়া যাবে (optional)
- যদি ফোন নম্বর match করে — existing customer link হবে
- নতুন হলে auto-create হবে type='walk_in'
- Walk-in customer → due tracking possible

---

## Customer Due Tracking

```
Total Due = SUM(sale.grand_total) - SUM(payments received)
```

### Due Payment Modal (Admin)
- Select customer
- Enter payment amount
- Payment method
- Reference

---

## Permissions

```
customers.view       — View customer list
customers.create     — Create customer
customers.edit       — Edit customer
customers.delete     — Delete customer
customers.due        — View/manage dues
```

---

## Sidebar Menu

```blade
<x-menu-item title="Customers" icon="o-user-group" link="/app/customers" />
```
