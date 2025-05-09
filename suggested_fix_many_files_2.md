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
