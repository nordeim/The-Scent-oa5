# diff -u js/main.js-orig js/main.js-new
--- js/main.js-orig	2025-04-29 14:09:22.635254331 +0800
+++ js/main.js-new	2025-04-29 19:19:49.795395899 +0800
@@ -842,6 +842,7 @@
          const nameInput = form.querySelector('#name');
          const emailInput = form.querySelector('#email');
          const csrfTokenInput = document.getElementById('csrf-token-value'); // Global CSRF
+         const newsletterCheckbox = form.querySelector('input[name="newsletter_signup"]'); // <-- Select the checkbox
 
         if (!nameInput || !emailInput || !passwordInput || !confirmPasswordInput || !submitButton || !csrfTokenInput) {
             console.error("Register form elements missing.");
@@ -878,6 +879,13 @@
         formData.append('confirm_password', confirmPasswordInput.value); // Send confirmation for backend double check if needed
         formData.append('csrf_token', csrfToken);
 
+        // --- START: FIX FOR NEWSLETTER PREFERENCE ---
+        // Append newsletter_signup only if the checkbox exists and is checked
+        if (newsletterCheckbox && newsletterCheckbox.checked) {
+            formData.append('newsletter_signup', '1'); // Use '1' as the value
+        }
+        // --- END: FIX FOR NEWSLETTER PREFERENCE ---
+
 
         fetch('index.php?page=register', {
             method: 'POST',
@@ -1386,7 +1394,7 @@
 // --- Checkout Page Initialization (Updated) ---
 function initCheckoutPage() {
     // console.log("Initializing Checkout Page");
-    const stripePublicKey = document.body.dataset.stripePublicKey; // Get PK from data attribute only
+    const stripePublicKey = document.body.dataset.stripePublicKey || '<?= defined(\'STRIPE_PUBLIC_KEY\') ? STRIPE_PUBLIC_KEY : \'\' ?>';
     const checkoutForm = document.getElementById('checkoutForm');
     const submitButton = document.getElementById('submit-button');
     const spinner = document.getElementById('spinner');
@@ -1731,58 +1739,124 @@
 
 
 // --- Page Initializer Dispatcher ---
-function initPage() {
-    const pageType = document.body.dataset.pageType;
-
-    switch(pageType) {
-        case 'home':
-            initHomePage();
-            break;
-        case 'products':
-            initProductsPage();
-            break;
-        case 'productDetail':
-            initProductDetailPage();
-            break;
-        case 'cart':
-            initCartPage();
-            break;
-        case 'login':
-            initLoginPage();
-            break;
-        case 'register':
-            initRegisterPage();
-            break;
-        case 'forgotPassword':
-            initForgotPasswordPage();
-            break;
-        case 'resetPassword':
-            initResetPasswordPage();
-            break;
-        case 'quiz':
-            initQuizPage();
-            break;
-        case 'quizResults':
-            initQuizResultsPage();
-            break;
-        case 'adminQuizAnalytics':
-            initAdminQuizAnalyticsPage();
-            break;
-        case 'adminCoupons':
-            initAdminCouponsPage();
-            break;
-        case 'checkout':
-            initCheckoutPage();
-            break;
-        case 'adminOrders':
-            initAdminOrdersPage();
-            break;
-        default:
-            console.warn('No specific initializer for this page type.');
+// Use the original dispatcher logic based on body class
+document.addEventListener('DOMContentLoaded', function() {
+    // Initialize AOS globally
+    if (typeof AOS !== 'undefined') {
+        AOS.init({ duration: 800, offset: 120, once: true });
+        // console.log('AOS Initialized Globally');
+    } else {
+        console.warn('AOS library not loaded.');
     }
+
+    const body = document.body;
+    // Map body class names to initializer functions
+    const pageInitializers = {
+        'page-home': initHomePage,
+        'page-products': initProductsPage,
+        'page-product-detail': initProductDetailPage,
+        'page-cart': initCartPage,
+        'page-login': initLoginPage,
+        'page-register': initRegisterPage,
+        'page-forgot-password': initForgotPasswordPage,
+        'page-reset-password': initResetPasswordPage,
+        'page-quiz': initQuizPage,
+        'page-quiz-results': initQuizResultsPage,
+        'page-admin-quiz-analytics': initAdminQuizAnalyticsPage,
+        'page-admin-coupons': initAdminCouponsPage,
+        'page-checkout': initCheckoutPage, // Added checkout initializer
+        'page-admin-orders': initAdminOrdersPage, // Added admin orders initializer
+         // Add other page classes and their init functions here
+         // 'page-account-dashboard': initAccountDashboardPage, // Example if needed
+         // 'page-account-profile': initAccountProfilePage, // Example if needed
+    };
+
+    let initialized = false;
+    for (const pageClass in pageInitializers) {
+        if (body.classList.contains(pageClass)) {
+	    // Assign data attributes using PHP variables for use in page initializers
+            body.dataset.baseUrl = '<?= BASE_URL ?>';
+            body.dataset.stripePublicKey = '<?= STRIPE_PUBLIC_KEY ?>';
+            body.dataset.freeShippingThreshold = '<?= FREE_SHIPPING_THRESHOLD ?>';
+            body.dataset.baseShippingCost = '<?= SHIPPING_COST ?>';
+            pageInitializers[pageClass]();
+            initialized = true;
+            // console.log(`Initialized: ${pageClass}`); // For debugging
+            break; // Assume only one main page class per body
+        }
+    }
+    // if (!initialized) {
+    //     console.log('No specific page initialization class found on body.');
+    // }
+
+    // Fetch mini cart content on initial load (if element exists)
+    if (document.getElementById('mini-cart-content') && typeof fetchMiniCart === 'function') {
+         fetchMiniCart();
+    }
+});
+
+
+// --- Mini Cart AJAX Update Function ---
+// (Keep the original function)
+function fetchMiniCart() {
+    const miniCartContent = document.getElementById('mini-cart-content');
+    if (!miniCartContent) return;
+
+    // Optional: Show a subtle loading state inside the dropdown
+    miniCartContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>';
+
+    fetch('index.php?page=cart&action=mini', {
+        method: 'GET',
+        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
+    })
+    .then(response => {
+        if (!response.ok) throw new Error(`Network response was not ok (${response.status})`);
+        return response.json();
+    })
+    .then(data => {
+        // Renders items or empty message based on data structure from CartController::mini
+        if (data.items && data.items.length > 0) {
+            let html = '<ul class="divide-y divide-gray-200 max-h-60 overflow-y-auto">';
+             data.items.forEach(item => {
+                 // Ensure item.product exists and has needed properties
+                 const productId = item.product?.id || '#'; // Fallback ID
+                 const imageUrl = item.product?.image || '/images/placeholder.jpg';
+                 const productName = item.product?.name || 'Unknown Product';
+                 const productPrice = parseFloat(item.product?.price || 0);
+                 const quantity = parseInt(item.quantity || 0);
+                 const lineTotal = productPrice * quantity;
+                 html += `
+                    <li class="flex items-center gap-3 py-3 px-1">
+                         <img src="${imageUrl}" alt="${productName}" class="w-12 h-12 object-cover rounded border flex-shrink-0">
+                         <div class="flex-1 min-w-0">
+                             <a href="index.php?page=product&id=${productId}" class="font-medium text-sm text-gray-800 hover:text-primary truncate block" title="${productName}">${productName}</a>
+                             <div class="text-xs text-gray-500">Qty: ${quantity} &times; $${productPrice.toFixed(2)}</div>
+                         </div>
+                         <div class="text-sm font-semibold text-gray-700">$${lineTotal.toFixed(2)}</div>
+                     </li>`;
+            });
+            html += '</ul>';
+            const subtotal = parseFloat(data.subtotal || 0);
+            html += `<div class="border-t border-gray-200 pt-4 mt-4">
+                 <div class="flex justify-between items-center mb-4">
+                     <span class="font-semibold text-gray-700">Subtotal:</span>
+                     <span class="font-bold text-primary text-lg">$${subtotal.toFixed(2)}</span>
+                 </div>
+                 <div class="flex flex-col gap-2">
+                     <a href="index.php?page=cart" class="btn btn-secondary w-full text-center">View Cart</a>
+                     <a href="index.php?page=checkout" class="btn btn-primary w-full text-center ${subtotal === 0 ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''}">Checkout</a>
+                 </div>
+             </div>`;
+            miniCartContent.innerHTML = html;
+        } else {
+            miniCartContent.innerHTML = '<div class="text-center text-gray-500 py-6 px-4">Your cart is empty.</div>';
+        }
+    })
+    .catch(error => {
+        console.error('Error fetching mini cart:', error);
+        miniCartContent.innerHTML = '<div class="text-center text-red-500 py-6 px-4">Could not load cart.</div>';
+    });
 }
 
-// Initialize the page-specific scripts
-document.addEventListener('DOMContentLoaded', initPage);
 
 // --- END OF FILE main.js ---
