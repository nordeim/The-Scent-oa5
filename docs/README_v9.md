# 🌿 The Scent - Premium Aromatherapy E-commerce Platform (v4.0 - Core Stable, UI Enhanced)

Welcome to **The Scent**, a modern, full-featured, and beautifully crafted e-commerce platform built to showcase and sell premium natural aromatherapy products. This project is designed from the ground up for extensibility, security, and seamless user experience, featuring a custom MVC-inspired PHP architecture.

> 🧘 “Find your moment of calm” – Discover your perfect scent and enhance your well-being.

---

## 🚀 Live Demo

[🌐 **Click here to view the demo**](#) *(Placeholder - Add Your Live Demo Link Here)*

---

## ✨ Current Status (v4.0 - Core Stable, Key Standardizations, Address UI Added)

*   ✅ **Core Functionality Stable:** Product Browsing (**Filtering/Sorting/Pagination OK**), Add-to-Cart (AJAX OK), Cart Management (AJAX OK), User Login/Registration (AJAX OK), Password Reset OK, Profile Update (Name/Email/Password/Newsletter OK), **Quiz Flow OK**, **Checkout Load OK**, **Order Confirmation OK**.
*   ✅ **Critical Bug Fixes Implemented:**
    *   Resolved **fatal error preventing checkout page load**.
    *   Resolved **critical flaw in Order Confirmation flow** (now uses Stripe API verification).
    *   Resolved **broken UI on Account pages** (`/index.php?page=account*`). Layout now consistent.
    *   Resolved **CSRF error on Scent Quiz submission**. Quiz flow is now functional.
    *   Resolved **SQL error on Product Category filtering** (Mixed named/positional parameters fixed).
    *   Resolved **Incorrect Quiz Results Display** (Now correctly shows products recommended during submission via session).
    *   Resolved **Redirect error after Quiz submission**.
    *   Mobile navigation CSS bug fixed.
*   ✅ **Standardizations Applied:**
    *   **Cart Storage:** Now consistently uses `$_SESSION` for Guests and the Database (`cart_items` table) for Logged-in users, managed by `CartController`. `$_SESSION['cart_count']` is reliably updated for header display.
    *   **Rate Limiting:** Now consistently applied via `BaseController::validateRateLimit()` to key sensitive endpoints (Login, Register, Password Reset Request/Attempt, Profile Update, Newsletter Subscribe, Coupon Apply, Checkout Submit). Relies on APCu extension.
    *   **Database Placeholders:** Standardized on named placeholders (`:param`) in `ProductModel` filtering methods to prevent SQL errors.
*   ✅ **UI Enhancements:**
    *   **Address Management UI Added:** The user profile page (`views/account/profile.php`) now includes a form section for users to view and *enter* their shipping address.
*   🚧 **Partially Implemented Features:**
    *   **Address Saving Logic (Backend Pending):** While the UI exists to *enter* an address on the profile page, the backend logic in `AccountController` to *process and save* this address from the profile form is **required**. The logic to *pre-fill* the checkout form from the DB and the logic to optionally *save* the address *during* checkout submission (`CheckoutController` calling `User::updateAddress`) exist.
*   ⚠️ **Other Known Issues/TODOs:**
    *   **Error Handling ("Headers Already Sent"):** Issue mitigated in `ErrorHandler.php`, but potential edge cases might remain if output starts before error handling. Consider making `views/error.php` self-contained.
    *   **Content Security Policy (CSP):** Needs review/tightening for production.
    *   **Rate Limiting Coverage:** While applied to key areas, a full review for other potentially sensitive endpoints (e.g., admin actions) is recommended.
    *   **Admin Panel Features:** Basic features exist (Coupons, Quiz Analytics, Product List/Form); full CRUD for Products/Orders/Users needed.
    *   **Code Quality/Refactoring:** Composer integration, dedicated router, templating engine, `.env` files, migrations, and tests are recommended for future maintainability.

---

## 🔖 Badges

![PHP](https://img.shields.io/badge/PHP-8.0+-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+/8.0+-orange?logo=mysql)
![Apache](https://img.shields.io/badge/Apache-2.4+-red?logo=apache)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-CDN-blue?logo=tailwindcss)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Core%20Stable/Dev-yellowgreen)

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
*   Personalized shopping via an interactive scent finder quiz (**Functional**, **Results Display Fixed**).
*   Dynamic product catalog with categories, filtering, sorting, and functional pagination (**Functional, Filtering Fixed**).
*   A functional shopping cart with AJAX updates and a **standardized storage mechanism** (Session for guests, DB for users).
*   Secure user authentication (Login/Registration/Password Reset/Profile Update) with robust validation and **consistent rate limiting** applied to sensitive actions.
*   A modular PHP codebase (MVC-inspired) for customization and growth.
*   A **stable core checkout process**, including page load (**Fixed**), AJAX interactions, payment intent creation, and **reliable order confirmation display (Fixed)**.
*   Functional user account pages with a **fixed, consistent UI** and **address management UI**.

Designed for extensibility, performance, and user-centric experience, The Scent provides a solid foundation for wellness or natural product businesses. This README reflects the current state (**v4.0**), including critical fixes, UI enhancements, and standardizations, while highlighting areas like backend address saving that require further implementation.

---

## 🎯 Features

### 🛍️ Core E-commerce
*   ✅ **Modern Landing Page:** Engaging design with video background, particle effects, scroll animations.
*   ✅ **Product Catalog:** Browse products with category filtering (**Fixed**), sorting (name, price), price range filtering, basic search.
*   ✅ **Product List Pagination:** Fully functional pagination.
*   ✅ **Product Detail Pages:** Rich content including image gallery, descriptions, attributes, related products.
*   ✅ **AJAX Add-to-Cart:** Add items from Home, Product List, Detail pages without page reloads.
*   ✅ **Functional Cart Page:** Updated grid layout, supports AJAX quantity updates and item removal.
*   🔄 **Standardized Cart Storage:** Uses Session for guests, Database for logged-in users, managed reliably via `CartController`. Header count reliable.
*   ✅ **Mini-Cart:** Header dropdown showing cart contents, updated via AJAX (reflects standardized count).
*   ✅ **Stock Validation:** Checks availability during Add-to-Cart and before rendering Checkout page.
*   ✅ **Responsive Design:** Adapts to various screen sizes. Mobile navigation fixed.

### 🔐 User Management
*   ✅ **User Authentication:** Functional Login and Registration (AJAX-based, secure password handling).
*   ✅ **Password Reset System:** Functional "Forgot Password" email flow and token-based password reset.
*   ✅ **User Profile Management:** View and update name, email, password, newsletter preferences.
*   ✅ **Order History:** View past orders and details.
*   ✅ **Account Pages UI:** Consistent layout applied (**Fixed**).
*   🚧 **Address Management:** **UI Added** to profile page for viewing/entering address. Backend method `User::updateAddress` exists. Checkout includes "Save Address" checkbox and logic to save *during checkout*. **Backend logic to save address *from profile form* needs implementation.**

### ✨ Personalization
*   ✅ **Scent Finder Quiz:** Interactive quiz to guide users to suitable products (**Functional, CSRF & Redirect Fixed**).
*   ✅ **Product Recommendations:** Displays relevant products based on quiz results (**Results Display Fixed**) or related items.

### 🛒 Shopping Experience
*   ✅ **Checkout Page Load:** Requires login, collects shipping info, **loads correctly**, pre-fills address if available in DB.
*   ✅ **Checkout AJAX:** Coupon application and tax calculation estimates functional via AJAX.
*   ✅ **Checkout Submission:** Server-side logic correctly validates stock/coupons, creates order, decrements inventory, creates Stripe Payment Intent within a transaction. Optionally saves address.
*   ✅ **Order Confirmation Page:** View exists, **logic is now reliable (Fixed)**, verifying payment via Stripe API before display.

### 💼 Business Features *(Partially Implemented / Needs Integration)*
*   ✅ **Inventory Management:** Basic stock tracking fields (`products` table) and DB update logic (`InventoryController::updateStock` uses audit trail).
*   ✅ **Tax System:** Basic tax calculation logic via `TaxController` functional for AJAX estimates.
*   ✅ **Coupon System:** Admin CRUD interface functional. Validation logic (`CouponController`) functional. Applied via AJAX in checkout. Server-side re-validation during final submission implemented.
*   ✅ **Email Notifications:** Functional system (`EmailService`) for Welcome, Password Reset. Order Confirmation sending via webhook functional. *(Shipping Update sending logic exists but depends on admin action)*. Requires SMTP configuration.

### 👑 Admin Features *(Modular, Basic)*
*   ✅ Requires 'admin' role. Basic RBAC checks in place.
*   ✅ **Quiz Analytics:** View basic quiz statistics (time-range filter functional).
*   ✅ **Coupon Management:** Basic CRUD interface functional, including AJAX toggle/delete.
*   ✅ **Product Management:** Basic list, create, edit, delete forms/logic functional.
*   🚧 *Further admin panels (Orders, Users) require development.*

### 🛡️ Security Features
*   ✅ **CSRF Protection:** Synchronizer Token Pattern globally enforced on POST requests.
*   ✅ **Input Validation/Sanitization:** Applied via `SecurityMiddleware::validateInput`.
*   ✅ **Secure Session Management:** HttpOnly, Secure flags, SameSite=Lax, ID Regeneration, IP/UA binding checks.
*   ✅ **Prepared Statements:** Protection against SQL Injection (**Named placeholders** used for filtering).
*   ✅ **Security Headers:** Standard headers applied (CSP needs review).
*   ✅ **Password Hashing:** Uses `password_hash`/`password_verify`.
*   ✅ **Webhook Security:** Stripe signature verification implemented.
*   🔄 **Rate Limiting:** **Applied consistently** to Login, Register, Password Reset, Profile Update, Newsletter Subscribe, Coupon Apply, Checkout Submit via `BaseController::validateRateLimit()`. (Relies on APCu).

---

## 🖼️ Screenshots

> 📸 *Please add updated screenshots reflecting the **fixed Account UI**, **functional Quiz Results**, **functional Product Filtering**, **functional Checkout/Confirmation**, and stable core flows!*

*   *Landing Page:* `[Insert Screenshot: views/home.php]`
*   *Product List (Filtering Fixed):* `[Insert Screenshot: views/products.php]`
*   *Product Detail:* `[Insert Screenshot: views/product_detail.php]`
*   *Cart Page (Updated UI):* `[Insert Screenshot: views/cart.php]`
*   *Quiz Page (Functional):* `[Insert Screenshot: views/quiz.php]`
*   *Quiz Results (Functional):* `[Insert Screenshot: views/quiz_results.php]`
*   *Login Page:* `[Insert Screenshot: views/login.php]`
*   *Checkout Page (Loading Correctly):* `[Insert Screenshot: views/checkout.php]`
*   *Order Confirmation Page (Functional):* `[Insert Screenshot: views/order_confirmation.php]`
*   *Account Dashboard (Fixed UI):* `[Insert Screenshot: views/account/dashboard.php]`
*   *Account Profile (Fixed UI + Address Form):* `[Insert Screenshot: views/account/profile.php]`
*   *Admin Coupons:* `[Insert Screenshot: views/admin/coupons.php]`

---

## 🧱 System Architecture

**Custom MVC-Inspired Modular PHP Architecture:**

*(Mermaid diagram remains the same - showing Client -> Apache -> index.php -> Core Includes -> Controllers -> Models -> DB and back through Views/JSON/Redirect)*

*   **`index.php`:** Central entry point, routing via `switch`, core includes, **global POST CSRF validation**.
*   **`Controllers`:** Business logic, extend `BaseController`, interact with Models (PDO), select View/Response. **Apply rate limiting where needed.**
*   **`Models`:** Database interaction via **PDO Prepared Statements (primarily named placeholders)**.
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
| Frontend         | HTML5, Tailwind CSS (CDN), Custom CSS (`css/style.css`), JavaScript (Vanilla), Font Awesome 6 (CDN)           | Uses AOS.js & Particles.js. **Account UI fixed.**                                |
| Backend          | PHP 8.0+                                                                                                  | Core logic, MVC-inspired structure.                                            |
| Web Server       | Apache 2.4+                                                                                               | Requires `mod_rewrite`.                                                        |
| Database         | MySQL 5.7+ / 8.0+ (or MariaDB equivalent)                                                                   | **Requires schema patch for `users` table.**                                     |
| Server-Side Libs | PDO                                                                                                       | For secure database access (**Prepared Statements, Named Placeholders**).        |
| Optional         | Composer                                                                                                  | Recommended for dependency management (PHPMailer, Stripe SDK).                   |
|                  | APCu                                                                                                      | **Required** for implemented rate limiting feature (ensure PHP extension enabled). |

---

## 📁 Folder Structure

*(Reflects current structure - See TDS v15 for detailed map)*

Key directories: `controllers/`, `models/`, `views/`, `includes/`, `js/`, `css/`, `images/`, `videos/`, `logs/`, `db/`.

---

## 🗃️ Database Schema

*   Base schema: [`db/the_scent_schema.sql.txt`](db/the_scent_schema.sql.txt).
*   **MANDATORY:** Apply the patch script [`db/the_scent_update_users_table.sql`](db/the_scent_update_users_table.sql) to update the `users` table with necessary columns (status, newsletter, reset tokens, address fields, timestamps). **Failure to apply will cause errors.**
*   **Cart Storage:** `cart_items` table used for logged-in users. Session used for guests. This is the implemented standard.
*   **Addresses:** Stored directly in the `users` table via added columns. **Profile UI allows editing, backend saving logic pending.** Checkout pre-fills from these columns and can save during order placement.

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
    *   Check that the site loads without errors.
    *   Test user registration and login.
    *   Verify the checkout page loads after logging in.
    *   **Test the Scent Quiz submission and verify results page shows products.**
    *   **Test product category filtering.**
    *   **Check Account Dashboard UI and Profile page UI (including address section).**
    *   **Test the full checkout flow including successful payment and order confirmation page display.**

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
9.  **Test thoroughly**, especially the **full checkout and order confirmation flow**, account management (profile info, password, address UI), **quiz flow and results display**, **product filtering**, cart login/logout merge, and rate limits.

---

## 🛡️ Security Best Practices Implemented

*   ✅ **SQL Injection Prevention:** PDO Prepared Statements used exclusively (**Named placeholders** for filtering).
*   ✅ **CSRF Protection:** Synchronizer Token Pattern implemented and enforced globally on POST.
*   ✅ **XSS Prevention:** Input validation (`SecurityMiddleware`) and output escaping (`htmlspecialchars()`) used. CSP header applied (needs review).
*   ✅ **Authentication & Authorization:** Secure password hashing, Role checks, secure password reset flow.
*   ✅ **Session Management:** Secure cookie flags, Session ID regeneration, Session integrity checks.
*   ✅ **Security Headers:** Standard headers applied (CSP needs review).
*   ✅ **Password Hashing:** Uses `password_hash`/`password_verify`.
*   ✅ **Webhook Security:** Stripe signature verification implemented.
*   ✅ **Error Handling:** Sensitive details suppressed in production. "Headers Already Sent" issue noted.
*   ✅ **Audit Logging:** Key security/user events logged.
*   ✅ **Rate Limiting:** **Applied consistently** to key sensitive endpoints (Login, Register, Password Reset, Profile Update, Newsletter Subscribe, Coupon Apply, Checkout Submit). Relies on APCu.

---

## 🔧 Customization & Extensibility

*   **Adding Features:** Follow MVC pattern: Create Controller (extend `BaseController`), View(s), Model(s). Add route in `index.php`. Implement CSRF token pattern for POST. Apply rate limiting if needed.
*   **Adding Products/Categories:** Update database. Admin UI is basic.
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

### ✅ Key Functionality Status Checklist (v4.0)
*   [✅] User Registration & Login/Logout (AJAX)
*   [✅] Password Reset Flow (Email + Form)
*   [✅] User Profile View & Update (Name, Email, Password, Newsletter Pref)
*   [✅] Product Listing & Pagination
*   [✅] Product Filtering (Category, Price Range) & Sorting (**Fixed**)
*   [✅] Product Detail View
*   [✅] Add to Cart (AJAX - Home, List, Detail)
*   [✅] Cart Page View (Updated UI)
*   [✅] Cart Item Quantity Update / Removal (AJAX)
*   🔄 **Cart Storage Standardized (Session vs. DB)**
*   ✅ Checkout Page Load (Requires Login, Pre-fills Address, **Loads OK**)
*   ✅ Checkout AJAX (Coupon Apply, Tax Estimate)
*   ✅ Checkout Submission (Server-side validation, Order Creation, PI Creation, Inventory Update, Optional Address Save)
*   🚧 Address Saving (**Profile UI Added**, Backend Saving Logic Pending)
*   ✅ Order Confirmation Display (**Reliable Flow Implemented**)
*   ✅ Scent Finder Quiz & Results Display (**Functional, CSRF & Redirect Fixed, Results Fixed**)
*   ✅ Newsletter Signup (AJAX)
*   ✅ Basic Admin Coupon Management (CRUD Interface)
*   ✅ Basic Admin Quiz Analytics UI
*   ✅ Account Pages UI (**Fixed**)
*   🔄 **Rate Limiting Applied to Key Endpoints**

### 🔮 Future Enhancements & Recommendations (Prioritized)
1.  **Implement Profile Address Saving (High Priority):** Add backend logic in `AccountController` to process and save address updates from the profile form.
2.  **Review & Tighten Content Security Policy (Medium Priority):** Update CSP in `config.php`. Avoid `'unsafe-inline'` if possible.
3.  **Fix Error Handling ("Headers Already Sent") (Low Priority):** Further investigate and refine `ErrorHandler.php` or make `views/error.php` self-contained.
4.  **Review Rate Limiting Coverage (Low Priority):** Ensure all potentially sensitive endpoints are covered.
5.  **Code Quality & Refactoring (Ongoing/Future):** Composer, Autoloader, Routing Component, Templating Engine, .env, Migrations, Tests.
6.  **Full Admin Panel (Future):** CRUD for Products, Orders, Users, etc.
7.  **Advanced Features (Future):** Search improvements, user reviews, wishlists.

---

## 📫 Contact

*   Project Maintainer/Support: `[Your Name/Email or GitHub Profile]`
*   GitHub Issues: `[Link to Project Issues Page]`

---

Built with ❤️ for aromatherapy enthusiasts.
