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
