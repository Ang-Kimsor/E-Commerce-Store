<div align="center">
  <h1>📊 E-Commerce Store - Admin & SuperAdmin Portal</h1>
  <p>A modern, high-performance backoffice administration application for managing catalog items, stock auditing, order fulfillment, analytical reporting, and global site configurations. Built with Nuxt.js 4, Vue 3, Pinia (persisted state), TailwindCSS, Chart.js, and Lucide Icons.</p>
</div>

---

## Table of Contents 📑

- [Overview 📖](#overview-)
- [User Roles & Access Levels 🧑‍💻](#user-roles--access-levels-)
- [Key Features ✨](#key-features-)
- [Tech Stack & Architecture 🛠️](#tech-stack--architecture-️)
- [Prerequisites ✅](#prerequisites-)
- [Installation & Setup 💾](#installation--setup-)
- [Environment Variables (.env) ⚙️](#environment-variables-env-️)
- [Available NPM Scripts 📜](#available-npm-scripts-)
- [Application Pages & Modules 🖥️](#application-pages--modules-️)
  - [1. General & Authentication Pages](#1-general--authentication-pages)
  - [2. Admin Management Modules](#2-admin-management-modules)
  - [3. Superadmin-Exclusive Modules](#3-superadmin-exclusive-modules)
- [Folder Structure 📂](#folder-structure-)
- [License 📄](#license-)

---

## Overview 📖

The **Admin & SuperAdmin Portal** (`frontend-admin`) is a single-page application (SPA) optimized for fast load times and rapid data entry. It provides store managers and platform administrators with complete control over inventory, customer transactions, and platform configurations.

It communicates with the **Laravel 12 Backend REST API** via token-based authentication (Bearer tokens stored in secure Pinia persisted state).

---

## User Roles & Access Levels 🧑‍💻

The portal implements distinct access layers depending on the authenticated user's role:

### 1. Admin Role 📊
- Access real-time analytics dashboards (revenue, total orders, sales charts, and customer counts).
- Product catalog management: create/edit products, manage images, SKU, pricing, discounts, and category associations.
- Inventory auditing: record stock movements (Inbound purchases, Outbound dispatches, and Manual adjustments).
- Order fulfillment: review customer payment receipts, verify transactions, transition order statuses, add internal notes, and download A4 PDF invoices.
- Customer management: inspect customer profiles, delivery addresses, order histories, and block/unblock access.
- Generate and download Excel reports for sales, products, customers, and inventory.

### 2. Superadmin Role 👑
- All features available to the standard Admin role.
- **Administrator Management:** Provision new admin accounts, update credentials, change roles, promote users, block/unblock, and restore soft-deleted administrators.
- **Global Site Configuration:** Update site title, store descriptions, company logo, browser favicon, contact phone/email, physical address, and Google Maps embed link.
- **Maintenance Mode Switch:** Instantly toggle platform-wide maintenance mode with customized maintenance messages.

---

## Key Features ✨

- **Interactive Analytics Dashboard:** Real-time metrics powered by Chart.js and `vue-chartjs` displaying sales trajectories, revenue summaries, and order status distributions.
- **Order Fulfillment Pipeline:** Interactive order review drawer allowing operators to verify uploaded bank payment slips, update fulfillment status (`pending`, `confirmed`, `processing`, `shipped`, `delivered`, `completed`, `cancelled`), and print/download A4 PDF invoices.
- **Catalog & Inventory Control:** Fast product search, stock level indicators, category tagging, image upload handling, and stock movement logs.
- **Excel Spreadsheet Export:** Instant one-click spreadsheet downloads for catalog lists, customer activity, order records, and sales reports.
- **Responsive & Modern Design:** Tailored with TailwindCSS using an intuitive layout, collapsible navigation, accessible modals, and Lucide icons.
- **Pinia Persisted State:** Retains session authentication and admin preferences across browser refreshes.

---

## Tech Stack & Architecture 🛠️

| Technology | Purpose |
| :--- | :--- |
| **Nuxt.js 4.5+** | Full-stack Vue framework (configured in SPA mode) |
| **Vue 3.5+** | Progressive JavaScript framework (Composition API with `<script setup>`) |
| **Pinia 4.x** | State management library |
| **pinia-plugin-persistedstate** | Automatic local state persistence for auth tokens |
| **TailwindCSS 3.x** | Utility-first styling framework |
| **Chart.js 4.x & vue-chartjs** | Interactive charts and analytics visualization |
| **Lucide Vue** | Clean, consistent modern iconography |
| **Vite & Nitro** | High-speed frontend bundling and static deployment engine |

---

## Prerequisites ✅

Ensure you have the following installed on your local development machine:

- **Node.js:** v18.x or v20.x+ (LTS recommended)
- **Package Manager:** `npm` (bundled with Node) or `pnpm` / `yarn`
- **Backend API:** The Laravel backend (`http://localhost:8000`) should be running.

---

## Installation & Setup 💾

### 1. Navigate to the Admin Directory

```bash
cd frontend-admin
```

### 2. Install Node Dependencies

```bash
npm install
```

### 3. Setup Environment File

Create or modify your `.env` file in the root of `frontend-admin`:

```env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
NUXT_PUBLIC_TELEGRAM_BOT=YourTelegramBotUsername
NUXT_PUBLIC_SITE_NAME="Store Admin"
```

### 4. Launch the Development Server

```bash
npm run dev
```

The administration portal will be available at: **`http://localhost:3000`**

---

## Environment Variables (.env) ⚙️

| Variable | Description | Default / Example |
| :--- | :--- | :--- |
| `NUXT_PUBLIC_API_BASE` | URL of the backend REST API | `http://localhost:8000/api` |
| `NUXT_PUBLIC_TELEGRAM_BOT` | Telegram bot username (without `@`) | `MyStoreBot` |
| `NUXT_PUBLIC_SITE_NAME` | Portal title shown in browser tab and navbar | `"Store Admin"` |

---

## Available NPM Scripts 📜

| Command | Action |
| :--- | :--- |
| `npm run dev` | Starts the Nuxt local development server on port 3000 |
| `npm run build` | Compiles the production build bundle |
| `npm run generate` | Pre-renders static files into `.output/public` for cPanel / static hosting |
| `npm run preview` | Locally preview the production build output |
| `npm run postinstall`| Prepares Nuxt types and auto-imports (`nuxt prepare`) |

---

## Application Pages & Modules 🖥️

All admin portal routes are organized under `app/pages/`:

### 1. General & Authentication Pages

| Route | Component | Access Role | Description |
| :--- | :--- | :--- | :--- |
| `/login` | `login.vue` | Public | Administrator login with email & password |
| `/` | `index.vue` | Admin / Superadmin | Operations dashboard with real-time sales metrics, revenue, and charts |
| `/profile` | `profile/index.vue` | Admin / Superadmin | Operator personal profile, credentials, and avatar management |

### 2. Admin Management Modules

| Route | Component | Access Role | Description |
| :--- | :--- | :--- | :--- |
| `/products` | `products/index.vue` | Admin / Superadmin | Catalog products listing, search, category filter, and stock levels |
| `/products/[id]` | `products/[id]/index.vue` | Admin / Superadmin | Product detail view, edit info, images, and stock movements |
| `/categories` | `categories/index.vue` | Admin / Superadmin | Product category hierarchy, create/edit categories, and Excel export |
| `/orders` | `orders/index.vue` | Admin / Superadmin | Incoming customer orders, status filtering, and fulfillment processing |
| `/orders/[id]` | `orders/[id]/index.vue` | Admin / Superadmin | Order detail view, bank payment slip verification, and invoice PDF |
| `/orders/create` | `orders/create.vue` | Admin / Superadmin | Manual order creation for walk-in or offline sales |
| `/customers` | `customers/index.vue` | Admin / Superadmin | Customer directory, order counts, account blocking/unblocking |
| `/customers/create` | `customers/create.vue` | Admin / Superadmin | Manual customer account registration with delivery addresses |
| `/customers/[id]` | `customers/[id]/index.vue` | Admin / Superadmin | Customer details, address book inspection, and order histories |
| `/reports` | `reports/index.vue` | Admin / Superadmin | Reporting hub redirecting to dedicated analytics modules |
| `/reports/sales` | `reports/sales.vue` | Admin / Superadmin | Sales analytics, revenue breakdown, and Excel exports |
| `/reports/products` | `reports/products.vue` | Admin / Superadmin | Top-performing products, low stock alerts, and catalog reports |
| `/reports/customers` | `reports/customers.vue` | Admin / Superadmin | Customer growth, order frequencies, and spending metrics |
| `/reports/inventory` | `reports/inventory.vue` | Admin / Superadmin | Inventory movement logs, replenishment audit, and adjustments |

### 3. Superadmin-Exclusive Modules

| Route | Component | Access Role | Description |
| :--- | :--- | :--- | :--- |
| `/admins` | `admins/index.vue` | Superadmin Only | Manage administrator accounts: create admins, change roles, block/unblock, and restore soft-deleted staff |
| `/settings` | `settings/index.vue` | Superadmin Only | Global platform configuration: store branding, logo, favicon, SEO tags, contact details, and maintenance mode toggle |

---

## Folder Structure 📂

```text
frontend-admin/
├── app/
│   ├── assets/
│   │   └── css/tailwind.css       # TailwindCSS root style configuration
│   ├── components/                # Reusable admin UI components
│   │   ├── common/                # Badges, buttons, modal wrappers, tables
│   │   ├── layout/                # Admin navigation bar, sidebar, and layout containers
│   │   └── modules/               # Domain-specific components (orders, products, reports)
│   ├── pages/                     # Nuxt filesystem-based route pages
│   ├── stores/                    # Pinia state stores (auth, orders, products, settings)
│   ├── app.vue                    # Root application component
│   └── spa-loading-template.html  # Initial HTML template shown during SPA bootstrapping
├── public/                        # Static public assets (icons, fallback images)
├── nuxt.config.ts                 # Nuxt 4 configuration & Nitro preset
├── package.json                   # Dependencies and scripts
├── tailwind.config.js             # Tailwind design tokens and theme extensions
└── tsconfig.json                  # TypeScript compiler settings
```

---

## License 📄

This project is proprietary software developed for the E-Commerce Store platform. All rights reserved.
