# The Scent – Technical Design Specification (v16)

## Table of Contents

1.  [Introduction](#introduction)
2.  [Project Philosophy & Goals](#project-philosophy--goals)
3.  [System Architecture Overview](#system-architecture-overview)
    *   3.1 [High-Level Workflow](#high-level-workflow)
    *   3.2 [Request-Response Life Cycle (Implemented Confirmation Flow)](#request-response-life-cycle-implemented-confirmation-flow)
4.  [Directory & File Structure](#directory--file-structure)
    *   4.1 [Folder Map](#folder-map)
    *   4.2 [Key Files Explained](#key-files-explained)
5.  [Routing and Application Flow](#routing-and-application-flow)
    *   5.1 [URL Routing via .htaccess](#url-routing-via-htaccess)
    *   5.2 [index.php: The Application Entry Point](#indexphp-the-application-entry-point)
    *   5.3 [Controller Dispatch & Action Flow](#controller-dispatch--action-flow)
    *   5.4 [Views: Templating and Rendering](#views-templating-and-rendering)
6.  [Frontend Architecture](#frontend-architecture)
    *   6.1 [CSS (css/style.css), Tailwind (CDN), and Other Libraries](#css-cssstylecss-tailwind-cdn-and-other-libraries)
    *   6.2 [Responsive Design and Accessibility](#responsive-design-and-accessibility)
    *   6.3 [JavaScript: Interactivity, Libraries, and CSRF Handling](#javascript-interactivity-libraries-and-csrf-handling)
7.  [Key Pages & Components](#key-pages--components)
    *   7.1 [Home/Landing Page (views/home.php)](#homelanding-page-viewshomephp)
    *   7.2 [Header and Navigation (views/layout/header.php)](#header-and-navigation-viewslayoutheaderphp)
    *   7.3 [Footer and Newsletter (views/layout/footer.php)](#footer-and-newsletter-viewslayoutfooterphp)
    *   7.4 [Product Grid & Cards](#product-grid--cards)
    *   7.5 [Shopping Cart (views/cart.php)](#shopping-cart-viewscartphp)
    *   7.6 [Product Detail Page (views/product_detail.php)](#product-detail-page-viewsproduct_detailphp)
    *   7.7 [Products Page (views/products.php)](#products-page-viewsproductsphp)
    *   7.8 [Checkout Process (views/checkout.php)](#checkout-process-viewscheckoutphp)
    *   7.9 [Order Confirmation (views/order_confirmation.php)](#order-confirmation-viewsorder_confirmationphp)
    *   7.10 [User Account Pages (views/account/*)](#user-account-pages-viewsaccount)
    *   7.11 [Quiz Flow & Personalization](#quiz-flow--personalization)
8.  [Backend Logic & Core PHP Components](#backend-logic--core-php-components)
    *   8.1 [Includes: Shared Logic (includes/)](#includes-shared-logic-includes)
    *   8.2 [Controllers: Business Logic Layer (controllers/ & BaseController.php)](#controllers-business-logic-layer-controllers--basecontrollerphp)
    *   8.3 [Database Abstraction (includes/db.php & models/)](#database-abstraction-includesdbphp--models)
    *   8.4 [Security Middleware & Error Handling](#security-middleware--error-handling)
    *   8.5 [Session, Auth, and User Flow](#session-auth-and-user-flow)
    *   8.6 [Payment Processing & Webhook Handling](#payment-processing--webhook-handling)
9.  [Database Design](#database-design)
    *   9.1 [Entity-Relationship Model (Conceptual)](#entity-relationship-model-conceptual)
    *   9.2 [Core Tables (from schema.sql + Updates)](#core-tables-from-schemasql--updates)
    *   9.3 [Schema Considerations & Recommendations](#schema-considerations--recommendations)
    *   9.4 [Data Flow Examples (Current State)](#data-flow-examples-current-state)
10. [Security Considerations & Best Practices](#security-considerations--best-practices)
    *   10.1 [Input Sanitization & Validation](#input-sanitization--validation)
    *   10.2 [Session Management](#session-management)
    *   10.3 [CSRF Protection (Implemented - Strict Pattern Required)](#csrf-protection-implemented---strict-pattern-required)
    *   10.4 [Security Headers & CSP Standardization](#security-headers--csp-standardization)
    *   10.5 [Rate Limiting (Applied to Key Endpoints)](#rate-limiting-applied-to-key-endpoints)
    *   10.6 [File Uploads & Permissions](#file-uploads--permissions)
    *   10.7 [Audit Logging & Error Handling](#audit-logging--error-handling)
    *   10.8 [SQL Injection Prevention](#sql-injection-prevention)
    *   10.9 [Payment Security](#payment-security)
11. [Extensibility & Onboarding](#extensibility--onboarding)
    *   11.1 [Adding Features, Pages, or Controllers](#adding-features-pages-or-controllers)
    *   11.2 [Adding Products, Categories, and Quiz Questions](#adding-products-categories-and-quiz-questions)
    *   11.3 [Developer Onboarding Checklist](#developer-onboarding-checklist)
    *   11.4 [Testing & Debugging Notes](#testing--debugging-notes)
12. [Future Enhancements & Recommendations](#future-enhancements--recommendations)
13. [Appendices](#appendices)
    *   A. [Key File Summaries](#a-key-file-summaries)
    *   B. [Glossary](#b-glossary)
    *   C. [Code Snippets and Patterns (CSRF, Confirmation, Named Placeholders, Address Handling, JSON Textarea Parsing)](#c-code-snippets-and-patterns-csrf-confirmation-named-placeholders-address-handling-json-textarea-parsing)
    *   D. [Mandatory Database Patch](#d-mandatory-database-patch)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v16.1**) serves as the updated technical design specification, reflecting the project's current state after applying significant fixes, standardizations, and basic Admin Product CRUD UI implementation.

This version documents **major improvements and stability fixes applied sequentially**:

1.  Fixed Checkout Page Load.
2.  Fixed Order Confirmation Flow.
3.  Fixed Account Pages UI.
4.  Fixed Quiz CSRF Error.
5.  Fixed Product Filter SQL Error (Mixed Placeholders).
6.  Fixed Quiz Results Logic (Session Storage).
7.  Standardized Cart Storage (Session vs. DB).
8.  Standardized Rate Limiting (Key Endpoints).
9.  Fixed Registration Failure (DB Logging Error in `EmailService`).
10. Fixed Address Saving Logic (`UserModel::updateAddress` key mapping).
11. Implemented Admin Product List & Form Views (`views/admin/products.php`, `views/admin/product_form.php`).

**Current Status (v16.1 - Core Stable, Admin Product UI Added, Reg/Address Fixed)**

*   ✅ **Core Functionality Stable:** Product Browsing (Filtering/Sorting/Pagination OK), Add-to-Cart (AJAX OK), Cart Management (AJAX OK), User Login/Registration (**AJAX Registration OK**), Password Reset OK, Profile Update (Name, Email, Password, Newsletter, **Address OK**), Quiz Flow OK, Checkout Load OK, Order Confirmation OK.
*   ✅ **Critical Bug Fixes Implemented:** All previously listed fixes are confirmed. Registration and Profile Address saving are now functional.
*   ✅ **Standardizations Applied:** Cart Storage, Rate Limiting, Named DB Placeholders (Filtering).
*   ✅ **UI Enhancements:** Account pages UI fixed. Address Management UI on profile page is functional (view/edit/save). Admin Product List and Form views implemented.
*   🚧 **Partially Implemented Features / Required Backend Adjustments:**
    *   **Admin Product Form JSON Fields:** The Admin Product form uses textareas (`benefits`, `gallery_images`) for fields stored as JSON in the database. The view expects newline or comma-separated input. **The backend `ProductController::saveAdminProduct` method needs adjustment to parse these textarea strings into arrays before passing them to the model for JSON encoding.** (See Appendix C).
*   ⚠️ **Other Known Issues/TODOs:**
    *   **Error Handling ("Headers Already Sent"):** Issue mitigated, but potential edge cases remain. Consider making `views/error.php` self-contained.
    *   **Content Security Policy (CSP):** Needs review/tightening for production.
    *   **Rate Limiting Coverage:** Review other potentially sensitive endpoints (e.g., admin actions beyond products).
    *   **Admin Panel Features:** Extend CRUD features (Orders, Users). Improve Quiz Analytics.
    *   **Code Quality/Refactoring:** Composer, Router, Templating, .env, Migrations, Tests recommended.

This document provides a comprehensive overview of the current architecture, logic, and flow, serving as an onboarding guide and reference for ongoing development.

---

## 2. Project Philosophy & Goals

*   **Security First:** Implemented via PDO Prepared Statements (named placeholders where applicable), input validation (`SecurityMiddleware`), secure session handling, CSRF protection (Synchronizer Token Pattern, enforced globally on POST), security headers (CSP needs review), **rate limiting applied consistently to key endpoints**.
*   **Simplicity & Maintainability:** Modular structure, clear includes in `index.php`. Consistent coding patterns enforced.
*   **Extensibility:** Architecture allows adding new features/pages. Clear extension points.
*   **Performance:** Direct routing, PDO prepared statements. CDN for frontend libs. APCu used for rate limiting.
*   **Modern User Experience:** Responsive design (Tailwind), subtle animations (AOS.js, Particles), AJAX interactions (Cart, Newsletter, **Login/Registration Functional**). Core user flows functional and stable.
*   **Transparency:** Explicit routing and includes in `index.php`.
*   **Accessibility & SEO:** Semantic HTML, `aria-label` usage. Basic practices followed.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

*(Mermaid diagram remains the same as TDS v15.0)*

*   **Key Principles:** Centralized routing (`index.php`), separation of concerns (Controller logic, Model data access, View presentation), secure database interaction (PDO Prepared Statements, **primarily using named placeholders now**), global CSRF validation on POST. `BaseController` provides shared utilities. **Cart storage logic standardized in `CartController`.** **Rate limiting applied consistently in `BaseController`.**

### 3.2 Request-Response Life Cycle (Implemented Confirmation Flow)

*(Flow remains the same as TDS v15.0 - the implemented logic is now correct and robust)*

---

## 4. Directory & File Structure

### 4.1 Folder Map

*(Structure remains the same as TDS v15.0, now includes `views/admin/products.php` and `views/admin/product_form.php`)*

### 4.2 Key Files Explained

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
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | **Functional.** Filtering uses Named Placeholders. Admin routing OK. **Needs JSON textarea parsing.** |
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
| `views/admin/product_form.php`  | Admin: Create/Edit product form view.                            | **Functional.** Displays fields, pre-fills on edit. **Requires controller update for JSON fields.** |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                              |
| `views/checkout.php`            | Checkout form view.                                              | **Loads OK.** Uses `$userAddress`. Defensive coding added. AJAX/Stripe OK.                      |
| `views/order_confirmation.php`  | Confirmation view.                                               | **Functional.** Controller logic fixed.                                                        |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token.                                                              |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                    |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Correctly handles CSRF for AJAX. Compatible with controllers.                                |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted.                                                        |
| `includes/EmailService.php`     | Sends emails (Welcome, Reset, Confirmation, etc.)              | **Functional.** **DB logging column fixed.**                                                     |
| `db/*`                          | Schema files.                                                    | **Update script is mandatory.**                                                                  |

---

## 5. Routing and Application Flow

*(No changes needed)*

---

## 6. Frontend Architecture

*(No changes needed)*

---

## 7. Key Pages & Components

*   **Home/Landing Page:** Functional.
*   **Header/Navigation:** Functional. CSRF output OK. Cart count logic simplified and reliable.
*   **Footer/Newsletter:** Functional.
*   **Product Grid/Cards:** Functional.
*   **Shopping Cart:** Functional with updated UI. AJAX OK. Cart storage standardized.
*   **Product Detail Page:** Functional.
*   **Products Page:** **Functional.** Filters/Sorting/Pagination work correctly (**SQL error fixed**).
*   **Checkout Process:** **Loads correctly.** Address pre-filling works. Optional save *during checkout* works. AJAX OK. Payment Intent OK.
*   **Order Confirmation:** **Functional & Reliable.** Logic fixed.
*   **User Account Pages:** **UI Fixed & Functional.** Profile page includes **functional Address Form (view/edit/save).**
*   **Quiz Flow:** **Functional.** CSRF issue fixed. **Results page displays correct products.**
*   **Registration Page:** **Functional.** DB logging issue fixed.
*   **Admin Product Pages:** Basic List and Create/Edit Form Views implemented. Backend logic exists but needs minor update for JSON fields.

---

## 8. Backend Logic & Core PHP Components

*   **Includes:**
    *   `EmailService`: DB logging fixed.
*   **Controllers:**
    *   `AccountController`: **Registration fixed.** **Profile Address saving functional.**
    *   `ProductController`: Admin CRUD logic exists. **Needs update to parse JSON textarea input.**
*   **Models:**
    *   `UserModel`: **`updateAddress` method fixed** and functional.
*   **Database Abstraction:** PDO Prepared Statements used throughout Models. **Named placeholders used for filtering.** Secure.
*   **Security Middleware & Error Handling:** Enforces headers, session rules, CSRF. Error handling global.
*   **Session, Auth, User Flow:** Secure session handling implemented. Auth flows functional. **Registration flow functional.** **Profile address update flow functional.**
*   **Payment Processing & Webhook:** Stripe PI flow implemented. Webhook confirmation OK.

---

## 9. Database Design

*(No changes needed to this section, emphasis remains on patch)*

### 9.1 Entity-Relationship Model (Conceptual)
### 9.2 Core Tables (from schema.sql + Updates)
### 9.3 Schema Considerations & Recommendations
### 9.4 Data Flow Examples (Current State)

*   **Product Filter Click:** Works (Named Placeholders Fixed).
*   **Quiz Submit:** Works (CSRF Fixed). Request -> `index.php` -> `QuizController::processQuiz` -> Validates -> `QuizModel::getRecommendations` -> `QuizModel::saveQuizResult` (stores IDs) -> Stores full recommendations in `$_SESSION` -> Redirects to results page.
*   **Quiz Results Load:** Works (Logic Fixed). GET -> `index.php` -> `QuizController::showResults` -> Retrieves recommendations from `$_SESSION` -> Renders `views/quiz_results.php`.
*   **User Registration:** Works (DB Logging Fixed). POST -> `index.php` -> `AccountController::register` -> Validates -> `UserModel::create` -> Commits -> *Attempts* `EmailService::sendWelcome` -> Returns JSON success -> JS redirects to Login.
*   **Profile Address Update:** Works (Model Fixed). Profile Form POST -> `index.php` -> `AccountController::updateProfile` -> Routes to `handleUpdateAddress` -> Validates -> Calls `UserModel::updateAddress` (passes data correctly) -> Commits -> Sets Flash -> Redirects.
*   **Admin Product Save:** Form POST -> `index.php` -> `ProductController::saveAdminProduct` -> Validates -> **(Needs Parsing Logic for JSON Textareas)** -> Calls `ProductModel::create/update` -> Commits -> Sets Flash -> Redirects.

---

## 10. Security Considerations & Best Practices

*   **Input Sanitization & Validation:** Implemented via `SecurityMiddleware`.
*   **Session Management:** Implemented (Secure flags, regeneration, integrity checks).
*   **CSRF Protection:** Implemented & Fixed (Synchronizer Token Pattern, global POST validation).
*   **Security Headers & CSP:** Implemented. CSP needs review/tightening.
*   **Rate Limiting:** **Applied Consistently to Key Endpoints.** Mechanism exists (`BaseController`), relies on APCu. Review coverage.
*   **File Uploads & Permissions:** Validation logic exists. Secure handling needed if used. `logs/` needs correct permissions.
*   **Audit Logging & Error Handling:** Implemented. Error handling has "Headers Already Sent" issue noted.
*   **SQL Injection Prevention:** Implemented via PDO Prepared Statements (**Named placeholders used for filtering**).
*   **Payment Security:** Offloaded to Stripe Elements. Webhook signature verification implemented.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

*   Follow pattern: Controller -> View -> `index.php` route. Implement CSRF token pattern for POST. Extend `BaseController`. Apply rate limiting if needed.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Via DB or Admin UI (Products CRUD UI now available).

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB, import *base* schema (`the_scent_schema.sql.txt`).
4.  **Apply DB schema updates:** Execute `the_scent_update_users_table.sql`. (**Mandatory**)
5.  Configure `config.php`.
6.  Set file permissions (`logs/` writable).
7.  Configure Apache VirtualHost.
8.  **(Optional but Recommended):** If using Composer, run `composer install`.
9.  Browse site, check logs (`error.log`, `security.log`).
10. **Verify CSRF flow** (inspect views/JS, test POST actions: Quiz, Login, **Register**, Newsletter, Cart, Profile Update, **Admin Product Delete**).
11. **Verify Core Functionality:** Add-to-Cart (Guest & Logged-in), Cart View/Update, Product List/Pagination/Filtering (**Verify category filtering works**), Login/**Register**, Profile Update (**Verify address form saves**), Password Reset, Quiz Flow (**Verify submission leads to correct results page with products**). Verify Checkout page loads. **Verify successful payment leads to the Order Confirmation page.** **Verify Account Dashboard UI.** **Verify Admin Product List/Create/Edit UI.**
12. **Verify Rate Limiting:** Trigger limits for actions like Login, Register, Password Reset Request.
13. **Note Remaining Known Issues:** Admin Product Controller JSON Parsing, "Headers Already Sent" error.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools, application logs (`logs/`), server logs (`apache_logs/`).
*   Test Checkout page load.
*   **Test User Profile Address saving.**
*   **Test the full payment flow through to the Order Confirmation page.**
*   **Test cart behavior when logging in/out (session merge).**
*   **Test Quiz submission and results page redirect.**
*   **Test Product category filtering.**
*   Test Account Dashboard UI.
*   Test rate limits.
*   **Test User Registration flow.**
*   **Test Admin Product Create/Edit/Delete.**
*   Enable `ENVIRONMENT = 'development'` in `config.php` for detailed PHP errors during debugging.

---

## 12. Future Enhancements & Recommendations

*(Prioritized List - v16.1)*

1.  **Implement Controller Parsing for JSON Textareas (Admin Product Form) (High Priority):** Update `ProductController::saveAdminProduct` to parse newline/comma-separated strings from `$_POST['benefits']` and `$_POST['gallery_images']` into arrays before passing them to the `ProductModel`.
2.  **Fix Error Handling ("Headers Already Sent") (Medium Priority):** Make `views/error.php` self-contained or use output buffering more consistently in `ErrorHandler` to prevent this warning.
3.  **Review & Tighten Content Security Policy (Medium Priority):** Update CSP in `config.php`. Avoid `'unsafe-inline'` if possible.
4.  **Review Rate Limiting Coverage (Low Priority):** Ensure `validateRateLimit` is applied to all necessary admin endpoints.
5.  **Code Quality & Refactoring (Ongoing/Future):** Composer, Autoloader, Routing Component, Templating Engine, .env, Migrations, Tests.
6.  **Full Admin Panel (Future):** CRUD for Orders, Users. Improve Quiz Analytics methods in `QuizModel`. Add Admin Dashboard content.
7.  **Advanced Features (Future):** Search improvements, user reviews, wishlists, actual file uploads for product images.

---

## 13. Appendices

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
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | **Functional.** Filtering uses Named Placeholders. Admin routing OK. **Needs JSON textarea parsing.** |
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
| `views/admin/product_form.php`  | Admin: Create/Edit product form view.                            | **Functional.** Displays fields, pre-fills on edit. **Requires controller update for JSON fields.** |
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

**Address Handling Pattern (Functional):**

*   **Schema:** `users` table has `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country` columns (via patch).
*   **Profile View (`views/account/profile.php`):** Displays address fields using `$userAddress`. Form uses field names like `address_line1`. Submits POST to `AccountController::updateProfile` with `action=update_address`.
*   **Controller (`AccountController::handleUpdateAddress`):** Receives POST. Validates input. Builds `$addressData` array with keys like `address_line1`. Calls `UserModel::updateAddress($userId, $addressData)`.
*   **Model (`UserModel::updateAddress` - Fixed):** Receives `$addressData`. Binds keys like `address_line1` correctly to the `UPDATE users` query.

    ```php
    // Snippet from UserModel::updateAddress (Correct Binding)
    $stmt->execute([
        ':address_line1' => $addressData['address_line1'] ?? null, // Correct key
        ':address_line2' => $addressData['address_line2'] ?? null,
        ':city' => $addressData['city'] ?? null,             // Correct key
        ':state' => $addressData['state'] ?? null,            // Correct key
        ':postal_code' => $addressData['postal_code'] ?? null,   // Correct key
        ':country' => $addressData['country'] ?? null,         // Correct key
        ':user_id' => $userId
    ]);
    ```
*   **Checkout View (`views/checkout.php`):** Pre-fills address using `$userAddress`. Form uses `shipping_` prefixed names. Optional "Save Address" checkbox exists.
*   **Checkout Controller (`CheckoutController::processCheckout`):** If "Save Address" checked, calls `UserModel::updateAddress($userId, $postData)` passing the checkout form's POST data directly (model expects keys like `address_line1`, controller uses `shipping_address`, etc. - **Correction**: Model now expects the correct keys from the *profile* form. Checkout controller needs to *map* `shipping_address` to `address_line1` etc. before calling `UserModel::updateAddress` if saving during checkout). **Correction Update:** The `UserModel::updateAddress` was fixed to accept keys matching the *profile form*. The `CheckoutController` *does* need to map its `$postData` (which has keys like `shipping_address`) to the structure expected by `UserModel::updateAddress` (keys like `address_line1`) if the "Save Address" checkbox is checked during checkout.

**Email Logging (`EmailService::logEmail` - Fixed):** *(Unchanged)*

**JSON Textarea Parsing (Required in `ProductController::saveAdminProduct`):**

```php
// Example needed within ProductController::saveAdminProduct
// Before preparing $data array for model create/update

// Parse 'benefits' textarea (assuming newline separated)
$benefitsInput = $_POST['benefits'] ?? '';
$benefitsArray = !empty($benefitsInput)
    ? array_values(array_filter(array_map('trim', explode("\n", $benefitsInput)))) // Split, trim, filter empty, re-index
    : [];
$data['benefits'] = $benefitsArray; // Pass the array to be JSON encoded by the model

// Parse 'gallery_images' textarea (assuming newline separated)
$galleryInput = $_POST['gallery_images'] ?? '';
$galleryArray = !empty($galleryInput)
    ? array_values(array_filter(array_map('trim', explode("\n", $galleryInput))))
    : [];
$data['gallery_images'] = $galleryArray; // Pass the array to be JSON encoded by the model

// ... proceed to call $this->productModel->create($data) or update($productId, $data) ...
```
*   **Explanation:** This code takes the raw string from the `benefits` and `gallery_images` textareas (submitted via POST), splits it into lines using `explode("\n")`, removes leading/trailing whitespace from each line using `array_map('trim', ...)` , filters out any resulting empty lines using `array_filter(...)`, and re-indexes the array using `array_values(...)`. This clean array is then assigned to the respective key in the `$data` array, which is then passed to the `ProductModel`. The model's `create`/`update` methods will handle the `json_encode()` before saving to the database.

### D. Mandatory Database Patch

*(Content remains the same - emphasize its importance)*

---
https://drive.google.com/file/d/13cnU0K3iV4uoQ_hK_6MLCEQJFavXk1a7/view?usp=sharing, https://drive.google.com/file/d/15UuqPd300pvQtIbjeiAFmmx-v1lt-374/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221BXJr2ZEy_12xEUWBNMQAJJ6lsG8Xreyt%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1FJUG_bFZpk6aSqSVanpQ5b_M2HpJkf1B/view?usp=sharing, https://drive.google.com/file/d/1FLZ6E-3q1FNTy2G7zT6C-wAa5dP8j0Q4/view?usp=sharing, https://drive.google.com/file/d/1LN2smIAIulUbGlGB9azT639n5F6b6CXy/view?usp=sharing, https://drive.google.com/file/d/1LXoRUMFJIjxumBGynOu0czpvY5_Hxciu/view?usp=sharing, https://drive.google.com/file/d/1NY8gQLxASAx-ntvFyR1NGlm_dCD67i_b/view?usp=sharing, https://drive.google.com/file/d/1PhIyuqG8HpM6ZdDUTJSlHWMy2Pvz9kFP/view?usp=sharing, https://drive.google.com/file/d/1TMKOd51RdN_9ne3G5ARWggDuG1ZbA9MX/view?usp=sharing, https://drive.google.com/file/d/1X_dUzJ1xA1TVTLFvktpHtIYO5Tz3hKCk/view?usp=sharing, https://drive.google.com/file/d/1ZaQhr2HCrtb_hWMrAaLSGA001U-JWNRV/view?usp=sharing, https://drive.google.com/file/d/1bvY_fzmpBEA9run9gpGZ7pK078f9MEz2/view?usp=sharing, https://drive.google.com/file/d/1dN8Jtgcdb1a9UgShndc9XymbFVZ32g1v/view?usp=sharing, https://drive.google.com/file/d/1rFkxMDPINub-YRgsLOIQx6PFlYQOtqve/view?usp=sharing, https://drive.google.com/file/d/1sbCw95Gz20ya4f6XNzQUpClvhuoQTQqN/view?usp=sharing, https://drive.google.com/file/d/1vh-9-onOu0V_Mvas9RECdY7ttsZNxhJd/view?usp=sharing, https://drive.google.com/file/d/1xl03suMcOQBni3A6f4vrkrQjAGME8rx0/view?usp=sharing
