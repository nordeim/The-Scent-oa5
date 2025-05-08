Okay, I have updated the necessary files to address the checkout initialization error and the admin product form JSON field parsing.

1.  **Checkout Initialization Error:** The `STRIPE_PUBLIC_KEY` in `config.php` was indeed truncated. I have updated it with the full key provided in the test simulation file. This should resolve the "Could not initialize payment system" error on the checkout page.
2.  **Admin Product JSON Fields:** The `saveAdminProduct` method in `controllers/ProductController.php` has been modified to correctly parse the newline-separated strings from the `benefits` and `gallery_images` textareas into PHP arrays before passing the data to the model. The model (`ProductModel`) already handles `json_encode` correctly.

Here are the updated files:

**`config.php` (Updated)**
```php
<?php
// config.php (Updated - Correct Stripe PK)

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
        'allowed_origins' => [BASE_URL],
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
// --- START FIX: Use the correct, complete Stripe Public Key ---
define('STRIPE_PUBLIC_KEY', 'pk_test_51RLNNX4axRnYhkNVHz16qi7Gq4UnX5LDalYvXf3lIqneXziRQFrzrk0e4dMyBqaKQ8IxmJhSqtpiApC2TaBcIQqS00NJG40ELn');
// --- END FIX ---
define('STRIPE_SECRET_KEY', 'sk_test_51R'); // IMPORTANT: Ensure this is also the correct FULL secret key
define('STRIPE_WEBHOOK_SECRET', 'whsec_your_stripe_webhook_secret'); // IMPORTANT: Get this from your Stripe Dashboard Webhook settings

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
```

**`controllers/ProductController.php` (Updated)**
```php
<?php
// controllers/ProductController.php (Updated: Added JSON Textarea Parsing in saveAdminProduct)

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
            $this->logSecurityEvent('error_show_home', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'], []); // Corrected parameter order
            error_log("Error loading homepage: " . $e->getMessage()); // Fallback logging
            $this->setFlashMessage('An error occurred while loading the page', 'error');
            // Redirect to a generic error page using BaseController helper
            $this->redirect('index.php?page=error'); // Redirect to generic error page
        }
    }

    public function showProductList() {
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

            // --- START: FIX 1 - Build NAMED Params/Conditions ---
            $conditions = [];
            $params = []; // Now an associative array

            // Apply search condition
            if (!empty($searchQuery)) {
                $conditions[] = "(p.name LIKE :search_name OR p.description LIKE :search_desc)"; // Named placeholders
                $params[':search_name'] = "%{$searchQuery}%";
                $params[':search_desc'] = "%{$searchQuery}%";
            }

            // Apply category filter
            if ($categoryId !== null && $categoryId !== false && $categoryId > 0) {
                $conditions[] = "p.category_id = :category_id"; // Named placeholder
                $params[':category_id'] = $categoryId;
            }

            // Apply price filters
            if ($minPrice !== null && $minPrice !== false && is_numeric($minPrice)) {
                $conditions[] = "p.price >= :min_price"; // Named placeholder
                $params[':min_price'] = $minPrice;
            }
            if ($maxPrice !== null && $maxPrice !== false && is_numeric($maxPrice)) {
                $conditions[] = "p.price <= :max_price"; // Named placeholder
                $params[':max_price'] = $maxPrice;
            }
            // --- END: FIX 1 ---

            // Get total count for pagination using the same named conditions/params
            $totalProducts = $this->productModel->getCount($conditions, $params); // Pass named params
            $totalPages = ($this->itemsPerPage > 0) ? ceil($totalProducts / $this->itemsPerPage) : 1;
            $totalPages = max(1, $totalPages); // Ensure at least 1 page

            // Get paginated products using named params
            $products = $this->productModel->getFiltered(
                $conditions,
                $params, // Pass named params
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
            $this->logSecurityEvent('error_show_product_list', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'], []); // Corrected parameter order
            error_log("Error loading product list: " . $e->getMessage()); // Fallback logging
            $this->setFlashMessage('Error loading products. Please try again.', 'error');
            $this->redirect('index.php?page=error'); // Redirect to generic error page
        }
    }

    public function showProduct($id) {
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
            $this->logSecurityEvent('error_show_product_detail', ['product_id' => $id ?? null, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'], []); // Corrected parameter order
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
    }


    /**
     * Handles displaying the form for creating/editing a product (GET)
     */
    public function showAdminProductForm(?int $id = null) {
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
     }

    /**
      * Handles saving (create or update) product data submitted via POST.
      */
     public function saveAdminProduct() {
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
             ];

             // --- START: FIX - Parse Textareas for JSON Fields ---
             $benefitsInput = $_POST['benefits'] ?? '';
             // Split by any newline type (\r\n, \r, \n), trim whitespace from each line, filter out empty lines, re-index numerically
             $data['benefits'] = !empty($benefitsInput)
                 ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $benefitsInput))))
                 : []; // Default to empty array if textarea was empty

             $galleryInput = $_POST['gallery_images'] ?? '';
             $data['gallery_images'] = !empty($galleryInput)
                 ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $galleryInput))))
                 : []; // Default to empty array
             // --- END: FIX - Parse Textareas for JSON Fields ---


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
     }

    /**
     * Handles deleting a product via POST request.
     */
    public function deleteAdminProduct(?int $id = null) {
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

---

**Conclusion:**

The checkout page initialization error was primarily due to an incomplete Stripe Public Key in the configuration. This has been corrected in `config.php`. Additionally, the logic for parsing multi-line text input for JSON fields ('benefits', 'gallery_images') in the Admin Product form has been implemented in `controllers/ProductController.php`.

With these updates, the checkout process should proceed to initialize the Stripe payment element correctly, and the admin product form should handle the specified JSON fields as intended. No further changes related to the `AccountController` refactoring or the initial reported issues were deemed necessary in other files.

---
Okay, I will carefully review the provided files and address the checkout initialization issue, along with ensuring compatibility with the refactored `AccountController`.

<think>
**Plan:**

1.  **Analyze Checkout Initialization Issue:**
    *   Trace the error "Could not initialize payment system. Please refresh." in `js/main.js` -> `initCheckoutPage`.
    *   Identify the point of failure: likely the `stripe = Stripe(stripePublicKey);` call within the `try...catch` block.
    *   Hypothesize potential causes:
        *   Invalid/missing `stripePublicKey`.
        *   Stripe.js library not loaded.
        *   Other JS errors preventing execution.
    *   Verify the source of `stripePublicKey`: It comes from `document.body.dataset.stripePublicKey`, which is populated by `views/layout/header.php` using the `STRIPE_PUBLIC_KEY` constant from `config.php`.
    *   Check `config.php` (`content_of_code_files_1.md`) for the `STRIPE_PUBLIC_KEY` value.
    *   Compare with the key used in the successful test (`stripe_test_v4.html`): `pk_test_51RLNNX4axRnYhkNVHz16qi7Gq4UnX5LDalYvXf3lIqneXziRQFrzrk0e4dMyBqaKQ8IxmJhSqtpiApC2TaBcIQqS00NJG40ELn`.

2.  **Confirm Compatibility (`index.php`, `js/main.js` with `AccountController`)**
    *   Review `index.php` routing for `page=login`, `page=register`, `page=account`, `page=forgot_password`, `page=reset_password`. Ensure they correctly call the methods in the refactored `AccountController` (`login`, `register`, `updateProfile`, `showProfile`, `requestPasswordReset`, `resetPassword`, etc.). Pay attention to GET vs. POST handling and AJAX expectations.
    *   Review `js/main.js` for `initLoginPage`, `initRegisterPage`. Ensure they use `fetch` correctly, handle JSON responses, and manage CSRF tokens for the AJAX login/register endpoints in `AccountController`.
    *   Check if profile updates in `js/main.js` rely on AJAX or standard POST, and verify `AccountController::updateProfile` handles the corresponding mechanism (standard POST based on the controller code).

3.  **Broader Compatibility Review:**
    *   Check `views/layout/header.php` for correct output of `data-stripe-public-key` and global CSRF token.
    *   Check `views/login.php`, `views/register.php`, etc., for correct form structures matching `AccountController` expectations and `js/main.js` AJAX handling.
    *   Check `views/account/*.php` for compatibility with data provided by `AccountController` methods (`showDashboard`, `showOrders`, `showProfile`).
    *   Check `includes/auth.php` consistency.
    *   Check other controllers/models for dependencies or interactions that might be affected (unlikely, but worth a quick scan).
    *   Review the Admin Product Form (`views/admin/product_form.php`) and the corresponding save logic (`controllers/ProductController.php::saveAdminProduct`) to ensure the handling of textarea fields intended for JSON data (`benefits`, `gallery_images`) is implemented correctly in the controller. The view uses standard textareas, so the controller must parse the newline-separated input.

4.  **Address Findings:**
    *   If the Stripe key is incorrect in `config.php`, update it.
    *   If compatibility issues are found between `index.php`/`main.js` and `AccountController`, modify the relevant file(s).
    *   If the Admin Product Controller needs updating for JSON fields, implement the parsing logic.
    *   If any other compatibility issues arise, address them.

5.  **Generate Output:**
    *   Provide the complete, updated versions of any modified files.
    *   Include a summary explaining the findings and the fixes applied, focusing on the checkout initialization error.

**Execution Analysis & Pre-computation:**

*   **Checkout Issue:** Checking `config.php` in `content_of_code_files_1.md`, the `STRIPE_PUBLIC_KEY` is `pk_test_51R`, which is clearly truncated. The correct key from `stripe_test_v4.html` needs to be placed here. This is the primary cause of the initialization failure. The JS code itself for reading and using the key is correct.
*   **Compatibility (`index.php`, `main.js`, `AccountController`)**:
    *   `index.php`: Routing looks correct. `page=account&section=profile` handles POST via `updateProfile`, GET via `showProfile`. Login/Register POST routes to respective methods. Seems compatible.
    *   `js/main.js`: `initLoginPage`/`initRegisterPage` use `fetch` and expect JSON, matching `AccountController`'s `login`/`register` methods which use `jsonResponse`. Profile updates rely on standard form POST, which `AccountController::updateProfile` handles. Seems compatible.
*   **Broader Review:**
    *   **Admin Product JSON Fields:** `views/admin/product_form.php` uses simple `<textarea>` for `benefits` and `gallery_images`. `controllers/ProductController.php` (`saveAdminProduct` in `content_of_code_files_2.md`) does *not* currently parse these textareas into arrays before calling the model's `create`/`update` method, which expects arrays for these JSON fields. This needs fixing in `ProductController::saveAdminProduct`.
    *   **Other files:** No other immediate incompatibilities jump out based on the refactored `AccountController` or the checkout fix.

**Final Plan Refined:**

1.  Fix `STRIPE_PUBLIC_KEY` in `config.php`.
2.  Update `controllers/ProductController.php` -> `saveAdminProduct` to parse the `benefits` and `gallery_images` textareas into arrays before passing them to the model.
3.  Provide the updated `config.php` and `controllers/ProductController.php` files.
4.  Confirm no changes are needed for `index.php`, `js/main.js`, or other files based *specifically* on the `AccountController` refactor or the checkout key fix.

</think>

Based on the analysis of the provided files and logs, here's the breakdown and the necessary fixes:

**1. Checkout Initialization Error ("Could not initialize payment system")**

*   **Root Cause:** The Stripe Public Key defined in `config.php` is truncated (`pk_test_51R`). The `Stripe()` function in `js/main.js` receives this invalid key via the `data-stripe-public-key` attribute (populated by `header.php` reading the constant) and fails to initialize the Stripe object, triggering the error message in the `catch` block. The debugging `console.log` added in `js/main.js` would confirm that an incomplete key is being passed to `Stripe()`.
*   **Fix:** Update the `STRIPE_PUBLIC_KEY` constant in `config.php` with the complete, correct test key.

**2. Compatibility Review (`index.php`, `js/main.js` with `AccountController`)**

*   **`index.php`:** The routing logic correctly handles both standard POST requests (for profile updates) and points AJAX requests (for login/register) to the appropriate `AccountController` methods. It is compatible with the refactored controller structure. No changes needed.
*   **`js/main.js`:** The AJAX handlers for login (`initLoginPage`) and registration (`initRegisterPage`) correctly use `fetch`, send `FormData`, and expect JSON responses, which aligns with the refactored `AccountController` methods (`login`, `register`) that use `jsonResponse`. The profile page updates rely on standard form submissions, which `AccountController::updateProfile` handles correctly. `js/main.js` is compatible. No changes needed *based on this specific compatibility check*.

**3. Broader Compatibility & Other Issues**

*   **Admin Product Form JSON Fields:** `views/admin/product_form.php` uses standard textareas for 'Benefits' and 'Gallery Images'. However, `controllers/ProductController.php` (`saveAdminProduct` method) does not parse the newline-separated input from these textareas into PHP arrays before passing the data to the `ProductModel`. The model expects arrays for these fields to `json_encode`.
    *   **Fix:** Modify `controllers/ProductController.php` -> `saveAdminProduct` to parse the `benefits` and `gallery_images` POST data from string (with newlines) into arrays.
*   **Other Files:** No other compatibility issues directly related to the `AccountController` refactor or the checkout key fix were identified in the other controllers, models, or views provided. The previous fixes (address handling, database schema patches, etc.) seem correctly implemented in the latest versions of the relevant files. The SQL error (`Unknown column 'subtotal'`) seen in the older error logs appears resolved in the current `models/Order.php::create` method.

**Summary of Changes:**

1.  **`config.php`:** Update `STRIPE_PUBLIC_KEY` constant.
2.  **`controllers/ProductController.php`:** Modify `saveAdminProduct` to handle JSON textarea parsing.

---

**Updated Files:**

```php
<?php
// config.php (Updated - Correct Stripe PK)

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
// --- START FIX: Use the correct, complete Stripe Public Key ---
define('STRIPE_PUBLIC_KEY', 'pk_test_51RLNNX4axRnYhkNVHz16qi7Gq4UnX5LDalYvXf3lIqneXziRQFrzrk0e4dMyBqaKQ8IxmJhSqtpiApC2TaBcIQqS00NJG40ELn');
// --- END FIX ---
define('STRIPE_SECRET_KEY', 'sk_test_51R'); // Ensure this is also the correct FULL secret key
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
```

```php
<?php
// controllers/ProductController.php (Updated: Added JSON Textarea Parsing in saveAdminProduct)

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
            $this->logSecurityEvent('error_show_home', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'], []); // Corrected parameter order
            error_log("Error loading homepage: " . $e->getMessage()); // Fallback logging
            $this->setFlashMessage('An error occurred while loading the page', 'error');
            // Redirect to a generic error page using BaseController helper
            $this->redirect('index.php?page=error'); // Redirect to generic error page
        }
    }

    public function showProductList() {
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

            // --- START: FIX 1 - Build NAMED Params/Conditions ---
            $conditions = [];
            $params = []; // Now an associative array

            // Apply search condition
            if (!empty($searchQuery)) {
                $conditions[] = "(p.name LIKE :search_name OR p.description LIKE :search_desc)"; // Named placeholders
                $params[':search_name'] = "%{$searchQuery}%";
                $params[':search_desc'] = "%{$searchQuery}%";
            }

            // Apply category filter
            if ($categoryId !== null && $categoryId !== false && $categoryId > 0) {
                $conditions[] = "p.category_id = :category_id"; // Named placeholder
                $params[':category_id'] = $categoryId;
            }

            // Apply price filters
            if ($minPrice !== null && $minPrice !== false && is_numeric($minPrice)) {
                $conditions[] = "p.price >= :min_price"; // Named placeholder
                $params[':min_price'] = $minPrice;
            }
            if ($maxPrice !== null && $maxPrice !== false && is_numeric($maxPrice)) {
                $conditions[] = "p.price <= :max_price"; // Named placeholder
                $params[':max_price'] = $maxPrice;
            }
            // --- END: FIX 1 ---

            // Get total count for pagination using the same named conditions/params
            $totalProducts = $this->productModel->getCount($conditions, $params); // Pass named params
            $totalPages = ($this->itemsPerPage > 0) ? ceil($totalProducts / $this->itemsPerPage) : 1;
            $totalPages = max(1, $totalPages); // Ensure at least 1 page

            // Get paginated products using named params
            $products = $this->productModel->getFiltered(
                $conditions,
                $params, // Pass named params
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
            $this->logSecurityEvent('error_show_product_list', ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'], []); // Corrected parameter order
            error_log("Error loading product list: " . $e->getMessage()); // Fallback logging
            $this->setFlashMessage('Error loading products. Please try again.', 'error');
            $this->redirect('index.php?page=error'); // Redirect to generic error page
        }
    }

    public function showProduct($id) {
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
            $this->logSecurityEvent('error_show_product_detail', ['product_id' => $id ?? null, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'], []); // Corrected parameter order
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
             ];

             // --- START: FIX - Parse Textareas for JSON Fields ---
             $benefitsInput = $_POST['benefits'] ?? '';
             // Split by any newline type, trim whitespace from each line, filter out empty lines, re-index numerically
             $data['benefits'] = !empty($benefitsInput)
                 ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $benefitsInput))))
                 : []; // Default to empty array if textarea was empty

             $galleryInput = $_POST['gallery_images'] ?? '';
             $data['gallery_images'] = !empty($galleryInput)
                 ? array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $galleryInput))))
                 : []; // Default to empty array
             // --- END: FIX - Parse Textareas for JSON Fields ---


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

---

**Summary:**

1.  **Checkout Initialization Error:** Fixed by updating the `STRIPE_PUBLIC_KEY` in `config.php` to the correct, full value. The JS and view logic for handling the key were already correct. The debugging logs added to `js/main.js` should now show the correct key being used, and the Stripe object should initialize successfully.
2.  **Admin Product Form (JSON Fields):** Updated `controllers/ProductController.php` (`saveAdminProduct` method) to correctly parse newline-separated input from the `benefits` and `gallery_images` textareas into PHP arrays before passing the data to the `ProductModel`. The model will then `json_encode` these arrays for database storage.
3.  **Compatibility:** The review confirmed that `index.php` and `js/main.js` are compatible with the refactored `AccountController.php`. No changes were required in these files for compatibility. Other provided files also appear compatible with the core changes reviewed.

With these changes, the checkout page should now correctly initialize the Stripe payment element, and the admin product form should correctly save multi-line benefits and gallery image URLs.

---
https://drive.google.com/file/d/1-XsCVZvn_FnuskMCB6B3EKF-YeoHfSCk/view?usp=sharing, https://drive.google.com/file/d/1-veczOxcIVt_UQFcULc6CZDqi0V0FvVO/view?usp=sharing, https://drive.google.com/file/d/16PPgcHjy9mgyBskvYP-SM8oCbK3sXgvj/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221DXwONLaqLDjtjfpVviWMd16WJIwzQfHZ%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1NTRDJ8_F35729Yvl2R7q6XjaumF9i6Iw/view?usp=sharing, https://drive.google.com/file/d/1Pd5OOSf8SCg_cPE67Tz8xjXkKLykqSMh/view?usp=sharing, https://drive.google.com/file/d/1Q5w3nvMJDGheGH5_Qrx_aNaFYpy5Un3L/view?usp=sharing, https://drive.google.com/file/d/1WGNs5seLo-xdjnFJszZ26rXGqcX1oJrS/view?usp=sharing, https://drive.google.com/file/d/1Y7cAz1fxJ9mCx9Ig5fWiakYEUP1C1JRf/view?usp=sharing, https://drive.google.com/file/d/1_m3UDRAzNIl-MfnQTMDAPBdQUe_ijDjL/view?usp=sharing, https://drive.google.com/file/d/1eKoTrzvaJ9r-0MCc7U-vtHc0bfXsWN31/view?usp=sharing, https://drive.google.com/file/d/1hxjY9-V16Zck8gvXJvXB6PcZ6_FGVt18/view?usp=sharing, https://drive.google.com/file/d/1kVGAHA68ZVWror4StJZHlrrDRA4el3rQ/view?usp=sharing, https://drive.google.com/file/d/1ks1FuLib8ZSzIH6GL3ws1KrZY7ml-h-A/view?usp=sharing, https://drive.google.com/file/d/1rWoVtxTkv-ZBFb6owiM3SpdL4bfr9y78/view?usp=sharing, https://drive.google.com/file/d/1sBpM-Z9DrN-PrNbH1TeHkMR9AQr_Y8O3/view?usp=sharing, https://drive.google.com/file/d/1uEOt5TkJhIsM4kSL8wMXWMhjVDsoqj_M/view?usp=sharing
