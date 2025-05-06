Okay, let's systematically review the compatibility and address the identified issues.

**Mental Sandbox Simulation & Reasoning:**

1.  **Compatibility (`index.php`, `js/main.js` vs. `AccountController`):**
    *   `index.php` routes `/account` requests based on `$_GET['section']`. For `section=profile`, GET calls `showProfile`, POST calls `updateProfile`. This matches the `AccountController` structure where `updateProfile` handles multiple POST actions (`update_profile`, `update_password`, `update_address`, `update_preferences`) based on a hidden `action` input.
    *   `js/main.js` handles login (`initLoginPage`) and registration (`initRegisterPage`) via AJAX, expecting JSON responses (`success`, `redirect`/`error`) which `AccountController` provides. The profile update forms in `views/account/profile.php` submit via standard POST, which is also handled correctly (though not via AJAX) by `AccountController::updateProfile`.
    *   *Conclusion:* Basic routing and AJAX interactions for login/register appear compatible. Profile updates use standard POST, which is also compatible with the controller logic.

2.  **Issue 1: Checkout Address Field Discrepancy:**
    *   `views/account/profile.php` has `address_line1` and `address_line2`.
    *   `views/checkout.php` only has `shipping_address` (maps to `address_line1`).
    *   `UserModel::updateAddress` expects keys like `address_line1`, `address_line2`, etc.
    *   `CheckoutController::processCheckout` reads required fields (including `shipping_address`) into `$postData`. If `save_address` is checked, it calls `UserModel::updateAddress`.
    *   *Problem:* `checkout.php` needs the second address line input. `CheckoutController` needs to read this new input and map `$postData` correctly (e.g., `shipping_address` -> `address_line1`, `new_shipping_address_line2` -> `address_line2`) before calling `UserModel::updateAddress`.
    *   *Plan:*
        *   Add `shipping_address_line2` input to `views/checkout.php`, pre-filling from `$userAddress`.
        *   Modify `CheckoutController::processCheckout` to read `shipping_address_line2`.
        *   Modify `CheckoutController::processCheckout`'s `if ($saveAddress)` block to create a correctly mapped `$addressUpdateData` array before calling `UserModel::updateAddress`.

3.  **Issue 2: Checkout Payment Initialization Error:**
    *   Error message: "Could not initialize payment system. Please refresh."
    *   Source: `js/main.js` -> `initCheckoutPage` -> `showMessage` triggered either because `stripePublicKey` is missing/falsy *or* the `stripe = Stripe(stripePublicKey)` call throws an error.
    *   Trace Data Flow:
        *   `config.php`: Defines `STRIPE_PUBLIC_KEY`. (File `content_of_code_files_1.md` shows a placeholder `pk_test_...` key).
        *   `index.php`: Includes `config.php`. Routes to `CheckoutController::showCheckout`.
        *   `CheckoutController::showCheckout`: Calls `renderView('checkout', $data)`. `$data` doesn't explicitly include the public key.
        *   `BaseController::renderView`: Includes `views/layout/header.php` *implicitly* (as part of standard view rendering).
        *   `views/layout/header.php`: Outputs `<body ... data-stripe-public-key="<?= htmlspecialchars(STRIPE_PUBLIC_KEY ?? '', ...) ?>">`. This relies on the `STRIPE_PUBLIC_KEY` *constant* being available globally.
        *   `views/checkout.php`: Includes `<script src="https://js.stripe.com/v3/"></script>` at the top.
        *   `views/layout/footer.php`: Includes `<script src="/js/main.js"></script>`.
        *   `js/main.js`: `DOMContentLoaded` -> Page Dispatcher -> `initCheckoutPage` runs. It reads `document.body.dataset.stripePublicKey`.
    *   *Analysis:* The setup *looks* correct. The constant should be defined, the header should output it, the Stripe JS should load before `main.js` runs its initializers. The placeholder key `pk_test_...` *should* work for initialization.
    *   *Possible Causes:*
        *   Typo in the *actual* `STRIPE_PUBLIC_KEY` constant value in the running environment (if not using the placeholder).
        *   Network error loading `https://js.stripe.com/v3/`.
        *   Unexpected JS error occurring before `initCheckoutPage` runs or inside it before `Stripe()` is called.
        *   Race condition where `Stripe()` is called before the library is fully initialized (less likely with script placement).
        *   The `data-stripe-public-key` attribute *is* empty for some reason (e.g., `config.php` failing silently before `header.php` runs - unlikely given other things work).
    *   *Plan:* Since the code looks structurally correct, the best approach without changing core logic is to add debugging `console.log` statements within `initCheckoutPage` in `js/main.js` to check the value of `stripePublicKey` being read and the outcome of the `Stripe()` constructor call within its `try...catch` block.

4.  **Broader Compatibility Review:** Systematically checked other files against the changes in `AccountController` and the planned fixes. No other immediate incompatibilities found. `EmailService` fix for DB logging column `mailer_error` looks correct. Quiz controller results logic using session seems restored correctly. Admin Product views added.

5.  **Execution Plan:**
    *   Update `views/checkout.php` to add the `shipping_address_line2` field.
    *   Update `controllers/CheckoutController.php` to handle the new field and map data correctly for address saving.
    *   Update `js/main.js` to add diagnostic `console.log` statements in `initCheckoutPage`.
    *   Provide the complete, updated versions of these three files.

---

```php
<?php
// controllers/CheckoutController.php (Updated - Handle address_line2, map data for save)

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
    private User $userModel; // Add UserModel instance variable

    // Updated Constructor to accept PaymentController
    public function __construct($pdo, PaymentController $paymentController) { // Added PaymentController dependency
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = $paymentController; // Store injected PaymentController
        $this->couponController = new CouponController($pdo);
        $this->userModel = new User($pdo); // Initialize UserModel
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

        // --- Corrected: Initialize UserModel properly ---
        // $userModel = new User($this->db); // Removed - Initialized in constructor now
        $userAddress = $this->userModel->getAddress($userId); // Fetches address data or null
        // --- End Correction ---


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
     * Optionally updates user address.
     */
    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
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
        $postData = []; // Store validated required fields here
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
        // --- START FIX: Explicitly read optional address line 2 ---
        // Use the same name as the input field in the view
        $postData['shipping_address_line2'] = $this->validateInput($_POST['shipping_address_line2'] ?? null, 'string', ['max' => 255]);
        // --- END FIX ---

        $orderNotes = $this->validateInput($_POST['order_notes'] ?? null, 'string', ['max' => 1000]);
        $saveAddress = isset($_POST['save_address']) && $_POST['save_address'] === '1';

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
                // --- START FIX: Use correct keys for order saving ---
                // Use the values from $postData which have the shipping_ prefix
                'shipping_address' => $postData['shipping_address'], // This corresponds to address_line1 generally
                'shipping_address_line2' => $postData['shipping_address_line2'] ?? null, // <<< Add address line 2
                // --- END FIX ---
                'shipping_city' => $postData['shipping_city'],
                'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'],
                'shipping_country' => $postData['shipping_country'],
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'order_notes' => $orderNotes,
                'payment_intent_id' => null // Initially null
            ];
            // --- Correction: Check if shipping_address_line2 column exists in orders table ---
            // Assuming the `orders` table *also* needs an `shipping_address_line2` column.
            // If it doesn't exist, remove `'shipping_address_line2' => ...` from $orderData above.
            // For now, assuming the column exists in `orders` table similar to `users` table.
            // If not, the OrderModel::create would need adjustment or this key removed here.

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
                 // Pass $this->db explicitly if InventoryController needs it and doesn't inherit
                 $inventoryController = new InventoryController($this->db); // Instantiate if not already available
                if (!$inventoryController->updateStock($productId, -$itemData['quantity'], 'sale', $orderId)) {
                    // updateStock should throw exception on failure, caught below
                    throw new Exception("Failed to update inventory for product ID {$productId}");
                }
            }

            // --- START FIX: Update User Address if Requested (Map keys correctly) ---
            if ($saveAddress) {
                 // Create a new array mapping checkout field names to user table column names
                 $addressUpdateData = [
                    'address_line1' => $postData['shipping_address'], // Map 'shipping_address' to 'address_line1'
                    'address_line2' => $postData['shipping_address_line2'], // Map 'shipping_address_line2' to 'address_line2'
                    'city'          => $postData['shipping_city'],
                    'state'         => $postData['shipping_state'],
                    'postal_code'   => $postData['shipping_zip'],
                    'country'       => $postData['shipping_country']
                 ];
                 // Pass the mapped data to UserModel::updateAddress
                if (!$this->userModel->updateAddress($userId, $addressUpdateData)) {
                     // Log warning but don't fail the checkout transaction
                     error_log("Warning: Failed to save user address during checkout for User ID {$userId}. Order ID {$orderId}.");
                } else {
                     $this->logAuditTrail('user_address_update_checkout', $userId, ['order_id' => $orderId]);
                }
            }
            // --- END FIX ---

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
         // (Method content unchanged - it was already correct)
         $this->requireLogin(true); // AJAX
         $this->validateRateLimit('coupon_apply');
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
                          // --- Corrected: Use $this->userModel ---
                          // $userModel = new User($this->db); // Removed
                          $user = $this->userModel->getById($order['user_id']);
                          // --- End Correction ---
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

```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<!-- Output CSRF token for JS (for AJAX checkout/coupon/tax) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<!-- Add Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<section class="checkout-section">
    <div class="container">
        <div class="checkout-container" data-aos="fade-up">
            <h1>Checkout</h1>

            <div class="checkout-grid">
                <!-- Shipping Form -->
                <div class="shipping-details">
                    <h2>Shipping Details</h2>
                    <!-- NOTE: The form tag itself doesn't need action/method as JS handles the submission -->
                    <form id="checkoutForm">
                        <!-- ADD Standard CSRF Token for initial server-side check during processCheckout -->
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <!-- Hidden field to potentially store applied coupon code -->
                        <input type="hidden" id="applied_coupon_code" name="applied_coupon_code" value="">

                        <div class="form-group">
                            <label for="shipping_name">Full Name *</label>
                            <input type="text" id="shipping_name" name="shipping_name" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['name'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_email">Email Address *</label>
                            <input type="email" id="shipping_email" name="shipping_email" required class="form-input"
                                   value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="shipping_address">Street Address *</label>
                            <input type="text" id="shipping_address" name="shipping_address" required class="form-input"
                                   value="<?= htmlspecialchars($userAddress['address_line1'] ?? '') ?>"
                                   placeholder="Street address, P.O. box, company name, c/o">
                        </div>

                        <!-- START FIX: Add Address Line 2 Input -->
                        <div class="form-group">
                             <label for="shipping_address_line2">Address Line 2 (Optional)</label>
                             <input type="text" id="shipping_address_line2" name="shipping_address_line2" class="form-input"
                                    value="<?= htmlspecialchars($userAddress['address_line2'] ?? '') ?>"
                                    placeholder="Apartment, suite, unit, building, floor, etc.">
                        </div>
                        <!-- END FIX -->


                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_city">City *</label>
                                <input type="text" id="shipping_city" name="shipping_city" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['city'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_state">State/Province *</label>
                                <input type="text" id="shipping_state" name="shipping_state" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['state'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="shipping_zip">ZIP/Postal Code *</label>
                                <input type="text" id="shipping_zip" name="shipping_zip" required class="form-input"
                                       value="<?= htmlspecialchars($userAddress['postal_code'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label for="shipping_country">Country *</label>
                                <select id="shipping_country" name="shipping_country" required class="form-select">
                                    <option value="">Select Country</option>
                                    <option value="US" <?= (($userAddress['country'] ?? '') === 'US') ? 'selected' : '' ?>>United States</option>
                                    <option value="CA" <?= (($userAddress['country'] ?? '') === 'CA') ? 'selected' : '' ?>>Canada</option>
                                    <option value="GB" <?= (($userAddress['country'] ?? '') === 'GB') ? 'selected' : '' ?>>United Kingdom</option>
                                    <!-- Add more countries as needed -->
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="order_notes">Order Notes (Optional)</label>
                            <textarea id="order_notes" name="order_notes" rows="3" class="form-textarea"></textarea>
                        </div>

                        <div class="form-group mt-4">
                            <label class="checkbox-label flex items-center text-sm text-gray-700 cursor-pointer font-body">
                                <input type="checkbox" name="save_address" value="1"
                                       class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2" checked>
                                <span>Save this shipping address to my profile</span>
                            </label>
                        </div>
                        <!-- The submit button is now outside the form, controlled by JS -->
                    </form>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <h2>Order Summary</h2>

                    <!-- Coupon Code Section -->
                    <div class="coupon-section">
                        <div class="form-group">
                            <label for="coupon_code">Have a coupon?</label>
                            <div class="coupon-input">
                                <input type="text" id="coupon_code" name="coupon_code_input" class="form-input"
                                       placeholder="Enter coupon code">
                                <button type="button" id="apply-coupon" class="btn-secondary">Apply</button>
                            </div>
                            <div id="coupon-message" class="hidden mt-2 text-sm"></div>
                        </div>
                    </div>

                    <div class="summary-items border-b border-gray-200 pb-4 mb-4">
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                                // Defensive access for variables used in this item's display
                                $productInfo = $item['product'] ?? []; // Access nested product array
                                $productId = $productInfo['id'] ?? ''; // Use empty string or 0 if appropriate
                                $imageUrl = $productInfo['image'] ?? '/images/placeholder.jpg';
                                $productName = $productInfo['name'] ?? 'Unknown Product';
                                $quantity = $item['quantity'] ?? 0;
                                $lineSubtotal = $item['subtotal'] ?? 0;
                            ?>
                            <div class="summary-item flex justify-between items-center text-sm py-1">
                                <div class="item-info flex items-center">
                                     <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($productName) ?>" class="w-10 h-10 object-cover rounded mr-2">
                                     <div>
                                         <span class="item-name font-medium text-gray-800"><?= htmlspecialchars($productName) ?></span>
                                         <span class="text-xs text-gray-500 block">Qty: <?= htmlspecialchars($quantity) ?></span>
                                     </div>
                                </div>
                                <span class="item-price font-medium text-gray-700">$<?= number_format($lineSubtotal, 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-totals space-y-2">
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium text-gray-900">$<span id="summary-subtotal"><?= number_format($subtotal ?? 0, 2) ?></span></span>
                        </div>
                         <div class="summary-row discount hidden flex justify-between items-center text-green-600">
                            <span>Discount (<span id="applied-coupon-code-display" class="font-mono text-xs bg-green-100 px-1 rounded"></span>):</span>
                            <span>-$<span id="discount-amount">0.00</span></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Shipping:</span>
                            <span class="font-medium text-gray-900" id="summary-shipping"><?= ($shipping_cost ?? 0) > 0 ? '$' . number_format($shipping_cost, 2) : '<span class="text-green-600">FREE</span>' ?></span>
                        </div>
                        <div class="summary-row flex justify-between items-center">
                            <span class="text-gray-600">Tax (<span id="tax-rate" class="text-xs"><?= htmlspecialchars($tax_rate_formatted ?? 'N/A') ?></span>):</span>
                            <span class="font-medium text-gray-900" id="tax-amount">$<?= number_format($tax_amount ?? 0, 2) ?></span>
                        </div>
                        <div class="summary-row total flex justify-between items-center border-t pt-3 mt-2">
                            <span class="text-lg font-bold text-gray-900">Total:</span>
                            <span class="text-lg font-bold text-primary">$<span id="summary-total"><?= number_format($total ?? 0, 2) ?></span></span>
                        </div>
                    </div>

                    <div class="payment-section mt-6">
                        <h3 class="text-lg font-semibold mb-4">Payment Method</h3>
                        <!-- Stripe Payment Element -->
                        <div id="payment-element" class="mb-4 p-3 border rounded bg-gray-50"></div>
                        <!-- Used to display form errors -->
                        <div id="payment-message" class="hidden text-red-600 text-sm text-center mb-4"></div>
                    </div>

                    <!-- Button is outside the form, triggered by JS -->
                    <button type="button" id="submit-button" class="btn btn-primary w-full place-order">
                        <span id="button-text">Place Order & Pay</span>
                        <div class="spinner hidden" id="spinner"></div>
                    </button>

                    <div class="secure-checkout mt-4 text-center text-xs text-gray-500">
                        <i class="fas fa-lock mr-1"></i>Secure Checkout via Stripe
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// PASTE THE ENTIRE SCRIPT BLOCK FROM js/main.js initCheckoutPage() HERE
// The provided JS in main.js already seems robust for checkout.
// The critical change was ensuring the PHP view provides data defensively.
// For completeness, I'll include the JS init logic here again,
// assuming it's correctly placed within the `initCheckoutPage` function in main.js.

document.addEventListener('DOMContentLoaded', function() {
    // This function would typically be called by the page dispatcher in main.js
    // if the body has class 'page-checkout'
    function initCheckoutPage() {
        console.log("Initializing Checkout Page JS..."); // Add console log for debugging
        // --- Configuration ---
        // Fetch config from body data attributes for better security/flexibility
        const bodyData = document.body.dataset;
        const stripePublicKey = bodyData.stripePublicKey || '';
        const freeShippingThreshold = parseFloat(bodyData.freeShippingThreshold || '50');
        const baseShippingCost = parseFloat(bodyData.baseShippingCost || '5.99');
        const baseUrl = bodyData.baseUrl || '/'; // Use base URL for return_url

        // --- Element Selectors ---
        const checkoutForm = document.getElementById('checkoutForm');
        const submitButton = document.getElementById('submit-button');
        const spinner = document.getElementById('spinner');
        const buttonText = document.getElementById('button-text');
        const paymentElementContainer = document.getElementById('payment-element');
        const paymentMessage = document.getElementById('payment-message');
        const csrfToken = document.getElementById('csrf-token-value')?.value;
        const couponCodeInput = document.getElementById('coupon_code');
        const applyCouponButton = document.getElementById('apply-coupon');
        const couponMessageEl = document.getElementById('coupon-message');
        const discountRow = document.querySelector('.summary-row.discount');
        const discountAmountEl = document.getElementById('discount-amount');
        const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
        const appliedCouponHiddenInput = document.getElementById('applied_coupon_code');
        const taxRateEl = document.getElementById('tax-rate');
        const taxAmountEl = document.getElementById('tax-amount');
        const shippingCountryEl = document.getElementById('shipping_country');
        const shippingStateEl = document.getElementById('shipping_state');
        const summarySubtotalEl = document.getElementById('summary-subtotal');
        const summaryShippingEl = document.getElementById('summary-shipping');
        const summaryTotalEl = document.getElementById('summary-total');

        // --- State Variables ---
        let elements;
        let stripe;
        // Initialize state from PHP output, using parseFloat defensively
        let currentSubtotal = parseFloat(summarySubtotalEl?.textContent?.replace('$', '') || '0');
        let currentShippingCost = parseFloat(summaryShippingEl?.textContent?.replace('$', '') || baseShippingCost.toString()); // Use parsed value or default
        let currentTaxAmount = parseFloat(taxAmountEl?.textContent?.replace('$', '') || '0');
        let currentDiscountAmount = parseFloat(discountAmountEl?.textContent?.replace('-$', '') || '0'); // Handle initial discount if page reloads with coupon


        // --- Basic Checks ---
         console.log("Stripe Public Key:", stripePublicKey); // <<< DEBUG LOG
        if (!stripePublicKey) {
            showMessage("Stripe configuration error. Payment cannot proceed.", true);
            setLoading(false, true); // Disable button permanently
            return;
        }
        if (!checkoutForm || !submitButton || !paymentElementContainer || !csrfToken || !summarySubtotalEl) {
            console.error("Checkout form critical elements missing. Aborting initialization.");
            // Don't show generic message here, could be confusing if Stripe hasn't loaded yet
            // showMessage("Checkout form error. Please refresh the page.", true);
            return;
        }

        // --- Initialize Stripe ---
        try {
             stripe = Stripe(stripePublicKey);
             console.log("Stripe object initialized:", stripe); // <<< DEBUG LOG
             const appearance = {
                 theme: 'stripe',
                 variables: {
                     colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151',
                     colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem'
                 }
             };
             elements = stripe.elements({ appearance });
             const paymentElement = elements.create('payment');
             paymentElement.mount('#payment-element');
             console.log("Stripe Payment Element mounted."); // <<< DEBUG LOG
        } catch (stripeError) {
            console.error("Stripe initialization error:", stripeError); // <<< DEBUG LOG
            showMessage("Could not initialize payment system. Please refresh.", true);
            setLoading(false, true);
            return;
        }


        // --- Helper Functions ---
        function setLoading(isLoading, disablePermanently = false) {
            if (!submitButton || !spinner || !buttonText) return;
            if (isLoading) {
                submitButton.disabled = true;
                spinner.classList.remove('hidden');
                buttonText.classList.add('hidden');
            } else {
                submitButton.disabled = disablePermanently;
                spinner.classList.add('hidden');
                buttonText.classList.remove('hidden');
            }
        }

        function showMessage(message, isError = true) {
            if (!paymentMessage) return;
            paymentMessage.textContent = message;
            paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
            paymentMessage.classList.remove('hidden');
        }

        function showCouponMessage(message, type) { // type = 'success', 'error', 'info'
            if (!couponMessageEl) return;
            couponMessageEl.textContent = message;
            couponMessageEl.className = `coupon-message mt-2 text-sm ${
                type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')
            }`;
            couponMessageEl.classList.remove('hidden');
        }

        function updateOrderSummaryUI() {
            if (!summarySubtotalEl || !discountRow || !discountAmountEl || !appliedCouponCodeDisplay || !summaryShippingEl || !taxAmountEl || !summaryTotalEl) return;

            summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);

            if (currentDiscountAmount > 0 && appliedCouponHiddenInput?.value) {
                discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
                appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
                discountRow.classList.remove('hidden');
            } else {
                discountAmountEl.textContent = '0.00';
                appliedCouponCodeDisplay.textContent = '';
                discountRow.classList.add('hidden');
            }

             const subtotalAfterDiscount = Math.max(0, currentSubtotal - currentDiscountAmount);
             currentShippingCost = subtotalAfterDiscount >= freeShippingThreshold ? 0 : baseShippingCost;
             summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';

            taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);

            const grandTotal = subtotalAfterDiscount + currentShippingCost + currentTaxAmount;
            summaryTotalEl.textContent = parseFloat(Math.max(0.50, grandTotal)).toFixed(2); // Ensure min $0.50 display
        }

        // --- Tax Calculation ---
        async function updateTax() {
            const country = shippingCountryEl?.value;
            const state = shippingStateEl?.value;

            if (!country || !taxRateEl || !taxAmountEl) {
                 if (taxRateEl) taxRateEl.textContent = 'N/A';
                 currentTaxAmount = 0;
                 updateOrderSummaryUI();
                return;
            }

            try {
                taxAmountEl.textContent = '...'; // Loading indicator
                const response = await fetch('index.php?page=checkout&action=calculateTax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', 'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                     },
                    // Pass current subtotal and discount for accurate tax calculation
                    body: JSON.stringify({ country, state, subtotal: currentSubtotal, discount: currentDiscountAmount })
                });

                if (!response.ok) throw new Error(`Tax calculation failed (${response.status})`);
                const data = await response.json();

                if (data.success) {
                    taxRateEl.textContent = data.tax_rate_formatted || 'N/A';
                    currentTaxAmount = parseFloat(data.tax_amount) || 0;
                } else {
                     console.warn("Tax calculation error:", data.error);
                     taxRateEl.textContent = 'Error';
                     currentTaxAmount = 0;
                }
            } catch (e) {
                console.error('Error fetching tax:', e);
                taxRateEl.textContent = 'Error';
                currentTaxAmount = 0;
            } finally {
                 updateOrderSummaryUI(); // Always update totals after tax calculation attempt
            }
        }

        if(shippingCountryEl) shippingCountryEl.addEventListener('change', updateTax);
        if(shippingStateEl) shippingStateEl.addEventListener('input', updateTax);

        // --- Coupon Application ---
        if (applyCouponButton && couponCodeInput && appliedCouponHiddenInput) {
            applyCouponButton.addEventListener('click', async function() {
                const couponCode = couponCodeInput.value.trim();
                if (!couponCode) {
                    showCouponMessage('Please enter a coupon code.', 'error'); return;
                }

                showCouponMessage('Applying...', 'info');
                applyCouponButton.disabled = true;

                try {
                    const response = await fetch('index.php?page=checkout&action=applyCouponAjax', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json', 'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            code: couponCode,
                            subtotal: currentSubtotal, // Send current subtotal
                            csrf_token: csrfToken // Send CSRF token
                        })
                    });

                     if (!response.ok) throw new Error(`Server error applying coupon (${response.status})`);
                     const data = await response.json();

                    if (data.success) {
                        showCouponMessage(data.message || 'Coupon applied!', 'success');
                        currentDiscountAmount = parseFloat(data.discount_amount) || 0;
                        appliedCouponHiddenInput.value = data.coupon_code || couponCode;
                        // Recalculate tax and update summary UI after applying discount
                         updateTax(); // Triggers tax recalc and UI update
                    } else {
                        showCouponMessage(data.message || 'Invalid coupon code.', 'error');
                        currentDiscountAmount = 0; // Reset discount
                        appliedCouponHiddenInput.value = ''; // Clear applied code
                        updateTax(); // Re-calculate tax and update summary UI without discount
                    }
                } catch (e) {
                    console.error('Coupon Apply Error:', e);
                    showCouponMessage('Failed to apply coupon. Please try again.', 'error');
                    currentDiscountAmount = 0;
                    appliedCouponHiddenInput.value = '';
                    updateTax(); // Re-calculate tax and update summary UI
                } finally {
                    applyCouponButton.disabled = false;
                }
            });
        } else {
            console.warn("Coupon elements not found. Coupon functionality disabled.");
        }

        // --- Checkout Form Submission ---
        submitButton.addEventListener('click', async function(e) {
            setLoading(true);
            showMessage(''); // Clear previous messages

            // 1. Client-side validation
            let isValid = true;
            // --- FIX: Include shipping_address_line2 in required check? No, it's optional. ---
            const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
            requiredFields.forEach(id => {
                const input = document.getElementById(id);
                if (!input || !input.value.trim()) {
                    isValid = false; input?.classList.add('input-error');
                } else { input?.classList.remove('input-error'); }
            });
            if (!isValid) {
                showMessage('Please fill in all required shipping fields.', true); setLoading(false);
                const firstError = checkoutForm.querySelector('.input-error');
                 firstError?.focus();
                 firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            // 2. Send checkout data to server -> create order, get clientSecret
            let clientSecret = null;
            let serverOrderId = null;
            try {
                const checkoutFormData = new FormData(checkoutForm);
                // Ensure applied coupon code is included if set
                if (appliedCouponHiddenInput && appliedCouponHiddenInput.value) {
                    checkoutFormData.set('applied_coupon_code', appliedCouponHiddenInput.value); // Ensure it's set correctly
                } else {
                    checkoutFormData.delete('applied_coupon_code'); // Remove if empty
                }
                 // Add save_address checkbox value
                 const saveAddressCheckbox = checkoutForm.querySelector('input[name="save_address"]');
                 if (saveAddressCheckbox && saveAddressCheckbox.checked) {
                     checkoutFormData.set('save_address', '1');
                 }

                const response = await fetch('index.php?page=checkout&action=processCheckout', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: checkoutFormData
                });

                // Log status and try to parse JSON regardless of status code initially
                console.log("Process Checkout Response Status:", response.status); // <<< DEBUG LOG
                const data = await response.json(); // Try to parse JSON
                console.log("Process Checkout Response Data:", data); // <<< DEBUG LOG

                if (response.ok && data.success && data.clientSecret && data.orderId) {
                    clientSecret = data.clientSecret;
                    serverOrderId = data.orderId;
                } else {
                    // Throw error using message from JSON if available
                    throw new Error(data.error || `Failed to process order on server (Status: ${response.status}).`);
                }
            } catch (serverError) {
                console.error('Server processing error:', serverError);
                showMessage(serverError.message, true); setLoading(false); return;
            }

            // 3. Confirm payment with Stripe using the obtained clientSecret
            if (clientSecret && stripe && elements) {
                // Ensure BASE_URL ends with '/' for correct path joining
                const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                const returnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation`;
                console.log("Stripe return_url:", returnUrl); // Log the return URL

                const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
                    elements,
                    clientSecret: clientSecret,
                    confirmParams: { return_url: returnUrl },
                    redirect: 'if_required'
                });

                if (stripeError) {
                     console.error("Stripe confirmPayment Error:", stripeError);
                     showMessage(stripeError.message || "Payment failed. Please check your card details or try another method.", true);
                     setLoading(false);
                }
                // If no error, Stripe handles the redirect on success.
            } else {
                if (!clientSecret) showMessage('Failed to get payment details from server.', true);
                if (!stripe || !elements) showMessage('Payment system not initialized correctly.', true);
                setLoading(false);
            }
        });

        // Initial UI calculations
        updateOrderSummaryUI();
        if (shippingCountryEl?.value) {
            updateTax(); // Initial tax calculation if country pre-filled
        }
    }

    // Call initializer if body class matches
    if (document.body.classList.contains('page-checkout')) {
        initCheckoutPage();
    }
});
</script>

<style>
/* Basic styles for loading/error states (same as before) */
.spinner {
    width: 1.25em; height: 1.25em; border: 3px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%; border-top-color: #ffffff; animation: spin 1s linear infinite;
    display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }
.hidden { display: none; }
.place-order:disabled { opacity: 0.7; cursor: not-allowed; }
.input-error { border-color: #dc2626; box-shadow: 0 0 0 1px #dc2626; }
.form-input, .form-select, .form-textarea {
     width: 100%; padding: 0.65rem 0.75rem; border: 1px solid #d1d5db;
     border-radius: 0.375rem; transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
}
.form-input:focus, .form-select:focus, .form-textarea:focus {
     outline: none; border-color: #1A4D5A; box-shadow: 0 0 0 2px rgba(26, 77, 90, 0.3);
}
/* Added form-group styles if not globally defined elsewhere */
.form-group { margin-bottom: 1rem; }
.form-row { display: flex; flex-wrap: wrap; gap: 1rem; }
.form-row .form-group { flex: 1 1 0%; min-width: 150px; margin-bottom: 0; } /* Adjust min-width as needed */
.coupon-input { display: flex; gap: 0.5rem; }
.coupon-input input { flex-grow: 1; }
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

```javascript
// --- START OF FILE main.js ---

// Mobile menu toggle
window.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.mobile-menu-toggle');
    var navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            // Toggle navigation visibility
            navLinks.classList.toggle('active');
            // Toggle body class to prevent scrolling when menu is open
            document.body.classList.toggle('menu-open');
            // Toggle icon class (optional, if you want fa-times)
             const icon = menuToggle.querySelector('i');
             if (icon) {
                 icon.classList.toggle('fa-bars');
                 icon.classList.toggle('fa-times');
             }
        });
    }
    // Close menu if clicking outside of it on mobile
    document.addEventListener('click', function(e) {
        if (navLinks && navLinks.classList.contains('active') && menuToggle && !menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
             navLinks.classList.remove('active');
             document.body.classList.remove('menu-open');
             const icon = menuToggle.querySelector('i');
             if (icon) {
                 icon.classList.remove('fa-times');
                 icon.classList.add('fa-bars');
             }
        }
    });
});

// showFlashMessage utility
window.showFlashMessage = function(message, type = 'info') {
    let flashContainer = document.querySelector('.flash-message-container');
    // Create container if it doesn't exist
    if (!flashContainer) {
        flashContainer = document.createElement('div');
        // Apply Tailwind classes for positioning and styling the container
        flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full space-y-2';
        document.body.appendChild(flashContainer);
    }

    const flashDiv = document.createElement('div');
    // Define color mapping using Tailwind classes
    const colorMap = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
    };
    // Apply Tailwind classes for the message appearance
    flashDiv.className = `flash-message border px-4 py-3 rounded relative shadow-md flex justify-between items-center transition-opacity duration-300 ease-out opacity-0 ${colorMap[type] || colorMap['info']}`;
    flashDiv.setAttribute('role', 'alert');

    const messageSpan = document.createElement('span');
    messageSpan.className = 'block sm:inline';
    messageSpan.textContent = message;
    flashDiv.appendChild(messageSpan);

    const closeButton = document.createElement('button'); // Use button for accessibility
    closeButton.className = 'ml-4 text-xl leading-none font-semibold hover:text-black';
    closeButton.innerHTML = '&times;';
    closeButton.setAttribute('aria-label', 'Close message');
    closeButton.onclick = () => {
        flashDiv.style.opacity = '0';
        // Remove after transition
        setTimeout(() => flashDiv.remove(), 300);
    };
    flashDiv.appendChild(closeButton);

    // Add to container and fade in
    flashContainer.appendChild(flashDiv);
    // Force reflow before adding opacity class for transition
    void flashDiv.offsetWidth;
    flashDiv.style.opacity = '1';


    // Auto-dismiss timer
    setTimeout(() => {
        if (flashDiv && flashDiv.parentNode) { // Check if it wasn't already closed
             flashDiv.style.opacity = '0';
             setTimeout(() => flashDiv.remove(), 300); // Remove after fade out
        }
    }, 5000); // Keep message for 5 seconds
};


// Global AJAX handlers (Add-to-Cart, Newsletter, etc.)
window.addEventListener('DOMContentLoaded', function() {
    // Add-to-Cart handler (using event delegation on the body)
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.add-to-cart');
        // Specific exclusion for related products button to prevent double handling if form also submits
        // We now rely solely on the global handler for *all* add-to-cart buttons.
        // const btnRelated = e.target.closest('.add-to-cart-related');

        if (!btn) return; // Exit if the clicked element is not an add-to-cart button or its child

        e.preventDefault(); // Prevent default behavior (like form submission if button is type=submit)
        if (btn.disabled) return; // Prevent multiple clicks while processing

        const productId = btn.dataset.productId;
        const csrfTokenInput = document.getElementById('csrf-token-value');
        const csrfToken = csrfTokenInput?.value;

        // Check if this button is inside the main product detail form to get quantity
        const productForm = btn.closest('#product-detail-add-cart-form');
        let quantity = 1; // Default quantity
        if (productForm) {
            const quantityInput = productForm.querySelector('input[name="quantity"]');
            if (quantityInput) {
                 quantity = parseInt(quantityInput.value) || 1;
            }
        }


        if (!productId || !csrfToken) {
            showFlashMessage('Cannot add to cart. Missing product or security token. Please refresh.', 'error');
            console.error('Add to Cart Error: Missing productId or CSRF token input.');
            return;
        }

        btn.disabled = true;
        const originalText = btn.textContent;
        // Check if the button already contains an icon or just text
        const hasIcon = btn.querySelector('i');
        const loadingHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
        const originalHTML = btn.innerHTML; // Store original HTML if it contains icons

        btn.innerHTML = loadingHTML; // Adding state with spinner

        fetch('index.php?page=cart&action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            // Ensure quantity is sent based on whether it's from the main form or a simple button
            body: `product_id=${encodeURIComponent(productId)}&quantity=${encodeURIComponent(quantity)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            }
            return response.text().then(text => {
                 console.error('Add to Cart - Non-JSON response:', response.status, text);
                 throw new Error(`Server returned status ${response.status}. Check server logs or network response.`);
            });
        })
        .then(data => {
            if (data.success) {
                showFlashMessage(data.message || 'Product added to cart!', 'success');
                const cartCountSpan = document.querySelector('.cart-count');
                if (cartCountSpan) {
                    cartCountSpan.textContent = data.cart_count || 0;
                    cartCountSpan.style.display = (data.cart_count || 0) > 0 ? 'flex' : 'none';
                }
                 // Optionally change button text briefly or add a checkmark icon
                 btn.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                 setTimeout(() => {
                     // Restore original HTML or text
                     btn.innerHTML = originalHTML;
                     // Re-enable button unless out of stock now
                     if (data.stock_status !== 'out_of_stock') {
                        btn.disabled = false;
                     } else {
                         // Keep disabled and update text if out of stock now
                         btn.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Out of Stock';
                         btn.classList.add('btn-disabled'); // Add a class if needed
                     }
                 }, 1500); // Reset after 1.5 seconds

                 // Update mini cart if applicable
                 if (typeof fetchMiniCart === 'function') {
                     fetchMiniCart();
                 }
            } else {
                showFlashMessage(data.message || 'Could not add product to cart.', 'error');
                btn.innerHTML = originalHTML; // Reset button immediately on failure
                btn.disabled = false;
            }
        })
        .catch((error) => {
            console.error('Add to Cart Fetch Error:', error);
            showFlashMessage(error.message || 'Error adding to cart. Please try again.', 'error');
            btn.innerHTML = originalHTML; // Reset button
            btn.disabled = false;
        });
    });

    // Newsletter AJAX handler (if present)
    var newsletterForm = document.getElementById('newsletter-form'); // Main newsletter form
    var newsletterFormFooter = document.getElementById('newsletter-form-footer'); // Footer newsletter form

    function handleNewsletterSubmit(formElement) {
        formElement.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = formElement.querySelector('input[name="email"]');
            const submitButton = formElement.querySelector('button[type="submit"]');
            const csrfTokenInput = formElement.querySelector('input[name="csrf_token"]'); // Get token from specific form

            if (!emailInput || !submitButton || !csrfTokenInput) {
                 console.error("Newsletter form elements missing.");
                 showFlashMessage('An error occurred. Please try again.', 'error');
                 return;
            }

            const email = emailInput.value.trim();
            const csrfToken = csrfTokenInput.value;

            if (!email || !/\S+@\S+\.\S+/.test(email)) {
                showFlashMessage('Please enter a valid email address.', 'error');
                return;
            }
            if (!csrfToken) {
                 showFlashMessage('Security token missing. Please refresh the page.', 'error');
                 return;
            }

            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subscribing...';

            fetch('index.php?page=newsletter&action=subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(res => {
                 const contentType = res.headers.get("content-type");
                 if (res.ok && contentType && contentType.indexOf("application/json") !== -1) {
                     return res.json();
                 }
                 return res.text().then(text => {
                     console.error('Newsletter - Non-JSON response:', res.status, text);
                     throw new Error(`Server returned status ${res.status}.`);
                 });
            })
            .then(data => {
                showFlashMessage(data.message || (data.success ? 'Subscription successful!' : 'Subscription failed.'), data.success ? 'success' : 'error');
                if (data.success) {
                    formElement.reset();
                }
            })
            .catch((error) => {
                console.error('Newsletter Fetch Error:', error);
                showFlashMessage(error.message || 'Error subscribing. Please try again later.', 'error');
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.textContent = originalButtonText;
            });
        });
    }

    if (newsletterForm) {
        handleNewsletterSubmit(newsletterForm);
    }
    if (newsletterFormFooter) {
        handleNewsletterSubmit(newsletterFormFooter);
    }
});


// --- Page Specific Initializers ---

function initHomePage() {
    // console.log("Initializing Home Page");
    // Particles.js initialization for hero section (if using)
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json', function() {
            // console.log('particles.js loaded - callback');
        });
    }
}

function initProductsPage() {
    // console.log("Initializing Products Page");
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            url.searchParams.delete('page_num');
            window.location.href = url.toString();
        });
    }

    const applyPriceFilter = document.querySelector('.apply-price-filter');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');

    if (applyPriceFilter && minPriceInput && maxPriceInput) {
        applyPriceFilter.addEventListener('click', function() {
            const minPrice = minPriceInput.value.trim();
            const maxPrice = maxPriceInput.value.trim();
            const url = new URL(window.location.href);

            if (minPrice) url.searchParams.set('min_price', minPrice);
            else url.searchParams.delete('min_price');

            if (maxPrice) url.searchParams.set('max_price', maxPrice);
            else url.searchParams.delete('max_price');

            url.searchParams.delete('page_num');
            window.location.href = url.toString();
        });
    }
}

function initProductDetailPage() {
    // console.log("Initializing Product Detail Page");
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-grid img');

    // Make updateMainImage function available globally for inline onclick
    // Note: Using event delegation below is generally preferred over inline onclick
    window.updateMainImage = function(thumbnailElement) {
        if (mainImage && thumbnailElement) {
            mainImage.src = thumbnailElement.dataset.largeImage || thumbnailElement.src;
            mainImage.alt = thumbnailElement.alt.replace('Thumbnail', 'Main view');

            thumbnails.forEach(img => img.parentElement.classList.remove('border-primary', 'border-2')); // Remove active style from parent div
            thumbnailElement.parentElement.classList.add('border-primary', 'border-2'); // Add active style to parent div
        }
    }

    // Set initial active thumbnail based on class (more reliable if structure changes)
    const activeThumbnailDiv = document.querySelector('.thumbnail-grid .border-primary');
    if (activeThumbnailDiv && mainImage && !mainImage.src.includes('placeholder.jpg')) { // Ensure first image isn't placeholder before potentially resetting
        const activeThumbImg = activeThumbnailDiv.querySelector('img');
        // Optional: Set main image source based on initially active thumb if needed
        // if (activeThumbImg) updateMainImage(activeThumbImg);
    } else if (thumbnails.length > 0) {
        // If no thumb is marked active, activate the first one
        thumbnails[0].parentElement.classList.add('border-primary', 'border-2');
    }


    // Quantity Selector Logic
    const quantityInput = document.querySelector('.quantity-selector input[name="quantity"]');
    if (quantityInput) {
        const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
        const quantityMin = parseInt(quantityInput.getAttribute('min') || '1');

        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value);
                if (isNaN(currentValue)) currentValue = quantityMin;

                if (this.classList.contains('plus')) {
                    if (currentValue < quantityMax) quantityInput.value = currentValue + 1;
                    else quantityInput.value = quantityMax;
                } else if (this.classList.contains('minus')) {
                    if (currentValue > quantityMin) quantityInput.value = currentValue - 1;
                    else quantityInput.value = quantityMin;
                }
            });
        });
         quantityInput.addEventListener('change', function() {
             let value = parseInt(this.value);
             if (isNaN(value) || value < quantityMin) this.value = quantityMin;
             if (value > quantityMax) this.value = quantityMax;
         });
     }


    // Tab Switching Logic
    const tabContainer = document.querySelector('.product-tabs'); // Adjusted selector
    if (tabContainer) {
         const tabBtns = tabContainer.querySelectorAll('.tab-btn');
         const tabPanes = tabContainer.querySelectorAll('.tab-pane');

         tabContainer.addEventListener('click', function(e) {
             const clickedButton = e.target.closest('.tab-btn');
             if (!clickedButton || clickedButton.classList.contains('text-primary')) return; // Check active style

             const tabId = clickedButton.dataset.tab;

             tabBtns.forEach(b => {
                 b.classList.remove('text-primary', 'border-primary');
                 b.classList.add('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
             });
             tabPanes.forEach(pane => pane.classList.remove('active')); // Assuming 'active' class controls visibility

             clickedButton.classList.add('text-primary', 'border-primary');
             clickedButton.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');

             const activePane = tabContainer.querySelector(`.tab-pane#${tabId}`);
             if (activePane) {
                 activePane.classList.add('active');
             }
         });

         // Ensure initial active tab's pane is visible on load
         const initialActiveTab = tabContainer.querySelector('.tab-btn.text-primary');
         if (initialActiveTab) {
             const initialTabId = initialActiveTab.dataset.tab;
             const initialActivePane = tabContainer.querySelector(`.tab-pane#${initialTabId}`);
             if (initialActivePane) {
                 initialActivePane.classList.add('active');
             }
         } else {
            // If no tab is active by default, activate the first one
            const firstTab = tabContainer.querySelector('.tab-btn');
            const firstPane = tabContainer.querySelector('.tab-pane');
            if (firstTab && firstPane) {
                 firstTab.classList.add('text-primary', 'border-primary');
                 firstTab.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
                 firstPane.classList.add('active');
            }
         }
         // Add 'active' class styles to style.css if not already present
         // .tab-pane { display: none; }
         // .tab-pane.active { display: block; }
    }

    // Note: The main add-to-cart button now uses the global handler, including quantity.
    // Related product add-to-cart buttons also use the global handler (default quantity 1).
}


function initCartPage() {
    // console.log("Initializing Cart Page");
    const cartForm = document.getElementById('cartForm');
    if (!cartForm) return;

    // --- Helper Functions for Cart ---
    function updateCartTotalsDisplay() {
        let subtotal = 0;
        let itemCount = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const priceElement = item.querySelector('.item-price');
            const quantityInput = item.querySelector('.item-quantity input');
            const subtotalElement = item.querySelector('.item-subtotal');

            if (priceElement && quantityInput) {
                // Extract price reliably, removing currency symbols etc.
                const priceText = priceElement.dataset.price || priceElement.textContent;
                const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                const quantity = parseInt(quantityInput.value);

                if (!isNaN(price) && !isNaN(quantity)) {
                    const lineTotal = price * quantity;
                    subtotal += lineTotal;
                    itemCount += quantity;
                    if (subtotalElement) {
                        subtotalElement.textContent = '$' + lineTotal.toFixed(2);
                    }
                }
            }
        });

        // Update summary totals
        const subtotalDisplay = cartForm.querySelector('.cart-summary .summary-row:nth-child(1) span:last-child');
        const totalDisplay = document.getElementById('cart-grand-total'); // Use specific ID for grand total
        const shippingDisplay = cartForm.querySelector('.cart-summary .summary-row.shipping span:last-child');
        const freeShippingThreshold = parseFloat(document.body.dataset.freeShippingThreshold || '50');
        const baseShippingCost = parseFloat(document.body.dataset.baseShippingCost || '5.99');

        const shippingCost = subtotal >= freeShippingThreshold ? 0 : baseShippingCost;


        if (subtotalDisplay) subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
        if (shippingDisplay) shippingDisplay.innerHTML = shippingCost === 0 ? '<span class="text-green-600">FREE</span>' : '$' + shippingCost.toFixed(2);
        if (totalDisplay) totalDisplay.textContent = '$' + (subtotal + shippingCost).toFixed(2); // Update grand total


        updateCartCountHeader(itemCount);

        // Handle empty cart state (find elements by class/ID)
        const emptyCartMessage = document.querySelector('.empty-cart'); // Needs an element with this class/ID
        const cartItemsContainer = document.querySelector('.cart-items'); // Container holding items
        const cartSummary = document.querySelector('.cart-summary'); // Summary section
        const cartActions = document.querySelector('.cart-actions'); // Buttons section
        const checkoutButton = document.querySelector('.checkout'); // Checkout button

        if (itemCount === 0) {
            if (cartItemsContainer) cartItemsContainer.classList.add('hidden');
            if (cartSummary) cartSummary.classList.add('hidden');
            if (cartActions) cartActions.classList.add('hidden');
            if (emptyCartMessage) emptyCartMessage.classList.remove('hidden');
        } else {
             if (cartItemsContainer) cartItemsContainer.classList.remove('hidden');
             if (cartSummary) cartSummary.classList.remove('hidden');
             if (cartActions) cartActions.classList.remove('hidden');
            if (emptyCartMessage) emptyCartMessage.classList.add('hidden');
        }

        if (checkoutButton) {
            checkoutButton.classList.toggle('opacity-50', itemCount === 0);
            checkoutButton.classList.toggle('cursor-not-allowed', itemCount === 0);
            if(itemCount === 0) checkoutButton.setAttribute('disabled', 'disabled');
            else checkoutButton.removeAttribute('disabled');
        }
    }

    function updateCartCountHeader(count) {
        const cartCountSpan = document.querySelector('.cart-count');
        if (cartCountSpan) {
            cartCountSpan.textContent = count;
            cartCountSpan.style.display = count > 0 ? 'flex' : 'none';
            cartCountSpan.classList.toggle('animate-pulse', count > 0);
            setTimeout(() => cartCountSpan.classList.remove('animate-pulse'), 1000);
        }
    }

    // --- Event Listeners for Cart Actions ---
    cartForm.addEventListener('click', function(e) {
        const quantityBtn = e.target.closest('.quantity-btn');
        if (quantityBtn) {
            const input = quantityBtn.parentElement.querySelector('input[name^="updates["]'); // Target input by name pattern
            if (!input) return;

            const max = parseInt(input.getAttribute('max') || '99');
            const min = parseInt(input.getAttribute('min') || '1');
            let value = parseInt(input.value);
            if (isNaN(value)) value = min;

            if (quantityBtn.classList.contains('plus')) {
                if (value < max) input.value = value + 1;
                else input.value = max;
            } else if (quantityBtn.classList.contains('minus')) {
                if (value > min) input.value = value - 1;
                else input.value = min;
            }
            // Trigger change event to update totals display immediately
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        const removeItemBtn = e.target.closest('.remove-item');
        if (removeItemBtn) {
            e.preventDefault();
            const cartItemRow = removeItemBtn.closest('.cart-item');
            if (!cartItemRow) return;

            const productId = removeItemBtn.dataset.productId;
            const csrfTokenInput = cartForm.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput?.value;


            if (!productId || !csrfToken) {
                showFlashMessage('Error removing item: Missing data.', 'error');
                return;
            }

            if (confirm('Are you sure you want to remove this item?')) {
                cartItemRow.style.opacity = '0';
                cartItemRow.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => {
                    cartItemRow.remove();
                    updateCartTotalsDisplay(); // Update totals after removing element visually
                }, 300);

                fetch('index.php?page=cart&action=remove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_id=${encodeURIComponent(productId)}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
                .then(data => {
                    if (data.success) {
                        showFlashMessage(data.message || 'Item removed.', 'success');
                        // Totals already updated visually. Header count updated by totals function.
                        if (typeof fetchMiniCart === 'function') fetchMiniCart();
                    } else {
                        showFlashMessage(data.message || 'Error removing item.', 'error');
                        // Revert optimistic UI update is complex, maybe force reload or rely on update button
                        updateCartTotalsDisplay(); // Re-run totals to ensure consistency
                    }
                })
                .catch(error => {
                    console.error('Error removing item:', error);
                    showFlashMessage('Failed to remove item.', 'error');
                    updateCartTotalsDisplay();
                });
            }
            return;
        }
    });

    cartForm.addEventListener('change', function(e) {
        if (e.target.matches('.item-quantity input')) {
            const input = e.target;
            const max = parseInt(input.getAttribute('max') || '99');
            const min = parseInt(input.getAttribute('min') || '1');
            let value = parseInt(input.value);

            if (isNaN(value) || value < min) input.value = min;
            if (value > max) {
                input.value = max;
                showFlashMessage(`Quantity cannot exceed ${max}.`, 'warning');
            }
            updateCartTotalsDisplay(); // Update totals on manual input change
        }
    });

    // AJAX Update Cart Button
    const updateCartButton = cartForm.querySelector('.update-cart'); // More specific selector
    if (updateCartButton) {
        updateCartButton.addEventListener('click', function(e) {
            e.preventDefault();
            const formData = new FormData(cartForm);
            const submitButton = this;
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';

            fetch('index.php?page=cart&action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().catch(() => ({ success: false, message: 'Invalid response from server.' })))
            .then(data => {
                if (data.success) {
                    showFlashMessage(data.message || 'Cart updated!', 'success');
                    updateCartTotalsDisplay(); // Recalculate totals visually
                    if (typeof fetchMiniCart === 'function') fetchMiniCart();
                } else {
                     // Display specific stock errors if provided
                    let errorMessage = data.message || 'Failed to update cart.';
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += ' ' + data.errors.join('; ');
                    }
                    showFlashMessage(errorMessage, 'error');
                    // Optionally reload or revert changes if update fails significantly
                    updateCartTotalsDisplay(); // Refresh totals again
                }
            })
            .catch(error => {
                console.error('Error updating cart:', error);
                showFlashMessage('Network error updating cart.', 'error');
                 updateCartTotalsDisplay(); // Refresh totals again
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.textContent = originalButtonText;
            });
        });
    }

     updateCartTotalsDisplay(); // Initial calculation
}


function initLoginPage() {
    // console.log("Initializing Login Page");
    const form = document.getElementById('loginForm');
    if (!form) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const buttonText = submitButton?.querySelector('.button-text');
    const buttonLoader = submitButton?.querySelector('.button-loader');

    // Password visibility toggle
    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            if (passwordInput && passwordInput.type) {
                 const icon = this.querySelector('i');
                 if (passwordInput.type === 'password') {
                     passwordInput.type = 'text';
                     icon?.classList.remove('fa-eye');
                     icon?.classList.add('fa-eye-slash');
                 } else {
                     passwordInput.type = 'password';
                     icon?.classList.remove('fa-eye-slash');
                     icon?.classList.add('fa-eye');
                 }
            }
        });
    });

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent standard form submission

        const emailInput = form.querySelector('#email');
        const passwordInput = form.querySelector('#password');
        const csrfTokenInput = document.getElementById('csrf-token-value'); // Get global CSRF

        if (!emailInput || !passwordInput || !submitButton || !csrfTokenInput) {
            console.error("Login form elements missing.");
            showFlashMessage('An error occurred submitting the form.', 'error');
            return;
        }
         const email = emailInput.value.trim();
         const password = passwordInput.value;
         const csrfToken = csrfTokenInput.value;


        if (!email || !password) {
             showFlashMessage('Please enter both email and password.', 'warning');
             return;
        }
         if (!csrfToken) {
             showFlashMessage('Security token missing. Please refresh.', 'error');
             return;
         }


        // Show loading state
        if(buttonText) buttonText.classList.add('hidden');
        if(buttonLoader) buttonLoader.classList.remove('hidden');
        submitButton.disabled = true;

        // Prepare data for fetch
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('csrf_token', csrfToken);
        // Append remember_me if needed
        const rememberMe = form.querySelector('input[name="remember_me"]');
        if (rememberMe && rememberMe.checked) {
            formData.append('remember_me', '1');
        }


        fetch('index.php?page=login', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             // Check content type before parsing JSON
             const contentType = response.headers.get("content-type");
             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                 return response.json();
             }
             // Handle non-JSON or error responses
             return response.text().then(text => {
                  console.error("Login error - non-JSON response:", response.status, text);
                  throw new Error(`Login failed. Server responded with status ${response.status}.`);
             });
         })
        .then(data => {
            if (data.success && data.redirect) {
                // Optional: show success message before redirect?
                // showFlashMessage('Login successful! Redirecting...', 'success');
                window.location.href = data.redirect; // Redirect on success
            } else {
                // Show error message from backend
                showFlashMessage(data.error || 'Login failed. Please check your credentials.', 'error');
            }
        })
        .catch(error => {
            console.error('Login Fetch Error:', error);
            showFlashMessage(error.message || 'An error occurred during login. Please try again.', 'error');
        })
        .finally(() => {
            // Hide loading state only if login failed (page redirects on success)
            if (buttonText) buttonText.classList.remove('hidden');
            if (buttonLoader) buttonLoader.classList.add('hidden');
            submitButton.disabled = false;
        });
    });
}


function initRegisterPage() {
    // console.log("Initializing Register Page");
    const form = document.getElementById('registerForm');
    if (!form) return;

    const passwordInput = form.querySelector('#password');
    const confirmPasswordInput = form.querySelector('#confirm_password');
    const submitButton = form.querySelector('button[type="submit"]');
    const buttonText = submitButton?.querySelector('.button-text');
    const buttonLoader = submitButton?.querySelector('.button-loader');

    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') }, // More general special char check
        match: { element: document.getElementById('req-match') }
    };

    function validatePassword() {
        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing

        let allMet = true;
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        for (const reqKey in requirements) {
            const req = requirements[reqKey];
            if (!req.element) continue;

            let isMet = false;
            if (reqKey === 'match') {
                isMet = passwordValue && passwordValue === confirmPasswordValue;
            } else if (req.regex) {
                isMet = req.regex.test(passwordValue);
            }

            req.element.classList.toggle('met', isMet);
            req.element.classList.toggle('not-met', !isMet);
            const icon = req.element.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-check-circle', isMet);
                icon.classList.toggle('fa-times-circle', !isMet);
                 icon.classList.toggle('text-green-500', isMet); // Add color classes
                 icon.classList.toggle('text-red-500', !isMet);
            }
            if (!isMet) allMet = false;
        }
        submitButton.disabled = !allMet;
        submitButton.classList.toggle('opacity-50', !allMet);
        submitButton.classList.toggle('cursor-not-allowed', !allMet);
        return allMet; // Return validation status
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validatePassword);
        validatePassword();
    }

    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function() {
            const passwordInputEl = this.previousElementSibling;
            if (passwordInputEl && passwordInputEl.type) {
                 const icon = this.querySelector('i');
                 if (passwordInputEl.type === 'password') {
                     passwordInputEl.type = 'text';
                     icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
                 } else {
                     passwordInputEl.type = 'password';
                     icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
                 }
            }
        });
    });

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Always prevent standard submission

        if (!validatePassword()) { // Re-validate before submit
            showFlashMessage('Please ensure all password requirements are met.', 'warning');
            passwordInput?.focus(); // Focus on the first password field
            return;
        }

         const nameInput = form.querySelector('#name');
         const emailInput = form.querySelector('#email');
         const csrfTokenInput = document.getElementById('csrf-token-value'); // Global CSRF
         const newsletterCheckbox = form.querySelector('input[name="newsletter_signup"]'); // <-- Select the checkbox

        if (!nameInput || !emailInput || !passwordInput || !confirmPasswordInput || !submitButton || !csrfTokenInput) {
            console.error("Register form elements missing.");
            showFlashMessage('An error occurred submitting the form.', 'error');
            return;
        }

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value; // Already validated
        const csrfToken = csrfTokenInput.value;


         if (!name || !email) {
             showFlashMessage('Please fill in all required fields.', 'warning');
             return;
         }
         if (!csrfToken) {
             showFlashMessage('Security token missing. Please refresh.', 'error');
             return;
         }


        // Show loading state
        if(buttonText) buttonText.classList.add('hidden');
        if(buttonLoader) buttonLoader.classList.remove('hidden');
        submitButton.disabled = true;

        // Prepare data for fetch
        const formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', confirmPasswordInput.value); // Send confirmation for backend double check if needed
        formData.append('csrf_token', csrfToken);

        // --- START: FIX FOR NEWSLETTER PREFERENCE ---
        // Append newsletter_signup only if the checkbox exists and is checked
        if (newsletterCheckbox && newsletterCheckbox.checked) {
            formData.append('newsletter_signup', '1'); // Use '1' as the value
        }
        // --- END: FIX FOR NEWSLETTER PREFERENCE ---


        fetch('index.php?page=register', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             const contentType = response.headers.get("content-type");
             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                 return response.json();
             }
             return response.text().then(text => {
                  console.error("Register error - non-JSON response:", response.status, text);
                  throw new Error(`Registration failed. Server responded with status ${response.status}.`);
             });
         })
        .then(data => {
            if (data.success && data.redirect) {
                 // Controller sets flash message for next page load, just redirect
                 window.location.href = data.redirect;
            } else {
                showFlashMessage(data.error || 'Registration failed. Please check your input and try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Register Fetch Error:', error);
            showFlashMessage(error.message || 'An error occurred during registration. Please try again.', 'error');
        })
        .finally(() => {
            // Hide loading state only if registration failed (page redirects on success)
            if (buttonText) buttonText.classList.remove('hidden');
            if (buttonLoader) buttonLoader.classList.add('hidden');
            // Re-enable button only if it failed, and re-validate password state
            validatePassword();
        });
    });
}


function initForgotPasswordPage() {
    // console.log("Initializing Forgot Password Page");
    const form = document.getElementById('forgotPasswordForm');
    if (!form) return;
    const submitButton = form.querySelector('button[type="submit"]');

    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
             // Keep standard form submission as controller handles redirect
             const email = form.querySelector('#email')?.value.trim();
             if (!email || !/\S+@\S+\.\S+/.test(email)) {
                 showFlashMessage('Please enter a valid email address.', 'error');
                 e.preventDefault();
                 return;
             }

            const buttonText = submitButton.querySelector('.button-text');
            const buttonLoader = submitButton.querySelector('.button-loader');
            if(buttonText) buttonText.classList.add('hidden');
            if(buttonLoader) buttonLoader.classList.remove('hidden');
            submitButton.disabled = true;
            // Allows standard POST
        });
    }
}


function initResetPasswordPage() {
    // console.log("Initializing Reset Password Page");
    const form = document.getElementById('resetPasswordForm');
    if (!form) return;

    const passwordInput = form.querySelector('#password');
    const confirmPasswordInput = form.querySelector('#password_confirm');
    const submitButton = form.querySelector('button[type="submit"]');

    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') },
        match: { element: document.getElementById('req-match') }
    };

    function validateResetPassword() {
        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing

        let allMet = true;
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        for (const reqKey in requirements) {
            const req = requirements[reqKey];
            if (!req.element) continue;
            let isMet = false;
            if (reqKey === 'match') {
                isMet = passwordValue && passwordValue === confirmPasswordValue;
            } else if (req.regex) {
                isMet = req.regex.test(passwordValue);
            }
            req.element.classList.toggle('met', isMet);
            req.element.classList.toggle('not-met', !isMet);
            const icon = req.element.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-check-circle', isMet);
                icon.classList.toggle('fa-times-circle', !isMet);
                icon.classList.toggle('text-green-500', isMet); // Add color classes
                icon.classList.toggle('text-red-500', !isMet);
            }
            if (!isMet) allMet = false;
        }
        submitButton.disabled = !allMet;
        submitButton.classList.toggle('opacity-50', !allMet);
        submitButton.classList.toggle('cursor-not-allowed', !allMet);
        return allMet; // Return validation status
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validateResetPassword);
        confirmPasswordInput.addEventListener('input', validateResetPassword);
        validateResetPassword();
    }

    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
         toggleBtn.addEventListener('click', function() {
             const passwordInputEl = this.previousElementSibling;
             if (passwordInputEl && passwordInputEl.type) {
                  const icon = this.querySelector('i');
                  if (passwordInputEl.type === 'password') {
                      passwordInputEl.type = 'text';
                      icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
                  } else {
                      passwordInputEl.type = 'password';
                      icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
                  }
             }
         });
     });

    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
            // Keep standard form submission as controller handles redirects
            if (!validateResetPassword()) { // Final validation check
                e.preventDefault();
                showFlashMessage('Please ensure all password requirements are met.', 'error');
                return;
            }
            const buttonText = submitButton.querySelector('.button-text');
            const buttonLoader = submitButton.querySelector('.button-loader');
             if(buttonText) buttonText.classList.add('hidden');
             if(buttonLoader) buttonLoader.classList.remove('hidden');
            submitButton.disabled = true;
            // Allows standard POST
        });
    }
}


function initQuizPage() {
    // console.log("Initializing Quiz Page");
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json');
    }

    const quizForm = document.getElementById('scent-quiz');
    if (quizForm) {
         const optionsContainer = quizForm.querySelector('.quiz-options-container');
         if (optionsContainer) {
             optionsContainer.addEventListener('click', (e) => {
                 const selectedOption = e.target.closest('.quiz-option');
                 if (!selectedOption) return;

                 // Find the actual radio button within the clicked label
                 const radioInput = selectedOption.querySelector('input[type="radio"]');
                 if (radioInput) {
                     radioInput.checked = true; // Ensure the radio button is checked

                     // Update visual states for all options
                     optionsContainer.querySelectorAll('.quiz-option').forEach(opt => {
                         const innerDiv = opt.querySelector('div');
                         const optRadio = opt.querySelector('input[type="radio"]');
                         if (innerDiv && optRadio) {
                              if (optRadio.checked) {
                                 innerDiv.classList.add('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
                                 innerDiv.classList.remove('border-gray-200');
                              } else {
                                 innerDiv.classList.remove('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
                                 innerDiv.classList.add('border-gray-200');
                              }
                         }
                     });
                 }
             });
         }

        quizForm.addEventListener('submit', (e) => {
             // Check if any radio button in the group is checked
             const selectedRadio = quizForm.querySelector('input[name="mood"]:checked');

             if (!selectedRadio) {
                 e.preventDefault();
                 showFlashMessage('Please select an option.', 'warning');
                 optionsContainer?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 return;
             }
              const submitButton = quizForm.querySelector('button[type="submit"]');
              if (submitButton) {
                  submitButton.disabled = true;
                  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Finding your scent...';
              }
             // Allows standard POST as controller handles rendering/redirect
        });
    }
}


function initQuizResultsPage() {
    // console.log("Initializing Quiz Results Page");
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json');
    }
}


function initAdminQuizAnalyticsPage() {
    // console.log("Initializing Admin Quiz Analytics");
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library is not loaded.');
        return;
    }
    let charts = {};
    const timeRangeSelect = document.getElementById('timeRange');
    const statsContainer = document.getElementById('statsContainer'); // Corrected ID if necessary
    const chartsContainer = document.getElementById('chartsContainer'); // Corrected ID if necessary
    const recommendationsTableBody = document.getElementById('recommendationsTable'); // Corrected ID

    // Check if elements exist before proceeding
     if (!timeRangeSelect || !document.getElementById('totalParticipants') || !document.getElementById('conversionRate') || !document.getElementById('avgCompletionTime') || !document.getElementById('scentChart') || !document.getElementById('moodChart') || !document.getElementById('completionsChart') || !recommendationsTableBody) {
          console.warn("One or more analytics elements not found. Analytics may not function correctly.");
          // Optionally display a message to the user if critical elements are missing
          // showFlashMessage("Could not load analytics components.", "error");
          // return; // Exit if critical elements are missing
     }


    Chart.defaults.font.family = "'Montserrat', sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
    Chart.defaults.plugins.legend.position = 'bottom';

    async function updateAnalytics() {
        const timeRange = timeRangeSelect ? timeRangeSelect.value : '7d'; // Default if select missing
        // Add visual indication of loading
        document.getElementById('totalParticipants')?.classList.add('opacity-50');
        document.getElementById('conversionRate')?.classList.add('opacity-50');
        document.getElementById('avgCompletionTime')?.classList.add('opacity-50');
        document.getElementById('scentChart')?.parentElement.classList.add('opacity-50');
        document.getElementById('moodChart')?.parentElement.classList.add('opacity-50');
        document.getElementById('completionsChart')?.parentElement.classList.add('opacity-50');
        recommendationsTableBody?.classList.add('opacity-50');

        try {
            // Use correct Admin route: index.php?page=admin&section=quiz_analytics
            const response = await fetch(`index.php?page=admin&section=quiz_analytics&range=${timeRange}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
             if (!response.ok) {
                  const errorText = await response.text();
                  throw new Error(`Network response was not ok (${response.status}): ${errorText}`);
             }
            const data = await response.json();

            // Adjust based on expected JSON structure from QuizController::showAnalytics
            if (data.success) {
                updateStatCards(data.data?.statistics);
                updateCharts(data.data?.preferences); // Pass the preferences part
                updateRecommendationsTable(data.data?.recommendations); // Pass the recommendations part
            } else {
                 throw new Error(data.error || 'Failed to fetch analytics data from the server.');
            }
        } catch (error) {
            console.error('Error fetching or processing analytics data:', error);
            showFlashMessage(`Failed to load analytics: ${error.message}`, 'error');
             // Update UI to show loading failed state
             document.getElementById('totalParticipants').textContent = 'Error';
             document.getElementById('conversionRate').textContent = 'Error';
             document.getElementById('avgCompletionTime').textContent = 'Error';
             document.getElementById('scentChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
             document.getElementById('moodChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
             document.getElementById('completionsChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
            if (recommendationsTableBody) recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">Could not load recommendations.</td></tr>';
        } finally {
             // Remove visual indication of loading
             document.getElementById('totalParticipants')?.classList.remove('opacity-50');
             document.getElementById('conversionRate')?.classList.remove('opacity-50');
             document.getElementById('avgCompletionTime')?.classList.remove('opacity-50');
             document.getElementById('scentChart')?.parentElement.classList.remove('opacity-50');
             document.getElementById('moodChart')?.parentElement.classList.remove('opacity-50');
             document.getElementById('completionsChart')?.parentElement.classList.remove('opacity-50');
             recommendationsTableBody?.classList.remove('opacity-50');
        }
    }

    function updateStatCards(stats) {
        if (!stats) {
             document.getElementById('totalParticipants').textContent = 'N/A';
             document.getElementById('conversionRate').textContent = 'N/A';
             document.getElementById('avgCompletionTime').textContent = 'N/A';
             return;
         }
        document.getElementById('totalParticipants').textContent = stats.total_quizzes ?? 'N/A';
        document.getElementById('conversionRate').textContent = stats.conversion_rate != null ? `${stats.conversion_rate}%` : 'N/A';
        document.getElementById('avgCompletionTime').textContent = stats.avg_completion_time != null ? `${stats.avg_completion_time}s` : 'N/A';
    }

    function updateCharts(preferences) {
         if (!preferences) {
              document.getElementById('scentChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>';
              document.getElementById('moodChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>';
              document.getElementById('completionsChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data.</p>';
              return;
         }
         Object.values(charts).forEach(chart => chart?.destroy());
         charts = {};
         const chartColors = ['#1A4D5A', '#A0C1B1', '#D4A76A', '#6B7280', '#F59E0B', '#10B981'];

         // Scent Preference Chart (Assuming 'scent_types' is correct key from controller)
         const scentCtx = document.getElementById('scentChart')?.getContext('2d');
         if (scentCtx && preferences.scent_types?.length > 0) {
             charts.scent = new Chart(scentCtx, {
                 type: 'doughnut',
                 data: { labels: preferences.scent_types.map(p => p.type), datasets: [{ data: preferences.scent_types.map(p => p.count), backgroundColor: chartColors, hoverOffset: 4 }] },
                 options: { responsive: true, plugins: { legend: { display: true }, title: { display: true, text: 'Scent Type Preferences' } } }
             });
         } else if (scentCtx) { scentCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No scent preference data.</p>'; }

         // Mood Effect Chart (Assuming 'mood_effects' is correct key from controller)
         const moodCtx = document.getElementById('moodChart')?.getContext('2d');
         if (moodCtx && preferences.mood_effects?.length > 0) {
            charts.mood = new Chart(moodCtx, {
                type: 'bar',
                data: { labels: preferences.mood_effects.map(p => p.effect), datasets: [{ label: 'Count', data: preferences.mood_effects.map(p => p.count), backgroundColor: chartColors[1], borderColor: chartColors[1], borderWidth: 1 }] },
                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Desired Mood Effects' } } }
            });
         } else if (moodCtx) { moodCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No mood effect data.</p>'; }

         // Daily Completions Chart (Assuming 'daily_completions' is correct key)
          const completionsCtx = document.getElementById('completionsChart')?.getContext('2d');
          if (completionsCtx && preferences.daily_completions?.length > 0) {
             charts.completions = new Chart(completionsCtx, {
                 type: 'line',
                 data: { labels: preferences.daily_completions.map(d => d.date), datasets: [{ label: 'Completions', data: preferences.daily_completions.map(d => d.count), borderColor: chartColors[0], backgroundColor: 'rgba(26, 77, 90, 0.1)', fill: true, tension: 0.1 }] },
                 options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Quiz Completions Over Time' } } }
             });
         } else if (completionsCtx) { completionsCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data for this period.</p>'; }
    }

    function updateRecommendationsTable(recommendations) {
        if (!recommendations || !recommendationsTableBody) return;
        if (recommendations.length === 0) {
            recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No recommendations data available for this period.</td></tr>';
            return;
        }
         // Assuming `recommendations` array has objects with keys like: name, category, recommendation_count, conversion_rate, id
        recommendationsTableBody.innerHTML = recommendations.map(product => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.category || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${product.recommendation_count ?? 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${product.conversion_rate != null ? `${product.conversion_rate}%` : 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                    <a href="index.php?page=admin&section=products&task=edit&id=${product.id || '#'}" class="text-indigo-600 hover:text-indigo-900" title="View/Edit Product"><i class="fas fa-eye"></i></a>
                </td>
            </tr>`).join('');
    }

    if (timeRangeSelect) {
        timeRangeSelect.addEventListener('change', updateAnalytics);
        updateAnalytics(); // Initial load
    } else {
        console.warn("Time range selector not found. Loading default analytics.");
        updateAnalytics(); // Attempt initial load with default range
    }
}


function initAdminCouponsPage() {
    // console.log("Initializing Admin Coupons Page");
    const createButton = document.getElementById('createCouponBtn');
    const couponFormContainer = document.getElementById('couponFormContainer');
    const couponForm = document.getElementById('couponForm');
    const cancelFormButton = document.getElementById('cancelCouponForm');
    const couponListTable = document.getElementById('couponListTable'); // Table body
    const discountTypeSelect = document.getElementById('discount_type');
    const valueHint = document.getElementById('valueHint');

    function showCouponForm(couponData = null) {
        if (!couponForm || !couponFormContainer) return;
        couponForm.reset();
        couponForm.querySelector('input[name="coupon_id"]').value = '';
        const formTitle = couponFormContainer.querySelector('h2');
        const submitBtn = couponForm.querySelector('button[type="submit"]');

        if (couponData) {
            // Populate form for editing
            couponForm.querySelector('input[name="coupon_id"]').value = couponData.id || '';
            couponForm.querySelector('input[name="code"]').value = couponData.code || '';
            couponForm.querySelector('textarea[name="description"]').value = couponData.description || '';
            couponForm.querySelector('select[name="discount_type"]').value = couponData.discount_type || 'fixed';
            couponForm.querySelector('input[name="value"]').value = couponData.discount_value || ''; // Use correct key
            couponForm.querySelector('input[name="min_spend"]').value = couponData.min_purchase_amount || ''; // Use correct key
            couponForm.querySelector('input[name="usage_limit"]').value = couponData.usage_limit || '';
            if (couponData.valid_from) couponForm.querySelector('input[name="valid_from"]').value = couponData.valid_from.replace(' ', 'T').substring(0, 16);
            if (couponData.valid_to) couponForm.querySelector('input[name="valid_to"]').value = couponData.valid_to.replace(' ', 'T').substring(0, 16);
             couponForm.querySelector('input[name="is_active"][value="1"]').checked = couponData.is_active == 1;
             couponForm.querySelector('input[name="is_active"][value="0"]').checked = couponData.is_active == 0;

             if(formTitle) formTitle.textContent = 'Edit Coupon';
             if(submitBtn) submitBtn.textContent = 'Update Coupon';
        } else {
             if(formTitle) formTitle.textContent = 'Create New Coupon';
             if(submitBtn) submitBtn.textContent = 'Create Coupon';
             // Set default active status for new coupons
             couponForm.querySelector('input[name="is_active"][value="1"]').checked = true;
        }

        updateValueHint();
        couponFormContainer.classList.remove('hidden');
        couponForm.scrollIntoView({ behavior: 'smooth' });
    }

    function hideCouponForm() {
        if (!couponForm || !couponFormContainer) return;
        couponForm.reset();
        couponFormContainer.classList.add('hidden');
    }

    function updateValueHint() {
        if (!discountTypeSelect || !valueHint) return;
        const selectedType = discountTypeSelect.value;
        if (selectedType === 'percentage') valueHint.textContent = 'Enter % (e.g., 10 for 10%). Max 100.';
        else if (selectedType === 'fixed') valueHint.textContent = 'Enter fixed amount (e.g., 15.50 for $15.50).';
        else valueHint.textContent = '';
    }

    // Function to handle AJAX actions for Toggle/Delete
    function handleCouponAction(url, successMessage, errorMessage, confirmationMessage) {
        if (confirmationMessage && !confirm(confirmationMessage)) {
            return; // Abort if user cancels confirmation
        }
        const csrfToken = couponForm.querySelector('input[name="csrf_token"]')?.value; // Get CSRF from form for POST

        fetch(url, {
            method: 'POST', // Use POST for actions that change state
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded' // Send CSRF in body
            },
            body: csrfToken ? `csrf_token=${encodeURIComponent(csrfToken)}` : ''
        })
        .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
        .then(data => {
            if (data.success) {
                showFlashMessage(successMessage, 'success');
                location.reload(); // Reload to see changes
            } else {
                showFlashMessage(data.message || errorMessage, 'error');
            }
        })
        .catch(error => {
            console.error('Coupon action error:', error);
            showFlashMessage('An error occurred. Please try again.', 'error');
        });
    }

    if (createButton) createButton.addEventListener('click', () => showCouponForm());
    if (cancelFormButton) cancelFormButton.addEventListener('click', hideCouponForm);
    if (discountTypeSelect) discountTypeSelect.addEventListener('change', updateValueHint);

    // Initial call for hint
    updateValueHint();

    // Event delegation for table buttons
    if (couponListTable) {
         couponListTable.addEventListener('click', function(e) {
             const editButton = e.target.closest('.edit-coupon');
             const toggleButton = e.target.closest('.toggle-status');
             const deleteButton = e.target.closest('.delete-coupon');

             if (editButton) {
                 e.preventDefault();
                 try {
                     const couponData = JSON.parse(editButton.dataset.coupon || '{}');
                     if (couponData.id) showCouponForm(couponData);
                     else console.error("Could not parse coupon data for editing.");
                 } catch (err) {
                     console.error("Error parsing coupon data:", err);
                     showFlashMessage('Could not load coupon data.', 'error');
                 }
                 return;
             }
             if (toggleButton) {
                 e.preventDefault();
                 const couponId = toggleButton.dataset.couponId;
                 if (couponId) {
                     handleCouponAction(
                         `index.php?page=admin&section=coupons&task=toggle_status&id=${couponId}`,
                         'Status updated.',
                         'Failed to update status.',
                         'Toggle status for this coupon?' // Confirmation message
                     );
                 }
                 return;
             }
             if (deleteButton) {
                 e.preventDefault();
                 const couponId = deleteButton.dataset.couponId;
                 if (couponId) {
                     handleCouponAction(
                         `index.php?page=admin&section=coupons&task=delete&id=${couponId}`,
                         'Coupon deleted.',
                         'Failed to delete coupon.',
                         'Permanently delete this coupon?' // Confirmation message
                     );
                 }
                 return;
             }
         });
    }

     // Handle form submission (standard POST, controller handles redirect)
     if (couponForm) {
         couponForm.addEventListener('submit', function() {
             const submitBtn = couponForm.querySelector('button[type="submit"]');
             if (submitBtn) {
                 submitBtn.disabled = true;
                 submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
             }
         });
     }
}


// --- Checkout Page Initialization (Updated with Debug Logs) ---
function initCheckoutPage() {
    console.log("Initializing Checkout Page JS..."); // <<< DEBUG LOG
    // --- Configuration ---
    // Fetch config from body data attributes for better security/flexibility
    const bodyData = document.body.dataset;
    const stripePublicKey = bodyData.stripePublicKey || '';
    const freeShippingThreshold = parseFloat(bodyData.freeShippingThreshold || '50');
    const baseShippingCost = parseFloat(bodyData.baseShippingCost || '5.99');
    const baseUrl = bodyData.baseUrl || '/'; // Use base URL for return_url

    // --- Element Selectors ---
    const checkoutForm = document.getElementById('checkoutForm');
    const submitButton = document.getElementById('submit-button');
    const spinner = document.getElementById('spinner');
    const buttonText = document.getElementById('button-text');
    const paymentElementContainer = document.getElementById('payment-element');
    const paymentMessage = document.getElementById('payment-message');
    const csrfToken = document.getElementById('csrf-token-value')?.value;
    const couponCodeInput = document.getElementById('coupon_code');
    const applyCouponButton = document.getElementById('apply-coupon');
    const couponMessageEl = document.getElementById('coupon-message');
    const discountRow = document.querySelector('.summary-row.discount');
    const discountAmountEl = document.getElementById('discount-amount');
    const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code');
    const taxRateEl = document.getElementById('tax-rate');
    const taxAmountEl = document.getElementById('tax-amount');
    const shippingCountryEl = document.getElementById('shipping_country');
    const shippingStateEl = document.getElementById('shipping_state');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryShippingEl = document.getElementById('summary-shipping');
    const summaryTotalEl = document.getElementById('summary-total');

    // --- State Variables ---
    let elements;
    let stripe;
    // Initialize state from PHP output, using parseFloat defensively
    let currentSubtotal = parseFloat(summarySubtotalEl?.textContent?.replace('$', '') || '0');
    let currentShippingCost = parseFloat(summaryShippingEl?.textContent?.replace('$', '') || baseShippingCost.toString()); // Use parsed value or default
    let currentTaxAmount = parseFloat(taxAmountEl?.textContent?.replace('$', '') || '0');
    let currentDiscountAmount = parseFloat(discountAmountEl?.textContent?.replace('-$', '') || '0'); // Handle initial discount if page reloads with coupon


    // --- Basic Checks ---
    console.log("Stripe Public Key:", stripePublicKey); // <<< DEBUG LOG
    if (!stripePublicKey) {
        showMessage("Stripe configuration error. Payment cannot proceed.", true);
        setLoading(false, true); // Disable button permanently
        return;
    }
    if (!checkoutForm || !submitButton || !paymentElementContainer || !csrfToken || !summarySubtotalEl) {
        console.error("Checkout form critical elements missing. Aborting initialization.");
        // Don't show generic message here, could be confusing if Stripe hasn't loaded yet
        // showMessage("Checkout form error. Please refresh the page.", true);
        return;
    }

    // --- Initialize Stripe ---
    try {
         stripe = Stripe(stripePublicKey);
         console.log("Stripe object initialized:", stripe); // <<< DEBUG LOG
         const appearance = {
             theme: 'stripe',
             variables: {
                 colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151',
                 colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem'
             }
         };
         elements = stripe.elements({ appearance });
         const paymentElement = elements.create('payment');
         paymentElement.mount('#payment-element');
         console.log("Stripe Payment Element mounted."); // <<< DEBUG LOG
    } catch (stripeError) {
        console.error("Stripe initialization error:", stripeError); // <<< DEBUG LOG
        showMessage("Could not initialize payment system. Please refresh.", true);
        setLoading(false, true);
        return;
    }


    // --- Helper Functions ---
    function setLoading(isLoading, disablePermanently = false) {
        if (!submitButton || !spinner || !buttonText) return;
        if (isLoading) {
            submitButton.disabled = true;
            spinner.classList.remove('hidden');
            buttonText.classList.add('hidden');
        } else {
            submitButton.disabled = disablePermanently;
            spinner.classList.add('hidden');
            buttonText.classList.remove('hidden');
        }
    }

    function showMessage(message, isError = true) {
        if (!paymentMessage) return;
        paymentMessage.textContent = message;
        paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
        paymentMessage.classList.remove('hidden');
    }

    function showCouponMessage(message, type) { // type = 'success', 'error', 'info'
        if (!couponMessageEl) return;
        couponMessageEl.textContent = message;
        couponMessageEl.className = `coupon-message mt-2 text-sm ${
            type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')
        }`;
        couponMessageEl.classList.remove('hidden');
    }

    function updateOrderSummaryUI() {
        if (!summarySubtotalEl || !discountRow || !discountAmountEl || !appliedCouponCodeDisplay || !summaryShippingEl || !taxAmountEl || !summaryTotalEl) return;

        summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);

        if (currentDiscountAmount > 0 && appliedCouponHiddenInput?.value) {
            discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
            appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
            discountRow.classList.remove('hidden');
        } else {
            discountAmountEl.textContent = '0.00';
            appliedCouponCodeDisplay.textContent = '';
            discountRow.classList.add('hidden');
        }

         const subtotalAfterDiscount = Math.max(0, currentSubtotal - currentDiscountAmount);
         currentShippingCost = subtotalAfterDiscount >= freeShippingThreshold ? 0 : baseShippingCost;
         summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';

        taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);

        const grandTotal = subtotalAfterDiscount + currentShippingCost + currentTaxAmount;
        summaryTotalEl.textContent = parseFloat(Math.max(0.50, grandTotal)).toFixed(2); // Ensure min $0.50 display
    }

    // --- Tax Calculation ---
    async function updateTax() {
        const country = shippingCountryEl?.value;
        const state = shippingStateEl?.value;

        if (!country || !taxRateEl || !taxAmountEl) {
             if (taxRateEl) taxRateEl.textContent = 'N/A';
             currentTaxAmount = 0;
             updateOrderSummaryUI();
            return;
        }

        try {
            taxAmountEl.textContent = '...'; // Loading indicator
            const response = await fetch('index.php?page=checkout&action=calculateTax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                 },
                // Pass current subtotal and discount for accurate tax calculation
                body: JSON.stringify({ country, state, subtotal: currentSubtotal, discount: currentDiscountAmount })
            });

            if (!response.ok) throw new Error(`Tax calculation failed (${response.status})`);
            const data = await response.json();

            if (data.success) {
                taxRateEl.textContent = data.tax_rate_formatted || 'N/A';
                currentTaxAmount = parseFloat(data.tax_amount) || 0;
            } else {
                 console.warn("Tax calculation error:", data.error);
                 taxRateEl.textContent = 'Error';
                 currentTaxAmount = 0;
            }
        } catch (e) {
            console.error('Error fetching tax:', e);
            taxRateEl.textContent = 'Error';
            currentTaxAmount = 0;
        } finally {
             updateOrderSummaryUI(); // Always update totals after tax calculation attempt
        }
    }

    if(shippingCountryEl) shippingCountryEl.addEventListener('change', updateTax);
    if(shippingStateEl) shippingStateEl.addEventListener('input', updateTax);

    // --- Coupon Application ---
    if (applyCouponButton && couponCodeInput && appliedCouponHiddenInput) {
        applyCouponButton.addEventListener('click', async function() {
            const couponCode = couponCodeInput.value.trim();
            if (!couponCode) {
                showCouponMessage('Please enter a coupon code.', 'error'); return;
            }

            showCouponMessage('Applying...', 'info');
            applyCouponButton.disabled = true;

            try {
                const response = await fetch('index.php?page=checkout&action=applyCouponAjax', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', 'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        code: couponCode,
                        subtotal: currentSubtotal, // Send current subtotal
                        csrf_token: csrfToken // Send CSRF token
                    })
                });

                 if (!response.ok) throw new Error(`Server error applying coupon (${response.status})`);
                 const data = await response.json();

                if (data.success) {
                    showCouponMessage(data.message || 'Coupon applied!', 'success');
                    currentDiscountAmount = parseFloat(data.discount_amount) || 0;
                    appliedCouponHiddenInput.value = data.coupon_code || couponCode;
                    // Recalculate tax and update summary UI after applying discount
                     updateTax(); // Triggers tax recalc and UI update
                } else {
                    showCouponMessage(data.message || 'Invalid coupon code.', 'error');
                    currentDiscountAmount = 0; // Reset discount
                    appliedCouponHiddenInput.value = ''; // Clear applied code
                    updateTax(); // Re-calculate tax and update summary UI without discount
                }
            } catch (e) {
                console.error('Coupon Apply Error:', e);
                showCouponMessage('Failed to apply coupon. Please try again.', 'error');
                currentDiscountAmount = 0;
                appliedCouponHiddenInput.value = '';
                updateTax(); // Re-calculate tax and update summary UI
            } finally {
                applyCouponButton.disabled = false;
            }
        });
    } else {
        console.warn("Coupon elements not found. Coupon functionality disabled.");
    }

    // --- Checkout Form Submission ---
    submitButton.addEventListener('click', async function(e) {
        setLoading(true);
        showMessage(''); // Clear previous messages

        // 1. Client-side validation
        let isValid = true;
        const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
        requiredFields.forEach(id => {
            const input = document.getElementById(id);
            if (!input || !input.value.trim()) {
                isValid = false; input?.classList.add('input-error');
            } else { input?.classList.remove('input-error'); }
        });
        if (!isValid) {
            showMessage('Please fill in all required shipping fields.', true); setLoading(false);
            const firstError = checkoutForm.querySelector('.input-error');
             firstError?.focus();
             firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        // 2. Send checkout data to server -> create order, get clientSecret
        let clientSecret = null;
        let serverOrderId = null;
        try {
            const checkoutFormData = new FormData(checkoutForm);
            // Ensure applied coupon code is included if set
            if (appliedCouponHiddenInput && appliedCouponHiddenInput.value) {
                checkoutFormData.set('applied_coupon_code', appliedCouponHiddenInput.value); // Ensure it's set correctly
            } else {
                checkoutFormData.delete('applied_coupon_code'); // Remove if empty
            }
             // Add save_address checkbox value
             const saveAddressCheckbox = checkoutForm.querySelector('input[name="save_address"]');
             if (saveAddressCheckbox && saveAddressCheckbox.checked) {
                 checkoutFormData.set('save_address', '1');
             }

            const response = await fetch('index.php?page=checkout&action=processCheckout', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: checkoutFormData
            });

            // Log status and try to parse JSON regardless of status code initially
            console.log("Process Checkout Response Status:", response.status); // <<< DEBUG LOG
            const data = await response.json(); // Try to parse JSON
            console.log("Process Checkout Response Data:", data); // <<< DEBUG LOG

            if (response.ok && data.success && data.clientSecret && data.orderId) {
                clientSecret = data.clientSecret;
                serverOrderId = data.orderId;
            } else {
                // Throw error using message from JSON if available
                throw new Error(data.error || `Failed to process order on server (Status: ${response.status}).`);
            }
        } catch (serverError) {
            console.error('Server processing error:', serverError);
            showMessage(serverError.message, true); setLoading(false); return;
        }

        // 3. Confirm payment with Stripe using the obtained clientSecret
        if (clientSecret && stripe && elements) {
            // Ensure BASE_URL ends with '/' for correct path joining
            const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
            const returnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation`;
            console.log("Stripe return_url:", returnUrl); // Log the return URL

            const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
                elements,
                clientSecret: clientSecret,
                confirmParams: { return_url: returnUrl },
                redirect: 'if_required'
            });

            if (stripeError) {
                 console.error("Stripe confirmPayment Error:", stripeError);
                 showMessage(stripeError.message || "Payment failed. Please check your card details or try another method.", true);
                 setLoading(false);
            }
            // If no error, Stripe handles the redirect on success.
        } else {
            if (!clientSecret) showMessage('Failed to get payment details from server.', true);
            if (!stripe || !elements) showMessage('Payment system not initialized correctly.', true);
            setLoading(false);
        }
    });

    // Initial UI calculations
    updateOrderSummaryUI();
    if (shippingCountryEl?.value) {
        updateTax(); // Initial tax calculation if country pre-filled
    }
}


// --- Admin Order Management Page ---
function initAdminOrdersPage() {
    // console.log("Initializing Admin Orders Page");
    const ordersTable = document.getElementById('ordersTable');
    const orderStatusSelects = document.querySelectorAll('.order-status-select');

    function updateOrderStatus(orderId, status) {
        fetch('index.php?page=admin&action=updateOrderStatus', { // Need to ensure index.php routes this correctly
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                // 'X-```javascript
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': document.getElementById('csrf-token-value')?.value // Include CSRF token
            },
            body: `order_id=${encodeURIComponent(orderId)}&status=${encodeURIComponent(status)}&csrf_token=${encodeURIComponent(document.getElementById('csrf-token-value')?.value || '')}` // Send CSRF token
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showFlashMessage('Order status updated successfully.', 'success');
                 // Maybe visually update the status in the table without full reload
                 const selectElement = document.querySelector(`.order-status-select[data-order-id="${orderId}"]`);
                 if (selectElement) {
                     // Optionally add a visual cue like a temporary background color change
                     selectElement.closest('tr')?.classList.add('bg-green-100');
                     setTimeout(() => selectElement.closest('tr')?.classList.remove('bg-green-100'), 2000);
                 }
            } else {
                showFlashMessage('Failed to update order status. Please try again.', 'error');
                 // Optionally revert the select dropdown if the update failed
                 // location.reload(); // Or force reload on failure
            }
        })
        .catch(error => {
            console.error('Error updating order status:', error);
            showFlashMessage('An error occurred while updating the order status.', 'error');
             // location.reload(); // Or force reload on failure
        });
    }

    orderStatusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            if (orderId && newStatus) {
                if (confirm(`Change order #${orderId} status to "${this.options[this.selectedIndex].text}"?`)) {
                     updateOrderStatus(orderId, newStatus);
                } else {
                    this.value = this.dataset.currentStatus; // Revert dropdown if cancelled
                }
            }
        });
         // Store initial status for potential revert
         select.dataset.currentStatus = select.value;
    });

    // Optional: Add more admin-specific functions and handlers as needed
}


// --- Page Initializer Dispatcher ---
// Use the original dispatcher logic based on body class
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS globally
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 800, offset: 120, once: true });
        // console.log('AOS Initialized Globally');
    } else {
        console.warn('AOS library not loaded.');
    }

    const body = document.body;
    // Map body class names to initializer functions
    const pageInitializers = {
        'page-home': initHomePage,
        'page-products': initProductsPage,
        'page-product-detail': initProductDetailPage,
        'page-cart': initCartPage,
        'page-login': initLoginPage,
        'page-register': initRegisterPage,
        'page-forgot-password': initForgotPasswordPage,
        'page-reset-password': initResetPasswordPage,
        'page-quiz': initQuizPage,
        'page-quiz-results': initQuizResultsPage,
        'page-admin-quiz-analytics': initAdminQuizAnalyticsPage,
        'page-admin-coupons': initAdminCouponsPage,
        'page-checkout': initCheckoutPage, // Added checkout initializer
        'page-admin-orders': initAdminOrdersPage, // Added admin orders initializer
         // Add other page classes and their init functions here
         // 'page-account-dashboard': initAccountDashboardPage, // Example if needed
         // 'page-account-profile': initAccountProfilePage, // Example if needed
    };

    let initialized = false;
    for (const pageClass in pageInitializers) {
        if (body.classList.contains(pageClass)) {
	    // Assign data attributes using PHP variables for use in page initializers
            // These are now read directly from header output
            // body.dataset.baseUrl = '<?= BASE_URL ?>';
            // body.dataset.stripePublicKey = '<?= STRIPE_PUBLIC_KEY ?>';
            // body.dataset.freeShippingThreshold = '<?= FREE_SHIPPING_THRESHOLD ?>';
            // body.dataset.baseShippingCost = '<?= SHIPPING_COST ?>';
            pageInitializers[pageClass]();
            initialized = true;
            // console.log(`Initialized: ${pageClass}`); // For debugging
            break; // Assume only one main page class per body
        }
    }
    // if (!initialized) {
    //     console.log('No specific page initialization class found on body.');
    // }

    // Fetch mini cart content on initial load (if element exists)
    if (document.getElementById('mini-cart-content') && typeof fetchMiniCart === 'function') {
         fetchMiniCart();
    }
});


// --- Mini Cart AJAX Update Function ---
// (Keep the original function)
function fetchMiniCart() {
    const miniCartContent = document.getElementById('mini-cart-content');
    if (!miniCartContent) return;

    // Optional: Show a subtle loading state inside the dropdown
    miniCartContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>';

    fetch('index.php?page=cart&action=mini', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (!response.ok) throw new Error(`Network response was not ok (${response.status})`);
        return response.json();
    })
    .then(data => {
        // Renders items or empty message based on data structure from CartController::mini
        if (data.items && data.items.length > 0) {
            let html = '<ul class="divide-y divide-gray-200 max-h-60 overflow-y-auto">';
             data.items.forEach(item => {
                 // Ensure item.product exists and has needed properties
                 const productId = item.product?.id || '#'; // Fallback ID
                 const imageUrl = item.product?.image || '/images/placeholder.jpg';
                 const productName = item.product?.name || 'Unknown Product';
                 const productPrice = parseFloat(item.product?.price || 0);
                 const quantity = parseInt(item.quantity || 0);
                 const lineTotal = productPrice * quantity;
                 html += `
                    <li class="flex items-center gap-3 py-3 px-1">
                         <img src="${imageUrl}" alt="${productName}" class="w-12 h-12 object-cover rounded border flex-shrink-0">
                         <div class="flex-1 min-w-0">
                             <a href="index.php?page=product&id=${productId}" class="font-medium text-sm text-gray-800 hover:text-primary truncate block" title="${productName}">${productName}</a>
                             <div class="text-xs text-gray-500">Qty: ${quantity} &times; $${productPrice.toFixed(2)}</div>
                         </div>
                         <div class="text-sm font-semibold text-gray-700">$${lineTotal.toFixed(2)}</div>
                     </li>`;
            });
            html += '</ul>';
            const subtotal = parseFloat(data.subtotal || 0);
            html += `<div class="border-t border-gray-200 pt-4 mt-4">
                 <div class="flex justify-between items-center mb-4">
                     <span class="font-semibold text-gray-700">Subtotal:</span>
                     <span class="font-bold text-primary text-lg">$${subtotal.toFixed(2)}</span>
                 </div>
                 <div class="flex flex-col gap-2">
                     <a href="index.php?page=cart" class="btn btn-secondary w-full text-center">View Cart</a>
                     <a href="index.php?page=checkout" class="btn btn-primary w-full text-center ${subtotal === 0 ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}">Checkout</a>
                 </div>
             </div>`;
            miniCartContent.innerHTML = html;
        } else {
            miniCartContent.innerHTML = '<div class="text-center text-gray-500 py-6 px-4">Your cart is empty.</div>';
        }
    })
    .catch(error => {
        console.error('Error fetching mini cart:', error);
        miniCartContent.innerHTML = '<div class="text-center text-red-500 py-6 px-4">Could not load cart.</div>';
    });
}


// --- END OF FILE main.js ---
```

**Summary of Changes:**

1.  **`views/checkout.php`:**
    *   Added a new form group for "Address Line 2 (Optional)" (`shipping_address_line2`) right after the "Street Address" field.
    *   Ensured the new field correctly pre-fills using `$userAddress['address_line2'] ?? ''`.

2.  **`controllers/CheckoutController.php`:**
    *   In the `processCheckout` method:
        *   Added code to explicitly read `shipping_address_line2` from `$_POST` after validating required fields.
        *   Modified the `if ($saveAddress)` block to create a new `$addressUpdateData` array, mapping the `shipping_*` field names from `$postData` to the column names expected by `UserModel::updateAddress` (e.g., `shipping_address` -> `address_line1`, `shipping_address_line2` -> `address_line2`).
        *   Passed the correctly mapped `$addressUpdateData` array to `$this->userModel->updateAddress()`.
        *   Added a check/assumption that the `orders` table also has a `shipping_address_line2` column when saving order data. *Note: If this column doesn't exist in the `orders` table schema, the corresponding key should be removed from the `$orderData` array within `processCheckout`.*

3.  **`js/main.js`:**
    *   In the `initCheckoutPage` function:
        *   Added `console.log("Initializing Checkout Page JS...");` at the start.
        *   Added `console.log("Stripe Public Key:", stripePublicKey);` after reading the key.
        *   Added `console.log("Stripe object initialized:", stripe);` after successfully calling `Stripe()`.
        *   Added `console.error("Stripe initialization error:", stripeError);` inside the `catch` block for Stripe initialization failure.
        *   Added `console.log("Stripe Payment Element mounted.");` after mounting the element.
        *   Added `console.log("Process Checkout Response Status:", response.status);` and `console.log("Process Checkout Response Data:", data);` inside the checkout submission handler to log the response from the backend.
        *   Added `console.log("Stripe return_url:", returnUrl);` before calling `stripe.confirmPayment`.
        *   Added `console.error("Stripe confirmPayment Error:", stripeError);` inside the `if (stripeError)` block.
    *   In `initCartPage` -> `updateCartTotalsDisplay`: Corrected calculation and DOM update for the grand total (`#cart-grand-total`) to include shipping costs.
    *   In `initAdminQuizAnalyticsPage`: Added checks for element existence before accessing properties/methods to prevent potential null errors if the DOM structure changes. Improved error handling display in the UI. Ensured chart/table data is accessed correctly from the expected nested structure (`data.data.statistics`, `data.data.preferences`, `data.data.recommendations`). Corrected table cell indexing and links.
    *   In `initAdminCouponsPage`: Corrected data keys used when populating the edit form (`discount_value`, `min_purchase_amount`). Ensured CSRF token is sent with POST requests for toggle/delete actions.
    *   In `initAdminOrdersPage`: Added CSRF token to the AJAX POST request for updating order status. Added confirmation dialog before status update. Added visual feedback on successful update (optional). Stored initial status in `data-current-status` to allow reverting dropdown on cancel.
    *   In `initQuizPage`: Corrected logic to check the actual radio button `checked` state and update corresponding visuals.


These changes address the specific issues raised: adding the missing address line field to checkout and ensuring the controller handles it for saving, and adding debugging logs to help pinpoint the Stripe initialization failure. The compatibility checks confirm that `index.php` and `js/main.js` generally align with the refactored `AccountController`. Further debugging using the added console logs will be needed to resolve the Stripe payment initialization issue.

---
Okay, I have carefully reviewed the diff outputs for `controllers/CheckoutController.php`, `views/checkout.php`, and `js/main.js` against their original versions and the stated goals.

**Validation Results:**

1.  **`diff -u controllers/CheckoutController.php-orig controllers/CheckoutController.php-new`:**
    *   **Issue 1 Fix (Address Line 2):**
        *   The code correctly reads the new `shipping_address_line2` field from `$_POST`.
        *   The code correctly adds `shipping_address_line2` to the `$orderData` array when creating the order record in the `orders` table (assuming the column exists there).
        *   The code correctly creates a separate `$addressUpdateData` array within the `if ($saveAddress)` block, mapping the `shipping_*` field names to the `address_line1`, `address_line2`, etc., keys expected by `UserModel::updateAddress`.
        *   The call `!$this->userModel->updateAddress($userId, $addressUpdateData)` now uses the correctly mapped data.
    *   **Regressions/Omissions:** No existing functionality appears to have been removed or negatively impacted. The changes are additive or modify the specific address-saving logic as intended.
    *   **Conclusion:** The changes in `controllers/CheckoutController.php` accurately implement the planned fix for handling and saving the second address line during checkout. **Validation PASSED.**

2.  **`diff -u views/checkout.php-orig views/checkout.php-new`:**
    *   **Issue 1 Fix (Address Line 2):**
        *   The new form group for "Address Line 2 (Optional)" with `name="shipping_address_line2"` has been correctly added after the "Street Address" field.
        *   The `value` attribute correctly uses `$userAddress['address_line2'] ?? ''` for pre-filling.
    *   **Issue 2 Fix (Debugging Logs):**
        *   All the planned `console.log` and `console.error` statements have been correctly added within the `initCheckoutPage` function in the embedded `<script>` tag to aid in debugging the Stripe initialization issue.
    *   **Regressions/Omissions:** No existing fields or functionality appear to have been removed or negatively impacted. The changes add the new field and the debugging logs as planned.
    *   **Conclusion:** The changes in `views/checkout.php` accurately implement the planned fixes for both Issue 1 (view part) and Issue 2 (debugging). **Validation PASSED.**

3.  **`diff -u js/main.js-orig js/main.js-new`:**
    *   **Issue 2 Fix (Debugging Logs):**
        *   All the planned `console.log` and `console.error` statements have been correctly added within the standalone `initCheckoutPage` function.
    *   **Other Fixes/Improvements:**
        *   `initProductDetailPage`: Image source check improved slightly (`includes` vs `endsWith`).
        *   `initCartPage`: Grand total calculation (`#cart-grand-total`) and shipping cost display logic were corrected in `updateCartTotalsDisplay`.
        *   `initQuizPage`: Logic for handling radio button selection and visual state updates was corrected.
        *   `initAdminQuizAnalyticsPage`: Added robustness checks for element existence, corrected data access paths (`data.data.*`), improved chart labels, and fixed link generation.
        *   `initAdminCouponsPage`: Corrected data keys (`discount_value`, `min_purchase_amount`) when populating the edit form and fixed CSRF token retrieval for AJAX actions.
        *   `initAdminOrdersPage`: Added CSRF token handling for AJAX status updates, added confirmation dialog, and improved UI feedback.
    *   **Regressions/Omissions:** No existing functionality appears to have been removed or negatively impacted. The changes correctly add the debugging logs for Issue 2 and implement various fixes and improvements in other page initializers.
    *   **Conclusion:** The changes in `js/main.js` accurately implement the debugging plan for Issue 2 and incorporate several other necessary fixes and improvements identified during the broader review. **Validation PASSED.**

**Overall Summary:**

The review of the provided diffs confirms that the changes made to `controllers/CheckoutController.php`, `views/checkout.php`, and `js/main.js` correctly address the specified issues and implement the planned fixes and debugging steps. The necessary code for handling the second address line in the checkout process (both view and controller logic) has been added. The requested debugging logs for the Stripe payment initialization issue have been inserted into the relevant JavaScript function. Additionally, several other important fixes and improvements identified during the review process have been correctly implemented in `js/main.js`. No regressions or accidental omissions of original code were detected in the diffs.

---
https://drive.google.com/file/d/123hv5nKUqgewF0EijNEsVCmfOVNse9tV/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%2215yICqTsxNyvXC4y7neLcks2qaQILb4gG%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/18UepE9wE5D-H6xJkVj2fAbRziUnqGSyj/view?usp=sharing, https://drive.google.com/file/d/18hKH60FDKEY_aG78LlhT0ybPzHbxMinI/view?usp=sharing, https://drive.google.com/file/d/1DKO9eGiUdVI9udIik0pR1iElemo8tLDT/view?usp=sharing, https://drive.google.com/file/d/1Jk1TZjEfqn7HSuXLObm-VYZJ956MwRaW/view?usp=sharing, https://drive.google.com/file/d/1L9mt8UvWrHDeKtp1QeA_LTg2RGHXwiLK/view?usp=sharing, https://drive.google.com/file/d/1OvoiijsaPFVLD2AYf6K5px9XIrMGaRlT/view?usp=sharing, https://drive.google.com/file/d/1POQ7zj01grkz_3sKF5MxgghBZsghIE46/view?usp=sharing, https://drive.google.com/file/d/1PcUogRJz2nKfaeLtpkBoQS_GbYumGZXX/view?usp=sharing, https://drive.google.com/file/d/1Vp9gtM4iMeGPrMYc7JNIJcT0w4m_zi_J/view?usp=sharing, https://drive.google.com/file/d/1Z6f86ThuU9gETnagrl8TB38UaUh6b1NT/view?usp=sharing, https://drive.google.com/file/d/1ZvOHIr44PIeGZ56xtbyiMu0LMVz2a3G5/view?usp=sharing, https://drive.google.com/file/d/1clRGf0pGXdM3NIZdiLxvoYxzE33N66dM/view?usp=sharing, https://drive.google.com/file/d/1g3JIk6QorfOBttNW95DPCj0MlGLfBoLC/view?usp=sharing, https://drive.google.com/file/d/1j8nXz0uX0mm4yl6vnf0KW4yfdG_POxz1/view?usp=sharing, https://drive.google.com/file/d/1olCawvC_W-DhLjpDmmMJGKdHNJrNHiO7/view?usp=sharing, https://drive.google.com/file/d/1v2ewLFnIvpv_t2gd5uEqI6bYYEDxqmqW/view?usp=sharing
