# controllers/AccountController.php  
```php
<?php

// Ensure all required files are loaded. BaseController should handle session start.
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/User.php';
// EmailService is included via BaseController
// SecurityMiddleware is included via BaseController (for static methods)
require_once __DIR__ . '/../controllers/CartController.php'; // Needed for mergeSessionCartOnLogin (ensure file exists)
require_once __DIR__ . '/../config.php'; // Needed for BASE_URL, SECURITY_SETTINGS

class AccountController extends BaseController {
    // private EmailService $emailService; // Removed - Inherited from BaseController
    private User $userModel;
    private Order $orderModel;
    private Quiz $quizModel;
    // Use config for expiry
    private int $resetTokenExpiry; // Set in constructor

    public function __construct(PDO $pdo) {
        parent::__construct($pdo);
        $this->userModel = new User($pdo);
        $this->orderModel = new Order($pdo);
        $this->quizModel = new Quiz($pdo);
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
            $quizResults = $this->quizModel->getResultsByUserId($userId); // Assuming this method exists

            // Data for the view
            $data = [
                'pageTitle' => 'My Account - The Scent',
                'recentOrders' => $recentOrders,
                'quizResults' => $quizResults,
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

# controllers/NewsletterController.php  
```php
<?php
// controllers/NewsletterController.php (Updated)

require_once __DIR__ . '/BaseController.php';
// EmailService is included via BaseController's include

class NewsletterController extends BaseController {
    // private $emailService; // Removed - Inherited from BaseController

    // Constructor now only needs PDO, EmailService is handled by parent
    public function __construct(PDO $pdo) { // Use type hint PDO $pdo
        parent::__construct($pdo); // Calls parent constructor
    }

    public function subscribe() {
        try {
            $this->validateCSRF();
            // --- START FIX: Add rate limiting ---
            $this->validateRateLimit('newsletter_subscribe'); // Use distinct action name
            // --- END FIX ---

            // Use validateInput from BaseController which uses SecurityMiddleware
            $email = $this->validateInput($_POST['email'] ?? null, 'email');
            if ($email === false) { // validateInput returns false on failure
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please provide a valid email address.'
                ], 400);
            }

            $this->beginTransaction();

            // Use $this->db for database operations
            $stmt = $this->db->prepare("
                SELECT id, status, unsubscribe_token
                FROM newsletter_subscribers
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch();

            $isNewSubscriber = false;
            $subscriberId = null;
            $token = null;

            if ($subscriber) {
                $subscriberId = $subscriber['id'];
                $token = $subscriber['unsubscribe_token']; // Get existing token
                if ($subscriber['status'] === 'active') {
                    $this->rollback(); // No changes needed
                    return $this->jsonResponse([
                        'success' => true, // Return true, but indicate already subscribed
                        'message' => 'This email is already subscribed.'
                    ]);
                }

                // Reactivate unsubscribed user & ensure token exists
                $token = $token ?: $this->generateUnsubscribeToken($email); // Generate if missing
                $updateStmt = $this->db->prepare("
                    UPDATE newsletter_subscribers
                    SET status = 'active',
                        updated_at = NOW(),
                        unsubscribed_at = NULL,
                        unsubscribe_token = ? -- Update token just in case
                    WHERE id = ?
                ");
                $updateStmt->execute([$token, $subscriber['id']]);
            } else {
                // Add new subscriber
                $isNewSubscriber = true;
                $token = $this->generateUnsubscribeToken($email); // Generate new token
                $insertStmt = $this->db->prepare("
                    INSERT INTO newsletter_subscribers (
                        email,
                        status,
                        ip_address,
                        unsubscribe_token,
                        created_at,
                        updated_at
                    ) VALUES (?, 'active', ?, ?, NOW(), NOW())
                ");
                $insertStmt->execute([
                    $email,
                    $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                    $token
                ]);
                $subscriberId = $this->db->lastInsertId();
            }

            // Send Welcome/Confirmation Email (using correct method)
            $unsubscribeLink = $this->getUnsubscribeUrl($email, $token);
            $emailSubject = $isNewSubscriber ? 'Welcome to The Scent Newsletter!' : 'You are now subscribed again!';
            $emailTemplate = 'newsletter_welcome'; // Use a consistent template name
            $emailData = [
                'email' => $email,
                'unsubscribe_link' => $unsubscribeLink,
                'is_reactivation' => !$isNewSubscriber
            ];

            // Use the inherited emailService instance and its sendEmail method
            $emailSent = $this->emailService->sendEmail(
                $email,
                $emailSubject,
                $emailTemplate,
                $emailData,
                false, // Not high priority
                null, // No specific user ID associated with newsletter signup itself
                'newsletter_welcome' // Email type for logging
            );

            if (!$emailSent) {
                 // Log but don't necessarily fail the whole subscription if email fails
                 error_log("Failed to send newsletter welcome email to {$email}");
            }

            $this->commit();

            return $this->jsonResponse([
                'success' => true,
                'message' => 'Thank you for subscribing!'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter subscription error: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_subscribe_error', ['error' => $e->getMessage(), 'email' => $email ?? null]);
            // --- START FIX: Check if rate limit error ---
            $statusCode = ($e->getCode() === 429) ? 429 : 500;
            $errorMessage = ($e->getCode() === 429) ? $e->getMessage() : 'An error occurred. Please try again later.';
            return $this->jsonResponse([
                'success' => false,
                'message' => $errorMessage
            ], $statusCode);
            // --- END FIX ---
        }
    }

    public function unsubscribe() {
        try {
            // Validate inputs using BaseController method
            $email = $this->validateInput($_GET['email'] ?? null, 'email');
            $token = $this->validateInput($_GET['token'] ?? null, 'string', ['max' => 64]); // Basic validation

            if ($email === false || $token === false || empty($token)) {
                throw new Exception('Invalid unsubscribe link parameters.');
            }

            $this->beginTransaction();

            // Use $this->db
            $stmt = $this->db->prepare("
                UPDATE newsletter_subscribers
                SET status = 'unsubscribed',
                    unsubscribed_at = NOW(),
                    updated_at = NOW()
                WHERE email = ?
                AND unsubscribe_token = ?
                AND status = 'active' -- Only unsubscribe active users
            ");
            $stmt->execute([$email, $token]);

            // Check if any row was actually updated
            if ($stmt->rowCount() === 0) {
                 // Could be already unsubscribed, or invalid link
                 // Check if the user exists but is already unsubscribed
                 $checkStmt = $this->db->prepare("SELECT status FROM newsletter_subscribers WHERE email = ? AND unsubscribe_token = ?");
                 $checkStmt->execute([$email, $token]);
                 $currentStatus = $checkStmt->fetchColumn();
                 if ($currentStatus === 'unsubscribed') {
                     // Already done, treat as success? Or specific message?
                     $this->commit(); // Commit as no change needed
                     return $this->jsonResponse([
                         'success' => true, // Indicate success as they are unsubscribed
                         'message' => 'You are already unsubscribed.'
                     ]);
                 } else {
                    // Invalid link / email / token combo
                     throw new Exception('Invalid or expired unsubscribe link.');
                 }
            }

             // Log successful unsubscribe using BaseController method
             $this->logAuditTrail('newsletter_unsubscribe', null, ['email' => $email]);


            $this->commit();

            // Consider showing a simple confirmation page instead of JSON for GET request
            // For now, returning JSON as per original structure
            return $this->jsonResponse([
                'success' => true,
                'message' => 'You have been successfully unsubscribed.'
            ]);

        } catch (Exception $e) {
            $this->rollback();
            error_log("Newsletter unsubscribe error: " . $e->getMessage());
            $this->logSecurityEvent('newsletter_unsubscribe_error', ['error' => $e->getMessage(), 'email' => $email ?? null]);

            // Return error JSON
            return $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage() // Show specific error message
            ], 400);
        }
    }

    private function generateUnsubscribeToken(string $email): string {
         // Use a more secure method if possible, but HMAC is reasonable
         // Ensure NEWSLETTER_SECRET_KEY is defined and strong in config.php
         if (!defined('NEWSLETTER_SECRET_KEY')) {
             error_log("NEWSLETTER_SECRET_KEY is not defined in config.php!");
             // Fallback, but highly insecure
             return bin2hex(random_bytes(16));
         }
         return hash_hmac(
             'sha256',
             $email . microtime(), // Add microtime for more uniqueness
             NEWSLETTER_SECRET_KEY
         );
     }

     private function getUnsubscribeUrl(string $email, string $token): string {
         // Construct the unsubscribe URL using BASE_URL
         $baseUrl = rtrim(BASE_URL, '/');
         return $baseUrl . '/index.php?page=newsletter&action=unsubscribe&email=' . urlencode($email) . '&token=' . urlencode($token);
     }

    // Remove logEmail method - it's inherited from BaseController
    // Remove getWelcomeEmailContent - welcome email content generated via renderTemplate

    // getSubscriberCount uses $this->db (inherited) - OK
    public function getSubscriberCount() {
        $this->requireAdmin();
        $stmt = $this->db->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'");
        return $stmt->fetchColumn();
    }

    // getRecentSubscribers uses $this->db (inherited) - OK
    public function getRecentSubscribers($limit = 10) {
        $this->requireAdmin();
        // Use prepare statement for limit
        $stmt = $this->db->prepare("
            SELECT email, created_at FROM newsletter_subscribers
            WHERE status = 'active' ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

} // End of NewsletterController class

```

# controllers/CheckoutController.php  
```php
<?php
// controllers/CheckoutController.php (Updated - Fix validateCartStock, add logging)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/PaymentController.php'; // Now needed for StripeClient access
require_once __DIR__ . '/../controllers/InventoryController.php';
require_once __DIR__ . '/../controllers/TaxController.php';
require_once __DIR__ . '/../controllers/CouponController.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php';

// Assume Stripe SDK is loaded via Composer autoload in index.php
// require_once __DIR__ . '/../vendor/autoload.php'; // Ensure autoloader is included

class CheckoutController extends BaseController {
    private Product $productModel;
    private Order $orderModel;
    private InventoryController $inventoryController;
    private TaxController $taxController;
    private PaymentController $paymentController; // Store PaymentController instance
    private CouponController $couponController;
    private User $userModel; // Add UserModel instance variable

    // Updated Constructor to accept PaymentController
    public function __construct($pdo, PaymentController $paymentController) { // Added PaymentController dependency
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->orderModel = new Order($pdo);
        $this->inventoryController = new InventoryController($pdo);
        $this->taxController = new TaxController($pdo);
        $this->paymentController = $paymentController; // Store injected PaymentController
        $this->couponController = new CouponController($pdo);
        $this->userModel = new User($pdo); // Initialize UserModel
    }

    /**
     * Display the checkout page.
     * Pre-fills address if available.
     * Calculates initial totals.
     */
    public function showCheckout() {
        // (Method content unchanged - it was already correct)
        $this->requireLogin();
        $userId = $this->getUserId();

        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems();

        if (empty($items)) {
             $this->setFlashMessage('Your cart is empty. Add some products before checking out.', 'info');
             $this->redirect('index.php?page=products');
             return;
        }

        $cartItems = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            // Validate stock before displaying checkout
            // Ensure 'product_id' and 'quantity' keys exist
            $productId = $item['product']['id'] ?? null; // Adjusted to match CartController structure
            $quantity = $item['quantity'] ?? 0;
            if (!$productId || $quantity <= 0) continue; // Skip if invalid

            if (!$this->productModel->isInStock($productId, $quantity)) {
                $this->setFlashMessage("Item '".htmlspecialchars($item['product']['name'] ?? 'Product')."' is out of stock. Please update your cart.", 'error');
                $this->redirect('index.php?page=cart');
                return;
            }
            $price = $item['product']['price'] ?? 0; // Adjusted to match CartController structure
            $lineSubtotal = $price * $quantity;
            $cartItems[] = [
                'product' => $item['product'], // Pass the whole product sub-array
                'quantity' => $quantity,
                'subtotal' => $lineSubtotal
            ];
            $subtotal += $lineSubtotal;
        }

        // Initial calculations (updated by JS/AJAX)
        $tax_rate_formatted = 'N/A'; // Placeholder
        $tax_amount = 0.0; // Placeholder
        $shipping_cost = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $total = $subtotal + $shipping_cost + $tax_amount;

        // --- Corrected: Initialize UserModel properly ---
        // $userModel = new User($this->db); // Removed - Initialized in constructor now
        $userAddress = $this->userModel->getAddress($userId); // Fetches address data or null
        // --- End Correction ---


        $csrfToken = $this->getCsrfToken();
        $bodyClass = 'page-checkout';
        $pageTitle = 'Checkout - The Scent';

        echo $this->renderView('checkout', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'tax_rate_formatted' => $tax_rate_formatted,
            'tax_amount' => $tax_amount,
            'shipping_cost' => $shipping_cost,
            'total' => $total,
            'csrfToken' => $csrfToken,
            'bodyClass' => $bodyClass,
            'pageTitle' => $pageTitle,
            'userAddress' => $userAddress ?? [] // Pass address data or empty array
        ]);
    }

    /**
     * AJAX endpoint to calculate tax based on country/state.
     */
    public function calculateTax() {
        // (Method content unchanged - it was already correct)
        $this->requireLogin(true); // AJAX request

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        $country = $this->validateInput($data['country'] ?? null, 'string');
        $state = $this->validateInput($data['state'] ?? null, 'string');
        $subtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); // Get subtotal from client JS
        $discount = $this->validateInput($data['discount'] ?? 0, 'float'); // Get discount from client JS

        $subtotalAfterDiscount = max(0, $subtotal - $discount);

        if (empty($country)) {
           return $this->jsonResponse(['success' => false, 'error' => 'Country is required'], 400);
        }

        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax($subtotalAfterDiscount, $country, $state); // Tax based on subtotal after discount
        $tax_rate = $this->taxController->getTaxRate($country, $state);
        $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount; // Estimate

        return $this->jsonResponse([
            'success' => true,
            'tax_rate_formatted' => $this->taxController->formatTaxRate($tax_rate),
            'tax_amount' => number_format($tax_amount, 2), // Send formatted
            'total' => number_format($total, 2) // Send formatted estimate
        ]);
    }

    // Helper to get cart subtotal for logged-in user (unchanged)
    private function calculateCartSubtotal(): float {
         $userId = $this->getUserId();
         if (!$userId) return 0.0;
         $cartModel = new Cart($this->db, $userId);
         $items = $cartModel->getItems();
         $subtotal = 0.0;
         foreach ($items as $item) { $subtotal += ($item['product']['price'] ?? 0) * ($item['quantity'] ?? 0); } // Adjusted structure
         return (float)$subtotal;
    }

    /**
     * Processes the checkout form submission via AJAX.
     * Creates order, handles inventory, coupons, and initiates payment intent.
     * Optionally updates user address.
     */
    public function processCheckout() {
        $this->validateRateLimit('checkout_submit');
        $this->requireLogin(true); // AJAX request
        $this->validateCSRF();

        $userId = $this->getUserId();
        $cartModel = new Cart($this->db, $userId);
        $items = $cartModel->getItems(); // Uses getCartItemsInternal which nests product data

        // --- START: Add Logging for Cart Items ---
        if (empty($items)) {
            error_log("Checkout Error: Cart items are empty for User ID: {$userId} at start of processCheckout.");
             // Return specific error if cart is unexpectedly empty
             return $this->jsonResponse(['success' => false, 'error' => 'Your cart appears empty. Please add items before checkout.'], 400);
         } else {
             error_log("Checkout Info: Found " . count($items) . " distinct item types for User ID: {$userId}");
         }
         // --- END: Add Logging ---


        // --- Collect Cart Details ---
        $cartItemsForOrder = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $productId = $item['product']['id'] ?? null; // Access nested product ID
            $quantity = $item['quantity'] ?? 0;
            $price = $item['product']['price'] ?? 0; // Access nested price
            $name = $item['product']['name'] ?? 'Unknown Product';
            if (!$productId || $quantity <= 0) continue;

            $cartItemsForOrder[$productId] = ['quantity' => $quantity, 'price' => $price, 'name' => $name];
            $subtotal += $price * $quantity;
        }

        // --- Validate Shipping Input ---
        $requiredFields = [
            'shipping_name', 'shipping_email', 'shipping_address', 'shipping_city',
            'shipping_state', 'shipping_zip', 'shipping_country'
        ];
        $missingFields = [];
        $postData = []; // Store validated required fields here
        foreach ($requiredFields as $field) {
            $value = $_POST[$field] ?? '';
            if (empty(trim($value))) {
                $missingFields[] = ucwords(str_replace('_', ' ', $field));
            } else {
                 $type = (strpos($field, 'email') !== false) ? 'email' : 'string';
                 $validatedValue = $this->validateInput($value, $type);
                 if ($validatedValue === false) {
                     $missingFields[] = ucwords(str_replace('_', ' ', $field)) . " (Invalid)";
                 } else {
                     $postData[$field] = $validatedValue;
                 }
            }
        }
        if (!empty($missingFields)) {
             return $this->jsonResponse([
                 'success' => false,
                 'error' => 'Please fill required shipping fields: ' . implode(', ', $missingFields) . '.'
             ], 400);
        }
        $postData['shipping_address_line2'] = $this->validateInput($_POST['shipping_address_line2'] ?? null, 'string', ['max' => 255]);
        $orderNotes = $this->validateInput($_POST['order_notes'] ?? null, 'string', ['max' => 1000]);
        $saveAddress = isset($_POST['save_address']) && $_POST['save_address'] === '1';

        // --- Validate Coupon (Again, server-side) ---
        $couponCode = $this->validateInput($_POST['applied_coupon_code'] ?? null, 'string');
        $coupon = null;
        $discountAmount = 0.0;
        if ($couponCode) {
            $validationResult = $this->couponController->validateCouponCodeOnly($couponCode, $subtotal);
            if ($validationResult['valid']) {
                 $coupon = $validationResult['coupon'];
                 if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) {
                     error_log("Checkout Warning: User {$userId} tried applying already used coupon '{$couponCode}' during final processing.");
                     $coupon = null;
                     $couponCode = null; // Clear the code if user already used it
                 } else {
                     $discountAmount = $this->couponController->calculateDiscount($coupon, $subtotal);
                 }
            } else {
                 error_log("Checkout Warning: Coupon '{$couponCode}' became invalid during final checkout for user {$userId}. Message: " . ($validationResult['message'] ?? 'N/A'));
                 $couponCode = null; // Clear the code
                 $coupon = null;
            }
        }

        // --- Calculate Final Totals ---
        $subtotalAfterDiscount = max(0, $subtotal - $discountAmount);
        $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST;
        $tax_amount = $this->taxController->calculateTax(
            $subtotalAfterDiscount,
            $postData['shipping_country'],
            $postData['shipping_state']
        );
        $total = $subtotalAfterDiscount + $shipping_cost + $tax_amount;
        $total = max(0.50, round($total, 2)); // Ensure minimum payment amount for Stripe

        // --- Start Transaction ---
        try {
            $this->beginTransaction();

            // --- Re-validate Stock within Transaction ---
            $stockErrors = $this->validateCartStock($cartItemsForOrder); // Use internal helper structure
            if (!empty($stockErrors)) {
                $this->rollback();
                 return $this->jsonResponse([
                     'success' => false,
                     'error' => 'Some items went out of stock: ' . implode(', ', $stockErrors) . '. Please review your cart.'
                 ], 409); // 409 Conflict is appropriate here
            }

            // --- Create Order Record ---
            $orderData = [
                'user_id' => $userId, 'subtotal' => $subtotal, 'discount_amount' => $discountAmount, 'coupon_code' => $coupon ? $coupon['code'] : null,
                'coupon_id' => $coupon ? $coupon['id'] : null, 'shipping_cost' => $shipping_cost, 'tax_amount' => $tax_amount, 'total_amount' => $total,
                'shipping_name' => $postData['shipping_name'], 'shipping_email' => $postData['shipping_email'], 'shipping_address' => $postData['shipping_address'],
                'shipping_address_line2' => $postData['shipping_address_line2'] ?? null, 'shipping_city' => $postData['shipping_city'], 'shipping_state' => $postData['shipping_state'],
                'shipping_zip' => $postData['shipping_zip'], 'shipping_country' => $postData['shipping_country'], 'status' => 'pending_payment',
                'payment_status' => 'pending', 'order_notes' => $orderNotes, 'payment_intent_id' => null
            ];
            $orderId = $this->orderModel->create($orderData);
            if (!$orderId) throw new Exception("Failed to create order record.");

            // --- Create Order Items & Decrement Inventory ---
            $itemStmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItemsForOrder as $productId => $itemData) {
                $itemStmt->execute([$orderId, $productId, $itemData['quantity'], $itemData['price']]);
                $inventoryController = new InventoryController($this->db);
                if (!$inventoryController->updateStock($productId, -$itemData['quantity'], 'sale', $orderId)) {
                    throw new Exception("Failed to update inventory for product ID {$productId}");
                }
            }

            // --- Update User Address if Requested ---
            if ($saveAddress) {
                 $addressUpdateData = [
                    'address_line1' => $postData['shipping_address'], 'address_line2' => $postData['shipping_address_line2'], 'city' => $postData['shipping_city'],
                    'state' => $postData['shipping_state'], 'postal_code' => $postData['shipping_zip'], 'country' => $postData['shipping_country'] ];
                if (!$this->userModel->updateAddress($userId, $addressUpdateData)) { error_log("Warning: Failed to save user address during checkout for User ID {$userId}. Order ID {$orderId}."); }
                else { $this->logAuditTrail('user_address_update_checkout', $userId, ['order_id' => $orderId]); }
            }

            // --- Create Payment Intent ---
            $paymentResult = $this->paymentController->createPaymentIntent($total, 'usd', $orderId, $postData['shipping_email']);
            if (!$paymentResult['success'] || empty($paymentResult['client_secret']) || empty($paymentResult['payment_intent_id'])) {
                $this->orderModel->updateStatus($orderId, 'payment_failed'); throw new Exception($paymentResult['error'] ?? 'Could not initiate payment.');
            }
            $clientSecret = $paymentResult['client_secret']; $paymentIntentId = $paymentResult['payment_intent_id'];

            // --- Update Order with Payment Intent ID ---
            if (!$this->orderModel->updatePaymentIntentId($orderId, $paymentIntentId)) { throw new Exception("Failed to link Payment Intent ID {$paymentIntentId} to Order ID {$orderId}."); }

            // --- Record Coupon Usage ---
            if ($coupon) { if (!$this->couponController->recordUsage($coupon['id'], $orderId, $userId, $discountAmount)) { error_log("Warning: Failed to record usage for coupon ID {$coupon['id']} on order ID {$orderId}."); } }

            // --- Commit Transaction ---
            $this->commit();

            $this->logAuditTrail('order_pending_payment', $userId, [ 'order_id' => $orderId, 'total_amount' => $total, 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN' ]);

            // --- Return Client Secret and Order ID ---
            return $this->jsonResponse([ 'success' => true, 'orderId' => $orderId, 'clientSecret' => $clientSecret ]);

        } catch (Exception $e) {
            $this->rollback(); error_log("Checkout processing error: User {$userId} - " . $e->getMessage());
            $statusCode = 500; if ($e->getCode() === 409 || $e->getCode() === 429) { $statusCode = $e->getCode(); }
            $errorMessage = ($statusCode == 409 || strpos($e->getMessage(), 'stock') !== false) ? $e->getMessage() : (($statusCode === 429) ? $e->getMessage() : 'An error occurred during checkout. Please try again.');
            if ($e instanceof PDOException) { $errorMessage = 'A database error occurred. Please try again later.'; }
            return $this->jsonResponse([ 'success' => false, 'error' => $errorMessage ], $statusCode);
        }
    }


    /**
     * Handles AJAX request from checkout page to validate and apply a coupon.
     */
    public function applyCouponAjax() {
         // (Method content unchanged - it was already correct)
         $this->requireLogin(true); $this->validateRateLimit('coupon_apply'); $this->validateCSRF();
         $json = file_get_contents('php://input'); $data = json_decode($json, true);
         $code = $this->validateInput($data['code'] ?? null, 'string'); $currentSubtotal = $this->validateInput($data['subtotal'] ?? null, 'float'); $userId = $this->getUserId();
         if (!$code || $currentSubtotal === false || $currentSubtotal < 0) { return $this->jsonResponse(['success' => false, 'message' => 'Invalid coupon code or subtotal amount provided.'], 400); }
         $validationResult = $this->couponController->validateCouponCodeOnly($code, $currentSubtotal);
         if (!$validationResult['valid']) { return $this->jsonResponse(['success' => false, 'message' => $validationResult['message']]); }
         $coupon = $validationResult['coupon'];
         if ($this->couponController->hasUserUsedCoupon($coupon['id'], $userId)) { return $this->jsonResponse(['success' => false, 'message' => 'You have already used this coupon.']); }
         $discountAmount = $this->couponController->calculateDiscount($coupon, $currentSubtotal); $subtotalAfterDiscount = max(0, $currentSubtotal - $discountAmount);
         $shipping_cost = $subtotalAfterDiscount >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; $newTotalEstimate = $subtotalAfterDiscount + $shipping_cost;
         return $this->jsonResponse([ 'success' => true, 'message' => 'Coupon applied successfully!', 'coupon_code' => $coupon['code'], 'discount_amount' => number_format($discountAmount, 2), 'new_total_estimate' => number_format($newTotalEstimate, 2) ]);
    }

    /**
     * Displays the order confirmation page. (ROBUST IMPLEMENTATION)
     * Verifies payment success using Stripe Payment Intent ID from URL.
     * REMOVED reliance on session variables.
     */
    public function showOrderConfirmation() {
         // (Method content unchanged from previous robust version)
         $this->requireLogin(); $userId = $this->getUserId(); $paymentIntentId = $this->validateInput($_GET['payment_intent'] ?? null, 'string');
         if (!$paymentIntentId || !str_starts_with($paymentIntentId, 'pi_')) { $this->setFlashMessage('Invalid or missing payment confirmation identifier.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
         try {
             if (!$this->paymentController || !($stripeClient = $this->paymentController->getStripeClient())) { error_log("Stripe client not available."); throw new Exception("Payment verification service temporarily unavailable."); }
             $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId, []);
             if ($paymentIntent->status !== 'succeeded') { error_log("Confirmation page accessed for non-succeeded PI: {$paymentIntentId}, Status: {$paymentIntent->status}"); $message = match ($paymentIntent->status) { 'processing' => 'Your payment is still processing.', default => 'Payment confirmation is pending or failed.', }; $this->setFlashMessage($message, 'warning'); $this->redirect('index.php?page=account&section=orders'); return; }
             $order = $this->orderModel->getByPaymentIntentId($paymentIntentId);
             if (!$order || $order['user_id'] !== $userId) { error_log("Order not found or user mismatch for PI: {$paymentIntentId}"); $this->logSecurityEvent('confirmation_access_denied', ['payment_intent_id' => $paymentIntentId, 'logged_in_user' => $userId, 'order_user' => $order['user_id'] ?? null]); $this->setFlashMessage('Order details not found or access denied.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
             $acceptableStatuses = ['processing', 'paid', 'shipped', 'delivered', 'completed']; if (!in_array($order['status'], $acceptableStatuses)) { error_log("Confirmation Warning: PI {$paymentIntentId} succeeded, but order {$order['id']} status is '{$order['status']}'."); }
             $fullOrder = $this->orderModel->getByIdAndUserId($order['id'], $userId);
             if (!$fullOrder || empty($fullOrder['items'])) { error_log("Could not fetch full order details for confirmed order ID: {$order['id']}"); $this->setFlashMessage('Could not display full order details.', 'error'); $this->redirect('index.php?page=account&section=orders'); return; }
             $csrfToken = $this->getCsrfToken(); $bodyClass = 'page-order-confirmation'; $pageTitle = 'Order Confirmation - The Scent';
             echo $this->renderView('order_confirmation', [ 'order' => $fullOrder, 'csrfToken' => $csrfToken, 'bodyClass' => $bodyClass, 'pageTitle' => $pageTitle ]);
         } catch (\Stripe\Exception\ApiErrorException $e) { error_log("Stripe API error fetching PI {$paymentIntentId}: " . $e->getMessage()); $this->setFlashMessage('Error verifying payment status.', 'error'); $this->redirect('index.php?page=account&action=orders');
         } catch (Exception $e) { error_log("Error showing order confirmation for PI {$paymentIntentId}: " . $e->getMessage()); $this->setFlashMessage('An unexpected error occurred.', 'error'); $this->redirect('index.php?page=account&action=orders'); }
     }


    // --- Admin Method (Restored - Unchanged from previous working state) ---
    public function updateOrderStatus($orderId, $status, $trackingInfo = null) {
         // (Method content unchanged - assuming it was already correct)
         $this->requireAdmin(true); $orderId = $this->validateInput($orderId, 'int'); $status = $this->validateInput($status, 'string');
         if (!$orderId || !$status) { return $this->jsonResponse(['success' => false, 'error' => 'Invalid input.'], 400); } $order = $this->orderModel->getById($orderId);
         if (!$order) { return $this->jsonResponse(['success' => false, 'error' => 'Order not found'], 404); }
         $allowedTransitions = [ /* ... transitions ... */ 'pending_payment' => ['paid', 'processing', 'cancelled', 'payment_failed'], 'paid' => ['processing', 'cancelled', 'refunded'], 'processing' => ['shipped', 'cancelled', 'refunded'], 'shipped' => ['delivered', 'refunded'], 'delivered' => ['refunded', 'completed'], 'payment_failed' => ['pending_payment', 'cancelled'], 'cancelled' => [], 'refunded' => [], 'partially_refunded' => ['refunded'], 'disputed' => ['refunded'], 'completed' => [] ];
         if (!isset($allowedTransitions[$order['status']]) || !in_array($status, $allowedTransitions[$order['status']])) { return $this->jsonResponse(['success' => false, 'error' => "Invalid status transition from '{$order['status']}' to '{$status}'."], 400); }
         try { $this->beginTransaction(); $updated = $this->orderModel->updateStatus($orderId, $status);
             if (!$updated) { $currentOrder = $this->orderModel->getById($orderId); if (!$currentOrder || $currentOrder['status'] !== $status) { throw new Exception("Failed to update order status in DB."); } }
             if ($status === 'shipped' && $trackingInfo && !empty($trackingInfo['number'])) { $trackingNumber = $this->validateInput($trackingInfo['number'], 'string', ['max' => 100]); $carrier = $this->validateInput($trackingInfo['carrier'] ?? null, 'string', ['max' => 100]);
                 if ($trackingNumber) { $trackingUpdated = $this->orderModel->updateTracking( $orderId, $trackingNumber, $carrier );
                      if ($trackingUpdated) { $user = $this->userModel->getById($order['user_id']); $fullOrder = $this->orderModel->getByIdAndUserId($orderId, $order['user_id']);
                           if ($user && $fullOrder && $this->emailService && method_exists($this->emailService, 'sendShippingUpdate')) { $this->emailService->sendShippingUpdate( $fullOrder, $user, $trackingNumber, $carrier ?? '' ); }
                           elseif (!$user) { error_log("Could not find user {$order['user_id']} to send shipping update for order {$orderId}"); } elseif (!$fullOrder) { error_log("Could not find full order details for shipping update email (Order ID: {$orderId})"); } else { error_log("EmailService or sendShippingUpdate method not available for order {$orderId}"); } }
                      else { error_log("Failed to update tracking info for order {$orderId}"); } } }
             if ($status === 'cancelled' || $status === 'refunded') { error_log("Order {$orderId} status changed to {$status}. Consider adding refund/restock logic here."); }
             $this->commit(); $adminUserId = $this->getUserId(); $this->logAuditTrail('order_status_update', $adminUserId, [ 'order_id' => $orderId, 'new_status' => $status, 'tracking_provided' => ($status === 'shipped' && !empty($trackingNumber)) ]);
             return $this->jsonResponse(['success' => true, 'message' => 'Order status updated successfully.']);
         } catch (Exception $e) { $this->rollback(); error_log("Error updating order status for {$orderId}: " . $e->getMessage()); return $this->jsonResponse(['success' => false, 'error' => 'Failed to update order status.'], 500); }
    }
    // --- End Admin Method (Restored) ---


    // --- Helper Methods ---
    /**
     * Internal helper to validate stock for items in the cart.
     * Expects $cartItems as [productId => ['quantity' => q, ...]]
     */
     private function validateCartStock(array $cartItems): array {
         $errors = [];
         // --- START FIX: Return empty array if cart is empty ---
         if (empty($cartItems)) {
             return []; // Cart being empty is not a stock error
         }
         // --- END FIX ---

         foreach ($cartItems as $productId => $itemData) {
             // Ensure itemData has quantity key
             $quantity = $itemData['quantity'] ?? 0;
             if ($quantity <= 0) continue;

             $product = $this->productModel->getById($productId);
             if (!$product) {
                 // This case should ideally be handled earlier, but good to have defence here
                 $errors[] = "Product ID {$productId} not found during stock check.";
                 continue;
             }
             if (!$this->productModel->isInStock($productId, $quantity)) {
                 // --- START FIX: Improve out-of-stock message ---
                 $errors[] = htmlspecialchars($product['name'] ?? "Product ID {$productId}") . " (Only " . ($product['stock_quantity'] ?? 0) . " left)"; // Be more specific
                 // --- END FIX ---
             }
         }
         return $errors;
     }

} // End of CheckoutController class

```

# views/register.php  
```php
<?php
// views/register.php (Corrected)
$pageTitle = 'Create Account - The Scent';
// Apply consistent gradient background and page identifier class
$bodyClass = 'page-register bg-gradient-to-br from-light to-secondary/20';

require_once __DIR__ . '/layout/header.php'; // Includes CSRF token output globally
?>

<section class="auth-section flex items-center justify-center min-h-[calc(100vh-80px)] py-12 px-4">
    <div class="container max-w-md mx-auto">
        <div class="auth-container bg-white p-8 md:p-12 rounded-xl shadow-2xl" data-aos="fade-up" data-aos-delay="100">
            <div class="text-center mb-10">
                <h1 class="text-3xl lg:text-4xl font-bold font-heading text-primary mb-3">Create Account</h1>
                <p class="text-gray-600 font-body">Join The Scent community to discover your perfect fragrance.</p>
            </div>

            <?php // Standard Flash Message Display (from header or dynamic)
                // This relies on the flash message container in the header/footer layout
            ?>
            <?php if (isset($_SESSION['flash_message'])): ?>
                <script>
                    // Use the JS function immediately if available, or queue it
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof window.showFlashMessage === 'function') {
                            window.showFlashMessage(<?= json_encode($_SESSION['flash_message']) ?>, <?= json_encode($_SESSION['flash_type'] ?? 'info') ?>);
                        }
                    });
                </script>
                <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form action="index.php?page=register" method="POST" class="auth-form space-y-6" id="registerForm">
		 <!-- CSRF Token is handled globally by JS reading #csrf-token-value -->
		<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-group">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1 font-body">Full Name</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1 font-body">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="Enter your email address"
                           pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                           title="Please enter a valid email address">
                </div>

                <div class="form-group">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1 font-body">Password</label>
                    <div class="password-input relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                               minlength="12"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&amp;_~`#^()=+[\]{}|\\:;&quot;'&lt;&gt;,.?/])[A-Za-z\d@$!%*?&amp;_~`#^()=+[\]{}|\\:;&quot;'&lt;&gt;,.?/]{12,}$"
                               title="Password must meet all requirements below"
                               placeholder="Create a strong password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary transition duration-150 ease-in-out" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1 font-body">Confirm Password</label>
                    <div class="password-input relative">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary transition duration-150 ease-in-out font-body"
                               placeholder="Confirm your password">
                        <button type="button" class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-primary transition duration-150 ease-in-out" aria-label="Toggle password visibility">
                            <i class="fas fa-eye text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Requirements Section - Styled -->
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
                        <li id="req-match" class="requirement flex items-center not-met"> <!-- ID matches JS -->
                            <i class="fas fa-times-circle text-red-500 mr-2 w-4 text-center"></i> Passwords match
                        </li>
                    </ul>
                </div>


                <div class="form-group pt-2">
                    <label class="checkbox-label flex items-center text-sm text-gray-700 cursor-pointer font-body">
                        <input type="checkbox" name="newsletter_signup" value="1"
                               class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                               checked>
                        <span>Sign up for newsletter & exclusive offers</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full py-3 text-lg font-semibold rounded-md shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-dark transition duration-150 ease-in-out flex items-center justify-center font-body" id="submitButton" disabled>
                    <span class="button-text">Create Account</span>
                    <span class="button-loader hidden ml-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>

            <div class="auth-links mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600 font-body">Already have an account?
                    <a href="index.php?page=login" class="font-medium text-primary hover:text-primary-dark transition duration-150 ease-in-out">Login here</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# views/quiz.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-quiz">

<div class="quiz-container min-h-screen bg-gradient-to-br from-primary/5 to-secondary/5 py-20">
    <!-- Particles Background -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-8" data-aos="fade-up">
            <h1 class="text-4xl font-heading font-semibold text-center mb-8">Find Your Perfect Scent</h1>
            <p class="text-center text-gray-600 mb-12">Let us guide you to the perfect aromatherapy products for your needs.</p>

            <form id="scent-quiz" method="POST" action="index.php?page=quiz&action=submit" class="space-y-8">
                <!-- CSRF Token: This input is correctly placed. 
                     The error "CSRF token validation failed" usually means the 
                     CONTROLLER (QuizController.php -> showQuiz method) 
                     is NOT passing the '$csrfToken' variable when rendering this view. 
                     Ensure the controller calls $this->getCsrfToken() and includes it 
                     in the $data array passed to renderView('quiz', $data). 
                -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="quiz-step" data-step="1">
                    <h3 class="text-2xl font-heading mb-6">What are you looking for today?</h3>

                    <!-- Added quiz-options-container div for potential JS targeting -->
                    <div class="quiz-options-container grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="relaxation" class="hidden" required>
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-spa text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Relaxation</h4>
                                <p class="text-sm text-gray-600">Find calm and peace in your daily routine</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="energy" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-bolt text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Energy</h4>
                                <p class="text-sm text-gray-600">Boost your vitality and motivation</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="focus" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-brain text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Focus</h4>
                                <p class="text-sm text-gray-600">Enhance concentration and clarity</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="balance" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-yin-yang text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Balance</h4>
                                <p class="text-sm text-gray-600">Find harmony in body and mind</p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-8 text-center">
                        <button type="submit" class="btn-primary inline-flex items-center space-x-2">
                            <span>Find My Perfect Scent</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# views/quiz_results.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-quiz-results">
<div class="min-h-screen bg-gradient-to-br from-primary/5 to-secondary/5 py-20">
    <!-- Particles Background -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto">
            <!-- Results Header -->
            <div class="text-center mb-12" data-aos="fade-down">
                <h1 class="text-4xl font-heading font-semibold mb-4">Your Perfect Scent Match</h1>
                <p class="text-xl text-gray-600">Based on your preferences, we've curated these perfect matches for you.</p>
            </div>

            <!-- Product Recommendations -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 md:gap-8 mb-12">
                <?php if (!isset($products) || !is_array($products)): $products = []; endif; ?>

                <?php if (empty($products)): ?>
                    <div class="col-span-full text-center py-12 bg-white rounded-xl shadow-lg" data-aos="fade-up">
                        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Specific Recommendations Found</h3>
                        <p class="text-gray-500 mb-6">We couldn't find specific products matching your selection, but feel free to explore our full collection!</p>
                        <a href="index.php?page=products" class="btn-primary">Shop All Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php // Using the standard product card structure for consistency ?>
                        <div class="product-card sample-card bg-white rounded-lg shadow-md overflow-hidden transition-shadow duration-300 hover:shadow-xl flex flex-col" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                            <div class="product-image relative h-64 overflow-hidden">
                                <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                                         alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                </a>
                                <?php if (!empty($product['is_featured'])): ?>
                                    <span class="absolute top-2 left-2 bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info p-4 flex flex-col flex-grow text-center">
                                <h3 class="text-lg font-semibold mb-1 font-heading text-primary hover:text-accent">
                                    <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h3>
                                <?php if (!empty($product['short_description'])): ?>
                                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['short_description']) ?></p>
                                <?php elseif (!empty($product['category_name'])): ?>
                                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                                <?php endif; ?>
                                <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                                <div class="product-actions mt-auto flex gap-2 justify-center">
                                    <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a>
                                    <?php $isOutOfStock = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0) && empty($product['backorder_allowed']); ?>
                                    <?php if (!$isOutOfStock): ?>
                                        <button class="btn btn-secondary add-to-cart"
                                                data-product-id="<?= $product['id'] ?>">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="text-center space-x-4" data-aos="fade-up">
                <a href="index.php?page=quiz" class="btn-secondary">
                    <i class="fas fa-redo mr-1"></i> Retake Quiz
                </a>
                <a href="index.php?page=products" class="btn-primary">
                    <i class="fas fa-shopping-bag mr-1"></i> Shop All Products
                </a>
            </div>

            <!-- Newsletter Signup -->
            <div class="mt-16 bg-white rounded-xl shadow-lg p-8 text-center" data-aos="fade-up">
                <h3 class="font-heading text-2xl mb-4">Stay Updated</h3>
                <p class="text-gray-600 mb-6">Sign up for our newsletter to receive personalized aromatherapy tips and exclusive offers.</p>

                <form id="newsletter-form-results" action="index.php?page=newsletter&action=subscribe" method="POST" class="flex flex-col md:flex-row gap-4 justify-center max-w-lg mx-auto">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email address"
                        class="newsletter-input flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"
                        required
                    >
                    <button type="submit" class="btn btn-primary newsletter-btn whitespace-nowrap">
                        Subscribe Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# views/order_confirmation.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>

<section class="confirmation-section">
    <div class="container">
        <div class="confirmation-container" data-aos="fade-up">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h1>Order Confirmed!</h1>
                <p>Thank you for your purchase. Your order (#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>) has been successfully placed.</p>
            </div>

            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="label">Order Number:</span>
                            <span class="value">#<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Date:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="label">Order Status:</span>
                            <!-- Display actual status from DB -->
                            <span class="value status-<?= htmlspecialchars($order['status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['status']))) ?>
                            </span>
                        </div>
                        <div class="info-item">
                            <span class="label">Payment Status:</span>
                             <span class="value status-<?= htmlspecialchars($order['payment_status']) ?>">
                                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($order['payment_status']))) ?>
                            </span>
                        </div>
                        <!-- Add estimated delivery if available -->
                        <?php if (!empty($order['estimated_delivery'])): ?>
                        <div class="info-item">
                            <span class="label">Estimated Delivery:</span>
                            <span class="value"><?= date('F j, Y', strtotime($order['estimated_delivery'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="shipping-info">
                    <h2>Shipping Address</h2>
                    <p><?= htmlspecialchars($order['shipping_name'] ?? 'N/A') ?></p>
                    <p><?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?></p>
                    <p>
                        <?= htmlspecialchars($order['shipping_city'] ?? 'N/A') ?>,
                        <?= htmlspecialchars($order['shipping_state'] ?? 'N/A') ?>
                        <?= htmlspecialchars($order['shipping_zip'] ?? 'N/A') ?>
                    </p>
                    <p><?= htmlspecialchars($order['shipping_country'] ?? 'N/A') ?></p>
                </div>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-items">
                    <?php // Ensure order items are correctly fetched and passed ?>
                    <?php if (!empty($order['items']) && is_array($order['items'])): ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="summary-item">
                                <div class="item-info">
                                    <span class="item-quantity"><?= $item['quantity'] ?>&times;</span>
                                    <span class="item-name"><?= htmlspecialchars($item['product_name'] ?? 'N/A') ?></span>
                                    <!-- Optional: add image -->
                                    <!-- <img src="<?= htmlspecialchars($item['image_url'] ?? '/images/placeholder.jpg') ?>" alt="" class="w-8 h-8 ml-2 rounded"> -->
                                </div>
                                <span class="item-price">$<?= number_format(($item['price_at_purchase'] ?? 0) * ($item['quantity'] ?? 0), 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                         <p class="text-gray-500 italic">Could not load order items.</p>
                    <?php endif; ?>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>$<?= number_format($order['subtotal'] ?? 0, 2) ?></span>
                    </div>
                    <?php if (!empty($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                        <div class="summary-row discount">
                            <span>Discount <?= !empty($order['coupon_code']) ? '(' . htmlspecialchars($order['coupon_code']) . ')' : '' ?>:</span>
                            <span>-$<?= number_format($order['discount_amount'], 2) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span><?= ($order['shipping_cost'] ?? 0) > 0 ? '$' . number_format($order['shipping_cost'], 2) : '<span class="text-green-600">FREE</span>' ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax:</span>
                        <span>$<?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Paid:</span>
                        <span>$<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>

            <div class="next-steps" data-aos="fade-up">
                <h2>What's Next?</h2>
                <div class="steps-grid">
                    <div class="step">
                        <i class="fas fa-envelope"></i>
                        <h3>Order Confirmation Email</h3>
                        <p>We've sent a confirmation email to <?= htmlspecialchars($order['shipping_email'] ?? 'your email') ?>.</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-truck"></i>
                        <h3>Shipping Updates</h3>
                        <p>You'll receive shipping updates and tracking information once your order ships.</p>
                    </div>
                    <div class="step">
                        <i class="fas fa-user"></i>
                        <h3>Track Your Order</h3>
                        <p>Visit your account dashboard to track your order and view order history.</p>
                    </div>
                </div>

                <div class="confirmation-actions">
                    <a href="index.php?page=account&section=orders" class="btn-secondary">View My Orders</a>
                    <a href="index.php?page=products" class="btn-primary">Continue Shopping</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# views/order-tracking.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<main class="container py-12">
    <h1 class="text-3xl font-heading mb-6">Track Your Order</h1>
    <form method="post" class="max-w-lg bg-white p-6 rounded shadow" action="#">
        <input type="hidden" name="csrf_token" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-4">
            <label for="order_id" class="block font-medium mb-1">Order ID</label>
            <input type="text" id="order_id" name="order_id" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="mb-4">
            <label for="email" class="block font-medium mb-1">Email</label>
            <input type="email" id="email" name="email" class="w-full border rounded px-3 py-2" required>
        </div>
        <button type="submit" class="btn-primary">Track Order</button>
    </form>
</main>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

