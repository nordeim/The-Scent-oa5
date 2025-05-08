<?php
// stripe_test.php - Minimal Stripe.js Initialization Test

// Define ROOT_PATH if not already defined (might be needed by config or other includes)
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// Include configuration to get the Stripe key
// Use error suppression and check definition for robustness
$configLoaded = @include_once __DIR__ . '/config.php';

if (!$configLoaded) {
    die("Error: config.php not found or failed to load.");
}

// Check if the constant is actually defined after including config.php
if (!defined('STRIPE_PUBLIC_KEY')) {
    $stripePublicKey = 'STRIPE_PUBLIC_KEY_CONSTANT_NOT_DEFINED'; // Indicate the constant itself is missing
    error_log("Stripe Test Error: STRIPE_PUBLIC_KEY constant is not defined in config.php");
} else {
    $stripePublicKey = STRIPE_PUBLIC_KEY;
     // Basic check if the key looks like a placeholder - it shouldn't if user updated it
     if (strpos($stripePublicKey, 'pk_test_51xxx') === 0 || empty($stripePublicKey)) {
         error_log("Stripe Test Warning: STRIPE_PUBLIC_KEY in config.php still seems to be a placeholder or is empty.");
     }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Initialization Test</title>
    <!-- Include Stripe.js directly -->
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body { font-family: sans-serif; padding: 20px; }
        #message { margin-top: 20px; padding: 15px; border-radius: 5px; }
        .success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        pre { background-color: #eee; padding: 10px; border-radius: 3px; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<!-- Pass the key via data attribute, similar to the main app -->
<body data-stripe-public-key="<?= htmlspecialchars($stripePublicKey) ?>">

    <h1>Stripe.js Initialization Test</h1>
    <p>Checking if Stripe can be initialized with the configured key.</p>
    <p>Public Key Found in config.php: <code><?= htmlspecialchars($stripePublicKey) ?></code></p>

    <div id="message">Initializing...</div>
    <!-- Dummy payment element div - might be needed by some internal Stripe checks -->
    <div id="payment-element" style="border:1px solid #ccc; padding: 10px; margin-top:10px;">Dummy Payment Element Area</div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageDiv = document.getElementById('message');
            const stripeKeyFromData = document.body.dataset.stripePublicKey;
            let stripe = null; // Initialize stripe variable

            console.log("Attempting to initialize Stripe with key:", stripeKeyFromData);
            messageDiv.innerHTML = `Attempting to initialize Stripe with key: <code>${stripeKeyFromData}</code><br>Check console (F12) for details.`;

            // Basic check for placeholder or missing key in JS
            if (!stripeKeyFromData || stripeKeyFromData === 'STRIPE_PUBLIC_KEY_CONSTANT_NOT_DEFINED' || stripeKeyFromData.startsWith('pk_test_51xxx')) {
                 messageDiv.innerHTML = `<strong>Error:</strong> Invalid or placeholder Stripe Public Key detected: <code>${stripeKeyFromData}</code>. Please check your config.php.`;
                 messageDiv.className = 'error';
                 console.error("Stripe Initialization Error: Invalid or placeholder key passed from PHP:", stripeKeyFromData);
                 return; // Stop execution
            }

            try {
                // --- The Core Initialization Step ---
                stripe = Stripe(stripeKeyFromData);
                // --- End Core Initialization Step ---

                if (stripe && typeof stripe.elements === 'function') {
                    // Basic check if stripe object looks valid
                    console.log("Stripe Initialization SUCCESSFUL!", stripe);
                    messageDiv.innerHTML = `<strong>Success!</strong> Stripe.js initialized successfully.<br>Stripe Object: <pre>${JSON.stringify(stripe, null, 2)}</pre>`; // Show basic object structure
                    messageDiv.className = 'success';

                    // Optional: Try creating and mounting an element to be more thorough
                    try {
                        const elements = stripe.elements();
                        const paymentElement = elements.create('payment'); // Or 'card'
                        paymentElement.mount('#payment-element');
                        console.log("Stripe Payment Element mounted successfully.");
                        messageDiv.innerHTML += "<br>Payment Element mounted successfully.";
                    } catch (elementError) {
                         console.error("Stripe Element Mounting Error:", elementError);
                         messageDiv.innerHTML += `<br><strong>Warning:</strong> Could not mount payment element: <pre>${JSON.stringify(elementError, null, 2)}</pre>`;
                         // Keep message class as success, as core init worked, but add warning
                    }

                } else {
                    // This case might occur if Stripe() runs but returns an unexpected object
                    console.error("Stripe Initialization Warning: Stripe() did not return a valid object.", stripe);
                    messageDiv.innerHTML = `<strong>Warning:</strong> Stripe initialized but might be invalid. Check console. Object: <pre>${JSON.stringify(stripe, null, 2)}</pre>`;
                    messageDiv.className = 'error';
                }

            } catch (error) {
                // Catch errors thrown by Stripe() constructor
                console.error("Stripe Initialization FAILED:", error);
                // Display detailed error information
                 let errorMessage = `<strong>Error:</strong> Stripe initialization failed.<br>`;
                 errorMessage += `<strong>Error Type:</strong> ${error.type || 'N/A'}<br>`;
                 errorMessage += `<strong>Message:</strong> ${error.message || 'No specific message.'}<br>`;
                 if (error.code) errorMessage += `<strong>Code:</strong> ${error.code}<br>`;
                 if (error.stack) errorMessage += `<strong>Stack:</strong> <pre>${error.stack}</pre>`; // Show stack trace if available
                 else errorMessage += `<strong>Details:</strong> <pre>${JSON.stringify(error, null, 2)}</pre>`;

                messageDiv.innerHTML = errorMessage;
                messageDiv.className = 'error';
            }
        });
    </script>

</body>
</html>
