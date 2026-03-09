# 🛍️ Module 08: Ecommerce Frontend

## Overview

কাস্টমাররা অনলাইনে পণ্য ব্রাউজ করতে পারবে, কার্টে যোগ করতে পারবে, এবং অর্ডার দিতে পারবে। এটি পাবলিক-ফেসিং ওয়েবসাইট।

---

## Page Structure

```
/                          → Homepage (existing, needs update)
/shop                      → Product Catalog
/shop/{category-slug}      → Category Products
/product/{product-slug}    → Single Product Page
/cart                      → Shopping Cart
/checkout                  → Checkout Page
/account                   → My Account (Module 07)
/account/orders            → My Orders
/account/addresses         → My Addresses
/order-tracking            → Track Order (public, by invoice #)
```

---

## Livewire Components

```
resources/views/web/⚡shop/page.blade.php             — Product Catalog
resources/views/web/⚡shop/page.php
resources/views/web/⚡product/page.blade.php           — Single Product
resources/views/web/⚡product/page.php
resources/views/web/⚡cart/page.blade.php              — Shopping Cart
resources/views/web/⚡cart/page.php
resources/views/web/⚡checkout/page.blade.php          — Checkout
resources/views/web/⚡checkout/page.php
resources/views/web/⚡order-tracking/page.blade.php    — Order Tracking
resources/views/web/⚡order-tracking/page.php
```

### Routes

```php
Route::livewire('/shop', 'web::shop')->name('web.shop');
Route::livewire('/shop/{slug}', 'web::shop')->name('web.shop.category');
Route::livewire('/product/{slug}', 'web::product')->name('web.product');
Route::livewire('/cart', 'web::cart')->name('web.cart');
Route::livewire('/order-tracking', 'web::order-tracking')->name('web.order-tracking');

Route::middleware('auth')->group(function () {
    Route::livewire('/checkout', 'web::checkout')->name('web.checkout');
});
```

---

## Ecommerce Layout

A separate layout for the public store:

```
resources/views/layouts/shop.blade.php
```

### Layout Structure

```
┌──────────────────────────────────────────────┐
│ Logo     [Shop] [About]   🔍  🛒(3) [Login] │
├──────────────────────────────────────────────┤
│                                              │
│              Page Content                    │
│                                              │
├──────────────────────────────────────────────┤
│ Footer: About | Contact | Social Links       │
└──────────────────────────────────────────────┘
```

---

## Features — Homepage (Updated)

### Hero Section
- Large banner with store branding
- Tagline: "কৃষি পণ্যের বিশ্বস্ত দোকান"
- CTA: "Shop Now" button

### Sections
1. **Featured Products** — Product cards grid
2. **Categories** — Category cards with images
3. **New Arrivals** — Recently added products
4. **Special Offers** — Discounted products (future)

---

## Features — Product Catalog (/shop)

### Layout
- Left: Filter sidebar (collapsible on mobile)
- Right: Product grid

### Filters
- Category (with sub-categories)
- Brand
- Price range (slider)
- Product type
- Sort by: Newest, Price Low→High, Price High→Low, Name A→Z

### Product Card

```
┌──────────────────┐
│    [Product       │
│     Image]        │
│                   │
│  Product Name     │
│  Brand Name       │
│  ৳350 / 100ml    │
│  ⭐ (future)      │
│  [Add to Cart]    │
└──────────────────┘
```

### Pagination
- Infinite scroll or "Load More" button
- 20 products per load

---

## Features — Single Product Page

### Layout

```
┌──────────────────────────────────────────────┐
│                                              │
│  [Product    ]  Product Name                 │
│  [Image      ]  Brand: Syngenta              │
│  [Gallery    ]  Category: Insecticide        │
│               ──────────────────             │
│  [thumb][thumb] ৳350 / 100ml                 │
│                 ৳1,200 / 500ml               │
│                 ──────────────────           │
│                 Variant: [100ml] [250ml] [500ml] │
│                 Quantity: [−] 1 [+]          │
│                                              │
│                 [🛒 Add to Cart]              │
│                                              │
│  ─────────────────────────────────────────   │
│  Description | Specifications                │
│  ─────────────────────────────────────────   │
│  Full product description here...            │
│                                              │
│  ─────────────────────────────────────────   │
│  Related Products                            │
│  [card] [card] [card] [card]                 │
└──────────────────────────────────────────────┘
```

### Features
- Image gallery with zoom
- Variant selection (changes price)
- Quantity selector
- Add to Cart (Livewire, no page reload)
- Description tab
- Related products (same category)
- Stock availability indicator: "In Stock" / "Low Stock" / "Out of Stock"

---

## Features — Cart Page

### Layout

```
┌──────────────────────────────────────────────────────┐
│ Shopping Cart (3 items)                              │
├──────────────────────────────────────────────────────┤
│ [img] Confidor 100ml    Qty: [−] 2 [+]    ৳700  [×]│
│ [img] Urea 50kg bag     Qty: [−] 1 [+]    ৳1,200[×]│
│ [img] Rope 100m roll    Qty: [−] 3 [+]    ৳600  [×]│
├──────────────────────────────────────────────────────┤
│                              Subtotal:     ৳2,500   │
│                              Delivery:     ৳---     │
│                              Total:        ৳2,500   │
│                                                      │
│                    [Continue Shopping] [Checkout →]   │
└──────────────────────────────────────────────────────┘
```

### Cart Storage
- **Guest:** Session-based cart (Livewire state or session)
- **Logged in:** Session + persistent cart in DB (optional)
- Cart merges on login

---

## Features — Checkout Page (Auth Required)

### Steps
1. **Delivery Address** — Select saved or add new
2. **Delivery Method** — Shop pickup / Courier
3. **Payment Method** — Cash on delivery / bKash / Nagad
4. **Order Review** — Confirm items, total
5. **Place Order**

### On Order Placement
1. Validate stock for all items
2. Create `orders` record (see Module 09)
3. Create `order_items` records
4. Reserve stock (`reserved_stock` on variants)
5. Clear cart
6. Send order confirmation notification
7. Redirect to order confirmation page

---

## Cart Service

```php
class CartService
{
    public function add(int $variantId, float $qty, int $unitId): void
    public function update(int $variantId, float $qty): void
    public function remove(int $variantId): void
    public function clear(): void
    public function getItems(): Collection
    public function getTotal(): float
    public function getItemCount(): int
    public function validateStock(): array  // Returns out-of-stock items
}
```

---

## SEO Meta Tags

```php
// Product page
<title>{{ $product->name }} - AminHub Agro Store</title>
<meta name="description" content="{{ Str::limit($product->description, 160) }}">
<meta property="og:image" content="{{ $product->primaryImage }}">
```

---

## Mobile Responsive

- Hamburger menu on mobile
- Full-width product grid (2 columns on mobile)
- Fixed bottom "Add to Cart" bar on product page
- Sticky cart icon with badge count

---

## Performance

- Lazy load product images
- Livewire pagination (no full page reload)
- Cache category tree
- Cache featured products (5 min)
