<?php
// Environment
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');
// --- MOVED BASE_URL DEFINITION HERE ---
define('BASE_URL', '/'); // Adjust for your environment ('/' for root, '/the-scent/' if in subfolder)
// --- END MOVED BASE_URL DEFINITION ---

// Security Settings
define('SECURITY_SETTINGS', [
    'session' => [
        'lifetime' => 3600,
        'secure' => true, // Requires HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
        'regenerate_id_interval' => 900 // 15 minutes
    ],
    'rate_limiting' => [
        'enabled' => true,
        'default_window' => 3600,
        'default_max_requests' => 100,
        'ip_whitelist' => [], // Add trusted IPs here
        'endpoints' => [
            'login' => ['window' => 300, 'max_requests' => 5],
            'password_reset_request' => ['window' => 3600, 'max_requests' => 3],
            'password_reset_attempt' => ['window' => 300, 'max_requests' => 5],
            'register' => ['window' => 3600, 'max_requests' => 5],
            'newsletter_subscribe' => ['window' => 3600, 'max_requests' => 10],
            'checkout_submit' => ['window' => 60, 'max_requests' => 10],
            'coupon_apply' => ['window' => 300, 'max_requests' => 15],
            'profile_update' => ['window' => 3600, 'max_requests' => 20],
            'address_update' => ['window' => 3600, 'max_requests' => 10],
            'quiz_submit' => ['window' => 60, 'max_requests' => 5]
            // Add other actions as needed
        ]
    ],
    'encryption' => [
        'algorithm' => 'AES-256-CBC',
        'key_length' => 32 // Added key_length for reference if needed
    ],
    'password' => [
        'min_length' => 12,
        'require_special' => true,
        'require_number' => true,
        'require_mixed_case' => true,
        'max_attempts' => 5, // Example: Max login attempts
        'lockout_duration' => 900 // Example: 15 minutes lockout
    ],
    'logging' => [
        'security_log' => __DIR__ . '/logs/security.log',
        'error_log' => __DIR__ . '/logs/error.log', // Keep PHP error log separate maybe
        'audit_log' => __DIR__ . '/logs/audit.log', // Keep audit separate
        'rotation_size' => 10485760, // 10MB (Example)
        'max_files' => 10 // Example: keep last 10 log files
    ],
    'cors' => [ // Cross-Origin Resource Sharing (Example, adjust as needed)
        // Use BASE_URL constant which is now defined above
        'allowed_origins' => [BASE_URL], // <<< THIS LINE CAUSED THE ERROR
        'allowed_methods' => ['GET', 'POST'], // Restrict methods
        'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Accept'], // Common headers
        'expose_headers' => [],
        'max_age' => 0 // Don't cache preflight requests aggressively during dev
    ],
    'csrf' => [
        'enabled' => true, // Keep enabled
        'token_length' => 32, // Standard length
        'token_lifetime' => 3600 // 1 hour validity
    ],
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        // CSP Update: Added *.stripe.com and *.stripe.network
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' https://js.stripe.com https://*.stripe.com; style-src 'self' 'unsafe-inline'; frame-src 'self' https://js.stripe.com https://*.stripe.com; img-src 'self' data: https:; connect-src 'self' https://api.stripe.com https://*.stripe.com https://*.stripe.network",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains' // Enable HSTS if using HTTPS
    ],
    'file_upload' => [ // Example file upload settings
        'max_size' => 5242880, // 5MB
        'allowed_types' => [ // Example MIME types
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf'
        ],
        'scan_malware' => false // Set to true if ClamAV or similar is available
    ]
]);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'the_scent');
define('DB_USER', 'scent_user');
define('DB_PASS', 'StrongPassword123'); // Use environment variables in production
// BASE_URL is defined near the top now

// Stripe Configuration (Replace placeholders with your actual keys)
define('STRIPE_PUBLIC_KEY', 'pk_test_51RLNNX4axRnYhkNVHz16qi7Gq4UnX5LDalYvXf3lIqneXziRQFrzrk0e4dMyBqaKQ8IxmJhSqtpiApC2TaBcIQqS00NJG40ELn');
define('STRIPE_SECRET_KEY', 'sk_test_51RLNNX4axRnYhkNVVM6I6jESZEGNKiI6ALCYm5dEzDLqqA17H0BkTz2Jvq3I3jmeBEFmUDN73AKKiL1Dj5omE2iJ00yutlxS1C');
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_stripe_webhook_secret'); // Get this from your Stripe Dashboard Webhook settings

// Email Configuration
define('SMTP_HOST', 'localhost'); // Or your actual SMTP host
define('SMTP_PORT', 1025); // Common ports: 587 (TLS), 465 (SSL), 25 (unencrypted), 1025 (Mailhog)
define('SMTP_USER', ''); // Your SMTP username (if required)
define('SMTP_PASS', ''); // Your SMTP password (if required)
define('SMTP_FROM', 'noreply@thescent.local'); // Your sending email address
define('SMTP_FROM_NAME', 'The Scent (Dev)'); // Your sender name
define('SMTP_DEBUG', false); // Set to true for verbose debugging during development ONLY

// Application Settings
define('TAX_RATE', 0.10); // Example: 10% tax rate (Not currently used, TaxController handles rates)
define('FREE_SHIPPING_THRESHOLD', 50.00); // Free shipping on orders over $50
define('SHIPPING_COST', 5.99); // Standard shipping cost
define('PASSWORD_RESET_EXPIRY_SECONDS', 3600); // 1 hour validity for password reset tokens
define('NEWSLETTER_SECRET_KEY', 'USE_A_STRONG_RANDOM_SECRET_KEY_HERE_32_CHARS+'); // Replace with a long random string

// Error & Logging
define('ERROR_LOG_PATH', __DIR__ . '/logs/error.log'); // Consolidated error log path
ini_set('log_errors', 1);
ini_set('error_log', ERROR_LOG_PATH);
// Determine error reporting level based on environment
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1); // Display errors ONLY in development
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT); // Log most errors in production
    ini_set('display_errors', 0); // DO NOT display errors in production
}
define('ERROR_ALERT_THRESHOLD', 10); // Example: Alert if same error occurs > 10 times/hour

// Session & Security Defaults (Backup if SECURITY_SETTINGS fails)
ini_set('session.cookie_lifetime', '3600');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_secure', '1'); // Requires HTTPS
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', '3600');

?>
