<div align="center">
  <h1>🛒 E-Commerce Store 🛒</h1>
  <p>A modern, full-stack B2B & B2C e-commerce platform for seamless product ordering, real-time Telegram order alerts with receipt & A4 invoice attachments, multi-tiered role access, and comprehensive store management. Built with a high-performance Laravel 12 backend and dual Nuxt.js 4 frontends.</p>
</div>

---

## Table of Contents 📑

- [About The Project 📖](#about-the-project-)
- [Key Features ✨](#key-features-)
- [User Roles & Usage 🧑‍💻](#user-roles--usage-)
- [Tools & Technologies 🛠️](#tools--technologies-️)
- [Getting Started 🚀](#getting-started-)
  - [Prerequisites ✅](#prerequisites-)
  - [Installation & Setup 💾](#installation--setup-)
    - [1. Clone Repository](#1-clone-repository)
    - [2. Backend Setup (Laravel 12 API)](#2-backend-setup-laravel-12-api)
    - [3. Frontend Admin Setup (Management Portal)](#3-frontend-admin-setup-management-portal)
    - [4. Frontend Customer Setup (Storefront)](#4-frontend-customer-setup-storefront)
  - [Default Access & Ports 🌐](#default-access--ports-)
- [Folder Structure 📂](#folder-structure-)
- [Useful Artisan Commands ⚡](#useful-artisan-commands-)
- [Contributors 🤝](#contributors-)
- [Contact 📬](#contact-)
- [Acknowledgements 🙏](#acknowledgements-)

---

## About The Project 📖

**E-Commerce Store** is an end-to-end e-commerce solution engineered to streamline product browsing, cart checkout, order processing, and administrative fulfillment. It provides two tailored frontends: a customer-facing storefront optimized for both desktop and mobile (including Telegram WebApp/Mini App compatibility) and an administration backoffice for managing catalog items, stock movements, customer orders, and analytical reporting.

The system incorporates an automated notification pipeline: when a customer submits an order with payment confirmation, an instant notification is dispatched to designated Telegram groups and administrators containing order details, payment receipt images, and an auto-generated A4 PDF tax invoice.

---

## Key Features ✨

- **Robust REST API:** Built with Laravel 12 utilizing Laravel Sanctum for secure token-based authentication, soft deletes, and structured API resources.
- **Dual Nuxt.js 4 Frontends:**
  - **Admin Backoffice:** Intuitive operations dashboard with sales graphs, inventory tracking, order processing workflows, and site configuration.
  - **Customer Storefront:** Fast, responsive, mobile-first shopping experience with category filtering, cart management, and multi-address selection.
- **Telegram Bot Automation:** Automated Telegram alerts dispatching order summaries, customer notes, receipt photos, and generated PDF invoices directly to Telegram chats and groups.
- **Telegram WebApp / Mini App Ready:** Configured frame-ancestors headers and mobile-responsive viewport enabling smooth embedding inside Telegram Mini Apps.
- **OTP Verification Suite:** Secure OTP-based authentication workflows for customer registration, login verification, password resets, email updates, and account deletion requests.
- **Document Generation & Reporting:**
  - **A4 PDF Invoices:** Built-in PDF generator (DomPDF) for customer invoices and order slips.
  - **Excel Data Exports:** One-click Excel spreadsheet exports (Maatwebsite Excel) for sales reports, products, categories, orders, customers, and stock logs.
- **Inventory & Stock Movement Auditing:** Real-time stock decrementing, incoming stock batches, and manual stock adjustment audit trails.
- **Dynamic Site Settings:** Configure store branding, logos, favicons, SEO tags, contact info, and maintenance mode toggles directly from the SuperAdmin control panel.

---

## User Roles & Usage 🧑‍💻

The system implements Role-Based Access Control (RBAC) across three distinct user roles:

### 1. Customer 🛍️

- Browse active products with dynamic category filters, search, and price discount calculations.
- Manage shopping cart and proceed through checkout with multiple payment methods (Bank Transfer with receipt image upload or Cash on Delivery).
- Maintain multiple delivery addresses with default selection.
- Track real-time order fulfillment status (`pending`, `confirmed`, `processing`, `shipped`, `delivered`, `completed`, `cancelled`).
- Download generated PDF invoices for past orders.
- Securely update personal profile, email, and password through multi-step OTP verification.

### 2. Admin 📊

- Access real-time analytics dashboards (total revenue, order counts, customer registrations, sales trends, and status charts).
- Manage product catalog: add/edit products, manage images, SKUs, pricing, discounts, and category associations.
- Monitor inventory levels and record stock movements (Inbound, Outbound, Adjustments).
- Process incoming orders: review uploaded payment receipts, verify payment status, update order statuses, and add internal admin notes.
- Customer management: view customer profiles, order histories, delivery addresses, and block/unblock accounts.
- Generate and download analytical Excel reports for sales performance, top products, customer activity, and stock levels.
- View real-time system notifications and alerts.

### 3. Superadmin 👑

- Full access to all Admin-level catalog, order, and reporting features.
- Manage Administrator accounts: create new admins, update credentials, assign roles, promote users, block/unblock, and restore soft-deleted administrators.
- Manage Global Site Settings: site name, descriptions, store logo, browser favicon, contact phone/email, physical address, Google Maps link, SEO metadata, and email sender configurations.
- Toggle maintenance mode with custom broadcast messages.

---

## Tools & Technologies 🛠️

- **Backend:** Laravel 12, PHP 8.2+, MySQL / SQLite, Laravel Sanctum, Barryvdh Laravel DomPDF, Maatwebsite Excel
- **Frontend (Admin & Customer):** Nuxt.js 4, Vue 3, Pinia (with Persisted State), TailwindCSS, Chart.js & Vue-Chartjs, Lucide Icons
- **Integrations:** Telegram Bot API
- **Dev & Build Tools:** Vite, Nitro, Composer, npm

---

## Getting Started 🚀

Follow these steps to set up and run the complete application locally.

### Prerequisites ✅

Ensure you have the following installed on your development machine:

- **PHP 8.2+** with required extensions (`pdo`, `pdo_mysql`, `gd`, `zip`, `mbstring`, `curl`, `fileinfo`)
- **Composer** (PHP dependency manager)
- **Node.js** (v18.x or v20.x+ recommended) and **npm**
- **MySQL / MariaDB** server (or SQLite)

---

### Installation & Setup 💾

#### 1. Clone Repository

```bash
git clone https://github.com/Ang-Kimsor/E-Commerce-Store.git
cd E-Commerce-Store
```

---

#### 2. Backend Setup (Laravel 12 API)

1. Navigate to the backend directory:

   ```bash
   cd backend
   ```

2. Install PHP dependencies:

   ```bash
   composer install
   ```

3. Configure environment settings:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your `.env` file with your database credentials and Telegram Bot configurations:

   ```env
   APP_NAME="App Name"
   APP_URL=http://localhost:8000

   # Database Configuration
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ecommerce_db
   DB_USERNAME=root
   DB_PASSWORD=

   # Telegram Bot Configuration (for instant order notifications)
   TELEGRAM_BOT_TOKEN=your_telegram_bot_token_here
   TELEGRAM_GROUP_IDS=your_group_chat_id_here
   ```

5. Run database migrations and seed default system settings:

   ```bash
   php artisan migrate
   ```

6. Create the storage symbolic link for uploaded images and receipts:

   ```bash
   php artisan storage:link
   ```

7. Create your initial Superadmin account:

   ```bash
   php artisan superadmin:create
   ```

   _(Follow the interactive prompts to enter Name, Email, Password, and optional Telegram User ID)._

8. Start the backend development server:
   ```bash
   php artisan serve --port=8000
   ```
   The API will be accessible at: `http://localhost:8000`

> 📖 **Detailed Documentation:** For complete API endpoints reference, architecture, and Artisan commands, see [backend/Readme.md](backend/Readme.md).

---

#### 3. Frontend Admin Setup (Management Portal)

1. Open another terminal and navigate to the admin frontend directory:

   ```bash
   cd frontend-admin
   ```

2. Install Node dependencies:

   ```bash
   npm install
   ```

3. Create and verify the `.env` file:

   ```env
   NUXT_PUBLIC_API_BASE=http://localhost:8000/api
   NUXT_PUBLIC_TELEGRAM_BOT=YourTelegramBotUsername
   NUXT_PUBLIC_SITE_NAME="Store Admin"
   ```

4. Launch the admin dashboard development server:

   ```bash
   npm run dev
   ```

   The admin portal will run on: `http://localhost:3000`

> 📖 **Detailed Documentation:** For UI components, admin workflows, and deployment guides, see [frontend-admin/README.md](frontend-admin/README.md).

---

#### 4. Frontend Customer Setup (Storefront)

1. Open a new terminal and navigate to the customer frontend directory:

   ```bash
   cd frontend-customer
   ```

2. Install Node dependencies:

   ```bash
   npm install
   ```

3. Create and verify the `.env` file:

   ```env
   NUXT_PUBLIC_API_BASE=http://localhost:8000/api
   NUXT_PUBLIC_TELEGRAM_BOT=YourTelegramBotUsername
   NUXT_PUBLIC_SITE_NAME="My Store"
   ```

4. Launch the customer storefront development server:

   ```bash
   npm run dev
   ```

   The customer storefront will run on: `http://localhost:3001` (or the port specified by Nuxt).

> 📖 **Detailed Documentation:** For storefront features, checkout flows, and Telegram Mini App integration, see [frontend-customer/README.md](frontend-customer/README.md).

---

### Default Access & Ports 🌐

| Application             | Role / Audience           | Default URL             |
| :---------------------- | :------------------------ | :---------------------- |
| **Backend REST API**    | System Core & Swagger/API | `http://localhost:8000` |
| **Admin Portal**        | Admins & Superadmins      | `http://localhost:3000` |
| **Customer Storefront** | Public / End Customers    | `http://localhost:3001` |

---

## Folder Structure 📂

Here is the high-level project directory structure:

```text
├── /backend                      # Laravel 12 REST API & Services
│   ├── /app
│   │   ├── /Console/Commands     # Custom Artisan commands (Superadmin, cleanup)
│   │   ├── /Enums                # Application enums (UserRole, OrderStatus, etc.)
│   │   ├── /Http/Controllers/Api # API Controllers (Admin, Customer, Auth)
│   │   ├── /Http/Requests        # Form validation requests
│   │   ├── /Models               # Eloquent Models (User, Product, Order, Category, etc.)
│   │   ├── /Notifications        # Mail and database notification classes
│   │   └── /Services             # Business logic (TelegramService, InvoiceService, etc.)
│   ├── /bootstrap                # Framework bootstrap files
│   ├── /config                   # Configuration files (telegram, sanctum, etc.)
│   ├── /database                 # Migrations, seeders, and factories
│   ├── /public                   # Web server document root and public storage
│   ├── /resources/views          # Blade templates (A4 PDF invoice templates)
│   ├── /routes                   # API routes (api.php) and web routes
│   ├── /storage                  # Uploaded payment slips, product images, and logs
│   ├── artisan                   # Artisan CLI executable
│   ├── composer.json             # PHP backend dependencies
│   └── Readme.md                 # Dedicated Backend REST API documentation
│
├── /frontend-admin               # Nuxt.js 4 Admin & SuperAdmin Portal
│   ├── /app
│   │   ├── /components           # Admin UI components (Sidebar, Tables, Modals)
│   │   ├── /pages                # Backoffice pages (Dashboard, Catalog, Orders, Reports)
│   │   ├── /stores               # Pinia state stores (Admin auth, orders, settings)
│   │   └── /assets/css           # Admin TailwindCSS stylesheets
│   ├── nuxt.config.ts            # Admin Nuxt configuration
│   ├── package.json              # Admin frontend dependencies
│   ├── tailwind.config.js        # Admin styling configuration
│   └── README.md                 # Dedicated Admin Portal documentation
│
├── /frontend-customer            # Nuxt.js 4 Customer Storefront
│   ├── /app
│   │   ├── /components           # Customer UI components (Navbar, Cart, ProductCard)
│   │   ├── /pages                # Application pages (Storefront, Cart, Orders, Profile)
│   │   ├── /stores               # Pinia state management (Auth, Cart, Products)
│   │   └── /assets/css           # TailwindCSS stylesheets
│   ├── nuxt.config.ts            # Nuxt configuration & Telegram WebApp headers
│   ├── package.json              # Customer frontend dependencies
│   ├── tailwind.config.js        # Tailwind styling configuration
│   └── README.md                 # Dedicated Customer Storefront documentation
│
└── README.md                     # Root project documentation
```

---

## Useful Artisan Commands ⚡

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

## Contributors 🤝

Contributions, issues, and feature requests are welcome! Feel free to check the repository, fork the project, and submit a pull request.

<a href="https://github.com/Ang-Kimsor/E-Commerce-Store/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=Ang-Kimsor/E-Commerce-Store" alt="Contributors" />
</a>

---

## Contact 📬

**Ang Kimsor**

- Email - [angkimsor@gmail.com](mailto:angkimsor@gmail.com)
- Phone ☎️ +85587932289
- Project Link: [https://github.com/Ang-Kimsor/E-Commerce-Store](https://github.com/Ang-Kimsor/E-Commerce-Store)

---

## Acknowledgements 🙏

- Thanks to the **Laravel** and **Vue / Nuxt** communities for building best-in-class developer ecosystems.
- **TailwindCSS** for the flexible and utility-first styling system.
- **Barryvdh Laravel DomPDF** for seamless PDF invoice rendering.
- **Maatwebsite Excel** for powerful spreadsheet data exporting.
- **Lucide Icons** for clean, modern iconography.
- **Telegram Bot API** for real-time mobile order alerts.
