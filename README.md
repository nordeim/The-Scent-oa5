# 🌿 The Scent - Premium Aromatherapy E-commerce Platform (v16.3)

Welcome to **The Scent**, a modern, full-featured, and beautifully crafted e-commerce platform built to showcase and sell premium natural aromatherapy products. This project is designed from the ground up for extensibility, security, and seamless user experience, featuring a custom MVC-inspired PHP architecture.

> 🧘 “Find your moment of calm” – Discover your perfect scent and enhance your well-being.

---

## 🚀 Live Demo

[🌐 **Click here to view the demo**](https://magenta-mole-338777.hostingersite.com/)

---

## ✨ Current Status (v16.3 - Account Dashboard Fixed, Core Stable)

*   ✅ **Core Functionality Stable:** Product Browsing (Filtering/Sorting/Pagination OK), Add-to-Cart (AJAX OK), Cart Management (AJAX OK), User Login/Registration (AJAX Registration OK), Password Reset OK, Profile Update (Name, Email, Password, Newsletter, Address OK), Quiz Flow OK, **Account Dashboard OK**, Checkout Load OK (Address Line 2 added), Order Confirmation OK.
*   ✅ **Critical Bug Fixes Implemented:**
    *   Resolved **Account Dashboard error** (TypeError: json_decode).
    *   Resolved Checkout Page Load failure.
    *   Resolved Order Confirmation flow inaccuracies.
    *   Resolved Account Pages UI inconsistencies.
    *   Resolved Quiz CSRF Error & Redirects.
    *   Resolved Product Filter SQL Error (Mixed Placeholders).
    *   Resolved Quiz Results display inaccuracies.
    *   Resolved Registration Failure (DB Logging Error).
    *   Resolved Profile Address Saving (`UserModel` key mapping).
    *   Resolved Checkout Address Field Discrepancy.
*   ✅ **Standardizations Applied:**
    *   **Cart Storage:** Consistent Session (Guest) vs. DB (User) handling via `CartController`.
    *   **Rate Limiting:** Applied consistently to key endpoints via `BaseController`.
    *   **Database Placeholders:** Standardized on named placeholders for filtering.
*   ✅ **UI Enhancements:**
    *   Account pages UI fixed.
    *   Address Management UI on profile page fully functional (View/Edit/Save).
    *   Admin Product CRUD UI implemented (List, Create, Edit, Delete views).
*   ✅ **Admin Functionality:**
    *   **Product Management (CRUD Interface Functional - List, Create, Edit, Delete Views & Logic, JSON fields parsed correctly).**
    *   Coupon Management (CRUD Interface Functional).
    *   Quiz Analytics (Basic View Functional).
*   ⚠️ **Known Issues/TODOs:**
    *   **Checkout Payment Initialization Error (Under Investigation):** The checkout page shows "Could not initialize payment system." **Debugging logs have been added to `js/main.js`**. *(Needs live debugging using console logs).*
    *   **Missing `tax_rates` Table (Patch Required):** Error logs indicate this table is missing. **An SQL patch is provided in Appendix D of the TDS and must be applied.**
    *   **Error Handling ("Headers Already Sent"):** Issue mitigated, potential edge cases remain. Consider making `views/error.php` self-contained.
    *   **Content Security Policy (CSP):** Needs review/tightening for production deployment.
    *   **Rate Limiting Coverage:** Review admin endpoints and other less critical areas.
    *   **Admin Panel Features:** Extend CRUD features (Orders, Users). Improve Quiz Analytics detail. Add Admin Dashboard content.
    *   **Code Quality/Refactoring:** Composer (autoloader is present, full adoption for deps like Stripe/PHPMailer), Router, Templating, .env, Migrations, Tests recommended for future maintainability.
    *   **File Uploads:** Admin product form currently uses URL input for images; actual file upload needs implementation.

This README provides a comprehensive overview of the current architecture, logic, and flow. For deeper technical details, refer to the [Technical Design Specification (v16.3)](#) *(Placeholder - Link to your TDS file if hosted separately)*.

---

## 🔖 Badges

![PHP](https://img.shields.io/badge/PHP-8.0+-blue?logo=php&style=for-the-badge)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange?logo=mysql&style=for-the-badge)
![Apache](https://img.shields.io/badge/Apache-2.4+-red?logo=apache&style=for-the-badge)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-CDN-blue?logo=tailwindcss&style=for-the-badge)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6-yellow?logo=javascript&style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-green&style=for-the-badge)
![Status](https://img.shields.io/badge/Status-Core%20Stable/Dev-yellowgreen&style=for-the-badge)

---

## 📚 Table of Contents

1.  [🌟 Introduction](#-introduction)
2.  [🎯 Features](#-features)
3.  [🖼️ Screenshots](#-screenshots)
4.  [🧱 System Architecture](#-system-architecture)
5.  [⚙️ Technology Stack](#-technology-stack)
6.  [📁 Folder Structure Overview](#-folder-structure-overview)
7.  [🗃️ Database Schema](#-database-schema)
8.  [📦 Installation Instructions](#-installation-instructions)
9.  [🚀 Deployment Guide Summary](#-deployment-guide-summary)
10. [🛡️ Security Best Practices Implemented](#-security-best-practices-implemented)
11. [🔧 Customization & Extensibility](#-customization--extensibility)
12. [🤝 Contributing](#-contributing)
13. [📄 License](#-license)
14. [🙏 Credits & Acknowledgements](#-credits--acknowledgements)

---

## 🌟 Introduction

**The Scent** is more than just an e-commerce platform — it’s an experience. Built specifically to support the sale and recommendation of **premium aromatherapy products**, the platform integrates:

*   A clean, modern, responsive UI/UX powered by Tailwind CSS and subtle animations.
*   Personalized shopping via an interactive scent finder quiz (**Functional**).
*   Dynamic product catalog with categories, filtering, sorting, and functional pagination (**Functional**).
*   A functional shopping cart with AJAX updates and a **standardized storage mechanism**.
*   Secure user authentication (**Registration Functional**, Login, Password Reset, Profile Update including **Address Management Functional**, **Account Dashboard Functional**) with robust validation and **consistent rate limiting**.
*   A modular PHP codebase (MVC-inspired) for customization and growth.
*   A **stable core checkout process**, including page load, AJAX interactions, payment intent creation, and **reliable order confirmation display**.
*   Functional user account pages with a **fixed, consistent UI**, **functional address management**, and a **working Account Dashboard**.
*   **Basic Admin panel** with functional **Product CRUD (including JSON field parsing)**, Coupon management, and Quiz Analytics.

Designed for extensibility, performance, and user-centric experience, The Scent provides a solid foundation for wellness or natural product businesses.

---

## 🎯 Features

### 🛍️ Core E-commerce
*   ✅ Modern Landing Page with video background and particle effects.
*   ✅ Product Catalog with server-side filtering (category, price range), sorting, pagination, and text search.
*   ✅ Detailed Product Pages with image gallery, descriptions, benefits, ingredients, usage instructions, and related products.
*   ✅ AJAX-powered "Add to Cart" functionality.
*   ✅ Functional Cart Page with AJAX quantity updates and item removal.
*   ✅ Standardized Cart Storage: Session for guests, Database (`cart_items`) for logged-in users, with merge-on-login.
*   ✅ Mini-Cart dropdown in header, updated via AJAX.
*   ✅ Stock Level Validation: Prevents adding/updating cart beyond available stock (unless backorder allowed).

### 🔐 User Management
*   ✅ Secure User Authentication: AJAX-driven Registration and Login.
*   ✅ Password Hashing: Uses `password_hash()` and `password_verify()`.
*   ✅ Password Reset System: Token-based, email-driven, and functional.
*   ✅ User Profile Management: Update name, email, password, newsletter preferences.
*   ✅ **Address Management:** Users can view, add, and update their shipping address via their profile page.
*   ✅ Order History & Details: Users can view their past orders and detailed information.
*   ✅ **Account Dashboard:** Displays recent orders and latest quiz results with recommended products. **(Error fixed, fully functional)**.
*   ✅ Account Pages UI: Consistent and improved user interface.

### ✨ Personalization & User Experience
*   ✅ Interactive Scent Finder Quiz: Guides users to product recommendations based on mood.
*   ✅ Product Recommendations: Based on quiz results and related items on product pages.
*   ✅ Responsive Design: Adapts to various screen sizes (Mobile navigation fixed).
*   ✅ Animated Page Transitions/Elements: Using AOS.js library.

### 🛒 Shopping & Checkout
*   ✅ Checkout Page: Secure form for shipping details, pre-fills from user profile if available. **Address Line 2 field included and handled.**
*   ✅ AJAX Coupon Application: Validate and apply coupon codes on the checkout page.
*   ✅ AJAX Tax Calculation: Estimates taxes based on shipping location (Country/State). *(Requires `tax_rates` table)*.
*   ✅ Stripe Payment Integration:
    *   Payment Intent creation on the backend.
    *   Stripe Elements for secure card input on the frontend.
    *   **Deferred Initialization:** Stripe Elements are initialized *after* `clientSecret` is fetched, matching best practices.
    *   Handles 3D Secure if required by the bank.
*   ✅ Order Creation: Saves order details, items, and applied discounts to the database.
*   ✅ Inventory Decrement: Stock levels are updated upon successful order placement.
*   ✅ Optional Address Saving: Users can opt to save their shipping address to their profile during checkout.
*   ✅ Order Confirmation Page: Reliable display of order details after successful payment verification via Stripe Payment Intent.

### 💼 Business & Admin Features
*   ✅ **Admin Panel (Role-Based Access):**
    *   **Product Management (CRUD):** List, Create, Edit, and Delete products. Supports standard fields and **JSON-based fields like 'Benefits' and 'Gallery Images' via textarea input (parsed by controller).**
    *   **Coupon Management (CRUD):** Create, list, edit, activate/deactivate, and delete coupon codes.
    *   **Quiz Analytics:** Basic dashboard to view quiz participation and popular choices.
*   ✅ Inventory Management: Tracks stock quantities. Product form allows setting stock, initial stock, low-stock threshold, and backorder allowance.
*   ✅ Email Notifications: System functional for Welcome, Password Reset, and Order Confirmation (sent via Stripe Webhook for `payment_intent.succeeded`). Low stock alerts are logged (email sending for these can be extended).

### 🛡️ Security
*   ✅ **CSRF Protection:** Synchronizer Token Pattern implemented for all POST requests.
*   ✅ **Input Validation & Sanitization:** Uses `SecurityMiddleware::validateInput()` for all user inputs.
*   ✅ **Output Escaping:** Uses `htmlspecialchars()` in views to prevent XSS.
*   ✅ **Secure Session Management:** HttpOnly, Secure, SameSite=Lax cookies; Session ID regeneration; User-agent and IP binding.
*   ✅ **Security Headers:** Configured in `config.php` and `BaseController` (X-Frame-Options, X-XSS-Protection, X-Content-Type-Options, Referrer-Policy, basic CSP).
*   ✅ **Password Hashing:** Strong hashing with `password_hash()` and `PASSWORD_DEFAULT`.
*   ✅ **Stripe Webhook Security:** Validates Stripe webhook signatures.
*   ✅ **Rate Limiting:** Applied to critical endpoints (login, registration, password reset, checkout, etc.) using APCu via `BaseController`.
*   ✅ **SQL Injection Prevention:** Uses PDO Prepared Statements consistently (named placeholders adopted for filtering).
*   ✅ **Error Handling:** Custom error handler configured for production-safe error display and logging.
*   ✅ **Audit Logging:** Key user and system actions are logged.

---

## 🖼️ Screenshots

> 📸 *Please add updated screenshots reflecting the current v16.3 state, including the functional **Account Dashboard**, **Admin Product List/Form with JSON field handling**, and the checkout page (noting the payment init issue is under investigation).*

*   *Landing Page:* `[Insert Screenshot: views/home.php]`
*   *Product List (Filtering Fixed):* `[Insert Screenshot: views/products.php]`
*   *Product Detail:* `[Insert Screenshot: views/product_detail.php]`
*   *Cart Page (Updated UI):* `[Insert Screenshot: views/cart.php]`
*   *Quiz Page (Functional):* `[Insert Screenshot: views/quiz.php]`
*   *Quiz Results (Functional):* `[Insert Screenshot: views/quiz_results.php]`
*   *Login Page:* `[Insert Screenshot: views/login.php]`
*   *Registration Page (Functional):* `[Insert Screenshot: views/register.php]`
*   *Checkout Page (Address Line 2, Payment Init Debugging):* `[Insert Screenshot: views/checkout.php with console open if possible]`
*   *Order Confirmation Page (Functional):* `[Insert Screenshot: views/order_confirmation.php]`
*   **Account Dashboard (Fixed & Functional - Showing Quiz Recs):** `[Insert Screenshot: views/account/dashboard.php]`
*   *Account Profile (Fixed UI + Functional Address Form):* `[Insert Screenshot: views/account/profile.php]`
*   *Admin Coupons:* `[Insert Screenshot: views/admin/coupons.php]`
*   **Admin Product List:** `[Insert Screenshot: views/admin/products.php]`
*   **Admin Product Form (Showing JSON Textareas):** `[Insert Screenshot: views/admin/product_form.php]`

---

## 🧱 System Architecture

**Custom MVC-Inspired Modular PHP Architecture:**

```mermaid
graph TD
    A[Client Browser] -- HTTP Request --> B(Apache Web Server);
    B -- Rewrite URL (.htaccess) --> C(index.php - Router/Dispatcher);
    C -- Loads --> D{Core Includes};
    D --> E[config.php];
    D --> F[includes/db.php];
    D --> G[includes/auth.php];
    D --> H[includes/SecurityMiddleware.php];
    D --> I[includes/ErrorHandler.php];
    D -- Autoloads --> J[vendor/autoload.php (Composer)];

    C -- Routes to --> K{Controller};
    K -- Extends --> L[controllers/BaseController.php];
    K -- Uses --> M{Model};
    M -- Interacts with (PDO) --> N[(Database - MySQL)];
    K -- Prepares Data for --> O{View};
    L -- Provides Utilities --> K;
    H -- Applies Security --> C;
    I -- Handles Errors --> C;

    O -- Renders HTML --> C;
    C -- HTTP Response --> A;

    subgraph AJAX Flow
        P[js/main.js AJAX Call] --> C;
        K -- Returns JSON --> P;
    end

    subgraph Stripe Flow
        Q[Stripe.js (Client-Side)] -- Tokenize/Confirm --> R(Stripe API);
        R -- Webhook --> S[payment/webhook route in index.php];
        S --> T[controllers/PaymentController.php];
        T -- Updates Order --> N;
    end
```

*   **`index.php`:** Central entry point, routing via `switch` statement, core includes, and **global POST CSRF validation**.
*   **`Controllers`:** Handle business logic, extend `BaseController` (which provides database connection, email service, CSRF methods, rate limiting, JSON/redirect responses, view rendering, auth checks, audit logging). Controllers interact with Models and select the appropriate View or JSON response.
*   **`Models`:** Responsible for all database interactions using **PDO Prepared Statements** (primarily named placeholders). They encapsulate data access logic.
*   **`Views`:** PHP templates that generate HTML output. They receive data from Controllers. Layout files (`views/layout/`) provide consistent page structure.
*   **`Includes`:** Contains core shared utilities like database connection setup (`db.php`), authentication functions (`auth.php`), security helpers (`SecurityMiddleware.php`), error handling (`ErrorHandler.php`), and email sending (`EmailService.php`).
*   **`config.php`:** Centralized configuration for database credentials, security settings (session, rate limiting, CSP, CSRF), API keys (Stripe), and application-wide constants.
*   **`js/main.js`:** Handles frontend interactivity, AJAX calls (e.g., add to cart, newsletter signup, login/registration), and page-specific initializations. It reads the global CSRF token from the DOM for AJAX requests.
*   **Composer (`vendor/autoload.php`):** Used for autoloading project classes (via classmap) and external libraries like PHPMailer and the Stripe PHP SDK.

---

## ⚙️ Technology Stack

| Layer            | Technology                                                                                                | Notes                                                                                             |
| :--------------- | :-------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------ |
| Frontend         | HTML5, Tailwind CSS (CDN), Custom CSS (`css/style.css`), JavaScript (Vanilla ES6+), Font Awesome 6 (CDN)   | Uses AOS.js & Particles.js for animations. Account Dashboard UI fixed.                            |
| Backend          | PHP 8.0+                                                                                                  | Core logic, custom MVC-inspired structure, OOP principles.                                        |
| Web Server       | Apache 2.4+                                                                                               | Requires `mod_rewrite` for clean URLs.                                                            |
| Database         | MySQL 5.7+ / 8.0+ (or MariaDB equivalent)                                                                   | **Requires schema patches for `users` and `tax_rates` tables (see Appendix D of TDS).**                |
| Server-Side Libs | PDO                                                                                                       | For secure database access (**Prepared Statements, Named Placeholders**).                             |
| Dependencies     | Composer (for PHPMailer, Stripe SDK, and project class autoloading)                                       | `vendor/autoload.php` is used.                                                                    |
| Caching (Opt.)   | APCu                                                                                                      | **Required** for the implemented server-side rate limiting feature (ensure PHP extension enabled). |

---

## 📁 Folder Structure Overview

A brief overview of the main project directories:

*   `/` (Root): `index.php` (entry point), `config.php`, `.htaccess`, `composer.json`, `README.md`.
*   `controllers/`: Contains controller classes (e.g., `ProductController.php`, `CartController.php`).
*   `css/`: Custom stylesheets (`style.css`).
*   `db/`: Database schema (`the_scent_schema.sql.txt`) and update scripts.
*   `images/`: Static images for products, layout, etc.
*   `includes/`: Core PHP files for shared functionality (e.g., `db.php`, `auth.php`, `SecurityMiddleware.php`, `ErrorHandler.php`, `EmailService.php`).
*   `js/`: JavaScript files (`main.js`).
*   `logs/`: Directory for error, security, and audit logs (must be writable by the web server).
*   `models/`: Contains model classes for database table interactions (e.g., `Product.php`, `User.php`).
*   `vendor/`: Composer dependencies (e.g., PHPMailer, Stripe SDK).
*   `views/`: Presentation layer files (PHP templates).
    *   `views/account/`: User account-specific views.
    *   `views/admin/`: Administrator panel views.
    *   `views/emails/`: Email templates.
    *   `views/layout/`: Header, footer, and other layout partials.
*   `videos/`: Video assets (e.g., for hero background).

*(For a detailed map, refer to the Technical Design Specification.)*

---

## 🗃️ Database Schema

The database schema is defined in [`db/the_scent_schema.sql.txt`](db/the_scent_schema.sql.txt).

**Key Tables:**
*   `users`: Stores user information, credentials, roles, and **address details**.
*   `products`: Product catalog information, including stock levels and JSON fields for benefits/gallery.
*   `categories`: Product categories.
*   `product_attributes`: (If used for advanced filtering, e.g., scent type, mood effect - current quiz recommendations are simpler).
*   `cart_items`: Stores items for logged-in users' carts.
*   `orders`: Main order information.
*   `order_items`: Line items for each order.
*   `quiz_results`: Stores user quiz submissions and recommended product IDs.
*   `coupons`: Coupon details and usage tracking.
*   `coupon_usage`: Logs individual coupon uses.
*   `newsletter_subscribers`: List of newsletter subscribers.
*   `audit_log`: Records significant system and user actions.
*   `email_log`: Tracks emails sent by the system.
*   **`tax_rates` (NEW - REQUIRES PATCH):** Stores tax rates for different regions.
*   **`tax_rate_history` (NEW - REQUIRES PATCH):** Tracks changes to tax rates.

**IMPORTANT:**
1.  **The `users` table requires a patch** (`db/the_scent_update_users_table.sql` - *ensure this file exists or integrate its changes into the main schema/TDS Appendix D*) to add columns for status, newsletter subscription, password reset tokens, and address fields.
2.  **The `tax_rates` and `tax_rate_history` tables are currently missing** and are essential for tax calculations. An SQL patch is provided in the Technical Design Specification (Appendix D) and **must be applied**.

*(A conceptual ERD can be found in the full Technical Design Specification.)*

---

## 📦 Installation Instructions

### Prerequisites
*   **Web Server:** Apache 2.4+ with `mod_rewrite` enabled.
*   **PHP:** 8.0 or higher.
*   **Required PHP Extensions:** `pdo_mysql`, `mbstring`, `openssl`, `json`, `session`, `fileinfo`, **`apcu`** (critical for rate limiting).
*   **Database:** MySQL 5.7+ / 8.0+ (or MariaDB equivalent).
*   **Composer:** For installing PHP dependencies.

### Steps
1.  **Clone Repository:**
    ```bash
    git clone <your-repository-url> the-scent
    cd the-scent
    ```
2.  **Install Dependencies:**
    ```bash
    composer install
    ```
3.  **Database Setup:**
    *   Create a MySQL database (e.g., `the_scent`) and a database user with appropriate permissions.
    *   Import the base schema:
        ```bash
        mysql -u YOUR_DB_USER -p YOUR_DB_NAME < db/the_scent_schema.sql.txt
        ```
    *   **Apply MANDATORY Database Patches:**
        *   **Users Table Update:** (Ensure `db/the_scent_update_users_table.sql` contains the necessary `ALTER TABLE users` statements as per TDS Appendix D, or apply them manually.)
            ```bash
            # mysql -u YOUR_DB_USER -p YOUR_DB_NAME < db/the_scent_update_users_table.sql 
            ```
            *(Note: If a single patch file for all updates exists, use that. The TDS currently has separate notes for the users table patch and the tax tables patch.)*
        *   **Tax Tables Creation:** (Apply the SQL provided in TDS Appendix D or a dedicated patch file)
            ```bash
            # Example: mysql -u YOUR_DB_USER -p YOUR_DB_NAME < db/patch_tax_tables.sql
            ```
            *(Ensure you create and apply this patch based on TDS Appendix D)*
4.  **Configuration:**
    *   Copy `config.sample.php` to `config.php` (if `config.php` is gitignored).
    *   Edit `config.php`:
        *   Set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`.
        *   Set your `STRIPE_PUBLIC_KEY`, `STRIPE_SECRET_KEY`, and `STRIPE_WEBHOOK_SECRET` (use test keys for development).
        *   Configure `SMTP_*` constants for email. For local development, consider Mailhog (port 1025, no auth).
        *   Set `NEWSLETTER_SECRET_KEY` to a long, random string.
        *   Ensure `BASE_URL` is correct for your environment (e.g., `/` for root, `/the-scent/` if in a subfolder).
        *   Set `ENVIRONMENT` to `development` for local setup.
5.  **File Permissions:**
    *   The `logs/` directory must be writable by the web server user (e.g., `www-data` on Debian/Ubuntu).
        ```bash
        sudo chown -R www-data:www-data logs
        sudo chmod -R 750 logs 
        ```
    *   It's good practice to restrict direct web access to sensitive files like `config.php` if not already handled by server configuration.
6.  **Web Server Configuration (Apache Example):**
    *   Set up a Virtual Host that points its `DocumentRoot` to the project's root directory (where `index.php` resides).
    *   Ensure `AllowOverride All` is set in the Virtual Host or relevant `<Directory>` directive to allow `.htaccess` to function.
    *   Enable `mod_rewrite`: `sudo a2enmod rewrite`
    *   Restart Apache: `sudo systemctl restart apache2`
7.  **PHP Configuration:**
    *   Ensure the **`apcu`** PHP extension is installed and enabled in your `php.ini`. If you installed it, you might need to restart PHP-FPM (if using it) or your web server.
8.  **Access the Site:**
    *   Navigate to the URL you configured for your local development environment.
9.  **Initial Verification & Admin Account:**
    *   Test user registration. The first registered user might need to be manually promoted to 'admin' in the `users` table (`role` column) to access admin features, or implement a seeder/setup script.
    *   Verify core functionalities: product browsing, cart, checkout page load (check console for Stripe init logs), quiz, account dashboard.

---

## 🚀 Deployment Guide Summary

*(High-level steps for deploying to a production environment)*

1.  **Code Deployment:** Transfer project files to the production server (e.g., via Git, FTP, rsync). Exclude development files/folders.
2.  **Database Setup:** Create the production database and user. Import the schema and **apply all necessary patches** (users table, tax tables).
3.  **Configuration (`config.php`):**
    *   Use **production** database credentials, Stripe **live** keys, and production SMTP settings. **Store sensitive credentials securely (e.g., using environment variables, not directly in `config.php` if possible).**
    *   Set `ENVIRONMENT` to `production`. This disables detailed error display and may enable other production-specific settings.
    *   Ensure `BASE_URL` is correct for the production domain.
4.  **File Permissions:** Set strict file permissions. Ensure `logs/` is writable by the web server user only where necessary. Make other files read-only for the web server where possible.
5.  **Web Server Configuration:** Configure Apache/Nginx for production (performance tuning, security hardening).
6.  **HTTPS:** **Crucial for e-commerce.** Obtain and install an SSL/TLS certificate (e.g., Let's Encrypt). Configure the web server to force HTTPS. Ensure `Strict-Transport-Security` header is active.
7.  **PHP Configuration:** Ensure `apcu` is enabled. Disable `display_errors` in `php.ini`. Configure `error_log` appropriately. Enable OPcache for performance.
8.  **Dependencies:** Run `composer install --no-dev --optimize-autoloader` on the server or as part of your deployment pipeline.
9.  **Testing:** Thoroughly test all functionalities in the production environment, especially payment processing with live Stripe keys (in test mode first if possible), user registration, login, and core e-commerce flows.
10. **Monitoring & Backups:** Set up server monitoring, application-level error tracking (e.g., Sentry, if integrated), and regular database/file backups.

---

## 🛡️ Security Best Practices Implemented

This platform incorporates several security measures:

*   ✅ **SQL Injection Prevention:** Consistent use of PDO Prepared Statements.
*   ✅ **Cross-Site Scripting (XSS) Prevention:** Output escaping with `htmlspecialchars()` in views and input sanitization. Content Security Policy header is present (needs review for production).
*   ✅ **Cross-Site Request Forgery (CSRF) Protection:** Synchronizer Token Pattern implemented. Tokens are generated and validated for all POST requests.
*   ✅ **Secure Authentication & Authorization:** Strong password hashing (`password_hash`), role-based access control (user/admin).
*   ✅ **Secure Session Management:** HttpOnly, Secure, SameSite=Lax cookie flags; regular session ID regeneration; binding session to User-Agent and IP address (basic integrity check).
*   ✅ **Security Headers:** Configured in `config.php` and `BaseController` (X-Frame-Options, X-XSS-Protection, X-Content-Type-Options, Referrer-Policy, basic CSP).
*   ✅ **Stripe Webhook Security:** Validates Stripe webhook signatures to ensure authenticity.
*   ✅ **Input Validation:** All user-supplied data is validated server-side using `SecurityMiddleware::validateInput()`.
*   ✅ **Rate Limiting:** Applied to critical endpoints (login, registration, password reset, checkout, etc.) to mitigate brute-force attacks. Requires APCu.
*   ✅ **Error Handling:** Custom error handling displays generic messages in production and logs detailed errors.
*   ✅ **Audit Logging:** Key actions are logged for monitoring and forensics.

---

## 🔧 Customization & Extensibility

The platform is designed to be extensible:

*   **Adding New Features/Pages:**
    1.  Create a new method in an existing Controller or create a new Controller class (extending `BaseController`).
    2.  Implement business logic within the controller method, interacting with Models as needed.
    3.  Create a new View file in the `views/` directory.
    4.  Add a new `case` to the `switch` statement in `index.php` to route requests to your new controller method.
    5.  For forms requiring POST, ensure the CSRF token is included in the form and validated in the controller.
    6.  Apply rate limiting in the controller if the endpoint is sensitive.
*   **Modifying Product Data:** Products, categories, etc., can be managed via the Admin Panel (Product CRUD) or directly in the database.
*   **Styling:** Modify Tailwind CSS utility classes directly in the views or add/override styles in `css/style.css`.
*   **Quiz Logic:** Modify questions in `QuizController::getQuestions()` and recommendation logic in `QuizModel::getRecommendations()`.

---

## 🤝 Contributing

We welcome contributions to The Scent! Please follow these guidelines:

1.  **Fork the Repository.**
2.  **Create a Feature/Bugfix Branch:** (e.g., `git checkout -b feature/amazing-new-feature` or `bugfix/checkout-issue`).
3.  **Code Standards:**
    *   Adhere to PSR-12 for PHP code.
    *   Write clear, commented code where necessary.
    *   Use semantic HTML and prioritize accessibility.
    *   Utilize Tailwind CSS classes for styling where possible.
4.  **Commit Changes:** Make clear, concise commit messages.
5.  **Push to Your Branch:** `git push origin feature/your-feature-branch`
6.  **Open a Pull Request:**
    *   Provide a clear title and description of your changes.
    *   Reference any related issues.
    *   Ensure your code has been tested.
7.  **Issue Tracker:** Report bugs or suggest features through the project's GitHub Issue Tracker `[Link to Your Project's Issues Page]`.

---

## 📄 License

This project is distributed under the **MIT License**.
See the `LICENSE` file (you'll need to create this file if it doesn't exist) for more information.

A typical MIT License text:
```
MIT License

Copyright (c) [Year] [Your Name/Organization Name]

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

## 🙏 Credits & Acknowledgements

*   **Core Technologies & Libraries:**
    *   PHP
    *   MySQL
    *   Apache
    *   PDO (PHP Data Objects)
    *   Tailwind CSS
    *   AOS.js (Animate On Scroll Library)
    *   Particles.js
    *   Font Awesome
    *   Stripe (Payment Processing & SDKs)
    *   PHPMailer (Email Sending - via Composer)
    *   APCu (Alternative PHP Cache - for Rate Limiting)
*   **Inspiration & Community Support:**
    *   Stack Overflow
    *   PHP & MySQL Online Communities
    *   OpenAI's GPT models for assistance with code generation, debugging, and documentation.

---
https://drive.google.com/file/d/114Vi8wQ3KdL9JS2uJuuYrZajTeYtPemi/view?usp=sharing, https://drive.google.com/file/d/162-RTd0jEV_bKatHQG9hqT6nTXyh1s3D/view?usp=sharing, https://drive.google.com/file/d/1ABd6Gv5FO-h8wwcsOKgBFo2J46Eu2G7M/view?usp=sharing, https://drive.google.com/file/d/1Ao_-D_rueOgaYD5cxXNmTDQmQMkZJYrw/view?usp=sharing, https://drive.google.com/file/d/1F0Aif9H4oS4sPnDYk5BpxpoXCg-GCtJp/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221FNMVKRTXfIB_jKYSvse0Kxs-CEZ0Gz8s%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1IAXPrucbGkCbtb5NIi_jMMqajLWOCEiW/view?usp=sharing, https://drive.google.com/file/d/1IJ4oswjj8NFbju3W2yMih5rSlShz5D3T/view?usp=sharing, https://drive.google.com/file/d/1JulTnb-Vuj5acV9a7-E4akwK9KBHSBMX/view?usp=sharing, https://drive.google.com/file/d/1L9Sb2X_GjbDYkalu5wDlUYsnVTgXOrBq/view?usp=sharing, https://drive.google.com/file/d/1UaiDOQFdkeCXTf9dVOfeGnT22A8eHGhl/view?usp=sharing, https://drive.google.com/file/d/1_T2IICQj_HehoNv31p-Yh83U2dh97xGy/view?usp=sharing, https://drive.google.com/file/d/1aN2bkRa-dZAq2Yk_j9UuRmzitLijQyCe/view?usp=sharing, https://drive.google.com/file/d/1c1J7bIqGbagP_MvSi4LhGkw4YSJVSYHd/view?usp=sharing, https://drive.google.com/file/d/1czJG9me_kMy_gBwxmHFkp7aXpMbt4Gu2/view?usp=sharing, https://drive.google.com/file/d/1eSsK378wdrm1_JktWeM31VwmguLi2oD5/view?usp=sharing, https://drive.google.com/file/d/1fBnu89LTruzfgXKLSsMgbu-VGHVA884O/view?usp=sharing, https://drive.google.com/file/d/1gIcTU8f0RrMaF4JeMtq4OS2rnNEXy2I-/view?usp=sharing, https://drive.google.com/file/d/1iqfp7oI--a3qcU0RYjF4Fj_lr1C9th5m/view?usp=sharing, https://drive.google.com/file/d/1lruvwREC1FbdcOXKtPaAaFLyK7IgKl8h/view?usp=sharing, https://drive.google.com/file/d/1oifnW3vZ9T_wtXs1gwtedAKocNi26ozn/view?usp=sharing, https://drive.google.com/file/d/1pJbMmLZPvvXhAt55k5teGwqxYjLYeF4K/view?usp=sharing, https://drive.google.com/file/d/1ygj3t1RRM2G1mTXEF4LneR54CAVRr1yj/view?usp=sharing

