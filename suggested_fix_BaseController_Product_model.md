Okay, I have carefully reviewed the provided code files, focusing on the compatibility of `index.php` and `main.js` with the refactored `AccountController.php`, analyzing the two specific bugs you reported, and then checking the compatibility of the remaining PHP files.

**Compatibility Review Summary:**

1.  **`index.php` vs. `AccountController.php`:** **Compatible.** The routing logic in `index.php` correctly maps URL parameters (`?page=account`, `?page=login`, etc., and `&section=...` for account sub-pages) to the corresponding methods in the refactored `AccountController.php` (e.g., `login`, `register`, `showDashboard`, `showProfile`, `updateProfile`, `showOrders`, `showOrderDetails`, `requestPasswordReset`, `resetPassword`). Dependency injection for `CheckoutController` is also handled correctly. CSRF validation is performed globally for POST requests as expected.
2.  **`main.js` vs. `AccountController.php` & `index.php`:** **Compatible.** The AJAX handlers in `main.js` (specifically `initLoginPage` and `initRegisterPage`) correctly target the endpoints defined in `index.php` (`?page=login`, `?page=register`). They send data using `FormData` and correctly include the CSRF token read from the global `#csrf-token-value`. The JavaScript correctly handles the expected JSON responses (`success`, `redirect`, `error`) returned by the `AccountController` methods.
3.  **Other PHP Files Compatibility:** All other provided PHP files (`controllers/*`, `includes/*`, `models/*`, `views/*`) appear compatible with the core trio (`AccountController`, `index.php`, `main.js`). They utilize `BaseController` methods where appropriate, handle CSRF tokens correctly in views and controllers, and model interactions align with controller logic. The previous fixes for Account UI, Checkout Load, and Order Confirmation are reflected correctly.

**Bug Analysis and Fixes:**

1.  **Issue #1: Quiz Redirect Error (`Redirect URL validation failed`)**
    *   **Cause:** The error log clearly points to `BaseController::redirect()`. The issue is the `filter_var($finalUrl, FILTER_VALIDATE_URL)` check within this method. This function expects a full URL (including scheme like `http://`), but the relative URL `/index.php?page=quiz&action=results` constructed by the method fails this validation.
    *   **Fix:** Remove the `filter_var` check from `BaseController::redirect()`. The preceding regex check provides basic protection against header injection for relative paths. The browser will handle the relative redirect correctly.
    *   **File Affected:** `controllers/BaseController.php`

2.  **Issue #2: Product Filter SQL Error (`Unknown column 'p.p.category_id'`)**
    *   **Cause:** The error `Unknown column 'p.p.category_id'` indicates that the table alias `p.` is being added twice to the `category_id` condition in the SQL query. Reviewing `ProductController::showProductList()` shows it correctly adds the alias (`$conditions[] = "p.category_id = ?"`). However, `ProductModel::getFiltered()` and `ProductModel::getCount()` *also* attempt to add the `p.` prefix using `preg_replace` inside an `array_map`. This redundant prefixing causes the error.
    *   **Fix:** Remove the `array_map` logic that adds table alias prefixes within the `ProductModel::getFiltered()` and `ProductModel::getCount()` methods. The controller is already responsible for providing correctly prefixed conditions.
    *   **File Affected:** `models/Product.php`

Below are the complete, updated versions of the affected files with the fixes applied.

---

**Updated File: `controllers/BaseController.php`**

```php
<?php

// Ensure SecurityMiddleware is available (likely included via index.php or autoloader)
require_once __DIR__ . '/../includes/SecurityMiddleware.php';
require_once __DIR__ . '/../includes/EmailService.php';
require_once __DIR__ . '/../config.php'; // For BASE_URL, SECURITY_SETTINGS

abstract class BaseController {
    protected PDO $db; // Use type hint
    protected EmailService $emailService; // Use type hint
    protected array $responseHeaders = []; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        $this->db = $pdo;
        $this->emailService = new EmailService($this->db); // Pass the PDO connection
        $this->initializeSecurityHeaders();
    }

    protected function initializeSecurityHeaders(): void { // Add return type hint
        // Use security settings from config if available
        $this->responseHeaders = SECURITY_SETTINGS['headers'] ?? [
            // Sensible defaults if not configured
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
             // Default CSP - stricter than original, adjust as needed
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self' 'unsafe-inline'; frame-src https://js.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains' // If HTTPS is enforced
        ];
        // Permissions-Policy can be added if needed
    }

    // --- CSRF Methods ---
    /**
     * Gets the current CSRF token, generating one if necessary.
     * Relies on SecurityMiddleware.
     *
     * @return string The CSRF token.
     */
    protected function getCsrfToken(): string {
        // Ensure CSRF is enabled in settings before generating
        if (defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['csrf']['enabled']) && !SECURITY_SETTINGS['csrf']['enabled']) {
             return ''; // Return empty string if CSRF disabled
         }
        return SecurityMiddleware::generateCSRFToken();
    }

    /**
     * Validates the CSRF token submitted in a POST request.
     * Relies on SecurityMiddleware, which throws an exception on failure.
     * It's recommended to call this at the beginning of POST action handlers.
     */
    protected function validateCSRF(): void { // Add return type hint
         // Ensure CSRF is enabled in settings before validating
         if (defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['csrf']['enabled']) && !SECURITY_SETTINGS['csrf']['enabled']) {
              return; // Skip validation if CSRF disabled
          }
        SecurityMiddleware::validateCSRF(); // Throws exception on failure
    }
    // --- End CSRF Methods ---


    /**
     * Ensures the user is logged in. If not, redirects to the login page or sends a 401 JSON response.
     * Also performs session integrity checks and regeneration.
     *
     * @param bool $isAjaxRequest Set to true if the request is AJAX, to return JSON instead of redirecting.
     */
    protected function requireLogin(bool $isAjaxRequest = false): void { // Added optional param and return type
        // Ensure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $details = [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN'
            ];
            $this->logSecurityEvent('unauthorized_access_attempt', $details);

            if ($isAjaxRequest) {
                 $this->jsonResponse(['error' => 'Authentication required.'], 401); // Exit via jsonResponse
            } else {
                 $this->setFlashMessage('Please log in to access this page.', 'warning');
                 // Store intended destination
                 $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? (BASE_URL . 'index.php?page=account');
                 $this->redirect('index.php?page=login'); // Exit via redirect
            }
            // Explicit exit for safety, although jsonResponse/redirect should exit
            exit();
        }

        // Verify session integrity only if user_id is set
        if (!$this->validateSessionIntegrity()) {
            $this->terminateSession('Session integrity check failed'); // Handles exit
        }

        // Check session age and regenerate if needed
        if ($this->shouldRegenerateSession()) {
            $this->regenerateSession();
        }
    }

    /**
     * Ensures the user is logged in and has the 'admin' role.
     *
     * @param bool $isAjaxRequest Set to true if the request is AJAX, to return JSON instead of redirecting.
     */
    protected function requireAdmin(bool $isAjaxRequest = false): void { // Added optional param and return type
        $this->requireLogin($isAjaxRequest); // Check login first

        // Check role existence and value
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            $details = [
                'user_id' => $_SESSION['user_id'] ?? null, // Should be set if requireLogin passed
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                'role_found' => $_SESSION['user_role'] ?? 'NOT SET'
            ];
            $this->logSecurityEvent('unauthorized_admin_attempt', $details);

            if ($isAjaxRequest) {
                 $this->jsonResponse(['error' => 'Admin access required.'], 403); // Exit via jsonResponse
            } else {
                 $this->setFlashMessage('You do not have permission to access this area.', 'error');
                 // Redirect to a safe page like account dashboard
                 $this->redirect('index.php?page=account'); // Exit via redirect
            }
            // Explicit exit for safety
            exit();
        }
    }


    /**
     * Validates input data using SecurityMiddleware.
     * This is a convenience wrapper. Direct use of SecurityMiddleware::validateInput is also fine.
     *
     * @param mixed $input The value to validate.
     * @param string $type The validation type (e.g., 'string', 'int', 'email').
     * @param array $options Additional validation options (e.g., ['min' => 1, 'max' => 100]).
     * @return mixed The validated and potentially sanitized input, or false on failure.
     */
    protected function validateInput(mixed $input, string $type, array $options = []): mixed {
        return SecurityMiddleware::validateInput($input, $type, $options);
    }


    /**
     * Sends a JSON response and terminates script execution.
     *
     * @param array $data The data to encode as JSON.
     * @param int $status The HTTP status code (default: 200).
     */
    protected function jsonResponse(array $data, int $status = 200): void { // Add return type hint
        // Prevent caching of JSON API responses
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');

        // Apply security headers (optional here if globally applied by SecurityMiddleware::apply)
        // foreach ($this->responseHeaders as $header => $value) { header("$header: $value"); }

        echo json_encode($data); // Removed pretty print for efficiency
        exit;
    }

    /**
     * Performs an HTTP redirect and terminates script execution.
     * Prepends BASE_URL if the URL is relative.
     *
     * @param string $url The relative path or full URL to redirect to.
     * @param int $statusCode The HTTP redirect status code (default: 302).
     */
    protected function redirect(string $url, int $statusCode = 302): void { // Add return type hint
        // Basic check to prevent header injection from $url if it comes from user input
         // Allow relative paths starting with '/' or alphanumeric, or full URLs
        if (!preg_match('~^(/|[\w\-./?=&%]+|https?://)~', $url)) { // Improved regex
             error_log("Invalid redirect URL pattern detected: " . $url);
             $url = '/'; // Redirect home as safe fallback
         }

        // Prepend BASE_URL if it's a relative path
        if (!preg_match('~^https?://~i', $url)) {
             // Ensure BASE_URL ends with a slash and $url doesn't start with one if needed
             $baseUrl = rtrim(BASE_URL, '/') . '/';
             $url = ltrim($url, '/');
             $finalUrl = $baseUrl . $url;
        } else {
            $finalUrl = $url;
        }


        // --- START FIX: Remove filter_var validation for relative URLs ---
        // if (!filter_var($finalUrl, FILTER_VALIDATE_URL)) {
        //     error_log("Redirect URL validation failed after constructing: " . $finalUrl);
        //     header('Location: ' . rtrim(BASE_URL, '/') . '/'); // Redirect home as safe fallback
        //     exit;
        // }
        // --- END FIX ---

        header('Location: ' . $finalUrl, true, $statusCode);
        exit;
    }

    /**
     * Sets a flash message in the session.
     *
     * @param string $message The message content.
     * @param string $type The message type ('info', 'success', 'warning', 'error').
     */
    protected function setFlashMessage(string $message, string $type = 'info'): void { // Add return type hint
        // Ensure session is started before trying to write to it
        if (session_status() === PHP_SESSION_NONE) {
             // Attempt to start session only if headers not sent
             if (!headers_sent()) {
                  session_start();
             } else {
                  // Cannot start session, log error
                  error_log("Session not active and headers already sent. Cannot set flash message: {$message}");
                  return;
             }
        }
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    // Transaction helpers
    protected function beginTransaction(): void { $this->db->beginTransaction(); } // Add return type hint
    protected function commit(): void { $this->db->commit(); } // Add return type hint
    protected function rollback(): void { if ($this->db->inTransaction()) { $this->db->rollBack(); } } // Add return type hint

    // User helpers
    protected function getCurrentUser(): ?array { return $_SESSION['user'] ?? null; } // Add return type hint
    protected function getUserId(): ?int { return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null; } // Add return type hint

    /**
     * Renders a view template with provided data.
     *
     * @param string $viewPath Path to the view file relative to the views directory (e.g., 'account/dashboard').
     * @param array $data Data to extract into the view's scope.
     * @return string The rendered HTML output.
     * @throws Exception If the view file is not found.
     */
    protected function renderView(string $viewPath, array $data = []): string { // Add return type hint
        // Ensure CSRF token is available for views that might need it
        if (!isset($data['csrfToken'])) {
             $data['csrfToken'] = $this->getCsrfToken();
        }
        // Ensure user data is available if needed by layout/views
        if (!isset($data['user']) && isset($_SESSION['user'])) {
            $data['user'] = $_SESSION['user'];
        }

        // Extract data to make it available in view
        extract($data);

        ob_start();
        // Use ROOT_PATH constant defined in index.php for reliability
        $viewFile = ROOT_PATH . '/views/' . $viewPath . '.php';

        if (!file_exists($viewFile)) {
            ob_end_clean(); // Clean buffer before throwing
            throw new Exception("View not found: {$viewFile}");
        }
        try {
            include $viewFile;
        } catch (\Throwable $e) {
             ob_end_clean(); // Clean buffer if view inclusion fails
             error_log("Error rendering view {$viewPath}: " . $e->getMessage());
             // It's often better to let the global ErrorHandler catch this
             throw $e; // Re-throw the error
        }
        return ob_get_clean();
    }

    /**
     * Logs an action to the audit trail database table.
     *
     * @param string $action A code representing the action performed (e.g., 'login_success', 'product_update').
     * @param int|null $userId The ID of the user performing the action, or null if anonymous/system.
     * @param array $details Additional context or data related to the action (will be JSON encoded).
     */
    protected function logAuditTrail(string $action, ?int $userId, array $details = []): void { // Add type hints
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_log (action, user_id, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $action,
                $userId, // Use the passed userId, allowing null
                $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
                json_encode($details) // Ensure details are encoded
            ]);
        } catch (Exception $e) {
            // Log failure to standard PHP error log
            error_log("Audit logging failed for action '{$action}': " . $e->getMessage());
        }
    }

    /**
     * Validates session integrity markers (User Agent and IP Address).
     * Should be called after confirming user_id is set in session.
     *
     * @return bool True if markers are present and match, false otherwise.
     */
    protected function validateSessionIntegrity(): bool { // Changed from private to protected
        // Check if essential markers exist
        if (!isset($_SESSION['user_agent']) || !isset($_SESSION['ip_address'])) {
             $this->logSecurityEvent('session_integrity_markers_missing', ['user_id' => $_SESSION['user_id'] ?? null]);
            return false; // Markers should have been set on login
        }

        // Compare User Agent
        $userAgentMatch = ($_SESSION['user_agent'] === ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        // Compare IP Address (allow simple mismatch logging for now, strict check below)
        $ipAddressMatch = ($_SESSION['ip_address'] === ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'));

        if (!$userAgentMatch || !$ipAddressMatch) {
             $this->logSecurityEvent('session_integrity_mismatch', [
                 'user_id' => $_SESSION['user_id'] ?? null,
                 'session_ip' => $_SESSION['ip_address'],
                 'current_ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                 'ip_match' => $ipAddressMatch,
                 'session_ua' => $_SESSION['user_agent'],
                 'current_ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                 'ua_match' => $userAgentMatch
             ]);
             // Decide if mismatch should invalidate session - usually yes for strict security
             return false; // Treat mismatch as invalid
        }
        return true;
    }

    /**
     * Checks if the session regeneration interval has passed.
     *
     * @return bool True if session should be regenerated, false otherwise.
     */
    protected function shouldRegenerateSession(): bool { // Changed from private to protected
        $interval = SECURITY_SETTINGS['session']['regenerate_id_interval'] ?? 900; // Default 15 mins from config
        // Check if last_regeneration is set and if interval has passed
        return !isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > $interval;
    }

    /**
     * Regenerates the session ID securely, preserving necessary session data.
     */
    protected function regenerateSession(): void { // Changed from private to protected
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return; // Can't regenerate if session not active
        }

        // Store essential data to transfer to the new session ID
        $currentSessionData = $_SESSION;

        if (session_regenerate_id(true)) { // Destroy old session data associated with the old ID
            // Restore data - may need more specific keys depending on what needs preserving
             $_SESSION = $currentSessionData; // Restore all data
             // Crucially, update the regeneration timestamp
             $_SESSION['last_regeneration'] = time();
        } else {
             // Log failure if regeneration fails (critical)
             $userId = $_SESSION['user_id'] ?? 'Unknown';
             error_log("CRITICAL: Session regeneration failed for user ID: " . $userId);
             $this->logSecurityEvent('session_regeneration_failed', ['user_id' => $userId]);
             // Consider terminating the session as a safety measure
             $this->terminateSession('Session regeneration failed.');
        }
    }

    /**
     * Terminates the current session securely.
     * Logs the reason and redirects to login page.
     *
     * @param string $reason The reason for termination (for logging).
     */
    protected function terminateSession(string $reason): void { // Already protected, added return type hint
        $userId = $_SESSION['user_id'] ?? null;
        $this->logSecurityEvent('session_terminated', [
            'reason' => $reason,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
        ]);

        // Standard session destruction steps
        $_SESSION = array(); // Unset all variables
        if (ini_get("session.use_cookies")) { // Delete the session cookie
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy(); // Destroy session data on server

        // Redirect to login page
        $this->redirect('index.php?page=login&reason=session_terminated'); // Use redirect helper
    }

    /**
     * Checks and enforces rate limits for a specific action based on IP address.
     * Uses APCu as the backend cache. Throws Exception on limit exceeded.
     *
     * @param string $action The identifier for the action being rate limited (e.g., 'login', 'password_reset').
     * @throws Exception If rate limit is exceeded (HTTP 429 implied).
     */
    protected function validateRateLimit(string $action): void { // Add return type hint
        // Check if rate limiting is enabled globally
        if (!isset(SECURITY_SETTINGS['rate_limiting']['enabled']) || !SECURITY_SETTINGS['rate_limiting']['enabled']) {
            return; // Skip if disabled
        }

        // Determine settings for this specific action
        $defaultSettings = [
            'window' => SECURITY_SETTINGS['rate_limiting']['default_window'] ?? 3600,
            'max_requests' => SECURITY_SETTINGS['rate_limiting']['default_max_requests'] ?? 100
        ];
        $settings = SECURITY_SETTINGS['rate_limiting']['endpoints'][$action] ?? $defaultSettings;

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        if ($ip === 'UNKNOWN') {
             error_log("Rate Limiting Warning: Cannot determine client IP address for action '{$action}'.");
             return;
        }

        // Check whitelist
        if (!empty(SECURITY_SETTINGS['rate_limiting']['ip_whitelist']) && in_array($ip, SECURITY_SETTINGS['rate_limiting']['ip_whitelist'])) {
            return; // Skip whitelisted IPs
        }

        // Use APCu for rate limiting (Ensure APCu extension is installed and enabled)
        if (function_exists('apcu_enabled') && apcu_enabled()) {
            $key = "rate_limit:{$action}:{$ip}";
            // Fetch attempts *atomically* if possible, otherwise handle potential race condition
            // apcu_inc returns the new value, apcu_add returns true/false
             $current_attempts = apcu_inc($key);

             if ($current_attempts === false) { // Key didn't exist or another issue
                  // Try adding the key with count 1 and TTL
                  if (apcu_add($key, 1, $settings['window'])) {
                      $current_attempts = 1;
                  } else {
                      // If add failed, it might mean it was just created by another request - try incrementing again
                      $current_attempts = apcu_inc($key);
                      if ($current_attempts === false) {
                           // Still failed, maybe APCu issue? Log error and potentially skip check
                           error_log("Rate Limiting Error: Failed to initialize or increment APCu key '{$key}'.");
                           $this->logSecurityEvent('rate_limit_backend_error', ['action' => $action, 'ip' => $ip, 'key' => $key]);
                           return; // Fail open in this edge case? Or throw 500?
                      }
                  }
             }


            if ($current_attempts > $settings['max_requests']) {
                $this->logSecurityEvent('rate_limit_exceeded', [
                    'action' => $action, 'ip' => $ip, 'limit' => $settings['max_requests'], 'window' => $settings['window']
                ]);
                throw new Exception('Rate limit exceeded. Please try again later.', 429);
            }
        } else {
            error_log("Rate Limiting Warning: APCu extension is not available or enabled. Rate limiting skipped for action '{$action}' from IP {$ip}.");
            $this->logSecurityEvent('rate_limit_backend_unavailable', ['action' => $action, 'ip' => $ip]);
        }
    }


    /**
     * Logs a security-related event to the designated security log file.
     *
     * @param string $event A code for the security event (e.g., 'login_failure', 'csrf_validation_failed').
     * @param array $details Contextual details about the event.
     */
     protected function logSecurityEvent(string $event, array $details = []): void { // Add return type hint
         // Add common context automatically
         $commonContext = [
             'timestamp' => date('Y-m-d H:i:s T'), // Include timezone
             'user_id' => $this->getUserId(), // Use helper method
             'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
             'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
             'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
             'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A'
         ];
         $logDetails = array_merge($commonContext, $details); // Merge, letting specific details override common ones if needed

         $logMessage = sprintf(
             "[SECURITY] Event: %s | Details: %s",
             $event,
             json_encode($logDetails) // Encode all details as JSON
         );

         // Log to the file specified in config
         $logFile = SECURITY_SETTINGS['logging']['security_log'] ?? (__DIR__ . '/../logs/security.log');
         // Ensure directory exists and is writable (simple check)
         $logDir = dirname($logFile);
         if (!is_dir($logDir)) { @mkdir($logDir, 0750, true); } // Attempt creation

         if (is_writable($logDir) && (file_exists($logFile) ? is_writable($logFile) : true) ) {
              error_log($logMessage . PHP_EOL, 3, $logFile);
         } else {
              // Fallback to standard PHP error log if specific log file isn't writable
              error_log("Security Log Write Failed! " . $logMessage);
         }
      }

} // End of BaseController class

```

---

**Updated File: `models/Product.php`**

```php
<?php
class Product {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        // Added category name join for potential admin list use
        $stmt = $this->pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC for consistency
    }

    public function getFeatured() {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name,
                   CASE
                       WHEN p.highlight_text IS NOT NULL THEN p.highlight_text
                       WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                       WHEN DATEDIFF(NOW(), p.created_at) <= 30 THEN 'New'
                       ELSE NULL
                   END as display_badge
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT 6
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        if ($product) {
            // Use null coalescing for safety
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        return $product ?: null; // Return null if not found
    }

    public function getByCategory($categoryId) {
        $stmt = $this->pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY p.id DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    /**
     * Creates a new product with extended fields.
     *
     * @param array $data Associative array of product data. Expected keys:
     *              name, description, short_description, price, category_id, image_url,
     *              sku, stock_quantity, initial_stock, low_stock_threshold,
     *              backorder_allowed, featured, created_by
     * @return int|false The ID of the newly created product or false on failure.
     */
    public function create($data) {
        // --- START: Updated create method ---
        $sql = "
            INSERT INTO products (
                name, description, short_description, price, category_id, image, sku,
                stock_quantity, initial_stock, low_stock_threshold, backorder_allowed,
                is_featured, created_at, updated_at, created_by, updated_by,
                size, scent_profile, origin, benefits, ingredients, usage_instructions, gallery_images
            ) VALUES (
                :name, :description, :short_description, :price, :category_id, :image_url, :sku,
                :stock_quantity, :initial_stock, :low_stock_threshold, :backorder_allowed,
                :featured, NOW(), NOW(), :created_by, :updated_by,
                :size, :scent_profile, :origin, :benefits, :ingredients, :usage_instructions, :gallery_images
            )
        ";
        $stmt = $this->pdo->prepare($sql);

        // Prepare data for binding, ensuring defaults and correct types
        $params = [
            ':name' => $data['name'] ?? null,
            ':description' => $data['description'] ?? null,
            ':short_description' => $data['short_description'] ?? null,
            ':price' => isset($data['price']) ? (float)$data['price'] : null,
            ':category_id' => isset($data['category_id']) ? (int)$data['category_id'] : null,
            ':image_url' => $data['image_url'] ?? '/images/placeholder.jpg', // Default image
            ':sku' => $data['sku'] ?? null,
            ':stock_quantity' => isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0,
            ':initial_stock' => isset($data['initial_stock']) ? (int)$data['initial_stock'] : ($data['stock_quantity'] ?? 0), // Default initial to current if not provided
            ':low_stock_threshold' => isset($data['low_stock_threshold']) ? (int)$data['low_stock_threshold'] : 5,
            ':backorder_allowed' => isset($data['backorder_allowed']) ? (int)(bool)$data['backorder_allowed'] : 0,
            ':featured' => isset($data['featured']) ? (int)(bool)$data['featured'] : 0,
            ':created_by' => $data['created_by'] ?? null, // User ID of creator
            ':updated_by' => $data['created_by'] ?? null, // Initially same as creator
            // Additional fields
            ':size' => $data['size'] ?? null,
            ':scent_profile' => $data['scent_profile'] ?? null,
            ':origin' => $data['origin'] ?? null,
             // Handle JSON fields - Assume input is array or string, store as JSON
             ':benefits' => isset($data['benefits']) ? json_encode($data['benefits']) : null,
             ':ingredients' => $data['ingredients'] ?? null,
             ':usage_instructions' => $data['usage_instructions'] ?? null,
             ':gallery_images' => isset($data['gallery_images']) ? json_encode($data['gallery_images']) : null,
        ];

        // Handle potential NULL values correctly for foreign keys etc.
        if ($params[':category_id'] === 0) $params[':category_id'] = null;
        if ($params[':created_by'] === 0) $params[':created_by'] = null;
        if ($params[':updated_by'] === 0) $params[':updated_by'] = null;

        $success = $stmt->execute($params);

        return $success ? (int)$this->pdo->lastInsertId() : false;
        // --- END: Updated create method ---
    }

    /**
     * Updates an existing product with extended fields.
     *
     * @param int $id Product ID to update.
     * @param array $data Associative array of product data to update. Expected keys match create().
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        // --- START: Updated update method ---
        // Build SET clause dynamically based on provided data
        $setClauses = [];
        $params = [':id' => $id];

        // Map input data keys to database columns and prepare SET clauses/params
        $fieldMap = [
            'name' => 'name', 'description' => 'description', 'short_description' => 'short_description',
            'price' => 'price', 'category_id' => 'category_id', 'image_url' => 'image',
            'sku' => 'sku', 'stock_quantity' => 'stock_quantity', 'initial_stock' => 'initial_stock',
            'low_stock_threshold' => 'low_stock_threshold', 'backorder_allowed' => 'backorder_allowed',
            'featured' => 'is_featured', 'size' => 'size', 'scent_profile' => 'scent_profile',
            'origin' => 'origin', 'benefits' => 'benefits', 'ingredients' => 'ingredients',
            'usage_instructions' => 'usage_instructions', 'gallery_images' => 'gallery_images',
            'updated_by' => 'updated_by' // Add updated_by
        ];

        foreach ($fieldMap as $dataKey => $dbColumn) {
            if (isset($data[$dataKey])) {
                $setClauses[] = "`{$dbColumn}` = :{$dataKey}"; // Use backticks for column names
                $value = $data[$dataKey];
                // Handle specific types
                if (in_array($dbColumn, ['category_id', 'stock_quantity', 'initial_stock', 'low_stock_threshold', 'updated_by'])) {
                    $value = ($value === '' || $value === null) ? null : (int)$value; // Allow null or cast to int
                } elseif (in_array($dbColumn, ['backorder_allowed', 'is_featured'])) {
                    $value = (int)(bool)$value; // Cast boolean to int
                } elseif ($dbColumn === 'price') {
                    $value = ($value === '' || $value === null) ? null : (float)$value; // Allow null or cast to float
                } elseif (in_array($dbColumn, ['benefits', 'gallery_images'])) {
                     // Assume $value is already an array or string meant to be JSON encoded
                     $value = json_encode($value);
                 }
                $params[":{$dataKey}"] = $value;
            }
        }

        // Add updated_at timestamp
        $setClauses[] = "updated_at = NOW()";

        if (empty($setClauses)) {
            return true; // No fields to update
        }

        $sql = "UPDATE products SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        // Handle potential NULL values for foreign keys
        if (isset($params[':category_id']) && $params[':category_id'] === 0) $params[':category_id'] = null;
        if (isset($params[':updated_by']) && $params[':updated_by'] === 0) $params[':updated_by'] = null;


        return $stmt->execute($params);
        // --- END: Updated update method ---
    }


    public function delete($id) {
        // Add related data deletion if necessary (e.g., product attributes, inventory movements?)
        // Consider soft delete (setting is_active=0) instead of hard delete.
        // For now, keeping hard delete as per original structure.
        try {
            $this->pdo->beginTransaction();
            // Delete related attributes first (if any)
            $stmtAttr = $this->pdo->prepare("DELETE FROM product_attributes WHERE product_id = ?");
            $stmtAttr->execute([$id]);
            // Delete inventory movements (optional - might want to keep for history)
            // $stmtInv = $this->pdo->prepare("DELETE FROM inventory_movements WHERE product_id = ?");
            // $stmtInv->execute([$id]);
            // Delete from cart items (important)
            $stmtCart = $this->pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
            $stmtCart->execute([$id]);
            // Delete from order items (set product_id to NULL? or prevent deletion if ordered?)
            // For now, we'll prevent deletion if it exists in order_items
            $stmtCheckOrder = $this->pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $stmtCheckOrder->execute([$id]);
            if ($stmtCheckOrder->fetchColumn() > 0) {
                throw new Exception("Cannot delete product: It exists in past orders.");
            }

            // Finally, delete the product
            $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $deleted = $stmt->execute([$id]);
            $this->pdo->commit();
            return $deleted;
        } catch (Exception $e) {
             $this->pdo->rollBack();
             error_log("Product delete error (ID: {$id}): " . $e->getMessage());
             // Re-throw the exception to be caught by the controller
             throw $e;
        }
    }

    // --- SEARCH/FILTER ---
    public function search($query, $limit = 10) { // Added limit parameter
        $stmt = $this->pdo->prepare("
            SELECT id, name, image, price FROM products
            WHERE name LIKE ? OR description LIKE ? OR sku LIKE ?
            ORDER BY name ASC
            LIMIT ?
        ");
        $searchTerm = "%{$query}%";
        $stmt->bindValue(1, $searchTerm);
        $stmt->bindValue(2, $searchTerm);
        $stmt->bindValue(3, $searchTerm);
        $stmt->bindValue(4, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getAllCategories() {
        $stmt = $this->pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getFiltered($conditions = [], $params = [], $sortBy = 'name_asc', $limit = 12, $offset = 0) {
        // --- START: Updated getFiltered method ---
        // Use conditions provided by Controller directly (controller adds 'p.' prefix)
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions); // Directly use conditions
        }
        // Sorting
        switch ($sortBy) {
            case 'price_asc': $sql .= " ORDER BY p.price ASC, p.name ASC"; break; // Added secondary sort
            case 'price_desc': $sql .= " ORDER BY p.price DESC, p.name ASC"; break; // Added secondary sort
            case 'name_desc': $sql .= " ORDER BY p.name DESC"; break;
            case 'created_at_desc': $sql .= " ORDER BY p.created_at DESC"; break; // Added created_at sort
            case 'name_asc': default: $sql .= " ORDER BY p.name ASC"; break;
        }
        $sql .= " LIMIT :limit OFFSET :offset"; // Use named placeholders
        $stmt = $this->pdo->prepare($sql);

        // Bind WHERE clause parameters (if any)
        $paramIndex = 1;
        foreach ($params as $value) {
            $stmt->bindValue($paramIndex++, $value); // Use positional binding for WHERE params
        }
        // Bind LIMIT/OFFSET parameters by name
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        foreach ($products as &$product) {
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        unset($product); // Unset reference
        return $products;
        // --- END: Updated getFiltered method ---
    }

    public function getPriceRange() {
        $stmt = $this->pdo->query("
            SELECT MIN(price) as min_price, MAX(price) as max_price
            FROM products
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getProductsByIds($ids) {
        if (empty($ids) || !is_array($ids)) { // Added check for array
            return [];
        }
        // Ensure all IDs are integers
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) { return $id > 0; }); // Remove non-positive IDs
        if (empty($ids)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->pdo->prepare("
            SELECT * FROM products
            WHERE id IN ($placeholders)
            ORDER BY FIELD(id, $placeholders)
        ");

        // Double the IDs array since we need it twice in the query
        $params = array_merge($ids, $ids);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function searchWithFilters($query, $categoryId = null, $minPrice = null, $maxPrice = null) {
        $conditions = ["(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)"]; // Added SKU search, table alias
        $params = ["%$query%", "%$query%", "%$query%"];
        if ($categoryId) {
            $conditions[] = "p.category_id = ?"; // Added table alias
            $params[] = $categoryId;
        }
        if ($minPrice !== null) {
            $conditions[] = "p.price >= ?"; // Added table alias
            $params[] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions[] = "p.price <= ?"; // Added table alias
            $params[] = $maxPrice;
        }
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(" AND ", $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        // This seems redundant with getRelated, preferring getRelated
        return $this->getRelated($categoryId, $productId, $limit);
    }

    public function getRelated($categoryId, $excludeId, $limit = 4) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT ?"
        );
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields for related products
        foreach ($products as &$product) {
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        unset($product); // Unset reference
        return $products;
    }

    public function updateStock($id, $quantity) {
        // Note: This is a simple +/- adjustment. Use InventoryController for audited movements.
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity + ?
            WHERE id = ?
        ");
        return $stmt->execute([$quantity, $id]);
    }

    public function checkStock($id) {
        $stmt = $this->pdo->prepare("
            SELECT stock_quantity, backorder_allowed, low_stock_threshold
            FROM products
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function isInStock($id, $requestedQuantity = 1) {
        $stock = $this->checkStock($id);
        if (!$stock) {
            return false; // Product doesn't exist
        }
        // Allow purchase if backorders are allowed OR if stock is sufficient
        return !empty($stock['backorder_allowed']) || $stock['stock_quantity'] >= $requestedQuantity;
    }

    public function getLowStockProducts($threshold = 5) { // Default threshold if needed
        // Use COALESCE to handle null low_stock_threshold, defaulting comparison to the $threshold param
        $sql = "
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock_quantity <= COALESCE(p.low_stock_threshold, ?)
            ORDER BY p.stock_quantity ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        // Bind the threshold value passed to the function
        $stmt->bindValue(1, (int)$threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function updateStockSettings($id, $threshold, $backorderAllowed) {
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET low_stock_threshold = ?,
                backorder_allowed = ?
            WHERE id = ?
        ");
        // Ensure boolean is correctly cast to int (0 or 1)
        return $stmt->execute([(int)$threshold, (int)(bool)$backorderAllowed, (int)$id]);
    }

    public function getCount($conditions = [], $params = []) {
        // --- START: Updated getCount method ---
        $sql = "SELECT COUNT(p.id) as count FROM products p";
        // Determine if category join is needed based on conditions
        $needsCategoryJoin = false;
        foreach($conditions as $cond) {
            if (strpos($cond, 'c.') !== false || strpos($cond, 'category_id') !== false) { // Check for alias or column name
                $needsCategoryJoin = true;
                break;
            }
        }
        if ($needsCategoryJoin) {
            $sql .= " LEFT JOIN categories c ON p.category_id = c.id";
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions); // Directly use conditions
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
        return $row ? (int)$row['count'] : 0;
        // --- END: Updated getCount method ---
    }

} // End Product Class

```

---

**Summary & Conclusion:**

The compatibility review confirmed that `index.php` and `main.js` are compatible with the refactored `AccountController.php`.

The two reported bugs have been addressed:

1.  **Quiz Redirect:** The `filter_var` validation was removed from `BaseController::redirect()`, allowing relative redirects to function correctly.
2.  **Product Filter SQL Error:** The redundant table alias prefixing logic (`array_map`) was removed from `ProductModel::getFiltered()` and `ProductModel::getCount()`. Controllers are now solely responsible for providing correctly prefixed conditions.

The updated `controllers/BaseController.php` and `models/Product.php` files contain these fixes. The remaining PHP files were reviewed and found to be compatible with the core routing, security patterns, and controller/model interactions. The project structure and core functionalities appear sound, with the noted exceptions of cart storage inconsistency and inconsistent rate limiting application.

---
https://drive.google.com/file/d/15n5Z5mfWZR34xCedeNAAM6yIfm1-FWHU/view?usp=sharing, https://drive.google.com/file/d/16GeRFaJufIhZkc6PiZEGOXcdsOd7tBHu/view?usp=sharing, https://drive.google.com/file/d/1AfNx4eK9bILxDYig8-ns-QefvSvL-Pea/view?usp=sharing, https://drive.google.com/file/d/1BKHUJxNXkL0LaOqLYe_1iR9Pm0WcECAh/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221CERPFoP63KbINXZ9VdrkC3jYm7dwmQXO%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1Ii_et2GgZUMfA_HH1SZlP3hpxiVZap_w/view?usp=sharing, https://drive.google.com/file/d/1TP6uFvb5Z87SPbtrPU8C47ajbkz8IpDU/view?usp=sharing, https://drive.google.com/file/d/1TSiGE6QNnLaJaDR5GQ908SvOzPBx-QfB/view?usp=sharing, https://drive.google.com/file/d/1V5nBvD9m0BQtwPJMbM935eBHtzgq2q5x/view?usp=sharing, https://drive.google.com/file/d/1WQ0bQETlO_rxE59s7byblCDqhTEMu9MX/view?usp=sharing, https://drive.google.com/file/d/1YcBsA8LnyLr4A3LtI1MXKK1L5atjvaGs/view?usp=sharing, https://drive.google.com/file/d/1eT100kdTNr0wJTTQzw7e23BQoxUpDkXf/view?usp=sharing, https://drive.google.com/file/d/1kglCS-e_-vgfchd4cVZXgvHSPagKFcbT/view?usp=sharing, https://drive.google.com/file/d/1m7MjeSIYuSj0Tl4pOdV798bhZQIXSIRc/view?usp=sharing, https://drive.google.com/file/d/1vfgp4mJYTE_XUleYpOQpbzR_ioYZ9LdF/view?usp=sharing, https://drive.google.com/file/d/1zHS8LxhZDjUpgksZsQDaDi0lIXo-VuJa/view?usp=sharing
