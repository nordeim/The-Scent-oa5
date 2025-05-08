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
