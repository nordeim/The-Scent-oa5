<?php
// includes/ErrorHandler.php (Updated v3 - Moved http_response_code)

// Ensure SecurityLogger class is defined before ErrorHandler uses it.
// (It's defined below in this same file)

class ErrorHandler {
    private static $logger; // For optional external PSR logger
    private static ?SecurityLogger $securityLogger = null; // Use type hint, initialize as null
    private static array $errorCount = []; // Use type hint
    private static array $lastErrorTime = []; // Use type hint

    public static function init($logger = null): void {
        self::$logger = $logger;

        // Instantiate SecurityLogger - PDO injection needs careful handling here
        // Since init is static and called early, we rely on the logger's fallback
        if (self::$securityLogger === null) {
            // Assumes SecurityLogger constructor handles PDO optionally or logs if unavailable
            self::$securityLogger = new SecurityLogger();
        }

        // --- Set up handlers ---
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
        // --- End of Set up handlers ---


        // Log rotation setup (Improved checks)
        $logDir = realpath(__DIR__ . '/../logs');
        if ($logDir === false) {
             $potentialLogDir = __DIR__ . '/../logs';
             if (!is_dir($potentialLogDir)) { // Check if directory creation is needed
                if (!@mkdir($potentialLogDir, 0750, true)) { // Attempt creation, suppress errors for logging
                      error_log("FATAL: Failed to create log directory: " . $potentialLogDir . " - Check parent directory permissions.");
                 } else {
                     @chmod($potentialLogDir, 0750); // Try setting permissions after creation
                 }
            } else {
                 // Directory exists but realpath failed (symlink issue?)
                 error_log("Warning: Log directory path resolution failed for: " . $potentialLogDir);
            }
        } elseif (!is_writable($logDir)) {
             error_log("FATAL: Log directory is not writable: " . ($logDir ?: 'N/A') . " - Check permissions.");
        }
    }

    /**
     * Custom error handler. Logs errors and displays an error page.
     */
    public static function handleError(int $errno, string $errstr, string $errfile = '', int $errline = 0): bool {
        // Check if error reporting is suppressed with @
        if (!(error_reporting() & $errno)) {
            return false; // Don't execute the PHP internal error handler
        }

        $error = [
            'type' => self::getErrorType($errno),
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline,
            'context' => self::getSecureContext()
            // No trace available from set_error_handler directly
        ];

        self::trackError($error); // Track frequency
        self::logErrorToFile($error); // Log to file/logger

        // Attempt to display the error page using output buffering for safety.
        ob_start();
        try {
            // --- START FIX: Move http_response_code inside try block ---
            // Set status code IF headers haven't been sent yet.
            if (!headers_sent()) {
                 http_response_code(500);
            } else {
                // Log the fact that we couldn't set the status code.
                error_log("ErrorHandler Warning: Cannot set HTTP 500 status code for handled error (errno: {$errno}), headers already sent. Error: {$errstr} in {$errfile}:{$errline}");
            }
            // --- END FIX ---

            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                self::displayErrorPage($error);
            } else {
                self::displayErrorPage(null); // Display generic error page
            }
            // Send the buffered error page content.
            // This might append to already sent content if headers were sent, which is unavoidable but better than a fatal error.
            echo ob_get_clean();
        } catch (Throwable $displayError) {
             ob_end_clean(); // Clean buffer if error page fails
             // If the error page itself fails, log it and output plain text.
             self::logDisplayError($error, $displayError);
             self::outputPlainTextError($error); // Output plain text fallback
        }

        // Prevent PHP's default error handler from running.
        // For fatal errors (E_ERROR, etc.), PHP might terminate regardless.
        return true;
    }

     /**
      * Custom exception handler. Logs uncaught exceptions and displays an error page.
      */
     public static function handleException(Throwable $exception): void {
        $error = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(), // Include trace
            'context' => self::getSecureContext()
        ];

        // Log the exception details
        self::logErrorToFile($error);

        // Log security exceptions specifically
        if (self::isSecurityError($error)) {
             if(self::$securityLogger) self::$securityLogger->warning("Potentially security-related exception caught", $error);
        }

         // Use output buffering to capture the error page output safely
         ob_start();
         try {
             // --- START FIX: Move http_response_code inside try block ---
             // Set status code IF headers haven't been sent.
             if (!headers_sent()) {
                 http_response_code(500);
             } else {
                 error_log("ErrorHandler Warning: Cannot set HTTP 500 status code for exception, headers already sent. Exception: " . $error['message']);
             }
             // --- END FIX ---

             if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                 self::displayErrorPage($error);
             } else {
                 self::displayErrorPage(null);
             }
             echo ob_get_clean(); // Send buffered output
         } catch (Throwable $displayError) {
              ob_end_clean(); // Discard buffer if error page fails
              self::logDisplayError($error, $displayError);
              self::outputPlainTextError($error); // Output plain text fallback
         }

         exit(1); // Ensure script terminates after handling uncaught exception
     }

     /**
      * Shutdown handler to catch fatal errors.
      */
     public static function handleFatalError(): void {
         $error = error_get_last();

         // Check if it's a fatal error type we want to handle
         if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
             // Create a structured error array similar to handleError/handleException
             $fatalError = [
                 'type' => self::getErrorType($error['type']),
                 'message' => $error['message'],
                 'file' => $error['file'],
                 'line' => $error['line'],
                 'context' => self::getSecureContext(),
                 'trace' => "N/A (Fatal Error)" // No trace available for most fatal errors
             ];

             self::logErrorToFile($fatalError); // Log the fatal error

              // Use output buffering for safety.
              ob_start();
              try {
                   // --- START FIX: Move http_response_code inside try block ---
                   // Attempt to set status code only if headers not sent.
                   if (!headers_sent()) {
                       http_response_code(500);
                   } else {
                        error_log("ErrorHandler Warning: Cannot set HTTP 500 status code during fatal error handling, headers already sent.");
                   }
                   // --- END FIX ---

                   if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                       self::displayErrorPage($fatalError);
                   } else {
                       self::displayErrorPage(null); // Generic error page
                   }
                   echo ob_get_clean(); // Send buffered output
               } catch (Throwable $displayError) {
                   ob_end_clean(); // Discard buffer if error page itself fails
                   self::logDisplayError($fatalError, $displayError);
                   self::outputPlainTextError($fatalError); // Output plain text fallback
               }
              // No exit() here, as script is already shutting down.
         }
     }

     // --- Helper methods ---

     private static function getErrorType(int $errno): string {
        switch ($errno) {
            case E_ERROR: return 'E_ERROR (Fatal Error)';
            case E_WARNING: return 'E_WARNING (Warning)';
            case E_PARSE: return 'E_PARSE (Parse Error)';
            case E_NOTICE: return 'E_NOTICE (Notice)';
            case E_CORE_ERROR: return 'E_CORE_ERROR (Core Error)';
            case E_CORE_WARNING: return 'E_CORE_WARNING (Core Warning)';
            case E_COMPILE_ERROR: return 'E_COMPILE_ERROR (Compile Error)';
            case E_COMPILE_WARNING: return 'E_COMPILE_WARNING (Compile Warning)';
            case E_USER_ERROR: return 'E_USER_ERROR (User Error)';
            case E_USER_WARNING: return 'E_USER_WARNING (User Warning)';
            case E_USER_NOTICE: return 'E_USER_NOTICE (User Notice)';
            case E_STRICT: return 'E_STRICT (Strict Notice)';
            case E_RECOVERABLE_ERROR: return 'E_RECOVERABLE_ERROR (Recoverable Error)';
            case E_DEPRECATED: return 'E_DEPRECATED (Deprecated)';
            case E_USER_DEPRECATED: return 'E_USER_DEPRECATED (User Deprecated)';
            default: return 'Unknown Error Type (' . $errno . ')';
        }
    }

     private static function getSecureContext(): array {
        $context = [
            'url' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
            'timestamp' => date('Y-m-d H:i:s T') // Add timezone
        ];
        // Add user context if available and session started
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Safely access user ID from session, checking both common structures
            $context['user_id'] = $_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? null;
        }
        return $context;
    }

     // Logs error details to the configured log file or PHP's error log.
     private static function logErrorToFile(array $error): void {
        $message = sprintf(
            "[%s] [%s] %s in %s on line %d",
            date('Y-m-d H:i:s T'),
            $error['type'],
            $error['message'],
            $error['file'] ?? 'N/A', // Use null coalescing for safety
            $error['line'] ?? 0     // Use null coalescing for safety
        );
        // Append trace if available
        if (!empty($error['trace'])) {
            $message .= "\nStack trace:\n" . $error['trace'];
        }
        // Append context if available
        if (!empty($error['context'])) {
            $contextJson = json_encode($error['context'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $message .= "\nContext: " . ($contextJson ?: "Failed to encode context: " . json_last_error_msg());
        }

        // Log to external logger if provided (PSR-3 basic compatibility check)
        if (self::$logger && method_exists(self::$logger, 'error')) {
            // Map error type to PSR log level (simplified mapping)
            $level = match (substr($error['type'], 0, 7)) {
                 'E_ERROR', 'E_PARSE', 'E_CORE_', 'E_COMPI' => 'critical',
                 'E_USER_' => 'error',
                 'E_WARNI', 'E_RECOV' => 'warning',
                 'E_DEPRE', 'E_NOTIC', 'E_STRIC' => 'notice', // Grouping notices
                 default => 'error'
            };
             // Call the appropriate PSR-3 method if it exists, otherwise fallback to error
             if (method_exists(self::$logger, $level)) {
                  self::$logger->{$level}($message);
             } else {
                 self::$logger->error($message); // Fallback to error level
             }
        } else {
             error_log($message); // Log to PHP's configured error log
        }

        // Log security related errors using SecurityLogger
        if (self::isSecurityError($error)) {
             // Ensure logger is available before calling
             if(isset(self::$securityLogger) && self::$securityLogger instanceof SecurityLogger) {
                self::$securityLogger->warning("Security-related error detected", $error);
             }
        }
    }

    // Checks if an error message or file indicates a potential security issue.
    private static function isSecurityError(array $error): bool {
        $securityKeywords = ['sql', 'database', 'injection', 'xss', 'cross-site', 'script', 'csrf', 'token', 'auth', 'password', 'login', 'permission', 'credentials', 'unauthorized', 'ssl', 'tls', 'certificate', 'encryption', 'overflow', 'upload', 'file inclusion', 'directory traversal', 'session fixation', 'hijack'];
        $errorMessageLower = strtolower($error['message'] ?? ''); // Use null coalescing
        $errorFileLower = strtolower($error['file'] ?? ''); // Use null coalescing

        foreach ($securityKeywords as $keyword) {
            // Use str_contains (PHP 8+) for better readability
            if (function_exists('str_contains') && str_contains($errorMessageLower, $keyword)) return true;
            // Fallback for PHP < 8
            elseif (!function_exists('str_contains') && strpos($errorMessageLower, $keyword) !== false) return true;
        }
         // Check if error occurs in sensitive files
         if (function_exists('str_contains')) {
             if (str_contains($errorFileLower, 'securitymiddleware.php') || str_contains($errorFileLower, 'auth.php')) {
                return true;
             }
         } else { // Fallback for PHP < 8
              if (strpos($errorFileLower, 'securitymiddleware.php') !== false || strpos($errorFileLower, 'auth.php') !== false) {
                  return true;
              }
         }

        return false;
    }

     // Includes the dedicated error view file.
     private static function displayErrorPage(?array $error = null): void {
        // This method is called within output buffering by the handlers.
        // It includes the error view, which is now self-contained.
        $isDevelopment = defined('ENVIRONMENT') && ENVIRONMENT === 'development';
        // Prepare data for the view, only passing details in development.
        $viewData = [
            'pageTitle' => 'Application Error', // Title for the error page itself
            // Pass the error details only if in development mode
            'error' => ($isDevelopment && $error !== null) ? $error : null
        ];
        // Extract variables into the current scope for the view file
        extract($viewData);

        // Define ROOT_PATH if not already defined globally (needed for view path)
        if (!defined('ROOT_PATH')) {
            // Assuming ErrorHandler.php is in includes/
            define('ROOT_PATH', realpath(__DIR__ . '/..'));
        }
        $errorViewPath = ROOT_PATH . '/views/error.php';

        if (file_exists($errorViewPath) && is_readable($errorViewPath)) {
            include $errorViewPath; // Include the self-contained view
        } else {
            // Fallback inline HTML ONLY if error view is missing (should not happen in production)
            // This fallback is minimal and safe.
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Error</title><style>body{font-family:sans-serif;padding:20px;background-color:#f8f9fa;color:#212529;}h1{color:#dc3545;}p{color:#6c757d;}.error-details{margin-top:20px;padding:15px;background-color:#f8d7da;border:1px solid #f5c6cb;color:#721c24;border-radius:4px;white-space:pre-wrap;word-wrap:break-word;font-size:0.9em;}</style></head><body><h1>Application Error</h1><p>An unexpected error occurred. Please try again later.</p>';
             // Conditionally display basic error info in development mode within the fallback
             if ($isDevelopment && isset($error)) {
                 echo '<div class="error-details"><strong>Details (Development Mode):</strong><br>';
                 echo 'Type: ' . htmlspecialchars($error['type'] ?? 'Unknown') . '<br>';
                 echo 'Message: ' . htmlspecialchars($error['message'] ?? 'N/A') . '<br>';
                 echo 'File: ' . htmlspecialchars($error['file'] ?? 'N/A') . '<br>';
                 echo 'Line: ' . htmlspecialchars($error['line'] ?? 'N/A') . '<br>';
                 // Avoid full trace in basic fallback for brevity/safety
                 echo '</div>';
             }
            echo '</body></html>';
            // Log that the primary error view was missing
            error_log("FATAL: Error view file not found or not readable at: " . $errorViewPath);
        }
     }

     // Logs an error that occurred during the display of the error page itself.
     private static function logDisplayError(array $originalError, Throwable $displayError): void {
         error_log(sprintf(
             "FATAL: Error occurred while displaying error page for original error [%s: %s]. Display Error: %s in %s:%d",
             $originalError['type'] ?? 'Unknown',
             $originalError['message'] ?? 'N/A',
             $displayError->getMessage(),
             $displayError->getFile(),
             $displayError->getLine()
         ));
         // Also log the trace of the error that occurred while displaying the page
         error_log("Display Error Stack Trace:\n" . $displayError->getTraceAsString());
     }

     // Outputs a plain text error message as a last resort.
     private static function outputPlainTextError(array $error): void {
         if (!headers_sent()) {
             // Attempt to send plain text header only if none were sent
             header('Content-Type: text/plain; charset=UTF-8', true, 500);
         }
         // Output might interleave badly if headers were already sent, but it's a fallback.
         echo "A critical error occurred.\n";
         if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
             // Provide more details in development mode plain text fallback
             echo "Error Type: " . ($error['type'] ?? 'Unknown') . "\n";
             echo "Message: " . ($error['message'] ?? 'N/A') . "\n";
             echo "File: " . ($error['file'] ?? 'N/A') . "\n";
             echo "Line: " . ($error['line'] ?? 'N/A') . "\n";
             if (!empty($error['trace'])) {
                 echo "Trace:\n" . $error['trace'] . "\n";
             }
         } else {
             echo "Please check server logs for details or contact support.\n";
         }
     }

    // Tracks error frequency and alerts if threshold is exceeded.
    private static function trackError(array $error): void {
         $errorKey = md5(($error['file'] ?? 'unknown_file') . ($error['line'] ?? '0') . ($error['type'] ?? 'unknown_type'));
         $now = time();
         // Initialize counters/timestamps if not set
         self::$errorCount[$errorKey] = self::$errorCount[$errorKey] ?? 0;
         self::$lastErrorTime[$errorKey] = self::$lastErrorTime[$errorKey] ?? $now;

         // Reset count if more than an hour (3600 seconds) has passed since the start of the window
         if ($now - self::$lastErrorTime[$errorKey] > 3600) {
             self::$errorCount[$errorKey] = 0; // Reset count
             self::$lastErrorTime[$errorKey] = $now; // Reset window start time
         }
         self::$errorCount[$errorKey]++; // Increment count for this error

         // Alert just once when the threshold is first exceeded within the window
         $alertThreshold = defined('ERROR_ALERT_THRESHOLD') ? (int)ERROR_ALERT_THRESHOLD : 10; // Get threshold from config or default
         if (self::$errorCount[$errorKey] === $alertThreshold + 1) {
             // Ensure securityLogger is initialized and available
             if (isset(self::$securityLogger) && self::$securityLogger instanceof SecurityLogger) {
                 self::$securityLogger->alert("High frequency error detected", [
                     'error_type' => $error['type'] ?? 'Unknown',
                     'error_message' => $error['message'] ?? 'N/A',
                     'file' => $error['file'] ?? 'N/A',
                     'line' => $error['line'] ?? 'N/A',
                     'count_in_window' => self::$errorCount[$errorKey],
                     'window_start_time' => date('Y-m-d H:i:s T', self::$lastErrorTime[$errorKey])
                 ]);
             } else {
                 // Fallback log if SecurityLogger isn't available
                 error_log("High frequency error detected but SecurityLogger not available: " . print_r($error, true));
             }
         }
     }

} // End of ErrorHandler class


// --- SecurityLogger Class (Remains unchanged from previous version) ---

class SecurityLogger {
    private string $logFile; // Use type hint
    private ?PDO $pdo = null; // Allow PDO to be nullable or set later

    public function __construct(?PDO $pdo = null) { // Make PDO optional for flexibility
         $this->pdo = $pdo; // Store PDO if provided
        // Define log path using config or default
         $logDir = defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['logging']['security_log'])
                 ? dirname(SECURITY_SETTINGS['logging']['security_log'])
                 : realpath(__DIR__ . '/../logs');

         // Corrected directory check and creation logic
         if ($logDir === false) {
             $potentialLogDir = __DIR__ . '/../logs';
             // Attempt to create if directory check itself failed (e.g. doesn't exist)
             if (!is_dir($potentialLogDir)) {
                 if (!@mkdir($potentialLogDir, 0750, true)) {
                      error_log("SecurityLogger FATAL: Failed to create log directory: " . $potentialLogDir);
                      $this->logFile = '/tmp/security_fallback.log'; // Use fallback
                 } else {
                      @chmod($potentialLogDir, 0750);
                      $logDir = realpath($potentialLogDir); // Try realpath again
                      if (!$logDir) $logDir = $potentialLogDir; // Use path even if realpath fails after creation
                 }
             } else {
                  // Directory exists but realpath failed? Log warning.
                  error_log("SecurityLogger Warning: Log directory path resolution failed for: " . $potentialLogDir);
                  $logDir = $potentialLogDir; // Use the path directly
             }
         }

         if (!$logDir || !is_writable($logDir)) {
             error_log("SecurityLogger FATAL: Log directory is not writable: " . ($logDir ?: 'Not Found'));
             $this->logFile = '/tmp/security_fallback.log'; // Use fallback
         } else {
             $logFileName = defined('SECURITY_SETTINGS') && isset(SECURITY_SETTINGS['logging']['security_log'])
                           ? basename(SECURITY_SETTINGS['logging']['security_log'])
                           : 'security.log'; // Default filename
             $this->logFile = $logDir . '/' . $logFileName;
         }
    }

    // --- Logging Methods (emergency, alert, etc.) ---
     public function emergency(string $message, array $context = []): void { $this->log('EMERGENCY', $message, $context); }
     public function alert(string $message, array $context = []): void { $this->log('ALERT', $message, $context); }
     public function critical(string $message, array $context = []): void { $this->log('CRITICAL', $message, $context); }
     public function error(string $message, array $context = []): void { $this->log('ERROR', $message, $context); }
     public function warning(string $message, array $context = []): void { $this->log('WARNING', $message, $context); }
     public function info(string $message, array $context = []): void { $this->log('INFO', $message, $context); } // Added info level
     public function debug(string $message, array $context = []): void { // Only log debug if enabled
         // Check if ENVIRONMENT constant is defined and set to 'development'
         $isDebug = (defined('ENVIRONMENT') && ENVIRONMENT === 'development');
         // Allow overriding with DEBUG_MODE if defined
         if (defined('DEBUG_MODE')) {
             $isDebug = (DEBUG_MODE === true);
         }

         if ($isDebug) {
             $this->log('DEBUG', $message, $context);
         }
     }

    // --- Private log method ---
    private function log(string $level, string $message, array $context): void {
        $timestamp = date('Y-m-d H:i:s T'); // Add Timezone

        // Include essential context automatically if not provided
        $autoContext = [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            // Attempt to get user ID safely
            'user_id' => (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user']['id']))
                         ? $_SESSION['user']['id']
                         : ((session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : null),
             // 'url' => $_SERVER['REQUEST_URI'] ?? null // Can be verbose
        ];
        // Merge auto-context first, so provided context can override if needed
        $finalContext = array_merge($autoContext, $context);

        // Use json_encode with flags for better readability and error handling
        $contextStr = json_encode($finalContext, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($contextStr === false) {
             $contextStr = "Failed to encode context: " . json_last_error_msg();
        }

        $logMessage = "[{$timestamp}] [{$level}] {$message} | Context: {$contextStr}" . PHP_EOL;

        // Log to file with locking
        // Suppress errors here as we have fallbacks and error logging within this class
        // Check if file exists and is writable one last time
        if (is_writable($this->logFile) || (is_writable(dirname($this->logFile)) && @touch($this->logFile)) ) {
             @file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        } else {
            // Fallback to PHP's error log if primary security log isn't writable
            error_log("SecurityLogger Fallback: Failed to write to {$this->logFile}. Logging message instead: {$logMessage}");
        }

        // Alert admins on critical issues
        if (in_array($level, ['EMERGENCY', 'ALERT', 'CRITICAL'])) {
            $this->alertAdmins($level, $message, $finalContext);
        }
    }

    // --- alertAdmins method ---
    private function alertAdmins(string $level, string $message, array $context): void {
        // Ensure EmailService class exists and is included/autoloaded
        if (!class_exists('EmailService')) {
             error_log("EmailService class not found. Cannot send security alert email.");
             return;
        }
        // Ensure BASE_URL is defined for EmailService constructor/methods
        if (!defined('BASE_URL')) {
             error_log("BASE_URL not defined. Cannot send security alert email.");
             return;
        }

        // Pragmatic Fix: Use global $pdo IF $this->pdo wasn't set during instantiation
        $pdoToUse = $this->pdo;
        if ($pdoToUse === null) {
             global $pdo; // Access global PDO (defined in db.php)
             if (!isset($pdo) || !$pdo instanceof PDO) {
                 error_log("Global PDO not available for SecurityLogger email alert. Cannot send email.");
                 return; // Cannot proceed without PDO
             }
             $pdoToUse = $pdo;
        }

        try {
             // Instantiate EmailService here, passing the required PDO object
             $emailService = new EmailService($pdoToUse);
             // Call the method responsible for sending security alerts
             // Ensure EmailService::sendSecurityAlert exists and accepts these parameters
             if (method_exists($emailService, 'sendSecurityAlert')) {
                 $emailService->sendSecurityAlert($level, $message, $context);
             } else {
                  error_log("EmailService::sendSecurityAlert method not found. Cannot send security alert email.");
             }
        } catch (Throwable $e) { // Catch Throwable for broader error coverage
            // Log failure to send alert email
            error_log("Failed to send security alert email: Level={$level}, Error=" . $e->getMessage() . " Trace: " . $e->getTraceAsString());
        }
    }

} // End of SecurityLogger class
