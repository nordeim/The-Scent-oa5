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

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v16.2**) serves as the updated technical design specification, reflecting the project's current state after applying significant fixes, standardizations, basic Admin Product CRUD UI implementation, and addressing checkout page issues.

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

**Current Status (v16.2 - Core Stable, Checkout Address Fixed, Payment Debugging Added, Admin Product UI Added)**

*   ✅ **Core Functionality Stable:** Product Browsing (Filtering/Sorting/Pagination OK), Add-to-Cart (AJAX OK), Cart Management (AJAX OK), User Login/Registration (AJAX Registration OK), Password Reset OK, Profile Update (Name, Email, Password, Newsletter, Address OK), Quiz Flow OK, Checkout Load OK (**Checkout form now includes Address Line 2**), Order Confirmation OK.
*   ✅ **Critical Bug Fixes Implemented:** All previously listed fixes are confirmed. Registration and Profile Address saving are functional. **Checkout address field discrepancy resolved.**
*   ✅ **Standardizations Applied:** Cart Storage, Rate Limiting, Named DB Placeholders (Filtering).
*   ✅ **UI Enhancements:** Account pages UI fixed. Address Management UI on profile page is functional. **Checkout page address form updated.** Admin Product List and Form views implemented. JS fixes applied for cart totals, quiz selection, and admin pages.
*   🚧 **Partially Implemented Features / Required Backend Adjustments:**
    *   **Admin Product Form JSON Fields:** The Admin Product form uses textareas (`benefits`, `gallery_images`). **The backend `ProductController::saveAdminProduct` still needs adjustment to parse these textarea strings into arrays before saving.** (See Appendix C).
*   ⚠️ **Known Issues/TODOs:**
    *   **Checkout Payment Initialization Error (Under Investigation):** The checkout page shows "Could not initialize payment system." **Debugging logs have been added to `js/main.js`** to help diagnose the root cause (likely related to Stripe public key availability or JS library loading/execution).
    *   **Error Handling ("Headers Already Sent"):** Issue mitigated, potential edge cases remain.
    *   **Content Security Policy (CSP):** Needs review/tightening for production.
    *   **Rate Limiting Coverage:** Review other endpoints (e.g., admin actions).
    *   **Admin Panel Features:** Extend CRUD (Orders, Users). Improve Quiz Analytics.
    *   **Code Quality/Refactoring:** Composer, Router, Templating, .env, Migrations, Tests recommended.

This document provides a comprehensive overview of the current architecture, logic, and flow, serving as an onboarding guide and reference for ongoing development.

---

## 2. Project Philosophy & Goals

*(No changes needed)*

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

*(Mermaid diagram remains the same)*

*   **Key Principles:** Centralized routing (`index.php`), separation of concerns (Controller logic, Model data access, View presentation), secure database interaction (PDO Prepared Statements, named placeholders where applicable), global CSRF validation on POST. `BaseController` provides shared utilities. Cart storage logic standardized. Rate limiting applied consistently.

### 3.2 Request-Response Life Cycle (Implemented Confirmation Flow)

1.  **Request:** User interacts (e.g., clicks "Place Order" on `/checkout`).
2.  **.htaccess:** Rewrites URL to `index.php`.
3.  **`index.php`:**
    *   Includes `config.php`, `db.php`, `auth.php`, `SecurityMiddleware.php`, `ErrorHandler.php`.
    *   Initializes Error Handling (`ErrorHandler::init()`).
    *   Applies Security Middleware (`SecurityMiddleware::apply()`).
    *   Handles Stripe Webhook route separately (skips CSRF).
    *   **Validates CSRF token for POST requests.**
    *   Determines `$page='checkout'`, `$action='processCheckout'`.
    *   Includes `controllers/CheckoutController.php` (and dependencies like `PaymentController`).
    *   Instantiates `CheckoutController`.
    *   Calls `$controller->processCheckout()`.
4.  **`CheckoutController::processCheckout()`:**
    *   Validates Rate Limit (`validateRateLimit`).
    *   Requires Login (`requireLogin`).
    *   Validates CSRF again (defense in depth).
    *   Retrieves cart items. Validates stock.
    *   Validates shipping POST data (including `shipping_address` and `shipping_address_line2`).
    *   Validates applied coupon (server-side).
    *   Calculates final totals.
    *   Starts DB Transaction (`beginTransaction`).
    *   Re-validates stock (within transaction).
    *   Creates order record in `orders` table (including both address lines if present).
    *   Creates `order_items` records.
    *   Decrements inventory (`InventoryController::updateStock`).
    *   **If "Save Address" checked:** Maps `shipping_*` fields to `address_line1`/`address_line2` etc. and calls `UserModel::updateAddress()`.
    *   Calls `PaymentController::createPaymentIntent()`.
    *   Updates order record with `payment_intent_id`.
    *   Records coupon usage.
    *   Commits DB Transaction (`commit`).
    *   Logs audit trail.
    *   Returns JSON response (`success: true`, `clientSecret`, `orderId`).
5.  **`js/main.js` (`initCheckoutPage`):**
    *   Receives JSON response from `processCheckout`.
    *   If successful, uses `clientSecret` to call `stripe.confirmPayment()`.
    *   Stripe handles 3DS (if needed) and redirects user to `return_url` (`/index.php?page=checkout&action=confirmation`) on success.
6.  **Request (Confirmation):** GET `/index.php?page=checkout&action=confirmation&payment_intent=pi_...&payment_intent_client_secret=...`.
7.  **`index.php`:** Routes to `CheckoutController::showOrderConfirmation()`.
8.  **`CheckoutController::showOrderConfirmation()`:**
    *   Requires Login.
    *   Validates `payment_intent` ID from GET parameter.
    *   Retrieves Payment Intent from Stripe API via `PaymentController::getStripeClient()`.
    *   Verifies PI `status` is `succeeded`.
    *   Fetches corresponding Order from DB using `payment_intent_id`.
    *   Validates Order ownership (`user_id`).
    *   Fetches full order details (including items).
    *   Calls `renderView('order_confirmation', $data)`.
9.  **`BaseController::renderView()`:** Renders `views/order_confirmation.php` using `$data`.
10. **Response:** Server sends the Order Confirmation HTML page to the browser.

---

## 4. Directory & File Structure

*(No changes needed)*

---

## 5. Routing and Application Flow

*(No changes needed)*

---

## 6. Frontend Architecture

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*(No changes needed)*

### 6.2 Responsive Design and Accessibility

*(No changes needed)*

### 6.3 JavaScript: Interactivity, Libraries, and CSRF Handling

*   **`js/main.js`:** Central script for frontend logic.
    *   Handles mobile menu, flash messages, AJAX Add-to-Cart, AJAX Newsletter Signup, **AJAX Login/Registration**.
    *   Includes page-specific initializers dispatched based on `<body>` class (e.g., `initHomePage`, `initProductsPage`, `initCheckoutPage`).
    *   **CSRF Handling:** Reads the global CSRF token from `<input type="hidden" id="csrf-token-value">` (output by `header.php`) and includes it in AJAX POST requests.
    *   **Recent Fixes:** Corrected cart total calculation logic, quiz radio button handling, admin page JS (analytics data fetching/display, coupon form population, order status updates).
    *   **Checkout Debugging:** Added `console.log` statements to `initCheckoutPage` to trace Stripe public key value and `Stripe()` initialization steps.

---

## 7. Key Pages & Components

*   **7.8 Checkout Process (`views/checkout.php`):**
    *   **Address Fields:** Now includes fields for both "Street Address" (`shipping_address`) and "Address Line 2 (Optional)" (`shipping_address_line2`), aligning with the profile page. Pre-filling uses `$userAddress`.
    *   **Payment Initialization:** Contains the `#payment-element` div for Stripe. Currently encountering an initialization error ("Could not initialize payment system..."). **Debugging logs have been added to `js/main.js`** to help diagnose this.
    *   **Save Address Option:** Includes a checkbox (`save_address`) allowing users to save the entered shipping details to their profile during checkout. The backend `CheckoutController` handles this logic, including mapping fields correctly before calling `UserModel::updateAddress`.
    *   **AJAX:** Coupon application and tax calculation use AJAX calls handled by `js/main.js`.
    *   **Submission:** Handled by `js/main.js`, which calls `CheckoutController::processCheckout` via AJAX, then uses the returned `clientSecret` to confirm payment with Stripe.

*(Other sections remain accurate based on previous updates)*

---

## 8. Backend Logic & Core PHP Components

*   **8.2 Controllers: Business Logic Layer (`controllers/` & `BaseController.php`):**
    *   `CheckoutController`: Updated `processCheckout` method to handle `shipping_address_line2` input and correctly map data keys when calling `UserModel::updateAddress` if the "Save Address" option is checked during checkout.

*(Other sections remain accurate based on previous updates)*

---

## 9. Database Design

*   **9.4 Data Flow Examples (Current State):**
    *   **Checkout Address Save (Optional):**
        1.  User fills `views/checkout.php` form (including `shipping_address` and `shipping_address_line2`) and checks "Save this shipping address...".
        2.  JS submits form data via AJAX to `CheckoutController::processCheckout`.
        3.  `processCheckout` validates data, checks `$saveAddress` flag.
        4.  If `true`, it creates `$addressUpdateData` mapping `shipping_address` -> `address_line1`, `shipping_address_line2` -> `address_line2`, etc.
        5.  Calls `UserModel::updateAddress($userId, $addressUpdateData)`.
        6.  `UserModel::updateAddress` executes `UPDATE users SET address_line1 = :address_line1, ... WHERE id = :user_id`.
    *   **Checkout Payment Initialization (Debugging):**
        1.  `CheckoutController::showCheckout` renders `views/checkout.php`.
        2.  `views/layout/header.php` includes `data-stripe-public-key`.
        3.  `js/main.js` -> `initCheckoutPage` reads the public key.
        4.  *Expected:* `stripe = Stripe(stripePublicKey)` initializes Stripe.js.
        5.  *Current Issue:* JS shows "Could not initialize payment system...". `console.log` statements added to check key value and `Stripe()` call outcome.

*(Other sections remain accurate based on previous updates)*

---

## 10. Security Considerations & Best Practices

*(No direct changes needed)*

---

## 11. Extensibility & Onboarding

*   **11.3 Developer Onboarding Checklist:**
    *   Step 11: Verify Checkout page loads correctly, **displays both address lines**, and check console logs for Stripe initialization messages/errors. **Test saving the address during checkout.**
*   **11.4 Testing & Debugging Notes:**
    *   **Check console logs on the Checkout page** for `Stripe Public Key: ...` and `Stripe object initialized: ...` or `Stripe initialization error: ...` messages.
    *   **Test the "Save this shipping address..." checkbox** during checkout and verify the address is updated in the user's profile (`/account/profile`).

*(Other sections remain accurate based on previous updates)*

---

## 12. Future Enhancements & Recommendations

*(Updated Priorities - v16.2)*

1.  **Resolve Checkout Payment Initialization Error (Highest Priority):** Use the added JS console logs to diagnose why `Stripe(stripePublicKey)` is failing or why the key might be missing/invalid. Fix the root cause (could be config, JS load order, network, etc.).
2.  **Implement Controller Parsing for JSON Textareas (Admin Product Form) (High Priority):** Update `ProductController::saveAdminProduct` to parse `benefits` and `gallery_images` textareas.
3.  **Fix Error Handling ("Headers Already Sent") (Medium Priority):** Improve `ErrorHandler` robustness.
4.  **Review & Tighten Content Security Policy (Medium Priority):** Update CSP in `config.php`.
5.  **Review Rate Limiting Coverage (Low Priority):** Ensure coverage on admin endpoints.
6.  **Code Quality & Refactoring (Ongoing/Future):** Composer, Autoloader, Routing Component, Templating Engine, .env, Migrations, Tests.
7.  **Full Admin Panel (Future):** CRUD for Orders, Users. Improve Quiz Analytics. Add Admin Dashboard content.
8.  **Advanced Features (Future):** Search improvements, user reviews, wishlists, actual file uploads.

---

## 13. Appendices

### A. Key File Summaries

*(Updated status notes for v16.2)*

| File/Folder                     | Purpose                                                          | Status Notes (v16.2)                                                                             |
| :------------------------------ | :--------------------------------------------------------------- | :----------------------------------------------------------------------------------------------- |
| `index.php`                     | Entry point, routing, core includes, auto POST CSRF validation   | OK. Admin Product routing functional.                                                            |
| `config.php`                    | DB credentials, App/Security settings, API keys                | OK. CSP/Rate Limit review needed. Secrets exposure.                                              |
| `includes/SecurityMiddleware.php`| Static security helpers (validation, CSRF)                     | OK.                                                                                              |
| `controllers/BaseController.php`| Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)       | OK. `redirect` fixed. Rate limiting logic OK.                                                    |
| `controllers/AccountController.php`| User auth/profile logic. AJAX/standard POST.                   | Functional. Rate limiting applied. Registration fixed. Profile Address saving fixed.         |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                              | Loads OK. **Handles Address Line 2 saving.** Confirmation Flow Fixed. Rate limiting applied.       |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                           | OK. Webhook session dependency removed. `getStripeClient()` available.                         |
| `controllers/CartController.php`    | Handles cart logic via AJAX.                                     | Functional. Cart storage standardized. Reliable session count update.                    |
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | Functional. Filtering uses Named Placeholders. Admin routing OK. **Needs JSON textarea parsing.** |
| `controllers/QuizController.php`    | Quiz logic.                                                    | Functional. CSRF fixed. Results logic uses Session.                                        |
| `controllers/NewsletterController.php`| Newsletter signup/unsubscribe.                               | OK. Rate limiting applied.                                                                       |
| `models/User.php`               | User DB logic (PDO Prepared Statements).                     | Updated & Functional. `updateAddress` fixed. `getAddress` OK. Requires patch.                |
| `models/Order.php`              | Order DB logic (PDO Prepared Statements).                    | OK. `create` signature updated. Item fetching OK.                                             |
| `models/Product.php`            | Product DB logic (PDO Prepared Statements).                  | Functional. Filtering uses Named Placeholders. Compatible with controller. OK.          |
| `models/Cart.php`               | DB cart logic (PDO Prepared Statements).                     | OK. Used only for logged-in users now.                                                         |
| `models/Quiz.php`               | Quiz DB logic (PDO Prepared Statements).                     | Functional. `recommendations` column selected correctly.                                 |
| `views/layout/header.php`       | Header, nav, assets, outputs global CSRF token & JS config.    | OK. Cart count logic simplified. **Outputs data-* attributes.**                               |
| `views/account/*.php`           | Account views (Dashboard, Orders, Profile, etc.)                 | UI Fixed. Profile address UI functional. Compatible with CSS/JS. OK.                         |
| `views/admin/products.php`      | Admin: List products view.                                       | Functional. Displays data, provides actions.                                                 |
| `views/admin/product_form.php`  | Admin: Create/Edit product form view.                            | Functional. Displays fields. **Requires controller update for JSON fields.**                   |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                              |
| `views/checkout.php`            | Checkout form view.                                              | Loads OK. **Includes Address Line 2.** AJAX/Stripe OK. **Payment init debugging added.**        |
| `views/order_confirmation.php`  | Confirmation view.                                               | Functional. Controller logic fixed.                                                        |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token. JS fixed.                                                  |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                    |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Handles CSRF. **Checkout debugging added. Cart, Quiz, Admin JS fixed.**                    |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted.                                                        |
| `includes/EmailService.php`     | Sends emails (Welcome, Reset, Confirmation, etc.)              | Functional. DB logging column fixed.                                                     |
| `db/*`                          | Schema files.                                                    | **Update script is mandatory.**                                                                  |

### B. Glossary

*(Standard terms)*

### C. Code Snippets and Patterns (CSRF, Confirmation, Named Placeholders, Address Handling, JSON Textarea Parsing, Stripe Init Debugging)

**CSRF Token Pattern:** *(Unchanged)*

**Implemented Order Confirmation Flow:** *(Unchanged - flow description is correct)*

**Named Placeholder Pattern (Example from `ProductModel::getFiltered`)** *(Unchanged)*

**Address Handling Pattern (Functional):** *(Updated to show checkout mapping)*

*   **Schema:** `users` table has `address_line1`, `address_line2`, `city`, `state`, `postal_code`, `country` columns (via patch).
*   **Profile View (`views/account/profile.php`):** Displays address fields using `$userAddress`. Form uses field names like `address_line1`. Submits POST to `AccountController::updateProfile` with `action=update_address`.
*   **Profile Controller (`AccountController::handleUpdateAddress`):** Receives POST. Validates input. Builds `$addressData` array with keys like `address_line1`. Calls `UserModel::updateAddress($userId, $addressData)`.
*   **Checkout View (`views/checkout.php`):** Pre-fills address using `$userAddress`. Form uses `shipping_` prefixed names (`shipping_address`, `shipping_address_line2`, etc.). Optional "Save Address" checkbox exists.
*   **Checkout Controller (`CheckoutController::processCheckout`):** If "Save Address" checked, *maps* `$postData` keys to the structure expected by `UserModel::updateAddress`.

    ```php
    // Snippet from CheckoutController::processCheckout (Mapping for Save Address)
    if ($saveAddress) {
         $addressUpdateData = [
            'address_line1' => $postData['shipping_address'], // Map 'shipping_address' to 'address_line1'
            'address_line2' => $postData['shipping_address_line2'], // Map 'shipping_address_line2' to 'address_line2'
            'city'          => $postData['shipping_city'],
            'state'         => $postData['shipping_state'],
            'postal_code'   => $postData['shipping_zip'],
            'country'       => $postData['shipping_country']
         ];
        if (!$this->userModel->updateAddress($userId, $addressUpdateData)) {
             // Log warning...
        }
    }
    ```
*   **Model (`UserModel::updateAddress` - Fixed):** Receives `$addressData` (with keys like `address_line1`). Binds keys correctly to the `UPDATE users` query.

**Email Logging (`EmailService::logEmail` - Fixed):** *(Unchanged)*

**JSON Textarea Parsing (Required in `ProductController::saveAdminProduct`):** *(Unchanged - still required)*

**Stripe Initialization Debugging (Added to `js/main.js::initCheckoutPage`):**

```javascript
// Snippet from js/main.js :: initCheckoutPage

// --- Basic Checks ---
console.log("Stripe Public Key:", stripePublicKey); // <<< Added Log
if (!stripePublicKey) {
    showMessage("Stripe configuration error. Payment cannot proceed.", true);
    setLoading(false, true); // Disable button permanently
    return;
}
// ... other checks ...

// --- Initialize Stripe ---
try {
     stripe = Stripe(stripePublicKey);
     console.log("Stripe object initialized:", stripe); // <<< Added Log
     // ... rest of initialization ...
     console.log("Stripe Payment Element mounted."); // <<< Added Log
} catch (stripeError) {
    console.error("Stripe initialization error:", stripeError); // <<< Added Log
    showMessage("Could not initialize payment system. Please refresh.", true);
    setLoading(false, true);
    return;
}
```

### D. Mandatory Database Patch

*(Content remains the same - emphasize its importance)*

---
https://drive.google.com/file/d/123hv5nKUqgewF0EijNEsVCmfOVNse9tV/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%2215yICqTsxNyvXC4y7neLcks2qaQILb4gG%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/18UepE9wE5D-H6xJkVj2fAbRziUnqGSyj/view?usp=sharing, https://drive.google.com/file/d/18hKH60FDKEY_aG78LlhT0ybPzHbxMinI/view?usp=sharing, https://drive.google.com/file/d/1DKO9eGiUdVI9udIik0pR1iElemo8tLDT/view?usp=sharing, https://drive.google.com/file/d/1Jk1TZjEfqn7HSuXLObm-VYZJ956MwRaW/view?usp=sharing, https://drive.google.com/file/d/1L9mt8UvWrHDeKtp1QeA_LTg2RGHXwiLK/view?usp=sharing, https://drive.google.com/file/d/1OvoiijsaPFVLD2AYf6K5px9XIrMGaRlT/view?usp=sharing, https://drive.google.com/file/d/1POQ7zj01grkz_3sKF5MxgghBZsghIE46/view?usp=sharing, https://drive.google.com/file/d/1PcUogRJz2nKfaeLtpkBoQS_GbYumGZXX/view?usp=sharing, https://drive.google.com/file/d/1Td98N2ofZEG8zTTQOpfogwenElY47oL_/view?usp=sharing, https://drive.google.com/file/d/1Vp9gtM4iMeGPrMYc7JNIJcT0w4m_zi_J/view?usp=sharing, https://drive.google.com/file/d/1Z6f86ThuU9gETnagrl8TB38UaUh6b1NT/view?usp=sharing, https://drive.google.com/file/d/1ZvOHIr44PIeGZ56xtbyiMu0LMVz2a3G5/view?usp=sharing, https://drive.google.com/file/d/1clRGf0pGXdM3NIZdiLxvoYxzE33N66dM/view?usp=sharing, https://drive.google.com/file/d/1g3JIk6QorfOBttNW95DPCj0MlGLfBoLC/view?usp=sharing, https://drive.google.com/file/d/1j8nXz0uX0mm4yl6vnf0KW4yfdG_POxz1/view?usp=sharing, https://drive.google.com/file/d/1olCawvC_W-DhLjpDmmMJGKdHNJrNHiO7/view?usp=sharing, https://drive.google.com/file/d/1v2ewLFnIvpv_t2gd5uEqI6bYYEDxqmqW/view?usp=sharing
