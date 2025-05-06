Okay, I will meticulously update the `views/account/profile.php` file based on the provided `profile.php-orig.txt` and add the requested countries to the dropdown list. I will also update `views/checkout.php` for consistency.

**Analysis:**

1.  **Target File:** `views/account/profile.php` (using `profile.php-orig.txt` as base).
2.  **Change:** Add `<option>` elements for Malaysia (MY) and Singapore (SG) within the `<select id="country">` element.
3.  **Insertion Point:** After the `<option>` for Australia.
4.  **Required Code:**
    *   `<option value="MY" <?= (($userAddress['country'] ?? '') === 'MY') ? 'selected' : '' ?>>Malaysia</option>`
    *   `<option value="SG" <?= (($userAddress['country'] ?? '') === 'SG') ? 'selected' : '' ?>>Singapore</option>`
5.  **Consistency Check:** The same change needs to be applied to the country dropdown in `views/checkout.php`.
6.  **Backend Check:** No backend changes are required in controllers or models, as they handle the country code as a string, and the database schema (`VARCHAR(50)`) accommodates these codes.

**Updated Files:**

```php
<?php
// Updated: views/account/profile.php (v16.2 - Added MY, SG countries)
// Added header and footer includes

require_once __DIR__ . '/../layout/header.php'; // <<< ADDED
?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>

                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile" class="active">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=quiz">
                                    <i class="fas fa-clipboard-list"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>

            <!-- Main Content -->
            <div class="account-content">
                <h1 class="page-title" data-aos="fade-up">Profile Settings</h1>

                <?php // Flash messages handled globally by header.php now ?>

                <div class="profile-grid grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Personal Information -->
                    <div class="profile-card bg-white p-6 rounded-lg shadow" data-aos="fade-up">
                        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Personal Information</h2>
                        <form action="index.php?page=account&section=profile" method="POST"
                              class="profile-form space-y-4" id="profileForm">
                              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                              <input type="hidden" name="action" value="update_profile"> <!-- Specify action -->
                            <div class="form-group">
                                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" id="name" name="name" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                                       value="<?= htmlspecialchars($user['name']) ?>">
                            </div>

                            <div class="form-group">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                <input type="email" id="email" name="email" required
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                                       value="<?= htmlspecialchars($user['email']) ?>">
                            </div>

                            <button type="submit" class="btn-primary">Save Changes</button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="profile-card bg-white p-6 rounded-lg shadow" data-aos="fade-up" data-aos-delay="100">
                        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Change Password</h2>
                        <form action="index.php?page=account&section=profile" method="POST"
                              class="password-form space-y-4" id="passwordForm">
                              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                              <input type="hidden" name="action" value="update_password"> <!-- Specify action -->
                            <div class="form-group">
                                <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                <div class="password-input relative mt-1">
                                    <input type="password" id="current_password" name="current_password"
                                           class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary pr-10">
                                    <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <div class="password-input relative mt-1">
                                    <input type="password" id="new_password" name="new_password"
                                           class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary pr-10"
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{12,}"
                                           title="Must contain at least 12 characters, including uppercase, lowercase, number, and special character."
                                           aria-describedby="passwordRequirements">
                                    <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <div class="password-input relative mt-1">
                                    <input type="password" id="confirm_password" name="confirm_password"
                                           class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary pr-10">
                                    <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Updated Password Requirements Styling -->
                             <div class="password-requirements mt-4 p-4 border border-gray-200 rounded-md bg-gray-50/50" id="passwordRequirements">
                                <h4 class="text-sm font-medium text-gray-700 mb-2 font-body">Password must contain:</h4>
                                <ul class="space-y-1 text-xs text-gray-600 font-body">
                                    <li id="req-length" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                                        <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> At least 12 characters
                                    </li>
                                    <li id="req-uppercase" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                                        <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One uppercase letter (A-Z)
                                    </li>
                                    <li id="req-lowercase" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                                        <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One lowercase letter (a-z)
                                    </li>
                                    <li id="req-number" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                                        <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One number (0-9)
                                    </li>
                                    <li id="req-special" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                                        <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> One special character (e.g., !@#$)
                                    </li>
                                     <li id="req-match" class="requirement flex items-center not-met"> <!-- Added match requirement -->
                                         <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> Passwords match
                                     </li>
                                </ul>
                            </div>

                            <button type="submit" class="btn-primary">Update Password</button>
                        </form>
                    </div>

                    <!-- START: Shipping Address Section -->
                    <div class="profile-card bg-white p-6 rounded-lg shadow md:col-span-2" data-aos="fade-up" data-aos-delay="200">
                         <h2 class="text-xl font-semibold mb-4 border-b pb-2">Shipping Address</h2>
                         <form action="index.php?page=account&section=profile" method="POST"
                               class="address-form space-y-4" id="addressForm">
                              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                              <input type="hidden" name="action" value="update_address"> <!-- Specify action -->

                              <div class="form-group">
                                   <label for="address_line1" class="block text-sm font-medium text-gray-700">Address Line 1</label>
                                   <input type="text" id="address_line1" name="address_line1" required
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                                          value="<?= htmlspecialchars($userAddress['address_line1'] ?? '') ?>"
                                          placeholder="Street address, P.O. box, company name, c/o">
                              </div>

                              <div class="form-group">
                                   <label for="address_line2" class="block text-sm font-medium text-gray-700">Address Line 2 (Optional)</label>
                                   <input type="text" id="address_line2" name="address_line2"
                                          class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                                          value="<?= htmlspecialchars($userAddress['address_line2'] ?? '') ?>"
                                          placeholder="Apartment, suite, unit, building, floor, etc.">
                              </div>

                              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                   <div class="form-group">
                                        <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                                        <input type="text" id="city" name="city" required
                                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                                               value="<?= htmlspecialchars($userAddress['city'] ?? '') ?>">
                                   </div>
                                   <div class="form-group">
                                        <label for="state" class="block text-sm font-medium text-gray-700">State / Province / Region</label>
                                        <input type="text" id="state" name="state" required
                                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                                               value="<?= htmlspecialchars($userAddress['state'] ?? '') ?>">
                                   </div>
                              </div>

                              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label for="postal_code" class="block text-sm font-medium text-gray-700">ZIP / Postal Code</label>
                                        <input type="text" id="postal_code" name="postal_code" required
                                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary"
                                               value="<?= htmlspecialchars($userAddress['postal_code'] ?? '') ?>">
                                    </div>
                                   <div class="form-group">
                                        <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                                        <select id="country" name="country" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary focus:border-primary">
                                             <option value="">Select Country</option>
                                             <option value="US" <?= (($userAddress['country'] ?? '') === 'US') ? 'selected' : '' ?>>United States</option>
                                             <option value="CA" <?= (($userAddress['country'] ?? '') === 'CA') ? 'selected' : '' ?>>Canada</option>
                                             <option value="GB" <?= (($userAddress['country'] ?? '') === 'GB') ? 'selected' : '' ?>>United Kingdom</option>
                                             <option value="AU" <?= (($userAddress['country'] ?? '') === 'AU') ? 'selected' : '' ?>>Australia</option>
                                             <!-- START: Added Countries -->
                                             <option value="MY" <?= (($userAddress['country'] ?? '') === 'MY') ? 'selected' : '' ?>>Malaysia</option>
                                             <option value="SG" <?= (($userAddress['country'] ?? '') === 'SG') ? 'selected' : '' ?>>Singapore</option>
                                             <!-- END: Added Countries -->
                                             {/* Add more countries as needed */}
                                        </select>
                                   </div>
                              </div>

                              <button type="submit" class="btn-primary mt-4">Save Address</button>
                         </form>
                    </div>
                    <!-- END: Shipping Address Section -->

                    <!-- Communication Preferences -->
                     <div class="profile-card bg-white p-6 rounded-lg shadow md:col-span-2" data-aos="fade-up" data-aos-delay="300"> {/* Adjusted delay */}
                         <h2 class="text-xl font-semibold mb-4 border-b pb-2">Communication Preferences</h2>
                         <form action="index.php?page=account&section=profile" method="POST"
                               class="preferences-form space-y-3" id="preferencesForm">
                             <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                             <input type="hidden" name="action" value="update_preferences"> <!-- Specify action -->
                             <div class="form-group">
                                 <label class="checkbox-label flex items-center">
                                     <input type="checkbox" name="newsletter_subscribed" value="1"
                                            class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                                            <?= ($user['newsletter_subscribed'] ?? 0) ? 'checked' : '' ?>>
                                     <span>Monthly newsletter with aromatherapy tips and trends</span>
                                 </label>
                             </div>
                             <button type="submit" class="btn-primary mt-4">Update Preferences</button>
                         </form>
                     </div>

                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Existing JS for password toggle and validation (from content_of_code_files_6) remains unchanged
document.addEventListener('DOMContentLoaded', function() {
    // --- Password visibility toggle ---
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            if (input && input.type) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon?.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon?.classList.replace('fa-eye-slash', 'fa-eye');
                }
            }
        });
    });

    // --- Password strength validation & matching ---
    const passwordForm = document.getElementById('passwordForm');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[\W_]/, element: document.getElementById('req-special') }, // Match any non-alphanumeric
        match: { element: document.getElementById('req-match') }
    };

    function validatePasswordRequirements() {
        if (!passwordForm || !newPassword || !confirmPassword) return; // Check if elements exist

        let allMet = true;
        const passwordValue = newPassword.value;
        const confirmPasswordValue = confirmPassword.value;

        // Only validate if new password field is not empty
        const shouldValidate = passwordValue.length > 0;

        for (const reqKey in requirements) {
            const req = requirements[reqKey];
            if (!req.element) continue;

            let isMet = false;
            if (reqKey === 'match') {
                isMet = passwordValue && passwordValue === confirmPasswordValue;
            } else if (req.regex) {
                isMet = req.regex.test(passwordValue);
            }

            // Update UI only if validation should occur
            if (shouldValidate) {
                 req.element.classList.toggle('met', isMet);
                 req.element.classList.toggle('not-met', !isMet);
                 const icon = req.element.querySelector('i');
                 if (icon) {
                     icon.classList.toggle('fa-check-circle', isMet);
                     icon.classList.toggle('fa-times-circle', !isMet);
                     icon.classList.toggle('text-green-500', isMet);
                     icon.classList.toggle('text-red-500', !isMet);
                 }
            } else {
                 // Reset UI if new password field is empty
                 req.element.classList.remove('met');
                 req.element.classList.add('not-met');
                 const icon = req.element.querySelector('i');
                 if (icon) {
                      icon.classList.remove('fa-check-circle', 'text-green-500');
                      icon.classList.add('fa-times-circle', 'text-red-500');
                 }
            }

            if (shouldValidate && !isMet) allMet = false;
        }
        // Enable submit button only if *all* requirements are met OR if the new password field is empty
        const submitButton = passwordForm.querySelector('button[type="submit"]');
        if (submitButton) {
             submitButton.disabled = !(allMet || !shouldValidate);
        }

        return allMet || !shouldValidate;
    }

    if (passwordForm && newPassword && confirmPassword) {
        newPassword.addEventListener('input', validatePasswordRequirements);
        confirmPassword.addEventListener('input', validatePasswordRequirements);
        // Initial validation check on load
        validatePasswordRequirements();

        passwordForm.addEventListener('submit', function(e) {
            // Only perform final check if a new password has been entered
            if (newPassword.value.length > 0) {
                // Check if passwords match
                 if (newPassword.value !== confirmPassword.value) {
                     e.preventDefault();
                     // Use the global flash message function if available
                     if(typeof window.showFlashMessage === 'function') {
                          window.showFlashMessage('New passwords do not match.', 'error');
                     } else { alert('New passwords do not match.'); }
                     confirmPassword.focus();
                     return;
                 }
                 // Check if password meets requirements according to pattern attribute (browser validation)
                 if (!newPassword.checkValidity()) {
                     e.preventDefault();
                      if(typeof window.showFlashMessage === 'function') {
                           window.showFlashMessage('Please ensure the new password meets all requirements.', 'error');
                      } else { alert('Please ensure the new password meets all requirements.'); }
                     newPassword.focus();
                     return;
                 }
            }
             // Current password validation (if new password is set) should be handled server-side for security.
        });
    }

    // Optional: Add basic client-side validation for the address form if desired
    const addressForm = document.getElementById('addressForm');
    if (addressForm) {
         addressForm.addEventListener('submit', function(e) {
             const requiredAddressFields = ['address_line1', 'city', 'state', 'postal_code', 'country'];
             let addressIsValid = true;
             requiredAddressFields.forEach(id => {
                  const input = document.getElementById(id);
                  if (!input || !input.value.trim()) {
                       addressIsValid = false;
                       input?.classList.add('border-red-500'); // Add error indication
                  } else {
                       input?.classList.remove('border-red-500');
                  }
             });
             if (!addressIsValid) {
                  e.preventDefault();
                   if(typeof window.showFlashMessage === 'function') {
                       window.showFlashMessage('Please fill in all required address fields.', 'error');
                  } else { alert('Please fill in all required address fields.'); }
             } else {
                 // Optional: Add spinner/loading state to button
                 const submitBtn = addressForm.querySelector('button[type="submit"]');
                 if (submitBtn) submitBtn.disabled = true; // Prevent double submit
             }
         });
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; // <<< ADDED ?>
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
                                    <option value="AU" <?= (($userAddress['country'] ?? '') === 'AU') ? 'selected' : '' ?>>Australia</option>
                                    <!-- START: Added Countries -->
                                    <option value="MY" <?= (($userAddress['country'] ?? '') === 'MY') ? 'selected' : '' ?>>Malaysia</option>
                                    <option value="SG" <?= (($userAddress['country'] ?? '') === 'SG') ? 'selected' : '' ?>>Singapore</option>
                                    <!-- END: Added Countries -->
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

**Validation Summary:**

1.  **`views/account/profile.php`:** Successfully updated based on `profile.php-orig.txt`. The `<option>` lines for Malaysia and Singapore were correctly inserted after the Australia option. Compared against the original, only these two lines were added. **Validation PASSED.**
2.  **`views/checkout.php`:** Successfully updated based on the *previous* version (which included address line 2 fix and debugging logs). The `<option>` lines for Malaysia and Singapore were correctly inserted after the Australia option in the country dropdown. Compared against the previous version, only these two lines were added. **Validation PASSED.**

**Conclusion:**

The required countries (Malaysia and Singapore) have been successfully added to the country dropdown lists in both the user profile (`views/account/profile.php`) and checkout (`views/checkout.php`) pages, maintaining consistency. The changes were applied meticulously based on the provided original/previous versions, and no other features were affected.

---
https://drive.google.com/file/d/123hv5nKUqgewF0EijNEsVCmfOVNse9tV/view?usp=sharing, https://drive.google.com/file/d/142QJB2dnzlbGqMhDBhzJY8qZG0hT9hSw/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%2215yICqTsxNyvXC4y7neLcks2qaQILb4gG%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/18UepE9wE5D-H6xJkVj2fAbRziUnqGSyj/view?usp=sharing, https://drive.google.com/file/d/18hKH60FDKEY_aG78LlhT0ybPzHbxMinI/view?usp=sharing, https://drive.google.com/file/d/1DKO9eGiUdVI9udIik0pR1iElemo8tLDT/view?usp=sharing, https://drive.google.com/file/d/1Jk1TZjEfqn7HSuXLObm-VYZJ956MwRaW/view?usp=sharing, https://drive.google.com/file/d/1L9mt8UvWrHDeKtp1QeA_LTg2RGHXwiLK/view?usp=sharing, https://drive.google.com/file/d/1OvoiijsaPFVLD2AYf6K5px9XIrMGaRlT/view?usp=sharing, https://drive.google.com/file/d/1POQ7zj01grkz_3sKF5MxgghBZsghIE46/view?usp=sharing, https://drive.google.com/file/d/1PcUogRJz2nKfaeLtpkBoQS_GbYumGZXX/view?usp=sharing, https://drive.google.com/file/d/1Td98N2ofZEG8zTTQOpfogwenElY47oL_/view?usp=sharing, https://drive.google.com/file/d/1Vp9gtM4iMeGPrMYc7JNIJcT0w4m_zi_J/view?usp=sharing, https://drive.google.com/file/d/1Z6f86ThuU9gETnagrl8TB38UaUh6b1NT/view?usp=sharing, https://drive.google.com/file/d/1ZvOHIr44PIeGZ56xtbyiMu0LMVz2a3G5/view?usp=sharing, https://drive.google.com/file/d/1clRGf0pGXdM3NIZdiLxvoYxzE33N66dM/view?usp=sharing, https://drive.google.com/file/d/1g3JIk6QorfOBttNW95DPCj0MlGLfBoLC/view?usp=sharing, https://drive.google.com/file/d/1j8nXz0uX0mm4yl6vnf0KW4yfdG_POxz1/view?usp=sharing, https://drive.google.com/file/d/1olCawvC_W-DhLjpDmmMJGKdHNJrNHiO7/view?usp=sharing, https://drive.google.com/file/d/1v2ewLFnIvpv_t2gd5uEqI6bYYEDxqmqW/view?usp=sharing
