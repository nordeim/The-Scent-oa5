Okay, I will carefully review the provided original files (`PaymentController.php-orig.txt`, `CheckoutController.php-orig.txt`, `checkout.php-orig.txt`) and generate the updated versions with the necessary fixes based on our previous analysis.

**Reconfirming Issues:**

1.  **`PaymentController.php`:** The original (`PaymentController.php-orig.txt`) matches the already corrected code from `content_of_code_files_3.md`. The problematic `$_SESSION['last_order_id'] = $order['id'];` line within `handleSuccessfulPayment` was indeed already removed in this version. Therefore, this file doesn't require further modification for the session dependency issue in the webhook.
2.  **`CheckoutController.php`:** The `showOrderConfirmation` method in the original (`CheckoutController.php-orig.txt`) still contains the flawed logic relying on session data or Payment Intent status *without* verifying ownership or fetching full details correctly. This **needs the rework** as planned.
3.  **`views/checkout.php`:** The original (`checkout.php-orig.txt`) accesses nested array keys like `$item['product']['image']` without defensive checks (`??`), making it susceptible to PHP Notices/Warnings if the data structure isn't exactly as expected. This **needs the robustness fix**.

**Plan:**

1.  Provide the already corrected `PaymentController.php` code (which matches `PaymentController.php-orig.txt` and the code block from `content_of_code_files_3.md`).
2.  Generate an updated `CheckoutController.php` based on `CheckoutController.php-orig.txt`, replacing *only* the `showOrderConfirmation` method with the robust Stripe API verification logic.
3.  Generate an updated `views/checkout.php` based on `checkout.php-orig.txt`, adding `??` null coalescing operators with sensible defaults for all potentially problematic array accesses within the cart items loop and related elements.

---

**1. Updated `controllers/PaymentController.php`**

*(This code matches `PaymentController.php-orig.txt` and the version in `content_of_code_files_3.md`, as the session variable removal was already present)*

```php
<?php
// controllers/PaymentController.php (Updated - Removed Session Dependency, Added Getter)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../config.php'; // Keep config include

// Use statement for Stripe classes
use Stripe\Stripe; // Added for setting API key globally if needed
use Stripe\StripeClient;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
// Include models needed for webhook actions
require_once __DIR__ . '/../models/Order.php'; // Needed for updating order status
require_once __DIR__ . '/../models/User.php';  // Needed for sending emails
require_once __DIR__ . '/../models/Cart.php';  // Needed for clearing cart

class PaymentController extends BaseController {
    private ?StripeClient $stripe; // Allow null initialization
    private ?string $webhookSecret; // Allow null initialization
    private Order $orderModel; // Add Order model instance
    // EmailService is inherited from BaseController

    public function __construct($pdo = null) {
        parent::__construct($pdo); // BaseController handles EmailService init now

        // Ensure PDO is available if needed directly or via BaseController
        if (!$this->db) {
             error_log("PDO connection not available in PaymentController constructor.");
             // Handle appropriately - maybe throw exception
             $this->stripe = null;
             $this->webhookSecret = null;
             return;
        }
        $this->orderModel = new Order($this->db); // Initialize Order model

        // Ensure Stripe keys are defined
        if (!defined('STRIPE_SECRET_KEY') || !defined('STRIPE_WEBHOOK_SECRET')) {
            error_log("Stripe keys are not defined in config.php");
            $this->stripe = null;
            $this->webhookSecret = null;
            return; // Stop initialization if keys are missing
        }

        // Use try-catch for external service initialization
        try {
            // It's generally recommended to set the API key globally once if possible,
            // but StripeClient constructor also works fine.
            // Stripe::setApiKey(STRIPE_SECRET_KEY);
            $this->stripe = new StripeClient(STRIPE_SECRET_KEY);
            $this->webhookSecret = STRIPE_WEBHOOK_SECRET;
        } catch (\Exception $e) {
             error_log("Failed to initialize Stripe client: " . $e->getMessage());
             $this->stripe = null; // Ensure stripe is null if init fails
             $this->webhookSecret = null;
        }
    }

    /**
     * Provides access to the initialized Stripe client.
     *
     * @return StripeClient|null The StripeClient instance or null if initialization failed.
     */
    public function getStripeClient(): ?StripeClient { // New Getter Method
        return $this->stripe;
    }

    /**
     * Create a Stripe Payment Intent.
     * Returns payment_intent_id along with client_secret.
     *
     * @param float $amount Amount in major currency unit (e.g., dollars).
     * @param string $currency 3-letter ISO currency code.
     * @param int $orderId Internal order ID for metadata.
     * @param string $customerEmail Email for receipt/customer matching.
     * @return array ['success' => bool, 'client_secret' => string|null, 'payment_intent_id' => string|null, 'error' => string|null]
     */
    public function createPaymentIntent(float $amount, string $currency = 'usd', int $orderId = 0, string $customerEmail = ''): array {
        // (Method content unchanged from previous version - it was already correct)
        if (!$this->stripe) {
             return ['success' => false, 'error' => 'Payment system unavailable.', 'client_secret' => null, 'payment_intent_id' => null];
        }

        $paymentIntentParams = []; // Define outside try for logging
        try {
            if ($amount <= 0.50) throw new InvalidArgumentException('Invalid payment amount (must be at least 0.50).'); // Stripe min amount
            $currency = strtolower(trim($currency));
            if (strlen($currency) !== 3) throw new InvalidArgumentException('Invalid currency code.');
            if ($orderId <= 0) throw new InvalidArgumentException('Invalid Order ID for Payment Intent.');

            $paymentIntentParams = [
                'amount' => (int)round($amount * 100), // Convert to cents
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'internal_order_id' => $orderId, // Use a clear key like internal_order_id
                    'user_id' => $this->getUserId() ?? 'guest', // Use helper
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                ]
            ];

             if (!empty($customerEmail) && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                 $paymentIntentParams['receipt_email'] = $customerEmail;
             }

            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentParams);

            // --- Return Payment Intent ID ---
            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id // Include the ID
            ];
            // --- End Return Payment Intent ID ---

        } catch (ApiErrorException $e) {
            error_log("Stripe API Error creating PaymentIntent for Order {$orderId}: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false, 'error' => 'Payment processing failed. Please try again.',
                'client_secret' => null, 'payment_intent_id' => null
            ];
        } catch (InvalidArgumentException $e) {
             error_log("Payment Intent Creation Invalid Argument for Order {$orderId}: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
             return [
                 'success' => false, 'error' => $e->getMessage(),
                 'client_secret' => null, 'payment_intent_id' => null
             ];
         } catch (Exception $e) {
            error_log("Payment Intent Creation Error for Order {$orderId}: " . $e->getMessage() . " | Params: " . json_encode($paymentIntentParams));
            return [
                'success' => false, 'error' => 'Could not initialize payment. Please try again later.',
                'client_secret' => null, 'payment_intent_id' => null
            ];
        }
    }


    /**
     * Handles incoming Stripe webhook events.
     */
    public function handleWebhook() {
        // (Method content largely unchanged, only the call to handleSuccessfulPayment is affected indirectly)
        if (!$this->stripe || !$this->webhookSecret) {
             http_response_code(503); // Service Unavailable
             error_log("Webhook handler cannot run: Stripe client or secret not initialized.");
             echo json_encode(['error' => 'Webhook configuration error.']);
             exit;
        }

        $payload = @file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        if (!$sigHeader) {
             error_log("Webhook Error: Missing Stripe signature header.");
             $this->jsonResponse(['error' => 'Missing signature'], 400);
             return;
        }
        if (empty($payload)) {
             error_log("Webhook Error: Empty payload received.");
             $this->jsonResponse(['error' => 'Empty payload'], 400);
             return;
        }

        $event = null; // Define $event outside try block
        try {
            $event = Webhook::constructEvent( $payload, $sigHeader, $this->webhookSecret );
        } catch (\UnexpectedValueException $e) {
            error_log("Webhook Error: Invalid payload. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid payload'], 400); return;
        } catch (SignatureVerificationException $e) {
            error_log("Webhook Error: Invalid signature. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Invalid signature'], 400); return;
        } catch (\Exception $e) {
            error_log("Webhook Error: Event construction failed. " . $e->getMessage());
            $this->jsonResponse(['error' => 'Webhook processing error'], 400); return;
        }

        // Handle the event
        try {
            // --- Start Transaction ---
            $this->beginTransaction();

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handleSuccessfulPayment($event->data->object); // This method is now simplified
                    break;

                case 'payment_intent.payment_failed':
                    $this->handleFailedPayment($event->data->object);
                    break;

                case 'charge.succeeded':
                     $this->handleChargeSucceeded($event->data->object);
                     break;

                case 'charge.dispute.created':
                    $this->handleDisputeCreated($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleRefund($event->data->object);
                    break;

                default:
                    error_log('Webhook Info: Received unhandled event type ' . $event->type);
            }

            // --- Commit Transaction ---
            $this->commit();
            $this->jsonResponse(['success' => true, 'message' => 'Webhook received']);

        } catch (Exception $e) {
            // --- Rollback Transaction ---
            $this->rollback();
            $eventType = $event ? $event->type : 'UNKNOWN';
            error_log("Webhook Handling Error (Event: {$eventType}): " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
            // Respond 500 to encourage Stripe retry for potentially transient errors
            $this->jsonResponse(
                ['success' => false, 'error' => 'Internal server error handling webhook.'],
                500
            );
        }
    }

    /**
     * Handles the payment_intent.succeeded event.
     * Updates order status, sends confirmation email, clears cart.
     * CRITICAL: REMOVED session variable setting.
     */
    private function handleSuccessfulPayment(\Stripe\PaymentIntent $paymentIntent): void {
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id);

         if (!$order) {
              $errorMessage = "Webhook Critical: PaymentIntent {$paymentIntent->id} succeeded but no matching order found in DB.";
              error_log($errorMessage);
              $this->logSecurityEvent('webhook_order_mismatch', ['payment_intent_id' => $paymentIntent->id, 'event_type' => 'payment_intent.succeeded']);
              return; // Acknowledge webhook, log error
         }

         // Idempotency check: Check if the order status indicates it's already processed or finalized.
         $processedStatuses = ['processing', 'paid', 'shipped', 'delivered', 'completed', 'refunded', 'partially_refunded', 'cancelled'];
         if (in_array($order['status'], $processedStatuses)) {
             error_log("Webhook Info: Received successful payment event for already processed order ID {$order['id']}. Status: {$order['status']}");
             return; // Avoid reprocessing
         }

        // If status is 'pending_payment' or 'payment_failed', proceed to update
        $newStatus = 'processing'; // Or 'paid' - depends on your workflow post-payment
        $updated = $this->orderModel->updateStatus($order['id'], $newStatus);

        if (!$updated) {
            // Re-check status in case of race condition
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || !in_array($currentOrder['status'], $processedStatuses)) {
                 // Throw exception only if update truly failed and status isn't already processed
                 error_log("Failed DB update for order: " . json_encode($order));
                 throw new Exception("Failed to update order ID {$order['id']} status to '{$newStatus}'.");
            } else {
                 // Status was likely updated by another process between initial check and update attempt
                 error_log("Webhook Info: Order ID {$order['id']} status already updated, skipping redundant update in handleSuccessfulPayment.");
            }
        } else {
             error_log("Webhook Success: Updated order ID {$order['id']} status to '{$newStatus}' for PaymentIntent {$paymentIntent->id}.");
        }

        // --- Email and Cart Clearing Logic (Unchanged) ---
        // Fetch full order details (including items) for the email
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             if ($this->emailService && method_exists($this->emailService, 'sendOrderConfirmation')) {
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                       // Send email confirmation
                       $emailSent = $this->emailService->sendOrderConfirmation($fullOrder, $user);
                       if ($emailSent) {
                            error_log("Webhook Success: Order confirmation email queued for order ID {$fullOrder['id']}.");
                       } else {
                            error_log("Webhook Warning: sendOrderConfirmation returned false for order ID {$fullOrder['id']}.");
                       }
                  } else {
                       error_log("Webhook Warning: Could not fetch user data for order confirmation email (Order ID: {$fullOrder['id']}, User ID: {$fullOrder['user_id']}).");
                  }
             } else {
                  error_log("Webhook Warning: EmailService or sendOrderConfirmation method not available for order ID {$fullOrder['id']}.");
             }
        } else {
             error_log("Webhook Warning: Could not fetch full order details for notification (Order ID: {$order['id']}).");
        }

        // Clear the user's cart (only if user_id is valid)
        if ($order['user_id']) {
            try {
                // Ensure Cart class is available
                if (!class_exists('Cart')) require_once __DIR__ . '/../models/Cart.php';
                $cartModel = new Cart($this->db, $order['user_id']);
                $cartModel->clearCart();
                error_log("Webhook Success: Cart cleared for user ID {$order['user_id']} after order {$order['id']} payment.");
            } catch (Exception $cartError) {
                 error_log("Webhook Warning: Failed to clear cart for user ID {$order['user_id']} after order {$order['id']} payment: " . $cartError->getMessage());
            }
        }
    }

    /**
     * Handles the payment_intent.payment_failed event.
     */
    private function handleFailedPayment(\Stripe\PaymentIntent $paymentIntent): void {
        // (Method content unchanged)
         $order = $this->orderModel->getByPaymentIntentId($paymentIntent->id);
         if (!$order) {
              error_log("Webhook Warning: PaymentIntent {$paymentIntent->id} failed but no matching order found.");
              return; // Acknowledge webhook
         }
         // Idempotency check
         if ($order['status'] === 'payment_failed' || in_array($order['status'], ['cancelled', 'paid', 'processing', 'shipped', 'delivered', 'completed'])) {
              error_log("Webhook Info: Received failed payment event for already resolved/failed order ID {$order['id']}. Status: {$order['status']}");
              return;
          }

        $newStatus = 'payment_failed';
        $updated = $this->orderModel->updateStatus($order['id'], $newStatus);

        if (!$updated) {
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || $currentOrder['status'] !== $newStatus) {
                throw new Exception("Failed to update order ID {$order['id']} status to '{$newStatus}'.");
            }
        } else {
            error_log("Webhook Info: Updated order ID {$order['id']} status to '{$newStatus}' for PaymentIntent {$paymentIntent->id}.");
        }

        // Send payment failed notification (optional)
        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             // Assuming method exists: sendPaymentFailedNotification(array $order, array $user)
             if ($this->emailService && method_exists($this->emailService, 'sendPaymentFailedNotification')) {
                  $userModel = new User($this->db);
                  $user = $userModel->getById($fullOrder['user_id']);
                  if ($user) {
                       // $this->emailService->sendPaymentFailedNotification($fullOrder, $user); // Uncomment if method exists
                       error_log("Webhook Info: Payment failed email would be queued for order ID {$fullOrder['id']}.");
                  } else {
                      error_log("Webhook Warning: Could not fetch user for failed payment email (Order ID: {$fullOrder['id']}).");
                  }
             }
        }
    }

    /**
     * Handles the charge.succeeded event (often informational if using PaymentIntents).
     */
     private function handleChargeSucceeded(\Stripe\Charge $charge): void {
         // (Method content unchanged)
         error_log("Webhook Info: Charge {$charge->id} succeeded (PaymentIntent: {$charge->payment_intent}). Order status managed via PaymentIntent events.");
     }

    /**
     * Handles the charge.dispute.created event.
     */
    private function handleDisputeCreated(\Stripe\Dispute $dispute): void {
        // (Method content unchanged)
        $order = $this->orderModel->getByPaymentIntentId($dispute->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Dispute {$dispute->id} created for PI {$dispute->payment_intent} but no matching order found.");
              return; // Acknowledge webhook
         }

        $newStatus = 'disputed';
        $updated = $this->orderModel->updateStatusAndDispute($order['id'], $newStatus, $dispute->id);

        if (!$updated) {
             $currentOrder = $this->orderModel->getById($order['id']);
             if (!$currentOrder || $currentOrder['status'] !== $newStatus) {
                 throw new Exception("Failed to update order ID {$order['id']} dispute status.");
             }
        } else {
             error_log("Webhook Alert: Order ID {$order['id']} status updated to '{$newStatus}' due to Dispute {$dispute->id}.");
        }

        $this->logSecurityEvent('stripe_dispute_created', [
             'order_id' => $order['id'],
             'dispute_id' => $dispute->id,
             'payment_intent_id' => $dispute->payment_intent,
             'amount' => $dispute->amount,
             'reason' => $dispute->reason
        ]);
        if ($this->emailService && method_exists($this->emailService, 'sendAdminDisputeAlert')) {
             // $this->emailService->sendAdminDisputeAlert($order['id'], $dispute->id, $dispute->reason, $dispute->amount); // Uncomment if method exists
             error_log("Webhook Info: Admin dispute alert would be queued for order ID {$order['id']}.");
        }
    }

    /**
     * Handles the charge.refunded event.
     */
    private function handleRefund(\Stripe\Charge $charge): void {
         // (Method content unchanged)
         $refund = $charge->refunds->data[0] ?? null;
         if (!$refund) {
             error_log("Webhook Warning: Received charge.refunded event for Charge {$charge->id} but no refund data found.");
             return;
         }

         $order = $this->orderModel->getByPaymentIntentId($charge->payment_intent);
         if (!$order) {
              error_log("Webhook Warning: Refund {$refund->id} processed for PI {$charge->payment_intent} but no matching order found.");
              return;
         }

         $isPartialRefund = ($charge->amount_refunded < $charge->amount_captured);
         $newStatus = $isPartialRefund ? 'partially_refunded' : 'refunded';
         $newPaymentStatus = $newStatus;

         $updated = $this->orderModel->updateRefundStatus($order['id'], $newStatus, $newPaymentStatus, $refund->id);

        if (!$updated) {
            $currentOrder = $this->orderModel->getById($order['id']);
            if (!$currentOrder || !in_array($currentOrder['status'], ['refunded', 'partially_refunded'])) {
                throw new Exception("Failed to update order ID {$order['id']} refund status.");
            }
        } else {
            error_log("Webhook Info: Order ID {$order['id']} status updated to '{$newStatus}' due to Refund {$refund->id}.");
        }

        $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $order['user_id']);
        if ($fullOrder) {
             if ($this->emailService && method_exists($this->emailService, 'sendRefundConfirmation')) {
                   $userModel = new User($this->db);
                   $user = $userModel->getById($fullOrder['user_id']);
                   if ($user) {
                        // $this->emailService->sendRefundConfirmation($fullOrder, $user, $refund->amount / 100.0); // Uncomment if method exists
                        error_log("Webhook Info: Refund confirmation email would be queued for order ID {$fullOrder['id']}.");
                   } else {
                        error_log("Webhook Warning: Could not fetch user for refund confirmation email (Order ID: {$fullOrder['id']}).");
                   }
             }
        }
    }
} // End PaymentController class
```

---

**2. Updated `controllers/CheckoutController.php`**

*(Based on `CheckoutController.php-orig.txt`, with the `showOrderConfirmation` method replaced)*

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
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            if (!$productId || $quantity <= 0) continue; // Skip if invalid

            if (!$this->productModel->isInStock($productId, $quantity)) {
                $this->setFlashMessage("Item '".htmlspecialchars($item['name'] ?? 'Product')."' is out of stock. Please update your cart.", 'error');
                $this->redirect('index.php?page=cart');
                return;
            }
            $price = $item['price'] ?? 0;
            $lineSubtotal = $price * $quantity;
            $cartItems[] = [
                'product' => $item,
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
         foreach ($items as $item) { $subtotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0); }
         return (float)$subtotal;
    }

    /**
     * Processes the checkout form submission via AJAX.
     * Creates order, handles inventory, coupons, and initiates payment intent.
     */
    public function processCheckout() {
        // (Method content largely unchanged - it was already correct)
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin(true); // AJAX request
        $this->validateCSRF();

        $userId = $this->getUserId();
        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart is empty.'], 400);
        }

        // --- Collect Cart Details ---
        $cartItemsForOrder = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;
            $price = $item['price'] ?? 0;
            $name = $item['name'] ?? 'Unknown Product';
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
            $stockErrors = $this->validateCartStock($cartItemsForOrder);
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
                // Decrement stock
                if (!$this->inventoryController->updateStock($productId, -$itemData['quantity'], 'sale', $orderId)) {
                    // updateStock should throw exception on failure, caught below
                    // For extra safety, check return value if updateStock might return false on failure
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
            $errorMessage = ($e->getCode() == 409 || strpos($e->getMessage(), 'stock') !== false)
                            ? $e->getMessage() // Show specific stock errors
                            : 'An error occurred during checkout. Please try again.';
             if ($e instanceof PDOException) { $errorMessage = 'A database error occurred. Please try again later.'; }

            return $this->jsonResponse([
                'success' => false,
                'error' => $errorMessage
            ], 500); // Use 500 for server errors
        }
    }


    /**
     * Handles AJAX request from checkout page to validate and apply a coupon.
     */
    public function applyCouponAjax() {
         // (Method content unchanged - it was already correct)
         $this->requireLogin(true); // AJAX
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
         $this->requireLogin(); // Ensure user is logged in
         $userId = $this->getUserId();

         // 1. Get Payment Intent ID from URL
         $paymentIntentId = $this->validateInput($_GET['payment_intent'] ?? null, 'string');

         if (!$paymentIntentId || !str_starts_with($paymentIntentId, 'pi_')) { // Basic format check
             $this->setFlashMessage('Invalid or missing payment confirmation identifier.', 'error');
             $this->redirect('index.php?page=account&action=orders'); // Use action=orders for consistency
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
                  $this->redirect('index.php?page=account&action=orders');
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
                  $this->redirect('index.php?page=account&action=orders');
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
                  $this->redirect('index.php?page=account&action=orders');
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

**3. Updated `views/checkout.php`**

*(Based on `checkout.php-orig.txt`, with defensive `??` operators added)*

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
                                   value="<?= htmlspecialchars($userAddress['address_line1'] ?? '') ?>">
                        </div>

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
                                $productId = $item['product']['id'] ?? ''; // Use empty string or 0 if appropriate
                                $imageUrl = $item['product']['image'] ?? '/images/placeholder.jpg';
                                $productName = $item['product']['name'] ?? 'Unknown Product';
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
             console.log("Stripe Payment Element mounted.");
        } catch (stripeError) {
            console.error("Stripe initialization error:", stripeError);
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

                const response = await fetch('index.php?page=checkout&action=processCheckout', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: checkoutFormData
                });

                // Log status and try to parse JSON regardless of status code initially
                console.log("Process Checkout Response Status:", response.status);
                const data = await response.json(); // Try to parse JSON
                console.log("Process Checkout Response Data:", data);

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
</style>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

---

**Summary of Changes and Review:**

1.  **`controllers/PaymentController.php`:**
    *   **Review:** Confirmed that the provided `-orig.txt` file already contained the corrected code where the session variable assignment in `handleSuccessfulPayment` was removed.
    *   **Action:** No further changes were needed. The file provided above is the correct, final version.

2.  **`controllers/CheckoutController.php`:**
    *   **Review:** Confirmed the `showOrderConfirmation` method in the `-orig.txt` file had the flawed session-based logic.
    *   **Action:** Replaced the *entire* `showOrderConfirmation` method with the robust implementation that verifies the Payment Intent status via the Stripe API and checks order ownership against the database. Added necessary `try/catch` blocks for error handling during API calls and DB lookups. No other methods were modified.

3.  **`views/checkout.php`:**
    *   **Review:** Confirmed that accesses to `$item['product']['...']` and other array keys within the `foreach ($cartItems as $item)` loop and elsewhere were direct and lacked defensive `??` operators.
    *   **Action:** Added null coalescing operators (`??`) with appropriate defaults (e.g., `''`, `0`, `'/images/placeholder.jpg'`, `false`) to all potentially problematic array accesses within the loop and related elements (`$imageUrl`, `$productName`, `$productId`, `$quantity`, `$lineSubtotal`, `$categoryName`, `$backorderAllowed`, `$stockQuantity`). This makes the view rendering more resilient to unexpected data structures or missing keys, addressing the likely cause of the PHP Notices/Warnings leading to the "Oops" error page. The JavaScript part (`initCheckoutPage`) remains unchanged as it was deemed robust previously.

**Conclusion:**

The provided code updates address the specific issues identified:

*   The session dependency flaw in `PaymentController`'s webhook handler is confirmed removed (it was already fixed in the provided source).
*   The critical flaw in `CheckoutController::showOrderConfirmation`'s logic has been fixed by implementing the Stripe API verification method.
*   `views/checkout.php` has been made more robust against potential PHP Notices/Warnings by adding defensive null coalescing operators, which should resolve the "Oops" error encountered after login when rendering the checkout page.

With these changes, the checkout flow should be more stable. However, remember to **check your `logs/error.log`** if the "Oops" error persists, as it will contain the specific error message needed for final debugging. Also, keep the recommendations regarding cart storage standardization and consistent rate limiting in mind for future improvements.

---
https://drive.google.com/file/d/1-sUAoBUl6pA64buwufYHBSSi5yZ62Raj/view?usp=sharing, https://drive.google.com/file/d/15bitqhWNq2U0oVwLAq5XykB5yTo0Xjyh/view?usp=sharing, https://drive.google.com/file/d/16HHyF3fUpldmiLxlpEE8NkjgAsjVRkWR/view?usp=sharing, https://drive.google.com/file/d/1A8TTC_ybZjQQV7Twy55kS73hvmPjAbdN/view?usp=sharing, https://drive.google.com/file/d/1Ch0NuZ3uSrLmZQVw9HA32lIpGpJJjde6/view?usp=sharing, https://drive.google.com/file/d/1CqlP3iZUNeT-Iz2HZte3dAVlZ3EE6AU9/view?usp=sharing, https://drive.google.com/file/d/1D54uflzlmAE5tS77KFtf50m3cfIDnMmU/view?usp=sharing, https://drive.google.com/file/d/1FeYPFvldnvjsirrFh5DF9M8WJwU4gqFf/view?usp=sharing, https://drive.google.com/file/d/1VL-sFQfsQba_oE3Qicmgr9nFd9gVe0jX/view?usp=sharing, https://drive.google.com/file/d/1bIM1MPPoku21T6LdQ92gj0H2KPoebDtJ/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221bdZUHlK9NGrEbspVNgspBdWk9Qnaz5jH%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1cE1TiQhqSq2l-5C9SnTS9GM2QKl74j2x/view?usp=sharing, https://drive.google.com/file/d/1gzmYOqQ_bMhQzcuMpipWfN591B9audJl/view?usp=sharing, https://drive.google.com/file/d/1kP97pYJWb-za26V7uitOe78EnSKN07uc/view?usp=sharing, https://drive.google.com/file/d/1kPkUVoSRrZ4gVXc5J5ohl4mW9MA9VB8E/view?usp=sharing, https://drive.google.com/file/d/1lxZ6G3BnCriOybsHE2Iq9cN8NMDAX26G/view?usp=sharing, https://drive.google.com/file/d/1pTUpeOtzldtz3zla_75B1fxEsc_N9Qku/view?usp=sharing, https://drive.google.com/file/d/1u4CMSYpcpxsNO1oYmN_AmAw4SgyB8Pxz/view?usp=sharing
