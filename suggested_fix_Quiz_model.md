Okay, I will update the `models/Quiz.php` file based on the provided `Quiz.php-orig.txt` by adding the missing `getDetailedAnalytics` and `getUserPreferenceHistory` methods, and adjusting the `saveQuizResult` signature for compatibility.

Here's the complete updated `models/Quiz.php`:

```php
<?php
// models/Quiz.php (Updated with getDetailedAnalytics, getUserPreferenceHistory, and adjusted saveQuizResult signature)

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
                (user_id, email, answers, recommended_products, created_at) /* Changed column name */
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
     * (Unchanged from original provided file)
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

    // --- START: NEW Method for detailed admin analytics ---
    /**
     * Fetches detailed analytics data for the admin dashboard.
     *
     * @param string $timeRange String identifier for the time range ('7', '30', '90', 'all').
     * @return array Structured analytics data.
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
                // Placeholder for conversion rate and avg time - requires order data join or quiz details
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

            // 3. Preferences - Scent Types (Placeholder - Requires mapping answers/recommendations to scent types)
            // This is complex without knowing the quiz structure or having product attributes linked directly in results
            // Example: If recommendations link to products with attributes
            // $sqlScent = "SELECT pa.scent_type as type, COUNT(*) as count ... JOIN products p ... JOIN product_attributes pa ... WHERE {$intervalClause} GROUP BY pa.scent_type ...";
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

            // 5. Top Recommendations & Conversion (Placeholder - requires joins/logic)
             // Example SQL structure (highly simplified, needs refinement)
             /*
             $sqlRecs = "
                 SELECT
                     p.id, p.name, c.name as category,
                     COUNT(DISTINCT qr.id) as recommendation_count,
                     -- Conversion requires joining recommendations with actual orders
                     NULL as conversion_rate
                 FROM quiz_results qr
                 CROSS JOIN JSON_TABLE(qr.recommended_products, '$[*]' COLUMNS(product_id INT PATH '$')) as recs
                 JOIN products p ON recs.product_id = p.id
                 LEFT JOIN categories c ON p.category_id = c.id
                 WHERE {$intervalClause}
                 GROUP BY p.id, p.name, c.name
                 ORDER BY recommendation_count DESC
                 LIMIT 10
             ";
             $stmtRecs = $this->pdo->prepare($sqlRecs);
             $stmtRecs->execute($params);
             $results['recommendations'] = $stmtRecs->fetchAll(PDO::FETCH_ASSOC);
             */
            $results['recommendations'] = []; // Placeholder


        } catch (PDOException $e) {
            error_log("Error generating detailed quiz analytics: " . $e->getMessage());
            // Return empty structure on error
        }
        return $results;
    }
    // --- END: NEW Method for detailed admin analytics ---

    // --- START: NEW Method for user preference history ---
    /**
     * Fetches the quiz submission history for a specific user.
     *
     * @param int $userId The user's ID.
     * @return array An array containing the user's quiz history (answers, recommendations, date).
     */
    public function getUserPreferenceHistory(int $userId): array {
        try {
            $stmt = $this->pdo->prepare("
                SELECT answers, recommended_products, created_at
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            $stmt->execute([$userId]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Optionally decode JSON fields here if needed by the view immediately
            foreach ($history as &$item) {
                 $item['answers'] = isset($item['answers']) ? (json_decode($item['answers'], true) ?? []) : [];
                 $item['recommended_products'] = isset($item['recommended_products']) ? (json_decode($item['recommended_products'], true) ?? []) : [];
            }
             unset($item); // Unset reference

            return $history ?: []; // Return history or empty array
        } catch (PDOException $e) {
            error_log("Error fetching user preference history for User ID {$userId}: " . $e->getMessage());
            return []; // Return empty array on error
        }
    }
    // --- END: NEW Method for user preference history ---


} // End Quiz Class
```

**Summary of Changes:**

1.  **Added `getDetailedAnalytics(string $timeRange)`:**
    *   Implemented the method to fetch structured data for the admin quiz analytics page.
    *   Includes queries for basic stats (total quizzes, unique participants), mood preferences (by extracting `$.mood` from the `answers` JSON), and daily completions.
    *   Placeholders are included for scent type preferences and recommendation conversion rates, as these require more complex joins or assumptions about the data structure that are not currently defined.
    *   Uses prepared statements and handles the `$timeRange` parameter correctly.
    *   Returns a structured array suitable for the controller and potentially the JS charting library.
2.  **Added `getUserPreferenceHistory(int $userId)`:**
    *   Implemented the method to fetch a user's quiz submission history (`answers`, `recommended_products`, `created_at`) ordered by date.
    *   Includes optional JSON decoding within the method.
    *   Handles errors gracefully, returning an empty array.
3.  **Adjusted `saveQuizResult` Signature:**
    *   Changed the method signature to `saveQuizResult(?int $userId, ?string $email, array $answers, array $recommendationIds, ?array $details = null): bool`. This matches the call pattern in the updated `QuizController` (`processQuiz` method), accepting the optional `$details` array even though it's not currently used within this model method. This prevents potential "ArgumentCountError" issues. The core logic still uses the `$recommendationIds` passed directly.

These changes ensure the `Quiz` model now provides the necessary methods (`getDetailedAnalytics`, `getUserPreferenceHistory`) called by the `QuizController` and aligns the `saveQuizResult` method signature.

---
https://drive.google.com/file/d/1-9_azZDcWjw0TcxtXLU40c5sLcK6BhAm/view?usp=sharing, https://drive.google.com/file/d/13uCUSW5n2_nNW-77ovqKgYBLTEgdt78G/view?usp=sharing, https://drive.google.com/file/d/14Tprq14Ce_o4PzicV8uN8NjRAR3RHVga/view?usp=sharing, https://drive.google.com/file/d/14VYTeB9-4Jm5_uVfbgZnDP1ifPtzpdjF/view?usp=sharing, https://drive.google.com/file/d/18K8IRy-9Wv97u6dOOefW5Fo2O8OJTKvl/view?usp=sharing, https://drive.google.com/file/d/1BYdkXkHVScOEmji3peSUTH1l6-pGVteb/view?usp=sharing, https://drive.google.com/file/d/1CQg29dlNbFSG8K8BUbIhdXk_2XrlAuLj/view?usp=sharing, https://drive.google.com/file/d/1D4n5LqJNzTS8OlAUmA-wk0Xxek6SsTkp/view?usp=sharing, https://drive.google.com/file/d/1G5hYeMTkOhgxce0DnqtPfXhSq8hbML7l/view?usp=sharing, https://drive.google.com/file/d/1HJuCZY5JdHpFjZOMceiMqXXAM1utvSiY/view?usp=sharing, https://drive.google.com/file/d/1LZqGe8yBastGfSr-2FF-dlCicVKx8Mxt/view?usp=sharing, https://drive.google.com/file/d/1MJbk8yBKEoXRTRPPZX00iF9mWEk63Xkp/view?usp=sharing, https://drive.google.com/file/d/1OGtWyJ9MarMZw1v6g54oklTyTBCuMBm6/view?usp=sharing, https://drive.google.com/file/d/1QLzTWsgtcU9pmDj37XknhJLR9jZc5La_/view?usp=sharing, https://drive.google.com/file/d/1QoZi6xLUiDkyha45tLtquPebxFa4vyrO/view?usp=sharing, https://drive.google.com/file/d/1TUtZdMZJIDtRzist1dRVNx8ba6tVZPoX/view?usp=sharing, https://drive.google.com/file/d/1Xz7Y-gFs0LvsNOJyoWicBYWEsbLB2ABW/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221eqUTi2hMG40eJPyqdHnqUSIqReBhkgAE%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1f6lL5QvX8TUZaBD34LCo_R69cVCpd74A/view?usp=sharing, https://drive.google.com/file/d/1l6LyD9k3XbqyvB6vF95HsFFYYAUHJeJi/view?usp=sharing, https://drive.google.com/file/d/1lh9lUQ_xb3y4KTKGbUOeVK6_5qpzQq-2/view?usp=sharing, https://drive.google.com/file/d/1mZQ39XUhy-jccDzKHeMjSp4HQNMjBhBG/view?usp=sharing, https://drive.google.com/file/d/1z9MLM7BFEh6TEnWfYMCD9rZBKyacanrI/view?usp=sharing
