Okay, I have carefully reviewed the provided code files, including `QuizController.php-orig.txt`, `index.php`, `main.js`, the other PHP files, logs, and HTML output.

**Compatibility Check:**

*   As concluded previously, `index.php` (routing) and `main.js` (AJAX handlers for login/register) are compatible with the refactored `AccountController.php`. The control flow, CSRF handling, and data expectations align correctly.

**Issue Analysis & Fixes:**

1.  **Issue 1: Product Filter Error (`/index.php?page=products&category=1`)**
    *   **Cause:** Confirmed from logs (`SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters`). This happens because `ProductModel::getFiltered()` and `ProductModel::getCount()` were trying to use both positional (`?`) and named (`:limit`, `:offset`) placeholders in the same query execution.
    *   **Fix:** Modify `ProductModel` methods (`getFiltered`, `getCount`) to consistently use *named placeholders* for all parameters. Update `ProductController::showProductList` to pass an associative array of named parameters to these model methods.

2.  **Issue 2: Empty Quiz Results (`/index.php?page=quiz&action=results`)**
    *   **Cause:** The originally provided `QuizController.php` (in `content_of_code_files_4.md`) had flawed logic in `showResults`, potentially recalculating recommendations. The `QuizController.php-orig.txt` correctly uses the session (`$_SESSION['quiz_recommendations']`) to store the *exact* recommendations generated during submission and retrieve them for the results page. This avoids issues with `RAND()` ordering. The previous response's fix in `models/Quiz.php` (selecting the correct `recommendations` column) is still necessary.
    *   **Fix:** Use the session-based logic from `QuizController.php-orig.txt` for `processQuiz` and `showResults`. Ensure the `recommendations` column is selected correctly in `QuizModel` (this fix was applied in the previous response's generated `models/Quiz.php`). Ensure `showResults` correctly passes data to the view.

**Updated Files:**

Here are the complete, updated versions of the files requiring changes:

```php
<?php
// models/Product.php (Updated: Consistent Named Placeholders)

class Product {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        // Added category name join for potential admin list use
        $stmt = $this->pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC for consistency
    }

    public function getFeatured() {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name,
                   CASE
                       WHEN p.highlight_text IS NOT NULL THEN p.highlight_text
                       WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                       WHEN DATEDIFF(NOW(), p.created_at) <= 30 THEN 'New'
                       ELSE NULL
                   END as display_badge
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT 6
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        if ($product) {
            // Use null coalescing for safety
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        return $product ?: null; // Return null if not found
    }

    public function getByCategory($categoryId) {
        $stmt = $this->pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY p.id DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    /**
     * Creates a new product with extended fields.
     *
     * @param array $data Associative array of product data. Expected keys:
     *              name, description, short_description, price, category_id, image_url,
     *              sku, stock_quantity, initial_stock, low_stock_threshold,
     *              backorder_allowed, featured, created_by
     * @return int|false The ID of the newly created product or false on failure.
     */
    public function create($data) {
        // --- START: Updated create method ---
        $sql = "
            INSERT INTO products (
                name, description, short_description, price, category_id, image, sku,
                stock_quantity, initial_stock, low_stock_threshold, backorder_allowed,
                is_featured, created_at, updated_at, created_by, updated_by,
                size, scent_profile, origin, benefits, ingredients, usage_instructions, gallery_images
            ) VALUES (
                :name, :description, :short_description, :price, :category_id, :image_url, :sku,
                :stock_quantity, :initial_stock, :low_stock_threshold, :backorder_allowed,
                :featured, NOW(), NOW(), :created_by, :updated_by,
                :size, :scent_profile, :origin, :benefits, :ingredients, :usage_instructions, :gallery_images
            )
        ";
        $stmt = $this->pdo->prepare($sql);

        // Prepare data for binding, ensuring defaults and correct types
        $params = [
            ':name' => $data['name'] ?? null,
            ':description' => $data['description'] ?? null,
            ':short_description' => $data['short_description'] ?? null,
            ':price' => isset($data['price']) ? (float)$data['price'] : null,
            ':category_id' => isset($data['category_id']) ? (int)$data['category_id'] : null,
            ':image_url' => $data['image_url'] ?? '/images/placeholder.jpg', // Default image
            ':sku' => $data['sku'] ?? null,
            ':stock_quantity' => isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0,
            ':initial_stock' => isset($data['initial_stock']) ? (int)$data['initial_stock'] : ($data['stock_quantity'] ?? 0), // Default initial to current if not provided
            ':low_stock_threshold' => isset($data['low_stock_threshold']) ? (int)$data['low_stock_threshold'] : 5,
            ':backorder_allowed' => isset($data['backorder_allowed']) ? (int)(bool)$data['backorder_allowed'] : 0,
            ':featured' => isset($data['featured']) ? (int)(bool)$data['featured'] : 0,
            ':created_by' => $data['created_by'] ?? null, // User ID of creator
            ':updated_by' => $data['created_by'] ?? null, // Initially same as creator
            // Additional fields
            ':size' => $data['size'] ?? null,
            ':scent_profile' => $data['scent_profile'] ?? null,
            ':origin' => $data['origin'] ?? null,
             // Handle JSON fields - Assume input is array or string, store as JSON
             ':benefits' => isset($data['benefits']) ? json_encode($data['benefits']) : null,
             ':ingredients' => $data['ingredients'] ?? null,
             ':usage_instructions' => $data['usage_instructions'] ?? null,
             ':gallery_images' => isset($data['gallery_images']) ? json_encode($data['gallery_images']) : null,
        ];

        // Handle potential NULL values correctly for foreign keys etc.
        if ($params[':category_id'] === 0) $params[':category_id'] = null;
        if ($params[':created_by'] === 0) $params[':created_by'] = null;
        if ($params[':updated_by'] === 0) $params[':updated_by'] = null;

        $success = $stmt->execute($params);

        return $success ? (int)$this->pdo->lastInsertId() : false;
        // --- END: Updated create method ---
    }

    /**
     * Updates an existing product with extended fields.
     *
     * @param int $id Product ID to update.
     * @param array $data Associative array of product data to update. Expected keys match create().
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        // --- START: Updated update method ---
        // Build SET clause dynamically based on provided data
        $setClauses = [];
        $params = [':id' => $id];

        // Map input data keys to database columns and prepare SET clauses/params
        $fieldMap = [
            'name' => 'name', 'description' => 'description', 'short_description' => 'short_description',
            'price' => 'price', 'category_id' => 'category_id', 'image_url' => 'image',
            'sku' => 'sku', 'stock_quantity' => 'stock_quantity', 'initial_stock' => 'initial_stock',
            'low_stock_threshold' => 'low_stock_threshold', 'backorder_allowed' => 'backorder_allowed',
            'featured' => 'is_featured', 'size' => 'size', 'scent_profile' => 'scent_profile',
            'origin' => 'origin', 'benefits' => 'benefits', 'ingredients' => 'ingredients',
            'usage_instructions' => 'usage_instructions', 'gallery_images' => 'gallery_images',
            'updated_by' => 'updated_by' // Add updated_by
        ];

        foreach ($fieldMap as $dataKey => $dbColumn) {
            if (isset($data[$dataKey])) {
                $setClauses[] = "`{$dbColumn}` = :{$dataKey}"; // Use backticks for column names
                $value = $data[$dataKey];
                // Handle specific types
                if (in_array($dbColumn, ['category_id', 'stock_quantity', 'initial_stock', 'low_stock_threshold', 'updated_by'])) {
                    $value = ($value === '' || $value === null) ? null : (int)$value; // Allow null or cast to int
                } elseif (in_array($dbColumn, ['backorder_allowed', 'is_featured'])) {
                    $value = (int)(bool)$value; // Cast boolean to int
                } elseif ($dbColumn === 'price') {
                    $value = ($value === '' || $value === null) ? null : (float)$value; // Allow null or cast to float
                } elseif (in_array($dbColumn, ['benefits', 'gallery_images'])) {
                     // Assume $value is already an array or string meant to be JSON encoded
                     $value = json_encode($value);
                 }
                $params[":{$dataKey}"] = $value;
            }
        }

        // Add updated_at timestamp
        $setClauses[] = "updated_at = NOW()";

        if (empty($setClauses)) {
            return true; // No fields to update
        }

        $sql = "UPDATE products SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        // Handle potential NULL values for foreign keys
        if (isset($params[':category_id']) && $params[':category_id'] === 0) $params[':category_id'] = null;
        if (isset($params[':updated_by']) && $params[':updated_by'] === 0) $params[':updated_by'] = null;


        return $stmt->execute($params);
        // --- END: Updated update method ---
    }


    public function delete($id) {
        // Add related data deletion if necessary (e.g., product attributes, inventory movements?)
        // Consider soft delete (setting is_active=0) instead of hard delete.
        // For now, keeping hard delete as per original structure.
        try {
            $this->pdo->beginTransaction();
            // Delete related attributes first (if any)
            $stmtAttr = $this->pdo->prepare("DELETE FROM product_attributes WHERE product_id = ?");
            $stmtAttr->execute([$id]);
            // Delete inventory movements (optional - might want to keep for history)
            // $stmtInv = $this->pdo->prepare("DELETE FROM inventory_movements WHERE product_id = ?");
            // $stmtInv->execute([$id]);
            // Delete from cart items (important)
            $stmtCart = $this->pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
            $stmtCart->execute([$id]);
            // Delete from order items (set product_id to NULL? or prevent deletion if ordered?)
            // For now, we'll prevent deletion if it exists in order_items
            $stmtCheckOrder = $this->pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $stmtCheckOrder->execute([$id]);
            if ($stmtCheckOrder->fetchColumn() > 0) {
                throw new Exception("Cannot delete product: It exists in past orders.");
            }

            // Finally, delete the product
            $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $deleted = $stmt->execute([$id]);
            $this->pdo->commit();
            return $deleted;
        } catch (Exception $e) {
             $this->pdo->rollBack();
             error_log("Product delete error (ID: {$id}): " . $e->getMessage());
             // Re-throw the exception to be caught by the controller
             throw $e;
        }
    }

    // --- SEARCH/FILTER ---
    public function search($query, $limit = 10) { // Added limit parameter
        $stmt = $this->pdo->prepare("
            SELECT id, name, image, price FROM products
            WHERE name LIKE ? OR description LIKE ? OR sku LIKE ?
            ORDER BY name ASC
            LIMIT ?
        ");
        $searchTerm = "%{$query}%";
        $stmt->bindValue(1, $searchTerm);
        $stmt->bindValue(2, $searchTerm);
        $stmt->bindValue(3, $searchTerm);
        $stmt->bindValue(4, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getAllCategories() {
        $stmt = $this->pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    /**
     * Fetches filtered and sorted products with pagination. Uses NAMED placeholders.
     *
     * @param array $conditions Array of SQL condition strings (e.g., "p.category_id = :category_id").
     * @param array $params Associative array of parameters to bind (e.g., [':category_id' => 1, ':limit' => 10]).
     * @param string $sortBy Sorting criteria.
     * @param int $limit Number of items per page.
     * @param int $offset Offset for pagination.
     * @return array List of products.
     */
    public function getFiltered(array $conditions = [], array $params = [], string $sortBy = 'name_asc', int $limit = 12, int $offset = 0): array {
        // --- START: FIX 1 - Use NAMED Placeholders ---
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Sorting
        switch ($sortBy) {
            case 'price_asc': $sql .= " ORDER BY p.price ASC, p.name ASC"; break;
            case 'price_desc': $sql .= " ORDER BY p.price DESC, p.name ASC"; break;
            case 'name_desc': $sql .= " ORDER BY p.name DESC"; break;
            case 'created_at_desc': $sql .= " ORDER BY p.created_at DESC"; break;
            case 'name_asc': default: $sql .= " ORDER BY p.name ASC"; break;
        }

        $sql .= " LIMIT :limit OFFSET :offset"; // Use named placeholders for limit/offset

        $stmt = $this->pdo->prepare($sql);

        // Add limit and offset to params array
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;

        // Bind all parameters using the associative array keys
        foreach ($params as $key => $value) {
            // Determine type (simplified: use INT for limit/offset, default for others)
            $type = PDO::PARAM_STR; // Default to string
            if ($key === ':limit' || $key === ':offset' || $key === ':category_id') { // Use PARAM_INT for specific keys
                 $type = PDO::PARAM_INT;
            } elseif (is_int($value)) {
                 $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $type = PDO::PARAM_NULL;
            }
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute(); // Execute without passing params array here, as they are bound

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        foreach ($products as &$product) {
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        unset($product); // Unset reference
        return $products;
        // --- END: FIX 1 ---
    }

    public function getPriceRange() {
        $stmt = $this->pdo->query("
            SELECT MIN(price) as min_price, MAX(price) as max_price
            FROM products
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getProductsByIds($ids) {
        if (empty($ids) || !is_array($ids)) { // Added check for array
            return [];
        }
        // Ensure all IDs are integers
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) { return $id > 0; }); // Remove non-positive IDs
        if (empty($ids)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        // Prepare the ORDER BY FIELD part separately
        $orderByField = "FIELD(p.id, $placeholders)"; // Use alias p.id

        $sql = "SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id IN ($placeholders)
                ORDER BY $orderByField";

        $stmt = $this->pdo->prepare($sql);

        // Double the IDs array for parameters
        $params = array_merge($ids, $ids);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }


    public function searchWithFilters($query, $categoryId = null, $minPrice = null, $maxPrice = null) {
        // --- START: FIX 1 - Use NAMED Placeholders ---
        $conditions = ["(p.name LIKE :query_name OR p.description LIKE :query_desc OR p.sku LIKE :query_sku)"]; // Added SKU search, table alias, named placeholders
        $params = [
            ':query_name' => "%{$query}%",
            ':query_desc' => "%{$query}%",
            ':query_sku' => "%{$query}%"
        ];
        if ($categoryId) {
            $conditions[] = "p.category_id = :category_id"; // Added table alias, named placeholder
            $params[':category_id'] = $categoryId;
        }
        if ($minPrice !== null) {
            $conditions[] = "p.price >= :min_price"; // Added table alias, named placeholder
            $params[':min_price'] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions[] = "p.price <= :max_price"; // Added table alias, named placeholder
            $params[':max_price'] = $maxPrice;
        }
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(" AND ", $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params); // Execute with named parameters
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
        // --- END: FIX 1 ---
    }

    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        // This seems redundant with getRelated, preferring getRelated
        return $this->getRelated($categoryId, $productId, $limit);
    }

    public function getRelated($categoryId, $excludeId, $limit = 4) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT ?"
        );
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields for related products
        foreach ($products as &$product) {
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        unset($product); // Unset reference
        return $products;
    }

    public function updateStock($id, $quantity) {
        // Note: This is a simple +/- adjustment. Use InventoryController for audited movements.
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity + ?
            WHERE id = ?
        ");
        return $stmt->execute([$quantity, $id]);
    }

    public function checkStock($id) {
        $stmt = $this->pdo->prepare("
            SELECT stock_quantity, backorder_allowed, low_stock_threshold
            FROM products
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function isInStock($id, $requestedQuantity = 1) {
        $stock = $this->checkStock($id);
        if (!$stock) {
            return false; // Product doesn't exist
        }
        // Allow purchase if backorders are allowed OR if stock is sufficient
        return !empty($stock['backorder_allowed']) || $stock['stock_quantity'] >= $requestedQuantity;
    }

    public function getLowStockProducts($threshold = 5) { // Default threshold if needed
        // Use COALESCE to handle null low_stock_threshold, defaulting comparison to the $threshold param
        $sql = "
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock_quantity <= COALESCE(p.low_stock_threshold, :threshold)
            ORDER BY p.stock_quantity ASC
        "; // Use named placeholder
        $stmt = $this->pdo->prepare($sql);
        // Bind the threshold value passed to the function
        $stmt->bindValue(':threshold', (int)$threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function updateStockSettings($id, $threshold, $backorderAllowed) {
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET low_stock_threshold = ?,
                backorder_allowed = ?
            WHERE id = ?
        ");
        // Ensure boolean is correctly cast to int (0 or 1)
        return $stmt->execute([(int)$threshold, (int)(bool)$backorderAllowed, (int)$id]);
    }

    /**
     * Gets the total count of products matching the filter conditions. Uses NAMED placeholders.
     *
     * @param array $conditions Array of SQL condition strings (e.g., "p.category_id = :category_id").
     * @param array $params Associative array of parameters to bind (e.g., [':category_id' => 1]).
     * @return int Total count.
     */
    public function getCount(array $conditions = [], array $params = []): int {
        // --- START: FIX 1 - Use NAMED Placeholders ---
        $sql = "SELECT COUNT(p.id) as count FROM products p";
        // Determine if category join is needed based on conditions
        $needsCategoryJoin = false;
        foreach($conditions as $cond) {
            if (strpos($cond, 'c.') !== false || strpos($cond, 'category_id') !== false) { // Check for alias or column name
                $needsCategoryJoin = true;
                break;
            }
        }
        if ($needsCategoryJoin) {
            $sql .= " LEFT JOIN categories c ON p.category_id = c.id";
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions); // Directly use conditions
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params); // Execute with named parameters
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
        return $row ? (int)$row['count'] : 0;
        // --- END: FIX 1 ---
    }

} // End Product Class
```

```php
<?php
// controllers/ProductController.php (Updated: Pass Named Parameters to Model)

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
         // --- START: NEW METHOD TO HANDLE POST FOR CREATE/EDIT ---
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
                 // JSON fields need special handling if not simple textareas
                 // 'benefits' => ...,
                 // 'gallery_images' => ...,
             ];

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
          // --- END: NEW METHOD TO HANDLE POST FOR CREATE/EDIT ---
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

---

**Conclusion:**

The provided code updates address the two specific issues reported:

1.  **Product Filter Error:** Resolved by switching to named placeholders in `ProductModel` and updating `ProductController` to pass parameters accordingly.
2.  **Empty Quiz Results:** Resolved by reverting `QuizController`'s `processQuiz` and `showResults` methods to use the session-based logic from the original file (`QuizController.php-orig.txt`) provided in the second prompt, ensuring the exact recommendations generated are displayed. The necessary fix in `models/Quiz.php` (selecting the correct `recommendations` column) was already included in the previously generated version and is assumed to be in place.

The compatibility between `AccountController.php`, `index.php`, and `main.js` remains sound. The other reviewed files appear compatible with these core components and the applied fixes. Remember to apply the mandatory database patch (`db/the_scent_update_users_table.sql`) if you haven't already.

---
Okay, I have performed a meticulous line-by-line comparison of the `diff` outputs for `controllers/QuizController.php` and `controllers/ProductController.php` against their original versions provided.

**Review Findings:**

1.  **`controllers/QuizController.php` Diff Analysis:**
    *   The `diff` shows the removal of the unused `handleQuizSubmission` and `handleQuiz` methods, which simplifies the controller as `processQuiz` is the method used by the router (`index.php`).
    *   The core logic for `showQuiz` and `processQuiz` has been restored to use the session (`$_SESSION['quiz_recommendations']`) for passing results, matching the logic in `QuizController.php-orig.txt`. This is the intended fix for Issue 2.
    *   Type hints were added for parameters and properties (`Quiz`, `Product`, `PDO`).
    *   The `ProductModel` instance (`$this->productModel`) was correctly added and initialized in the constructor, necessary for fetching product details in `showUserQuizHistory` and potentially in the fallback logic for `showResults` (though the primary path now uses the session).
    *   Error handling in `showQuiz` was simplified (removed AJAX check for GET request error).
    *   Validation in `processQuiz` was slightly improved to check for valid mood values.
    *   An audit log call was added in `processQuiz`.
    *   The `showResults` method now correctly uses `renderView` and passes the necessary data (`products`, `csrfToken`, etc.).
    *   The helper/unused methods `getAnalytics`, `getPersonalizedRecommendations`, `getQuizHistory` were removed as they are not directly invoked by the router and their logic is now incorporated into the main controller actions (`showAnalytics`, `showUserQuizHistory`).
    *   **Conclusion:** The changes accurately reflect the intended fix for Issue 2 (using session for quiz results) and include minor improvements like type hinting and better validation, while removing unused code. **No original functionality related to the primary quiz flow appears lost.**

2.  **`controllers/ProductController.php` Diff Analysis:**
    *   The primary change is within the `showProductList` method. The logic for building the `$params` array was modified to create an *associative array* with *named placeholders* (e.g., `':category_id' => $categoryId`) instead of a simple indexed array with positional placeholders (`?`). This directly addresses the `SQLSTATE[HY093]` error reported (Issue 1).
    *   Type hints were added for properties (`Product`, `int`, `array`).
    *   The calls to `$this->logSecurityEvent` in the `catch` blocks had their parameter order corrected (passing the details array as the second argument, `$context` as the third).
    *   Placeholder comments (`// ... (code remains the same...`) were removed.
    *   **Conclusion:** The changes directly target and fix the SQL parameter binding error (Issue 1) by switching to named parameters for filtering. The parameter order correction for logging is also applied. **All other methods and functionality remain identical to the original file.**

**Overall Confirmation:**

The diff analysis confirms that the updated versions of `controllers/QuizController.php` and `controllers/ProductController.php` successfully incorporate the fixes for the reported issues while preserving the original functionality of the methods relevant to the user flows. The changes are targeted and consistent with the identified problems and solutions.

---
Okay, I have meticulously reviewed the `diff` output comparing `models/Product.php-orig` and `models/Product.php-new`.

**Line-by-Line Diff Review Summary:**

1.  **Header Comment:** A comment indicating the file update reason (Consistent Named Placeholders) was added. This is acceptable documentation.
2.  **`getFiltered` Method:**
    *   The docblock was updated to reflect `$params` being an associative array. **Correct**.
    *   The core SQL query structure (SELECT, FROM, JOIN) remains unchanged. **Correct**.
    *   The construction of the `WHERE` clause using `$conditions` passed from the controller is unchanged. **Correct**.
    *   The `ORDER BY` logic remains unchanged. **Correct**.
    *   The `LIMIT` and `OFFSET` clause was correctly changed to use named placeholders (`:limit`, `:offset`). **Correct**.
    *   The `$params` array is now explicitly treated as associative, and `:limit` and `:offset` are added to it. **Correct**.
    *   A `foreach` loop was added to iterate through the associative `$params` array and bind each named parameter individually using `$stmt->bindValue()`. The type inference logic within the loop appears reasonable. **Correct**.
    *   The `$stmt->execute()` call was correctly modified to *not* pass the `$params` array, as parameters are now bound individually. **Correct**.
    *   The post-fetch JSON decoding logic remains unchanged. **Correct**.
    *   Start/End FIX comments were added for clarity. Acceptable.
3.  **`getProductsByIds` Method:**
    *   The `ORDER BY FIELD(id, ...)` clause was updated to `ORDER BY FIELD(p.id, ...)` to correctly use the table alias `p`. **Correct**.
    *   A `LEFT JOIN` with the `categories` table was added to fetch `category_name`. This is an acceptable enhancement for consistency, assuming the view might use it, and doesn't break existing functionality. **Acceptable Enhancement**.
4.  **`searchWithFilters` Method:**
    *   The SQL conditions were correctly updated to use named placeholders (`:query_name`, `:category_id`, etc.). **Correct**.
    *   The `$params` array is now correctly built as an associative array. **Correct**.
    *   The `$stmt->execute($params)` call correctly passes the associative array for execution with named placeholders. **Correct**.
5.  **`getLowStockProducts` Method:**
    *   The SQL `WHERE` clause was correctly updated to use a named placeholder (`:threshold`). **Correct**.
    *   The `$stmt->bindValue(':threshold', ...)` call correctly binds the named placeholder. **Correct**.
6.  **`getCount` Method:**
    *   The docblock was updated to reflect associative `$params`. **Correct**.
    *   The SQL query construction remains the same. **Correct**.
    *   The `$stmt->execute($params)` call correctly passes the associative array for execution with named placeholders. **Correct**.
7.  **Other Methods:** All other methods (`getAll`, `getFeatured`, `getById`, `getByCategory`, `create`, `update`, `delete`, `search`, `getAllCategories`, `getPriceRange`, `getRelated`, `updateStock`, `checkStock`, `isInStock`, `updateStockSettings`) appear **unchanged** in terms of their core logic and functionality based on the provided diff.

**Conclusion:**

The review confirms that the changes made to `models/Product.php` successfully address the `SQLSTATE[HY093]` error by consistently implementing **named placeholders** in the methods that previously mixed placeholder types (`getFiltered`, `getCount`, `searchWithFilters`, `getLowStockProducts`). The parameter binding logic was updated accordingly. The minor enhancement in `getProductsByIds` (adding category join and alias) is functionally correct. **No original features or functions appear to have been lost or negatively impacted by these specific changes.** The file is ready for use.

---
https://drive.google.com/file/d/12RwQ2-fl-jGekxCw1GIaP0fVyIRqL0aT/view?usp=sharing, https://drive.google.com/file/d/15dniiCzQ8TwDd8d0PGfOA_hvF1CYilK7/view?usp=sharing, https://drive.google.com/file/d/17SjET2jWhYUuAfDG4mBBD0NfJ_rOTw15/view?usp=sharing, https://drive.google.com/file/d/18eDCMIfoA9rraVkv_FpmkP2apWbmT6AP/view?usp=sharing, https://drive.google.com/file/d/191gRD6MOMJfMv8lCbVsJ-xpJiBGKfMPF/view?usp=sharing, https://drive.google.com/file/d/1GNpG9mgBIemisQ12H_3po8McVjwQjGBx/view?usp=sharing, https://drive.google.com/file/d/1Mtepal_GgNTlQSY-zYnDEGNBD6e0fXoX/view?usp=sharing, https://drive.google.com/file/d/1cfk5Wj1sC0HxUMLzDExKfKmO9q4884bM/view?usp=sharing, https://drive.google.com/file/d/1d11Ip08ScYnd9mGisaELgq01N3Fs3Pou/view?usp=sharing, https://drive.google.com/file/d/1gm4SE9PDjYEswjvg6OrKnAqPzAFxD2pD/view?usp=sharing, https://drive.google.com/file/d/1jaTMga12DwAewlCLJZ3xrCVUKNvPN7l0/view?usp=sharing, https://drive.google.com/file/d/1mOgo3R6eiz7HBRPJCBTwwf1PoAEoNTTe/view?usp=sharing, https://drive.google.com/file/d/1qhcUpiC0L1W9mbbVCC5RhpGX4FY25KEA/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221rKmt8U0lqA-V1QOi8rnaq1BfD9YvjCRO%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1tQt5eFEXxWEUaHK8OyFDbG7zeEUtTf3C/view?usp=sharing, https://drive.google.com/file/d/1wpcAecbmcf7VFTo_pa03sCQ91FNRPovQ/view?usp=sharing, https://drive.google.com/file/d/1zSjCh4tIUxoEaLsGHg8ePfhdhAVUVSRw/view?usp=sharing

---
Okay, I have thoroughly reviewed the provided code files, logs, and HTML outputs. Based on the analysis, here are the findings and the necessary code updates to address the reported issues and ensure compatibility.

**Analysis Summary:**

1.  **Compatibility Check (AccountController, index.php, main.js):**
    *   `index.php`: The routing logic appears compatible with the refactored `AccountController`. It correctly handles GET requests for showing forms/pages and POST requests (via AJAX or standard) for processing actions like login, register, and profile updates. The global CSRF validation for POST requests is appropriate.
    *   `main.js`: The AJAX handlers for login (`initLoginPage`) and register (`initRegisterPage`) correctly target the appropriate routes (`index.php?page=login`, `index.php?page=register`) and expect JSON responses, which `AccountController` provides. The handling of CSRF tokens for AJAX seems correct (reading from `#csrf-token-value`). Password validation logic in JS aligns with typical requirements. The newsletter preference checkbox handling in `initRegisterPage` is correct.
    *   `AccountController.php`: The refactored controller handles different actions based on the request method (GET vs. POST) or specific AJAX calls, which aligns with the routing and JS logic. Rate limiting has been correctly applied to sensitive actions.
    *   **Conclusion:** The core interaction between `index.php`, `main.js`, and `AccountController.php` appears compatible and functional based on the provided code.

2.  **Issue 1: Product Filter Error (`/index.php?page=products&category=1`)**
    *   **Cause:** The Apache error log clearly shows `SQLSTATE[HY093]: Invalid parameter number: mixed named and positional parameters`. This occurs in `ProductModel.php` within the `getFiltered` method because it attempts to use both positional placeholders (`?` for filters) and named placeholders (`:limit`, `:offset` for pagination) in the same prepared statement execution. While `bindValue` *can* sometimes allow this, it's prone to driver inconsistencies and errors. The fix is to use *only one type* of placeholder binding within a single `execute` call. We will switch the filter parameters to use named placeholders as well for consistency.
    *   **Files to Update:** `models/Product.php` and `controllers/ProductController.php`.

3.  **Issue 2: Empty Quiz Results (`/index.php?page=quiz&action=results`)**
    *   **Cause:** The `QuizController::showResults` method recalculates recommendations using `QuizModel::getRecommendations($answers)` *after* the user has already submitted the quiz and potentially received *different* recommendations (due to `RAND()` ordering) which were saved. The results page should display the products corresponding to the recommendations *actually saved* during the quiz submission. Additionally, `QuizModel::getResultsByUserId` was selecting the wrong column (`recommended_products` instead of `recommendations`).
    *   **Files to Update:** `controllers/QuizController.php` and `models/Quiz.php`.

4.  **Other File Compatibility:**
    *   `header.php`: Compatible. Cart count logic relies on `$_SESSION['cart_count']` which `CartController` correctly maintains.
    *   Other Controllers (`Cart`, `Checkout`, `Payment`, `Coupon`, etc.): Appear compatible with the core structure and `BaseController`.
    *   Other Models (`User`, `Order`, `Cart`, etc.): Appear compatible. `User.php` requires the DB patch.
    *   Views: Compatible, except `quiz_results.php` which will now receive correctly fetched products. `account/*.php` views are compatible after previous UI fixes.
    *   Core Includes (`ErrorHandler`, `SecurityMiddleware`, `auth`, `db`): Appear stable and compatible.

**Plan Execution:**

1.  Update `models/Product.php` to use named placeholders for filters in `getFiltered` and `getCount`.
2.  Update `controllers/ProductController.php` to build an associative array for parameters passed to the model.
3.  Update `models/Quiz.php` to select the correct `recommendations` column in `getResultsByUserId`.
4.  Update `controllers/QuizController.php`'s `showResults` method to fetch products based on the *saved* recommendation IDs.

---

**Updated Code Files:**

Here are the complete updated versions of the files needing modification:

```php
<?php
// models/Product.php (Updated: Consistent Named Placeholders)

class Product {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        // Added category name join for potential admin list use
        $stmt = $this->pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC for consistency
    }

    public function getFeatured() {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name,
                   CASE
                       WHEN p.highlight_text IS NOT NULL THEN p.highlight_text
                       WHEN p.stock_quantity <= p.low_stock_threshold THEN 'Low Stock'
                       WHEN DATEDIFF(NOW(), p.created_at) <= 30 THEN 'New'
                       ELSE NULL
                   END as display_badge
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT 6
        ");

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        if ($product) {
            // Use null coalescing for safety
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        return $product ?: null; // Return null if not found
    }

    public function getByCategory($categoryId) {
        $stmt = $this->pdo->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? ORDER BY p.id DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    /**
     * Creates a new product with extended fields.
     *
     * @param array $data Associative array of product data. Expected keys:
     *              name, description, short_description, price, category_id, image_url,
     *              sku, stock_quantity, initial_stock, low_stock_threshold,
     *              backorder_allowed, featured, created_by
     * @return int|false The ID of the newly created product or false on failure.
     */
    public function create($data) {
        // --- START: Updated create method ---
        $sql = "
            INSERT INTO products (
                name, description, short_description, price, category_id, image, sku,
                stock_quantity, initial_stock, low_stock_threshold, backorder_allowed,
                is_featured, created_at, updated_at, created_by, updated_by,
                size, scent_profile, origin, benefits, ingredients, usage_instructions, gallery_images
            ) VALUES (
                :name, :description, :short_description, :price, :category_id, :image_url, :sku,
                :stock_quantity, :initial_stock, :low_stock_threshold, :backorder_allowed,
                :featured, NOW(), NOW(), :created_by, :updated_by,
                :size, :scent_profile, :origin, :benefits, :ingredients, :usage_instructions, :gallery_images
            )
        ";
        $stmt = $this->pdo->prepare($sql);

        // Prepare data for binding, ensuring defaults and correct types
        $params = [
            ':name' => $data['name'] ?? null,
            ':description' => $data['description'] ?? null,
            ':short_description' => $data['short_description'] ?? null,
            ':price' => isset($data['price']) ? (float)$data['price'] : null,
            ':category_id' => isset($data['category_id']) ? (int)$data['category_id'] : null,
            ':image_url' => $data['image_url'] ?? '/images/placeholder.jpg', // Default image
            ':sku' => $data['sku'] ?? null,
            ':stock_quantity' => isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0,
            ':initial_stock' => isset($data['initial_stock']) ? (int)$data['initial_stock'] : ($data['stock_quantity'] ?? 0), // Default initial to current if not provided
            ':low_stock_threshold' => isset($data['low_stock_threshold']) ? (int)$data['low_stock_threshold'] : 5,
            ':backorder_allowed' => isset($data['backorder_allowed']) ? (int)(bool)$data['backorder_allowed'] : 0,
            ':featured' => isset($data['featured']) ? (int)(bool)$data['featured'] : 0,
            ':created_by' => $data['created_by'] ?? null, // User ID of creator
            ':updated_by' => $data['created_by'] ?? null, // Initially same as creator
            // Additional fields
            ':size' => $data['size'] ?? null,
            ':scent_profile' => $data['scent_profile'] ?? null,
            ':origin' => $data['origin'] ?? null,
             // Handle JSON fields - Assume input is array or string, store as JSON
             ':benefits' => isset($data['benefits']) ? json_encode($data['benefits']) : null,
             ':ingredients' => $data['ingredients'] ?? null,
             ':usage_instructions' => $data['usage_instructions'] ?? null,
             ':gallery_images' => isset($data['gallery_images']) ? json_encode($data['gallery_images']) : null,
        ];

        // Handle potential NULL values correctly for foreign keys etc.
        if ($params[':category_id'] === 0) $params[':category_id'] = null;
        if ($params[':created_by'] === 0) $params[':created_by'] = null;
        if ($params[':updated_by'] === 0) $params[':updated_by'] = null;

        $success = $stmt->execute($params);

        return $success ? (int)$this->pdo->lastInsertId() : false;
        // --- END: Updated create method ---
    }

    /**
     * Updates an existing product with extended fields.
     *
     * @param int $id Product ID to update.
     * @param array $data Associative array of product data to update. Expected keys match create().
     * @return bool True on success, false on failure.
     */
    public function update($id, $data) {
        // --- START: Updated update method ---
        // Build SET clause dynamically based on provided data
        $setClauses = [];
        $params = [':id' => $id];

        // Map input data keys to database columns and prepare SET clauses/params
        $fieldMap = [
            'name' => 'name', 'description' => 'description', 'short_description' => 'short_description',
            'price' => 'price', 'category_id' => 'category_id', 'image_url' => 'image',
            'sku' => 'sku', 'stock_quantity' => 'stock_quantity', 'initial_stock' => 'initial_stock',
            'low_stock_threshold' => 'low_stock_threshold', 'backorder_allowed' => 'backorder_allowed',
            'featured' => 'is_featured', 'size' => 'size', 'scent_profile' => 'scent_profile',
            'origin' => 'origin', 'benefits' => 'benefits', 'ingredients' => 'ingredients',
            'usage_instructions' => 'usage_instructions', 'gallery_images' => 'gallery_images',
            'updated_by' => 'updated_by' // Add updated_by
        ];

        foreach ($fieldMap as $dataKey => $dbColumn) {
            if (isset($data[$dataKey])) {
                $setClauses[] = "`{$dbColumn}` = :{$dataKey}"; // Use backticks for column names
                $value = $data[$dataKey];
                // Handle specific types
                if (in_array($dbColumn, ['category_id', 'stock_quantity', 'initial_stock', 'low_stock_threshold', 'updated_by'])) {
                    $value = ($value === '' || $value === null) ? null : (int)$value; // Allow null or cast to int
                } elseif (in_array($dbColumn, ['backorder_allowed', 'is_featured'])) {
                    $value = (int)(bool)$value; // Cast boolean to int
                } elseif ($dbColumn === 'price') {
                    $value = ($value === '' || $value === null) ? null : (float)$value; // Allow null or cast to float
                } elseif (in_array($dbColumn, ['benefits', 'gallery_images'])) {
                     // Assume $value is already an array or string meant to be JSON encoded
                     $value = json_encode($value);
                 }
                $params[":{$dataKey}"] = $value;
            }
        }

        // Add updated_at timestamp
        $setClauses[] = "updated_at = NOW()";

        if (empty($setClauses)) {
            return true; // No fields to update
        }

        $sql = "UPDATE products SET " . implode(', ', $setClauses) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        // Handle potential NULL values for foreign keys
        if (isset($params[':category_id']) && $params[':category_id'] === 0) $params[':category_id'] = null;
        if (isset($params[':updated_by']) && $params[':updated_by'] === 0) $params[':updated_by'] = null;


        return $stmt->execute($params);
        // --- END: Updated update method ---
    }


    public function delete($id) {
        // Add related data deletion if necessary (e.g., product attributes, inventory movements?)
        // Consider soft delete (setting is_active=0) instead of hard delete.
        // For now, keeping hard delete as per original structure.
        try {
            $this->pdo->beginTransaction();
            // Delete related attributes first (if any)
            $stmtAttr = $this->pdo->prepare("DELETE FROM product_attributes WHERE product_id = ?");
            $stmtAttr->execute([$id]);
            // Delete inventory movements (optional - might want to keep for history)
            // $stmtInv = $this->pdo->prepare("DELETE FROM inventory_movements WHERE product_id = ?");
            // $stmtInv->execute([$id]);
            // Delete from cart items (important)
            $stmtCart = $this->pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
            $stmtCart->execute([$id]);
            // Delete from order items (set product_id to NULL? or prevent deletion if ordered?)
            // For now, we'll prevent deletion if it exists in order_items
            $stmtCheckOrder = $this->pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
            $stmtCheckOrder->execute([$id]);
            if ($stmtCheckOrder->fetchColumn() > 0) {
                throw new Exception("Cannot delete product: It exists in past orders.");
            }

            // Finally, delete the product
            $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
            $deleted = $stmt->execute([$id]);
            $this->pdo->commit();
            return $deleted;
        } catch (Exception $e) {
             $this->pdo->rollBack();
             error_log("Product delete error (ID: {$id}): " . $e->getMessage());
             // Re-throw the exception to be caught by the controller
             throw $e;
        }
    }

    // --- SEARCH/FILTER ---
    public function search($query, $limit = 10) { // Added limit parameter
        $stmt = $this->pdo->prepare("
            SELECT id, name, image, price FROM products
            WHERE name LIKE ? OR description LIKE ? OR sku LIKE ?
            ORDER BY name ASC
            LIMIT ?
        ");
        $searchTerm = "%{$query}%";
        $stmt->bindValue(1, $searchTerm);
        $stmt->bindValue(2, $searchTerm);
        $stmt->bindValue(3, $searchTerm);
        $stmt->bindValue(4, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getAllCategories() {
        $stmt = $this->pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    /**
     * Fetches filtered and sorted products with pagination. Uses NAMED placeholders.
     *
     * @param array $conditions Array of SQL condition strings (e.g., "p.category_id = :category_id").
     * @param array $params Associative array of parameters to bind (e.g., [':category_id' => 1, ':limit' => 10]).
     * @param string $sortBy Sorting criteria.
     * @param int $limit Number of items per page.
     * @param int $offset Offset for pagination.
     * @return array List of products.
     */
    public function getFiltered(array $conditions = [], array $params = [], string $sortBy = 'name_asc', int $limit = 12, int $offset = 0): array {
        // --- START: FIX 1 - Use NAMED Placeholders ---
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        // Sorting
        switch ($sortBy) {
            case 'price_asc': $sql .= " ORDER BY p.price ASC, p.name ASC"; break;
            case 'price_desc': $sql .= " ORDER BY p.price DESC, p.name ASC"; break;
            case 'name_desc': $sql .= " ORDER BY p.name DESC"; break;
            case 'created_at_desc': $sql .= " ORDER BY p.created_at DESC"; break;
            case 'name_asc': default: $sql .= " ORDER BY p.name ASC"; break;
        }

        $sql .= " LIMIT :limit OFFSET :offset"; // Use named placeholders for limit/offset

        $stmt = $this->pdo->prepare($sql);

        // Add limit and offset to params array
        $params[':limit'] = (int)$limit;
        $params[':offset'] = (int)$offset;

        // Bind all parameters using the associative array keys
        foreach ($params as $key => $value) {
            // Determine type (simplified: use INT for limit/offset, default for others)
            $type = (in_array($key, [':limit', ':offset'])) ? PDO::PARAM_INT : PDO::PARAM_STR;
            if (is_int($value)) $type = PDO::PARAM_INT; // Check if value itself is integer
            if (is_bool($value)) $type = PDO::PARAM_BOOL;
            if (is_null($value)) $type = PDO::PARAM_NULL;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute(); // Execute without passing params array here, as they are bound

        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        foreach ($products as &$product) {
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        unset($product); // Unset reference
        return $products;
        // --- END: FIX 1 ---
    }

    public function getPriceRange() {
        $stmt = $this->pdo->query("
            SELECT MIN(price) as min_price, MAX(price) as max_price
            FROM products
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function getProductsByIds($ids) {
        if (empty($ids) || !is_array($ids)) { // Added check for array
            return [];
        }
        // Ensure all IDs are integers
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids, function($id) { return $id > 0; }); // Remove non-positive IDs
        if (empty($ids)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        // Prepare the ORDER BY FIELD part separately
        $orderByField = "FIELD(id, $placeholders)";

        $sql = "SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id IN ($placeholders)
                ORDER BY $orderByField";

        $stmt = $this->pdo->prepare($sql);

        // Double the IDs array for parameters
        $params = array_merge($ids, $ids);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }


    public function searchWithFilters($query, $categoryId = null, $minPrice = null, $maxPrice = null) {
        // --- START: FIX 1 - Use NAMED Placeholders ---
        $conditions = ["(p.name LIKE :query_name OR p.description LIKE :query_desc OR p.sku LIKE :query_sku)"]; // Added SKU search, table alias, named placeholders
        $params = [
            ':query_name' => "%{$query}%",
            ':query_desc' => "%{$query}%",
            ':query_sku' => "%{$query}%"
        ];
        if ($categoryId) {
            $conditions[] = "p.category_id = :category_id"; // Added table alias, named placeholder
            $params[':category_id'] = $categoryId;
        }
        if ($minPrice !== null) {
            $conditions[] = "p.price >= :min_price"; // Added table alias, named placeholder
            $params[':min_price'] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions[] = "p.price <= :max_price"; // Added table alias, named placeholder
            $params[':max_price'] = $maxPrice;
        }
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(" AND ", $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params); // Execute with named parameters
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
        // --- END: FIX 1 ---
    }

    public function getRelatedProducts($productId, $categoryId, $limit = 4) {
        // This seems redundant with getRelated, preferring getRelated
        return $this->getRelated($categoryId, $productId, $limit);
    }

    public function getRelated($categoryId, $excludeId, $limit = 4) {
        $stmt = $this->pdo->prepare(
            "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? ORDER BY RAND() LIMIT ?"
        );
        $stmt->bindValue(1, $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(2, $excludeId, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields for related products
        foreach ($products as &$product) {
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        unset($product); // Unset reference
        return $products;
    }

    public function updateStock($id, $quantity) {
        // Note: This is a simple +/- adjustment. Use InventoryController for audited movements.
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity + ?
            WHERE id = ?
        ");
        return $stmt->execute([$quantity, $id]);
    }

    public function checkStock($id) {
        $stmt = $this->pdo->prepare("
            SELECT stock_quantity, backorder_allowed, low_stock_threshold
            FROM products
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function isInStock($id, $requestedQuantity = 1) {
        $stock = $this->checkStock($id);
        if (!$stock) {
            return false; // Product doesn't exist
        }
        // Allow purchase if backorders are allowed OR if stock is sufficient
        return !empty($stock['backorder_allowed']) || $stock['stock_quantity'] >= $requestedQuantity;
    }

    public function getLowStockProducts($threshold = 5) { // Default threshold if needed
        // Use COALESCE to handle null low_stock_threshold, defaulting comparison to the $threshold param
        $sql = "
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.stock_quantity <= COALESCE(p.low_stock_threshold, :threshold)
            ORDER BY p.stock_quantity ASC
        "; // Use named placeholder
        $stmt = $this->pdo->prepare($sql);
        // Bind the threshold value passed to the function
        $stmt->bindValue(':threshold', (int)$threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function updateStockSettings($id, $threshold, $backorderAllowed) {
        $stmt = $this->pdo->prepare("
            UPDATE products
            SET low_stock_threshold = ?,
                backorder_allowed = ?
            WHERE id = ?
        ");
        // Ensure boolean is correctly cast to int (0 or 1)
        return $stmt->execute([(int)$threshold, (int)(bool)$backorderAllowed, (int)$id]);
    }

    /**
     * Gets the total count of products matching the filter conditions. Uses NAMED placeholders.
     *
     * @param array $conditions Array of SQL condition strings (e.g., "p.category_id = :category_id").
     * @param array $params Associative array of parameters to bind (e.g., [':category_id' => 1]).
     * @return int Total count.
     */
    public function getCount(array $conditions = [], array $params = []): int {
        // --- START: FIX 1 - Use NAMED Placeholders ---
        $sql = "SELECT COUNT(p.id) as count FROM products p";
        // Determine if category join is needed based on conditions
        $needsCategoryJoin = false;
        foreach($conditions as $cond) {
            if (strpos($cond, 'c.') !== false || strpos($cond, 'category_id') !== false) { // Check for alias or column name
                $needsCategoryJoin = true;
                break;
            }
        }
        if ($needsCategoryJoin) {
            $sql .= " LEFT JOIN categories c ON p.category_id = c.id";
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions); // Directly use conditions
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params); // Execute with named parameters
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
        return $row ? (int)$row['count'] : 0;
        // --- END: FIX 1 ---
    }

} // End Product Class
```

```php
<?php
// controllers/ProductController.php (Updated: Pass Named Parameters to Model)

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
            // --- FIX: Changed second argument from null to [] ---
            $this->logSecurityEvent('error_show_home', [], ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
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
            // --- FIX: Changed second argument from null to [] ---
            $this->logSecurityEvent('error_show_product_list', [], ['error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
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
            // --- FIX: Changed second argument from null to [] ---
            $this->logSecurityEvent('error_show_product_detail', [], ['product_id' => $id ?? null, 'error' => $e->getMessage(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN']);
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
         // --- START: NEW METHOD TO HANDLE POST FOR CREATE/EDIT ---
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
                 // JSON fields need special handling if not simple textareas
                 // 'benefits' => ...,
                 // 'gallery_images' => ...,
             ];

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
          // --- END: NEW METHOD TO HANDLE POST FOR CREATE/EDIT ---
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
            // --- START: FIX 2 - Select 'recommendations' column ---
            $stmt = $this->pdo->prepare("
                SELECT id, user_id, email, answers, recommendations, created_at
                FROM quiz_results
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            // --- END: FIX 2 ---
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

```php
<?php
// controllers/QuizController.php (Updated: showResults logic)

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
        // Use BaseController method to get CSRF token
        $csrfToken = $this->getCsrfToken();
        $questions = $this->quizModel->getQuestions(); // Fetch questions

        // Use BaseController render helper
        $data = [
            'pageTitle' => 'Scent Finder Quiz',
            'csrfToken' => $csrfToken,
            'questions' => $questions, // Pass questions to the view
            'bodyClass' => 'page-quiz' // For JS initializer
        ];
        echo $this->renderView('quiz', $data);
    }

    /**
     * Processes quiz submission, saves results, and redirects.
     */
    public function processQuiz() {
        try {
            $this->validateCSRF(); // Validate CSRF token

            // Validate answers
            $answers = [];
            // Example for single question 'mood'
            $mood = $this->validateInput($_POST['mood'] ?? null, 'string');
            if (!$mood || !in_array($mood, ['relaxation', 'energy', 'focus', 'balance'])) {
                throw new Exception('Invalid mood selection.');
            }
            $answers['mood'] = $mood;

            // Get user ID or null for guests
            $userId = $this->getUserId();
            $userEmail = null; // Email not collected in this quiz version

            // Get recommendations
            $recommendedProducts = $this->quizModel->getRecommendations($answers);
            // Extract only product IDs for saving
            $recommendationIds = array_column($recommendedProducts, 'id');
            $recommendationIds = array_filter($recommendationIds, 'is_numeric'); // Ensure numeric IDs

            // Save result (including recommendation IDs)
            $saveSuccess = $this->quizModel->saveQuizResult(
                 $userId,
                 $userEmail,
                 $answers,
                 $recommendationIds
            );

            if (!$saveSuccess) {
                 error_log("Failed to save quiz result for user " . ($userId ?? 'guest'));
                 // Proceed to redirect anyway, but maybe log an error
            } else {
                 $this->logAuditTrail('quiz_completed', $userId, ['answers' => $answers, 'recommendations_count' => count($recommendationIds)]);
            }

            // --- START: FIX 2 - Store results in session for immediate display ---
            // Store the fetched product details in session to show on the results page immediately after redirect
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['quiz_results_products'] = $recommendedProducts; // Store the full product data
            // --- END: FIX 2 ---

            // Redirect to results page
            $this->redirect('index.php?page=quiz&action=results'); // Use BaseController helper

        } catch (Exception $e) {
            error_log("Quiz processing error: " . $e->getMessage());
            $this->setFlashMessage('Error processing quiz: ' . $e->getMessage(), 'error');
             // Redirect back to quiz on error
             $this->redirect('index.php?page=quiz');
        }
    }


    /**
      * Displays the quiz results page, showing products based on the LATEST saved recommendations.
      */
     public function showResults() {
         // --- START: FIX 2 - Retrieve results from session first, then fallback ---
         if (session_status() === PHP_SESSION_NONE) session_start();

         $products = [];
         if (isset($_SESSION['quiz_results_products'])) {
             // Use results stored right after submission
             $products = $_SESSION['quiz_results_products'];
             unset($_SESSION['quiz_results_products']); // Clear session after displaying once
         } else {
             // Fallback: Get latest result from DB and fetch products (if user revisits page)
             $userId = $this->getUserId();
             if ($userId) {
                 $latestResult = $this->quizModel->getResultsByUserId($userId)[0] ?? null; // Get the most recent one
             } else {
                 $latestResult = null; // Cannot get latest for guest if session is lost
             }

             if ($latestResult && !empty($latestResult['recommendations'])) {
                 $recommendationIds = json_decode($latestResult['recommendations'], true);
                 if (is_array($recommendationIds) && !empty($recommendationIds)) {
                     // Ensure IDs are numeric
                     $recommendationIds = array_filter($recommendationIds, 'is_numeric');
                     if (!empty($recommendationIds)) {
                         // Fetch products using the saved IDs
                         $products = $this->productModel->getProductsByIds($recommendationIds);
                     }
                 }
             }

             // If still no products after fallback, $products remains empty, view will handle it.
             if (empty($products)) {
                 error_log("Quiz results page loaded, but no recommendations found in session or DB for user " . ($userId ?? 'guest'));
                 // Optionally, fetch generic featured products as a final fallback
                 // $products = $this->productModel->getFeatured(3);
             }
         }
         // --- END: FIX 2 ---

         $csrfToken = $this->getCsrfToken();
         $data = [
             'pageTitle' => 'Your Scent Recommendations',
             'products' => $products, // Pass the correctly fetched products
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

        // --- START: Fetch data using detailed method ---
        // Assuming getDetailedAnalytics returns the nested structure needed by JS
        $analyticsData = $this->quizModel->getDetailedAnalytics($days);
        // --- END: Fetch data ---

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

} // End QuizController class
```

---

**Summary of Changes:**

1.  **`models/Product.php`**:
    *   Modified `getFiltered()` and `getCount()` to use named placeholders (`:placeholder`) instead of positional placeholders (`?`) for filter parameters to resolve the `SQLSTATE[HY093]` error.
    *   Updated internal parameter binding logic to work with named placeholders.
    *   Modified `getProductsByIds()` to handle the `ORDER BY FIELD()` clause parameter binding correctly.
2.  **`controllers/ProductController.php`**:
    *   Updated `showProductList()` to build the `$params` array as an associative array with named keys (e.g., `:category_id`, `:min_price`) to match the changes in the model.
3.  **`models/Quiz.php`**:
    *   Corrected the SQL query in `getResultsByUserId()` to select the `recommendations` column instead of the non-existent `recommended_products`.
4.  **`controllers/QuizController.php`**:
    *   Reworked `showResults()`:
        *   It now first attempts to retrieve full product details stored in the session immediately after the quiz submission redirect (`$_SESSION['quiz_results_products']`).
        *   If the session data isn't available (e.g., user revisits the page), it falls back to fetching the *latest* quiz result from the database.
        *   It decodes the `recommendations` (JSON array of IDs) from the fetched result.
        *   It uses `ProductModel::getProductsByIds()` to fetch the *specific* product details corresponding to the *saved* IDs.
        *   It passes *these* products to the `quiz_results.php` view.

These changes should resolve the product filtering error (Issue 1) and the empty quiz results page issue (Issue 2) by ensuring the correct data fetching and parameter binding techniques are used. Compatibility with `AccountController`, `index.php`, and `main.js` is maintained.

---
https://drive.google.com/file/d/12RwQ2-fl-jGekxCw1GIaP0fVyIRqL0aT/view?usp=sharing, https://drive.google.com/file/d/15dniiCzQ8TwDd8d0PGfOA_hvF1CYilK7/view?usp=sharing, https://drive.google.com/file/d/17SjET2jWhYUuAfDG4mBBD0NfJ_rOTw15/view?usp=sharing, https://drive.google.com/file/d/18eDCMIfoA9rraVkv_FpmkP2apWbmT6AP/view?usp=sharing, https://drive.google.com/file/d/191gRD6MOMJfMv8lCbVsJ-xpJiBGKfMPF/view?usp=sharing, https://drive.google.com/file/d/1GNpG9mgBIemisQ12H_3po8McVjwQjGBx/view?usp=sharing, https://drive.google.com/file/d/1Mtepal_GgNTlQSY-zYnDEGNBD6e0fXoX/view?usp=sharing, https://drive.google.com/file/d/1cfk5Wj1sC0HxUMLzDExKfKmO9q4884bM/view?usp=sharing, https://drive.google.com/file/d/1d11Ip08ScYnd9mGisaELgq01N3Fs3Pou/view?usp=sharing, https://drive.google.com/file/d/1gm4SE9PDjYEswjvg6OrKnAqPzAFxD2pD/view?usp=sharing, https://drive.google.com/file/d/1jaTMga12DwAewlCLJZ3xrCVUKNvPN7l0/view?usp=sharing, https://drive.google.com/file/d/1mOgo3R6eiz7HBRPJCBTwwf1PoAEoNTTe/view?usp=sharing, https://drive.google.com/file/d/1qhcUpiC0L1W9mbbVCC5RhpGX4FY25KEA/view?usp=sharing, https://aistudio.google.com/app/prompts?state=%7B%22ids%22:%5B%221rKmt8U0lqA-V1QOi8rnaq1BfD9YvjCRO%22%5D,%22action%22:%22open%22,%22userId%22:%22103961307342447084491%22,%22resourceKeys%22:%7B%7D%7D&usp=sharing, https://drive.google.com/file/d/1tQt5eFEXxWEUaHK8OyFDbG7zeEUtTf3C/view?usp=sharing, https://drive.google.com/file/d/1wpcAecbmcf7VFTo_pa03sCQ91FNRPovQ/view?usp=sharing, https://drive.google.com/file/d/1zSjCh4tIUxoEaLsGHg8ePfhdhAVUVSRw/view?usp=sharing
