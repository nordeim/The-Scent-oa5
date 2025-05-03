<?php
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

    public function getFiltered($conditions = [], $params = [], $sortBy = 'name_asc', $limit = 12, $offset = 0) {
        // --- Corrected version from content_of_code_files_2 ---
        $fixedConditions = array_map(function($cond) {
            // Ensure word boundaries for simple replacements, handle table aliases
            $cond = preg_replace('/\bname\b/', 'p.name', $cond);
            $cond = preg_replace('/\bdescription\b/', 'p.description', $cond);
            $cond = preg_replace('/\bprice\b/', 'p.price', $cond);
            $cond = preg_replace('/\bcategory_id\b/', 'p.category_id', $cond);
            return $cond;
        }, $conditions);
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        if (!empty($fixedConditions)) {
            $sql .= " WHERE " . implode(" AND ", $fixedConditions);
        }
        // Sorting
        switch ($sortBy) {
            case 'price_asc': $sql .= " ORDER BY p.price ASC, p.name ASC"; break; // Added secondary sort
            case 'price_desc': $sql .= " ORDER BY p.price DESC, p.name ASC"; break; // Added secondary sort
            case 'name_desc': $sql .= " ORDER BY p.name DESC"; break;
            case 'created_at_desc': $sql .= " ORDER BY p.created_at DESC"; break; // Added created_at sort
            case 'name_asc': default: $sql .= " ORDER BY p.name ASC"; break;
        }
        $sql .= " LIMIT :limit OFFSET :offset"; // Use named placeholders
        $stmt = $this->pdo->prepare($sql);

        // Bind WHERE clause parameters
        $paramIndex = 1;
        foreach ($params as $value) {
            $stmt->bindValue($paramIndex++, $value); // Use positional binding for WHERE params
        }
        // Bind LIMIT/OFFSET parameters by name
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode JSON fields if present
        foreach ($products as &$product) {
            $product['benefits'] = isset($product['benefits']) ? (json_decode($product['benefits'], true) ?? []) : [];
            $product['gallery_images'] = isset($product['gallery_images']) ? (json_decode($product['gallery_images'], true) ?? []) : [];
        }
        unset($product); // Unset reference
        return $products;
    }

    // --- Unchanged methods below (getStock, isInStock, etc.) ---
    // ... (rest of the methods from the provided content_of_code_files_2.md remain here) ...
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
        $stmt = $this->pdo->prepare("
            SELECT * FROM products
            WHERE id IN ($placeholders)
            ORDER BY FIELD(id, $placeholders)
        ");

        // Double the IDs array since we need it twice in the query
        $params = array_merge($ids, $ids);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
    }

    public function searchWithFilters($query, $categoryId = null, $minPrice = null, $maxPrice = null) {
        $conditions = ["(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)"]; // Added SKU search, table alias
        $params = ["%$query%", "%$query%", "%$query%"];
        if ($categoryId) {
            $conditions[] = "p.category_id = ?"; // Added table alias
            $params[] = $categoryId;
        }
        if ($minPrice !== null) {
            $conditions[] = "p.price >= ?"; // Added table alias
            $params[] = $minPrice;
        }
        if ($maxPrice !== null) {
            $conditions[] = "p.price <= ?"; // Added table alias
            $params[] = $maxPrice;
        }
        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE " . implode(" AND ", $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
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
            WHERE p.stock_quantity <= COALESCE(p.low_stock_threshold, ?)
            ORDER BY p.stock_quantity ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        // Bind the threshold value passed to the function
        $stmt->bindValue(1, (int)$threshold, PDO::PARAM_INT);
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

    public function getCount($conditions = [], $params = []) {
        // --- Corrected version from content_of_code_files_2 ---
        $fixedConditions = array_map(function($cond) {
            // Ensure word boundaries for simple replacements, handle table aliases
            $cond = preg_replace('/\\bname\\b/', 'p.name', $cond);
            $cond = preg_replace('/\\bdescription\\b/', 'p.description', $cond);
            $cond = preg_replace('/\\bprice\\b/', 'p.price', $cond);
            $cond = preg_replace('/\\bcategory_id\\b/', 'p.category_id', $cond);
            return $cond;
        }, $conditions);
        $needsCategoryJoin = false;
        foreach($fixedConditions as $cond) {
            if (strpos($cond, 'c.') !== false) {
                $needsCategoryJoin = true;
                break;
            }
        }
        $sql = "SELECT COUNT(p.id) as count FROM products p";
        // Join categories only if category conditions exist
        if ($needsCategoryJoin || array_key_exists('category_id', $params) || in_array('p.category_id = ?', $fixedConditions)) {
            $sql .= " LEFT JOIN categories c ON p.category_id = c.id";
        }
        if (!empty($fixedConditions)) {
            $sql .= " WHERE " . implode(" AND ", $fixedConditions);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC); // Added FETCH_ASSOC
        return $row ? (int)$row['count'] : 0;
    }

} // End Product Class
