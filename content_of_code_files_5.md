# includes/EmailService.php  
```php
<?php
// includes/EmailService.php (Updated)

// Assuming PHPMailer is managed via Composer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure the path to autoload.php is correct if using Composer
// If not using Composer, ensure PHPMailer classes are included manually before this class.
// require_once __DIR__ . '/../vendor/autoload.php'; // Uncomment if using Composer

// Include dependencies manually if not using Composer
// require_once __DIR__ . '/../path/to/PHPMailer/src/Exception.php';
// require_once __DIR__ . '/../path/to/PHPMailer/src/PHPMailer.php';
// require_once __DIR__ . '/../path/to/PHPMailer/src/SMTP.php';

require_once __DIR__ . '/../config.php'; // For SMTP constants, BASE_URL
require_once __DIR__ . '/../includes/ErrorHandler.php'; // For SecurityLogger (assuming it's defined there)

class EmailService {
    private PHPMailer $mailer;
    private string $templatePath;
    private SecurityLogger $securityLogger;
    private ?string $dkimPrivateKey = null;
    private array $emailQueue = [];
    private PDO $pdo; // Changed from global to instance property

    // Constructor now accepts PDO dependency
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo; // Store PDO instance
        $this->templatePath = realpath(__DIR__ . '/../views/emails/');
        if ($this->templatePath === false || !is_dir($this->templatePath)) {
             error_log("Email template path invalid or not found: " . __DIR__ . '/../views/emails/');
             // Consider throwing an exception or handling this more gracefully
             $this->templatePath = ''; // Prevent errors later, but emails with templates will fail
        } else {
            $this->templatePath .= '/'; // Ensure trailing slash
        }
        $this->securityLogger = new SecurityLogger(); // Assumes SecurityLogger doesn't need PDO
        $this->loadDKIMKey();
        $this->initializeMailer();
    }

    private function initializeMailer(): void {
        $this->mailer = new PHPMailer(true); // Enable exceptions

        try {
            // Server settings
            if (defined('SMTP_DEBUG') && SMTP_DEBUG) { // Optional debug configuration
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            // disable below 4 for Mailhog. Need to enable back for production
            // $this->mailer->SMTPAuth = true;
            // $this->mailer->Username = SMTP_USER;
            // $this->mailer->Password = SMTP_PASS;
            // $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->Encoding = 'base64';

            // Sender
             if (defined('SMTP_FROM') && defined('SMTP_FROM_NAME')) {
                 $this->mailer->setFrom(SMTP_FROM, SMTP_FROM_NAME);
             } else {
                 error_log("SMTP_FROM or SMTP_FROM_NAME not defined in config.php");
                 // Set a default fallback if needed
                 // $this->mailer->setFrom('noreply@example.com', 'The Scent');
             }


            // Enable DKIM signing if configured
            if ($this->dkimPrivateKey && defined('BASE_URL')) {
                 $domain = parse_url(BASE_URL, PHP_URL_HOST) ?: 'the-scent.com'; // Fallback domain
                $this->mailer->DKIM_domain = $domain;
                $this->mailer->DKIM_private = $this->dkimPrivateKey;
                $this->mailer->DKIM_selector = 'thescent'; // Make this configurable?
                $this->mailer->DKIM_passphrase = ''; // Assuming no passphrase
                $this->mailer->DKIM_identity = $this->mailer->From;
            }

        } catch (Exception $e) {
            $this->logError('Mailer initialization failed: ' . $this->mailer->ErrorInfo);
            // Throwing here might prevent the app from loading if email is critical on startup
            // Consider just logging and letting sending fail later.
            // throw new Exception('Email service initialization failed');
        }
    }

    private function loadDKIMKey(): void {
        // Make DKIM path configurable?
        $keyPath = realpath(__DIR__ . '/../config/dkim/private.key'); // Use realpath for robustness
        if ($keyPath && file_exists($keyPath) && is_readable($keyPath)) {
            $this->dkimPrivateKey = file_get_contents($keyPath);
        } else {
            // Log if DKIM key is expected but not found/readable
            // error_log("DKIM private key not found or not readable at: " . $keyPath);
            $this->dkimPrivateKey = null;
        }
    }

    // Now uses $this->pdo
    private function logEmail(?int $userId, string $emailType, string $recipientEmail, string $subject, string $status, ?string $errorMessage = null): void {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO email_log
                (user_id, email_type, to_email, subject, status, error_message, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId,
                $emailType,
                $recipientEmail,
                $subject,
                $status,
                $errorMessage
            ]);
        } catch (Exception $e) {
            // Log to PHP error log if DB logging fails
            error_log("DB Email logging failed for '{$emailType}' to '{$recipientEmail}': " . $e->getMessage());
        }
    }

    // --- START OF ADDED METHOD ---
    /**
     * Sends a welcome email to a newly registered user.
     *
     * @param string $recipientEmail The email address of the new user.
     * @param string $recipientName The name of the new user.
     * @return bool True on success, false on failure.
     */
    public function sendWelcome(string $recipientEmail, string $recipientName): bool {
        $subject = 'Welcome to The Scent!';
        $template = 'welcome'; // Assumes views/emails/welcome.php exists
        $userId = null; // Usually no user ID known *yet* when sending welcome

        // Data for the email template
        $data = [
            'name' => $recipientName,
            'store_url' => BASE_URL,
            'login_url' => BASE_URL . 'index.php?page=login'
        ];

        try {
            $this->validateEmailAddress($recipientEmail); // Validate recipient

            // Use the generic sendEmail method for consistency
            return $this->sendEmail($recipientEmail, $subject, $template, $data, false, $userId, 'welcome_email');

        } catch (Exception $e) {
            // Error already logged within sendEmail or validation methods
            // Log specific context if needed
            error_log("Failed to initiate welcome email to {$recipientEmail}: " . $e->getMessage());
            return false;
        }
    }
    // --- END OF ADDED METHOD ---


    // Updated sendPasswordReset to use sendEmail method
    public function sendPasswordReset(array $user, string $token, string $resetLink): bool {
         if (!isset($user['email']) || !isset($user['name'])) {
             $this->logError('Invalid user data for password reset', ['user_id' => $user['id'] ?? null]);
             return false; // Or throw exception
         }
         $subject = 'Reset Your Password - The Scent';
         $template = 'password_reset';
         $data = [
             'name' => $user['name'],
             'resetLink' => $resetLink // Pass the pre-generated link
         ];
         // Send with high priority maybe?
         return $this->sendEmail($user['email'], $subject, $template, $data, true, $user['id'], 'password_reset');
     }

    // Updated sendOrderConfirmation to use sendEmail method
    public function sendOrderConfirmation(array $order, array $user): bool {
         if (!isset($user['email']) || !isset($order['id'])) {
             $this->logError('Invalid order/user data for confirmation', ['user_id' => $user['id'] ?? null, 'order_id' => $order['id'] ?? null]);
             return false; // Or throw exception
         }
         $subject = 'Order Confirmation #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
         $template = 'order_confirmation';
         $data = [
             'user' => $user,
             'order' => $order
         ];
         return $this->sendEmail($user['email'], $subject, $template, $data, false, $user['id'], 'order_confirmation');
     }

     // Updated sendShippingUpdate to use sendEmail method
     public function sendShippingUpdate(array $order, array $user, string $trackingNumber, string $carrier): bool {
         if (!isset($user['email']) || !isset($order['id'])) {
             $this->logError('Invalid order/user data for shipping update', ['user_id' => $user['id'] ?? null, 'order_id' => $order['id'] ?? null]);
             return false; // Or throw exception
         }
         $subject = 'Shipping Update - Order #' . str_pad($order['id'], 6, '0', STR_PAD_LEFT);
         $template = 'shipping_update'; // Assumes views/emails/shipping_update.php exists
         $data = [
             'user' => $user,
             'order' => $order,
             'trackingNumber' => $trackingNumber,
             'carrier' => $carrier
             // Add tracking URL if available/needed
         ];
         return $this->sendEmail($user['email'], $subject, $template, $data, false, $user['id'], 'shipping_update');
     }

     // Updated sendNewsletter to use sendEmail method (for consistency, though it was similar)
     public function sendNewsletter(string $email, string $subject, string $template = 'newsletter_general', array $data = []): bool {
         // Assuming a generic newsletter template exists
         return $this->sendEmail($email, $subject, $template, $data, false, null, 'newsletter');
     }

     // Keep sendSecurityAlert as it might have specific formatting/recipient needs
     public function sendSecurityAlert(string $level, string $message, array $context): bool {
        $template = 'security_alert'; // Assumes views/emails/security_alert.php exists
        $subject = "Security Alert [{$level}]: The Scent";
        $recipient = defined('SECURITY_ALERT_EMAIL') ? SECURITY_ALERT_EMAIL : null; // Get recipient from config

        if (!$recipient) {
            $this->logError('SECURITY_ALERT_EMAIL not configured. Cannot send alert.', $context);
            return false;
        }

        $data = [
            'level' => $level,
            'alert_message' => $message, // Use different key to avoid clash if 'message' is in context
            'context' => print_r($context, true), // Format context for email body
            'timestamp' => date('Y-m-d H:i:s T')
        ];

        // Send with high priority
        return $this->sendEmail($recipient, $subject, $template, $data, true, null, 'security_alert');
    }

    // Generic send method - The core sending logic
    // Added $userId and $emailType for centralized logging
    public function sendEmail(string $to, string $subject, string $template, array $data = [], bool $priority = false, ?int $userId = null, string $emailType = 'general'): bool {
        try {
            $this->validateEmailAddress($to);
            $this->validateTemplate($template);

            $html = $this->renderTemplate($template, $data);
            $text = $this->convertToPlainText($html); // Generate plain text version

            // Reset mailer state for this specific email
            $this->mailer->clearAllRecipients(); // Clears all types of recipients (To, CC, BCC)
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();

            // Re-apply necessary headers and settings
            $this->addSecurityHeaders(); // Add custom security headers
            if ($this->dkimPrivateKey) { // Re-apply DKIM if needed
                 $domain = parse_url(BASE_URL, PHP_URL_HOST) ?: 'the-scent.com';
                 $this->mailer->DKIM_domain = $domain;
                 $this->mailer->DKIM_private = $this->dkimPrivateKey;
                 $this->mailer->DKIM_selector = 'thescent';
                 $this->mailer->DKIM_passphrase = '';
                 $this->mailer->DKIM_identity = $this->mailer->From;
             }


            // Add recipient
            $this->mailer->addAddress($to);

            // Set content
            $this->mailer->Subject = $this->sanitizeSubject($subject);
            $this->mailer->isHTML(true);
            $this->mailer->Body = $html;
            $this->mailer->AltBody = $text;

            // Set message priority if requested
            if ($priority) {
                $this->mailer->Priority = 1; // 1 = High, 3 = Normal, 5 = Low
                $this->mailer->AddCustomHeader('X-Priority', '1 (Highest)');
                $this->mailer->AddCustomHeader('Importance', 'High');
            } else {
                 $this->mailer->Priority = 3;
                 $this->mailer->AddCustomHeader('X-Priority', '3 (Normal)');
                 $this->mailer->AddCustomHeader('Importance', 'Normal');
            }

            $sent = $this->mailer->send();

            if ($sent) {
                // Log success to DB
                $this->logEmail($userId, $emailType, $to, $subject, 'sent');
                return true;
            } else {
                // Log failure to DB (PHPMailer exception usually caught below)
                 $this->logEmail($userId, $emailType, $to, $subject, 'failed', $this->mailer->ErrorInfo);
                $this->logError("Email sending failed (Mailer Error): {$this->mailer->ErrorInfo}", ['to' => $to, 'subject' => $subject]);
                 return false;
            }

        } catch (Exception $e) {
            // Log general exception during sending or setup
            $errorMessage = $e->getMessage() . (isset($this->mailer->ErrorInfo) ? " | Mailer Error: " . $this->mailer->ErrorInfo : "");
            $this->logError("Email sending failed (Exception): " . $errorMessage, [
                'to' => $to,
                'subject' => $subject,
                'template' => $template
            ]);
            // Log failure to DB
            $this->logEmail($userId, $emailType, $to, $subject, 'failed', $errorMessage);
            // Optionally re-throw or return false based on desired application flow
            // throw new Exception('Failed to send email');
             return false;
        }
    }


    // --- Helper Methods --- (validateEmailAddress, validateTemplate, renderTemplate, sanitize*, convertToPlainText, addSecurityHeaders)

    private function validateEmailAddress(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->securityLogger->warning('Invalid email format attempted', ['email' => $email]);
            throw new Exception('Invalid email address format');
        }
        // Add domain/MX record check optionally here if needed
    }

    private function validateTemplate(string $template): void {
         if (empty($this->templatePath)) {
             throw new Exception('Email template path is not configured.');
         }
        // Basic check for directory traversal
        if (strpos($template, '..') !== false || strpos($template, '/') !== false || strpos($template, '\\') !== false) {
             $this->securityLogger->error('Potential directory traversal in email template name', ['template' => $template]);
            throw new Exception('Invalid email template name.');
        }
        $templateFile = $this->templatePath . $template . '.php';
        if (!file_exists($templateFile) || !is_readable($templateFile)) {
             $this->logError('Email template not found or not readable', ['template_file' => $templateFile]);
            throw new Exception('Email template not found: ' . $template);
        }
        // Permission check removed - focus on readability and existence. Filesystem permissions are server config.
    }

    private function renderTemplate(string $template, array $data): string {
        if (empty($this->templatePath)) return "Error: Email template path missing."; // Graceful fallback

        $templateFile = $this->templatePath . $template . '.php';
         // Double check existence just before include
         if (!file_exists($templateFile) || !is_readable($templateFile)) {
             error_log("Error: Template file missing or unreadable in renderTemplate: $templateFile");
             return "Error rendering email content."; // Fallback content
         }

        // Sanitize data before extracting
        extract($this->sanitizeTemplateData($data));
        ob_start();
        try {
            include $templateFile;
        } catch (Throwable $t) { // Catch parse errors etc. in template
            error_log("Error including email template ($templateFile): " . $t->getMessage());
            ob_end_clean(); // Clean buffer if include failed
            return "Error rendering email content."; // Fallback content
        }
        return ob_get_clean();
    }

    private function sanitizeTemplateData(array $data): array {
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Allow arrays/objects to pass through for structured data in templates,
            // but ensure strings are escaped. Individual templates must handle nested data safely.
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_scalar($value) || is_null($value)) {
                $sanitized[$key] = $value; // Allow numbers, bools, null
            } else {
                $sanitized[$key] = $value; // Pass arrays/objects as is - template must handle
            }
        }
        return $sanitized;
    }

    private function sanitizeSubject(string $subject): string {
        // Remove characters that could interfere with email headers
        return preg_replace('/[\r\n\t]+/', '', trim($subject));
    }

    private function convertToPlainText(string $html): string {
        // More robust conversion
        $text = $html;
        // Convert links
        $text = preg_replace('/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*?)<\/a>/si', '$3 [$2]', $text);
        // Convert line breaks
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        // Convert paragraphs
        $text = preg_replace('/<\/?p\s*\/?>/i', "\n\n", $text);
        // Remove remaining tags
        $text = strip_tags($text);
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/(\s*\n\s*){3,}/', "\n\n", $text); // Max 2 consecutive newlines
        return trim($text);
    }

    private function addSecurityHeaders(): void {
        // Generate a unique message ID if not already set by PHPMailer
        if (empty($this->mailer->MessageID)) {
            $messageId = sprintf(
                '<%s@%s>',
                bin2hex(random_bytes(16)), // More standard unique ID format
                parse_url(BASE_URL, PHP_URL_HOST) ?: 'the-scent.com'
            );
            $this->mailer->MessageID = $messageId;
        }
        // These are less common/standard for basic emails, but can add minor obscurity
        // $this->mailer->AddCustomHeader('X-Mailer', 'TheScent-SecureMailer/1.0');
        // $this->mailer->AddCustomHeader('X-Content-Type-Options', 'nosniff');
        // $this->mailer->AddCustomHeader('X-XSS-Protection', '1; mode=block');
    }

    // --- Removed Methods ---
    // Removed specific template getter functions like getOrderConfirmationTemplate as renderTemplate is used.
    // Removed queueing logic for simplicity - emails are sent directly. Add back if needed.
    // Removed duplicate sendPasswordResetEmail - use sendPasswordReset.

    // Log Error Helper (uses SecurityLogger)
    private function logError(string $message, array $context = []): void {
        // Log using the SecurityLogger instance
        $this->securityLogger->error($message, $context); // Assuming SecurityLogger has an error method
        // Also log to PHP's error log for visibility
        error_log("EmailService Error: " . $message . " | Context: " . json_encode($context));
    }

    // Ensure processEmailQueue is removed if queueing is removed
    // public function processEmailQueue() { ... }

} // End of EmailService class

```

# views/account/dashboard.php  
```php
<?php
// views/account/dashboard.php
require_once __DIR__ . '/../layout/header.php'; // Standard header include
?>

<section class="account-section">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar Navigation -->
            <aside class="account-sidebar" data-aos="fade-right">
                <div class="account-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <h3><?= htmlspecialchars($user['name'] ?? 'User') ?></h3>
                        <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    </div>

                    <nav>
                        <ul>
                            <li>
                                <a href="index.php?page=account" class="active">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=orders">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                            </li>
                            <li>
                                <a href="index.php?page=account&section=profile">
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
                <h1 class="page-title" data-aos="fade-up">My Account Dashboard</h1>

                <!-- Account Overview -->
                <div class="dashboard-grid">
                    <!-- Quick Stats -->
                    <div class="dashboard-card stats" data-aos="fade-up">
                        <div class="stat-item">
                            <i class="fas fa-shopping-bag"></i>
                            <div class="stat-info">
                                <span class="stat-value"><?= count($recentOrders ?? []) ?></span>
                                <span class="stat-label">Recent Orders</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-box"></i>
                            <div class="stat-info">
                                <?php // Ensure $quizResults is always an array before counting ?>
                                <span class="stat-value"><?= is_array($quizResults ?? []) ? count($quizResults) : 0 ?></span>
                                <span class="stat-label">Saved Preferences</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Orders -->
                    <div class="dashboard-card orders" data-aos="fade-up">
                        <div class="card-header">
                            <h2>Recent Orders</h2>
                            <a href="index.php?page=account&section=orders" class="btn-link">
                                View All <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <?php if (empty($recentOrders)): ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag"></i>
                                <p>No orders yet</p>
                                <a href="index.php?page=products" class="btn-primary">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="order-item">
                                        <div class="order-info">
                                            <span class="order-number">
                                                #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?>
                                            </span>
                                            <span class="order-date">
                                                <?= date('M j, Y', strtotime($order['created_at'])) ?>
                                            </span>
                                        </div>
                                        <div class="order-details">
                                            <span class="order-status <?= htmlspecialchars($order['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                            </span>
                                            <span class="order-total">
                                                $<?= number_format($order['total_amount'], 2) ?>
                                            </span>
                                        </div>
                                        <a href="index.php?page=account&section=orders&id=<?= $order['id'] ?>"
                                           class="btn-secondary">View Details</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Scent Quiz Results -->
                    <div class="dashboard-card quiz" data-aos="fade-up">
                        <div class="card-header">
                            <h2>Your Scent Profile</h2>
                            <a href="index.php?page=account&section=quiz" class="btn-link">
                                View History <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>

                        <?php if (empty($quizResults)): ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <p>Take our scent quiz to discover your perfect match</p>
                                <a href="index.php?page=quiz" class="btn-primary">Take Quiz</a>
                            </div>
                        <?php else: ?>
                            <?php
                                // Ensure $quizResults[0] exists and keys are set before accessing
                                $latestQuiz = $quizResults[0];
                                $preferences = isset($latestQuiz['answers']) ? json_decode($latestQuiz['answers'], true) : [];
                                if (!is_array($preferences)) $preferences = []; // Ensure it's an array
                                // Use 'recommendations' key from DB
                                $recommendedIds = isset($latestQuiz['recommendations']) ? json_decode($latestQuiz['recommendations'], true) : [];
                                if (!is_array($recommendedIds)) $recommendedIds = []; // Ensure it's an array
                            ?>
                            <div class="quiz-results">
                                <div class="scent-preferences">
                                    <h3>Your Preferences</h3>
                                    <?php if (!empty($preferences)): ?>
                                        <ul>
                                            <?php foreach ($preferences as $key => $pref): // Display key/value from answers ?>
                                                <li>
                                                    <i class="fas fa-check"></i>
                                                    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?>: <strong><?= htmlspecialchars($pref) ?></strong>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                     <?php else: ?>
                                        <p>No preferences recorded for this result.</p>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($recommendedIds)): ?>
                                    <div class="recommended-products">
                                        <h3>Recommended Products</h3>
                                        <div class="product-recommendations">
                                            <?php
                                                // Ensure $pdo is available (auto-available via renderView)
                                                if (isset($pdo)) {
                                                    if (!class_exists('Product')) require_once __DIR__ . '/../../models/Product.php';
                                                    $productModel = new Product($pdo);
                                                    $recommendations = $productModel->getProductsByIds($recommendedIds);
                                                    if (empty($recommendations)) {
                                                         echo '<p class="text-gray-500 italic">Could not load recommended products.</p>';
                                                    } else {
                                                         foreach ($recommendations as $product):
                                            ?>
                                                            <div class="recommended-product">
                                                                <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                                                                     alt="<?= htmlspecialchars($product['name']) ?>">
                                                                <div class="product-info">
                                                                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                                                                    <p class="price">$<?= number_format($product['price'], 2) ?></p>
                                                                    <a href="index.php?page=product&id=<?= $product['id'] ?>"
                                                                       class="btn-secondary">View Product</a>
                                                                </div>
                                                            </div>
                                            <?php
                                                          endforeach;
                                                    }
                                                } else {
                                                    echo '<p class="text-red-500">Error: Database connection not available for product recommendations.</p>';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                 <?php else: ?>
                                     <div class="recommended-products">
                                          <h3>Recommended Products</h3>
                                          <p>No specific product recommendations available for this result.</p>
                                     </div>
                                 <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="dashboard-card actions" data-aos="fade-up">
                        <h2>Quick Actions</h2>
                        <div class="action-buttons">
                            <a href="index.php?page=quiz" class="btn-action">
                                <i class="fas fa-sync"></i>
                                Retake Quiz
                            </a>
                            <a href="index.php?page=account&section=profile" class="btn-action">
                                <i class="fas fa-user-edit"></i>
                                Edit Profile
                            </a>
                            <a href="index.php?page=products" class="btn-action">
                                <i class="fas fa-shopping-cart"></i>
                                Shop Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; // Standard footer include ?>

```

# views/account/order_details.php  
```php
<?php
// Updated: views/account/order_details.php
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

<?php require_once __DIR__ . '/../layout/footer.php'; // <<< ADDED ?>

```

# views/account/orders.php  
```php
<?php
// Updated: views/account/orders.php
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

<?php require_once __DIR__ . '/../layout/footer.php'; // <<< ADDED ?>

```

# views/account/profile.php  
```php
<?php
// Updated: views/account/profile.php
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

                    <!-- Communication Preferences -->
                     <div class="profile-card bg-white p-6 rounded-lg shadow md:col-span-2" data-aos="fade-up" data-aos-delay="200">
                         <h2 class="text-xl font-semibold mb-4 border-b pb-2">Communication Preferences</h2>
                         <form action="index.php?page=account&section=profile" method="POST"
                               class="preferences-form space-y-3">
                             <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                             <input type="hidden" name="action" value="update_preferences"> <!-- Specify action -->
                             <?php /* Removed unused checkboxes as they are not in the DB schema
                             <div class="form-group">
                                 <label class="checkbox-label flex items-center">
                                     <input type="checkbox" name="email_marketing"
                                            class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                                            <?php //= ($user['email_marketing'] ?? 0) ? 'checked' : '' ?>>
                                     <span>Promotional emails about new products and special offers</span>
                                 </label>
                             </div>
                             <div class="form-group">
                                 <label class="checkbox-label flex items-center">
                                     <input type="checkbox" name="email_orders"
                                            class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2"
                                            <?php //= ($user['email_orders'] ?? 1) ? 'checked' : '' ?>>
                                     <span>Order status updates and shipping notifications</span>
                                 </label>
                             </div>
                             */ ?>
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

        passwordForm.addEventListener('submit', function(e) {
            // Only perform final check if a new password has been entered
            if (newPassword.value.length > 0) {
                // Check if passwords match
                 if (newPassword.value !== confirmPassword.value) {
                     e.preventDefault();
                     alert('New passwords do not match.');
                     confirmPassword.focus();
                     return;
                 }
                 // Check if password meets requirements according to pattern attribute (browser validation)
                 if (!newPassword.checkValidity()) {
                     e.preventDefault();
                     alert('Please ensure the new password meets all requirements.');
                     newPassword.focus();
                     return;
                 }
            }
             // Current password validation (if new password is set) should be handled server-side for security.
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layout/footer.php'; // <<< ADDED ?>

```

# views/checkout.php  
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

                        <!-- START FIX: Add "Save Address" Checkbox -->
                        <div class="form-group mt-4">
                            <label class="checkbox-label flex items-center text-sm text-gray-700 cursor-pointer font-body">
                                <input type="checkbox" name="save_address" value="1"
                                       class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary mr-2" checked>
                                <span>Save this shipping address to my profile</span>
                            </label>
                        </div>
                        <!-- END FIX -->

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
        // console.log("Initializing Checkout Page JS..."); // Add console log for debugging
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
             // console.log("Stripe Payment Element mounted.");
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
                // console.log("Process Checkout Response Status:", response.status);
                const data = await response.json(); // Try to parse JSON
                // console.log("Process Checkout Response Data:", data);

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
                // console.log("Stripe return_url:", returnUrl); // Log the return URL

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

# includes/auth.php  
```php
<?php
session_set_cookie_params([
    'lifetime' => 3600,
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

function register($email, $password, $name) {
    global $pdo;
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return false;
    }
    
    // Hash password and create user
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
    return $stmt->execute([$email, $hashedPassword, $name]);
}

function login($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Store user data in session (except password)
        unset($user['password']);
        $_SESSION['user'] = $user;
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function logout() {
    unset($_SESSION['user']);
    session_destroy();
}

function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}
```

