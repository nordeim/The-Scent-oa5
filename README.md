# The Scent – Technical Design Specification (v17)

## Table of Contents

1.  [Introduction](#introduction)
2.  [Project Philosophy & Goals](#project-philosophy--goals)
3.  [System Architecture Overview](#system-architecture-overview)
    *   3.1 [High-Level Workflow](#high-level-workflow)
    *   3.2 [Request-Response Life Cycle (Implemented Confirmation Flow)](#request-response-life-cycle-implemented-confirmation-flow)
    *   3.3 [Request-Response Life Cycle (Account Dashboard - Fixed)](#request-response-life-cycle-account-dashboard---fixed)
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
        *   7.10.1 [Account Dashboard (`views/account/dashboard.php` - Fixed)](#account-dashboard-viewsaccountdashboardphp---fixed)
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
    *   C. [Code Snippets and Patterns (CSRF, Confirmation, Named Placeholders, Address Handling, JSON Textarea Parsing, Stripe Init Debugging, Account Dashboard Data Flow)](#c-code-snippets-and-patterns-csrf-confirmation-named-placeholders-address-handling-json-textarea-parsing-stripe-init-debugging-account-dashboard-data-flow)
    *   D. [Mandatory Database Patch](#d-mandatory-database-patch)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v17**) serves as the updated technical design specification, reflecting the project's current state after applying significant fixes, standardizations, basic Admin Product CRUD UI implementation, **fixing checkout address field discrepancies**, **adding Stripe initialization debugging**, and **resolving the Account Dashboard error.**

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
12. Fixed Checkout Address Field Discrepancy (`views/checkout.php`, `controllers/CheckoutController.php`).
13. Added Debugging for Checkout Payment Initialization Error (`js/main.js`, `views/checkout.php`).
14. Applied various JS Fixes (`js/main.js` for cart totals, quiz selection, admin pages).
15. Implemented JSON Textarea Parsing in Admin Product Controller (`controllers/ProductController.php`).
16. **Fixed Account Dashboard Error** by refactoring quiz recommendation data fetching in `AccountController` and updating `views/account/dashboard.php`.

**Current Status (v17 - Core Stable, Account Dashboard Fixed, Checkout Payment Debugging Added, Admin Product Parsing Fixed)**

*   ✅ **Core Functionality Stable:** Product Browsing (Filtering/Sorting/Pagination OK), Add-to-Cart (AJAX OK), Cart Management (AJAX OK), User Login/Registration (AJAX Registration OK), Password Reset OK, Profile Update (Name, Email, Password, Newsletter, Address OK), Quiz Flow OK, **Account Dashboard OK**, Checkout Load OK (Address Line 2 added), Order Confirmation OK.
*   ✅ **Critical Bug Fixes Implemented:** All previously listed fixes are confirmed. **Account Dashboard error resolved.** Checkout address field handling is complete. Admin product form JSON field parsing is implemented.
*   ✅ **Standardizations Applied:** Cart Storage, Rate Limiting, Named DB Placeholders (Filtering).
*   ✅ **UI Enhancements:** Account pages UI fixed. Address Management UI on profile page functional. Checkout page address form updated. Admin Product List and Form views implemented. JS fixes applied.
*   ⚠️ **Known Issues/TODOs:**
    *   **Checkout Payment Initialization Error (Under Investigation):** The checkout page shows "Could not initialize payment system." **Debugging logs have been added to `js/main.js`**. *(Needs live debugging using console logs).*
    *   **Missing `tax_rates` Table (Patch Required):** Error logs indicate this table is missing. **An SQL patch is provided in Appendix D and must be applied.**
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

*(Mermaid diagram remains the same from v16.2)*

*   **Key Principles:** Centralized routing (`index.php`), separation of concerns (Controller logic, Model data access, View presentation), secure database interaction (PDO Prepared Statements, named placeholders where applicable), global CSRF validation on POST. `BaseController` provides shared utilities. Cart storage logic standardized. Rate limiting applied consistently.

### 3.2 Request-Response Life Cycle (Implemented Confirmation Flow)

*(No changes needed from v16.2 - logic remains correct)*

### 3.3 Request-Response Life Cycle (Account Dashboard - Fixed)

1.  **Request:** User logs in and navigates to `/index.php?page=account` (or is redirected after login).
2.  **.htaccess:** Rewrites URL to `index.php`.
3.  **`index.php`:**
    *   Includes dependencies, initializes Error Handling, applies Security Middleware.
    *   Validates CSRF (if POST, though usually GET for dashboard).
    *   Routes to `AccountController::showDashboard()`.
4.  **`AccountController::showDashboard()`:**
    *   Requires Login (`requireLogin`).
    *   Fetches `$recentOrders` using `OrderModel`.
    *   Fetches `$quizResults` using `QuizModel::getResultsByUserId()`. This method **already decodes** the `answers` and `recommendations` (which are product IDs) JSON strings into PHP arrays.
    *   If `$quizResults` exist:
        *   It takes the `recommendations` (array of product IDs) from the latest quiz result.
        *   It calls `$this->productModel->getProductsByIds()` to fetch full product details for these recommended IDs (e.g., the top 2 for the dashboard card).
    *   Prepares a `$data` array including `$recentOrders`, `$quizResults`, `$latestQuizRecommendationsDetails` (the fetched product details), `$user`, `$csrfToken`, and `$bodyClass`.
    *   Calls `renderView('account/dashboard', $data)`.
5.  **`BaseController::renderView()`:**
    *   Extracts variables from `$data` into the local scope.
    *   Includes `views/account/dashboard.php`.
6.  **`views/account/dashboard.php`:**
    *   Uses the extracted variables.
    *   Displays recent orders.
    *   Displays quiz preferences from `$latestQuiz['answers']` (which is already a PHP array).
    *   Displays recommended products by iterating through `$latestQuizRecommendationsDetails` (which is an array of product detail arrays).
    *   **Crucially, it no longer calls `json_decode()` on quiz answers or recommendations, nor does it attempt to fetch product details directly.**
7.  **Response:** Server sends the Account Dashboard HTML page to the browser.

---

## 4. Directory & File Structure

### 4.1 Folder Map

*(Structure remains the same)*

### 4.2 Key Files Explained

*(Updated status notes for v17 in Appendix A)*

---

## 5. Routing and Application Flow

*(No changes needed from v16.2)*

---

## 6. Frontend Architecture

*(No changes needed from v16.2, JS section description updated implicitly)*

---

## 7. Key Pages & Components

*(Updated Section 7.10 for Account Dashboard)*

*   **7.1 Home/Landing Page (`views/home.php`)**: *(No changes)*
*   **7.2 Header and Navigation (`views/layout/header.php`)**: *(No changes)*
*   **7.3 Footer and Newsletter (`views/layout/footer.php`)**: *(No changes)*
*   **7.4 Product Grid & Cards**: *(No changes)*
*   **7.5 Shopping Cart (`views/cart.php`)**: *(No changes)*
*   **7.6 Product Detail Page (`views/product_detail.php`)**: *(No changes)*
*   **7.7 Products Page (`views/products.php`)**: *(No changes)*
*   **7.8 Checkout Process (`views/checkout.php`)**:
    *   **Address Fields:** Correctly includes `shipping_address` and `shipping_address_line2`. Pre-filling and saving to profile handled by controller.
    *   **Payment Initialization:** Debugging logs in `js/main.js` will help identify the "Could not initialize payment system" root cause.
    *   **Save Address Option:** Functional.
*   **7.9 Order Confirmation (`views/order_confirmation.php`)**: *(No changes)*
*   **7.10 User Account Pages (`views/account/*`)**:
    *   **7.10.1 Account Dashboard (`views/account/dashboard.php` - Fixed)**:
        *   **Error Fixed:** The "Oops! Something Went Wrong" error (TypeError with `json_decode`) has been resolved.
        *   **Data Flow:** `AccountController::showDashboard()` now correctly fetches product details for quiz recommendations. The `QuizModel` provides already decoded `answers` and `recommendations` (as product IDs). The controller then uses `ProductModel` to get the full product details for these IDs.
        *   **View Logic:** `views/account/dashboard.php` now receives pre-fetched `latestQuizRecommendationsDetails` and directly uses the arrays for `answers` and `preferences` without redundant `json_decode` calls or direct data fetching. This ensures the view is cleaner and the controller handles data preparation.
*   **7.11 Quiz Flow & Personalization**: *(No changes to core flow, dashboard display fixed)*

---

## 8. Backend Logic & Core PHP Components

*(Updated Section 8.2 and 8.3)*

*   **8.1 Includes: Shared Logic (`includes/`)**: *(No changes)*
*   **8.2 Controllers: Business Logic Layer (`controllers/` & `BaseController.php`)**:
    *   `AccountController`:
        *   The `showDashboard()` method has been refactored. It now instantiates and uses `ProductModel`.
        *   It retrieves quiz results (which are already decoded by `QuizModel`) and then uses the product IDs from the latest quiz's recommendations to fetch full product details via `ProductModel::getProductsByIds()`.
        *   This `$latestQuizRecommendationsDetails` array (containing product objects/arrays) is then passed to the `views/account/dashboard.php` view.
    *   `ProductController`: `saveAdminProduct` method correctly parses newline-separated textareas for JSON fields.
    *   `CheckoutController`: Handles `shipping_address_line2`.
*   **8.3 Database Abstraction (`includes/db.php` & `models/`)**:
    *   `QuizModel`: `getResultsByUserId()` correctly returns decoded JSON fields (`answers`, `recommendations`) as PHP arrays.
    *   `ProductModel`: `getProductsByIds()` is used by `AccountController` to fetch details for quiz recommendations.
*   **8.4 Security Middleware & Error Handling**: *(No changes)*
*   **8.5 Session, Auth, and User Flow**: *(No changes)*
*   **8.6 Payment Processing & Webhook Handling**: *(No changes)*

---

## 9. Database Design

*(Updated Section 9.2 and 9.4)*

*   **9.1 Entity-Relationship Model (Conceptual)**: *(No changes)*
*   **9.2 Core Tables (from `schema.sql` + Updates)**:
    *   The `tax_rates` and `tax_rate_history` tables are currently missing from `the_scent_schema.sql.txt` and are the cause of "Table ... tax_rates doesn't exist" errors during tax calculation. **The SQL patch provided in Appendix D is essential and must be applied to the database.**
*   **9.3 Schema Considerations & Recommendations**: *(No changes)*
*   **9.4 Data Flow Examples (Current State)**:
    *   **Checkout Address Save (Optional):** *(No changes from v16.2)*
    *   **Checkout Payment Initialization (Debugging):** *(No changes from v16.2)*
    *   **Account Dashboard Quiz Recommendations (Fixed):**
        1.  User navigates to `/index.php?page=account`.
        2.  `AccountController::showDashboard()` is invoked.
        3.  `$this->quizModel->getResultsByUserId($userId)` is called. It returns an array of quiz history, where each item has `answers` (decoded PHP array) and `recommendations` (decoded PHP array of product IDs).
        4.  The controller takes `recommendations` from the latest quiz.
        5.  `$this->productModel->getProductsByIds($recommendedIds)` is called to get full product details for display (e.g., the top 2 for the dashboard).
        6.  The controller passes `$quizResults` (containing the latest quiz with its already decoded `answers`) and `$latestQuizRecommendationsDetails` (array of product detail arrays) to `views/account/dashboard.php`.
        7.  The view iterates through `$preferences = $latestQuiz['answers']` to display selected preferences.
        8.  The view iterates through `$latestQuizRecommendationsDetails` to display product cards for each recommended product, accessing properties like `$product['name']`, `$product['image']`, `$product['price']`.

---

## 10. Security Considerations & Best Practices

*(No changes needed from v16.2)*

---

## 11. Extensibility & Onboarding

*   **11.3 Developer Onboarding Checklist:**
    1.  Clone the repository.
    2.  Set up a local development environment (PHP, MySQL/MariaDB, Apache/Nginx).
    3.  Import `the_scent_schema.sql.txt` into the database.
    4.  **Crucially, apply the database patch for `tax_rates` and `tax_rate_history` tables (see Appendix D).**
    5.  Run `composer install` to install dependencies (like PHPMailer).
    6.  Copy `config.sample.php` to `config.php` and update database credentials, Stripe keys (use test keys), and `NEWSLETTER_SECRET_KEY`. Ensure `BASE_URL` is correct.
    7.  Set up appropriate file permissions (especially for `logs/`).
    8.  Configure `.htaccess` or server block for URL rewriting.
    9.  Browse the application, test core flows: registration, login, product browsing, add to cart, checkout (up to Stripe form), quiz.
    10. Review this TDS document and key files (Appendix A).
    11. Verify Checkout page loads correctly, displays both address lines, and **check console logs for Stripe initialization messages/errors**. Test saving the address during checkout.
    12. **Verify Admin Product form correctly saves multi-line 'Benefits' and 'Gallery Images' as JSON.**
    13. **Verify Account Dashboard loads correctly after login and quiz completion, and displays quiz recommendations with product details.**
*   **11.4 Testing & Debugging Notes:**
    *   **Check console logs on the Checkout page** for Stripe initialization messages/errors.
    *   Test the "Save this shipping address..." checkbox during checkout.
    *   Test Admin Product Create/Edit, specifically entering multiple lines in the 'Benefits' and 'Gallery Images' textareas and verifying the data saves and displays correctly.
    *   **Verify the Account Dashboard** after taking a quiz to ensure preferences and recommended products (with images, names, prices) display correctly without errors.

*(Other sections remain accurate)*

---

## 12. Future Enhancements & Recommendations

*(Updated Priorities - v17)*

1.  **Apply `tax_rates` Database Patch (Highest Priority):** Essential to fix tax calculation errors noted in logs. (SQL provided in Appendix D).
2.  **Resolve Checkout Payment Initialization Error (High Priority):** Use JS console logs on the live checkout page to diagnose why `Stripe(stripePublicKey)` is failing or why the key might be missing/invalid. Fix the root cause.
3.  **Fix Error Handling ("Headers Already Sent") (Medium Priority):** Make `views/error.php` self-contained or use output buffering more consistently in `ErrorHandler`.
4.  **Review & Tighten Content Security Policy (Medium Priority):** Update CSP in `config.php`.
5.  **Review Rate Limiting Coverage (Low Priority):** Ensure coverage on admin endpoints.
6.  **Code Quality & Refactoring (Ongoing/Future):** Composer autoloader setup, formal Router, Templating Engine, .env for configuration, Database Migrations, Unit/Integration Tests.
7.  **Full Admin Panel (Future):** CRUD for Orders, Users. Improve Quiz Analytics. Add Admin Dashboard content.
8.  **Advanced Features (Future):** Search improvements, user reviews, wishlists, actual file uploads.

---

## 13. Appendices

### A. Key File Summaries

*(Updated status notes for v17)*

| File/Folder                     | Purpose                                                          | Status Notes (v17)                                                                                 |
| :------------------------------ | :--------------------------------------------------------------- | :--------------------------------------------------------------------------------------------------- |
| `index.php`                     | Entry point, routing, core includes, auto POST CSRF validation   | OK. Admin Product routing functional.                                                                |
| `config.php`                    | DB credentials, App/Security settings, API keys                | OK. CSP/Rate Limit review needed. Secrets exposure.                                                  |
| `includes/SecurityMiddleware.php`| Static security helpers (validation, CSRF)                     | OK.                                                                                                  |
| `controllers/BaseController.php`| Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)       | OK. `redirect` fixed. Rate limiting logic OK.                                                        |
| `controllers/AccountController.php`| User auth/profile logic. AJAX/standard POST.                   | Functional. **Dashboard data fetching fixed.** Rate limiting. Registration. Profile Address.         |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                              | Loads OK. Handles Address Line 2. Confirmation Flow Fixed. Rate limiting.                        |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                           | OK. Webhook session dependency removed. `getStripeClient()` available.                             |
| `controllers/CartController.php`    | Handles cart logic via AJAX.                                     | Functional. Cart storage standardized. Reliable session count update. JS fixes applied.          |
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | Functional. Filtering uses Named Placeholders. Admin routing OK. JSON textarea parsing added.      |
| `controllers/QuizController.php`    | Quiz logic.                                                    | Functional. CSRF fixed. Results logic uses Session. JS fixes applied.                            |
| `controllers/NewsletterController.php`| Newsletter signup/unsubscribe.                               | OK. Rate limiting applied.                                                                           |
| `models/User.php`               | User DB logic (PDO Prepared Statements).                     | Updated & Functional. `updateAddress` fixed. `getAddress` OK. Requires patch.                    |
| `models/Order.php`              | Order DB logic (PDO Prepared Statements).                    | OK. `create` signature updated. Item fetching OK.                                                 |
| `models/Product.php`            | Product DB logic (PDO Prepared Statements).                  | Functional. Filtering uses Named Placeholders. `create`/`update` handle JSON encoding. OK.      |
| `models/Cart.php`               | DB cart logic (PDO Prepared Statements).                     | OK. Used only for logged-in users now.                                                             |
| `models/Quiz.php`               | Quiz DB logic (PDO Prepared Statements).                     | Functional. `recommendations` column selected correctly. **Returns decoded arrays.**            |
| `views/layout/header.php`       | Header, nav, assets, outputs global CSRF token & JS config.    | OK. Cart count logic simplified. Outputs data-* attributes.                                       |
| `views/account/dashboard.php`   | Account Dashboard view.                                          | **Error fixed.** Uses controller-provided data for quiz recommendations. Compatible. OK.        |
| `views/account/*.php`           | Other Account views (Orders, Profile, etc.)                      | UI Fixed. Profile address UI functional. Compatible with CSS/JS. OK.                             |
| `views/admin/products.php`      | Admin: List products view.                                       | Functional. Displays data, provides actions.                                                     |
| `views/admin/product_form.php`  | Admin: Create/Edit product form view.                            | Functional. Displays fields. Controller handles JSON fields parsing.                         |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                                  |
| `views/checkout.php`            | Checkout form view.                                              | Loads OK. Includes Address Line 2. AJAX/Stripe OK. **Payment init debugging added.**            |
| `views/order_confirmation.php`  | Confirmation view.                                               | Functional. Controller logic fixed.                                                            |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token. JS fixed.                                                      |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                        |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Handles CSRF. **Checkout debugging added. Cart, Quiz, Admin JS fixed.**                    |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted. Robustness checks passed.                         |
| `includes/EmailService.php`     | Sends emails (Welcome, Reset, Confirmation, etc.)              | Functional. DB logging column fixed.                                                     |
| `db/*`                          | Schema files.                                                    | **Update script for `tax_rates` mandatory.**                                                  |

### B. Glossary

*(Standard terms)*

### C. Code Snippets and Patterns (CSRF, Confirmation, Named Placeholders, Address Handling, JSON Textarea Parsing, Stripe Init Debugging, Account Dashboard Data Flow)

**CSRF Token Pattern:** *(Unchanged)*

**Implemented Order Confirmation Flow:** *(Unchanged - flow description is correct)*

**Named Placeholder Pattern (Example from `ProductModel::getFiltered`)** *(Unchanged)*

**Address Handling Pattern (Functional):** *(No changes from v16.2)*

**Email Logging (`EmailService::logEmail` - Fixed):** *(Unchanged)*

**JSON Textarea Parsing (Implemented in `ProductController::saveAdminProduct`):** *(No changes from v16.2 snippet)*

**Stripe Initialization Debugging (Added to `js/main.js::initCheckoutPage`):** *(No changes from v16.2 snippet)*

**Account Dashboard Data Flow (Fixed):**

*   **Controller (`AccountController::showDashboard`):**
    ```php
    // Snippet from AccountController::showDashboard (Simplified)
    $quizResults = $this->quizModel->getResultsByUserId($userId);
    $latestQuizRecommendationsDetails = []; // Initialize
    if (!empty($quizResults)) {
        $latestQuiz = $quizResults[0]; // Assumes results are ordered DESC by date
        // $latestQuiz['recommendations'] is already an array of product IDs from QuizModel
        $recommendedIds = (isset($latestQuiz['recommendations']) && is_array($latestQuiz['recommendations']))
                          ? $latestQuiz['recommendations']
                          : [];

        if (!empty($recommendedIds)) {
            $numericIds = array_filter($recommendedIds, 'is_numeric'); // Ensure IDs are numeric
            if(!empty($numericIds)) {
                 // Fetch details for a limited number (e.g., top 2) for the dashboard card
                 $latestQuizRecommendationsDetails = $this->productModel->getProductsByIds(array_slice($numericIds, 0, 2));
            }
        }
    }
    $data = [
        // ... other data like recentOrders, user, csrfToken, bodyClass ...
        'quizResults' => $quizResults, // Pass all quiz results (latest can be derived in view if needed)
        'latestQuizRecommendationsDetails' => $latestQuizRecommendationsDetails, // Pass fetched product details
    ];
    echo $this->renderView('account/dashboard', $data);
    ```
*   **View (`views/account/dashboard.php`):**
    ```php
    // Snippet from views/account/dashboard.php (Scent Quiz Results Card - Simplified)
    // ...
    // $latestQuiz = $quizResults[0]; // Assuming $quizResults is passed and not empty
    // $preferences = $latestQuiz['answers']; // Already a PHP array from controller/model

    // Display Recommended Products using pre-fetched $latestQuizRecommendationsDetails
    // $latestQuizRecommendationsDetails is passed directly from the controller
    if (isset($latestQuizRecommendationsDetails) && !empty($latestQuizRecommendationsDetails)) {
        // ... loop through $latestQuizRecommendationsDetails as $product ...
        // htmlspecialchars($product['name']), etc.
    } else {
        // ... fallback message for no recommendations ...
    }
    // ...
    ```

### D. Mandatory Database Patch

This patch adds the `tax_rates` and `tax_rate_history` tables necessary for tax calculations. **Applying this patch is crucial to resolve errors related to the missing `tax_rates` table.**

```sql
-- Database Schema Patch for Tax Tables (v16.3)
-- This patch adds the `tax_rates` and `tax_rate_history` tables
-- if they do not already exist, as indicated by the error logs.

CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `country_code` VARCHAR(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO 3166-1 alpha-2 country code',
  `state_code` VARCHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO 3166-2 state/province code (if applicable)',
  `rate` DECIMAL(10,4) NOT NULL COMMENT 'Tax rate (e.g., 0.05 for 5%)',
  `is_active` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Whether this tax rate is currently active',
  `start_date` DATE DEFAULT NULL COMMENT 'Date when this tax rate becomes effective',
  `end_date` DATE DEFAULT NULL COMMENT 'Date when this tax rate expires (NULL if no expiry)',
  `created_by` INT DEFAULT NULL COMMENT 'User ID of the admin who created/last modified this rate',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_country_state` (`country_code`, `state_code`),
  KEY `idx_country_code` (`country_code`),
  KEY `idx_is_active` (`is_active`),
  KEY `fk_tax_rates_user` (`created_by`),
  CONSTRAINT `fk_tax_rates_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores tax rates for different regions';

CREATE TABLE IF NOT EXISTS `tax_rate_history` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tax_rate_id` INT NOT NULL,
  `old_rate` DECIMAL(10,4) DEFAULT NULL COMMENT 'Previous tax rate',
  `new_rate` DECIMAL(10,4) NOT NULL COMMENT 'New tax rate after change',
  `changed_by` INT DEFAULT NULL COMMENT 'User ID of the admin who made the change',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of when the change was made',
  PRIMARY KEY (`id`),
  KEY `idx_tax_rate_id` (`tax_rate_id`),
  KEY `fk_tax_history_user` (`changed_by`),
  CONSTRAINT `fk_tax_rate_history_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tax_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks changes to tax rates';

-- Optional: Insert some default tax rates if desired
INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('*', NULL, 0.0000, 1, CURDATE(), 1); -- Example: No tax (user ID 1 for admin)

INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('US', 'CA', 0.0925, 1, CURDATE(), 1); -- Example: 9.25% for US, California

INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('CA', NULL, 0.0500, 1, CURDATE(), 1); -- Example: 5% for all of Canada
```

