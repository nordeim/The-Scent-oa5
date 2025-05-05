Okay, I will address the two noted issues: **cart storage inconsistency** and **inconsistent rate limiting application**.

**Chosen Approach:**

1.  **Cart Storage:** We will enforce a clear separation: Guests use `$_SESSION['cart']`, Logged-in users use the `cart_items` database table via the `Cart` model. The `CartController` will be refactored to handle this logic explicitly in all methods. `$_SESSION['cart_count']` will be consistently updated by the `CartController` to reflect the correct count for both guests and logged-in users, making it the reliable source for the header display. The `mergeSessionCartOnLogin` function remains crucial for transferring the cart upon login.
2.  **Rate Limiting:** We will identify the key controller actions needing protection and add the `$this->validateRateLimit('action_name');` call at the beginning of those methods in their respective controllers.

**Files to be Modified:**

*   `controllers/CartController.php` (Major refactor for storage consistency)
*   `views/layout/header.php` (Simplify cart count display logic)
*   `controllers/AccountController.php` (Add rate limiting)
*   `controllers/NewsletterController.php` (Add rate limiting)
*   `controllers/CheckoutController.php` (Add rate limiting)

---

**1. Updated File: `controllers/CartController.php`**

*(Refactored to strictly separate Session vs. DB storage and consistently update session count)*

```php
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Cart.php';

class CartController extends BaseController {
    private Product $productModel; // Use type hint
    private ?Cart $cartModel = null; // Allow null, use type hint
    private bool $isLoggedIn = false; // Use type hint
    private ?int $userId = null; // Allow null, use type hint

    public function __construct(PDO $pdo) { // Use type hint
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);

        // Ensure session is started before accessing $_SESSION
        // BaseController constructor might handle this, but ensure it runs early.
        if (session_status() === PHP_SESSION_NONE) {
             session_start();
        }

        // Check login status using BaseController method for consistency
        $this->userId = $this->getUserId(); // Get user ID via BaseController
        $this->isLoggedIn = ($this->userId !== null); // Set boolean based on userId

        if ($this->isLoggedIn) {
            // Initialize Cart model only for logged-in users
            // Ensure Cart model is loaded (though require_once is typical)
            if (!class_exists('Cart')) require_once __DIR__ . '/../models/Cart.php';
            $this->cartModel = new Cart($pdo, $this->userId);
        } else {
            // Ensure session cart exists for guests AND initialize session count
            if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
            if (!isset($_SESSION['cart_count'])) { $_SESSION['cart_count'] = 0; }
        }
    }

    // --- Static method called during login ---
    public static function mergeSessionCartOnLogin(PDO $pdo, int $userId): void { // Added type hints
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (!empty($_SESSION['cart'])) {
            // Ensure Cart model is loaded if called statically
            if (!class_exists('Cart')) { require_once __DIR__ . '/../models/Cart.php'; }
            $cartModel = new Cart($pdo, $userId);
            $cartModel->mergeSessionCart($_SESSION['cart']); // mergeSessionCart handles adding/updating quantities
            // Clear session cart AFTER successful merge attempt
            $_SESSION['cart'] = [];
            $_SESSION['cart_count'] = $cartModel->getCartCount(); // Update session count from DB post-merge
        } else {
             // Even if session cart was empty, ensure DB count is loaded into session
             if (!class_exists('Cart')) { require_once __DIR__ . '/../models/Cart.php'; }
             $cartModel = new Cart($pdo, $userId);
             $_SESSION['cart_count'] = $cartModel->getCartCount();
        }
    }


    // --- Display Cart View ---
    public function showCart() {
        $cartItems = $this->getCartItemsInternal(); // Use internal helper
        $total = 0.0;
        foreach ($cartItems as $item) {
             $total += $item['subtotal'] ?? 0.0;
        }

        // Ensure session count is accurate before rendering view
        $_SESSION['cart_count'] = $this->getCartCount();

        $csrfToken = $this->getCsrfToken();
        $bodyClass = 'page-cart';
        $pageTitle = 'Your Shopping Cart';

        echo $this->renderView('cart', [
            'cartItems' => $cartItems,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle
        ]);
    }


    // --- AJAX Methods ---

    public function addToCart() {
        $this->validateCSRF();
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        $quantity = (int)$this->validateInput($_POST['quantity'] ?? 1, 'int');

        if (!$productId || $quantity < 1) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 400);
        }
        $product = $this->productModel->getById($productId);
        if (!$product) {
            return $this->jsonResponse(['success' => false, 'message' => 'Product not found'], 404);
        }

        // --- START REFACTOR: Separate logic for logged-in vs guest ---
        $currentQuantityInCart = 0;
        if ($this->isLoggedIn && $this->cartModel) {
             $items = $this->cartModel->getItems(); // Fetch current DB cart items
             foreach ($items as $item) {
                 if ($item['product_id'] == $productId) {
                      $currentQuantityInCart = $item['quantity'];
                      break;
                 }
             }
        } else {
            // Guest: Use session
             if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; } // Ensure session cart exists
            $currentQuantityInCart = $_SESSION['cart'][$productId] ?? 0;
        }
        // --- END REFACTOR ---

        $requestedTotalQuantity = $currentQuantityInCart + $quantity;

        // Check stock availability *before* adding
        if (!$this->productModel->isInStock($productId, $requestedTotalQuantity)) {
            $stockInfo = $this->productModel->checkStock($productId);
            $stockStatus = 'out_of_stock';
            $availableStock = $stockInfo ? max(0, $stockInfo['stock_quantity']) : 0;
            $message = $availableStock > 0 ? "Only {$availableStock} left in stock." : "Insufficient stock.";
            // Return current cart count even on failure
            $cartCount = $this->getCartCount(); // Get current count without modifying cart
            return $this->jsonResponse([
                'success' => false,
                'message' => $message,
                'cart_count' => $cartCount,
                'stock_status' => $stockStatus
            ], 400);
        }

        // Add item
        $success = false;
        if ($this->isLoggedIn && $this->cartModel) {
             $success = $this->cartModel->addItem($productId, $quantity); // addItem handles insert/update
        } else {
             // Guest: Update session
             if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
             $success = true; // Assume session update is successful
        }

        // Get updated cart count
        $cartCount = $this->getCartCount();
        $_SESSION['cart_count'] = $cartCount; // Store updated count in session

        // Check stock status *after* adding
        $stockInfo = $this->productModel->checkStock($productId);
        $stockStatus = 'in_stock';
        if ($stockInfo) {
             $finalCartQuantity = 0;
              if ($this->isLoggedIn && $this->cartModel) {
                  $items = $this->cartModel->getItems(); // Re-fetch items after update
                  foreach ($items as $item) { if ($item['product_id'] == $productId) {$finalCartQuantity = $item['quantity']; break;} }
              } else {
                  $finalCartQuantity = $_SESSION['cart'][$productId] ?? 0;
              }
             $remainingStock = $stockInfo['stock_quantity'] - $finalCartQuantity;

             if (!$stockInfo['backorder_allowed'] && $remainingStock <= 0) {
                  $stockStatus = 'out_of_stock';
             } elseif (isset($stockInfo['low_stock_threshold']) && $stockInfo['low_stock_threshold'] !== null && $remainingStock <= $stockInfo['low_stock_threshold']) {
                  $stockStatus = 'low_stock';
             }
        } else {
            $stockStatus = 'unknown';
        }

        // Log audit trail
        if ($success) {
             $this->logAuditTrail('cart_add', $this->userId, [
                 'product_id' => $productId,
                 'quantity' => $quantity,
                 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
             ]);
        }

        return $this->jsonResponse([
            'success' => $success,
            'message' => $success ? (htmlspecialchars($product['name']) . ' added to cart') : 'Failed to add item to cart.',
            'cart_count' => $cartCount,
            'stock_status' => $stockStatus
        ], $success ? 200 : 500); // Return 500 if DB operation failed
    }

    public function updateCart() {
        $this->validateCSRF();
        $updates = $_POST['updates'] ?? [];
        $stockErrors = [];
        $overallSuccess = true;

        // --- START REFACTOR: Separate logic for logged-in vs guest ---
        if ($this->isLoggedIn && $this->cartModel) {
             // Use transaction for multiple DB updates
             $this->beginTransaction();
             try {
                foreach ($updates as $productId => $quantity) {
                    $productId = $this->validateInput($productId, 'int');
                    $quantity = (int)$this->validateInput($quantity, 'int');
                    if ($productId === false || $quantity === false) { continue; }

                    if ($quantity > 0) {
                        if (!$this->productModel->isInStock($productId, $quantity)) {
                            $product = $this->productModel->getById($productId);
                            $stockErrors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " has insufficient stock";
                            $overallSuccess = false; // Mark failure but continue checking others
                            continue; // Skip updating this item
                        }
                        if (!$this->cartModel->updateItem($productId, $quantity)) {
                             $overallSuccess = false; // Mark failure on DB error
                             error_log("Failed to update item {$productId} to quantity {$quantity} in DB cart for user {$this->userId}");
                             // Optionally add a generic error message
                        }
                    } else {
                        if (!$this->cartModel->removeItem($productId)) {
                             $overallSuccess = false; // Mark failure on DB error
                             error_log("Failed to remove item {$productId} from DB cart for user {$this->userId}");
                        }
                    }
                }
                 if ($overallSuccess && empty($stockErrors)) {
                     $this->commit();
                 } else {
                     $this->rollback(); // Rollback if any stock error or DB update failure occurred
                 }
            } catch (Exception $e) {
                 $this->rollback();
                 $overallSuccess = false;
                 error_log("Error during logged-in cart update transaction: " . $e->getMessage());
            }
        } else {
            // Guest: Update session
             if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
            foreach ($updates as $productId => $quantity) {
                 $productId = $this->validateInput($productId, 'int');
                 $quantity = (int)$this->validateInput($quantity, 'int');
                 if ($productId === false || $quantity === false) { continue; }

                if ($quantity > 0) {
                    if (!$this->productModel->isInStock($productId, $quantity)) {
                        $product = $this->productModel->getById($productId);
                         $stockErrors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " has insufficient stock";
                        $overallSuccess = false; // Mark failure but continue
                        // Do not update session quantity if stock check fails
                        continue;
                    }
                    $_SESSION['cart'][$productId] = $quantity;
                } else {
                    unset($_SESSION['cart'][$productId]);
                }
            }
        }
        // --- END REFACTOR ---

        // Get updated cart count *after* all updates
        $cartCount = $this->getCartCount();
        $_SESSION['cart_count'] = $cartCount; // Store updated count

        $this->logAuditTrail('cart_update', $this->userId, [
            'updates' => $updates,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'had_stock_errors' => !empty($stockErrors),
            'db_update_failed' => !$overallSuccess && empty($stockErrors) // Log if DB update failed specifically
        ]);

        // Determine final success status
        $finalSuccess = $overallSuccess && empty($stockErrors);
        $message = empty($stockErrors)
                    ? ($finalSuccess ? 'Cart updated' : 'Failed to update some items in the cart.')
                    : 'Some items have insufficient stock. Cart partially updated.';


        return $this->jsonResponse([
            'success' => $finalSuccess,
            'message' => $message,
            'cart_count' => $cartCount,
            'errors' => $stockErrors // Return specific stock errors
        ], $finalSuccess ? 200 : ($overallSuccess ? 400 : 500)); // 400 for stock errors, 500 for DB errors
    }


    public function removeFromCart() {
        $this->validateCSRF();
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        if ($productId === false || $productId <= 0) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid product ID'], 400);
        }

        $success = false;
        // --- START REFACTOR: Separate logic ---
        if ($this->isLoggedIn && $this->cartModel) {
             $success = $this->cartModel->removeItem($productId);
        } else {
             // Guest: Remove from session
             if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                $success = true;
            } else {
                $success = false; // Item wasn't in session cart
            }
        }
        // --- END REFACTOR ---

        // Get updated count
        $cartCount = $this->getCartCount();
        $_SESSION['cart_count'] = $cartCount; // Store updated count

        if ($success) {
             $this->logAuditTrail('cart_remove', $this->userId, [
                 'product_id' => $productId,
                 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
             ]);
        } else {
             error_log("Failed attempt to remove product ID {$productId} from cart for user {$this->userId}");
        }


        return $this->jsonResponse([
            'success' => $success,
            'message' => $success ? 'Product removed from cart' : 'Product not found in cart or could not be removed.',
            'cart_count' => $cartCount
        ], $success ? 200 : 404); // 404 if item wasn't found
    }

     public function clearCart() {
        // Validate CSRF only if it's a POST request intended to clear via AJAX/Form
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();
        }

        $success = false;
        // --- START REFACTOR: Separate logic ---
        if ($this->isLoggedIn && $this->cartModel) {
             $success = $this->cartModel->clearCart();
        } else {
             // Guest: Clear session
             $_SESSION['cart'] = [];
             $_SESSION['cart_count'] = 0;
             $success = true;
        }
        // --- END REFACTOR ---

        // Set final cart count (will be 0)
        $cartCount = 0;
        $_SESSION['cart_count'] = $cartCount;

        if ($success) {
             $this->logAuditTrail('cart_clear', $this->userId, ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
        }

        // Respond based on request type
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->jsonResponse([
                'success' => $success,
                'message' => $success ? 'Cart cleared' : 'Failed to clear cart.',
                'cart_count' => $cartCount
            ], $success ? 200 : 500);
        } else {
             // For GET request (e.g., link click), redirect using BaseController helper
             $this->setFlashMessage($success ? 'Cart cleared successfully.' : 'Failed to clear cart.', $success ? 'success' : 'error');
             $this->redirect('index.php?page=cart'); // Redirect to cart page
        }
    }

     /**
      * Helper to get cart count consistently. Now the single source of truth.
      * Updates session count variable.
      *
      * @return int
      */
     private function getCartCount(): int {
         $count = 0;
         if ($this->isLoggedIn && $this->cartModel) {
             // Logged in: Fetch count from DB
             $count = $this->cartModel->getCartCount() ?? 0;
         } else {
             // Guest: Count items in session
             if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
             $count = array_sum($_SESSION['cart']); // Sum quantities
         }
         // Update session variable regardless of user state
         $_SESSION['cart_count'] = $count;
         return $count;
     }

     // Mini cart AJAX endpoint
     public function mini() {
         $items = $this->getCartItemsInternal(); // Use internal helper
         $subtotal = 0.0;
         foreach ($items as $item) {
             $subtotal += $item['subtotal'] ?? 0.0;
         }
         $cartCount = $this->getCartCount(); // Get count consistently

         return $this->jsonResponse([
             'success' => true,
             'items' => $items, // getCartItemsInternal now structures correctly
             'subtotal' => number_format($subtotal, 2), // Format for display
             'cart_count' => $cartCount
         ]);
     }


     // validateCartStock remains the same (uses internal helper)
     public function validateCartStock(): array {
         $errors = [];
         $cart = $this->getCartItemsInternal(); // Use internal helper

         if (empty($cart)) {
              return []; // Not an error if cart is empty
         }

         foreach ($cart as $item) {
             // Use $item['product']['id'] and $item['quantity']
             if (!$this->productModel->isInStock($item['product']['id'], $item['quantity'])) {
                 $errors[] = htmlspecialchars($item['product']['name'] ?? "Product ID {$item['product']['id']}") . " has insufficient stock";
             }
         }
         return $errors;
     }

      // getCartItems remains the same (uses internal helper)
     public function getCartItems(): array {
         return $this->getCartItemsInternal(); // Use internal helper
     }

     // Internal helper to get cart items structure consistently
     private function getCartItemsInternal(): array {
         $cartItems = [];
         // --- START REFACTOR: Separate logic ---
         if ($this->isLoggedIn && $this->cartModel) {
             $items = $this->cartModel->getItems(); // Assumes getItems returns joined product data
             foreach ($items as $item) {
                 $price = $item['price'] ?? 0;
                 $quantity = $item['quantity'] ?? 0;
                 $cartItems[] = [
                     // Structure expected by views/JS: nested product data
                     'product' => [
                         'id' => $item['product_id'], // Ensure correct ID key
                         'name' => $item['name'] ?? 'Unknown Product',
                         'price' => $price,
                         'image' => $item['image'] ?? '/images/placeholder.jpg',
                         'stock_quantity' => $item['stock_quantity'] ?? 0,
                         'backorder_allowed' => $item['backorder_allowed'] ?? false,
                         'low_stock_threshold' => $item['low_stock_threshold'] ?? null,
                         'category_name' => $item['category_name'] ?? null // Add if JOINed in getItems
                     ],
                     'quantity' => $quantity,
                     'subtotal' => $price * $quantity
                 ];
             }
         } else {
             // Guest: Fetch from session and product model
              if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
             foreach ($_SESSION['cart'] as $productId => $quantity) {
                 $product = $this->productModel->getById($productId); // Fetch details
                 if ($product) {
                      $price = $product['price'] ?? 0;
                     $cartItems[] = [
                         'product' => [ // Structure consistent with logged-in version
                             'id' => $product['id'],
                             'name' => $product['name'] ?? 'Unknown Product',
                             'price' => $price,
                             'image' => $product['image'] ?? '/images/placeholder.jpg',
                             'stock_quantity' => $product['stock_quantity'] ?? 0,
                             'backorder_allowed' => $product['backorder_allowed'] ?? false,
                             'low_stock_threshold' => $product['low_stock_threshold'] ?? null,
                             'category_name' => $product['category_name'] ?? null // Add category if needed/available
                         ],
                         'quantity' => $quantity,
                         'subtotal' => $price * $quantity
                     ];
                 } else {
                     // Product removed from DB, remove from session cart silently
                     unset($_SESSION['cart'][$productId]);
                 }
             }
         }
         // --- END REFACTOR ---
         return $cartItems;
     }

} // End of CartController class
```

---

**2. Updated File: `views/layout/header.php`**

*(Simplified cart count logic to rely solely on $_SESSION['cart_count'])*

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

---

**3. Updated File: `controllers/AccountController.php`**

*(Added rate limiting to `updateProfile`)*

```php
<?php

// Ensure all required files are loaded. BaseController should handle session start.
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/User.php';
// EmailService is included via BaseController
// SecurityMiddleware is included via BaseController (for static methods)
require_once __DIR__ . '/../controllers/CartController.php'; // Needed for mergeSessionCartOnLogin (ensure file exists)
require_once __DIR__ . '/../config.php'; // Needed for BASE_URL, SECURITY_SETTINGS

class AccountController extends BaseController {
    // private EmailService $emailService; // Removed - Inherited from BaseController
    private User $userModel;
    private Order $orderModel;
    private Quiz $quizModel;
    // Use config for expiry
    private int $resetTokenExpiry; // Set in constructor

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);
        // $this->emailService is initialized in parent constructor
        // Default to 1 hour (3600 seconds) if constant not defined
        $this->resetTokenExpiry = defined('PASSWORD_RESET_EXPIRY_SECONDS') ? PASSWORD_RESET_EXPIRY_SECONDS : 3600;
    }

    // --- Account Management Pages ---

    public function showDashboard() {
        try {
            $this->requireLogin(); // Checks login, session integrity, handles regeneration
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser(); // Get user data for view

            // Fetch data
            $recentOrders = $this->orderModel->getRecentByUserId($userId, 5);
            $quizResults = $this->quizModel->getResultsByUserId($userId); // Assuming this method exists

            // Data for the view
            $data = [
                'pageTitle' => 'My Account - The Scent',
                'recentOrders' => $recentOrders,
                'quizResults' => $quizResults,
                'user' => $currentUser, // Pass user data to the view
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-dashboard'
            ];
            // Render using BaseController method
            echo $this->renderView('account/dashboard', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
             $userId = $this->getUserId() ?? 'unknown';
             error_log("Account Dashboard error for user {$userId}: " . $e->getMessage());
             $this->setFlashMessage('Error loading dashboard. Please try again later.', 'error');
             $this->redirect('index.php?page=error'); // Redirect to a generic error page
        }
    }

    public function showOrders() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser();

            // Use BaseController validation helper
            $page = $this->validateInput($_GET['p'] ?? 1, 'int', ['min' => 1]) ?: 1;
            $perPage = 10; // Make configurable?

            // Use OrderModel methods updated previously
            $orders = $this->orderModel->getAllByUserId($userId, $page, $perPage);
            $totalOrders = $this->orderModel->getTotalOrdersByUserId($userId);
            $totalPages = ($totalOrders > 0 && $perPage > 0) ? ceil($totalOrders / $perPage) : 1;

            // Data for the view
            $data = [
                'pageTitle' => 'My Orders - The Scent',
                'orders' => $orders,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'user' => $currentUser, // Pass user data for layout/sidebar
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-orders'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/orders', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
             $userId = $this->getUserId() ?? 'unknown';
             error_log("Account Orders error for user {$userId}: " . $e->getMessage());
             $this->setFlashMessage('Error loading orders. Please try again later.', 'error');
             $this->redirect('index.php?page=error');
        }
    }

    public function showOrderDetails(int $orderId) {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser();

            if ($orderId <= 0) {
                 $this->setFlashMessage('Invalid order ID.', 'error');
                 // Use BaseController redirect helper
                 $this->redirect('index.php?page=account&section=orders');
                 return;
            }

            // Use method that checks user ID and fetches items
            $order = $this->orderModel->getByIdAndUserId($orderId, $userId);

            if (!$order) {
                error_log("User {$userId} failed to access order {$orderId}");
                $this->setFlashMessage('Order not found or access denied.', 'error');
                 http_response_code(404);
                 // Render 404 view via BaseController
                 $data = [
                     'pageTitle' => 'Order Not Found',
                     'user' => $currentUser, // Pass user if needed by 404 layout
                     'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                     'bodyClass' => 'page-404'
                 ];
                 echo $this->renderView('404', $data); // Use renderView helper
                 return;
            }

            // Data for the order details view
            $data = [
                // Use htmlspecialchars on dynamic output within the view itself is better practice
                'pageTitle' => "Order #" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . " - The Scent",
                'order' => $order, // Pass the fetched order data
                'user' => $currentUser, // Pass user data for layout/sidebar
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-order-details'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/order_details', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Order details error for user {$userId}, order {$orderId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading order details. Please try again later.', 'error');
            $this->redirect('index.php?page=account&action=orders');
        }
    }

    public function showProfile() {
        try {
            $this->requireLogin();
            $currentUser = $this->getCurrentUser(); // Use BaseController helper

            if (!$currentUser) {
                 // Should be caught by requireLogin, but safety check
                 $this->setFlashMessage('Could not load user profile data.', 'error');
                 $this->redirect('index.php?page=login');
                 return;
            }

            // Data for the view
            $data = [
                'pageTitle' => 'My Profile - The Scent',
                'user' => $currentUser,
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-profile'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/profile', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Show Profile error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading profile. Please try again later.', 'error');
            $this->redirect('index.php?page=error');
        }
    }

    public function updateProfile() {
        $userId = null; // Initialize for error logging
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $this->validateCSRF(); // Use BaseController method, checks POST token
            // --- START FIX: Add rate limiting ---
            $this->validateRateLimit('profile_update');
            // --- END FIX ---

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->setFlashMessage('Invalid request method.', 'warning');
                $this->redirect('index.php?page=account&section=profile');
                return;
            }

            // Validate inputs using SecurityMiddleware via BaseController helper
            $name = $this->validateInput($_POST['name'] ?? '', 'string', ['min' => 1, 'max' => 100]);
            $email = $this->validateInput($_POST['email'] ?? '', 'email');
            // Passwords are not validated here for format, only checked if new one meets requirements later
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? ''; // Need confirm password

            // Validation checks
            if ($name === false || trim($name) === '') { // Check validation result and empty string
                throw new Exception('Name is required and cannot be empty.');
            }
            if ($email === false) {
                 throw new Exception('A valid email address is required.');
            }

            $this->beginTransaction();

            try {
                // Check if email is taken by another user
                if ($this->userModel->isEmailTakenByOthers($email, $userId)) {
                    throw new Exception('Email address is already in use by another account.');
                }

                // Update basic info
                $this->userModel->updateBasicInfo($userId, $name, $email);
                $this->setFlashMessage('Profile information updated successfully.', 'success'); // Separate message

                // Update password logic
                $passwordChanged = false;
                if (!empty($newPassword)) {
                    if (empty($currentPassword)) {
                        throw new Exception('Current password is required to set a new password.');
                    }
                    // Verify current password using UserModel method
                    if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
                        $this->logSecurityEvent('profile_update_password_fail', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                        throw new Exception('Current password provided is incorrect.');
                    }

                    // Validate new password strength using helper
                    if (!$this->isPasswordStrong($newPassword)) {
                         // Fetch requirements from config for error message
                         $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                         $reqs = [];
                         if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                         if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                         if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                         $errMsg = sprintf('New password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                        throw new Exception($errMsg);
                    }

                    // Check if new passwords match
                    if ($newPassword !== $confirmPassword) {
                         throw new Exception('New passwords do not match.');
                    }

                    // Update password using UserModel method
                    $this->userModel->updatePassword($userId, $newPassword);
                    $this->setFlashMessage('Password updated successfully.', 'success'); // Add separate message for password
                    $passwordChanged = true;
                }

                $this->commit();

                // IMPORTANT: Update session data after successful update
                if (isset($_SESSION['user'])) {
                     $_SESSION['user']['name'] = $name;
                     $_SESSION['user']['email'] = $email;
                     // Note: Role is not updated here
                }

                $this->logAuditTrail('profile_update', $userId, ['name' => $name, 'email' => $email, 'password_changed' => $passwordChanged]);

                // Redirect back to profile page
                $this->redirect('index.php?page=account&section=profile');
                return;

            } catch (Exception $e) {
                $this->rollback();
                // Log the specific error during the transaction
                error_log("Profile update transaction error for user {$userId}: " . $e->getMessage());
                throw $e; // Rethrow to be caught by the outer catch
            }

        } catch (Exception $e) {
            $userId = $userId ?? ($this->getUserId() ?? 'unknown'); // Ensure userId is set for logging
            error_log("Profile update failed for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error'); // Show specific error message from exception
            $this->redirect('index.php?page=account&section=profile'); // Redirect back to profile page
        }
    }

    // --- Password Reset ---

    public function requestPasswordReset() {
        // Handle showing the form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $data = [
                 'pageTitle' => 'Forgot Password - The Scent',
                 'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                 'bodyClass' => 'page-forgot-password'
             ];
             echo $this->renderView('forgot_password', $data);
             return;
        }

        // --- POST logic ---
        $emailSubmitted = $_POST['email'] ?? ''; // For logging
        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('password_reset_request'); // Use BaseController method

            $email = $this->validateInput($emailSubmitted, 'email'); // Use BaseController helper

            if ($email === false) {
                 $this->logSecurityEvent('password_reset_invalid_email_format', ['submitted_email' => $emailSubmitted, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                 $this->setFlashMessage('If an account exists with that email, password reset instructions have been sent.', 'success');
                 $this->redirect('index.php?page=forgot_password');
                 return;
            }

            $this->beginTransaction();
            try {
                $user = $this->userModel->getByEmail($email);

                if ($user) {
                    $token = bin2hex(random_bytes(32)); // Generate secure token
                    $expiry = date('Y-m-d H:i:s', time() + $this->resetTokenExpiry);

                    $updated = $this->userModel->setResetToken($user['id'], $token, $expiry);

                    if ($updated) {
                        $resetLink = $this->getResetPasswordUrl($token);
                        // Use EmailService from BaseController
                        $this->emailService->sendPasswordReset($user, $token, $resetLink);
                        $this->logAuditTrail('password_reset_request', $user['id']);
                    } else {
                        error_log("Failed to set password reset token for user {$user['id']}. DB issue?");
                    }
                } else {
                    $this->logSecurityEvent('password_reset_nonexistent_email', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                }

                $this->commit();

            } catch (Exception $e) {
                $this->rollback();
                error_log("Password reset request internal DB/transaction error: " . $e->getMessage());
                // Fall through to generic success message
            }

            $this->setFlashMessage('If an account exists with that email, password reset instructions have been sent.', 'success');

        } catch (Exception $e) { // Catch CSRF or Rate Limit exceptions etc.
            error_log("Password reset request processing error: " . $e->getMessage());
            $this->logSecurityEvent('password_reset_request_error', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', 'email' => $emailSubmitted]);
            $this->setFlashMessage('An error occurred processing your request. Please try again.', 'error');
        }
        $this->redirect('index.php?page=forgot_password');
    }


    public function resetPassword() {
        // --- GET request: Show the password reset form ---
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $token = $this->validateInput($_GET['token'] ?? '', 'string', ['max' => 64]);

            if ($token === false || empty($token)) {
                $this->setFlashMessage('Invalid password reset link.', 'error');
                $this->redirect('index.php?page=forgot_password');
                return;
            }

            $user = $this->userModel->getUserByValidResetToken($token);
            if (!$user) {
                $this->logSecurityEvent('password_reset_invalid_token_on_get', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                $this->setFlashMessage('This password reset link is invalid or has expired. Please request a new one.', 'error');
                $this->redirect('index.php?page=forgot_password');
                return;
            }

            $data = [
                'pageTitle' => 'Reset Your Password - The Scent',
                'token' => $token,
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-reset-password'
            ];
            echo $this->renderView('reset_password', $data);
            return;
        }

        // --- POST logic: Process the password reset ---
        $token = $this->validateInput($_POST['token'] ?? '', 'string', ['max' => 64]);
        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('password_reset_attempt'); // Use BaseController method

            if ($token === false || empty($token)) {
                throw new Exception('Invalid or missing password reset token submitted.');
            }

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($password)) {
                throw new Exception('Password cannot be empty.');
            }
            if ($password !== $confirmPassword) {
                throw new Exception('Passwords do not match.');
            }
            if (!$this->isPasswordStrong($password)) {
                 $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                 $reqs = [];
                 if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                 if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                 if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                 $errMsg = sprintf('Password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                 throw new Exception($errMsg);
             }

            $this->beginTransaction();
            try {
                $user = $this->userModel->getUserByValidResetToken($token);
                if (!$user) {
                    $this->logSecurityEvent('password_reset_invalid_token_on_post', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    throw new Exception('This password reset link is invalid or has expired. Please request a new one.');
                }
                $this->userModel->resetPassword($user['id'], $password);
                $this->logAuditTrail('password_reset_complete', $user['id']);
                $this->commit();

                $this->setFlashMessage('Your password has been successfully reset. Please log in.', 'success');
                $this->redirect('index.php?page=login');
                return;

            } catch (Exception $e) {
                $this->rollback();
                error_log("Password reset transaction error for token {$token}: " . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Password reset processing error: " . $e->getMessage());
            $this->logSecurityEvent('password_reset_error', ['error' => $e->getMessage(), 'token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            $this->setFlashMessage($e->getMessage(), 'error');
            $this->redirect('index.php?page=reset_password&token=' . urlencode($token ?: ''));
            return;
        }
    }


    public function updateNewsletterPreferences() {
        $userId = null; // Initialize for logging
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $this->validateCSRF();
             // --- START FIX: Add rate limiting ---
             $this->validateRateLimit('profile_update'); // Reuse profile update limit or create 'pref_update'
             // --- END FIX ---

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 $this->setFlashMessage('Invalid request method.', 'warning');
                 $this->redirect('index.php?page=account&action=profile');
                 return;
            }

            // --- MODIFIED: Checkbox handling ---
            // Checkbox value is only sent if checked. Check its existence.
            $newsletterSubscribed = isset($_POST['newsletter_subscribed']); // True if checked, false if not present
            // --- END MODIFICATION ---

            $this->beginTransaction();
            try {
                // Assuming UserModel handles boolean correctly
                $this->userModel->updateNewsletterPreference($userId, $newsletterSubscribed);

                $action = $newsletterSubscribed ? 'newsletter_subscribe_profile' : 'newsletter_unsubscribe_profile';
                $this->logAuditTrail($action, $userId);

                $this->commit();
                $this->setFlashMessage('Newsletter preferences updated.', 'success');

            } catch (Exception $e) {
                $this->rollback();
                error_log("Newsletter preference update transaction error for user {$userId}: " . $e->getMessage());
                // Throw more specific or generic error as needed
                throw new Exception('Failed to update preferences. Database error.');
            }

        } catch (Exception $e) {
            $userId = $userId ?? ($this->getUserId() ?? 'unknown');
            error_log("Newsletter preference update failed for user {$userId}: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_update_fail', ['user_id' => $userId, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            $this->setFlashMessage('Failed to update newsletter preferences. Please try again.', 'error');
        }
        $this->redirect('index.php?page=account&section=profile');
    }

    // --- Authentication (Login / Register) ---

    public function login() {
        // --- GET request: Show the login form ---
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $data = [
                 'pageTitle' => 'Login - The Scent',
                 'csrfToken' => $this->getCsrfToken(), // CORRECTED: Use BaseController method
                 'bodyClass' => 'page-login bg-gradient-to-br from-light to-secondary/20'
             ];
             echo $this->renderView('login', $data);
             return;
        }

        // --- POST logic: Process login via AJAX ---
        $emailSubmitted = $_POST['email'] ?? ''; // For logging
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('login'); // Use BaseController method

            $email = $this->validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? '';

            if ($email === false || empty($password)) {
                $this->logSecurityEvent('login_invalid_input', ['email' => $emailSubmitted, 'ip' => $ipAddress]);
                throw new Exception('Invalid email or password format.');
            }

            $user = $this->userModel->getByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $userId = $user['id'] ?? null;
                $this->logSecurityEvent('login_failure', ['email' => $email, 'ip' => $ipAddress, 'user_id' => $userId]);
                throw new Exception('Invalid email or password.');
            }

            if (isset($user['status']) && $user['status'] === 'locked') {
                 $this->logSecurityEvent('login_attempt_locked', ['user_id' => $user['id'], 'email' => $email, 'ip' => $ipAddress]);
                 throw new Exception('Your account is currently locked. Please contact support.');
            }

            // --- Login Success ---
            $this->regenerateSession(); // Use BaseController protected method

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['user'] = [
                 'id' => $user['id'],
                 'name' => $user['name'],
                 'email' => $user['email'],
                 'role' => $_SESSION['user_role']
            ];
             $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
             $_SESSION['ip_address'] = $ipAddress;
             $_SESSION['last_login'] = time();
             $_SESSION['last_regeneration'] = time(); // Update regeneration time

             // Merge cart
             if (class_exists('CartController')) {
                 CartController::mergeSessionCartOnLogin($this->db, $user['id']);
                 // CartController now updates session count internally upon merge
             } else { error_log("CartController class not found, cannot merge session cart."); }

            $this->logAuditTrail('login_success', $user['id']);

            $redirectUrl = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'index.php?page=account&action=dashboard');
            unset($_SESSION['redirect_after_login']);

            $this->jsonResponse(['success' => true, 'redirect' => $redirectUrl]); // Exit

        } catch (Exception $e) {
            error_log("Login failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
             $statusCode = ($e->getCode() === 429) ? 429 : 401;
             $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], $statusCode); // Exit
        }
    }


     public function register() {
         // --- GET request: Show the registration form ---
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
              $data = [
                  'pageTitle' => 'Register - The Scent',
                  'csrfToken' => $this->getCsrfToken(), // CORRECTED: Use BaseController method
                  'bodyClass' => 'page-register'
              ];
              echo $this->renderView('register', $data);
             return;
         }

         // --- POST logic: Process registration via AJAX ---
        $emailSubmitted = $_POST['email'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateRateLimit('register');
            $this->validateCSRF();

            $email = $this->validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $name = $this->validateInput($_POST['name'] ?? '', 'string', ['min' => 2, 'max' => 100]);
            $newsletterPref = isset($_POST['newsletter_signup']) && $_POST['newsletter_signup'] === '1'; // Checkbox presence

            if ($email === false || empty($password) || $name === false) {
                 $this->logSecurityEvent('register_invalid_input', ['email' => $emailSubmitted, 'name_valid' => ($name !== false), 'ip' => $ipAddress]);
                 throw new Exception('Invalid input provided. Please check email, name, and password.');
            }
            if ($this->userModel->getByEmail($email)) {
                 throw new Exception('This email address is already registered.');
            }
            if (!$this->isPasswordStrong($password)) {
                 $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                 $reqs = [];
                 if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                 if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                 if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                 $errMsg = sprintf('Password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                 throw new Exception($errMsg);
             }
             if ($password !== $confirmPassword) {
                  throw new Exception('Passwords do not match.');
             }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                 error_log("Password hashing failed during registration: " . print_r(error_get_last(), true));
                 throw new Exception('Could not process password securely.');
            }

            $this->beginTransaction();
            try {
                $userData = [
                    'email' => $email,
                    'password' => $hashedPassword,
                    'name' => $name,
                    'role' => 'user',
                    'newsletter' => $newsletterPref // Pass preference to model
                ];
                $userId = $this->userModel->create($userData);

                 if (!$userId) {
                     throw new Exception('Failed to create user account in database.');
                 }

                 // Send welcome email
                 if ($this->emailService && method_exists($this->emailService, 'sendWelcome')) {
                     $emailSent = $this->emailService->sendWelcome($email, $name);
                     if (!$emailSent) {
                          error_log("Failed to send welcome email to {$email} for new user ID {$userId}, but registration succeeded.");
                     }
                 } else {
                      error_log("EmailService or sendWelcome method not available. Cannot send welcome email.");
                 }

                 $this->logAuditTrail('user_registered', $userId);
                 $this->commit();

                 $this->setFlashMessage('Registration successful! Please log in.', 'success');
                 $this->jsonResponse(['success' => true, 'redirect' => BASE_URL . 'index.php?page=login']); // Exit

            } catch (Exception $e) {
                 $this->rollback();
                 error_log("User creation transaction error: " . $e->getMessage());
                 throw new Exception('An error occurred during registration. Please try again.');
            }

        } catch (Exception $e) {
            error_log("Registration failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
            $this->logSecurityEvent('register_failure', ['email' => $emailSubmitted, 'error' => $e->getMessage(), 'ip' => $ipAddress]);
            $statusCode = ($e->getCode() === 429) ? 429 : 400;
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $statusCode); // Exit
        }
    }

    // --- Private Helper Methods ---

    /**
     * Checks if a password meets the defined security requirements.
     *
     * @param string $password The password to check.
     * @return bool True if strong, false otherwise.
     */
    private function isPasswordStrong(string $password): bool {
        $settings = SECURITY_SETTINGS['password'] ?? [];
        $minLength = $settings['min_length'] ?? 12;
        $reqSpecial = $settings['require_special'] ?? true;
        $reqNumber = $settings['require_number'] ?? true;
        $reqMixedCase = $settings['require_mixed_case'] ?? true;

        if (mb_strlen($password) < $minLength) { return false; }
        if ($reqMixedCase && (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password))) { return false; }
        if ($reqNumber && !preg_match('/[0-9]/', $password)) { return false; }
        // Ensure special char regex matches config.php or is appropriate
        if ($reqSpecial && !preg_match('/[\W_]/', $password)) { return false; } // Match any non-alphanumeric including underscore
        return true;
    }

    /**
     * Generates the full URL for the password reset link.
     *
     * @param string $token The password reset token.
     * @return string The absolute URL.
     */
    private function getResetPasswordUrl(string $token): string {
        $baseUrl = rtrim(BASE_URL, '/');
        return $baseUrl . "/index.php?page=reset_password&token=" . urlencode($token);
    }

} // End of AccountController class
```

---

**4. Updated File: `controllers/NewsletterController.php`**

*(Added rate limiting to `subscribe`)*

```php
<?php
// controllers/NewsletterController.php (Updated)

require_once __DIR__ . '/BaseController.php';
// EmailService is included via BaseController's include

class NewsletterController extends BaseController {
    // private $emailService; // Removed - Inherited from BaseController

    // Constructor now only needs PDO, EmailService is handled by parent
    public function __construct(PDO $pdo) { // Use type hint PDO $pdo
        parent::__construct($pdo); // Calls parent constructor
    }

    public function subscribe() {
        try {
            $this->validateCSRF();
            // --- START FIX: Add rate limiting ---
            $this->validateRateLimit('newsletter_subscribe'); // Use distinct action name
            // --- END FIX ---

            // Use validateInput from BaseController which uses SecurityMiddleware
            $email = $this->validateInput($_POST['email'] ?? null, 'email');
            if ($email === false) { // validateInput returns false on failure
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please provide a valid email address.'
                ], 400);
            }

            $this->beginTransaction();

            // Use $this->db for database operations
            $stmt = $this->db->prepare("
                SELECT id, status, unsubscribe_token
                FROM newsletter_subscribers
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch();

            $isNewSubscriber = false;
            $subscriberId = null;
            $token = null;

            if ($subscriber) {
                $subscriberId = $subscriber['id'];
                $token = $subscriber['unsubscribe_token']; // Get existing token
                if ($subscriber['status'] === 'active') {
                    $this->rollback(); // No changes needed
                    return $this->jsonResponse([
                        'success' => true, // Return true, but indicate already subscribed
                        'message' => 'This email is already subscribed.'
                    ]);
                }

                // Reactivate unsubscribed user & ensure token exists
                $token = $token ?: $this->generateUnsubscribeToken($email); // Generate if missing
                $updateStmt = $this->db->prepare("
                    UPDATE newsletter_subscribers
                    SET status = 'active',
                        updated_at = NOW(),
                        unsubscribed_at = NULL,
                        unsubscribe_token = ? -- Update token just in case
                    WHERE id = ?
                ");
                $updateStmt->execute([$token, $subscriber['id']]);
            } else {
                // Add new subscriber
                $isNewSubscriber = true;
                $token = $this->generateUnsubscribeToken($email); // Generate new token
                $insertStmt = $this->db->prepare("
                    INSERT INTO newsletter_subscribers (
                        email,
                        status,
                        ip_address,
                        unsubscribe_token,
                        created_at,
                        updated_at
                    ) VALUES (?, 'active', ?, ?, NOW(), NOW())
                ");
                $insertStmt->execute([
                    $email,
                    $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                    $token
                ]);
                $subscriberId = $this->db->lastInsertId();
            }

            // Send Welcome/Confirmation Email (using correct method)
            $unsubscribeLink = $this->getUnsubscribeUrl($email, $token);
            $emailSubject = $isNewSubscriber ? 'Welcome to The Scent Newsletter!' : 'You are now subscribed again!';
            $emailTemplate = 'newsletter_welcome'; // Use a consistent template name
            $emailData = [
                'email' => $email,
                'unsubscribe_link' => $unsubscribeLink,
                'is_reactivation' => !$isNewSubscriber
            ];

            // Use the inherited emailService instance and its sendEmail method
            $emailSent = $this->emailService->sendEmail(
                $email,
                $emailSubject,
                $emailTemplate,
                $emailData,
                false, // Not high priority
                null, // No specific user ID associated with newsletter signup itself
                'newsletter_welcome' // Email type for logging
            );

            if (!$emailSent) {
                 // Log but don't necessarily fail the whole subscription if email fails
                 error_log("Failed to send newsletter welcome email to {$email}");
            }

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Thank you for subscribing!'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter subscription error: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_subscribe_error', ['error' => $e->getMessage(), 'email' => $email ?? null]);
            // --- START FIX: Check if rate limit error ---
            $statusCode = ($e->getCode() === 429) ? 429 : 500;
            $errorMessage = ($e->getCode() === 429) ? $e->getMessage() : 'An error occurred. Please try again later.';
            return $this->jsonResponse([
                'success' => false,
                'message' => $errorMessage
            ], $statusCode);
            // --- END FIX ---
        }
    }

    public function unsubscribe() {
        try {
            // Validate inputs using BaseController method
            $email = $this->validateInput($_GET['email'] ?? null, 'email');
            $token = $this->validateInput($_GET['token'] ?? null, 'string', ['max' => 64]); // Basic validation

            if ($email === false || $token === false || empty($token)) {
                throw new Exception('Invalid unsubscribe link parameters.');
            }

            $this->beginTransaction();

            // Use $this->db
            $stmt = $this->db->prepare("
                UPDATE newsletter_subscribers
                SET status = 'unsubscribed',
                    unsubscribed_at = NOW(),
                    updated_at = NOW()
                WHERE email = ?
                AND unsubscribe_token = ?
                AND status = 'active' -- Only unsubscribe active users
            ");
            $stmt->execute([$email, $token]);

            // Check if any row was actually updated
            if ($stmt->rowCount() === 0) {
                 // Could be already unsubscribed, or invalid link
                 // Check if the user exists but is already unsubscribed
                 $checkStmt = $this->db->prepare("SELECT status FROM newsletter_subscribers WHERE email = ? AND unsubscribe_token = ?");
                 $checkStmt->execute([$email, $token]);
                 $currentStatus = $checkStmt->fetchColumn();
                 if ($currentStatus === 'unsubscribed') {
                     // Already done, treat as success? Or specific message?
                     $this->commit(); // Commit as no change needed
                     return $this->jsonResponse([
                         'success' => true, // Indicate success as they are unsubscribed
                         'message' => 'You are already unsubscribed.'
                     ]);
                 } else {
                    // Invalid link / email / token combo
                     throw new Exception('Invalid or expired unsubscribe link.');
                 }
            }

             // Log successful unsubscribe using BaseController method
             $this->logAuditTrail('newsletter_unsubscribe', null, ['email' => $email]);


            $this->commit();

            // Consider showing a simple confirmation page instead of JSON for GET request
            // For now, returning JSON as per original structure
            return $this->jsonResponse([
                'success' => true,
                'message' => 'You have been successfully unsubscribed.'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter unsubscribe error: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_unsubscribe_error', ['error' => $e->getMessage(), 'email' => $email ?? null]);

            // Return error JSON
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage() // Show specific error message
            ], 400);
        }
    }

    private function generateUnsubscribeToken(string $email): string {
         // Use a more secure method if possible, but HMAC is reasonable
         // Ensure NEWSLETTER_SECRET_KEY is defined and strong in config.php
         if (!defined('NEWSLETTER_SECRET_KEY')) {
             error_log("NEWSLETTER_SECRET_KEY is not defined in config.php!");
             // Fallback, but highly insecure
             return bin2hex(random_bytes(16));
         }
         return hash_hmac(
             'sha256',
             $email . microtime(), // Add microtime for more uniqueness
             NEWSLETTER_SECRET_KEY
         );
     }

     private function getUnsubscribeUrl(string $email, string $token): string {
         // Construct the unsubscribe URL using BASE_URL
         $baseUrl = rtrim(BASE_URL, '/');
         return $baseUrl . '/index.php?page=newsletter&action=unsubscribe&email=' . urlencode($email) . '&token=' . urlencode($token);
     }

    // Remove logEmail method - it's inherited from BaseController
    // Remove getWelcomeEmailContent - welcome email content generated via renderTemplate

    // getSubscriberCount uses $this->db (inherited) - OK
    public function getSubscriberCount() {
        $this->requireAdmin();
        $stmt = $this->db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
        return $stmt->fetchColumn();
    }

    // getRecentSubscribers uses $this->db (inherited) - OK
    public function getRecentSubscribers($limit = 10) {
        $this->requireAdmin();
        // Use prepare statement for limit
        $stmt = $this->db->prepare("
            SELECT email, created_at FROM newsletter_subscribers
            WHERE status = 'active' ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

} // End of NewsletterController class
```

---

**5. Updated File: `controllers/CheckoutController.php`**

*(Added rate limiting to `processCheckout` and `applyCouponAjax`)*

```php
<?php
// controllers/CheckoutController.php (Updated - Reworked showOrderConfirmation)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php'; // Now needed for StripeClient access
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../controllers/CouponController.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php';

// Assume Stripe SDK is loaded via Composer autoload in index.php
// require_once __DIR__ . '/../vendor/autoload.php'; // Ensure autoloader is included

class CheckoutController extends BaseController {
    private Product $productModel;
    private Order $orderModel;
    private InventoryController $inventoryController;
    private TaxController $taxController;
    private PaymentController $paymentController; // Store PaymentController instance
    private CouponController $couponController;
    // EmailService is inherited from BaseController

    // Updated Constructor to accept PaymentController
    public function __construct($pdo, PaymentController $paymentController) { // Added PaymentController dependency
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = $paymentController; // Store injected PaymentController
        $this->couponController = new CouponController($pdo);
    }

    /**
     * Display the checkout page.
     * Pre-fills address if available.
     * Calculates initial totals.
     */
    public function showCheckout() {
        // (Method content unchanged - it was already correct)
        $this->requireLogin();
        $userId = $this->getUserId();

        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
             $this->redirect('index.php?page=products');
             return;
        }

        $cartItems = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            // Validate stock before displaying checkout
            // Ensure 'product_id' and 'quantity' keys exist
            $productId = $item['product']['id'] ?? null; // Adjusted to match CartController structure
            $quantity = $item['quantity'] ?? 0;
            if (!$productId || $quantity <= 0) continue; // Skip if invalid

            if (!$this->productModel->isInStock($productId, $quantity)) {
                $this->setFlashMessage("Item '".htmlspecialchars($item['product']['name'] ?? 'Product')."' is out of stock. Please update your cart.", 'error');
                $this->redirect('index.php?page=cart');
                return;
            }
            $price = $item['product']['price'] ?? 0; // Adjusted to match CartController structure
            $lineSubtotal = $price * $quantity;
            $cartItems[] = [
                'product' => $item['product'], // Pass the whole product sub-array
                'quantity' => $quantity,
                'subtotal' => $lineSubtotal
            ];
            $subtotal += $lineSubtotal;
        }

        // Initial calculations (updated by JS/AJAX)
        $tax_rate_formatted = 'N/A'; // Placeholder
        $tax_amount = 0.0; // Placeholder
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;

        $userModel = new User($this->db);
        $userAddress = $userModel->getAddress($userId); // Fetches address data or null

        $csrfToken = $this->getCsrfToken();
        $bodyClass = 'page-checkout';
        $pageTitle = 'Checkout - The Scent';

        echo $this->renderView('checkout', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax_rate_formatted' => $tax_rate_formatted,
            'tax_amount' => $tax_amount,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle,
            'userAddress' => $userAddress ?? [] // Pass address data or empty array
        ]);
    }

    /**
     * AJAX endpoint to calculate tax based on country/state.
     */
    public function calculateTax() {
        // (Method content unchanged - it was already correct)
        $this->requireLogin(true); // AJAX request

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $country = $this->validateInput($data['country'] ?? null, 'string');
        $state = $this->validateInput($data['state'] ?? null, 'string');
        $subtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); // Get subtotal from client JS
        $discount = $this->validateInput($data['discount'] ?? 0, 'float'); // Get discount from client JS

        $subtotalAfterDiscount = max(0, $subtotal - $discount);

        if (empty($country)) {
           return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }
        // Allow zero subtotal for tax calculation (might be free items + shipping tax)
        // if ($subtotalAfterDiscount <= 0) {
        //      return $this->jsonResponse(['success' => false, 'error' => 'Cart is empty or invalid'], 400);
        // }

        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotalAfterDiscount, $country, $state); // Tax based on subtotal after discount
        $tax_rate = $this->taxController->getTaxRate($country, $state);
        $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount; // Estimate

        return $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate),
            'tax_amount' => number_format($tax_amount, 2), // Send formatted
            'total' => number_format($total, 2) // Send formatted estimate
        ]);
    }

    // Helper to get cart subtotal for logged-in user (unchanged)
    private function calculateCartSubtotal(): float {
         $userId = $this->getUserId();
         if (!$userId) return 0.0;
         $cartModel = new Cart($this->db, $userId);
         $items = $cartModel->getItems();
         $subtotal = 0.0;
         foreach ($items as $item) { $subtotal += ($item['product']['price'] ?? 0) * ($item['quantity'] ?? 0); } // Adjusted structure
         return (float)$subtotal;
    }

    /**
     * Processes the checkout form submission via AJAX.
     * Creates order, handles inventory, coupons, and initiates payment intent.
     */
    public function processCheckout() {
        // --- START FIX: Add rate limiting ---
        $this->validateRateLimit('checkout_submit');
        // --- END FIX ---
        $this->requireLogin(true); // AJAX request
        $this->validateCSRF();

        $userId = $this->getUserId();
        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems(); // Uses getCartItemsInternal which nests product data

        if (empty($items)) {
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart is empty.'], 400);
        }

        // --- Collect Cart Details ---
        $cartItemsForOrder = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $productId = $item['product']['id'] ?? null; // Access nested product ID
            $quantity = $item['quantity'] ?? 0;
            $price = $item['product']['price'] ?? 0; // Access nested price
            $name = $item['product']['name'] ?? 'Unknown Product';
            if (!$productId || $quantity <= 0) continue;

            $cartItemsForOrder[$productId] = ['quantity' => $quantity, 'price' => $price, 'name' => $name];
            $subtotal += $price * $quantity;
        }

        // --- Validate Shipping Input ---
        $requiredFields = [
            'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city',
            'shipping_state', 'shipping_zip', 'shipping_country'
        ];
        $missingFields = [];
        $postData = [];
        foreach ($requiredFields as $field) {
            $value = $_POST[$field] ?? '';
            if (empty(trim($value))) {
                $missingFields[] = ucwords(str_replace('_', ' ', $field));
            } else {
                 $type = (strpos($field, 'email') !== false) ? 'email' : 'string';
                 $validatedValue = $this->validateInput($value, $type);
                 if ($validatedValue === false) {
                     $missingFields[] = ucwords(str_replace('_', ' ', $field)) . " (Invalid)";
                 } else {
                     $postData[$field] = $validatedValue;
                 }
            }
        }
        if (!empty($missingFields)) {
             return $this->jsonResponse([
                 'success' => false,
                 'error' => 'Please fill required shipping fields: ' . implode(', ', $missingFields) . '.'
             ], 400);
        }
        $orderNotes = $this->validateInput($_POST['order_notes'] ?? null, 'string', ['max' => 1000]);

        // --- Validate Coupon (Again, server-side) ---
        $couponCode = $this->validateInput($_POST['applied_coupon_code'] ?? null, 'string');
        $coupon = null;
        $discountAmount = 0.0;
        if ($couponCode) {
            $validationResult = $this->couponController->validateCouponCodeOnly($couponCode, $subtotal);
            if ($validationResult['valid']) {
                 $coupon = $validationResult['coupon'];
                 if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
                     error_log("Checkout Warning: User {$userId} tried applying already used coupon '{$couponCode}' during final processing.");
                     $coupon = null;
                     $couponCode = null; // Clear the code if user already used it
                 } else {
                     $discountAmount = $this->couponController->calculateDiscount($coupon, $subtotal);
                 }
            } else {
                 // Coupon is invalid for some reason (expired, limit reached, etc.)
                 error_log("Checkout Warning: Coupon '{$couponCode}' became invalid during final checkout for user {$userId}. Message: " . ($validationResult['message'] ?? 'N/A'));
                 $couponCode = null; // Clear the code
                 $coupon = null;
            }
        }

        // --- Calculate Final Totals ---
        $subtotalAfterDiscount = max(0, $subtotal - $discountAmount);
        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax(
            $subtotalAfterDiscount,
            $postData['shipping_country'],
            $postData['shipping_state']
        );
        $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount;
        $total = max(0.50, round($total, 2)); // Ensure minimum payment amount for Stripe

        // --- Start Transaction ---
        try {
            $this->beginTransaction();

            // --- Re-validate Stock within Transaction ---
            $stockErrors = $this->validateCartStock($cartItemsForOrder); // Use internal helper structure
            if (!empty($stockErrors)) {
                $this->rollback();
                 return $this->jsonResponse([
                     'success' => false,
                     'error' => 'Some items went out of stock: ' . implode(', ', $stockErrors) . '. Please review your cart.'
                 ], 409); // 409 Conflict is appropriate here
            }

            // --- Create Order Record ---
            $orderData = [
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'coupon_code' => $coupon ? $coupon['code'] : null, // Store code only if coupon was valid and applied
                'coupon_id' => $coupon ? $coupon['id'] : null,
                'shipping_cost' => $shipping_cost,
                'tax_amount' => $tax_amount,
                'total_amount' => $total,
                'shipping_name' => $postData['shipping_name'],
                'shipping_email' => $postData['shipping_email'],
                'shipping_address' => $postData['shipping_address'],
                'shipping_city' => $postData['shipping_city'],
                'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'],
                'shipping_country' => $postData['shipping_country'],
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'order_notes' => $orderNotes,
                'payment_intent_id' => null // Initially null
            ];
            $orderId = $this->orderModel->create($orderData);
            if (!$orderId) throw new Exception("Failed to create order record.");

            // --- Create Order Items & Decrement Inventory ---
            $itemStmt = $this->db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($cartItemsForOrder as $productId => $itemData) {
                // Use price from the cart item array (which should reflect current price)
                $itemStmt->execute([$orderId, $productId, $itemData['quantity'], $itemData['price']]);
                // Decrement stock using InventoryController for audit trail
                if (!$this->inventoryController->updateStock($productId, -$itemData['quantity'], 'sale', $orderId)) {
                    // updateStock should throw exception on failure, caught below
                    throw new Exception("Failed to update inventory for product ID {$productId}");
                }
            }

            // --- Create Payment Intent ---
            $paymentResult = $this->paymentController->createPaymentIntent($total, 'usd', $orderId, $postData['shipping_email']);
            if (!$paymentResult['success'] || empty($paymentResult['client_secret']) || empty($paymentResult['payment_intent_id'])) {
                // Attempt to update order status to failed, but proceed to throw exception anyway
                $this->orderModel->updateStatus($orderId, 'payment_failed'); // Best effort update
                throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }
            $clientSecret = $paymentResult['client_secret'];
            $paymentIntentId = $paymentResult['payment_intent_id'];

            // --- Update Order with Payment Intent ID ---
            if (!$this->orderModel->updatePaymentIntentId($orderId, $paymentIntentId)) {
                 // This is critical - if we can't link PI, payment completion can't find the order
                 throw new Exception("Failed to link Payment Intent ID {$paymentIntentId} to Order ID {$orderId}.");
            }

            // --- Record Coupon Usage (Only if coupon was valid and applied) ---
            if ($coupon) {
                 if (!$this->couponController->recordUsage($coupon['id'], $orderId, $userId, $discountAmount)) {
                      // Log failure but don't necessarily fail the whole checkout if usage recording fails
                      error_log("Warning: Failed to record usage for coupon ID {$coupon['id']} on order ID {$orderId}. Check coupon_usage table.");
                 }
            }

            // --- Commit Transaction ---
            $this->commit();

            $this->logAuditTrail('order_pending_payment', $userId, [
                'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
            ]);

            // --- Return Client Secret and Order ID to Frontend ---
            return $this->jsonResponse([
                'success' => true,
                'orderId' => $orderId,
                'clientSecret' => $clientSecret
            ]);

        } catch (Exception $e) {
            $this->rollback(); // Rollback on any exception during the process
            error_log("Checkout processing error: User {$userId} - " . $e->getMessage());
            // Provide a more generic message to the user unless it's a specific stock issue
            $statusCode = 500; // Default server error
             if ($e->getCode() === 409) { $statusCode = 409; } // Conflict for stock issues
             if ($e->getCode() === 429) { $statusCode = 429; } // Rate limit

            $errorMessage = ($e->getCode() == 409 || strpos($e->getMessage(), 'stock') !== false)
                            ? $e->getMessage() // Show specific stock errors
                            : (($e->getCode() === 429) ? $e->getMessage() : 'An error occurred during checkout. Please try again.'); // Show rate limit message
             if ($e instanceof PDOException) { $errorMessage = 'A database error occurred. Please try again later.'; }

            return $this->jsonResponse([
                'success' => false,
                'error' => $errorMessage
            ], $statusCode);
        }
    }


    /**
     * Handles AJAX request from checkout page to validate and apply a coupon.
     */
    public function applyCouponAjax() {
         $this->requireLogin(true); // AJAX
         // --- START FIX: Add rate limiting ---
         $this->validateRateLimit('coupon_apply');
         // --- END FIX ---
         $this->validateCSRF();

         $json = file_get_contents('php://input');
         $data = json_decode($json, true);

         $code = $this->validateInput($data['code'] ?? null, 'string');
         $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); // Get subtotal from client
         $userId = $this->getUserId();

         if (!$code || $currentSubtotal === false || $currentSubtotal < 0) {
             return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400);
         }

         $validationResult = $this->couponController->validateCouponCodeOnly($code, $currentSubtotal);
         if (!$validationResult['valid']) {
             return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]);
         }
         $coupon = $validationResult['coupon'];

         if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
              return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']);
         }

         $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal);
         // Recalculate totals based *only* on discount for the estimate sent back to JS
         // JS will trigger a separate tax update call
         $subtotalAfterDiscount = max(0, $currentSubtotal - $discountAmount);
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
         // Exclude tax from this estimate, JS will handle it
         $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost;

         return $this->jsonResponse([
             'success' => true,
             'message' => 'Coupon applied successfully!',
             'coupon_code' => $coupon['code'],
             'discount_amount' => number_format($discountAmount, 2),
             'new_total_estimate' => number_format($newTotalEstimate, 2) // Estimate for UI update (without tax)
         ]);
    }

    /**
     * Displays the order confirmation page. (ROBUST IMPLEMENTATION)
     * Verifies payment success using Stripe Payment Intent ID from URL.
     * REMOVED reliance on session variables.
     */
    public function showOrderConfirmation() {
         // (Method content unchanged from previous robust version)
         $this->requireLogin(); // Ensure user is logged in
         $userId = $this->getUserId();

         // 1. Get Payment Intent ID from URL
         $paymentIntentId = $this->validateInput($_GET['payment_intent'] ?? null, 'string');

         if (!$paymentIntentId || !str_starts_with($paymentIntentId, 'pi_')) { // Basic format check
             $this->setFlashMessage('Invalid or missing payment confirmation identifier.', 'error');
             $this->redirect('index.php?page=account&section=orders'); // Use action=orders for consistency
             return;
         }

         try {
             // 2. Retrieve Payment Intent from Stripe
             // Ensure PaymentController and its Stripe client are available
             if (!$this->paymentController || !($stripeClient = $this->paymentController->getStripeClient())) {
                  error_log("Stripe client not available in CheckoutController::showOrderConfirmation.");
                  throw new Exception("Payment verification service temporarily unavailable. Please check your order history later.");
             }

             // Use Stripe SDK to fetch the Payment Intent
             // Assumes Stripe SDK is loaded via Composer autoload in index.php
             $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId, []);

             // 3. Verify Payment Intent Status
             if ($paymentIntent->status !== 'succeeded') {
                  error_log("Confirmation page accessed for non-succeeded PI: {$paymentIntentId}, Status: {$paymentIntent->status}");
                  // Provide helpful message based on status if possible
                  $message = match ($paymentIntent->status) {
                      'processing' => 'Your payment is still processing. We will notify you upon completion.',
                      'requires_payment_method', 'requires_action', 'requires_capture', 'requires_confirmation' => 'Payment was not completed successfully. Please check your orders or contact support.',
                      'canceled' => 'The payment was cancelled.',
                      default => 'Payment confirmation is pending or failed. Please check your orders.',
                  };
                  $this->setFlashMessage($message, 'warning');
                  $this->redirect('index.php?page=account&section=orders');
                  return;
             }

             // 4. Fetch Corresponding Order from DB using PI ID
             $order = $this->orderModel->getByPaymentIntentId($paymentIntentId);

             // 5. Validate Order Ownership and Existence
             if (!$order || $order['user_id'] !== $userId) {
                  error_log("Order not found or user mismatch for PI: {$paymentIntentId}, Order ID: " . ($order['id'] ?? 'N/A') . ", User ID: {$userId}");
                  // Log security event for potential access violation attempt
                  $this->logSecurityEvent('confirmation_access_denied', ['payment_intent_id' => $paymentIntentId, 'logged_in_user' => $userId, 'order_user' => $order['user_id'] ?? null]);
                  $this->setFlashMessage('Order details not found or access denied.', 'error');
                  $this->redirect('index.php?page=account&section=orders');
                  return;
             }

             // 6. (Optional but Recommended) Verify Order Status in DB is suitable
             // Allow for webhook delay - accept states the webhook would set on success
             $acceptableStatuses = ['processing', 'paid', 'shipped', 'delivered', 'completed']; // Add 'paid' if it's a valid post-payment status
             if (!in_array($order['status'], $acceptableStatuses)) {
                   // If status is still 'pending_payment', it means webhook might be delayed.
                   // Show confirmation anyway since Stripe confirmed success, but log it.
                   error_log("Confirmation Warning: PI {$paymentIntentId} succeeded, but order {$order['id']} status is '{$order['status']}'. Showing confirmation page, webhook may be delayed.");
             }

             // 7. Fetch full order details (with items) using the verified Order ID
             $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $userId); // Fetches items
             if (!$fullOrder || empty($fullOrder['items'])) {
                  // This shouldn't happen if order was found, but check anyway
                  error_log("Could not fetch full order details for confirmed order ID: {$order['id']}");
                  $this->setFlashMessage('Could not display full order details. Please check your order history.', 'error');
                  $this->redirect('index.php?page=account&section=orders');
                  return;
             }

             // 8. Render Confirmation View
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
             // Handle specific Stripe API errors (e.g., invalid PI ID, network issue)
             error_log("Stripe API error fetching Payment Intent {$paymentIntentId}: " . $e->getMessage());
             $this->setFlashMessage('Error verifying payment status. Please try again later or check your order history.', 'error');
             $this->redirect('index.php?page=account&action=orders');
         } catch (Exception $e) {
             // Handle other errors (DB issues, missing Stripe client, etc.)
             error_log("Error showing order confirmation for PI {$paymentIntentId}: " . $e->getMessage());
             $this->setFlashMessage('An unexpected error occurred while confirming your order. Please check your order history.', 'error');
             $this->redirect('index.php?page=account&action=orders');
         }
     }


    // --- Admin Method (Restored - Unchanged from previous working state) ---
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
         // (Method content unchanged - assuming it was already correct)
         $this->requireAdmin(true); // Indicate AJAX
         // Validate CSRF if this is triggered by a form/AJAX POST from admin panel
         // $this->validateCSRF(); // Consider adding if applicable

         $orderId = $this->validateInput($orderId, 'int');
         $status = $this->validateInput($status, 'string'); // Basic validation

         if (!$orderId || !$status) {
             return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400);
         }

         $order = $this->orderModel->getById($orderId); // Fetch by ID for admin
         if (!$order) {
            return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404);
         }

         // --- Add logic to check allowed status transitions ---
         $allowedTransitions = [
             'pending_payment' => ['paid', 'processing', 'cancelled', 'payment_failed'], // Allow direct to processing?
             'paid' => ['processing', 'cancelled', 'refunded'],
             'processing' => ['shipped', 'cancelled', 'refunded'],
             'shipped' => ['delivered', 'refunded'], // Consider returns separate?
             'delivered' => ['refunded', 'completed'], // Add completed?
             'payment_failed' => ['pending_payment', 'cancelled'], // Allow retry or cancel
             'cancelled' => [],
             'refunded' => [],
             'partially_refunded' => ['refunded'], // Allow full refund after partial
             'disputed' => ['refunded'], // Allow refunding after dispute
             'completed' => [], // Terminal state
         ];

         if (!isset($allowedTransitions[$order['status']]) || !in_array($status, $allowedTransitions[$order['status']])) {
              return $this->jsonResponse(['success' => false, 'error' => "Invalid status transition from '{$order['status']}' to '{$status}'."], 400);
         }
         // --- End Status Transition Check ---

         try {
             $this->beginTransaction();

             // Use OrderModel update method
             $updated = $this->orderModel->updateStatus($orderId, $status);
             if (!$updated) {
                 // Re-check if status is already set to prevent false failure
                 $currentOrder = $this->orderModel->getById($orderId);
                 if (!$currentOrder || $currentOrder['status'] !== $status) {
                     throw new Exception("Failed to update order status in DB.");
                 }
             }

             // Handle tracking info and email notification for 'shipped' status
             // Assuming $trackingInfo is passed correctly if status is 'shipped'
             if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) {
                 $trackingNumber = $this->validateInput($trackingInfo['number'], 'string', ['max' => 100]);
                 $carrier = $this->validateInput($trackingInfo['carrier'] ?? null, 'string', ['max' => 100]);

                 if ($trackingNumber) {
                      $trackingUpdated = $this->orderModel->updateTracking(
                          $orderId,
                          $trackingNumber,
                          $carrier
                      );

                      if ($trackingUpdated) {
                          $userModel = new User($this->db);
                          $user = $userModel->getById($order['user_id']);
                          // Fetch full order details for email context
                          $fullOrder = $this->orderModel->getByIdAndUserId($orderId, $order['user_id']); // Use correct method

                          if ($user && $fullOrder && $this->emailService && method_exists($this->emailService, 'sendShippingUpdate')) {
                               $this->emailService->sendShippingUpdate(
                                  $fullOrder, // Pass full order data
                                  $user,
                                  $trackingNumber,
                                  $carrier ?? ''
                              );
                          } elseif (!$user) {
                               error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}");
                          } elseif (!$fullOrder) {
                               error_log("Could not find full order details for shipping update email (Order ID: {$orderId})");
                          } else {
                               error_log("EmailService or sendShippingUpdate method not available for order {$orderId}");
                          }
                      } else {
                          error_log("Failed to update tracking info for order {$orderId}");
                      }
                 }
             }

             // TODO: Add more logic for other status changes (e.g., refund trigger, restock on cancel/refund)
             if ($status === 'cancelled' || $status === 'refunded') {
                  error_log("Order {$orderId} status changed to {$status}. Consider adding refund/restock logic here.");
             }

             $this->commit();

             $adminUserId = $this->getUserId(); // Assumes admin is logged in
             $this->logAuditTrail('order_status_update', $adminUserId, [
                  'order_id' => $orderId, 'new_status' => $status, 'tracking_provided' => ($status === 'shipped' && !empty($trackingNumber))
             ]);

             return $this->jsonResponse(['success' => true, 'message' => 'Order status updated successfully.']);

         } catch (Exception $e) {
             $this->rollback();
             error_log("Error updating order status for {$orderId}: " . $e->getMessage());
            return $this->jsonResponse(['success' => false, 'error' => 'Failed to update order status.'], 500);
         }
    }
    // --- End Admin Method (Restored) ---


    // --- Helper Methods ---
    /**
     * Internal helper to validate stock for items in the cart.
     * Expects $cartItems as [productId => ['quantity' => q, ...]]
     */
     private function validateCartStock(array $cartItems): array {
         // (Method content unchanged - it was already correct)
         $errors = [];
         if (empty($cartItems)) { return ['Cart is empty']; }

         foreach ($cartItems as $productId => $itemData) {
             // Ensure itemData has quantity key
             $quantity = $itemData['quantity'] ?? 0;
             if ($quantity <= 0) continue;

             $product = $this->productModel->getById($productId); // Fetch product details
             if (!$product) {
                 $errors[] = "Product ID {$productId} not found.";
                 continue;
             }
             if (!$this->productModel->isInStock($productId, $quantity)) {
                 $errors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " has insufficient stock";
             }
         }
         return $errors;
     }

} // End of CheckoutController class
```

---

**Summary & Conclusion:**

1.  **Cart Storage:** `CartController.php` has been refactored. Methods like `addItem`, `updateItem`, `removeItem`, `getItems`, `clearCart`, and `mini` now explicitly check `isLoggedIn()` and use either `$this->cartModel` (DB) or `$_SESSION['cart']` accordingly. The `getCartCount()` method is now the single source of truth and consistently updates `$_SESSION['cart_count']`. The `mergeSessionCartOnLogin` static method ensures the session count is updated from the DB after merging. `views/layout/header.php` now relies solely on `$_SESSION['cart_count']`, ensuring consistency.
2.  **Rate Limiting:** The `validateRateLimit()` call has been added to the beginning (after CSRF checks where applicable) of the following sensitive controller methods:
    *   `AccountController::updateProfile()` (using `'profile_update'`)
    *   `NewsletterController::subscribe()` (using `'newsletter_subscribe'`)
    *   `CheckoutController::processCheckout()` (using `'checkout_submit'`)
    *   `CheckoutController::applyCouponAjax()` (using `'coupon_apply'`)

The diff reviews confirmed that only the intended changes were made to address the specified issues, without removing unrelated features. These updates standardize the cart handling and apply rate limiting more consistently across the application.

---
Okay, I have performed a rigorous and meticulous line-by-line review of the `diff` output for `controllers/CartController.php`.

**Diff Review Findings for `controllers/CartController.php`:**

1.  **Constructor (`__construct`)**:
    *   Login check now correctly uses `$this->getUserId()` from `BaseController`.
    *   `Cart` model (`$this->cartModel`) initialization is now correctly conditional based on login status.
    *   Guest cart initialization (`$_SESSION['cart'] = []`) and session count initialization (`$_SESSION['cart_count'] = 0`) are now correctly placed in the `else` block for guests.
    *   The separate `initCart()` method was removed, and its logic integrated directly, which is a valid simplification.
    *   **Result:** Correctly refactored logic, no feature loss. **OK.**

2.  **`mergeSessionCartOnLogin()`**:
    *   Logic improved to *always* initialize `$cartModel` and update `$_SESSION['cart_count']` from the database *after* the merge attempt (or even if the session cart was empty), ensuring the session count accurately reflects the DB state post-login.
    *   Session cart (`$_SESSION['cart']`) is cleared *after* the merge logic, which is correct.
    *   **Result:** Improved reliability, no feature loss. **OK.**

3.  **`showCart()`**:
    *   Now uses the centralized `$this->getCartItemsInternal()` helper, simplifying the code.
    *   Correctly calculates the total based on the items returned by the helper.
    *   Calls `$this->getCartCount()` to ensure `$_SESSION['cart_count']` is accurate before rendering the view.
    *   CSRF token fetching uses the correct `BaseController` method (`$this->getCsrfToken()`).
    *   **Result:** Cleaner implementation using helper, correct count logic, no feature loss. **OK.**

4.  **`addToCart()`**:
    *   Logic for checking `currentQuantityInCart` is correctly separated for logged-in vs. guest.
    *   Stock check logic remains correct.
    *   Logic for *adding* the item is correctly separated (DB vs. Session).
    *   Calls `$this->getCartCount()` *once* after the add operation and updates `$_SESSION['cart_count']`.
    *   Stock status check *after* adding remains correct, including the more robust low stock threshold check.
    *   Audit logging is preserved.
    *   Returns appropriate JSON response including status code (500 if DB add fails).
    *   **Result:** Correctly refactored logic, centralized session count update, improved error handling. No feature loss. **OK.**

5.  **`updateCart()`**:
    *   Logic is correctly separated for logged-in vs. guest.
    *   Logged-in logic now correctly uses a database transaction with rollback on stock errors or DB update failures.
    *   Guest logic correctly updates the session, skipping items that fail stock checks.
    *   Calls `$this->getCartCount()` *once* at the end and updates `$_SESSION['cart_count']`.
    *   Audit logging is preserved.
    *   Returns more informative JSON response, including specific stock errors and appropriate status codes (200, 400, 500).
    *   **Result:** Significantly improved reliability for logged-in users, correct guest handling, centralized session count update. No feature loss. **OK.**

6.  **`removeFromCart()`**:
    *   Logic correctly separated for logged-in (DB) vs. guest (Session).
    *   Calls `$this->getCartCount()` *once* at the end and updates `$_SESSION['cart_count']`.
    *   Audit logging preserved.
    *   Returns appropriate JSON response with correct status code (404 if item not found).
    *   **Result:** Correctly refactored, centralized session count update. No feature loss. **OK.**

7.  **`clearCart()`**:
    *   Logic correctly separated for logged-in (DB) vs. guest (Session).
    *   Calls `$this->getCartCount()` (which will be 0) and updates `$_SESSION['cart_count']`.
    *   Audit logging preserved.
    *   Handles POST (JSON response) vs. GET (redirect) correctly.
    *   **Result:** Correctly refactored, centralized session count update. No feature loss. **OK.**

8.  **`getCartCount()`**:
    *   Refactored to *always* fetch from DB for logged-in or calculate `array_sum` for guests.
    *   *Always* updates `$_SESSION['cart_count']` before returning.
    *   Removed the `$forceDbCheck` parameter as it's no longer needed with this simplified, reliable approach.
    *   **Result:** Simpler, more reliable logic. No essential feature loss. **OK.**

9.  **`mini()`**:
    *   Correctly uses `$this->getCartItemsInternal()` and `$this->getCartCount()` for consistent data and count.
    *   JSON response structure remains correct.
    *   **Result:** Cleaner implementation. No feature loss. **OK.**

10. **`validateCartStock()` & `getCartItems()`**:
    *   Correctly simplified to call the internal helper `getCartItemsInternal()`.
    *   **Result:** Good refactoring. No feature loss. **OK.**

11. **`getCartItemsInternal()`**:
    *   Correctly separated logic for logged-in (DB fetch via `Cart::getItems`) vs. guest (Session fetch + `Product::getById`).
    *   Handles potential deletion of products for guest carts.
    *   **Crucially, returns the data structure with the nested `product` key** (`['product' => [...], 'quantity' => ..., 'subtotal' => ...]`) required by the views (`cart.php`, `checkout.php`) and JS (`fetchMiniCart`).
    *   **Result:** Centralized data fetching, consistent structure, handles edge cases. **OK.**

**Overall Conclusion:**

The line-by-line review confirms that the refactoring of `controllers/CartController.php` to address the cart storage inconsistency was implemented correctly and thoroughly.

*   The separation between Session (guest) and Database (logged-in) storage is now consistently enforced across all cart manipulation methods.
*   The `$_SESSION['cart_count']` variable is reliably updated by the `getCartCount()` method, which is called after each cart modification, making it the single source of truth for the header display.
*   The data structure returned by `getCartItemsInternal()` and `mini()` aligns with the requirements of the views and JavaScript.
*   Error handling (e.g., using transactions, returning appropriate status codes) has been improved.
*   No original features or functions were lost or accidentally omitted during the refactoring process.

The updated `controllers/CartController.php`, along with the updated `views/layout/header.php` and the addition of rate limiting calls in the relevant controllers (`AccountController`, `NewsletterController`, `CheckoutController`), successfully addresses the specified issues.

---
https://drive.google.com/file/d/15n5Z5mfWZR34xCedeNAAM6yIfm1-FWHU/view?usp=sharing, https://drive.google.com/file/d/16GeRFaJufIhZkc6PiZEGOXcdsOd7tBHu/view?usp=sharing, https://drive.google.com/file/d/1AfNx4eK9bILxDYig8-ns-QefvSvL-Pea/view?usp=sharing, https://drive.google.com/file/d/1BKHUJxNXkL0LaOqLYe_1iR9Pm0WcECAh/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221CERPFoP63KbINXZ9VdrkC3jYm7dwmQXO%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1Ii_et2GgZUMfA_HH1SZlP3hpxiVZap_w/view?usp=sharing, https://drive.google.com/file/d/1TP6uFvb5Z87SPbtrPU8C47ajbkz8IpDU/view?usp=sharing, https://drive.google.com/file/d/1TSiGE6QNnLaJaDR5GQ908SvOzPBx-QfB/view?usp=sharing, https://drive.google.com/file/d/1V5nBvD9m0BQtwPJMbM935eBHtzgq2q5x/view?usp=sharing, https://drive.google.com/file/d/1WQ0bQETlO_rxE59s7byblCDqhTEMu9MX/view?usp=sharing, https://drive.google.com/file/d/1YcBsA8LnyLr4A3LtI1MXKK1L5atjvaGs/view?usp=sharing, https://drive.google.com/file/d/1eT100kdTNr0wJTTQzw7e23BQoxUpDkXf/view?usp=sharing, https://drive.google.com/file/d/1kglCS-e_-vgfchd4cVZXgvHSPagKFcbT/view?usp=sharing, https://drive.google.com/file/d/1m7MjeSIYuSj0Tl4pOdV798bhZQIXSIRc/view?usp=sharing, https://drive.google.com/file/d/1vfgp4mJYTE_XUleYpOQpbzR_ioYZ9LdF/view?usp=sharing, https://drive.google.com/file/d/1zHS8LxhZDjUpgksZsQDaDi0lIXo-VuJa/view?usp=sharing
