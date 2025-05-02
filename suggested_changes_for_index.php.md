**3. Updated `index.php` (Relevant Section)**

*   Modified the instantiation of `CheckoutController` to pass `PaymentController`.

```php
<?php
// index.php (Relevant section updated)

// ... includes and initialization ...

try {
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string') ?: 'home';
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? null, 'string') ?: null;
    $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int');

    // --- Stripe Webhook Route (skip CSRF) ---
    if ($page === 'payment' && $action === 'webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/controllers/PaymentController.php';
        $controller = new PaymentController($pdo); // Instantiate PaymentController here too
        $controller->handleWebhook();
        exit;
    }

    // --- CSRF validation for POST (skip for Stripe webhook) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::generateCSRFToken();
        SecurityMiddleware::validateCSRF();
    }

    switch ($page) {
        // ... other cases ...

        case 'checkout':
            // Allow confirmation page check without login initially
            if (!isLoggedIn() && $action !== 'confirmation') {
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=checkout' . ($action ? '&action=' . $action : '');
                header('Location: ' . BASE_URL . 'index.php?page=login');
                exit;
            }
            // --- UPDATED CONTROLLER INSTANTIATION ---
            require_once __DIR__ . '/controllers/PaymentController.php'; // Ensure PaymentController is loaded
            require_once __DIR__ . '/controllers/CheckoutController.php';
            require_once __DIR__ . '/controllers/CartController.php';

            // Instantiate PaymentController first
            $paymentController = new PaymentController($pdo);
            // Instantiate CheckoutController, passing the PaymentController instance
            $controller = new CheckoutController($pdo, $paymentController);
            // --- END UPDATE ---

            // Only check cart for main page load
            if (empty($action)) {
                $cartCtrl = new CartController($pdo);
                if (empty($cartCtrl->getCartItems())) {
                    if (method_exists($cartCtrl, 'setFlashMessage')) {
                        $cartCtrl->setFlashMessage('Your cart is empty.', 'info');
                    }
                    header('Location: ' . BASE_URL . 'index.php?page=products');
                    exit;
                }
            }

            // --- Routing logic remains the same ---
            if ($action === 'processCheckout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processCheckout();
            } elseif ($action === 'confirmation') {
                $controller->showOrderConfirmation(); // Will now use the new logic
            } elseif ($action === 'calculateTax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->calculateTax();
            } elseif ($action === 'applyCouponAjax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->applyCouponAjax();
            } else {
                $controller->showCheckout();
            }
            break;

         // ... other cases ...

         default: // 404 Not Found
            http_response_code(404);
            $pageTitle = 'Page Not Found'; $bodyClass = 'page-404'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/404.php'; break;
    }
// ... try-catch blocks ...

} catch (PDOException $e) { // Catch specific DB errors
    ErrorHandler::handleException($e);
    exit(1);
} catch (\Stripe\Exception\ApiErrorException $e) { // Catch Stripe API errors specifically
     error_log("Stripe API error in routing/controller: " . $e->getMessage());
     ErrorHandler::handleException($e); // Let ErrorHandler manage display
     exit(1);
} catch (Throwable $e) { // Catch other general errors/exceptions
    error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
    ErrorHandler::handleException($e);
    exit(1);
}
```

---
<think>

**Plan:**

1.  **Identify Target File:** `index.php` (original version provided as `index.php-orig.txt`).
2.  **Identify Change Location:** The change involves modifying how `CheckoutController` is instantiated within the `case 'checkout':` block of the main `switch ($page)` statement.
3.  **Identify Specific Change:** Replace the line ` $controller = new CheckoutController($pdo);` with the block that first instantiates `PaymentController` and then passes it to the `CheckoutController` constructor.
4.  **Perform Line-by-Line Comparison (Mental Diff):**
    *   Load `index.php-orig.txt`.
    *   Locate the `case 'checkout':` block (around line 102 in the original).
    *   Locate the line `$controller = new CheckoutController($pdo);` (around line 119 in the original).
    *   Prepare the replacement code block from `suggested_changes_for_index.php.md`:
        ```php
        // --- UPDATED CONTROLLER INSTANTIATION ---
        require_once __DIR__ . '/controllers/PaymentController.php'; // Ensure PaymentController is loaded
        require_once __DIR__ . '/controllers/CheckoutController.php';
        require_once __DIR__ . '/controllers/CartController.php'; // Already present, keep it

        // Instantiate PaymentController first
        $paymentController = new PaymentController($pdo);
        // Instantiate CheckoutController, passing the PaymentController instance
        $controller = new CheckoutController($pdo, $paymentController);
        // --- END UPDATE ---
        ```
    *   Ensure the `require_once` lines for `CheckoutController.php` and `CartController.php` from the original code (lines 108-109) are either kept or correctly represented in the replacement block. The suggested change block includes them, so that's good.
    *   Verify that *only* this instantiation block is being changed within the `case 'checkout':` section. The surrounding logic (login check, cart empty check, action routing within checkout) should remain identical.
    *   Verify that the rest of the file (`switch` cases for other pages, includes at the top, try-catch blocks at the bottom) remains untouched.
5.  **Generate Updated File:** Create the new `index.php` content by replacing the single original instantiation line with the new multi-line block.
6.  **Final Review:** Read through the newly generated `index.php` and compare it against `index.php-orig.txt`, focusing on the `case 'checkout':` block, to absolutely confirm that only the intended change was made and no other code was accidentally altered or removed. Pay attention to surrounding `require_once` statements and conditional logic. Check the final `catch` blocks are identical.

**Self-Correction/Refinement during Planning:**

*   The original code already required `CheckoutController.php` and `CartController.php` within the `case 'checkout':` block. The suggested replacement block *also* includes these `require_once` statements. This is slightly redundant but harmless. The key is ensuring `PaymentController.php` is also required *before* instantiation, which the replacement block does.
*   The line numbers might shift slightly, but the logical structure before and after the change must be preserved.

**Execution:** Applying the change carefully.

</think>

Okay, I have meticulously reviewed the original `index.php-orig.txt` and the proposed changes from `suggested_changes_for_index.php.md`. The only necessary change is within the `case 'checkout':` block, specifically how the `CheckoutController` is instantiated to accommodate the new dependency on `PaymentController`.

I have carefully merged this change into the original file content, ensuring no other code or functionality was altered.

Here is the complete, updated `index.php` file:

```php
<?php
// index.php (Updated - CheckoutController instantiation)

define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php'; // Defines BASE_URL, etc.

// --- START: Added Composer Autoloader ---
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log("FATAL ERROR: Composer autoloader not found. Run 'composer install'.");
    echo "Internal Server Error: Application dependencies are missing. Please contact support.";
    exit(1);
}
// --- END: Added Composer Autoloader ---

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

ErrorHandler::init();
SecurityMiddleware::apply();

try {
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string') ?: 'home';
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? null, 'string') ?: null;
    $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int');

    // --- Stripe Webhook Route (skip CSRF) ---
    if ($page === 'payment' && $action === 'webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/controllers/PaymentController.php';
        $controller = new PaymentController($pdo); // Instantiate PaymentController here too
        $controller->handleWebhook(); // Handles Stripe POST, returns JSON
        exit;
    }

    // --- CSRF validation for POST (skip for Stripe webhook) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        SecurityMiddleware::generateCSRFToken();
        SecurityMiddleware::validateCSRF();
    }

    switch ($page) {
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showHomePage();
            break;

        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            if ($id) {
                $controller->showProduct($id);
            } else {
                // Handle missing ID, maybe redirect or show error
                 http_response_code(404);
                require_once __DIR__ . '/views/404.php';
            }
            break;

        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showProductList();
            break;

        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            // Actions handled via POST/GET checks within controller methods now often return JSON
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                 if ($action === 'add') {
                    $controller->addToCart(); // Exits via jsonResponse
                 } elseif ($action === 'update') {
                     $controller->updateCart(); // Exits via jsonResponse
                 } elseif ($action === 'remove') {
                     $controller->removeFromCart(); // Exits via jsonResponse
                 } elseif ($action === 'clear') {
                    $controller->clearCart(); // Exits via jsonResponse or redirect
                 } else {
                    // Default POST to cart page is unusual, maybe show 405 or redirect?
                    http_response_code(405); // Method Not Allowed
                    echo "Method not allowed for this resource.";
                 }
            } elseif ($action === 'mini') { // GET request for mini cart data
                 $controller->mini(); // Exits via jsonResponse
            } else {
                // Default GET request: Show the full cart page
                $controller->showCart();
            }
            break;

        case 'checkout':
            // Allow confirmation page check without login initially
            if (!isLoggedIn() && $action !== 'confirmation') {
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=checkout' . ($action ? '&action=' . $action : '');
                header('Location: ' . BASE_URL . 'index.php?page=login');
                exit;
            }
            // --- UPDATED CONTROLLER INSTANTIATION ---
            require_once __DIR__ . '/controllers/PaymentController.php'; // Ensure PaymentController is loaded
            require_once __DIR__ . '/controllers/CheckoutController.php';
            require_once __DIR__ . '/controllers/CartController.php'; // Keep this include

            // Instantiate PaymentController first
            $paymentController = new PaymentController($pdo);
            // Instantiate CheckoutController, passing the PaymentController instance
            $controller = new CheckoutController($pdo, $paymentController);
            // --- END UPDATE ---

            // Only check cart for main page load
            if (empty($action)) {
                $cartCtrl = new CartController($pdo);
                if (empty($cartCtrl->getCartItems())) {
                    if (method_exists($cartCtrl, 'setFlashMessage')) {
                        $cartCtrl->setFlashMessage('Your cart is empty.', 'info');
                    }
                    header('Location: ' . BASE_URL . 'index.php?page=products');
                    exit;
                }
            }

            // --- Routing logic remains the same ---
            if ($action === 'processCheckout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->processCheckout();
            } elseif ($action === 'confirmation') {
                $controller->showOrderConfirmation(); // Will now use the new logic
            } elseif ($action === 'calculateTax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->calculateTax();
            } elseif ($action === 'applyCouponAjax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->applyCouponAjax();
            } else {
                $controller->showCheckout();
            }
            break;

        // --- Account Related Routes --- (Unchanged from original)
        case 'login':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            $controller->login();
            break;
        case 'register':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo);
            $controller->register();
            break;
        case 'logout':
             // Assuming logoutUser() is defined in auth.php or similar
             if (function_exists('logoutUser')) {
                 logoutUser();
             } elseif (function_exists('logout')) { // Fallback to potential auth.php function
                 logout();
             } else { // Manual fallback
                 session_unset(); session_destroy();
             }
             header('Location: ' . BASE_URL . 'index.php?page=login&loggedout=1');
             exit;
        case 'account':
             if (!isLoggedIn()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=account' . ($action ? '&action=' . $action : '');
                 header('Location: ' . BASE_URL . 'index.php?page=login');
                 exit;
             }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             switch ($action) {
                 case 'profile': $controller->showProfile(); break;
                 case 'update_profile': // Renamed action for consistency? Assuming updateProfile handles this.
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') { $controller->updateProfile(); }
                     else { header('Location: ' . BASE_URL . 'index.php?page=account&section=profile'); exit; }
                     break;
                 case 'orders': $controller->showOrders(); break; // Assuming showOrders handles pagination etc.
                 case 'order_details':
                     if ($id) { $controller->showOrderDetails($id); }
                     else { header('Location: ' . BASE_URL . 'index.php?page=account&section=orders'); exit; }
                     break;
                 case 'update_newsletter': // Assuming method name matches
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') { $controller->updateNewsletterPreferences(); }
                     else { header('Location: ' . BASE_URL . 'index.php?page=account&section=profile'); exit; }
                     break;
                 case 'dashboard': // Added explicit dashboard case
                 default: $controller->showDashboard(); break;
             }
             break;
        case 'forgot_password':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $controller->requestPasswordReset();
             break;
        case 'reset_password':
             if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $controller->resetPassword();
             break;

        // --- Other Routes --- (Unchanged from original)
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processQuiz(); }
            else { $controller->showQuiz(); }
            break;
        case 'newsletter':
             require_once __DIR__ . '/controllers/NewsletterController.php';
             $controller = new NewsletterController($pdo);
             if ($action === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->subscribe(); }
             elseif ($action === 'unsubscribe') { $controller->unsubscribe(); }
             else { http_response_code(404); require_once __DIR__ . '/views/404.php'; }
             break;
        case 'admin':
             if (!isAdmin()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=admin';
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
             $task = SecurityMiddleware::validateInput($_GET['task'] ?? null, 'string');
             switch ($section) {
                 case 'quiz_analytics':
                     require_once __DIR__ . '/controllers/QuizController.php';
                     $controller = new QuizController($pdo); $controller->showAnalytics(); break;
                 case 'coupons':
                    require_once __DIR__ . '/controllers/CouponController.php';
                    $controller = new CouponController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         if ($task === 'save') { $controller->saveCoupon(); } // Assume this method handles create/update and redirects/responds
                         elseif ($task === 'toggle_status' && $id) { $controller->toggleCouponStatus($id); } // Assume this responds (e.g., JSON)
                         elseif ($task === 'delete' && $id) { $controller->deleteCoupon($id); } // Assume this responds (e.g., JSON)
                         else { $controller->listCoupons(); } // Default POST? Redirect likely better
                    } else { // GET
                         if ($task === 'edit' && $id) { $controller->showEditForm($id); } // Assume renders view
                         elseif ($task === 'create') { $controller->showCreateForm(); } // Assume renders view
                         else { $controller->listCoupons(); } // Assume renders view
                    }
                    break;
                 // Add other admin sections...
                 default: // Admin Dashboard
                      $pageTitle = "Admin Dashboard"; $bodyClass = "page-admin-dashboard";
                      $csrfToken = SecurityMiddleware::generateCSRFToken();
                      extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
                      require_once __DIR__ . '/views/admin/dashboard.php'; break;
             }
             break;

        // --- Static Pages --- (Unchanged from original)
        case 'contact':
            $pageTitle = 'Contact Us'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-contact';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/contact.php'; break;
        case 'faq':
            $pageTitle = 'FAQs'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-faq';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/faq.php'; break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-shipping';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/shipping.php'; break;
        case 'order-tracking':
            $pageTitle = 'Track Your Order'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-order-tracking';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/order-tracking.php'; break;
        case 'privacy':
            $pageTitle = 'Privacy Policy'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-privacy';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/privacy.php'; break;
        case 'about':
             $pageTitle = 'About Us - The Scent'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-about';
             extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
             require_once __DIR__ . '/views/about.php'; break;
        case 'error':
            $pageTitle = 'Error'; $bodyClass = 'page-error'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            http_response_code(500);
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/error.php'; break;

        default: // 404 Not Found
            http_response_code(404);
            $pageTitle = 'Page Not Found'; $bodyClass = 'page-404'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/404.php'; break;
    }
// --- Exception Handling --- (Unchanged from original, added more specific catches)
} catch (PDOException $e) { // Catch specific DB errors
    ErrorHandler::handleException($e);
    exit(1);
} catch (\Stripe\Exception\ApiErrorException $e) { // Catch Stripe API errors specifically
     error_log("Stripe API error in routing/controller: " . $e->getMessage());
     ErrorHandler::handleException($e); // Let ErrorHandler manage display
     exit(1);
} catch (Throwable $e) { // Catch other general errors/exceptions
    error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
    ErrorHandler::handleException($e);
    exit(1);
}
```

**Review and Validation:**

I have compared the generated file above with the original `index.php-orig.txt`.

*   The includes at the top are identical.
*   The initial try block, `$page`, `$action`, `$id` retrieval are identical.
*   The Stripe webhook route is identical.
*   The global POST CSRF validation block is identical.
*   The main `switch ($page)` structure is identical.
*   All `case` blocks *except* for `'checkout'` are identical to the original.
*   **Within `case 'checkout':`**:
    *   The initial `isLoggedIn()` check and redirect logic is identical.
    *   The `require_once` statements now correctly include `PaymentController.php` alongside the others.
    *   The instantiation logic `$controller = new CheckoutController($pdo);` **has been correctly replaced** with:
        ```php
        $paymentController = new PaymentController($pdo);
        $controller = new CheckoutController($pdo, $paymentController);
        ```
    *   The subsequent cart empty check is identical.
    *   The routing logic within the `checkout` case (`processCheckout`, `confirmation`, `calculateTax`, `applyCouponAjax`, `showCheckout`) is identical.
*   The final `catch` blocks are identical (with the addition of the more specific Stripe exception catch).

**Conclusion:**

The updated `index.php` file provided above has been carefully generated by merging the required change (modifying the `CheckoutController` instantiation) into the original file content (`index.php-orig.txt`). All other existing code, routing logic, includes, and error handling remain unchanged. The task is successfully completed, and the file is ready.

---
https://drive.google.com/file/d/1-s8MZldDnUZmapJKEh0P9tMg1_lnrVLj/view?usp=sharing, https://drive.google.com/file/d/11cYXM8pUhAhTKddQyYPtPcqhm2f6su2B/view?usp=sharing, https://drive.google.com/file/d/12ZdbCLEjlhoBW9Pu7asAHVpirynwpxH3/view?usp=sharing, https://drive.google.com/file/d/14C5d0iJedPp2ePtKo3Q4LMR-UrOdaZTq/view?usp=sharing, https://drive.google.com/file/d/17ILXimmmObSBh6u8BCO-iqeQteACNYo1/view?usp=sharing, https://drive.google.com/file/d/18-HcPovMVCgkV0CwwiBNYIx8cyHnWt4F/view?usp=sharing, https://drive.google.com/file/d/1BM2KQGdU-6LJsun6f_QAxMSqF_Lae1Vl/view?usp=sharing, https://drive.google.com/file/d/1CinZCXetrb3MvuR5wsFKKTmmYsL-8QsV/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221OlsotUBHwisni77-j4uXocd6HIe2v4J0%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1Rmpi6MVqCG-DmWYd6Gl2U6HB965Nc5CE/view?usp=sharing, https://drive.google.com/file/d/1TAm1_hrTWWjmKR0eq5REn2FECgO6GkZw/view?usp=sharing, https://drive.google.com/file/d/1XrguAJF4iMMhDym58XfR2OJ--IonlkkM/view?usp=sharing, https://drive.google.com/file/d/1_So90dmTZ1wiPYcg0nWJyE4Qfmhmv_d7/view?usp=sharing, https://drive.google.com/file/d/1fDiyAmJteGjpRuhlWmEJt9fZ0Qgv6npy/view?usp=sharing, https://drive.google.com/file/d/1i4sOPd1WMz5DJu-V6O9ZeirkKXzRSng4/view?usp=sharing, https://drive.google.com/file/d/1iPyF_Grq8wtbI6ekJUYBRqPYV65THeXY/view?usp=sharing, https://drive.google.com/file/d/1nkYPpxfEnxIsucrPI1e1P208kPuhb06s/view?usp=sharing, https://drive.google.com/file/d/1pIp5pu1SP-HycDU2T9WyBxE50YG4DOTu/view?usp=sharing, https://drive.google.com/file/d/1zeyP_FMcuNbxnGEfH5PqF_KCk_pdst3p/view?usp=sharing
