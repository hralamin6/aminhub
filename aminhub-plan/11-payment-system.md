# 💳 Module 11: Payment System

## Overview

পেমেন্ট সিস্টেম — Phase 1 এ manual confirmation, পরবর্তীতে automated payment gateway integration।

---

## Supported Payment Methods

| Method | Type   | Phase 1      | Phase 2 (Future)       |
| ------ | ------ | ------------ | ---------------------- |
| Cash   | POS    | ✅ Automatic  | ✅ Same                 |
| COD    | Online | ✅ Manual     | ✅ Same                 |
| bKash  | Both   | ✅ Manual     | 🔄 API integration     |
| Nagad  | Both   | ✅ Manual     | 🔄 API integration     |
| Card   | Online | ❌ Not now    | 🔄 SSLCommerz/Stripe   |

---

## Database Schema

### `payments` Table (Unified for all payment types)

```sql
CREATE TABLE payments (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payable_type    VARCHAR(100) NOT NULL,             -- 'App\Models\Sale', 'App\Models\Order', 'App\Models\Purchase'
    payable_id      BIGINT UNSIGNED NOT NULL,
    amount          DECIMAL(12,2) NOT NULL,
    payment_method  ENUM('cash', 'bkash', 'nagad', 'card', 'bank_transfer', 'check', 'other') NOT NULL,
    transaction_id  VARCHAR(255) NULL,                 -- bKash/Nagad TrxID
    payment_date    DATE NOT NULL,
    status          ENUM('pending', 'confirmed', 'failed', 'refunded') DEFAULT 'confirmed',
    reference       VARCHAR(255) NULL,
    note            TEXT NULL,
    confirmed_by    BIGINT UNSIGNED NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,

    FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (payable_type, payable_id),
    INDEX (payment_method),
    INDEX (transaction_id),
    INDEX (payment_date)
);
```

> **Note:** Using polymorphic relationship (`payable_type` + `payable_id`) so the same payments table works for Sales, Orders, and Purchases.

---

## Laravel Files to Create

### Model

```
app/Models/Payment.php
```

### Migration

```
database/migrations/xxxx_create_payments_table.php
```

### Service

```
app/Services/PaymentService.php
```

---

## Payment Flow — POS Sale

```
1. Cashier enters products
2. Customer pays cash / bKash / Nagad
3. Payment recorded immediately as "confirmed"
4. Receipt generated with payment info
```

---

## Payment Flow — Online Order (Phase 1: Manual)

```
1. Customer places order and selects payment method
2. If COD → payment_status = "unpaid", confirmed on delivery
3. If bKash/Nagad →
   a. Show payment instructions (number to send to)
   b. Customer sends money manually
   c. Customer enters TrxID in order
   d. Admin verifies and confirms payment
   e. payment_status = "paid"
```

### bKash Manual Payment UI (Customer Side)

```
┌──────────────────────────────────┐
│ 📱 bKash Payment                 │
│                                  │
│ Send ৳4,250 to:                  │
│ 01XXXXXXXXX (Personal)           │
│                                  │
│ After sending, enter your        │
│ transaction ID below:            │
│                                  │
│ TrxID: [___________________]    │
│                                  │
│ [Submit Payment Info]            │
└──────────────────────────────────┘
```

### Admin Confirmation UI

```
┌──────────────────────────────────┐
│ Payment Verification             │
│                                  │
│ Order: ORD-2026-0042             │
│ Amount: ৳4,250                   │
│ Method: bKash                    │
│ Customer TrxID: ABC123XYZ        │
│                                  │
│ [✅ Confirm] [❌ Reject]          │
└──────────────────────────────────┘
```

---

## PaymentService

```php
class PaymentService
{
    /**
     * Record a payment (POS cash, admin confirmation, etc.)
     */
    public function recordPayment(
        Model $payable,              // Sale, Order, or Purchase
        float $amount,
        string $method,
        ?string $transactionId = null,
        ?string $note = null
    ): Payment

    /**
     * Confirm a pending payment (admin action for online orders)
     */
    public function confirmPayment(Payment $payment, int $confirmedBy): void

    /**
     * Reject a payment
     */
    public function rejectPayment(Payment $payment, string $reason): void

    /**
     * Refund a payment (for returns/cancellations)
     */
    public function refundPayment(Payment $payment, float $amount, string $reason): void

    /**
     * Get total paid amount for a payable
     */
    public function getTotalPaid(Model $payable): float

    /**
     * Get payment history for a payable
     */
    public function getPaymentHistory(Model $payable): Collection
}
```

---

## Payment Settings (Admin)

Stored in `settings` table:

```php
$paymentSettings = [
    'bkash_number'          => '01XXXXXXXXX',
    'bkash_type'            => 'personal',       // personal / merchant
    'nagad_number'          => '01XXXXXXXXX',
    'nagad_type'            => 'personal',
    'cod_enabled'           => true,
    'bkash_enabled'         => true,
    'nagad_enabled'         => true,
    'card_enabled'          => false,
    'min_order_amount'      => 0,
];
```

---

## Customer Due Management (POS)

POS-এ কাস্টমার বাকি রাখতে পারবে:

```
Sale Total: ৳5,000
Paid: ৳3,000
Due: ৳2,000

→ payment_status = 'partial'
→ Customer's total_due increases
```

### Due Collection
Admin can collect due later:

```
Select Customer → See total due → Record payment → Due reduces
```

---

## Permissions

```
payments.view       — View payments
payments.confirm    — Confirm pending payments
payments.refund     — Process refunds
payments.collect    — Collect customer dues
```

---

## Sidebar Menu

Payments integrated into Sales and Orders sections (no separate menu needed).

Optional: Add a "Due Payments" page:

```blade
<x-menu-item title="Due Collections" icon="o-banknotes" link="/app/due-payments" />
```

---

## Future Phase 2: Payment Gateway

When ready to integrate bKash/Nagad API:

### bKash Integration (Sandbox → Production)
```
composer require karim007/laravel-bkash
```

### SSLCommerz Integration (for cards)
```
composer require sslcommerz/laravel-sslcommerz
```

The polymorphic `payments` table design supports this transition seamlessly.
