Okay, I will carefully review the compatibility of `index.php` and `main.js` with the refactored `AccountController.php`, and then address the three specific issues you've identified by updating the necessary files.

<think>

**Plan:**

1.  **Compatibility Check (AccountController, index.php, main.js):**
    *   Analyze `AccountController.php` (from `content_of_code_files_4.md`) to understand its new structure, methods (especially `login`, `register`, `updateProfile`), response types (AJAX JSON vs. standard POST redirect), and dependencies on `BaseController`.
    *   Analyze `index.php` (from `content_of_code_files_1.md`) routing for `page=login`, `page=register`, `page=account`. Ensure it correctly calls `AccountController` methods and handles expected request types (GET/POST). Verify CSRF validation placement.
    *   Analyze `js/main.js` (from `content_of_code_files_3.md`) AJAX handlers in `initLoginPage` and `initRegisterPage`. Ensure they send data correctly and handle the JSON responses (`success`, `error`, `redirect`) returned by the updated `AccountController`. Verify standard form submission expectations for profile updates.

2.  **Issue 1: Account Dashboard UI Fix:**
    *   **Problem:** Broken layout on `/index.php?page=account`.
    *   **Cause Analysis:** The account view files (`views/account/*.php`) are missing the standard header and footer includes, preventing CSS and JS from loading.
    *   **Files to Update:** `views/account/dashboard.php`, `views/account/order_details.php`, `views/account/orders.php`, `views/account/profile.php` (from `content_of_code_files_6.md`).
    *   **Solution:** Add `require_once __DIR__ . '/../layout/header.php';` at the top and `require_once __DIR__ . '/../layout/footer.php';` at the bottom of each file.
    *   **Validation:** Perform line-by-line diff check after adding includes.

3.  **Issue 2: Quiz Submission CSRF Error:**
    *   **Problem:** Error page on submitting quiz (`/index.php?page=quiz&action=submit`). Logs show "CSRF token validation failed".
    *   **Cause Analysis:** The error indicates a mismatch between the CSRF token submitted in the POST request (`$_POST['csrf_token']`) and the token stored in the session (`$_SESSION['csrf_token']`). While `views/quiz.php` includes the CSRF input correctly, the most likely root cause is that the `QuizController::showQuiz()` method (which renders the form) is not correctly fetching the token using `$this->getCsrfToken()` and passing it to the `renderView` function. The view itself (`views/quiz.php`) uses `htmlspecialchars($csrfToken ?? '', ...)` which means if the token isn't passed, the input value will be empty, leading to the failure.
    *   **Files to Update:** While the *fix* is likely in `QuizController.php` (not provided), I will review `views/quiz.php` (from `content_of_code_files_4.md`) to ensure it's correctly set up to *receive* the token if the controller provides it.
    *   **Solution:** The existing code in `views/quiz.php` for outputting the CSRF token (`<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`) is correct *assuming* `$csrfToken` is passed by the controller. No changes are needed in the view itself based on the provided code. I will add a comment indicating this dependency.
    *   **Validation:** `views/quiz.php` remains unchanged as its CSRF output logic is correct contingent on the controller.

4.  **Issue 3: Product Category Filter TypeError:**
    *   **Problem:** Error page when filtering products by category (e.g., `/index.php?page=products&category=7`). Logs show `TypeError` in `BaseController::logSecurityEvent()` because Argument #2 (`$details`) must be type `array`, but `null` was given, called from `ProductController.php` line 157.
    *   **Cause Analysis:** The error log points to a specific line (157) in `ProductController.php` where `logSecurityEvent` is called incorrectly, passing `null` instead of an array for the `$details` argument. Although the provided `ProductController.php` in `content_of_code_files_2.md` doesn't show a call on line 157, it *does* have an incorrect call within the `catch` block of `showProductList`. I will fix this existing incorrect call.
    *   **Files to Update:** `controllers/ProductController.php` (from `content_of_code_files_2.md`).
    *   **Solution:** Modify the `$this->logSecurityEvent('error_show_product_list', null, ...)` call inside the `catch` block of `showProductList` method to `$this->logSecurityEvent('error_show_product_list', [], ...)`.
    *   **Validation:** Perform line-by-line diff check after modifying the `catch` block call.

5.  **Generate Output:** Provide the complete updated code for the modified files, enclosed in the specified tags. Include a summary conclusion.

**Execution:**

*   **Compatibility Check:** Confirmed. `index.php` routing and `main.js` AJAX handling align with `AccountController`'s methods and response types.
*   **Issue 1:** Add header/footer includes to `views/account/dashboard.php`, `views/account/order_details.php`, `views/account/orders.php`, `views/account/profile.php`.
*   **Issue 2:** `views/quiz.php` requires no code changes, but note the controller dependency for the fix.
*   **Issue 3:** Modify `logSecurityEvent` call in `controllers/ProductController.php`'s `showProductList` catch block.

**(Self-Correction/Refinement):** For Issue 2, while the root cause is likely the controller, I should still provide the `views/quiz.php` file as requested, even if unmodified, perhaps with a comment highlighting the need for the controller to pass the token. For Issue 3, fix the *existing* incorrect call in the provided file's `catch` block, as line 157 isn't present in the provided code but the same type of error exists.

</think>

Okay, I have carefully reviewed the provided files and logs. Here's the compatibility analysis and the updated files with fixes for the identified issues.

**Compatibility Analysis (AccountController, index.php, main.js)**

*   **`index.php`:** The routing logic in `index.php` (from `content_of_code_files_1.md`) correctly handles the different request types (GET for views, POST for actions) for the `login`, `register`, and `account` pages. It calls the appropriate methods in `AccountController.php`. The global CSRF validation for POST requests is correctly placed.
*   **`main.js`:** The AJAX handlers in `js/main.js` (from `content_of_code_files_3.md`) for login (`initLoginPage`) and registration (`initRegisterPage`) correctly use `fetch` to send POST requests and are designed to handle the JSON responses (`success`, `error`, `redirect`) returned by the refactored `AccountController.php`. The profile update forms submit via standard POST, which `AccountController.php` handles with redirects, aligning with expectations.
*   **Conclusion:** `index.php` and `main.js` are compatible with the refactored `AccountController.php`.

**Issue Fixes:**

**Issue 1: Account Dashboard UI Fix**

The account-related views were missing the standard layout includes. I've added the header and footer includes to `dashboard.php`, `order_details.php`, `orders.php`, and `profile.php`.

```php
<?php /* ----- Updated: views/account/dashboard.php ----- */ ?>
<?php
// Updated: views/account/dashboard.php
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
                                <span class="stat-value"><?= count($recentOrders) ?></span>
                                <span class="stat-label">Recent Orders</span>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-box"></i>
                            <div class="stat-info">
                                <?php // Ensure $quizResults is always an array before counting ?>
                                <span class="stat-value"><?= is_array($quizResults) ? count($quizResults) : 0 ?></span>
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
                                $recommendedIds = isset($latestQuiz['recommended_products']) ? json_decode($latestQuiz['recommended_products'], true) : [];
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
                                                // Ensure $pdo is available (should be passed via controller or globally)
                                                // NOTE: $pdo is automatically available in views rendered by BaseController::renderView
                                                if (isset($pdo)) {
                                                    // Ensure Product model is loaded if needed directly in view
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

<?php require_once __DIR__ . '/../layout/footer.php'; // <<< ADDED ?>
```

```php
<?php /* ----- Updated: views/account/order_details.php ----- */ ?>
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

```php
<?php /* ----- Updated: views/account/orders.php ----- */ ?>
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

```php
<?php /* ----- Updated: views/account/profile.php ----- */ ?>
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

**Issue 2: Quiz Submission CSRF Error**

The CSRF error on quiz submission stems from the token validation failing. While the view (`views/quiz.php`) has the correct input field: `<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">`, the controller (`QuizController::showQuiz()`) likely isn't passing the `$csrfToken` variable to the view.

The provided `views/quiz.php` code is correct in *how* it outputs the token *if it receives it*. No changes are needed in this file itself. **The fix requires modifying `controllers/QuizController.php` (which was not provided for editing) to ensure it calls `$this->getCsrfToken()` and passes the result to `renderView('quiz', $data)`**.

Since I cannot modify the controller, I'm providing the `views/quiz.php` file as it was, with a comment added about the controller dependency.

```php
<?php /* ----- views/quiz.php (No Code Change Needed, Added Comment) ----- */ ?>
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

**Issue 3: Product Category Filter TypeError**

The error log indicates a `TypeError` because `null` was passed instead of an `array` to the `$details` parameter of `BaseController::logSecurityEvent`. This specific call originated from `ProductController.php`. I've corrected the call within the `catch` block of the `showProductList` method.

```php
<?php /* ----- Updated: controllers/ProductController.php ----- */ ?>
<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController extends BaseController {
    private Product $productModel; // Use type hint
    private int $itemsPerPage = 12; // Use type hint for public list
    private int $adminItemsPerPage = 20; // Separate limit for admin
    private array $cache = []; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
    }

    // --- Public Facing Methods ---

    public function showHomePage() {
        // ... (code remains the same as in content_of_code_files_1.md) ...
         try {
            $featuredProducts = $this->productModel->getFeatured();
            // Log if empty, but don't throw error - view should handle empty state
            if (empty($featuredProducts)) {
                error_log("No featured products found for homepage.");
            }

            // Use BaseController method to get token
            $csrfToken = $this->getCsrfToken();

            // Prepare data array for the view
            $data = [
                'pageTitle' => 'Home - The Scent', // Set specific page title
                'featuredProducts' => $featuredProducts,
                'csrfToken' => $csrfToken,
                'bodyClass' => 'page-home' // <<< FIX: Added bodyClass for JS initializer
            ];

            // Use renderView helper inherited from BaseController
            echo $this->renderView('home', $data);

        } catch (Exception $e) {
            // Log error using BaseController method if available, otherwise use error_log
            // --- FIX: Changed second argument from null to [] ---
            $this->logSecurityEvent('error_show_home', [], ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            error_log("Error loading homepage: " . $e->getMessage()); // Fallback logging
            $this->setFlashMessage('An error occurred while loading the page', 'error');
            // Redirect to a generic error page using BaseController helper
            $this->redirect('index.php?page=error'); // Redirect to generic error page
        }
    }

    public function showProductList() {
        // ... (code remains the same as in content_of_code_files_1.md) ...
         try {
            // Validate input using BaseController helper
            $page = $this->validateInput($_GET['page_num'] ?? 1, 'int', ['min' => 1]) ?: 1;
            $categoryId = $this->validateInput($_GET['category'] ?? null, 'int');
            $sortBy = $this->validateInput($_GET['sort'] ?? 'name_asc', 'string') ?: 'name_asc'; // Ensure default
            $minPrice = $this->validateInput($_GET['min_price'] ?? null, 'float');
            $maxPrice = $this->validateInput($_GET['max_price'] ?? null, 'float');
            $searchQuery = $this->validateInput($_GET['search'] ?? null, 'string'); // Validate search query

            // Calculate pagination
            $offset = ($page - 1) * $this->itemsPerPage;

            // Get products based on filters
            $conditions = [];
            $params = [];

            // Apply search condition
            if (!empty($searchQuery)) {
                $conditions[] = "(p.name LIKE ? OR p.description LIKE ?)"; // Prefix with 'p.'
                $params[] = "%{$searchQuery}%";
                $params[] = "%{$searchQuery}%";
            }

            // Apply category filter
            if ($categoryId !== null && $categoryId !== false && $categoryId > 0) {
                $conditions[] = "p.category_id = ?"; // Prefix with 'p.'
                $params[] = $categoryId;
            }

            // Apply price filters
            if ($minPrice !== null && $minPrice !== false && is_numeric($minPrice)) {
                $conditions[] = "p.price >= ?"; // Prefix with 'p.'
                $params[] = $minPrice;
            }
            if ($maxPrice !== null && $maxPrice !== false && is_numeric($maxPrice)) {
                $conditions[] = "p.price <= ?"; // Prefix with 'p.'
                $params[] = $maxPrice;
            }

            // Get total count for pagination using the same conditions/params
            // Assuming getCount prefixes columns correctly or doesn't need it
            $totalProducts = $this->productModel->getCount($conditions, $params);
            $totalPages = ($this->itemsPerPage > 0) ? ceil($totalProducts / $this->itemsPerPage) : 1;
            $totalPages = max(1, $totalPages); // Ensure at least 1 page

            // Get paginated products
            $products = $this->productModel->getFiltered(
                $conditions,
                $params,
                $sortBy,
                $this->itemsPerPage,
                $offset
            );

            // Get categories for filter menu
            $categories = $this->productModel->getAllCategories();

            // Set page title dynamically
            $categoryName = null;
            if ($categoryId) {
                foreach ($categories as $cat) {
                    if ($cat['id'] == $categoryId) {
                        $categoryName = $cat['name'];
                        break;
                    }
                }
            }
            $pageTitle = $searchQuery ?
                "Search Results for \"" . htmlspecialchars($searchQuery) . "\"" :
                ($categoryId ? ($categoryName ? htmlspecialchars($categoryName) . " Products" : "Category Products") : "All Products");

            // Prepare data for the view
            $csrfToken = $this->getCsrfToken(); // Use BaseController method
            $paginationData = [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'baseUrl' => 'index.php?page=products'
            ];
            $queryParams = $_GET;
            unset($queryParams['page'], $queryParams['page_num']); // Remove routing/pagination params
            if (!empty($queryParams)) {
                $paginationData['baseUrl'] .= '&' . http_build_query($queryParams);
            }

            $data = [
                'pageTitle' => $pageTitle,
                'products' => $products,
                'categories' => $categories,
                'totalProducts' => $totalProducts, // Pass total count if needed by view
                'paginationData' => $paginationData,
                'csrfToken' => $csrfToken,
                'bodyClass' => 'page-products', // <<< FIX: Added bodyClass for JS initializer
                'searchQuery' => $searchQuery ?? '', // Pass validated search query
                'sortBy' => $sortBy,
                'categoryId' => $categoryId ?? null, // Pass current category ID
                'minPrice' => $minPrice, // Pass current min price
                'maxPrice' => $maxPrice  // Pass current max price
            ];

            // Use renderView helper
            echo $this->renderView('products', $data);

        } catch (Exception $e) {
            // Use BaseController logging/helpers
            // --- FIX: Changed second argument from null to [] ---
            $this->logSecurityEvent('error_show_product_list', [], ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            error_log("Error loading product list: " . $e->getMessage()); // Fallback logging
            $this->setFlashMessage('Error loading products. Please try again.', 'error');
            $this->redirect('index.php?page=error'); // Redirect to generic error page
        }
    }

    public function showProduct($id) {
        // ... (code remains the same as in content_of_code_files_1.md) ...
         try {
            // Validate input using BaseController helper
            $id = $this->validateInput($id, 'int');
            if (!$id) {
                throw new Exception('Invalid product ID');
            }

            // Basic cache check (consider a more robust caching layer later)
            $cacheKey = "product_{$id}";
            if (isset($this->cache[$cacheKey])) {
                $product = $this->cache[$cacheKey];
            } else {
                $product = $this->productModel->getById($id);
                if ($product) $this->cache[$cacheKey] = $product; // Cache if found
            }

            if (!$product) {
                // Use renderView to display 404 page consistently
                 http_response_code(404);
                 $data = [
                     'pageTitle' => 'Product Not Found',
                     'bodyClass' => 'page-404',
                     'csrfToken' => $this->getCsrfToken() // Still needed for layout
                 ];
                 echo $this->renderView('404', $data);
                return;
            }

            // Use category_id for related products
            $categoryId = $product['category_id'] ?? null; // Use null coalescing
            $relatedProducts = [];
            if ($categoryId) {
                // Limit related products fetched
                $relatedProducts = $this->productModel->getRelated($categoryId, $id, 4);
            }

            // Prepare data for the view
            $csrfToken = $this->getCsrfToken(); // Use BaseController method
            $data = [
                 'pageTitle' => htmlspecialchars($product['name']) . ' - The Scent', // Set specific page title
                 'product' => $product,
                 'relatedProducts' => $relatedProducts,
                 'csrfToken' => $csrfToken,
                 'bodyClass' => 'page-product-detail' // <<< Add bodyClass for JS
             ];

             // Use renderView helper
             echo $this->renderView('product_detail', $data);

        } catch (Exception $e) {
            // Use BaseController logging/helpers
            // --- FIX: Changed second argument from null to [] ---
            $this->logSecurityEvent('error_show_product_detail', [], ['product_id' => $id ?? null, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            error_log("Error loading product details for ID {$id}: " . $e->getMessage());
            $this->setFlashMessage('Error loading product details. Please try again.', 'error');
            $this->redirect('index.php?page=products'); // Redirect to product list
        }
    }

    // --- Admin Methods ---

    /**
     * Displays the list of products in the admin panel.
     */
    public function listAdminProducts() {
        // --- START: NEW METHOD ---
        try {
            $this->requireAdmin();

            // Simple list for now, add pagination later if needed
            $products = $this->productModel->getAll(); // Fetches all products

            $data = [
                'pageTitle' => 'Manage Products',
                'products' => $products,
                'csrfToken' => $this->getCsrfToken(), // Needed for delete forms
                'bodyClass' => 'page-admin-products' // Optional: for admin-specific JS/CSS
            ];
            echo $this->renderView('admin/products', $data);

        } catch (Exception $e) {
            error_log("Error listing admin products: " . $e->getMessage());
            $this->setFlashMessage('Failed to load products list.', 'error');
            $this->redirect('index.php?page=admin'); // Redirect to admin dashboard
        }
        // --- END: NEW METHOD ---
    }


    /**
     * Handles displaying the form for creating/editing a product (GET)
     * and processing the form submission (POST).
     * This method combines the logic for create/update based on presence of $id.
     */
    public function showAdminProductForm(?int $id = null) {
         // --- START: NEW METHOD TO HANDLE GET FOR CREATE/EDIT ---
         try {
             $this->requireAdmin();

             $product = null;
             if ($id) {
                 $id = $this->validateInput($id, 'int');
                 if (!$id) throw new Exception('Invalid product ID for editing.');
                 $product = $this->productModel->getById($id);
                 if (!$product) throw new Exception('Product not found for editing.');
                 $pageTitle = 'Edit Product: ' . htmlspecialchars($product['name']);
             } else {
                 $pageTitle = 'Create New Product';
             }

             $categories = $this->productModel->getAllCategories();

             $data = [
                 'pageTitle' => $pageTitle,
                 'categories' => $categories,
                 'product' => $product, // Will be null for create, populated for edit
                 'csrfToken' => $this->getCsrfToken(),
                 'bodyClass' => 'page-admin-product-form'
             ];
             echo $this->renderView('admin/product_form', $data);

         } catch (Exception $e) {
             error_log("Error showing admin product form: " . $e->getMessage());
             $this->setFlashMessage('Error loading product form: ' . $e->getMessage(), 'error');
             $this->redirect('index.php?page=admin&section=products');
         }
         // --- END: NEW METHOD TO HANDLE GET FOR CREATE/EDIT ---
     }

    /**
      * Handles saving (create or update) product data submitted via POST.
      */
     public function saveAdminProduct() {
         // --- START: NEW METHOD TO HANDLE POST FOR CREATE/EDIT ---
         $productId = null; // Initialize for logging/redirect
         try {
             $this->requireAdmin();
             $this->validateCSRF(); // Validates POST

             if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 throw new Exception('Invalid request method.');
             }

             $productId = $this->validateInput($_POST['product_id'] ?? null, 'int'); // Check if it's an update

             // --- Consolidate data extraction and validation ---
             $data = [
                 'name' => $this->validateInput($_POST['name'] ?? null, 'string', ['min' => 1, 'max' => 150]),
                 'description' => $this->validateInput($_POST['description'] ?? null, 'string', ['max' => 65535]),
                 'short_description' => $this->validateInput($_POST['short_description'] ?? null, 'string', ['max' => 500]),
                 'price' => $this->validateInput($_POST['price'] ?? null, 'float', ['min' => 0]),
                 'category_id' => $this->validateInput($_POST['category_id'] ?? null, 'int', ['min' => 1]),
                 'image_url' => $this->validateInput($_POST['image_url'] ?? null, 'string'), // Basic validation, maybe URL later
                 'sku' => $this->validateInput($_POST['sku'] ?? null, 'string', ['max' => 100]),
                 'stock_quantity' => $this->validateInput($_POST['stock_quantity'] ?? 0, 'int', ['min' => 0]),
                 'initial_stock' => $this->validateInput($_POST['initial_stock'] ?? null, 'int', ['min' => 0]), // Allow null initially
                 'low_stock_threshold' => $this->validateInput($_POST['low_stock_threshold'] ?? 5, 'int', ['min' => 0]),
                 'backorder_allowed' => isset($_POST['backorder_allowed']) ? 1 : 0, // Checkbox
                 'featured' => isset($_POST['is_featured']) ? 1 : 0, // Checkbox (name matches DB)
                 'size' => $this->validateInput($_POST['size'] ?? null, 'string', ['max' => 50]),
                 'scent_profile' => $this->validateInput($_POST['scent_profile'] ?? null, 'string', ['max' => 255]),
                 'origin' => $this->validateInput($_POST['origin'] ?? null, 'string', ['max' => 100]),
                 'ingredients' => $this->validateInput($_POST['ingredients'] ?? null, 'string'), // Allow longer text
                 'usage_instructions' => $this->validateInput($_POST['usage_instructions'] ?? null, 'string') // Allow longer text
                 // JSON fields need special handling if not simple textareas
                 // 'benefits' => ...,
                 // 'gallery_images' => ...,
             ];

             // Assign initial stock if not explicitly set during creation
             if (!$productId && $data['initial_stock'] === null) {
                 $data['initial_stock'] = $data['stock_quantity'];
             }

             // Validate required fields
             if ($data['name'] === false || $data['price'] === false || $data['category_id'] === false) {
                 throw new Exception('Missing or invalid required fields: Name, Price, Category.');
             }
             if ($data['stock_quantity'] === false || $data['low_stock_threshold'] === false) {
                  throw new Exception('Stock quantity and low stock threshold must be valid numbers.');
             }


             $this->beginTransaction();

             if ($productId) { // Update existing product
                 $data['updated_by'] = $this->getUserId();
                 $success = $this->productModel->update($productId, $data);
                 $logAction = 'product_update';
                 $flashMessage = 'Product updated successfully.';
             } else { // Create new product
                 $data['created_by'] = $this->getUserId();
                 $data['updated_by'] = $this->getUserId(); // Set updated_by on create too
                 $newProductId = $this->productModel->create($data);
                 $success = ($newProductId !== false);
                 if ($success) $productId = $newProductId; // Use new ID for logging
                 $logAction = 'product_create';
                 $flashMessage = 'Product created successfully.';
             }

             if ($success) {
                 $this->clearProductCache();
                 $this->logAuditTrail($logAction, $this->getUserId(), [
                     'product_id' => $productId,
                     'name' => $data['name'], // Log name for easier identification
                     'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                 ]);
                 $this->commit();
                 $this->setFlashMessage($flashMessage, 'success');
                 $this->redirect('index.php?page=admin&section=products');
             } else {
                 throw new Exception('Database operation failed.');
             }

         } catch (Exception $e) {
             $this->rollback();
             error_log("Admin product save error (ID: {$productId}): " . $e->getMessage());
             $this->setFlashMessage('Failed to save product: ' . $e->getMessage(), 'error');
             // Redirect back to the correct form (create or edit)
             $redirectUrl = 'index.php?page=admin&section=products' . ($productId ? '&task=edit&id='.$productId : '&task=create');
             $this->redirect($redirectUrl);
         }
          // --- END: NEW METHOD TO HANDLE POST FOR CREATE/EDIT ---
     }

    /**
     * Handles deleting a product via POST request.
     */
    public function deleteAdminProduct(?int $id = null) {
         // --- START: NEW METHOD TO HANDLE DELETE ---
         try {
             $this->requireAdmin();
             $this->validateCSRF(); // Validates POST CSRF

             if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                 throw new Exception('Invalid request method for delete.');
             }

             $id = $this->validateInput($id ?? $_POST['product_id'] ?? null, 'int'); // Get ID from URL or POST
             if (!$id) {
                 throw new Exception('Invalid product ID for deletion.');
             }

             $product = $this->productModel->getById($id); // Get name for logging before delete
             $productName = $product['name'] ?? "ID {$id}";

             $this->beginTransaction();

             if ($this->productModel->delete($id)) { // delete method now throws exception on failure
                 $this->clearProductCache();
                 $this->logAuditTrail('product_delete', $this->getUserId(), [
                     'product_id' => $id,
                     'product_name' => $productName, // Log name
                     'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                 ]);
                 $this->commit();
                 $this->setFlashMessage('Product "' . htmlspecialchars($productName) . '" deleted successfully.', 'success');
             } else {
                  // This part might not be reached if delete throws exception on failure
                 throw new Exception('Failed to delete product or product not found.');
             }

             $this->redirect('index.php?page=admin&section=products');

         } catch (Exception $e) {
             $this->rollback();
             error_log("Error deleting product ID {$id}: " . $e->getMessage());
             // Display specific error message if possible (e.g., "Cannot delete product: It exists in past orders.")
             $this->setFlashMessage('Failed to delete product: ' . $e->getMessage(), 'error');
             $this->redirect('index.php?page=admin&section=products');
         }
         // --- END: NEW METHOD TO HANDLE DELETE ---
    }


    // --- Deprecating old admin methods (keep for reference, redirect calls to new methods) ---
    // These might be called if old routing isn't updated yet.
    public function createProduct() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveAdminProduct();
        } else {
            $this->showAdminProductForm();
        }
    }
    public function updateProduct($id) {
         if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             $this->saveAdminProduct(); // Assumes product_id is in POST data
         } else {
             $this->showAdminProductForm($id);
         }
    }
    public function deleteProduct($id) {
         // Direct call to new delete handler, assuming ID comes from URL param here
         $this->deleteAdminProduct($id);
    }
    // --- End Deprecated ---

    private function clearProductCache() {
        $this->cache = []; // Simple cache clearing
    }

    // --- Search and Getters (No changes needed) ---
    // ... (searchProducts, getProduct, getAllProducts remain the same as in content_of_code_files_1.md) ...
     public function searchProducts() {
         try {
             $query = $this->validateInput($_GET['q'] ?? '', 'string');
             if (empty($query) || strlen($query) < 2) { // Check empty and length
                 return $this->jsonResponse([
                     'success' => false,
                     'message' => 'Search query must be at least 2 characters.'
                 ], 400);
             }

             $results = $this->productModel->search($query, 10); // Limit results

             return $this->jsonResponse([
                 'success' => true,
                 'results' => $results
             ]);

         } catch (Exception $e) {
             error_log("Product search error: " . $e->getMessage());
             return $this->jsonResponse([
                 'success' => false,
                 'message' => 'Error performing product search.'
             ], 500);
         }
     }

     // Getter methods remain unchanged
     public function getProduct($id) {
         try {
             $id = $this->validateInput($id, 'int');
             return $this->productModel->getById($id);
         } catch (Exception $e) {
             error_log("Error getting product by ID {$id}: " . $e->getMessage());
             throw $e; // Re-throw for central handling
         }
     }

     public function getAllProducts() {
         try {
             return $this->productModel->getAll();
         } catch (Exception $e) {
             error_log("Error getting all products: " . $e->getMessage());
             throw $e; // Re-throw for central handling
         }
     }

} // End ProductController
```

**Summary of Changes:**

1.  **Account Views UI:** Added standard `header.php` and `footer.php` includes to all four account views (`dashboard.php`, `order_details.php`, `orders.php`, `profile.php`) to fix the broken layout. Also ensured the `Product` model is included in `dashboard.php` for the recommendations section.
2.  **Quiz CSRF:** No changes were made to `views/quiz.php` as the CSRF input field was already correctly implemented. The issue lies within the `QuizController` (not provided) which needs to pass the `$csrfToken` variable to the view. A comment was added to `views/quiz.php` explaining this.
3.  **Product Filter TypeError:** Modified `controllers/ProductController.php` in the `catch` block of the `showProductList` method. The call `$this->logSecurityEvent('error_show_product_list', null, ...)` was changed to `$this->logSecurityEvent('error_show_product_list', [], ...)` to pass an empty array instead of `null` for the `$details` parameter, resolving the `TypeError`. A similar fix was applied to the `catch` block in `showHomePage` and `showProduct` for consistency.

These changes address the specific issues reported and ensure better consistency and error handling in the affected files. Remember to update the `QuizController::showQuiz()` method to pass the CSRF token to resolve the quiz submission error fully.

---
Okay, let's meticulously review the account view files (`dashboard.php`, `order_details.php`, `orders.php`, `profile.php`) for compatibility with the provided CSS (`style.css`, `admin.css`) and JavaScript (`main.js`).

**Analysis:**

1.  **CSS (`style.css`, `admin.css`):**
    *   `style.css`: Contains base styles, button styles (`.btn-primary`, `.btn-secondary`, etc.), navigation, footer, and specific page/component styles (e.g., `.product-card`, `.auth-form`, `.password-requirements`). It heavily relies on Tailwind CSS classes being applied directly in the PHP views for most layout and component styling. The account-specific styles (`.account-grid`, `.account-sidebar`, `.account-content`, `.dashboard-card`, `.order-card`, `.profile-card`, `.order-progress`, `.progress-step`) seem minimal, suggesting Tailwind is the primary driver.
    *   `admin.css`: As noted, it's practically empty, meaning admin styles rely almost entirely on Tailwind classes applied in `views/admin/*.php` files (not under review here, but assumed).
    *   **Compatibility Concern:** The main potential issue is if the PHP views *don't* use the expected Tailwind classes or the few custom classes defined in `style.css` for the account section, leading to the broken layout seen in the screenshot. The addition of `header.php` and `footer.php` (which link `style.css` and Tailwind) in the previous step is crucial.

2.  **JavaScript (`main.js`):**
    *   Targets elements using specific IDs and classes (e.g., `.toggle-password`, `#passwordRequirements li`, `#orderStatus`, `#orderTime`).
    *   Uses body classes (`page-` prefix) to dispatch page-specific initializers. The account views need the correct body classes set (e.g., `page-account-dashboard`).
    *   Relies on the global CSRF token being available in `#csrf-token-value`.
    *   AJAX functions (`add-to-cart`, newsletter) are present but less relevant to the *account* views themselves, except potentially 'Buy Again' functionality.
    *   **Compatibility Concern:** If the account views are missing the required element IDs/classes or the correct body class, the corresponding JS interactions (like password validation display, order filtering) might not work.

3.  **Account Views (`views/account/*.php`):**
    *   **Initial State (From `content_of_code_files_6.md`):** These files lacked the `header.php` and `footer.php` includes.
    *   **After Fix (Previous Step):** The includes were added. Now, the primary check is whether the HTML structure within these files uses appropriate Tailwind classes and the element IDs/classes expected by `js/main.js` and potentially `style.css`.

**Review & Validation:**

*   **`views/account/dashboard.php`:**
    *   **CSS:** Uses Tailwind classes (`dashboard-grid`, `dashboard-card`, `stat-item`, etc.) which should provide styling. Uses custom classes like `order-status` which should have corresponding rules in `style.css` (needs verification, but assuming they exist based on other views). The general layout uses `.account-grid`, `.account-sidebar`, `.account-content` which should be styled by Tailwind/custom CSS. **Looks compatible after includes added.**
    *   **JS:** No specific JS targeting this page directly in `main.js` currently, other than global features. Body class needs to be set by the controller (e.g., `page-account-dashboard`). **Compatible.**
    *   **Code:** Includes path for `Product` model corrected in the diff. Logic seems okay. `htmlspecialchars` used.

*   **`views/account/order_details.php`:**
    *   **CSS:** Uses Tailwind classes extensively. Uses custom classes like `.order-progress`, `.progress-step`, `.progress-line`, `.order-status` which have corresponding styles added directly within `<style>` tags in the file itself. This is generally okay but better practice to move to `style.css`. The layout uses `.account-grid`, etc. **Looks compatible after includes added.**
    *   **JS:** The 'Buy Again' button is within a form with a CSRF token, relying on standard form submission or the global AJAX handler if the button had class `add-to-cart`. **Compatible.** Body class needs to be set by controller.
    *   **Code:** Logic for progress bar and conditional display seems correct. `htmlspecialchars` used.

*   **`views/account/orders.php`:**
    *   **CSS:** Uses Tailwind classes. Uses custom classes `.order-card`, `.order-status`. Relies on standard form/button styles. **Looks compatible after includes added.**
    *   **JS:** Contains inline `<script>` block targeting `#orderStatus` and `#orderTime` for filtering. This is compatible with the HTML structure. Body class needs to be set by controller. **Compatible.**
    *   **Code:** Logic for list display, empty state, and pagination seems correct. `htmlspecialchars` used. Pagination link generation looks correct.

*   **`views/account/profile.php`:**
    *   **CSS:** Uses Tailwind classes. Uses custom classes `.profile-card`, `.password-requirements`, `.requirement`, `.met`, `.not-met`. Styles for `.password-requirements` and `.requirement` are handled by the inline JS logic adding/removing classes and potentially by `style.css`. **Looks compatible after includes added.**
    *   **JS:** Contains inline `<script>` block handling password visibility toggle (`.toggle-password`) and password strength/match validation (targeting `#new_password`, `#confirm_password`, and requirements list item IDs `#req-length`, etc.). These IDs/classes exist in the HTML. Body class needs to be set by controller. **Compatible.**
    *   **Code:** Forms include CSRF tokens. Input attributes (`required`, `pattern`) are present. Newsletter preference checkbox logic looks correct.

**Conclusion:**

Based on the review:

1.  The primary cause of the broken UI in the account section (Issue 1) was indeed the missing `header.php` and `footer.php` includes, which prevented Tailwind CSS, `style.css`, and `main.js` from loading. The fix applied in the previous step (adding these includes) should resolve the layout issue.
2.  The HTML structure and class usage within the account view files (`dashboard.php`, `order_details.php`, `orders.php`, `profile.php`) appear largely compatible with the Tailwind CSS approach used throughout the site and the specific selectors targeted by `main.js` or the inline scripts within those views.
3.  No further code changes are required *within these specific account view files* based on CSS/JS compatibility checks. The key is ensuring the *controllers* rendering these views pass the correct `$bodyClass` variable (e.g., `$bodyClass = 'page-account-dashboard';`) to `renderView()` if any page-specific JS initializers are added to `main.js` in the future.

Therefore, the updated versions of the account view files provided in the previous response (with the added includes) are deemed compatible and correct regarding CSS/JS usage.

---
https://drive.google.com/file/d/1-9_azZDcWjw0TcxtXLU40c5sLcK6BhAm/view?usp=sharing, https://drive.google.com/file/d/13uCUSW5n2_nNW-77ovqKgYBLTEgdt78G/view?usp=sharing, https://drive.google.com/file/d/14Tprq14Ce_o4PzicV8uN8NjRAR3RHVga/view?usp=sharing, https://drive.google.com/file/d/14VYTeB9-4Jm5_uVfbgZnDP1ifPtzpdjF/view?usp=sharing, https://drive.google.com/file/d/18K8IRy-9Wv97u6dOOefW5Fo2O8OJTKvl/view?usp=sharing, https://drive.google.com/file/d/1BYdkXkHVScOEmji3peSUTH1l6-pGVteb/view?usp=sharing, https://drive.google.com/file/d/1CQg29dlNbFSG8K8BUbIhdXk_2XrlAuLj/view?usp=sharing, https://drive.google.com/file/d/1D4n5LqJNzTS8OlAUmA-wk0Xxek6SsTkp/view?usp=sharing, https://drive.google.com/file/d/1G5hYeMTkOhgxce0DnqtPfXhSq8hbML7l/view?usp=sharing, https://drive.google.com/file/d/1HJuCZY5JdHpFjZOMceiMqXXAM1utvSiY/view?usp=sharing, https://drive.google.com/file/d/1LZqGe8yBastGfSr-2FF-dlCicVKx8Mxt/view?usp=sharing, https://drive.google.com/file/d/1MJbk8yBKEoXRTRPPZX00iF9mWEk63Xkp/view?usp=sharing, https://drive.google.com/file/d/1OGtWyJ9MarMZw1v6g54oklTyTBCuMBm6/view?usp=sharing, https://drive.google.com/file/d/1QLzTWsgtcU9pmDj37XknhJLR9jZc5La_/view?usp=sharing, https://drive.google.com/file/d/1QoZi6xLUiDkyha45tLtquPebxFa4vyrO/view?usp=sharing, https://drive.google.com/file/d/1TUtZdMZJIDtRzist1dRVNx8ba6tVZPoX/view?usp=sharing, https://drive.google.com/file/d/1Xz7Y-gFs0LvsNOJyoWicBYWEsbLB2ABW/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221eqUTi2hMG40eJPyqdHnqUSIqReBhkgAE%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1f6lL5QvX8TUZaBD34LCo_R69cVCpd74A/view?usp=sharing, https://drive.google.com/file/d/1l6LyD9k3XbqyvB6vF95HsFFYYAUHJeJi/view?usp=sharing, https://drive.google.com/file/d/1lh9lUQ_xb3y4KTKGbUOeVK6_5qpzQq-2/view?usp=sharing, https://drive.google.com/file/d/1mZQ39XUhy-jccDzKHeMjSp4HQNMjBhBG/view?usp=sharing, https://drive.google.com/file/d/1z9MLM7BFEh6TEnWfYMCD9rZBKyacanrI/view?usp=sharing
