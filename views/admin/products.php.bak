<?php
// File: views/admin/products.php

// Assuming admin_header includes necessary styles (Tailwind) & JS (FontAwesome)
// Also assuming $csrfToken and $products are passed from the controller
require_once __DIR__ . '/../layout/admin_header.php'; // Use appropriate admin header path
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Manage Products</h1>
        <a href="index.php?page=admin&section=products&task=create" class="btn btn-primary">
            <i class="fas fa-plus mr-2"></i> Create New Product
        </a>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded <?= ($_SESSION['flash_type'] ?? 'info') === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Featured</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= $product['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-10 w-10 rounded-md object-cover">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($product['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$<?= number_format($product['price'] ?? 0, 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center <?= (isset($product['stock_quantity']) && $product['stock_quantity'] <= ($product['low_stock_threshold'] ?? 5)) ? 'text-red-600 font-semibold' : 'text-gray-500' ?>">
                                    <?= $product['stock_quantity'] ?? 'N/A' ?>
                                    <?php if (!empty($product['backorder_allowed'])): ?>
                                        <span class="text-xs text-blue-500">(BO)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <?php if (!empty($product['is_featured'])): ?>
                                        <span class="text-green-500"><i class="fas fa-check-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-gray-400"><i class="fas fa-minus-circle"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <a href="index.php?page=admin&section=products&task=edit&id=<?= $product['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <!-- Delete Form -->
                                    <form action="index.php?page=admin&section=products&task=delete&id=<?= $product['id'] ?>" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete product \'<?= htmlspecialchars(addslashes($product['name']), ENT_QUOTES) ?>\'? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>"> <!-- Optional: ID also in URL -->
                                        <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Optional: Add Pagination Controls Here if implementing list pagination -->
    </div>
</div>

<?php
require_once __DIR__ . '/../layout/admin_footer.php'; // Use appropriate admin footer path
?>
