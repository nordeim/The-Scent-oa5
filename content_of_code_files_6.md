# views/account/dashboard.php  
```php
<?php
// views/account/dashboard.php (Layout Refactored with Tailwind CSS - Quiz History Link Updated)
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
                                <a href="index.php?page=quiz&action=history" class="flex items-center px-4 py-2 rounded-md text-gray-600 hover:bg-gray-100 hover:text-primary transition duration-150 ease-in-out"> {/* MODIFIED LINK */}
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
                        $quizContent .= "<i class='fas fa-flask text-4xl text-gray-300 mb-3'></i>"; // Changed icon
                        $quizContent .= "<p class='text-gray-600 mb-4'>Take the quiz to discover your profile.</p>";
                        $quizContent .= "<a href='index.php?page=quiz' class='btn-primary btn-sm'>Take Quiz Now</a>";
                        $quizContent .= "</div>";
                    } else {
                        $latestQuiz = $quizResults[0]; // Get the most recent result
                        $preferences = isset($latestQuiz['answers']) ? json_decode($latestQuiz['answers'], true) : [];
                        if (!is_array($preferences)) $preferences = [];
                        $recommendedIds = isset($latestQuiz['recommendations']) ? json_decode($latestQuiz['recommendations'], true) : [];
                        if (!is_array($recommendedIds)) $recommendedIds = [];

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

                         // Display Recommended Products (Fetch details if needed)
                         if (!empty($recommendedIds)) {
                             $quizContent .= "<div><h3 class='font-semibold text-gray-700 mb-2 mt-4 border-t pt-3'>Top Recommendations:</h3>";
                             // Fetch product details based on $recommendedIds
                              if (isset($pdo)) { // Check if $pdo is available
                                   if (!class_exists('Product')) require_once __DIR__ . '/../../models/Product.php';
                                   $productModel = new Product($pdo);
                                   // Fetch details for a limited number, e.g., 2 for the dashboard card
                                   $recommendations = $productModel->getProductsByIds(array_slice($recommendedIds, 0, 2));
                                   if (!empty($recommendations)) {
                                       $quizContent .= "<div class='flex flex-col gap-3'>";
                                       foreach ($recommendations as $product) {
                                            $quizContent .= "<div class='recommended-product flex items-center gap-3 p-2 border rounded-md bg-gray-50/50'>";
                                            $quizContent .= "<img src='" . htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') . "' alt='" . htmlspecialchars($product['name']) . "' class='w-10 h-10 object-cover rounded flex-shrink-0'>";
                                            $quizContent .= "<div class='flex-grow'><h4 class='text-sm font-medium text-primary'>" . htmlspecialchars($product['name']) . "</h4>";
                                            $quizContent .= "<p class='text-xs text-gray-500'>$" . number_format($product['price'], 2) . "</p></div>";
                                            $quizContent .= "<a href='index.php?page=product&id={$product['id']}' class='btn-secondary btn-xs whitespace-nowrap'>View</a>";
                                            $quizContent .= "</div>";
                                       }
                                       $quizContent .= "</div>";
                                   } else {
                                       $quizContent .= "<p class='text-sm text-gray-500 italic'>Could not load recommendations.</p>";
                                   }
                              } else {
                                   $quizContent .= "<p class='text-sm text-red-500 italic'>Database connection error.</p>";
                              }
                         } else {
                              $quizContent .= "<p class='text-sm text-gray-500 italic mt-4 border-t pt-3'>No product recommendations from this quiz.</p>";
                         }
                         $quizContent .= "</div>";
                         $quizContent .= "</div>"; // Close space-y-4
                    }
                    renderDashboardCard('Your Scent Profile', $quizContent, 'index.php?page=quiz&action=history', 'View History', 200); // MODIFIED LINK
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

# views/account/order_details.php  
```php
<?php
// Updated: views/account/order_details.php (Quiz History Link Updated)

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
                                <a href="index.php?page=quiz&action=history"> {/* MODIFIED LINK */}
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

```

# views/account/orders.php  
```php
<?php
// Updated: views/account/orders.php (Quiz History Link Updated)

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
                                <a href="index.php?page=quiz&action=history"> {/* MODIFIED LINK */}
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

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

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

# views/admin/quiz_analytics.php  
```php
<?php 
$section = 'quiz_analytics';
require_once __DIR__ . '/../layout/admin_header.php'; 
?>
<body class="page-admin-quiz-analytics">
<section class="admin-section">
    <div class="container">
        <div class="admin-container" data-aos="fade-up">
            <div class="admin-header">
                <h1>Quiz Analytics Dashboard</h1>
                <div class="date-filter">
                    <select id="timeRange" onchange="updateAnalytics()">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 3 Months</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="analytics-grid">
                <div class="stat-card" data-aos="fade-up">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Total Participants</h3>
                        <p class="stat-value" id="totalParticipants">Loading...</p>
                    </div>
                </div>
                
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Conversion Rate</h3>
                        <p class="stat-value" id="conversionRate">Loading...</p>
                    </div>
                </div>
                
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3>Avg. Completion Time</h3>
                        <p class="stat-value" id="avgCompletionTime">Loading...</p>
                    </div>
                </div>
            </div>

            <!-- Preferences Analysis -->
            <div class="analytics-row">
                <div class="chart-container" data-aos="fade-up">
                    <h2>Popular Scent Types</h2>
                    <canvas id="scentChart"></canvas>
                </div>
                
                <div class="chart-container" data-aos="fade-up">
                    <h2>Mood Effects Distribution</h2>
                    <canvas id="moodChart"></canvas>
                </div>
            </div>

            <!-- Daily Quiz Completions -->
            <div class="analytics-row">
                <div class="chart-container full-width" data-aos="fade-up">
                    <h2>Daily Quiz Completions</h2>
                    <canvas id="completionsChart"></canvas>
                </div>
            </div>

            <!-- Top Product Recommendations -->
            <div class="recommendations-table" data-aos="fade-up">
                <h2>Most Recommended Products</h2>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Times Recommended</th>
                                <th>Conversion Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="recommendationsTable">
                            <tr>
                                <td colspan="5">Loading data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/admin_footer.php'; ?>
```

# stripe_test_v4.php  
```php
<?php
// stripe_test_v4.php - Comprehensive Checkout Simulation (Using Composer Classmap Autoloader)

// --- START: Fatal Error Capture ---
function captureFatalError() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) { header('Content-Type: text/plain; charset=UTF-8'); }
        echo "\n\n--- FATAL ERROR DETECTED ---\n";
        echo "Type:    " . ($error['type'] ?? 'Unknown') . "\n";
        echo "Message: " . ($error['message'] ?? 'N/A') . "\n";
        echo "File:    " . ($error['file'] ?? 'N/A') . "\n";
        echo "Line:    " . ($error['line'] ?? 'N/A') . "\n";
        echo "---------------------------\n\n";
    }
}
register_shutdown_function('captureFatalError');
// --- END: Fatal Error Capture ---

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_PATH', __DIR__);

// --- Core Includes FIRST ---
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php'; // Provides $pdo
// ErrorHandler should be autoloaded now via classmap
// SecurityMiddleware should be autoloaded now via classmap

// --- Use Composer Autoloader ---
$autoloader_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloader_path)) {
    echo "PHP Setup: Using Composer autoloader.\n";
    require_once $autoloader_path;
} else {
    // If autoloader is missing after running composer dump-autoload, something is wrong
     die("FATAL: Composer autoloader not found at {$autoloader_path}. Run 'composer dump-autoload -o'.\n");
}

// --- Check if core classes are loaded by Autoloader ---
if (!isset($pdo)) die("FATAL: PDO object not available. Check includes/db.php.\n");
if (!class_exists('ErrorHandler')) die("FATAL: ErrorHandler class not loaded by autoloader.\n");
if (!class_exists('SecurityMiddleware')) die("FATAL: SecurityMiddleware class not loaded by autoloader.\n");
if (!class_exists('BaseController')) die("FATAL: BaseController class not loaded by autoloader.\n");
if (!class_exists('EmailService')) die("FATAL: EmailService class not loaded by autoloader.\n");
// --- End Check core classes ---

// Initialize Error Handler AFTER class definition is loaded
ErrorHandler::init();
echo "PHP Setup: ErrorHandler initialized.\n";


// --- Simulate Logged-in User ---
$testUserId = 1; // Use the ID confirmed previously
$testUserData = null;
try {
    // Check if User class is loaded
    if (!class_exists('User')) die("FATAL: User class not loaded by autoloader.\n");
    $userCheckStmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
    $userCheckStmt->execute([$testUserId]);
    $testUserData = $userCheckStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("FATAL: Database error checking test user: " . $e->getMessage() . "\n"); }
if (!$testUserData) { die("FATAL: Test User ID {$testUserId} not found in the database. Please use a valid ID.\n"); }
$testUserEmail = $testUserData['email']; $testUserName = $testUserData['name'];
echo "PHP Setup: Test user found (ID: {$testUserId}, Email: {$testUserEmail}).\n";

// --- Start Session ---
if (session_status() === PHP_SESSION_NONE) { if (!headers_sent()) { session_start(); echo "PHP Setup: Session started.\n"; } else { error_log("stripe_test_v4.php Warning: Headers already sent, cannot start session."); echo "PHP Setup Warning: Headers already sent, cannot start session.\n"; } } else { echo "PHP Setup: Session already active.\n"; }
$_SESSION['user_id'] = $testUserId; $_SESSION['user_role'] = 'user'; $_SESSION['user'] = [ 'id' => $testUserId, 'name' => $testUserName, 'email' => $testUserEmail, 'role' => 'user' ]; $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'TestAgentCLI'; $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'; $_SESSION['last_login'] = time(); $_SESSION['last_regeneration'] = time();

// --- Simulate Cart ---
$testProductId = 1; // Use the ID confirmed previously
$testQuantity = 1;
try {
    if (!class_exists('Product')) die("FATAL: Product class not loaded by autoloader.\n");
    $productCheckStmt = $pdo->prepare("SELECT id FROM products WHERE id = ?"); $productCheckStmt->execute([$testProductId]); if (!$productCheckStmt->fetch()) { die("FATAL: Test Product ID {$testProductId} not found in the database.\n"); } echo "PHP Setup: Test product found (ID: {$testProductId}).\n";
    if (!class_exists('Cart')) { die("FATAL: Cart class not loaded by autoloader.\n"); }
    $cartModel = new Cart($pdo, $testUserId); $cartModel->clearCart(); if (!$cartModel->addItem($testProductId, $testQuantity)) { throw new Exception("CartModel::addItem returned false."); } $_SESSION['cart_count'] = $cartModel->getCartCount(); $cartCheckItems = $cartModel->getItems(); if (empty($cartCheckItems)) { throw new Exception("Failed to add/retrieve test item."); } echo "PHP Setup: Cart simulated successfully (User: {$testUserId}, Product: {$testProductId}, Count: {$_SESSION['cart_count']}).\n";
} catch (Exception $e) { die("Cart Simulation Error: " . $e->getMessage() . "\n"); }

// --- Instantiate Controllers ---
try {
    if (!class_exists('PaymentController')) die("FATAL: PaymentController class not loaded by autoloader.\n"); if (!class_exists('CheckoutController')) die("FATAL: CheckoutController class not loaded by autoloader.\n");
    $paymentController = new PaymentController($pdo); $checkoutController = new CheckoutController($pdo, $paymentController); echo "PHP Setup: Controllers instantiated successfully.\n";
} catch (Throwable $e) { die("FATAL: Error instantiating controllers: " . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString() . "\n"); }

// --- CSRF & Post Data ---
if (!class_exists('SecurityMiddleware')) { die("FATAL: SecurityMiddleware class not loaded by autoloader.\n"); } $csrfToken = SecurityMiddleware::generateCSRFToken(); echo "PHP Setup: CSRF Token generated: " . $csrfToken . "\n";
$samplePostData = [ 'shipping_name' => $testUserName, 'shipping_email' => $testUserEmail, 'shipping_address' => '123 Test St', 'shipping_address_line2' => 'Apt 4B', 'shipping_city' => 'Testville', 'shipping_state' => 'TS', 'shipping_zip' => '54321', 'shipping_country' => 'US', 'order_notes' => 'Test order generated by stripe_test_v4.php', 'save_address' => '0', 'csrf_token' => $csrfToken, 'applied_coupon_code' => '' ]; echo "PHP Setup: Sample POST data prepared.\n";

// --- Check Constants ---
if (!defined('STRIPE_PUBLIC_KEY') || !defined('BASE_URL')) { die("Error: STRIPE_PUBLIC_KEY or BASE_URL constant is not defined. Check config.php.\n"); } $stripePublicKey = STRIPE_PUBLIC_KEY; $baseUrl = BASE_URL; echo "PHP Setup: Constants checked (Stripe PK / Base URL).\n"; echo "PHP Setup: Completed successfully. Generating HTML output...\n";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stripe Checkout Simulation (v5 - Classmap Autoloader)</title> <!-- Renamed Title -->
    <script src="https://js.stripe.com/v3/"></script>
    <style> /* Styles unchanged */
        body { font-family: sans-serif; padding: 15px; font-size: 14px; line-height: 1.5; }
        #message, #log { margin-top: 15px; padding: 10px; border-radius: 4px; border: 1px solid #ccc; }
        #log { background-color: #f8f8f8; font-family: monospace; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow-y: auto; font-size: 12px;}
        .success { background-color: #e9f7ef; border-color: #a7d7c5; color: #1e4634; }
        .error { background-color: #fceded; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #eef6fc; border-color: #b8d7ea; color: #0c5460; }
        code { background-color: #eee; padding: 2px 4px; border-radius: 3px; }
        button { padding: 10px 15px; font-size: 16px; cursor: pointer; background-color: #1A4D5A; color: white; border: none; border-radius: 4px; margin-top: 15px; }
        button:disabled { background-color: #ccc; cursor: not-allowed; }
        .spinner { display: inline-block; width: 1em; height: 1em; border: 2px solid rgba(0,0,0,.1); border-left-color: #fff; border-radius: 50%; animation: spin 1s linear infinite; margin-left: 5px; vertical-align: middle; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hidden { display: none; }
    </style>
</head>
<body data-stripe-public-key="<?= htmlspecialchars($stripePublicKey) ?>"
      data-base-url="<?= htmlspecialchars($baseUrl) ?>">

    <h1>Stripe Checkout Simulation (v5 - Classmap Autoloader)</h1> <!-- Renamed Title -->
    <p>This script attempts to simulate the full checkout flow using Composer's classmap autoloader:</p>
    <ol>
        <li>Initialize Stripe.js core object.</li>
        <li>Simulate form data submission to the backend (`CheckoutController::processCheckout`).</li>
        <li>Receive `clientSecret` and `orderId` from backend.</li>
        <li>Initialize Stripe Elements **using the clientSecret**.</li>
        <li>Mount the Payment Element.</li>
        <li>Use the mounted Elements to call `stripe.confirmPayment()`.</li>
    </ol>
    <p>Check the log below and the browser console (F12) for detailed steps and errors.</p>

    <!-- Hidden CSRF Token for JS -->
    <input type="hidden" id="csrf-token-value" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Stripe Element Placeholder -->
    <div id="payment-element" style="border:1px solid #ccc; padding: 10px; margin-top:10px; min-height: 40px;">Stripe Payment Element will load here...</div>

    <!-- Message Area -->
    <div id="message" class="info">Messages will appear here...</div>

    <!-- Submit Button -->
    <button id="submit-button">
        <span id="button-text">Run Checkout Simulation</span>
        <span id="spinner" class="spinner hidden"></span>
    </button>

    <!-- Detailed Log Area -->
    <h2>Execution Log:</h2>
    <pre id="log">Starting simulation...</pre>

    <script>
        // --- JavaScript Simulation (v5 - Corrected Elements Init - Unchanged from previous step) ---
        const logElement = document.getElementById('log');
        const messageDiv = document.getElementById('message');
        const stripeKey = document.body.dataset.stripePublicKey;
        const csrfToken = document.getElementById('csrf-token-value').value;
        const paymentElementContainer = document.getElementById('payment-element');
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');
        const baseUrl = document.body.dataset.baseUrl || '/';
        let stripe = null;

        // --- Helper Functions (Unchanged) ---
        function logStep(step, status, data = null) { const timestamp = new Date().toLocaleTimeString(); let logEntry = `[${timestamp}] ${step}: ${status}`; if (data) { try { logEntry += `\nData: ${JSON.stringify(data, (key, value) => typeof value === 'object' && value !== null && value.constructor === Object && Object.keys(value).length === 0 ? '{}' : value, 2 )}`; } catch (e) { logEntry += `\nData: (Could not serialize - ${typeof data})`; } } logElement.textContent += `\n${logEntry}\n`; logElement.scrollTop = logElement.scrollHeight; }
        function showStatus(message, type = 'info') { messageDiv.textContent = message; messageDiv.className = type; }
        function setLoading(isLoading) { submitButton.disabled = isLoading; spinner.classList.toggle('hidden', !isLoading); buttonText.classList.toggle('hidden', isLoading); }

        // --- Initialize Stripe Core Object ONLY ---
        function initializeStripeCore() {
            logStep("Stripe Core Init", "Starting...", { key: stripeKey ? stripeKey.substring(0, 10) + '...' : 'MISSING' });
            if (!stripeKey || stripeKey.startsWith('pk_test_51xxx')) { const msg = "Invalid or placeholder Stripe Public Key."; logStep("Stripe Core Init", "FAILED", { error: msg }); showStatus(msg, 'error'); setLoading(false); submitButton.disabled = true; return false; }
            try {
                stripe = Stripe(stripeKey); if (!stripe) { throw new Error("Stripe(key) failed to return an object."); }
                logStep("Stripe Core Init", "SUCCESS", { stripeObjectPresent: !!stripe }); paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Secure payment form will load here...</p>';
                showStatus("Stripe Core ready. Click button to simulate checkout.", 'info'); return true;
            } catch (error) { logStep("Stripe Core Init", "FAILED", { error: error.message, details: error }); console.error("Stripe Core Initialization FAILED:", error); showStatus(`Stripe Core Initialization FAILED: ${error.message}`, 'error'); setLoading(false); submitButton.disabled = true; return false; }
        }

        // --- Simulate Checkout Button Click ---
        submitButton.addEventListener('click', async () => {
            if (!stripe) { showStatus("Stripe Core not initialized correctly.", 'error'); return; }
            setLoading(true); showStatus("Simulating checkout process...", 'info'); logStep("Checkout Click", "Initiated");
            let clientSecret = null; let orderId = null; let processCheckoutError = null; let elements = null;
            const formData = new FormData();
            <?php foreach ($samplePostData as $key => $value): ?> formData.append('<?= $key ?>', '<?= addslashes($value) ?>'); <?php endforeach; ?>
            logStep("Backend Call", "Sending data to processCheckout...", Object.fromEntries(formData));
            try {
                const response = await fetch('index.php?page=checkout&action=processCheckout', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
                logStep("Backend Call", `Received status ${response.status}`); const responseText = await response.text(); let loggableText = responseText; try { const tempData = JSON.parse(responseText); if (tempData.clientSecret) { loggableText = JSON.stringify({...tempData, clientSecret: tempData.clientSecret.substring(0, 15) + '...'}); } } catch(parseErr) {} logStep("Backend Call", "Raw response text (Secret Redacted)", loggableText);
                if (!response.ok) { let errorMsg = `Server returned status ${response.status}.`; try { const errorData = JSON.parse(responseText); errorMsg = errorData.error || errorMsg; } catch(e){} throw new Error(errorMsg); }
                const data = JSON.parse(responseText); logStep("Backend Call", "Parsed JSON response", data);
                if (data.success && data.clientSecret && data.orderId) { clientSecret = data.clientSecret; orderId = data.orderId; logStep("Backend Call", "SUCCESS", { clientSecret: clientSecret.substring(0, 15) + '...', orderId }); showStatus(`Backend processed (Order ID: ${orderId}). Loading payment form...`, 'info'); }
                else { throw new Error(data.error || 'Backend failed to process checkout.'); }
            } catch (error) { processCheckoutError = error; logStep("Backend Call", "FAILED", { error: error.message, details: error }); console.error("Backend processCheckout Error:", error); showStatus(`Backend Error: ${error.message}`, 'error'); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Could not prepare payment. Please try again.</p>'; setLoading(false); return; }
            try {
                if (!clientSecret) throw new Error("Client secret is missing after backend call.");
                const appearance = { theme: 'stripe', variables: { colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151', colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem' } };
                elements = stripe.elements({ clientSecret: clientSecret, appearance }); logStep("Stripe Elements", "Created with clientSecret", { elementsObjectPresent: !!elements });
                const paymentElement = elements.create('payment'); paymentElementContainer.innerHTML = ''; paymentElement.mount('#payment-element'); logStep("Payment Element", "Mounted successfully"); showStatus(`Payment form loaded. Confirming payment for Order ID: ${orderId}...`, 'info');
            } catch (elementsError) { logStep("Elements/Mount", "FAILED", { error: elementsError.message, details: elementsError }); console.error("Stripe Elements creation/mounting error:", elementsError); showMessage("Failed to load the payment form. Please refresh.", true); paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error loading payment form.</p>'; setLoading(false); return; }
            if (clientSecret && stripe && elements) {
                 logStep("Stripe Confirm", "Attempting stripe.confirmPayment..."); const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/'; const testReturnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation&simulated=1`; logStep("Stripe Confirm", "Using return_url", testReturnUrl);
                 try {
                     const { error, paymentIntent } = await stripe.confirmPayment({ elements, confirmParams: { return_url: testReturnUrl }, redirect: 'if_required' });
                     if (error) { logStep("Stripe Confirm", "FAILED", { errorType: error.type, errorMessage: error.message, details: error }); console.error("Stripe confirmPayment Error:", error); showStatus(`Stripe Payment Confirmation Failed: ${error.message}`, 'error'); }
                     else if (paymentIntent) { logStep("Stripe Confirm", "SUCCESS/PENDING", { paymentIntentStatus: paymentIntent.status, paymentIntentId: paymentIntent.id }); console.log("Stripe confirmPayment Result:", paymentIntent); showStatus(`Stripe Payment Confirmation finished! Status: ${paymentIntent.status}. Check console/webhook.`, 'success'); }
                     else { logStep("Stripe Confirm", "UNKNOWN OUTCOME (No Redirect, No Error, No PI)", { error: null, paymentIntent: null }); showStatus("Stripe payment confirmation finished with an unknown outcome.", 'warning'); }
                 } catch (confirmError) { logStep("Stripe Confirm", "EXCEPTION", { error: confirmError.message, details: confirmError }); console.error("Exception during stripe.confirmPayment:", confirmError); showStatus(`Exception during payment confirmation: ${confirmError.message}`, 'error'); }
                 finally { setLoading(false); }
            } else { logStep("Stripe Confirm", "SKIPPED", { reason: "Missing clientSecret, stripe, or elements." }); showMessage('Internal error before payment confirmation.', 'error'); setLoading(false); }
        });

        // Initialize Stripe Core on page load
        if (!initializeStripeCore()) { logStep("Setup", "Stripe Core initialization failed. Disabling button."); submitButton.disabled = true; }
    </script>

</body>
</html>

```

