<div align="center">
  <h1>⚙️ E-Commerce Store - Backend REST API</h1>
  <p>Robust, high-performance RESTful API powering the E-Commerce Store platform. Built with Laravel 12, Laravel Sanctum token authentication, Telegram Bot notification pipeline with receipt and A4 tax invoice attachments, Barryvdh DomPDF generator, and Maatwebsite Excel reporting engine.</p>
</div>

---

## Table of Contents 📑

- [Overview 📖](#overview-)
- [Key Features ✨](#key-features-)
- [Architecture & Tech Stack 🛠️](#architecture--tech-stack-️)
- [Prerequisites ✅](#prerequisites-)
- [Installation & Setup 💾](#installation--setup-)
- [Environment Variables (.env) ⚙️](#environment-variables-env-️)
- [Database Migrations & Seeding 🗄️](#database-migrations--seeding-️)
- [Storage & File Uploads 📁](#storage--file-uploads-)
- [Telegram Bot Notification Engine 🤖](#telegram-bot-notification-engine-)
- [Custom Artisan Commands ⚡](#custom-artisan-commands-)
- [API Endpoints Reference 🌐](#api-endpoints-reference-)
  - [Customer Auth & OTP](#customer-auth--otp)
  - [Customer Storefront Endpoints](#customer-storefront-endpoints)
  - [Admin Authentication](#admin-authentication)
  - [Admin Management Endpoints](#admin-management-endpoints)
  - [Superadmin System Endpoints](#superadmin-system-endpoints)
- [Folder Structure 📂](#folder-structure-)
- [Development & Testing 🧪](#development--testing-)
- [License 📄](#license-)

---

## Overview 📖

The **Backend REST API** serves as the central engine of the E-Commerce Store. It handles business logic, database transactions, order fulfillment, secure file storage, multi-channel notifications, and Role-Based Access Control (RBAC) across **Customers**, **Admins**, and **Superadmins**.

It seamlessly integrates with:
- **Customer Storefront (`/frontend-customer`):** Product browsing, cart checkout, payment receipt uploads, address book management, order tracking, and profile security via OTP.
- **Admin Management Portal (`/frontend-admin`):** Catalog management, stock movement logging, order status transitions, customer accounts auditing, analytical sales reporting, and site-wide configuration.
- **Telegram Bot API:** Instant webhook-style alerts for newly placed orders with customer information, receipt snapshots, and generated A4 PDF tax invoices.

---

## Key Features ✨

- **Laravel 12 Architecture:** Clean, modular service-oriented architecture with form request validation, Eloquent API resources, and database transactions.
- **Role-Based Access Control (RBAC):** Multi-role authentication implemented with **Laravel Sanctum** (`customer`, `admin`, `superadmin`).
- **Telegram Order Alert Pipeline:** Dispatches order notifications in real time to configured Telegram groups and administrator chats, complete with payment slip photos and auto-generated A4 PDF invoices.
- **A4 PDF Invoice Generation:** Automated tax invoice generation with Barryvdh DomPDF for customer downloads and Telegram notification attachments.
- **Excel Data Export Engine:** One-click Excel spreadsheet export using Maatwebsite Excel for sales summaries, catalog products, categories, orders, customers, and stock logs.
- **Comprehensive OTP Verification Suite:** Secure 6-digit OTP email system with cooldown limits for customer registration, login verification, password reset, email updates, and account deletion.
- **Stock & Inventory Auditing:** Real-time stock movement tracking (Inbound replenishment, Outbound sales, and Manual adjustments).
- **Dynamic Site Settings & Maintenance Mode:** Superadmin runtime site settings (store branding, logos, contact info, SEO metadata, and maintenance mode toggle).
- **Public & Protected Storage File Delivery:** Custom streaming route for uploaded product images and payment slips with proper MIME type headers and caching.

---

## Architecture & Tech Stack 🛠️

| Technology | Purpose |
| :--- | :--- |
| **PHP 8.2+** | Core programming language |
| **Laravel 12.x** | Modern web application framework |
| **Laravel Sanctum 4.x** | Token-based API authentication |
| **MySQL 8.x / SQLite** | Relational database management system |
| **Barryvdh DomPDF 3.x** | Server-side PDF invoice generation |
| **Maatwebsite Excel 3.x** | Excel & CSV spreadsheet exports |
| **Telegram Bot API** | Automated order alerts and document dispatch |
| **Composer** | PHP dependency and package manager |

---

## Prerequisites ✅

Make sure your server or local environment has the following installed:

- **PHP >= 8.2** with extensions:
  - `pdo` and `pdo_mysql` (or `pdo_sqlite`)
  - `gd` or `imagick` (for image processing)
  - `zip` (for Maatwebsite Excel)
  - `mbstring`, `curl`, `fileinfo`, `openssl`, `tokenizer`, `xml`
- **Composer 2.x**
- **MySQL / MariaDB** server (or SQLite for lightweight local setups)

---

## Installation & Setup 💾

Follow these steps to set up and run the backend locally:

### 1. Navigate to the Backend Directory

```bash
cd backend
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Setup Environment File

Copy the example configuration file and generate the application encryption key:

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure Database and Application Details

Edit `.env` and set your database connection, application URL, and Telegram Bot credentials:

```env
APP_NAME="E-Commerce Store"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration (MySQL Example)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce_db
DB_USERNAME=root
DB_PASSWORD=

# Or SQLite:
# DB_CONNECTION=sqlite

# Telegram Bot Notifications
TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
TELEGRAM_GROUP_IDS=your_telegram_chat_or_group_id_here

# Mail Settings (Used for OTP verification emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="no-reply@yourstore.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 5. Run Database Migrations

Run database schema migrations to create all required tables:

```bash
php artisan migrate
```

### 6. Create Storage Symbolic Link

Expose public storage for uploaded product images and payment slips:

```bash
php artisan storage:link
```

### 7. Provision the Initial Superadmin Account

Run the interactive CLI command to create your first administrative user:

```bash
php artisan superadmin:create
```

*You will be prompted to provide:*
- Full Name
- Email Address
- Password
- *(Optional)* Telegram User ID

### 8. Start the Local API Server

Launch the Laravel development server:

```bash
php artisan serve --port=8000
```

The API will be live at: **`http://localhost:8000/api`**

---

## Environment Variables (.env) ⚙️

| Variable | Description | Example / Default |
| :--- | :--- | :--- |
| `APP_NAME` | The display name of the application | `"E-Commerce Store"` |
| `APP_URL` | Base URL of the backend server | `http://localhost:8000` |
| `DB_CONNECTION` | Database driver (`mysql`, `sqlite`) | `mysql` |
| `DB_DATABASE` | Database name or SQLite file path | `ecommerce_db` |
| `TELEGRAM_BOT_TOKEN` | Token provided by `@BotFather` | `123456789:ABCdefGhI...` |
| `TELEGRAM_GROUP_IDS` | Comma-separated group/channel/chat IDs | `-1001234567890` |
| `MAIL_MAILER` | Mail driver for sending OTP codes | `smtp` or `log` |
| `FILESYSTEM_DISK` | Default storage driver | `public` |

---

## Storage & File Uploads 📁

File assets are managed using Laravel's file storage disk:
- **Product Images:** Stored under `storage/app/public/products`
- **Payment Slip Receipts:** Stored under `storage/app/public/receipts`
- **User Avatars:** Stored under `storage/app/public/avatars`
- **Site Logos / Favicons:** Stored under `storage/app/public/settings`

Files are accessible publicly via the secure custom endpoint:
```
GET /api/storage/{path}
```

---

## Telegram Bot Notification Engine 🤖

When a customer submits an order:
1. The backend triggers `TelegramService`.
2. A formatted order summary (Order #, Customer Name, Phone, Items, Total Price, Delivery Address, Payment Method) is compiled.
3. If a payment receipt was uploaded, the image is dispatched directly to the Telegram group.
4. An A4 PDF tax invoice is generated via DomPDF and attached to the alert as a document.

---

## Custom Artisan Commands ⚡

The backend includes purpose-built CLI commands for managing administrative users and routine data cleanup:

| Command | Description |
| :--- | :--- |
| `php artisan superadmin:create` | Interactively provision a new superadministrator account. |
| `php artisan superadmin:active {email?}` | Activate a deactivated superadmin account. |
| `php artisan superadmin:inactive {email?}` | Deactivate an active superadmin account. |
| `php artisan customer:clean-otp` | Hard delete old customer OTP verifications to free up DB space. |
| `php artisan notifications:clean` | Purge old read notifications to optimize database performance. |
| `php artisan customer:clean-account` | Process account deletions that have passed the 90-day grace period. |

---

## API Endpoints Reference 🌐

Base URL: `http://localhost:8000/api`

### Customer Auth & OTP

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/customer/auth/register/send-otp` | Send registration OTP to email | Public |
| `POST` | `/customer/auth/register/verify-otp` | Verify OTP and create customer account | Public |
| `POST` | `/customer/auth/login` | Send login OTP code | Public |
| `POST` | `/customer/auth/login/verify-otp` | Verify login OTP and return bearer token | Public |
| `POST` | `/customer/auth/forgot-password/send-otp` | Send forgot password OTP | Public |
| `POST` | `/customer/auth/forgot-password/verify-otp` | Verify forgot password OTP | Public |
| `POST` | `/customer/auth/reset-password` | Reset customer account password | Public |
| `POST` | `/customer/auth/resend-otp` | Resend OTP code (respects cooldown) | Public |
| `GET` | `/customer/auth/otp-status` | Check cooldown and active OTP status | Public |
| `POST` | `/customer/auth/logout` | Revoke current customer token | Sanctum (Customer) |
| `GET` | `/customer/auth/me` | Fetch authenticated customer profile | Sanctum (Customer) |

### Customer Storefront Endpoints

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/products` | List active products (search & category filters) | Public |
| `GET` | `/products/{id}` | Get product details | Public |
| `GET` | `/categories` | List active categories | Public |
| `GET` | `/settings` | Public site settings (name, logo, maintenance) | Public |
| `GET` | `/settings/{key}` | Public single site setting by key | Public |
| `GET` | `/storage/{path}` | Stream public image asset | Public |
| `GET` | `/customer/addresses` | List customer delivery addresses | Sanctum (Customer) |
| `POST` | `/customer/addresses` | Create new delivery address | Sanctum (Customer) |
| `PUT` | `/customer/addresses/{id}` | Update delivery address / set default | Sanctum (Customer) |
| `DELETE` | `/customer/addresses/{id}` | Remove delivery address | Sanctum (Customer) |
| `GET` | `/customer/orders` | List customer order history | Sanctum (Customer) |
| `POST` | `/customer/orders` | Submit new order (receipt upload supported) | Sanctum (Customer) |
| `GET` | `/customer/orders/{id}` | Get detailed customer order info | Sanctum (Customer) |
| `GET` | `/customer/orders/{order}/invoice` | Download A4 PDF tax invoice | Sanctum (Customer) |
| `GET` | `/customer/profile` | View customer profile | Sanctum (Customer) |
| `POST` | `/customer/profile` | Update customer profile & avatar | Sanctum (Customer) |
| `DELETE` | `/customer/profile/avatar` | Remove customer profile avatar | Sanctum (Customer) |
| `POST` | `/customer/profile/change-email/*` | Multi-step OTP email change workflow | Sanctum (Customer) |
| `POST` | `/customer/profile/change-password/*`| OTP verified password change | Sanctum (Customer) |
| `DELETE` | `/customer/profile/delete-account/verify`| OTP verified account deletion | Sanctum (Customer) |

### Admin Authentication

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `POST` | `/admin/auth/login` | Admin/Superadmin login with email and password | Public |
| `POST` | `/admin/auth/logout` | Revoke current admin token | Sanctum (Admin) |

### Admin Management Endpoints

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/dashboard` | Analytics metrics, sales summaries, and charts | Sanctum (Admin) |
| `GET` | `/admin/categories` | List all categories (including soft-deleted) | Sanctum (Admin) |
| `POST` | `/admin/categories` | Create new category | Sanctum (Admin) |
| `PUT` | `/admin/categories/{id}` | Update category | Sanctum (Admin) |
| `DELETE` | `/admin/categories/{id}` | Soft-delete category | Sanctum (Admin) |
| `POST` | `/admin/categories/{id}/restore` | Restore soft-deleted category | Sanctum (Admin) |
| `GET` | `/admin/categories/export` | Export categories list to Excel | Sanctum (Admin) |
| `GET` | `/admin/products` | List all products with stock and filters | Sanctum (Admin) |
| `POST` | `/admin/products` | Create product with image uploads | Sanctum (Admin) |
| `PUT` | `/admin/products/{id}` | Update product details | Sanctum (Admin) |
| `DELETE` | `/admin/products/{id}` | Soft-delete product | Sanctum (Admin) |
| `POST` | `/admin/products/{id}/restore` | Restore soft-deleted product | Sanctum (Admin) |
| `GET` | `/admin/products/export` | Export product catalog to Excel | Sanctum (Admin) |
| `GET` | `/admin/products/{id}/stock-movements` | List stock history for product | Sanctum (Admin) |
| `POST` | `/admin/products/{id}/stock-movements` | Record stock movement (in/out/adjust) | Sanctum (Admin) |
| `GET` | `/admin/orders` | List customer orders with status filters | Sanctum (Admin) |
| `GET` | `/admin/orders/{id}` | View detailed order info & payment slip | Sanctum (Admin) |
| `PUT` | `/admin/orders/{id}` | Update order status and admin notes | Sanctum (Admin) |
| `GET` | `/admin/orders/{id}/pdf` | Download A4 PDF order invoice | Sanctum (Admin) |
| `GET` | `/admin/orders/export` | Export orders to Excel spreadsheet | Sanctum (Admin) |
| `GET` | `/admin/customers` | List customer accounts with order counts | Sanctum (Admin) |
| `POST` | `/admin/customers/{id}/block` | Block customer account | Sanctum (Admin) |
| `POST` | `/admin/customers/{id}/unblock` | Unblock customer account | Sanctum (Admin) |
| `GET` | `/admin/customers/export` | Export customers list to Excel | Sanctum (Admin) |
| `GET` | `/admin/reports/sales` | Query sales analytics report | Sanctum (Admin) |
| `GET` | `/admin/reports/products` | Query product performance report | Sanctum (Admin) |
| `GET` | `/admin/reports/customers` | Query customer behavior report | Sanctum (Admin) |
| `GET` | `/admin/reports/inventory` | Query inventory movements report | Sanctum (Admin) |
| `GET` | `/admin/notifications` | List admin notifications | Sanctum (Admin) |
| `POST` | `/admin/notifications/mark-all-read` | Mark all notifications as read | Sanctum (Admin) |

### Superadmin System Endpoints

| Method | Endpoint | Description | Auth Required |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin/admins` | List all administrator accounts | Sanctum (Admin/Superadmin) |
| `POST` | `/admin/admins` | Create new admin account | Sanctum (Superadmin) |
| `PUT` | `/admin/admins/{id}` | Update admin credentials or role | Sanctum (Superadmin) |
| `DELETE` | `/admin/admins/{id}` | Delete administrator account | Sanctum (Superadmin) |
| `POST` | `/admin/admins/{id}/restore` | Restore soft-deleted administrator | Sanctum (Superadmin) |
| `POST` | `/admin/admins/{id}/block` | Block administrator access | Sanctum (Superadmin) |
| `POST` | `/admin/admins/{id}/unblock` | Unblock administrator access | Sanctum (Superadmin) |
| `GET` | `/admin/settings` | Get all system configuration items | Sanctum (Superadmin) |
| `POST` | `/admin/settings/update` | Update site name, logo, favicon, maintenance | Sanctum (Superadmin) |
| `DELETE` | `/admin/settings/{key}/image`| Delete uploaded site logo or favicon | Sanctum (Superadmin) |
| `GET` | `/admin/profile` | Superadmin/Admin profile details | Sanctum (Superadmin) |
| `POST` | `/admin/profile` | Update profile information and avatar | Sanctum (Superadmin) |

---

## Folder Structure 📂

```text
backend/
├── app/
│   ├── Console/Commands/      # Custom CLI Artisan commands
│   ├── Enums/                 # Application enums (UserRole, OrderStatus, etc.)
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── Admin/         # 13 Admin & Superadmin API controllers
│   │   │   └── Customer/      # 6 Customer API controllers
│   │   ├── Middleware/        # Sanctum and role-checking middlewares
│   │   └── Requests/          # Form validation request classes
│   ├── Models/                # Eloquent models (User, Product, Order, Category, etc.)
│   ├── Notifications/         # Mail and database notification classes
│   └── Services/              # Core business services (TelegramService, InvoiceService)
├── bootstrap/                 # Application bootstrap & provider configuration
├── config/                    # Configuration files (telegram, sanctum, mail, etc.)
├── database/
│   ├── factories/             # Database model factories
│   ├── migrations/            # Database schema migrations
│   └── seeders/               # Initial system seeders
├── public/                    # Web server entry point (index.php)
├── resources/
│   └── views/invoices/        # Blade templates for A4 DomPDF invoices
├── routes/
│   ├── api.php                # Complete REST API route definitions
│   └── web.php                # Web routes
├── storage/                   # Uploaded images, receipts, and application logs
├── tests/                     # Unit and Feature test suites
├── artisan                    # Artisan CLI executable
├── composer.json              # PHP dependencies
└── vite.config.js             # Asset bundling configuration
```

---

## Development & Testing 🧪

Run automated backend tests:

```bash
composer test
```

Or execute via Artisan:

```bash
php artisan test
```

To run all background tasks in development mode (server, queue worker, and log pail):

```bash
composer dev
```

---

## License 📄

This project is proprietary software developed for the E-Commerce Store platform. All rights reserved.
