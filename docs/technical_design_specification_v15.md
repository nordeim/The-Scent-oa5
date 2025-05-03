# The Scent – Technical Design Specification (v12.0)

## Table of Contents

1.  [Introduction](#introduction)
2.  [Project Philosophy & Goals](#project-philosophy--goals)
3.  [System Architecture Overview](#system-architecture-overview)
    *   3.1 [High-Level Workflow](#high-level-workflow)
    *   3.2 [Request-Response Life Cycle (Recommended Confirmation Flow)](#request-response-life-cycle-recommended-confirmation-flow)
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
    *   10.5 [Rate Limiting (Standardization Recommended)](#rate-limiting-standardization-recommended)
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
    *   C. [Code Snippets and Patterns (CSRF, Recommended Confirmation Flow)](#c-code-snippets-and-patterns-csrf-recommended-confirmation-flow)

---

## 1. Introduction

The Scent is a modular, secure, and extensible e-commerce platform focused on delivering premium aromatherapy products. It’s engineered with a custom PHP MVC-inspired architecture without reliance on heavy frameworks, maximizing transparency and developer control. This document (**v12.0**) serves as the updated technical design specification, reflecting the project's current state after incorporating the latest code reviews, fixes, and analysis.

This version documents the **correct implementation of `User::getAddress()`** in `models/User.php`, which successfully resolves the fatal error previously preventing the checkout page (`views/checkout.php`) from loading. The `users` table schema has been updated via the `the_scent_update_users_table.sql` script to support this and other user management features. Consequently, the **Checkout page now loads correctly**, and **User Profile Management** (Name, Email, Password, Newsletter Preferences) and **Password Reset** flows are fully functional via `AccountController.php`, which relies on the updated `User` model.

Core functionalities like Product Listing (with pagination), Add-to-Cart (AJAX), and Cart Management (AJAX with updated UI) remain functional. However, this review identified a **critical flaw in the Order Confirmation flow**, which unreliably depends on session data being set by the asynchronous Stripe webhook. Additionally, **inconsistent cart storage** (Session vs. DB) and **inconsistent rate limiting** usage are noted areas requiring standardization for production readiness.

This document aims to offer deep insight into the system’s structure, logic, and flow, serving as a comprehensive onboarding and reference guide for the current state of the application, including known issues and prioritized recommendations for achieving production quality.

---

## 2. Project Philosophy & Goals

*   **Security First:** Implemented via PDO Prepared Statements, input validation (`SecurityMiddleware`), secure session handling, CSRF protection (Synchronizer Token Pattern, enforced globally on POST), and security headers (CSP needs review).
*   **Simplicity & Maintainability:** Modular structure, clear includes in `index.php`. Consistent coding patterns enforced, especially for CSRF.
*   **Extensibility:** Architecture allows adding new features/pages, requiring manual includes but providing clear extension points. New POST features must follow the CSRF pattern.
*   **Performance:** Direct routing, PDO prepared statements. CDN for frontend libs. Caching (APCu for rate limiting) used but needs standardization.
*   **Modern User Experience:** Responsive design (Tailwind), subtle animations (AOS, Particles), AJAX interactions (Cart, Newsletter, Login/Register). **Core user flows (Auth, Cart, Checkout Load) are functional.**
*   **Transparency:** Explicit routing and includes in `index.php`.
*   **Accessibility & SEO:** Semantic HTML, `aria-label` usage. Basic practices followed.

---

## 3. System Architecture Overview

### 3.1 High-Level Workflow

```mermaid
graph LR
    A[Browser/Client] --> B(Apache2 Server);
    B -- .htaccess rewrite --> C(index.php / Front Controller);
    C -- Initializes --> D{Core Includes};
    D --> E(config.php);
    D --> F(db.php / PDO);
    D --> G(ErrorHandler.php);
    D --> H(SecurityMiddleware.php);
    C -- Validates POST CSRF --> C;
    C -- Dispatches Request --> I(Controllers);
    I -- Uses --> J(Models);
    J -- Interacts via PDO --> K[(MySQL Database)];
    I -- Extends --> L(BaseController);
    L -- Provides --> I;
    I -- Prepares Data --> M{Response};
    M -- Renders View --> N(Views / PHP Templates);
    N -- Includes --> O(Layouts / header.php, footer.php);
    M -- Sends JSON --> A;
    M -- Sends Redirect --> A;
    N -- Sends HTML --> A;

    subgraph Core Includes
        E
        F
        G
        H
    end

    subgraph Controllers
        L
        AccountController
        CartController
        ProductController
        CheckoutController
        PaymentController
        NewsletterController
        QuizController
        CouponController
        InventoryController
        TaxController
    end

    subgraph Models
        UserModel
        ProductModel
        CartModel
        OrderModel
        QuizModel
        CouponModel(Implicit)
    end
```

*   **Key Principles:** Centralized routing (`index.php`), separation of concerns (Controller logic, Model data access, View presentation), secure database interaction (PDO Prepared Statements), global CSRF validation on POST.

### 3.2 Request-Response Life Cycle (Recommended Confirmation Flow)

*(Example: User completes payment and is redirected back)*

1.  **Redirect from Stripe:** User lands on `index.php?page=checkout&action=confirmation&payment_intent=pi_...&payment_intent_client_secret=cs_...`
2.  **Initialization (`/index.php`):** Core files loaded, DB connected, security middleware applied.
3.  **Routing (`/index.php`):** `$page='checkout'`, `$action='confirmation'`. CSRF check skipped (GET request). Routes to `CheckoutController::showOrderConfirmation`.
4.  **Controller Action (`CheckoutController::showOrderConfirmation` - Recommended Logic):**
    *   `requireLogin()` validates user session.
    *   Retrieves `payment_intent` ID (`$pi_id`) from `$_GET`. **Validates input.**
    *   **Crucially, calls Stripe API:** `$paymentIntent = $stripe->paymentIntents->retrieve($pi_id);` (Requires Stripe SDK/Client).
    *   **Verifies PI status:** Checks if `$paymentIntent->status === 'succeeded'`.
    *   **Fetches Order from DB:** `$order = $this->orderModel->getByPaymentIntentId($pi_id);`.
    *   **Validates Order:** Checks if `$order` exists, if `$order['user_id']` matches logged-in user, and if `$order['status']` is appropriate (e.g., 'processing', 'paid').
    *   If all checks pass: Fetches full order details (with items) using `$order['id']`. Renders `views/order_confirmation.php` with order data.
    *   If any check fails: Redirects to account/orders page with an appropriate flash message.
5.  **View Rendering (`views/order_confirmation.php`):** Displays order details passed from the controller.
6.  **Response Transmission:** Server sends HTML page.

---

## 4. Directory & File Structure

### 4.1 Folder Map

```
/ (project root: /cdrom/project/The-Scent-oa5) <-- Apache DocumentRoot
|-- index.php              # Main entry script (routing, core includes, dispatch, POST CSRF validation)
|-- config.php             # Environment, DB, security settings (SECURITY_SETTINGS array)
|-- css/
|   |-- style.css          # Custom CSS rules
|-- images/                # Public image assets (structure assumed, contains products/)
|-- videos/                # Public video assets (e.g., hero.mp4)
|-- particles.json         # Particles.js configuration
|-- .htaccess              # Apache URL rewrite rules & config
|-- includes/              # Shared PHP utility/core files
|   |-- auth.php           # Helpers: isLoggedIn(), isAdmin() (login/register functions deprecated)
|   |-- db.php             # PDO connection setup (makes $pdo available)
|   |-- SecurityMiddleware.php # Security helpers (apply headers/session, validation, CSRF gen/validation)
|   |-- ErrorHandler.php   # Error/exception handling setup (Headers issue noted)
|   |-- EmailService.php   # Email sending logic
|-- controllers/           # Business logic / request handlers
|   |-- BaseController.php # Abstract base with shared helpers (DB, JSON, redirect, validation, CSRF token fetch, Rate Limiting, auth checks, logging, etc.)
|   |-- AccountController.php # User auth, profile (Functional)
|   |-- ProductController.php # Product listing/detail (Pagination OK)
|   |-- CartController.php    # Cart logic, AJAX handlers (Storage inconsistency noted)
|   |-- CheckoutController.php # Checkout process (Loads, Confirmation Flow needs rework)
|   |-- PaymentController.php # Payment Intent creation, Webhook handling (Session issue in webhook)
|   |-- NewsletterController.php # Newsletter subscription
|   |-- QuizController.php    # Quiz logic
|   |-- CouponController.php  # Coupon admin logic
|   |-- InventoryController.php# Stock management logic
|   |-- TaxController.php     # Tax calculation logic
|-- models/                # Data representation / DB interaction (using PDO Prepared Statements)
|   |-- Product.php        # Product data access (Pagination OK)
|   |-- User.php           # User data access (Updated & Functional, getAddress implemented)
|   |-- Order.php          # Order data access (Compatible)
|   |-- Cart.php           # DB Cart logic (Usage inconsistent)
|   |-- Quiz.php           # Quiz data access (Compatible)
|-- views/                 # HTML/PHP templates
|   |-- home.php, products.php, product_detail.php, cart.php, checkout.php, ... # Page views
|   |-- account/             # User account specific views (Functional)
|   |-- admin/             # Admin-specific views (Coupons, Quiz Analytics functional)
|   |-- layout/            # Reusable layout partials (header, footer)
|-- logs/                  # Directory for log files (requires write permissions)
|   |-- security.log
|   |-- error.log
|   |-- audit.log
|-- db/                    # Database schema & patches
|   |-- the_scent_schema.sql.txt # Base schema definition
|   |-- the_scent_update_users_table.sql # REQUIRED patch for 'users' table
|-- js/                    # Custom JavaScript
|   |-- main.js            # Global handlers (AJAX, UI), page initializers
|-- README.md              # Project documentation (v2.0)
|-- technical_design_specification.md # (This document v12.0)
|-- composer.json, composer.lock, vendor/ # Added if Composer is used
|-- LICENSE                # MIT License file (Assumed)
|-- ... (other docs, HTML output files)
```

### 4.2 Key Files Explained

*(Reflecting current state and known issues)*

*   **index.php**: Central router. **Auto POST CSRF validation robust.** Dispatches correctly.
*   **config.php**: Central config. **CSP needs review.** Rate limit config exists but usage inconsistent. Secrets should ideally move to `.env`.
*   **includes/SecurityMiddleware.php**: Provides core security functions (validation, CSRF).
*   **controllers/BaseController.php**: Abstract base. Provides essential helpers. **`validateRateLimit` usage needs standardization.**
*   **controllers/AccountController.php**: Handles user auth/profile. **Functional.** Relies on updated `User` model.
*   **controllers/CheckoutController.php**: Handles checkout. **Loads correctly.** Relies on implemented `User::getAddress`. **`showOrderConfirmation` logic is flawed (session dependency).**
*   **controllers/PaymentController.php**: Handles Stripe PI creation and webhooks. **Webhook handler's reliance on `$_SESSION` for confirmation page is unreliable.**
*   **controllers/CartController.php**: Handles cart logic. AJAX functional. **Cart storage inconsistency (Session/DB) needs resolution.**
*   **models/User.php**: **Updated and Functional.** `getAddress()` implemented. Meets `AccountController` needs. Requires schema patch applied.
*   **models/Order.php**: Order DB logic. Compatible.
*   **models/Product.php**: Product DB logic. Pagination functional.
*   **models/Cart.php**: DB cart logic. Usage inconsistent (see `CartController`).
*   **views/layout/header.php**: Outputs global CSRF token (`#csrf-token-value`). Reflects login state.
*   **views/layout/footer.php**: Includes `main.js`, which reads `#csrf-token-value` for AJAX CSRF.
*   **views/*.php**: Templates. Must output CSRF token correctly. Use `htmlspecialchars()`.
*   **views/checkout.php**: **Loads correctly.** Uses `$userAddress` data. AJAX/Stripe JS functional.
*   **views/cart.php**: Functional with updated UI. Relies on AJAX.
*   **views/order_confirmation.php**: View itself is fine, but the logic to *reach* it (`CheckoutController::showOrderConfirmation`) is flawed.
*   **js/main.js**: Handles AJAX (Add-to-Cart, Cart updates, Login/Register, Newsletter, Checkout Coupon/Tax). **Correctly reads CSRF token from `#csrf-token-value`.** Page initializers based on `body` class.
*   **includes/ErrorHandler.php**: Global error handling. **"Headers Already Sent" issue identified.**
*   **db/the_scent_update_users_table.sql**: **Mandatory patch script** for database schema.

---

## 5. Routing and Application Flow

*(No fundamental changes needed, emphasis on CSRF)*

### 5.1 URL Routing via .htaccess

*   Standard `mod_rewrite` rules route non-file/directory requests to `/index.php`. Verified functional.

### 5.2 index.php: The Application Entry Point

*   Single entry point. Initializes core systems. Determines `$page`, `$action`. **Crucially, validates CSRF token globally for all POST requests before routing (except Stripe webhook).** Dispatches based on `$page`.

### 5.3 Controller Dispatch & Action Flow

*   Controllers included/instantiated within `index.php` switch. Extend `BaseController`.
*   **CSRF Token Flow:** Controllers rendering views needing subsequent CSRF protection *must* fetch the token (`$this->getCsrfToken()`) and pass it to the view data array.
*   Controllers handling AJAX POST expect CSRF token in the request body (validated by `index.php`). Controllers handling standard POST expect CSRF token in `$_POST['csrf_token']` (validated by `index.php`).
*   Rate limiting check (`$this->validateRateLimit()`) **should be applied** consistently in sensitive controller actions.

### 5.4 Views: Templating and Rendering

*   PHP files in `views/` mix HTML and PHP. Use `htmlspecialchars()` for output.
*   **CSRF Token Output:** Views initiating forms/AJAX **must** output the token via `<input type="hidden" id="csrf-token-value" value="...">`. Standard forms *also* need `<input type="hidden" name="csrf_token" value="...">`.
*   JS reads token from `#csrf-token-value`.

---

## 6. Frontend Architecture

*(Emphasis on CSRF handling)*

### 6.1 CSS (css/style.css), Tailwind (CDN), and Other Libraries

*   Styling via Tailwind CDN + custom CSS.
*   Libraries: Google Fonts, Font Awesome 6, AOS.js, Particles.js (CDNs/local).
*   **Mobile Nav CSS:** Fixed by removing `display: none` for `.main-nav`.

### 6.2 Responsive Design and Accessibility

*   Tailwind for responsiveness. Mobile menu functional. Basic accessibility considered.

### 6.3 JavaScript: Interactivity, Libraries, and CSRF Handling

*   **`js/main.js`:** Core script included in footer. Handles global UI (flash messages, mobile menu) and AJAX interactions.
*   **Page Initializers:** Use `body` class to trigger specific JS setup (e.g., `initCartPage`, `initCheckoutPage`).
*   **AJAX CSRF:** All AJAX POST requests (Add-to-Cart, Cart Update/Remove, Login, Register, Newsletter, Checkout Coupon/Tax) correctly read the CSRF token from the global `#csrf-token-value` hidden input and include it in the request body/payload. This aligns with the server-side validation in `index.php`.

---

## 7. Key Pages & Components

*(Updated status)*

*   **Home/Landing Page:** Functional. Add-to-Cart works.
*   **Header/Navigation:** Functional. Mobile menu fixed. CSRF token output correct.
*   **Footer/Newsletter:** Functional. AJAX newsletter signup works.
*   **Product Grid/Cards:** Functional. Add-to-Cart works.
*   **Shopping Cart:** Functional with updated UI. AJAX updates/removals work. **Cart storage inconsistency noted.**
*   **Product Detail Page:** Functional. AJAX Add-to-Cart works.
*   **Products Page:** Functional. Filters/Sorting/Pagination work. Add-to-Cart works.
*   **Checkout Process:** **Loads correctly.** Address pre-filling works (uses `users` table fields). AJAX for Tax/Coupon functional. Payment Intent creation functional. **Confirmation flow after payment redirect is flawed.**
*   **Order Confirmation:** View exists. Logic to display it (`CheckoutController::showOrderConfirmation`) is **unreliable due to session dependency.** Needs rework (See Recommendation 2).
*   **User Account Pages:** Functional (Dashboard, Orders List/Details, Profile View/Update).
*   **Quiz Flow:** Functional.

---

## 8. Backend Logic & Core PHP Components

*(Updated status/notes)*

*   **Includes:** Core utilities function as expected. `auth.php` login/register likely deprecated.
*   **Controllers:** Logic separated. `BaseController` provides shared functionality.
    *   `AccountController`: Functional post-refactor and `User` model update.
    *   `CheckoutController`: Loads, AJAX functional. Confirmation flow needs rework.
    *   `PaymentController`: PI creation OK. Webhook session logic flawed.
    *   `CartController`: Functional AJAX. Cart storage inconsistency.
    *   Rate limiting usage inconsistent.
*   **Database Abstraction:** PDO Prepared Statements used throughout Models. **Secure.**
*   **Models:**
    *   `User.php`: **Updated & Functional.** `getAddress` implemented.
    *   Others (`Product`, `Order`, `Cart`, `Quiz`) compatible with controllers.
*   **Security Middleware & Error Handling:** `SecurityMiddleware` enforces headers, session rules, CSRF. `ErrorHandler` handles errors globally, but "Headers Already Sent" issue exists.
*   **Session, Auth, User Flow:** Secure session handling implemented. Auth flows functional.
*   **Payment Processing & Webhook:** Stripe PI flow implemented. **Webhook's session usage for confirmation is the main flaw.**

---

## 9. Database Design

### 9.1 Entity-Relationship Model (Conceptual)

*(No changes needed)*

Standard e-commerce relationships.

### 9.2 Core Tables (from schema.sql + Updates)

*   Schema defined in `the_scent_schema.sql.txt`.
*   **Mandatory Update:** `the_scent_update_users_table.sql` must be applied to add necessary columns to the `users` table (status, newsletter, reset tokens, timestamps, address fields).

### 9.3 Schema Considerations & Recommendations

*   **Addresses:** Currently in `users` table. Functional, but separate table recommended for scalability.
*   **Cart:** `cart_items` table exists but inconsistently used vs. Session. **Recommendation:** Standardize usage.

### 9.4 Data Flow Examples (Current State)

*   **Add to Cart:** Works via AJAX, updates session/DB depending on login.
*   **Checkout Page Load:** Works. Fetches cart, user address, renders form.
*   **Checkout Submit:** Works. Server validates, creates order/PI, returns clientSecret.
*   **Payment Success -> Redirect:** Works. Stripe redirects to confirmation URL.
*   **Confirmation Page Load:** **Fails intermittently/unreliably** because it depends on `$_SESSION['last_order_id']` which the webhook often cannot set correctly.

---

## 10. Security Considerations & Best Practices

*   **Input Sanitization & Validation:** **Implemented** via `SecurityMiddleware`.
*   **Session Management:** **Implemented** (Secure flags, regeneration, integrity checks).
*   **CSRF Protection:** **Implemented** (Synchronizer Token Pattern, global POST validation).
*   **Security Headers & CSP:** **Implemented.** CSP needs review/tightening.
*   **Rate Limiting:** **Partially Implemented.** Mechanism exists (`BaseController`), but usage needs standardization. Relies on APCu.
*   **File Uploads & Permissions:** Validation logic exists. Secure handling needed if used. `logs/` needs correct permissions.
*   **Audit Logging & Error Handling:** **Implemented.** Error handling has "Headers Already Sent" issue.
*   **SQL Injection Prevention:** **Implemented** via PDO Prepared Statements.
*   **Payment Security:** Handled via Stripe Elements (PCI compliance offloaded). Webhook signature verification **implemented**.

---

## 11. Extensibility & Onboarding

### 11.1 Adding Features, Pages, or Controllers

*   Follow pattern: Controller -> View -> `index.php` route. Implement CSRF token pattern for POST. Extend `BaseController`.

### 11.2 Adding Products, Categories, and Quiz Questions

*   Via DB or future Admin UI.

### 11.3 Developer Onboarding Checklist

1.  Setup LAMP/LEMP, enable `mod_rewrite`.
2.  Clone repo.
3.  Setup DB, import *base* schema (`the_scent_schema.sql.txt`).
4.  **Apply DB schema updates:** Execute `the_scent_update_users_table.sql`.
5.  Configure `config.php`.
6.  Set file permissions (`logs/` writable).
7.  Configure Apache VirtualHost.
8.  Browse site, check logs.
9.  **Verify CSRF flow** (inspect views/JS, test POST actions).
10. **Verify Core Functionality:** Add-to-Cart, Cart View/Update, Product List/Pagination, Login/Register, Profile Update, Password Reset. **Verify Checkout page loads**.
11. **Note Known Issues:** Understand the flawed confirmation flow, cart inconsistency, rate limiting gaps.

### 11.4 Testing & Debugging Notes

*   Use browser dev tools, application logs, server logs.
*   Test Checkout page load specifically.
*   Test User Profile/Password flows.
*   Observe behavior of Order Confirmation page (likely fails or redirects).
*   Test cart behavior when logging in/out.
*   Manually trigger rate limits if testing that feature.

---

## 12. Future Enhancements & Recommendations

*(Prioritized List)*

1.  **Rework Order Confirmation Flow (Critical Priority):** Remove session dependency. Use URL parameters (`payment_intent` ID) from Stripe redirect and verify status/ownership server-side before displaying confirmation. (See Appendix C for recommended logic).
2.  **Standardize Cart Storage (High Priority):** Choose DB-only (for logged-in) or Session-only (until checkout) and enforce consistently in `CartController`. DB-only is generally preferred for logged-in persistence.
3.  **Standardize Rate Limiting (Medium Priority):** Apply `BaseController::validateRateLimit()` consistently to sensitive controller actions (Login, Register, Password Reset, Checkout Submit, Coupon Apply). Ensure APCu reliability or implement fallback.
4.  **Review & Tighten Content Security Policy (Medium Priority):** Update CSP in `config.php` based on actual needs (Stripe, fonts, etc.). Avoid `'unsafe-inline'` if possible.
5.  **Fix Error Handling ("Headers Already Sent") (Medium Priority):** Make `views/error.php` self-contained or use output buffering in `ErrorHandler`.
6.  **Implement `User::getAddress` Fully (Medium Priority):** Although the method exists and prevents errors, implement the logic to actually *store* address info when user profiles are updated or during checkout if saving addresses is desired. Update `views/checkout.php` to use this stored data if available.
7.  **Code Quality & Refactoring (Ongoing/Future):**
    *   Implement Composer for autoloading and managing dependencies (PHPMailer, Stripe SDK).
    *   Refactor `index.php` routing to a dedicated Router class.
    *   Consider a simple templating engine (e.g., Twig, Plates) for cleaner views.
    *   Use `.env` files for configuration/secrets.
    *   Implement database migrations.
    *   Add unit/integration tests.
8.  **Full Admin Panel (Future):** Develop CRUD interfaces for Products, Orders, Users, etc.
9.  **Advanced Features (Future):** Search improvements, user reviews, wishlists, etc.

---

## 13. Appendices

### A. Key File Summaries

| File/Folder                 | Purpose                                                        | Status Notes                                                                                                   |
| :-------------------------- | :------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------- |
| `index.php`                 | Entry point, routing, core includes, auto POST CSRF validation | OK                                                                                                             |
| `config.php`                | DB credentials, App/Security settings, API keys              | OK. CSP/Rate Limit review needed. Secrets exposure.                                                          |
| `includes/SecurityMiddleware.php` | Static security helpers (validation, CSRF)                   | OK                                                                                                             |
| `controllers/BaseController.php` | Abstract base helpers (DB, JSON, auth, CSRF, Rate Limit)     | OK. Rate limiting usage inconsistent.                                                                        |
| `controllers/AccountController.php` | User auth/profile logic. AJAX/standard POST.                 | **Functional.**                                                                                              |
| `controllers/CheckoutController.php`| Handles checkout. AJAX interaction.                            | **Loads.** Confirmation flow logic flawed.                                                                 |
| `controllers/PaymentController.php` | Stripe PI creation, Webhook handling                         | OK, but Webhook session dependency flawed.                                                                   |
| `controllers/CartController.php`| Handles cart logic via AJAX.                                   | Functional. **Cart storage inconsistent.**                                                                 |
| `models/User.php`           | User DB logic (**PDO Prepared Statements**).                   | **Updated & Functional.** `getAddress` implemented. Requires schema patch.                                   |
| `models/Order.php`          | Order DB logic (**PDO Prepared Statements**).                  | OK.                                                                                                            |
| `models/Product.php`        | Product DB logic (**PDO Prepared Statements**).                | OK. Pagination functional.                                                                                   |
| `models/Cart.php`           | DB cart logic (**PDO Prepared Statements**).                   | OK. Usage inconsistent.                                                                                      |
| `views/layout/header.php`   | Header, nav, assets, outputs global CSRF token.              | OK.                                                                                                            |
| `views/*.php`               | HTML/PHP templates, must output CSRF token correctly.          | OK. Requires correct CSRF token output.                                                                      |
| `views/layout/footer.php`   | Footer, JS init, global AJAX handlers read CSRF token.       | OK.                                                                                                            |
| `views/checkout.php`        | Checkout form view.                                            | **Loads.** Uses `$userAddress`. Needs working confirmation flow post-payment.                                  |
| `views/cart.php`            | Cart view.                                                     | Functional, updated UI.                                                                                      |
| `views/order_confirmation.php`| Confirmation view.                                             | View OK, logic to display it is flawed.                                                                    |
| `js/main.js`                | Frontend logic, AJAX handlers, CSRF handling via hidden input. | OK. Correctly handles CSRF for AJAX.                                                                         |
| `includes/ErrorHandler.php` | Global error handling.                                         | OK. "Headers Already Sent" issue noted.                                                                      |
| `db/*`                      | Schema files.                                                  | **Update script is mandatory.**                                                                              |

### B. Glossary

(Standard terms: MVC, CSRF, XSS, SQLi, PDO, AJAX, CDN, CSP, Rate Limiting, Prepared Statements, Synchronizer Token Pattern, APCu, Payment Intent (PI), Webhook, Idempotency)

### C. Code Snippets and Patterns (CSRF, Recommended Confirmation Flow)

#### 1. Correct & Required CSRF Token Implementation Pattern

*   **Controller (Rendering View):**
    ```php
    // Inside controller action method
    $csrfToken = $this->getCsrfToken();
    echo $this->renderView('your_view', ['csrfToken' => $csrfToken, /* ... other data ... */]);
    ```
*   **View (`your_view.php`):**
    ```html
    <!-- MUST be present for JS AJAX -->
    <input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <!-- Include in standard forms -->
    <form method="POST" action="...">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <!-- other form fields -->
        <button type="submit">Submit</button>
    </form>
    ```
*   **JavaScript (`main.js` - AJAX Example):**
    ```javascript
    // Inside an AJAX function
    const csrfToken = document.getElementById('csrf-token-value')?.value;
    if (!csrfToken) { /* handle error */ }

    fetch('...', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, // or application/json
        body: `csrf_token=${encodeURIComponent(csrfToken)}&other_param=value` // Include token
    })
    // ... rest of fetch logic
    ```
*   **Server (`index.php`):** (Handles validation automatically for POST before routing)
    ```php
    // ... near top of index.php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::generateCSRFToken(); // Ensure token exists in session if first POST
        SecurityMiddleware::validateCSRF(); // Validates against $_POST['csrf_token']
    }
    ```

#### 2. Recommended Order Confirmation Flow (`CheckoutController::showOrderConfirmation`)

*   **Problem:** Current flow relies on `$_SESSION['last_order_id']` set by the webhook, which is unreliable.
*   **Solution:** Use Payment Intent ID from Stripe's redirect URL, verify status and ownership server-side.

```php
    // controllers/CheckoutController.php

    /**
     * Displays the order confirmation page. (RECOMMENDED IMPLEMENTATION)
     * Verifies payment success using Stripe Payment Intent ID from URL.
     */
    public function showOrderConfirmation() {
         $this->requireLogin(); // Ensure user is logged in
         $userId = $this->getUserId();

         // 1. Get Payment Intent ID from URL
         $paymentIntentId = $this->validateInput($_GET['payment_intent'] ?? null, 'string'); // Example: pi_...
         // $clientSecret = $this->validateInput($_GET['payment_intent_client_secret'] ?? null, 'string'); // Might also be present

         if (!$paymentIntentId) {
             $this->setFlashMessage('Invalid confirmation link.', 'error');
             $this->redirect('index.php?page=account&section=orders');
             return;
         }

         try {
             // 2. Retrieve Payment Intent from Stripe (Requires StripeClient instance)
             // Assuming $this->paymentController holds the PaymentController instance
             if (!isset($this->paymentController) || !$this->paymentController->getStripeClient()) { // Add a getter in PaymentController
                  error_log("Stripe client not available in CheckoutController.");
                  throw new Exception("Payment verification service unavailable.");
             }
             $stripeClient = $this->paymentController->getStripeClient(); // Add getStripeClient() to PaymentController
             $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId, []);

             // 3. Verify Payment Intent Status
             if ($paymentIntent->status !== 'succeeded') {
                  error_log("Confirmation page accessed for non-succeeded PI: {$paymentIntentId}, Status: {$paymentIntent->status}");
                  $this->setFlashMessage('Payment confirmation is pending or failed. Please check your orders.', 'warning');
                  $this->redirect('index.php?page=account&section=orders');
                  return;
             }

             // 4. Fetch Corresponding Order from DB using PI ID
             // OrderModel needs getByPaymentIntentId method (already exists)
             $order = $this->orderModel->getByPaymentIntentId($paymentIntentId);

             // 5. Validate Order Ownership and Existence
             if (!$order || $order['user_id'] !== $userId) {
                  error_log("Order not found or user mismatch for PI: {$paymentIntentId}, Order ID: " . ($order['id'] ?? 'N/A') . ", User ID: {$userId}");
                  $this->setFlashMessage('Order details not found or access denied.', 'error');
                  $this->redirect('index.php?page=account&section=orders');
                  return;
             }

             // 6. Fetch full order details (with items) using the verified Order ID
             $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $userId); // Fetches items
             if (!$fullOrder || empty($fullOrder['items'])) {
                  error_log("Could not fetch full order details for confirmed order ID: {$order['id']}");
                  $this->setFlashMessage('Could not display full order details.', 'error');
                  $this->redirect('index.php?page=account&section=orders');
                  return;
             }

             // 7. Render Confirmation View
             $csrfToken = $this->getCsrfToken();
             $bodyClass = 'page-order-confirmation';
             $pageTitle = 'Order Confirmation - The Scent';

             echo $this->renderView('order_confirmation', [
                 'order' => $fullOrder, // Pass the verified and complete order data
                 'csrfToken' => $csrfToken,
                 'bodyClass' => $bodyClass,
                 'pageTitle' => $pageTitle
             ]);

         } catch (\Stripe\Exception\ApiErrorException $e) {
             error_log("Stripe API error fetching Payment Intent {$paymentIntentId}: " . $e->getMessage());
             $this->setFlashMessage('Error verifying payment status.', 'error');
             $this->redirect('index.php?page=account&section=orders');
         } catch (Exception $e) {
             error_log("Error showing order confirmation for PI {$paymentIntentId}: " . $e->getMessage());
             $this->setFlashMessage('An unexpected error occurred.', 'error');
             $this->redirect('index.php?page=account&section=orders');
         }
     }
```

*(Note: This recommended controller logic requires adding a method like `getStripeClient()` to `PaymentController` to allow `CheckoutController` access to the initialized Stripe client, or passing the client instance during `CheckoutController`'s construction).*

