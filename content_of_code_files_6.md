# views/quiz.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-quiz">

<div class="quiz-container min-h-screen bg-gradient-to-br from-primary/5 to-secondary/5 py-20">
    <!-- Particles Background -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-8" data-aos="fade-up">
            <h1 class="text-4xl font-heading font-semibold text-center mb-8">Find Your Perfect Scent</h1>
            <p class="text-center text-gray-600 mb-12">Let us guide you to the perfect aromatherapy products for your needs.</p>

            <form id="scent-quiz" method="POST" action="index.php?page=quiz&action=submit" class="space-y-8">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="quiz-step" data-step="1">
                    <h3 class="text-2xl font-heading mb-6">What are you looking for today?</h3>

                    <!-- Added quiz-options-container div for potential JS targeting -->
                    <div class="quiz-options-container grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="relaxation" class="hidden" required>
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-spa text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Relaxation</h4>
                                <p class="text-sm text-gray-600">Find calm and peace in your daily routine</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="energy" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-bolt text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Energy</h4>
                                <p class="text-sm text-gray-600">Boost your vitality and motivation</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="focus" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-brain text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Focus</h4>
                                <p class="text-sm text-gray-600">Enhance concentration and clarity</p>
                            </div>
                        </label>

                        <label class="quiz-option group">
                            <input type="radio" name="mood" value="balance" class="hidden">
                            <div class="p-6 border-2 border-gray-200 rounded-xl cursor-pointer transition-all duration-300 group-hover:border-primary group-hover:bg-primary/5">
                                <i class="fas fa-yin-yang text-3xl mb-4 text-primary"></i>
                                <h4 class="font-heading text-xl mb-2">Balance</h4>
                                <p class="text-sm text-gray-600">Find harmony in body and mind</p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-8 text-center">
                        <button type="submit" class="btn-primary inline-flex items-center space-x-2">
                            <span>Find My Perfect Scent</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

# views/quiz_results.php  
```php
<?php require_once __DIR__ . '/layout/header.php'; ?>
<body class="page-quiz-results">
<div class="min-h-screen bg-gradient-to-br from-primary/5 to-secondary/5 py-20">
    <!-- Particles Background -->
    <div id="particles-js" class="absolute inset-0 z-0"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="max-w-4xl mx-auto">
            <!-- Results Header -->
            <div class="text-center mb-12" data-aos="fade-down">
                <h1 class="text-4xl font-heading font-semibold mb-4">Your Perfect Scent Match</h1>
                <p class="text-xl text-gray-600">Based on your preferences, we've curated these perfect matches for you.</p>
            </div>

            <!-- Product Recommendations -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 md:gap-8 mb-12">
                <?php if (!isset($products) || !is_array($products)): $products = []; endif; ?>

                <?php if (empty($products)): ?>
                    <div class="col-span-full text-center py-12 bg-white rounded-xl shadow-lg" data-aos="fade-up">
                        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">No Specific Recommendations Found</h3>
                        <p class="text-gray-500 mb-6">We couldn't find specific products matching your selection, but feel free to explore our full collection!</p>
                        <a href="index.php?page=products" class="btn-primary">Shop All Products</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $index => $product): ?>
                        <?php // Using the standard product card structure for consistency ?>
                        <div class="product-card sample-card bg-white rounded-lg shadow-md overflow-hidden transition-shadow duration-300 hover:shadow-xl flex flex-col" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                            <div class="product-image relative h-64 overflow-hidden">
                                <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                    <img src="<?= htmlspecialchars($product['image'] ?? '/images/placeholder.jpg') ?>"
                                         alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover transition-transform duration-300 hover:scale-105">
                                </a>
                                <?php if (!empty($product['is_featured'])): ?>
                                    <span class="absolute top-2 left-2 bg-accent text-white text-xs font-semibold px-2 py-0.5 rounded-full">Featured</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info p-4 flex flex-col flex-grow text-center">
                                <h3 class="text-lg font-semibold mb-1 font-heading text-primary hover:text-accent">
                                    <a href="index.php?page=product&id=<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </a>
                                </h3>
                                <?php if (!empty($product['short_description'])): ?>
                                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['short_description']) ?></p>
                                <?php elseif (!empty($product['category_name'])): ?>
                                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($product['category_name']) ?></p>
                                <?php endif; ?>
                                <p class="price text-base font-semibold text-accent mb-4 mt-auto">$<?= isset($product['price']) ? number_format($product['price'], 2) : 'N/A' ?></p>
                                <div class="product-actions mt-auto flex gap-2 justify-center">
                                    <a href="index.php?page=product&id=<?= $product['id'] ?>" class="btn btn-primary">View Details</a>
                                    <?php $isOutOfStock = (!isset($product['stock_quantity']) || $product['stock_quantity'] <= 0) && empty($product['backorder_allowed']); ?>
                                    <?php if (!$isOutOfStock): ?>
                                        <button class="btn btn-secondary add-to-cart"
                                                data-product-id="<?= $product['id'] ?>">
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="text-center space-x-4" data-aos="fade-up">
                <a href="index.php?page=quiz" class="btn-secondary">
                    <i class="fas fa-redo mr-1"></i> Retake Quiz
                </a>
                <a href="index.php?page=products" class="btn-primary">
                    <i class="fas fa-shopping-bag mr-1"></i> Shop All Products
                </a>
            </div>

            <!-- Newsletter Signup -->
            <div class="mt-16 bg-white rounded-xl shadow-lg p-8 text-center" data-aos="fade-up">
                <h3 class="font-heading text-2xl mb-4">Stay Updated</h3>
                <p class="text-gray-600 mb-6">Sign up for our newsletter to receive personalized aromatherapy tips and exclusive offers.</p>

                <form id="newsletter-form-results" action="index.php?page=newsletter&action=subscribe" method="POST" class="flex flex-col md:flex-row gap-4 justify-center max-w-lg mx-auto">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input
                        type="email"
                        name="email"
                        placeholder="Enter your email address"
                        class="newsletter-input flex-1 px-4 py-2 rounded-lg border border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"
                        required
                    >
                    <button type="submit" class="btn btn-primary newsletter-btn whitespace-nowrap">
                        Subscribe Now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout/footer.php'; ?>

```

