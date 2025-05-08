<?php
// stripe_test_v4.php - Comprehensive Checkout Simulation (Using Composer Classmap Autoloader)

// --- START: Fatal Error Capture ---
function captureFatalError() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) { header('Content-Type: text/plain; charset=UTF-8'); }
        echo "\n\n--- FATAL ERROR DETECTED ---\n";
        echo "Type:    " . ($error['type'] ?? 'Unknown') . "\n";
        echo "Message: " . ($error['message'] ?? 'N/A') . "\n";
        echo "File:    " . ($error['file'] ?? 'N/A') . "\n";
        echo "Line:    " . ($error['line'] ?? 'N/A') . "\n";
        echo "---------------------------\n\n";
    }
}
register_shutdown_function('captureFatalError');
// --- END: Fatal Error Capture ---

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', __DIR__);

// --- Core Includes FIRST ---
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php'; // Provides $pdo
// ErrorHandler should be autoloaded now via classmap
// SecurityMiddleware should be autoloaded now via classmap

// --- Use Composer Autoloader ---
$autoloader_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader_path)) {
    echo "PHP Setup: Using Composer autoloader.\n";
    require_once $autoloader_path;
} else {
    // If autoloader is missing after running composer dump-autoload, something is wrong
     die("FATAL: Composer autoloader not found at {$autoloader_path}. Run 'composer dump-autoload -o'.\n");
}

// --- Check if core classes are loaded by Autoloader ---
if (!isset($pdo)) die("FATAL: PDO object not available. Check includes/db.php.\n");
if (!class_exists('ErrorHandler')) die("FATAL: ErrorHandler class not loaded by autoloader.\n");
if (!class_exists('SecurityMiddleware')) die("FATAL: SecurityMiddleware class not loaded by autoloader.\n");
if (!class_exists('BaseController')) die("FATAL: BaseController class not loaded by autoloader.\n");
if (!class_exists('EmailService')) die("FATAL: EmailService class not loaded by autoloader.\n");
// --- End Check core classes ---

// Initialize Error Handler AFTER class definition is loaded
ErrorHandler::init();
echo "PHP Setup: ErrorHandler initialized.\n";


// --- Simulate Logged-in User ---
$testUserId = 2; // Use the ID confirmed previously
$testUserData = null;
try {
    // Check if User class is loaded
    if (!class_exists('User')) die("FATAL: User class not loaded by autoloader.\n");
    $userCheckStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
    $userCheckStmt->execute([$testUserId]);
    $testUserData = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("FATAL: Database error checking test user: " . $e->getMessage() . "\n"); }
if (!$testUserData) { die("FATAL: Test User ID {$testUserId} not found in the database. Please use a valid ID.\n"); }
$testUserEmail = $testUserData['email']; $testUserName = $testUserData['name'];
echo "PHP Setup: Test user found (ID: {$testUserId}, Email: {$testUserEmail}).\n";

// --- Start Session ---
if (session_status() === PHP_SESSION_NONE) { if (!headers_sent()) { session_start(); echo "PHP Setup: Session started.\n"; } else { error_log("stripe_test_v4.php Warning: Headers already sent, cannot start session."); echo "PHP Setup Warning: Headers already sent, cannot start session.\n"; } } else { echo "PHP Setup: Session already active.\n"; }
$_SESSION['user_id'] = $testUserId; $_SESSION['user_role'] = 'user'; $_SESSION['user'] = [ 'id' => $testUserId, 'name' => $testUserName, 'email' => $testUserEmail, 'role' => 'user' ]; $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'TestAgentCLI'; $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; $_SESSION['last_login'] = time(); $_SESSION['last_regeneration'] = time();

// --- Simulate Cart ---
$testProductId = 1; // Use the ID confirmed previously
$testQuantity = 1;
try {
    if (!class_exists('Product')) die("FATAL: Product class not loaded by autoloader.\n");
    $productCheckStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?"); $productCheckStmt->execute([$testProductId]); if (!$productCheckStmt->fetch()) { die("FATAL: Test Product ID {$testProductId} not found in the database.\n"); } echo "PHP Setup: Test product found (ID: {$testProductId}).\n";
    if (!class_exists('Cart')) { die("FATAL: Cart class not loaded by autoloader.\n"); }
    $cartModel = new Cart($pdo, $testUserId); $cartModel->clearCart(); if (!$cartModel->addItem($testProductId, $testQuantity)) { throw new Exception("CartModel::addItem returned false."); } $_SESSION['cart_count'] = $cartModel->getCartCount(); $cartCheckItems = $cartModel->getItems(); if (empty($cartCheckItems)) { throw new Exception("Failed to add/retrieve test item."); } echo "PHP Setup: Cart simulated successfully (User: {$testUserId}, Product: {$testProductId}, Count: {$_SESSION['cart_count']}).\n";
} catch (Exception $e) { die("Cart Simulation Error: " . $e->getMessage() . "\n"); }

// --- Instantiate Controllers ---
try {
    if (!class_exists('PaymentController')) die("FATAL: PaymentController class not loaded by autoloader.\n"); if (!class_exists('CheckoutController')) die("FATAL: CheckoutController class not loaded by autoloader.\n");
    $paymentController = new PaymentController($pdo); $checkoutController = new CheckoutController($pdo, $paymentController); echo "PHP Setup: Controllers instantiated successfully.\n";
} catch (Throwable $e) { die("FATAL: Error instantiating controllers: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString() . "\n"); }

// --- CSRF & Post Data ---
if (!class_exists('SecurityMiddleware')) { die("FATAL: SecurityMiddleware class not loaded by autoloader.\n"); } $csrfToken = SecurityMiddleware::generateCSRFToken(); echo "PHP Setup: CSRF Token generated: " . $csrfToken . "\n";
$samplePostData = [ 'shipping_name' => $testUserName, 'shipping_email' => $testUserEmail, 'shipping_address' => '123 Test St', 'shipping_address_line2' => 'Apt 4B', 'shipping_city' => 'Testville', 'shipping_state' => 'TS', 'shipping_zip' => '54321', 'shipping_country' => 'US', 'order_notes' => 'Test order generated by stripe_test_v4.php', 'save_address' => '0', 'csrf_token' => $csrfToken, 'applied_coupon_code' => '' ]; echo "PHP Setup: Sample POST data prepared.\n";

// --- Check Constants ---
if (!defined('STRIPE_PUBLIC_KEY') || !defined('BASE_URL')) { die("Error: STRIPE_PUBLIC_KEY or BASE_URL constant is not defined. Check config.php.\n"); } $stripePublicKey = STRIPE_PUBLIC_KEY; $baseUrl = BASE_URL; echo "PHP Setup: Constants checked (Stripe PK / Base URL).\n"; echo "PHP Setup: Completed successfully. Generating HTML output...\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Checkout Simulation (v5 - Classmap Autoloader)</title> <!-- Renamed Title -->
    <script src="https://js.stripe.com/v3/"></script>
    <style> /* Styles unchanged */
        body { font-family: sans-serif; padding: 15px; font-size: 14px; line-height: 1.5; }
        #message, #log { margin-top: 15px; padding: 10px; border-radius: 4px; border: 1px solid #ccc; }
        #log { background-color: #f8f8f8; font-family: monospace; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; font-size: 12px;}
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

    <h1>Stripe Checkout Simulation (v5 - Classmap Autoloader)</h1> <!-- Renamed Title -->
    <p>This script attempts to simulate the full checkout flow using Composer's classmap autoloader:</p>
    <ol>
        <li>Initialize Stripe.js core object.</li>
        <li>Simulate form data submission to the backend (`CheckoutController::processCheckout`).</li>
        <li>Receive `clientSecret` and `orderId` from backend.</li>
        <li>Initialize Stripe Elements **using the clientSecret**.</li>
        <li>Mount the Payment Element.</li>
        <li>Use the mounted Elements to call `stripe.confirmPayment()`.</li>
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
        // --- JavaScript Simulation (v5 - Corrected Elements Init - Unchanged from previous step) ---
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

        // --- Helper Functions (Unchanged) ---
        function logStep(step, status, data = null) { const timestamp = new Date().toLocaleTimeString(); let logEntry = `[${timestamp}] ${step}: ${status}`; if (data) { try { logEntry += `\nData: ${JSON.stringify(data, (key, value) => typeof value === 'object' && value !== null && value.constructor === Object && Object.keys(value).length === 0 ? '{}' : value, 2 )}`; } catch (e) { logEntry += `\nData: (Could not serialize - ${typeof data})`; } } logElement.textContent += `\n${logEntry}\n`; logElement.scrollTop = logElement.scrollHeight; }
        function showStatus(message, type = 'info') { messageDiv.textContent = message; messageDiv.className = type; }
        function setLoading(isLoading) { submitButton.disabled = isLoading; spinner.classList.toggle('hidden', !isLoading); buttonText.classList.toggle('hidden', isLoading); }

        // --- Initialize Stripe Core Object ONLY ---
        function initializeStripeCore() {
            logStep("Stripe Core Init", "Starting...", { key: stripeKey ? stripeKey.substring(0, 10) + '...' : 'MISSING' });
            if (!stripeKey || stripeKey.startsWith('pk_test_51xxx')) { const msg = "Invalid or placeholder Stripe Public Key."; logStep("Stripe Core Init", "FAILED", { error: msg }); showStatus(msg, 'error'); setLoading(false); submitButton.disabled = true; return false; }
            try {
                stripe = Stripe(stripeKey); if (!stripe) { throw new Error("Stripe(key) failed to return an object."); }
                logStep("Stripe Core Init", "SUCCESS", { stripeObjectPresent: !!stripe }); paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Secure payment form will load here...</p>';
                showStatus("Stripe Core ready. Click button to simulate checkout.", 'info'); return true;
            } catch (error) { logStep("Stripe Core Init", "FAILED", { error: error.message, details: error }); console.error("Stripe Core Initialization FAILED:", error); showStatus(`Stripe Core Initialization FAILED: ${error.message}`, 'error'); setLoading(false); submitButton.disabled = true; return false; }
        }

        // --- Simulate Checkout Button Click ---
        submitButton.addEventListener('click', async () => {
            if (!stripe) { showStatus("Stripe Core not initialized correctly.", 'error'); return; }
            setLoading(true); showStatus("Simulating checkout process...", 'info'); logStep("Checkout Click", "Initiated");
            let clientSecret = null; let orderId = null; let processCheckoutError = null; let elements = null;
            const formData = new FormData();
            <?php foreach ($samplePostData as $key => $value): ?> formData.append('<?= $key ?>', '<?= addslashes($value) ?>'); <?php endforeach; ?>
            logStep("Backend Call", "Sending data to processCheckout...", Object.fromEntries(formData));
            try {
                const response = await fetch('index.php?page=checkout&action=processCheckout', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
                logStep("Backend Call", `Received status ${response.status}`); const responseText = await response.text(); let loggableText = responseText; try { const tempData = JSON.parse(responseText); if (tempData.clientSecret) { loggableText = JSON.stringify({...tempData, clientSecret: tempData.clientSecret.substring(0, 15) + '...'}); } } catch(parseErr) {} logStep("Backend Call", "Raw response text (Secret Redacted)", loggableText);
                if (!response.ok) { let errorMsg = `Server returned status ${response.status}.`; try { const errorData = JSON.parse(responseText); errorMsg = errorData.error || errorMsg; } catch(e){} throw new Error(errorMsg); }
                const data = JSON.parse(responseText); logStep("Backend Call", "Parsed JSON response", data);
                if (data.success && data.clientSecret && data.orderId) { clientSecret = data.clientSecret; orderId = data.orderId; logStep("Backend Call", "SUCCESS", { clientSecret: clientSecret.substring(0, 15) + '...', orderId }); showStatus(`Backend processed (Order ID: ${orderId}). Loading payment form...`, 'info'); }
                else { throw new Error(data.error || 'Backend failed to process checkout.'); }
            } catch (error) { processCheckoutError = error; logStep("Backend Call", "FAILED", { error: error.message, details: error }); console.error("Backend processCheckout Error:", error); showStatus(`Backend Error: ${error.message}`, 'error'); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Could not prepare payment. Please try again.</p>'; setLoading(false); return; }
            try {
                if (!clientSecret) throw new Error("Client secret is missing after backend call.");
                const appearance = { theme: 'stripe', variables: { colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151', colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem' } };
                elements = stripe.elements({ clientSecret: clientSecret, appearance }); logStep("Stripe Elements", "Created with clientSecret", { elementsObjectPresent: !!elements });
                const paymentElement = elements.create('payment'); paymentElementContainer.innerHTML = ''; paymentElement.mount('#payment-element'); logStep("Payment Element", "Mounted successfully"); showStatus(`Payment form loaded. Confirming payment for Order ID: ${orderId}...`, 'info');
            } catch (elementsError) { logStep("Elements/Mount", "FAILED", { error: elementsError.message, details: elementsError }); console.error("Stripe Elements creation/mounting error:", elementsError); showMessage("Failed to load the payment form. Please refresh.", true); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error loading payment form.</p>'; setLoading(false); return; }
            if (clientSecret && stripe && elements) {
                 logStep("Stripe Confirm", "Attempting stripe.confirmPayment..."); const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/'; const testReturnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation&simulated=1`; logStep("Stripe Confirm", "Using return_url", testReturnUrl);
                 try {
                     const { error, paymentIntent } = await stripe.confirmPayment({ elements, confirmParams: { return_url: testReturnUrl }, redirect: 'if_required' });
                     if (error) { logStep("Stripe Confirm", "FAILED", { errorType: error.type, errorMessage: error.message, details: error }); console.error("Stripe confirmPayment Error:", error); showStatus(`Stripe Payment Confirmation Failed: ${error.message}`, 'error'); }
                     else if (paymentIntent) { logStep("Stripe Confirm", "SUCCESS/PENDING", { paymentIntentStatus: paymentIntent.status, paymentIntentId: paymentIntent.id }); console.log("Stripe confirmPayment Result:", paymentIntent); showStatus(`Stripe Payment Confirmation finished! Status: ${paymentIntent.status}. Check console/webhook.`, 'success'); }
                     else { logStep("Stripe Confirm", "UNKNOWN OUTCOME (No Redirect, No Error, No PI)", { error: null, paymentIntent: null }); showStatus("Stripe payment confirmation finished with an unknown outcome.", 'warning'); }
                 } catch (confirmError) { logStep("Stripe Confirm", "EXCEPTION", { error: confirmError.message, details: confirmError }); console.error("Exception during stripe.confirmPayment:", confirmError); showStatus(`Exception during payment confirmation: ${confirmError.message}`, 'error'); }
                 finally { setLoading(false); }
            } else { logStep("Stripe Confirm", "SKIPPED", { reason: "Missing clientSecret, stripe, or elements." }); showMessage('Internal error before payment confirmation.', 'error'); setLoading(false); }
        });

        // Initialize Stripe Core on page load
        if (!initializeStripeCore()) { logStep("Setup", "Stripe Core initialization failed. Disabling button."); submitButton.disabled = true; }
    </script>

</body>
</html>
