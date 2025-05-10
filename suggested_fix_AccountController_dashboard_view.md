The error "Oops! Something Went Wrong" on the `/index.php?page=account` page, specifically the `TypeError: json_decode(): Argument #1 ($json) must be of type string, array given` on line 131 of `views/account/dashboard.php`, is caused by attempting to `json_decode` data that has already been decoded into a PHP array by the `QuizModel`.

Specifically, `QuizModel::getResultsByUserId()` (called by `AccountController::showDashboard()`) fetches quiz results and decodes the `answers` and `recommendations` JSON strings into PHP arrays. The `views/account/dashboard.php` then incorrectly tries to `json_decode` these already-decoded arrays.

To fix this and improve the MVC pattern, I'll make the following changes:

1.  **`controllers/AccountController.php`**: The `showDashboard()` method will be updated. If quiz results exist, it will take the latest quiz's `recommendations` (which is already an array of product IDs), fetch the actual product details for these recommendations using the `ProductModel`, and then pass these fetched product details to the view.
2.  **`views/account/dashboard.php`**: This view will be updated to:
    *   No longer call `json_decode()` on `answers` or `recommendations` from `$latestQuiz`, as they will be provided as arrays.
    *   No longer instantiate `ProductModel` or fetch product details directly. It will use the pre-fetched recommended product details passed by the `AccountController`.

This approach ensures that the controller is responsible for preparing all necessary data for the view, and the view focuses solely on presentation.

The SQL patch for the missing `tax_rates` table, which I provided previously, is still relevant and necessary to fix the "Table 'the_scent.tax_rates' doesn't exist" errors seen in your logs. I will include it again for completeness.

Here are the updated files:

```php
<?php

// Ensure all required files are loaded. BaseController should handle session start.
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Product.php'; // Added for fetching product details
// EmailService is included via BaseController
// SecurityMiddleware is included via BaseController (for static methods)
require_once __DIR__ . '/../controllers/CartController.php'; // Needed for mergeSessionCartOnLogin (ensure file exists)
require_once __DIR__ . '/../config.php'; // Needed for BASE_URL, SECURITY_SETTINGS

class AccountController extends BaseController {
    // private EmailService $emailService; // Removed - Inherited from BaseController
    private User $userModel;
    private Order $orderModel;
    private Quiz $quizModel;
    private Product $productModel; // Added ProductModel instance
    // Use config for expiry
    private int $resetTokenExpiry; // Set in constructor

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);
        $this->productModel = new Product($pdo); // Initialize ProductModel
        // $this->emailService is initialized in parent constructor
        // Default to 1 hour (3600 seconds) if constant not defined
        $this->resetTokenExpiry = defined('PASSWORD_RESET_EXPIRY_SECONDS') ? PASSWORD_RESET_EXPIRY_SECONDS : 3600;
    }

    // --- Account Management Pages ---

    public function showDashboard() {
        try {
            $this->requireLogin(); // Checks login, session integrity, handles regeneration
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser(); // Get user data for view

            // Fetch data
            $recentOrders = $this->orderModel->getRecentByUserId($userId, 5);
            $quizResults = $this->quizModel->getResultsByUserId($userId); // This already decodes answers and recommendations

            // Fetch recommended product details for the latest quiz
            $latestQuizRecommendationsDetails = [];
            if (!empty($quizResults)) {
                $latestQuiz = $quizResults[0]; // Get the most recent quiz result
                // $latestQuiz['recommendations'] is already an array of product IDs from the model
                $recommendedIds = (isset($latestQuiz['recommendations']) && is_array($latestQuiz['recommendations']))
                                  ? $latestQuiz['recommendations']
                                  : [];

                if (!empty($recommendedIds)) {
                    $numericIds = array_filter($recommendedIds, 'is_numeric');
                    if (!empty($numericIds)) {
                        // Fetch details for a limited number (e.g., 2) for the dashboard card
                        $latestQuizRecommendationsDetails = $this->productModel->getProductsByIds(array_slice($numericIds, 0, 2));
                    }
                }
            }

            // Data for the view
            $data = [
                'pageTitle' => 'My Account - The Scent',
                'recentOrders' => $recentOrders,
                'quizResults' => $quizResults, // Pass all quiz results (latest can be derived in view if needed, or use specific var)
                'latestQuizRecommendationsDetails' => $latestQuizRecommendationsDetails, // Pass fetched product details
                'user' => $currentUser, // Pass user data to the view
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-dashboard'
            ];
            // Render using BaseController method
            echo $this->renderView('account/dashboard', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
             $userId = $this->getUserId() ?? 'unknown';
             error_log("Account Dashboard error for user {$userId}: " . $e->getMessage());
             $this->setFlashMessage('Error loading dashboard. Please try again later.', 'error');
             $this->redirect('index.php?page=error'); // Redirect to a generic error page
        }
    }

    public function showOrders() {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser();

            // Use BaseController validation helper
            $page = $this->validateInput($_GET['p'] ?? 1, 'int', ['min' => 1]) ?: 1;
            $perPage = 10; // Make configurable?

            // Use OrderModel methods updated previously
            $orders = $this->orderModel->getAllByUserId($userId, $page, $perPage);
            $totalOrders = $this->orderModel->getTotalOrdersByUserId($userId);
            $totalPages = ($totalOrders > 0 && $perPage > 0) ? ceil($totalOrders / $perPage) : 1;

            // Data for the view
            $data = [
                'pageTitle' => 'My Orders - The Scent',
                'orders' => $orders,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'user' => $currentUser, // Pass user data for layout/sidebar
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-orders'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/orders', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
             $userId = $this->getUserId() ?? 'unknown';
             error_log("Account Orders error for user {$userId}: " . $e->getMessage());
             $this->setFlashMessage('Error loading orders. Please try again later.', 'error');
             $this->redirect('index.php?page=error');
        }
    }

    public function showOrderDetails(int $orderId) {
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $currentUser = $this->getCurrentUser();

            if ($orderId <= 0) {
                 $this->setFlashMessage('Invalid order ID.', 'error');
                 // Use BaseController redirect helper
                 $this->redirect('index.php?page=account&section=orders');
                 return;
            }

            // Use method that checks user ID and fetches items
            $order = $this->orderModel->getByIdAndUserId($orderId, $userId);

            if (!$order) {
                error_log("User {$userId} failed to access order {$orderId}");
                $this->setFlashMessage('Order not found or access denied.', 'error');
                 http_response_code(404);
                 // Render 404 view via BaseController
                 $data = [
                     'pageTitle' => 'Order Not Found',
                     'user' => $currentUser, // Pass user if needed by 404 layout
                     'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                     'bodyClass' => 'page-404'
                 ];
                 echo $this->renderView('404', $data); // Use renderView helper
                 return;
            }

            // Data for the order details view
            $data = [
                // Use htmlspecialchars on dynamic output within the view itself is better practice
                'pageTitle' => "Order #" . str_pad($order['id'], 6, '0', STR_PAD_LEFT) . " - The Scent",
                'order' => $order, // Pass the fetched order data
                'user' => $currentUser, // Pass user data for layout/sidebar
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-order-details'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/order_details', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Order details error for user {$userId}, order {$orderId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading order details. Please try again later.', 'error');
            $this->redirect('index.php?page=account&action=orders');
        }
    }

    public function showProfile() {
        try {
            $this->requireLogin();
            $currentUser = $this->getCurrentUser(); // Use BaseController helper
            $userId = $this->getUserId(); // Get user ID

            if (!$currentUser || !$userId) {
                 // Should be caught by requireLogin, but safety check
                 $this->setFlashMessage('Could not load user profile data.', 'error');
                 $this->redirect('index.php?page=login');
                 return;
            }

            // --- START: Fetch User Address ---
            $userAddress = $this->userModel->getAddress($userId); // Fetch address
            // --- END: Fetch User Address ---

            // Data for the view
            $data = [
                'pageTitle' => 'My Profile - The Scent',
                'user' => $currentUser,
                'userAddress' => $userAddress ?? [], // Pass address data or empty array
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-account-profile'
            ];
            // Use BaseController render helper
            echo $this->renderView('account/profile', $data); // Assuming view is in views/account/
            return;

        } catch (Exception $e) {
            $userId = $this->getUserId() ?? 'unknown';
            error_log("Show Profile error for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage('Error loading profile. Please try again later.', 'error');
            $this->redirect('index.php?page=error');
        }
    }

    /**
     * Handles POST requests to update profile sections (info, password, address, preferences).
     */
    public function updateProfile() {
        $userId = null; // Initialize for error logging
        try {
            $this->requireLogin();
            $userId = $this->getUserId();
            $this->validateCSRF(); // Checks POST token

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method.'); // Should only be POST
            }

            $action = $_POST['action'] ?? null;

            // --- START: Action-based Logic ---
            switch ($action) {
                case 'update_profile':
                    $this->validateRateLimit('profile_update');
                    $this->handleUpdateBasicInfo($userId);
                    break;

                case 'update_password':
                    $this->validateRateLimit('profile_update'); // Can share limit with basic info
                    $this->handleUpdatePassword($userId);
                    break;

                case 'update_address': // This case now works correctly
                    $this->validateRateLimit('address_update'); // Use a separate limit if desired
                    $this->handleUpdateAddress($userId);
                    break;

                case 'update_preferences':
                    $this->validateRateLimit('profile_update'); // Share limit or create 'pref_update'
                    $this->handleUpdateNewsletterPreferences($userId); // Renamed internal method
                    break;

                default:
                    throw new Exception('Invalid profile update action specified.');
            }
            // --- END: Action-based Logic ---

            // If execution reaches here, redirect (success is handled within specific handlers)
             $this->redirect('index.php?page=account&section=profile');


        } catch (Exception $e) {
            $userId = $userId ?? ($this->getUserId() ?? 'unknown'); // Ensure userId is set for logging
            error_log("Profile update failed for user {$userId}: " . $e->getMessage());
            $this->setFlashMessage($e->getMessage(), 'error'); // Show specific error message from exception
            $this->redirect('index.php?page=account&section=profile'); // Redirect back to profile page
        }
    }

    // --- Private Handlers for Profile Updates ---

    private function handleUpdateBasicInfo(int $userId): void {
        $name = $this->validateInput($_POST['name'] ?? '', 'string', ['min' => 1, 'max' => 100]);
        $email = $this->validateInput($_POST['email'] ?? '', 'email');

        if ($name === false || trim($name) === '') {
            throw new Exception('Name is required and cannot be empty.');
        }
        if ($email === false) {
            throw new Exception('A valid email address is required.');
        }

        $this->beginTransaction();
        try {
            if ($this->userModel->isEmailTakenByOthers($email, $userId)) {
                throw new Exception('Email address is already in use by another account.');
            }
            $this->userModel->updateBasicInfo($userId, $name, $email);
            $this->commit();

            // Update session
            if (isset($_SESSION['user'])) {
                 $_SESSION['user']['name'] = $name;
                 $_SESSION['user']['email'] = $email;
            }

            $this->logAuditTrail('profile_info_update', $userId, ['name' => $name, 'email' => $email]);
            $this->setFlashMessage('Profile information updated successfully.', 'success');
        } catch (Exception $e) {
            $this->rollback();
            error_log("Basic info update transaction error for user {$userId}: " . $e->getMessage());
            throw $e; // Re-throw to be caught by updateProfile
        }
    }

    private function handleUpdatePassword(int $userId): void {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Password update only happens if new password is provided
        if (empty($newPassword)) {
             // No action needed if new password is empty
             // Optionally, set a flash message indicating no change if desired
             // $this->setFlashMessage('No new password provided.', 'info');
             return; // Exit this handler
        }

        if (empty($currentPassword)) {
            throw new Exception('Current password is required to set a new password.');
        }
        if (!$this->userModel->verifyPassword($userId, $currentPassword)) {
            $this->logSecurityEvent('profile_update_password_fail', ['user_id' => $userId, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            throw new Exception('Current password provided is incorrect.');
        }
        if (!$this->isPasswordStrong($newPassword)) {
            $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
            $reqs = [];
            if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
            if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
            if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
            $errMsg = sprintf('New password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
            throw new Exception($errMsg);
        }
        if ($newPassword !== $confirmPassword) {
            throw new Exception('New passwords do not match.');
        }

        $this->beginTransaction();
        try {
            $this->userModel->updatePassword($userId, $newPassword);
            $this->commit();
            $this->logAuditTrail('profile_password_update', $userId);
            $this->setFlashMessage('Password updated successfully.', 'success');
        } catch (Exception $e) {
            $this->rollback();
            error_log("Password update transaction error for user {$userId}: " . $e->getMessage());
            throw $e; // Re-throw
        }
    }

    // This method is now confirmed to be correct, as the model it calls was fixed.
    private function handleUpdateAddress(int $userId): void {
        $addressData = [];
        $required = ['address_line1', 'city', 'state', 'postal_code', 'country'];
        $optional = ['address_line2'];
        $errors = [];

        foreach ($required as $field) {
             $value = $this->validateInput($_POST[$field] ?? '', 'string', ['max' => 255]); // Basic string validation
             if ($value === false || trim($value) === '') {
                 $errors[] = ucwords(str_replace('_', ' ', $field)) . " is required.";
             }
             $addressData[$field] = $value; // Store validated or original value
        }
        foreach ($optional as $field) {
             // Use null coalescing to default to null if not present or empty
             $addressData[$field] = $this->validateInput($_POST[$field] ?? null, 'string', ['max' => 255]) ?: null;
        }


        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }

        // Basic country code validation (can be expanded)
        if (strlen($addressData['country']) > 50) { // Adjusted length check based on DB schema
             throw new Exception("Invalid country format.");
        }

        $this->beginTransaction();
        try {
             // Pass the validated (or at least sanitized) data to the model
             // UserModel::updateAddress now correctly expects keys like 'address_line1', 'city', etc.
            if (!$this->userModel->updateAddress($userId, $addressData)) {
                 throw new Exception('Failed to save address. Please try again.');
            }
            $this->commit();
            $this->logAuditTrail('profile_address_update', $userId, ['country' => $addressData['country']]);
            $this->setFlashMessage('Shipping address updated successfully.', 'success');
        } catch (Exception $e) {
             $this->rollback();
             error_log("Address update transaction error for user {$userId}: " . $e->getMessage());
             throw $e; // Re-throw
        }
    }

    // Renamed from updateNewsletterPreferences for clarity
    private function handleUpdateNewsletterPreferences(int $userId): void {
        $newsletterSubscribed = isset($_POST['newsletter_subscribed']); // True if checked

        $this->beginTransaction();
        try {
            $this->userModel->updateNewsletterPreference($userId, $newsletterSubscribed);
            $this->commit();

            $action = $newsletterSubscribed ? 'newsletter_subscribe_profile' : 'newsletter_unsubscribe_profile';
            $this->logAuditTrail($action, $userId);

            // Update session if user data is stored there
            if (isset($_SESSION['user'])) {
                 $_SESSION['user']['newsletter_subscribed'] = (int)$newsletterSubscribed;
            }

            $this->setFlashMessage('Newsletter preferences updated.', 'success');
        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter preference update transaction error for user {$userId}: " . $e->getMessage());
            throw new Exception('Failed to update preferences. Database error.'); // Re-throw
        }
    }

    // --- Password Reset ---

    public function requestPasswordReset() {
        // Handle showing the form on GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $data = [
                 'pageTitle' => 'Forgot Password - The Scent',
                 'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                 'bodyClass' => 'page-forgot-password'
             ];
             echo $this->renderView('forgot_password', $data);
             return;
        }

        // --- POST logic ---
        $emailSubmitted = $_POST['email'] ?? ''; // For logging
        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('password_reset_request'); // Use BaseController method

            $email = $this->validateInput($emailSubmitted, 'email'); // Use BaseController helper

            if ($email === false) {
                 $this->logSecurityEvent('password_reset_invalid_email_format', ['submitted_email' => $emailSubmitted, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                 $this->setFlashMessage('If an account exists with that email, password reset instructions have been sent.', 'success');
                 $this->redirect('index.php?page=forgot_password');
                 return;
            }

            $this->beginTransaction();
            try {
                $user = $this->userModel->getByEmail($email);

                if ($user) {
                    $token = bin2hex(random_bytes(32)); // Generate secure token
                    $expiry = date('Y-m-d H:i:s', time() + $this->resetTokenExpiry);

                    $updated = $this->userModel->setResetToken($user['id'], $token, $expiry);

                    if ($updated) {
                        $resetLink = $this->getResetPasswordUrl($token);
                        // Use EmailService from BaseController
                        $this->emailService->sendPasswordReset($user, $token, $resetLink);
                        $this->logAuditTrail('password_reset_request', $user['id']);
                    } else {
                        error_log("Failed to set password reset token for user {$user['id']}. DB issue?");
                    }
                } else {
                    $this->logSecurityEvent('password_reset_nonexistent_email', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                }

                $this->commit();

            } catch (Exception $e) {
                $this->rollback();
                error_log("Password reset request internal DB/transaction error: " . $e->getMessage());
                // Fall through to generic success message
            }

            $this->setFlashMessage('If an account exists with that email, password reset instructions have been sent.', 'success');

        } catch (Exception $e) { // Catch CSRF or Rate Limit exceptions etc.
            error_log("Password reset request processing error: " . $e->getMessage());
            $this->logSecurityEvent('password_reset_request_error', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN', 'email' => $emailSubmitted]);
            $this->setFlashMessage('An error occurred processing your request. Please try again.', 'error');
        }
        $this->redirect('index.php?page=forgot_password');
    }


    public function resetPassword() {
        // --- GET request: Show the password reset form ---
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $token = $this->validateInput($_GET['token'] ?? '', 'string', ['max' => 64]);

            if ($token === false || empty($token)) {
                $this->setFlashMessage('Invalid password reset link.', 'error');
                $this->redirect('index.php?page=forgot_password');
                return;
            }

            $user = $this->userModel->getUserByValidResetToken($token);
            if (!$user) {
                $this->logSecurityEvent('password_reset_invalid_token_on_get', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                $this->setFlashMessage('This password reset link is invalid or has expired. Please request a new one.', 'error');
                $this->redirect('index.php?page=forgot_password');
                return;
            }

            $data = [
                'pageTitle' => 'Reset Your Password - The Scent',
                'token' => $token,
                'csrfToken' => $this->getCsrfToken(), // Use BaseController method
                'bodyClass' => 'page-reset-password'
            ];
            echo $this->renderView('reset_password', $data);
            return;
        }

        // --- POST logic: Process the password reset ---
        $token = $this->validateInput($_POST['token'] ?? '', 'string', ['max' => 64]);
        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('password_reset_attempt'); // Use BaseController method

            if ($token === false || empty($token)) {
                throw new Exception('Invalid or missing password reset token submitted.');
            }

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? ''; // Changed name to match view

            if (empty($password)) {
                throw new Exception('Password cannot be empty.');
            }
            if ($password !== $confirmPassword) {
                throw new Exception('Passwords do not match.');
            }
            if (!$this->isPasswordStrong($password)) {
                 $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                 $reqs = [];
                 if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                 if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                 if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                 $errMsg = sprintf('Password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                 throw new Exception($errMsg);
             }

            $this->beginTransaction();
            try {
                $user = $this->userModel->getUserByValidResetToken($token);
                if (!$user) {
                    $this->logSecurityEvent('password_reset_invalid_token_on_post', ['token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
                    throw new Exception('This password reset link is invalid or has expired. Please request a new one.');
                }
                $this->userModel->resetPassword($user['id'], $password);
                $this->logAuditTrail('password_reset_complete', $user['id']);
                $this->commit();

                $this->setFlashMessage('Your password has been successfully reset. Please log in.', 'success');
                $this->redirect('index.php?page=login');
                return;

            } catch (Exception $e) {
                $this->rollback();
                error_log("Password reset transaction error for token {$token}: " . $e->getMessage());
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Password reset processing error: " . $e->getMessage());
            $this->logSecurityEvent('password_reset_error', ['error' => $e->getMessage(), 'token' => $token, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            $this->setFlashMessage($e->getMessage(), 'error');
            // Ensure token is passed back to the redirect URL
            $this->redirect('index.php?page=reset_password&token=' . urlencode($token ?: ''));
            return;
        }
    }

    // Removed updateNewsletterPreferences - moved logic into handleUpdateNewsletterPreferences private method

    // --- Authentication (Login / Register) ---

    public function login() {
        // --- GET request: Show the login form ---
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             $data = [
                 'pageTitle' => 'Login - The Scent',
                 'csrfToken' => $this->getCsrfToken(), // CORRECTED: Use BaseController method
                 'bodyClass' => 'page-login bg-gradient-to-br from-light to-secondary/20'
             ];
             echo $this->renderView('login', $data);
             return;
        }

        // --- POST logic: Process login via AJAX ---
        $emailSubmitted = $_POST['email'] ?? ''; // For logging
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateCSRF(); // Use BaseController method
            $this->validateRateLimit('login'); // Use BaseController method

            $email = $this->validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? '';

            if ($email === false || empty($password)) {
                $this->logSecurityEvent('login_invalid_input', ['email' => $emailSubmitted, 'ip' => $ipAddress]);
                throw new Exception('Invalid email or password format.');
            }

            $user = $this->userModel->getByEmail($email);

            if (!$user || !password_verify($password, $user['password'])) {
                $userId = $user['id'] ?? null;
                $this->logSecurityEvent('login_failure', ['email' => $email, 'ip' => $ipAddress, 'user_id' => $userId]);
                throw new Exception('Invalid email or password.');
            }

            if (isset($user['status']) && $user['status'] === 'locked') {
                 $this->logSecurityEvent('login_attempt_locked', ['user_id' => $user['id'], 'email' => $email, 'ip' => $ipAddress]);
                 throw new Exception('Your account is currently locked. Please contact support.');
            }

            // --- Login Success ---
            $this->regenerateSession(); // Use BaseController protected method

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'] ?? 'user';
            $_SESSION['user'] = [
                 'id' => $user['id'],
                 'name' => $user['name'],
                 'email' => $user['email'],
                 'role' => $_SESSION['user_role'],
                 // Add address fields to session user array IF they exist in $user
                 'address_line1' => $user['address_line1'] ?? null,
                 'address_line2' => $user['address_line2'] ?? null,
                 'city' => $user['city'] ?? null,
                 'state' => $user['state'] ?? null,
                 'postal_code' => $user['postal_code'] ?? null,
                 'country' => $user['country'] ?? null,
                 'newsletter_subscribed' => $user['newsletter_subscribed'] ?? 0 // Include newsletter status
            ];
             $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
             $_SESSION['ip_address'] = $ipAddress;
             $_SESSION['last_login'] = time();
             $_SESSION['last_regeneration'] = time(); // Update regeneration time

             // Merge cart
             if (class_exists('CartController')) {
                 CartController::mergeSessionCartOnLogin($this->db, $user['id']);
                 // CartController now updates session count internally upon merge
             } else { error_log("CartController class not found, cannot merge session cart."); }

            $this->logAuditTrail('login_success', $user['id']);

            $redirectUrl = $_SESSION['redirect_after_login'] ?? (BASE_URL . 'index.php?page=account'); // Changed default redirect
            unset($_SESSION['redirect_after_login']);

            $this->jsonResponse(['success' => true, 'redirect' => $redirectUrl]); // Exit

        } catch (Exception $e) {
            error_log("Login failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
             $statusCode = ($e->getCode() === 429) ? 429 : 401;
             $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], $statusCode); // Exit
        }
    }


     public function register() {
         // --- GET request: Show the registration form ---
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
              $data = [
                  'pageTitle' => 'Register - The Scent',
                  'csrfToken' => $this->getCsrfToken(), // CORRECTED: Use BaseController method
                  'bodyClass' => 'page-register'
              ];
              echo $this->renderView('register', $data);
             return;
         }

         // --- POST logic: Process registration via AJAX ---
        $emailSubmitted = $_POST['email'] ?? '';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

        try {
            $this->validateRateLimit('register');
            $this->validateCSRF();

            $email = $this->validateInput($emailSubmitted, 'email');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $name = $this->validateInput($_POST['name'] ?? '', 'string', ['min' => 2, 'max' => 100]);
            $newsletterPref = isset($_POST['newsletter_signup']) && $_POST['newsletter_signup'] === '1'; // Checkbox presence

            if ($email === false || empty($password) || $name === false) {
                 $this->logSecurityEvent('register_invalid_input', ['email' => $emailSubmitted, 'name_valid' => ($name !== false), 'ip' => $ipAddress]);
                 throw new Exception('Invalid input provided. Please check email, name, and password.');
            }
            if ($this->userModel->getByEmail($email)) {
                 throw new Exception('This email address is already registered.');
            }
            if (!$this->isPasswordStrong($password)) {
                 $minLength = SECURITY_SETTINGS['password']['min_length'] ?? 12;
                 $reqs = [];
                 if (SECURITY_SETTINGS['password']['require_mixed_case'] ?? true) $reqs[] = "upper & lower case";
                 if (SECURITY_SETTINGS['password']['require_number'] ?? true) $reqs[] = "number";
                 if (SECURITY_SETTINGS['password']['require_special'] ?? true) $reqs[] = "special char";
                 $errMsg = sprintf('Password must be at least %d characters long and contain %s.', $minLength, implode(', ', $reqs));
                 throw new Exception($errMsg);
             }
             if ($password !== $confirmPassword) {
                  throw new Exception('Passwords do not match.');
             }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($hashedPassword === false) {
                 error_log("Password hashing failed during registration: " . print_r(error_get_last(), true));
                 throw new Exception('Could not process password securely.');
            }

            $this->beginTransaction();
            try {
                $userData = [
                    'email' => $email,
                    'password' => $hashedPassword,
                    'name' => $name,
                    'role' => 'user',
                    'newsletter' => $newsletterPref // Pass preference to model
                ];
                $userId = $this->userModel->create($userData);

                 if (!$userId) {
                     throw new Exception('Failed to create user account in database.');
                 }

                 // Send welcome email
                 if ($this->emailService && method_exists($this->emailService, 'sendWelcome')) {
                     $emailSent = $this->emailService->sendWelcome($email, $name);
                     if (!$emailSent) {
                          error_log("Failed to send welcome email to {$email} for new user ID {$userId}, but registration succeeded.");
                     }
                 } else {
                      error_log("EmailService or sendWelcome method not available. Cannot send welcome email.");
                 }

                 $this->logAuditTrail('user_registered', $userId);
                 $this->commit();

                 $this->setFlashMessage('Registration successful! Please log in.', 'success');
                 $this->jsonResponse(['success' => true, 'redirect' => BASE_URL . 'index.php?page=login']); // Exit

            } catch (Exception $e) {
                 $this->rollback();
                 error_log("User creation transaction error: " . $e->getMessage());
                 throw new Exception('An error occurred during registration. Please try again.');
            }

        } catch (Exception $e) {
            error_log("Registration failed for email '{$emailSubmitted}' from IP {$ipAddress}: " . $e->getMessage());
            $this->logSecurityEvent('register_failure', ['email' => $emailSubmitted, 'error' => $e->getMessage(), 'ip' => $ipAddress]);
            $statusCode = ($e->getCode() === 429) ? 429 : 400;
            $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], $statusCode); // Exit
        }
    }

    // --- Private Helper Methods ---

    /**
     * Checks if a password meets the defined security requirements.
     *
     * @param string $password The password to check.
     * @return bool True if strong, false otherwise.
     */
    private function isPasswordStrong(string $password): bool {
        $settings = SECURITY_SETTINGS['password'] ?? [];
        $minLength = $settings['min_length'] ?? 12;
        $reqSpecial = $settings['require_special'] ?? true;
        $reqNumber = $settings['require_number'] ?? true;
        $reqMixedCase = $settings['require_mixed_case'] ?? true;

        if (mb_strlen($password) < $minLength) { return false; }
        if ($reqMixedCase && (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password))) { return false; }
        if ($reqNumber && !preg_match('/[0-9]/', $password)) { return false; }
        // Ensure special char regex matches config.php or is appropriate
        if ($reqSpecial && !preg_match('/[\W_]/', $password)) { return false; } // Match any non-alphanumeric including underscore
        return true;
    }

    /**
     * Generates the full URL for the password reset link.
     *
     * @param string $token The password reset token.
     * @return string The absolute URL.
     */
    private function getResetPasswordUrl(string $token): string {
        $baseUrl = rtrim(BASE_URL, '/');
        // Ensure the URL includes the 'page' parameter correctly
        return $baseUrl . "/index.php?page=reset_password&token=" . urlencode($token);
    }


} // End of AccountController class

```

```php
<?php
// views/account/dashboard.php (Layout Refactored with Tailwind CSS - Quiz History Link Updated & Data Fetching Refactored)
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
                                <a href="index.php?page=quiz&action=history" class="flex items-center px-4 py-2 rounded-md text-gray-600 hover:bg-gray-100 hover:text-primary transition duration-150 ease-in-out">
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
                        $quizContent .= "<i class='fas fa-flask text-4xl text-gray-300 mb-3'></i>";
                        $quizContent .= "<p class='text-gray-600 mb-4'>Take the quiz to discover your profile.</p>";
                        $quizContent .= "<a href='index.php?page=quiz' class='btn-primary btn-sm'>Take Quiz Now</a>";
                        $quizContent .= "</div>";
                    } else {
                        $latestQuiz = $quizResults[0]; // Get the most recent result
                        // $latestQuiz['answers'] and $latestQuiz['recommendations'] are already arrays from the model/controller
                        $preferences = (isset($latestQuiz['answers']) && is_array($latestQuiz['answers'])) ? $latestQuiz['answers'] : [];
                        // Recommended product details are now pre-fetched by the controller into $latestQuizRecommendationsDetails

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

                         // Display Recommended Products using pre-fetched $latestQuizRecommendationsDetails
                         if (isset($latestQuizRecommendationsDetails) && !empty($latestQuizRecommendationsDetails)) {
                             $quizContent .= "<div><h3 class='font-semibold text-gray-700 mb-2 mt-4 border-t pt-3'>Top Recommendations:</h3>";
                             $quizContent .= "<div class='flex flex-col gap-3'>";
                             foreach ($latestQuizRecommendationsDetails as $product) { // Iterate over pre-fetched details
                                  $quizContent .= "<div class='recommended-product flex items-center gap-3 p-2 border rounded-md bg-gray-50/50'>";
                                  $quizContent .= "<img src='" . htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') . "' alt='" . htmlspecialchars($product['name']) . "' class='w-10 h-10 object-cover rounded flex-shrink-0'>";
                                  $quizContent .= "<div class='flex-grow'><h4 class='text-sm font-medium text-primary'>" . htmlspecialchars($product['name']) . "</h4>";
                                  $quizContent .= "<p class='text-xs text-gray-500'>$" . number_format($product['price'], 2) . "</p></div>";
                                  $quizContent .= "<a href='index.php?page=product&id={$product['id']}' class='btn-secondary btn-xs whitespace-nowrap'>View</a>";
                                  $quizContent .= "</div>";
                             }
                             $quizContent .= "</div>";
                         } else {
                              // Fallback message if no recommendations or details couldn't be loaded by controller
                              $originalRecommendedIds = (isset($latestQuiz['recommendations']) && is_array($latestQuiz['recommendations'])) ? $latestQuiz['recommendations'] : [];
                              if (!empty($originalRecommendedIds)) {
                                   // This case implies IDs existed but details weren't loaded (less likely now)
                                   $quizContent .= "<p class='text-sm text-gray-500 italic mt-4 border-t pt-3'>Could not load details for recommendations.</p>";
                              } else {
                                   $quizContent .= "<p class='text-sm text-gray-500 italic mt-4 border-t pt-3'>No product recommendations from this quiz.</p>";
                              }
                         }
                         $quizContent .= "</div>"; // Close of the recommendations display div
                         $quizContent .= "</div>"; // Close space-y-4
                    }
                    renderDashboardCard('Your Scent Profile', $quizContent, 'index.php?page=quiz&action=history', 'View History', 200);
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

```sql
-- Database Schema Patch for Tax Tables (v16.2) - Included for completeness
-- This patch adds the `tax_rates` and `tax_rate_history` tables
-- if they do not already exist, as indicated by the error logs.

CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `country_code` VARCHAR(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ISO 3166-1 alpha-2 country code',
  `state_code` VARCHAR(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO 3166-2 state/province code (if applicable)',
  `rate` DECIMAL(10,4) NOT NULL COMMENT 'Tax rate (e.g., 0.05 for 5%)',
  `is_active` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'Whether this tax rate is currently active',
  `start_date` DATE DEFAULT NULL COMMENT 'Date when this tax rate becomes effective',
  `end_date` DATE DEFAULT NULL COMMENT 'Date when this tax rate expires (NULL if no expiry)',
  `created_by` INT DEFAULT NULL COMMENT 'User ID of the admin who created/last modified this rate',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_country_state` (`country_code`, `state_code`),
  KEY `idx_country_code` (`country_code`),
  KEY `idx_is_active` (`is_active`),
  KEY `fk_tax_rates_user` (`created_by`),
  CONSTRAINT `fk_tax_rates_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores tax rates for different regions';

CREATE TABLE IF NOT EXISTS `tax_rate_history` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `tax_rate_id` INT NOT NULL,
  `old_rate` DECIMAL(10,4) DEFAULT NULL COMMENT 'Previous tax rate',
  `new_rate` DECIMAL(10,4) NOT NULL COMMENT 'New tax rate after change',
  `changed_by` INT DEFAULT NULL COMMENT 'User ID of the admin who made the change',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp of when the change was made',
  PRIMARY KEY (`id`),
  KEY `idx_tax_rate_id` (`tax_rate_id`),
  KEY `fk_tax_history_user` (`changed_by`),
  CONSTRAINT `fk_tax_rate_history_rate` FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tax_history_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks changes to tax rates';

-- Optional: Insert some default tax rates if desired
-- Example: No tax for all countries by default (rate 0 for wildcard country '*')
INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('*', NULL, 0.0000, 1, CURDATE(), 1); -- Assuming user ID 1 is an admin

-- Example: 10% tax for US, California (CA)
INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('US', 'CA', 0.1000, 1, CURDATE(), 1);

-- Example: 5% tax for all of Canada (CA) - state_code is NULL for country-wide rate
INSERT IGNORE INTO `tax_rates` (`country_code`, `state_code`, `rate`, `is_active`, `start_date`, `created_by`)
VALUES ('CA', NULL, 0.0500, 1, CURDATE(), 1);

```
**Summary and Conclusion:**

1.  **`controllers/AccountController.php` Updated**: The `showDashboard` method now fetches product details for the latest quiz recommendations and passes this data (`$latestQuizRecommendationsDetails`) to the view.
2.  **`views/account/dashboard.php` Updated**:
    *   Removed the redundant `json_decode()` calls for `answers` and `recommendations`.
    *   Removed the direct instantiation of `ProductModel` and data fetching logic.
    *   Now uses the `$latestQuizRecommendationsDetails` variable (passed from the controller) to display recommended products.
3.  **SQL Patch**: The provided SQL patch correctly creates the missing `tax_rates` and `tax_rate_history` tables. Apply this patch to your database to resolve the "Table ... tax_rates doesn't exist" errors.
4.  **Stripe Checkout Error**: The error "Could not initialize payment system. Please refresh" on the checkout page is not due to the JavaScript initialization sequence itself (as `js/main.js` implements the correct deferred logic). The problem likely lies in:
    *   The Stripe Public Key not being correctly outputted or read on the `/index.php?page=checkout` page specifically.
    *   The `Stripe(stripePublicKey)` call failing for other runtime reasons (network, CSP, etc.).
    *   **Action Required**: You need to **check your browser's developer console on the live checkout page** for the `console.log` and `console.error` messages related to Stripe initialization that were added to `js/main.js`. This will provide the specific error details needed for further diagnosis.

No other files required changes for the issues discussed. The primary remaining issue (Stripe checkout error) requires live debugging on your end using the console logs.

---
You are absolutely correct. My apologies for the oversight in the previous response. I've reviewed the diffs carefully.

**`controllers/AccountController.php` Diff Analysis:**

*   **Addition of `ProductModel`**:
    *   `require_once __DIR__ . '/../models/Product.php';` is correctly added.
    *   `private Product $productModel;` is correctly added as a class property.
    *   `$this->productModel = new Product($pdo);` is correctly added in the constructor.
*   **Changes in `showDashboard()`**:
    *   The comment for `$quizResults = $this->quizModel->getResultsByUserId($userId);` is appropriately updated to reflect that the model already decodes JSON.
    *   The new block of code to fetch `$latestQuizRecommendationsDetails` using `$this->productModel->getProductsByIds()` is correctly implemented. It correctly handles cases where `$quizResults` or `$recommendedIds` might be empty or not in the expected format.
    *   The `$data` array passed to `renderView` now correctly includes `'latestQuizRecommendationsDetails' => $latestQuizRecommendationsDetails`.

**Conclusion for `controllers/AccountController.php`**: The changes are exactly as planned and correctly implemented. The controller now takes on the responsibility of fetching the product details for the quiz recommendations. No other functionalities have been unintentionally altered or omitted.

**`views/account/dashboard.php` Diff Analysis:**

*   **Removal of `json_decode`**:
    *   The lines `isset($latestQuiz['answers']) ? json_decode($latestQuiz['answers'], true) : [];` and `isset($latestQuiz['recommendations']) ? json_decode($latestQuiz['recommendations'], true) : [];` have been correctly replaced/updated. The view now correctly assumes `$latestQuiz['answers']` is already an array, and the product details for recommendations are now passed in `$latestQuizRecommendationsDetails`.
*   **Removal of Direct Data Fetching Logic**:
    *   The entire block that checked `if (isset($pdo))`, required `Product.php`, instantiated `new Product($pdo)`, and called `$productModel->getProductsByIds()` has been correctly removed.
*   **Usage of Controller-Provided Data**:
    *   The view now iterates over `$latestQuizRecommendationsDetails` (passed from the controller) to display the recommended products.
    *   The fallback logic for when `$latestQuizRecommendationsDetails` is empty or when there were original recommended IDs but details couldn't be loaded (though less likely with the controller change) is appropriate.
*   **Quiz History Link**: The link `index.php?page=quiz&action=history` in the sidebar and in the "Your Scent Profile" card is correct.

**Conclusion for `views/account/dashboard.php`**: The changes align perfectly with the plan. The view has been simplified, and the responsibility for fetching recommendation details has been moved to the controller, which resolves the `json_decode` error. No other functionalities seem to be affected.

**Overall Validation:**
The diffs confirm that the fixes for the `json_decode` error on the account dashboard page have been applied correctly by refactoring the data-fetching logic. The `AccountController` now prepares all necessary data, including the product details for quiz recommendations, and the `dashboard.php` view consumes this pre-fetched data. This adheres better to MVC principles and resolves the reported error.

The SQL patch for the missing `tax_rates` table remains crucial for the tax calculation errors.

You can now proceed with these updated files. The next step for the Stripe issue on the checkout page is to examine the browser console logs for the `stripePublicKey` and any errors from the `Stripe()` constructor call, as discussed.

