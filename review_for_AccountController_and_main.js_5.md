Okay, I understand the core issue is the "Oops! Something Went Wrong" error page appearing after a successful login, and the provided error log clearly points to `AccountController::showDashboard()` calling a non-existent method `Quiz::getResultsByUserId()`.

My plan is:

1.  **Confirm Compatibility:** Review `index.php` and `main.js` against the refactored `AccountController.php` to ensure routing, AJAX calls, and expected data structures align.
2.  **Diagnose the Error:** Verify the error log's finding by checking `AccountController.php` and `models/Quiz.php`.
3.  **Implement the Fix:** Add the missing `getResultsByUserId` method to `models/Quiz.php`.
4.  **Review Other Files:** Systematically check other PHP files for compatibility issues related to the `AccountController`, `index.php`, and `main.js` trio, starting with `header.php`.
5.  **Provide Updated Code:** Generate the complete, updated version of `models/Quiz.php`.
6.  **Summarize:** Explain the findings and the fix applied.

```php
<think>
**Step 1 & 2: Compatibility Check & Error Diagnosis**

*   **Error Source:** The log `Call to undefined method Quiz::getResultsByUserId() ... in AccountController->showDashboard()` is definitive. The problem lies in `AccountController::showDashboard` attempting to fetch quiz results for the user from the `Quiz` model using a method that isn't defined there.
*   **Checking `AccountController.php` (`content_of_code_files_4.md`):**
    *   Inside `showDashboard()`:
        ```php
        $quizResults = $this->quizModel->getResultsByUserId($userId); // Assuming this method exists
        ```
    *   This confirms the controller *expects* this method on the `Quiz` model (`$this->quizModel`).
*   **Checking `models/Quiz.php` (`content_of_code_files_1.md`):**
    *   Scanning the file confirms there is no method named `getResultsByUserId`. Methods available are `__construct`, `getQuestions`, `getRecommendations`, `saveQuizResult`, `getScentDescription`, `getMoodDescription`, `getAnalytics`, `getPopularMoods`, `getPersonalizedRecommendations`.
    *   **Diagnosis Confirmed:** The method call is incorrect.
*   **Checking `index.php` (`content_of_code_files_1.md` vs. `AccountController.php`):**
    *   Routing for `page=account` correctly includes `AccountController.php`.
    *   The `switch ($section)` block correctly handles `dashboard`, `profile`, `orders`, routing to the corresponding `AccountController` methods (`showDashboard`, `showProfile`/`updateProfile`, `showOrders`/`showOrderDetails`).
    *   Routing for login/register/password reset pages also correctly points to `AccountController` methods.
    *   CSRF validation is handled globally in `index.php` for POST, and `AccountController` methods correctly call `$this->validateCSRF()` for their POST actions.
    *   **Conclusion:** `index.php` appears compatible with `AccountController`'s structure and routing expectations.
*   **Checking `main.js` (`content_of_code_files_3.md` vs. `AccountController.php`):**
    *   `initLoginPage`: Handles AJAX login, sends `email`, `password`, `csrf_token`. Expects JSON `success/error/redirect`. Matches `AccountController::login()`.
    *   `initRegisterPage`: Handles AJAX registration, sends `name`, `email`, `password`, `confirm_password`, `csrf_token`, `newsletter_signup`. Expects JSON `success/error/redirect`. Matches `AccountController::register()`.
    *   `initForgotPasswordPage` / `initResetPasswordPage`: Handle UI validation/spinners for standard POST forms. Matches non-AJAX nature of password reset in `AccountController`.
    *   `initAccountProfilePage` (implied): JS handles password toggle. Form submission appears to be standard POST, matching `AccountController::updateProfile()`.
    *   **Conclusion:** `main.js` appears compatible with `AccountController`'s AJAX/POST expectations for login, registration, and profile interactions.

**Step 3: Plan the Fix**

*   The most logical fix is to implement the missing `getResultsByUserId` method in `models/Quiz.php`. This method should query the `quiz_results` table for a given user ID.

**Step 4: Implement Fix in `models/Quiz.php`**

*   Add the new method `getResultsByUserId(int $userId): array`.
*   Query `quiz_results` table, selecting relevant columns (e.g., `id`, `answers`, `recommendations`, `created_at`).
*   Filter by `user_id`.
*   Order by `created_at DESC` to get the most recent results first.
*   Use prepared statements.
*   Include basic error handling (try/catch).

**Step 5: Review Other Files for Compatibility**

*   **`views/layout/header.php` (`content_of_code_files_1.md`):** It checks `isLoggedIn()` (defined in `includes/auth.php`, which `AccountController` uses implicitly via `requireLogin`), gets cart count from session (updated by `AccountController` on login), and outputs global CSRF token. Seems compatible.
*   **`views/account/dashboard.php` (`content_of_code_files_5.md`):** This view *uses* the `$quizResults` variable. It was failing because `AccountController` couldn't fetch this data. With the fix in `models/Quiz.php`, `AccountController` should now successfully fetch `$quizResults`, making this view compatible.
*   **Other `views/account/*.php`:** These depend on `$user` and `$orders` data passed by `AccountController`. The controller methods (`showProfile`, `showOrders`, `showOrderDetails`) correctly fetch and pass this data. Compatible.
*   **`models/User.php` (`content_of_code_files_1.md`):** `AccountController` heavily relies on this model. Methods like `getByEmail`, `create`, `verifyPassword`, `setResetToken`, `getUserByValidResetToken`, `resetPassword`, `updateBasicInfo`, `updatePassword`, `isEmailTakenByOthers`, `updateNewsletterPreference` are all present and used correctly by the controller. Compatible.
*   **`models/Order.php` (`content_of_code_files_2.md`):** `AccountController::showDashboard` calls `getRecentByUserId`. `AccountController::showOrders` calls `getAllByUserId` and `getTotalOrdersByUserId`. `AccountController::showOrderDetails` calls `getByIdAndUserId`. These methods exist. Compatible.
*   **`includes/EmailService.php` (`content_of_code_files_5.md`):** `AccountController` calls `sendPasswordReset` and `sendWelcome`. These methods exist. Compatible.
*   **`controllers/CartController.php` (`content_of_code_files_2.md`):** `AccountController::login` calls the static method `CartController::mergeSessionCartOnLogin`. This method exists. Compatible.
*   **`includes/auth.php` (`content_of_code_files_5.md`):** Provides global functions like `isLoggedIn()`. `AccountController` uses `$this->requireLogin()` which effectively checks the session state set during login, consistent with `isLoggedIn()`. Compatible.
*   **`controllers/BaseController.php` (`content_of_code_files_1.md`):** `AccountController` extends this and uses its methods (`requireLogin`, `getUserId`, `getCurrentUser`, `validateCSRF`, `validateInput`, `setFlashMessage`, `redirect`, `renderView`, `logAuditTrail`, `logSecurityEvent`, `beginTransaction`, `commit`, `rollback`, `jsonResponse`, `getCsrfToken`). Compatible.
*   **Other files:** No direct interactions identified that would be broken by the `AccountController` refactoring or the `Quiz` model fix.

**Step 6: Generate Updated Code**

*   Prepare the complete `models/Quiz.php` file, adding the new method based on the original provided version.

</think>
```php
<?php
// models/Quiz.php (Updated to add getResultsByUserId)

class Quiz {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getQuestions() {
        // --- This method remains unchanged ---
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
        // --- End Unchanged Section ---
    }

    public function getRecommendations($answers) {
        // --- This method remains unchanged ---
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
                JOIN product_attributes pa ON p.id = pa.product_id
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

            return $products;
        } catch (PDOException $e) {
            error_log("Error getting recommendations: " . $e->getMessage());
            throw $e; // Re-throw to be handled globally
        }
        // --- End Unchanged Section ---
    }

    public function saveQuizResult($userId, $email, $answers, $recommendations) {
        // --- This method remains unchanged ---
        try {
            // Extract product IDs from the recommendations array
            $recommendationIds = [];
            if (is_array($recommendations)) {
                foreach ($recommendations as $product) {
                    if (isset($product['id'])) {
                        $recommendationIds[] = (int)$product['id'];
                    }
                }
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO quiz_results
                (user_id, email, answers, recommended_products, created_at) /* Changed column name */
                VALUES (?, ?, ?, ?, NOW()) /* Use NOW() for DB consistency */
            ");

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
        // --- End Unchanged Section ---
    }

    // --- START: ADDED METHOD to fix the error ---
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
            // Select necessary columns, potentially decode JSON fields if needed later
            $stmt = $this->pdo->prepare("
                SELECT id, user_id, email, answers, recommended_products, created_at
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optionally decode JSON fields here if needed immediately
            // foreach ($results as &$result) {
            //     $result['answers'] = isset($result['answers']) ? json_decode($result['answers'], true) : [];
            //     $result['recommended_products'] = isset($result['recommended_products']) ? json_decode($result['recommended_products'], true) : [];
            // }
            // unset($result);

            return $results ?: []; // Return results or empty array

        } catch (PDOException $e) {
            error_log("Error fetching quiz results for user ID {$userId}: " . $e->getMessage());
            return []; // Return empty array on database error
        }
    }
    // --- END: ADDED METHOD ---


    private function getScentDescription($scentType) {
        // --- This method remains unchanged ---
        $descriptions = [
            'floral' => 'Delicate and romantic floral notes that bring peace and harmony',
            'woody' => 'Rich, grounding woody scents that promote stability and strength',
            'citrus' => 'Bright, uplifting citrus notes that energize and refresh',
            'oriental' => 'Warm, exotic notes that create a sense of luxury and comfort',
            'fresh' => 'Clean, crisp scents that invigorate and purify'
        ];
        return $descriptions[$scentType] ?? '';
        // --- End Unchanged Section ---
    }

    private function getMoodDescription($moodEffect) {
        // --- This method remains unchanged ---
        $descriptions = [
            'calming' => 'Perfect for relaxation and stress relief',
            'energizing' => 'Ideal for boosting energy and motivation',
            'focusing' => 'Helps improve concentration and mental clarity',
            'balancing' => 'Promotes overall harmony and well-being'
        ];
        return $descriptions[$moodEffect] ?? '';
        // --- End Unchanged Section ---
    }

    public function getAnalytics($timeRange = 30) {
        // --- This method remains unchanged ---
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
        // --- End Unchanged Section ---
    }

    public function getPopularMoods($timeRange = 30) {
        // --- This method remains unchanged ---
        try {
             $intervalClause = "created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
             if ($timeRange === 'all') {
                 $intervalClause = "1=1";
                 $params = [];
             } else {
                 $timeRange = max(1, (int)$timeRange);
                 $params = [$timeRange];
             }

             // Adjust JSON path if answers structure changes
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
        // --- End Unchanged Section ---
    }

    public function getPersonalizedRecommendations($userId, $limit = 3) {
        // --- This method remains unchanged ---
         try {
            $limit = max(1, (int)$limit); // Ensure positive limit

            // Get user's most recent quiz result
            $stmtHistory = $this->pdo->prepare("
                SELECT answers, recommended_products
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
                $excludeIds = json_decode($latestResult['recommended_products'], true);
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
        // --- End Unchanged Section ---
    }

    /** Helper for fallback recommendations */
    private function getFallbackRecommendations(int $limit, array $excludeIds = []): array {
        // --- This helper method remains unchanged ---
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
        // --- End Unchanged Section ---
    }

} // End Quiz Class
```

**Summary of Changes:**

1.  **Added `getResultsByUserId(int $userId): array` method to `models/Quiz.php`:** This method queries the `quiz_results` table for the specified `$userId`, orders the results by creation date (descending), and returns them as an array. It includes basic error handling and returns an empty array on failure or if the user has no results.
2.  **Compatibility Review:** Confirmed that `index.php` routing and `main.js` AJAX handling are compatible with the refactored `AccountController`. No changes were needed in these files for this specific issue. The review of other files also indicated compatibility with the `AccountController` structure, contingent on the fix to the `Quiz` model.

**Conclusion:**

The error "Oops! Something Went Wrong" after login was directly caused by the `AccountController::showDashboard` method calling `getResultsByUserId` on the `Quiz` model, which did not exist. By adding the `getResultsByUserId` method to `models/Quiz.php` as provided above, this specific error should be resolved. The application should now correctly load the user dashboard after a successful login.

---
https://drive.google.com/file/d/14OI1fbTJTT-_D2xTD6qVQW7syzUYKOYA/view?usp=sharing, https://drive.google.com/file/d/18wvofKXB0pbUBXJtHDQXtrp4asC5KNJG/view?usp=sharing, https://drive.google.com/file/d/1BwfdVM3hmuVOeaLnr1KxUw9tIsE12Umc/view?usp=sharing, https://drive.google.com/file/d/1HNOEsAIbDAH-uMoOHUa80ZDpNtjbcDzS/view?usp=sharing, https://drive.google.com/file/d/1Hr_Yuo8-zGgO_F97n7feX8hM_KlIQUa8/view?usp=sharing, https://drive.google.com/file/d/1IMTgpaw0tPxNZcmJH4qBPtQoLosR9VXY/view?usp=sharing, https://drive.google.com/file/d/1Ie7aETBIpIRxeuQOi_sa4UowX6A7honf/view?usp=sharing, https://drive.google.com/file/d/1N6ZC3tw46ujk7Uat3wJ3T9UTv66J_USc/view?usp=sharing, https://drive.google.com/file/d/1UNnqGlM-Veo95tHfF7uc5j1UHNCbKwQc/view?usp=sharing, https://drive.google.com/file/d/1c41z7X7ICpj6IEOxJbESMYIqnKAt6SOj/view?usp=sharing, https://drive.google.com/file/d/1pnTxLaZWINuEIsl5exr-2kx981_FF7EG/view?usp=sharing, https://drive.google.com/file/d/1qKBdim8bqcF0U9EsKl-m1-1Lq7jPNyzl/view?usp=sharing, https://drive.google.com/file/d/1uX5e8O7cI5k_M7tcsRRtdkmRddCidUl0/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221v2IR9EZSFj8zloQQGB3D2CGhTWQ9YPhX%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1w3ZsrFWbKBBXf5SoiBLWr01yZVBxz18v/view?usp=sharing
