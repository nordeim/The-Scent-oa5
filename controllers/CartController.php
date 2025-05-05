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
