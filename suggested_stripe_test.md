Okay, this is a different and very revealing result!

1.  **`php stripe_test_v2.php` Output:** The output is your standard application error page (`views/error.php`) rendered without any specific error details (because `ENVIRONMENT` is likely not 'development' or the error display within the error page is suppressed). This means the PHP script itself encountered a **fatal error** during its setup phase *before* it could even start generating the simulation HTML.
2.  **`php stripe_test.php` Output:** This simpler script runs successfully via the command line, outputting the expected HTML. This confirms that when executed directly, PHP can read `config.php` and access the `STRIPE_PUBLIC_KEY`.

**Analysis:**

*   The fact that `stripe_test.php` works via CLI but `stripe_test_v2.php` produces a fatal error page strongly suggests an issue within the **PHP includes or instantiation logic** added in `stripe_test_v2.php`. The error isn't with Stripe initialization itself in this case, but something earlier in the PHP execution.
*   The most common reasons for this type of fatal error during includes are:
    *   **File Not Found:** A `require_once` or `include_once` is pointing to a file that doesn't exist at that path.
    *   **Class Not Found:** Trying to instantiate a class (e.g., `new PaymentController($pdo)`) before its definition has been included or autoloaded.
    *   **Syntax Errors:** A syntax error in one of the included PHP files (`controllers/*`, `models/*`, etc.).
    *   **Dependency Issues:** A class expects a dependency (like the `$pdo` object) that isn't available or correctly passed during instantiation.

**Debugging `stripe_test_v2.php`:**

Since we don't see the specific PHP fatal error message (it's hidden by the production error page), we need to modify `stripe_test_v2.php` slightly to force error display when run via CLI.

**Modified `stripe_test_v2.php` (Add Error Display):**

```php
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
```

**Key Changes in v2:**

1.  **Forced Error Display:** `ini_set` and `error_reporting` added at the top to ensure any PHP fatal errors during includes/instantiation are printed to the CLI output instead of showing the generic HTML error page.
2.  **Explicit EmailService Include:** Added `require_once __DIR__ . '/includes/EmailService.php';` as `BaseController` and `PaymentController` depend on it.
3.  **User/Product Validation:** Added checks to ensure the `$testUserId` and `$testProductId` actually exist in the database before proceeding, providing clearer error messages if they don't.
4.  **Cart Simulation:** Added `clearCart()` before `addItem` to ensure a clean state for each test run. Added checks to ensure the item was actually added.
5.  **Controller Instantiation Check:** Wrapped controller instantiation in a `try...catch` block to catch fatal errors like missing classes or constructor issues.
6.  **CLI Feedback:** Added `echo` statements to provide feedback on the CLI about the progress of the PHP setup phase.
7.  **JS Logging Enhancements:** Improved JSON logging in the browser to handle potential circular references or empty objects better. Added logging for raw backend response text. Redacted most of the clientSecret in logs.
8.  **JS `confirmPayment` Fix:** Removed the `clientSecret` parameter from the `stripe.confirmPayment` call. The `clientSecret` should only be passed when creating the `elements` group *if* you intend to use that specific mode (which we are not doing here; we get it later from the backend). The `elements` object is already associated with the PaymentIntent via the `clientSecret` when it's created/updated on the backend and passed to the frontend for confirmation.

**Instructions:**

1.  Save this new code as `stripe_test_v2.php`.
2.  **Crucially, update `$testUserId` and `$testProductId`** at the top of the script to valid IDs from your database.
3.  Run it from the command line: `php stripe_test_v2.php`
4.  **Observe the CLI output:** Look for any PHP errors (Notices, Warnings, Fatal Errors) or the "FATAL:" messages. If it completes the "PHP Setup" steps successfully, it will output the HTML.
5.  **If it outputs HTML:** Access the script via your browser (`http://localhost:8080/stripe_test_v2.php`). Open the Developer Console (F12), go to the Console tab, and click the "Run Checkout Simulation" button. Observe the on-page log (`<pre id="log">`) and the browser console for detailed steps and errors.

This more comprehensive test should pinpoint exactly where the failure occurs – either during the PHP setup/controller instantiation phase (indicated by CLI errors) or during the frontend JS interaction with your backend and Stripe (indicated by browser console/log errors).

---
Okay, this indicates the fundamental Stripe.js setup is working, but the interaction within your application's checkout flow is failing *after* the initial `Stripe()` call but *before* or *during* the `stripe.confirmPayment()` call.

Let's create `stripe_test_v2.php`. This script will attempt to mimic the *entire* process happening when you click "Place Order & Pay" on your checkout page:

1.  **PHP Setup:** Simulate a logged-in user, a basic cart, and instantiate necessary controllers.
2.  **HTML/JS Frontend:** Load Stripe.js, initialize Elements, and provide a button.
3.  **Button Click (JS):**
    *   Simulate sending shipping data + CSRF token to your `CheckoutController::processCheckout` endpoint via `fetch`.
    *   Receive the `clientSecret` and `orderId` back from the server.
    *   Use the `clientSecret` to call `stripe.confirmPayment()`.
4.  **Logging:** Log detailed information at each step to the browser console and the page itself.

**`stripe_test_v2.php` (Save in project root):**

```php
<?php
// stripe_test_v2.php - Comprehensive Checkout Simulation

// --- PHP Setup ---
define('ROOT_PATH', __DIR__);
// Error Reporting for Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
}

ErrorHandler::init(); // Initialize basic error handling

// Simulate Logged-in User (Replace with a valid User ID from your DB)
$testUserId = 1; // <<< IMPORTANT: Change this to an existing user ID in your 'users' table
$testUserEmail = 'test_user@thescent.local'; // Use a placeholder or real email
$testUserName = 'Test User';

// Start session and set user data
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = $testUserId;
$_SESSION['user_role'] = 'user'; // Assuming 'user' role
$_SESSION['user'] = [
    'id' => $testUserId,
    'name' => $testUserName,
    'email' => $testUserEmail,
    'role' => 'user'
    // Add address fields if needed, though they are overridden by POST data below
];
// Required for session integrity checks in BaseController
$_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'TestAgent';
$_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SESSION['last_login'] = time();
$_SESSION['last_regeneration'] = time();

// Simulate Cart (Add at least one VALID product ID from your DB)
$testProductId = 1; // <<< IMPORTANT: Change this to an existing product ID
$testQuantity = 1;
try {
    $cartModel = new Cart($pdo, $testUserId);
    // Clear existing test cart items first? Optional.
    // $cartModel->clearCart();
    $cartModel->addItem($testProductId, $testQuantity); // Add item to DB cart
    $_SESSION['cart_count'] = $cartModel->getCartCount(); // Ensure session count is accurate
    $cartCheckItems = $cartModel->getItems();
    if (empty($cartCheckItems)) {
        throw new Exception("Failed to add test item (ID: {$testProductId}) to the cart for user ID {$testUserId}. Ensure product exists and user can have a cart.");
    }
} catch (Exception $e) {
    die("Cart Simulation Error: " . $e->getMessage() . "<br>Please check the testUserId and testProductId.");
}


// Instantiate Controllers required for checkout process
$paymentController = new PaymentController($pdo);
$checkoutController = new CheckoutController($pdo, $paymentController); // Pass PaymentController

// Generate CSRF token (BaseController method preferred if available)
// Directly using SecurityMiddleware for simplicity in this standalone script
$csrfToken = SecurityMiddleware::generateCSRFToken();

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

// Check Essential Constants
if (!defined('STRIPE_PUBLIC_KEY') || !defined('BASE_URL')) {
    die("Error: STRIPE_PUBLIC_KEY or BASE_URL constant is not defined. Check config.php.");
}
$stripePublicKey = STRIPE_PUBLIC_KEY;
$baseUrl = BASE_URL;

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
                    logEntry += `\nData: ${JSON.stringify(data, null, 2)}`;
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
            logStep("Stripe Init", "Starting...", { key: stripeKey });
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
                elements = stripe.elements({ appearance }); // Create Elements group *without* clientSecret initially
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
            // Add sample shipping data from PHP
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
                logStep("Backend Call", "Raw response text", responseText);

                if (!response.ok) {
                    throw new Error(`Server returned status ${response.status}. Response: ${responseText}`);
                }

                const data = JSON.parse(responseText); // Parse text
                logStep("Backend Call", "Parsed JSON response", data);

                if (data.success && data.clientSecret && data.orderId) {
                    clientSecret = data.clientSecret;
                    orderId = data.orderId;
                    logStep("Backend Call", "SUCCESS", { clientSecret: clientSecret.substring(0, 8) + '...', orderId });
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
                 logStep("Stripe Confirm", "Attempting stripe.confirmPayment...", { clientSecret: clientSecret.substring(0, 8) + '...' });
                 try {
                     // Construct return URL (doesn't really matter where it goes for this test)
                     const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                     const testReturnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation&simulated=1`;
                     logStep("Stripe Confirm", "Using return_url", testReturnUrl);

                     const { error, paymentIntent } = await stripe.confirmPayment({
                         elements,
                         clientSecret: clientSecret,
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
                     } else if (paymentIntent) {
                          // Payment succeeded!
                          logStep("Stripe Confirm", "SUCCESS", { paymentIntentStatus: paymentIntent.status, paymentIntentId: paymentIntent.id });
                          console.log("Stripe confirmPayment Success:", paymentIntent);
                          showStatus(`Stripe Payment Confirmation Successful! Status: ${paymentIntent.status}`, 'success');
                           // You would typically redirect the user to your confirmation page here in a real app
                     } else {
                          // Unexpected state
                          logStep("Stripe Confirm", "UNKNOWN OUTCOME", { error: null, paymentIntent: null });
                          showStatus("Stripe payment confirmation returned an unexpected result.", 'error');
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
        initializeStripe();

    </script>

</body>
</html>
```

**Explanation and How to Use:**

1.  **Save:** Save this code as `stripe_test_v2.php` in your project root.
2.  **Configure:**
    *   **`$testUserId`:** Change `1` to a User ID that **definitely exists** in your `users` table.
    *   **`$testProductId`:** Change `1` to a Product ID that **definitely exists** in your `products` table and ideally has stock > 0.
3.  **Access:** Open `http://localhost:8080/stripe_test_v2.php` (or your equivalent URL) in your browser.
4.  **Observe Log (`<pre id="log">` on the page):**
    *   **Stripe Init:** Should show "SUCCESS".
    *   **Stripe Elements:** Should show "Created".
    *   **Payment Element:** Should show "Mounted successfully".
    *   Initial Message should be "Stripe initialized. Click button...".
5.  **Open Developer Console (F12):** Keep the console open to see detailed JS errors or Stripe object details.
6.  **Click Button:** Click the "Run Checkout Simulation" button.
7.  **Observe Log & Console:**
    *   **Backend Call:** Watch the log for data sent to `processCheckout` and the response received. Check for "SUCCESS" or "FAILED". If it fails here, the issue is in your `CheckoutController` or one of its dependencies (Order creation, Inventory update, etc.). Check `apache-error.log` for related PHP errors.
    *   **Stripe Confirm:** If the backend call succeeded, watch the log for the `stripe.confirmPayment` attempt.
        *   **SUCCESS:** Means the entire flow worked in this simulation. The problem is likely very specific to the DOM structure or other JS interactions on your *actual* checkout page.
        *   **FAILED:** Check the `errorType` and `errorMessage` in the log and console. This often points to issues with the Payment Intent (`clientSecret`), card details (if using a test card directly in the element), or Stripe account configuration.
        *   **EXCEPTION:** Indicates a more fundamental JS error during the confirmation process.

This detailed simulation should help narrow down whether the problem occurs during the backend processing (`processCheckout`) or the final frontend payment confirmation (`stripe.confirmPayment`). Report back the results from the **on-page log** and the **browser console**.

---
Okay, thank you for running the `stripe_test.php` script and providing the output.

This is very informative! Here's the analysis:

1.  **Core Stripe Initialization SUCCESS:** The line `<strong>Success!</strong> Stripe.js initialized successfully.` clearly indicates that the `stripe = Stripe(stripeKeyFromData);` call within the `try` block succeeded. This confirms:
    *   Your `config.php` is correct.
    *   The `STRIPE_PUBLIC_KEY` constant is defined and holds your valid test key.
    *   The Stripe.js library (`https://js.stripe.com/v3/`) is loading correctly.
    *   The fundamental `Stripe()` constructor call works with your key.

2.  **Element Mounting WARNING:** The warning message you received is key:
    > Warning: Could not mount payment element:
    > {
    > "message": "In order to create a payment element, you must pass a clientSecret or mode when creating the Elements group.\n\n e.g. stripe.elements({clientSecret: \"{{CLIENT_SECRET}}\"})",
    > "name": "IntegrationError"
    > }

    *   **What it means:** This warning is coming from the optional part of the test script where it tries to *immediately* create and mount the `payment` element (`elements.create('payment'); paymentElement.mount('#payment-element');`). Stripe is indicating that *if* you are creating the Payment Element, the `stripe.elements()` call usually needs more context, like a `clientSecret` (which you get *after* creating a PaymentIntent on your server) or a `mode` (for specific setup flows like Setup Intents).
    *   **Why it's likely a Red Herring for the Original Problem:** In your actual checkout flow (`js/main.js -> initCheckoutPage`), you correctly create the Elements group (`stripe.elements({ appearance })`) and mount the Payment Element *before* you have the `clientSecret`. You only get the `clientSecret` later when the user clicks "Place Order & Pay" and your server (`CheckoutController::processCheckout`) creates the Payment Intent. This is the standard and correct flow. The warning in the test script arises because it tries to mount the element *without* the later context that your main application *will* eventually provide to `stripe.confirmPayment`.

**Conclusion & Next Steps:**

Since the core `Stripe()` initialization **succeeded** in the test script, the original error message "Could not initialize payment system. Please refresh." on your actual checkout page **must be caused by something else preventing the `initCheckoutPage` function in `js/main.js` from running correctly or completing the `try` block.**

The most likely cause is **another JavaScript error** occurring on the checkout page that halts execution before or during the `initCheckoutPage` function.

**Please perform the following critical step:**

1.  Navigate to your **checkout page** (`/index.php?page=checkout`) in the browser (the one displaying the "Could not initialize..." error).
2.  Open the **Developer Console** (F12).
3.  Go to the **"Console"** tab.
4.  **Refresh** the checkout page (Ctrl+R or Cmd+R).
5.  Look very carefully for **ANY red error messages** that appear in the console *after* the page reloads.
6.  **Copy and paste the exact error messages** you find here.

These console errors from the actual checkout page are now the key to diagnosing the final step of this issue.

---
https://drive.google.com/file/d/10UXm7z2kPA06IHDhqeMTf3WZ5rKH1TnR/view?usp=sharing, https://drive.google.com/file/d/12oBsB_dhIP9L53VQlVcr9RC7n2W1SqKC/view?usp=sharing, https://drive.google.com/file/d/13rcrk6CPFQilYTWVSxJ8kVBxRiGD1ZWd/view?usp=sharing, https://drive.google.com/file/d/16imdccPwOR82rY_7lZQ88UpZ8iOXxHdg/view?usp=sharing, https://drive.google.com/file/d/16myrTzOOfE6-HSfWMi6Si29UA-BJp-VQ/view?usp=sharing, https://drive.google.com/file/d/1AYgfMtN1d1-9u9vu3U8BTnMs_iimvXd3/view?usp=sharing, https://drive.google.com/file/d/1AgLmaeIjyP_PV83jsHwROD9ihzh6WbvP/view?usp=sharing, https://drive.google.com/file/d/1BMgwB5VP_u3hWVJJrF_xSdopPFL3Hb8c/view?usp=sharing, https://drive.google.com/file/d/1C17krIsUYodkrcWgIfReUX2rXbMcPO-x/view?usp=sharing, https://drive.google.com/file/d/1IdpqFzukNcHvMRWIOQdHqCPk9DMZAxeo/view?usp=sharing, https://drive.google.com/file/d/1IhIpJPaLBe1eCOxsWk2Y0mE1tHxOjzZD/view?usp=sharing, https://drive.google.com/file/d/1KRkJq2BdXRYc_vaJaHz_eKPpi27TzHeM/view?usp=sharing, https://drive.google.com/file/d/1LDZCUFOfOCqg6_kDHYtbj0m0gtMvig8p/view?usp=sharing, https://drive.google.com/file/d/1PKNAlUkqL1ZP4h6hglqpTAbjyENOXhQX/view?usp=sharing, https://drive.google.com/file/d/1YOn5sMoUPaO7sMbdyTIOPIxZAT81tIHl/view?usp=sharing, https://drive.google.com/file/d/1YRvmW5LOIMKZ2pdNCKb6W-jU97b7ufiJ/view?usp=sharing, https://drive.google.com/file/d/1YuJOhriI6V0oJN5vHvHUi6jDCI8iQ1h_/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221ZgPVhzXEiFkRf798JkS5aqhRjYYp2ITI%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1ZgnZ5LL6EXQBvSubIwIRS5ip7fTVAnzI/view?usp=sharing, https://drive.google.com/file/d/1_5BVOFRWYIvmWzI-DzHETqvSoEWfh43l/view?usp=sharing, https://drive.google.com/file/d/1gN6zXQ_PTV2YF9GkwR3IagLx6X3gwxv9/view?usp=sharing, https://drive.google.com/file/d/1hJlAzdnBNoo1iYUGdaQsZxsH3txBd-62/view?usp=sharing, https://drive.google.com/file/d/1j-O6iOn3jWVDDFefVBLYx77Xld88yq5A/view?usp=sharing, https://drive.google.com/file/d/1soWEHHyAPUfcv_ZzhfB84O0W83iG7AuM/view?usp=sharing, https://drive.google.com/file/d/1tMdkomSIzN5_FIxTpVpCFk4bWzDwocv2/view?usp=sharing, https://drive.google.com/file/d/1wyFY4k7qi14bD00E6_2k5vrMVfvGURVQ/view?usp=sharing
