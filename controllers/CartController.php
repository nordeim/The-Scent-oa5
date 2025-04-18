<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Product.php';

class CartController extends BaseController {
    private $productModel;
    
    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->productModel = new Product($pdo);
        $this->initCart();
    }
    
    private function initCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
    
    public function showCart() {
        $cartItems = [];
        $total = 0;
        
        // Get full product details for cart items
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = $this->productModel->getById($productId);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product['price'] * $quantity
                ];
                $total += $product['price'] * $quantity;
            }
        }
        
        require_once __DIR__ . '/../views/cart.php';
    }
    
    public function addToCart() {
        $this->validateAjax();
        $this->validateCSRF();
        
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        $quantity = (int)$this->validateInput($_POST['quantity'] ?? 1, 'int');
        
        if (!$productId || $quantity < 1) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid product or quantity'], 400);
        }
        
        // Check stock availability
        if (!$this->productModel->isInStock($productId, $quantity)) {
            $this->jsonResponse(['success' => false, 'message' => 'Insufficient stock'], 400);
        }
        
        // Add or update quantity
        if (isset($_SESSION['cart'][$productId])) {
            $newQuantity = $_SESSION['cart'][$productId] + $quantity;
            // Recheck stock for total quantity
            if (!$this->productModel->isInStock($productId, $newQuantity)) {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'Insufficient stock for requested quantity'
                ], 400);
            }
            $_SESSION['cart'][$productId] = $newQuantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Product added to cart',
            'cartCount' => array_sum($_SESSION['cart'])
        ]);
    }
    
    public function updateCart() {
        $this->validateAjax();
        $this->validateCSRF();
        
        $updates = $_POST['updates'] ?? [];
        $stockErrors = [];
        
        foreach ($updates as $productId => $quantity) {
            $productId = $this->validateInput($productId, 'int');
            $quantity = (int)$this->validateInput($quantity, 'int');
            
            if ($quantity > 0) {
                // Validate stock
                if (!$this->productModel->isInStock($productId, $quantity)) {
                    $product = $this->productModel->getById($productId);
                    $stockErrors[] = "{$product['name']} has insufficient stock";
                    continue;
                }
                $_SESSION['cart'][$productId] = $quantity;
            } else {
                unset($_SESSION['cart'][$productId]);
            }
        }
        
        $this->jsonResponse([
            'success' => empty($stockErrors),
            'message' => empty($stockErrors) ? 'Cart updated' : 'Some items have insufficient stock',
            'cartCount' => array_sum($_SESSION['cart']),
            'errors' => $stockErrors
        ]);
    }
    
    public function removeFromCart() {
        $this->validateAjax();
        $this->validateCSRF();
        
        $productId = $this->validateInput($_POST['product_id'] ?? null, 'int');
        
        if (!$productId || !isset($_SESSION['cart'][$productId])) {
            $this->jsonResponse(['success' => false, 'message' => 'Product not found in cart'], 404);
        }
        
        unset($_SESSION['cart'][$productId]);
        
        $this->jsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cartCount' => array_sum($_SESSION['cart'])
        ]);
    }
    
    public function clearCart() {
        $_SESSION['cart'] = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateAjax();
            $this->validateCSRF();
            $this->jsonResponse([
                'success' => true,
                'message' => 'Cart cleared',
                'cartCount' => 0
            ]);
        } else {
            $this->redirect('cart');
        }
    }
    
    public function validateCartStock() {
        $errors = [];
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            if (!$this->productModel->isInStock($productId, $quantity)) {
                $product = $this->productModel->getById($productId);
                $errors[] = "{$product['name']} has insufficient stock";
            }
        }
        
        return $errors;
    }
    
    public function getCartItems() {
        $this->initCart();
        $cartItems = [];
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = $this->productModel->getById($productId);
            if ($product) {
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $product['price'] * $quantity
                ];
            }
        }
        
        return $cartItems;
    }
}