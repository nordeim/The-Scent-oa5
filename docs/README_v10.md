# 🌿 The Scent - Premium Aromatherapy E-commerce Platform

Welcome to **The Scent**, a modern, full-featured, and beautifully crafted e-commerce platform built to showcase and sell premium natural aromatherapy products. This project is designed from the ground up for extensibility, security, and seamless user experience, featuring a custom MVC-inspired PHP architecture.

> 🧘 “Find your moment of calm” – Discover your perfect scent and enhance your well-being.

---

## 🚀 Live Demo

[🌐 **Click here to view the demo**](#) *(Placeholder - Add Your Live Demo Link Here)*

---

## ✨ Current Status (v16.1 - Core Stable, Admin Product CRUD Added)

*   ✅ **Core Functionality Stable:** Product Browsing (Filtering/Sorting/Pagination OK), Add-to-Cart (AJAX OK), Cart Management (AJAX OK), User Login/Registration (**Registration OK**), Password Reset OK, Profile Update (Name, Email, Password, Newsletter, **Address OK**), Quiz Flow OK, Checkout Load OK, Order Confirmation OK.
*   ✅ **Critical Bug Fixes Implemented:**
    *   Resolved Checkout Page Load failure.
    *   Resolved Order Confirmation flow inaccuracies.
    *   Resolved Account Pages UI inconsistencies.
    *   Resolved Quiz CSRF Error & Redirects.
    *   Resolved Product Filter SQL Error (Mixed Placeholders).
    *   Resolved Quiz Results display inaccuracies.
    *   Resolved **Registration Failure** (DB Logging Error).
    *   Resolved **Profile Address Saving** (`UserModel` key mapping).
*   ✅ **Standardizations Applied:**
    *   **Cart Storage:** Consistent Session (Guest) vs. DB (User) handling via `CartController`.
    *   **Rate Limiting:** Applied consistently to key endpoints via `BaseController`.
    *   **Database Placeholders:** Standardized on named placeholders for filtering.
*   ✅ **UI Enhancements:**
    *   Account pages UI fixed.
    *   **Address Management UI on profile page fully functional (View/Edit/Save).**
    *   **Admin Product CRUD UI implemented** (List, Create, Edit, Delete views).
*   ✅ **Admin Functionality:**
    *   **Product CRUD functional** (List, Create, Edit, Delete via UI & Controller). JSON fields handled.
    *   Coupon CRUD functional.
    *   Quiz Analytics functional.
*   ⚠️ **Known Issues/TODOs:**
    *   **Error Handling ("Headers Already Sent"):** Minor issue mitigated, but potential edge cases remain. Consider making `views/error.php` self-contained.
    *   **Content Security Policy (CSP):** Needs review/tightening for production deployment.
    *   **Rate Limiting Coverage:** Review admin endpoints and other less critical areas.
    *   **Admin Panel Features:** Extend CRUD features (Orders, Users). Improve Quiz Analytics detail. Add Admin Dashboard content.
    *   **Code Quality/Refactoring:** Composer, Router, Templating, .env, Migrations, Tests recommended for future maintainability.
    *   **File Uploads:** Admin product form currently uses URL input; actual file upload needs implementation.

This document provides a comprehensive overview of the current architecture, logic, and flow, serving as an onboarding guide and reference for ongoing development.

---

## 🔖 Badges

![PHP](https://img.shields.io/badge/PHP-8.0+-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+/8.0+-orange?logo=mysql)
![Apache](https://img.shields.io/badge/Apache-2.4+-red?logo=apache)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-CDN-blue?logo=tailwindcss)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Core%20Stable/Admin%20Dev-yellowgreen)

---

## 📚 Table of Contents

1.  [🌟 Introduction](#-introduction)
2.  [🎯 Features](#-features)
3.  [🖼️ Screenshots](#-screenshots)
4.  [🧱 System Architecture](#-system-architecture)
5.  [⚙️ Technology Stack](#-technology-stack)
6.  [📁 Folder Structure](#-folder-structure)
7.  [🗃️ Database Schema](#-database-schema)
8.  [📦 Installation Instructions](#-installation-instructions)
9.  [🚀 Deployment Guide Summary](#-deployment-guide-summary)
10. [🛡️ Security Best Practices](#-security-best-practices)
11. [🔧 Customization & Extensibility](#-customization--extensibility)
12. [🤝 Contributing](#-contributing)
13. [📄 License](#-license)
14. [🙏 Credits](#-credits)
15. [📎 Appendix](#-appendix)

---

## 🌟 Introduction

**The Scent** is more than just an e-commerce platform — it’s an experience. Built specifically to support the sale and recommendation of **premium aromatherapy products**, the platform integrates:

*   A clean, modern, responsive UI/UX powered by Tailwind CSS and subtle animations.
*   Personalized shopping via an interactive scent finder quiz (**Functional**).
*   Dynamic product catalog with categories, filtering, sorting, and functional pagination (**Functional**).
*   A functional shopping cart with AJAX updates and a **standardized storage mechanism**.
*   Secure user authentication (**Registration Functional**, Login, Password Reset, Profile Update including **Address Management Functional**) with robust validation and **consistent rate limiting**.
*   A modular PHP codebase (MVC-inspired) for customization and growth.
*   A **stable core checkout process**, including page load, AJAX interactions, payment intent creation, and **reliable order confirmation display**.
*   Functional user account pages with a **fixed, consistent UI** and **functional address management**.
*   **Basic Admin panel** with functional **Product CRUD**, Coupon management, and Quiz Analytics.

Designed for extensibility, performance, and user-centric experience, The Scent provides a solid foundation for wellness or natural product businesses. This README reflects the current state (**v16.1**).

---

## 🎯 Features

### 🛍️ Core E-commerce
*   ✅ Modern Landing Page.
*   ✅ Product Catalog (Filtering, Sorting, Pagination, Search).
*   ✅ Product Detail Pages (Gallery, Description, Related).
*   ✅ AJAX Add-to-Cart.
*   ✅ Functional Cart Page (AJAX Quantity/Remove).
*   ✅ Standardized Cart Storage (Session vs. DB).
*   ✅ Mini-Cart (AJAX Updated).
*   ✅ Stock Validation (Add-to-Cart, Checkout).
*   ✅ Responsive Design (Mobile Nav Fixed).

### 🔐 User Management
*   ✅ User Authentication (**Registration Functional**, Login Functional - AJAX).
*   ✅ Password Reset System (Functional).
*   ✅ User Profile Management (Name, Email, Password, Newsletter Prefs - **Functional**).
*   ✅ **Address Management (View/Edit/Save via Profile Page - Functional)**.
*   ✅ Order History & Details View (Functional).
*   ✅ Account Pages UI (Fixed & Consistent).

### ✨ Personalization
*   ✅ Scent Finder Quiz (Functional Flow & Results Display).
*   ✅ Product Recommendations (Quiz-based & Related Items).

### 🛒 Shopping Experience
*   ✅ Checkout Page Load (Requires Login, Address Pre-fill).
*   ✅ Checkout AJAX (Coupon Apply, Tax Estimate).
*   ✅ Checkout Submission (Validation, Order Creation, Stripe PI, Inventory Decrement, Optional Address Save).
*   ✅ Order Confirmation Page (Functional & Reliable via Stripe Verification).

### 💼 Business Features
*   ✅ Inventory Management (Stock Tracking, Basic Admin Updates via Product Form, Audited movements via `InventoryController`).
*   ✅ Tax System (Basic Calculation via `TaxController`).
*   ✅ Coupon System (Admin CRUD, Checkout Application & Validation Functional).
*   ✅ Email Notifications (System Functional - Welcome, Reset, Order Confirmation via Webhook).

### 👑 Admin Features *(Modular, Basic)*
*   ✅ Requires 'admin' role.
*   ✅ **Product Management (CRUD Interface Functional - List, Create, Edit, Delete Views & Logic)**.
*   ✅ Coupon Management (CRUD Interface Functional).
*   ✅ Quiz Analytics (Basic View Functional).
*   🚧 *Further admin panels (Orders, Users) require development.*

### 🛡️ Security Features
*   ✅ CSRF Protection (Global POST Validation).
*   ✅ Input Validation/Sanitization (`SecurityMiddleware`).
*   ✅ Secure Session Management (Flags, Regen, Integrity Checks).
*   ✅ Security Headers (CSP needs review).
*   ✅ Password Hashing (`password_hash`/`verify`).
*   ✅ Webhook Security (Stripe Signature Verification).
*   ✅ Rate Limiting (Applied Consistently to Key Endpoints - Requires APCu).
*   ✅ SQL Injection Prevention (PDO Prepared Statements - Named Placeholders).
*   ✅ Audit Logging.
*   ✅ Error Handling (Production safe).

---

## 🖼️ Screenshots

> 📸 *Please add updated screenshots reflecting the **functional Registration**, **functional Profile Address form**, functional **Admin Product List/Form**, and stable core flows!*

*   *Landing Page:* `[Insert Screenshot: views/home.php]`
*   *Product List (Filtering Fixed):* `[Insert Screenshot: views/products.php]`
*   *Product Detail:* `[Insert Screenshot: views/product_detail.php]`
*   *Cart Page (Updated UI):* `[Insert Screenshot: views/cart.php]`
*   *Quiz Page (Functional):* `[Insert Screenshot: views/quiz.php]`
*   *Quiz Results (Functional):* `[Insert Screenshot: views/quiz_results.php]`
*   *Login Page:* `[Insert Screenshot: views/login.php]`
*   **Registration Page (Functional):** `[Insert Screenshot: views/register.php]`
*   *Checkout Page (Loading Correctly):* `[Insert Screenshot: views/checkout.php]`
*   *Order Confirmation Page (Functional):* `[Insert Screenshot: views/order_confirmation.php]`
*   *Account Dashboard (Fixed UI):* `[Insert Screenshot: views/account/dashboard.php]`
*   **Account Profile (Fixed UI + Functional Address Form):** `[Insert Screenshot: views/account/profile.php]`
*   *Admin Coupons:* `[Insert Screenshot: views/admin/coupons.php]`
*   **Admin Product List:** `[Insert Screenshot: views/admin/products.php]`
*   **Admin Product Form:** `[Insert Screenshot: views/admin/product_form.php]`

---

## 🧱 System Architecture

**Custom MVC-Inspired Modular PHP Architecture:**

*(Mermaid diagram remains the same - showing Client -> Apache -> index.php -> Core Includes -> Controllers -> Models -> DB and back through Views/JSON/Redirect)*

*   **`index.php`:** Central entry point, routing via `switch`, core includes, **global POST CSRF validation**.
*   **`Controllers`:** Business logic, extend `BaseController`, interact with Models (PDO), select View/Response. **Apply rate limiting where needed.**
*   **`Models`:** Database interaction via **PDO Prepared Statements (primarily named placeholders now)**.
*   **`Views`:** PHP templates, HTML output, receive data from Controllers.
*   **`Includes`:** Core utilities (DB, Auth, Security, Error Handling, Email).
*   **`config.php`:** Configuration (DB, Security, API Keys).
*   **`js/main.js`:** Frontend interactivity, AJAX calls, **reads CSRF token reliably**.
*   **Cart Storage:** Standardized: Session for Guests, DB (`cart_items`) for Users, managed by `CartController`.
*   **Rate Limiting:** Standardized: Applied via `BaseController` to key endpoints.

---

## ⚙️ Technology Stack

| Layer            | Technology                                                                                                | Notes                                                                            |
| :--------------- | :-------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------- |
| Frontend         | HTML5, Tailwind CSS (CDN), Custom CSS (`css/style.css`), JavaScript (Vanilla), Font Awesome 6 (CDN)           | Uses AOS.js & Particles.js. Account UI fixed. Admin Product UI added.            |
| Backend          | PHP 8.0+                                                                                                  | Core logic, MVC-inspired structure.                                            |
| Web Server       | Apache 2.4+                                                                                               | Requires `mod_rewrite`.                                                        |
| Database         | MySQL 5.7+ / 8.0+ (or MariaDB equivalent)                                                                   | **Requires schema patch for `users` table.**                                     |
| Server-Side Libs | PDO                                                                                                       | For secure database access (**Prepared Statements, Named Placeholders**).        |
| Optional         | Composer                                                                                                  | Recommended for dependency management (PHPMailer, Stripe SDK).                   |
|                  | APCu                                                                                                      | **Required** for implemented rate limiting feature (ensure PHP extension enabled). |

---

## 📁 Folder Structure

*(Reflects current structure - See TDS v15 for detailed map, includes new admin views)*

Key directories: `controllers/`, `models/`, `views/`, `includes/`, `js/`, `css/`, `images/`, `videos/`, `logs/`, `db/`. Includes `views/admin/products.php`, `views/admin/product_form.php`.

---

## 🗃️ Database Schema

*   Base schema: [`db/the_scent_schema.sql.txt`](db/the_scent_schema.sql.txt).
*   **MANDATORY:** Apply the patch script [`db/the_scent_update_users_table.sql`](db/the_scent_update_users_table.sql) to update the `users` table with necessary columns (status, newsletter, reset tokens, address fields, timestamps). **Failure to apply will cause errors.**
*   **Cart Storage:** `cart_items` table used for logged-in users. Session used for guests. This is the implemented standard.
*   **Addresses:** Stored directly in the `users` table via added columns. **Profile UI allows viewing/editing, backend saving is functional.** Checkout pre-fills from these columns and can save during order placement.

*(Simplified ER Diagram remains the same)*

---

## 📦 Installation Instructions

### Prerequisites
*   Web Server: Apache 2.4+ with `mod_rewrite` enabled.
*   PHP: 8.0 or higher.
*   Required PHP Extensions: `pdo_mysql`, `mbstring`, `openssl`, `json`, `session`, `fileinfo`, **`apcu`** (for rate limiting).
*   Database: MySQL 5.7+ / 8.0+ or MariaDB equivalent.

### Steps
1.  **Clone Repository:** `git clone <your-repo-url> the-scent && cd the-scent`
2.  **Database Setup:**
    *   Create database & user (adjust credentials).
    *   Import the **base schema**: `mysql -u USER -p DBNAME < db/the_scent_schema.sql.txt`
    *   **Apply the MANDATORY `users` table update patch:** `mysql -u USER -p DBNAME < db/the_scent_update_users_table.sql`
3.  **Configuration:**
    *   Edit `config.php`: Set `DB_*` constants. Set `STRIPE_*` keys. Set `SMTP_*` constants. Review `BASE_URL`. Set `ENVIRONMENT` ('development' or 'production').
4.  **File Permissions:**
    *   Ensure web server user (e.g., `www-data`) has write access to `logs/`: `sudo chown www-data:www-data logs && sudo chmod 750 logs`
    *   Restrict access to `config.php`: `sudo chmod 640 config.php`
5.  **Apache Configuration:**
    *   Set up Virtual Host pointing `DocumentRoot` to the project root.
    *   Ensure `AllowOverride All` is set. Enable `mod_rewrite`. Restart Apache.
6.  **PHP Configuration:** Ensure the `apcu` extension is enabled in your `php.ini` for rate limiting. Restart PHP-FPM/Apache if necessary.
7.  **(Optional but Recommended) Composer:** Run `composer install` if using Composer for dependencies (e.g., PHPMailer, Stripe SDK). Ensure `index.php` includes `vendor/autoload.php`.
8.  **Access Site:** Browse to your configured URL.
9.  **Initial Verification:**
    *   Check site loads.
    *   Test user **registration** and login.
    *   Verify checkout page loads.
    *   Test Scent Quiz submission and results page.
    *   Test product category filtering.
    *   Check Account Dashboard UI and **Profile page Address saving**.
    *   Test the full checkout flow including payment and order confirmation page.
    *   Verify Admin **Product List, Create, Edit, Delete** functionality.

---

## 🚀 Deployment Guide Summary

1.  Transfer files.
2.  Set up production DB, import schema, **apply MANDATORY `users` table patch**.
3.  Use secure production credentials in `config.php` (consider `.env`). Set `ENVIRONMENT` to `production`.
4.  Set strict file permissions (`logs/` writable).
5.  Configure production web server (Apache/Nginx).
6.  **Enable HTTPS**. Force HTTPS.
7.  **Enable APCu** PHP extension on the server.
8.  Keep server software updated. Enable PHP OPcache.
9.  **Test thoroughly**, especially checkout/confirmation, **registration**, **profile address saving**, quiz flow, filtering, cart merge, rate limits, **admin product CRUD**.

---

## 🛡️ Security Best Practices Implemented

*   ✅ SQL Injection Prevention (PDO Prepared Statements - Named Placeholders).
*   ✅ CSRF Protection (Synchronizer Token Pattern, global POST validation).
*   ✅ XSS Prevention (Input Validation, Output Escaping, CSP header).
*   ✅ Authentication & Authorization (Secure Hashing, Roles, Secure Reset).
*   ✅ Session Management (Secure Flags, Regen, Integrity Checks).
*   ✅ Security Headers (CSP needs review).
*   ✅ Webhook Security (Stripe Signature Verification).
*   ✅ Error Handling (Production safe).
*   ✅ Audit Logging.
*   ✅ Rate Limiting (Applied Consistently to Key Endpoints - Requires APCu).

---

## 🔧 Customization & Extensibility

*   **Adding Features:** Follow MVC pattern: Controller -> View -> `index.php` route. Implement CSRF token pattern for POST. Extend `BaseController`. Apply rate limiting if needed.
*   **Adding Products/Categories:** Update database or use Admin UI.
*   **Styling:** Modify Tailwind classes or `css/style.css`.

---

## 🤝 Contributing

*(Standard contribution guidelines)*

*   **Code Standards:** PSR-12 PHP. Semantic HTML. Tailwind preferred.
*   **Branching:** Feature/bugfix branches.
*   **Commits:** Clear messages.
*   **Pull Requests:** Clear descriptions. Test functionality.
*   **Issues:** Report via project's Issue Tracker `[Link to Issues]`.

---

## 📄 License

Distributed under the **MIT License**. See the `LICENSE` file for details (assuming MIT).

---

## 🙏 Credits

*   **Libraries:** Tailwind CSS, AOS.js, Particles.js, Font Awesome, PHPMailer (if used), Stripe PHP SDK (if used)
*   **Core Technologies:** PHP, MySQL, Apache, PDO, APCu
*   **Services:** Stripe (Payment Processing)
*   **Inspiration/Assistance:** Stack Overflow, PHP & MySQL Communities, OpenAI's GPT

---

## 📎 Appendix

### A. Key File Summaries

*(Updated status notes for v16.1)*

| File/Folder                     | Purpose                                                          | Status Notes (v16.1)                                                                             |
| :------------------------------ | :--------------------------------------------------------------- | :----------------------------------------------------------------------------------------------- |
| `index.php`                     | Entry point, routing, core includes, auto POST CSRF validation   | OK. Admin Product routing functional.                                                            |
| `config.php`                    | DB credentials, App/Security settings, API keys                | OK. CSP/Rate Limit review needed. Secrets exposure.                                              |
| `includes/SecurityMiddleware.php`| Static security helpers (validation, CSRF)                     | OK.                                                                                              |
| `controllers/BaseController.php`| Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)       | OK. `redirect` fixed. Rate limiting logic OK.                                                    |
| `controllers/AccountController.php`| User auth/profile logic. AJAX/standard POST.                   | **Functional.** Rate limiting applied. **Registration fixed.** **Profile Address saving fixed.**         |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                              | **Loads OK. Confirmation Flow Fixed.** Rate limiting applied.                                    |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                           | OK. Webhook session dependency removed. `getStripeClient()` available.                         |
| `controllers/CartController.php`    | Handles cart logic via AJAX.                                     | **Functional.** **Cart storage standardized.** Reliable session count update.                    |
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | **Functional.** Filtering uses Named Placeholders. Admin routing/CRUD logic OK. JSON parsing added. |
| `controllers/QuizController.php`    | Quiz logic.                                                    | **Functional.** **CSRF fixed. Results logic uses Session.**                                        |
| `controllers/NewsletterController.php`| Newsletter signup/unsubscribe.                               | OK. Rate limiting applied.                                                                       |
| `models/User.php`               | User DB logic (**PDO Prepared Statements**).                     | **Updated & Functional.** **`updateAddress` fixed.** `getAddress` OK. Requires patch.                |
| `models/Order.php`              | Order DB logic (**PDO Prepared Statements**).                    | OK.                                                                                              |
| `models/Product.php`            | Product DB logic (**PDO Prepared Statements**).                  | **Functional.** Filtering uses Named Placeholders. Compatible with controller. OK.          |
| `models/Cart.php`               | DB cart logic (**PDO Prepared Statements**).                     | OK. Used only for logged-in users now.                                                         |
| `models/Quiz.php`               | Quiz DB logic (**PDO Prepared Statements**).                     | **Functional.** **`recommendations` column selected correctly.**                                 |
| `views/layout/header.php`       | Header, nav, assets, outputs global CSRF token.                | OK. Cart count logic simplified.                                                                 |
| `views/account/*.php`           | Account views (Dashboard, Orders, Profile, etc.)                 | **UI Fixed.** Profile address UI functional. Compatible with CSS/JS. OK.                         |
| `views/admin/products.php`      | Admin: List products view.                                       | **Functional.** Displays data, provides actions.                                                 |
| `views/admin/product_form.php`  | Admin: Create/Edit product form view.                            | **Functional.** Displays fields, pre-fills on edit. Controller handles JSON fields.              |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                              |
| `views/checkout.php`            | Checkout form view.                                              | **Loads OK.** Uses `$userAddress`. Defensive coding added. AJAX/Stripe OK.                      |
| `views/order_confirmation.php`  | Confirmation view.                                               | **Functional.** Controller logic fixed.                                                        |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token.                                                              |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                    |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Correctly handles CSRF for AJAX. Compatible with controllers.                                |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted.                                                        |
| `includes/EmailService.php`     | Sends emails (Welcome, Reset, Confirmation, etc.)              | **Functional.** **DB logging column fixed.**                                                     |
| `db/*`                          | Schema files.                                                    | **Update script is mandatory.**                                                                  |

### B. Glossary

(Standard terms)

### C. Code Snippets and Patterns (CSRF, Confirmation, Named Placeholders, Address Handling, JSON Textarea Parsing)

**CSRF Token Pattern:** *(Unchanged)*

**Implemented Order Confirmation Flow:** *(Unchanged - flow description is correct)*

**Named Placeholder Pattern (Example from `ProductModel::getFiltered`)** *(Unchanged)*

**Address Handling Pattern (Functional):** *(Unchanged - pattern description is correct)*

**JSON Textarea Parsing (Implemented in `ProductController::saveAdminProduct`):**

```php
// Example from ProductController::saveAdminProduct showing parsing logic

// --- START: Parse Textareas for JSON Fields ---
$benefitsInput = $_POST['benefits'] ?? '';
// Split by any newline type, trim whitespace from each line, filter out empty lines, re-index numerically
$data['benefits'] = !empty($benefitsInput)
    ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $benefitsInput))))
    : []; // Default to empty array if textarea was empty

$galleryInput = $_POST['gallery_images'] ?? '';
$data['gallery_images'] = !empty($galleryInput)
    ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $galleryInput))))
    : []; // Default to empty array
// --- END: Parse Textareas for JSON Fields ---

// Pass $data array (now containing PHP arrays for 'benefits' and 'gallery_images')
// to the ProductModel's create/update methods.
// The model handles json_encode() before saving to the database.
// $success = $this->productModel->update($productId, $data); or ->create($data);
```

### D. Mandatory Database Patch

*(Content remains the same - emphasize its importance)*

