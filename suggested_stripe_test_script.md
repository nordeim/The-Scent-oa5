Okay, that fatal error message is very clear:

```
FATAL: Cart class not found. Check includes/autoloader.
```

**Analysis:**

1.  **Composer Autoloader:** The script successfully identifies and includes `/vendor/autoload.php`.
2.  **Class Not Found:** Despite using the autoloader, PHP cannot find the definition for the `Cart` class when the script tries to instantiate it:
    ```php
    $cartModel = new Cart($pdo, $testUserId);
    ```
3.  **Cause:** This usually happens when:
    *   The `Cart` class file (`models/Cart.php`) does not exist or is in the wrong location.
    *   The `Cart` class file exists, but it doesn't adhere to the PSR-4 autoloading standard that Composer expects (e.g., incorrect namespace, incorrect filename casing compared to the class name, or the `composer.json` doesn't map the `models/` directory correctly).
    *   There was an error during `composer install` or `composer dump-autoload`.

**Solution:**

Since you are using Composer, the best practice is to ensure your `composer.json` is configured correctly for PSR-4 autoloading and that your class files match this configuration.

**1. Verify `models/Cart.php`:**

*   Make sure the file `/home/u385792050/domains/magenta-mole-338777.hostingersite.com/public_html/models/Cart.php` exists.
*   Make sure the class name inside the file is exactly `Cart` (case-sensitive).
*   Make sure there are no syntax errors within `models/Cart.php`.

**2. Check `composer.json` for Autoloading:**

Your `composer.json` currently looks like this:

```json
{
    "require": {
        "stripe/stripe-php": "^17.1",
        "phpmailer/phpmailer": "^6.10"
    },
    "scripts": {
        "serve": "php -S localhost:8000"
    }
}
```

It's missing an `autoload` section to tell Composer where to find *your* application's classes (like Controllers and Models).

**Add PSR-4 Autoloading to `composer.json`:**

Modify your `composer.json` to include an `autoload` section that maps namespaces to directories. Assuming your controllers are intended to be in an `App\Controllers` namespace and models in `App\Models`, etc., and they reside in the `controllers/` and `models/` directories respectively:

```json
{
    "require": {
        "stripe/stripe-php": "^17.1",
        "phpmailer/phpmailer": "^6.10"
    },
    "autoload": {
        "psr-4": {
            "App\\Controllers\\": "controllers/",
            "App\\Models\\": "models/",
            "App\\Includes\\": "includes/"
        }
    },
    "scripts": {
        "serve": "php -S localhost:8000"
    }
}
```

**3. Update Class Files with Namespaces:**

If you add the PSR-4 autoloading, you **must** add namespaces to the top of your class files.

*   **`models/Cart.php`:**
    ```php
    <?php
    namespace App\Models; // Add this namespace

    use PDO; // Import PDO if needed within the namespace
    use PDOException; // Import PDOException

    class Cart {
        private PDO $pdo;
        private int $userId;

        // ... rest of the Cart class code ...
    }
    ```

*   **Similarly, add namespaces to all your other classes:**
    *   `controllers/BaseController.php`: `namespace App\Controllers;` (and `use PDO;`, `use App\Includes\EmailService;`, etc.)
    *   `controllers/AccountController.php`: `namespace App\Controllers;` (and `use PDO;`, `use App\Models\User;`, etc.)
    *   `models/User.php`: `namespace App\Models;` (and `use PDO;`, etc.)
    *   `includes/EmailService.php`: `namespace App\Includes;` (and `use PDO;`, `use PHPMailer\PHPMailer\PHPMailer;`, etc.)
    *   And so on for *all* controllers, models, and potentially includes classes you want autoloaded.

**4. Update Composer Autoloader:**

After editing `composer.json` and adding namespaces to your files, run this command in your terminal within the project root (`/home/u385792050/domains/magenta-mole-338777.hostingersite.com/public_html`):

```bash
composer dump-autoload -o
```

This regenerates the optimized autoloader files based on your `composer.json`.

**5. Modify Instantiation in `stripe_test_v3.php`:**

If you added namespaces, you need to use the fully qualified class names when instantiating or use `use` statements at the top of `stripe_test_v3.php`:

```php
<?php
// stripe_test_v3.php (v6 - Using Namespaces)

// Add use statements after initial includes/setup
use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use App\Controllers\PaymentController;
use App\Controllers\CheckoutController;
// ... other necessary use statements

// ... (rest of PHP setup) ...

try {
    // Instantiate using the class name directly because of the 'use' statements
    $cartModel = new Cart($pdo, $testUserId);
    // ...
    $paymentController = new PaymentController($pdo);
    $checkoutController = new CheckoutController($pdo, $paymentController);
    // ...
} catch (Throwable $e) {
    // ... error handling ...
}

// ... (rest of script) ...
?>
```

**Recommendation:**

Implementing PSR-4 autoloading is the standard and most maintainable way forward. It eliminates the need for manual `require_once` statements for your classes.

**Steps:**

1.  **Update `composer.json`** with the `autoload` section shown above.
2.  **Add namespaces** (`namespace App\Models;`, `namespace App\Controllers;`, `namespace App\Includes;`) to the top of *all* relevant PHP class files (`.php` files containing `class ... { ... }`). Add necessary `use` statements within namespaced files (e.g., `use PDO;`).
3.  Run `composer dump-autoload -o` in your terminal.
4.  Modify `stripe_test_v3.php` to use the correct namespaces (either via `use` statements at the top or fully qualified names like `new \App\Models\Cart(...)`).
5.  Run `php stripe_test_v3.php` again.

This should resolve the "Class not found" error. Let me know if you encounter issues adding namespaces or running `composer dump-autoload`.

---
<think>
1.  **Analyze Input:** The user ran `php stripe_test_v3.php` (which is the V4 code from the previous step, just misnamed by the user perhaps). The CLI output shows the PHP setup part completed successfully, including finding the user, product, simulating the cart, instantiating controllers, and generating the CSRF token. It then outputs the HTML.
2.  **Identify Problem:** The user is showing the *output* of the PHP script (which is HTML+JS), not the *result* of running that HTML in a browser. The request asks for analysis based on this output, but the *real* test happens when this HTML is rendered and the JS executes in a browser.
3.  **Recall Previous Steps:** We fixed the `ErrorHandler not found` fatal error. The previous test (`stripe_test.php` V1) confirmed basic Stripe initialization worked. The simulation script `stripe_test_v3.php` (V4 code) was created to test the full flow, including backend interaction and the JS `confirmPayment` call.
4.  **Infer User Action:** The user likely saved the HTML output generated by `php stripe_test_v3.php` to a file (e.g., `stripe_test_v3_output.html`) and opened *that file* in their browser, as instructed in the previous step.
5.  **Re-examine the JS Code:** The JavaScript inside the generated HTML (specifically the `initCheckoutPage` function copied from `main.js` in the last step) contains the corrected flow:
    *   Initialize `stripe = Stripe(key)`.
    *   *Do not* create elements yet.
    *   On button click:
        *   Call backend `processCheckout`.
        *   On backend success (get `clientSecret`):
            *   Create `elements = stripe.elements({ clientSecret, appearance })`.
            *   Create `paymentElement = elements.create('payment')`.
            *   Mount `paymentElement.mount('#payment-element')`.
            *   Call `stripe.confirmPayment({ elements, confirmParams: ... })`.
6.  **Hypothesize Browser Behavior:** When the user opens `stripe_test_v3_output.html` and clicks the button:
    *   `initializeStripe()` should run successfully on page load (as confirmed by `stripe_test.php`). The placeholder message should appear in `#payment-element`.
    *   Clicking the button triggers the `fetch` call to `index.php?page=checkout&action=processCheckout`.
    *   The backend controller should execute, create the order/PaymentIntent, and return `{ success: true, clientSecret: 'pi_...', orderId: ... }`.
    *   The JS `fetch` `.then()` block receives the `clientSecret`.
    *   It creates `stripe.elements({ clientSecret, ... })`. **This is the critical step that was failing before.**
    *   It mounts the `paymentElement`. The user should *see* the Stripe form appear.
    *   It calls `stripe.confirmPayment()`.
    *   Since it's test mode and `redirect: 'if_required'`, it likely won't redirect immediately but should log "SUCCESS/PENDING" and show the paymentIntent status.
7.  **Compare to User Image:** The user provided an image showing the *browser* rendering of the *simulation* script (`stripe_test_v3_output.html`, presumably).
    *   The on-page message is "Stripe Initialization FAILED: In order to create a payment element...".
    *   The on-page log shows:
        *   `Stripe Init: SUCCESS` (Confirms `stripe = Stripe(key)` worked).
        *   `Stripe Elements: Created` (Confirms `elements = stripe.elements({ appearance });` worked).
        *   **`Stripe Init: FAILED`** with the error "In order to create a payment element, you must pass a clientSecret or mode..."
    *   **Contradiction:** The log says `Stripe Elements: Created` successfully, but *then* immediately logs `Stripe Init: FAILED` with the exact error message related to *creating the payment element*. This implies the error isn't happening during the initial `stripe = Stripe(key)` call, nor during the `elements = stripe.elements({ appearance })` call, but specifically during the `paymentElement = elements.create('payment');` call within the `initializeStripe` function.
8.  **Re-examine `initializeStripe` in the JS:** Let's look closely at the JS provided in the file the user ran (`stripe_test_v3.php` containing V4 code):
    ```javascript
    // 1. Initialize Stripe & Elements
    function initializeStripe() {
        // ... key checks ...
        try {
            stripe = Stripe(stripeKey);
             if (!stripe || typeof stripe.elements !== 'function') { throw new Error("Stripe(key) did not return a valid object."); }
             logStep("Stripe Init", "SUCCESS", { stripeObjectPresent: !!stripe });
             const appearance = { theme: 'stripe' };
            // --- PROBLEM IS HERE ---
            elements = stripe.elements({ appearance }); // Create Elements group (This needs clientSecret or mode for payment element)
            logStep("Stripe Elements", "Created", { elementsObjectPresent: !!elements });
            const paymentElement = elements.create('payment'); // << THIS FAILS because elements group was created without clientSecret/mode
            paymentElement.mount('#payment-element');
            logStep("Payment Element", "Mounted successfully"); // << Never reached
            showStatus("Stripe initialized. Click button to simulate checkout.", 'info');
            return true;
        } catch (error) {
            logStep("Stripe Init", "FAILED", { error: error.message, details: error }); console.error("Stripe Initialization FAILED:", error);
            showStatus(`Stripe Initialization FAILED: ${error.message}`, 'error'); setLoading(false); submitButton.disabled = true; return false;
        }
    }
    ```
9.  **Identify the Bug:** The merge was incorrect! My previous analysis correctly identified the required *flow*, but the implementation in the provided `js/main.js` (and therefore the copied JS in `stripe_test_v3.php`) **still creates the `elements` group and the `paymentElement` immediately on page load**, not *after* getting the `clientSecret`. The error message confirms this: `elements.create('payment')` fails because the `elements` group it belongs to was created without the necessary `clientSecret` or `mode`.
10. **Plan Correction:** Modify the `initializeStripe` function in the test script (and subsequently in `js/main.js`) to *only* initialize `stripe = Stripe(key)`. Move the `stripe.elements(...)`, `elements.create('payment')`, and `paymentElement.mount(...)` calls into the `submitButton` click handler, *after* the `clientSecret` is received from the backend.

</think>

You've found the exact error! The browser console log from running the simulation confirms the issue we suspected, but with a twist:

1.  `[2:42:10 PM] Stripe Init: SUCCESS`: The core `stripe = Stripe(key)` works fine.
2.  `[2:42:10 PM] Stripe Elements: Created`: The `elements = stripe.elements({ appearance });` line *also* appears to run without throwing an immediate error.
3.  `[2:42:10 PM] Stripe Init: FAILED`: The error occurs immediately after creating the `elements` group, and the message is clear:
    > "In order to create a payment element, you must pass a clientSecret or mode when creating the Elements group."

**Root Cause:**

My previous attempt to fix `js/main.js` (and subsequently the JS in `stripe_test_v3.php`) was **incomplete**. While I correctly identified that `stripe.confirmPayment` needs the `clientSecret` flow, I missed that creating the *specific `payment` element* (`elements.create('payment')`) *also* requires the `elements` group it belongs to (`stripe.elements({...})`) to have been initialized with either a `clientSecret` or a `mode`.

The current code initializes the `elements` group on page load *without* the `clientSecret` (because it doesn't have it yet) and then immediately tries to create the `paymentElement`, which fails.

**Solution:**

We need to follow the structure decided upon earlier: defer the creation of **both** the `elements` group *and* the `paymentElement` until *after* the `clientSecret` is received from the backend upon clicking the submit button.

**Corrected `stripe_test_v3.php` (v5 - Final Fix for JS Flow):**

This version applies the correct JavaScript logic.

```php
<?php
// stripe_test_v3.php - Comprehensive Checkout Simulation (v5 - Corrected JS Flow)

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
require_once __DIR__ . '/includes/ErrorHandler.php'; // Needs to be included before init
ErrorHandler::init(); // Initialize basic error handling
// --- End Core Includes ---

require_once __DIR__ . '/includes/SecurityMiddleware.php';

// --- Use Composer Autoloader if available ---
$autoloader_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader_path)) {
    echo "PHP Setup: Using Composer autoloader.\n";
    require_once $autoloader_path;
    // Manual include non-PSR classes if needed
    if (!class_exists('BaseController')) require_once __DIR__ . '/controllers/BaseController.php';
    if (!class_exists('EmailService')) require_once __DIR__ . '/includes/EmailService.php';
    // Autoloader should handle the rest if composer.json is correct
} else {
    echo "PHP Setup: Composer autoloader not found, using manual includes.\n";
    // Manual requires if no autoloader
    require_once __DIR__ . '/controllers/BaseController.php';
    require_once __DIR__ . '/includes/EmailService.php'; // BaseController needs this
    require_once __DIR__ . '/controllers/PaymentController.php';
    require_once __DIR__ . '/controllers/CheckoutController.php';
    require_once __DIR__ . '/controllers/CouponController.php';
    require_once __DIR__ . '/controllers/TaxController.php';
    require_once __DIR__ . '/controllers/InventoryController.php';
    require_once __DIR__ . '/models/Order.php';
    require_once __DIR__ . '/models/Product.php';
    require_once __DIR__ . '/models/Cart.php';
    require_once __DIR__ . '/models/User.php';
}


// --- Simulate Logged-in User ---
$testUserId = 2; // Use the ID confirmed previously
if (!isset($pdo)) die("FATAL: PDO object not available after includes/db.php. Check db.php.\n");
$testUserData = null;
try {
    $userCheckStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
    $userCheckStmt->execute([$testUserId]);
    $testUserData = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("FATAL: Database error checking test user: " . $e->getMessage() . "\n"); }
if (!$testUserData) { die("FATAL: Test User ID {$testUserId} not found in the database. Please use a valid ID.\n"); }
$testUserEmail = $testUserData['email']; $testUserName = $testUserData['name'];
echo "PHP Setup: Test user found (ID: {$testUserId}, Email: {$testUserEmail}).\n";

// --- Start Session ---
if (session_status() === PHP_SESSION_NONE) { if (!headers_sent()) { session_start(); echo "PHP Setup: Session started.\n"; } else { error_log("stripe_test_v3.php Warning: Headers already sent, cannot start session."); echo "PHP Setup Warning: Headers already sent, cannot start session.\n"; } } else { echo "PHP Setup: Session already active.\n"; }
$_SESSION['user_id'] = $testUserId; $_SESSION['user_role'] = 'user'; $_SESSION['user'] = [ 'id' => $testUserId, 'name' => $testUserName, 'email' => $testUserEmail, 'role' => 'user' ]; $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'TestAgentCLI'; $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; $_SESSION['last_login'] = time(); $_SESSION['last_regeneration'] = time();

// --- Simulate Cart ---
$testProductId = 1; // Use the ID confirmed previously
$testQuantity = 1;
try {
    $productCheckStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?"); $productCheckStmt->execute([$testProductId]); if (!$productCheckStmt->fetch()) { die("FATAL: Test Product ID {$testProductId} not found in the database.\n"); } echo "PHP Setup: Test product found (ID: {$testProductId}).\n";
    if (!class_exists('Cart')) { die("FATAL: Cart class not found. Check includes/autoloader.\n"); }
    $cartModel = new Cart($pdo, $testUserId); $cartModel->clearCart(); if (!$cartModel->addItem($testProductId, $testQuantity)) { throw new Exception("CartModel::addItem returned false."); } $_SESSION['cart_count'] = $cartModel->getCartCount(); $cartCheckItems = $cartModel->getItems(); if (empty($cartCheckItems)) { throw new Exception("Failed to add/retrieve test item."); } echo "PHP Setup: Cart simulated successfully (User: {$testUserId}, Product: {$testProductId}, Count: {$_SESSION['cart_count']}).\n";
} catch (Exception $e) { die("Cart Simulation Error: " . $e->getMessage() . "\n"); }

// --- Instantiate Controllers ---
try {
    if (!class_exists('PaymentController')) die("FATAL: PaymentController class not found.\n"); if (!class_exists('CheckoutController')) die("FATAL: CheckoutController class not found.\n");
    $paymentController = new PaymentController($pdo); $checkoutController = new CheckoutController($pdo, $paymentController); echo "PHP Setup: Controllers instantiated successfully.\n";
} catch (Throwable $e) { die("FATAL: Error instantiating controllers: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString() . "\n"); }

// --- CSRF & Post Data ---
if (!class_exists('SecurityMiddleware')) { die("FATAL: SecurityMiddleware class not found.\n"); } $csrfToken = SecurityMiddleware::generateCSRFToken(); echo "PHP Setup: CSRF Token generated: " . $csrfToken . "\n";
$samplePostData = [ 'shipping_name' => $testUserName, 'shipping_email' => $testUserEmail, 'shipping_address' => '123 Test St', 'shipping_address_line2' => 'Apt 4B', 'shipping_city' => 'Testville', 'shipping_state' => 'TS', 'shipping_zip' => '54321', 'shipping_country' => 'US', 'order_notes' => 'Test order generated by stripe_test_v3.php', 'save_address' => '0', 'csrf_token' => $csrfToken, 'applied_coupon_code' => '' ]; echo "PHP Setup: Sample POST data prepared.\n";

// --- Check Constants ---
if (!defined('STRIPE_PUBLIC_KEY') || !defined('BASE_URL')) { die("Error: STRIPE_PUBLIC_KEY or BASE_URL constant is not defined. Check config.php.\n"); } $stripePublicKey = STRIPE_PUBLIC_KEY; $baseUrl = BASE_URL; echo "PHP Setup: Constants checked (Stripe PK / Base URL).\n"; echo "PHP Setup: Completed successfully. Generating HTML output...\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Checkout Simulation (v5 - Corrected JS Flow)</title>
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

    <h1>Stripe Checkout Simulation (v5 - Corrected JS Flow)</h1>
    <p>This script attempts to simulate the full checkout flow:</p>
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
        // --- JavaScript Simulation (v5 - Corrected Elements Init) ---
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
        // Elements will be initialized LATER

        // --- Helper Functions (logging, status, loading - unchanged) ---
        function logStep(step, status, data = null) { /* ... */ }
        function showStatus(message, type = 'info') { /* ... */ }
        function setLoading(isLoading) { /* ... */ }
        // --- Include full definition of helper functions here ---
        function logStep(step, status, data = null) {
            const timestamp = new Date().toLocaleTimeString();
            let logEntry = `[${timestamp}] ${step}: ${status}`;
            if (data) { try { logEntry += `\nData: ${JSON.stringify(data, (key, value) => typeof value === 'object' && value !== null && value.constructor === Object && Object.keys(value).length === 0 ? '{}' : value, 2 )}`; } catch (e) { logEntry += `\nData: (Could not serialize - ${typeof data})`; } }
            logElement.textContent += `\n${logEntry}\n`; logElement.scrollTop = logElement.scrollHeight;
        }
        function showStatus(message, type = 'info') { messageDiv.textContent = message; messageDiv.className = type; }
        function setLoading(isLoading) { submitButton.disabled = isLoading; spinner.classList.toggle('hidden', !isLoading); buttonText.classList.toggle('hidden', isLoading); }

        // --- Initialize Stripe Core Object ONLY ---
        function initializeStripeCore() {
            logStep("Stripe Core Init", "Starting...", { key: stripeKey ? stripeKey.substring(0, 10) + '...' : 'MISSING' });
            if (!stripeKey || stripeKey.startsWith('pk_test_51xxx')) {
                const msg = "Invalid or placeholder Stripe Public Key."; logStep("Stripe Core Init", "FAILED", { error: msg }); showStatus(msg, 'error'); setLoading(false); submitButton.disabled = true; return false;
            }
            try {
                stripe = Stripe(stripeKey);
                if (!stripe) { throw new Error("Stripe(key) failed to return an object."); }
                logStep("Stripe Core Init", "SUCCESS", { stripeObjectPresent: !!stripe });
                paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Secure payment form will load here...</p>'; // Placeholder
                showStatus("Stripe Core ready. Click button to simulate checkout.", 'info');
                return true;
            } catch (error) {
                logStep("Stripe Core Init", "FAILED", { error: error.message, details: error }); console.error("Stripe Core Initialization FAILED:", error);
                showStatus(`Stripe Core Initialization FAILED: ${error.message}`, 'error'); setLoading(false); submitButton.disabled = true; return false;
            }
        }

        // --- Simulate Checkout Button Click ---
        submitButton.addEventListener('click', async () => {
            if (!stripe) { showStatus("Stripe Core not initialized correctly.", 'error'); return; } // Check core stripe object
            setLoading(true); showStatus("Simulating checkout process...", 'info'); logStep("Checkout Click", "Initiated");

            // 1. Call Backend (processCheckout)
            let clientSecret = null; let orderId = null; let processCheckoutError = null; let elements = null; // Define elements locally for this process
            const formData = new FormData();
            <?php foreach ($samplePostData as $key => $value): ?> formData.append('<?= $key ?>', '<?= addslashes($value) ?>'); <?php endforeach; ?>
            logStep("Backend Call", "Sending data to processCheckout...", Object.fromEntries(formData));

            try {
                const response = await fetch('index.php?page=checkout&action=processCheckout', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
                logStep("Backend Call", `Received status ${response.status}`);
                const responseText = await response.text();
                let loggableText = responseText; try { const tempData = JSON.parse(responseText); if (tempData.clientSecret) { loggableText = JSON.stringify({...tempData, clientSecret: tempData.clientSecret.substring(0, 15) + '...'}); } } catch(parseErr) {}
                logStep("Backend Call", "Raw response text (Secret Redacted)", loggableText);
                if (!response.ok) { let errorMsg = `Server returned status ${response.status}.`; try { const errorData = JSON.parse(responseText); errorMsg = errorData.error || errorMsg; } catch(e){} throw new Error(errorMsg); }
                const data = JSON.parse(responseText); logStep("Backend Call", "Parsed JSON response", data);
                if (data.success && data.clientSecret && data.orderId) {
                    clientSecret = data.clientSecret; orderId = data.orderId;
                    logStep("Backend Call", "SUCCESS", { clientSecret: clientSecret.substring(0, 15) + '...', orderId });
                    showStatus(`Backend processed (Order ID: ${orderId}). Loading payment form...`, 'info');
                } else { throw new Error(data.error || 'Backend failed to process checkout.'); }
            } catch (error) {
                processCheckoutError = error; logStep("Backend Call", "FAILED", { error: error.message, details: error }); console.error("Backend processCheckout Error:", error);
                showStatus(`Backend Error: ${error.message}`, 'error'); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Could not prepare payment. Please try again.</p>'; setLoading(false); return;
            }

            // --- *** 2. Initialize Elements & Mount Payment Element *** ---
            try {
                if (!clientSecret) throw new Error("Client secret is missing after backend call.");
                const appearance = { theme: 'stripe', variables: { colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151', colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem' } };
                elements = stripe.elements({ clientSecret: clientSecret, appearance }); // Pass clientSecret
                logStep("Stripe Elements", "Created with clientSecret", { elementsObjectPresent: !!elements });
                const paymentElement = elements.create('payment');
                paymentElementContainer.innerHTML = ''; // Clear placeholder
                paymentElement.mount('#payment-element');
                logStep("Payment Element", "Mounted successfully");
                showStatus(`Payment form loaded. Confirming payment for Order ID: ${orderId}...`, 'info');
            } catch (elementsError) {
                logStep("Elements/Mount", "FAILED", { error: elementsError.message, details: elementsError }); console.error("Stripe Elements creation/mounting error:", elementsError);
                showMessage("Failed to load the payment form. Please refresh.", true); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error loading payment form.</p>';
                setLoading(false); return;
            }

            // --- *** 3. Confirm Payment *** ---
            if (clientSecret && stripe && elements) { // Check elements again
                 logStep("Stripe Confirm", "Attempting stripe.confirmPayment...");
                 const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                 const testReturnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation&simulated=1`;
                 logStep("Stripe Confirm", "Using return_url", testReturnUrl);
                 try {
                     const { error, paymentIntent } = await stripe.confirmPayment({ elements, confirmParams: { return_url: testReturnUrl }, redirect: 'if_required' });
                     if (error) { logStep("Stripe Confirm", "FAILED", { errorType: error.type, errorMessage: error.message, details: error }); console.error("Stripe confirmPayment Error:", error); showStatus(`Stripe Payment Confirmation Failed: ${error.message}`, 'error'); }
                     else if (paymentIntent) { logStep("Stripe Confirm", "SUCCESS/PENDING", { paymentIntentStatus: paymentIntent.status, paymentIntentId: paymentIntent.id }); console.log("Stripe confirmPayment Result:", paymentIntent); showStatus(`Stripe Payment Confirmation finished! Status: ${paymentIntent.status}. Check console/webhook.`, 'success'); }
                     else { logStep("Stripe Confirm", "UNKNOWN OUTCOME (No Redirect, No Error, No PI)", { error: null, paymentIntent: null }); showStatus("Stripe payment confirmation finished with an unknown outcome.", 'warning'); }
                 } catch (confirmError) { logStep("Stripe Confirm", "EXCEPTION", { error: confirmError.message, details: confirmError }); console.error("Exception during stripe.confirmPayment:", confirmError); showStatus(`Exception during payment confirmation: ${confirmError.message}`, 'error');
                 } finally { setLoading(false); }
            } else { logStep("Stripe Confirm", "SKIPPED", { reason: "Missing clientSecret, stripe, or elements." }); showMessage('Internal error before payment confirmation.', 'error'); setLoading(false); }
        });

        // Initialize Stripe Core on page load
        if (!initializeStripeCore()) { logStep("Setup", "Stripe Core initialization failed. Disabling button."); submitButton.disabled = true; }
    </script>

</body>
</html>
```

**Summary of Changes in `stripe_test_v3.php` (v5):**

1.  **Fatal Error Catcher:** Kept the `register_shutdown_function` to catch any unexpected PHP fatal errors.
2.  **PHP Setup:** Enhanced checks for `$pdo`, user, and product existence with clearer `die()` messages for CLI. Added `echo` statements for better CLI progress tracking. Moved `ErrorHandler::init()` to run right after core includes. Prioritized Composer autoloader but added manual includes as fallbacks with checks.
3.  **JavaScript (`initializeStripeCore`):** Renamed the initial JS function to clarify it only initializes the core `stripe` object, *not* the elements. It sets placeholder text in the `#payment-element` div.
4.  **JavaScript (`submitButton` Listener):**
    *   Renamed the main function `initializeStripe` was split. Core init runs on page load.
    *   The click handler now performs the following sequence:
        *   Calls the backend (`processCheckout`) to get the `clientSecret`.
        *   **NEW:** If backend is successful, it then creates `stripe.elements({ clientSecret, ... })`.
        *   **NEW:** It then creates and mounts the `paymentElement`.
        *   Finally, it calls `stripe.confirmPayment({ elements, ... })`.
    *   Error handling added specifically for the elements creation/mounting step.

**Please run this corrected `stripe_test_v3.php` (v5) script again via PHP CLI (`php stripe_test_v3.php`), save the HTML output, and then test that HTML file in your browser.** This version implements the standard Stripe Payment Element flow correctly in JavaScript and should resolve the initialization errors. Report the results from the browser test (on-page log and console).

---
https://drive.google.com/file/d/10UXm7z2kPA06IHDhqeMTf3WZ5rKH1TnR/view?usp=sharing, https://drive.google.com/file/d/12oBsB_dhIP9L53VQlVcr9RC7n2W1SqKC/view?usp=sharing, https://drive.google.com/file/d/13rcrk6CPFQilYTWVSxJ8kVBxRiGD1ZWd/view?usp=sharing, https://drive.google.com/file/d/16imdccPwOR82rY_7lZQ88UpZ8iOXxHdg/view?usp=sharing, https://drive.google.com/file/d/16myrTzOOfE6-HSfWMi6Si29UA-BJp-VQ/view?usp=sharing, https://drive.google.com/file/d/1AYgfMtN1d1-9u9vu3U8BTnMs_iimvXd3/view?usp=sharing, https://drive.google.com/file/d/1AgLmaeIjyP_PV83jsHwROD9ihzh6WbvP/view?usp=sharing, https://drive.google.com/file/d/1BMgwB5VP_u3hWVJJrF_xSdopPFL3Hb8c/view?usp=sharing, https://drive.google.com/file/d/1C17krIsUYodkrcWgIfReUX2rXbMcPO-x/view?usp=sharing, https://drive.google.com/file/d/1IdpqFzukNcHvMRWIOQdHqCPk9DMZAxeo/view?usp=sharing, https://drive.google.com/file/d/1IhIpJPaLBe1eCOxsWk2Y0mE1tHxOjzZD/view?usp=sharing, https://drive.google.com/file/d/1KRkJq2BdXRYc_vaJaHz_eKPpi27TzHeM/view?usp=sharing, https://drive.google.com/file/d/1LDZCUFOfOCqg6_kDHYtbj0m0gtMvig8p/view?usp=sharing, https://drive.google.com/file/d/1PKNAlUkqL1ZP4h6hglqpTAbjyENOXhQX/view?usp=sharing, https://drive.google.com/file/d/1YOn5sMoUPaO7sMbdyTIOPIxZAT81tIHl/view?usp=sharing, https://drive.google.com/file/d/1YRvmW5LOIMKZ2pdNCKb6W-jU97b7ufiJ/view?usp=sharing, https://drive.google.com/file/d/1YuJOhriI6V0oJN5vHvHUi6jDCI8iQ1h_/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221ZgPVhzXEiFkRf798JkS5aqhRjYYp2ITI%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1ZgnZ5LL6EXQBvSubIwIRS5ip7fTVAnzI/view?usp=sharing, https://drive.google.com/file/d/1_5BVOFRWYIvmWzI-DzHETqvSoEWfh43l/view?usp=sharing, https://drive.google.com/file/d/1d1mRCTvrOJOeRpNrXLqp3xMV_rJOh4_D/view?usp=sharing, https://drive.google.com/file/d/1ffOzhQ4tc1cWeorO-Hvt67--GqB_pLu5/view?usp=sharing, https://drive.google.com/file/d/1gN6zXQ_PTV2YF9GkwR3IagLx6X3gwxv9/view?usp=sharing, https://drive.google.com/file/d/1hJlAzdnBNoo1iYUGdaQsZxsH3txBd-62/view?usp=sharing, https://drive.google.com/file/d/1j-O6iOn3jWVDDFefVBLYx77Xld88yq5A/view?usp=sharing, https://drive.google.com/file/d/1jMDj4O1ttVkVt54ABAIxJzMotKwm0mtV/view?usp=sharing, https://drive.google.com/file/d/1pcYIBRmG2NL1USKqAxzXUcaIpVPw0mw_/view?usp=sharing, https://drive.google.com/file/d/1soWEHHyAPUfcv_ZzhfB84O0W83iG7AuM/view?usp=sharing, https://drive.google.com/file/d/1tMdkomSIzN5_FIxTpVpCFk4bWzDwocv2/view?usp=sharing, https://drive.google.com/file/d/1wyFY4k7qi14bD00E6_2k5vrMVfvGURVQ/view?usp=sharing

---
<think>
1.  **Analyze Input:** The user ran `php stripe_test_v3.php` (which is the V4 code from the previous step, just misnamed by the user perhaps). The CLI output shows the PHP setup part completed successfully, including finding the user, product, simulating the cart, instantiating controllers, and generating the CSRF token. It then outputs the HTML.
2.  **Identify Problem:** The user is showing the *output* of the PHP script (which is HTML+JS), not the *result* of running that HTML in a browser. The request asks for analysis based on this output, but the *real* test happens when this HTML is rendered and the JS executes in a browser.
3.  **Recall Previous Steps:** We fixed the `ErrorHandler not found` fatal error. The previous test (`stripe_test.php` V1) confirmed basic Stripe initialization worked. The simulation script `stripe_test_v3.php` (V4 code) was created to test the full flow, including backend interaction and the JS `confirmPayment` call.
4.  **Infer User Action:** The user likely saved the HTML output generated by `php stripe_test_v3.php` to a file (e.g., `stripe_test_v3_output.html`) and opened *that file* in their browser, as instructed in the previous step.
5.  **Re-examine the JS Code:** The JavaScript inside the generated HTML (specifically the `initCheckoutPage` function copied from `main.js` in the last step) contains the corrected flow:
    *   Initialize `stripe = Stripe(key)`.
    *   *Do not* create elements yet.
    *   On button click:
        *   Call backend `processCheckout`.
        *   On backend success (get `clientSecret`):
            *   Create `elements = stripe.elements({ clientSecret, appearance })`.
            *   Create `paymentElement = elements.create('payment')`.
            *   Mount `paymentElement.mount('#payment-element')`.
            *   Call `stripe.confirmPayment({ elements, confirmParams: ... })`.
6.  **Hypothesize Browser Behavior:** When the user opens `stripe_test_v3_output.html` and clicks the button:
    *   `initializeStripe()` should run successfully on page load (as confirmed by `stripe_test.php`). The placeholder message should appear in `#payment-element`.
    *   Clicking the button triggers the `fetch` call to `index.php?page=checkout&action=processCheckout`.
    *   The backend controller should execute, create the order/PaymentIntent, and return `{ success: true, clientSecret: 'pi_...', orderId: ... }`.
    *   The JS `fetch` `.then()` block receives the `clientSecret`.
    *   It creates `stripe.elements({ clientSecret, ... })`. **This is the critical step that was failing before.**
    *   It mounts the `paymentElement`. The user should *see* the Stripe form appear.
    *   It calls `stripe.confirmPayment()`.
    *   Since it's test mode and `redirect: 'if_required'`, it likely won't redirect immediately but should log "SUCCESS/PENDING" and show the paymentIntent status.
7.  **Compare to User Image:** The user provided an image showing the *browser* rendering of the *simulation* script (`stripe_test_v3_output.html`, presumably).
    *   The on-page message is "Stripe Initialization FAILED: In order to create a payment element...".
    *   The on-page log shows:
        *   `Stripe Init: SUCCESS` (Confirms `stripe = Stripe(key)` worked).
        *   `Stripe Elements: Created` (Confirms `elements = stripe.elements({ appearance });` worked).
        *   **`Stripe Init: FAILED`** with the error "In order to create a payment element, you must pass a clientSecret or mode..."
    *   **Contradiction:** The log says `Stripe Elements: Created` successfully, but *then* immediately logs `Stripe Init: FAILED` with the exact error message related to *creating the payment element*. This implies the error isn't happening during the initial `stripe = Stripe(key)` call, nor during the `elements = stripe.elements({ appearance })` call, but specifically during the `paymentElement = elements.create('payment');` call within the `initializeStripe` function.
8.  **Re-examine `initializeStripe` in the JS:** Let's look closely at the JS provided in the file the user ran (`stripe_test_v3.php` containing V4 code):
    ```javascript
    // 1. Initialize Stripe & Elements
    function initializeStripe() {
        // ... key checks ...
        try {
            stripe = Stripe(stripeKey);
             if (!stripe || typeof stripe.elements !== 'function') { throw new Error("Stripe(key) did not return a valid object."); }
             logStep("Stripe Init", "SUCCESS", { stripeObjectPresent: !!stripe });
             const appearance = { theme: 'stripe' };
            // --- PROBLEM IS HERE ---
            elements = stripe.elements({ appearance }); // Create Elements group (This needs clientSecret or mode for payment element)
            logStep("Stripe Elements", "Created", { elementsObjectPresent: !!elements });
            const paymentElement = elements.create('payment'); // << THIS FAILS because elements group was created without clientSecret/mode
            paymentElement.mount('#payment-element');
            logStep("Payment Element", "Mounted successfully"); // << Never reached
            showStatus("Stripe initialized. Click button to simulate checkout.", 'info');
            return true;
        } catch (error) {
            logStep("Stripe Init", "FAILED", { error: error.message, details: error }); console.error("Stripe Initialization FAILED:", error);
            showStatus(`Stripe Initialization FAILED: ${error.message}`, 'error'); setLoading(false); submitButton.disabled = true; return false;
        }
    }
    ```
9.  **Identify the Bug:** The merge was incorrect! My previous analysis correctly identified the required *flow*, but the implementation in the provided `js/main.js` (and therefore the copied JS in `stripe_test_v3.php`) **still creates the `elements` group and the `paymentElement` immediately on page load**, not *after* getting the `clientSecret`. The error message confirms this: `elements.create('payment')` fails because the `elements` group it belongs to was created without the necessary `clientSecret` or `mode`.
10. **Plan Correction:** Modify the `initializeStripe` function in the test script (and subsequently in `js/main.js`) to *only* initialize `stripe = Stripe(key)`. Move the `stripe.elements(...)`, `elements.create('payment')`, and `paymentElement.mount(...)` calls into the `submitButton` click handler, *after* the `clientSecret` is received from the backend.

</think>

You've found the exact error! The browser console log from running the simulation confirms the issue we suspected, but with a twist:

1.  `[2:42:10 PM] Stripe Init: SUCCESS`: The core `stripe = Stripe(key)` works fine.
2.  `[2:42:10 PM] Stripe Elements: Created`: The `elements = stripe.elements({ appearance });` line *also* appears to run without throwing an immediate error.
3.  `[2:42:10 PM] Stripe Init: FAILED`: The error occurs immediately after creating the `elements` group, and the message is clear:
    > "In order to create a payment element, you must pass a clientSecret or mode when creating the Elements group."

**Root Cause:**

My previous attempt to fix `js/main.js` (and subsequently the JS in `stripe_test_v3.php`) was **incomplete**. While I correctly identified that `stripe.confirmPayment` needs the `clientSecret` flow, I missed that creating the *specific `payment` element* (`elements.create('payment')`) *also* requires the `elements` group it belongs to (`stripe.elements({...})`) to have been initialized with either a `clientSecret` or a `mode`.

The current code initializes the `elements` group on page load *without* the `clientSecret` (because it doesn't have it yet) and then immediately tries to create the `paymentElement`, which fails.

**Solution:**

We need to follow the structure decided upon earlier: defer the creation of **both** the `elements` group *and* the `paymentElement` until *after* the `clientSecret` is received from the backend upon clicking the submit button.

**Corrected `stripe_test_v3.php` (v5 - Final Fix for JS Flow):**

This version applies the correct JavaScript logic.

```php
<?php
// stripe_test_v3.php - Comprehensive Checkout Simulation (v5 - Corrected JS Flow)

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
require_once __DIR__ . '/includes/ErrorHandler.php'; // Needs to be included before init
ErrorHandler::init(); // Initialize basic error handling
// --- End Core Includes ---

require_once __DIR__ . '/includes/SecurityMiddleware.php';

// --- Use Composer Autoloader if available ---
$autoloader_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader_path)) {
    echo "PHP Setup: Using Composer autoloader.\n";
    require_once $autoloader_path;
    // Manual include non-PSR classes if needed
    if (!class_exists('BaseController')) require_once __DIR__ . '/controllers/BaseController.php';
    if (!class_exists('EmailService')) require_once __DIR__ . '/includes/EmailService.php';
    // Autoloader should handle the rest if composer.json is correct
} else {
    echo "PHP Setup: Composer autoloader not found, using manual includes.\n";
    // Manual requires if no autoloader
    require_once __DIR__ . '/controllers/BaseController.php';
    require_once __DIR__ . '/includes/EmailService.php'; // BaseController needs this
    require_once __DIR__ . '/controllers/PaymentController.php';
    require_once __DIR__ . '/controllers/CheckoutController.php';
    require_once __DIR__ . '/controllers/CouponController.php';
    require_once __DIR__ . '/controllers/TaxController.php';
    require_once __DIR__ . '/controllers/InventoryController.php';
    require_once __DIR__ . '/models/Order.php';
    require_once __DIR__ . '/models/Product.php';
    require_once __DIR__ . '/models/Cart.php';
    require_once __DIR__ . '/models/User.php';
}


// --- Simulate Logged-in User ---
$testUserId = 2; // Use the ID confirmed previously
if (!isset($pdo)) die("FATAL: PDO object not available after includes/db.php. Check db.php.\n");
$testUserData = null;
try {
    $userCheckStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
    $userCheckStmt->execute([$testUserId]);
    $testUserData = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("FATAL: Database error checking test user: " . $e->getMessage() . "\n"); }
if (!$testUserData) { die("FATAL: Test User ID {$testUserId} not found in the database. Please use a valid ID.\n"); }
$testUserEmail = $testUserData['email']; $testUserName = $testUserData['name'];
echo "PHP Setup: Test user found (ID: {$testUserId}, Email: {$testUserEmail}).\n";

// --- Start Session ---
if (session_status() === PHP_SESSION_NONE) { if (!headers_sent()) { session_start(); echo "PHP Setup: Session started.\n"; } else { error_log("stripe_test_v3.php Warning: Headers already sent, cannot start session."); echo "PHP Setup Warning: Headers already sent, cannot start session.\n"; } } else { echo "PHP Setup: Session already active.\n"; }
$_SESSION['user_id'] = $testUserId; $_SESSION['user_role'] = 'user'; $_SESSION['user'] = [ 'id' => $testUserId, 'name' => $testUserName, 'email' => $testUserEmail, 'role' => 'user' ]; $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'TestAgentCLI'; $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; $_SESSION['last_login'] = time(); $_SESSION['last_regeneration'] = time();

// --- Simulate Cart ---
$testProductId = 1; // Use the ID confirmed previously
$testQuantity = 1;
try {
    $productCheckStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?"); $productCheckStmt->execute([$testProductId]); if (!$productCheckStmt->fetch()) { die("FATAL: Test Product ID {$testProductId} not found in the database.\n"); } echo "PHP Setup: Test product found (ID: {$testProductId}).\n";
    if (!class_exists('Cart')) { die("FATAL: Cart class not found. Check includes/autoloader.\n"); }
    $cartModel = new Cart($pdo, $testUserId); $cartModel->clearCart(); if (!$cartModel->addItem($testProductId, $testQuantity)) { throw new Exception("CartModel::addItem returned false."); } $_SESSION['cart_count'] = $cartModel->getCartCount(); $cartCheckItems = $cartModel->getItems(); if (empty($cartCheckItems)) { throw new Exception("Failed to add/retrieve test item."); } echo "PHP Setup: Cart simulated successfully (User: {$testUserId}, Product: {$testProductId}, Count: {$_SESSION['cart_count']}).\n";
} catch (Exception $e) { die("Cart Simulation Error: " . $e->getMessage() . "\n"); }

// --- Instantiate Controllers ---
try {
    if (!class_exists('PaymentController')) die("FATAL: PaymentController class not found.\n"); if (!class_exists('CheckoutController')) die("FATAL: CheckoutController class not found.\n");
    $paymentController = new PaymentController($pdo); $checkoutController = new CheckoutController($pdo, $paymentController); echo "PHP Setup: Controllers instantiated successfully.\n";
} catch (Throwable $e) { die("FATAL: Error instantiating controllers: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString() . "\n"); }

// --- CSRF & Post Data ---
if (!class_exists('SecurityMiddleware')) { die("FATAL: SecurityMiddleware class not found.\n"); } $csrfToken = SecurityMiddleware::generateCSRFToken(); echo "PHP Setup: CSRF Token generated: " . $csrfToken . "\n";
$samplePostData = [ 'shipping_name' => $testUserName, 'shipping_email' => $testUserEmail, 'shipping_address' => '123 Test St', 'shipping_address_line2' => 'Apt 4B', 'shipping_city' => 'Testville', 'shipping_state' => 'TS', 'shipping_zip' => '54321', 'shipping_country' => 'US', 'order_notes' => 'Test order generated by stripe_test_v3.php', 'save_address' => '0', 'csrf_token' => $csrfToken, 'applied_coupon_code' => '' ]; echo "PHP Setup: Sample POST data prepared.\n";

// --- Check Constants ---
if (!defined('STRIPE_PUBLIC_KEY') || !defined('BASE_URL')) { die("Error: STRIPE_PUBLIC_KEY or BASE_URL constant is not defined. Check config.php.\n"); } $stripePublicKey = STRIPE_PUBLIC_KEY; $baseUrl = BASE_URL; echo "PHP Setup: Constants checked (Stripe PK / Base URL).\n"; echo "PHP Setup: Completed successfully. Generating HTML output...\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Checkout Simulation (v5 - Corrected JS Flow)</title>
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

    <h1>Stripe Checkout Simulation (v5 - Corrected JS Flow)</h1>
    <p>This script attempts to simulate the full checkout flow:</p>
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
        // --- JavaScript Simulation (v5 - Corrected Elements Init) ---
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
        // Elements will be initialized LATER

        // --- Helper Functions (logging, status, loading - unchanged) ---
        function logStep(step, status, data = null) { /* ... */ }
        function showStatus(message, type = 'info') { /* ... */ }
        function setLoading(isLoading) { /* ... */ }
        // --- Include full definition of helper functions here ---
        function logStep(step, status, data = null) {
            const timestamp = new Date().toLocaleTimeString();
            let logEntry = `[${timestamp}] ${step}: ${status}`;
            if (data) { try { logEntry += `\nData: ${JSON.stringify(data, (key, value) => typeof value === 'object' && value !== null && value.constructor === Object && Object.keys(value).length === 0 ? '{}' : value, 2 )}`; } catch (e) { logEntry += `\nData: (Could not serialize - ${typeof data})`; } }
            logElement.textContent += `\n${logEntry}\n`; logElement.scrollTop = logElement.scrollHeight;
        }
        function showStatus(message, type = 'info') { messageDiv.textContent = message; messageDiv.className = type; }
        function setLoading(isLoading) { submitButton.disabled = isLoading; spinner.classList.toggle('hidden', !isLoading); buttonText.classList.toggle('hidden', isLoading); }

        // --- Initialize Stripe Core Object ONLY ---
        function initializeStripeCore() {
            logStep("Stripe Core Init", "Starting...", { key: stripeKey ? stripeKey.substring(0, 10) + '...' : 'MISSING' });
            if (!stripeKey || stripeKey.startsWith('pk_test_51xxx')) {
                const msg = "Invalid or placeholder Stripe Public Key."; logStep("Stripe Core Init", "FAILED", { error: msg }); showStatus(msg, 'error'); setLoading(false); submitButton.disabled = true; return false;
            }
            try {
                stripe = Stripe(stripeKey);
                if (!stripe) { throw new Error("Stripe(key) failed to return an object."); }
                logStep("Stripe Core Init", "SUCCESS", { stripeObjectPresent: !!stripe });
                paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Secure payment form will load here...</p>'; // Placeholder
                showStatus("Stripe Core ready. Click button to simulate checkout.", 'info');
                return true;
            } catch (error) {
                logStep("Stripe Core Init", "FAILED", { error: error.message, details: error }); console.error("Stripe Core Initialization FAILED:", error);
                showStatus(`Stripe Core Initialization FAILED: ${error.message}`, 'error'); setLoading(false); submitButton.disabled = true; return false;
            }
        }

        // --- Simulate Checkout Button Click ---
        submitButton.addEventListener('click', async () => {
            if (!stripe) { showStatus("Stripe Core not initialized correctly.", 'error'); return; } // Check core stripe object
            setLoading(true); showStatus("Simulating checkout process...", 'info'); logStep("Checkout Click", "Initiated");

            // 1. Call Backend (processCheckout)
            let clientSecret = null; let orderId = null; let processCheckoutError = null; let elements = null; // Define elements locally for this process
            const formData = new FormData();
            <?php foreach ($samplePostData as $key => $value): ?> formData.append('<?= $key ?>', '<?= addslashes($value) ?>'); <?php endforeach; ?>
            logStep("Backend Call", "Sending data to processCheckout...", Object.fromEntries(formData));

            try {
                const response = await fetch('index.php?page=checkout&action=processCheckout', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
                logStep("Backend Call", `Received status ${response.status}`);
                const responseText = await response.text();
                let loggableText = responseText; try { const tempData = JSON.parse(responseText); if (tempData.clientSecret) { loggableText = JSON.stringify({...tempData, clientSecret: tempData.clientSecret.substring(0, 15) + '...'}); } } catch(parseErr) {}
                logStep("Backend Call", "Raw response text (Secret Redacted)", loggableText);
                if (!response.ok) { let errorMsg = `Server returned status ${response.status}.`; try { const errorData = JSON.parse(responseText); errorMsg = errorData.error || errorMsg; } catch(e){} throw new Error(errorMsg); }
                const data = JSON.parse(responseText); logStep("Backend Call", "Parsed JSON response", data);
                if (data.success && data.clientSecret && data.orderId) {
                    clientSecret = data.clientSecret; orderId = data.orderId;
                    logStep("Backend Call", "SUCCESS", { clientSecret: clientSecret.substring(0, 15) + '...', orderId });
                    showStatus(`Backend processed (Order ID: ${orderId}). Loading payment form...`, 'info');
                } else { throw new Error(data.error || 'Backend failed to process checkout.'); }
            } catch (error) {
                processCheckoutError = error; logStep("Backend Call", "FAILED", { error: error.message, details: error }); console.error("Backend processCheckout Error:", error);
                showStatus(`Backend Error: ${error.message}`, 'error'); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Could not prepare payment. Please try again.</p>'; setLoading(false); return;
            }

            // --- *** 2. Initialize Elements & Mount Payment Element *** ---
            try {
                if (!clientSecret) throw new Error("Client secret is missing after backend call.");
                const appearance = { theme: 'stripe', variables: { colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151', colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem' } };
                elements = stripe.elements({ clientSecret: clientSecret, appearance }); // Pass clientSecret
                logStep("Stripe Elements", "Created with clientSecret", { elementsObjectPresent: !!elements });
                const paymentElement = elements.create('payment');
                paymentElementContainer.innerHTML = ''; // Clear placeholder
                paymentElement.mount('#payment-element');
                logStep("Payment Element", "Mounted successfully");
                showStatus(`Payment form loaded. Confirming payment for Order ID: ${orderId}...`, 'info');
            } catch (elementsError) {
                logStep("Elements/Mount", "FAILED", { error: elementsError.message, details: elementsError }); console.error("Stripe Elements creation/mounting error:", elementsError);
                showMessage("Failed to load the payment form. Please refresh.", true); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error loading payment form.</p>';
                setLoading(false); return;
            }

            // --- *** 3. Confirm Payment *** ---
            if (clientSecret && stripe && elements) { // Check elements again
                 logStep("Stripe Confirm", "Attempting stripe.confirmPayment...");
                 const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
                 const testReturnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation&simulated=1`;
                 logStep("Stripe Confirm", "Using return_url", testReturnUrl);
                 try {
                     const { error, paymentIntent } = await stripe.confirmPayment({ elements, confirmParams: { return_url: testReturnUrl }, redirect: 'if_required' });
                     if (error) { logStep("Stripe Confirm", "FAILED", { errorType: error.type, errorMessage: error.message, details: error }); console.error("Stripe confirmPayment Error:", error); showStatus(`Stripe Payment Confirmation Failed: ${error.message}`, 'error'); }
                     else if (paymentIntent) { logStep("Stripe Confirm", "SUCCESS/PENDING", { paymentIntentStatus: paymentIntent.status, paymentIntentId: paymentIntent.id }); console.log("Stripe confirmPayment Result:", paymentIntent); showStatus(`Stripe Payment Confirmation finished! Status: ${paymentIntent.status}. Check console/webhook.`, 'success'); }
                     else { logStep("Stripe Confirm", "UNKNOWN OUTCOME (No Redirect, No Error, No PI)", { error: null, paymentIntent: null }); showStatus("Stripe payment confirmation finished with an unknown outcome.", 'warning'); }
                 } catch (confirmError) { logStep("Stripe Confirm", "EXCEPTION", { error: confirmError.message, details: confirmError }); console.error("Exception during stripe.confirmPayment:", confirmError); showStatus(`Exception during payment confirmation: ${confirmError.message}`, 'error');
                 } finally { setLoading(false); }
            } else { logStep("Stripe Confirm", "SKIPPED", { reason: "Missing clientSecret, stripe, or elements." }); showMessage('Internal error before payment confirmation.', 'error'); setLoading(false); }
        });

        // Initialize Stripe Core on page load
        if (!initializeStripeCore()) { logStep("Setup", "Stripe Core initialization failed. Disabling button."); submitButton.disabled = true; }
    </script>

</body>
</html>
```

**Summary of Changes in `stripe_test_v3.php` (v5):**

1.  **Fatal Error Catcher:** Kept the `register_shutdown_function` to catch any unexpected PHP fatal errors.
2.  **PHP Setup:** Enhanced checks for `$pdo`, user, and product existence with clearer `die()` messages for CLI. Added `echo` statements for better CLI progress tracking. Moved `ErrorHandler::init()` to run right after core includes. Prioritized Composer autoloader but added manual includes as fallbacks with checks.
3.  **JavaScript (`initializeStripeCore`):** Renamed the initial JS function to clarify it only initializes the core `stripe` object, *not* the elements. It sets placeholder text in the `#payment-element` div.
4.  **JavaScript (`submitButton` Listener):**
    *   Renamed the main function `initializeStripe` was split. Core init runs on page load.
    *   The click handler now performs the following sequence:
        *   Calls the backend (`processCheckout`) to get the `clientSecret`.
        *   **NEW:** If backend is successful, it then creates `stripe.elements({ clientSecret, ... })`.
        *   **NEW:** It then creates and mounts the `paymentElement`.
        *   Finally, it calls `stripe.confirmPayment({ elements, ... })`.
    *   Error handling added specifically for the elements creation/mounting step.

**Please run this corrected `stripe_test_v3.php` (v5) script again via PHP CLI (`php stripe_test_v3.php`), save the HTML output, and then test that HTML file in your browser.** This version implements the standard Stripe Payment Element flow correctly in JavaScript and should resolve the initialization errors. Report the results from the browser test (on-page log and console).

---
https://drive.google.com/file/d/10UXm7z2kPA06IHDhqeMTf3WZ5rKH1TnR/view?usp=sharing, https://drive.google.com/file/d/12oBsB_dhIP9L53VQlVcr9RC7n2W1SqKC/view?usp=sharing, https://drive.google.com/file/d/13rcrk6CPFQilYTWVSxJ8kVBxRiGD1ZWd/view?usp=sharing, https://drive.google.com/file/d/16imdccPwOR82rY_7lZQ88UpZ8iOXxHdg/view?usp=sharing, https://drive.google.com/file/d/16myrTzOOfE6-HSfWMi6Si29UA-BJp-VQ/view?usp=sharing, https://drive.google.com/file/d/1AYgfMtN1d1-9u9vu3U8BTnMs_iimvXd3/view?usp=sharing, https://drive.google.com/file/d/1AgLmaeIjyP_PV83jsHwROD9ihzh6WbvP/view?usp=sharing, https://drive.google.com/file/d/1BMgwB5VP_u3hWVJJrF_xSdopPFL3Hb8c/view?usp=sharing, https://drive.google.com/file/d/1C17krIsUYodkrcWgIfReUX2rXbMcPO-x/view?usp=sharing, https://drive.google.com/file/d/1IdpqFzukNcHvMRWIOQdHqCPk9DMZAxeo/view?usp=sharing, https://drive.google.com/file/d/1IhIpJPaLBe1eCOxsWk2Y0mE1tHxOjzZD/view?usp=sharing, https://drive.google.com/file/d/1KRkJq2BdXRYc_vaJaHz_eKPpi27TzHeM/view?usp=sharing, https://drive.google.com/file/d/1LDZCUFOfOCqg6_kDHYtbj0m0gtMvig8p/view?usp=sharing, https://drive.google.com/file/d/1PKNAlUkqL1ZP4h6hglqpTAbjyENOXhQX/view?usp=sharing, https://drive.google.com/file/d/1YOn5sMoUPaO7sMbdyTIOPIxZAT81tIHl/view?usp=sharing, https://drive.google.com/file/d/1YRvmW5LOIMKZ2pdNCKb6W-jU97b7ufiJ/view?usp=sharing, https://drive.google.com/file/d/1YuJOhriI6V0oJN5vHvHUi6jDCI8iQ1h_/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221ZgPVhzXEiFkRf798JkS5aqhRjYYp2ITI%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1ZgnZ5LL6EXQBvSubIwIRS5ip7fTVAnzI/view?usp=sharing, https://drive.google.com/file/d/1_5BVOFRWYIvmWzI-DzHETqvSoEWfh43l/view?usp=sharing, https://drive.google.com/file/d/1d1mRCTvrOJOeRpNrXLqp3xMV_rJOh4_D/view?usp=sharing, https://drive.google.com/file/d/1ffOzhQ4tc1cWeorO-Hvt67--GqB_pLu5/view?usp=sharing, https://drive.google.com/file/d/1gN6zXQ_PTV2YF9GkwR3IagLx6X3gwxv9/view?usp=sharing, https://drive.google.com/file/d/1hJlAzdnBNoo1iYUGdaQsZxsH3txBd-62/view?usp=sharing, https://drive.google.com/file/d/1j-O6iOn3jWVDDFefVBLYx77Xld88yq5A/view?usp=sharing, https://drive.google.com/file/d/1jMDj4O1ttVkVt54ABAIxJzMotKwm0mtV/view?usp=sharing, https://drive.google.com/file/d/1pcYIBRmG2NL1USKqAxzXUcaIpVPw0mw_/view?usp=sharing, https://drive.google.com/file/d/1soWEHHyAPUfcv_ZzhfB84O0W83iG7AuM/view?usp=sharing, https://drive.google.com/file/d/1tMdkomSIzN5_FIxTpVpCFk4bWzDwocv2/view?usp=sharing, https://drive.google.com/file/d/1wyFY4k7qi14bD00E6_2k5vrMVfvGURVQ/view?usp=sharing
