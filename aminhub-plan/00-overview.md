# 🌾 AminHub — Agro Retail Management System

## Project Blueprint & Master Plan

---

## 📋 Project Summary

**Project Name:** AminHub  
**Type:** Agro Retail ERP + POS + Ecommerce  
**Domain:** Agricultural Input Retail Store Management

একটি লোকাল কৃষি দোকানের পূর্ণাঙ্গ ম্যানেজমেন্ট সিস্টেম — যেখানে POS বিক্রি, ইনভেন্টরি, ক্রয়, অনলাইন ই-কমার্স, ডেলিভারি এবং রিপোর্টিং — সব এক প্ল্যাটফর্মে।

---

## 🏗️ Existing Tech Stack (Current Codebase)

| Layer          | Technology                                          |
| -------------- | --------------------------------------------------- |
| **Framework**  | Laravel 12                                          |
| **Frontend**   | Livewire 4 (Functional Components)                  |
| **UI Kit**     | Mary UI 2.x + DaisyUI 5.x                          |
| **CSS**        | Tailwind CSS 4.x                                    |
| **Auth**       | Laravel built-in + Socialite                        |
| **Roles**      | Spatie Laravel Permission 6.x                       |
| **Media**      | Spatie Media Library 11.x                           |
| **Backup**     | Spatie Laravel Backup 9.x                           |
| **Realtime**   | Pusher + Laravel Echo                               |
| **PWA**        | erag/laravel-pwa                                    |
| **Build**      | Vite 7 + laravel-vite-plugin                        |
| **Database**   | MySQL                                               |
| **Monitoring** | Laravel Pulse                                       |

---

## 🧱 Existing Modules (Already Built)

These modules are already functional in the codebase:

| Module               | Status | Route                   |
| -------------------- | ------ | ----------------------- |
| Dashboard            | ✅ Done | `/app/`                 |
| User Profile         | ✅ Done | `/app/profile/`         |
| Settings             | ✅ Done | `/app/settings/`        |
| Roles & Permissions  | ✅ Done | `/app/roles/`           |
| User Management      | ✅ Done | `/app/users/`           |
| Backups              | ✅ Done | `/app/backups/`         |
| Translations (i18n)  | ✅ Done | `/app/translate/`       |
| Pages (CMS)          | ✅ Done | `/app/pages/`           |
| Notifications        | ✅ Done | `/app/notifications/`   |
| Activity Feed        | ✅ Done | `/app/activities/feed/` |
| My Activities        | ✅ Done | `/app/activities/my/`   |
| Chat System          | ✅ Done | `/app/chat/`            |
| AI Chat              | ✅ Done | `/app/ai-chat/`         |
| Web Homepage         | ✅ Done | `/`                     |
| Dynamic Pages        | ✅ Done | `/{slug}`               |

---

## 🆕 New Modules to Build

These modules need to be built for the Agro Retail ERP system:

| #  | Module                  | Priority  | Plan File                        |
| -- | ----------------------- | --------- | -------------------------------- |
| 1  | Product Management      | 🔴 High   | `01-product-management.md`       |
| 2  | Category & Brand System | 🔴 High   | `02-category-brand.md`           |
| 3  | Unit Conversion System  | 🔴 High   | `03-unit-system.md`              |
| 4  | Inventory Management    | 🔴 High   | `04-inventory-management.md`     |
| 5  | Purchase Management     | 🔴 High   | `05-purchase-management.md`      |
| 6  | POS System              | 🔴 High   | `06-pos-system.md`               |
| 7  | Customer System         | 🟡 Medium | `07-customer-system.md`          |
| 8  | Ecommerce Frontend      | 🟡 Medium | `08-ecommerce.md`                |
| 9  | Order Management        | 🟡 Medium | `09-order-management.md`         |
| 10 | Delivery Management     | 🟡 Medium | `10-delivery-management.md`      |
| 11 | Payment System          | 🟡 Medium | `11-payment-system.md`           |
| 12 | Reports & Analytics     | 🟢 Low    | `12-reports-analytics.md`        |
| 13 | Security & Audit        | 🟢 Low    | `13-security-audit.md`           |
| 14 | Admin Dashboard (New)   | 🟢 Low    | `14-admin-dashboard.md`          |

---

## 👥 User Roles

| Role       | Access Level                                         |
| ---------- | ---------------------------------------------------- |
| **Admin**  | পুরো সিস্টেম — products, purchase, reports, settings |
| **Staff**  | POS sale, stock view, limited admin access            |
| **Customer** | Ecommerce website — browse, cart, orders            |

---

## 🗂️ System Architecture

```
┌─────────────────────────────────────────────────┐
│                   AminHub                        │
├──────────┬──────────┬──────────┬────────────────┤
│  Admin   │   POS    │  Ecomm   │   Public Web   │
│  Panel   │ Interface│  Store   │   (Homepage)   │
├──────────┴──────────┴──────────┴────────────────┤
│              Shared Business Logic               │
│  (Products, Inventory, Orders, Payments, etc.)   │
├─────────────────────────────────────────────────┤
│           Laravel 12 + Livewire 4               │
│           Mary UI + DaisyUI + Tailwind          │
├─────────────────────────────────────────────────┤
│                    MySQL                         │
└─────────────────────────────────────────────────┘
```

---

## 📦 Products Sold

| Category     | Type       | Units              |
| ------------ | ---------- | ------------------ |
| কিটনাশক      | liquid     | ml, liter          |
| সার          | powder/bag | kg, bag            |
| পলিথিন       | packaged   | piece, roll        |
| রশি          | solid      | meter, roll, piece |
| বীজ          | packaged   | kg, packet         |

---

## 🔄 Development Phases

### Phase 1 — Core Backend (Weeks 1–3)
- Product Management (categories, brands, products, variants, units)
- Unit Conversion System
- Inventory Management (stock movements, ledger)
- Purchase Management (suppliers, invoices)

### Phase 2 — Sales System (Weeks 4–5)
- POS Interface
- Sales Management
- Receipt Printing

### Phase 3 — Ecommerce (Weeks 6–8)
- Customer registration/login
- Product catalog frontend
- Cart & Checkout
- Order Management

### Phase 4 — Delivery & Payment (Weeks 9–10)
- Delivery system
- Payment tracking
- Order workflow

### Phase 5 — Reports & Polish (Weeks 11–12)
- Sales/Purchase/Stock/Profit reports
- Admin dashboard with analytics
- Low stock alerts
- Security & audit logging

---

## 📁 Plan Files Index

Each module has its own detailed specification file:

```
aminhub-plan/
├── 00-overview.md              ← You are here
├── 01-product-management.md    ← Products, Variants
├── 02-category-brand.md        ← Categories, Brands
├── 03-unit-system.md           ← Unit Conversion (Critical)
├── 04-inventory-management.md  ← Stock, Movements, Batches
├── 05-purchase-management.md   ← Suppliers, Purchase Orders
├── 06-pos-system.md            ← POS Interface
├── 07-customer-system.md       ← Customer Accounts
├── 08-ecommerce.md             ← Online Store Frontend
├── 09-order-management.md      ← Orders, Workflow
├── 10-delivery-management.md   ← Delivery & Courier
├── 11-payment-system.md        ← Payment Methods
├── 12-reports-analytics.md     ← Reports & Dashboards
├── 13-security-audit.md        ← Security, Permissions, Logs
└── 14-admin-dashboard.md       ← Admin Analytics Dashboard
```
