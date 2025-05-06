<?php
// views/admin/product_form.php
// Form for creating or editing products. (Consolidated v16.0)

// Use the admin layout header
require_once __DIR__ . '/../layout/admin_header.php';

// Determine if we are editing or creating
$isEditMode = isset($product) && !empty($product['id']);
$formAction = $isEditMode
    ? 'index.php?page=admin&section=products&task=save&id=' . urlencode($product['id'])
    : 'index.php?page=admin&section=products&task=save';

// Helper function to pre-fill form fields, handling potential nulls gracefully
function getAdminFieldValue($product, $field, $default = '') {
    // Special handling for boolean/tinyint represented as 0/1 for value attributes
    if (in_array($field, ['stock_quantity', 'initial_stock', 'low_stock_threshold'])) {
         return isset($product[$field]) ? (int)$product[$field] : $default;
    }
    // Special handling for price to ensure correct formatting if needed (though type=number handles it)
    if ($field === 'price') {
        return isset($product[$field]) ? number_format((float)$product[$field], 2, '.', '') : $default;
    }
    // Default handling for text/other fields
    return isset($product[$field]) ? htmlspecialchars($product[$field], ENT_QUOTES, 'UTF-8') : $default;
}

// Helper for JSON fields (display as newline/comma separated)
function getJsonFieldValue($product, $field) {
    if (!isset($product[$field])) return '';
    $data = is_array($product[$field]) ? $product[$field] : json_decode($product[$field], true);
    if (is_array($data)) {
        // Filter out empty strings before joining
        $data = array_filter($data, function($value) { return trim($value) !== ''; });
        return htmlspecialchars(implode("\n", $data), ENT_QUOTES, 'UTF-8'); // Use newline for textarea display
    }
    // If it's not valid JSON or already a string, display as is
    return htmlspecialchars($product[$field], ENT_QUOTES, 'UTF-8');
}

// Helper for checkboxes
function getAdminCheckedValue($product, $field) {
    return isset($product[$field]) && $product[$field] == 1 ? 'checked' : '';
}

?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-primary mb-6">
        <?= $isEditMode ? 'Edit Product: ' . htmlspecialchars($product['name']) : 'Create New Product' ?>
    </h1>

    <?php // Display standard flash messages
    if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded <?= ($_SESSION['flash_type'] ?? 'info') == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>" role="alert">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <form action="<?= $formAction ?>" method="POST" enctype="multipart/form-data" class="bg-white shadow-md rounded-lg p-6 space-y-6">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <?php if ($isEditMode): ?>
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
        <?php endif; ?>

        <!-- Using Grid Layout for better alignment -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

            <!-- Column 1 -->
            <div class="space-y-6">
                <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Core Information</legend>
                     <div class="form-group">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name <span class="text-red-500">*</span></label>
                        <input type="text" id="name" name="name" required maxlength="150" value="<?= getAdminFieldValue($product, 'name') ?>" class="form-input w-full">
                    </div>
                     <div class="form-group mt-4">
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" required class="form-select w-full">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories ?? [] as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (isset($product['category_id']) && $product['category_id'] == $category['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mt-4">
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price ($) <span class="text-red-500">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= getAdminFieldValue($product, 'price') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4">
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input type="text" id="sku" name="sku" maxlength="100" value="<?= getAdminFieldValue($product, 'sku') ?>" class="form-input w-full">
                    </div>
                </fieldset>

                 <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Details</legend>
                    <div class="form-group">
                        <label for="size" class="block text-sm font-medium text-gray-700 mb-1">Size (e.g., 10ml, 100g)</label>
                        <input type="text" id="size" name="size" maxlength="50" value="<?= getAdminFieldValue($product, 'size') ?>" class="form-input w-full">
                    </div>
                     <div class="form-group mt-4">
                        <label for="scent_profile" class="block text-sm font-medium text-gray-700 mb-1">Scent Profile (Keywords)</label>
                        <input type="text" id="scent_profile" name="scent_profile" maxlength="255" value="<?= getAdminFieldValue($product, 'scent_profile') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4">
                        <label for="origin" class="block text-sm font-medium text-gray-700 mb-1">Origin</label>
                        <input type="text" id="origin" name="origin" maxlength="100" value="<?= getAdminFieldValue($product, 'origin') ?>" class="form-input w-full">
                    </div>
                 </fieldset>
            </div>

            <!-- Column 2 -->
            <div class="space-y-6">
                 <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Inventory</legend>
                     <div class="form-group">
                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity <span class="text-red-500">*</span></label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" step="1" required value="<?= getAdminFieldValue($product, 'stock_quantity', '0') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4">
                        <label for="initial_stock" class="block text-sm font-medium text-gray-700 mb-1">Initial Stock (Optional)</label>
                        <input type="number" id="initial_stock" name="initial_stock" min="0" step="1" value="<?= getAdminFieldValue($product, 'initial_stock') ?>" class="form-input w-full" placeholder="Defaults to Stock Qty if empty on create">
                        <p class="text-xs text-gray-500 mt-1">Used for stock %. Defaults to current stock on creation if left empty.</p>
                    </div>
                     <div class="form-group mt-4">
                        <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Threshold</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0" step="1" value="<?= getAdminFieldValue($product, 'low_stock_threshold', '5') ?>" class="form-input w-full">
                    </div>
                    <div class="form-group mt-4 flex items-center">
                        <input type="checkbox" id="backorder_allowed" name="backorder_allowed" value="1" class="form-checkbox h-5 w-5 text-primary rounded" <?= getAdminCheckedValue($product, 'backorder_allowed') ?>>
                        <label for="backorder_allowed" class="ml-2 block text-sm font-medium text-gray-700">Allow Backorders</label>
                    </div>
                </fieldset>

                <fieldset class="border p-4 rounded h-full">
                    <legend class="text-lg font-semibold text-primary px-2">Images</legend>
                    <div class="form-group">
                        <label for="image_url" class="block text-sm font-medium text-gray-700 mb-1">Main Image URL</label>
                        <input type="text" id="image_url" name="image_url" value="<?= getAdminFieldValue($product, 'image', '/images/placeholder.jpg') ?>" class="form-input w-full">
                        <p class="text-xs text-gray-500 mt-1">Enter full path (e.g., /images/products/...) or URL.</p>
                    </div>
                     <div class="form-group mt-4">
                        <label for="gallery_images" class="block text-sm font-medium text-gray-700 mb-1">Gallery Image URLs (Enter one per line)</label>
                         <textarea id="gallery_images" name="gallery_images" rows="4" class="form-textarea w-full" placeholder="/images/prod_1a.jpg&#10;/images/prod_1b.jpg"><?= getJsonFieldValue($product, 'gallery_images') ?></textarea>
                         <p class="text-xs text-gray-500 mt-1">Enter full paths or URLs. Server will convert to JSON.</p>
                     </div>
                     <div class="mt-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded">
                        <p><strong class="font-semibold">Note:</strong> File upload is not implemented. Enter URLs/paths directly.</p>
                    </div>
                </fieldset>

            </div>
        </div>

        <!-- Full Width Fields Below Grid -->
        <fieldset class="border p-4 rounded">
            <legend class="text-lg font-semibold text-primary px-2">Descriptions & Usage</legend>
            <div class="form-group">
                <label for="short_description" class="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                <textarea id="short_description" name="short_description" rows="3" maxlength="500" class="form-textarea w-full" placeholder="A brief summary for product listings..."><?= getAdminFieldValue($product, 'short_description') ?></textarea>
            </div>
            <div class="form-group mt-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Full Description</label>
                <textarea id="description" name="description" rows="6" class="form-textarea w-full" placeholder="Detailed product description..."><?= getAdminFieldValue($product, 'description') ?></textarea>
            </div>
            <div class="form-group mt-4">
                <label for="ingredients" class="block text-sm font-medium text-gray-700 mb-1">Ingredients</label>
                <textarea id="ingredients" name="ingredients" rows="3" class="form-textarea w-full" placeholder="List key ingredients..."><?= getAdminFieldValue($product, 'ingredients') ?></textarea>
            </div>
            <div class="form-group mt-4">
                <label for="usage_instructions" class="block text-sm font-medium text-gray-700 mb-1">Usage Instructions</label>
                <textarea id="usage_instructions" name="usage_instructions" rows="4" class="form-textarea w-full" placeholder="How to use the product..."><?= getAdminFieldValue($product, 'usage_instructions') ?></textarea>
            </div>
             <div class="form-group mt-4">
                <label for="benefits" class="block text-sm font-medium text-gray-700 mb-1">Benefits (Enter one per line)</label>
                 <textarea id="benefits" name="benefits" rows="4" class="form-textarea w-full" placeholder="Calming&#10;Stress Relief"><?= getJsonFieldValue($product, 'benefits') ?></textarea>
                 <p class="text-xs text-gray-500 mt-1">Enter one benefit per line. Server will convert to JSON.</p>
             </div>
        </fieldset>

        <!-- Settings Section -->
        <fieldset class="border p-4 rounded">
             <legend class="text-lg font-semibold text-primary px-2">Visibility</legend>
            <div class="form-group flex items-center">
                <input type="checkbox" id="is_featured" name="is_featured" value="1" class="form-checkbox h-5 w-5 text-primary rounded" <?= getAdminCheckedValue($product, 'is_featured') ?>>
                <label for="is_featured" class="ml-2 block text-sm font-medium text-gray-700">Featured Product (Show on Homepage)</label>
            </div>
             <!-- Add is_active toggle if needed in schema/controller -->
             <!-- <div class="form-group flex items-center mt-2">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="form-checkbox h-5 w-5 text-primary rounded" checked>
                <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">Product Active (Visible in Shop)</label>
            </div> -->
        </fieldset>

        <!-- Actions -->
        <div class="flex justify-end space-x-4 pt-6 border-t mt-6">
            <a href="index.php?page=admin&section=products" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                 <i class="fas fa-save mr-2"></i><?= $isEditMode ? 'Update Product' : 'Create Product' ?>
            </button>
        </div>
    </form>
</div>

<script>
    // Basic JS for form interactions if needed
    document.querySelector('form').addEventListener('submit', function() {
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            // Add a spinner or change text
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Saving...`;
        }
        // The controller needs to parse newline-separated textareas for 'benefits' and 'gallery_images'
    });
</script>


<?php
// Use the admin layout footer
require_once __DIR__ . '/../layout/admin_footer.php';
?>
