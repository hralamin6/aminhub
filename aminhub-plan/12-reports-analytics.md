# 📈 Module 12: Reports & Analytics

## Overview

অ্যাডমিন সব ধরনের ব্যবসায়িক রিপোর্ট দেখতে পারবে — বিক্রি, ক্রয়, স্টক, প্রফিট, ট্রেন্ড।

---

## Report Types

| Report            | Description                              |
| ----------------- | ---------------------------------------- |
| Sales Report      | Daily / Weekly / Monthly sales data      |
| Purchase Report   | Supplier-wise, date-wise purchase data   |
| Stock Report      | Current stock value, movement history    |
| Profit Report     | Sales price − Purchase price             |
| Low Stock Report  | Products below minimum stock             |
| Customer Report   | Top customers, dues                      |
| Product Report    | Top selling, slow moving                 |
| Payment Report    | Method-wise, due collections             |
| Expiry Report     | Expiring batches within X days           |

---

## Livewire Components

```
resources/views/app/⚡reports/page.blade.php              — Reports Hub
resources/views/app/⚡reports/page.php
resources/views/app/⚡report-sales/page.blade.php         — Sales Report
resources/views/app/⚡report-sales/page.php
resources/views/app/⚡report-purchases/page.blade.php     — Purchase Report
resources/views/app/⚡report-purchases/page.php
resources/views/app/⚡report-stock/page.blade.php         — Stock Report
resources/views/app/⚡report-stock/page.php
resources/views/app/⚡report-profit/page.blade.php        — Profit Report
resources/views/app/⚡report-profit/page.php
resources/views/app/⚡report-low-stock/page.blade.php     — Low Stock Report
resources/views/app/⚡report-low-stock/page.php
```

### Routes

```php
Route::livewire('/app/reports/', 'app::reports')->name('app.reports');
Route::livewire('/app/reports/sales/', 'app::report-sales')->name('app.reports.sales');
Route::livewire('/app/reports/purchases/', 'app::report-purchases')->name('app.reports.purchases');
Route::livewire('/app/reports/stock/', 'app::report-stock')->name('app.reports.stock');
Route::livewire('/app/reports/profit/', 'app::report-profit')->name('app.reports.profit');
Route::livewire('/app/reports/low-stock/', 'app::report-low-stock')->name('app.reports.low-stock');
```

---

## Sales Report

### Filters
- Date range (today / this week / this month / custom)
- Sale type (POS / Online / All)
- Payment method
- Customer
- Product / Category

### Summary Cards
| Total Sales | Total Revenue | Avg Order Value | Items Sold |
|-------------|---------------|-----------------|------------|

### Chart
- Daily sales trend (line chart)
- Sales by category (pie chart)
- Sales by payment method (bar chart)

### Table
| Date | Invoice # | Customer | Items | Total | Payment | Type |
|------|-----------|----------|-------|-------|---------|------|

### Export
- PDF download
- Excel/CSV download

---

## Purchase Report

### Filters
- Date range
- Supplier
- Payment status

### Summary Cards
| Total Purchases | Total Paid | Total Due | Suppliers Used |
|-----------------|------------|-----------|----------------|

### Table
| Date | Invoice # | Supplier | Items | Total | Paid | Due |
|------|-----------|----------|-------|-------|------|-----|

---

## Stock Report

### Views
1. **Current Stock** — All product stock with value
2. **Stock Movement** — Movement history with filters
3. **Stock Value** — Total inventory value (stock × purchase price)

### Current Stock Table
| Product | Variant | Stock (base) | Stock (display) | Avg Cost | Value |
|---------|---------|-------------|----------------|----------|-------|

### Summary Cards
| Total Products | Total Stock Value | Low Stock Items | Out of Stock |
|---------------|-------------------|-----------------|-------------|

---

## Profit Report

### Calculation

```
Profit = Sale Price - Purchase Price (weighted average cost)

Gross Profit per item
= sale_price - weighted_avg_purchase_price

Gross Profit Total
= SUM(gross profit per item × quantity sold)

Net Profit
= Gross Profit - Discounts - Returns
```

### Filters
- Date range
- Product / Category
- Customer

### Summary
| Gross Revenue | Cost of Goods | Gross Profit | Margin % |
|--------------|---------------|-------------|----------|

### Table
| Product | Variant | Qty Sold | Revenue | Cost | Profit | Margin |
|---------|---------|----------|---------|------|--------|--------|

### Chart
- Monthly profit trend (bar chart)
- Profit by category (pie chart)

---

## Low Stock Report

### Table
| Product | Variant | Current Stock | Min Stock | Shortage | Last Restocked |
|---------|---------|-------------|-----------|----------|---------------|

### Highlight
- Red: Out of stock (0)
- Orange: Below minimum
- Yellow: Near minimum (within 20%)

---

## Reports Hub Page

Dashboard-style page with links to all reports + quick summary:

```
┌──────────────────────────────────────────────────┐
│ 📊 Reports Hub                                   │
├──────────┬──────────┬──────────┬────────────────┤
│ 💰 Sales  │ 🛒 Purchase│ 📦 Stock │ 📈 Profit     │
│ View →   │ View →   │ View →   │ View →        │
├──────────┴──────────┴──────────┴────────────────┤
│                                                  │
│ Quick Stats (Last 30 Days)                       │
│ ● Total Sales: ৳2,50,000                         │
│ ● Total Purchases: ৳1,80,000                    │
│ ● Gross Profit: ৳70,000 (28%)                   │
│ ● Low Stock Items: 12                            │
│                                                  │
└──────────────────────────────────────────────────┘
```

---

## ReportService

```php
class ReportService
{
    public function getSalesReport(array $filters): array
    public function getPurchaseReport(array $filters): array
    public function getStockReport(array $filters): array
    public function getProfitReport(array $filters): array
    public function getLowStockReport(): Collection
    public function getTopSellingProducts(int $limit = 10, ?Carbon $from, ?Carbon $to): Collection
    public function getTopCustomers(int $limit = 10): Collection
    public function getDailySalesTrend(Carbon $from, Carbon $to): array
    public function getCategoryWiseSales(Carbon $from, Carbon $to): array
}
```

---

## Sidebar Menu

```blade
<x-menu-sub title="Reports" icon="o-chart-bar">
    <x-menu-item title="Reports Hub"   icon="o-presentation-chart-bar" link="/app/reports" />
    <x-menu-item title="Sales"         icon="o-banknotes"       link="/app/reports/sales" />
    <x-menu-item title="Purchases"     icon="o-shopping-cart"    link="/app/reports/purchases" />
    <x-menu-item title="Stock"         icon="o-archive-box"     link="/app/reports/stock" />
    <x-menu-item title="Profit"        icon="o-arrow-trending-up" link="/app/reports/profit" />
    <x-menu-item title="Low Stock"     icon="o-exclamation-triangle" link="/app/reports/low-stock" />
</x-menu-sub>
```

---

## Permissions

```
reports.sales       — View sales reports
reports.purchases   — View purchase reports
reports.stock       — View stock reports
reports.profit      — View profit reports
reports.export      — Export reports to PDF/Excel
```

---

## Chart Library

Use a lightweight chart library for Livewire:

```
// Option 1: Chart.js via CDN (recommended)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

// Option 2: ApexCharts
// Option 3: Mary UI built-in chart component (if available)
```
