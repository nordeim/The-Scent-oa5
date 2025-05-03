<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController extends BaseController {
    private Product $productModel; // Use type hint
    private int $itemsPerPage = 12; // Use type hint
    private array $cache = []; // Use type hint

    public function __construct(PDO $pdo) { // Use type hint
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
    }

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
            $this->logSecurityEvent('error_show_home', null, ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
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
            $this->logSecurityEvent('error_show_product_list', null, ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
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
            $this->logSecurityEvent('error_show_product_detail', null, ['product_id' => $id ?? null, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
            error_log("Error loading product details for ID {$id}: " . $e->getMessage());
            $this->setFlashMessage('Error loading product details. Please try again.', 'error');
            $this->redirect('index.php?page=products'); // Redirect to product list
        }
    }

    // --- Admin Methods (No changes needed for bodyClass, assuming admin layout handles its own JS init if needed) ---
    // (Kept existing admin methods from content_of_code_files_2.md)
    public function createProduct() {
        try {
            $this->requireAdmin();
            $this->validateCSRF();

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'name' => $this->validateInput($_POST['name'], 'string'),
                    'description' => $this->validateInput($_POST['description'], 'string'),
                    'price' => $this->validateInput($_POST['price'], 'float'),
                    'category_id' => $this->validateInput($_POST['category_id'], 'int'), // Assuming category ID is passed
                    'image_url' => $this->validateInput($_POST['image_url'], 'url'),
                    'stock_quantity' => $this->validateInput($_POST['stock_quantity'] ?? 0, 'int'),
                    'low_stock_threshold' => $this->validateInput($_POST['low_stock_threshold'] ?? 5, 'int'),
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'created_by' => $this->getUserId() // Use BaseController helper
                ];

                // Validate required fields
                foreach (['name', 'price', 'category_id'] as $field) {
                    if (empty($data[$field])) {
                        throw new Exception("Missing required field: {$field}");
                    }
                }

                $this->beginTransaction();

                $productId = $this->productModel->create($data);

                if ($productId) {
                    $this->clearProductCache(); // Clear cache
                    $this->logAuditTrail('product_create', $this->getUserId(), [
                        'product_id' => $productId,
                        'name' => $data['name'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                    ]);
                    $this->commit();
                    $this->setFlashMessage('Product created successfully', 'success');
                    $this->redirect('index.php?page=admin&section=products'); // Adjusted redirect
                } else {
                    throw new Exception('Failed to create product in database.');
                }
            }

            // Display form on GET request
            $categories = $this->productModel->getAllCategories();
            $data = [
                'pageTitle' => 'Create Product',
                'categories' => $categories,
                'product' => null, // No product data for create form
                'csrfToken' => $this->getCsrfToken(),
                'bodyClass' => 'page-admin-product-form' // Add body class if admin layout uses it
            ];
            echo $this->renderView('admin/product_form', $data); // Use renderView

        } catch (Exception $e) {
            $this->rollback();
            error_log("Error creating product: " . $e->getMessage());
            $this->setFlashMessage('Failed to create product: ' . $e->getMessage(), 'error');
            $this->redirect('index.php?page=admin&section=products&task=create'); // Redirect back to create form
        }
    }

    public function updateProduct($id) {
        try {
            $this->requireAdmin();

            $id = $this->validateInput($id, 'int');
            if (!$id) {
                throw new Exception('Invalid product ID');
            }

            $product = $this->productModel->getById($id);
            if (!$product) {
                throw new Exception('Product not found');
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->validateCSRF(); // Validate CSRF only on POST

                $data = [
                    'name' => $this->validateInput($_POST['name'], 'string'),
                    'description' => $this->validateInput($_POST['description'], 'string'),
                    'price' => $this->validateInput($_POST['price'], 'float'),
                    'category_id' => $this->validateInput($_POST['category_id'], 'int'), // Assuming category ID is passed
                    'image_url' => $this->validateInput($_POST['image_url'], 'url'),
                    'stock_quantity' => $this->validateInput($_POST['stock_quantity'] ?? 0, 'int'),
                    'low_stock_threshold' => $this->validateInput($_POST['low_stock_threshold'] ?? 5, 'int'),
                    'featured' => isset($_POST['featured']) ? 1 : 0,
                    'updated_by' => $this->getUserId() // Use BaseController helper
                ];

                 // Validate required fields
                foreach (['name', 'price', 'category_id'] as $field) {
                     if (empty($data[$field])) {
                         throw new Exception("Missing required field: {$field}");
                     }
                }

                $this->beginTransaction();

                if ($this->productModel->update($id, $data)) {
                    $this->clearProductCache(); // Clear cache
                    $this->logAuditTrail('product_update', $this->getUserId(), [
                        'product_id' => $id,
                        'name' => $data['name'],
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                    ]);
                    $this->commit();
                    $this->setFlashMessage('Product updated successfully', 'success');
                    $this->redirect('index.php?page=admin&section=products'); // Adjusted redirect
                } else {
                     throw new Exception('Failed to update product in database.');
                }
            }

            // Display form on GET request
            $categories = $this->productModel->getAllCategories();
            $viewData = [
                'pageTitle' => 'Edit Product',
                'categories' => $categories,
                'product' => $product, // Pass existing product data
                'csrfToken' => $this->getCsrfToken(),
                'bodyClass' => 'page-admin-product-form' // Add body class if admin layout uses it
            ];
            echo $this->renderView('admin/product_form', $viewData); // Use renderView

        } catch (Exception $e) {
            $this->rollback();
            error_log("Error updating product ID {$id}: " . $e->getMessage());
            $this->setFlashMessage('Failed to update product: ' . $e->getMessage(), 'error');
            $this->redirect("index.php?page=admin&section=products&task=edit&id={$id}"); // Redirect back to edit form
        }
    }

    public function deleteProduct($id) {
        try {
            $this->requireAdmin();
            // Assuming delete is triggered by POST for safety
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method for delete.');
            }
            $this->validateCSRF();

            $id = $this->validateInput($id, 'int');
            if (!$id) {
                throw new Exception('Invalid product ID');
            }

            $this->beginTransaction();

            if ($this->productModel->delete($id)) {
                $this->clearProductCache(); // Clear cache
                $this->logAuditTrail('product_delete', $this->getUserId(), [
                    'product_id' => $id,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN'
                ]);
                $this->commit();
                $this->setFlashMessage('Product deleted successfully', 'success');
            } else {
                throw new Exception('Failed to delete product or product not found.');
            }

            $this->redirect('index.php?page=admin&section=products'); // Adjusted redirect

        } catch (Exception $e) {
            $this->rollback();
            error_log("Error deleting product ID {$id}: " . $e->getMessage());
            $this->setFlashMessage('Failed to delete product: ' . $e->getMessage(), 'error');
            $this->redirect('index.php?page=admin&section=products'); // Adjusted redirect
        }
    }

    private function clearProductCache() {
        $this->cache = []; // Simple cache clearing
    }

    // --- Search and Getters (No changes needed for bodyClass) ---
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
}
