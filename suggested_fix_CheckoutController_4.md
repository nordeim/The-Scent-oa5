Okay, this is another excellent step forward! The results from running the simulation (`stripe_test_v4.html`) in the browser are very clear.

**Analysis of Browser Test Results:**

1.  **PHP Setup:** The initial PHP setup messages confirm that the script ran successfully on the server using the Composer classmap autoloader, found the test user (ID 1, `admin@thescent.com`), simulated adding product 1 to the cart, instantiated controllers, and generated the HTML/JS.
2.  **Stripe Core Init:** The on-page log confirms `Stripe Core Init: SUCCESS`. The core Stripe object initialized without problems in the browser.
3.  **Checkout Click:** You clicked the "Run Checkout Simulation" button.
4.  **Backend Call:**
    *   The JS sent the simulated form data to `index.php?page=checkout&action=processCheckout`.
    *   The backend responded with **`status 409`** (Conflict).
    *   The raw response body (JSON) was: `{"success":false,"error":"Some items went out of stock: Cart is empty. Please review your cart."}`
    *   The JS correctly logged `Backend Call: FAILED` and displayed the error message "Backend Error: Some items went out of stock: Cart is empty. Please review your cart." on the page.

**Diagnosis:**

The Stripe initialization problem is **solved**. The new issue is a **server-side logic error** within `CheckoutController::processCheckout` that occurs *before* the Payment Intent is created.

The specific error message "Some items went out of stock: Cart is empty. Please review your cart." comes from the stock validation check within the `processCheckout` method's transaction block:

```php
// Snippet from CheckoutController::processCheckout

// --- Re-validate Stock within Transaction ---
$stockErrors = $this->validateCartStock($cartItemsForOrder); // Use internal helper structure
if (!empty($stockErrors)) {
    $this->rollback();
     return $this->jsonResponse([
         'success' => false,
         // --- THIS IS THE ERROR MESSAGE BEING RETURNED ---
         'error' => 'Some items went out of stock: ' . implode(', ', $stockErrors) . '. Please review your cart.'
     ], 409); // 409 Conflict is appropriate here
}

// --- Where does $cartItemsForOrder come from? ---
// It's built earlier in the *same method* from the CartModel:

$cartModel = new Cart($this->db, $userId);
$items = $cartModel->getItems(); // Uses getCartItemsInternal which nests product data
// ... (validation if $items is empty) ...
$cartItemsForOrder = [];
$subtotal = 0.0;
foreach ($items as $item) {
    // ... build $cartItemsForOrder[$productId] = ['quantity' => q, 'price' => p, 'name' => n]; ...
}

// --- And where does validateCartStock get its data? ---
private function validateCartStock(array $cartItems): array {
    $errors = [];
    if (empty($cartItems)) {
        // --- PROBLEM: It incorrectly reports empty cart as out of stock ---
        return ['Cart is empty']; // This should NOT be treated as a stock error
    }
    // ... rest of stock checking loop ...
    return $errors;
}
```

**The Bug:**

The `validateCartStock` helper method inside `CheckoutController` incorrectly returns `['Cart is empty']` when the cart is empty, and the main `processCheckout` method treats this non-empty array as a stock error, causing it to return the 409 Conflict response with the wrong error message.

Although the test script *does* simulate adding an item to the cart (`$cartModel->addItem(1, 1)`), something might be going wrong when `processCheckout` tries to retrieve the items *again* at the start of its execution (`$items = $cartModel->getItems();`). It seems `$items` is empty when `$this->validateCartStock($cartItemsForOrder)` is called.

**Possible Reasons for Empty Cart in `processCheckout`:**

1.  **Session Issue:** Although the test script sets session variables, the web server environment (Apache/PHP-FPM) might handle sessions differently than the CLI, potentially leading to the session data not being available correctly when the AJAX request hits `processCheckout`. The "Headers already sent, cannot start session" warning during CLI execution might hint at session configuration nuances in your hosting environment.
2.  **Cart Logic Bug:** There might be a subtle bug in `CartModel::getItems` or the cart logic itself that results in an empty cart being returned under certain conditions during the AJAX request, even though `addItem` seemed to work in the script setup.
3.  **Database Issue:** A less likely possibility is a database rollback or issue preventing the cart item added in the test script setup from being persisted correctly until the AJAX request is handled.

**Solution:**

1.  **Fix `validateCartStock`:** Modify the helper method to return an empty array (no errors) if the cart is empty, instead of returning an error message.
2.  **Investigate Empty Cart:** Add logging inside `CheckoutController::processCheckout` right after `$items = $cartModel->getItems();` to see if the `$items` array is actually empty when the AJAX request comes in, and if so, why.

**Updated `controllers/CheckoutController.php`:**

```php
<?php
// controllers/CheckoutController.php (Updated - Fix validateCartStock, add logging)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../controllers/CouponController.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php';

class CheckoutController extends BaseController {
    private Product $productModel;
    private Order $orderModel;
    private InventoryController $inventoryController;
    private TaxController $taxController;
    private PaymentController $paymentController;
    private CouponController $couponController;
    private User $userModel;

    public function __construct($pdo, PaymentController $paymentController) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = $paymentController;
        $this->couponController = new CouponController($pdo);
        $this->userModel = new User($pdo);
    }

    public function showCheckout() {
        $this->requireLogin();
        $userId = $this->getUserId();
        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
             $this->redirect('index.php?page=products');
             return;
        }
        $cartItems = []; $subtotal = 0.0;
        foreach ($items as $item) {
            $productId = $item['product']['id'] ?? null; $quantity = $item['quantity'] ?? 0;
            if (!$productId || $quantity <= 0) continue;
            if (!$this->productModel->isInStock($productId, $quantity)) {
                $this->setFlashMessage("Item '".htmlspecialchars($item['product']['name'] ?? 'Product')."' is out of stock. Please update your cart.", 'error');
                $this->redirect('index.php?page=cart'); return;
            }
            $price = $item['product']['price'] ?? 0; $lineSubtotal = $price * $quantity;
            $cartItems[] = ['product' => $item['product'], 'quantity' => $quantity, 'subtotal' => $lineSubtotal];
            $subtotal += $lineSubtotal;
        }
        $tax_rate_formatted = 'N/A'; $tax_amount = 0.0;
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;
        $userAddress = $this->userModel->getAddress($userId);
        $csrfToken = $this->getCsrfToken(); $bodyClass = 'page-checkout'; $pageTitle = 'Checkout - The Scent';
        echo $this->renderView('checkout', [ /* ... view data ... */
            'cartItems' => $cartItems, 'subtotal' => $subtotal, 'tax_rate_formatted' => $tax_rate_formatted,
            'tax_amount' => $tax_amount, 'shipping_cost' => $shipping_cost, 'total' => $total,
            'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass, 'pageTitle' => $pageTitle,
            'userAddress' => $userAddress ?? [] ]);
    }

    public function calculateTax() { /* ... unchanged ... */
        $this->requireLogin(true);
        $json = file_get_contents('php://input'); $data = json_decode($json, true);
        $country = $this->validateInput($data['country'] ?? null, 'string');
        $state = $this->validateInput($data['state'] ?? null, 'string');
        $subtotal = $this->validateInput($data['subtotal'] ?? null, 'float');
        $discount = $this->validateInput($data['discount'] ?? 0, 'float');
        $subtotalAfterDiscount = max(0, $subtotal - $discount);
        if (empty($country)) { return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400); }
        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotalAfterDiscount, $country, $state);
        $tax_rate = $this->taxController->getTaxRate($country, $state);
        $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount;
        return $this->jsonResponse([ 'success' => true, 'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate), 'tax_amount' => number_format($tax_amount, 2), 'total' => number_format($total, 2) ]);
     }

    private function calculateCartSubtotal(): float { /* ... unchanged ... */
         $userId = $this->getUserId(); if (!$userId) return 0.0; $cartModel = new Cart($this->db, $userId);
         $items = $cartModel->getItems(); $subtotal = 0.0;
         foreach ($items as $item) { $subtotal += ($item['product']['price'] ?? 0) * ($item['quantity'] ?? 0); } return (float)$subtotal;
    }

    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin(true);
        $this->validateCSRF();

        $userId = $this->getUserId();
        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems();

        // --- START: Add Logging for Cart Items ---
        if (empty($items)) {
            error_log("Checkout Error: Cart items are empty for User ID: {$userId} at start of processCheckout.");
             // Return specific error if cart is unexpectedly empty
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart appears empty. Please add items before checkout.'], 400);
         } else {
             error_log("Checkout Info: Found " . count($items) . " distinct item types for User ID: {$userId}");
         }
         // --- END: Add Logging ---


        $cartItemsForOrder = []; $subtotal = 0.0;
        foreach ($items as $item) { /* ... build $cartItemsForOrder ... */
            $productId = $item['product']['id'] ?? null; $quantity = $item['quantity'] ?? 0; $price = $item['product']['price'] ?? 0; $name = $item['product']['name'] ?? 'Unknown Product';
            if (!$productId || $quantity <= 0) continue;
            $cartItemsForOrder[$productId] = ['quantity' => $quantity, 'price' => $price, 'name' => $name]; $subtotal += $price * $quantity;
        }

        // --- Validate Shipping Input (unchanged) ---
        $requiredFields = [ 'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country' ];
        $missingFields = []; $postData = [];
        foreach ($requiredFields as $field) { /* ... validation ... */
            $value = $_POST[$field] ?? '';
            if (empty(trim($value))) { $missingFields[] = ucwords(str_replace('_', ' ', $field)); }
            else { $type = (strpos($field, 'email') !== false) ? 'email' : 'string'; $validatedValue = $this->validateInput($value, $type);
                if ($validatedValue === false) { $missingFields[] = ucwords(str_replace('_', ' ', $field)) . " (Invalid)"; } else { $postData[$field] = $validatedValue; } }
        }
        if (!empty($missingFields)) { return $this->jsonResponse([ 'success' => false, 'error' => 'Please fill required shipping fields: ' . implode(', ', $missingFields) . '.' ], 400); }
        $postData['shipping_address_line2'] = $this->validateInput($_POST['shipping_address_line2'] ?? null, 'string', ['max' => 255]);
        $orderNotes = $this->validateInput($_POST['order_notes'] ?? null, 'string', ['max' => 1000]);
        $saveAddress = isset($_POST['save_address']) && $_POST['save_address'] === '1';


        // --- Validate Coupon (unchanged) ---
        $couponCode = $this->validateInput($_POST['applied_coupon_code'] ?? null, 'string'); $coupon = null; $discountAmount = 0.0;
        if ($couponCode) { /* ... coupon validation ... */
            $validationResult = $this->couponController->validateCouponCodeOnly($couponCode, $subtotal);
            if ($validationResult['valid']) { $coupon = $validationResult['coupon'];
                if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) { error_log("Checkout Warning: User {$userId} tried applying already used coupon '{$couponCode}'."); $coupon = null; $couponCode = null; }
                else { $discountAmount = $this->couponController->calculateDiscount($coupon, $subtotal); }
            } else { error_log("Checkout Warning: Coupon '{$couponCode}' invalid. Message: " . ($validationResult['message'] ?? 'N/A')); $couponCode = null; $coupon = null; }
        }

        // --- Calculate Final Totals (unchanged) ---
        $subtotalAfterDiscount = max(0, $subtotal - $discountAmount);
        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax( $subtotalAfterDiscount, $postData['shipping_country'], $postData['shipping_state'] );
        $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount; $total = max(0.50, round($total, 2));

        // --- Start Transaction ---
        try {
            $this->beginTransaction();

            // --- Re-validate Stock within Transaction ---
            // Pass the *correctly structured* $cartItemsForOrder array
            $stockErrors = $this->validateCartStock($cartItemsForOrder); // <--- FIX is in validateCartStock method
            if (!empty($stockErrors)) {
                $this->rollback();
                 return $this->jsonResponse([
                     'success' => false,
                     'error' => 'Some items went out of stock: ' . implode(', ', $stockErrors) . '. Please review your cart.'
                 ], 409);
            }

            // --- Create Order Record (unchanged) ---
            $orderData = [ /* ... order data ... */
                'user_id' => $userId, 'subtotal' => $subtotal, 'discount_amount' => $discountAmount, 'coupon_code' => $coupon ? $coupon['code'] : null,
                'coupon_id' => $coupon ? $coupon['id'] : null, 'shipping_cost' => $shipping_cost, 'tax_amount' => $tax_amount, 'total_amount' => $total,
                'shipping_name' => $postData['shipping_name'], 'shipping_email' => $postData['shipping_email'], 'shipping_address' => $postData['shipping_address'],
                'shipping_address_line2' => $postData['shipping_address_line2'] ?? null, 'shipping_city' => $postData['shipping_city'], 'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'], 'shipping_country' => $postData['shipping_country'], 'status' => 'pending_payment',
                'payment_status' => 'pending', 'order_notes' => $orderNotes, 'payment_intent_id' => null ];
            $orderId = $this->orderModel->create($orderData);
            if (!$orderId) throw new Exception("Failed to create order record.");

            // --- Create Order Items & Decrement Inventory (unchanged) ---
            $itemStmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItemsForOrder as $productId => $itemData) {
                $itemStmt->execute([$orderId, $productId, $itemData['quantity'], $itemData['price']]);
                $inventoryController = new InventoryController($this->db);
                if (!$inventoryController->updateStock($productId, -$itemData['quantity'], 'sale', $orderId)) {
                    throw new Exception("Failed to update inventory for product ID {$productId}");
                }
            }

            // --- Update User Address if Requested (unchanged) ---
            if ($saveAddress) { $addressUpdateData = [ /* ... mapping ... */ 'address_line1' => $postData['shipping_address'], 'address_line2' => $postData['shipping_address_line2'], 'city' => $postData['shipping_city'], 'state' => $postData['shipping_state'], 'postal_code' => $postData['shipping_zip'], 'country' => $postData['shipping_country'] ];
                if (!$this->userModel->updateAddress($userId, $addressUpdateData)) { error_log("Warning: Failed to save user address during checkout for User ID {$userId}. Order ID {$orderId}."); }
                else { $this->logAuditTrail('user_address_update_checkout', $userId, ['order_id' => $orderId]); }
            }

            // --- Create Payment Intent (unchanged) ---
            $paymentResult = $this->paymentController->createPaymentIntent($total, 'usd', $orderId, $postData['shipping_email']);
            if (!$paymentResult['success'] || empty($paymentResult['client_secret']) || empty($paymentResult['payment_intent_id'])) {
                $this->orderModel->updateStatus($orderId, 'payment_failed'); throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }
            $clientSecret = $paymentResult['client_secret']; $paymentIntentId = $paymentResult['payment_intent_id'];

            // --- Update Order with Payment Intent ID (unchanged) ---
            if (!$this->orderModel->updatePaymentIntentId($orderId, $paymentIntentId)) { throw new Exception("Failed to link Payment Intent ID {$paymentIntentId} to Order ID {$orderId}."); }

            // --- Record Coupon Usage (unchanged) ---
            if ($coupon) { if (!$this->couponController->recordUsage($coupon['id'], $orderId, $userId, $discountAmount)) { error_log("Warning: Failed to record usage for coupon ID {$coupon['id']} on order ID {$orderId}."); } }

            // --- Commit Transaction ---
            $this->commit();

            $this->logAuditTrail('order_pending_payment', $userId, [ 'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN' ]);

            // --- Return Client Secret and Order ID ---
            return $this->jsonResponse([ 'success' => true, 'orderId' => $orderId, 'clientSecret' => $clientSecret ]);

        } catch (Exception $e) {
            $this->rollback(); error_log("Checkout processing error: User {$userId} - " . $e->getMessage());
            $statusCode = 500; if ($e->getCode() === 409 || $e->getCode() === 429) { $statusCode = $e->getCode(); }
            $errorMessage = ($statusCode == 409 || strpos($e->getMessage(), 'stock') !== false) ? $e->getMessage() : (($statusCode === 429) ? $e->getMessage() : 'An error occurred during checkout. Please try again.');
            if ($e instanceof PDOException) { $errorMessage = 'A database error occurred. Please try again later.'; }
            return $this->jsonResponse([ 'success' => false, 'error' => $errorMessage ], $statusCode);
        }
    }

    public function applyCouponAjax() { /* ... unchanged ... */
         $this->requireLogin(true); $this->validateRateLimit('coupon_apply'); $this->validateCSRF();
         $json = file_get_contents('php://input'); $data = json_decode($json, true);
         $code = $this->validateInput($data['code'] ?? null, 'string'); $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); $userId = $this->getUserId();
         if (!$code || $currentSubtotal === false || $currentSubtotal < 0) { return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400); }
         $validationResult = $this->couponController->validateCouponCodeOnly($code, $currentSubtotal);
         if (!$validationResult['valid']) { return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]); }
         $coupon = $validationResult['coupon'];
         if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) { return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']); }
         $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal); $subtotalAfterDiscount = max(0, $currentSubtotal - $discountAmount);
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost;
         return $this->jsonResponse([ 'success' => true, 'message' => 'Coupon applied successfully!', 'coupon_code' => $coupon['code'], 'discount_amount' => number_format($discountAmount, 2), 'new_total_estimate' => number_format($newTotalEstimate, 2) ]);
    }

    public function showOrderConfirmation() { /* ... unchanged ... */
         $this->requireLogin(); $userId = $this->getUserId(); $paymentIntentId = $this->validateInput($_GET['payment_intent'] ?? null, 'string');
         if (!$paymentIntentId || !str_starts_with($paymentIntentId, 'pi_')) { $this->setFlashMessage('Invalid or missing payment confirmation identifier.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
         try {
             if (!$this->paymentController || !($stripeClient = $this->paymentController->getStripeClient())) { error_log("Stripe client not available."); throw new Exception("Payment verification service temporarily unavailable."); }
             $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId, []);
             if ($paymentIntent->status !== 'succeeded') { error_log("Confirmation page accessed for non-succeeded PI: {$paymentIntentId}, Status: {$paymentIntent->status}"); $message = match ($paymentIntent->status) { 'processing' => 'Your payment is still processing.', default => 'Payment confirmation is pending or failed.', }; $this->setFlashMessage($message, 'warning'); $this->redirect('index.php?page=account&section=orders'); return; }
             $order = $this->orderModel->getByPaymentIntentId($paymentIntentId);
             if (!$order || $order['user_id'] !== $userId) { error_log("Order not found or user mismatch for PI: {$paymentIntentId}"); $this->logSecurityEvent('confirmation_access_denied', ['payment_intent_id' => $paymentIntentId, 'logged_in_user' => $userId, 'order_user' => $order['user_id'] ?? null]); $this->setFlashMessage('Order details not found or access denied.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
             $acceptableStatuses = ['processing', 'paid', 'shipped', 'delivered', 'completed']; if (!in_array($order['status'], $acceptableStatuses)) { error_log("Confirmation Warning: PI {$paymentIntentId} succeeded, but order {$order['id']} status is '{$order['status']}'."); }
             $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $userId);
             if (!$fullOrder || empty($fullOrder['items'])) { error_log("Could not fetch full order details for confirmed order ID: {$order['id']}"); $this->setFlashMessage('Could not display full order details.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
             $csrfToken = $this->getCsrfToken(); $bodyClass = 'page-order-confirmation'; $pageTitle = 'Order Confirmation - The Scent';
             echo $this->renderView('order_confirmation', [ 'order' => $fullOrder, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass, 'pageTitle' => $pageTitle ]);
         } catch (\Stripe\Exception\ApiErrorException $e) { error_log("Stripe API error fetching PI {$paymentIntentId}: " . $e->getMessage()); $this->setFlashMessage('Error verifying payment status.', 'error'); $this->redirect('index.php?page=account&action=orders');
         } catch (Exception $e) { error_log("Error showing order confirmation for PI {$paymentIntentId}: " . $e->getMessage()); $this->setFlashMessage('An unexpected error occurred.', 'error'); $this->redirect('index.php?page=account&action=orders'); }
     }


    public function updateOrderStatus($orderId, $status, $trackingInfo = null) { /* ... unchanged ... */
         $this->requireAdmin(true); $orderId = $this->validateInput($orderId, 'int'); $status = $this->validateInput($status, 'string');
         if (!$orderId || !$status) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400); } $order = $this->orderModel->getById($orderId);
         if (!$order) { return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404); }
         $allowedTransitions = [ /* ... transitions ... */ 'pending_payment' => ['paid', 'processing', 'cancelled', 'payment_failed'], 'paid' => ['processing', 'cancelled', 'refunded'], 'processing' => ['shipped', 'cancelled', 'refunded'], 'shipped' => ['delivered', 'refunded'], 'delivered' => ['refunded', 'completed'], 'payment_failed' => ['pending_payment', 'cancelled'], 'cancelled' => [], 'refunded' => [], 'partially_refunded' => ['refunded'], 'disputed' => ['refunded'], 'completed' => [] ];
         if (!isset($allowedTransitions[$order['status']]) || !in_array($status, $allowedTransitions[$order['status']])) { return $this->jsonResponse(['success' => false, 'error' => "Invalid status transition from '{$order['status']}' to '{$status}'."], 400); }
         try { $this->beginTransaction(); $updated = $this->orderModel->updateStatus($orderId, $status);
             if (!$updated) { $currentOrder = $this->orderModel->getById($orderId); if (!$currentOrder || $currentOrder['status'] !== $status) { throw new Exception("Failed to update order status in DB."); } }
             if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) { $trackingNumber = $this->validateInput($trackingInfo['number'], 'string', ['max' => 100]); $carrier = $this->validateInput($trackingInfo['carrier'] ?? null, 'string', ['max' => 100]);
                 if ($trackingNumber) { $trackingUpdated = $this->orderModel->updateTracking( $orderId, $trackingNumber, $carrier );
                      if ($trackingUpdated) { $user = $this->userModel->getById($order['user_id']); $fullOrder = $this->orderModel->getByIdAndUserId($orderId, $order['user_id']);
                           if ($user && $fullOrder && $this->emailService && method_exists($this->emailService, 'sendShippingUpdate')) { $this->emailService->sendShippingUpdate( $fullOrder, $user, $trackingNumber, $carrier ?? '' ); }
                           elseif (!$user) { error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}"); } elseif (!$fullOrder) { error_log("Could not find full order details for shipping update email (Order ID: {$orderId})"); } else { error_log("EmailService or sendShippingUpdate method not available for order {$orderId}"); } }
                      else { error_log("Failed to update tracking info for order {$orderId}"); } } }
             if ($status === 'cancelled' || $status === 'refunded') { error_log("Order {$orderId} status changed to {$status}. Consider adding refund/restock logic here."); }
             $this->commit(); $adminUserId = $this->getUserId(); $this->logAuditTrail('order_status_update', $adminUserId, [ 'order_id' => $orderId, 'new_status' => $status, 'tracking_provided' => ($status === 'shipped' && !empty($trackingNumber)) ]);
             return $this->jsonResponse(['success' => true, 'message' => 'Order status updated successfully.']);
         } catch (Exception $e) { $this->rollback(); error_log("Error updating order status for {$orderId}: " . $e->getMessage()); return $this->jsonResponse(['success' => false, 'error' => 'Failed to update order status.'], 500); }
    }

    // --- Helper Methods ---
    /**
     * Internal helper to validate stock for items in the cart.
     * Expects $cartItems as [productId => ['quantity' => q, ...]]
     */
     private function validateCartStock(array $cartItems): array {
         $errors = [];
         // --- START FIX: Return empty array if cart is empty ---
         if (empty($cartItems)) {
             return []; // Cart being empty is not a stock error
         }
         // --- END FIX ---

         foreach ($cartItems as $productId => $itemData) {
             $quantity = $itemData['quantity'] ?? 0;
             if ($quantity <= 0) continue;

             $product = $this->productModel->getById($productId);
             if (!$product) {
                 // This case should ideally be handled earlier, but good to have defence here
                 $errors[] = "Product ID {$productId} not found during stock check.";
                 continue;
             }
             if (!$this->productModel->isInStock($productId, $quantity)) {
                 $errors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " (Only " . ($product['stock_quantity'] ?? 0) . " left)"; // Be more specific
             }
         }
         return $errors;
     }

} // End of CheckoutController class

```

**Explanation of Fixes:**

1.  **`validateCartStock` Method:**
    *   Changed the initial check: `if (empty($cartItems))` now correctly `return [];` instead of `return ['Cart is empty'];`. An empty cart doesn't represent a stock error.
    *   Improved the error message when stock is insufficient to include the actual quantity remaining: `... (Only X left)`.
2.  **Logging in `processCheckout`:**
    *   Added `error_log` calls at the beginning of `processCheckout` to log whether the `$items` array retrieved from `CartModel` is empty or not. This helps diagnose *why* `validateCartStock` might have been receiving an empty array previously.
    *   Added a specific JSON error response if the cart is found empty at the start of `processCheckout`, providing better feedback than the misleading stock error.

**Retesting:**

1.  Replace your `controllers/CheckoutController.php` with the updated code above.
2.  **Clear Logs:** Optionally clear your `logs/error.log` file to easily see new errors.
3.  Run the simulation again:
    *   Execute `php stripe_test_v4.php > stripe_test_v4_output.html`
    *   Open `stripe_test_v4_output.html` in your browser.
    *   Open the Developer Console (F12).
    *   Click "Run Checkout Simulation".
4.  **Observe & Report:**
    *   Check the browser's on-page log and console for errors.
    *   Check the server's PHP error log (`logs/error.log`) for any new entries generated during the AJAX call to `processCheckout`.

With the fix in `validateCartStock`, the incorrect "Cart is empty" stock error should be gone. If the process *still* fails at the backend call step, the new logging in `processCheckout` and any errors in `logs/error.log` should pinpoint the next issue (e.g., if the cart is *truly* empty when retrieved by the controller, or if another error occurs during order/inventory processing).

---
Okay, I will now carefully merge the necessary fix for the `validateCartStock` method and the added logging into the `processCheckout` method of the provided `controllers/CheckoutController.php`.

**Analysis of Required Changes:**

1.  **`validateCartStock()` Method:** Modify the first `if` statement to `return [];` instead of `return ['Cart is empty'];`. Improve the out-of-stock error message.
2.  **`processCheckout()` Method:** Add `error_log` calls after `$items = $cartModel->getItems();` to check if the cart is empty at that point. Add a specific JSON response if the cart is indeed empty.

**Line-by-Line Comparison and Merging Plan:**

1.  Locate the `validateCartStock` method in the original file.
2.  Change the line `return ['Cart is empty'];` to `return [];`.
3.  Update the error message line inside the `foreach` loop within `validateCartStock`.
4.  Locate the `processCheckout` method.
5.  Find the line `$items = $cartModel->getItems();`.
6.  Insert the logging and empty check block immediately after that line.
7.  Ensure all other parts of the file remain identical to the original (`CheckoutController.php-orig.txt`).

**Updated `controllers/CheckoutController.php`:**

```php
<?php
// controllers/CheckoutController.php (Updated - Fix validateCartStock, add logging)

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

        // --- START: Add Logging for Cart Items ---
        if (empty($items)) {
            error_log("Checkout Error: Cart items are empty for User ID: {$userId} at start of processCheckout.");
             // Return specific error if cart is unexpectedly empty
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart appears empty. Please add items before checkout.'], 400);
         } else {
             error_log("Checkout Info: Found " . count($items) . " distinct item types for User ID: {$userId}");
         }
         // --- END: Add Logging ---


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
        $postData['shipping_address_line2'] = $this->validateInput($_POST['shipping_address_line2'] ?? null, 'string', ['max' => 255]);
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
                'user_id' => $userId, 'subtotal' => $subtotal, 'discount_amount' => $discountAmount, 'coupon_code' => $coupon ? $coupon['code'] : null,
                'coupon_id' => $coupon ? $coupon['id'] : null, 'shipping_cost' => $shipping_cost, 'tax_amount' => $tax_amount, 'total_amount' => $total,
                'shipping_name' => $postData['shipping_name'], 'shipping_email' => $postData['shipping_email'], 'shipping_address' => $postData['shipping_address'],
                'shipping_address_line2' => $postData['shipping_address_line2'] ?? null, 'shipping_city' => $postData['shipping_city'], 'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'], 'shipping_country' => $postData['shipping_country'], 'status' => 'pending_payment',
                'payment_status' => 'pending', 'order_notes' => $orderNotes, 'payment_intent_id' => null
            ];
            $orderId = $this->orderModel->create($orderData);
            if (!$orderId) throw new Exception("Failed to create order record.");

            // --- Create Order Items & Decrement Inventory ---
            $itemStmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItemsForOrder as $productId => $itemData) {
                $itemStmt->execute([$orderId, $productId, $itemData['quantity'], $itemData['price']]);
                $inventoryController = new InventoryController($this->db);
                if (!$inventoryController->updateStock($productId, -$itemData['quantity'], 'sale', $orderId)) {
                    throw new Exception("Failed to update inventory for product ID {$productId}");
                }
            }

            // --- Update User Address if Requested ---
            if ($saveAddress) {
                 $addressUpdateData = [
                    'address_line1' => $postData['shipping_address'], 'address_line2' => $postData['shipping_address_line2'], 'city' => $postData['shipping_city'],
                    'state' => $postData['shipping_state'], 'postal_code' => $postData['shipping_zip'], 'country' => $postData['shipping_country'] ];
                if (!$this->userModel->updateAddress($userId, $addressUpdateData)) { error_log("Warning: Failed to save user address during checkout for User ID {$userId}. Order ID {$orderId}."); }
                else { $this->logAuditTrail('user_address_update_checkout', $userId, ['order_id' => $orderId]); }
            }

            // --- Create Payment Intent ---
            $paymentResult = $this->paymentController->createPaymentIntent($total, 'usd', $orderId, $postData['shipping_email']);
            if (!$paymentResult['success'] || empty($paymentResult['client_secret']) || empty($paymentResult['payment_intent_id'])) {
                $this->orderModel->updateStatus($orderId, 'payment_failed'); throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }
            $clientSecret = $paymentResult['client_secret']; $paymentIntentId = $paymentResult['payment_intent_id'];

            // --- Update Order with Payment Intent ID ---
            if (!$this->orderModel->updatePaymentIntentId($orderId, $paymentIntentId)) { throw new Exception("Failed to link Payment Intent ID {$paymentIntentId} to Order ID {$orderId}."); }

            // --- Record Coupon Usage ---
            if ($coupon) { if (!$this->couponController->recordUsage($coupon['id'], $orderId, $userId, $discountAmount)) { error_log("Warning: Failed to record usage for coupon ID {$coupon['id']} on order ID {$orderId}."); } }

            // --- Commit Transaction ---
            $this->commit();

            $this->logAuditTrail('order_pending_payment', $userId, [ 'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN' ]);

            // --- Return Client Secret and Order ID ---
            return $this->jsonResponse([ 'success' => true, 'orderId' => $orderId, 'clientSecret' => $clientSecret ]);

        } catch (Exception $e) {
            $this->rollback(); error_log("Checkout processing error: User {$userId} - " . $e->getMessage());
            $statusCode = 500; if ($e->getCode() === 409 || $e->getCode() === 429) { $statusCode = $e->getCode(); }
            $errorMessage = ($statusCode == 409 || strpos($e->getMessage(), 'stock') !== false) ? $e->getMessage() : (($statusCode === 429) ? $e->getMessage() : 'An error occurred during checkout. Please try again.');
            if ($e instanceof PDOException) { $errorMessage = 'A database error occurred. Please try again later.'; }
            return $this->jsonResponse([ 'success' => false, 'error' => $errorMessage ], $statusCode);
        }
    }


    /**
     * Handles AJAX request from checkout page to validate and apply a coupon.
     */
    public function applyCouponAjax() {
         // (Method content unchanged - it was already correct)
         $this->requireLogin(true); $this->validateRateLimit('coupon_apply'); $this->validateCSRF();
         $json = file_get_contents('php://input'); $data = json_decode($json, true);
         $code = $this->validateInput($data['code'] ?? null, 'string'); $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); $userId = $this->getUserId();
         if (!$code || $currentSubtotal === false || $currentSubtotal < 0) { return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400); }
         $validationResult = $this->couponController->validateCouponCodeOnly($code, $currentSubtotal);
         if (!$validationResult['valid']) { return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]); }
         $coupon = $validationResult['coupon'];
         if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) { return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']); }
         $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal); $subtotalAfterDiscount = max(0, $currentSubtotal - $discountAmount);
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost;
         return $this->jsonResponse([ 'success' => true, 'message' => 'Coupon applied successfully!', 'coupon_code' => $coupon['code'], 'discount_amount' => number_format($discountAmount, 2), 'new_total_estimate' => number_format($newTotalEstimate, 2) ]);
    }

    /**
     * Displays the order confirmation page. (ROBUST IMPLEMENTATION)
     * Verifies payment success using Stripe Payment Intent ID from URL.
     * REMOVED reliance on session variables.
     */
    public function showOrderConfirmation() {
         // (Method content unchanged from previous robust version)
         $this->requireLogin(); $userId = $this->getUserId(); $paymentIntentId = $this->validateInput($_GET['payment_intent'] ?? null, 'string');
         if (!$paymentIntentId || !str_starts_with($paymentIntentId, 'pi_')) { $this->setFlashMessage('Invalid or missing payment confirmation identifier.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
         try {
             if (!$this->paymentController || !($stripeClient = $this->paymentController->getStripeClient())) { error_log("Stripe client not available."); throw new Exception("Payment verification service temporarily unavailable."); }
             $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId, []);
             if ($paymentIntent->status !== 'succeeded') { error_log("Confirmation page accessed for non-succeeded PI: {$paymentIntentId}, Status: {$paymentIntent->status}"); $message = match ($paymentIntent->status) { 'processing' => 'Your payment is still processing.', default => 'Payment confirmation is pending or failed.', }; $this->setFlashMessage($message, 'warning'); $this->redirect('index.php?page=account&section=orders'); return; }
             $order = $this->orderModel->getByPaymentIntentId($paymentIntentId);
             if (!$order || $order['user_id'] !== $userId) { error_log("Order not found or user mismatch for PI: {$paymentIntentId}"); $this->logSecurityEvent('confirmation_access_denied', ['payment_intent_id' => $paymentIntentId, 'logged_in_user' => $userId, 'order_user' => $order['user_id'] ?? null]); $this->setFlashMessage('Order details not found or access denied.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
             $acceptableStatuses = ['processing', 'paid', 'shipped', 'delivered', 'completed']; if (!in_array($order['status'], $acceptableStatuses)) { error_log("Confirmation Warning: PI {$paymentIntentId} succeeded, but order {$order['id']} status is '{$order['status']}'."); }
             $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $userId);
             if (!$fullOrder || empty($fullOrder['items'])) { error_log("Could not fetch full order details for confirmed order ID: {$order['id']}"); $this->setFlashMessage('Could not display full order details.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
             $csrfToken = $this->getCsrfToken(); $bodyClass = 'page-order-confirmation'; $pageTitle = 'Order Confirmation - The Scent';
             echo $this->renderView('order_confirmation', [ 'order' => $fullOrder, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass, 'pageTitle' => $pageTitle ]);
         } catch (\Stripe\Exception\ApiErrorException $e) { error_log("Stripe API error fetching PI {$paymentIntentId}: " . $e->getMessage()); $this->setFlashMessage('Error verifying payment status.', 'error'); $this->redirect('index.php?page=account&action=orders');
         } catch (Exception $e) { error_log("Error showing order confirmation for PI {$paymentIntentId}: " . $e->getMessage()); $this->setFlashMessage('An unexpected error occurred.', 'error'); $this->redirect('index.php?page=account&action=orders'); }
     }


    // --- Admin Method (Restored - Unchanged from previous working state) ---
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
         // (Method content unchanged - assuming it was already correct)
         $this->requireAdmin(true); $orderId = $this->validateInput($orderId, 'int'); $status = $this->validateInput($status, 'string');
         if (!$orderId || !$status) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400); } $order = $this->orderModel->getById($orderId);
         if (!$order) { return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404); }
         $allowedTransitions = [ /* ... transitions ... */ 'pending_payment' => ['paid', 'processing', 'cancelled', 'payment_failed'], 'paid' => ['processing', 'cancelled', 'refunded'], 'processing' => ['shipped', 'cancelled', 'refunded'], 'shipped' => ['delivered', 'refunded'], 'delivered' => ['refunded', 'completed'], 'payment_failed' => ['pending_payment', 'cancelled'], 'cancelled' => [], 'refunded' => [], 'partially_refunded' => ['refunded'], 'disputed' => ['refunded'], 'completed' => [] ];
         if (!isset($allowedTransitions[$order['status']]) || !in_array($status, $allowedTransitions[$order['status']])) { return $this->jsonResponse(['success' => false, 'error' => "Invalid status transition from '{$order['status']}' to '{$status}'."], 400); }
         try { $this->beginTransaction(); $updated = $this->orderModel->updateStatus($orderId, $status);
             if (!$updated) { $currentOrder = $this->orderModel->getById($orderId); if (!$currentOrder || $currentOrder['status'] !== $status) { throw new Exception("Failed to update order status in DB."); } }
             if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) { $trackingNumber = $this->validateInput($trackingInfo['number'], 'string', ['max' => 100]); $carrier = $this->validateInput($trackingInfo['carrier'] ?? null, 'string', ['max' => 100]);
                 if ($trackingNumber) { $trackingUpdated = $this->orderModel->updateTracking( $orderId, $trackingNumber, $carrier );
                      if ($trackingUpdated) { $user = $this->userModel->getById($order['user_id']); $fullOrder = $this->orderModel->getByIdAndUserId($orderId, $order['user_id']);
                           if ($user && $fullOrder && $this->emailService && method_exists($this->emailService, 'sendShippingUpdate')) { $this->emailService->sendShippingUpdate( $fullOrder, $user, $trackingNumber, $carrier ?? '' ); }
                           elseif (!$user) { error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}"); } elseif (!$fullOrder) { error_log("Could not find full order details for shipping update email (Order ID: {$orderId})"); } else { error_log("EmailService or sendShippingUpdate method not available for order {$orderId}"); } }
                      else { error_log("Failed to update tracking info for order {$orderId}"); } } }
             if ($status === 'cancelled' || $status === 'refunded') { error_log("Order {$orderId} status changed to {$status}. Consider adding refund/restock logic here."); }
             $this->commit(); $adminUserId = $this->getUserId(); $this->logAuditTrail('order_status_update', $adminUserId, [ 'order_id' => $orderId, 'new_status' => $status, 'tracking_provided' => ($status === 'shipped' && !empty($trackingNumber)) ]);
             return $this->jsonResponse(['success' => true, 'message' => 'Order status updated successfully.']);
         } catch (Exception $e) { $this->rollback(); error_log("Error updating order status for {$orderId}: " . $e->getMessage()); return $this->jsonResponse(['success' => false, 'error' => 'Failed to update order status.'], 500); }
    }
    // --- End Admin Method (Restored) ---


    // --- Helper Methods ---
    /**
     * Internal helper to validate stock for items in the cart.
     * Expects $cartItems as [productId => ['quantity' => q, ...]]
     */
     private function validateCartStock(array $cartItems): array {
         $errors = [];
         // --- START FIX: Return empty array if cart is empty ---
         if (empty($cartItems)) {
             return []; // Cart being empty is not a stock error
         }
         // --- END FIX ---

         foreach ($cartItems as $productId => $itemData) {
             // Ensure itemData has quantity key
             $quantity = $itemData['quantity'] ?? 0;
             if ($quantity <= 0) continue;

             $product = $this->productModel->getById($productId);
             if (!$product) {
                 // This case should ideally be handled earlier, but good to have defence here
                 $errors[] = "Product ID {$productId} not found during stock check.";
                 continue;
             }
             if (!$this->productModel->isInStock($productId, $quantity)) {
                 // --- START FIX: Improve out-of-stock message ---
                 $errors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " (Only " . ($product['stock_quantity'] ?? 0) . " left)"; // Be more specific
                 // --- END FIX ---
             }
         }
         return $errors;
     }

} // End of CheckoutController class

```

**Review and Validation:**

*   **`validateCartStock` Fix:** The change `if (empty($cartItems)) { return []; }` is correctly implemented. The improved error message `(Only X left)` is also correctly implemented.
*   **`processCheckout` Logging:** The `error_log` calls and the initial check for an empty cart `if (empty($items)) { ... }` are correctly added after `$items = $cartModel->getItems();`.
*   **Other Code:** All other code sections within the controller remain identical to the original version (`CheckoutController.php-orig.txt`), preserving existing functionality for `showCheckout`, `calculateTax`, `applyCouponAjax`, `showOrderConfirmation`, and the structure of `processCheckout` apart from the added logging and the fixed call to `validateCartStock`.

**Conclusion:**

The updated `controllers/CheckoutController.php` file correctly incorporates the fix for the `validateCartStock` method and adds the requested logging. No other features or functionalities have been omitted or altered compared to the provided original version. This version is ready for testing.

---
https://drive.google.com/file/d/10UXm7z2kPA06IHDhqeMTf3WZ5rKH1TnR/view?usp=sharing, https://drive.google.com/file/d/113ZIkgVIofHhyY6e-9NXmxRNQ2W1Z8Sg/view?usp=sharing, https://drive.google.com/file/d/12oBsB_dhIP9L53VQlVcr9RC7n2W1SqKC/view?usp=sharing, https://drive.google.com/file/d/13rcrk6CPFQilYTWVSxJ8kVBxRiGD1ZWd/view?usp=sharing, https://drive.google.com/file/d/141gZy4x39Ob1fUyv3__3UW_UZxT_qMqo/view?usp=sharing, https://drive.google.com/file/d/16imdccPwOR82rY_7lZQ88UpZ8iOXxHdg/view?usp=sharing, https://drive.google.com/file/d/16myrTzOOfE6-HSfWMi6Si29UA-BJp-VQ/view?usp=sharing, https://drive.google.com/file/d/1AYgfMtN1d1-9u9vu3U8BTnMs_iimvXd3/view?usp=sharing, https://drive.google.com/file/d/1AgLmaeIjyP_PV83jsHwROD9ihzh6WbvP/view?usp=sharing, https://drive.google.com/file/d/1BMgwB5VP_u3hWVJJrF_xSdopPFL3Hb8c/view?usp=sharing, https://drive.google.com/file/d/1C17krIsUYodkrcWgIfReUX2rXbMcPO-x/view?usp=sharing, https://drive.google.com/file/d/1IdpqFzukNcHvMRWIOQdHqCPk9DMZAxeo/view?usp=sharing, https://drive.google.com/file/d/1IhIpJPaLBe1eCOxsWk2Y0mE1tHxOjzZD/view?usp=sharing, https://drive.google.com/file/d/1Ja6Pg3H3lFS5AWQienCzKgCXttxzweYH/view?usp=sharing, https://drive.google.com/file/d/1KRkJq2BdXRYc_vaJaHz_eKPpi27TzHeM/view?usp=sharing, https://drive.google.com/file/d/1LDZCUFOfOCqg6_kDHYtbj0m0gtMvig8p/view?usp=sharing, https://drive.google.com/file/d/1PKNAlUkqL1ZP4h6hglqpTAbjyENOXhQX/view?usp=sharing, https://drive.google.com/file/d/1S4x17i4LNzkbOa9gaWjHD8AW4saRqP6y/view?usp=sharing, https://drive.google.com/file/d/1XnTI3jXqNoAZAysDvKEo4SyfjDEPLqnn/view?usp=sharing, https://drive.google.com/file/d/1YOn5sMoUPaO7sMbdyTIOPIxZAT81tIHl/view?usp=sharing, https://drive.google.com/file/d/1YRvmW5LOIMKZ2pdNCKb6W-jU97b7ufiJ/view?usp=sharing, https://drive.google.com/file/d/1YuJOhriI6V0oJN5vHvHUi6jDCI8iQ1h_/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221ZgPVhzXEiFkRf798JkS5aqhRjYYp2ITI%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1ZgnZ5LL6EXQBvSubIwIRS5ip7fTVAnzI/view?usp=sharing, https://drive.google.com/file/d/1_5BVOFRWYIvmWzI-DzHETqvSoEWfh43l/view?usp=sharing, https://drive.google.com/file/d/1d1mRCTvrOJOeRpNrXLqp3xMV_rJOh4_D/view?usp=sharing, https://drive.google.com/file/d/1ffOzhQ4tc1cWeorO-Hvt67--GqB_pLu5/view?usp=sharing, https://drive.google.com/file/d/1gN6zXQ_PTV2YF9GkwR3IagLx6X3gwxv9/view?usp=sharing, https://drive.google.com/file/d/1gdBD6E5Hq8KUx-beTvTxVW-V9RGmkNw1/view?usp=sharing, https://drive.google.com/file/d/1hJlAzdnBNoo1iYUGdaQsZxsH3txBd-62/view?usp=sharing, https://drive.google.com/file/d/1hqgnM67KOCyQTlqnECCQe7Z1OWzaoyJA/view?usp=sharing, https://drive.google.com/file/d/1i4WEJOJuTv40aMbOGJj-VYVSFzMGCJxB/view?usp=sharing, https://drive.google.com/file/d/1j-O6iOn3jWVDDFefVBLYx77Xld88yq5A/view?usp=sharing, https://drive.google.com/file/d/1jMDj4O1ttVkVt54ABAIxJzMotKwm0mtV/view?usp=sharing, https://drive.google.com/file/d/1k4qyA4zxC9OkHIRrq1elWGF2AFB6ryTm/view?usp=sharing, https://drive.google.com/file/d/1pcYIBRmG2NL1USKqAxzXUcaIpVPw0mw_/view?usp=sharing, https://drive.google.com/file/d/1ribnsuXMCOm8SUPfOF9eD8xNlpIgQJlt/view?usp=sharing, https://drive.google.com/file/d/1soWEHHyAPUfcv_ZzhfB84O0W83iG7AuM/view?usp=sharing, https://drive.google.com/file/d/1tMdkomSIzN5_FIxTpVpCFk4bWzDwocv2/view?usp=sharing, https://drive.google.com/file/d/1tf61PANOM5my7BsXajbAGskinWBc9p_m/view?usp=sharing, https://drive.google.com/file/d/1wyFY4k7qi14bD00E6_2k5vrMVfvGURVQ/view?usp=sharing

---
<think>
1.  **Goal:** Validate the `diff` output comparing `CheckoutController.php-orig.txt` and `CheckoutController.php-new` (the version generated in the previous step with fixes for `validateCartStock` and added logging). Ensure only the intended changes were applied and no other code was accidentally modified or omitted.
2.  **Analyze Diff:**
    *   **Line 1:** File name comment updated. OK.
    *   **Line 169-177:** Added logging and early return if `$items` is empty. This is an intended change. OK.
    *   **Line 214-216:** Removed comments related to the previous (incorrect) fix for address line 2. This is cleanup. OK.
    *   **Line 238-240:** Removed comments. Cleanup. OK.
    *   **Line 270-306:** The multi-line `$orderData` array assignment was collapsed into a single line in the new version. Functionally identical, just formatting. OK.
    *   **Line 311-315:** The multi-line `INSERT INTO order_items` statement was collapsed. Functionally identical. OK.
    *   **Line 319-321:** The multi-line `if (!$inventoryController->updateStock...)` block was collapsed. Functionally identical. OK.
    *   **Line 323-336:** Removed comments related to the previous address fix. Address mapping logic remains the same. OK.
    *   **Line 340-345:** The multi-line `createPaymentIntent` call was collapsed. Functionally identical. OK.
    *   **Line 350-353:** The multi-line `updatePaymentIntentId` check was collapsed. Functionally identical. OK.
    *   **Line 355-360:** The multi-line `recordUsage` check was collapsed. Functionally identical. OK.
    *   **Line 364-367:** The multi-line `logAuditTrail` call was collapsed. Functionally identical. OK.
    *   **Line 370-374:** The multi-line `jsonResponse` call was collapsed. Functionally identical. OK.
    *   **Line 379-391:** The multi-line `catch (Exception $e)` block was collapsed. Logic remains identical. OK.
    *   **Line 397-420:** The multi-line `applyCouponAjax` method content was collapsed. Logic remains identical. OK.
    *   **Line 426-469:** The multi-line `showOrderConfirmation` method content was collapsed. Logic remains identical. OK.
    *   **Line 474-520:** The multi-line `updateOrderStatus` method content was collapsed. Logic remains identical. OK.
    *   **Line 529-531:** The `validateCartStock` method's initial `if` condition was changed from `return ['Cart is empty'];` to `return [];`. This is the intended bug fix. OK.
    *   **Line 537-541:** The error message inside `validateCartStock` was updated to include `(Only X left)`. This is the intended improvement. OK.
3.  **Compare Functionality:** All core logic blocks (constructor, `showCheckout`, `calculateTax`, `processCheckout`, `applyCouponAjax`, `showOrderConfirmation`, `updateOrderStatus`, `validateCartStock`) are present in both versions. The essential changes are the logging added to `processCheckout` and the modification within `validateCartStock`. The collapsing of multi-line statements is purely stylistic and doesn't affect function. No features were omitted.
4.  **Conclusion:** The `diff` confirms that the merge was performed correctly. Only the intended changes (logging in `processCheckout`, fix and message improvement in `validateCartStock`) were applied. No other functional code was accidentally removed or altered. Formatting changes (collapsing multi-line statements) occurred but do not impact functionality.

</think>
**Conclusion:**

Yes, the `diff` output confirms that the changes were applied correctly to `controllers/CheckoutController.php`.

**Validation:**

1.  **Intended Changes:**
    *   **`processCheckout()` Logging:** The lines adding `error_log` calls and the check/return for `if (empty($items))` after retrieving cart items were correctly added (`+++` lines 172-177 vs the original).
    *   **`validateCartStock()` Fix:** The line `return ['Cart is empty'];` was correctly changed to `return [];` (`---` line 675 vs `+++` line 407).
    *   **`validateCartStock()` Message:** The error message was correctly updated to include the remaining stock (`---` line 689 vs `+++` line 419).
2.  **Accidental Omissions/Changes:**
    *   All other methods (`__construct`, `showCheckout`, `calculateTax`, `applyCouponAjax`, `showOrderConfirmation`, `updateOrderStatus`) and the core logic within `processCheckout` remain structurally and functionally identical to the original file.
    *   The numerous `---` and `+++` blocks showing changes in multi-line array definitions, method calls, or exception handling are purely **formatting differences** introduced by the generation process (collapsing multi-line statements/arrays onto fewer lines). They do not represent changes in the actual code logic or functionality.

Therefore, I can confirm that the updated `controllers/CheckoutController.php` correctly incorporates the necessary fixes without omitting or unintentionally altering other parts of the original code.
