# 🌿 The Scent - Premium Aromatherapy E-commerce Platform (v3.0)

Welcome to **The Scent**, a modern, full-featured, and beautifully crafted e-commerce platform built to showcase and sell premium natural aromatherapy products. This project is designed from the ground up for extensibility, security, and seamless user experience, featuring a custom MVC-inspired PHP architecture.

> 🧘 “Find your moment of calm” – Discover your perfect scent and enhance your well-being.

---

## 🚀 Live Demo

[🌐 **Click here to view the demo**](#) *(Placeholder - Add Your Live Demo Link Here)*

---

## ✨ Current Status (v3.0 - Functional Core, Needs Polish for Production)

*   ✅ **Core Functionality:** Product Browsing (with Pagination), Add-to-Cart (AJAX), Cart Management (AJAX with Updated UI), User Login/Registration (AJAX), Password Reset, Profile Update (Name, Email, Password, Newsletter), Scent Quiz & Results Display.
*   ✅ **Key Fixes Implemented:** Resolved fatal error preventing checkout page load (`User::getAddress` implemented). Resolved critical compatibility issues between `AccountController` and `User` model. Mobile navigation CSS bug fixed. Database schema patch provided for `users` table.
*   ⚠️ **Critical Issues Requiring Action for Production:**
    *   **Order Confirmation Flow:** Current reliance on session data set by asynchronous webhooks is **unreliable** and **must be reworked** (See Recommendation #1 in Future Enhancements).
    *   **Inconsistent Cart Storage:** Uses Session for guests and Database for logged-in users. **Standardization recommended** (See Recommendation #2).
    *   **Inconsistent Rate Limiting:** Security mechanism exists but is not applied consistently across all sensitive endpoints. **Standardization recommended** (See Recommendation #3).
*   ⚠️ **Other Known Issues/TODOs:**
    *   `User::getAddress()` fetches address data, but logic to *save* user addresses during profile update or checkout is not yet implemented. Pre-filling checkout depends on data existing in the DB.
    *   Content Security Policy (CSP) needs review/tightening.
    *   Error handling for "Headers Already Sent" needs refinement.
    *   Admin panel features are basic (Coupons, Quiz Analytics); full CRUD for Products/Orders/Users needed.
    *   Consideration for Composer adoption for dependency management.

---

## 🔖 Badges

![PHP](https://img.shields.io/badge/PHP-8.0+-blue?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-5.7+/8.0+-orange?logo=mysql)
![Apache](https://img.shields.io/badge/Apache-2.4+-red?logo=apache)
![Tailwind CSS](https://img.shields.io/badge/TailwindCSS-CDN-blue?logo=tailwindcss)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Functional/Dev-yellowgreen)

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

*   A clean, modern, responsive UI/UX powered by Tailwind CSS and subtle animations (AOS.js, Particles.js).
*   Personalized shopping via an interactive scent finder quiz.
*   Dynamic product catalog with categories, filtering, sorting, and functional pagination.
*   A functional shopping cart with AJAX updates, item removal, and an updated grid UI.
*   Secure user authentication (Login/Registration/Password Reset/Profile Update) with robust validation, utilizing AJAX where appropriate.
*   A modular PHP codebase (MVC-inspired) for easy customization and future growth.
*   Core checkout process flow up to payment initiation.

Designed for extensibility, performance, and user-centric experience, The Scent provides a solid foundation for wellness or natural product businesses. This README reflects the current state (**v3.0**), including recent critical fixes and identified areas needing improvement for production deployment.

---

## 🎯 Features

### 🛍️ Core E-commerce
*   ✅ **Modern Landing Page:** Engaging design with video background, particle effects, and scroll animations.
*   ✅ **Product Catalog:** Browse products with category filtering, sorting (name, price), price range filtering, and basic search.
*   ✅ **Product List Pagination:** Fully functional pagination.
*   ✅ **Product Detail Pages:** Rich content including image gallery, descriptions, attributes, related products.
*   ✅ **AJAX Add-to-Cart:** Add items from Home, Product List, and Detail pages without page reloads.
*   ✅ **Functional Cart Page:** Updated grid layout, supports AJAX quantity updates and item removal. *(Storage inconsistency needs review)*.
*   ✅ **Mini-Cart:** Header dropdown showing cart contents, updated via AJAX.
*   ✅ **Stock Validation:** Checks availability during Add-to-Cart and before rendering Checkout page.
*   ✅ **Responsive Design:** Adapts to various screen sizes. Mobile navigation fixed.

### 🔐 User Management
*   ✅ **User Authentication:** Functional Login and Registration (AJAX-based, secure password handling).
*   ✅ **Password Reset System:** Functional "Forgot Password" email flow and token-based password reset.
*   ✅ **User Profile Management:** View and update name, email, password, and newsletter preferences.
*   ✅ **Order History:** View past orders and details (*Display relies on orders being completed*).

### ✨ Personalization
*   ✅ **Scent Finder Quiz:** Interactive quiz to guide users to suitable products.
*   ✅ **Product Recommendations:** Displays relevant products based on quiz results or related items.

### 🛒 Shopping Experience
*   ✅ **Checkout Page Load:** Requires login, collects shipping info, **loads correctly**, pre-fills address if available in DB.
*   ✅ **Checkout AJAX:** Coupon application and tax calculation estimates functional via AJAX.
*   ✅ **Checkout Submission:** Server-side logic correctly validates stock/coupons, creates order, decrements inventory, creates Stripe Payment Intent within a transaction.
*   🚧 **Order Confirmation Page:** View exists, but the mechanism to display it after successful payment is **unreliable** and **needs rework**.

### 💼 Business Features *(Partially Implemented / Needs Integration)*
*   ✅ **Inventory Management:** Basic stock tracking fields (`products` table) and DB update logic (`InventoryController::updateStock`) exist.
*   ✅ **Tax System:** Basic tax calculation logic via `TaxController` functional for AJAX estimates.
*   ✅ **Coupon System:** Admin CRUD interface functional. Validation logic (`CouponController`) functional. Applied via AJAX in checkout. Server-side re-validation during final submission implemented.
*   ✅ **Email Notifications:** Functional system (`EmailService`) for Welcome, Password Reset. *(Order Confirmation/Shipping Update sending relies on successful payment completion & webhook handling)*. Requires SMTP configuration.

### 👑 Admin Features *(Modular, Basic)*
*   ✅ Requires 'admin' role. Basic RBAC checks in place.
*   ✅ **Quiz Analytics:** View basic quiz statistics (time-range filter functional).
*   ✅ **Coupon Management:** Basic CRUD interface functional, including AJAX toggle/delete.
*   🚧 *Further admin panels (Products, Orders, Users) require development.*

---

## 🖼️ Screenshots

> 📸 *Please add updated screenshots of the application here!*

*   *Landing Page:* `[Insert Screenshot: views/home.php]`
*   *Product List (with Pagination):* `[Insert Screenshot: views/products.php]`
*   *Product Detail:* `[Insert Screenshot: views/product_detail.php]`
*   *Cart Page (Updated UI):* `[Insert Screenshot: views/cart.php]`
*   *Login Page:* `[Insert Screenshot: views/login.php]`
*   *Register Page:* `[Insert Screenshot: views/register.php]`
*   *Checkout Page (Loading Correctly):* `[Insert Screenshot: views/checkout.php]`
*   *Account Dashboard:* `[Insert Screenshot: views/account/dashboard.php]`
*   *Admin Coupons:* `[Insert Screenshot: views/admin/coupons.php]`

---

## 🧱 System Architecture

**Custom MVC-Inspired Modular PHP Architecture:**

*(Mermaid diagram remains the same as in TDS v12 - showing Client -> Apache -> index.php -> Core Includes -> Controllers -> Models -> DB and back through Views/JSON/Redirect)*

*   **`index.php`:** Central entry point, routing via `switch`, core includes, **global POST CSRF validation**.
*   **`Controllers`:** Business logic, extend `BaseController`, interact with Models (PDO), select View/Response. **Must pass CSRF token to views needing it.**
*   **`Models`:** Database interaction via **PDO Prepared Statements**. `User` model updated.
*   **`Views`:** PHP templates, HTML output, receive data from Controllers. **Must output CSRF token to `#csrf-token-value` for JS.**
*   **`Includes`:** Core utilities (DB, Auth, Security, Error Handling, Email).
*   **`config.php`:** Configuration (DB, Security, API Keys).
*   **`js/main.js`:** Frontend interactivity, AJAX calls, **reads CSRF token from `#csrf-token-value`**.

---

## ⚙️ Technology Stack

| Layer            | Technology                                                                                                | Notes                                                              |
| :--------------- | :-------------------------------------------------------------------------------------------------------- | :----------------------------------------------------------------- |
| Frontend         | HTML5, Tailwind CSS (CDN), Custom CSS (`css/style.css`), JavaScript (Vanilla), Font Awesome 6 (CDN)           | Uses AOS.js & Particles.js. Mobile nav fixed.                    |
| Backend          | PHP 8.0+                                                                                                  | Core logic, MVC-inspired structure.                              |
| Web Server       | Apache 2.4+                                                                                               | Requires `mod_rewrite`.                                            |
| Database         | MySQL 5.7+ / 8.0+ (or MariaDB equivalent)                                                                   | Requires schema patch for `users` table.                         |
| Server-Side Libs | PDO                                                                                                       | For secure database access (Prepared Statements).                |
| Optional         | Composer                                                                                                  | Recommended for dependency management (PHPMailer, Stripe SDK).   |
|                  | APCu                                                                                                      | Used by rate limiting mechanism (requires consistent usage).     |

---

## 📁 Folder Structure

*(Reflects current structure - See TDS v12 for detailed map)*

Key directories: `controllers/`, `models/`, `views/`, `includes/`, `js/`, `css/`, `images/`, `videos/`, `logs/`, `db/`.

---

## 🗃️ Database Schema

*   Base schema: [`db/the_scent_schema.sql.txt`](db/the_scent_schema.sql.txt).
*   **IMPORTANT:** Apply the patch script [`db/the_scent_update_users_table.sql`](db/the_scent_update_users_table.sql) to update the `users` table with necessary columns (status, newsletter, reset tokens, address fields, timestamps).
*   **Key Tables:** `users` (updated), `products`, `categories`, `orders`, `order_items`, `cart_items` (inconsistent usage), `quiz_results`, `newsletter_subscribers`, `audit_log`, `coupons`, `coupon_usage`, `inventory_movements`, `tax_rates`, `tax_rate_history`.
*   **Addresses:** Stored directly in the `users` table. `User::getAddress()` is implemented. Saving addresses needs implementation.

*(Simplified ER Diagram remains the same as in TDS v12)*

---

## 📦 Installation Instructions

### Prerequisites
*   Web Server: Apache 2.4+ with `mod_rewrite` enabled.
*   PHP: 8.0 or higher.
*   Required PHP Extensions: `pdo_mysql`, `mbstring`, `openssl`, `json`, `session`, `fileinfo`, `apcu` (recommended for rate limiting).
*   Database: MySQL 5.7+ / 8.0+ or MariaDB equivalent.

### Steps
1.  **Clone Repository:** `git clone <your-repo-url> the-scent && cd the-scent`
2.  **Database Setup:**
    *   Create database & user (adjust credentials).
    *   Import the **base schema**: `mysql -u USER -p DBNAME < db/the_scent_schema.sql.txt`
    *   **Apply the `users` table update patch:** `mysql -u USER -p DBNAME < db/the_scent_update_users_table.sql`
3.  **Configuration:**
    *   Edit `config.php`: Set `DB_*` constants. Set `STRIPE_*` keys. Set `SMTP_*` constants. Review `BASE_URL`. Set `ENVIRONMENT` ('development' or 'production').
4.  **File Permissions:**
    *   Ensure web server user (e.g., `www-data`) has write access to `logs/`: `sudo chown www-data:www-data logs && sudo chmod 750 logs`
    *   Restrict access to `config.php`: `sudo chmod 640 config.php`
5.  **Apache Configuration:**
    *   Set up Virtual Host pointing `DocumentRoot` to the project root.
    *   Ensure `AllowOverride All` is set. Enable `mod_rewrite`. Restart Apache.
6.  **(Optional but Recommended) Composer:** Run `composer install` if using Composer for dependencies (e.g., PHPMailer, Stripe SDK). Ensure `index.php` includes `vendor/autoload.php`.
7.  **Access Site:** Browse to your configured URL.

---

## 🚀 Deployment Guide Summary

1.  Transfer files.
2.  Set up production DB, import schema, **apply `users` table patch**.
3.  Use secure production credentials in `config.php` (consider `.env`). Set `ENVIRONMENT` to `production`.
4.  Set strict file permissions (`logs/` writable).
5.  Configure production web server (Apache/Nginx).
6.  **Enable HTTPS**. Force HTTPS.
7.  Keep server software updated. Enable PHP OPcache.
8.  Test thoroughly, especially the Order Confirmation flow after implementing the recommended fix.

---

## 🛡️ Security Best Practices Implemented

*   ✅ **SQL Injection Prevention:** PDO Prepared Statements used exclusively.
*   ✅ **CSRF Protection:** Synchronizer Token Pattern implemented and enforced globally on POST. JS reads token correctly.
*   ✅ **XSS Prevention:** Input validation (`SecurityMiddleware`) and output escaping (`htmlspecialchars()`) used. CSP header applied (needs review).
*   ✅ **Authentication & Authorization:** Secure password hashing (`password_hash`/`verify`), Role checks (`isAdmin`, `requireAdmin`), secure password reset flow.
*   ✅ **Session Management:** Secure cookie flags (HttpOnly, Secure, SameSite=Lax), Session ID regeneration, Session integrity checks (IP/User Agent).
*   ✅ **Security Headers:** Standard headers applied (X-Frame-Options, X-Content-Type-Options, HSTS, etc.). **CSP needs review/tightening.**
*   ✅ **Error Handling:** Sensitive details suppressed in production. **"Headers Already Sent" issue noted.**
*   ✅ **Audit Logging:** Key security/user events logged.
*   🚧 **Rate Limiting:** Mechanism exists but **usage is inconsistent**. Relies on APCu. **Standardization recommended.**
*   ✅ **Payment Security:** Offloaded to Stripe Elements (PCI DSS). Webhook signature verification implemented.

---

## 🔧 Customization & Extensibility

*   **Adding Features:** Follow MVC pattern: Create Controller (extend `BaseController`), View(s), Model(s). Add route in `index.php`. **Implement CSRF token pattern** for POST actions.
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

*   **Libraries:** Tailwind CSS, AOS.js, Particles.js, Font Awesome
*   **Core Technologies:** PHP, MySQL, Apache, PDO
*   **Services:** Stripe (Payment Processing)
*   **Inspiration/Assistance:** Stack Overflow, PHP & MySQL Communities, OpenAI's GPT

---

## 📎 Appendix

### ✅ Key Functionality Status Checklist (v3.0)
*   [✅] User Registration & Login/Logout (AJAX)
*   [✅] Password Reset Flow (Email + Form)
*   [✅] User Profile View & Update (Name, Email, Password, Newsletter Pref)
*   [✅] Product Listing & Pagination
*   [✅] Product Filtering (Category, Price Range) & Sorting
*   [✅] Product Detail View
*   [✅] Add to Cart (AJAX - Home, List, Detail)
*   [✅] Cart Page View (Updated UI)
*   [✅] Cart Item Quantity Update / Removal (AJAX)
*   [✅] Checkout Page Load (Requires Login, Pre-fills Address)
*   [✅] Checkout AJAX (Coupon Apply, Tax Estimate)
*   [✅] Checkout Submission (Server-side validation, Order Creation, PI Creation, Inventory Update)
*   [✅] Scent Quiz & Results Display
*   [✅] Newsletter Signup (AJAX)
*   [✅] Basic Admin Coupon Management (CRUD Interface)
*   [✅] Basic Admin Quiz Analytics UI
*   [🚧] Order Confirmation Display (**Unreliable Flow - Needs Rework**)
*   [🚧] Cart Storage Consistency (**Needs Standardization**)
*   [🚧] Rate Limiting (**Needs Consistent Implementation**)
*   [🚧] Address Saving (**Needs Implementation in Profile/Checkout**)

### 🔮 Future Enhancements & Recommendations (Prioritized)
1.  **Rework Order Confirmation Flow (Critical Priority):** Remove session dependency, use URL parameters from Stripe redirect, verify PI status server-side.
2.  **Standardize Cart Storage (High Priority):** Use DB consistently for logged-in users.
3.  **Standardize Rate Limiting (Medium Priority):** Apply `BaseController::validateRateLimit()` consistently to sensitive actions.
4.  **Review & Tighten Content Security Policy (Medium Priority):** Update `config.php`.
5.  **Fix Error Handling ("Headers Already Sent") (Medium Priority):** Make error page self-contained or use output buffering.
6.  **Implement Address Saving (Medium Priority):** Add logic to save user addresses (profile/checkout).
7.  **Code Quality & Refactoring (Ongoing/Future):** Composer, Autoloader, Routing Component, Templating Engine, .env, Migrations, Tests.
8.  **Full Admin Panel (Future):** CRUD for Products, Orders, Users, etc.
9.  **Advanced Features (Future):** Search, Reviews, Wishlists.

---

## 📫 Contact

*   Project Maintainer/Support: `[Your Name/Email or GitHub Profile]`
*   GitHub Issues: `[Link to Project Issues Page]`

---

Built with ❤️ for aromatherapy enthusiasts.
