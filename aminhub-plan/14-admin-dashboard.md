# 🎯 Module 14: Admin Dashboard (Enhanced)

## Overview

বিদ্যমান ড্যাশবোর্ড আপডেট করে ব্যবসায়িক KPI, চার্ট এবং অ্যালার্ট যুক্ত করা।

---

## Dashboard Layout

```
┌──────────────────────────────────────────────────────────────────┐
│ 🌾 AminHub Dashboard                         [Today ▼] [≡ Menu] │
├──────────┬──────────┬──────────┬──────────┬──────────────────────┤
│ 💰 Today  │ 🛒 Orders │ 📦 Stock  │ 👥 Cust.  │                    │
│ Sales    │ Pending  │ Low/Alert│ New Today│                    │
│ ৳12,500  │ 8        │ 15       │ 3        │                    │
├──────────┴──────────┴──────────┴──────────┴──────────────────────┤
│                                                                  │
│ ┌──────────────────────────────┐ ┌──────────────────────────────┐│
│ │ Sales Trend (7 Days)         │ │ Sales by Category            ││
│ │                              │ │                              ││
│ │  █                           │ │     ╭──╮                      ││
│ │  █  █        █               │ │   ╭─┤সার├──╮                  ││
│ │  █  █  █     █  █            │ │   │ ╰──╯  │                  ││
│ │  █  █  █  █  █  █  █        │ │  কীটনাশক  পলিথিন             ││
│ │  ─────────────────           │ │                              ││
│ │  Mon Tue Wed Thu Fri Sat Sun │ │                              ││
│ └──────────────────────────────┘ └──────────────────────────────┘│
│                                                                  │
│ ┌──────────────────────────────┐ ┌──────────────────────────────┐│
│ │ ⚠️ Low Stock Alert            │ │ 📋 Recent Orders             ││
│ │                              │ │                              ││
│ │ 🔴 Urea — 10 kg (min: 50)    │ │ ORD-0042 — ৳4,250 pending   ││
│ │ 🟠 TSP  — 25 kg (min: 30)    │ │ ORD-0041 — ৳2,100 confirmed ││
│ │ 🔴 Confidor 100ml — 0        │ │ ORD-0040 — ৳850 delivered    ││
│ │ 🟠 DAP  — 15 kg (min: 20)    │ │ ORD-0039 — ৳3,500 shipped   ││
│ │            [View All →]      │ │            [View All →]      ││
│ └──────────────────────────────┘ └──────────────────────────────┘│
│                                                                  │
│ ┌──────────────────────────────┐ ┌──────────────────────────────┐│
│ │ 🏆 Top Selling Products       │ │ 💳 Payment Summary           ││
│ │                              │ │                              ││
│ │ 1. Urea 50kg ─── 45 sold    │ │ Cash:  ৳8,200               ││
│ │ 2. Confidor 100ml ── 38     │ │ bKash: ৳3,100               ││
│ │ 3. DAP 50kg ─── 32 sold     │ │ Nagad: ৳1,200               ││
│ │ 4. Rope 100m ─── 28 sold    │ │ Due:   ৳2,500               ││
│ │ 5. Polythene dz ── 25       │ │                              ││
│ └──────────────────────────────┘ └──────────────────────────────┘│
│                                                                  │
│ ┌──────────────────────────────────────────────────────────────┐ │
│ │ 🕐 Recent Activity                                           │ │
│ │                                                              │ │
│ │ • Admin created sale INV-2026-0042         — 2 min ago      │ │
│ │ • Admin confirmed order ORD-2026-0041      — 15 min ago     │ │
│ │ • Admin added 10 bags of Urea (purchase)   — 1 hour ago     │ │
│ │ • New customer রহিম registered             — 2 hours ago    │ │
│ └──────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

---

## Dashboard Components

### 1. KPI Cards (Top Row)

| Card            | Data Source                  | Color  |
| --------------- | --------------------------- | ------ |
| Today's Sales   | SUM(sales.grand_total) today | Green  |
| Pending Orders  | COUNT(orders.pending)        | Orange |
| Low Stock       | COUNT(low_stock_products)    | Red    |
| New Customers   | COUNT(customers) today       | Blue   |

### 2. Sales Trend Chart
- Last 7 days line/bar chart
- Shows daily revenue
- Compare to previous week (optional)

### 3. Category Pie Chart
- Sales by category (this month)
- Doughnut/pie chart

### 4. Low Stock Widget
- List of products below min_stock
- Color coded: red (out), orange (low), yellow (near)
- Link to reorder/purchase

### 5. Recent Orders Widget
- Last 5 orders with status badges
- Quick action: Confirm / Process

### 6. Top Selling Products
- Top 5 by quantity sold this month
- Progress bars showing relative performance

### 7. Payment Summary
- Today's payment breakdown by method
- Total due outstanding

### 8. Recent Activity Feed
- Last 5 activities from activity log
- Links to relevant pages

---

## Livewire Component Update

### Existing File
```
resources/views/app/⚡dashboard/page.blade.php
resources/views/app/⚡dashboard/page.php
```

### Data Properties
```php
// In page.php
state([
    'todaySales'     => fn() => $this->getTodaySales(),
    'pendingOrders'  => fn() => $this->getPendingOrders(),
    'lowStockCount'  => fn() => $this->getLowStockCount(),
    'newCustomers'   => fn() => $this->getNewCustomers(),
    'salesTrend'     => fn() => $this->getSalesTrend(),
    'categorySales'  => fn() => $this->getCategorySales(),
    'lowStockItems'  => fn() => $this->getLowStockItems(),
    'recentOrders'   => fn() => $this->getRecentOrders(),
    'topProducts'    => fn() => $this->getTopProducts(),
    'paymentSummary' => fn() => $this->getPaymentSummary(),
    'recentActivity' => fn() => $this->getRecentActivity(),
]);
```

---

## Date Range Selector

Dashboard supports different time periods:

```
[Today] [This Week] [This Month] [This Year] [Custom Range]
```

All widgets update based on selected period.

---

## Quick Actions

Dashboard should have quick action buttons:

```
[+ New Sale (POS)] [+ New Purchase] [+ New Product] [View Reports]
```

---

## Auto Refresh

Dashboard data refreshes:
- Every 60 seconds (Livewire polling) or
- Wire:poll.60s on specific widgets
- Pending order count updates in real-time via Pusher (existing infrastructure)

---

## Permissions

Dashboard widgets are permission-aware:
```php
// Only show sales widget if user can view sales
@can('sales.view')
    // Sales KPI card
@endcan

@can('orders.view')
    // Pending Orders card
@endcan

@can('inventory.view')
    // Low Stock card
@endcan
```

---

## Staff Dashboard (Simplified)

Staff role sees a simplified dashboard:
- Today's sales (own sales only)
- Quick POS access
- Low stock alerts
- No profit/financial data
