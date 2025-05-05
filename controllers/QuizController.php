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
