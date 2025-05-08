<?php
// stripe_test_v2.php - Comprehensive Checkout Simulation (with forced error display)

// --- Force Error Display for CLI Debugging ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- End Force Error Display ---

// --- PHP Setup ---
define('ROOT_PATH', __DIR__);

// Include Core Files (adjust paths if necessary)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php'; // Provides $pdo
require_once __DIR__ . '/includes/ErrorHandler.php'; // Basic error handling init
require_once __DIR__ . '/includes/SecurityMiddleware.php';

// --- Controllers & Models ---
// Use autoloader if available, otherwise require_once
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Manual requires if no autoloader
    require_once __DIR__ . '/controllers/BaseController.php';
    require_once __DIR__ . '/controllers/PaymentController.php';
    require_once __DIR__ . '/controllers/CheckoutController.php';
    require_once __DIR__ . '/controllers/CouponController.php'; // Dependencies of CheckoutController
    require_once __DIR__ . '/controllers/TaxController.php';
    require_once __DIR__ . '/controllers/InventoryController.php';
    require_once __DIR__ . '/models/Order.php';
    require_once __DIR__ . '/models/Product.php';
    require_once __DIR__ . '/models/Cart.php';
    require_once __DIR__ . '/models/User.php';
    // Add other models if needed by controllers above
    // --- ADD MISSING REQUIRE ---
    require_once __DIR__ . '/includes/EmailService.php'; // Needs to be included for BaseController/PaymentController
    // --- END ADD MISSING REQUIRE ---
}

ErrorHandler::init(); // Initialize basic error handling

// Simulate Logged-in User (Replace with a valid User ID from your DB)
$testUserId = 1; // <<< IMPORTANT: Change this to an existing user ID in your 'users' table
// Fetch user details to ensure test user exists
if (!isset($pdo)) die("FATAL: PDO object not available after includes/db.php"); // Check PDO
$userCheckStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
$userCheckStmt->execute([$testUserId]);
$testUserData = $userCheckStmt->fetch(PDO::FETCH_ASSOC);

if (!$testUserData) {
    die("FATAL: Test User ID {$testUserId} not found in the database. Please use a valid ID.");
}
$testUserEmail = $testUserData['email'];
$testUserName = $testUserData['name'];


// Start session and set user data
if (session_status() === PHP_SESSION_NONE) {
    // Check if headers already sent before starting session (relevant for web, less for CLI)
    if (!headers_sent()) {
        session_start();
    } else {
        // If run via web and headers sent, session won't start properly
        error_log("stripe_test_v2.php Warning: Headers already sent, cannot start session.");
        // Fallback: Use test data without session for CLI execution if needed
        // This might cause issues if controllers strictly require session data
    }
}

// Set session data regardless of start success (for CLI testing)
$_SESSION['user_id'] = $testUserId;
$_SESSION['user_role'] = 'user'; // Assuming 'user' role
$_SESSION['user'] = [
    'id' => $testUserId,
    'name' => $testUserName,
    'email' => $testUserEmail,
    'role' => 'user'
];
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'TestAgentCLI';
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SESSION['last_login'] = time();
$_SESSION['last_regeneration'] = time();


// Simulate Cart (Add at least one VALID product ID from your DB)
$testProductId = 1; // <<< IMPORTANT: Change this to an existing product ID
$testQuantity = 1;
try {
    // Ensure Product exists
    $productCheckStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $productCheckStmt->execute([$testProductId]);
    if (!$productCheckStmt->fetch()) {
         die("FATAL: Test Product ID {$testProductId} not found in the database. Please use a valid ID.");
    }

    $cartModel = new Cart($pdo, $testUserId);
    // Clear existing test cart items first? Optional.
    $cartModel->clearCart(); // Clear previous test items
    if (!$cartModel->addItem($testProductId, $testQuantity)) { // Add item to DB cart
         throw new Exception("CartModel::addItem returned false.");
    }
    $_SESSION['cart_count'] = $cartModel->getCartCount(); // Ensure session count is accurate
    $cartCheckItems = $cartModel->getItems();
    if (empty($cartCheckItems)) {
        throw new Exception("Failed to add/retrieve test item (ID: {$testProductId}) to the cart for user ID {$testUserId}.");
    }
     echo "PHP Setup: Cart simulated successfully for User ID: {$testUserId} with Product ID: {$testProductId}\n"; // CLI feedback
} catch (Exception $e) {
    die("Cart Simulation Error: " . $e->getMessage() . "<br>Please check the testUserId ({$testUserId}) and testProductId ({$testProductId}).");
}


// Instantiate Controllers required for checkout process
// Add try-catch for instantiation
try {
    $paymentController = new PaymentController($pdo);
    $checkoutController = new CheckoutController($pdo, $paymentController); // Pass PaymentController
    echo "PHP Setup: Controllers instantiated successfully.\n"; // CLI feedback
} catch (Throwable $e) { // Catch Throwable for fatal errors like missing classes
     die("FATAL: Error instantiating controllers: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString());
}


// Generate CSRF token (BaseController method preferred if available)
// Directly using SecurityMiddleware for simplicity in this standalone script
$csrfToken = SecurityMiddleware::generateCSRFToken();
echo "PHP Setup: CSRF Token generated: " . $csrfToken . "\n"; // CLI feedback

// --- Sample Shipping Data (mimics form POST) ---
$samplePostData = [
    'shipping_name' => $testUserName,
    'shipping_email' => $testUserEmail,
    'shipping_address' => '123 Test St',
    'shipping_address_line2' => 'Apt 4B',
    'shipping_city' => 'Testville',
    'shipping_state' => 'TS', // Use a state code if your tax logic needs it
    'shipping_zip' => '54321',
    'shipping_country' => 'US', // Use a country code
    'order_notes' => 'Test order generated by stripe_test_v2.php',
    'save_address' => '0', // Or '1' to test address saving
    'csrf_token' => $csrfToken, // Add CSRF token to simulated data
    'applied_coupon_code' => '' // Simulate no coupon initially
];
echo "PHP Setup: Sample POST data prepared.\n"; // CLI feedback

// Check Essential Constants
if (!defined('STRIPE_PUBLIC_KEY') || !defined('BASE_URL')) {
    die("Error: STRIPE_PUBLIC_KEY or BASE_URL constant is not defined. Check config.php.");
}
$stripePublicKey = STRIPE_PUBLIC_KEY;
$baseUrl = BASE_URL;
echo "PHP Setup: Constants checked (Stripe PK / Base URL).\n"; // CLI feedback

// --- HTML Output starts here ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Checkout Simulation (v2)</title>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body { font-family: sans-serif; padding: 15px; font-size: 14px; line-height: 1.5; }
        #message, #log { margin-top: 15px; padding: 10px; border-radius: 4px; border: 1px solid #ccc; }
        #log { background-color: #f8f8f8; font-family: monospace; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; }
        .success { background-color: #e9f7ef; border-color: #a7d7c5; color: #1e4634; }
        .error { background-color: #fceded; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #eef6fc; border-color: #b8d7ea; color: #0c5460; }
        code { background-color: #eee; padding: 2px 4px; border-radius: 3px; }
        button { padding: 10px 15px; font-size: 16px; cursor: pointer; background-color: #1A4D5A; color: white; border: none; border-radius: 4px; margin-top: 15px; }
        button:disabled { background-color: #ccc; cursor: not-allowed; }
        .spinner { display: inline-block; width: 1em; height: 1em; border: 2px solid rgba(0,0,0,.1); border-left-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; margin-left: 5px; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hidden { display: none; }
    </style>
</head>
<body data-stripe-public-key="<?= htmlspecialchars($stripePublicKey) ?>"
      data-base-url="<?= htmlspecialchars($baseUrl) ?>">

    <h1>Stripe Checkout Simulation (v2)</h1>
    <p>This script attempts to simulate the full checkout flow:</p>
    <ol>
        <li>Initialize Stripe.js and Elements.</li>
        <li>Simulate form data submission to the backend (`CheckoutController::processCheckout`).</li>
        <li>Receive `clientSecret` and `orderId` from backend.</li>
        <li>Use `clientSecret` to call `stripe.confirmPayment()`.</li>
    </ol>
    <p>Check the log below and the browser console (F12) for detailed steps and errors.</p>

    <!-- Hidden CSRF Token for JS -->
    <input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Stripe Element Placeholder -->
    <div id="payment-element" style="border:1px solid #ccc; padding: 10px; margin-top:10px; min-height: 40px;">Stripe Payment Element will load here...</div>

    <!-- Message Area -->
    <div id="message" class="info">Messages will appear here...</div>

    <!-- Submit Button -->
    <button id="submit-button">
        <span id="button-text">Run Checkout Simulation</span>
        <span id="spinner" class="spinner hidden"></span>
    </button>

    <!-- Detailed Log Area -->
    <h2>Execution Log:</h2>
    <pre id="log">Starting simulation...</pre>

    <script>
        // --- JavaScript Simulation ---
        const logElement = document.getElementById('log');
        const messageDiv = document.getElementById('message');
        const stripeKey = document.body.dataset.stripePublicKey;
        const csrfToken = document.getElementById('csrf-token-value').value;
        const paymentElementContainer = document.getElementById('payment-element');
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');
        const baseUrl = document.body.dataset.baseUrl || '/';

        let stripe = null;
        let elements = null;

        // Logging helper
        function logStep(step, status, data = null) {
            const timestamp = new Date().toLocaleTimeString();
            let logEntry = `[${timestamp}] ${step}: ${status}`;
            if (data) {
                try {
                    // Attempt to pretty-print JSON, handle other types gracefully
                     logEntry += `\nData: ${JSON.stringify(data, (key, value) =>
                         typeof value === 'object' && value !== null && value.constructor === Object && Object.keys(value).length === 0 ? '{}' : value,
                         2
                     )}`;
                 } catch (e) {
                    logEntry += `\nData: (Could not serialize - ${typeof data})`;
                }
            }
            logElement.textContent += `\n${logEntry}\n`;
            logElement.scrollTop = logElement.scrollHeight; // Auto-scroll
        }

        // Message display helper
        function showStatus(message, type = 'info') {
            messageDiv.textContent = message;
            messageDiv.className = type; // 'info', 'success', 'error'
        }

        // Loading state helper
        function setLoading(isLoading) {
            submitButton.disabled = isLoading;
            spinner.classList.toggle('hidden', !isLoading);
            buttonText.classList.toggle('hidden', isLoading);
        }

        // 1. Initialize Stripe & Elements
        function initializeStripe() {
            logStep("Stripe Init", "Starting...", { key: stripeKey ? stripeKey.substring(0, 10) + '...' : 'MISSING' });
            if (!stripeKey || stripeKey.startsWith('pk_test_51xxx')) {
                const msg = "Invalid or placeholder Stripe Public Key.";
                logStep("Stripe Init", "FAILED", { error: msg });
                showStatus(msg, 'error');
                setLoading(false); // Keep button disabled
                submitButton.disabled = true;
                return false;
            }
            try {
                stripe = Stripe(stripeKey);
                 if (!stripe || typeof stripe.elements !== 'function') {
                     throw new Error("Stripe(key) did not return a valid object.");
                 }
                 logStep("Stripe Init", "SUCCESS", { stripeObjectPresent: !!stripe });
                 const appearance = { theme: 'stripe' }; // Basic appearance
                // --- FIX: Pass Client Secret options IF AVAILABLE LATER ---
                // Elements group created *without* clientSecret initially
                elements = stripe.elements({ appearance });
                logStep("Stripe Elements", "Created", { elementsObjectPresent: !!elements });

                // Mount Payment Element
                const paymentElement = elements.create('payment');
                paymentElement.mount('#payment-element');
                logStep("Payment Element", "Mounted successfully");
                showStatus("Stripe initialized. Click button to simulate checkout.", 'info');
                return true;

            } catch (error) {
                logStep("Stripe Init", "FAILED", { error: error.message, details: error });
                console.error("Stripe Initialization FAILED:", error);
                showStatus(`Stripe Initialization FAILED: ${error.message}`, 'error');
                setLoading(false);
                submitButton.disabled = true;
                return false;
            }
        }

        // 2. Simulate Checkout Button Click
        submitButton.addEventListener('click', async () => {
            if (!stripe || !elements) {
                showStatus("Stripe not initialized correctly.", 'error');
                return;
            }
            setLoading(true);
            showStatus("Simulating checkout process...", 'info');
            logStep("Checkout Click", "Initiated");

            // 3. Call Backend (processCheckout)
            let clientSecret = null;
            let orderId = null;
            let processCheckoutError = null;
            const formData = new FormData();
            // Add sample shipping data from PHP (using CSRF from the page)
            <?php foreach ($samplePostData as $key => $value): ?>
                formData.append('<?= $key ?>', '<?= addslashes($value) ?>');
            <?php endforeach; ?>

            logStep("Backend Call", "Sending data to processCheckout...", Object.fromEntries(formData));

            try {
                const response = await fetch('index.php?page=checkout&action=processCheckout', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData
                });

                logStep("Backend Call", `Received status ${response.status}`);
                const responseText = await response.text(); // Get text first for logging
                 // Avoid logging potentially sensitive clientSecret in full
                 let loggableText = responseText;
                 try {
                     const tempData = JSON.parse(responseText);
                     if (tempData.clientSecret) {
                         loggableText = JSON.stringify({...tempData, clientSecret: tempData.clientSecret.substring(0, 15) + '...'});
                     }
                 } catch(parseErr) {/* Ignore parsing error for logging text */}
                 logStep("Backend Call", "Raw response text (Secret Redacted)", loggableText);


                if (!response.ok) {
                    let errorMsg = `Server returned status ${response.status}.`;
                     try { // Try to get error from JSON
                         const errorData = JSON.parse(responseText);
                         errorMsg = errorData.error || errorMsg;
                     } catch(e){ /* Use default message */ }
                     throw new Error(errorMsg);
                 }

                const data = JSON.parse(responseText); // Parse text again
                logStep("Backend Call", "Parsed JSON response", data);

                if (data.success && data.clientSecret && data.orderId) {
                    clientSecret = data.clientSecret;
                    orderId = data.orderId;
                    logStep("Backend Call", "SUCCESS", { clientSecret: clientSecret.substring(0, 15) + '...', orderId });
                    showStatus(`Backend processed successfully (Order ID: ${orderId}). Confirming payment...`, 'info');
                } else {
                    throw new Error(data.error || 'Backend failed to process checkout.');
                }

            } catch (error) {
                processCheckoutError = error; // Store error
                logStep("Backend Call", "FAILED", { error: error.message, details: error });
                console.error("Backend processCheckout Error:", error);
                showStatus(`Backend Error: ${error.message}`, 'error');
                setLoading(false);
                // Do not proceed to confirmPayment if backend failed
                return;
            }

            // 4. Confirm Payment with Stripe
            if (clientSecret) {
                 logStep("Stripe Confirm", "Attempting stripe.confirmPayment...", { clientSecret: clientSecret.substring(0, 15) + '...' });
                 try {
                     // Construct return URL (doesn't really matter where it goes for this test)
                     const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                     const testReturnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation&simulated=1`;
                     logStep("Stripe Confirm", "Using return_url", testReturnUrl);

                     const { error, paymentIntent } = await stripe.confirmPayment({
                         elements,
                         // clientSecret: clientSecret, // DO NOT pass clientSecret here for confirmPayment
                         confirmParams: {
                             return_url: testReturnUrl,
                         },
                         redirect: 'if_required' // IMPORTANT: Don't redirect away from the test page
                     });


                     if (error) {
                          // Payment failed or requires user action (e.g., 3DS)
                          logStep("Stripe Confirm", "FAILED", { errorType: error.type, errorMessage: error.message, details: error });
                          console.error("Stripe confirmPayment Error:", error);
                          showStatus(`Stripe Payment Confirmation Failed: ${error.message}`, 'error');
                     } else {
                         // If redirect: 'if_required' didn't redirect, paymentIntent should be available
                         if (paymentIntent) {
                             // Payment succeeded or needs further action indicated by status
                             logStep("Stripe Confirm", "SUCCESS/PENDING", { paymentIntentStatus: paymentIntent.status, paymentIntentId: paymentIntent.id });
                             console.log("Stripe confirmPayment Result:", paymentIntent);
                             showStatus(`Stripe Payment Confirmation finished! Status: ${paymentIntent.status}. Check console/webhook.`, 'success');
                              // Typically you'd check paymentIntent.status here ('succeeded', 'requires_action', etc.)
                         } else {
                              // This case might happen if redirect occurs but isn't handled properly by the browser in some scenarios,
                              // or if the promise resolves without error or paymentIntent (unusual).
                              logStep("Stripe Confirm", "UNKNOWN OUTCOME (No Redirect, No Error, No PI)", { error: null, paymentIntent: null });
                              showStatus("Stripe payment confirmation finished with an unknown outcome.", 'warning');
                         }
                     }
                 } catch (confirmError) {
                     logStep("Stripe Confirm", "EXCEPTION", { error: confirmError.message, details: confirmError });
                     console.error("Exception during stripe.confirmPayment:", confirmError);
                     showStatus(`Exception during payment confirmation: ${confirmError.message}`, 'error');
                 } finally {
                     setLoading(false);
                 }
            } else {
                 // This case should have been caught earlier, but double-check
                 logStep("Stripe Confirm", "SKIPPED", { reason: "clientSecret not obtained from backend." });
                 setLoading(false);
            }
        });

        // Initialize Stripe on page load
        if (!initializeStripe()) {
            // Initialization failed, disable button permanently maybe
            logStep("Setup", "Stripe initialization failed. Disabling button.");
            submitButton.disabled = true;
        }

    </script>

</body>
</html>
