<?php
// File: views/admin/product_form.php

// Assuming admin_header includes necessary styles & JS
require_once __DIR__ . '/../layout/admin_header.php'; // Use appropriate admin header path

$isEditMode = isset($product) && !empty($product['id']);
$formAction = $isEditMode
    ? 'index.php?page=admin&section=products&task=save&id=' . $product['id'] // ID in URL for clarity, though hidden input is primary
    : 'index.php?page=admin&section=products&task=save';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">
        <?= $isEditMode ? 'Edit Product: ' . htmlspecialchars($product['name']) : 'Create New Product' ?>
    </h1>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded <?= ($_SESSION['flash_type'] ?? 'info') === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <form action="<?= $formAction ?>" method="POST" class="bg-white shadow-md rounded-lg p-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Left Column -->
            <div>
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input type="text" id="name" name="name" required maxlength="150"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['name'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" id="sku" name="sku" maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['sku'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select id="category_id" name="category_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories ?? [] as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= (isset($product['category_id']) && $product['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                 <div class="mb-4">
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                    <input type="number" id="price" name="price" required step="0.01" min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['price'] ?? '') ?>">
                </div>

                 <div class="mb-4">
                    <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                    <input type="text" id="image_url" name="image_url"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>">
                     <p class="text-xs text-gray-500 mt-1">Enter full URL or path relative to web root (e.g., /images/products/...).</p>
                </div>

                 <div class="mb-4">
                    <label for="size" class="block text-sm font-medium text-gray-700 mb-1">Size (e.g., 10ml, 100g)</label>
                    <input type="text" id="size" name="size" maxlength="50"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['size'] ?? '') ?>">
                </div>

                 <div class="mb-4">
                    <label for="origin" class="block text-sm font-medium text-gray-700 mb-1">Origin</label>
                    <input type="text" id="origin" name="origin" maxlength="100"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['origin'] ?? '') ?>">
                </div>

            </div>

            <!-- Right Column -->
            <div>
                <div class="mb-4">
                    <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" required min="0" step="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['stock_quantity'] ?? '0') ?>">
                </div>

                <div class="mb-4">
                    <label for="initial_stock" class="block text-sm font-medium text-gray-700 mb-1">Initial Stock (Optional)</label>
                    <input type="number" id="initial_stock" name="initial_stock" min="0" step="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['initial_stock'] ?? '') ?>" placeholder="Defaults to Stock Qty if empty on create">
                    <p class="text-xs text-gray-500 mt-1">Used for stock percentage calculation. Defaults to current stock on creation if left empty.</p>
                </div>

                <div class="mb-4">
                    <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold</label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" required min="0" step="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                           value="<?= htmlspecialchars($product['low_stock_threshold'] ?? '5') ?>">
                </div>

                <div class="mb-4">
                     <label for="scent_profile" class="block text-sm font-medium text-gray-700 mb-1">Scent Profile</label>
                     <input type="text" id="scent_profile" name="scent_profile" maxlength="255"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                            value="<?= htmlspecialchars($product['scent_profile'] ?? '') ?>">
                 </div>


                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Options</label>
                    <div class="flex items-center space-x-4">
                         <label class="inline-flex items-center">
                            <input type="checkbox" name="backorder_allowed" value="1"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                   <?= !empty($product['backorder_allowed']) ? 'checked' : '' ?>>
                            <span class="ml-2 text-sm text-gray-600">Allow Backorders</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="is_featured" value="1"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                   <?= !empty($product['is_featured']) ? 'checked' : '' ?>>
                            <span class="ml-2 text-sm text-gray-600">Featured Product</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Full Width Fields -->
        <div class="mb-4">
            <label for="short_description" class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
            <textarea id="short_description" name="short_description" rows="3" maxlength="500"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="A brief summary for product listings..."><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
            <textarea id="description" name="description" rows="6"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="Detailed product description..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
        </div>

         <div class="mb-4">
             <label for="ingredients" class="block text-sm font-medium text-gray-700 mb-1">Ingredients</label>
             <textarea id="ingredients" name="ingredients" rows="3"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="List key ingredients..."><?= htmlspecialchars($product['ingredients'] ?? '') ?></textarea>
         </div>

         <div class="mb-4">
             <label for="usage_instructions" class="block text-sm font-medium text-gray-700 mb-1">Usage Instructions</label>
             <textarea id="usage_instructions" name="usage_instructions" rows="4"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="How to use the product..."><?= htmlspecialchars($product['usage_instructions'] ?? '') ?></textarea>
         </div>

          <!-- Simple Textareas for JSON fields - requires manual JSON/comma separation for now -->
         <div class="mb-4">
            <label for="benefits" class="block text-sm font-medium text-gray-700 mb-1">Benefits (JSON or Comma-separated)</label>
            <textarea id="benefits" name="benefits" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder='e.g., ["Calming","Stress Relief"] or Calming, Stress Relief'><?= htmlspecialchars(isset($product['benefits']) ? (is_array($product['benefits']) ? implode(', ', $product['benefits']) : $product['benefits']) : '') ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Enter as a valid JSON array or comma-separated list.</p>
        </div>
         <div class="mb-4">
            <label for="gallery_images" class="block text-sm font-medium text-gray-700 mb-1">Gallery Image URLs (JSON or Comma-separated)</label>
            <textarea id="gallery_images" name="gallery_images" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder='e.g., ["/images/prod_1a.jpg","/images/prod_1b.jpg"] or /images/prod_1a.jpg, /images/prod_1b.jpg'><?= htmlspecialchars(isset($product['gallery_images']) ? (is_array($product['gallery_images']) ? implode(', ', $product['gallery_images']) : $product['gallery_images']) : '') ?></textarea>
            <p class="text-xs text-gray-500 mt-1">Enter as a valid JSON array or comma-separated list of image paths/URLs.</p>
        </div>


        <!-- Form Actions -->
        <div class="flex justify-end space-x-4 mt-8">
            <a href="index.php?page=admin&section=products" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <?= $isEditMode ? 'Update Product' : 'Create Product' ?>
            </button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../layout/admin_footer.php'; // Use appropriate admin footer path
?>
