I understand. It seems my previous response was cut short. I will now continue with the plan, starting by providing the complete and corrected version of `views/account/profile.php`, then re-verifying `js/main.js`, `index.php`, `TaxController.php`, and the other account view files for compatibility and the identified issues.

**1. Regenerate `views/account/profile.php`**

The following is the complete and corrected version of `views/account/profile.php`, based on the version provided in `content_of_code_files_5.md`, ensuring the JavaScript is complete and the "Quiz History" link is updated.

```php
<?php
// Updated: views/account/profile.php (v16.2 - Quiz History Link Updated, Added MY, SG countries)

require_once __DIR__ . '/../layout/header.php'; 
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
                                <a href="index.php?page=quiz&action=history"> 
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
                                             <option value="MY" <?= (($userAddress['country'] ?? '') === 'MY') ? 'selected' : '' ?>>Malaysia</option>
                                             <option value="SG" <?= (($userAddress['country'] ?? '') === 'SG') ? 'selected' : '' ?>>Singapore</option>
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
        if (!passwordForm || !newPassword || !confirmPassword) return; 

        let allMet = true;
        const passwordValue = newPassword.value;
        const confirmPasswordValue = confirmPassword.value;
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
        const submitButton = passwordForm.querySelector('button[type="submit"]');
        if (submitButton) {
             submitButton.disabled = !(allMet || !shouldValidate);
        }
        return allMet || !shouldValidate;
    }

    if (passwordForm && newPassword && confirmPassword) {
        newPassword.addEventListener('input', validatePasswordRequirements);
        confirmPassword.addEventListener('input', validatePasswordRequirements);
        validatePasswordRequirements();

        passwordForm.addEventListener('submit', function(e) {
            if (newPassword.value.length > 0) {
                 if (newPassword.value !== confirmPassword.value) {
                     e.preventDefault();
                     if(typeof window.showFlashMessage === 'function') {
                          window.showFlashMessage('New passwords do not match.', 'error');
                     } else { alert('New passwords do not match.'); }
                     confirmPassword.focus();
                     return;
                 }
                 if (!newPassword.checkValidity()) {
                     e.preventDefault();
                      if(typeof window.showFlashMessage === 'function') {
                           window.showFlashMessage('Please ensure the new password meets all requirements.', 'error');
                      } else { alert('Please ensure the new password meets all requirements.'); }
                     newPassword.focus();
                     return;
                 }
            }
        });
    }

    const addressForm = document.getElementById('addressForm');
    if (addressForm) {
         addressForm.addEventListener('submit', function(e) {
             const requiredAddressFields = ['address_line1', 'city', 'state', 'postal_code', 'country'];
             let addressIsValid = true;
             requiredAddressFields.forEach(id => {
                  const input = document.getElementById(id);
                  if (!input || !input.value.trim()) {
                       addressIsValid = false;
                       input?.classList.add('border-red-500'); 
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
                 const submitBtn = addressForm.querySelector('button[type="submit"]');
                 if (submitBtn) {
                     submitBtn.disabled = true;
                     submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Address...';
                 }
             }
         });
    }

    // Add submit handlers for profile and preferences forms to show loading state
    const profileForm = document.getElementById('profileForm');
    if(profileForm) {
        profileForm.addEventListener('submit', function() {
            const submitBtn = profileForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving Changes...';
            }
        });
    }
    const preferencesForm = document.getElementById('preferencesForm');
    if(preferencesForm) {
        preferencesForm.addEventListener('submit', function() {
            const submitBtn = preferencesForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating Preferences...';
            }
        });
    }

});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

```

**2. Re-verify `js/main.js`**

The version of `js/main.js` in `content_of_code_files_3.md` already has the corrected Stripe initialization logic within `initCheckoutPage`. The key changes were:
*   `stripe = Stripe(stripePublicKey);` is called early.
*   `elements = stripe.elements({ clientSecret: clientSecret, appearance });` and `paymentElement.mount('#payment-element');` are called *inside* the `submitButton.addEventListener('click', async function(e) { ... })` handler, specifically *after* the `clientSecret` has been successfully fetched from the `processCheckout` backend call.
*   Placeholder messages in `#payment-element` are updated through the flow.

This deferred initialization strategy matches the working `stripe_test_v4.php` and is correct. No further changes are needed to `js/main.js` regarding Stripe initialization based on the current information.

**3. Re-verify `index.php`**

The version of `index.php` in `content_of_code_files_1.md` has the following relevant routing for `page=quiz`:

```php
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processQuiz(); }
            // Missing 'results' action if using QuizController::showResults()
            // Missing 'history' action if Account sidebar points to QuizController for history
            else { $controller->showQuiz(); }
            break;
```

The `AccountController` in `content_of_code_files_4.md` *does not* have a `showQuizHistory` method anymore. This responsibility has been moved to `QuizController::showUserQuizHistory()`.
The sidebar links in account views have been updated to point to `index.php?page=quiz&action=history`.

Therefore, `index.php` needs to be updated to handle `page=quiz&action=history` and `page=quiz&action=results`.

**Updated `index.php` (relevant case):**

```php
<?php
// index.php (Updated - Admin Product Routing & Quiz History Routing)

define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php'; // Defines BASE_URL, etc.

// --- START: Added Composer Autoloader ---
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log("FATAL ERROR: Composer autoloader not found. Run 'composer install'.");
    echo "Internal Server Error: Application dependencies are missing. Please contact support.";
    exit(1);
}
// --- END: Added Composer Autoloader ---

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

ErrorHandler::init();
SecurityMiddleware::apply();

try {
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string') ?: 'home';
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? null, 'string') ?: null; // Use null default
    $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int'); // Use null default

    // --- Stripe Webhook Route (skip CSRF) ---
    if ($page === 'payment' && $action === 'webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/controllers/PaymentController.php';
        $controller = new PaymentController($pdo); // Instantiate PaymentController here too
        $controller->handleWebhook(); // Handles Stripe POST, returns JSON
        exit;
    }

    // --- CSRF validation for POST (skip for Stripe webhook) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ensure session active before CSRF generation/validation
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        SecurityMiddleware::generateCSRFToken(); // Ensure token exists if needed
        SecurityMiddleware::validateCSRF(); // Throws exception on failure
    }

    switch ($page) {
        // --- Public Routes (Unchanged) ---
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showHomePage();
            break;
        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            if ($id) {
                $controller->showProduct($id);
            } else {
                 http_response_code(404);
                 // Use renderView from a BaseController instance if available, or direct include
                 // For simplicity here, direct include:
                 $pageTitle = 'Page Not Found'; $bodyClass = 'page-404'; $csrfToken = SecurityMiddleware::generateCSRFToken();
                 extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
                 require_once __DIR__ . '/views/404.php';
            }
            break;
        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showProductList();
            break;
        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                 if ($action === 'add') { $controller->addToCart(); }
                 elseif ($action === 'update') { $controller->updateCart(); }
                 elseif ($action === 'remove') { $controller->removeFromCart(); }
                 elseif ($action === 'clear') { $controller->clearCart(); }
                 else { http_response_code(405); echo "Method not allowed."; }
            } elseif ($action === 'mini') { $controller->mini(); }
            else { $controller->showCart(); }
            break;
        case 'checkout':
            if (!isLoggedIn() && $action !== 'confirmation') {
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=checkout' . ($action ? '&action=' . $action : '');
                header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
            }
            require_once __DIR__ . '/controllers/PaymentController.php';
            require_once __DIR__ . '/controllers/CheckoutController.php';
            require_once __DIR__ . '/controllers/CartController.php';
            $paymentController = new PaymentController($pdo);
            $controller = new CheckoutController($pdo, $paymentController);
            if (empty($action)) {
                $cartCtrl = new CartController($pdo);
                if (empty($cartCtrl->getCartItems())) {
                    if(method_exists($controller, 'setFlashMessage')) {
                        $controller->setFlashMessage('Your cart is empty.', 'info');
                    } else { $_SESSION['flash_message'] = 'Your cart is empty.'; $_SESSION['flash_type'] = 'info';}
                    header('Location: ' . BASE_URL . 'index.php?page=products'); exit;
                }
            }
            if ($action === 'processCheckout' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processCheckout(); }
            elseif ($action === 'confirmation') { $controller->showOrderConfirmation(); }
            elseif ($action === 'calculateTax' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->calculateTax(); }
            elseif ($action === 'applyCouponAjax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 require_once __DIR__ . '/controllers/CouponController.php'; 
                 $couponController = new CouponController($pdo);
                 $couponController->applyCouponAjax(); 
            } else { $controller->showCheckout(); }
            break;
        case 'login':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo); $controller->login(); break;
        case 'register':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo); $controller->register(); break;
        case 'logout':
             if (function_exists('logout')) { logout(); }
             else { session_unset(); session_destroy(); }
             header('Location: ' . BASE_URL . 'index.php?page=login&loggedout=1'); exit;
        case 'account':
             if (!isLoggedIn()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=account' . ($action ? '&action=' . $action : '');
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string'); 
             switch ($section) { 
                 case 'profile':
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                          $controller->updateProfile(); 
                     } else {
                          $controller->showProfile(); 
                     }
                     break;
                 case 'orders':
                     if ($id) { $controller->showOrderDetails($id); }
                     else { $controller->showOrders(); } 
                     break;
                 case 'dashboard': 
                 default: $controller->showDashboard(); break;
             }
             break;
        case 'forgot_password':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo); $controller->requestPasswordReset(); break;
        case 'reset_password':
             if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo); $controller->resetPassword(); break;
        
        // --- Quiz Routes ---
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processQuiz(); }
            elseif ($action === 'results') { $controller->showResults(); } // Handles showing quiz results page
            elseif ($action === 'history' && isLoggedIn()) { // Handles showing user's quiz history
                $controller->showUserQuizHistory(); 
            } elseif ($action === 'history' && !isLoggedIn()) { // If not logged in, redirect to login
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=quiz&action=history';
                header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
            }
            else { $controller->showQuiz(); } // Default is to show the quiz form
            break;

        case 'newsletter':
             require_once __DIR__ . '/controllers/NewsletterController.php';
             $controller = new NewsletterController($pdo);
             if ($action === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->subscribe(); }
             elseif ($action === 'unsubscribe') { $controller->unsubscribe(); }
             else { http_response_code(404); require_once __DIR__ . '/views/404.php'; }
             break;

        case 'admin':
             if (!isAdmin()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=admin';
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
             $task = SecurityMiddleware::validateInput($_GET['task'] ?? 'list', 'string'); 
             $adminId = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int'); 

             switch ($section) {
                case 'products': 
                    require_once __DIR__ . '/controllers/ProductController.php';
                    $controller = new ProductController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        if ($task === 'save') {
                             $controller->saveAdminProduct(); 
                        } elseif ($task === 'delete') {
                             $controller->deleteAdminProduct($adminId); 
                        } else {
                             $controller->listAdminProducts();
                        }
                    } else {
                        if ($task === 'create') {
                             $controller->showAdminProductForm(); 
                        } elseif ($task === 'edit' && $adminId) {
                             $controller->showAdminProductForm($adminId); 
                        } else { 
                             $controller->listAdminProducts(); 
                        }
                    }
                    break; 

                 case 'quiz_analytics':
                     require_once __DIR__ . '/controllers/QuizController.php';
                     $controller = new QuizController($pdo); $controller->showAnalytics(); break;
                 case 'coupons':
                    require_once __DIR__ . '/controllers/CouponController.php';
                    $controller = new CouponController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         if ($task === 'save') { $controller->saveCoupon(); }
                         elseif ($task === 'toggle_status' && $adminId) { $controller->toggleCouponStatus($adminId); }
                         elseif ($task === 'delete' && $adminId) { $controller->deleteCoupon($adminId); }
                         else { $controller->listCoupons(); }
                    } else { 
                         if ($task === 'edit' && $adminId) { $controller->showEditForm($adminId); }
                         elseif ($task === 'create') { $controller->showCreateForm(); }
                         else { $controller->listCoupons(); }
                    }
                    break;
                 case 'dashboard':
                 default: 
                      $pageTitle = "Admin Dashboard"; $bodyClass = "page-admin-dashboard";
                      $csrfToken = SecurityMiddleware::generateCSRFToken();
                      extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
                      require_once __DIR__ . '/views/admin/dashboard.php'; break;
             }
             break;

        case 'contact':
            $pageTitle = 'Contact Us'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-contact';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/contact.php'; break;
        case 'faq':
            $pageTitle = 'FAQs'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-faq';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/faq.php'; break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-shipping';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/shipping.php'; break;
        case 'order-tracking':
            $pageTitle = 'Track Your Order'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-order-tracking';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/order-tracking.php'; break;
        case 'privacy':
            $pageTitle = 'Privacy Policy'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-privacy';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/privacy.php'; break;
        case 'about':
             $pageTitle = 'About Us - The Scent'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-about';
             extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
             require_once __DIR__ . '/views/about.php'; break;
        case 'error':
            $pageTitle = 'Error'; $bodyClass = 'page-error'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            http_response_code(500);
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/error.php'; break;

        default: 
            http_response_code(404);
            $pageTitle = 'Page Not Found'; $bodyClass = 'page-404'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/404.php'; break;
    }
} catch (PDOException $e) { ErrorHandler::handleException($e); exit(1);
} catch (\Stripe\Exception\ApiErrorException $e) { error_log("Stripe API error in routing/controller: " . $e->getMessage()); ErrorHandler::handleException($e); exit(1);
} catch (Throwable $e) { error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); ErrorHandler::handleException($e); exit(1);
}

```

**4. Re-verify `TaxController.php`**

The version in `content_of_code_files_3.md` was already corrected in my previous (internal) thought process. The `$this->db` property is correctly used. No further changes are needed here. I will output the corrected version again for completeness.

```php
<?php
// controllers/TaxController.php (Updated to use $this->db instead of $this->pdo)

require_once __DIR__ . '/BaseController.php';

class TaxController extends BaseController {
    private $cache = [];
    // $db property is inherited from BaseController
    
    public function calculateTax($subtotal, $country, $state = null) {
        try {
            $subtotal = $this->validateInput($subtotal, 'float');
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if ($subtotal === false || !$country) { // Ensure subtotal is not false from validation
                throw new Exception('Invalid tax calculation parameters');
            }
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return round($subtotal * $this->cache[$cacheKey], 2);
            }
            
            // Get tax rate from database
            $stmt = $this->db->prepare(" 
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? (float)$result['rate'] : 0; // Cast rate to float
            $this->cache[$cacheKey] = $rate;
            
            return round($subtotal * $rate, 2);
            
        } catch (Exception $e) {
            error_log("Tax calculation error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTaxRate($country, $state = null) {
        try {
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if (!$country) return 0;
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $stmt = $this->db->prepare(" 
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? (float)$result['rate'] : 0; // Cast rate to float
            $this->cache[$cacheKey] = $rate;
            
            return $rate;
            
        } catch (Exception $e) {
            error_log("Tax rate lookup error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function formatTaxRate($rate) {
        return number_format((float)$rate * 100, 2) . '%'; // Cast rate to float
    }
    
    public function getAllTaxRates() {
        try {
            $this->requireAdmin();
            
            $stmt = $this->db->query(" 
                SELECT 
                    tr.*,
                    COUNT(th.id) as change_count,
                    MAX(th.created_at) as last_modified
                FROM tax_rates tr
                LEFT JOIN tax_rate_history th ON tr.id = th.tax_rate_id
                GROUP BY tr.id
                ORDER BY tr.country_code, tr.state_code
            ");
            
            return $this->jsonResponse([
                'success' => true,
                'rates' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rates: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rates'
            ], 500);
        }
    }
    
    public function updateTaxRate() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $data = [
                'country_code' => $this->validateInput($_POST['country_code'], 'string'),
                'state_code' => $this->validateInput($_POST['state_code'] ?? null, 'string'),
                'rate' => $this->validateInput($_POST['rate'], 'float'),
                'start_date' => $this->validateInput($_POST['start_date'] ?? date('Y-m-d'), 'string'), // Validate as date string
                'end_date' => $this->validateInput($_POST['end_date'] ?? null, 'string'), // Validate as date string
                'is_active' => isset($_POST['is_active']) ? 1 : 0 // Convert to int for DB
            ];
            
            if (!$data['country_code'] || $data['rate'] === false || $data['rate'] < 0) { // Check rate validation
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid tax rate data'
                ], 400);
            }
            
            $this->beginTransaction(); // Uses $this->db from BaseController
            
            // Get existing rate if any
            $stmt = $this->db->prepare(" 
                SELECT id, rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR (state_code IS NULL AND ? IS NULL))
            ");
            $stmt->execute([
                $data['country_code'],
                $data['state_code'],
                $data['state_code']
            ]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing rate
                $stmt = $this->db->prepare(" 
                    UPDATE tax_rates 
                    SET rate = ?,
                        start_date = ?,
                        end_date = ?,
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $existing['id']
                ]);
                
                // Log the change
                if ($existing['rate'] != $data['rate']) {
                    $this->logRateChange(
                        $existing['id'],
                        $existing['rate'],
                        $data['rate']
                    );
                }
            } else {
                // Insert new rate
                $stmt = $this->db->prepare(" 
                    INSERT INTO tax_rates (
                        country_code,
                        state_code,
                        rate,
                        start_date,
                        end_date,
                        is_active,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['country_code'],
                    $data['state_code'],
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $this->getUserId()
                ]);
                
                $rateId = $this->db->lastInsertId(); 
                $this->logRateChange($rateId, 0, $data['rate']);
            }
            
            // Clear cache for this region
            $cacheKey = "{$data['country_code']}_{$data['state_code']}";
            unset($this->cache[$cacheKey]);
            
            $this->commit(); // Uses $this->db from BaseController
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Tax rate updated successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback(); // Uses $this->db from BaseController
            error_log("Tax rate update error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update tax rate'
            ], 500);
        }
    }
    
    private function logRateChange($rateId, $oldRate, $newRate) {
        $stmt = $this->db->prepare(" 
            INSERT INTO tax_rate_history (
                tax_rate_id,
                old_rate,
                new_rate,
                changed_by
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $rateId,
            $oldRate,
            $newRate,
            $this->getUserId()
        ]);
    }
    
    public function getTaxRateHistory($rateId) {
        try {
            $this->requireAdmin();
            
            $rateId = $this->validateInput($rateId, 'int');
            if (!$rateId) {
                throw new Exception('Invalid tax rate ID');
            }
            
            $stmt = $this->db->prepare(" 
                SELECT 
                    th.*,
                    u.name as changed_by_name
                FROM tax_rate_history th
                LEFT JOIN users u ON th.changed_by = u.id
                WHERE th.tax_rate_id = ?
                ORDER BY th.created_at DESC
            ");
            $stmt->execute([$rateId]);
            
            return $this->jsonResponse([
                'success' => true,
                'history' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rate history: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rate history'
            ], 500);
        }
    }
}
```

**5. Re-verify other Account View Files**

The files `views/account/dashboard.php`, `views/account/order_details.php`, and `views/account/orders.php` (from `content_of_code_files_6.md` for `dashboard.php` and `order_details.php`, and `content_of_code_files_5.md` for `orders.php`) have already been updated with the correct "Quiz History" link (`index.php?page=quiz&action=history`) in the previous responses/internal processing. They are presented above.

**Summary of Actions and Verification:**

*   **`views/account/profile.php`:** Regenerated completely and correctly, ensuring the JS block is complete and the "Quiz History" link is updated to `index.php?page=quiz&action=history`.
*   **`js/main.js` (`initCheckoutPage`):** Verified. The Stripe Elements initialization is correctly deferred until after the `clientSecret` is obtained. Debug logs are in place.
*   **`index.php`:** Updated to correctly route `page=quiz&action=history` to `QuizController::showUserQuizHistory()` and `page=quiz&action=results` to `QuizController::showResults()`.
*   **`TaxController.php`:** Verified. All instances of `$this->pdo` are correctly changed to `$this->db`.
*   **`views/account/dashboard.php`:** Verified. "Quiz History" link points to `index.php?page=quiz&action=history`.
*   **`views/account/order_details.php`:** Verified. "Quiz History" link points to `index.php?page=quiz&action=history`.
*   **`views/account/orders.php`:** Verified. "Quiz History" link points to `index.php?page=quiz&action=history`.

The core issue of Stripe initialization on the checkout page should now be resolved by the deferred Elements creation in `js/main.js`. The `TaxController` errors are also resolved. The account page sidebar navigation for Quiz History is now consistent with the `QuizController` handling this functionality.

---
Okay, I've thoroughly reviewed the provided files, focusing on the Stripe initialization issue on the checkout page, compatibility with the refactored `AccountController`, and the `TaxController` error.

**Primary Issue: Stripe Initialization Failure on Checkout Page**

The error "Could not initialize payment system. Please refresh" on `/index.php?page=checkout` is indeed due to the Stripe Elements (`stripe.elements()`) and Payment Element (`elements.create('payment')`) being initialized *before* the `clientSecret` is fetched from the backend. The `stripe_test_v4.php` script correctly defers this initialization until after the `clientSecret` is available.

**Plan Execution & Fixes:**

1.  **`js/main.js` (`initCheckoutPage` function):**
    *   Modified to initialize only the core `stripe = Stripe(stripePublicKey);` object on page load.
    *   The creation of `elements` and mounting of the `paymentElement` has been moved into the `submitButton`'s click handler. This ensures these steps occur *after* the `clientSecret` is successfully retrieved from the `processCheckout` backend call.
    *   The placeholder message in `#payment-element` is updated accordingly.

2.  **Account Page Sidebar Links (Quiz History):**
    *   The "Quiz History" link in the account sidebar navigation has been updated across relevant files (`views/account/dashboard.php`, `views/account/order_details.php`, `views/account/orders.php`, `views/account/profile.php`) to point to `index.php?page=quiz&action=history` instead of `index.php?page=account&section=quiz`.

3.  **`index.php` (Routing for Quiz History):**
    *   The routing for `page=quiz` has been updated to include an `action=history` case, which directs to `QuizController::showUserQuizHistory()`. This aligns with the updated sidebar links.

4.  **`TaxController.php` (PDO Property Fix):**
    *   All instances of `$this->pdo` within `TaxController.php` have been replaced with `$this->db`. This resolves the "Undefined property: TaxController::$pdo" error and subsequent "Call to a member function prepare() on null" errors, as `TaxController` inherits the `$db` (PDO instance) property from `BaseController`.

**Compatibility Review Summary:**

*   **`AccountController.php` and `index.php` / `js/main.js`:**
    *   The routing in `index.php` for `/account/*` pages is compatible with the methods in `AccountController.php`.
    *   AJAX interactions for login and registration in `js/main.js` are compatible with `AccountController.php`'s expectations (JSON request/response).
    *   Standard POST submissions for profile updates are correctly handled.
    *   The Quiz History link change (detailed above) ensures proper navigation.

*   **Other Files:**
    *   **`views/layout/header.php`:** Correctly outputs `data-stripe-public-key` and the global CSRF token. Cart count logic is sound.
    *   **`config.php`:** `STRIPE_PUBLIC_KEY` appears to be correctly set (it's a test key, but validly formatted). The CSP is comprehensive.
    *   No other direct incompatibilities were found with the primary files in question (`AccountController`, `index.php`, `js/main.js`) in the remaining PHP files. The primary focus was on the checkout flow and `TaxController` errors.

Below are the updated versions of the modified files.

```php
<?php
// index.php (Updated - Admin Product Routing & Quiz History Routing)

define('ROOT_PATH', __DIR__);
require_once __DIR__ . '/config.php'; // Defines BASE_URL, etc.

// --- START: Added Composer Autoloader ---
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    error_log("FATAL ERROR: Composer autoloader not found. Run 'composer install'.");
    echo "Internal Server Error: Application dependencies are missing. Please contact support.";
    exit(1);
}
// --- END: Added Composer Autoloader ---

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/SecurityMiddleware.php';
require_once __DIR__ . '/includes/ErrorHandler.php';

ErrorHandler::init();
SecurityMiddleware::apply();

try {
    $page = SecurityMiddleware::validateInput($_GET['page'] ?? 'home', 'string') ?: 'home';
    $action = SecurityMiddleware::validateInput($_GET['action'] ?? null, 'string') ?: null; // Use null default
    $id = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int'); // Use null default

    // --- Stripe Webhook Route (skip CSRF) ---
    if ($page === 'payment' && $action === 'webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_once __DIR__ . '/controllers/PaymentController.php';
        $controller = new PaymentController($pdo); // Instantiate PaymentController here too
        $controller->handleWebhook(); // Handles Stripe POST, returns JSON
        exit;
    }

    // --- CSRF validation for POST (skip for Stripe webhook) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Ensure session active before CSRF generation/validation
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        SecurityMiddleware::generateCSRFToken(); // Ensure token exists if needed
        SecurityMiddleware::validateCSRF(); // Throws exception on failure
    }

    switch ($page) {
        // --- Public Routes (Unchanged) ---
        case 'home':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showHomePage();
            break;
        case 'product':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            if ($id) {
                $controller->showProduct($id);
            } else {
                 http_response_code(404);
                 require_once __DIR__ . '/views/404.php'; // Consider using renderView
            }
            break;
        case 'products':
            require_once __DIR__ . '/controllers/ProductController.php';
            $controller = new ProductController($pdo);
            $controller->showProductList();
            break;
        case 'cart':
            require_once __DIR__ . '/controllers/CartController.php';
            $controller = new CartController($pdo);
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                 if ($action === 'add') { $controller->addToCart(); }
                 elseif ($action === 'update') { $controller->updateCart(); }
                 elseif ($action === 'remove') { $controller->removeFromCart(); }
                 elseif ($action === 'clear') { $controller->clearCart(); }
                 else { http_response_code(405); echo "Method not allowed."; }
            } elseif ($action === 'mini') { $controller->mini(); }
            else { $controller->showCart(); }
            break;
        case 'checkout':
            if (!isLoggedIn() && $action !== 'confirmation') {
                $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=checkout' . ($action ? '&action=' . $action : '');
                header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
            }
            require_once __DIR__ . '/controllers/PaymentController.php';
            require_once __DIR__ . '/controllers/CheckoutController.php';
            require_once __DIR__ . '/controllers/CartController.php';
            $paymentController = new PaymentController($pdo);
            $controller = new CheckoutController($pdo, $paymentController);
            if (empty($action)) {
                $cartCtrl = new CartController($pdo);
                if (empty($cartCtrl->getCartItems())) {
                    // Use BaseController flash message if possible, assumes controller has it
                    if(method_exists($controller, 'setFlashMessage')) {
                        $controller->setFlashMessage('Your cart is empty.', 'info');
                    } else { $_SESSION['flash_message'] = 'Your cart is empty.'; $_SESSION['flash_type'] = 'info';}
                    header('Location: ' . BASE_URL . 'index.php?page=products'); exit;
                }
            }
            if ($action === 'processCheckout' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processCheckout(); }
            elseif ($action === 'confirmation') { $controller->showOrderConfirmation(); }
            elseif ($action === 'calculateTax' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->calculateTax(); }
            elseif ($action === 'applyCouponAjax' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                 require_once __DIR__ . '/controllers/CouponController.php'; // Need CouponController here
                 $couponController = new CouponController($pdo);
                 $couponController->applyCouponAjax(); // Delegate to CouponController
            } else { $controller->showCheckout(); }
            break;
        // --- Auth Routes (Unchanged) ---
        case 'login':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo); $controller->login(); break;
        case 'register':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
            require_once __DIR__ . '/controllers/AccountController.php';
            $controller = new AccountController($pdo); $controller->register(); break;
        case 'logout':
             if (function_exists('logout')) { logout(); }
             else { session_unset(); session_destroy(); }
             header('Location: ' . BASE_URL . 'index.php?page=login&loggedout=1'); exit;
        case 'account':
             if (!isLoggedIn()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=account' . ($action ? '&action=' . $action : '');
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo);
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string'); // Define section for account
             switch ($section) { // Use section instead of action here
                 case 'profile':
                     // Decide based on request method if it's view or update
                     if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                          $controller->updateProfile(); // POST handles update logic
                     } else {
                          $controller->showProfile(); // GET shows the profile page
                     }
                     break;
                 case 'orders':
                     // Check if an order ID is provided for details view
                     if ($id) { $controller->showOrderDetails($id); }
                     else { $controller->showOrders(); } // Show list otherwise
                     break;
                 // NOTE: 'quiz' section for account page is removed.
                 // Quiz history is now handled by QuizController via page=quiz&action=history
                 case 'dashboard': // Explicit dashboard case
                 default: $controller->showDashboard(); break;
             }
             break;
        case 'forgot_password':
            if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo); $controller->requestPasswordReset(); break;
        case 'reset_password':
             if (isLoggedIn()) { header('Location: ' . BASE_URL . 'index.php?page=account'); exit; }
             require_once __DIR__ . '/controllers/AccountController.php';
             $controller = new AccountController($pdo); $controller->resetPassword(); break;
        
        // --- Quiz Routes ---
        case 'quiz':
            require_once __DIR__ . '/controllers/QuizController.php';
            $controller = new QuizController($pdo);
            if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->processQuiz(); }
            elseif ($action === 'results') { $controller->showResults(); }
            elseif ($action === 'history') { // <<< ADDED: Route for user quiz history
                $controller->showUserQuizHistory(); 
            }
            else { $controller->showQuiz(); }
            break;

        case 'newsletter':
             require_once __DIR__ . '/controllers/NewsletterController.php';
             $controller = new NewsletterController($pdo);
             if ($action === 'subscribe' && $_SERVER['REQUEST_METHOD'] === 'POST') { $controller->subscribe(); }
             elseif ($action === 'unsubscribe') { $controller->unsubscribe(); }
             else { http_response_code(404); require_once __DIR__ . '/views/404.php'; }
             break;

        // --- START: Updated Admin Routing ---
        case 'admin':
             if (!isAdmin()) {
                 $_SESSION['redirect_after_login'] = BASE_URL . 'index.php?page=admin';
                 header('Location: ' . BASE_URL . 'index.php?page=login'); exit;
             }
             $section = SecurityMiddleware::validateInput($_GET['section'] ?? 'dashboard', 'string');
             $task = SecurityMiddleware::validateInput($_GET['task'] ?? 'list', 'string'); // Default task to 'list'
             $adminId = SecurityMiddleware::validateInput($_GET['id'] ?? null, 'int'); // Use a different var name for admin ID

             switch ($section) {
                case 'products': // NEW Product Section
                    require_once __DIR__ . '/controllers/ProductController.php';
                    $controller = new ProductController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // POST requests handle saving or deleting
                        if ($task === 'save') {
                             $controller->saveAdminProduct(); // Handles both create/update POST
                        } elseif ($task === 'delete') {
                             $controller->deleteAdminProduct($adminId); // Assumes ID might be passed via GET or POST
                        } else {
                             // Unknown POST task, redirect to list
                             $controller->listAdminProducts();
                        }
                    } else {
                        // GET requests handle displaying lists or forms
                        if ($task === 'create') {
                             $controller->showAdminProductForm(); // Show empty form
                        } elseif ($task === 'edit' && $adminId) {
                             $controller->showAdminProductForm($adminId); // Show form with product data
                        } else { // Default task or 'list'
                             $controller->listAdminProducts(); // Show product list
                        }
                    }
                    break; // End Product Section

                 case 'quiz_analytics':
                     require_once __DIR__ . '/controllers/QuizController.php';
                     $controller = new QuizController($pdo); $controller->showAnalytics(); break;
                 case 'coupons':
                    require_once __DIR__ . '/controllers/CouponController.php';
                    $controller = new CouponController($pdo);
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                         // Use adminId for consistency
                         if ($task === 'save') { $controller->saveCoupon(); }
                         elseif ($task === 'toggle_status' && $adminId) { $controller->toggleCouponStatus($adminId); }
                         elseif ($task === 'delete' && $adminId) { $controller->deleteCoupon($adminId); }
                         else { $controller->listCoupons(); }
                    } else { // GET
                         if ($task === 'edit' && $adminId) { $controller->showEditForm($adminId); }
                         elseif ($task === 'create') { $controller->showCreateForm(); }
                         else { $controller->listCoupons(); }
                    }
                    break;
                 // Add other admin sections here...
                 case 'dashboard':
                 default: // Admin Dashboard
                      $pageTitle = "Admin Dashboard"; $bodyClass = "page-admin-dashboard";
                      $csrfToken = SecurityMiddleware::generateCSRFToken();
                      extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
                      require_once __DIR__ . '/views/admin/dashboard.php'; break;
             }
             break;
        // --- END: Updated Admin Routing ---

        // --- Static Pages (Unchanged) ---
        case 'contact':
            $pageTitle = 'Contact Us'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-contact';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/contact.php'; break;
        case 'faq':
            $pageTitle = 'FAQs'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-faq';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/faq.php'; break;
        case 'shipping':
            $pageTitle = 'Shipping & Returns'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-shipping';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/shipping.php'; break;
        case 'order-tracking':
            $pageTitle = 'Track Your Order'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-order-tracking';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/order-tracking.php'; break;
        case 'privacy':
            $pageTitle = 'Privacy Policy'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-privacy';
            extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
            require_once __DIR__ . '/views/privacy.php'; break;
        case 'about':
             $pageTitle = 'About Us - The Scent'; $csrfToken = SecurityMiddleware::generateCSRFToken(); $bodyClass = 'page-about';
             extract(['pageTitle' => $pageTitle, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass]);
             require_once __DIR__ . '/views/about.php'; break;
        case 'error':
            $pageTitle = 'Error'; $bodyClass = 'page-error'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            http_response_code(500);
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/error.php'; break;

        default: // 404 Not Found
            http_response_code(404);
            $pageTitle = 'Page Not Found'; $bodyClass = 'page-404'; $csrfToken = SecurityMiddleware::generateCSRFToken();
            extract(['pageTitle' => $pageTitle, 'bodyClass' => $bodyClass, 'csrfToken' => $csrfToken]);
            require_once __DIR__ . '/views/404.php'; break;
    }
// --- Exception Handling (Unchanged) ---
} catch (PDOException $e) { ErrorHandler::handleException($e); exit(1);
} catch (\Stripe\Exception\ApiErrorException $e) { error_log("Stripe API error in routing/controller: " . $e->getMessage()); ErrorHandler::handleException($e); exit(1);
} catch (Throwable $e) { error_log("General error/exception in index.php: " . $e->getMessage() . " Trace: " . $e->getTraceAsString()); ErrorHandler::handleException($e); exit(1);
}

```

```javascript
// --- START OF FILE main.js ---

// Mobile menu toggle
window.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.querySelector('.mobile-menu-toggle');
    var navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', function() {
            // Toggle navigation visibility
            navLinks.classList.toggle('active');
            // Toggle body class to prevent scrolling when menu is open
            document.body.classList.toggle('menu-open');
            // Toggle icon class (optional, if you want fa-times)
             const icon = menuToggle.querySelector('i');
             if (icon) {
                 icon.classList.toggle('fa-bars');
                 icon.classList.toggle('fa-times');
             }
        });
    }
    // Close menu if clicking outside of it on mobile
    document.addEventListener('click', function(e) {
        if (navLinks && navLinks.classList.contains('active') && menuToggle && !menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
             navLinks.classList.remove('active');
             document.body.classList.remove('menu-open');
             const icon = menuToggle.querySelector('i');
             if (icon) {
                 icon.classList.remove('fa-times');
                 icon.classList.add('fa-bars');
             }
        }
    });
});

// showFlashMessage utility
window.showFlashMessage = function(message, type = 'info') {
    let flashContainer = document.querySelector('.flash-message-container');
    // Create container if it doesn't exist
    if (!flashContainer) {
        flashContainer = document.createElement('div');
        // Apply Tailwind classes for positioning and styling the container
        flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full space-y-2';
        document.body.appendChild(flashContainer);
    }

    const flashDiv = document.createElement('div');
    // Define color mapping using Tailwind classes
    const colorMap = {
        success: 'bg-green-100 border-green-400 text-green-700',
        error: 'bg-red-100 border-red-400 text-red-700',
        info: 'bg-blue-100 border-blue-400 text-blue-700',
        warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
    };
    // Apply Tailwind classes for the message appearance
    flashDiv.className = `flash-message border px-4 py-3 rounded relative shadow-md flex justify-between items-center transition-opacity duration-300 ease-out opacity-0 ${colorMap[type] || colorMap['info']}`;
    flashDiv.setAttribute('role', 'alert');

    const messageSpan = document.createElement('span');
    messageSpan.className = 'block sm:inline';
    messageSpan.textContent = message;
    flashDiv.appendChild(messageSpan);

    const closeButton = document.createElement('button'); // Use button for accessibility
    closeButton.className = 'ml-4 text-xl leading-none font-semibold hover:text-black';
    closeButton.innerHTML = '&times;';
    closeButton.setAttribute('aria-label', 'Close message');
    closeButton.onclick = () => {
        flashDiv.style.opacity = '0';
        // Remove after transition
        setTimeout(() => flashDiv.remove(), 300);
    };
    flashDiv.appendChild(closeButton);

    // Add to container and fade in
    flashContainer.appendChild(flashDiv);
    // Force reflow before adding opacity class for transition
    void flashDiv.offsetWidth;
    flashDiv.style.opacity = '1';


    // Auto-dismiss timer
    setTimeout(() => {
        if (flashDiv && flashDiv.parentNode) { // Check if it wasn't already closed
             flashDiv.style.opacity = '0';
             setTimeout(() => flashDiv.remove(), 300); // Remove after fade out
        }
    }, 5000); // Keep message for 5 seconds
};


// Global AJAX handlers (Add-to-Cart, Newsletter, etc.)
window.addEventListener('DOMContentLoaded', function() {
    // Add-to-Cart handler (using event delegation on the body)
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.add-to-cart');
        // Specific exclusion for related products button to prevent double handling if form also submits
        // We now rely solely on the global handler for *all* add-to-cart buttons.
        // const btnRelated = e.target.closest('.add-to-cart-related');

        if (!btn) return; // Exit if the clicked element is not an add-to-cart button or its child

        e.preventDefault(); // Prevent default behavior (like form submission if button is type=submit)
        if (btn.disabled) return; // Prevent multiple clicks while processing

        const productId = btn.dataset.productId;
        const csrfTokenInput = document.getElementById('csrf-token-value');
        const csrfToken = csrfTokenInput?.value;

        // Check if this button is inside the main product detail form to get quantity
        const productForm = btn.closest('#product-detail-add-cart-form');
        let quantity = 1; // Default quantity
        if (productForm) {
            const quantityInput = productForm.querySelector('input[name="quantity"]');
            if (quantityInput) {
                 quantity = parseInt(quantityInput.value) || 1;
            }
        }


        if (!productId || !csrfToken) {
            showFlashMessage('Cannot add to cart. Missing product or security token. Please refresh.', 'error');
            console.error('Add to Cart Error: Missing productId or CSRF token input.');
            return;
        }

        btn.disabled = true;
        const originalText = btn.textContent;
        // Check if the button already contains an icon or just text
        const hasIcon = btn.querySelector('i');
        const loadingHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
        const originalHTML = btn.innerHTML; // Store original HTML if it contains icons

        btn.innerHTML = loadingHTML; // Adding state with spinner

        fetch('index.php?page=cart&action=add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            // Ensure quantity is sent based on whether it's from the main form or a simple button
            body: `product_id=${encodeURIComponent(productId)}&quantity=${encodeURIComponent(quantity)}&csrf_token=${encodeURIComponent(csrfToken)}`
        })
        .then(response => {
            const contentType = response.headers.get("content-type");
            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                return response.json();
            }
            return response.text().then(text => {
                 console.error('Add to Cart - Non-JSON response:', response.status, text);
                 throw new Error(`Server returned status ${response.status}. Check server logs or network response.`);
            });
        })
        .then(data => {
            if (data.success) {
                showFlashMessage(data.message || 'Product added to cart!', 'success');
                const cartCountSpan = document.querySelector('.cart-count');
                if (cartCountSpan) {
                    cartCountSpan.textContent = data.cart_count || 0;
                    cartCountSpan.style.display = (data.cart_count || 0) > 0 ? 'flex' : 'none';
                }
                 // Optionally change button text briefly or add a checkmark icon
                 btn.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                 setTimeout(() => {
                     // Restore original HTML or text
                     btn.innerHTML = originalHTML;
                     // Re-enable button unless out of stock now
                     if (data.stock_status !== 'out_of_stock') {
                        btn.disabled = false;
                     } else {
                         // Keep disabled and update text if out of stock now
                         btn.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Out of Stock';
                         btn.classList.add('btn-disabled'); // Add a class if needed
                     }
                 }, 1500); // Reset after 1.5 seconds

                 // Update mini cart if applicable
                 if (typeof fetchMiniCart === 'function') {
                     fetchMiniCart();
                 }
            } else {
                showFlashMessage(data.message || 'Could not add product to cart.', 'error');
                btn.innerHTML = originalHTML; // Reset button immediately on failure
                btn.disabled = false;
            }
        })
        .catch((error) => {
            console.error('Add to Cart Fetch Error:', error);
            showFlashMessage(error.message || 'Error adding to cart. Please try again.', 'error');
            btn.innerHTML = originalHTML; // Reset button
            btn.disabled = false;
        });
    });

    // Newsletter AJAX handler (if present)
    var newsletterForm = document.getElementById('newsletter-form'); // Main newsletter form
    var newsletterFormFooter = document.getElementById('newsletter-form-footer'); // Footer newsletter form

    function handleNewsletterSubmit(formElement) {
        formElement.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = formElement.querySelector('input[name="email"]');
            const submitButton = formElement.querySelector('button[type="submit"]');
            const csrfTokenInput = formElement.querySelector('input[name="csrf_token"]'); // Get token from specific form

            if (!emailInput || !submitButton || !csrfTokenInput) {
                 console.error("Newsletter form elements missing.");
                 showFlashMessage('An error occurred. Please try again.', 'error');
                 return;
            }

            const email = emailInput.value.trim();
            const csrfToken = csrfTokenInput.value;

            if (!email || !/\S+@\S+\.\S+/.test(email)) {
                showFlashMessage('Please enter a valid email address.', 'error');
                return;
            }
            if (!csrfToken) {
                 showFlashMessage('Security token missing. Please refresh the page.', 'error');
                 return;
            }

            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subscribing...';

            fetch('index.php?page=newsletter&action=subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `email=${encodeURIComponent(email)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(res => {
                 const contentType = res.headers.get("content-type");
                 if (res.ok && contentType && contentType.indexOf("application/json") !== -1) {
                     return res.json();
                 }
                 return res.text().then(text => {
                     console.error('Newsletter - Non-JSON response:', res.status, text);
                     throw new Error(`Server returned status ${res.status}.`);
                 });
            })
            .then(data => {
                showFlashMessage(data.message || (data.success ? 'Subscription successful!' : 'Subscription failed.'), data.success ? 'success' : 'error');
                if (data.success) {
                    formElement.reset();
                }
            })
            .catch((error) => {
                console.error('Newsletter Fetch Error:', error);
                showFlashMessage(error.message || 'Error subscribing. Please try again later.', 'error');
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.textContent = originalButtonText;
            });
        });
    }

    if (newsletterForm) {
        handleNewsletterSubmit(newsletterForm);
    }
    if (newsletterFormFooter) {
        handleNewsletterSubmit(newsletterFormFooter);
    }
});


// --- Page Specific Initializers ---

function initHomePage() {
    // console.log("Initializing Home Page");
    // Particles.js initialization for hero section (if using)
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json', function() {
            // console.log('particles.js loaded - callback');
        });
    }
}

function initProductsPage() {
    // console.log("Initializing Products Page");
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('sort', this.value);
            url.searchParams.delete('page_num');
            window.location.href = url.toString();
        });
    }

    const applyPriceFilter = document.querySelector('.apply-price-filter');
    const minPriceInput = document.getElementById('minPrice');
    const maxPriceInput = document.getElementById('maxPrice');

    if (applyPriceFilter && minPriceInput && maxPriceInput) {
        applyPriceFilter.addEventListener('click', function() {
            const minPrice = minPriceInput.value.trim();
            const maxPrice = maxPriceInput.value.trim();
            const url = new URL(window.location.href);

            if (minPrice) url.searchParams.set('min_price', minPrice);
            else url.searchParams.delete('min_price');

            if (maxPrice) url.searchParams.set('max_price', maxPrice);
            else url.searchParams.delete('max_price');

            url.searchParams.delete('page_num');
            window.location.href = url.toString();
        });
    }
}

function initProductDetailPage() {
    // console.log("Initializing Product Detail Page");
    const mainImage = document.getElementById('mainImage');
    const thumbnails = document.querySelectorAll('.thumbnail-grid img');

    // Make updateMainImage function available globally for inline onclick
    // Note: Using event delegation below is generally preferred over inline onclick
    window.updateMainImage = function(thumbnailElement) {
        if (mainImage && thumbnailElement) {
            mainImage.src = thumbnailElement.dataset.largeImage || thumbnailElement.src;
            mainImage.alt = thumbnailElement.alt.replace('Thumbnail', 'Main view');

            thumbnails.forEach(img => img.parentElement.classList.remove('border-primary', 'border-2')); // Remove active style from parent div
            thumbnailElement.parentElement.classList.add('border-primary', 'border-2'); // Add active style to parent div
        }
    }

    // Set initial active thumbnail based on class (more reliable if structure changes)
    const activeThumbnailDiv = document.querySelector('.thumbnail-grid .border-primary');
    if (activeThumbnailDiv && mainImage && !mainImage.src.includes('placeholder.jpg')) { // Ensure first image isn't placeholder before potentially resetting
        const activeThumbImg = activeThumbnailDiv.querySelector('img');
        // Optional: Set main image source based on initially active thumb if needed
        // if (activeThumbImg) updateMainImage(activeThumbImg);
    } else if (thumbnails.length > 0) {
        // If no thumb is marked active, activate the first one
        thumbnails[0].parentElement.classList.add('border-primary', 'border-2');
    }


    // Quantity Selector Logic
    const quantityInput = document.querySelector('.quantity-selector input[name="quantity"]');
    if (quantityInput) {
        const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
        const quantityMin = parseInt(quantityInput.getAttribute('min') || '1');

        document.querySelectorAll('.quantity-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                let currentValue = parseInt(quantityInput.value);
                if (isNaN(currentValue)) currentValue = quantityMin;

                if (this.classList.contains('plus')) {
                    if (currentValue < quantityMax) quantityInput.value = currentValue + 1;
                    else quantityInput.value = quantityMax;
                } else if (this.classList.contains('minus')) {
                    if (currentValue > quantityMin) quantityInput.value = currentValue - 1;
                    else quantityInput.value = quantityMin;
                }
            });
        });
         quantityInput.addEventListener('change', function() {
             let value = parseInt(this.value);
             if (isNaN(value) || value < quantityMin) this.value = quantityMin;
             if (value > quantityMax) this.value = quantityMax;
         });
     }


    // Tab Switching Logic
    const tabContainer = document.querySelector('.product-tabs'); // Adjusted selector
    if (tabContainer) {
         const tabBtns = tabContainer.querySelectorAll('.tab-btn');
         const tabPanes = tabContainer.querySelectorAll('.tab-pane');

         tabContainer.addEventListener('click', function(e) {
             const clickedButton = e.target.closest('.tab-btn');
             if (!clickedButton || clickedButton.classList.contains('text-primary')) return; // Check active style

             const tabId = clickedButton.dataset.tab;

             tabBtns.forEach(b => {
                 b.classList.remove('text-primary', 'border-primary');
                 b.classList.add('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
             });
             tabPanes.forEach(pane => pane.classList.remove('active')); // Assuming 'active' class controls visibility

             clickedButton.classList.add('text-primary', 'border-primary');
             clickedButton.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');

             const activePane = tabContainer.querySelector(`.tab-pane#${tabId}`);
             if (activePane) {
                 activePane.classList.add('active');
             }
         });

         // Ensure initial active tab's pane is visible on load
         const initialActiveTab = tabContainer.querySelector('.tab-btn.text-primary');
         if (initialActiveTab) {
             const initialTabId = initialActiveTab.dataset.tab;
             const initialActivePane = tabContainer.querySelector(`.tab-pane#${initialTabId}`);
             if (initialActivePane) {
                 initialActivePane.classList.add('active');
             }
         } else {
            // If no tab is active by default, activate the first one
            const firstTab = tabContainer.querySelector('.tab-btn');
            const firstPane = tabContainer.querySelector('.tab-pane');
            if (firstTab && firstPane) {
                 firstTab.classList.add('text-primary', 'border-primary');
                 firstTab.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
                 firstPane.classList.add('active');
            }
         }
         // Add 'active' class styles to style.css if not already present
         // .tab-pane { display: none; }
         // .tab-pane.active { display: block; }
    }

    // Note: The main add-to-cart button now uses the global handler, including quantity.
    // Related product add-to-cart buttons also use the global handler (default quantity 1).
}


function initCartPage() {
    // console.log("Initializing Cart Page");
    const cartForm = document.getElementById('cartForm');
    if (!cartForm) return;

    // --- Helper Functions for Cart ---
    function updateCartTotalsDisplay() {
        let subtotal = 0;
        let itemCount = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const priceElement = item.querySelector('.item-price');
            const quantityInput = item.querySelector('.item-quantity input');
            const subtotalElement = item.querySelector('.item-subtotal');

            if (priceElement && quantityInput) {
                // Extract price reliably, removing currency symbols etc.
                const priceText = priceElement.dataset.price || priceElement.textContent;
                const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
                const quantity = parseInt(quantityInput.value);

                if (!isNaN(price) && !isNaN(quantity)) {
                    const lineTotal = price * quantity;
                    subtotal += lineTotal;
                    itemCount += quantity;
                    if (subtotalElement) {
                        subtotalElement.innerHTML = // Use innerHTML to allow md:hidden span
                            `<span class="md:hidden text-xs text-gray-500 mr-1">Subtotal:</span>$${lineTotal.toFixed(2)}`;
                    }
                }
            }
        });

        // Update summary totals
        const subtotalDisplay = cartForm.querySelector('.cart-summary .summary-row:nth-child(1) span:last-child');
        const totalDisplay = document.getElementById('cart-grand-total'); // Use specific ID for grand total
        const shippingDisplay = cartForm.querySelector('.cart-summary .summary-row.shipping span:last-child');
        const freeShippingThreshold = parseFloat(document.body.dataset.freeShippingThreshold || '50');
        const baseShippingCost = parseFloat(document.body.dataset.baseShippingCost || '5.99');

        const shippingCost = subtotal >= freeShippingThreshold ? 0 : baseShippingCost;


        if (subtotalDisplay) subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
        if (shippingDisplay) shippingDisplay.innerHTML = shippingCost === 0 ? '<span class="text-green-600">FREE</span>' : '$' + shippingCost.toFixed(2);
        if (totalDisplay) totalDisplay.textContent = '$' + (subtotal + shippingCost).toFixed(2); // Update grand total


        updateCartCountHeader(itemCount);

        // Handle empty cart state (find elements by class/ID)
        const emptyCartMessageEl = document.querySelector('.empty-cart'); 
        const cartGrid = document.querySelector('.grid.grid-cols-1.lg\\:grid-cols-3'); // The main grid holding items and summary

        if (itemCount === 0) {
            if (cartGrid) cartGrid.classList.add('hidden');
            if (emptyCartMessageEl) emptyCartMessageEl.classList.remove('hidden');
        } else {
             if (cartGrid) cartGrid.classList.remove('hidden');
            if (emptyCartMessageEl) emptyCartMessageEl.classList.add('hidden');
        }

        const checkoutButton = document.querySelector('.checkout'); 
        if (checkoutButton) {
            checkoutButton.classList.toggle('opacity-50', itemCount === 0);
            checkoutButton.classList.toggle('cursor-not-allowed', itemCount === 0);
            if(itemCount === 0) checkoutButton.setAttribute('disabled', 'disabled');
            else checkoutButton.removeAttribute('disabled');
        }
    }

    function updateCartCountHeader(count) {
        const cartCountSpan = document.querySelector('.cart-count');
        if (cartCountSpan) {
            cartCountSpan.textContent = count;
            cartCountSpan.style.display = count > 0 ? 'flex' : 'none';
            cartCountSpan.classList.toggle('animate-pulse', count > 0);
            setTimeout(() => cartCountSpan.classList.remove('animate-pulse'), 1000);
        }
    }

    // --- Event Listeners for Cart Actions ---
    cartForm.addEventListener('click', function(e) {
        const quantityBtn = e.target.closest('.quantity-btn');
        if (quantityBtn) {
            const input = quantityBtn.parentElement.querySelector('input[name^="updates["]'); // Target input by name pattern
            if (!input) return;

            const max = parseInt(input.getAttribute('max') || '99');
            const min = parseInt(input.getAttribute('min') || '1');
            let value = parseInt(input.value);
            if (isNaN(value)) value = min;

            if (quantityBtn.classList.contains('plus')) {
                if (value < max) input.value = value + 1;
                else input.value = max;
            } else if (quantityBtn.classList.contains('minus')) {
                if (value > min) input.value = value - 1;
                else input.value = min;
            }
            // Trigger change event to update totals display immediately
            input.dispatchEvent(new Event('change', { bubbles: true }));
            return;
        }

        const removeItemBtn = e.target.closest('.remove-item');
        if (removeItemBtn) {
            e.preventDefault();
            const cartItemRow = removeItemBtn.closest('.cart-item');
            if (!cartItemRow) return;

            const productId = removeItemBtn.dataset.productId;
            const csrfTokenInput = cartForm.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfTokenInput?.value;


            if (!productId || !csrfToken) {
                showFlashMessage('Error removing item: Missing data.', 'error');
                return;
            }

            if (confirm('Are you sure you want to remove this item?')) {
                cartItemRow.style.opacity = '0';
                cartItemRow.style.transition = 'opacity 0.3s ease-out';
                setTimeout(() => {
                    cartItemRow.remove();
                    updateCartTotalsDisplay(); // Update totals after removing element visually
                }, 300);

                fetch('index.php?page=cart&action=remove', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `product_id=${encodeURIComponent(productId)}&csrf_token=${encodeURIComponent(csrfToken)}`
                })
                .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
                .then(data => {
                    if (data.success) {
                        showFlashMessage(data.message || 'Item removed.', 'success');
                        // Totals already updated visually. Header count updated by totals function.
                        if (typeof fetchMiniCart === 'function') fetchMiniCart();
                    } else {
                        showFlashMessage(data.message || 'Error removing item.', 'error');
                        // Revert optimistic UI update is complex, maybe force reload or rely on update button
                        updateCartTotalsDisplay(); // Re-run totals to ensure consistency
                    }
                })
                .catch(error => {
                    console.error('Error removing item:', error);
                    showFlashMessage('Failed to remove item.', 'error');
                    updateCartTotalsDisplay();
                });
            }
            return;
        }
    });

    cartForm.addEventListener('change', function(e) {
        if (e.target.matches('.item-quantity input')) {
            const input = e.target;
            const max = parseInt(input.getAttribute('max') || '99');
            const min = parseInt(input.getAttribute('min') || '1');
            let value = parseInt(input.value);

            if (isNaN(value) || value < min) input.value = min;
            if (value > max) {
                input.value = max;
                showFlashMessage(`Quantity cannot exceed ${max}.`, 'warning');
            }
            updateCartTotalsDisplay(); // Update totals on manual input change
        }
    });

    // AJAX Update Cart Button
    const updateCartButton = cartForm.querySelector('.update-cart'); // More specific selector
    if (updateCartButton) {
        updateCartButton.addEventListener('click', function(e) {
            e.preventDefault();
            const formData = new FormData(cartForm);
            const submitButton = this;
            const originalButtonText = submitButton.innerHTML; // Store full HTML
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';

            fetch('index.php?page=cart&action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json().catch(() => ({ success: false, message: 'Invalid response from server.' })))
            .then(data => {
                if (data.success) {
                    showFlashMessage(data.message || 'Cart updated!', 'success');
                    updateCartTotalsDisplay(); // Recalculate totals visually
                    if (typeof fetchMiniCart === 'function') fetchMiniCart();
                } else {
                     // Display specific stock errors if provided
                    let errorMessage = data.message || 'Failed to update cart.';
                    if (data.errors && data.errors.length > 0) {
                        errorMessage += ' ' + data.errors.join('; ');
                    }
                    showFlashMessage(errorMessage, 'error');
                    // Optionally reload or revert changes if update fails significantly
                    updateCartTotalsDisplay(); // Refresh totals again
                }
            })
            .catch(error => {
                console.error('Error updating cart:', error);
                showFlashMessage('Network error updating cart.', 'error');
                 updateCartTotalsDisplay(); // Refresh totals again
            })
            .finally(() => {
                 submitButton.disabled = false;
                 submitButton.innerHTML = originalButtonText;
            });
        });
    }

     updateCartTotalsDisplay(); // Initial calculation
}


function initLoginPage() {
    // console.log("Initializing Login Page");
    const form = document.getElementById('loginForm');
    if (!form) return;

    const submitButton = form.querySelector('button[type="submit"]');
    const buttonText = submitButton?.querySelector('.button-text');
    const buttonLoader = submitButton?.querySelector('.button-loader');

    // Password visibility toggle
    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            if (passwordInput && passwordInput.type) {
                 const icon = this.querySelector('i');
                 if (passwordInput.type === 'password') {
                     passwordInput.type = 'text';
                     icon?.classList.remove('fa-eye');
                     icon?.classList.add('fa-eye-slash');
                 } else {
                     passwordInput.type = 'password';
                     icon?.classList.remove('fa-eye-slash');
                     icon?.classList.add('fa-eye');
                 }
            }
        });
    });

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent standard form submission

        const emailInput = form.querySelector('#email');
        const passwordInput = form.querySelector('#password');
        const csrfTokenInput = document.getElementById('csrf-token-value'); // Get global CSRF

        if (!emailInput || !passwordInput || !submitButton || !csrfTokenInput) {
            console.error("Login form elements missing.");
            showFlashMessage('An error occurred submitting the form.', 'error');
            return;
        }
         const email = emailInput.value.trim();
         const password = passwordInput.value;
         const csrfToken = csrfTokenInput.value;


        if (!email || !password) {
             showFlashMessage('Please enter both email and password.', 'warning');
             return;
        }
         if (!csrfToken) {
             showFlashMessage('Security token missing. Please refresh.', 'error');
             return;
         }


        // Show loading state
        if(buttonText) buttonText.classList.add('hidden');
        if(buttonLoader) buttonLoader.classList.remove('hidden');
        submitButton.disabled = true;

        // Prepare data for fetch
        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);
        formData.append('csrf_token', csrfToken);
        // Append remember_me if needed
        const rememberMe = form.querySelector('input[name="remember_me"]');
        if (rememberMe && rememberMe.checked) {
            formData.append('remember_me', '1');
        }


        fetch('index.php?page=login', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             // Check content type before parsing JSON
             const contentType = response.headers.get("content-type");
             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                 return response.json();
             }
             // Handle non-JSON or error responses
             return response.text().then(text => {
                  console.error("Login error - non-JSON response:", response.status, text);
                  throw new Error(`Login failed. Server responded with status ${response.status}.`);
             });
         })
        .then(data => {
            if (data.success && data.redirect) {
                // Optional: show success message before redirect?
                // showFlashMessage('Login successful! Redirecting...', 'success');
                window.location.href = data.redirect; // Redirect on success
            } else {
                // Show error message from backend
                showFlashMessage(data.error || 'Login failed. Please check your credentials.', 'error');
            }
        })
        .catch(error => {
            console.error('Login Fetch Error:', error);
            showFlashMessage(error.message || 'An error occurred during login. Please try again.', 'error');
        })
        .finally(() => {
            // Hide loading state only if login failed (page redirects on success)
            if (buttonText) buttonText.classList.remove('hidden');
            if (buttonLoader) buttonLoader.classList.add('hidden');
            submitButton.disabled = false;
        });
    });
}


function initRegisterPage() {
    // console.log("Initializing Register Page");
    const form = document.getElementById('registerForm');
    if (!form) return;

    const passwordInput = form.querySelector('#password');
    const confirmPasswordInput = form.querySelector('#confirm_password');
    const submitButton = form.querySelector('button[type="submit"]');
    const buttonText = submitButton?.querySelector('.button-text');
    const buttonLoader = submitButton?.querySelector('.button-loader');

    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') }, // More general special char check
        match: { element: document.getElementById('req-match') }
    };

    function validatePassword() {
        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing

        let allMet = true;
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        for (const reqKey in requirements) {
            const req = requirements[reqKey];
            if (!req.element) continue;

            let isMet = false;
            if (reqKey === 'match') {
                isMet = passwordValue && passwordValue === confirmPasswordValue;
            } else if (req.regex) {
                isMet = req.regex.test(passwordValue);
            }

            req.element.classList.toggle('met', isMet);
            req.element.classList.toggle('not-met', !isMet);
            const icon = req.element.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-check-circle', isMet);
                icon.classList.toggle('fa-times-circle', !isMet);
                 icon.classList.toggle('text-green-500', isMet); // Add color classes
                 icon.classList.toggle('text-red-500', !isMet);
            }
            if (!isMet) allMet = false;
        }
        submitButton.disabled = !allMet;
        submitButton.classList.toggle('opacity-50', !allMet);
        submitButton.classList.toggle('cursor-not-allowed', !allMet);
        return allMet; // Return validation status
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validatePassword);
        validatePassword();
    }

    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function() {
            const passwordInputEl = this.previousElementSibling;
            if (passwordInputEl && passwordInputEl.type) {
                 const icon = this.querySelector('i');
                 if (passwordInputEl.type === 'password') {
                     passwordInputEl.type = 'text';
                     icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
                 } else {
                     passwordInputEl.type = 'password';
                     icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
                 }
            }
        });
    });

    // AJAX form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault(); // Always prevent standard submission

        if (!validatePassword()) { // Re-validate before submit
            showFlashMessage('Please ensure all password requirements are met.', 'warning');
            passwordInput?.focus(); // Focus on the first password field
            return;
        }

         const nameInput = form.querySelector('#name');
         const emailInput = form.querySelector('#email');
         const csrfTokenInput = document.getElementById('csrf-token-value'); // Global CSRF
         const newsletterCheckbox = form.querySelector('input[name="newsletter_signup"]'); // <-- Select the checkbox

        if (!nameInput || !emailInput || !passwordInput || !confirmPasswordInput || !submitButton || !csrfTokenInput) {
            console.error("Register form elements missing.");
            showFlashMessage('An error occurred submitting the form.', 'error');
            return;
        }

        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value; // Already validated
        const csrfToken = csrfTokenInput.value;


         if (!name || !email) {
             showFlashMessage('Please fill in all required fields.', 'warning');
             return;
         }
         if (!csrfToken) {
             showFlashMessage('Security token missing. Please refresh.', 'error');
             return;
         }


        // Show loading state
        if(buttonText) buttonText.classList.add('hidden');
        if(buttonLoader) buttonLoader.classList.remove('hidden');
        submitButton.disabled = true;

        // Prepare data for fetch
        const formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', confirmPasswordInput.value); // Send confirmation for backend double check if needed
        formData.append('csrf_token', csrfToken);

        // Append newsletter_signup only if the checkbox exists and is checked
        if (newsletterCheckbox && newsletterCheckbox.checked) {
            formData.append('newsletter_signup', '1'); // Use '1' as the value
        }


        fetch('index.php?page=register', {
            method: 'POST',
            body: formData
        })
        .then(response => {
             const contentType = response.headers.get("content-type");
             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
                 return response.json();
             }
             return response.text().then(text => {
                  console.error("Register error - non-JSON response:", response.status, text);
                  throw new Error(`Registration failed. Server responded with status ${response.status}.`);
             });
         })
        .then(data => {
            if (data.success && data.redirect) {
                 // Controller sets flash message for next page load, just redirect
                 window.location.href = data.redirect;
            } else {
                showFlashMessage(data.error || 'Registration failed. Please check your input and try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Register Fetch Error:', error);
            showFlashMessage(error.message || 'An error occurred during registration. Please try again.', 'error');
        })
        .finally(() => {
            // Hide loading state only if registration failed (page redirects on success)
            if (buttonText) buttonText.classList.remove('hidden');
            if (buttonLoader) buttonLoader.classList.add('hidden');
            // Re-enable button only if it failed, and re-validate password state
            validatePassword();
        });
    });
}


function initForgotPasswordPage() {
    // console.log("Initializing Forgot Password Page");
    const form = document.getElementById('forgotPasswordForm');
    if (!form) return;
    const submitButton = form.querySelector('button[type="submit"]');

    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
             // Keep standard form submission as controller handles redirect
             const email = form.querySelector('#email')?.value.trim();
             if (!email || !/\S+@\S+\.\S+/.test(email)) {
                 showFlashMessage('Please enter a valid email address.', 'error');
                 e.preventDefault();
                 return;
             }

            const buttonText = submitButton.querySelector('.button-text');
            const buttonLoader = submitButton.querySelector('.button-loader');
            if(buttonText) buttonText.classList.add('hidden');
            if(buttonLoader) buttonLoader.classList.remove('hidden');
            submitButton.disabled = true;
            // Allows standard POST
        });
    }
}


function initResetPasswordPage() {
    // console.log("Initializing Reset Password Page");
    const form = document.getElementById('resetPasswordForm');
    if (!form) return;

    const passwordInput = form.querySelector('#password');
    const confirmPasswordInput = form.querySelector('#password_confirm');
    const submitButton = form.querySelector('button[type="submit"]');

    const requirements = {
        length: { regex: /.{12,}/, element: document.getElementById('req-length') },
        uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
        lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
        number: { regex: /[0-9]/, element: document.getElementById('req-number') },
        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') },
        match: { element: document.getElementById('req-match') }
    };

    function validateResetPassword() {
        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing

        let allMet = true;
        const passwordValue = passwordInput.value;
        const confirmPasswordValue = confirmPasswordInput.value;

        for (const reqKey in requirements) {
            const req = requirements[reqKey];
            if (!req.element) continue;
            let isMet = false;
            if (reqKey === 'match') {
                isMet = passwordValue && passwordValue === confirmPasswordValue;
            } else if (req.regex) {
                isMet = req.regex.test(passwordValue);
            }
            req.element.classList.toggle('met', isMet);
            req.element.classList.toggle('not-met', !isMet);
            const icon = req.element.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-check-circle', isMet);
                icon.classList.toggle('fa-times-circle', !isMet);
                icon.classList.toggle('text-green-500', isMet); // Add color classes
                icon.classList.toggle('text-red-500', !isMet);
            }
            if (!isMet) allMet = false;
        }
        submitButton.disabled = !allMet;
        submitButton.classList.toggle('opacity-50', !allMet);
        submitButton.classList.toggle('cursor-not-allowed', !allMet);
        return allMet; // Return validation status
    }

    if (passwordInput && confirmPasswordInput) {
        passwordInput.addEventListener('input', validateResetPassword);
        confirmPasswordInput.addEventListener('input', validateResetPassword);
        validateResetPassword();
    }

    form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
         toggleBtn.addEventListener('click', function() {
             const passwordInputEl = this.previousElementSibling;
             if (passwordInputEl && passwordInputEl.type) {
                  const icon = this.querySelector('i');
                  if (passwordInputEl.type === 'password') {
                      passwordInputEl.type = 'text';
                      icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
                  } else {
                      passwordInputEl.type = 'password';
                      icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
                  }
             }
         });
     });

    if (form && submitButton) {
        form.addEventListener('submit', function(e) {
            // Keep standard form submission as controller handles redirects
            if (!validateResetPassword()) { // Final validation check
                e.preventDefault();
                showFlashMessage('Please ensure all password requirements are met.', 'error');
                return;
            }
            const buttonText = submitButton.querySelector('.button-text');
            const buttonLoader = submitButton.querySelector('.button-loader');
             if(buttonText) buttonText.classList.add('hidden');
             if(buttonLoader) buttonLoader.classList.remove('hidden');
            submitButton.disabled = true;
            // Allows standard POST
        });
    }
}


function initQuizPage() {
    // console.log("Initializing Quiz Page");
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json');
    }

    const quizForm = document.getElementById('scent-quiz');
    if (quizForm) {
         const optionsContainer = quizForm.querySelector('.quiz-options-container');
         if (optionsContainer) {
             optionsContainer.addEventListener('click', (e) => {
                 const selectedOption = e.target.closest('.quiz-option');
                 if (!selectedOption) return;

                 // Find the actual radio button within the clicked label
                 const radioInput = selectedOption.querySelector('input[type="radio"]');
                 if (radioInput) {
                     radioInput.checked = true; // Ensure the radio button is checked

                     // Update visual states for all options
                     optionsContainer.querySelectorAll('.quiz-option').forEach(opt => {
                         const innerDiv = opt.querySelector('div');
                         const optRadio = opt.querySelector('input[type="radio"]');
                         if (innerDiv && optRadio) {
                              if (optRadio.checked) {
                                 innerDiv.classList.add('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
                                 innerDiv.classList.remove('border-gray-200');
                              } else {
                                 innerDiv.classList.remove('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
                                 innerDiv.classList.add('border-gray-200');
                              }
                         }
                     });
                 }
             });
         }

        quizForm.addEventListener('submit', (e) => {
             // Check if any radio button in the group is checked
             const selectedRadio = quizForm.querySelector('input[name="mood"]:checked');

             if (!selectedRadio) {
                 e.preventDefault();
                 showFlashMessage('Please select an option.', 'warning');
                 optionsContainer?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                 return;
             }
              const submitButton = quizForm.querySelector('button[type="submit"]');
              if (submitButton) {
                  submitButton.disabled = true;
                  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Finding your scent...';
              }
             // Allows standard POST as controller handles rendering/redirect
        });
    }
}


function initQuizResultsPage() {
    // console.log("Initializing Quiz Results Page");
    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
        particlesJS.load('particles-js', '/particles.json');
    }
}


function initAdminQuizAnalyticsPage() {
    // console.log("Initializing Admin Quiz Analytics");
    if (typeof Chart === 'undefined') {
        console.error('Chart.js library is not loaded.');
        return;
    }
    let charts = {};
    const timeRangeSelect = document.getElementById('timeRange');
    const statsContainer = document.getElementById('statsContainer'); // Corrected ID if necessary
    const chartsContainer = document.getElementById('chartsContainer'); // Corrected ID if necessary
    const recommendationsTableBody = document.getElementById('recommendationsTable'); // Corrected ID

    // Check if elements exist before proceeding
     if (!timeRangeSelect || !document.getElementById('totalParticipants') || !document.getElementById('conversionRate') || !document.getElementById('avgCompletionTime') || !document.getElementById('scentChart') || !document.getElementById('moodChart') || !document.getElementById('completionsChart') || !recommendationsTableBody) {
          console.warn("One or more analytics elements not found. Analytics may not function correctly.");
          // Optionally display a message to the user if critical elements are missing
          // showFlashMessage("Could not load analytics components.", "error");
          // return; // Exit if critical elements are missing
     }


    Chart.defaults.font.family = "'Montserrat', sans-serif";
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };
    Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
    Chart.defaults.plugins.legend.position = 'bottom';

    async function updateAnalytics() {
        const timeRange = timeRangeSelect ? timeRangeSelect.value : '7d'; // Default if select missing
        // Add visual indication of loading
        document.getElementById('totalParticipants')?.classList.add('opacity-50');
        document.getElementById('conversionRate')?.classList.add('opacity-50');
        document.getElementById('avgCompletionTime')?.classList.add('opacity-50');
        document.getElementById('scentChart')?.parentElement.classList.add('opacity-50');
        document.getElementById('moodChart')?.parentElement.classList.add('opacity-50');
        document.getElementById('completionsChart')?.parentElement.classList.add('opacity-50');
        recommendationsTableBody?.classList.add('opacity-50');

        try {
            // Use correct Admin route: index.php?page=admin&section=quiz_analytics
            const response = await fetch(`index.php?page=admin&section=quiz_analytics&range=${timeRange}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
             if (!response.ok) {
                  const errorText = await response.text();
                  throw new Error(`Network response was not ok (${response.status}): ${errorText}`);
             }
            const data = await response.json();

            // Adjust based on expected JSON structure from QuizController::showAnalytics
            if (data.success) {
                updateStatCards(data.data?.statistics);
                updateCharts(data.data?.preferences); // Pass the preferences part
                updateRecommendationsTable(data.data?.recommendations); // Pass the recommendations part
            } else {
                 throw new Error(data.error || 'Failed to fetch analytics data from the server.');
            }
        } catch (error) {
            console.error('Error fetching or processing analytics data:', error);
            showFlashMessage(`Failed to load analytics: ${error.message}`, 'error');
             // Update UI to show loading failed state
             document.getElementById('totalParticipants').textContent = 'Error';
             document.getElementById('conversionRate').textContent = 'Error';
             document.getElementById('avgCompletionTime').textContent = 'Error';
             document.getElementById('scentChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
             document.getElementById('moodChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
             document.getElementById('completionsChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
            if (recommendationsTableBody) recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">Could not load recommendations.</td></tr>';
        } finally {
             // Remove visual indication of loading
             document.getElementById('totalParticipants')?.classList.remove('opacity-50');
             document.getElementById('conversionRate')?.classList.remove('opacity-50');
             document.getElementById('avgCompletionTime')?.classList.remove('opacity-50');
             document.getElementById('scentChart')?.parentElement.classList.remove('opacity-50');
             document.getElementById('moodChart')?.parentElement.classList.remove('opacity-50');
             document.getElementById('completionsChart')?.parentElement.classList.remove('opacity-50');
             recommendationsTableBody?.classList.remove('opacity-50');
        }
    }

    function updateStatCards(stats) {
        if (!stats) {
             document.getElementById('totalParticipants').textContent = 'N/A';
             document.getElementById('conversionRate').textContent = 'N/A';
             document.getElementById('avgCompletionTime').textContent = 'N/A';
             return;
         }
        document.getElementById('totalParticipants').textContent = stats.total_quizzes ?? 'N/A';
        document.getElementById('conversionRate').textContent = stats.conversion_rate != null ? `${stats.conversion_rate}%` : 'N/A';
        document.getElementById('avgCompletionTime').textContent = stats.avg_completion_time != null ? `${stats.avg_completion_time}s` : 'N/A';
    }

    function updateCharts(preferences) {
         if (!preferences) {
              document.getElementById('scentChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>';
              document.getElementById('moodChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>';
              document.getElementById('completionsChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data.</p>';
              return;
         }
         Object.values(charts).forEach(chart => chart?.destroy());
         charts = {};
         const chartColors = ['#1A4D5A', '#A0C1B1', '#D4A76A', '#6B7280', '#F59E0B', '#10B981'];

         // Scent Preference Chart (Assuming 'scent_types' is correct key from controller)
         const scentCtx = document.getElementById('scentChart')?.getContext('2d');
         if (scentCtx && preferences.scent_types?.length > 0) {
             charts.scent = new Chart(scentCtx, {
                 type: 'doughnut',
                 data: { labels: preferences.scent_types.map(p => p.type), datasets: [{ data: preferences.scent_types.map(p => p.count), backgroundColor: chartColors, hoverOffset: 4 }] },
                 options: { responsive: true, plugins: { legend: { display: true }, title: { display: true, text: 'Scent Type Preferences' } } }
             });
         } else if (scentCtx) { scentCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No scent preference data.</p>'; }

         // Mood Effect Chart (Assuming 'mood_effects' is correct key from controller)
         const moodCtx = document.getElementById('moodChart')?.getContext('2d');
         if (moodCtx && preferences.mood_effects?.length > 0) {
            charts.mood = new Chart(moodCtx, {
                type: 'bar',
                data: { labels: preferences.mood_effects.map(p => p.effect), datasets: [{ label: 'Count', data: preferences.mood_effects.map(p => p.count), backgroundColor: chartColors[1], borderColor: chartColors[1], borderWidth: 1 }] },
                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Desired Mood Effects' } } }
            });
         } else if (moodCtx) { moodCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No mood effect data.</p>'; }

         // Daily Completions Chart (Assuming 'daily_completions' is correct key)
          const completionsCtx = document.getElementById('completionsChart')?.getContext('2d');
          if (completionsCtx && preferences.daily_completions?.length > 0) {
             charts.completions = new Chart(completionsCtx, {
                 type: 'line',
                 data: { labels: preferences.daily_completions.map(d => d.date), datasets: [{ label: 'Completions', data: preferences.daily_completions.map(d => d.count), borderColor: chartColors[0], backgroundColor: 'rgba(26, 77, 90, 0.1)', fill: true, tension: 0.1 }] },
                 options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Quiz Completions Over Time' } } }
             });
         } else if (completionsCtx) { completionsCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data for this period.</p>'; }
    }

    function updateRecommendationsTable(recommendations) {
        if (!recommendations || !recommendationsTableBody) return;
        if (recommendations.length === 0) {
            recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No recommendations data available for this period.</td></tr>';
            return;
        }
         // Assuming `recommendations` array has objects with keys like: name, category, recommendation_count, conversion_rate, id
        recommendationsTableBody.innerHTML = recommendations.map(product => `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${product.category || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${product.recommendation_count ?? 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">${product.conversion_rate != null ? `${product.conversion_rate}%` : 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                    <a href="index.php?page=admin&section=products&task=edit&id=${product.id || '#'}" class="text-indigo-600 hover:text-indigo-900" title="View/Edit Product"><i class="fas fa-eye"></i></a>
                </td>
            </tr>`).join('');
    }

    if (timeRangeSelect) {
        timeRangeSelect.addEventListener('change', updateAnalytics);
        updateAnalytics(); // Initial load
    } else {
        console.warn("Time range selector not found. Loading default analytics.");
        updateAnalytics(); // Attempt initial load with default range
    }
}


function initAdminCouponsPage() {
    // console.log("Initializing Admin Coupons Page");
    const createButton = document.getElementById('createCouponBtn');
    const couponFormContainer = document.getElementById('couponFormContainer');
    const couponForm = document.getElementById('couponForm');
    const cancelFormButton = document.getElementById('cancelCouponForm');
    const couponListTable = document.getElementById('couponListTable'); // Table body
    const discountTypeSelect = document.getElementById('discount_type');
    const valueHint = document.getElementById('valueHint');

    function showCouponForm(couponData = null) {
        if (!couponForm || !couponFormContainer) return;
        couponForm.reset();
        couponForm.querySelector('input[name="coupon_id"]').value = '';
        const formTitle = couponFormContainer.querySelector('h2');
        const submitBtn = couponForm.querySelector('button[type="submit"]');

        if (couponData) {
            // Populate form for editing
            couponForm.querySelector('input[name="coupon_id"]').value = couponData.id || '';
            couponForm.querySelector('input[name="code"]').value = couponData.code || '';
            couponForm.querySelector('textarea[name="description"]').value = couponData.description || '';
            couponForm.querySelector('select[name="discount_type"]').value = couponData.discount_type || 'fixed';
            couponForm.querySelector('input[name="value"]').value = couponData.discount_value || ''; // Use correct key
            couponForm.querySelector('input[name="min_spend"]').value = couponData.min_purchase_amount || ''; // Use correct key
            couponForm.querySelector('input[name="usage_limit"]').value = couponData.usage_limit || '';
            if (couponData.valid_from) couponForm.querySelector('input[name="valid_from"]').value = couponData.valid_from.replace(' ', 'T').substring(0, 16);
            if (couponData.valid_to) couponForm.querySelector('input[name="valid_to"]').value = couponData.valid_to.replace(' ', 'T').substring(0, 16);
             couponForm.querySelector('input[name="is_active"][value="1"]').checked = couponData.is_active == 1;
             couponForm.querySelector('input[name="is_active"][value="0"]').checked = couponData.is_active == 0;

             if(formTitle) formTitle.textContent = 'Edit Coupon';
             if(submitBtn) submitBtn.textContent = 'Update Coupon';
        } else {
             if(formTitle) formTitle.textContent = 'Create New Coupon';
             if(submitBtn) submitBtn.textContent = 'Create Coupon';
             // Set default active status for new coupons
             couponForm.querySelector('input[name="is_active"][value="1"]').checked = true;
        }

        updateValueHint();
        couponFormContainer.classList.remove('hidden');
        couponForm.scrollIntoView({ behavior: 'smooth' });
    }

    function hideCouponForm() {
        if (!couponForm || !couponFormContainer) return;
        couponForm.reset();
        couponFormContainer.classList.add('hidden');
    }

    function updateValueHint() {
        if (!discountTypeSelect || !valueHint) return;
        const selectedType = discountTypeSelect.value;
        if (selectedType === 'percentage') valueHint.textContent = 'Enter % (e.g., 10 for 10%). Max 100.';
        else if (selectedType === 'fixed') valueHint.textContent = 'Enter fixed amount (e.g., 15.50 for $15.50).';
        else valueHint.textContent = '';
    }

    // Function to handle AJAX actions for Toggle/Delete
    function handleCouponAction(url, successMessage, errorMessage, confirmationMessage) {
        if (confirmationMessage && !confirm(confirmationMessage)) {
            return; // Abort if user cancels confirmation
        }
        const csrfToken = couponForm.querySelector('input[name="csrf_token"]')?.value; // Get CSRF from form for POST

        fetch(url, {
            method: 'POST', // Use POST for actions that change state
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/x-www-form-urlencoded' // Send CSRF in body
            },
            body: csrfToken ? `csrf_token=${encodeURIComponent(csrfToken)}` : ''
        })
        .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
        .then(data => {
            if (data.success) {
                showFlashMessage(successMessage, 'success');
                location.reload(); // Reload to see changes
            } else {
                showFlashMessage(data.message || errorMessage, 'error');
            }
        })
        .catch(error => {
            console.error('Coupon action error:', error);
            showFlashMessage('An error occurred. Please try again.', 'error');
        });
    }

    if (createButton) createButton.addEventListener('click', () => showCouponForm());
    if (cancelFormButton) cancelFormButton.addEventListener('click', hideCouponForm);
    if (discountTypeSelect) discountTypeSelect.addEventListener('change', updateValueHint);

    // Initial call for hint
    updateValueHint();

    // Event delegation for table buttons
    if (couponListTable) {
         couponListTable.addEventListener('click', function(e) {
             const editButton = e.target.closest('.edit-coupon');
             const toggleButton = e.target.closest('.toggle-status');
             const deleteButton = e.target.closest('.delete-coupon');

             if (editButton) {
                 e.preventDefault();
                 try {
                     const couponData = JSON.parse(editButton.dataset.coupon || '{}');
                     if (couponData.id) showCouponForm(couponData);
                     else console.error("Could not parse coupon data for editing.");
                 } catch (err) {
                     console.error("Error parsing coupon data:", err);
                     showFlashMessage('Could not load coupon data.', 'error');
                 }
                 return;
             }
             if (toggleButton) {
                 e.preventDefault();
                 const couponId = toggleButton.dataset.couponId;
                 if (couponId) {
                     handleCouponAction(
                         `index.php?page=admin&section=coupons&task=toggle_status&id=${couponId}`,
                         'Status updated.',
                         'Failed to update status.',
                         'Toggle status for this coupon?' // Confirmation message
                     );
                 }
                 return;
             }
             if (deleteButton) {
                 e.preventDefault();
                 const couponId = deleteButton.dataset.couponId;
                 if (couponId) {
                     handleCouponAction(
                         `index.php?page=admin&section=coupons&task=delete&id=${couponId}`,
                         'Coupon deleted.',
                         'Failed to delete coupon.',
                         'Permanently delete this coupon?' // Confirmation message
                     );
                 }
                 return;
             }
         });
    }

     // Handle form submission (standard POST, controller handles redirect)
     if (couponForm) {
         couponForm.addEventListener('submit', function() {
             const submitBtn = couponForm.querySelector('button[type="submit"]');
             if (submitBtn) {
                 submitBtn.disabled = true;
                 submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
             }
         });
     }
}


// --- Checkout Page Initialization (v5 - Stripe Elements Init Deferred) ---
function initCheckoutPage() {
    console.log("Initializing Checkout Page JS (v5 - Stripe Elements Init Deferred)...");
    // --- Configuration ---
    const bodyData = document.body.dataset;
    const stripePublicKey = bodyData.stripePublicKey || '';
    const freeShippingThreshold = parseFloat(bodyData.freeShippingThreshold || '50');
    const baseShippingCost = parseFloat(bodyData.baseShippingCost || '5.99');
    const baseUrl = bodyData.baseUrl || '/';

    // --- Element Selectors ---
    const checkoutForm = document.getElementById('checkoutForm');
    const submitButton = document.getElementById('submit-button');
    const spinner = document.getElementById('spinner');
    const buttonText = document.getElementById('button-text');
    const paymentElementContainer = document.getElementById('payment-element');
    const paymentMessage = document.getElementById('payment-message');
    const csrfToken = document.getElementById('csrf-token-value')?.value;
    const shippingCountryEl = document.getElementById('shipping_country');
    const shippingStateEl = document.getElementById('shipping_state');
    const summarySubtotalEl = document.getElementById('summary-subtotal');
    const summaryShippingEl = document.getElementById('summary-shipping');
    const summaryTotalEl = document.getElementById('summary-total');
    const taxAmountEl = document.getElementById('tax-amount');
    const taxRateEl = document.getElementById('tax-rate');
    const discountRow = document.querySelector('.summary-row.discount');
    const discountAmountEl = document.getElementById('discount-amount');
    const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code');
    const couponCodeInput = document.getElementById('coupon_code');
    const applyCouponButton = document.getElementById('apply-coupon');
    const couponMessageEl = document.getElementById('coupon-message');

    // --- State Variables ---
    let stripe = null; // Core Stripe object
    let elements = null; // Stripe Elements instance
    let currentSubtotal = parseFloat(summarySubtotalEl?.textContent?.replace('$', '') || '0');
    let currentShippingCost = parseFloat(summaryShippingEl?.textContent?.replace(/[^0-9.]/g, '') || baseShippingCost.toString());
    let currentTaxAmount = parseFloat(taxAmountEl?.textContent?.replace('$', '') || '0');
    let currentDiscountAmount = parseFloat(discountAmountEl?.textContent?.replace('-$', '') || '0');


    // --- Basic Checks & Stripe Core Initialization ---
    console.log("Stripe Public Key (from body.dataset):", stripePublicKey);
    if (!checkoutForm || !submitButton || !paymentElementContainer || !csrfToken || !summarySubtotalEl) {
        console.error("Checkout form critical elements missing. Aborting initialization."); return;
    }
    if (typeof Stripe === 'undefined') {
        console.error("Stripe.js library not loaded or `Stripe` object is undefined.");
        showMessage("Payment system library (Stripe.js) failed to load. Please check your internet connection or ad-blockers and refresh.", true);
        setLoading(false, true);
        if(paymentElementContainer) paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error: Payment library missing. Cannot initialize payment form.</p>';
        return;
    }
    if (!stripePublicKey) {
        showMessage("Stripe configuration error. Payment cannot proceed.", true);
        setLoading(false, true); return;
    }

    try {
         stripe = Stripe(stripePublicKey); // Initialize Stripe core object
         if (!stripe) { throw new Error("Stripe(key) failed to return an object."); }
         console.log("Stripe core object initialized successfully:", stripe);
         // Set initial placeholder for payment element
         if (paymentElementContainer) {
            paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Payment form will load after address and shipping are confirmed.</p>';
         }
    } catch (stripeCoreError) {
        console.error("Stripe Core Initialization error:", stripeCoreError);
        showMessage("Could not initialize payment system. Details: " + stripeCoreError.message, true);
        setLoading(false, true);
        if(paymentElementContainer) paymentElementContainer.innerHTML = `<p class="text-sm text-red-500 text-center p-4">Payment system init failed: ${stripeCoreError.message}</p>`;
        return;
    }

    // --- Helper Functions (setLoading, showMessage, showCouponMessage, updateOrderSummaryUI, updateTax) ---
    // These functions remain the same as in the previous version (content_of_code_files_3.md initCheckoutPage)
    // For brevity, not repeating them here but assume they are correctly implemented.
    // setLoading, showMessage, showCouponMessage, updateOrderSummaryUI, updateTax:
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
    function showCouponMessage(message, type) {
        if (!couponMessageEl) return;
        couponMessageEl.textContent = message;
        couponMessageEl.className = `coupon-message mt-2 text-sm ${type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')}`;
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
         summaryTotalEl.textContent = parseFloat(Math.max(0.50, grandTotal)).toFixed(2);
     }
    async function updateTax() {
            const country = shippingCountryEl?.value;
            const state = shippingStateEl?.value;
            if (!country || !taxRateEl || !taxAmountEl) {
                 if (taxRateEl) taxRateEl.textContent = 'N/A'; currentTaxAmount = 0; updateOrderSummaryUI(); return;
            }
            try {
                taxAmountEl.textContent = '...';
                const requestBody = { country, state, subtotal: currentSubtotal, discount: currentDiscountAmount, csrf_token: csrfToken };
                const response = await fetch('index.php?page=checkout&action=calculateTax', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(requestBody)
                });
                if (!response.ok) throw new Error(`Tax calculation failed (${response.status})`);
                const data = await response.json();
                if (data.success) { taxRateEl.textContent = data.tax_rate_formatted || 'N/A'; currentTaxAmount = parseFloat(data.tax_amount) || 0; }
                else { console.warn("Tax calculation error:", data.error); taxRateEl.textContent = 'Error'; currentTaxAmount = 0; }
            } catch (e) { console.error('Error fetching tax:', e); taxRateEl.textContent = 'Error'; currentTaxAmount = 0;
            } finally { updateOrderSummaryUI(); }
        }


    // --- Event Listeners (Tax, Coupon) ---
    if(shippingCountryEl) shippingCountryEl.addEventListener('change', updateTax);
    if(shippingStateEl) shippingStateEl.addEventListener('input', updateTax);
    if (applyCouponButton && couponCodeInput && appliedCouponHiddenInput) {
        applyCouponButton.addEventListener('click', async function() {
            const couponCode = couponCodeInput.value.trim(); if (!couponCode) { showCouponMessage('Please enter a coupon code.', 'error'); return; }
            showCouponMessage('Applying...', 'info'); applyCouponButton.disabled = true;
            try {
                const response = await fetch('index.php?page=checkout&action=applyCouponAjax', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ code: couponCode, subtotal: currentSubtotal, csrf_token: csrfToken })
                });
                if (!response.ok) throw new Error(`Server error applying coupon (${response.status})`);
                const data = await response.json();
                if (data.success) {
                    showCouponMessage(data.message || 'Coupon applied!', 'success'); currentDiscountAmount = parseFloat(data.discount_amount) || 0;
                    appliedCouponHiddenInput.value = data.coupon_code || couponCode; updateTax();
                } else {
                    showCouponMessage(data.message || 'Invalid coupon code.', 'error'); currentDiscountAmount = 0; appliedCouponHiddenInput.value = ''; updateTax();
                }
            } catch (e) { console.error('Coupon Apply Error:', e); showCouponMessage('Failed to apply coupon. Please try again.', 'error'); currentDiscountAmount = 0; appliedCouponHiddenInput.value = ''; updateTax();
            } finally { applyCouponButton.disabled = false; }
        });
    }


    // --- Checkout Form Submission ---
    submitButton.addEventListener('click', async function(e) {
        setLoading(true);
        showMessage(''); // Clear previous messages
        if(paymentElementContainer) paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Processing order and loading secure payment form...</p>';

        // 1. Client-side validation
        let isValid = true;
        const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
        requiredFields.forEach(id => {
            const input = document.getElementById(id);
            if (!input || !input.value.trim()) { isValid = false; input?.classList.add('input-error'); } else { input?.classList.remove('input-error'); }
        });
        if (!isValid) {
            showMessage('Please fill in all required shipping fields.', true); setLoading(false);
            const firstError = checkoutForm.querySelector('.input-error'); firstError?.focus(); firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if(paymentElementContainer) paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Please complete shipping details first.</p>'; return;
        }

        // 2. Send checkout data to server -> create order, get clientSecret
        let clientSecret = null;
        let serverOrderId = null;
        // `elements` is already defined in the outer scope of initCheckoutPage

        try {
            const checkoutFormData = new FormData(checkoutForm);
            if (appliedCouponHiddenInput && appliedCouponHiddenInput.value) { checkoutFormData.set('applied_coupon_code', appliedCouponHiddenInput.value); } else { checkoutFormData.delete('applied_coupon_code'); }
            const saveAddressCheckbox = checkoutForm.querySelector('input[name="save_address"]'); if (saveAddressCheckbox && saveAddressCheckbox.checked) { checkoutFormData.set('save_address', '1'); }
            
            console.log("Calling processCheckout backend...");
            const response = await fetch('index.php?page=checkout&action=processCheckout', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: checkoutFormData });
            
            console.log("Backend ProcessCheckout Response Status:", response.status);
            const data = await response.json(); 
            console.log("Backend ProcessCheckout Response Data:", data);

            if (response.ok && data.success && data.clientSecret && data.orderId) {
                clientSecret = data.clientSecret; serverOrderId = data.orderId; 
                console.log("Received clientSecret and orderId:", serverOrderId);
            } else { throw new Error(data.error || `Failed to process order on server (Status: ${response.status}).`); }
        } catch (serverError) {
            console.error('Server processing error:', serverError); showMessage(serverError.message, true);
            if(paymentElementContainer) paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Could not prepare payment. Please try again.</p>';
            setLoading(false); return;
        }

        // --- Step 3: Initialize Stripe Elements & Mount Payment Element ---
        // This now happens *after* clientSecret is obtained.
        if (clientSecret && stripe) { // Ensure stripe object is still valid
            try {
                const appearance = { theme: 'stripe', variables: { colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151', colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem' } };
                elements = stripe.elements({ clientSecret: clientSecret, appearance }); // Initialize/Re-initialize Elements with clientSecret
                console.log("Stripe Elements created/re-created with clientSecret.");
                
                const paymentElement = elements.create('payment');
                if(paymentElementContainer) paymentElementContainer.innerHTML = ''; // Clear previous placeholder
                paymentElement.mount('#payment-element'); 
                console.log("Payment Element mounted successfully.");
            } catch (elementsError) {
                console.error("Stripe Elements creation/mounting error:", elementsError); 
                showMessage("Failed to load the payment form. Please refresh. Details: " + elementsError.message, true);
                if(paymentElementContainer) paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error loading payment form.</p>';
                setLoading(false); return;
            }
        } else {
            if (!clientSecret) showMessage('Failed to get payment authorization from server.', true);
            if (!stripe) showMessage('Payment system core not initialized.', true);
            setLoading(false); return;
        }


        // --- STEP 4: Confirm Payment ---
        if (clientSecret && stripe && elements) { // Double check all are present
            console.log("Attempting stripe.confirmPayment...");
            const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
            const returnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation`;
            console.log("Stripe return_url:", returnUrl);

            const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
                elements,
                confirmParams: { return_url: returnUrl },
                redirect: 'if_required' // Stripe handles redirection if needed
            });

            if (stripeError) {
                 console.error("Stripe confirmPayment Error:", stripeError);
                 showMessage(stripeError.message || "Payment failed. Please check details or try another method.", true);
                 setLoading(false); // Re-enable button on error
            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                 // This case is mainly if redirect: 'if_required' doesn't redirect for some reason
                 // but payment succeeded (e.g. for some test cards or specific flows).
                 console.log("Stripe confirmPayment SUCCEEDED directly:", paymentIntent);
                 window.location.href = returnUrl; // Manually redirect
            } else if (paymentIntent) {
                 // PI exists but not succeeded, and no redirect happened
                 console.log("Stripe confirmPayment finished with status:", paymentIntent.status);
                 showMessage(`Payment status: ${paymentIntent.status}. You might be redirected or need to take further action.`, false);
                 setLoading(false);
            } else {
                 // No error, no PI, no redirect: This usually means Stripe handled the redirect.
                 // Keep loading spinner ON if Stripe is handling redirect.
                 console.log("confirmPayment finished. Assuming Stripe is handling redirect or an earlier error occurred.");
                 // setLoading(true) might be appropriate if a redirect is *always* expected,
                 // but if_required might not always redirect. Best to let the button re-enable if no explicit error.
                 // setLoading(false); // Or comment out to keep spinner if Stripe redirects
            }
        } else {
            console.error("Missing clientSecret, stripe, or elements for confirmPayment.");
            showMessage('Internal error during payment confirmation setup.', true);
            setLoading(false);
        }
    });

    // Initial UI calculations
    updateOrderSummaryUI();
    if (shippingCountryEl?.value) {
        updateTax();
    }
} // End initCheckoutPage


// --- Page Initializer Dispatcher ---
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS globally
    if (typeof AOS !== 'undefined') {
        AOS.init({ duration: 800, offset: 120, once: true });
    } else {
        console.warn('AOS library not loaded.');
    }

    const body = document.body;
    // Map body class names to initializer functions
    const pageInitializers = {
        'page-home': initHomePage,
        'page-products': initProductsPage,
        'page-product-detail': initProductDetailPage,
        'page-cart': initCartPage,
        'page-login': initLoginPage,
        'page-register': initRegisterPage,
        'page-forgot-password': initForgotPasswordPage,
        'page-reset-password': initResetPasswordPage,
        'page-quiz': initQuizPage,
        'page-quiz-results': initQuizResultsPage,
        'page-admin-quiz-analytics': initAdminQuizAnalyticsPage,
        'page-admin-coupons': initAdminCouponsPage,
        'page-checkout': initCheckoutPage, // Ensure this is called
        'page-admin-orders': initAdminOrdersPage,
    };

    let initialized = false;
    for (const pageClass in pageInitializers) {
        if (body.classList.contains(pageClass)) {
            pageInitializers[pageClass]();
            initialized = true;
            // console.log(`Initialized: ${pageClass}`);
            break;
        }
    }

    // Fetch mini cart content on initial load
    if (document.getElementById('mini-cart-content') && typeof fetchMiniCart === 'function') {
         fetchMiniCart();
    }
});


// --- Mini Cart AJAX Update Function ---
function fetchMiniCart() {
    const miniCartContent = document.getElementById('mini-cart-content');
    if (!miniCartContent) return;

    miniCartContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>';

    fetch('index.php?page=cart&action=mini', {
        method: 'GET',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (!response.ok) throw new Error(`Network response was not ok (${response.status})`);
        return response.json();
    })
    .then(data => {
        if (data.items && data.items.length > 0) {
            let html = '<ul class="divide-y divide-gray-200 max-h-60 overflow-y-auto">';
             data.items.forEach(item => {
                 const productId = item.product?.id || '#';
                 const imageUrl = item.product?.image || '/images/placeholder.jpg';
                 const productName = item.product?.name || 'Unknown Product';
                 const productPrice = parseFloat(item.product?.price || 0);
                 const quantity = parseInt(item.quantity || 0);
                 const lineTotal = productPrice * quantity;
                 html += `
                    <li class="flex items-center gap-3 py-3 px-1">
                         <img src="${imageUrl}" alt="${productName}" class="w-12 h-12 object-cover rounded border flex-shrink-0">
                         <div class="flex-1 min-w-0">
                             <a href="index.php?page=product&id=${productId}" class="font-medium text-sm text-gray-800 hover:text-primary truncate block" title="${productName}">${productName}</a>
                             <div class="text-xs text-gray-500">Qty: ${quantity} &times; $${productPrice.toFixed(2)}</div>
                         </div>
                         <div class="text-sm font-semibold text-gray-700">$${lineTotal.toFixed(2)}</div>
                     </li>`;
            });
            html += '</ul>';
            const subtotal = parseFloat(data.subtotal || 0);
            html += `<div class="border-t border-gray-200 pt-4 mt-4">
                 <div class="flex justify-between items-center mb-4">
                     <span class="font-semibold text-gray-700">Subtotal:</span>
                     <span class="font-bold text-primary text-lg">$${subtotal.toFixed(2)}</span>
                 </div>
                 <div class="flex flex-col gap-2">
                     <a href="index.php?page=cart" class="btn btn-secondary w-full text-center">View Cart</a>
                     <a href="index.php?page=checkout" class="btn btn-primary w-full text-center ${subtotal === 0 ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}">Checkout</a>
                 </div>
             </div>`;
            miniCartContent.innerHTML = html;
        } else {
            miniCartContent.innerHTML = '<div class="text-center text-gray-500 py-6 px-4">Your cart is empty.</div>';
        }
    })
    .catch(error => {
        console.error('Error fetching mini cart:', error);
        miniCartContent.innerHTML = '<div class="text-center text-red-500 py-6 px-4">Could not load cart.</div>';
    });
}

// --- END OF FILE main.js ---

```

```php
<?php
// controllers/TaxController.php (Updated to use $this->db instead of $this->pdo)

require_once __DIR__ . '/BaseController.php';

class TaxController extends BaseController {
    private $cache = [];
    // $db property is inherited from BaseController
    
    public function calculateTax($subtotal, $country, $state = null) {
        try {
            $subtotal = $this->validateInput($subtotal, 'float');
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if ($subtotal === false || !$country) { // Ensure subtotal is not false from validation
                throw new Exception('Invalid tax calculation parameters');
            }
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return round($subtotal * $this->cache[$cacheKey], 2);
            }
            
            // Get tax rate from database
            $stmt = $this->db->prepare(" // USE $this->db
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? (float)$result['rate'] : 0; // Cast rate to float
            $this->cache[$cacheKey] = $rate;
            
            return round($subtotal * $rate, 2);
            
        } catch (Exception $e) {
            error_log("Tax calculation error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function getTaxRate($country, $state = null) {
        try {
            $country = $this->validateInput($country, 'string');
            $state = $this->validateInput($state, 'string');
            
            if (!$country) return 0;
            
            // Check cache first
            $cacheKey = "{$country}_{$state}";
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }
            
            $stmt = $this->db->prepare(" // USE $this->db
                SELECT rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR state_code IS NULL)
                AND is_active = TRUE
                AND start_date <= NOW()
                AND (end_date IS NULL OR end_date > NOW())
                ORDER BY state_code IS NULL
                LIMIT 1
            ");
            $stmt->execute([$country, $state]);
            $result = $stmt->fetch();
            
            $rate = $result ? (float)$result['rate'] : 0; // Cast rate to float
            $this->cache[$cacheKey] = $rate;
            
            return $rate;
            
        } catch (Exception $e) {
            error_log("Tax rate lookup error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function formatTaxRate($rate) {
        return number_format((float)$rate * 100, 2) . '%'; // Cast rate to float
    }
    
    public function getAllTaxRates() {
        try {
            $this->requireAdmin();
            
            $stmt = $this->db->query(" // USE $this->db
                SELECT 
                    tr.*,
                    COUNT(th.id) as change_count,
                    MAX(th.created_at) as last_modified
                FROM tax_rates tr
                LEFT JOIN tax_rate_history th ON tr.id = th.tax_rate_id
                GROUP BY tr.id
                ORDER BY tr.country_code, tr.state_code
            ");
            
            return $this->jsonResponse([
                'success' => true,
                'rates' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rates: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rates'
            ], 500);
        }
    }
    
    public function updateTaxRate() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();
            
            $data = [
                'country_code' => $this->validateInput($_POST['country_code'], 'string'),
                'state_code' => $this->validateInput($_POST['state_code'] ?? null, 'string'),
                'rate' => $this->validateInput($_POST['rate'], 'float'),
                'start_date' => $this->validateInput($_POST['start_date'] ?? date('Y-m-d'), 'string'), // Validate as date string
                'end_date' => $this->validateInput($_POST['end_date'] ?? null, 'string'), // Validate as date string
                'is_active' => isset($_POST['is_active']) ? 1 : 0 // Convert to int for DB
            ];
            
            if (!$data['country_code'] || $data['rate'] === false || $data['rate'] < 0) { // Check rate validation
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid tax rate data'
                ], 400);
            }
            
            $this->beginTransaction(); // Uses $this->db from BaseController
            
            // Get existing rate if any
            $stmt = $this->db->prepare(" // USE $this->db
                SELECT id, rate 
                FROM tax_rates 
                WHERE country_code = ? 
                AND (state_code = ? OR (state_code IS NULL AND ? IS NULL))
            ");
            $stmt->execute([
                $data['country_code'],
                $data['state_code'],
                $data['state_code']
            ]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing rate
                $stmt = $this->db->prepare(" // USE $this->db
                    UPDATE tax_rates 
                    SET rate = ?,
                        start_date = ?,
                        end_date = ?,
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $existing['id']
                ]);
                
                // Log the change
                if ($existing['rate'] != $data['rate']) {
                    $this->logRateChange(
                        $existing['id'],
                        $existing['rate'],
                        $data['rate']
                    );
                }
            } else {
                // Insert new rate
                $stmt = $this->db->prepare(" // USE $this->db
                    INSERT INTO tax_rates (
                        country_code,
                        state_code,
                        rate,
                        start_date,
                        end_date,
                        is_active,
                        created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['country_code'],
                    $data['state_code'],
                    $data['rate'],
                    $data['start_date'],
                    $data['end_date'],
                    $data['is_active'],
                    $this->getUserId()
                ]);
                
                $rateId = $this->db->lastInsertId(); // USE $this->db
                $this->logRateChange($rateId, 0, $data['rate']);
            }
            
            // Clear cache for this region
            $cacheKey = "{$data['country_code']}_{$data['state_code']}";
            unset($this->cache[$cacheKey]);
            
            $this->commit(); // Uses $this->db from BaseController
            
            return $this->jsonResponse([
                'success' => true,
                'message' => 'Tax rate updated successfully'
            ]);
            
        } catch (Exception $e) {
            $this->rollback(); // Uses $this->db from BaseController
            error_log("Tax rate update error: " . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to update tax rate'
            ], 500);
        }
    }
    
    private function logRateChange($rateId, $oldRate, $newRate) {
        $stmt = $this->db->prepare(" // USE $this->db
            INSERT INTO tax_rate_history (
                tax_rate_id,
                old_rate,
                new_rate,
                changed_by
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $rateId,
            $oldRate,
            $newRate,
            $this->getUserId()
        ]);
    }
    
    public function getTaxRateHistory($rateId) {
        try {
            $this->requireAdmin();
            
            $rateId = $this->validateInput($rateId, 'int');
            if (!$rateId) {
                throw new Exception('Invalid tax rate ID');
            }
            
            $stmt = $this->db->prepare(" // USE $this->db
                SELECT 
                    th.*,
                    u.name as changed_by_name
                FROM tax_rate_history th
                LEFT JOIN users u ON th.changed_by = u.id
                WHERE th.tax_rate_id = ?
                ORDER BY th.created_at DESC
            ");
            $stmt->execute([$rateId]);
            
            return $this->jsonResponse([
                'success' => true,
                'history' => $stmt->fetchAll()
            ]);
            
        } catch (Exception $e) {
            error_log("Error fetching tax rate history: " . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to retrieve tax rate history'
            ], 500);
        }
    }
}
```

```php
<?php
// views/account/dashboard.php (Layout Refactored with Tailwind CSS - Quiz History Link Updated)
require_once __DIR__ . '/../layout/header.php'; // Standard header include

// Helper to render dashboard cards consistently
function renderDashboardCard($title, $content, $linkUrl = null, $linkText = 'View All', $aosDelay = 0, $extraClasses = '') {
    echo "<div class='bg-white rounded-lg shadow-md p-6 {$extraClasses}' data-aos='fade-up' data-aos-delay='{$aosDelay}'>";
    if ($title) {
        echo "<div class='flex justify-between items-center mb-4 border-b pb-2'>";
        echo "<h2 class='text-xl font-semibold text-primary font-heading'>{$title}</h2>";
        if ($linkUrl) {
            echo "<a href='{$linkUrl}' class='text-sm text-primary hover:text-primary-dark font-semibold flex items-center gap-1'>";
            echo "{$linkText} <i class='fas fa-arrow-right text-xs'></i>";
            echo "</a>";
        }
        echo "</div>";
    }
    echo "<div class='card-content'>"; // Container for content
    echo $content;
    echo "</div>";
    echo "</div>";
}
?>

<section class="account-section py-10 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Sidebar Navigation -->
            <aside class="lg:col-span-1" data-aos="fade-right">
                <div class="account-sidebar bg-white p-6 rounded-lg shadow-md sticky top-24">
                    <div class="user-info text-center border-b pb-4 mb-4">
                        <i class="fas fa-user-circle text-5xl text-primary mb-2"></i>
                        <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($user['name'] ?? 'User') ?></h3>
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    </div>

                    <nav>
                        <ul class="space-y-2">
                            <li>
                                <a href="index.php?page=account" class="flex items-center px-4 py-2 rounded-md text-gray-700 bg-secondary/20 border-l-4 border-primary font-semibold">
                                    <i class="fas fa-home w-6 text-center mr-3 text-primary"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders" class="flex items-center px-4 py-2 rounded-md text-gray-600 hover:bg-gray-100 hover:text-primary transition duration-150 ease-in-out">
                                    <i class="fas fa-shopping-bag w-6 text-center mr-3"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile" class="flex items-center px-4 py-2 rounded-md text-gray-600 hover:bg-gray-100 hover:text-primary transition duration-150 ease-in-out">
                                    <i class="fas fa-user w-6 text-center mr-3"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=quiz&action=history" class="flex items-center px-4 py-2 rounded-md text-gray-600 hover:bg-gray-100 hover:text-primary transition duration-150 ease-in-out"> {/* MODIFIED LINK */}
                                    <i class="fas fa-clipboard-list w-6 text-center mr-3"></i> Quiz History
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=logout" class="flex items-center px-4 py-2 rounded-md text-gray-600 hover:bg-gray-100 hover:text-primary transition duration-150 ease-in-out">
                                    <i class="fas fa-sign-out-alt w-6 text-center mr-3"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="lg:col-span-3">
                <h1 class="text-3xl font-bold text-primary mb-8 font-heading" data-aos="fade-up">Account Dashboard</h1>

                <!-- Grid for Dashboard Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- Quick Stats Card -->
                    <?php
                    $statsContent = "<div class='flex flex-col sm:flex-row justify-around gap-4'>";
                    $statsContent .= "<div class='stat-item flex items-center space-x-3 p-3'>";
                    $statsContent .= "<i class='fas fa-shopping-bag text-3xl text-secondary'></i>";
                    $statsContent .= "<div class='stat-info'><span class='block text-2xl font-semibold text-primary'>" . count($recentOrders ?? []) . "</span><span class='text-sm text-gray-500'>Recent Orders</span></div>";
                    $statsContent .= "</div>";
                    $statsContent .= "<div class='stat-item flex items-center space-x-3 p-3'>";
                    $statsContent .= "<i class='fas fa-star text-3xl text-secondary'></i>"; // Changed icon
                    $statsContent .= "<div class='stat-info'><span class='block text-2xl font-semibold text-primary'>" . (is_array($quizResults ?? []) ? count($quizResults) : 0) . "</span><span class='text-sm text-gray-500'>Quiz Results</span></div>"; // Updated label
                    $statsContent .= "</div>";
                    $statsContent .= "</div>";
                    renderDashboardCard(null, $statsContent, null, null, 0, 'md:col-span-2'); // Span full width on medium+
                    ?>

                    <!-- Recent Orders Card -->
                    <?php
                    $ordersContent = '';
                    if (empty($recentOrders)) {
                        $ordersContent = "<div class='text-center py-6'>";
                        $ordersContent .= "<i class='fas fa-shopping-bag text-4xl text-gray-300 mb-3'></i>";
                        $ordersContent .= "<p class='text-gray-600 mb-4'>No orders found yet.</p>";
                        $ordersContent .= "<a href='index.php?page=products' class='btn-primary btn-sm'>Start Shopping</a>";
                        $ordersContent .= "</div>";
                    } else {
                        $ordersContent .= "<div class='orders-list space-y-3'>";
                        foreach ($recentOrders as $order) {
                            $ordersContent .= "<div class='order-item flex justify-between items-center border p-3 rounded-md hover:bg-gray-50 transition duration-150'>";
                            $ordersContent .= "<div>";
                            $ordersContent .= "<span class='font-semibold text-primary block'>#" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . "</span>";
                            $ordersContent .= "<span class='text-xs text-gray-500'>" . date('M j, Y', strtotime($order['created_at'])) . "</span>";
                            $ordersContent .= "</div>";
                            $ordersContent .= "<div class='text-right'>";
                            $ordersContent .= "<span class='order-status status-" . htmlspecialchars($order['status']) . " text-xs font-medium px-2 py-0.5 rounded-full'>" . ucfirst(htmlspecialchars($order['status'])) . "</span>";
                            $ordersContent .= "<span class='text-sm font-semibold ml-2'>$" . number_format($order['total_amount'], 2) . "</span>";
                            $ordersContent .= "</div>";
                             $ordersContent .= "<div><a href='index.php?page=account&section=orders&id={$order['id']}' class='btn-secondary btn-xs'>Details</a></div>";
                            $ordersContent .= "</div>";
                        }
                        $ordersContent .= "</div>";
                    }
                    renderDashboardCard('Recent Orders', $ordersContent, 'index.php?page=account&section=orders', 'View All', 100);
                    ?>

                    <!-- Scent Quiz Results Card -->
                    <?php
                    $quizContent = '';
                    if (empty($quizResults)) {
                        $quizContent = "<div class='text-center py-6'>";
                        $quizContent .= "<i class='fas fa-flask text-4xl text-gray-300 mb-3'></i>"; // Changed icon
                        $quizContent .= "<p class='text-gray-600 mb-4'>Take the quiz to discover your profile.</p>";
                        $quizContent .= "<a href='index.php?page=quiz' class='btn-primary btn-sm'>Take Quiz Now</a>";
                        $quizContent .= "</div>";
                    } else {
                        $latestQuiz = $quizResults[0]; // Get the most recent result
                        $preferences = isset($latestQuiz['answers']) ? json_decode($latestQuiz['answers'], true) : [];
                        if (!is_array($preferences)) $preferences = [];
                        $recommendedIds = isset($latestQuiz['recommendations']) ? json_decode($latestQuiz['recommendations'], true) : [];
                        if (!is_array($recommendedIds)) $recommendedIds = [];

                        $quizContent .= "<div class='space-y-4'>";
                        $quizContent .= "<div><h3 class='font-semibold text-gray-700 mb-2'>Latest Preferences:</h3>";
                        if (!empty($preferences)) {
                            $quizContent .= "<ul class='list-disc list-inside space-y-1 text-sm text-gray-600 pl-4'>";
                            foreach ($preferences as $key => $pref) {
                                $quizContent .= "<li>" . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . ": <strong>" . htmlspecialchars($pref) . "</strong></li>";
                            }
                            $quizContent .= "</ul>";
                        } else {
                            $quizContent .= "<p class='text-sm text-gray-500 italic'>No preferences recorded for latest result.</p>";
                        }
                         $quizContent .= "</div>";

                         // Display Recommended Products (Fetch details if needed)
                         if (!empty($recommendedIds)) {
                             $quizContent .= "<div><h3 class='font-semibold text-gray-700 mb-2 mt-4 border-t pt-3'>Top Recommendations:</h3>";
                             // Fetch product details based on $recommendedIds
                              if (isset($pdo)) { // Check if $pdo is available
                                   if (!class_exists('Product')) require_once __DIR__ . '/../../models/Product.php';
                                   $productModel = new Product($pdo);
                                   // Fetch details for a limited number, e.g., 2 for the dashboard card
                                   $recommendations = $productModel->getProductsByIds(array_slice($recommendedIds, 0, 2));
                                   if (!empty($recommendations)) {
                                       $quizContent .= "<div class='flex flex-col gap-3'>";
                                       foreach ($recommendations as $product) {
                                            $quizContent .= "<div class='recommended-product flex items-center gap-3 p-2 border rounded-md bg-gray-50/50'>";
                                            $quizContent .= "<img src='" . htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') . "' alt='" . htmlspecialchars($product['name']) . "' class='w-10 h-10 object-cover rounded flex-shrink-0'>";
                                            $quizContent .= "<div class='flex-grow'><h4 class='text-sm font-medium text-primary'>" . htmlspecialchars($product['name']) . "</h4>";
                                            $quizContent .= "<p class='text-xs text-gray-500'>$" . number_format($product['price'], 2) . "</p></div>";
                                            $quizContent .= "<a href='index.php?page=product&id={$product['id']}' class='btn-secondary btn-xs whitespace-nowrap'>View</a>";
                                            $quizContent .= "</div>";
                                       }
                                       $quizContent .= "</div>";
                                   } else {
                                       $quizContent .= "<p class='text-sm text-gray-500 italic'>Could not load recommendations.</p>";
                                   }
                              } else {
                                   $quizContent .= "<p class='text-sm text-red-500 italic'>Database connection error.</p>";
                              }
                         } else {
                              $quizContent .= "<p class='text-sm text-gray-500 italic mt-4 border-t pt-3'>No product recommendations from this quiz.</p>";
                         }
                         $quizContent .= "</div>";
                         $quizContent .= "</div>"; // Close space-y-4
                    }
                    renderDashboardCard('Your Scent Profile', $quizContent, 'index.php?page=quiz&action=history', 'View History', 200); // MODIFIED LINK
                    ?>

                    <!-- Quick Actions Card -->
                    <?php
                    $actionsContent = "<div class='grid grid-cols-1 sm:grid-cols-3 gap-4'>";
                    $actionsContent .= "<a href='index.php?page=account&section=profile' class='btn-action flex flex-col items-center p-4 bg-gray-100 rounded-lg hover:bg-secondary/20 transition duration-150 text-center'><i class='fas fa-user-edit text-2xl mb-2 text-primary'></i><span class='text-sm font-medium'>Edit Profile</span></a>";
                    $actionsContent .= "<a href='index.php?page=quiz' class='btn-action flex flex-col items-center p-4 bg-gray-100 rounded-lg hover:bg-secondary/20 transition duration-150 text-center'><i class='fas fa-sync text-2xl mb-2 text-primary'></i><span class='text-sm font-medium'>Retake Quiz</span></a>";
                    $actionsContent .= "<a href='index.php?page=products' class='btn-action flex flex-col items-center p-4 bg-gray-100 rounded-lg hover:bg-secondary/20 transition duration-150 text-center'><i class='fas fa-shopping-bag text-2xl mb-2 text-primary'></i><span class='text-sm font-medium'>Shop Now</span></a>";
                    $actionsContent .= "</div>";
                    renderDashboardCard('Quick Actions', $actionsContent, null, null, 300, 'md:col-span-2'); // Span full width
                    ?>

                </div> <!-- End Dashboard Grid -->
            </div> <!-- End Account Content -->
        </div> <!-- End Account Grid -->
    </div> <!-- End Container -->
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

```

```php
<?php
// Updated: views/account/order_details.php (Quiz History Link Updated)

require_once __DIR__ . '/../layout/header.php'; 
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
                                <a href="index.php?page=account&section=orders" class="active">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=quiz&action=history"> {/* MODIFIED LINK */}
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
                <div class="order-details-header" data-aos="fade-up">
                    <div class="header-left">
                        <a href="index.php?page=account&section=orders" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                        <h1>Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
                    </div>
                    <div class="header-right">
                        <span class="order-date">
                            <?= date('F j, Y', strtotime($order['created_at'])) ?>
                        </span>
                        <span class="order-status <?= htmlspecialchars($order['status']) ?>">
                            <?= ucfirst(htmlspecialchars($order['status'])) ?>
                        </span>
                    </div>
                </div>

                <!-- Order Progress -->
                <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'payment_failed' && $order['status'] !== 'disputed' && $order['status'] !== 'refunded'): // Don't show progress for terminal states ?>
                    <div class="order-progress" data-aos="fade-up">
                        <?php
                        $statuses = ['processing', 'shipped', 'delivered']; // Adjusted flow
                        // Find the current index (or default to -1 if not found/before processing)
                        $currentIndex = array_search($order['status'], $statuses);
                        if ($currentIndex === false) $currentIndex = -1;

                        foreach ($statuses as $index => $status):
                            $isActive = $index <= $currentIndex; // Step is active if it's the current status or before
                            $isCompleted = $index < $currentIndex; // Step is completed if it's before the current status

                            // Determine icon based on status
                            $iconClass = match($status) {
                                'processing' => 'fa-clock',
                                'shipped' => 'fa-truck',
                                'delivered' => 'fa-box-check', // Use a check mark icon
                                default => 'fa-question-circle' // Fallback icon
                            };
                        ?>
                            <div class="progress-step <?= $isActive ? 'active' : '' ?>">
                                <div class="step-icon">
                                    <?php if ($isCompleted): ?>
                                        <i class="fas fa-check"></i>
                                    <?php else: ?>
                                        <i class="fas <?= $iconClass ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="step-label">
                                    <?= ucfirst($status) ?>
                                    <?php
                                        // Check if status date exists (e.g., shipped_at, delivered_at)
                                        // Assuming Order model populates these if status is reached
                                        $statusDateKey = $status . '_at'; // Convention: processing_at, shipped_at, etc.
                                        if ($status === $order['status'] && isset($order[$statusDateKey]) && $order[$statusDateKey]):
                                    ?>
                                        <span class="step-date">
                                            <?= date('M j', strtotime($order[$statusDateKey])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($index < count($statuses) - 1): ?>
                                <div class="progress-line <?= $isActive ? 'active' : '' ?>"></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="order-details-grid">
                    <!-- Order Items -->
                    <div class="order-items-card" data-aos="fade-up">
                        <h2>Order Items</h2>
                        <div class="items-list">
                            <?php
                                // Ensure items is an array, default to empty if not set or invalid JSON
                                $items = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];
                            ?>
                             <?php if (empty($items)): ?>
                                <p class="text-gray-500 italic p-4">No items found for this order.</p>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <div class="order-item">
                                        <div class="item-image">
                                            <img src="<?= htmlspecialchars($item['image_url'] ?? '/images/placeholder.jpg') ?>"
                                                 alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>">
                                        </div>
                                        <div class="item-details">
                                            <h3><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></h3>
                                            <p class="item-meta">
                                                Quantity: <?= htmlspecialchars($item['quantity'] ?? 0) ?> |
                                                Price: $<?= number_format($item['price_at_purchase'] ?? 0, 2) ?>
                                            </p>
                                            <?php /* Removed options display as it's not in the current item data structure
                                            <?php if (!empty($item['options'])): ?>
                                                <p class="item-options">
                                                    Options: <?= htmlspecialchars(implode(', ', $item['options'])) ?>
                                                </p>
                                            <?php endif; ?>
                                            */ ?>
                                        </div>
                                        <div class="item-actions">
                                            <span class="item-total">
                                                $<?= number_format(($item['quantity'] ?? 0) * ($item['price_at_purchase'] ?? 0), 2) ?>
                                            </span>
                                            <form action="index.php?page=cart&action=add" method="POST">
                                                 <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                                 <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id'] ?? '') ?>">
                                                 <input type="hidden" name="quantity" value="<?= htmlspecialchars($item['quantity'] ?? 1) ?>">
                                                 <button type="submit" class="btn-secondary" <?= empty($item['product_id']) ? 'disabled' : '' ?>>Buy Again</button>
                                             </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                             <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary-card" data-aos="fade-up">
                        <h2>Order Summary</h2>
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>$<?= number_format($order['subtotal'], 2) ?></span>
                            </div>
                            <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                <div class="summary-row discount">
                                    <span>
                                        Discount
                                        <?php if (!empty($order['coupon_code'])): ?>
                                            <div class="coupon-tag">
                                                <i class="fas fa-tag"></i>
                                                <?= htmlspecialchars($order['coupon_code']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </span>
                                    <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span><?= ($order['shipping_cost'] ?? 0) > 0 ? '$' . number_format($order['shipping_cost'], 2) : '<span class="text-green-600">FREE</span>' ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Tax</span>
                                <span>$<?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>$<?= number_format($order['total_amount'], 2) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Information -->
                    <div class="shipping-info-card" data-aos="fade-up">
                        <h2>Shipping Information</h2>
                        <div class="shipping-details">
                            <div class="address-section">
                                <h3>Delivery Address</h3>
                                <address>
                                    <?= htmlspecialchars($order['shipping_name']) ?><br>
                                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                                    <?= htmlspecialchars($order['shipping_city']) ?>,
                                    <?= htmlspecialchars($order['shipping_state']) ?>
                                    <?= htmlspecialchars($order['shipping_zip']) ?><br>
                                    <?= htmlspecialchars($order['shipping_country']) ?>
                                </address>
                            </div>

                            <?php // Check if tracking number exists and status is shipped or delivered ?>
                            <?php if ($order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                                <?php if (!empty($order['tracking_number'])): ?>
                                <div class="tracking-section mt-4 border-t pt-4">
                                    <h3>Tracking Information</h3>
                                    <p class="tracking-number">
                                        <i class="fas fa-truck"></i>
                                        Tracking Number: <?= htmlspecialchars($order['tracking_number']) ?>
                                         <?php if (!empty($order['carrier'])): ?>
                                             (<?= htmlspecialchars($order['carrier']) ?>)
                                         <?php endif; ?>
                                    </p>
                                    <?php
                                        // Basic URL generation for common carriers (can be expanded)
                                        $trackingUrl = '#'; // Default fallback
                                        $carrierLower = strtolower($order['carrier'] ?? '');
                                        if ($carrierLower === 'ups') {
                                            $trackingUrl = 'https://www.ups.com/track?tracknum=' . urlencode($order['tracking_number']);
                                        } elseif ($carrierLower === 'fedex') {
                                            $trackingUrl = 'https://www.fedex.com/fedextrack/?trknbr=' . urlencode($order['tracking_number']);
                                        } elseif ($carrierLower === 'usps') {
                                             $trackingUrl = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . urlencode($order['tracking_number']);
                                        }
                                        // Add more carriers as needed
                                    ?>
                                     <?php if ($trackingUrl !== '#'): ?>
                                     <a href="<?= htmlspecialchars($trackingUrl) ?>"
                                        class="btn-primary inline-block mt-2" target="_blank" rel="noopener noreferrer">
                                         Track Package
                                     </a>
                                     <?php endif; ?>
                                     <?php if (!empty($order['estimated_delivery'])): ?>
                                     <p class="estimated-delivery mt-2 text-sm text-gray-600">
                                         Estimated Delivery: <?= date('F j, Y', strtotime($order['estimated_delivery'])) ?>
                                     </p>
                                     <?php endif; ?>
                                </div>
                                <?php else: ?>
                                    <div class="tracking-section mt-4 border-t pt-4">
                                        <p class="text-gray-500 italic">Tracking information not yet available.</p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Additional Actions -->
                    <div class="order-actions-card" data-aos="fade-up">
                        <h2>Need Help?</h2>
                        <div class="action-buttons">
                            <a href="index.php?page=contact&order=<?= $order['id'] ?>" class="btn-secondary"> <!-- Changed to contact page -->
                                <i class="fas fa-question-circle"></i>
                                Contact Support
                            </a>
                            <?php // Allow cancellation only for 'processing' or 'paid' status ?>
                            <?php if (in_array($order['status'], ['processing', 'paid'])): ?>
                                <form action="index.php?page=account&section=orders&action=cancel" method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit"
                                            class="btn-danger"
                                            onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <i class="fas fa-times"></i>
                                        Cancel Order
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.coupon-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background-color: #f3f4f6;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    color: #374151;
    margin-left: 0.5rem;
}

.coupon-tag i {
    color: #059669;
}

.summary-row.discount {
    color: #059669;
}
/* Add styles for progress bar if not already in main CSS */
.order-progress { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; padding: 1rem 0;}
.progress-step { text-align: center; flex: 1; position: relative; }
.step-icon { width: 40px; height: 40px; border-radius: 50%; background-color: #e5e7eb; /* Gray */ color: #9ca3af; display: inline-flex; justify-content: center; align-items: center; margin-bottom: 0.5rem; border: 2px solid #e5e7eb; transition: all 0.3s ease; }
.step-label { font-size: 0.875rem; color: #6b7280; /* Gray */ }
.step-date { display: block; font-size: 0.75rem; color: #9ca3af; }
.progress-step.active .step-icon { background-color: #A0C1B1; color: #1A4D5A; border-color: #1A4D5A; }
.progress-step.active .step-label { color: #1A4D5A; font-weight: 500; }
.progress-step.active .step-date { color: #374151; }
.progress-step .fa-check { font-size: 1rem; }
.progress-line { height: 2px; background-color: #e5e7eb; flex-grow: 1; margin: 0 -1px; transform: translateY(21px); transition: background-color 0.3s ease; }
.progress-line.active { background-color: #1A4D5A; }
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

```

```php
<?php
// Updated: views/account/orders.php (Quiz History Link Updated)

require_once __DIR__ . '/../layout/header.php'; 
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
                                <a href="index.php?page=account&section=orders" class="active">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile">
                                    <i class="fas fa-user"></i> Profile Settings
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=quiz&action=history"> {/* MODIFIED LINK */}
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
                <h1 class="page-title" data-aos="fade-up">Order History</h1>

                <?php if (empty($orders)): ?>
                    <div class="empty-state text-center py-12" data-aos="fade-up">
                        <i class="fas fa-shopping-bag text-6xl text-gray-300 mb-4"></i>
                        <p class="text-xl text-gray-700 mb-6">You haven't placed any orders yet</p>
                        <a href="index.php?page=products" class="btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="orders-container">
                        <!-- Order Filter -->
                        <div class="order-filters flex flex-wrap gap-4 mb-6" data-aos="fade-up">
                            <select id="orderStatus" class="form-select flex-grow md:flex-grow-0">
                                <option value="">All Statuses</option>
                                <option value="pending_payment">Pending Payment</option>
                                <option value="paid">Paid</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="refunded">Refunded</option>
                                <option value="payment_failed">Payment Failed</option>
                            </select>

                            <select id="orderTime" class="form-select flex-grow md:flex-grow-0">
                                <option value="">All Time</option>
                                <option value="30">Last 30 Days</option>
                                <option value="90">Last 3 Months</option>
                                <option value="365">Last Year</option>
                            </select>
                        </div>

                        <!-- Orders List -->
                        <div class="orders-list space-y-6" data-aos="fade-up">
                            <?php foreach ($orders as $order): ?>
                                <?php
                                    // Ensure items is an array, default to empty if not set or invalid JSON
                                    $orderItems = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];
                                ?>
                                <div class="order-card bg-white rounded-lg shadow overflow-hidden">
                                    <div class="order-header bg-gray-50 px-6 py-3 border-b flex flex-wrap justify-between items-center gap-2">
                                        <div class="order-meta">
                                            <h3 class="font-semibold text-primary">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                            <span class="order-date text-sm text-gray-500">
                                                <?= date('F j, Y', strtotime($order['created_at'])) ?>
                                            </span>
                                        </div>
                                        <span class="order-status status-<?= htmlspecialchars($order['status']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
                                        </span>
                                    </div>

                                    <div class="order-items p-6 space-y-4">
                                        <?php if (empty($orderItems)): ?>
                                            <p class="text-gray-500 italic">No items found for this order.</p>
                                        <?php else: ?>
                                            <?php foreach ($orderItems as $item): ?>
                                                <div class="order-item flex items-center gap-4">
                                                    <img src="<?= htmlspecialchars($item['image_url'] ?? '/images/placeholder.jpg') ?>"
                                                         alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>" class="w-16 h-16 object-cover rounded border">
                                                    <div class="item-details flex-grow">
                                                        <h4 class="font-medium text-sm"><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></h4>
                                                        <p class="item-meta text-xs text-gray-500">
                                                            Quantity: <?= $item['quantity'] ?? 0 ?> |
                                                            Price: $<?= number_format($item['price_at_purchase'] ?? 0, 2) ?>
                                                        </p>
                                                    </div>
                                                    <div class="item-total text-sm font-semibold">
                                                        $<?= number_format(($item['quantity'] ?? 0) * ($item['price_at_purchase'] ?? 0), 2) ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="order-footer bg-gray-50 px-6 py-4 border-t flex flex-wrap justify-between items-center gap-4">
                                        <div class="order-summary text-sm">
                                            <span class="font-semibold">Total:</span>
                                            <span class="text-lg font-bold text-primary ml-1">$<?= number_format($order['total_amount'], 2) ?></span>
                                            <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                                                <span class="text-xs text-green-600 ml-2">(Includes -$<?= number_format($order['discount_amount'], 2) ?> discount)</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="order-actions flex gap-2">
                                            <a href="index.php?page=account&section=orders&id=<?= $order['id'] ?>"
                                               class="btn-secondary btn-sm">View Details</a>
                                            <?php if ($order['status'] === 'shipped' && !empty($order['tracking_number'])): ?>
                                                <?php
                                                    $trackingUrl = '#'; // Default fallback
                                                    $carrierLower = strtolower($order['carrier'] ?? '');
                                                    if ($carrierLower === 'ups') $trackingUrl = 'https://www.ups.com/track?tracknum=' . urlencode($order['tracking_number']);
                                                    elseif ($carrierLower === 'fedex') $trackingUrl = 'https://www.fedex.com/fedextrack/?trknbr=' . urlencode($order['tracking_number']);
                                                    elseif ($carrierLower === 'usps') $trackingUrl = 'https://tools.usps.com/go/TrackConfirmAction?tLabels=' . urlencode($order['tracking_number']);
                                                ?>
                                                <?php if ($trackingUrl !== '#'): ?>
                                                <a href="<?= htmlspecialchars($trackingUrl) ?>"
                                                   class="btn-primary btn-sm" target="_blank" rel="noopener noreferrer">
                                                    Track Package
                                                </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php /* Add Buy Again Button if needed
                                            <form action="index.php?page=cart&action=add" method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                                <?php // Requires adding all product IDs/Qtys to re-add the order ?>
                                                <button type="submit" class="btn-outline btn-sm">Buy Again</button>
                                            </form>
                                            */ ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if (isset($totalPages) && $totalPages > 1): ?>
                            <div class="pagination mt-8 flex justify-center" data-aos="fade-up">
                                <?php
                                    $currentPage = $currentPage ?? 1; // Default to 1 if not set
                                    $queryParams = $_GET; // Get current query parameters
                                    unset($queryParams['page'], $queryParams['p']); // Remove routing/pagination params
                                    $baseUrl = 'index.php?page=account&section=orders&' . http_build_query($queryParams);
                                ?>
                                <nav aria-label="Page navigation">
                                    <ul class="inline-flex items-center -space-x-px">
                                        <li>
                                            <a href="<?= $currentPage > 1 ? htmlspecialchars($baseUrl . '&p=' . ($currentPage - 1)) : '#' ?>"
                                               class="py-2 px-3 ml-0 leading-tight text-gray-500 bg-white rounded-l-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?= $currentPage <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                                <i class="fas fa-chevron-left"></i> <span class="sr-only">Previous</span>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li>
                                                <a href="<?= htmlspecialchars($baseUrl . '&p=' . $i) ?>"
                                                   class="py-2 px-3 leading-tight <?= ($i === $currentPage) ? 'z-10 text-primary bg-secondary border-primary hover:bg-secondary hover:text-primary' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700' ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        <li>
                                            <a href="<?= $currentPage < $totalPages ? htmlspecialchars($baseUrl . '&p=' . ($currentPage + 1)) : '#' ?>"
                                               class="py-2 px-3 leading-tight text-gray-500 bg-white rounded-r-lg border border-gray-300 hover:bg-gray-100 hover:text-gray-700 <?= $currentPage >= $totalPages ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                                <span class="sr-only">Next</span> <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Order filtering
    const orderStatusSelect = document.getElementById('orderStatus');
    const orderTimeSelect = document.getElementById('orderTime');

    function filterOrders() {
        const url = new URL(window.location.href);
        url.searchParams.delete('p'); // Reset to page 1 on filter change

        if (orderStatusSelect.value) {
            url.searchParams.set('status', orderStatusSelect.value);
        } else {
            url.searchParams.delete('status');
        }

        if (orderTimeSelect.value) {
            url.searchParams.set('time', orderTimeSelect.value);
        } else {
            url.searchParams.delete('time');
        }

        window.location.href = url.toString();
    }

    if (orderStatusSelect) orderStatusSelect.addEventListener('change', filterOrders);
    if (orderTimeSelect) orderTimeSelect.addEventListener('change', filterOrders);

    // Set initial filter values from URL parameters if they exist
    const params = new URLSearchParams(window.location.search);
    if (params.has('status') && orderStatusSelect) {
        orderStatusSelect.value = params.get('status');
    }
    if (params.has('time') && orderTimeSelect) {
        orderTimeSelect.value = params.get('time');
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

```

```php
<?php
// Updated: views/account/profile.php (v16.2 - Quiz History Link Updated, Added MY, SG countries)

require_once __DIR__ . '/../layout/header.php'; 
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
                                <a href="index.php?page=quiz&action=history"> {/* MODIFIED LINK */}
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
                                             <option value="MY" <?= (($userAddress['country'] ?? '') === 'MY') ? 'selected' : '' ?>>Malaysia</option>
                                             <option value="SG" <?= (($userAddress['country'] ?? '') === 'SG') ? 'selected' : '' ?>>Singapore</option>
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
             let
