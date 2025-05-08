# index.php  
```php
<?php
// index.php (Updated - Admin Product Routing)

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
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? null, 'string') ?: null; // Use null default
    $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int'); // Use null default

    // --- Stripe Webhook Route (skip CSRF) ---
    if ($page === 'payment' && $action === 'webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/controllers/PaymentController.php';
        $controller = new PaymentController($pdo); // Instantiate PaymentController here too
        $controller->handleWebhook(); // Handles Stripe POST, returns JSON
        exit;
    }

    // --- CSRF validation for POST (skip for Stripe webhook) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ensure session active before CSRF generation/validation
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        SecurityMiddleware::generateCSRFToken(); // Ensure token exists if needed
        SecurityMiddleware::validateCSRF(); // Throws exception on failure
    }

    switch ($page) {
        // --- Public Routes (Unchanged) ---
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
                 http_response_code(404);
                 require_once __DIR__ . '/views/404.php'; // Consider using renderView
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
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                 if ($action === 'add') { $controller->addToCart(); }
                 elseif ($action === 'update') { $controller->updateCart(); }
                 elseif ($action === 'remove') { $controller->removeFromCart(); }
                 elseif ($action === 'clear') { $controller->clearCart(); }
                 else { http_response_code(405); echo "Method not allowed."; }
            } elseif ($action === 'mini') { $controller->mini(); }
            else { $controller->showCart(); }
            break;
        case 'checkout':
            if (!isLoggedIn() && $action !== 'confirmation') {
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=checkout' . ($action ? '&action=' . $action : '');
                header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
            }
            require_once __DIR__ . '/controllers/PaymentController.php';
            require_once __DIR__ . '/controllers/CheckoutController.php';
            require_once __DIR__ . '/controllers/CartController.php';
            $paymentController = new PaymentController($pdo);
            $controller = new CheckoutController($pdo, $paymentController);
            if (empty($action)) {
                $cartCtrl = new CartController($pdo);
                if (empty($cartCtrl->getCartItems())) {
                    // Use BaseController flash message if possible, assumes controller has it
                    if(method_exists($controller, 'setFlashMessage')) {
                        $controller->setFlashMessage('Your cart is empty.', 'info');
                    } else { $_SESSION['flash_message'] = 'Your cart is empty.'; $_SESSION['flash_type'] = 'info';}
                    header('Location: ' . BASE_URL . 'index.php?page=products'); exit;
                }
            }
            if ($action === 'processCheckout' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processCheckout(); }
            elseif ($action === 'confirmation') { $controller->showOrderConfirmation(); }
            elseif ($action === 'calculateTax' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->calculateTax(); }
            elseif ($action === 'applyCouponAjax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 require_once __DIR__ . '/controllers/CouponController.php'; // Need CouponController here
                 $couponController = new CouponController($pdo);
                 $couponController->applyCouponAjax(); // Delegate to CouponController
            } else { $controller->showCheckout(); }
            break;
        // --- Auth Routes (Unchanged) ---
        case 'login':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo); $controller->login(); break;
        case 'register':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo); $controller->register(); break;
        case 'logout':
             if (function_exists('logout')) { logout(); }
             else { session_unset(); session_destroy(); }
             header('Location: ' . BASE_URL . 'index.php?page=login&loggedout=1'); exit;
        case 'account':
             if (!isLoggedIn()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=account' . ($action ? '&action=' . $action : '');
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string'); // Define section for account
             switch ($section) { // Use section instead of action here
                 case 'profile':
                     // Decide based on request method if it's view or update
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                          $controller->updateProfile(); // POST handles update logic
                     } else {
                          $controller->showProfile(); // GET shows the profile page
                     }
                     break;
                 case 'orders':
                     // Check if an order ID is provided for details view
                     if ($id) { $controller->showOrderDetails($id); }
                     else { $controller->showOrders(); } // Show list otherwise
                     break;
                 // Add other account sections (e.g., quiz history) here
                 // case 'quiz': $controller->showQuizHistory(); break;
                 case 'dashboard': // Explicit dashboard case
                 default: $controller->showDashboard(); break;
             }
             break;
        case 'forgot_password':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo); $controller->requestPasswordReset(); break;
        case 'reset_password':
             if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo); $controller->resetPassword(); break;
        // --- Other Routes (Unchanged except Newsletter/Quiz) ---
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

        // --- START: Updated Admin Routing ---
        case 'admin':
             if (!isAdmin()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=admin';
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
             $task = SecurityMiddleware::validateInput($_GET['task'] ?? 'list', 'string'); // Default task to 'list'
             $adminId = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int'); // Use a different var name for admin ID

             switch ($section) {
                case 'products': // NEW Product Section
                    require_once __DIR__ . '/controllers/ProductController.php';
                    $controller = new ProductController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // POST requests handle saving or deleting
                        if ($task === 'save') {
                             $controller->saveAdminProduct(); // Handles both create/update POST
                        } elseif ($task === 'delete') {
                             $controller->deleteAdminProduct($adminId); // Assumes ID might be passed via GET or POST
                        } else {
                             // Unknown POST task, redirect to list
                             $controller->listAdminProducts();
                        }
                    } else {
                        // GET requests handle displaying lists or forms
                        if ($task === 'create') {
                             $controller->showAdminProductForm(); // Show empty form
                        } elseif ($task === 'edit' && $adminId) {
                             $controller->showAdminProductForm($adminId); // Show form with product data
                        } else { // Default task or 'list'
                             $controller->listAdminProducts(); // Show product list
                        }
                    }
                    break; // End Product Section

                 case 'quiz_analytics':
                     require_once __DIR__ . '/controllers/QuizController.php';
                     $controller = new QuizController($pdo); $controller->showAnalytics(); break;
                 case 'coupons':
                    require_once __DIR__ . '/controllers/CouponController.php';
                    $controller = new CouponController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         // Use adminId for consistency
                         if ($task === 'save') { $controller->saveCoupon(); }
                         elseif ($task === 'toggle_status' && $adminId) { $controller->toggleCouponStatus($adminId); }
                         elseif ($task === 'delete' && $adminId) { $controller->deleteCoupon($adminId); }
                         else { $controller->listCoupons(); }
                    } else { // GET
                         if ($task === 'edit' && $adminId) { $controller->showEditForm($adminId); }
                         elseif ($task === 'create') { $controller->showCreateForm(); }
                         else { $controller->listCoupons(); }
                    }
                    break;
                 // Add other admin sections here...
                 case 'dashboard':
                 default: // Admin Dashboard
                      $pageTitle = "Admin Dashboard"; $bodyClass = "page-admin-dashboard";
                      $csrfToken = SecurityMiddleware::generateCSRFToken();
                      extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
                      require_once __DIR__ . '/views/admin/dashboard.php'; break;
             }
             break;
        // --- END: Updated Admin Routing ---

        // --- Static Pages (Unchanged) ---
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
// --- Exception Handling (Unchanged) ---
} catch (PDOException $e) { ErrorHandler::handleException($e); exit(1);
} catch (\Stripe\Exception\ApiErrorException $e) { error_log("Stripe API error in routing/controller: " . $e->getMessage()); ErrorHandler::handleException($e); exit(1);
} catch (Throwable $e) { error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); ErrorHandler::handleException($e); exit(1);
}

```

# config.php.1  
```php
<?php
// Environment
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');
// --- MOVED BASE_URL DEFINITION HERE ---
define('BASE_URL', '/'); // Adjust for your environment ('/' for root, '/the-scent/' if in subfolder)
// --- END MOVED BASE_URL DEFINITION ---

// Security Settings
define('SECURITY_SETTINGS', [
    'session' => [
        'lifetime' => 3600,
        'secure' => true, // Requires HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
        'regenerate_id_interval' => 900 // 15 minutes
    ],
    'rate_limiting' => [
        'enabled' => true,
        'default_window' => 3600,
        'default_max_requests' => 100,
        'ip_whitelist' => [], // Add trusted IPs here
        'endpoints' => [
            'login' => ['window' => 300, 'max_requests' => 5],
            'password_reset_request' => ['window' => 3600, 'max_requests' => 3],
            'password_reset_attempt' => ['window' => 300, 'max_requests' => 5],
            'register' => ['window' => 3600, 'max_requests' => 5],
            'newsletter_subscribe' => ['window' => 3600, 'max_requests' => 10],
            'checkout_submit' => ['window' => 60, 'max_requests' => 10],
            'coupon_apply' => ['window' => 300, 'max_requests' => 15],
            'profile_update' => ['window' => 3600, 'max_requests' => 20],
            'address_update' => ['window' => 3600, 'max_requests' => 10],
            'quiz_submit' => ['window' => 60, 'max_requests' => 5]
            // Add other actions as needed
        ]
    ],
    'encryption' => [
        'algorithm' => 'AES-256-CBC',
        'key_length' => 32 // Added key_length for reference if needed
    ],
    'password' => [
        'min_length' => 12,
        'require_special' => true,
        'require_number' => true,
        'require_mixed_case' => true,
        'max_attempts' => 5, // Example: Max login attempts
        'lockout_duration' => 900 // Example: 15 minutes lockout
    ],
    'logging' => [
        'security_log' => __DIR__ . '/logs/security.log',
        'error_log' => __DIR__ . '/logs/error.log', // Keep PHP error log separate maybe
        'audit_log' => __DIR__ . '/logs/audit.log', // Keep audit separate
        'rotation_size' => 10485760, // 10MB (Example)
        'max_files' => 10 // Example: keep last 10 log files
    ],
    'cors' => [ // Cross-Origin Resource Sharing (Example, adjust as needed)
        // Use BASE_URL constant which is now defined above
        'allowed_origins' => [BASE_URL], // <<< THIS LINE CAUSED THE ERROR
        'allowed_methods' => ['GET', 'POST'], // Restrict methods
        'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Accept'], // Common headers
        'expose_headers' => [],
        'max_age' => 0 // Don't cache preflight requests aggressively during dev
    ],
    'csrf' => [
        'enabled' => true, // Keep enabled
        'token_length' => 32, // Standard length
        'token_lifetime' => 3600 // 1 hour validity
    ],
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // CSP Update: Added *.stripe.com and *.stripe.network
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com https://*.stripe.com; style-src 'self' 'unsafe-inline'; frame-src 'self' https://js.stripe.com https://*.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com https://*.stripe.com https://*.stripe.network",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains' // Enable HSTS if using HTTPS
    ],
    'file_upload' => [ // Example file upload settings
        'max_size' => 5242880, // 5MB
        'allowed_types' => [ // Example MIME types
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf'
        ],
        'scan_malware' => false // Set to true if ClamAV or similar is available
    ]
]);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'the_scent');
define('DB_USER', 'scent_user');
define('DB_PASS', 'StrongPassword123'); // Use environment variables in production
// BASE_URL is defined near the top now

// Stripe Configuration (Replace placeholders with your actual keys)
define('STRIPE_PUBLIC_KEY', 'pk_test_51');
define('STRIPE_SECRET_KEY', 'sk_test_51');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_stripe_webhook_secret'); // Get this from your Stripe Dashboard Webhook settings

// Email Configuration
define('SMTP_HOST', 'localhost'); // Or your actual SMTP host
define('SMTP_PORT', 1025); // Common ports: 587 (TLS), 465 (SSL), 25 (unencrypted), 1025 (Mailhog)
define('SMTP_USER', ''); // Your SMTP username (if required)
define('SMTP_PASS', ''); // Your SMTP password (if required)
define('SMTP_FROM', 'noreply@thescent.local'); // Your sending email address
define('SMTP_FROM_NAME', 'The Scent (Dev)'); // Your sender name
define('SMTP_DEBUG', false); // Set to true for verbose debugging during development ONLY

// Application Settings
define('TAX_RATE', 0.10); // Example: 10% tax rate (Not currently used, TaxController handles rates)
define('FREE_SHIPPING_THRESHOLD', 50.00); // Free shipping on orders over $50
define('SHIPPING_COST', 5.99); // Standard shipping cost
define('PASSWORD_RESET_EXPIRY_SECONDS', 3600); // 1 hour validity for password reset tokens
define('NEWSLETTER_SECRET_KEY', 'USE_A_STRONG_RANDOM_SECRET_KEY_HERE_32_CHARS+'); // Replace with a long random string

// Error & Logging
define('ERROR_LOG_PATH', __DIR__ . '/logs/error.log'); // Consolidated error log path
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_PATH);
// Determine error reporting level based on environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1); // Display errors ONLY in development
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT); // Log most errors in production
    ini_set('display_errors', 0); // DO NOT display errors in production
}
define('ERROR_ALERT_THRESHOLD', 10); // Example: Alert if same error occurs > 10 times/hour

// Session & Security Defaults (Backup if SECURITY_SETTINGS fails)
ini_set('session.cookie_lifetime', '3600');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_secure', '1'); // Requires HTTPS
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', '3600');

?>

```

# includes/db.php  
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable error display in production

if (!file_exists(__DIR__ . '/../config.php')) {
    error_log("Config file not found");
    require_once __DIR__ . '/../views/error.php';
    exit();
}

require_once __DIR__ . '/../config.php';

if (!defined('DB_HOST')) {
    error_log("DB_HOST not defined");
    require_once __DIR__ . '/../views/error.php';
    exit();
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    require_once __DIR__ . '/../views/error.php';
    exit();
}

```

# views/home.php  
```php
<?php 
// home.php (v3 - Merged UI Fixes + Preserved Functionality)
require_once __DIR__ . '/layout/header.php'; // Uses header-fixed.php content implicitly
echo '<!-- DEBUG: home.php loaded -->';
$delay = 0; // Initialize delay counter for animations
?>
<!-- Output CSRF token for JS (for AJAX add-to-cart/newsletter) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars(
    $csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- Hero Section with Video Background -->
<section class="hero-section relative min-h-screen flex items-center justify-center overflow-hidden">
    <div id="particles-js" class="absolute inset-0 z-0"></div>
    <video class="absolute inset-0 w-full h-full object-cover z-[-1]" autoplay muted loop playsinline poster="/images/scent5.jpg">
        <source src="/videos/hero.mp4" type="video/mp4">
        <img src="/images/scent5.jpg" alt="Calming Nature" class="w-full h-full object-cover" />
    </video>
    <div class="absolute inset-0 bg-gradient-to-br from-primary/40 to-primary-dark/50 z-10"></div>
    <div class="container relative z-20 flex flex-col items-center justify-center text-center text-white px-6">
        <div data-aos="fade-down">
            <h1 class="text-4xl md:text-5xl font-bold mb-6 font-heading" style="text-shadow: 0 2px 4px rgba(0,0,0,0.7);">Find Your Moment of Calm</h1>
            <p class="text-lg md:text-xl mb-8 max-w-2xl mx-auto font-body">Experience premium, natural aromatherapy crafted to enhance well-being and restore balance.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#featured-products" class="btn btn-primary">Explore Our Collections</a>
            </div>
        </div>
    </div>
</section>

<!-- About/Mission Section (Keep existing) -->
<section class="about-section py-20 bg-white" id="about">
    <div class="container">
        <div class="about-container grid md:grid-cols-2 gap-12 items-center">
            <div class="about-image" data-aos="fade-left">
                <img src="<?= file_exists($_SERVER['DOCUMENT_ROOT'] . '/images/about.jpg') ? '/images/about.jpg' : 'https://placehold.co/800x600/e9ecef/495057?text=About+The+Scent' ?>"
                     alt="About The Scent" 
                     class="rounded-lg shadow-xl w-full">
            </div>
            <div class="about-content" data-aos="fade-right">
                <h2 class="text-3xl font-bold mb-6">Rooted in Nature, Crafted with Care</h2>
                <p class="mb-6">At The Scent, we harness the power of nature to nurture your mental and physical well-being. Our high-quality, sustainably sourced ingredients are transformed into exquisite aromatherapy products by expert hands.</p>
                <p class="mb-6">Our unique and creative formulations are crafted with expertise to create harmonious, balanced, and well-rounded aromatherapy products that enhance both mental and physical health.</p>
                <a href="index.php?page=about" class="btn btn-secondary">Learn Our Story</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section (Merged UI) -->
<section class="featured-section py-16 bg-light" id="featured-products">
    <div class="container mx-auto text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-12" data-aos="fade-up">Featured Collections</h2>
        <div class="featured-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 px-6">
            <?php if (isset($featuredProducts) && is_array($featuredProducts) && !empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                    <!-- Apply suggested card structure/style -->
                    <div class="product-card sample-card" data-aos="zoom-in" style="border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.05); overflow:hidden;">
                        <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>"
                             class="w-full h-64 object-cover" loading="lazy">
                        <div class="product-info" style="padding:1.5rem; text-align:center;">
                            <h3 style="margin-bottom:0.5rem; font-size:1.3rem;"><?= htmlspecialchars($product['name']) ?></h3>
                            
                            <!-- Apply suggested short description / category display logic -->
                            <?php if (!empty($product['short_description'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['short_description']) ?></p>
                            <?php elseif (!empty($product['category_name'])): ?>
                                <p style="font-size:0.9rem; color:#666; margin-bottom:1rem;"><?= htmlspecialchars($product['category_name']) ?></p>
                            <?php endif; ?>

                            <!-- *** Re-integrate existing actions to preserve functionality *** -->
                            <div class="product-actions flex gap-2 justify-center mt-4">
                                <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a> 
                                <?php if (isset($product['stock_quantity']) && $product['stock_quantity'] > 0): ?>
                                    <button class="btn btn-secondary add-to-cart" 
                                            data-product-id="<?= $product['id'] ?>"
                                            <?= isset($product['low_stock_threshold']) && $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'data-low-stock="true"' : '' ?>>
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                            <!-- *** End of re-integrated actions *** -->
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-600">
                    <p>Discover our curated collection of premium aromatherapy products.</p>
                    <a href="index.php?page=products" class="inline-block mt-4 text-primary hover:underline">Browse All Products</a>
                </div>
            <?php endif; ?>
        </div>
        <!-- Apply suggested "Shop All" CTA below grid -->
        <div class="view-all-cta" style="text-align:center; margin-top:3rem;">
            <a href="index.php?page=products" class="btn btn-primary">Shop All Products</a>
        </div>
    </div>
</section>

<!-- Benefits Section (Keep existing) -->
<section class="py-20 bg-white">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">Why Choose The Scent</h2>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Natural Ingredients</h3>
                <p>Premium quality raw materials sourced from around the world.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-heart text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Wellness Focus</h3>
                <p>Products designed to enhance both mental and physical well-being.</p>
            </div>
            <div class="benefit-card" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-certificate text-4xl text-primary mb-4"></i>
                <h3 class="text-xl font-semibold mb-4">Expert Crafted</h3>
                <p>Unique formulations created by aromatherapy experts.</p>
            </div>
        </div>
    </div>
</section>

<!-- Quiz/Finder Section (Keep existing) -->
<section class="quiz-section py-20 bg-light" id="finder">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-8" data-aos="fade-up">Discover Your Perfect Scent</h2>
        <p class="text-center mb-12 text-lg" data-aos="fade-up" data-aos-delay="100">Tailor your aromatherapy experience to your mood and needs.</p>
        <div class="grid md:grid-cols-5 gap-6 mb-8 finder-grid">
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <i class="fas fa-leaf text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Relaxation</h3>
                <p class="text-sm text-gray-600 text-center">Calming scents to help you unwind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-bolt text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Energy</h3>
                <p class="text-sm text-gray-600 text-center">Invigorating aromas to uplift your day.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <i class="fas fa-brain text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Focus</h3>
                <p class="text-sm text-gray-600 text-center">Clarifying blends for a clear mind.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="300">
                <i class="fas fa-moon text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Sleep</h3>
                <p class="text-sm text-gray-600 text-center">Soothing scents for a peaceful night's rest.</p>
            </div>
            <div class="finder-card flex flex-col items-center p-6 bg-white rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="400">
                <i class="fas fa-balance-scale text-4xl text-primary mb-4"></i>
                <h3 class="font-semibold mb-2">Balance</h3>
                <p class="text-sm text-gray-600 text-center">Harmonious aromas to center you.</p>
            </div>
        </div>
        <div class="text-center" data-aos="fade-up" data-aos-delay="500">
            <a href="index.php?page=quiz" class="btn btn-secondary">Take the Full Scent Quiz</a>
        </div>
    </div>
</section>

<!-- Newsletter Section (Merged UI) -->
<section class="newsletter-section py-20 bg-light" id="newsletter">
    <div class="container">
        <div class="max-w-2xl mx-auto text-center" data-aos="fade-up">
            <h2 class="text-3xl font-bold mb-6">Stay Connected</h2>
            <p class="mb-8">Subscribe to receive updates, exclusive offers, and aromatherapy tips.</p>
            <!-- Apply suggested form structure/style -->
            <form id="newsletter-form" class="newsletter-form flex flex-col sm:flex-row gap-4 justify-center">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input flex-1 px-4 py-2 rounded-full border border-gray-300 focus:border-primary">
                <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
            </form>
            <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1;text-decoration:underline;">Privacy Policy</a>.</p>
        </div>
    </div>
</section>

<!-- Testimonials Section (Keep existing) -->
<section class="py-20 bg-white" id="testimonials">
    <div class="container">
        <h2 class="text-3xl font-bold text-center mb-12" data-aos="fade-up">What Our Community Says</h2>
        <div class="testimonial-grid grid md:grid-cols-3 gap-8">
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="0">
                <p class="mb-4 italic">"The Lavender Essential Oil transformed my bedtime routine—its calming aroma truly helps me unwind."</p>
                <span class="block font-semibold mb-2">- Sarah L., Los Angeles</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="100">
                <p class="mb-4 italic">"The Focus Blend oil improved my concentration at home without overwhelming my senses."</p>
                <span class="block font-semibold mb-2">- Michael T., Chicago</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
            <div class="testimonial-card bg-light p-8 rounded-lg shadow-md" data-aos="fade-up" data-aos-delay="200">
                <p class="mb-4 italic">"Handcrafted soaps that feel divine and truly nourish sensitive skin. A luxurious experience."</p>
                <span class="block font-semibold mb-2">- Emma R., Seattle</span>
                <div class="text-accent text-lg">★★★★★</div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; // Uses footer-fixed.php content implicitly ?>

```

# views/layout/header.php  
```php
<?php
// views/layout/header.php (Updated with data-* attributes for JS config and simplified cart count)

require_once __DIR__ . '/../../includes/auth.php'; // Provides isLoggedIn()

// --- START SESSION IF NOT ACTIVE ---
// Ensure session is started before accessing session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// --- END START SESSION ---

// It's assumed the controller rendering this view has already generated
// and passed $csrfToken and $bodyClass variables into the view's scope.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'The Scent - Premium Aromatherapy Products' ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Montserrat:wght@400;500;600&family=Raleway:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Tailwind CSS custom config -->
    <script>
        window.tailwind = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1A4D5A',
                        'primary-dark': '#164249',
                        secondary: '#A0C1B1',
                        accent: '#D4A76A',
                    },
                    fontFamily: {
                        heading: ['Cormorant Garamond', 'serif'],
                        body: ['Montserrat', 'sans-serif'],
                        accent: ['Raleway', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="<?= isset($bodyClass) ? htmlspecialchars($bodyClass) : '' ?>"
      data-base-url="<?= htmlspecialchars(BASE_URL ?? '/', ENT_QUOTES, 'UTF-8') ?>"
      data-stripe-public-key="<?= htmlspecialchars(STRIPE_PUBLIC_KEY ?? '', ENT_QUOTES, 'UTF-8') ?>"
      data-free-shipping-threshold="<?= htmlspecialchars(FREE_SHIPPING_THRESHOLD ?? '50', ENT_QUOTES, 'UTF-8') ?>"
      data-base-shipping-cost="<?= htmlspecialchars(SHIPPING_COST ?? '5.99', ENT_QUOTES, 'UTF-8') ?>">

    <!-- Global CSRF Token Input for JavaScript AJAX Requests -->
    <input type="hidden" id="csrf-token-value" value="<?= isset($csrfToken) ? htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') : '' ?>">

    <header>
        <nav class="main-nav sample-header">
            <div class="container header-container">
                <div class="logo">
                    <a href="index.php" style="text-transform:uppercase; letter-spacing:1px;">The Scent</a>
                    <span style="display:block; font-family:'Raleway',sans-serif; font-size:0.7rem; letter-spacing:2px; text-transform:uppercase; color:#A0C1B1; margin-top:-5px; opacity:0.8;">AROMATHERAPY</span>
                </div>
                <div class="nav-links" id="mobile-menu">
                    <a href="index.php">Home</a>
                    <a href="index.php?page=products">Shop</a>
                    <a href="index.php?page=quiz">Scent Finder</a>
                    <a href="index.php?page=about">About</a>
                    <a href="index.php?page=contact">Contact</a>
                </div>
                <div class="header-icons">
                    <a href="#" aria-label="Search"><i class="fas fa-search"></i></a>
                    <?php if (isLoggedIn()): ?>
                        <a href="index.php?page=account" aria-label="Account"><i class="fas fa-user"></i></a>
                    <?php else: ?>
                        <a href="index.php?page=login" aria-label="Login"><i class="fas fa-user"></i></a>
                    <?php endif; ?>
                    <a href="index.php?page=cart" class="cart-link relative group" aria-label="Cart">
                        <i class="fas fa-shopping-bag"></i>
                        <?php
                            // --- START SIMPLIFIED CART COUNT ---
                            // Rely on CartController keeping $_SESSION['cart_count'] accurate
                            $cartCount = $_SESSION['cart_count'] ?? 0;
                            // --- END SIMPLIFIED CART COUNT ---
                        ?>
                        <span class="cart-count" style="display: <?= $cartCount > 0 ? 'flex' : 'none' ?>;">
                            <?= $cartCount ?>
                        </span>
                        <!-- Mini-cart dropdown -->
                        <div class="mini-cart-dropdown absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden group-hover:block group-focus-within:block transition-all duration-200" style="min-width:320px;">
                            <div id="mini-cart-content" class="p-4">
                                <!-- Content loaded via fetchMiniCart() in main.js -->
                                <div class="text-center text-gray-500 py-6">Loading cart...</div>
                            </div>
                        </div>
                    </a>
                </div>
                <button class="mobile-menu-toggle md:hidden" aria-label="Toggle Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>
    <main class="pt-[80px]"> <!-- Add padding-top to main content to offset fixed header -->

        <!-- Flash message display area (consider moving if needed, but often okay here) -->
        <?php if (isset($_SESSION['flash_message'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof window.showFlashMessage === 'function') {
                        window.showFlashMessage(<?= json_encode($_SESSION['flash_message']) ?>, <?= json_encode($_SESSION['flash_type'] ?? 'info') ?>);
                    } else {
                        console.warn('showFlashMessage not ready for server-side flash.');
                    }
                });
            </script>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>

        <!-- Container for dynamically added flash messages by JS -->
        <div class="flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full space-y-2"></div>

```

# views/layout/footer.php  
```php
</main>
    <footer>
        <div class="container">
            <div class="footer-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:3rem;margin-bottom:3rem;">
                <div class="footer-about">
                    <h3>About The Scent</h3>
                    <p>Creating premium aromatherapy products to enhance mental and physical well-being through the power of nature.</p>
                    <div class="social-icons">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Pinterest"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="footer-links">
                    <h3>Shop</h3>
                    <ul>
                        <li><a href="index.php?page=products">Essential Oils</a></li>
                        <li><a href="index.php?page=products">Natural Soaps</a></li>
                        <li><a href="index.php?page=products">Gift Sets</a></li>
                        <li><a href="index.php?page=products">New Arrivals</a></li>
                        <li><a href="index.php?page=products">Bestsellers</a></li>
                    </ul>
                </div>
                <div class="footer-links">
                    <h3>Help</h3>
                    <ul>
                        <li><a href="index.php?page=contact">Contact Us</a></li>
                        <li><a href="index.php?page=faq">FAQs</a></li>
                        <li><a href="index.php?page=shipping">Shipping & Returns</a></li>
                        <li><a href="index.php?page=order-tracking">Track Your Order</a></li>
                        <li><a href="index.php?page=privacy">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Aromatherapy Lane, Wellness City, WB 12345</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                    <p><i class="fas fa-envelope"></i> hello@thescent.com</p>
                    <form id="newsletter-form-footer" class="newsletter-form" style="margin-top:1rem;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="email" name="email" placeholder="Enter your email" required class="newsletter-input">
                        <button type="submit" class="btn btn-primary newsletter-btn">Subscribe</button>
                    </form>
                    <p class="newsletter-consent" style="font-size:0.8rem;opacity:0.7; margin-top:1rem;">By subscribing, you agree to our <a href="index.php?page=privacy" style="color:#A0C1B1; text-decoration:underline;">Privacy Policy</a> and consent to receive emails from The Scent.</p>
                </div>
            </div>
            <div class="footer-bottom" style="background-color:#222b2e; padding:1.5rem 0; margin-top:2rem;">
                <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:0.85rem;">
                    <p>&copy; <?= date('Y') ?> The Scent. All rights reserved.</p>
                    <div class="payment-methods" style="display:flex;align-items:center;gap:0.8rem;">
                        <span>Accepted Payments:</span>
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fab fa-cc-amex"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script src="/js/main.js"></script>
</body>
</html>

```

# views/cart.php  
```php
<?php
// Updated: views/cart.php
// Fixed access to product ID within the loop

require_once __DIR__ . '/layout/header.php';
?>
<body class="page-cart">
<!-- Output CSRF token for JS (for AJAX cart actions) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<section class="cart-section">
    <div class="container">
        <div class="cart-container" data-aos="fade-up">
            <h1>Your Shopping Cart</h1>

            <?php if (empty($cartItems)): ?>
                <div class="empty-cart text-center py-16">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                    <p class="text-xl text-gray-700 mb-6">Your cart is currently empty.</p>
                    <a href="index.php?page=products" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form id="cartForm" action="index.php?page=cart&action=update" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Cart Items Column -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="cart-items bg-white shadow rounded-lg overflow-hidden">
                             <div class="hidden md:flex px-6 py-3 bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                 <div class="w-2/5">Product</div>
                                 <div class="w-1/5 text-center">Price</div>
                                 <div class="w-1/5 text-center">Quantity</div>
                                 <div class="w-1/5 text-right">Subtotal</div>
                                 <div class="w-10"></div> <!-- Spacer for remove button -->
                             </div>
                            <?php foreach ($cartItems as $item): ?>
                                <?php
                                    // Defensive variable assignment
                                    $productData = $item['product'] ?? [];
                                    $productId = $productData['product_id'] ?? ($productData['id'] ?? null); // <<<< USE product_id FIRST
                                    $productName = $productData['name'] ?? 'N/A';
                                    $imageUrl = $productData['image'] ?? '/images/placeholder.jpg';
                                    $price = $productData['price'] ?? 0;
                                    $stockQuantity = $productData['stock_quantity'] ?? 0;
                                    $backorderAllowed = $productData['backorder_allowed'] ?? false;
                                    $categoryName = $productData['category_name'] ?? '';
                                    $quantity = $item['quantity'] ?? 0;
                                    $subtotal = $item['subtotal'] ?? 0;
                                    $maxQuantity = ($backorderAllowed || !isset($stockQuantity)) ? 99 : max(1, $stockQuantity);
                                ?>
                                <div class="cart-item flex flex-wrap md:flex-nowrap items-center px-4 py-4 md:px-6 md:py-4 border-b border-gray-200 last:border-b-0" data-product-id="<?= htmlspecialchars($productId ?? '') ?>">
                                    <!-- Product Details (Image & Name) -->
                                    <div class="w-full md:w-2/5 flex items-center mb-4 md:mb-0">
                                        <div class="item-image w-16 h-16 md:w-20 md:h-20 mr-4 flex-shrink-0">
                                            <img src="<?= htmlspecialchars($imageUrl) ?>"
                                                 alt="<?= htmlspecialchars($productName) ?>"
                                                 class="w-full h-full object-cover rounded border">
                                        </div>
                                        <div class="item-details flex-grow">
                                            <h3 class="font-semibold text-primary hover:text-accent text-sm md:text-base">
                                                <a href="index.php?page=product&id=<?= htmlspecialchars($productId ?? '') ?>">
                                                    <?= htmlspecialchars($productName) ?>
                                                </a>
                                            </h3>
                                            <?php if (!empty($categoryName)): ?>
                                                <p class="text-xs text-gray-500 hidden md:block"><?= htmlspecialchars($categoryName) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="item-price w-1/3 md:w-1/5 text-center md:text-base text-gray-700" data-price="<?= $price ?>">
                                        <span class="md:hidden text-xs text-gray-500 mr-1">Price:</span>
                                        $<?= number_format($price, 2) ?>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="item-quantity w-1/3 md:w-1/5 text-center flex justify-center items-center my-2 md:my-0">
                                        <div class="quantity-selector flex items-center border border-gray-300 rounded">
                                             <button type="button" class="quantity-btn minus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-l" aria-label="Decrease quantity">-</button>
                                             <input type="number" name="updates[<?= htmlspecialchars($productId ?? '') ?>]"
                                                    value="<?= $quantity ?>" min="1" max="<?= $maxQuantity ?>"
                                                    class="w-10 h-8 md:w-12 md:h-10 text-center border-l border-r border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary text-sm"
                                                    aria-label="Product quantity" <?= empty($productId) ? 'disabled' : '' ?>>
                                             <button type="button" class="quantity-btn plus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-r" aria-label="Increase quantity">+</button>
                                        </div>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="item-subtotal w-1/3 md:w-1/5 text-right font-semibold md:text-base text-gray-900">
                                         <span class="md:hidden text-xs text-gray-500 mr-1">Subtotal:</span>
                                        $<?= number_format($subtotal, 2) ?>
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="w-full md:w-10 text-center md:text-right mt-2 md:mt-0 md:pl-2">
                                        <button type="button" class="remove-item text-gray-400 hover:text-red-600 transition duration-150 ease-in-out"
                                                data-product-id="<?= htmlspecialchars($productId ?? '') ?>" title="Remove item" <?= empty($productId) ? 'disabled' : '' ?>>
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Cart Actions (Update Cart moved near items) -->
                        <div class="cart-actions text-right mt-4">
                            <button type="submit" class="btn btn-secondary update-cart">
                                <i class="fas fa-sync-alt mr-1"></i> Update Cart
                            </button>
                        </div>
                    </div>


                    <!-- Cart Summary Column -->
                    <div class="lg:col-span-1">
                        <div class="cart-summary bg-white shadow rounded-lg p-6 sticky top-24">
                            <h2 class="text-xl font-semibold mb-6 border-b pb-3">Order Summary</h2>
                            <div class="space-y-3 mb-6">
                                <div class="summary-row flex justify-between items-center">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium text-gray-900">$<?= number_format($total ?? 0, 2) ?></span>
                                </div>
                                <div class="summary-row shipping flex justify-between items-center">
                                    <span class="text-gray-600">Shipping:</span>
                                    <?php $shipping_cost = ($total ?? 0) >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; ?>
                                    <span class="font-medium text-gray-900">
                                        <?= $shipping_cost == 0 ? '<span class="text-green-600">FREE</span>' : '$' . number_format($shipping_cost, 2) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="summary-row total flex justify-between items-center border-t pt-4">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-primary" id="cart-grand-total">
                                    $<?= number_format(($total ?? 0) + $shipping_cost, 2) ?>
                                </span>
                            </div>
                            <div class="mt-8">
                                <a href="index.php?page=checkout" class="btn btn-primary w-full text-center checkout <?= empty($cartItems) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= empty($cartItems) ? 'aria-disabled="true" onclick="return false;"' : '' ?>>
                                    Proceed to Checkout
                                </a>
                            </div>
                            <p class="text-xs text-gray-500 text-center mt-4">Shipping & taxes calculated at checkout.</p>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# controllers/BaseController.php  
```php
<?php

// Ensure SecurityMiddleware is available (likely included via index.php or autoloader)
require_once __DIR__ . '/../includes/SecurityMiddleware.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../config.php'; // For BASE_URL, SECURITY_SETTINGS

abstract class BaseController {
    protected PDO $db; // Use type hint
    protected EmailService $emailService; // Use type hint
    protected array $responseHeaders = []; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        $this->db = $pdo;
        $this->emailService = new EmailService($this->db); // Pass the PDO connection
        $this->initializeSecurityHeaders();
    }

    protected function initializeSecurityHeaders(): void { // Add return type hint
        // Use security settings from config if available
        $this->responseHeaders = SECURITY_SETTINGS['headers'] ?? [
            // Sensible defaults if not configured
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
             // Default CSP - stricter than original, adjust as needed
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self' 'unsafe-inline'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains' // If HTTPS is enforced
        ];
        // Permissions-Policy can be added if needed
    }

    // --- CSRF Methods ---
    /**
     * Gets the current CSRF token, generating one if necessary.
     * Relies on SecurityMiddleware.
     *
     * @return string The CSRF token.
     */
    protected function getCsrfToken(): string {
        // Ensure CSRF is enabled in settings before generating
        if (defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['csrf']['enabled']) && !SECURITY_SETTINGS['csrf']['enabled']) {
             return ''; // Return empty string if CSRF disabled
         }
        return SecurityMiddleware::generateCSRFToken();
    }

    /**
     * Validates the CSRF token submitted in a POST request.
     * Relies on SecurityMiddleware, which throws an exception on failure.
     * It's recommended to call this at the beginning of POST action handlers.
     */
    protected function validateCSRF(): void { // Add return type hint
         // Ensure CSRF is enabled in settings before validating
         if (defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['csrf']['enabled']) && !SECURITY_SETTINGS['csrf']['enabled']) {
              return; // Skip validation if CSRF disabled
          }
        SecurityMiddleware::validateCSRF(); // Throws exception on failure
    }
    // --- End CSRF Methods ---


    /**
     * Ensures the user is logged in. If not, redirects to the login page or sends a 401 JSON response.
     * Also performs session integrity checks and regeneration.
     *
     * @param bool $isAjaxRequest Set to true if the request is AJAX, to return JSON instead of redirecting.
     */
    protected function requireLogin(bool $isAjaxRequest = false): void { // Added optional param and return type
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $details = [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN'
            ];
            $this->logSecurityEvent('unauthorized_access_attempt', $details);

            if ($isAjaxRequest) {
                 $this->jsonResponse(['error' => 'Authentication required.'], 401); // Exit via jsonResponse
            } else {
                 $this->setFlashMessage('Please log in to access this page.', 'warning');
                 // Store intended destination
                 $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . 'index.php?page=account');
                 $this->redirect('index.php?page=login'); // Exit via redirect
            }
            // Explicit exit for safety, although jsonResponse/redirect should exit
            exit();
        }

        // Verify session integrity only if user_id is set
        if (!$this->validateSessionIntegrity()) {
            $this->terminateSession('Session integrity check failed'); // Handles exit
        }

        // Check session age and regenerate if needed
        if ($this->shouldRegenerateSession()) {
            $this->regenerateSession();
        }
    }

    /**
     * Ensures the user is logged in and has the 'admin' role.
     *
     * @param bool $isAjaxRequest Set to true if the request is AJAX, to return JSON instead of redirecting.
     */
    protected function requireAdmin(bool $isAjaxRequest = false): void { // Added optional param and return type
        $this->requireLogin($isAjaxRequest); // Check login first

        // Check role existence and value
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $details = [
                'user_id' => $_SESSION['user_id'] ?? null, // Should be set if requireLogin passed
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                'role_found' => $_SESSION['user_role'] ?? 'NOT SET'
            ];
            $this->logSecurityEvent('unauthorized_admin_attempt', $details);

            if ($isAjaxRequest) {
                 $this->jsonResponse(['error' => 'Admin access required.'], 403); // Exit via jsonResponse
            } else {
                 $this->setFlashMessage('You do not have permission to access this area.', 'error');
                 // Redirect to a safe page like account dashboard
                 $this->redirect('index.php?page=account'); // Exit via redirect
            }
            // Explicit exit for safety
            exit();
        }
    }


    /**
     * Validates input data using SecurityMiddleware.
     * This is a convenience wrapper. Direct use of SecurityMiddleware::validateInput is also fine.
     *
     * @param mixed $input The value to validate.
     * @param string $type The validation type (e.g., 'string', 'int', 'email').
     * @param array $options Additional validation options (e.g., ['min' => 1, 'max' => 100]).
     * @return mixed The validated and potentially sanitized input, or false on failure.
     */
    protected function validateInput(mixed $input, string $type, array $options = []): mixed {
        return SecurityMiddleware::validateInput($input, $type, $options);
    }


    /**
     * Sends a JSON response and terminates script execution.
     *
     * @param array $data The data to encode as JSON.
     * @param int $status The HTTP status code (default: 200).
     */
    protected function jsonResponse(array $data, int $status = 200): void { // Add return type hint
        // Prevent caching of JSON API responses
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        // Apply security headers (optional here if globally applied by SecurityMiddleware::apply)
        // foreach ($this->responseHeaders as $header => $value) { header("$header: $value"); }

        echo json_encode($data); // Removed pretty print for efficiency
        exit;
    }

    /**
     * Performs an HTTP redirect and terminates script execution.
     * Prepends BASE_URL if the URL is relative.
     *
     * @param string $url The relative path or full URL to redirect to.
     * @param int $statusCode The HTTP redirect status code (default: 302).
     */
    protected function redirect(string $url, int $statusCode = 302): void { // Add return type hint
        // Basic check to prevent header injection from $url if it comes from user input
         // Allow relative paths starting with '/' or alphanumeric, or full URLs
        if (!preg_match('~^(/|[\w\-./?=&%]+|https?://)~', $url)) { // Improved regex
             error_log("Invalid redirect URL pattern detected: " . $url);
             $url = '/'; // Redirect home as safe fallback
         }

        // Prepend BASE_URL if it's a relative path
        if (!preg_match('~^https?://~i', $url)) {
             // Ensure BASE_URL ends with a slash and $url doesn't start with one if needed
             $baseUrl = rtrim(BASE_URL, '/') . '/';
             $url = ltrim($url, '/');
             $finalUrl = $baseUrl . $url;
        } else {
            $finalUrl = $url;
        }


        // --- START FIX: Remove filter_var validation for relative URLs ---
        // if (!filter_var($finalUrl, FILTER_VALIDATE_URL)) {
        //     error_log("Redirect URL validation failed after constructing: " . $finalUrl);
        //     header('Location: ' . rtrim(BASE_URL, '/') . '/'); // Redirect home as safe fallback
        //     exit;
        // }
        // --- END FIX ---

        header('Location: ' . $finalUrl, true, $statusCode);
        exit;
    }

    /**
     * Sets a flash message in the session.
     *
     * @param string $message The message content.
     * @param string $type The message type ('info', 'success', 'warning', 'error').
     */
    protected function setFlashMessage(string $message, string $type = 'info'): void { // Add return type hint
        // Ensure session is started before trying to write to it
        if (session_status() === PHP_SESSION_NONE) {
             // Attempt to start session only if headers not sent
             if (!headers_sent()) {
                  session_start();
             } else {
                  // Cannot start session, log error
                  error_log("Session not active and headers already sent. Cannot set flash message: {$message}");
                  return;
             }
        }
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    // Transaction helpers
    protected function beginTransaction(): void { $this->db->beginTransaction(); } // Add return type hint
    protected function commit(): void { $this->db->commit(); } // Add return type hint
    protected function rollback(): void { if ($this->db->inTransaction()) { $this->db->rollBack(); } } // Add return type hint

    // User helpers
    protected function getCurrentUser(): ?array { return $_SESSION['user'] ?? null; } // Add return type hint
    protected function getUserId(): ?int { return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null; } // Add return type hint

    /**
     * Renders a view template with provided data.
     *
     * @param string $viewPath Path to the view file relative to the views directory (e.g., 'account/dashboard').
     * @param array $data Data to extract into the view's scope.
     * @return string The rendered HTML output.
     * @throws Exception If the view file is not found.
     */
    protected function renderView(string $viewPath, array $data = []): string { // Add return type hint
        // Ensure CSRF token is available for views that might need it
        if (!isset($data['csrfToken'])) {
             $data['csrfToken'] = $this->getCsrfToken();
        }
        // Ensure user data is available if needed by layout/views
        if (!isset($data['user']) && isset($_SESSION['user'])) {
            $data['user'] = $_SESSION['user'];
        }

        // Extract data to make it available in view
        extract($data);

        ob_start();
        // Use ROOT_PATH constant defined in index.php for reliability
        $viewFile = ROOT_PATH . '/views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            ob_end_clean(); // Clean buffer before throwing
            throw new Exception("View not found: {$viewFile}");
        }
        try {
            include $viewFile;
        } catch (\Throwable $e) {
             ob_end_clean(); // Clean buffer if view inclusion fails
             error_log("Error rendering view {$viewPath}: " . $e->getMessage());
             // It's often better to let the global ErrorHandler catch this
             throw $e; // Re-throw the error
        }
        return ob_get_clean();
    }

    /**
     * Logs an action to the audit trail database table.
     *
     * @param string $action A code representing the action performed (e.g., 'login_success', 'product_update').
     * @param int|null $userId The ID of the user performing the action, or null if anonymous/system.
     * @param array $details Additional context or data related to the action (will be JSON encoded).
     */
    protected function logAuditTrail(string $action, ?int $userId, array $details = []): void { // Add type hints
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (action, user_id, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $action,
                $userId, // Use the passed userId, allowing null
                $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
                json_encode($details) // Ensure details are encoded
            ]);
        } catch (Exception $e) {
            // Log failure to standard PHP error log
            error_log("Audit logging failed for action '{$action}': " . $e->getMessage());
        }
    }

    /**
     * Validates session integrity markers (User Agent and IP Address).
     * Should be called after confirming user_id is set in session.
     *
     * @return bool True if markers are present and match, false otherwise.
     */
    protected function validateSessionIntegrity(): bool { // Changed from private to protected
        // Check if essential markers exist
        if (!isset($_SESSION['user_agent']) || !isset($_SESSION['ip_address'])) {
             $this->logSecurityEvent('session_integrity_markers_missing', ['user_id' => $_SESSION['user_id'] ?? null]);
            return false; // Markers should have been set on login
        }

        // Compare User Agent
        $userAgentMatch = ($_SESSION['user_agent'] === ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        // Compare IP Address (allow simple mismatch logging for now, strict check below)
        $ipAddressMatch = ($_SESSION['ip_address'] === ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'));

        if (!$userAgentMatch || !$ipAddressMatch) {
             $this->logSecurityEvent('session_integrity_mismatch', [
                 'user_id' => $_SESSION['user_id'] ?? null,
                 'session_ip' => $_SESSION['ip_address'],
                 'current_ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                 'ip_match' => $ipAddressMatch,
                 'session_ua' => $_SESSION['user_agent'],
                 'current_ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                 'ua_match' => $userAgentMatch
             ]);
             // Decide if mismatch should invalidate session - usually yes for strict security
             return false; // Treat mismatch as invalid
        }
        return true;
    }

    /**
     * Checks if the session regeneration interval has passed.
     *
     * @return bool True if session should be regenerated, false otherwise.
     */
    protected function shouldRegenerateSession(): bool { // Changed from private to protected
        $interval = SECURITY_SETTINGS['session']['regenerate_id_interval'] ?? 900; // Default 15 mins from config
        // Check if last_regeneration is set and if interval has passed
        return !isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > $interval;
    }

    /**
     * Regenerates the session ID securely, preserving necessary session data.
     */
    protected function regenerateSession(): void { // Changed from private to protected
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return; // Can't regenerate if session not active
        }

        // Store essential data to transfer to the new session ID
        $currentSessionData = $_SESSION;

        if (session_regenerate_id(true)) { // Destroy old session data associated with the old ID
            // Restore data - may need more specific keys depending on what needs preserving
             $_SESSION = $currentSessionData; // Restore all data
             // Crucially, update the regeneration timestamp
             $_SESSION['last_regeneration'] = time();
        } else {
             // Log failure if regeneration fails (critical)
             $userId = $_SESSION['user_id'] ?? 'Unknown';
             error_log("CRITICAL: Session regeneration failed for user ID: " . $userId);
             $this->logSecurityEvent('session_regeneration_failed', ['user_id' => $userId]);
             // Consider terminating the session as a safety measure
             $this->terminateSession('Session regeneration failed.');
        }
    }

    /**
     * Terminates the current session securely.
     * Logs the reason and redirects to login page.
     *
     * @param string $reason The reason for termination (for logging).
     */
    protected function terminateSession(string $reason): void { // Already protected, added return type hint
        $userId = $_SESSION['user_id'] ?? null;
        $this->logSecurityEvent('session_terminated', [
            'reason' => $reason,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

        // Standard session destruction steps
        $_SESSION = array(); // Unset all variables
        if (ini_get("session.use_cookies")) { // Delete the session cookie
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy(); // Destroy session data on server

        // Redirect to login page
        $this->redirect('index.php?page=login&reason=session_terminated'); // Use redirect helper
    }

    /**
     * Checks and enforces rate limits for a specific action based on IP address.
     * Uses APCu as the backend cache. Throws Exception on limit exceeded.
     *
     * @param string $action The identifier for the action being rate limited (e.g., 'login', 'password_reset').
     * @throws Exception If rate limit is exceeded (HTTP 429 implied).
     */
    protected function validateRateLimit(string $action): void { // Add return type hint
        // Check if rate limiting is enabled globally
        if (!isset(SECURITY_SETTINGS['rate_limiting']['enabled']) || !SECURITY_SETTINGS['rate_limiting']['enabled']) {
            return; // Skip if disabled
        }

        // Determine settings for this specific action
        $defaultSettings = [
            'window' => SECURITY_SETTINGS['rate_limiting']['default_window'] ?? 3600,
            'max_requests' => SECURITY_SETTINGS['rate_limiting']['default_max_requests'] ?? 100
        ];
        $settings = SECURITY_SETTINGS['rate_limiting']['endpoints'][$action] ?? $defaultSettings;

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        if ($ip === 'UNKNOWN') {
             error_log("Rate Limiting Warning: Cannot determine client IP address for action '{$action}'.");
             return;
        }

        // Check whitelist
        if (!empty(SECURITY_SETTINGS['rate_limiting']['ip_whitelist']) && in_array($ip, SECURITY_SETTINGS['rate_limiting']['ip_whitelist'])) {
            return; // Skip whitelisted IPs
        }

        // Use APCu for rate limiting (Ensure APCu extension is installed and enabled)
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            $key = "rate_limit:{$action}:{$ip}";
            // Fetch attempts *atomically* if possible, otherwise handle potential race condition
            // apcu_inc returns the new value, apcu_add returns true/false
             $current_attempts = apcu_inc($key);

             if ($current_attempts === false) { // Key didn't exist or another issue
                  // Try adding the key with count 1 and TTL
                  if (apcu_add($key, 1, $settings['window'])) {
                      $current_attempts = 1;
                  } else {
                      // If add failed, it might mean it was just created by another request - try incrementing again
                      $current_attempts = apcu_inc($key);
                      if ($current_attempts === false) {
                           // Still failed, maybe APCu issue? Log error and potentially skip check
                           error_log("Rate Limiting Error: Failed to initialize or increment APCu key '{$key}'.");
                           $this->logSecurityEvent('rate_limit_backend_error', ['action' => $action, 'ip' => $ip, 'key' => $key]);
                           return; // Fail open in this edge case? Or throw 500?
                      }
                  }
             }


            if ($current_attempts > $settings['max_requests']) {
                $this->logSecurityEvent('rate_limit_exceeded', [
                    'action' => $action, 'ip' => $ip, 'limit' => $settings['max_requests'], 'window' => $settings['window']
                ]);
                throw new Exception('Rate limit exceeded. Please try again later.', 429);
            }
        } else {
            error_log("Rate Limiting Warning: APCu extension is not available or enabled. Rate limiting skipped for action '{$action}' from IP {$ip}.");
            $this->logSecurityEvent('rate_limit_backend_unavailable', ['action' => $action, 'ip' => $ip]);
        }
    }


    /**
     * Logs a security-related event to the designated security log file.
     *
     * @param string $event A code for the security event (e.g., 'login_failure', 'csrf_validation_failed').
     * @param array $details Contextual details about the event.
     */
     protected function logSecurityEvent(string $event, array $details = []): void { // Add return type hint
         // Add common context automatically
         $commonContext = [
             'timestamp' => date('Y-m-d H:i:s T'), // Include timezone
             'user_id' => $this->getUserId(), // Use helper method
             'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
             'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
             'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
             'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A'
         ];
         $logDetails = array_merge($commonContext, $details); // Merge, letting specific details override common ones if needed

         $logMessage = sprintf(
             "[SECURITY] Event: %s | Details: %s",
             $event,
             json_encode($logDetails) // Encode all details as JSON
         );

         // Log to the file specified in config
         $logFile = SECURITY_SETTINGS['logging']['security_log'] ?? (__DIR__ . '/../logs/security.log');
         // Ensure directory exists and is writable (simple check)
         $logDir = dirname($logFile);
         if (!is_dir($logDir)) { @mkdir($logDir, 0750, true); } // Attempt creation

         if (is_writable($logDir) && (file_exists($logFile) ? is_writable($logFile) : true) ) {
              error_log($logMessage . PHP_EOL, 3, $logFile);
         } else {
              // Fallback to standard PHP error log if specific log file isn't writable
              error_log("Security Log Write Failed! " . $logMessage);
         }
      }

} // End of BaseController class

```

# models/User.php  
```php
<?php
// models/User.php (Updated to implement updateAddress correctly)

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get user details by ID.
     * @param int $id User ID.
     * @return array|false User data array or false if not found.
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get user details by email address.
     * @param string $email Email address.
     * @return array|false User data array or false if not found.
     */
    public function getByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    /**
     * Creates a new user.
     * Assumes $data['password'] is already hashed.
     * Assumes 'newsletter_subscribed' and 'status' columns exist.
     *
     * @param array $data User data including name, email, password (hashed), role, newsletter preference.
     * @return int|false The ID of the newly created user or false on failure.
     */
    public function create($data) {
        // Assumes DB schema has: name, email, password, role, status, newsletter_subscribed, created_at, updated_at
        $sql = "
            INSERT INTO users (
                name, email, password, role, status, newsletter_subscribed, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ";
        $stmt = $this->pdo->prepare($sql);

        $success = $stmt->execute([
            $data['name'],
            $data['email'],
            $data['password'], // Expecting already hashed password from controller
            $data['role'] ?? 'user',
            $data['status'] ?? 'active', // Default status to 'active'
            isset($data['newsletter']) ? (int)$data['newsletter'] : 0 // Convert boolean to int (0/1)
        ]);
        return $success ? (int)$this->pdo->lastInsertId() : false;
    }

    /*
     * Removed generic update method - Replaced by specific update methods below.
     * public function update($id, $data) { ... }
     */

    /**
     * Deletes a user by ID.
     * @param int $id User ID.
     * @return bool True on success, false on failure.
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Verifies a user's password.
     * Renamed from validatePassword for clarity.
     *
     * @param int $userId User ID.
     * @param string $password The plain text password to verify.
     * @return bool True if the password is valid, false otherwise.
     */
    public function verifyPassword($userId, $password) {
        $user = $this->getById($userId);
        // Ensure user exists and password field is not empty before verifying
        return $user && !empty($user['password']) && password_verify($password, $user['password']);
    }

    /**
     * Gets the user's address details from the database.
     * Uses the address columns added to the 'users' table.
     *
     * @param int $userId User ID.
     * @return array|null Address data array or null if user not found.
     */
    public function getAddress(int $userId): ?array {
        // --- START FIX: Implement getAddress ---
        try {
            // Select the specific address columns from the users table
            $sql = "SELECT address_line1, address_line2, city, state, postal_code, country
                    FROM users
                    WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $address = $stmt->fetch(PDO::FETCH_ASSOC);

            // fetch() returns false if no row is found. Return null in that case.
            return $address ?: null;

        } catch (PDOException $e) {
            // Log the error and return null if the query fails
            error_log("Error fetching address for user ID {$userId}: " . $e->getMessage());
            return null;
        }
        // --- END FIX ---
    }

    /**
     * Updates a user's basic information (name and email).
     * Assumes 'updated_at' column exists with ON UPDATE CURRENT_TIMESTAMP or is updated manually.
     *
     * @param int $userId User ID.
     * @param string $name New full name.
     * @param string $email New email address.
     * @return bool True on success, false on failure.
     */
    public function updateBasicInfo(int $userId, string $name, string $email): bool {
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $sql = "UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$name, $email, $userId]);
    }

    /**
     * Checks if an email address is already registered by another user.
     *
     * @param string $email Email address to check.
     * @param int $currentUserId The ID of the user *currently* being updated (to exclude them from the check).
     * @return bool True if the email is taken by someone else, false otherwise.
     */
    public function isEmailTakenByOthers(string $email, int $currentUserId): bool {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $currentUserId]);
        // If fetchColumn returns a value (an ID), it means the email is taken by another user.
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Sets or updates the password reset token and its expiry time for a user.
     * Assumes 'reset_token' and 'reset_token_expires_at' columns exist in the 'users' table.
     *
     * @param int $userId User ID.
     * @param string $token The secure reset token.
     * @param string $expiry SQL formatted DATETIME string for expiry.
     * @return bool True on success, false on failure.
     */
    public function setResetToken(int $userId, string $token, string $expiry): bool {
        // Assumes DB schema has: reset_token VARCHAR(255) NULL, reset_token_expires_at DATETIME NULL
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $sql = "UPDATE users SET reset_token = ?, reset_token_expires_at = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$token, $expiry, $userId]);
    }

    /**
     * Retrieves user data based on a valid (non-null and non-expired) password reset token.
     * Assumes 'reset_token' and 'reset_token_expires_at' columns exist.
     *
     * @param string $token The password reset token to search for.
     * @return array|false User data array or false if token is invalid/expired.
     */
    public function getUserByValidResetToken(string $token): ?array {
        // Assumes DB schema has: reset_token VARCHAR(255) NULL, reset_token_expires_at DATETIME NULL
        $sql = "SELECT * FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        return $user ?: null; // Return null instead of false for consistency
    }

    /**
     * Updates a user's password and clears the reset token information.
     * Assumes 'reset_token' and 'reset_token_expires_at' columns exist.
     *
     * @param int $userId User ID.
     * @param string $newPassword The new plain text password (will be hashed).
     * @return bool True on success, false on failure.
     */
    public function resetPassword(int $userId, string $newPassword): bool {
        // Assumes DB schema has: reset_token VARCHAR(255) NULL, reset_token_expires_at DATETIME NULL
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            error_log("Password hashing failed during password reset for user ID: {$userId}");
            return false; // Indicate failure
        }

        $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Updates the user's newsletter subscription preference.
     * Assumes 'newsletter_subscribed' column (BOOLEAN/TINYINT) exists.
     *
     * @param int $userId User ID.
     * @param bool $isSubscribed True to subscribe, false to unsubscribe.
     * @return bool True on success, false on failure.
     */
    public function updateNewsletterPreference(int $userId, bool $isSubscribed): bool {
        // Assumes DB schema has: newsletter_subscribed BOOLEAN or TINYINT(1)
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $sql = "UPDATE users SET newsletter_subscribed = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([(int)$isSubscribed, $userId]); // Cast boolean to 0 or 1
    }

    /**
     * Updates only the user's password. Called after verifying the current password.
     *
     * @param int $userId User ID.
     * @param string $newPassword New plain text password (will be hashed).
     * @return bool True on success, false on failure.
     */
    public function updatePassword(int $userId, string $newPassword): bool {
        // Assumes updated_at is handled by DB trigger or needs explicit update
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hashedPassword === false) {
            error_log("Password hashing failed during password update for user ID: {$userId}");
            return false;
        }

        $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Updates the user's shipping address details.
     * Assumes address columns exist in the 'users' table.
     *
     * @param int $userId The ID of the user to update.
     * @param array $addressData Associative array containing address components
     *        (e.g., ['address_line1' => '...', 'city' => '...', ...]).
     *        Keys should match the form field names.
     * @return bool True on success, false on failure.
     */
    public function updateAddress(int $userId, array $addressData): bool {
        // --- START FIX: Correct key mapping ---
        try {
            $sql = "
                UPDATE users SET
                    address_line1 = :address_line1,
                    address_line2 = :address_line2,
                    city = :city,
                    state = :state,
                    postal_code = :postal_code,
                    country = :country,
                    updated_at = NOW()
                WHERE id = :user_id
            ";
            $stmt = $this->pdo->prepare($sql);

            // Use the keys directly from the form data passed by the controller
            $success = $stmt->execute([
                ':address_line1' => $addressData['address_line1'] ?? null,
                ':address_line2' => $addressData['address_line2'] ?? null,
                ':city' => $addressData['city'] ?? null,
                ':state' => $addressData['state'] ?? null,
                ':postal_code' => $addressData['postal_code'] ?? null,
                ':country' => $addressData['country'] ?? null,
                ':user_id' => $userId
            ]);
            return $success;
        } catch (PDOException $e) {
            error_log("Error updating address for user ID {$userId}: " . $e->getMessage());
            return false;
        }
        // --- END FIX ---
    }

} // End of User class

```

# models/Quiz.php  
```php
<?php
// models/Quiz.php (Updated: Fixed column name in getResultsByUserId)

class Quiz {
    private PDO $pdo; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        $this->pdo = $pdo;
    }

    public function getQuestions() {
        // This method remains unchanged from the original
        return [
            [
                'id' => 'mood',
                'question' => 'What are you looking for today?',
                'options' => [
                    'relaxation' => [
                        'label' => 'Relaxation',
                        'icon' => 'fa-spa',
                        'description' => 'Find calm and peace in your daily routine'
                    ],
                    'energy' => [
                        'label' => 'Energy',
                        'icon' => 'fa-bolt',
                        'description' => 'Boost your vitality and motivation'
                    ],
                    'focus' => [
                        'label' => 'Focus',
                        'icon' => 'fa-brain',
                        'description' => 'Enhance concentration and clarity'
                    ],
                    'balance' => [
                        'label' => 'Balance',
                        'icon' => 'fa-yin-yang',
                        'description' => 'Find harmony in body and mind'
                    ]
                ]
            ]
        ];
    }

    public function getRecommendations($answers) {
        // This method remains unchanged from the original
        try {
            $moodEffectMap = [
                'relaxation' => 'calming',
                'energy' => 'energizing',
                'focus' => 'focusing',
                'balance' => 'balancing'
            ];

            $mood = $answers['mood'] ?? 'relaxation';
            $moodEffect = $moodEffectMap[$mood] ?? 'calming';

            // Get matching products based on mood and attributes
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type, pa.intensity_level
                FROM products p
                LEFT JOIN product_attributes pa ON p.id = pa.product_id /* Use LEFT JOIN in case attributes are missing */
                WHERE pa.mood_effect = ?
                ORDER BY RAND()
                LIMIT 3
            ");

            $stmt->execute([$moodEffect]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no exact matches, get featured products as fallback
            if (empty($products)) {
                $stmt = $this->pdo->prepare("
                    SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type, pa.intensity_level
                    FROM products p
                    LEFT JOIN product_attributes pa ON p.id = pa.product_id /* Use LEFT JOIN */
                    WHERE p.is_featured = 1
                    ORDER BY RAND()
                    LIMIT 3
                ");
                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Add scent descriptions
            foreach ($products as &$product) {
                // Defensive check for keys before accessing
                $scentType = $product['scent_type'] ?? null;
                $moodEff = $product['mood_effect'] ?? null;
                $product['scent_description'] = $scentType ? $this->getScentDescription($scentType) : '';
                $product['mood_description'] = $moodEff ? $this->getMoodDescription($moodEff) : '';
            }
             unset($product); // Unset reference

            return $products ?: []; // Ensure array return
        } catch (PDOException $e) {
            error_log("Error getting recommendations: " . $e->getMessage());
            throw $e; // Re-throw to be handled globally
        }
    }

    /**
     * Saves quiz result. Accepts optional $details array (not currently used but added for signature compatibility).
     *
     * @param int|null $userId
     * @param string|null $email
     * @param array $answers
     * @param array $recommendationIds Array of recommended product IDs.
     * @param array|null $details Optional extra details (e.g., completion time, browser). Not currently stored.
     * @return bool
     */
    public function saveQuizResult(?int $userId, ?string $email, array $answers, array $recommendationIds, ?array $details = null): bool {
        // Adjusted signature to accept 5 arguments as called by controller, but $details is currently unused here.
        // Kept original implementation using $recommendationIds.
        try {
            // The controller now passes an array of IDs directly.
            $stmt = $this->pdo->prepare("
                INSERT INTO quiz_results
                (user_id, email, answers, recommendations, created_at) /* Use correct column name 'recommendations' */
                VALUES (?, ?, ?, ?, NOW()) /* Use NOW() for DB consistency */
            ");

            // Log details if provided (optional)
            // if ($details) { error_log("Quiz Save Details: " . json_encode($details)); }

            return $stmt->execute([
                $userId, // Can be null for guests
                $email, // Can be null for logged-in users if not collected
                json_encode($answers), // Store answers as JSON
                json_encode($recommendationIds) // Store recommended product IDs as JSON array
            ]);
        } catch (PDOException $e) {
            error_log("Error saving quiz result: " . $e->getMessage());
             // Don't throw here, controller might want to proceed anyway
             return false; // Indicate failure
        }
    }

    /**
     * Fetches all quiz results for a specific user, ordered by date.
     *
     * @param int $userId The user's ID.
     * @return array An array of quiz results, or an empty array if none found or on error.
     */
    public function getResultsByUserId(int $userId): array {
        if ($userId <= 0) {
            return []; // Return empty array for invalid user ID
        }
        try {
            // --- START FIX: Select 'recommendations' column instead of 'recommended_products' ---
            $stmt = $this->pdo->prepare("
                SELECT id, user_id, email, answers, recommendations, created_at
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            // --- END FIX ---
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optionally decode JSON fields here if needed by the view immediately
            foreach ($results as &$item) {
                 $item['answers'] = isset($item['answers']) ? (json_decode($item['answers'], true) ?? []) : [];
                 // Decode the correct column now
                 $item['recommendations'] = isset($item['recommendations']) ? (json_decode($item['recommendations'], true) ?? []) : [];
            }
            unset($item); // Unset reference


            return $results ?: []; // Return results or empty array

        } catch (PDOException $e) {
            error_log("Error fetching quiz results for user ID {$userId}: " . $e->getMessage());
            return []; // Return empty array on database error
        }
    }


    private function getScentDescription($scentType) {
        // This method remains unchanged from the original
        $descriptions = [
            'floral' => 'Delicate and romantic floral notes that bring peace and harmony',
            'woody' => 'Rich, grounding woody scents that promote stability and strength',
            'citrus' => 'Bright, uplifting citrus notes that energize and refresh',
            'oriental' => 'Warm, exotic notes that create a sense of luxury and comfort',
            'fresh' => 'Clean, crisp scents that invigorate and purify'
        ];
        return $descriptions[$scentType] ?? '';
    }

    private function getMoodDescription($moodEffect) {
        // This method remains unchanged from the original
        $descriptions = [
            'calming' => 'Perfect for relaxation and stress relief',
            'energizing' => 'Ideal for boosting energy and motivation',
            'focusing' => 'Helps improve concentration and mental clarity',
            'balancing' => 'Promotes overall harmony and well-being'
        ];
        return $descriptions[$moodEffect] ?? '';
    }

    /**
     * Simple analytics - aggregates counts by date.
     * (Unchanged from original)
     */
    public function getAnalytics($timeRange = 30) {
        try {
             $intervalClause = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
             if ($timeRange === 'all') {
                 $intervalClause = "1=1"; // No date filtering for 'all'
                 $params = [];
             } else {
                 $timeRange = max(1, (int)$timeRange); // Ensure positive integer
                 $params = [$timeRange];
             }

             $sql = "
                 SELECT
                     DATE(created_at) as date,
                     COUNT(*) as total_quizzes,
                     COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id ELSE email END) as unique_participants,
                     COUNT(DISTINCT user_id) as registered_users,
                     COUNT(DISTINCT CASE WHEN user_id IS NULL THEN email END) as guest_users
                 FROM quiz_results
                 WHERE {$intervalClause}
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC /* Changed to ASC for charting */
             ";
             $stmt = $this->pdo->prepare($sql);
             $stmt->execute($params);
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             error_log("Error getting quiz analytics: " . $e->getMessage());
             return []; // Return empty on error
         }
    }

    /**
     * Gets counts of popular mood selections.
     * (Unchanged from original)
     */
    public function getPopularMoods($timeRange = 30) {
        try {
             $intervalClause = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
             if ($timeRange === 'all') {
                 $intervalClause = "1=1";
                 $params = [];
             } else {
                 $timeRange = max(1, (int)$timeRange);
                 $params = [$timeRange];
             }

             $sql = "
                 SELECT
                     JSON_UNQUOTE(JSON_EXTRACT(answers, '$.mood')) as mood, /* Assumes 'mood' key */
                     COUNT(*) as count
                 FROM quiz_results
                 WHERE JSON_VALID(answers) /* Ensure answers is valid JSON */
                   AND JSON_EXTRACT(answers, '$.mood') IS NOT NULL /* Ensure mood key exists */
                   AND {$intervalClause}
                 GROUP BY mood
                 HAVING mood IS NOT NULL AND mood != '' /* Filter out null/empty results */
                 ORDER BY count DESC
             ";
             $stmt = $this->pdo->prepare($sql);
             $stmt->execute($params);
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             error_log("Error getting popular moods: " . $e->getMessage());
             return []; // Return empty on error
         }
    }

    /**
     * Gets personalized recommendations based on user's latest quiz.
     * (Unchanged from original)
     */
    public function getPersonalizedRecommendations($userId, $limit = 3) {
         try {
            $limit = max(1, (int)$limit); // Ensure positive limit

            // Get user's most recent quiz result
            $stmtHistory = $this->pdo->prepare("
                SELECT answers, recommendations /* Use correct column */
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmtHistory->execute([$userId]);
            $latestResult = $stmtHistory->fetch();

            $excludeIds = [];
            $targetMood = null;
            $targetScent = null; // Added target scent possibility

            if ($latestResult) {
                $answers = json_decode($latestResult['answers'], true);
                $targetMood = $answers['mood'] ?? null;
                // Decode existing recommendations to exclude them
                $excludeIds = json_decode($latestResult['recommendations'], true); // Use correct column
                if (!is_array($excludeIds)) $excludeIds = [];
                $excludeIds = array_filter($excludeIds, 'is_numeric'); // Ensure numeric IDs
            }

            // Build query dynamically based on available criteria
            $sql = "SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type
                    FROM products p
                    LEFT JOIN product_attributes pa ON p.id = pa.product_id
                    WHERE 1=1 "; // Start WHERE clause
            $params = [];

            if ($targetMood) {
                 $moodEffectMap = ['relaxation' => 'calming', 'energy' => 'energizing', 'focus' => 'focusing', 'balance' => 'balancing'];
                 if (isset($moodEffectMap[$targetMood])) {
                     $sql .= " AND pa.mood_effect = ?";
                     $params[] = $moodEffectMap[$targetMood];
                 }
            }

            // Optionally: Add scent preference logic if quiz captures it
            // if ($targetScent) { $sql .= " AND pa.scent_type = ?"; $params[] = $targetScent; }

            if (!empty($excludeIds)) {
                $placeholders = rtrim(str_repeat('?,', count($excludeIds)), ',');
                $sql .= " AND p.id NOT IN ({$placeholders})";
                $params = array_merge($params, $excludeIds);
            }

            // Add ORDER BY and LIMIT
            $sql .= " ORDER BY RAND() LIMIT ?";
            $params[] = $limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fallback: If not enough personalized results, fill with featured
            $needed = $limit - count($products);
            if ($needed > 0) {
                // Add already recommended IDs to exclude list for fallback too
                $currentIds = array_column($products, 'id');
                $excludeFallback = array_merge($excludeIds, $currentIds);
                $fallbackProducts = $this->getFallbackRecommendations($needed, $excludeFallback);
                $products = array_merge($products, $fallbackProducts);
            }

            return array_slice($products, 0, $limit); // Ensure exactly $limit items

        } catch (PDOException $e) {
            error_log("Error getting personalized recommendations for user {$userId}: " . $e->getMessage());
            return $this->getFallbackRecommendations($limit); // Provide fallback on error
        }
    }

    /** Helper for fallback recommendations (Unchanged from original) */
    private function getFallbackRecommendations(int $limit, array $excludeIds = []): array {
         try {
             $sql = "SELECT p.*
                     FROM products p
                     WHERE p.is_featured = 1";
             $params = [];

             if (!empty($excludeIds)) {
                  $placeholders = rtrim(str_repeat('?,', count($excludeIds)), ',');
                  $sql .= " AND p.id NOT IN ({$placeholders})";
                  $params = $excludeIds;
             }

             $sql .= " ORDER BY RAND() LIMIT ?";
             $params[] = $limit;

             $stmt = $this->pdo->prepare($sql);
             // Bind params correctly based on whether excludeIds were added
             $paramIndex = 1;
             foreach($params as $param) {
                  $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
                  $stmt->bindValue($paramIndex++, $param, $type);
             }

             $stmt->execute();
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             error_log("Error getting fallback recommendations: " . $e->getMessage());
             return [];
         }
    }

    /**
     * Fetches detailed analytics data for the admin dashboard.
     * (Unchanged from previous correct version)
     */
    public function getDetailedAnalytics(string $timeRange): array {
        $results = [
            'statistics' => ['total_quizzes' => 0, 'unique_participants' => 0, 'conversion_rate' => 0, 'avg_completion_time' => 0],
            'preferences' => ['mood_effects' => [], 'scent_types' => [], 'daily_completions' => []], // Assuming structure for charts
            'recommendations' => []
        ];

        try {
            // Determine date interval SQL clause
            $intervalClause = "1=1"; // Default for 'all'
            $params = [];
            if ($timeRange !== 'all') {
                $days = (int)$timeRange;
                if ($days > 0) {
                    $intervalClause = "qr.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                    $params[] = $days;
                }
            }

            // 1. Basic Statistics
            $sqlStats = "
                SELECT
                    COUNT(qr.id) as total_quizzes,
                    COUNT(DISTINCT CASE WHEN qr.user_id IS NOT NULL THEN qr.user_id ELSE qr.email END) as unique_participants
                    /* Add conversion rate and avg time later if data available */
                FROM quiz_results qr
                WHERE {$intervalClause}
            ";
            $stmtStats = $this->pdo->prepare($sqlStats);
            $stmtStats->execute($params);
            $statsData = $stmtStats->fetch(PDO::FETCH_ASSOC);
            if ($statsData) {
                $results['statistics']['total_quizzes'] = (int)$statsData['total_quizzes'];
                $results['statistics']['unique_participants'] = (int)$statsData['unique_participants'];
                 $results['statistics']['conversion_rate'] = null; // Requires joining with orders
                 $results['statistics']['avg_completion_time'] = null; // Requires storing completion time
            }

            // 2. Preferences - Mood Effects (assuming 'mood' key in answers JSON)
            $sqlMood = "
                SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(qr.answers, '$.mood')) as effect,
                    COUNT(*) as count
                FROM quiz_results qr
                WHERE JSON_VALID(qr.answers) AND JSON_EXTRACT(qr.answers, '$.mood') IS NOT NULL AND {$intervalClause}
                GROUP BY effect
                ORDER BY count DESC
            ";
            $stmtMood = $this->pdo->prepare($sqlMood);
            $stmtMood->execute($params);
            $results['preferences']['mood_effects'] = $stmtMood->fetchAll(PDO::FETCH_ASSOC);

            // 3. Preferences - Scent Types (Placeholder)
            $results['preferences']['scent_types'] = []; // Placeholder

            // 4. Daily Completions
            $sqlDaily = "
                SELECT DATE(qr.created_at) as date, COUNT(*) as count
                FROM quiz_results qr
                WHERE {$intervalClause}
                GROUP BY DATE(qr.created_at)
                ORDER BY date ASC
            ";
            $stmtDaily = $this->pdo->prepare($sqlDaily);
            $stmtDaily->execute($params);
            $results['preferences']['daily_completions'] = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

            // 5. Top Recommendations & Conversion (Placeholder)
            $results['recommendations'] = []; // Placeholder

        } catch (PDOException $e) {
            error_log("Error generating detailed quiz analytics: " . $e->getMessage());
        }
        return $results;
    }


    /**
     * Fetches the quiz submission history for a specific user.
     * (Unchanged from previous correct version)
     */
    public function getUserPreferenceHistory(int $userId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT answers, recommendations, created_at /* Use correct column name */
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optionally decode JSON fields here if needed by the view immediately
            foreach ($history as &$item) {
                 $item['answers'] = isset($item['answers']) ? (json_decode($item['answers'], true) ?? []) : [];
                 $item['recommendations'] = isset($item['recommendations']) ? (json_decode($item['recommendations'], true) ?? []) : []; // Use correct column name
            }
             unset($item); // Unset reference

            return $history ?: []; // Return history or empty array
        } catch (PDOException $e) {
            error_log("Error fetching user preference history for User ID {$userId}: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }


} // End Quiz Class

```

