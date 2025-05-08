Okay, I have carefully reviewed the provided code files, logs, HTML outputs, and issue descriptions. Here's a breakdown of the analysis and the necessary fixes:

**Compatibility Check (`AccountController`, `index.php`, `main.js`)**

*   **`index.php`:** The routing logic in `index.php` correctly handles the `/account` page requests, including sections like `dashboard`, `orders`, `profile`, and differentiating between GET (show view) and POST (update data) for the profile section. It appears compatible with the structure of the refactored `AccountController.php`.
*   **`js/main.js`:** The AJAX handlers for login (`initLoginPage`) and registration (`initRegisterPage`) align with how `AccountController.php` handles these actions (expecting POST, returning JSON). The profile update forms (`profileForm`, `passwordForm`, `addressForm`, `preferencesForm`) are handled via standard POST submissions, which is also consistent with how `AccountController::updateProfile` expects to receive data via the `$_POST` superglobal based on the `action` parameter.

**Conclusion:** `index.php` and `js/main.js` are compatible with the provided `controllers/AccountController.php`. No changes are required in these two files based *solely* on the `AccountController` refactor itself.

**Addressing Specific Issues:**

**Issue 1: Landing Page Links Not Working**

*   **Analysis:** The provided HTML (`current_landing_page.html`) and the corresponding view (`views/home.php`) show that the "Why Choose The Scent" and "Discover Your Perfect Scent" sections contain informational `div` elements (`benefit-card`, `finder-card`) with icons and text. There are **no `<a>` (anchor/link) tags** within these specific cards.
*   **Conclusion:** These elements are not designed to be clickable links. They are informational blocks displaying benefits or scent categories. The visual design might make them *appear* clickable, but functionally they are not links.
*   **Solution:** No code changes are required. This is either a misunderstanding of the UI element's purpose or a potential UI design suggestion to make them look less like interactive buttons if they aren't meant to be.

**Issue 2: Quiz Not Producing Recommendations**

*   **Analysis:**
    1.  The quiz form (`views/quiz.php`) correctly POSTs to `index.php?page=quiz&action=submit`.
    2.  `index.php` routes this to `QuizController::processQuiz()`.
    3.  `QuizController::processQuiz()` calculates recommendations using `QuizModel::getRecommendations()`, stores the *full product details* array in `$_SESSION['quiz_recommendations']`, and redirects to `index.php?page=quiz&action=results`.
    4.  `index.php` routes the GET request to `QuizController::showResults()`.
    5.  `QuizController::showResults()` retrieves the recommendations from the session and passes them as `$products` to `views/quiz_results.php`.
    6.  `views/quiz_results.php` iterates through `$products` to display them, *or* shows a "No Specific Recommendations Found" message if `$products` is empty.
    7.  The `QuizModel::getRecommendations()` method tries to find products matching the selected mood's effect in the `product_attributes` table. If none are found, it falls back to fetching products marked as `is_featured = 1`.
    8.  **Hypothesis:** The most likely reason for seeing no recommendations is that either:
        *   The `product_attributes` table lacks entries matching the `mood_effect` corresponding to the selected quiz option ('relaxation' -> 'calming', 'energy' -> 'energizing', etc.).
        *   *AND* No products in the `products` table have `is_featured` set to `1` to serve as a fallback.
    9.  There's also a minor bug in `QuizModel::getResultsByUserId` where it selects `recommended_products` instead of the correct column `recommendations`. While not directly causing *this* issue (which uses session), it needs fixing for the Account Quiz History.
*   **Solution:**
    1.  **Verify Data:** Ensure the `product_attributes` table is populated with data linking products to mood effects (`calming`, `energizing`, `focusing`, `balancing`). Also, ensure at least a few products have `is_featured = 1` in the `products` table to provide fallback recommendations. This is likely a *data* issue, not a code issue for the immediate quiz result flow.
    2.  **Fix Model Bug:** Correct the column name selected in `QuizModel::getResultsByUserId`.

**Issue 3: Messy Account Dashboard Layout**

*   **Analysis:** The current `views/account/dashboard.php` uses custom CSS classes like `dashboard-grid`, `dashboard-card`, etc. While Tailwind CSS is available globally, it's not being fully utilized for the dashboard's internal layout, leading to potential inconsistencies or reliance on incomplete custom CSS.
*   **Solution:** Refactor `views/account/dashboard.php` to use Tailwind CSS utility classes (`grid`, `grid-cols-*`, `gap-*`, `p-*`, `bg-*`, `rounded-*`, `shadow-*`, `flex`, etc.) for layout and styling. This will provide a more robust, responsive, and visually pleasing arrangement consistent with the rest of the site's styling approach.

**Issue 4: Checkout Payment Error**

*   **Analysis:**
    1.  The error message "Could not initialize payment system. Please refresh." originates from the `catch` block in `js/main.js -> initCheckoutPage()` when `stripe = Stripe(stripePublicKey);` fails.
    2.  The debugging `console.log` statements added previously would show the value of `stripePublicKey` being read from the `body`'s `data-stripe-public-key` attribute.
    3.  `views/layout/header.php` outputs this data attribute using the `STRIPE_PUBLIC_KEY` constant from `config.php`.
    4.  `config.php` defines `STRIPE_PUBLIC_KEY` as the placeholder `'pk_test_51xxx'`.
    5.  Stripe.js will fail to initialize with an invalid placeholder key.
*   **Conclusion:** The root cause is the placeholder Stripe public key in `config.php`. The JavaScript code is attempting to initialize Stripe correctly, but failing because the provided key is invalid.
*   **Solution:** The user **must** replace the placeholder value `'pk_test_51xxx'` in `config.php` with their actual Stripe **Publishable Key**. No code changes are needed in the PHP or JS files themselves to fix this specific initialization error, as the mechanism to read and use the key is already correct. The added console logs will confirm that the placeholder key is being passed to the `Stripe()` function.

---

**Applying Necessary Code Fixes:**

Based on the analysis, the following files need updates:

1.  `models/Quiz.php` (Fix column name in `getResultsByUserId`)
2.  `views/account/dashboard.php` (Refactor layout with Tailwind CSS)

---

**Updated Files:**

**1. `models/Quiz.php`**

```php
<?php
// models/Quiz.php (Updated: Fixed column name in getResultsByUserId)

class Quiz {
    private PDO $pdo; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        $this->pdo = $pdo;
    }

    public function getQuestions() {
        // This method remains unchanged from the original
        return [
            [
                'id' => 'mood',
                'question' => 'What are you looking for today?',
                'options' => [
                    'relaxation' => [
                        'label' => 'Relaxation',
                        'icon' => 'fa-spa',
                        'description' => 'Find calm and peace in your daily routine'
                    ],
                    'energy' => [
                        'label' => 'Energy',
                        'icon' => 'fa-bolt',
                        'description' => 'Boost your vitality and motivation'
                    ],
                    'focus' => [
                        'label' => 'Focus',
                        'icon' => 'fa-brain',
                        'description' => 'Enhance concentration and clarity'
                    ],
                    'balance' => [
                        'label' => 'Balance',
                        'icon' => 'fa-yin-yang',
                        'description' => 'Find harmony in body and mind'
                    ]
                ]
            ]
        ];
    }

    public function getRecommendations($answers) {
        // This method remains unchanged from the original
        try {
            $moodEffectMap = [
                'relaxation' => 'calming',
                'energy' => 'energizing',
                'focus' => 'focusing',
                'balance' => 'balancing'
            ];

            $mood = $answers['mood'] ?? 'relaxation';
            $moodEffect = $moodEffectMap[$mood] ?? 'calming';

            // Get matching products based on mood and attributes
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type, pa.intensity_level
                FROM products p
                LEFT JOIN product_attributes pa ON p.id = pa.product_id /* Use LEFT JOIN in case attributes are missing */
                WHERE pa.mood_effect = ?
                ORDER BY RAND()
                LIMIT 3
            ");

            $stmt->execute([$moodEffect]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // If no exact matches, get featured products as fallback
            if (empty($products)) {
                $stmt = $this->pdo->prepare("
                    SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type, pa.intensity_level
                    FROM products p
                    LEFT JOIN product_attributes pa ON p.id = pa.product_id /* Use LEFT JOIN */
                    WHERE p.is_featured = 1
                    ORDER BY RAND()
                    LIMIT 3
                ");
                $stmt->execute();
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Add scent descriptions
            foreach ($products as &$product) {
                // Defensive check for keys before accessing
                $scentType = $product['scent_type'] ?? null;
                $moodEff = $product['mood_effect'] ?? null;
                $product['scent_description'] = $scentType ? $this->getScentDescription($scentType) : '';
                $product['mood_description'] = $moodEff ? $this->getMoodDescription($moodEff) : '';
            }
             unset($product); // Unset reference

            return $products ?: []; // Ensure array return
        } catch (PDOException $e) {
            error_log("Error getting recommendations: " . $e->getMessage());
            throw $e; // Re-throw to be handled globally
        }
    }

    /**
     * Saves quiz result. Accepts optional $details array (not currently used but added for signature compatibility).
     *
     * @param int|null $userId
     * @param string|null $email
     * @param array $answers
     * @param array $recommendationIds Array of recommended product IDs.
     * @param array|null $details Optional extra details (e.g., completion time, browser). Not currently stored.
     * @return bool
     */
    public function saveQuizResult(?int $userId, ?string $email, array $answers, array $recommendationIds, ?array $details = null): bool {
        // Adjusted signature to accept 5 arguments as called by controller, but $details is currently unused here.
        // Kept original implementation using $recommendationIds.
        try {
            // The controller now passes an array of IDs directly.
            $stmt = $this->pdo->prepare("
                INSERT INTO quiz_results
                (user_id, email, answers, recommendations, created_at) /* Use correct column name 'recommendations' */
                VALUES (?, ?, ?, ?, NOW()) /* Use NOW() for DB consistency */
            ");

            // Log details if provided (optional)
            // if ($details) { error_log("Quiz Save Details: " . json_encode($details)); }

            return $stmt->execute([
                $userId, // Can be null for guests
                $email, // Can be null for logged-in users if not collected
                json_encode($answers), // Store answers as JSON
                json_encode($recommendationIds) // Store recommended product IDs as JSON array
            ]);
        } catch (PDOException $e) {
            error_log("Error saving quiz result: " . $e->getMessage());
             // Don't throw here, controller might want to proceed anyway
             return false; // Indicate failure
        }
    }

    /**
     * Fetches all quiz results for a specific user, ordered by date.
     *
     * @param int $userId The user's ID.
     * @return array An array of quiz results, or an empty array if none found or on error.
     */
    public function getResultsByUserId(int $userId): array {
        if ($userId <= 0) {
            return []; // Return empty array for invalid user ID
        }
        try {
            // --- START FIX: Select 'recommendations' column instead of 'recommended_products' ---
            $stmt = $this->pdo->prepare("
                SELECT id, user_id, email, answers, recommendations, created_at
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            // --- END FIX ---
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optionally decode JSON fields here if needed by the view immediately
            foreach ($results as &$item) {
                 $item['answers'] = isset($item['answers']) ? (json_decode($item['answers'], true) ?? []) : [];
                 // Decode the correct column now
                 $item['recommendations'] = isset($item['recommendations']) ? (json_decode($item['recommendations'], true) ?? []) : [];
            }
            unset($item); // Unset reference


            return $results ?: []; // Return results or empty array

        } catch (PDOException $e) {
            error_log("Error fetching quiz results for user ID {$userId}: " . $e->getMessage());
            return []; // Return empty array on database error
        }
    }


    private function getScentDescription($scentType) {
        // This method remains unchanged from the original
        $descriptions = [
            'floral' => 'Delicate and romantic floral notes that bring peace and harmony',
            'woody' => 'Rich, grounding woody scents that promote stability and strength',
            'citrus' => 'Bright, uplifting citrus notes that energize and refresh',
            'oriental' => 'Warm, exotic notes that create a sense of luxury and comfort',
            'fresh' => 'Clean, crisp scents that invigorate and purify'
        ];
        return $descriptions[$scentType] ?? '';
    }

    private function getMoodDescription($moodEffect) {
        // This method remains unchanged from the original
        $descriptions = [
            'calming' => 'Perfect for relaxation and stress relief',
            'energizing' => 'Ideal for boosting energy and motivation',
            'focusing' => 'Helps improve concentration and mental clarity',
            'balancing' => 'Promotes overall harmony and well-being'
        ];
        return $descriptions[$moodEffect] ?? '';
    }

    /**
     * Simple analytics - aggregates counts by date.
     * (Unchanged from original)
     */
    public function getAnalytics($timeRange = 30) {
        try {
             $intervalClause = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
             if ($timeRange === 'all') {
                 $intervalClause = "1=1"; // No date filtering for 'all'
                 $params = [];
             } else {
                 $timeRange = max(1, (int)$timeRange); // Ensure positive integer
                 $params = [$timeRange];
             }

             $sql = "
                 SELECT
                     DATE(created_at) as date,
                     COUNT(*) as total_quizzes,
                     COUNT(DISTINCT CASE WHEN user_id IS NOT NULL THEN user_id ELSE email END) as unique_participants,
                     COUNT(DISTINCT user_id) as registered_users,
                     COUNT(DISTINCT CASE WHEN user_id IS NULL THEN email END) as guest_users
                 FROM quiz_results
                 WHERE {$intervalClause}
                 GROUP BY DATE(created_at)
                 ORDER BY date ASC /* Changed to ASC for charting */
             ";
             $stmt = $this->pdo->prepare($sql);
             $stmt->execute($params);
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             error_log("Error getting quiz analytics: " . $e->getMessage());
             return []; // Return empty on error
         }
    }

    /**
     * Gets counts of popular mood selections.
     * (Unchanged from original)
     */
    public function getPopularMoods($timeRange = 30) {
        try {
             $intervalClause = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
             if ($timeRange === 'all') {
                 $intervalClause = "1=1";
                 $params = [];
             } else {
                 $timeRange = max(1, (int)$timeRange);
                 $params = [$timeRange];
             }

             $sql = "
                 SELECT
                     JSON_UNQUOTE(JSON_EXTRACT(answers, '$.mood')) as mood, /* Assumes 'mood' key */
                     COUNT(*) as count
                 FROM quiz_results
                 WHERE JSON_VALID(answers) /* Ensure answers is valid JSON */
                   AND JSON_EXTRACT(answers, '$.mood') IS NOT NULL /* Ensure mood key exists */
                   AND {$intervalClause}
                 GROUP BY mood
                 HAVING mood IS NOT NULL AND mood != '' /* Filter out null/empty results */
                 ORDER BY count DESC
             ";
             $stmt = $this->pdo->prepare($sql);
             $stmt->execute($params);
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             error_log("Error getting popular moods: " . $e->getMessage());
             return []; // Return empty on error
         }
    }

    /**
     * Gets personalized recommendations based on user's latest quiz.
     * (Unchanged from original)
     */
    public function getPersonalizedRecommendations($userId, $limit = 3) {
         try {
            $limit = max(1, (int)$limit); // Ensure positive limit

            // Get user's most recent quiz result
            $stmtHistory = $this->pdo->prepare("
                SELECT answers, recommendations /* Use correct column */
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmtHistory->execute([$userId]);
            $latestResult = $stmtHistory->fetch();

            $excludeIds = [];
            $targetMood = null;
            $targetScent = null; // Added target scent possibility

            if ($latestResult) {
                $answers = json_decode($latestResult['answers'], true);
                $targetMood = $answers['mood'] ?? null;
                // Decode existing recommendations to exclude them
                $excludeIds = json_decode($latestResult['recommendations'], true); // Use correct column
                if (!is_array($excludeIds)) $excludeIds = [];
                $excludeIds = array_filter($excludeIds, 'is_numeric'); // Ensure numeric IDs
            }

            // Build query dynamically based on available criteria
            $sql = "SELECT DISTINCT p.*, pa.mood_effect, pa.scent_type
                    FROM products p
                    LEFT JOIN product_attributes pa ON p.id = pa.product_id
                    WHERE 1=1 "; // Start WHERE clause
            $params = [];

            if ($targetMood) {
                 $moodEffectMap = ['relaxation' => 'calming', 'energy' => 'energizing', 'focus' => 'focusing', 'balance' => 'balancing'];
                 if (isset($moodEffectMap[$targetMood])) {
                     $sql .= " AND pa.mood_effect = ?";
                     $params[] = $moodEffectMap[$targetMood];
                 }
            }

            // Optionally: Add scent preference logic if quiz captures it
            // if ($targetScent) { $sql .= " AND pa.scent_type = ?"; $params[] = $targetScent; }

            if (!empty($excludeIds)) {
                $placeholders = rtrim(str_repeat('?,', count($excludeIds)), ',');
                $sql .= " AND p.id NOT IN ({$placeholders})";
                $params = array_merge($params, $excludeIds);
            }

            // Add ORDER BY and LIMIT
            $sql .= " ORDER BY RAND() LIMIT ?";
            $params[] = $limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fallback: If not enough personalized results, fill with featured
            $needed = $limit - count($products);
            if ($needed > 0) {
                // Add already recommended IDs to exclude list for fallback too
                $currentIds = array_column($products, 'id');
                $excludeFallback = array_merge($excludeIds, $currentIds);
                $fallbackProducts = $this->getFallbackRecommendations($needed, $excludeFallback);
                $products = array_merge($products, $fallbackProducts);
            }

            return array_slice($products, 0, $limit); // Ensure exactly $limit items

        } catch (PDOException $e) {
            error_log("Error getting personalized recommendations for user {$userId}: " . $e->getMessage());
            return $this->getFallbackRecommendations($limit); // Provide fallback on error
        }
    }

    /** Helper for fallback recommendations (Unchanged from original) */
    private function getFallbackRecommendations(int $limit, array $excludeIds = []): array {
         try {
             $sql = "SELECT p.*
                     FROM products p
                     WHERE p.is_featured = 1";
             $params = [];

             if (!empty($excludeIds)) {
                  $placeholders = rtrim(str_repeat('?,', count($excludeIds)), ',');
                  $sql .= " AND p.id NOT IN ({$placeholders})";
                  $params = $excludeIds;
             }

             $sql .= " ORDER BY RAND() LIMIT ?";
             $params[] = $limit;

             $stmt = $this->pdo->prepare($sql);
             // Bind params correctly based on whether excludeIds were added
             $paramIndex = 1;
             foreach($params as $param) {
                  $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
                  $stmt->bindValue($paramIndex++, $param, $type);
             }

             $stmt->execute();
             return $stmt->fetchAll(PDO::FETCH_ASSOC);
         } catch (PDOException $e) {
             error_log("Error getting fallback recommendations: " . $e->getMessage());
             return [];
         }
    }

    /**
     * Fetches detailed analytics data for the admin dashboard.
     * (Unchanged from previous correct version)
     */
    public function getDetailedAnalytics(string $timeRange): array {
        $results = [
            'statistics' => ['total_quizzes' => 0, 'unique_participants' => 0, 'conversion_rate' => 0, 'avg_completion_time' => 0],
            'preferences' => ['mood_effects' => [], 'scent_types' => [], 'daily_completions' => []], // Assuming structure for charts
            'recommendations' => []
        ];

        try {
            // Determine date interval SQL clause
            $intervalClause = "1=1"; // Default for 'all'
            $params = [];
            if ($timeRange !== 'all') {
                $days = (int)$timeRange;
                if ($days > 0) {
                    $intervalClause = "qr.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                    $params[] = $days;
                }
            }

            // 1. Basic Statistics
            $sqlStats = "
                SELECT
                    COUNT(qr.id) as total_quizzes,
                    COUNT(DISTINCT CASE WHEN qr.user_id IS NOT NULL THEN qr.user_id ELSE qr.email END) as unique_participants
                    /* Add conversion rate and avg time later if data available */
                FROM quiz_results qr
                WHERE {$intervalClause}
            ";
            $stmtStats = $this->pdo->prepare($sqlStats);
            $stmtStats->execute($params);
            $statsData = $stmtStats->fetch(PDO::FETCH_ASSOC);
            if ($statsData) {
                $results['statistics']['total_quizzes'] = (int)$statsData['total_quizzes'];
                $results['statistics']['unique_participants'] = (int)$statsData['unique_participants'];
                 $results['statistics']['conversion_rate'] = null; // Requires joining with orders
                 $results['statistics']['avg_completion_time'] = null; // Requires storing completion time
            }

            // 2. Preferences - Mood Effects (assuming 'mood' key in answers JSON)
            $sqlMood = "
                SELECT
                    JSON_UNQUOTE(JSON_EXTRACT(qr.answers, '$.mood')) as effect,
                    COUNT(*) as count
                FROM quiz_results qr
                WHERE JSON_VALID(qr.answers) AND JSON_EXTRACT(qr.answers, '$.mood') IS NOT NULL AND {$intervalClause}
                GROUP BY effect
                ORDER BY count DESC
            ";
            $stmtMood = $this->pdo->prepare($sqlMood);
            $stmtMood->execute($params);
            $results['preferences']['mood_effects'] = $stmtMood->fetchAll(PDO::FETCH_ASSOC);

            // 3. Preferences - Scent Types (Placeholder)
            $results['preferences']['scent_types'] = []; // Placeholder

            // 4. Daily Completions
            $sqlDaily = "
                SELECT DATE(qr.created_at) as date, COUNT(*) as count
                FROM quiz_results qr
                WHERE {$intervalClause}
                GROUP BY DATE(qr.created_at)
                ORDER BY date ASC
            ";
            $stmtDaily = $this->pdo->prepare($sqlDaily);
            $stmtDaily->execute($params);
            $results['preferences']['daily_completions'] = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

            // 5. Top Recommendations & Conversion (Placeholder)
            $results['recommendations'] = []; // Placeholder

        } catch (PDOException $e) {
            error_log("Error generating detailed quiz analytics: " . $e->getMessage());
        }
        return $results;
    }


    /**
     * Fetches the quiz submission history for a specific user.
     * (Unchanged from previous correct version)
     */
    public function getUserPreferenceHistory(int $userId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT answers, recommendations, created_at /* Use correct column name */
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optionally decode JSON fields here if needed by the view immediately
            foreach ($history as &$item) {
                 $item['answers'] = isset($item['answers']) ? (json_decode($item['answers'], true) ?? []) : [];
                 $item['recommendations'] = isset($item['recommendations']) ? (json_decode($item['recommendations'], true) ?? []) : []; // Use correct column name
            }
             unset($item); // Unset reference

            return $history ?: []; // Return history or empty array
        } catch (PDOException $e) {
            error_log("Error fetching user preference history for User ID {$userId}: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }


} // End Quiz Class
```

**2. `views/account/dashboard.php`**

```php
<?php
// views/account/dashboard.php (Layout Refactored with Tailwind CSS)
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
                                <a href="index.php?page=account&section=quiz" class="flex items-center px-4 py-2 rounded-md text-gray-600 hover:bg-gray-100 hover:text-primary transition duration-150 ease-in-out">
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
                    renderDashboardCard('Your Scent Profile', $quizContent, 'index.php?page=account&section=quiz', 'View History', 200);
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

---

**Summary of Changes and Actions:**

1.  **Quiz Results Issue:** Updated `models/Quiz.php` -> `getResultsByUserId` to select the correct `recommendations` column. Informed the user that the primary reason for no recommendations showing after the quiz is likely missing data in `product_attributes` or no featured products set, and this needs to be checked/populated in the database.
2.  **Dashboard Layout Issue:** Refactored `views/account/dashboard.php` using Tailwind CSS for a cleaner, more structured layout. Used a helper function `renderDashboardCard` for consistency.
3.  **Landing Page Links:** Clarified that the sections reported are not intended to be links. No code change made.
4.  **Checkout Payment Error:** Explained that the error stems from the placeholder Stripe Public Key in `config.php`. The user needs to replace `'pk_test_51xxx'` with their actual key. The debugging logs added in `js/main.js` previously will help confirm this. No further code changes made for this issue.
5.  **Compatibility:** Confirmed `index.php` and `js/main.js` are compatible with the refactored `AccountController.php`.
6.  **Other Files:** No other files required changes based on the compatibility review and the specific issues raised, beyond the `QuizModel` fix.

The user should now:
1. Replace the placeholder Stripe Public Key in `config.php`.
2. Check the `product_attributes` and `products` tables to ensure data exists for quiz recommendations.
3. Replace `models/Quiz.php` and `views/account/dashboard.php` with the updated versions provided above.

---
https://drive.google.com/file/d/10UXm7z2kPA06IHDhqeMTf3WZ5rKH1TnR/view?usp=sharing, https://drive.google.com/file/d/12oBsB_dhIP9L53VQlVcr9RC7n2W1SqKC/view?usp=sharing, https://drive.google.com/file/d/13rcrk6CPFQilYTWVSxJ8kVBxRiGD1ZWd/view?usp=sharing, https://drive.google.com/file/d/16myrTzOOfE6-HSfWMi6Si29UA-BJp-VQ/view?usp=sharing, https://drive.google.com/file/d/1AYgfMtN1d1-9u9vu3U8BTnMs_iimvXd3/view?usp=sharing, https://drive.google.com/file/d/1BMgwB5VP_u3hWVJJrF_xSdopPFL3Hb8c/view?usp=sharing, https://drive.google.com/file/d/1C17krIsUYodkrcWgIfReUX2rXbMcPO-x/view?usp=sharing, https://drive.google.com/file/d/1IdpqFzukNcHvMRWIOQdHqCPk9DMZAxeo/view?usp=sharing, https://drive.google.com/file/d/1IhIpJPaLBe1eCOxsWk2Y0mE1tHxOjzZD/view?usp=sharing, https://drive.google.com/file/d/1KRkJq2BdXRYc_vaJaHz_eKPpi27TzHeM/view?usp=sharing, https://drive.google.com/file/d/1LDZCUFOfOCqg6_kDHYtbj0m0gtMvig8p/view?usp=sharing, https://drive.google.com/file/d/1PKNAlUkqL1ZP4h6hglqpTAbjyENOXhQX/view?usp=sharing, https://drive.google.com/file/d/1YOn5sMoUPaO7sMbdyTIOPIxZAT81tIHl/view?usp=sharing, https://drive.google.com/file/d/1YRvmW5LOIMKZ2pdNCKb6W-jU97b7ufiJ/view?usp=sharing, https://drive.google.com/file/d/1YuJOhriI6V0oJN5vHvHUi6jDCI8iQ1h_/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221ZgPVhzXEiFkRf798JkS5aqhRjYYp2ITI%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1_5BVOFRWYIvmWzI-DzHETqvSoEWfh43l/view?usp=sharing, https://drive.google.com/file/d/1gN6zXQ_PTV2YF9GkwR3IagLx6X3gwxv9/view?usp=sharing, https://drive.google.com/file/d/1hJlAzdnBNoo1iYUGdaQsZxsH3txBd-62/view?usp=sharing, https://drive.google.com/file/d/1j-O6iOn3jWVDDFefVBLYx77Xld88yq5A/view?usp=sharing, https://drive.google.com/file/d/1soWEHHyAPUfcv_ZzhfB84O0W83iG7AuM/view?usp=sharing, https://drive.google.com/file/d/1tMdkomSIzN5_FIxTpVpCFk4bWzDwocv2/view?usp=sharing, https://drive.google.com/file/d/1wyFY4k7qi14bD00E6_2k5vrMVfvGURVQ/view?usp=sharing
