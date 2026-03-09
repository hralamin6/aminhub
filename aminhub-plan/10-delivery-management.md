# 🚚 Module 10: Delivery Management

## Overview

ডেলিভারি সিস্টেম — দোকানের নিজস্ব ডেলিভারি এবং কুরিয়ার সার্ভিস এর মাধ্যমে পণ্য পৌঁছানো।

---

## Delivery Methods

| Method        | Description                            |
| ------------- | -------------------------------------- |
| shop_delivery | দোকানের নিজস্ব ডেলিভারি (local area) |
| courier       | কুরিয়ার সার্ভিসের মাধ্যমে           |
| pickup        | কাস্টমার নিজে দোকান থেকে নেবে         |

---

## Database Schema

### `delivery_zones` Table

```sql
CREATE TABLE delivery_zones (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,             -- "Gazipur City", "Tongi"
    district_id     BIGINT UNSIGNED NULL,
    upazila_id      BIGINT UNSIGNED NULL,
    delivery_charge DECIMAL(8,2) NOT NULL DEFAULT 0,
    is_active       BOOLEAN DEFAULT true,
    estimated_days  INT DEFAULT 1,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE SET NULL,
    FOREIGN KEY (upazila_id) REFERENCES upazilas(id) ON DELETE SET NULL
);
```

### `couriers` Table

```sql
CREATE TABLE couriers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,            -- "Pathao", "Steadfast", "Redx"
    phone           VARCHAR(20) NULL,
    website         VARCHAR(500) NULL,
    default_charge  DECIMAL(8,2) DEFAULT 0,
    is_active       BOOLEAN DEFAULT true,
    note            TEXT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP
);
```

### Delivery fields on `orders` table (already included in Module 09)

```sql
delivery_method     ENUM('shop_delivery', 'courier', 'pickup')
courier_name        VARCHAR(255) NULL
tracking_number     VARCHAR(255) NULL
delivery_date       DATE NULL
delivery_charge     DECIMAL(12,2) DEFAULT 0
```

---

## Laravel Files to Create

### Models

```
app/Models/DeliveryZone.php
app/Models/Courier.php
```

### Migrations

```
database/migrations/xxxx_create_delivery_zones_table.php
database/migrations/xxxx_create_couriers_table.php
```

### Livewire Components

```
resources/views/app/⚡delivery-zones/page.blade.php   — Delivery Zones
resources/views/app/⚡delivery-zones/page.php
resources/views/app/⚡couriers/page.blade.php          — Courier List
resources/views/app/⚡couriers/page.php
```

### Routes

```php
Route::livewire('/app/delivery-zones/', 'app::delivery-zones')->name('app.delivery-zones');
Route::livewire('/app/couriers/', 'app::couriers')->name('app.couriers');
```

---

## Features — Delivery Zones Page

### Table

| Zone Name | District | Upazila | Charge | Est. Days | Status | Actions |
|-----------|----------|---------|--------|-----------|--------|---------|

### Form (Modal)
- Zone Name
- District (cascading dropdown)
- Upazila (cascading dropdown)
- Delivery Charge (৳)
- Estimated Delivery Days
- Active toggle

---

## Features — Couriers Page

### Table

| Name | Phone | Default Charge | Status | Actions |
|------|-------|---------------|--------|---------|

### Form (Modal)
- Courier Name (Pathao, Steadfast, Redx, Sundarban, etc.)
- Phone
- Website
- Default Charge
- Note
- Active toggle

---

## Delivery Charge Calculation (at Checkout)

```php
class DeliveryService
{
    public function calculateCharge(string $method, ?int $districtId, ?int $upazilaId): float
    {
        if ($method === 'pickup') {
            return 0;
        }

        if ($method === 'shop_delivery') {
            $zone = DeliveryZone::where('district_id', $districtId)
                ->where('upazila_id', $upazilaId)
                ->where('is_active', true)
                ->first();

            return $zone?->delivery_charge ?? 0;
        }

        if ($method === 'courier') {
            // Use default courier charge or zone-based charge
            return $this->getCourierCharge($districtId);
        }

        return 0;
    }

    public function getEstimatedDays(string $method, ?int $districtId): int
    {
        // ...
    }
}
```

---

## Tracking Flow

```
Admin assigns tracking number (when status = shipped)
  → Order detail page shows tracking info
  → Customer sees tracking on "My Orders"
  → Public tracking page (/order-tracking) → enter order # + phone
```

---

## Sidebar Menu

```blade
<x-menu-sub title="Delivery" icon="o-truck">
    <x-menu-item title="Delivery Zones" icon="o-map-pin" link="/app/delivery-zones" />
    <x-menu-item title="Couriers"       icon="o-paper-airplane" link="/app/couriers" />
</x-menu-sub>
```

---

## Permissions

```
delivery.view       — View zones & couriers
delivery.manage     — Create/edit/delete zones & couriers
```

---

## Default Couriers (Seed Data)

```php
$couriers = [
    ['name' => 'Pathao Courier',     'default_charge' => 60],
    ['name' => 'Steadfast Courier',  'default_charge' => 70],
    ['name' => 'RedX',               'default_charge' => 65],
    ['name' => 'Sundarban Courier',  'default_charge' => 80],
    ['name' => 'SA Paribahan',       'default_charge' => 100],
    ['name' => 'JEFY Express',       'default_charge' => 55],
];
```
