<div align="center">
  <h1>🛍️ E-Commerce Store - Customer Storefront</h1>
  <p>A fast, responsive, mobile-first customer storefront designed for desktop browsers, mobile devices, and seamless embedding inside Telegram WebApp / Mini Apps. Built with Nuxt.js 4, Vue 3, Pinia (persisted state), TailwindCSS, and Lucide Icons.</p>
</div>

---

## Table of Contents 📑

- [Overview 📖](#overview-)
- [Key Features ✨](#key-features-)
- [Telegram WebApp & Mini App Ready 📱](#telegram-webapp--mini-app-ready-)
- [Tech Stack & Architecture 🛠️](#tech-stack--architecture-️)
- [Prerequisites ✅](#prerequisites-)
- [Installation & Setup 💾](#installation--setup-)
- [Environment Variables (.env) ⚙️](#environment-variables-env-️)
- [Available NPM Scripts 📜](#available-npm-scripts-)
- [Application Pages & Modules 🖥️](#application-pages--modules-️)
- [Customer Workflows 🛒](#customer-workflows-)
  - [1. Authentication & OTP Verification](#1-authentication--otp-verification)
  - [2. Cart & Checkout Process](#2-cart--checkout-process)
  - [3. Order Tracking & Invoicing](#3-order-tracking--invoicing)
- [Folder Structure 📂](#folder-structure-)
- [Build & Deployment 🚀](#build--deployment-)
- [License 📄](#license-)

---

## Overview 📖

The **Customer Storefront** (`frontend-customer`) provides an intuitive, friction-free shopping experience for end-users. Customers can effortlessly browse products, filter by categories, configure delivery destinations, checkout with bank transfer receipts or cash on delivery, and track real-time fulfillment updates.

Designed from the ground up to support both standalone web browsers and embedded Telegram Mini Apps, it features custom viewport handling and proper `frame-ancestors` CSP rules.

---

## Key Features ✨

- **Modern Product Catalog:** Dynamic category filtering, instant keyword search, promotional discount calculations, and high-density responsive product cards.
- **Persistent Shopping Cart:** Powered by Pinia with local storage persistence, retaining selected items, quantities, and price calculations across sessions.
- **Flexible Checkout & Payment:**
  - Bank transfer payment with drag-and-drop payment slip/receipt image upload.
  - Cash on Delivery (COD) payment option.
  - Multi-address selection with instant default address assignment.
- **Real-Time Order Tracking:** Detailed timeline for order progress (`pending` ➔ `confirmed` ➔ `processing` ➔ `shipped` ➔ `delivered` ➔ `completed`).
- **A4 PDF Tax Invoices:** Direct in-browser download of officially formatted PDF tax invoices for past orders.
- **Passwordless & OTP Authentication Suite:** Secure OTP-driven email flows for registration, login, forgotten passwords, email changes, and account deletion.
- **Customer Address Book:** Multiple shipping address management with tags (Home, Work, etc.) and primary address designation.
- **Maintenance Mode Detection:** Automatically diverts customers to an informative maintenance screen if the backend reports maintenance mode enabled.

---

## Telegram WebApp & Mini App Ready 📱

The storefront is fully configured to operate as a **Telegram Mini App (TMA)**:
- **Frame-Ancestors Security Policy:** `nuxt.config.ts` includes `frame-ancestors 'self' https://web.telegram.org https://telegram.org` allowing safe iframe embedding within Telegram clients.
- **Mobile Viewport Optimization:** Pre-configured `viewport` settings prevent unwanted mobile zooming and provide a native application feel.
- **Bot Integration:** Configured with `NUXT_PUBLIC_TELEGRAM_BOT` to connect store actions with Telegram bot notifications.

---

## Tech Stack & Architecture 🛠️

| Technology | Purpose |
| :--- | :--- |
| **Nuxt.js 4.5+** | Progressive Vue framework configured for client-side SPA rendering |
| **Vue 3.5+** | Frontend UI framework utilizing Composition API (`<script setup>`) |
| **Pinia 4.x** | Centralized reactive state management |
| **pinia-plugin-persistedstate** | Automatic persistent caching for cart, user auth, and settings |
| **TailwindCSS 3.x** | Utility-first responsive CSS styling |
| **Lucide Vue** | Clean modern icons for shopping carts, user profiles, and badges |
| **Vite & Nitro** | Next-generation build tooling and static compilation engine |

---

## Prerequisites ✅

Make sure the following prerequisites are met before starting:

- **Node.js:** v18.x or v20.x+ (LTS recommended)
- **Package Manager:** `npm` (included with Node.js) or `pnpm` / `yarn`
- **Backend API:** The Laravel backend (`http://localhost:8000`) should be running.

---

## Installation & Setup 💾

### 1. Navigate to the Customer Frontend Directory

```bash
cd frontend-customer
```

### 2. Install Node Dependencies

```bash
npm install
```

### 3. Setup Environment File

Create or verify the `.env` file in the root of `frontend-customer`:

```env
NUXT_PUBLIC_API_BASE=http://localhost:8000/api
NUXT_PUBLIC_TELEGRAM_BOT=YourTelegramBotUsername
NUXT_PUBLIC_SITE_NAME="My Store"
```

### 4. Launch the Development Server

```bash
npm run dev
```

The customer storefront will run on: **`http://localhost:3001`**

---

## Environment Variables (.env) ⚙️

| Variable | Description | Default / Example |
| :--- | :--- | :--- |
| `NUXT_PUBLIC_API_BASE` | URL of the backend REST API endpoint | `http://localhost:8000/api` |
| `NUXT_PUBLIC_TELEGRAM_BOT` | Telegram bot username (without `@`) | `MyStoreBot` |
| `NUXT_PUBLIC_SITE_NAME` | Storefront title displayed across page headers | `"My Store"` |

---

## Available NPM Scripts 📜

| Command | Action |
| :--- | :--- |
| `npm run dev` | Runs the development server on port 3001 |
| `npm run build` | Builds the production bundle |
| `npm run generate` | Pre-renders static files into `.output/public` for cPanel / static hosting |
| `npm run preview` | Previews the generated production build locally |
| `npm run postinstall`| Generates Nuxt auto-imports and type definitions (`nuxt prepare`) |

---

## Application Pages & Modules 🖥️

All storefront routes are organized under `app/pages/`:

| Route | Component | Description |
| :--- | :--- | :--- |
| `/` | `index.vue` | Main storefront home: banner, categories, product catalog |
| `/products/[id]` | `products/[id].vue` | Product detailed view: image gallery, price, add to cart |
| `/cart` | `cart.vue` | Shopping cart drawer, items review, checkout dialog |
| `/orders` | `orders.vue` | Order history list, status badges, PDF invoice links |
| `/addresses` | `addresses.vue` | Delivery address management (add, edit, set default) |
| `/profile` | `profile/index.vue` | User details, avatar upload, password & email update |
| `/login` | `login.vue` | Customer login with email OTP code |
| `/register` | `register.vue` | New customer account creation with OTP verification |
| `/verify-otp` | `verify-otp.vue` | Reusable 6-digit OTP verification screen |
| `/forgot-password` | `forgot-password.vue`| Request password reset code via email |
| `/reset-password` | `reset-password.vue` | Submit new password after OTP verification |
| `/maintenance` | `maintenance.vue` | Maintenance mode broadcast display |

---

## Customer Workflows 🛒

### 1. Authentication & OTP Verification
- Customers enter their email to request a 6-digit OTP.
- The backend sends the verification code with a cooldown timer.
- On successful validation, a Sanctum token is returned and securely stored in Pinia.

### 2. Cart & Checkout Process
- Customers add products to the cart; state is preserved even if the page refreshes.
- During checkout, the customer selects or adds a delivery address.
- Customers choose either **Cash on Delivery** or **Bank Transfer**.
- If Bank Transfer is selected, the customer uploads their payment slip.
- Upon placement, an automated Telegram notification with receipt and invoice is dispatched.

### 3. Order Tracking & Invoicing
- Customers visit `/orders` to view real-time fulfillment updates.
- A **Download Invoice** button triggers the backend DomPDF generator to fetch an official A4 tax invoice.

---

## Folder Structure 📂

```text
frontend-customer/
├── app/
│   ├── assets/
│   │   └── css/tailwind.css       # TailwindCSS root styling
│   ├── components/                # Reusable UI components
│   │   ├── common/                # Buttons, loaders, input fields, badges
│   │   ├── layout/                # Storefront navbar, footer, mobile bottom bar
│   │   └── modules/               # ProductCard, CartModal, AddressModal, OrderCard
│   ├── pages/                     # Nuxt filesystem routes (Home, Cart, Orders, Profile)
│   ├── stores/                    # Pinia state stores (auth, cart, products, addresses)
│   ├── app.vue                    # Root application component
│   └── spa-loading-template.html  # Initial HTML template shown during SPA bootstrapping
├── public/                        # Static assets, logos, and placeholders
├── nuxt.config.ts                 # Nuxt 4 configuration & Telegram Mini App headers
├── package.json                   # Dependencies and scripts
├── tailwind.config.js             # Styling configuration & color palettes
└── tsconfig.json                  # TypeScript compiler settings
```

---

## Build & Deployment 🚀

The customer storefront is configured for **Static SPA hosting** via Nitro (`preset: 'static'`):

```bash
npm run generate
```

This creates a self-contained static site inside `.output/public`.

### Deploying to cPanel / Apache

Upload the contents of `.output/public` to your root public directory (e.g. `public_html`). Ensure you have an `.htaccess` file configured for SPA routing:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```

---

## License 📄

This project is proprietary software developed for the E-Commerce Store platform. All rights reserved.
