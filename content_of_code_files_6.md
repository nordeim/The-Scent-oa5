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

# controllers/QuizController.php  
```php
<?php
// controllers/QuizController.php (Updated: Reverted showResults/processQuiz to session logic)

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Quiz.php';
require_once __DIR__ . '/../models/Product.php'; // Added for fetching product details

class QuizController extends BaseController {
    private Quiz $quizModel; // Use type hint
    private Product $productModel; // Added product model instance

    public function __construct(PDO $pdo) { // Use type hint
        parent::__construct($pdo);
        $this->quizModel = new Quiz($pdo);
        $this->productModel = new Product($pdo); // Initialize product model
    }

    /**
     * Displays the quiz form.
     */
    public function showQuiz() {
        try {
             $questions = $this->quizModel->getQuestions();
             $csrfToken = $this->getCsrfToken(); // Use BaseController method

             $data = [
                 'pageTitle' => 'Scent Finder Quiz',
                 'csrfToken' => $csrfToken,
                 'questions' => $questions,
                 'bodyClass' => 'page-quiz' // For JS initializer
             ];
             echo $this->renderView('quiz', $data); // Use renderView

        } catch (Exception $e) {
            error_log("Error loading quiz questions: " . $e->getMessage());
            $this->setFlashMessage('Failed to load quiz questions. Please try again.', 'error');
            $this->redirect('index.php?page=home'); // Redirect home on error
        }
    }

    /**
     * Processes quiz submission, saves results, stores recommendations in session, and redirects.
     * Logic restored from QuizController.php-orig.txt
     */
    public function processQuiz() {
        $this->validateRateLimit('quiz_submit');
        try {
            // Ensure session is started before CSRF validation or accessing session data
            if (session_status() === PHP_SESSION_NONE) { session_start(); }

            $this->validateCSRF(); // Validate CSRF token

            $startTime = $_SESSION['quiz_start_time'] ?? time(); // Use start time if set previously
            $completionTime = time() - $startTime;
            unset($_SESSION['quiz_start_time']); // Clear start time

            $answers = [];
            // Simplified answer collection based on current quiz form
             if (isset($_POST['mood'])) {
                 $answers['mood'] = $this->validateInput($_POST['mood'], 'string');
             }

            if (empty($answers) || empty($answers['mood']) || !in_array($answers['mood'], ['relaxation', 'energy', 'focus', 'balance'])) {
                 throw new Exception('Please select a valid option.');
            }

            $this->beginTransaction();

            // Get personalized recommendations
            $recommendations = $this->quizModel->getRecommendations($answers);

             // Prepare recommendation IDs for saving
             $recommendationIds = [];
             if (is_array($recommendations)) {
                  foreach ($recommendations as $product) {
                      if (isset($product['id'])) $recommendationIds[] = (int)$product['id'];
                  }
              }

            // Save quiz results if user is logged in
            $userId = $this->getUserId();
            $userEmail = null; // Get email only if needed and available
             if ($userId) {
                 $currentUser = $this->getCurrentUser();
                 $userEmail = $currentUser['email'] ?? null;
             }

            $sessionId = session_id();
            $browserInfo = $_SERVER['HTTP_USER_AGENT'] ?? null;

            // Call saveQuizResult correctly (passing IDs)
             $saveSuccess = $this->quizModel->saveQuizResult(
                 $userId,
                 $userEmail,
                 $answers,
                 $recommendationIds
             );

            if (!$saveSuccess) {
                 error_log("Failed to save quiz result for user " . ($userId ?? 'guest'));
                 // Proceed anyway, but log the error
            }

            $this->commit();

            // Store full recommendations in session for results page (as per original logic)
            $_SESSION['quiz_recommendations'] = $recommendations;
            $this->logAuditTrail('quiz_completed', $userId, ['answers' => $answers, 'recommendations_count' => count($recommendationIds)]);

            // Redirect to results display action using BaseController method
            return $this->redirect('index.php?page=quiz&action=results');

        } catch (Exception $e) {
            $this->rollback();
            error_log("Quiz processing error: " . $e->getMessage());

            $this->setFlashMessage($e->getMessage(), 'error');
            return $this->redirect('index.php?page=quiz');
        }
    }


    /**
      * Displays the quiz results page, showing products stored in the session.
      * Logic restored from QuizController.php-orig.txt
      */
     public function showResults() {
         // Ensure session is started before accessing
         if (session_status() === PHP_SESSION_NONE) { session_start(); }

         // Retrieve recommendations from session
         if (!isset($_SESSION['quiz_recommendations'])) {
             $this->setFlashMessage('Please complete the quiz first to see recommendations.', 'info');
             $this->redirect('index.php?page=quiz');
             return; // Stop execution
         }

         $recommendations = $_SESSION['quiz_recommendations'];
         // Clear recommendations after retrieving them
         unset($_SESSION['quiz_recommendations']);

         $csrfToken = $this->getCsrfToken();
         $data = [
             'pageTitle' => 'Your Scent Recommendations',
             'products' => $recommendations, // Pass recommendations as 'products'
             'csrfToken' => $csrfToken,
             'bodyClass' => 'page-quiz-results' // For JS initializer
         ];

         echo $this->renderView('quiz_results', $data);
     }

    /**
     * Displays quiz analytics in the admin area.
     */
    public function showAnalytics() {
        $this->requireAdmin();

        // Get time range filter from query string, default to 7 days
        $timeRange = $this->validateInput($_GET['range'] ?? '7d', 'string');
        $days = match ($timeRange) {
            '1d' => 1,
            '30d' => 30,
            '90d' => 90,
            'all' => 'all',
            '7d' => 7, // Default
            default => 7,
        };

        // Fetch data using detailed method
        $analyticsData = $this->quizModel->getDetailedAnalytics($days);

        // Handle AJAX request (for dynamic updates)
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return $this->jsonResponse([
                 'success' => true,
                 'data' => $analyticsData // Send the fetched data back
             ]);
        }

        // Handle standard page load
        $data = [
            'pageTitle' => 'Quiz Analytics',
            'analyticsData' => $analyticsData, // Pass initial data
            'currentTimeRange' => $timeRange,
            'csrfToken' => $this->getCsrfToken(),
            'bodyClass' => 'page-admin-quiz-analytics' // For JS initializer
        ];

        echo $this->renderView('admin/quiz_analytics', $data);
    }


    /**
     * Shows the quiz history for the logged-in user.
     * Requires login.
     */
    public function showUserQuizHistory() {
        $this->requireLogin();
        $userId = $this->getUserId();

        try {
             $history = $this->quizModel->getUserPreferenceHistory($userId);
             // Fetch product details for recommended IDs in each history item
             foreach ($history as &$item) {
                 $productIds = $item['recommendations'] ?? [];
                 if (!empty($productIds) && is_array($productIds)) {
                      // Ensure IDs are numeric before fetching
                      $numericIds = array_filter($productIds, 'is_numeric');
                      if (!empty($numericIds)) {
                           $item['recommended_products_details'] = $this->productModel->getProductsByIds($numericIds);
                      } else {
                           $item['recommended_products_details'] = [];
                      }
                 } else {
                      $item['recommended_products_details'] = [];
                 }
             }
             unset($item); // Unset reference

             $data = [
                 'pageTitle' => 'Your Quiz History - The Scent',
                 'history' => $history,
                 'user' => $this->getCurrentUser(), // For sidebar/layout
                 'csrfToken' => $this->getCsrfToken(),
                 'bodyClass' => 'page-account-quiz-history'
             ];
             echo $this->renderView('account/quiz_history', $data); // Assuming view exists

        } catch (Exception $e) {
             error_log("Error fetching user quiz history for user {$userId}: " . $e->getMessage());
             $this->setFlashMessage('Failed to load quiz history.', 'error');
             $this->redirect('index.php?page=account'); // Redirect to dashboard
        }
    }

    // Removed handleQuizSubmission and handleQuiz as processQuiz is the active method based on index.php
    // Removed getAnalytics, getPersonalizedRecommendations, getQuizHistory as they are not directly called by index.php

} // End QuizController class

```

# views/admin/products.php  
```php
<?php
// views/admin/products.php
// Displays the list of products in the admin panel. (Consolidated v16.0)

// Use the admin layout header
require_once __DIR__ . '/../layout/admin_header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-primary"><?= htmlspecialchars($pageTitle ?? 'Manage Products') ?></h1>
        <a href="index.php?page=admin&section=products&task=create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i>Create New Product
        </a>
    </div>

    <?php // Display standard flash messages
    if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded <?= ($_SESSION['flash_type'] ?? 'info') == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($product['id'] ?? '') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($product['name'] ?? 'Product') ?>" class="h-10 w-10 object-cover rounded">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="index.php?page=admin&section=products&task=edit&id=<?= $product['id'] ?>" class="text-primary hover:underline">
                                        <?= htmlspecialchars($product['name'] ?? 'N/A') ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center <?= (isset($product['stock_quantity']) && $product['stock_quantity'] <= ($product['low_stock_threshold'] ?? 5)) ? 'text-red-600 font-semibold' : 'text-gray-500' ?>">
                                    <?= $product['stock_quantity'] ?? 'N/A' ?>
                                    <?php if (!empty($product['backorder_allowed'])): ?>
                                        <span class="text-xs text-blue-500">(BO)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <?= !empty($product['is_featured']) ? '<i class="fas fa-check-circle text-green-500"></i>' : '<i class="fas fa-times-circle text-gray-400"></i>' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="index.php?page=admin&section=products&task=edit&id=<?= $product['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- Delete Form per Row (Using original confirmation dialog) -->
                                    <form action="index.php?page=admin&section=products&task=delete&id=<?= $product['id'] ?>" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete product \'<?= htmlspecialchars(addslashes($product['name']), ENT_QUOTES) ?>\'? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>"> <!-- Optional: ID also in URL -->
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Optional: Add pagination controls here if implementing pagination for admin list -->

</div>

<?php
// Use the admin layout footer
require_once __DIR__ . '/../layout/admin_footer.php';
?>

```

# views/admin/product_form.php  
```php
<?php
// views/admin/product_form.php
// Form for creating or editing products. (Consolidated v16.0)

// Use the admin layout header
require_once __DIR__ . '/../layout/admin_header.php';

// Determine if we are editing or creating
$isEditMode = isset($product) && !empty($product['id']);
$formAction = $isEditMode
    ? 'index.php?page=admin&section=products&task=save&id=' . urlencode($product['id'])
    : 'index.php?page=admin&section=products&task=save';

// Helper function to pre-fill form fields, handling potential nulls gracefully
function getAdminFieldValue($product, $field, $default = '') {
    // Special handling for boolean/tinyint represented as 0/1 for value attributes
    if (in_array($field, ['stock_quantity', 'initial_stock', 'low_stock_threshold'])) {
         return isset($product[$field]) ? (int)$product[$field] : $default;
    }
    // Special handling for price to ensure correct formatting if needed (though type=number handles it)
    if ($field === 'price') {
        return isset($product[$field]) ? number_format((float)$product[$field], 2, '.', '') : $default;
    }
    // Default handling for text/other fields
    return isset($product[$field]) ? htmlspecialchars($product[$field], ENT_QUOTES, 'UTF-8') : $default;
}

// Helper for JSON fields (display as newline/comma separated)
function getJsonFieldValue($product, $field) {
    if (!isset($product[$field])) return '';
    $data = is_array($product[$field]) ? $product[$field] : json_decode($product[$field], true);
    if (is_array($data)) {
        // Filter out empty strings before joining
        $data = array_filter($data, function($value) { return trim($value) !== ''; });
        return htmlspecialchars(implode("\n", $data), ENT_QUOTES, 'UTF-8'); // Use newline for textarea display
    }
    // If it's not valid JSON or already a string, display as is
    return htmlspecialchars($product[$field], ENT_QUOTES, 'UTF-8');
}

// Helper for checkboxes
function getAdminCheckedValue($product, $field) {
    return isset($product[$field]) && $product[$field] == 1 ? 'checked' : '';
}

?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-primary mb-6">
        <?= $isEditMode ? 'Edit Product: ' . htmlspecialchars($product['name']) : 'Create New Product' ?>
    </h1>

    <?php // Display standard flash messages
    if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded <?= ($_SESSION['flash_type'] ?? 'info') == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <form action="<?= $formAction ?>" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded-lg p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
        <?php endif; ?>

        <!-- Using Grid Layout for better alignment -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

            <!-- Column 1 -->
            <div class="space-y-6">
                <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Core Information</legend>
                     <div class="form-group">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" required maxlength="150" value="<?= getAdminFieldValue($product, 'name') ?>" class="form-input w-full">
                    </div>
                     <div class="form-group mt-4">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" required class="form-select w-full">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories ?? [] as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (isset($product['category_id']) && $product['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mt-4">
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price ($) <span class="text-red-500">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= getAdminFieldValue($product, 'price') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4">
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input type="text" id="sku" name="sku" maxlength="100" value="<?= getAdminFieldValue($product, 'sku') ?>" class="form-input w-full">
                    </div>
                </fieldset>

                 <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Details</legend>
                    <div class="form-group">
                        <label for="size" class="block text-sm font-medium text-gray-700 mb-1">Size (e.g., 10ml, 100g)</label>
                        <input type="text" id="size" name="size" maxlength="50" value="<?= getAdminFieldValue($product, 'size') ?>" class="form-input w-full">
                    </div>
                     <div class="form-group mt-4">
                        <label for="scent_profile" class="block text-sm font-medium text-gray-700 mb-1">Scent Profile (Keywords)</label>
                        <input type="text" id="scent_profile" name="scent_profile" maxlength="255" value="<?= getAdminFieldValue($product, 'scent_profile') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4">
                        <label for="origin" class="block text-sm font-medium text-gray-700 mb-1">Origin</label>
                        <input type="text" id="origin" name="origin" maxlength="100" value="<?= getAdminFieldValue($product, 'origin') ?>" class="form-input w-full">
                    </div>
                 </fieldset>
            </div>

            <!-- Column 2 -->
            <div class="space-y-6">
                 <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Inventory</legend>
                     <div class="form-group">
                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity <span class="text-red-500">*</span></label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" step="1" required value="<?= getAdminFieldValue($product, 'stock_quantity', '0') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4">
                        <label for="initial_stock" class="block text-sm font-medium text-gray-700 mb-1">Initial Stock (Optional)</label>
                        <input type="number" id="initial_stock" name="initial_stock" min="0" step="1" value="<?= getAdminFieldValue($product, 'initial_stock') ?>" class="form-input w-full" placeholder="Defaults to Stock Qty if empty on create">
                        <p class="text-xs text-gray-500 mt-1">Used for stock %. Defaults to current stock on creation if left empty.</p>
                    </div>
                     <div class="form-group mt-4">
                        <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0" step="1" value="<?= getAdminFieldValue($product, 'low_stock_threshold', '5') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4 flex items-center">
                        <input type="checkbox" id="backorder_allowed" name="backorder_allowed" value="1" class="form-checkbox h-5 w-5 text-primary rounded" <?= getAdminCheckedValue($product, 'backorder_allowed') ?>>
                        <label for="backorder_allowed" class="ml-2 block text-sm font-medium text-gray-700">Allow Backorders</label>
                    </div>
                </fieldset>

                <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Images</legend>
                    <div class="form-group">
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Main Image URL</label>
                        <input type="text" id="image_url" name="image_url" value="<?= getAdminFieldValue($product, 'image', '/images/placeholder.jpg') ?>" class="form-input w-full">
                        <p class="text-xs text-gray-500 mt-1">Enter full path (e.g., /images/products/...) or URL.</p>
                    </div>
                     <div class="form-group mt-4">
                        <label for="gallery_images" class="block text-sm font-medium text-gray-700 mb-1">Gallery Image URLs (Enter one per line)</label>
                         <textarea id="gallery_images" name="gallery_images" rows="4" class="form-textarea w-full" placeholder="/images/prod_1a.jpg&#10;/images/prod_1b.jpg"><?= getJsonFieldValue($product, 'gallery_images') ?></textarea>
                         <p class="text-xs text-gray-500 mt-1">Enter full paths or URLs. Server will convert to JSON.</p>
                     </div>
                     <div class="mt-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded">
                        <p><strong class="font-semibold">Note:</strong> File upload is not implemented. Enter URLs/paths directly.</p>
                    </div>
                </fieldset>

            </div>
        </div>

        <!-- Full Width Fields Below Grid -->
        <fieldset class="border p-4 rounded">
            <legend class="text-lg font-semibold text-primary px-2">Descriptions & Usage</legend>
            <div class="form-group">
                <label for="short_description" class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                <textarea id="short_description" name="short_description" rows="3" maxlength="500" class="form-textarea w-full" placeholder="A brief summary for product listings..."><?= getAdminFieldValue($product, 'short_description') ?></textarea>
            </div>
            <div class="form-group mt-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
                <textarea id="description" name="description" rows="6" class="form-textarea w-full" placeholder="Detailed product description..."><?= getAdminFieldValue($product, 'description') ?></textarea>
            </div>
            <div class="form-group mt-4">
                <label for="ingredients" class="block text-sm font-medium text-gray-700 mb-1">Ingredients</label>
                <textarea id="ingredients" name="ingredients" rows="3" class="form-textarea w-full" placeholder="List key ingredients..."><?= getAdminFieldValue($product, 'ingredients') ?></textarea>
            </div>
            <div class="form-group mt-4">
                <label for="usage_instructions" class="block text-sm font-medium text-gray-700 mb-1">Usage Instructions</label>
                <textarea id="usage_instructions" name="usage_instructions" rows="4" class="form-textarea w-full" placeholder="How to use the product..."><?= getAdminFieldValue($product, 'usage_instructions') ?></textarea>
            </div>
             <div class="form-group mt-4">
                <label for="benefits" class="block text-sm font-medium text-gray-700 mb-1">Benefits (Enter one per line)</label>
                 <textarea id="benefits" name="benefits" rows="4" class="form-textarea w-full" placeholder="Calming&#10;Stress Relief"><?= getJsonFieldValue($product, 'benefits') ?></textarea>
                 <p class="text-xs text-gray-500 mt-1">Enter one benefit per line. Server will convert to JSON.</p>
             </div>
        </fieldset>

        <!-- Settings Section -->
        <fieldset class="border p-4 rounded">
             <legend class="text-lg font-semibold text-primary px-2">Visibility</legend>
            <div class="form-group flex items-center">
                <input type="checkbox" id="is_featured" name="is_featured" value="1" class="form-checkbox h-5 w-5 text-primary rounded" <?= getAdminCheckedValue($product, 'is_featured') ?>>
                <label for="is_featured" class="ml-2 block text-sm font-medium text-gray-700">Featured Product (Show on Homepage)</label>
            </div>
             <!-- Add is_active toggle if needed in schema/controller -->
             <!-- <div class="form-group flex items-center mt-2">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="form-checkbox h-5 w-5 text-primary rounded" checked>
                <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">Product Active (Visible in Shop)</label>
            </div> -->
        </fieldset>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 pt-6 border-t mt-6">
            <a href="index.php?page=admin&section=products" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                 <i class="fas fa-save mr-2"></i><?= $isEditMode ? 'Update Product' : 'Create Product' ?>
            </button>
        </div>
    </form>
</div>

<script>
    // Basic JS for form interactions if needed
    document.querySelector('form').addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            // Add a spinner or change text
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Saving...`;
        }
        // The controller needs to parse newline-separated textareas for 'benefits' and 'gallery_images'
    });
</script>


<?php
// Use the admin layout footer
require_once __DIR__ . '/../layout/admin_footer.php';
?>

```

