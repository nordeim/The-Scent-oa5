# The Scent – Technical Design Specification (v16.0)

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
    *   C. [Code Snippets and Patterns (CSRF, Implemented Confirmation Flow, Named Placeholders)](#c-code-snippets-and-patterns-csrf-implemented-confirmation-flow-named-placeholders)
    *   D. [Mandatory Database Patch](#d-mandatory-database-patch)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v16.0**) serves as the updated technical design specification, reflecting the project's current state after applying significant fixes and standardizations.

This version documents **major improvements and stability fixes applied sequentially**:

1.  **Fixed Checkout Page Load:** Resolved fatal error preventing `views/checkout.php` loading. Requires DB patch application.
2.  **Fixed Order Confirmation Flow:** Replaced flawed session logic with robust Stripe API verification.
3.  **Fixed Account Pages UI:** Corrected broken layout by including standard headers/footers.
4.  **Fixed Quiz CSRF Error:** Resolved CSRF token issue preventing quiz submission.
5.  **Fixed Product Filter SQL Error:** Corrected query error (`SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters`) when filtering products by category by standardizing on **named placeholders** in `ProductModel`.
6.  **Fixed Quiz Results Logic:** Ensured the results page (`quiz_results.php`) consistently displays the products recommended during the *actual* quiz submission by leveraging session storage, resolving inconsistencies caused by `RAND()` ordering.
7.  **Standardized Cart Storage:** `CartController` now strictly separates Session (Guest) vs. DB (Logged-in) storage. `$_SESSION['cart_count']` is the reliable source for the header.
8.  **Standardized Rate Limiting:** Rate limiting is now consistently applied via `BaseController::validateRateLimit()` to key sensitive endpoints (Login, Register, Password Reset, Profile Update, Newsletter Subscribe, Coupon Apply, Checkout Submit). Relies on APCu extension.

**Remaining Known Issues / Areas for Improvement:**

*   **Address Saving:** Logic to *save* user addresses during profile updates or checkout is not yet implemented. Checkout pre-filling depends on data existing in the DB.
*   **Error Handling ("Headers Already Sent"):** Issue noted in `ErrorHandler.php` requires further investigation to prevent warnings if errors occur late in the request lifecycle (making `views/error.php` self-contained might help).
*   **Content Security Policy (CSP):** Needs review/tightening for production.
*   **Rate Limiting Coverage:** While applied to key areas, a full review for other potentially sensitive endpoints (e.g., admin actions) is recommended.
*   **Admin Panel Features:** Basic features exist (Coupons, Quiz Analytics, Product List/Form); full CRUD for Products/Orders/Users needed.
*   **Code Quality/Refactoring:** Composer integration, dedicated router, templating engine, `.env` files, migrations, and tests are recommended for future maintainability.

This document provides a comprehensive overview of the current architecture, logic, and flow, serving as an onboarding guide and reference for ongoing development.

---

## 2. Project Philosophy & Goals

*   **Security First:** Implemented via PDO Prepared Statements (using named placeholders where applicable), input validation (`SecurityMiddleware`), secure session handling, CSRF protection (Synchronizer Token Pattern, enforced globally on POST), security headers (CSP needs review), **rate limiting applied consistently to key endpoints**.
*   **Simplicity & Maintainability:** Modular structure, clear includes in `index.php`. Consistent coding patterns enforced.
*   **Extensibility:** Architecture allows adding new features/pages. Clear extension points.
*   **Performance:** Direct routing, PDO prepared statements. CDN for frontend libs. APCu used for rate limiting.
*   **Modern User Experience:** Responsive design (Tailwind), subtle animations (AOS.js, Particles), AJAX interactions (Cart, Newsletter, Login/Register). **Core user flows functional and stable.**
*   **Transparency:** Explicit routing and includes in `index.php`.
*   **Accessibility & SEO:** Semantic HTML, `aria-label` usage. Basic practices followed.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

*(Mermaid diagram remains the same as TDS v15.0)*

*   **Key Principles:** Centralized routing (`index.php`), separation of concerns (Controller logic, Model data access, View presentation), secure database interaction (PDO Prepared Statements, **primarily using named placeholders now**), global CSRF validation on POST. `BaseController` provides shared utilities. **Cart storage logic standardized in `CartController`.** **Rate limiting applied in relevant controllers.**

### 3.2 Request-Response Life Cycle (Implemented Confirmation Flow)

*(Flow remains the same as TDS v15.0 - the implemented logic is now correct and robust)*

---

## 4. Directory & File Structure

### 4.1 Folder Map

*(Structure remains the same as TDS v15.0)*

### 4.2 Key Files Explained

*(Updated status notes)*

| File/Folder                     | Purpose                                                          | Status Notes                                                                                     |
| :------------------------------ | :--------------------------------------------------------------- | :----------------------------------------------------------------------------------------------- |
| `index.php`                     | Entry point, routing, core includes, auto POST CSRF validation   | OK. Correct DI. Routing logic functional.                                                        |
| `config.php`                    | DB credentials, App/Security settings, API keys                | OK. CSP/Rate Limit review needed. Secrets exposure.                                              |
| `includes/SecurityMiddleware.php`| Static security helpers (validation, CSRF)                     | OK.                                                                                              |
| `controllers/BaseController.php`| Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)       | OK. `redirect` fixed. Rate limiting logic OK.                                                    |
| `controllers/AccountController.php`| User auth/profile logic. AJAX/standard POST.                   | **Functional.** Rate limiting applied.                                                           |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                              | **Loads OK. Confirmation Flow Fixed.** Rate limiting applied.                                    |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                           | OK. Webhook session dependency removed. `getStripeClient()` available.                         |
| `controllers/CartController.php`    | Handles cart logic via AJAX.                                     | **Functional.** **Cart storage standardized.** Reliable session count update.                    |
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | **Functional.** **Filtering uses Named Placeholders.** Pagination OK. Admin routing OK.            |
| `controllers/QuizController.php`    | Quiz logic.                                                    | **Functional.** **CSRF fixed. Results logic uses Session.**                                        |
| `controllers/NewsletterController.php`| Newsletter signup/unsubscribe.                               | OK. Rate limiting applied.                                                                       |
| `models/User.php`               | User DB logic (**PDO Prepared Statements**).                     | **Updated & Functional.** `getAddress` implemented. Requires patch. OK.                          |
| `models/Order.php`              | Order DB logic (**PDO Prepared Statements**).                    | OK.                                                                                              |
| `models/Product.php`            | Product DB logic (**PDO Prepared Statements**).                  | **Functional.** **Filtering uses Named Placeholders.** Compatible with controller. OK.          |
| `models/Cart.php`               | DB cart logic (**PDO Prepared Statements**).                     | OK. Used only for logged-in users now.                                                         |
| `models/Quiz.php`               | Quiz DB logic (**PDO Prepared Statements**).                     | **Functional.** **`recommendations` column selected correctly.**                                 |
| `views/layout/header.php`       | Header, nav, assets, outputs global CSRF token.                | OK. Cart count logic simplified.                                                                 |
| `views/account/*.php`           | Account views (Dashboard, Orders, Profile, etc.)                 | **UI Fixed.** Compatible with CSS/JS. OK.                                                        |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                              |
| `views/checkout.php`            | Checkout form view.                                              | **Loads OK.** Uses `$userAddress`. Defensive coding added. AJAX/Stripe OK.                      |
| `views/order_confirmation.php`  | Confirmation view.                                               | **Functional.** Controller logic fixed.                                                        |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token.                                                              |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                    |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Correctly handles CSRF for AJAX. Compatible with controllers.                                |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted.                                                        |
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
*   **Checkout Process:** **Loads correctly.** Address pre-filling works. AJAX OK. Payment Intent OK.
*   **Order Confirmation:** **Functional & Reliable.** Logic fixed.
*   **User Account Pages:** **UI Fixed & Functional.**
*   **Quiz Flow:** **Functional.** CSRF issue fixed. **Results page displays correct products.**

---

## 8. Backend Logic & Core PHP Components

*   **Includes:** Core utilities function as expected.
*   **Controllers:** Logic separated. `BaseController` provides shared functionality.
    *   `AccountController`: Functional. Rate limiting applied.
    *   `CheckoutController`: Loads & Confirmation Flow Fixed. Rate limiting applied.
    *   `PaymentController`: PI creation OK. Webhook confirmation OK.
    *   `CartController`: Functional AJAX. Cart storage standardized.
    *   `ProductController`: **Filtering fixed (named placeholders).** Admin routing OK.
    *   `QuizController`: **CSRF fixed. Results logic fixed (uses session).**
    *   `NewsletterController`: Rate limiting applied.
    *   `BaseController`: Provides helpers. Rate limiting logic OK. `redirect` fixed.
*   **Database Abstraction:** PDO Prepared Statements used throughout Models. **Named placeholders used for filtering.** Secure.
*   **Models:**
    *   `User.php`: Updated & Functional. `getAddress` implemented. Requires patch. OK.
    *   `Order.php`: Compatible. OK.
    *   `Product.php`: **Functional. Filtering uses Named Placeholders.** Compatible with controller. OK.
    *   `Cart.php`: DB cart logic for logged-in users. OK.
    *   `Quiz.php`: **Functional.** `recommendations` column selected correctly. OK.
*   **Security Middleware & Error Handling:** Enforces headers, session rules, CSRF. Error handling global.
*   **Session, Auth, User Flow:** Secure session handling implemented. Auth flows functional.
*   **Payment Processing & Webhook:** Stripe PI flow implemented. Webhook confirmation OK.

---

## 9. Database Design

*(No changes needed to this section, emphasis remains on patch)*

### 9.1 Entity-Relationship Model (Conceptual)
### 9.2 Core Tables (from schema.sql + Updates)
### 9.3 Schema Considerations & Recommendations
### 9.4 Data Flow Examples (Current State)

*   *(Flow descriptions remain the same, but the underlying implementation is now correct)*
*   **Product Filter Click:** Works. Request -> `index.php` -> `ProductController::showProductList` -> Builds named params -> `ProductModel::getFiltered` (uses named params) -> Returns products -> Renders `views/products.php`.
*   **Quiz Submit:** Works. POST -> `index.php` -> `QuizController::processQuiz` -> Validates -> `QuizModel::getRecommendations` -> `QuizModel::saveQuizResult` (stores IDs) -> Stores full recommendations in `$_SESSION` -> Redirects to results page.
*   **Quiz Results Load:** Works. GET -> `index.php` -> `QuizController::showResults` -> Retrieves recommendations from `$_SESSION` -> Renders `views/quiz_results.php`.

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

*   Via DB or future Admin UI.

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
10. **Verify CSRF flow** (inspect views/JS, test POST actions: Quiz, Login, Register, Newsletter, Cart, Profile Update).
11. **Verify Core Functionality:** Add-to-Cart (Guest & Logged-in), Cart View/Update, Product List/Pagination/Filtering (**Verify category filtering works**), Login/Register, Profile Update, Password Reset, Quiz Flow (**Verify submission leads to correct results page with products**). Verify Checkout page loads. **Verify successful payment leads to the Order Confirmation page.** **Verify Account Dashboard UI.**
12. **Verify Rate Limiting:** Trigger limits for actions like Login, Register, Password Reset Request.
13. **Note Remaining Known Issues:** Address Saving, "Headers Already Sent" error.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools, application logs (`logs/`), server logs (`apache_logs/`).
*   Test Checkout page load.
*   Test User Profile/Password flows.
*   **Test the full payment flow through to the Order Confirmation page.**
*   **Test cart behavior when logging in/out (session merge).**
*   **Test Quiz submission and results page redirect.**
*   **Test Product category filtering.**
*   Test Account Dashboard UI.
*   Test rate limits.
*   Enable `ENVIRONMENT = 'development'` in `config.php` for detailed PHP errors during debugging.

---

## 12. Future Enhancements & Recommendations

*(Prioritized List - Critical issues addressed)*

1.  **Implement Address Saving (High Priority):** Add UI and logic in profile page to manage saved addresses. Allow selection/saving during checkout.
2.  **Fix Error Handling ("Headers Already Sent") (Medium Priority):** Make `views/error.php` self-contained or use output buffering more consistently in `ErrorHandler` to prevent this warning.
3.  **Review & Tighten Content Security Policy (Medium Priority):** Update CSP in `config.php`. Avoid `'unsafe-inline'` if possible.
4.  **Review Rate Limiting Coverage (Low Priority):** Ensure `validateRateLimit` is applied to all necessary endpoints beyond the key ones now covered.
5.  **Code Quality & Refactoring (Ongoing/Future):** Composer, Autoloader, Routing Component, Templating Engine, .env, Migrations, Tests.
6.  **Full Admin Panel (Future):** CRUD for Products, Orders, Users, etc. Improve Quiz Analytics methods in `QuizModel`.
7.  **Advanced Features (Future):** Search improvements, user reviews, wishlists.

---

## 13. Appendices

### A. Key File Summaries

*(Updated status notes)*

| File/Folder                     | Purpose                                                          | Status Notes                                                                                     |
| :------------------------------ | :--------------------------------------------------------------- | :----------------------------------------------------------------------------------------------- |
| `index.php`                     | Entry point, routing, core includes, auto POST CSRF validation   | OK. Correct DI. Routing logic functional.                                                        |
| `config.php`                    | DB credentials, App/Security settings, API keys                | OK. CSP/Rate Limit review needed. Secrets exposure.                                              |
| `includes/SecurityMiddleware.php`| Static security helpers (validation, CSRF)                     | OK.                                                                                              |
| `controllers/BaseController.php`| Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)       | OK. `redirect` fixed. Rate limiting logic OK.                                                    |
| `controllers/AccountController.php`| User auth/profile logic. AJAX/standard POST.                   | **Functional.** Rate limiting applied.                                                           |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                              | **Loads OK. Confirmation Flow Fixed.** Rate limiting applied.                                    |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                           | OK. Webhook session dependency removed. `getStripeClient()` available.                         |
| `controllers/CartController.php`    | Handles cart logic via AJAX.                                     | **Functional.** **Cart storage standardized.** Reliable session count update.                    |
| `controllers/ProductController.php`| Product listing/detail/admin.                                  | **Functional.** **Filtering uses Named Placeholders.** Pagination OK. Admin routing OK.            |
| `controllers/QuizController.php`    | Quiz logic.                                                    | **Functional.** **CSRF fixed. Results logic uses Session.**                                        |
| `controllers/NewsletterController.php`| Newsletter signup/unsubscribe.                               | OK. Rate limiting applied.                                                                       |
| `models/User.php`               | User DB logic (**PDO Prepared Statements**).                     | **Updated & Functional.** `getAddress` implemented. Requires patch. OK.                          |
| `models/Order.php`              | Order DB logic (**PDO Prepared Statements**).                    | OK.                                                                                              |
| `models/Product.php`            | Product DB logic (**PDO Prepared Statements**).                  | **Functional.** **Filtering uses Named Placeholders.** Compatible with controller. OK.          |
| `models/Cart.php`               | DB cart logic (**PDO Prepared Statements**).                     | OK. Used only for logged-in users now.                                                         |
| `models/Quiz.php`               | Quiz DB logic (**PDO Prepared Statements**).                     | **Functional.** **`recommendations` column selected correctly.**                                 |
| `views/layout/header.php`       | Header, nav, assets, outputs global CSRF token.                | OK. Cart count logic simplified.                                                                 |
| `views/account/*.php`           | Account views (Dashboard, Orders, Profile, etc.)                 | **UI Fixed.** Compatible with CSS/JS. OK.                                                        |
| `views/layout/footer.php`       | Footer, JS init, global AJAX handlers read CSRF token.         | OK.                                                                                              |
| `views/checkout.php`            | Checkout form view.                                              | **Loads OK.** Uses `$userAddress`. Defensive coding added. AJAX/Stripe OK.                      |
| `views/order_confirmation.php`  | Confirmation view.                                               | **Functional.** Controller logic fixed.                                                        |
| `views/quiz.php`                | Quiz form view.                                                  | OK. Controller passes CSRF token.                                                              |
| `views/quiz_results.php`        | Quiz results view.                                               | OK. Controller passes correct product data.                                                    |
| `js/main.js`                    | Frontend logic, AJAX handlers, CSRF handling via hidden input.   | OK. Correctly handles CSRF for AJAX. Compatible with controllers.                                |
| `includes/ErrorHandler.php`     | Global error handling.                                           | OK. "Headers Already Sent" issue noted.                                                        |
| `db/*`                          | Schema files.                                                    | **Update script is mandatory.**                                                                  |

### B. Glossary

(Standard terms)

### C. Code Snippets and Patterns (CSRF, Implemented Confirmation Flow, Named Placeholders)

**CSRF Token Pattern:** *(Unchanged)*

**Implemented Order Confirmation Flow:** *(Unchanged - flow description is correct)*

**Named Placeholder Pattern (Example from `ProductModel::getFiltered`)**

```php
// Example showing named parameter binding in ProductModel::getFiltered
// SQL construction (simplified)
$sql = "SELECT ... FROM products p WHERE p.category_id = :category_id AND p.price >= :min_price LIMIT :limit OFFSET :offset";
$stmt = $this->pdo->prepare($sql);

// Parameter array (built in controller or model)
$params = [
    ':category_id' => 1,
    ':min_price' => 10.0,
    ':limit' => 12,      // Value will be bound as INT
    ':offset' => 0       // Value will be bound as INT
];

// Binding loop
foreach ($params as $key => $value) {
    $type = PDO::PARAM_STR; // Default
    if ($key === ':limit' || $key === ':offset' || $key === ':category_id') {
         $type = PDO::PARAM_INT;
    } // Add more type checks as needed (float, bool, null)
    $stmt->bindValue($key, $value, $type);
}

// Execute without passing params array again
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
```

*   **Benefit:** Avoids `SQLSTATE[HY093]` errors by using only one placeholder style (named) per query execution. More readable for complex queries.

### D. Mandatory Database Patch

*(Content remains the same - emphasize its importance)*

---
https://drive.google.com/file/d/12RwQ2-fl-jGekxCw1GIaP0fVyIRqL0aT/view?usp=sharing, https://drive.google.com/file/d/15dniiCzQ8TwDd8d0PGfOA_hvF1CYilK7/view?usp=sharing, https://drive.google.com/file/d/17SjET2jWhYUuAfDG4mBBD0NfJ_rOTw15/view?usp=sharing, https://drive.google.com/file/d/18eDCMIfoA9rraVkv_FpmkP2apWbmT6AP/view?usp=sharing, https://drive.google.com/file/d/191gRD6MOMJfMv8lCbVsJ-xpJiBGKfMPF/view?usp=sharing, https://drive.google.com/file/d/1GNpG9mgBIemisQ12H_3po8McVjwQjGBx/view?usp=sharing, https://drive.google.com/file/d/1Mtepal_GgNTlQSY-zYnDEGNBD6e0fXoX/view?usp=sharing, https://drive.google.com/file/d/1cfk5Wj1sC0HxUMLzDExKfKmO9q4884bM/view?usp=sharing, https://drive.google.com/file/d/1d11Ip08ScYnd9mGisaELgq01N3Fs3Pou/view?usp=sharing, https://drive.google.com/file/d/1gm4SE9PDjYEswjvg6OrKnAqPzAFxD2pD/view?usp=sharing, https://drive.google.com/file/d/1jaTMga12DwAewlCLJZ3xrCVUKNvPN7l0/view?usp=sharing, https://drive.google.com/file/d/1mOgo3R6eiz7HBRPJCBTwwf1PoAEoNTTe/view?usp=sharing, https://drive.google.com/file/d/1qhcUpiC0L1W9mbbVCC5RhpGX4FY25KEA/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221rKmt8U0lqA-V1QOi8rnaq1BfD9YvjCRO%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1tQt5eFEXxWEUaHK8OyFDbG7zeEUtTf3C/view?usp=sharing, https://drive.google.com/file/d/1wpcAecbmcf7VFTo_pa03sCQ91FNRPovQ/view?usp=sharing, https://drive.google.com/file/d/1zSjCh4tIUxoEaLsGHg8ePfhdhAVUVSRw/view?usp=sharing
