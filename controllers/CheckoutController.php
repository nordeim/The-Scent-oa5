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
