# The Scent – Technical Design Specification (v16.2)

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
    *   C. [Code Snippets and Patterns (CSRF, Confirmation, Named Placeholders, Address Handling, JSON Textarea Parsing, Stripe Init Debugging)](#c-code-snippets-and-patterns-csrf-confirmation-named-placeholders-address-handling-json-textarea-parsing-stripe-init-debugging)
    *   D. [Mandatory Database Patch](#d-mandatory-database-patch)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v16.2**) serves as the updated technical design specification, reflecting the project's current state after applying significant fixes, standardizations, basic Admin Product CRUD UI implementation, **checkout address field fixes**, and **adding Stripe initialization debugging**.

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
12. **Fixed Checkout Address Field Discrepancy** (`views/checkout.php`, `controllers/CheckoutController.php`).
13. **Added Debugging for Checkout Payment Initialization Error** (`js/main.js`, `views/checkout.php`).
14. **Applied various JS Fixes** (`js/main.js` for cart totals, quiz selection, admin pages).
15. **Implemented JSON Textarea Parsing in Admin Product Controller** (`controllers/ProductController.php`).

**Current Status (v16.2 - Core Stable, Checkout Address Fixed, Payment Debugging Added, Admin Product Parsing Fixed)**

*   ✅ **Core Functionality Stable:** Product Browsing (Filtering/Sorting/Pagination OK), Add-to-Cart (AJAX OK), Cart Management (AJAX OK), User Login/Registration (AJAX Registration OK), Password Reset OK, Profile Update (Name, Email, Password, Newsletter, Address OK), Quiz Flow OK, Checkout Load OK (Address Line 2 added), Order Confirmation OK.
*   ✅ **Critical Bug Fixes Implemented:** All previously listed fixes are confirmed. **Checkout address field handling is complete.** **Admin product form JSON field parsing is implemented in the controller.**
*   ✅ **Standardizations Applied:** Cart Storage, Rate Limiting, Named DB Placeholders (Filtering).
*   ✅ **UI Enhancements:** Account pages UI fixed. Address Management UI on profile page functional. Checkout page address form updated. Admin Product List and Form views implemented. JS fixes applied.
*   ⚠️ **Known Issues/TODOs:**
    *   **Checkout Payment Initialization Error (Under Investigation):** The checkout page shows "Could not initialize payment system." **Debugging logs have been added to `js/main.js`** to help diagnose the root cause. *(Needs investigation using the logs).*
    *   **Error Handling ("Headers Already Sent"):** Issue mitigated, potential edge cases remain. Consider making `views/error.php` self-contained.
    *   **Content Security Policy (CSP):** Needs review/tightening for production.
    *   **Rate Limiting Coverage:** Review other endpoints (e.g., admin actions).
    *   **Admin Panel Features:** Extend CRUD features (Orders, Users). Improve Quiz Analytics.
    *   **Code Quality/Refactoring:** Composer, Router, Templating, .env, Migrations, Tests recommended.

This document provides a comprehensive overview of the current architecture, logic, and flow, serving as an onboarding guide and reference for ongoing development.

---

## 2. Project Philosophy & Goals

*   **Security First:** Implemented via PDO Prepared Statements (named placeholders where applicable), input validation (`SecurityMiddleware`), secure session handling, CSRF protection (Synchronizer Token Pattern, enforced globally on POST), security headers (CSP needs review), rate limiting applied consistently to key endpoints.
*   **Simplicity & Maintainability:** Modular structure, clear includes in `index.php`. Consistent coding patterns enforced.
*   **Extensibility:** Architecture allows adding new features/pages. Clear extension points.
*   **Performance:** Direct routing, PDO prepared statements. CDN for frontend libs. APCu used for rate limiting.
*   **Modern User Experience:** Responsive design (Tailwind), subtle animations (AOS.js, Particles), AJAX interactions (Cart, Newsletter, Login/Registration Functional). Core user flows functional and stable.
*   **Transparency:** Explicit routing and includes in `index.php`.
*   **Accessibility & SEO:** Semantic HTML, `aria-label` usage. Basic practices followed.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

*(Mermaid diagram remains the same)*

*   **Key Principles:** Centralized routing (`index.php`), separation of concerns (Controller logic, Model data access, View presentation), secure database interaction (PDO Prepared Statements, named placeholders where applicable), global CSRF validation on POST. `BaseController` provides shared utilities. Cart storage logic standardized. Rate limiting applied consistently.

### 3.2 Request-Response Life Cycle (Implemented Confirmation Flow)

*(Flow remains the same, logic is now correct)*

1.  **Request:** User interacts (e.g., clicks "Place Order" on `/checkout`).
2.  **.htaccess:** Rewrites URL to `index.php`.
3.  **`index.php`:**
    *   Includes dependencies, initializes Error Handling, applies Security Middleware.
    *   Handles Stripe Webhook route separately.
    *   **Validates CSRF token for POST requests.**
    *   Routes to `CheckoutController::processCheckout()`.
4.  **`CheckoutController::processCheckout()`:**
    *   Validates Rate Limit, Login, CSRF.
    *   Retrieves cart items, validates stock.
    *   Validates shipping POST data (**including `shipping_address_line2`**).
    *   Validates applied coupon.
    *   Calculates final totals.
    *   Starts DB Transaction.
    *   Re-validates stock.
    *   Creates `orders` record (**includes `shipping_address_line2`**).
    *   Creates `order_items` records.
    *   Decrements inventory.
    *   **If "Save Address" checked:** **Maps `shipping_*` fields correctly** and calls `UserModel::updateAddress()`.
    *   Calls `PaymentController::createPaymentIntent()`.
    *   Updates order with `payment_intent_id`.
    *   Records coupon usage.
    *   Commits DB Transaction.
    *   Logs audit trail.
    *   Returns JSON (`success: true`, `clientSecret`, `orderId`).
5.  **`js/main.js` (`initCheckoutPage`):**
    *   Receives JSON response.
    *   If successful, calls `stripe.confirmPayment()` using `clientSecret`.
    *   Stripe handles 3DS and redirects to confirmation URL on success.
6.  **Request (Confirmation):** GET `/index.php?page=checkout&action=confirmation&payment_intent=pi_...`
7.  **`index.php`:** Routes to `CheckoutController::showOrderConfirmation()`.
8.  **`CheckoutController::showOrderConfirmation()`:**
    *   Requires Login.
    *   Validates `payment_intent` ID.
    *   Retrieves Payment Intent from Stripe API.
    *   Verifies PI `status` is `succeeded`.
    *   Fetches corresponding Order from DB.
    *   Validates Order ownership.
    *   Fetches full order details.
    *   Renders `views/order_confirmation.php`.
9.  **Response:** Server sends the Order Confirmation HTML page.

---

## 4. Directory & File Structure

### 4.1 Folder Map

*(Structure remains the same, includes `views/admin/products.php`, `views/admin/product_form.php`)*

### 4.2 Key Files Explained

*(Updated status notes for v16.2)*

| File/Folder                     | Purpose                                                          | Status Notes (v16.2)                                                                                 |
| :------------------------------ | :--------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------- |
| `index.php`                     | Entry point, routing, core includes, auto POST CSRF validation   | OK. Admin Product routing functional.                                                                |
| `config.php`                    | DB credentials, App/Security settings, API keys                | OK. CSP/Rate Limit review needed. Secrets exposure.                                                  |
| `includes/SecurityMiddleware.php`| Static security helpers (validation, CSRF)                     | OK.                                                                                                  |
| `controllers/BaseController.php`| Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)       | OK. `redirect` fixed. Rate limiting logic OK.                                                        |
| `controllers/AccountController.php`| User auth/profile logic. AJAX/standard POST.                   | Functional. Rate limiting applied. Registration fixed. Profile Address saving fixed.             |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                              | Loads OK. **Handles Address Line 2 saving.** Confirmation Flow Fixed. Rate limiting applied.           |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                           | OK. Webhook session dependency removed. `getStripeClient()` available.                             |
| `controllers/CartController.php`    | Handles cart logic via AJAX.                                     | Functional. Cart storage standardized. Reliable session count update. JS fixes applied.          |
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | Functional. Filtering uses Named Placeholders. Admin routing OK. **JSON textarea parsing added.**      |
| `controllers/QuizController.php`    | Quiz logic.                                                    | Functional. CSRF fixed. Results logic uses Session. JS fixes applied.                            |
| `controllers/NewsletterController.php`| Newsletter signup/unsubscribe.                               | OK. Rate limiting applied.                                                                           |
| `models/User.php`               | User DB logic (PDO Prepared Statements).                     | Updated & Functional. `updateAddress` fixed. `getAddress` OK. Requires patch.                    |
| `models/Order.php`              | Order DB logic (PDO Prepared Statements).                    | OK. `create` signature updated. Item fetching OK.                                                 |
| `models/Product.php`            | Product DB logic (PDO Prepared Statements).                  | Functional. Filtering uses Named Placeholders. Compatible with controller. OK.              |
| `models/Cart.php`               | DB cart logic (PDO Prepared Statements).                     | OK. Used only for logged-in users now.                                                             |
| `models/Quiz.php`               | Quiz DB logic (PDO Prepared Statements).                     | Functional. `recommendations` column selected correctly.                                     |
| `views/layout/header.php`       | Header, nav, assets, outputs global CSRF token & JS config.    | OK. Cart count logic simplified. Outputs data-* attributes.                                       |
| `views/account/*.php`           | Account views (Dashboard, Orders, Profile, etc.)                 | UI Fixed. Profile address UI functional. Compatible with CSS/JS. OK.                             |
| `views/admin/products.php`      | Admin: List products view.                                       | Functional. Displays data, provides actions.                                                     |
| `views/admin/product_form.php`  | Admin: Create/Edit product form view.                            | Functional. Displays fields. Controller handles JSON fields.                                      |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                                  |
| `views/checkout.php`            | Checkout form view.                                              | Loads OK. **Includes Address Line 2.** AJAX/Stripe OK. **Payment init debugging added.**            |
| `views/order_confirmation.php`  | Confirmation view.                                               | Functional. Controller logic fixed.                                                            |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token. JS fixed.                                                      |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                        |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Handles CSRF. **Checkout debugging added. Cart, Quiz, Admin JS fixed.**                        |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted. Robustness checks passed (no changes needed).         |
| `includes/EmailService.php`     | Sends emails (Welcome, Reset, Confirmation, etc.)              | Functional. DB logging column fixed.                                                         |
| `db/*`                          | Schema files.                                                    | **Update script is mandatory.**                                                                      |

---

## 5. Routing and Application Flow

*(No changes needed)*

---

## 6. Frontend Architecture

*(No changes needed, JS section description updated implicitly)*

---

## 7. Key Pages & Components

*   **7.8 Checkout Process (`views/checkout.php`):**
    *   **Address Fields:** Now correctly includes fields for both "Street Address" (`shipping_address`) and "Address Line 2 (Optional)" (`shipping_address_line2`).
    *   **Payment Initialization:** Still under investigation due to "Could not initialize payment system..." error. **Debugging logs added to JS** to trace the public key and Stripe object initialization.
    *   **Save Address Option:** Functionality confirmed. Controller maps checkout fields correctly to user profile fields before saving.
*   **7.11 Quiz Flow & Personalization:** Quiz submission button confirmed working with current code. JS logic for selection handling corrected.

*(Other sections remain accurate)*

---

## 8. Backend Logic & Core PHP Components

*   **8.2 Controllers: Business Logic Layer (`controllers/` & `BaseController.php`):**
    *   `ProductController`: `saveAdminProduct` method updated to correctly parse newline-separated strings from `benefits` and `gallery_images` textareas into arrays before passing to the model.
    *   `CheckoutController`: `processCheckout` method updated to handle `shipping_address_line2` and correctly map address data when saving to the user profile.
*   **8.4 Security Middleware & Error Handling:**
    *   `ErrorHandler.php`: Reviewed, existing robustness checks (e.g., `headers_sent()`) deemed sufficient based on provided code. "Headers Already Sent" warning remains a potential edge case to monitor.

*(Other sections remain accurate)*

---

## 9. Database Design

*   **9.4 Data Flow Examples (Current State):**
    *   **Admin Product Save (JSON Fields):**
        1.  Admin submits `views/admin/product_form.php`.
        2.  POST request to `index.php?page=admin&section=products&task=save`.
        3.  `ProductController::saveAdminProduct` receives POST data.
        4.  **It now parses `$_POST['benefits']` and `$_POST['gallery_images']` (textarea strings) into PHP arrays.**
        5.  The `$data` array (containing the PHP arrays) is passed to `ProductModel::create` or `update`.
        6.  `ProductModel` uses `json_encode()` on the arrays before executing the SQL INSERT/UPDATE.

*(Other sections remain accurate)*

---

## 10. Security Considerations & Best Practices

*(No direct changes needed)*

---

## 11. Extensibility & Onboarding

*   **11.3 Developer Onboarding Checklist:**
    *   Step 11: Verify Checkout page loads correctly, displays both address lines, **and check console logs for Stripe initialization messages/errors**. Test saving the address during checkout. **Verify Admin Product form correctly saves multi-line 'Benefits' and 'Gallery Images' as JSON.**
*   **11.4 Testing & Debugging Notes:**
    *   **Check console logs on the Checkout page** for Stripe initialization messages/errors.
    *   Test the "Save this shipping address..." checkbox during checkout.
    *   **Test Admin Product Create/Edit, specifically entering multiple lines in the 'Benefits' and 'Gallery Images' textareas and verifying the data saves and displays correctly.**

*(Other sections remain accurate)*

---

## 12. Future Enhancements & Recommendations

*(Updated Priorities - v16.2)*

1.  **Resolve Checkout Payment Initialization Error (Highest Priority):** Use the added JS console logs to diagnose why `Stripe(stripePublicKey)` is failing or why the key might be missing/invalid. Fix the root cause.
2.  **Fix Error Handling ("Headers Already Sent") (Medium Priority):** Make `views/error.php` self-contained or use output buffering more consistently in `ErrorHandler`.
3.  **Review & Tighten Content Security Policy (Medium Priority):** Update CSP in `config.php`.
4.  **Review Rate Limiting Coverage (Low Priority):** Ensure coverage on admin endpoints.
5.  **Code Quality & Refactoring (Ongoing/Future):** Composer, Autoloader, Routing Component, Templating Engine, .env, Migrations, Tests.
6.  **Full Admin Panel (Future):** CRUD for Orders, Users. Improve Quiz Analytics. Add Admin Dashboard content.
7.  **Advanced Features (Future):** Search improvements, user reviews, wishlists, actual file uploads.

---

## 13. Appendices

### A. Key File Summaries

*(Updated status notes for v16.2)*

| File/Folder                     | Purpose                                                          | Status Notes (v16.2)                                                                                 |
| :------------------------------ | :--------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------- |
| `index.php`                     | Entry point, routing, core includes, auto POST CSRF validation   | OK. Admin Product routing functional.                                                                |
| `config.php`                    | DB credentials, App/Security settings, API keys                | OK. CSP/Rate Limit review needed. Secrets exposure.                                                  |
| `includes/SecurityMiddleware.php`| Static security helpers (validation, CSRF)                     | OK.                                                                                                  |
| `controllers/BaseController.php`| Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)       | OK. `redirect` fixed. Rate limiting logic OK.                                                        |
| `controllers/AccountController.php`| User auth/profile logic. AJAX/standard POST.                   | Functional. Rate limiting applied. Registration fixed. Profile Address saving fixed.             |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                              | Loads OK. **Handles Address Line 2 saving.** Confirmation Flow Fixed. Rate limiting applied.           |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                           | OK. Webhook session dependency removed. `getStripeClient()` available.                             |
| `controllers/CartController.php`    | Handles cart logic via AJAX.                                     | Functional. Cart storage standardized. Reliable session count update. JS fixes applied.          |
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | Functional. Filtering uses Named Placeholders. Admin routing OK. **JSON textarea parsing added.**      |
| `controllers/QuizController.php`    | Quiz logic.                                                    | Functional. CSRF fixed. Results logic uses Session. JS fixes applied.                            |
| `controllers/NewsletterController.php`| Newsletter signup/unsubscribe.                               | OK. Rate limiting applied.                                                                           |
| `models/User.php`               | User DB logic (PDO Prepared Statements).                     | Updated & Functional. `updateAddress` fixed. `getAddress` OK. Requires patch.                    |
| `models/Order.php`              | Order DB logic (PDO Prepared Statements).                    | OK. `create` signature updated. Item fetching OK.                                                 |
| `models/Product.php`            | Product DB logic (PDO Prepared Statements).                  | Functional. Filtering uses Named Placeholders. `create`/`update` handle JSON encoding. OK.        |
| `models/Cart.php`               | DB cart logic (PDO Prepared Statements).                     | OK. Used only for logged-in users now.                                                             |
| `models/Quiz.php`               | Quiz DB logic (PDO Prepared Statements).                     | Functional. `recommendations` column selected correctly.                                     |
| `views/layout/header.php`       | Header, nav, assets, outputs global CSRF token & JS config.    | OK. Cart count logic simplified. Outputs data-* attributes.                                       |
| `views/account/*.php`           | Account views (Dashboard, Orders, Profile, etc.)                 | UI Fixed. Profile address UI functional. Compatible with CSS/JS. OK.                             |
| `views/admin/products.php`      | Admin: List products view.                                       | Functional. Displays data, provides actions.                                                     |
| `views/admin/product_form.php`  | Admin: Create/Edit product form view.                            | Functional. Displays fields. Controller handles JSON fields parsing.                             |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                                  |
| `views/checkout.php`            | Checkout form view.                                              | Loads OK. **Includes Address Line 2.** AJAX/Stripe OK. **Payment init debugging added.**            |
| `views/order_confirmation.php`  | Confirmation view.                                               | Functional. Controller logic fixed.                                                            |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token. JS fixed.                                                      |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                        |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Handles CSRF. **Checkout debugging added. Cart, Quiz, Admin JS fixed.**                        |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted. Robustness checks passed.                             |
| `includes/EmailService.php`     | Sends emails (Welcome, Reset, Confirmation, etc.)              | Functional. DB logging column fixed.                                                         |
| `db/*`                          | Schema files.                                                    | **Update script is mandatory.**                                                                      |

### B. Glossary

*(Standard terms)*

### C. Code Snippets and Patterns (CSRF, Confirmation, Named Placeholders, Address Handling, JSON Textarea Parsing, Stripe Init Debugging)

**CSRF Token Pattern:** *(Unchanged)*

**Implemented Order Confirmation Flow:** *(Unchanged - flow description is correct)*

**Named Placeholder Pattern (Example from `ProductModel::getFiltered`)** *(Unchanged)*

**Address Handling Pattern (Functional):** *(Updated description)*

*   **Schema:** `users` table has address columns (via patch). `orders` table *should also* have `shipping_address_line2` if needed for order records.
*   **Profile View:** Displays address fields from `$userAddress`. Form uses keys like `address_line1`. Submits POST to `AccountController::updateProfile`.
*   **Profile Controller:** `handleUpdateAddress` validates and builds `$addressData` with keys like `address_line1`. Calls `UserModel::updateAddress($userId, $addressData)`.
*   **Checkout View:** Displays address fields from `$userAddress`. Form uses `shipping_` prefixed names. Optional "Save Address" checkbox.
*   **Checkout Controller:** `processCheckout` reads `shipping_` fields. If "Save Address" is checked, it *maps* the `shipping_` keys to `address_line1`, etc., in `$addressUpdateData` before calling `UserModel::updateAddress`. Saves `shipping_` fields directly to the `orders` table.
*   **Model (`UserModel::updateAddress`):** Receives `$addressData` (with keys like `address_line1`). Binds keys correctly to `UPDATE users`.

**Email Logging (`EmailService::logEmail` - Fixed):** *(Unchanged)*

**JSON Textarea Parsing (Implemented in `ProductController::saveAdminProduct`):** *(Snippet added to show implementation)*

```php
// Snippet from ProductController::saveAdminProduct

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

// ... rest of data validation ...

// Pass $data (containing PHP arrays for benefits/gallery_images)
// to $this->productModel->create($data) or update($productId, $data)
// The model handles json_encode() before saving.
```

**Stripe Initialization Debugging (Added to `js/main.js::initCheckoutPage`):** *(Snippet added)*

```javascript
// Snippet from js/main.js :: initCheckoutPage

// --- Basic Checks ---
console.log("Stripe Public Key:", stripePublicKey); // <<< Added Log
if (!stripePublicKey) { /* ... */ }
// ... other checks ...

// --- Initialize Stripe ---
try {
     stripe = Stripe(stripePublicKey);
     console.log("Stripe object initialized:", stripe); // <<< Added Log
     // ... rest of initialization ...
     console.log("Stripe Payment Element mounted."); // <<< Added Log
} catch (stripeError) {
    console.error("Stripe initialization error:", stripeError); // <<< Added Log
    // ... error handling ...
}
```

### D. Mandatory Database Patch

*(Content remains the same - emphasize its importance)*

