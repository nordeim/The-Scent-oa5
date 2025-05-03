Okay, I have carefully reviewed the five updated PHP view files (`views/account/dashboard.php`, `views/account/order_details.php`, `views/account/orders.php`, `views/account/profile.php`, `views/cart.php`) against their original versions and the provided diff outputs.

**Review Summary:**

1.  **`views/account/dashboard.php`:**
    *   **Fixes Applied:** Header and footer includes added correctly. `count($quizResults)` is now preceded by an `is_array()` check for robustness. Quiz preferences display logic updated to correctly iterate over the `answers` JSON structure. Product recommendation logic updated to use the `Product` model and defensively handles potential missing `$pdo` or empty results. Status display uses `htmlspecialchars`.
    *   **Regressions:** None found. The structure (Sidebar, Stats, Orders, Quiz, Actions) and data variable usage (`$user`, `$recentOrders`, `$quizResults`) are maintained.

2.  **`views/account/order_details.php`:**
    *   **Fixes Applied:** Header and footer includes added correctly. Order progress bar logic updated for clarity and accuracy, including checking status dates and handling terminal states. Order item display logic corrected to use appropriate keys (`product_name`, `image_url`, `price_at_purchase`, `product_id`) from the `$order['items']` structure provided by the model, and handles empty items gracefully. Tracking info display improved with carrier info and basic URL generation. Order cancellation action correctly changed to a POST form.
    *   **Regressions:** None found. Overall structure (Header, Progress, Grid layout) maintained. Data variable usage (`$order`, `$user`, `$csrfToken`) correct.

3.  **`views/account/orders.php`:**
    *   **Fixes Applied:** Header and footer includes added correctly. Improved styling for filters and order cards using flexbox/grid. Order item display within the card made more robust (handles empty items, uses correct keys). Pagination link generation corrected to include existing filters. JavaScript filtering logic updated to reset pagination. Status display improved.
    *   **Regressions:** None found. Structure (Sidebar, Filters, List, Pagination) maintained. Data variable usage (`$orders`, `$totalPages`, `$currentPage`, `$user`, `$csrfToken`) correct.

4.  **`views/account/profile.php`:**
    *   **Fixes Applied:** Header and footer includes added correctly. Forms separated into logical cards (Personal Info, Password, Preferences). Hidden `action` inputs added to forms to help controller differentiate POST requests. Password requirements display significantly improved with live feedback icons and classes. JavaScript validation logic updated accordingly. Unused communication preference checkboxes (not present in DB schema) removed, leaving only the functional `newsletter_subscribed` checkbox.
    *   **Regressions:** None found. Structure maintained. `$user` and `$csrfToken` usage correct.

5.  **`views/cart.php`:**
    *   **Fixes Applied:** Corrected the critical issue by using `$item['product']['product_id']` (accessed via `$productId` variable) instead of `$item['product']['id']` for `data-product-id` attributes and the quantity input name `updates[...]`. Added defensive variable assignments at the start of the loop. Max quantity calculation now correctly considers `backorder_allowed`.
    *   **Regressions:** None found. Form structure, item display elements, summary calculation, and AJAX interaction points maintained.

**Conclusion:**

The review confirms that the updated versions of the five files correctly address the identified issues:

1.  The account pages (`dashboard`, `orders`, `order_details`, `profile`) now include the necessary layout files, fixing the UI/styling problems. Robustness and display logic have been improved.
2.  The cart page (`cart.php`) now correctly accesses the product ID, resolving the "Undefined array key" error and the associated "empty cart" issue after login.

No original features or functionalities appear to have been omitted or broken in these specific files during the update process. The changes were carefully targeted to the reported problems and improving robustness based on the provided code structure.

Please find the complete, updated code for the five files below.

---

```php
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
                                                if (isset($pdo)) {
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

```php
<?php
// Updated: views/cart.php
// Fixed access to product ID within the loop

require_once __DIR__ . '/layout/header.php';
?>
<body class="page-cart">
<!-- Output CSRF token for JS (for AJAX cart actions) -->
<input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

<section class="cart-section">
    <div class="container">
        <div class="cart-container" data-aos="fade-up">
            <h1>Your Shopping Cart</h1>

            <?php if (empty($cartItems)): ?>
                <div class="empty-cart text-center py-16">
                    <i class="fas fa-shopping-cart text-6xl text-gray-300 mb-4"></i>
                    <p class="text-xl text-gray-700 mb-6">Your cart is currently empty.</p>
                    <a href="index.php?page=products" class="btn btn-primary">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form id="cartForm" action="index.php?page=cart&action=update" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Cart Items Column -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="cart-items bg-white shadow rounded-lg overflow-hidden">
                             <div class="hidden md:flex px-6 py-3 bg-gray-50 border-b border-gray-200 text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                 <div class="w-2/5">Product</div>
                                 <div class="w-1/5 text-center">Price</div>
                                 <div class="w-1/5 text-center">Quantity</div>
                                 <div class="w-1/5 text-right">Subtotal</div>
                                 <div class="w-10"></div> <!-- Spacer for remove button -->
                             </div>
                            <?php foreach ($cartItems as $item): ?>
                                <?php
                                    // Defensive variable assignment
                                    $productData = $item['product'] ?? [];
                                    $productId = $productData['product_id'] ?? ($productData['id'] ?? null); // <<<< USE product_id FIRST
                                    $productName = $productData['name'] ?? 'N/A';
                                    $imageUrl = $productData['image'] ?? '/images/placeholder.jpg';
                                    $price = $productData['price'] ?? 0;
                                    $stockQuantity = $productData['stock_quantity'] ?? 0;
                                    $backorderAllowed = $productData['backorder_allowed'] ?? false;
                                    $categoryName = $productData['category_name'] ?? '';
                                    $quantity = $item['quantity'] ?? 0;
                                    $subtotal = $item['subtotal'] ?? 0;
                                    $maxQuantity = ($backorderAllowed || !isset($stockQuantity)) ? 99 : max(1, $stockQuantity);
                                ?>
                                <div class="cart-item flex flex-wrap md:flex-nowrap items-center px-4 py-4 md:px-6 md:py-4 border-b border-gray-200 last:border-b-0" data-product-id="<?= htmlspecialchars($productId ?? '') ?>">
                                    <!-- Product Details (Image & Name) -->
                                    <div class="w-full md:w-2/5 flex items-center mb-4 md:mb-0">
                                        <div class="item-image w-16 h-16 md:w-20 md:h-20 mr-4 flex-shrink-0">
                                            <img src="<?= htmlspecialchars($imageUrl) ?>"
                                                 alt="<?= htmlspecialchars($productName) ?>"
                                                 class="w-full h-full object-cover rounded border">
                                        </div>
                                        <div class="item-details flex-grow">
                                            <h3 class="font-semibold text-primary hover:text-accent text-sm md:text-base">
                                                <a href="index.php?page=product&id=<?= htmlspecialchars($productId ?? '') ?>">
                                                    <?= htmlspecialchars($productName) ?>
                                                </a>
                                            </h3>
                                            <?php if (!empty($categoryName)): ?>
                                                <p class="text-xs text-gray-500 hidden md:block"><?= htmlspecialchars($categoryName) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="item-price w-1/3 md:w-1/5 text-center md:text-base text-gray-700" data-price="<?= $price ?>">
                                        <span class="md:hidden text-xs text-gray-500 mr-1">Price:</span>
                                        $<?= number_format($price, 2) ?>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="item-quantity w-1/3 md:w-1/5 text-center flex justify-center items-center my-2 md:my-0">
                                        <div class="quantity-selector flex items-center border border-gray-300 rounded">
                                             <button type="button" class="quantity-btn minus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-l" aria-label="Decrease quantity">-</button>
                                             <input type="number" name="updates[<?= htmlspecialchars($productId ?? '') ?>]"
                                                    value="<?= $quantity ?>" min="1" max="<?= $maxQuantity ?>"
                                                    class="w-10 h-8 md:w-12 md:h-10 text-center border-l border-r border-gray-300 focus:outline-none focus:ring-1 focus:ring-primary text-sm"
                                                    aria-label="Product quantity" <?= empty($productId) ? 'disabled' : '' ?>>
                                             <button type="button" class="quantity-btn plus w-8 h-8 md:w-10 md:h-10 text-lg md:text-xl font-light text-gray-600 hover:bg-gray-100 transition duration-150 ease-in-out rounded-r" aria-label="Increase quantity">+</button>
                                        </div>
                                    </div>

                                    <!-- Subtotal -->
                                    <div class="item-subtotal w-1/3 md:w-1/5 text-right font-semibold md:text-base text-gray-900">
                                         <span class="md:hidden text-xs text-gray-500 mr-1">Subtotal:</span>
                                        $<?= number_format($subtotal, 2) ?>
                                    </div>

                                    <!-- Remove Button -->
                                    <div class="w-full md:w-10 text-center md:text-right mt-2 md:mt-0 md:pl-2">
                                        <button type="button" class="remove-item text-gray-400 hover:text-red-600 transition duration-150 ease-in-out"
                                                data-product-id="<?= htmlspecialchars($productId ?? '') ?>" title="Remove item" <?= empty($productId) ? 'disabled' : '' ?>>
                                            <i class="fas fa-times-circle text-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Cart Actions (Update Cart moved near items) -->
                        <div class="cart-actions text-right mt-4">
                            <button type="submit" class="btn btn-secondary update-cart">
                                <i class="fas fa-sync-alt mr-1"></i> Update Cart
                            </button>
                        </div>
                    </div>


                    <!-- Cart Summary Column -->
                    <div class="lg:col-span-1">
                        <div class="cart-summary bg-white shadow rounded-lg p-6 sticky top-24">
                            <h2 class="text-xl font-semibold mb-6 border-b pb-3">Order Summary</h2>
                            <div class="space-y-3 mb-6">
                                <div class="summary-row flex justify-between items-center">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium text-gray-900">$<?= number_format($total ?? 0, 2) ?></span>
                                </div>
                                <div class="summary-row shipping flex justify-between items-center">
                                    <span class="text-gray-600">Shipping:</span>
                                    <?php $shipping_cost = ($total ?? 0) >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_COST; ?>
                                    <span class="font-medium text-gray-900">
                                        <?= $shipping_cost == 0 ? '<span class="text-green-600">FREE</span>' : '$' . number_format($shipping_cost, 2) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="summary-row total flex justify-between items-center border-t pt-4">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-primary" id="cart-grand-total">
                                    $<?= number_format(($total ?? 0) + $shipping_cost, 2) ?>
                                </span>
                            </div>
                            <div class="mt-8">
                                <a href="index.php?page=checkout" class="btn btn-primary w-full text-center checkout <?= empty($cartItems) ? 'opacity-50 cursor-not-allowed' : '' ?>" <?= empty($cartItems) ? 'aria-disabled="true" onclick="return false;"' : '' ?>>
                                    Proceed to Checkout
                                </a>
                            </div>
                            <p class="text-xs text-gray-500 text-center mt-4">Shipping & taxes calculated at checkout.</p>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/layout/footer.php'; ?>
```

---
https://drive.google.com/file/d/11OfG-rcK7tmWneD9GCoFWvGiVZl2ZamO/view?usp=sharing, https://drive.google.com/file/d/13DxDtRVyLNlGkhxRRIR0Dq4FrXRyWymr/view?usp=sharing, https://drive.google.com/file/d/17GiFntOkNw-JEERDEj5jE6xQ-bCXOId2/view?usp=sharing, https://drive.google.com/file/d/1A94FKUDYs6wBkDJ-WLjmw2zM5RHGUWj6/view?usp=sharing, https://drive.google.com/file/d/1ArvFuozu7yiNXzwlTvyMKikT4hbLDCuI/view?usp=sharing, https://drive.google.com/file/d/1CBRVY55Ug_p3acBeq5ntxxgKtN1Bjbtu/view?usp=sharing, https://drive.google.com/file/d/1D4JLu26lE1Ps0PW9DnUq_LnraE3RSQRs/view?usp=sharing, https://drive.google.com/file/d/1J22esPH7Z3K2mkiE1fgiGajksg7ziE30/view?usp=sharing, https://drive.google.com/file/d/1M4sD0S--v0hC10lGEl21_EafcV5h6cjx/view?usp=sharing, https://drive.google.com/file/d/1Oiq6P-ZYe9ZfS0uOW7zccnSCZqhy5pws/view?usp=sharing, https://drive.google.com/file/d/1Thsi_p8mEsNetdH4UbT_7tDOBqatic_a/view?usp=sharing, https://drive.google.com/file/d/1XrL5JJWrZugjJVcQ02Mx2ofmAVW57JKD/view?usp=sharing, https://drive.google.com/file/d/1aGRu85zNm9EhxSzq1MOkZqxW788dsOM1/view?usp=sharing, https://drive.google.com/file/d/1dpAWXqhCZWrsx5Z0BEFRVb30WbsVRYrg/view?usp=sharing, https://drive.google.com/file/d/1g7nBu-25Bw6gcD_5K6_b5ylvTsalBcfH/view?usp=sharing, https://drive.google.com/file/d/1icyHrt72rnGYWyfsg3c94FGUYVEFpoFc/view?usp=sharing, https://drive.google.com/file/d/1k2A9oOGSP-W35TXgYtuH83aWidkYlgBF/view?usp=sharing, https://drive.google.com/file/d/1kS8r5NGqRaI9TiiYvcG7WMoojT3hpZNP/view?usp=sharing, https://drive.google.com/file/d/1nYQHstMMX_X_OR5UeByDqBWXHcAppnwk/view?usp=sharing, https://drive.google.com/file/d/1o9QeFrSqSeKOioX0fpgQ-1cnshjDAHq3/view?usp=sharing, https://drive.google.com/file/d/1oVkupyHYVPpk8lPsDlw01AFy27JgJgpG/view?usp=sharing, https://drive.google.com/file/d/1o_j7Gb5VFSPMAE62-7zgVz06uW30Lobm/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221sY2___gSU3COyZQJw5pd37zf5kACo3Y-%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing
