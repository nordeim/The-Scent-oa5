# `diff -u js/main.js-ofig js/main.js-new (v2)
--- js/main.js-orig	2025-05-08 18:54:18.012838921 +0800
+++ js/main.js-new	2025-05-09 11:58:06.078204569 +0800
@@ -6,11 +6,8 @@
     var navLinks = document.querySelector('.nav-links');
     if (menuToggle && navLinks) {
         menuToggle.addEventListener('click', function() {
-            // Toggle navigation visibility
             navLinks.classList.toggle('active');
-            // Toggle body class to prevent scrolling when menu is open
             document.body.classList.toggle('menu-open');
-            // Toggle icon class (optional, if you want fa-times)
              const icon = menuToggle.querySelector('i');
              if (icon) {
                  icon.classList.toggle('fa-bars');
@@ -18,7 +15,6 @@
              }
         });
     }
-    // Close menu if clicking outside of it on mobile
     document.addEventListener('click', function(e) {
         if (navLinks && navLinks.classList.contains('active') && menuToggle && !menuToggle.contains(e.target) && !navLinks.contains(e.target)) {
              navLinks.classList.remove('active');
@@ -35,118 +31,76 @@
 // showFlashMessage utility
 window.showFlashMessage = function(message, type = 'info') {
     let flashContainer = document.querySelector('.flash-message-container');
-    // Create container if it doesn't exist
     if (!flashContainer) {
         flashContainer = document.createElement('div');
-        // Apply Tailwind classes for positioning and styling the container
         flashContainer.className = 'flash-message-container fixed top-5 right-5 z-[1100] max-w-sm w-full space-y-2';
         document.body.appendChild(flashContainer);
     }
-
     const flashDiv = document.createElement('div');
-    // Define color mapping using Tailwind classes
     const colorMap = {
         success: 'bg-green-100 border-green-400 text-green-700',
         error: 'bg-red-100 border-red-400 text-red-700',
         info: 'bg-blue-100 border-blue-400 text-blue-700',
         warning: 'bg-yellow-100 border-yellow-400 text-yellow-700'
     };
-    // Apply Tailwind classes for the message appearance
     flashDiv.className = `flash-message border px-4 py-3 rounded relative shadow-md flex justify-between items-center transition-opacity duration-300 ease-out opacity-0 ${colorMap[type] || colorMap['info']}`;
     flashDiv.setAttribute('role', 'alert');
-
     const messageSpan = document.createElement('span');
     messageSpan.className = 'block sm:inline';
     messageSpan.textContent = message;
     flashDiv.appendChild(messageSpan);
-
-    const closeButton = document.createElement('button'); // Use button for accessibility
+    const closeButton = document.createElement('button');
     closeButton.className = 'ml-4 text-xl leading-none font-semibold hover:text-black';
     closeButton.innerHTML = '&times;';
     closeButton.setAttribute('aria-label', 'Close message');
     closeButton.onclick = () => {
         flashDiv.style.opacity = '0';
-        // Remove after transition
         setTimeout(() => flashDiv.remove(), 300);
     };
     flashDiv.appendChild(closeButton);
-
-    // Add to container and fade in
     flashContainer.appendChild(flashDiv);
-    // Force reflow before adding opacity class for transition
-    void flashDiv.offsetWidth;
+    void flashDiv.offsetWidth; 
     flashDiv.style.opacity = '1';
-
-
-    // Auto-dismiss timer
     setTimeout(() => {
-        if (flashDiv && flashDiv.parentNode) { // Check if it wasn't already closed
+        if (flashDiv && flashDiv.parentNode) {
              flashDiv.style.opacity = '0';
-             setTimeout(() => flashDiv.remove(), 300); // Remove after fade out
+             setTimeout(() => flashDiv.remove(), 300);
         }
-    }, 5000); // Keep message for 5 seconds
+    }, 5000);
 };
 
-
-// Global AJAX handlers (Add-to-Cart, Newsletter, etc.)
+// Global AJAX handlers
 window.addEventListener('DOMContentLoaded', function() {
-    // Add-to-Cart handler (using event delegation on the body)
     document.body.addEventListener('click', function(e) {
         const btn = e.target.closest('.add-to-cart');
-        // Specific exclusion for related products button to prevent double handling if form also submits
-        // We now rely solely on the global handler for *all* add-to-cart buttons.
-        // const btnRelated = e.target.closest('.add-to-cart-related');
-
-        if (!btn) return; // Exit if the clicked element is not an add-to-cart button or its child
-
-        e.preventDefault(); // Prevent default behavior (like form submission if button is type=submit)
-        if (btn.disabled) return; // Prevent multiple clicks while processing
-
+        if (!btn) return;
+        e.preventDefault();
+        if (btn.disabled) return;
         const productId = btn.dataset.productId;
         const csrfTokenInput = document.getElementById('csrf-token-value');
         const csrfToken = csrfTokenInput?.value;
-
-        // Check if this button is inside the main product detail form to get quantity
         const productForm = btn.closest('#product-detail-add-cart-form');
-        let quantity = 1; // Default quantity
+        let quantity = 1;
         if (productForm) {
             const quantityInput = productForm.querySelector('input[name="quantity"]');
-            if (quantityInput) {
-                 quantity = parseInt(quantityInput.value) || 1;
-            }
+            if (quantityInput) quantity = parseInt(quantityInput.value) || 1;
         }
-
-
         if (!productId || !csrfToken) {
             showFlashMessage('Cannot add to cart. Missing product or security token. Please refresh.', 'error');
-            console.error('Add to Cart Error: Missing productId or CSRF token input.');
             return;
         }
-
         btn.disabled = true;
-        const originalText = btn.textContent;
-        // Check if the button already contains an icon or just text
-        const hasIcon = btn.querySelector('i');
-        const loadingHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
-        const originalHTML = btn.innerHTML; // Store original HTML if it contains icons
-
-        btn.innerHTML = loadingHTML; // Adding state with spinner
-
+        const originalHTML = btn.innerHTML;
+        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding...';
         fetch('index.php?page=cart&action=add', {
             method: 'POST',
             headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
-            // Ensure quantity is sent based on whether it's from the main form or a simple button
             body: `product_id=${encodeURIComponent(productId)}&quantity=${encodeURIComponent(quantity)}&csrf_token=${encodeURIComponent(csrfToken)}`
         })
         .then(response => {
             const contentType = response.headers.get("content-type");
-            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
-                return response.json();
-            }
-            return response.text().then(text => {
-                 console.error('Add to Cart - Non-JSON response:', response.status, text);
-                 throw new Error(`Server returned status ${response.status}. Check server logs or network response.`);
-            });
+            if (response.ok && contentType && contentType.indexOf("application/json") !== -1) return response.json();
+            return response.text().then(text => { throw new Error(`Server returned status ${response.status}. Check server logs or network response.`); });
         })
         .then(data => {
             if (data.success) {
@@ -156,72 +110,40 @@
                     cartCountSpan.textContent = data.cart_count || 0;
                     cartCountSpan.style.display = (data.cart_count || 0) > 0 ? 'flex' : 'none';
                 }
-                 // Optionally change button text briefly or add a checkmark icon
                  btn.innerHTML = '<i class="fas fa-check mr-2"></i>Added!';
                  setTimeout(() => {
-                     // Restore original HTML or text
                      btn.innerHTML = originalHTML;
-                     // Re-enable button unless out of stock now
-                     if (data.stock_status !== 'out_of_stock') {
-                        btn.disabled = false;
-                     } else {
-                         // Keep disabled and update text if out of stock now
-                         btn.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Out of Stock';
-                         btn.classList.add('btn-disabled'); // Add a class if needed
-                     }
-                 }, 1500); // Reset after 1.5 seconds
-
-                 // Update mini cart if applicable
-                 if (typeof fetchMiniCart === 'function') {
-                     fetchMiniCart();
-                 }
+                     if (data.stock_status !== 'out_of_stock') btn.disabled = false;
+                     else { btn.innerHTML = '<i class="fas fa-times-circle mr-2"></i>Out of Stock'; btn.classList.add('btn-disabled'); }
+                 }, 1500);
+                 if (typeof fetchMiniCart === 'function') fetchMiniCart();
             } else {
                 showFlashMessage(data.message || 'Could not add product to cart.', 'error');
-                btn.innerHTML = originalHTML; // Reset button immediately on failure
-                btn.disabled = false;
+                btn.innerHTML = originalHTML; btn.disabled = false;
             }
         })
         .catch((error) => {
-            console.error('Add to Cart Fetch Error:', error);
             showFlashMessage(error.message || 'Error adding to cart. Please try again.', 'error');
-            btn.innerHTML = originalHTML; // Reset button
-            btn.disabled = false;
+            btn.innerHTML = originalHTML; btn.disabled = false;
         });
     });
 
-    // Newsletter AJAX handler (if present)
-    var newsletterForm = document.getElementById('newsletter-form'); // Main newsletter form
-    var newsletterFormFooter = document.getElementById('newsletter-form-footer'); // Footer newsletter form
-
+    var newsletterForm = document.getElementById('newsletter-form');
+    var newsletterFormFooter = document.getElementById('newsletter-form-footer');
     function handleNewsletterSubmit(formElement) {
         formElement.addEventListener('submit', function(e) {
             e.preventDefault();
             const emailInput = formElement.querySelector('input[name="email"]');
             const submitButton = formElement.querySelector('button[type="submit"]');
-            const csrfTokenInput = formElement.querySelector('input[name="csrf_token"]'); // Get token from specific form
-
+            const csrfTokenInput = formElement.querySelector('input[name="csrf_token"]');
             if (!emailInput || !submitButton || !csrfTokenInput) {
-                 console.error("Newsletter form elements missing.");
-                 showFlashMessage('An error occurred. Please try again.', 'error');
-                 return;
-            }
-
-            const email = emailInput.value.trim();
-            const csrfToken = csrfTokenInput.value;
-
-            if (!email || !/\S+@\S+\.\S+/.test(email)) {
-                showFlashMessage('Please enter a valid email address.', 'error');
-                return;
-            }
-            if (!csrfToken) {
-                 showFlashMessage('Security token missing. Please refresh the page.', 'error');
-                 return;
+                 showFlashMessage('An error occurred. Please try again.', 'error'); return;
             }
-
+            const email = emailInput.value.trim(); const csrfToken = csrfTokenInput.value;
+            if (!email || !/\S+@\S+\.\S+/.test(email)) { showFlashMessage('Please enter a valid email address.', 'error'); return; }
+            if (!csrfToken) { showFlashMessage('Security token missing. Please refresh the page.', 'error'); return; }
             const originalButtonText = submitButton.textContent;
-            submitButton.disabled = true;
-            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subscribing...';
-
+            submitButton.disabled = true; submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subscribing...';
             fetch('index.php?page=newsletter&action=subscribe', {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
@@ -229,133 +151,66 @@
             })
             .then(res => {
                  const contentType = res.headers.get("content-type");
-                 if (res.ok && contentType && contentType.indexOf("application/json") !== -1) {
-                     return res.json();
-                 }
-                 return res.text().then(text => {
-                     console.error('Newsletter - Non-JSON response:', res.status, text);
-                     throw new Error(`Server returned status ${res.status}.`);
-                 });
+                 if (res.ok && contentType && contentType.indexOf("application/json") !== -1) return res.json();
+                 return res.text().then(text => { throw new Error(`Server returned status ${res.status}.`); });
             })
             .then(data => {
                 showFlashMessage(data.message || (data.success ? 'Subscription successful!' : 'Subscription failed.'), data.success ? 'success' : 'error');
-                if (data.success) {
-                    formElement.reset();
-                }
+                if (data.success) formElement.reset();
             })
-            .catch((error) => {
-                console.error('Newsletter Fetch Error:', error);
-                showFlashMessage(error.message || 'Error subscribing. Please try again later.', 'error');
-            })
-            .finally(() => {
-                 submitButton.disabled = false;
-                 submitButton.textContent = originalButtonText;
-            });
+            .catch((error) => { showFlashMessage(error.message || 'Error subscribing. Please try again later.', 'error'); })
+            .finally(() => { submitButton.disabled = false; submitButton.textContent = originalButtonText; });
         });
     }
-
-    if (newsletterForm) {
-        handleNewsletterSubmit(newsletterForm);
-    }
-    if (newsletterFormFooter) {
-        handleNewsletterSubmit(newsletterFormFooter);
-    }
+    if (newsletterForm) handleNewsletterSubmit(newsletterForm);
+    if (newsletterFormFooter) handleNewsletterSubmit(newsletterFormFooter);
 });
 
-
 // --- Page Specific Initializers ---
-
-function initHomePage() {
-    // console.log("Initializing Home Page");
-    // Particles.js initialization for hero section (if using)
-    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
-        particlesJS.load('particles-js', '/particles.json', function() {
-            // console.log('particles.js loaded - callback');
-        });
-    }
-}
-
+function initHomePage() { if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) particlesJS.load('particles-js', '/particles.json'); }
 function initProductsPage() {
-    // console.log("Initializing Products Page");
     const sortSelect = document.getElementById('sort');
     if (sortSelect) {
         sortSelect.addEventListener('change', function() {
             const url = new URL(window.location.href);
-            url.searchParams.set('sort', this.value);
-            url.searchParams.delete('page_num');
+            url.searchParams.set('sort', this.value); url.searchParams.delete('page_num');
             window.location.href = url.toString();
         });
     }
-
     const applyPriceFilter = document.querySelector('.apply-price-filter');
-    const minPriceInput = document.getElementById('minPrice');
-    const maxPriceInput = document.getElementById('maxPrice');
-
+    const minPriceInput = document.getElementById('minPrice'); const maxPriceInput = document.getElementById('maxPrice');
     if (applyPriceFilter && minPriceInput && maxPriceInput) {
         applyPriceFilter.addEventListener('click', function() {
-            const minPrice = minPriceInput.value.trim();
-            const maxPrice = maxPriceInput.value.trim();
+            const minPrice = minPriceInput.value.trim(); const maxPrice = maxPriceInput.value.trim();
             const url = new URL(window.location.href);
-
-            if (minPrice) url.searchParams.set('min_price', minPrice);
-            else url.searchParams.delete('min_price');
-
-            if (maxPrice) url.searchParams.set('max_price', maxPrice);
-            else url.searchParams.delete('max_price');
-
-            url.searchParams.delete('page_num');
-            window.location.href = url.toString();
+            if (minPrice) url.searchParams.set('min_price', minPrice); else url.searchParams.delete('min_price');
+            if (maxPrice) url.searchParams.set('max_price', maxPrice); else url.searchParams.delete('max_price');
+            url.searchParams.delete('page_num'); window.location.href = url.toString();
         });
     }
 }
-
 function initProductDetailPage() {
-    // console.log("Initializing Product Detail Page");
-    const mainImage = document.getElementById('mainImage');
-    const thumbnails = document.querySelectorAll('.thumbnail-grid img');
-
-    // Make updateMainImage function available globally for inline onclick
-    // Note: Using event delegation below is generally preferred over inline onclick
+    const mainImage = document.getElementById('mainImage'); const thumbnails = document.querySelectorAll('.thumbnail-grid img');
     window.updateMainImage = function(thumbnailElement) {
         if (mainImage && thumbnailElement) {
             mainImage.src = thumbnailElement.dataset.largeImage || thumbnailElement.src;
             mainImage.alt = thumbnailElement.alt.replace('Thumbnail', 'Main view');
-
-            thumbnails.forEach(img => img.parentElement.classList.remove('border-primary', 'border-2')); // Remove active style from parent div
-            thumbnailElement.parentElement.classList.add('border-primary', 'border-2'); // Add active style to parent div
+            thumbnails.forEach(img => img.parentElement.classList.remove('border-primary', 'border-2'));
+            thumbnailElement.parentElement.classList.add('border-primary', 'border-2');
         }
     }
-
-    // Set initial active thumbnail based on class (more reliable if structure changes)
     const activeThumbnailDiv = document.querySelector('.thumbnail-grid .border-primary');
-    if (activeThumbnailDiv && mainImage && !mainImage.src.includes('placeholder.jpg')) { // Ensure first image isn't placeholder before potentially resetting
-        const activeThumbImg = activeThumbnailDiv.querySelector('img');
-        // Optional: Set main image source based on initially active thumb if needed
-        // if (activeThumbImg) updateMainImage(activeThumbImg);
-    } else if (thumbnails.length > 0) {
-        // If no thumb is marked active, activate the first one
-        thumbnails[0].parentElement.classList.add('border-primary', 'border-2');
-    }
-
-
-    // Quantity Selector Logic
+    if (activeThumbnailDiv && mainImage && !mainImage.src.includes('placeholder.jpg')) {} 
+    else if (thumbnails.length > 0) thumbnails[0].parentElement.classList.add('border-primary', 'border-2');
     const quantityInput = document.querySelector('.quantity-selector input[name="quantity"]');
     if (quantityInput) {
         const quantityMax = parseInt(quantityInput.getAttribute('max') || '99');
         const quantityMin = parseInt(quantityInput.getAttribute('min') || '1');
-
         document.querySelectorAll('.quantity-btn').forEach(btn => {
             btn.addEventListener('click', function() {
-                let currentValue = parseInt(quantityInput.value);
-                if (isNaN(currentValue)) currentValue = quantityMin;
-
-                if (this.classList.contains('plus')) {
-                    if (currentValue < quantityMax) quantityInput.value = currentValue + 1;
-                    else quantityInput.value = quantityMax;
-                } else if (this.classList.contains('minus')) {
-                    if (currentValue > quantityMin) quantityInput.value = currentValue - 1;
-                    else quantityInput.value = quantityMin;
-                }
+                let currentValue = parseInt(quantityInput.value); if (isNaN(currentValue)) currentValue = quantityMin;
+                if (this.classList.contains('plus')) quantityInput.value = currentValue < quantityMax ? currentValue + 1 : quantityMax;
+                else if (this.classList.contains('minus')) quantityInput.value = currentValue > quantityMin ? currentValue - 1 : quantityMin;
             });
         });
          quantityInput.addEventListener('change', function() {
@@ -364,608 +219,282 @@
              if (value > quantityMax) this.value = quantityMax;
          });
      }
-
-
-    // Tab Switching Logic
-    const tabContainer = document.querySelector('.product-tabs'); // Adjusted selector
+    const tabContainer = document.querySelector('.product-tabs');
     if (tabContainer) {
-         const tabBtns = tabContainer.querySelectorAll('.tab-btn');
-         const tabPanes = tabContainer.querySelectorAll('.tab-pane');
-
+         const tabBtns = tabContainer.querySelectorAll('.tab-btn'); const tabPanes = tabContainer.querySelectorAll('.tab-pane');
          tabContainer.addEventListener('click', function(e) {
              const clickedButton = e.target.closest('.tab-btn');
-             if (!clickedButton || clickedButton.classList.contains('text-primary')) return; // Check active style
-
+             if (!clickedButton || clickedButton.classList.contains('text-primary')) return;
              const tabId = clickedButton.dataset.tab;
-
-             tabBtns.forEach(b => {
-                 b.classList.remove('text-primary', 'border-primary');
-                 b.classList.add('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
-             });
-             tabPanes.forEach(pane => pane.classList.remove('active')); // Assuming 'active' class controls visibility
-
+             tabBtns.forEach(b => { b.classList.remove('text-primary', 'border-primary'); b.classList.add('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300'); });
+             tabPanes.forEach(pane => pane.classList.remove('active'));
              clickedButton.classList.add('text-primary', 'border-primary');
              clickedButton.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
-
              const activePane = tabContainer.querySelector(`.tab-pane#${tabId}`);
-             if (activePane) {
-                 activePane.classList.add('active');
-             }
+             if (activePane) activePane.classList.add('active');
          });
-
-         // Ensure initial active tab's pane is visible on load
          const initialActiveTab = tabContainer.querySelector('.tab-btn.text-primary');
          if (initialActiveTab) {
-             const initialTabId = initialActiveTab.dataset.tab;
-             const initialActivePane = tabContainer.querySelector(`.tab-pane#${initialTabId}`);
-             if (initialActivePane) {
-                 initialActivePane.classList.add('active');
-             }
+             const initialTabId = initialActiveTab.dataset.tab; const initialActivePane = tabContainer.querySelector(`.tab-pane#${initialTabId}`);
+             if (initialActivePane) initialActivePane.classList.add('active');
          } else {
-            // If no tab is active by default, activate the first one
-            const firstTab = tabContainer.querySelector('.tab-btn');
-            const firstPane = tabContainer.querySelector('.tab-pane');
+            const firstTab = tabContainer.querySelector('.tab-btn'); const firstPane = tabContainer.querySelector('.tab-pane');
             if (firstTab && firstPane) {
                  firstTab.classList.add('text-primary', 'border-primary');
                  firstTab.classList.remove('text-gray-500', 'border-transparent', 'hover:text-primary', 'hover:border-gray-300');
                  firstPane.classList.add('active');
             }
          }
-         // Add 'active' class styles to style.css if not already present
-         // .tab-pane { display: none; }
-         // .tab-pane.active { display: block; }
     }
-
-    // Note: The main add-to-cart button now uses the global handler, including quantity.
-    // Related product add-to-cart buttons also use the global handler (default quantity 1).
 }
-
-
 function initCartPage() {
-    // console.log("Initializing Cart Page");
-    const cartForm = document.getElementById('cartForm');
-    if (!cartForm) return;
-
-    // --- Helper Functions for Cart ---
+    const cartForm = document.getElementById('cartForm'); if (!cartForm) return;
     function updateCartTotalsDisplay() {
-        let subtotal = 0;
-        let itemCount = 0;
+        let subtotal = 0; let itemCount = 0;
         document.querySelectorAll('.cart-item').forEach(item => {
-            const priceElement = item.querySelector('.item-price');
-            const quantityInput = item.querySelector('.item-quantity input');
+            const priceElement = item.querySelector('.item-price'); const quantityInput = item.querySelector('.item-quantity input');
             const subtotalElement = item.querySelector('.item-subtotal');
-
             if (priceElement && quantityInput) {
-                // Extract price reliably, removing currency symbols etc.
                 const priceText = priceElement.dataset.price || priceElement.textContent;
-                const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
-                const quantity = parseInt(quantityInput.value);
-
+                const price = parseFloat(priceText.replace(/[^0-9.]/g, '')); const quantity = parseInt(quantityInput.value);
                 if (!isNaN(price) && !isNaN(quantity)) {
-                    const lineTotal = price * quantity;
-                    subtotal += lineTotal;
-                    itemCount += quantity;
-                    if (subtotalElement) {
-                        subtotalElement.textContent = '$' + lineTotal.toFixed(2);
-                    }
+                    const lineTotal = price * quantity; subtotal += lineTotal; itemCount += quantity;
+                    if (subtotalElement) subtotalElement.textContent = '$' + lineTotal.toFixed(2);
                 }
             }
         });
-
-        // Update summary totals
         const subtotalDisplay = cartForm.querySelector('.cart-summary .summary-row:nth-child(1) span:last-child');
-        const totalDisplay = document.getElementById('cart-grand-total'); // Use specific ID for grand total
+        const totalDisplay = document.getElementById('cart-grand-total');
         const shippingDisplay = cartForm.querySelector('.cart-summary .summary-row.shipping span:last-child');
         const freeShippingThreshold = parseFloat(document.body.dataset.freeShippingThreshold || '50');
         const baseShippingCost = parseFloat(document.body.dataset.baseShippingCost || '5.99');
-
         const shippingCost = subtotal >= freeShippingThreshold ? 0 : baseShippingCost;
-
-
         if (subtotalDisplay) subtotalDisplay.textContent = '$' + subtotal.toFixed(2);
         if (shippingDisplay) shippingDisplay.innerHTML = shippingCost === 0 ? '<span class="text-green-600">FREE</span>' : '$' + shippingCost.toFixed(2);
-        if (totalDisplay) totalDisplay.textContent = '$' + (subtotal + shippingCost).toFixed(2); // Update grand total
-
-
+        if (totalDisplay) totalDisplay.textContent = '$' + (subtotal + shippingCost).toFixed(2);
         updateCartCountHeader(itemCount);
-
-        // Handle empty cart state (find elements by class/ID)
-        const emptyCartMessage = document.querySelector('.empty-cart'); // Needs an element with this class/ID
-        const cartItemsContainer = document.querySelector('.cart-items'); // Container holding items
-        const cartSummary = document.querySelector('.cart-summary'); // Summary section
-        const cartActions = document.querySelector('.cart-actions'); // Buttons section
-        const checkoutButton = document.querySelector('.checkout'); // Checkout button
-
+        const emptyCartMessage = document.querySelector('.empty-cart'); const cartItemsContainer = document.querySelector('.cart-items');
+        const cartSummary = document.querySelector('.cart-summary'); const cartActions = document.querySelector('.cart-actions');
+        const checkoutButton = document.querySelector('.checkout');
         if (itemCount === 0) {
-            if (cartItemsContainer) cartItemsContainer.classList.add('hidden');
-            if (cartSummary) cartSummary.classList.add('hidden');
-            if (cartActions) cartActions.classList.add('hidden');
-            if (emptyCartMessage) emptyCartMessage.classList.remove('hidden');
+            if (cartItemsContainer) cartItemsContainer.classList.add('hidden'); if (cartSummary) cartSummary.classList.add('hidden');
+            if (cartActions) cartActions.classList.add('hidden'); if (emptyCartMessage) emptyCartMessage.classList.remove('hidden');
         } else {
-             if (cartItemsContainer) cartItemsContainer.classList.remove('hidden');
-             if (cartSummary) cartSummary.classList.remove('hidden');
-             if (cartActions) cartActions.classList.remove('hidden');
-            if (emptyCartMessage) emptyCartMessage.classList.add('hidden');
+             if (cartItemsContainer) cartItemsContainer.classList.remove('hidden'); if (cartSummary) cartSummary.classList.remove('hidden');
+             if (cartActions) cartActions.classList.remove('hidden'); if (emptyCartMessage) emptyCartMessage.classList.add('hidden');
         }
-
         if (checkoutButton) {
-            checkoutButton.classList.toggle('opacity-50', itemCount === 0);
-            checkoutButton.classList.toggle('cursor-not-allowed', itemCount === 0);
-            if(itemCount === 0) checkoutButton.setAttribute('disabled', 'disabled');
-            else checkoutButton.removeAttribute('disabled');
+            checkoutButton.classList.toggle('opacity-50', itemCount === 0); checkoutButton.classList.toggle('cursor-not-allowed', itemCount === 0);
+            if(itemCount === 0) checkoutButton.setAttribute('disabled', 'disabled'); else checkoutButton.removeAttribute('disabled');
         }
     }
-
     function updateCartCountHeader(count) {
         const cartCountSpan = document.querySelector('.cart-count');
         if (cartCountSpan) {
-            cartCountSpan.textContent = count;
-            cartCountSpan.style.display = count > 0 ? 'flex' : 'none';
+            cartCountSpan.textContent = count; cartCountSpan.style.display = count > 0 ? 'flex' : 'none';
             cartCountSpan.classList.toggle('animate-pulse', count > 0);
             setTimeout(() => cartCountSpan.classList.remove('animate-pulse'), 1000);
         }
     }
-
-    // --- Event Listeners for Cart Actions ---
     cartForm.addEventListener('click', function(e) {
         const quantityBtn = e.target.closest('.quantity-btn');
         if (quantityBtn) {
-            const input = quantityBtn.parentElement.querySelector('input[name^="updates["]'); // Target input by name pattern
-            if (!input) return;
-
-            const max = parseInt(input.getAttribute('max') || '99');
-            const min = parseInt(input.getAttribute('min') || '1');
-            let value = parseInt(input.value);
-            if (isNaN(value)) value = min;
-
-            if (quantityBtn.classList.contains('plus')) {
-                if (value < max) input.value = value + 1;
-                else input.value = max;
-            } else if (quantityBtn.classList.contains('minus')) {
-                if (value > min) input.value = value - 1;
-                else input.value = min;
-            }
-            // Trigger change event to update totals display immediately
-            input.dispatchEvent(new Event('change', { bubbles: true }));
-            return;
+            const input = quantityBtn.parentElement.querySelector('input[name^="updates["]'); if (!input) return;
+            const max = parseInt(input.getAttribute('max') || '99'); const min = parseInt(input.getAttribute('min') || '1');
+            let value = parseInt(input.value); if (isNaN(value)) value = min;
+            if (quantityBtn.classList.contains('plus')) value = value < max ? value + 1 : max;
+            else if (quantityBtn.classList.contains('minus')) value = value > min ? value - 1 : min;
+            input.value = value; input.dispatchEvent(new Event('change', { bubbles: true })); return;
         }
-
         const removeItemBtn = e.target.closest('.remove-item');
         if (removeItemBtn) {
-            e.preventDefault();
-            const cartItemRow = removeItemBtn.closest('.cart-item');
-            if (!cartItemRow) return;
-
-            const productId = removeItemBtn.dataset.productId;
-            const csrfTokenInput = cartForm.querySelector('input[name="csrf_token"]');
+            e.preventDefault(); const cartItemRow = removeItemBtn.closest('.cart-item'); if (!cartItemRow) return;
+            const productId = removeItemBtn.dataset.productId; const csrfTokenInput = cartForm.querySelector('input[name="csrf_token"]');
             const csrfToken = csrfTokenInput?.value;
-
-
-            if (!productId || !csrfToken) {
-                showFlashMessage('Error removing item: Missing data.', 'error');
-                return;
-            }
-
+            if (!productId || !csrfToken) { showFlashMessage('Error removing item: Missing data.', 'error'); return; }
             if (confirm('Are you sure you want to remove this item?')) {
-                cartItemRow.style.opacity = '0';
-                cartItemRow.style.transition = 'opacity 0.3s ease-out';
-                setTimeout(() => {
-                    cartItemRow.remove();
-                    updateCartTotalsDisplay(); // Update totals after removing element visually
-                }, 300);
-
+                cartItemRow.style.opacity = '0'; cartItemRow.style.transition = 'opacity 0.3s ease-out';
+                setTimeout(() => { cartItemRow.remove(); updateCartTotalsDisplay(); }, 300);
                 fetch('index.php?page=cart&action=remove', {
-                    method: 'POST',
-                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
+                    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                     body: `product_id=${encodeURIComponent(productId)}&csrf_token=${encodeURIComponent(csrfToken)}`
                 })
                 .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
                 .then(data => {
-                    if (data.success) {
-                        showFlashMessage(data.message || 'Item removed.', 'success');
-                        // Totals already updated visually. Header count updated by totals function.
-                        if (typeof fetchMiniCart === 'function') fetchMiniCart();
-                    } else {
-                        showFlashMessage(data.message || 'Error removing item.', 'error');
-                        // Revert optimistic UI update is complex, maybe force reload or rely on update button
-                        updateCartTotalsDisplay(); // Re-run totals to ensure consistency
-                    }
+                    if (data.success) { showFlashMessage(data.message || 'Item removed.', 'success'); if (typeof fetchMiniCart === 'function') fetchMiniCart(); }
+                    else { showFlashMessage(data.message || 'Error removing item.', 'error'); updateCartTotalsDisplay(); }
                 })
-                .catch(error => {
-                    console.error('Error removing item:', error);
-                    showFlashMessage('Failed to remove item.', 'error');
-                    updateCartTotalsDisplay();
-                });
-            }
-            return;
+                .catch(error => { showFlashMessage('Failed to remove item.', 'error'); updateCartTotalsDisplay(); });
+            } return;
         }
     });
-
     cartForm.addEventListener('change', function(e) {
         if (e.target.matches('.item-quantity input')) {
-            const input = e.target;
-            const max = parseInt(input.getAttribute('max') || '99');
-            const min = parseInt(input.getAttribute('min') || '1');
-            let value = parseInt(input.value);
-
-            if (isNaN(value) || value < min) input.value = min;
-            if (value > max) {
-                input.value = max;
-                showFlashMessage(`Quantity cannot exceed ${max}.`, 'warning');
-            }
-            updateCartTotalsDisplay(); // Update totals on manual input change
+            const input = e.target; const max = parseInt(input.getAttribute('max') || '99');
+            const min = parseInt(input.getAttribute('min') || '1'); let value = parseInt(input.value);
+            if (isNaN(value) || value < min) input.value = min; if (value > max) { input.value = max; showFlashMessage(`Quantity cannot exceed ${max}.`, 'warning');}
+            updateCartTotalsDisplay();
         }
     });
-
-    // AJAX Update Cart Button
-    const updateCartButton = cartForm.querySelector('.update-cart'); // More specific selector
+    const updateCartButton = cartForm.querySelector('.update-cart');
     if (updateCartButton) {
         updateCartButton.addEventListener('click', function(e) {
-            e.preventDefault();
-            const formData = new FormData(cartForm);
-            const submitButton = this;
-            const originalButtonText = submitButton.textContent;
-            submitButton.disabled = true;
+            e.preventDefault(); const formData = new FormData(cartForm); const submitButton = this;
+            const originalButtonText = submitButton.textContent; submitButton.disabled = true;
             submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
-
-            fetch('index.php?page=cart&action=update', {
-                method: 'POST',
-                body: formData
-            })
+            fetch('index.php?page=cart&action=update', { method: 'POST', body: formData })
             .then(response => response.json().catch(() => ({ success: false, message: 'Invalid response from server.' })))
             .then(data => {
                 if (data.success) {
-                    showFlashMessage(data.message || 'Cart updated!', 'success');
-                    updateCartTotalsDisplay(); // Recalculate totals visually
+                    showFlashMessage(data.message || 'Cart updated!', 'success'); updateCartTotalsDisplay();
                     if (typeof fetchMiniCart === 'function') fetchMiniCart();
                 } else {
-                     // Display specific stock errors if provided
                     let errorMessage = data.message || 'Failed to update cart.';
-                    if (data.errors && data.errors.length > 0) {
-                        errorMessage += ' ' + data.errors.join('; ');
-                    }
-                    showFlashMessage(errorMessage, 'error');
-                    // Optionally reload or revert changes if update fails significantly
-                    updateCartTotalsDisplay(); // Refresh totals again
+                    if (data.errors && data.errors.length > 0) errorMessage += ' ' + data.errors.join('; ');
+                    showFlashMessage(errorMessage, 'error'); updateCartTotalsDisplay();
                 }
             })
-            .catch(error => {
-                console.error('Error updating cart:', error);
-                showFlashMessage('Network error updating cart.', 'error');
-                 updateCartTotalsDisplay(); // Refresh totals again
-            })
-            .finally(() => {
-                 submitButton.disabled = false;
-                 submitButton.textContent = originalButtonText;
-            });
+            .catch(error => { showFlashMessage('Network error updating cart.', 'error'); updateCartTotalsDisplay(); })
+            .finally(() => { submitButton.disabled = false; submitButton.textContent = originalButtonText; });
         });
     }
-
-     updateCartTotalsDisplay(); // Initial calculation
+     updateCartTotalsDisplay();
 }
-
-
 function initLoginPage() {
-    // console.log("Initializing Login Page");
-    const form = document.getElementById('loginForm');
-    if (!form) return;
-
-    const submitButton = form.querySelector('button[type="submit"]');
-    const buttonText = submitButton?.querySelector('.button-text');
+    const form = document.getElementById('loginForm'); if (!form) return;
+    const submitButton = form.querySelector('button[type="submit"]'); const buttonText = submitButton?.querySelector('.button-text');
     const buttonLoader = submitButton?.querySelector('.button-loader');
-
-    // Password visibility toggle
     form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
         toggleBtn.addEventListener('click', function() {
             const passwordInput = this.previousElementSibling;
             if (passwordInput && passwordInput.type) {
                  const icon = this.querySelector('i');
-                 if (passwordInput.type === 'password') {
-                     passwordInput.type = 'text';
-                     icon?.classList.remove('fa-eye');
-                     icon?.classList.add('fa-eye-slash');
-                 } else {
-                     passwordInput.type = 'password';
-                     icon?.classList.remove('fa-eye-slash');
-                     icon?.classList.add('fa-eye');
-                 }
+                 if (passwordInput.type === 'password') { passwordInput.type = 'text'; icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash'); }
+                 else { passwordInput.type = 'password'; icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye'); }
             }
         });
     });
-
-    // AJAX form submission
     form.addEventListener('submit', function(e) {
-        e.preventDefault(); // Prevent standard form submission
-
-        const emailInput = form.querySelector('#email');
-        const passwordInput = form.querySelector('#password');
-        const csrfTokenInput = document.getElementById('csrf-token-value'); // Get global CSRF
-
+        e.preventDefault();
+        const emailInput = form.querySelector('#email'); const passwordInput = form.querySelector('#password');
+        const csrfTokenInput = document.getElementById('csrf-token-value');
         if (!emailInput || !passwordInput || !submitButton || !csrfTokenInput) {
-            console.error("Login form elements missing.");
-            showFlashMessage('An error occurred submitting the form.', 'error');
-            return;
+            showFlashMessage('An error occurred submitting the form.', 'error'); return;
         }
-         const email = emailInput.value.trim();
-         const password = passwordInput.value;
-         const csrfToken = csrfTokenInput.value;
-
-
-        if (!email || !password) {
-             showFlashMessage('Please enter both email and password.', 'warning');
-             return;
-        }
-         if (!csrfToken) {
-             showFlashMessage('Security token missing. Please refresh.', 'error');
-             return;
-         }
-
-
-        // Show loading state
-        if(buttonText) buttonText.classList.add('hidden');
-        if(buttonLoader) buttonLoader.classList.remove('hidden');
+         const email = emailInput.value.trim(); const password = passwordInput.value; const csrfToken = csrfTokenInput.value;
+        if (!email || !password) { showFlashMessage('Please enter both email and password.', 'warning'); return; }
+         if (!csrfToken) { showFlashMessage('Security token missing. Please refresh.', 'error'); return; }
+        if(buttonText) buttonText.classList.add('hidden'); if(buttonLoader) buttonLoader.classList.remove('hidden');
         submitButton.disabled = true;
-
-        // Prepare data for fetch
-        const formData = new FormData();
-        formData.append('email', email);
-        formData.append('password', password);
-        formData.append('csrf_token', csrfToken);
-        // Append remember_me if needed
-        const rememberMe = form.querySelector('input[name="remember_me"]');
-        if (rememberMe && rememberMe.checked) {
-            formData.append('remember_me', '1');
-        }
-
-
-        fetch('index.php?page=login', {
-            method: 'POST',
-            body: formData
-        })
+        const formData = new FormData(); formData.append('email', email); formData.append('password', password); formData.append('csrf_token', csrfToken);
+        const rememberMe = form.querySelector('input[name="remember_me"]'); if (rememberMe && rememberMe.checked) formData.append('remember_me', '1');
+        fetch('index.php?page=login', { method: 'POST', body: formData })
         .then(response => {
-             // Check content type before parsing JSON
              const contentType = response.headers.get("content-type");
-             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
-                 return response.json();
-             }
-             // Handle non-JSON or error responses
-             return response.text().then(text => {
-                  console.error("Login error - non-JSON response:", response.status, text);
-                  throw new Error(`Login failed. Server responded with status ${response.status}.`);
-             });
+             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) return response.json();
+             return response.text().then(text => { throw new Error(`Login failed. Server responded with status ${response.status}.`); });
          })
         .then(data => {
-            if (data.success && data.redirect) {
-                // Optional: show success message before redirect?
-                // showFlashMessage('Login successful! Redirecting...', 'success');
-                window.location.href = data.redirect; // Redirect on success
-            } else {
-                // Show error message from backend
-                showFlashMessage(data.error || 'Login failed. Please check your credentials.', 'error');
-            }
-        })
-        .catch(error => {
-            console.error('Login Fetch Error:', error);
-            showFlashMessage(error.message || 'An error occurred during login. Please try again.', 'error');
+            if (data.success && data.redirect) window.location.href = data.redirect;
+            else showFlashMessage(data.error || 'Login failed. Please check your credentials.', 'error');
         })
+        .catch(error => { showFlashMessage(error.message || 'An error occurred during login. Please try again.', 'error'); })
         .finally(() => {
-            // Hide loading state only if login failed (page redirects on success)
-            if (buttonText) buttonText.classList.remove('hidden');
-            if (buttonLoader) buttonLoader.classList.add('hidden');
+            if (buttonText) buttonText.classList.remove('hidden'); if (buttonLoader) buttonLoader.classList.add('hidden');
             submitButton.disabled = false;
         });
     });
 }
-
-
 function initRegisterPage() {
-    // console.log("Initializing Register Page");
-    const form = document.getElementById('registerForm');
-    if (!form) return;
-
-    const passwordInput = form.querySelector('#password');
-    const confirmPasswordInput = form.querySelector('#confirm_password');
-    const submitButton = form.querySelector('button[type="submit"]');
-    const buttonText = submitButton?.querySelector('.button-text');
+    const form = document.getElementById('registerForm'); if (!form) return;
+    const passwordInput = form.querySelector('#password'); const confirmPasswordInput = form.querySelector('#confirm_password');
+    const submitButton = form.querySelector('button[type="submit"]'); const buttonText = submitButton?.querySelector('.button-text');
     const buttonLoader = submitButton?.querySelector('.button-loader');
-
     const requirements = {
         length: { regex: /.{12,}/, element: document.getElementById('req-length') },
         uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
         lowercase: { regex: /[a-z]/, element: document.getElementById('req-lowercase') },
         number: { regex: /[0-9]/, element: document.getElementById('req-number') },
-        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') }, // More general special char check
+        special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') },
         match: { element: document.getElementById('req-match') }
     };
-
     function validatePassword() {
-        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing
-
-        let allMet = true;
-        const passwordValue = passwordInput.value;
-        const confirmPasswordValue = confirmPasswordInput.value;
-
+        if (!passwordInput || !confirmPasswordInput || !submitButton) return true;
+        let allMet = true; const passwordValue = passwordInput.value; const confirmPasswordValue = confirmPasswordInput.value;
         for (const reqKey in requirements) {
-            const req = requirements[reqKey];
-            if (!req.element) continue;
-
-            let isMet = false;
-            if (reqKey === 'match') {
-                isMet = passwordValue && passwordValue === confirmPasswordValue;
-            } else if (req.regex) {
-                isMet = req.regex.test(passwordValue);
-            }
-
-            req.element.classList.toggle('met', isMet);
-            req.element.classList.toggle('not-met', !isMet);
+            const req = requirements[reqKey]; if (!req.element) continue; let isMet = false;
+            if (reqKey === 'match') isMet = passwordValue && passwordValue === confirmPasswordValue;
+            else if (req.regex) isMet = req.regex.test(passwordValue);
+            req.element.classList.toggle('met', isMet); req.element.classList.toggle('not-met', !isMet);
             const icon = req.element.querySelector('i');
             if (icon) {
-                icon.classList.toggle('fa-check-circle', isMet);
-                icon.classList.toggle('fa-times-circle', !isMet);
-                 icon.classList.toggle('text-green-500', isMet); // Add color classes
-                 icon.classList.toggle('text-red-500', !isMet);
+                icon.classList.toggle('fa-check-circle', isMet); icon.classList.toggle('fa-times-circle', !isMet);
+                 icon.classList.toggle('text-green-500', isMet); icon.classList.toggle('text-red-500', !isMet);
             }
             if (!isMet) allMet = false;
         }
-        submitButton.disabled = !allMet;
-        submitButton.classList.toggle('opacity-50', !allMet);
-        submitButton.classList.toggle('cursor-not-allowed', !allMet);
-        return allMet; // Return validation status
-    }
-
-    if (passwordInput && confirmPasswordInput) {
-        passwordInput.addEventListener('input', validatePassword);
-        confirmPasswordInput.addEventListener('input', validatePassword);
-        validatePassword();
+        submitButton.disabled = !allMet; submitButton.classList.toggle('opacity-50', !allMet); submitButton.classList.toggle('cursor-not-allowed', !allMet);
+        return allMet;
     }
-
+    if (passwordInput && confirmPasswordInput) { passwordInput.addEventListener('input', validatePassword); confirmPasswordInput.addEventListener('input', validatePassword); validatePassword(); }
     form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
         toggleBtn.addEventListener('click', function() {
             const passwordInputEl = this.previousElementSibling;
             if (passwordInputEl && passwordInputEl.type) {
                  const icon = this.querySelector('i');
-                 if (passwordInputEl.type === 'password') {
-                     passwordInputEl.type = 'text';
-                     icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
-                 } else {
-                     passwordInputEl.type = 'password';
-                     icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
-                 }
+                 if (passwordInputEl.type === 'password') { passwordInputEl.type = 'text'; icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash'); }
+                 else { passwordInputEl.type = 'password'; icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye'); }
             }
         });
     });
-
-    // AJAX form submission
     form.addEventListener('submit', function(e) {
-        e.preventDefault(); // Always prevent standard submission
-
-        if (!validatePassword()) { // Re-validate before submit
-            showFlashMessage('Please ensure all password requirements are met.', 'warning');
-            passwordInput?.focus(); // Focus on the first password field
-            return;
-        }
-
-         const nameInput = form.querySelector('#name');
-         const emailInput = form.querySelector('#email');
-         const csrfTokenInput = document.getElementById('csrf-token-value'); // Global CSRF
-         const newsletterCheckbox = form.querySelector('input[name="newsletter_signup"]'); // <-- Select the checkbox
-
+        e.preventDefault();
+        if (!validatePassword()) { showFlashMessage('Please ensure all password requirements are met.', 'warning'); passwordInput?.focus(); return; }
+         const nameInput = form.querySelector('#name'); const emailInput = form.querySelector('#email');
+         const csrfTokenInput = document.getElementById('csrf-token-value'); const newsletterCheckbox = form.querySelector('input[name="newsletter_signup"]');
         if (!nameInput || !emailInput || !passwordInput || !confirmPasswordInput || !submitButton || !csrfTokenInput) {
-            console.error("Register form elements missing.");
-            showFlashMessage('An error occurred submitting the form.', 'error');
-            return;
+            showFlashMessage('An error occurred submitting the form.', 'error'); return;
         }
-
-        const name = nameInput.value.trim();
-        const email = emailInput.value.trim();
-        const password = passwordInput.value; // Already validated
-        const csrfToken = csrfTokenInput.value;
-
-
-         if (!name || !email) {
-             showFlashMessage('Please fill in all required fields.', 'warning');
-             return;
-         }
-         if (!csrfToken) {
-             showFlashMessage('Security token missing. Please refresh.', 'error');
-             return;
-         }
-
-
-        // Show loading state
-        if(buttonText) buttonText.classList.add('hidden');
-        if(buttonLoader) buttonLoader.classList.remove('hidden');
-        submitButton.disabled = true;
-
-        // Prepare data for fetch
-        const formData = new FormData();
-        formData.append('name', name);
-        formData.append('email', email);
-        formData.append('password', password);
-        formData.append('confirm_password', confirmPasswordInput.value); // Send confirmation for backend double check if needed
-        formData.append('csrf_token', csrfToken);
-
-        // --- START: FIX FOR NEWSLETTER PREFERENCE ---
-        // Append newsletter_signup only if the checkbox exists and is checked
-        if (newsletterCheckbox && newsletterCheckbox.checked) {
-            formData.append('newsletter_signup', '1'); // Use '1' as the value
-        }
-        // --- END: FIX FOR NEWSLETTER PREFERENCE ---
-
-
-        fetch('index.php?page=register', {
-            method: 'POST',
-            body: formData
-        })
+        const name = nameInput.value.trim(); const email = emailInput.value.trim(); const password = passwordInput.value; const csrfToken = csrfTokenInput.value;
+         if (!name || !email) { showFlashMessage('Please fill in all required fields.', 'warning'); return; }
+         if (!csrfToken) { showFlashMessage('Security token missing. Please refresh.', 'error'); return; }
+        if(buttonText) buttonText.classList.add('hidden'); if(buttonLoader) buttonLoader.classList.remove('hidden'); submitButton.disabled = true;
+        const formData = new FormData(); formData.append('name', name); formData.append('email', email); formData.append('password', password);
+        formData.append('confirm_password', confirmPasswordInput.value); formData.append('csrf_token', csrfToken);
+        if (newsletterCheckbox && newsletterCheckbox.checked) formData.append('newsletter_signup', '1');
+        fetch('index.php?page=register', { method: 'POST', body: formData })
         .then(response => {
              const contentType = response.headers.get("content-type");
-             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) {
-                 return response.json();
-             }
-             return response.text().then(text => {
-                  console.error("Register error - non-JSON response:", response.status, text);
-                  throw new Error(`Registration failed. Server responded with status ${response.status}.`);
-             });
+             if (response.ok && contentType && contentType.indexOf("application/json") !== -1) return response.json();
+             return response.text().then(text => { throw new Error(`Registration failed. Server responded with status ${response.status}.`); });
          })
         .then(data => {
-            if (data.success && data.redirect) {
-                 // Controller sets flash message for next page load, just redirect
-                 window.location.href = data.redirect;
-            } else {
-                showFlashMessage(data.error || 'Registration failed. Please check your input and try again.', 'error');
-            }
-        })
-        .catch(error => {
-            console.error('Register Fetch Error:', error);
-            showFlashMessage(error.message || 'An error occurred during registration. Please try again.', 'error');
+            if (data.success && data.redirect) window.location.href = data.redirect;
+            else showFlashMessage(data.error || 'Registration failed. Please check your input and try again.', 'error');
         })
+        .catch(error => { showFlashMessage(error.message || 'An error occurred during registration. Please try again.', 'error'); })
         .finally(() => {
-            // Hide loading state only if registration failed (page redirects on success)
-            if (buttonText) buttonText.classList.remove('hidden');
-            if (buttonLoader) buttonLoader.classList.add('hidden');
-            // Re-enable button only if it failed, and re-validate password state
+            if (buttonText) buttonText.classList.remove('hidden'); if (buttonLoader) buttonLoader.classList.add('hidden');
             validatePassword();
         });
     });
 }
-
-
 function initForgotPasswordPage() {
-    // console.log("Initializing Forgot Password Page");
-    const form = document.getElementById('forgotPasswordForm');
-    if (!form) return;
+    const form = document.getElementById('forgotPasswordForm'); if (!form) return;
     const submitButton = form.querySelector('button[type="submit"]');
-
     if (form && submitButton) {
         form.addEventListener('submit', function(e) {
-             // Keep standard form submission as controller handles redirect
              const email = form.querySelector('#email')?.value.trim();
-             if (!email || !/\S+@\S+\.\S+/.test(email)) {
-                 showFlashMessage('Please enter a valid email address.', 'error');
-                 e.preventDefault();
-                 return;
-             }
-
-            const buttonText = submitButton.querySelector('.button-text');
-            const buttonLoader = submitButton.querySelector('.button-loader');
-            if(buttonText) buttonText.classList.add('hidden');
-            if(buttonLoader) buttonLoader.classList.remove('hidden');
-            submitButton.disabled = true;
-            // Allows standard POST
+             if (!email || !/\S+@\S+\.\S+/.test(email)) { showFlashMessage('Please enter a valid email address.', 'error'); e.preventDefault(); return; }
+            const buttonText = submitButton.querySelector('.button-text'); const buttonLoader = submitButton.querySelector('.button-loader');
+            if(buttonText) buttonText.classList.add('hidden'); if(buttonLoader) buttonLoader.classList.remove('hidden'); submitButton.disabled = true;
         });
     }
 }
-
-
 function initResetPasswordPage() {
-    // console.log("Initializing Reset Password Page");
-    const form = document.getElementById('resetPasswordForm');
-    if (!form) return;
-
-    const passwordInput = form.querySelector('#password');
-    const confirmPasswordInput = form.querySelector('#password_confirm');
+    const form = document.getElementById('resetPasswordForm'); if (!form) return;
+    const passwordInput = form.querySelector('#password'); const confirmPasswordInput = form.querySelector('#password_confirm');
     const submitButton = form.querySelector('button[type="submit"]');
-
     const requirements = {
         length: { regex: /.{12,}/, element: document.getElementById('req-length') },
         uppercase: { regex: /[A-Z]/, element: document.getElementById('req-uppercase') },
@@ -974,291 +503,132 @@
         special: { regex: /[^A-Za-z0-9]/, element: document.getElementById('req-special') },
         match: { element: document.getElementById('req-match') }
     };
-
     function validateResetPassword() {
-        if (!passwordInput || !confirmPasswordInput || !submitButton) return true; // Return true if elements missing
-
-        let allMet = true;
-        const passwordValue = passwordInput.value;
-        const confirmPasswordValue = confirmPasswordInput.value;
-
+        if (!passwordInput || !confirmPasswordInput || !submitButton) return true;
+        let allMet = true; const passwordValue = passwordInput.value; const confirmPasswordValue = confirmPasswordInput.value;
         for (const reqKey in requirements) {
-            const req = requirements[reqKey];
-            if (!req.element) continue;
-            let isMet = false;
-            if (reqKey === 'match') {
-                isMet = passwordValue && passwordValue === confirmPasswordValue;
-            } else if (req.regex) {
-                isMet = req.regex.test(passwordValue);
-            }
-            req.element.classList.toggle('met', isMet);
-            req.element.classList.toggle('not-met', !isMet);
+            const req = requirements[reqKey]; if (!req.element) continue; let isMet = false;
+            if (reqKey === 'match') isMet = passwordValue && passwordValue === confirmPasswordValue;
+            else if (req.regex) isMet = req.regex.test(passwordValue);
+            req.element.classList.toggle('met', isMet); req.element.classList.toggle('not-met', !isMet);
             const icon = req.element.querySelector('i');
             if (icon) {
-                icon.classList.toggle('fa-check-circle', isMet);
-                icon.classList.toggle('fa-times-circle', !isMet);
-                icon.classList.toggle('text-green-500', isMet); // Add color classes
-                icon.classList.toggle('text-red-500', !isMet);
+                icon.classList.toggle('fa-check-circle', isMet); icon.classList.toggle('fa-times-circle', !isMet);
+                icon.classList.toggle('text-green-500', isMet); icon.classList.toggle('text-red-500', !isMet);
             }
             if (!isMet) allMet = false;
         }
-        submitButton.disabled = !allMet;
-        submitButton.classList.toggle('opacity-50', !allMet);
-        submitButton.classList.toggle('cursor-not-allowed', !allMet);
-        return allMet; // Return validation status
+        submitButton.disabled = !allMet; submitButton.classList.toggle('opacity-50', !allMet); submitButton.classList.toggle('cursor-not-allowed', !allMet);
+        return allMet;
     }
-
-    if (passwordInput && confirmPasswordInput) {
-        passwordInput.addEventListener('input', validateResetPassword);
-        confirmPasswordInput.addEventListener('input', validateResetPassword);
-        validateResetPassword();
-    }
-
+    if (passwordInput && confirmPasswordInput) { passwordInput.addEventListener('input', validateResetPassword); confirmPasswordInput.addEventListener('input', validateResetPassword); validateResetPassword(); }
     form.querySelectorAll('.toggle-password').forEach(toggleBtn => {
          toggleBtn.addEventListener('click', function() {
              const passwordInputEl = this.previousElementSibling;
              if (passwordInputEl && passwordInputEl.type) {
                   const icon = this.querySelector('i');
-                  if (passwordInputEl.type === 'password') {
-                      passwordInputEl.type = 'text';
-                      icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash');
-                  } else {
-                      passwordInputEl.type = 'password';
-                      icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye');
-                  }
+                  if (passwordInputEl.type === 'password') { passwordInputEl.type = 'text'; icon?.classList.remove('fa-eye'); icon?.classList.add('fa-eye-slash'); }
+                  else { passwordInputEl.type = 'password'; icon?.classList.remove('fa-eye-slash'); icon?.classList.add('fa-eye'); }
              }
          });
      });
-
     if (form && submitButton) {
         form.addEventListener('submit', function(e) {
-            // Keep standard form submission as controller handles redirects
-            if (!validateResetPassword()) { // Final validation check
-                e.preventDefault();
-                showFlashMessage('Please ensure all password requirements are met.', 'error');
-                return;
-            }
-            const buttonText = submitButton.querySelector('.button-text');
-            const buttonLoader = submitButton.querySelector('.button-loader');
-             if(buttonText) buttonText.classList.add('hidden');
-             if(buttonLoader) buttonLoader.classList.remove('hidden');
-            submitButton.disabled = true;
-            // Allows standard POST
+            if (!validateResetPassword()) { e.preventDefault(); showFlashMessage('Please ensure all password requirements are met.', 'error'); return; }
+            const buttonText = submitButton.querySelector('.button-text'); const buttonLoader = submitButton.querySelector('.button-loader');
+             if(buttonText) buttonText.classList.add('hidden'); if(buttonLoader) buttonLoader.classList.remove('hidden'); submitButton.disabled = true;
         });
     }
 }
-
-
 function initQuizPage() {
-    // console.log("Initializing Quiz Page");
-    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
-        particlesJS.load('particles-js', '/particles.json');
-    }
-
+    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) particlesJS.load('particles-js', '/particles.json');
     const quizForm = document.getElementById('scent-quiz');
     if (quizForm) {
          const optionsContainer = quizForm.querySelector('.quiz-options-container');
          if (optionsContainer) {
              optionsContainer.addEventListener('click', (e) => {
-                 const selectedOption = e.target.closest('.quiz-option');
-                 if (!selectedOption) return;
-
-                 // Find the actual radio button within the clicked label
+                 const selectedOption = e.target.closest('.quiz-option'); if (!selectedOption) return;
                  const radioInput = selectedOption.querySelector('input[type="radio"]');
                  if (radioInput) {
-                     radioInput.checked = true; // Ensure the radio button is checked
-
-                     // Update visual states for all options
+                     radioInput.checked = true;
                      optionsContainer.querySelectorAll('.quiz-option').forEach(opt => {
-                         const innerDiv = opt.querySelector('div');
-                         const optRadio = opt.querySelector('input[type="radio"]');
+                         const innerDiv = opt.querySelector('div'); const optRadio = opt.querySelector('input[type="radio"]');
                          if (innerDiv && optRadio) {
-                              if (optRadio.checked) {
-                                 innerDiv.classList.add('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
-                                 innerDiv.classList.remove('border-gray-200');
-                              } else {
-                                 innerDiv.classList.remove('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary');
-                                 innerDiv.classList.add('border-gray-200');
-                              }
+                              if (optRadio.checked) { innerDiv.classList.add('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary'); innerDiv.classList.remove('border-gray-200'); }
+                              else { innerDiv.classList.remove('border-primary', 'bg-primary/10', 'ring-2', 'ring-primary'); innerDiv.classList.add('border-gray-200'); }
                          }
                      });
                  }
              });
          }
-
         quizForm.addEventListener('submit', (e) => {
-             // Check if any radio button in the group is checked
              const selectedRadio = quizForm.querySelector('input[name="mood"]:checked');
-
              if (!selectedRadio) {
-                 e.preventDefault();
-                 showFlashMessage('Please select an option.', 'warning');
-                 optionsContainer?.scrollIntoView({ behavior: 'smooth', block: 'center' });
-                 return;
+                 e.preventDefault(); showFlashMessage('Please select an option.', 'warning');
+                 optionsContainer?.scrollIntoView({ behavior: 'smooth', block: 'center' }); return;
              }
               const submitButton = quizForm.querySelector('button[type="submit"]');
-              if (submitButton) {
-                  submitButton.disabled = true;
-                  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Finding your scent...';
-              }
-             // Allows standard POST as controller handles rendering/redirect
+              if (submitButton) { submitButton.disabled = true; submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Finding your scent...'; }
         });
     }
 }
-
-
-function initQuizResultsPage() {
-    // console.log("Initializing Quiz Results Page");
-    if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) {
-        particlesJS.load('particles-js', '/particles.json');
-    }
-}
-
-
+function initQuizResultsPage() { if (typeof particlesJS !== 'undefined' && document.getElementById('particles-js')) particlesJS.load('particles-js', '/particles.json'); }
 function initAdminQuizAnalyticsPage() {
-    // console.log("Initializing Admin Quiz Analytics");
-    if (typeof Chart === 'undefined') {
-        console.error('Chart.js library is not loaded.');
-        return;
-    }
-    let charts = {};
-    const timeRangeSelect = document.getElementById('timeRange');
-    const statsContainer = document.getElementById('statsContainer'); // Corrected ID if necessary
-    const chartsContainer = document.getElementById('chartsContainer'); // Corrected ID if necessary
-    const recommendationsTableBody = document.getElementById('recommendationsTable'); // Corrected ID
-
-    // Check if elements exist before proceeding
-     if (!timeRangeSelect || !document.getElementById('totalParticipants') || !document.getElementById('conversionRate') || !document.getElementById('avgCompletionTime') || !document.getElementById('scentChart') || !document.getElementById('moodChart') || !document.getElementById('completionsChart') || !recommendationsTableBody) {
-          console.warn("One or more analytics elements not found. Analytics may not function correctly.");
-          // Optionally display a message to the user if critical elements are missing
-          // showFlashMessage("Could not load analytics components.", "error");
-          // return; // Exit if critical elements are missing
-     }
-
-
-    Chart.defaults.font.family = "'Montserrat', sans-serif";
-    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
-    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' };
-    Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
+    if (typeof Chart === 'undefined') return;
+    let charts = {}; const timeRangeSelect = document.getElementById('timeRange');
+    const recommendationsTableBody = document.getElementById('recommendationsTable');
+     if (!timeRangeSelect || !document.getElementById('totalParticipants') || !document.getElementById('conversionRate') || !document.getElementById('avgCompletionTime') || !document.getElementById('scentChart') || !document.getElementById('moodChart') || !document.getElementById('completionsChart') || !recommendationsTableBody) return;
+    Chart.defaults.font.family = "'Montserrat', sans-serif"; Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.7)';
+    Chart.defaults.plugins.tooltip.titleFont = { size: 14, weight: 'bold' }; Chart.defaults.plugins.tooltip.bodyFont = { size: 12 };
     Chart.defaults.plugins.legend.position = 'bottom';
-
     async function updateAnalytics() {
-        const timeRange = timeRangeSelect ? timeRangeSelect.value : '7d'; // Default if select missing
-        // Add visual indication of loading
-        document.getElementById('totalParticipants')?.classList.add('opacity-50');
-        document.getElementById('conversionRate')?.classList.add('opacity-50');
-        document.getElementById('avgCompletionTime')?.classList.add('opacity-50');
-        document.getElementById('scentChart')?.parentElement.classList.add('opacity-50');
-        document.getElementById('moodChart')?.parentElement.classList.add('opacity-50');
-        document.getElementById('completionsChart')?.parentElement.classList.add('opacity-50');
+        const timeRange = timeRangeSelect ? timeRangeSelect.value : '7d';
+        document.getElementById('totalParticipants')?.classList.add('opacity-50'); document.getElementById('conversionRate')?.classList.add('opacity-50');
+        document.getElementById('avgCompletionTime')?.classList.add('opacity-50'); document.getElementById('scentChart')?.parentElement.classList.add('opacity-50');
+        document.getElementById('moodChart')?.parentElement.classList.add('opacity-50'); document.getElementById('completionsChart')?.parentElement.classList.add('opacity-50');
         recommendationsTableBody?.classList.add('opacity-50');
-
         try {
-            // Use correct Admin route: index.php?page=admin&section=quiz_analytics
-            const response = await fetch(`index.php?page=admin&section=quiz_analytics&range=${timeRange}`, {
-                headers: {
-                    'X-Requested-With': 'XMLHttpRequest',
-                    'Accept': 'application/json'
-                }
-            });
-             if (!response.ok) {
-                  const errorText = await response.text();
-                  throw new Error(`Network response was not ok (${response.status}): ${errorText}`);
-             }
+            const response = await fetch(`index.php?page=admin&section=quiz_analytics&range=${timeRange}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
+             if (!response.ok) { const errorText = await response.text(); throw new Error(`Network response was not ok (${response.status}): ${errorText}`); }
             const data = await response.json();
-
-            // Adjust based on expected JSON structure from QuizController::showAnalytics
-            if (data.success) {
-                updateStatCards(data.data?.statistics);
-                updateCharts(data.data?.preferences); // Pass the preferences part
-                updateRecommendationsTable(data.data?.recommendations); // Pass the recommendations part
-            } else {
-                 throw new Error(data.error || 'Failed to fetch analytics data from the server.');
-            }
+            if (data.success) { updateStatCards(data.data?.statistics); updateCharts(data.data?.preferences); updateRecommendationsTable(data.data?.recommendations); }
+            else { throw new Error(data.error || 'Failed to fetch analytics data from the server.'); }
         } catch (error) {
-            console.error('Error fetching or processing analytics data:', error);
             showFlashMessage(`Failed to load analytics: ${error.message}`, 'error');
-             // Update UI to show loading failed state
-             document.getElementById('totalParticipants').textContent = 'Error';
-             document.getElementById('conversionRate').textContent = 'Error';
-             document.getElementById('avgCompletionTime').textContent = 'Error';
+             document.getElementById('totalParticipants').textContent = 'Error'; document.getElementById('conversionRate').textContent = 'Error'; document.getElementById('avgCompletionTime').textContent = 'Error';
              document.getElementById('scentChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
              document.getElementById('moodChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
              document.getElementById('completionsChart').parentElement.innerHTML = '<p class="text-red-500 text-center">Could not load chart.</p>';
             if (recommendationsTableBody) recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center text-red-500">Could not load recommendations.</td></tr>';
         } finally {
-             // Remove visual indication of loading
-             document.getElementById('totalParticipants')?.classList.remove('opacity-50');
-             document.getElementById('conversionRate')?.classList.remove('opacity-50');
-             document.getElementById('avgCompletionTime')?.classList.remove('opacity-50');
-             document.getElementById('scentChart')?.parentElement.classList.remove('opacity-50');
-             document.getElementById('moodChart')?.parentElement.classList.remove('opacity-50');
-             document.getElementById('completionsChart')?.parentElement.classList.remove('opacity-50');
+             document.getElementById('totalParticipants')?.classList.remove('opacity-50'); document.getElementById('conversionRate')?.classList.remove('opacity-50');
+             document.getElementById('avgCompletionTime')?.classList.remove('opacity-50'); document.getElementById('scentChart')?.parentElement.classList.remove('opacity-50');
+             document.getElementById('moodChart')?.parentElement.classList.remove('opacity-50'); document.getElementById('completionsChart')?.parentElement.classList.remove('opacity-50');
              recommendationsTableBody?.classList.remove('opacity-50');
         }
     }
-
     function updateStatCards(stats) {
-        if (!stats) {
-             document.getElementById('totalParticipants').textContent = 'N/A';
-             document.getElementById('conversionRate').textContent = 'N/A';
-             document.getElementById('avgCompletionTime').textContent = 'N/A';
-             return;
-         }
+        if (!stats) { document.getElementById('totalParticipants').textContent = 'N/A'; document.getElementById('conversionRate').textContent = 'N/A'; document.getElementById('avgCompletionTime').textContent = 'N/A'; return; }
         document.getElementById('totalParticipants').textContent = stats.total_quizzes ?? 'N/A';
         document.getElementById('conversionRate').textContent = stats.conversion_rate != null ? `${stats.conversion_rate}%` : 'N/A';
         document.getElementById('avgCompletionTime').textContent = stats.avg_completion_time != null ? `${stats.avg_completion_time}s` : 'N/A';
     }
-
     function updateCharts(preferences) {
-         if (!preferences) {
-              document.getElementById('scentChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>';
-              document.getElementById('moodChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>';
-              document.getElementById('completionsChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data.</p>';
-              return;
-         }
-         Object.values(charts).forEach(chart => chart?.destroy());
-         charts = {};
-         const chartColors = ['#1A4D5A', '#A0C1B1', '#D4A76A', '#6B7280', '#F59E0B', '#10B981'];
-
-         // Scent Preference Chart (Assuming 'scent_types' is correct key from controller)
+         if (!preferences) { document.getElementById('scentChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>'; document.getElementById('moodChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No preference data.</p>'; document.getElementById('completionsChart').parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data.</p>'; return; }
+         Object.values(charts).forEach(chart => chart?.destroy()); charts = {}; const chartColors = ['#1A4D5A', '#A0C1B1', '#D4A76A', '#6B7280', '#F59E0B', '#10B981'];
          const scentCtx = document.getElementById('scentChart')?.getContext('2d');
-         if (scentCtx && preferences.scent_types?.length > 0) {
-             charts.scent = new Chart(scentCtx, {
-                 type: 'doughnut',
-                 data: { labels: preferences.scent_types.map(p => p.type), datasets: [{ data: preferences.scent_types.map(p => p.count), backgroundColor: chartColors, hoverOffset: 4 }] },
-                 options: { responsive: true, plugins: { legend: { display: true }, title: { display: true, text: 'Scent Type Preferences' } } }
-             });
-         } else if (scentCtx) { scentCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No scent preference data.</p>'; }
-
-         // Mood Effect Chart (Assuming 'mood_effects' is correct key from controller)
+         if (scentCtx && preferences.scent_types?.length > 0) charts.scent = new Chart(scentCtx, { type: 'doughnut', data: { labels: preferences.scent_types.map(p => p.type), datasets: [{ data: preferences.scent_types.map(p => p.count), backgroundColor: chartColors, hoverOffset: 4 }] }, options: { responsive: true, plugins: { legend: { display: true }, title: { display: true, text: 'Scent Type Preferences' } } } });
+         else if (scentCtx) scentCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No scent preference data.</p>';
          const moodCtx = document.getElementById('moodChart')?.getContext('2d');
-         if (moodCtx && preferences.mood_effects?.length > 0) {
-            charts.mood = new Chart(moodCtx, {
-                type: 'bar',
-                data: { labels: preferences.mood_effects.map(p => p.effect), datasets: [{ label: 'Count', data: preferences.mood_effects.map(p => p.count), backgroundColor: chartColors[1], borderColor: chartColors[1], borderWidth: 1 }] },
-                options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Desired Mood Effects' } } }
-            });
-         } else if (moodCtx) { moodCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No mood effect data.</p>'; }
-
-         // Daily Completions Chart (Assuming 'daily_completions' is correct key)
+         if (moodCtx && preferences.mood_effects?.length > 0) charts.mood = new Chart(moodCtx, { type: 'bar', data: { labels: preferences.mood_effects.map(p => p.effect), datasets: [{ label: 'Count', data: preferences.mood_effects.map(p => p.count), backgroundColor: chartColors[1], borderColor: chartColors[1], borderWidth: 1 }] }, options: { indexAxis: 'y', responsive: true, scales: { x: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Desired Mood Effects' } } } });
+         else if (moodCtx) moodCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No mood effect data.</p>';
           const completionsCtx = document.getElementById('completionsChart')?.getContext('2d');
-          if (completionsCtx && preferences.daily_completions?.length > 0) {
-             charts.completions = new Chart(completionsCtx, {
-                 type: 'line',
-                 data: { labels: preferences.daily_completions.map(d => d.date), datasets: [{ label: 'Completions', data: preferences.daily_completions.map(d => d.count), borderColor: chartColors[0], backgroundColor: 'rgba(26, 77, 90, 0.1)', fill: true, tension: 0.1 }] },
-                 options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Quiz Completions Over Time' } } }
-             });
-         } else if (completionsCtx) { completionsCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data for this period.</p>'; }
+          if (completionsCtx && preferences.daily_completions?.length > 0) charts.completions = new Chart(completionsCtx, { type: 'line', data: { labels: preferences.daily_completions.map(d => d.date), datasets: [{ label: 'Completions', data: preferences.daily_completions.map(d => d.count), borderColor: chartColors[0], backgroundColor: 'rgba(26, 77, 90, 0.1)', fill: true, tension: 0.1 }] }, options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false }, title: { display: true, text: 'Quiz Completions Over Time' } } } });
+         else if (completionsCtx) completionsCtx.canvas.parentElement.innerHTML = '<p class="text-center text-gray-500">No completion data for this period.</p>';
     }
-
     function updateRecommendationsTable(recommendations) {
         if (!recommendations || !recommendationsTableBody) return;
-        if (recommendations.length === 0) {
-            recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No recommendations data available for this period.</td></tr>';
-            return;
-        }
-         // Assuming `recommendations` array has objects with keys like: name, category, recommendation_count, conversion_rate, id
+        if (recommendations.length === 0) { recommendationsTableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No recommendations data available for this period.</td></tr>'; return; }
         recommendationsTableBody.innerHTML = recommendations.map(product => `
             <tr class="hover:bg-gray-50">
                 <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${product.name || 'N/A'}</td>
@@ -1270,305 +640,183 @@
                 </td>
             </tr>`).join('');
     }
-
-    if (timeRangeSelect) {
-        timeRangeSelect.addEventListener('change', updateAnalytics);
-        updateAnalytics(); // Initial load
-    } else {
-        console.warn("Time range selector not found. Loading default analytics.");
-        updateAnalytics(); // Attempt initial load with default range
-    }
+    if (timeRangeSelect) { timeRangeSelect.addEventListener('change', updateAnalytics); updateAnalytics(); }
+    else updateAnalytics();
 }
-
-
 function initAdminCouponsPage() {
-    // console.log("Initializing Admin Coupons Page");
-    const createButton = document.getElementById('createCouponBtn');
-    const couponFormContainer = document.getElementById('couponFormContainer');
-    const couponForm = document.getElementById('couponForm');
-    const cancelFormButton = document.getElementById('cancelCouponForm');
-    const couponListTable = document.getElementById('couponListTable'); // Table body
-    const discountTypeSelect = document.getElementById('discount_type');
+    const createButton = document.getElementById('createCouponBtn'); const couponFormContainer = document.getElementById('couponFormContainer');
+    const couponForm = document.getElementById('couponForm'); const cancelFormButton = document.getElementById('cancelCouponForm');
+    const couponListTable = document.getElementById('couponListTable'); const discountTypeSelect = document.getElementById('discount_type');
     const valueHint = document.getElementById('valueHint');
-
     function showCouponForm(couponData = null) {
         if (!couponForm || !couponFormContainer) return;
-        couponForm.reset();
-        couponForm.querySelector('input[name="coupon_id"]').value = '';
-        const formTitle = couponFormContainer.querySelector('h2');
-        const submitBtn = couponForm.querySelector('button[type="submit"]');
-
+        couponForm.reset(); couponForm.querySelector('input[name="coupon_id"]').value = '';
+        const formTitle = couponFormContainer.querySelector('h2'); const submitBtn = couponForm.querySelector('button[type="submit"]');
         if (couponData) {
-            // Populate form for editing
-            couponForm.querySelector('input[name="coupon_id"]').value = couponData.id || '';
-            couponForm.querySelector('input[name="code"]').value = couponData.code || '';
-            couponForm.querySelector('textarea[name="description"]').value = couponData.description || '';
-            couponForm.querySelector('select[name="discount_type"]').value = couponData.discount_type || 'fixed';
-            couponForm.querySelector('input[name="value"]').value = couponData.discount_value || ''; // Use correct key
-            couponForm.querySelector('input[name="min_spend"]').value = couponData.min_purchase_amount || ''; // Use correct key
+            couponForm.querySelector('input[name="coupon_id"]').value = couponData.id || ''; couponForm.querySelector('input[name="code"]').value = couponData.code || '';
+            couponForm.querySelector('textarea[name="description"]').value = couponData.description || ''; couponForm.querySelector('select[name="discount_type"]').value = couponData.discount_type || 'fixed';
+            couponForm.querySelector('input[name="value"]').value = couponData.discount_value || ''; couponForm.querySelector('input[name="min_spend"]').value = couponData.min_purchase_amount || '';
             couponForm.querySelector('input[name="usage_limit"]').value = couponData.usage_limit || '';
             if (couponData.valid_from) couponForm.querySelector('input[name="valid_from"]').value = couponData.valid_from.replace(' ', 'T').substring(0, 16);
             if (couponData.valid_to) couponForm.querySelector('input[name="valid_to"]').value = couponData.valid_to.replace(' ', 'T').substring(0, 16);
              couponForm.querySelector('input[name="is_active"][value="1"]').checked = couponData.is_active == 1;
              couponForm.querySelector('input[name="is_active"][value="0"]').checked = couponData.is_active == 0;
-
-             if(formTitle) formTitle.textContent = 'Edit Coupon';
-             if(submitBtn) submitBtn.textContent = 'Update Coupon';
+             if(formTitle) formTitle.textContent = 'Edit Coupon'; if(submitBtn) submitBtn.textContent = 'Update Coupon';
         } else {
-             if(formTitle) formTitle.textContent = 'Create New Coupon';
-             if(submitBtn) submitBtn.textContent = 'Create Coupon';
-             // Set default active status for new coupons
+             if(formTitle) formTitle.textContent = 'Create New Coupon'; if(submitBtn) submitBtn.textContent = 'Create Coupon';
              couponForm.querySelector('input[name="is_active"][value="1"]').checked = true;
         }
-
-        updateValueHint();
-        couponFormContainer.classList.remove('hidden');
-        couponForm.scrollIntoView({ behavior: 'smooth' });
-    }
-
-    function hideCouponForm() {
-        if (!couponForm || !couponFormContainer) return;
-        couponForm.reset();
-        couponFormContainer.classList.add('hidden');
+        updateValueHint(); couponFormContainer.classList.remove('hidden'); couponForm.scrollIntoView({ behavior: 'smooth' });
     }
-
+    function hideCouponForm() { if (!couponForm || !couponFormContainer) return; couponForm.reset(); couponFormContainer.classList.add('hidden'); }
     function updateValueHint() {
-        if (!discountTypeSelect || !valueHint) return;
-        const selectedType = discountTypeSelect.value;
+        if (!discountTypeSelect || !valueHint) return; const selectedType = discountTypeSelect.value;
         if (selectedType === 'percentage') valueHint.textContent = 'Enter % (e.g., 10 for 10%). Max 100.';
         else if (selectedType === 'fixed') valueHint.textContent = 'Enter fixed amount (e.g., 15.50 for $15.50).';
         else valueHint.textContent = '';
     }
-
-    // Function to handle AJAX actions for Toggle/Delete
     function handleCouponAction(url, successMessage, errorMessage, confirmationMessage) {
-        if (confirmationMessage && !confirm(confirmationMessage)) {
-            return; // Abort if user cancels confirmation
-        }
-        const csrfToken = couponForm.querySelector('input[name="csrf_token"]')?.value; // Get CSRF from form for POST
-
-        fetch(url, {
-            method: 'POST', // Use POST for actions that change state
-            headers: {
-                'X-Requested-With': 'XMLHttpRequest',
-                'Content-Type': 'application/x-www-form-urlencoded' // Send CSRF in body
-            },
-            body: csrfToken ? `csrf_token=${encodeURIComponent(csrfToken)}` : ''
-        })
+        if (confirmationMessage && !confirm(confirmationMessage)) return;
+        const csrfToken = couponForm.querySelector('input[name="csrf_token"]')?.value;
+        fetch(url, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: csrfToken ? `csrf_token=${encodeURIComponent(csrfToken)}` : '' })
         .then(response => response.json().catch(() => ({ success: false, message: 'Invalid server response.' })))
         .then(data => {
-            if (data.success) {
-                showFlashMessage(successMessage, 'success');
-                location.reload(); // Reload to see changes
-            } else {
-                showFlashMessage(data.message || errorMessage, 'error');
-            }
+            if (data.success) { showFlashMessage(successMessage, 'success'); location.reload(); }
+            else { showFlashMessage(data.message || errorMessage, 'error'); }
         })
-        .catch(error => {
-            console.error('Coupon action error:', error);
-            showFlashMessage('An error occurred. Please try again.', 'error');
-        });
+        .catch(error => { showFlashMessage('An error occurred. Please try again.', 'error'); });
     }
-
     if (createButton) createButton.addEventListener('click', () => showCouponForm());
     if (cancelFormButton) cancelFormButton.addEventListener('click', hideCouponForm);
     if (discountTypeSelect) discountTypeSelect.addEventListener('change', updateValueHint);
-
-    // Initial call for hint
     updateValueHint();
-
-    // Event delegation for table buttons
     if (couponListTable) {
          couponListTable.addEventListener('click', function(e) {
-             const editButton = e.target.closest('.edit-coupon');
-             const toggleButton = e.target.closest('.toggle-status');
-             const deleteButton = e.target.closest('.delete-coupon');
-
+             const editButton = e.target.closest('.edit-coupon'); const toggleButton = e.target.closest('.toggle-status'); const deleteButton = e.target.closest('.delete-coupon');
              if (editButton) {
-                 e.preventDefault();
-                 try {
-                     const couponData = JSON.parse(editButton.dataset.coupon || '{}');
-                     if (couponData.id) showCouponForm(couponData);
-                     else console.error("Could not parse coupon data for editing.");
-                 } catch (err) {
-                     console.error("Error parsing coupon data:", err);
-                     showFlashMessage('Could not load coupon data.', 'error');
-                 }
-                 return;
-             }
-             if (toggleButton) {
-                 e.preventDefault();
-                 const couponId = toggleButton.dataset.couponId;
-                 if (couponId) {
-                     handleCouponAction(
-                         `index.php?page=admin&section=coupons&task=toggle_status&id=${couponId}`,
-                         'Status updated.',
-                         'Failed to update status.',
-                         'Toggle status for this coupon?' // Confirmation message
-                     );
-                 }
-                 return;
-             }
-             if (deleteButton) {
-                 e.preventDefault();
-                 const couponId = deleteButton.dataset.couponId;
-                 if (couponId) {
-                     handleCouponAction(
-                         `index.php?page=admin&section=coupons&task=delete&id=${couponId}`,
-                         'Coupon deleted.',
-                         'Failed to delete coupon.',
-                         'Permanently delete this coupon?' // Confirmation message
-                     );
-                 }
-                 return;
-             }
-         });
+                 e.preventDefault(); try { const couponData = JSON.parse(editButton.dataset.coupon || '{}'); if (couponData.id) showCouponForm(couponData); } catch (err) { showFlashMessage('Could not load coupon data.', 'error'); } return;
+              }
+             if (toggleButton) { e.preventDefault(); const couponId = toggleButton.dataset.couponId; if (couponId) handleCouponAction( `index.php?page=admin&section=coupons&task=toggle_status&id=${couponId}`, 'Status updated.', 'Failed to update status.', 'Toggle status for this coupon?' ); return; }
+             if (deleteButton) { e.preventDefault(); const couponId = deleteButton.dataset.couponId; if (couponId) handleCouponAction( `index.php?page=admin&section=coupons&task=delete&id=${couponId}`, 'Coupon deleted.', 'Failed to delete coupon.', 'Permanently delete this coupon?' ); return; }
+          });
     }
-
-     // Handle form submission (standard POST, controller handles redirect)
      if (couponForm) {
          couponForm.addEventListener('submit', function() {
              const submitBtn = couponForm.querySelector('button[type="submit"]');
-             if (submitBtn) {
-                 submitBtn.disabled = true;
-                 submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
-             }
+             if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...'; }
          });
      }
 }
-
-
-// --- Checkout Page Initialization (v4 - Corrected Flow) ---
 function initCheckoutPage() {
-    console.log("Initializing Checkout Page JS (v4)...");
-    // --- Configuration ---
-    const bodyData = document.body.dataset;
-    const stripePublicKey = bodyData.stripePublicKey || '';
-    const freeShippingThreshold = parseFloat(bodyData.freeShippingThreshold || '50');
-    const baseShippingCost = parseFloat(bodyData.baseShippingCost || '5.99');
+    console.log("Initializing Checkout Page JS (v4.1 - Stripe Object Check)...");
+    const bodyData = document.body.dataset; const stripePublicKey = bodyData.stripePublicKey || '';
+    const freeShippingThreshold = parseFloat(bodyData.freeShippingThreshold || '50'); const baseShippingCost = parseFloat(bodyData.baseShippingCost || '5.99');
     const baseUrl = bodyData.baseUrl || '/';
-
-    // --- Element Selectors ---
-    const checkoutForm = document.getElementById('checkoutForm');
-    const submitButton = document.getElementById('submit-button');
-    const spinner = document.getElementById('spinner');
-    const buttonText = document.getElementById('button-text');
-    const paymentElementContainer = document.getElementById('payment-element');
-    const paymentMessage = document.getElementById('payment-message');
+    const checkoutForm = document.getElementById('checkoutForm'); const submitButton = document.getElementById('submit-button');
+    const spinner = document.getElementById('spinner'); const buttonText = document.getElementById('button-text');
+    const paymentElementContainer = document.getElementById('payment-element'); const paymentMessage = document.getElementById('payment-message');
     const csrfToken = document.getElementById('csrf-token-value')?.value;
-    const shippingCountryEl = document.getElementById('shipping_country');
-    const shippingStateEl = document.getElementById('shipping_state');
-    const summarySubtotalEl = document.getElementById('summary-subtotal');
-    const summaryShippingEl = document.getElementById('summary-shipping');
-    const summaryTotalEl = document.getElementById('summary-total');
-    const taxAmountEl = document.getElementById('tax-amount');
-    const taxRateEl = document.getElementById('tax-rate');
-    const discountRow = document.querySelector('.summary-row.discount');
-    const discountAmountEl = document.getElementById('discount-amount');
-    const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
-    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code');
-    const couponCodeInput = document.getElementById('coupon_code');
-    const applyCouponButton = document.getElementById('apply-coupon');
-    const couponMessageEl = document.getElementById('coupon-message');
-
-    // --- State Variables ---
-    let stripe = null; // Initialize as null
-    // let elements = null; // Defer elements initialization
+    const shippingCountryEl = document.getElementById('shipping_country'); const shippingStateEl = document.getElementById('shipping_state');
+    const summarySubtotalEl = document.getElementById('summary-subtotal'); const summaryShippingEl = document.getElementById('summary-shipping');
+    const summaryTotalEl = document.getElementById('summary-total'); const taxAmountEl = document.getElementById('tax-amount');
+    const taxRateEl = document.getElementById('tax-rate'); const discountRow = document.querySelector('.summary-row.discount');
+    const discountAmountEl = document.getElementById('discount-amount'); const appliedCouponCodeDisplay = document.getElementById('applied-coupon-code-display');
+    const appliedCouponHiddenInput = document.getElementById('applied_coupon_code'); const couponCodeInput = document.getElementById('coupon_code');
+    const applyCouponButton = document.getElementById('apply-coupon'); const couponMessageEl = document.getElementById('coupon-message');
+    let stripe = null;
     let currentSubtotal = parseFloat(summarySubtotalEl?.textContent?.replace('$', '') || '0');
-    let currentShippingCost = parseFloat(summaryShippingEl?.textContent?.replace(/[^0-9.]/g, '') || baseShippingCost.toString()); // Handle FREE text
+    let currentShippingCost = parseFloat(summaryShippingEl?.textContent?.replace(/[^0-9.]/g, '') || baseShippingCost.toString());
     let currentTaxAmount = parseFloat(taxAmountEl?.textContent?.replace('$', '') || '0');
     let currentDiscountAmount = parseFloat(discountAmountEl?.textContent?.replace('-$', '') || '0');
 
-
-    // --- Basic Checks ---
-    console.log("Stripe Public Key:", stripePublicKey);
-    if (!stripePublicKey) {
-        showMessage("Stripe configuration error. Payment cannot proceed.", true);
-        setLoading(false, true); return;
-    }
-    if (!checkoutForm || !submitButton || !paymentElementContainer || !csrfToken || !summarySubtotalEl) {
-        console.error("Checkout form critical elements missing. Aborting initialization."); return;
+    console.log("Stripe Public Key (from body.dataset):", stripePublicKey);
+    if (!stripePublicKey) { showMessage("Stripe configuration error. Payment cannot proceed.", true); setLoading(false, true); return; }
+    if (!checkoutForm || !submitButton || !paymentElementContainer || !csrfToken || !summarySubtotalEl) { console.error("Checkout form critical elements missing."); return; }
+
+    // --- ADDED: Check if Stripe object is available ---
+    if (typeof Stripe === 'undefined') {
+        console.error("Stripe.js library not loaded or `Stripe` object is undefined.");
+        showMessage("Payment system library (Stripe.js) failed to load. Please check your internet connection or ad-blockers and refresh.", true);
+        setLoading(false, true);
+        paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error: Payment library missing. Cannot initialize payment form.</p>';
+        return;
     }
+    // --- END ADDED ---
 
-    // --- Initialize Stripe Core Object ONLY ---
     try {
          stripe = Stripe(stripePublicKey);
          if (!stripe) { throw new Error("Stripe(key) failed to return an object."); }
-         console.log("Stripe object initialized:", stripe);
-         paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Secure payment form will load here...</p>'; // Placeholder
-
+         console.log("Stripe object initialized successfully:", stripe);
+         paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Secure payment form will load here...</p>';
     } catch (stripeError) {
         console.error("Stripe initialization error:", stripeError);
-        showMessage("Could not initialize payment system. Please refresh.", true);
-        setLoading(false, true);
-        return;
+        showMessage("Could not initialize payment system. Please refresh. Details: " + stripeError.message, true);
+        setLoading(false, true); return;
     }
 
-    // --- Helper Functions (Ensure these are fully defined here) ---
-     function setLoading(isLoading, disablePermanently = false) {
-        if (!submitButton || !spinner || !buttonText) return;
-        if (isLoading) {
-            submitButton.disabled = true;
-            spinner.classList.remove('hidden');
-            buttonText.classList.add('hidden');
+    function setLoading(isLoading, disablePermanently = false) {
+       if (!submitButton || !spinner || !buttonText) return;
+       if (isLoading) {
+           submitButton.disabled = true;
+           spinner.classList.remove('hidden');
+           buttonText.classList.add('hidden');
+       } else {
+           submitButton.disabled = disablePermanently;
+           spinner.classList.add('hidden');
+           buttonText.classList.remove('hidden');
+       }
+   }
+   function showMessage(message, isError = true) {
+       if (!paymentMessage) return;
+       paymentMessage.textContent = message;
+       paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
+       paymentMessage.classList.remove('hidden');
+   }
+   function showCouponMessage(message, type) {
+       if (!couponMessageEl) return;
+       couponMessageEl.textContent = message;
+       couponMessageEl.className = `coupon-message mt-2 text-sm ${type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')}`;
+       couponMessageEl.classList.remove('hidden');
+   }
+    function updateOrderSummaryUI() {
+        if (!summarySubtotalEl || !discountRow || !discountAmountEl || !appliedCouponCodeDisplay || !summaryShippingEl || !taxAmountEl || !summaryTotalEl) return;
+        summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);
+        if (currentDiscountAmount > 0 && appliedCouponHiddenInput?.value) {
+            discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
+            appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
+            discountRow.classList.remove('hidden');
         } else {
-            submitButton.disabled = disablePermanently;
-            spinner.classList.add('hidden');
-            buttonText.classList.remove('hidden');
-        }
-    }
-    function showMessage(message, isError = true) {
-        if (!paymentMessage) return;
-        paymentMessage.textContent = message;
-        paymentMessage.className = `payment-message text-center text-sm my-4 ${isError ? 'text-red-600' : 'text-green-600'}`;
-        paymentMessage.classList.remove('hidden');
-    }
-    function showCouponMessage(message, type) {
-        if (!couponMessageEl) return;
-        couponMessageEl.textContent = message;
-        couponMessageEl.className = `coupon-message mt-2 text-sm ${type === 'success' ? 'text-green-600' : (type === 'error' ? 'text-red-600' : 'text-gray-600')}`;
-        couponMessageEl.classList.remove('hidden');
-    }
-     function updateOrderSummaryUI() {
-         if (!summarySubtotalEl || !discountRow || !discountAmountEl || !appliedCouponCodeDisplay || !summaryShippingEl || !taxAmountEl || !summaryTotalEl) return;
-         summarySubtotalEl.textContent = parseFloat(currentSubtotal).toFixed(2);
-         if (currentDiscountAmount > 0 && appliedCouponHiddenInput?.value) {
-             discountAmountEl.textContent = parseFloat(currentDiscountAmount).toFixed(2);
-             appliedCouponCodeDisplay.textContent = appliedCouponHiddenInput.value;
-             discountRow.classList.remove('hidden');
-         } else {
-             discountAmountEl.textContent = '0.00';
-             appliedCouponCodeDisplay.textContent = '';
-             discountRow.classList.add('hidden');
-         }
-         const subtotalAfterDiscount = Math.max(0, currentSubtotal - currentDiscountAmount);
-         currentShippingCost = subtotalAfterDiscount >= freeShippingThreshold ? 0 : baseShippingCost;
-         summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';
-         taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);
-         const grandTotal = subtotalAfterDiscount + currentShippingCost + currentTaxAmount;
-         summaryTotalEl.textContent = parseFloat(Math.max(0.50, grandTotal)).toFixed(2);
-     }
+            discountAmountEl.textContent = '0.00';
+            appliedCouponCodeDisplay.textContent = '';
+            discountRow.classList.add('hidden');
+        }
+        const subtotalAfterDiscount = Math.max(0, currentSubtotal - currentDiscountAmount);
+        currentShippingCost = subtotalAfterDiscount >= freeShippingThreshold ? 0 : baseShippingCost;
+        summaryShippingEl.innerHTML = currentShippingCost > 0 ? '$' + parseFloat(currentShippingCost).toFixed(2) : '<span class="text-green-600">FREE</span>';
+        taxAmountEl.textContent = '$' + parseFloat(currentTaxAmount).toFixed(2);
+        const grandTotal = subtotalAfterDiscount + currentShippingCost + currentTaxAmount;
+        summaryTotalEl.textContent = parseFloat(Math.max(0.50, grandTotal)).toFixed(2);
+    }
     async function updateTax() {
-            const country = shippingCountryEl?.value;
-            const state = shippingStateEl?.value;
-            if (!country || !taxRateEl || !taxAmountEl) {
-                 if (taxRateEl) taxRateEl.textContent = 'N/A'; currentTaxAmount = 0; updateOrderSummaryUI(); return;
-            }
-            try {
-                taxAmountEl.textContent = '...';
-                const response = await fetch('index.php?page=checkout&action=calculateTax', {
-                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
-                    body: JSON.stringify({ country, state, subtotal: currentSubtotal, discount: currentDiscountAmount })
-                });
-                if (!response.ok) throw new Error(`Tax calculation failed (${response.status})`);
-                const data = await response.json();
-                if (data.success) { taxRateEl.textContent = data.tax_rate_formatted || 'N/A'; currentTaxAmount = parseFloat(data.tax_amount) || 0; }
-                else { console.warn("Tax calculation error:", data.error); taxRateEl.textContent = 'Error'; currentTaxAmount = 0; }
-            } catch (e) { console.error('Error fetching tax:', e); taxRateEl.textContent = 'Error'; currentTaxAmount = 0;
-            } finally { updateOrderSummaryUI(); }
-        }
+        const country = shippingCountryEl?.value; const state = shippingStateEl?.value;
+        if (!country || !taxRateEl || !taxAmountEl) { if (taxRateEl) taxRateEl.textContent = 'N/A'; currentTaxAmount = 0; updateOrderSummaryUI(); return; }
+        try {
+            taxAmountEl.textContent = '...';
+            // --- MODIFIED: Add csrf_token to JSON body for calculateTax ---
+            const requestBody = { country, state, subtotal: currentSubtotal, discount: currentDiscountAmount, csrf_token: csrfToken };
+            const response = await fetch('index.php?page=checkout&action=calculateTax', {
+                method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
+                body: JSON.stringify(requestBody)
+            });
+            // --- END MODIFICATION ---
+            if (!response.ok) throw new Error(`Tax calculation failed (${response.status})`);
+            const data = await response.json();
+            if (data.success) { taxRateEl.textContent = data.tax_rate_formatted || 'N/A'; currentTaxAmount = parseFloat(data.tax_amount) || 0; }
+            else { console.warn("Tax calculation error:", data.error); taxRateEl.textContent = 'Error'; currentTaxAmount = 0; }
+        } catch (e) { console.error('Error fetching tax:', e); taxRateEl.textContent = 'Error'; currentTaxAmount = 0;
+        } finally { updateOrderSummaryUI(); }
+    }
 
-    // --- Event Listeners (Tax, Coupon) ---
     if(shippingCountryEl) shippingCountryEl.addEventListener('change', updateTax);
     if(shippingStateEl) shippingStateEl.addEventListener('input', updateTax);
     if (applyCouponButton && couponCodeInput && appliedCouponHiddenInput) {
@@ -1593,13 +841,9 @@
         });
     }
 
-    // --- Checkout Form Submission (Modified Flow) ---
     submitButton.addEventListener('click', async function(e) {
-        setLoading(true);
-        showMessage('');
+        setLoading(true); showMessage('');
         paymentElementContainer.innerHTML = '<p class="text-sm text-gray-500 text-center p-4">Loading secure payment form...</p>';
-
-        // 1. Client-side validation
         let isValid = true;
         const requiredFields = ['shipping_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
         requiredFields.forEach(id => {
@@ -1611,11 +855,7 @@
             const firstError = checkoutForm.querySelector('.input-error'); firstError?.focus(); firstError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
             paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Please complete shipping details first.</p>'; return;
         }
-
-        // 2. Send checkout data to server -> create order, get clientSecret
-        let clientSecret = null;
-        let serverOrderId = null;
-        let elements = null; // Define elements here for this scope
+        let clientSecret = null; let serverOrderId = null; let elements = null;
         try {
             const checkoutFormData = new FormData(checkoutForm);
             if (appliedCouponHiddenInput && appliedCouponHiddenInput.value) { checkoutFormData.set('applied_coupon_code', appliedCouponHiddenInput.value); } else { checkoutFormData.delete('applied_coupon_code'); }
@@ -1632,49 +872,40 @@
             paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Could not prepare payment. Please try again.</p>';
             setLoading(false); return;
         }
-
-        // --- *** NEW STEP 3: Initialize Elements & Mount Payment Element *** ---
         try {
-            if (!clientSecret) throw new Error("Client secret is missing after backend call."); // Safety check
+            if (!clientSecret) throw new Error("Client secret is missing after backend call.");
             const appearance = { theme: 'stripe', variables: { colorPrimary: '#1A4D5A', colorBackground: '#ffffff', colorText: '#374151', colorDanger: '#dc2626', fontFamily: 'Montserrat, sans-serif', borderRadius: '0.375rem' } };
-            elements = stripe.elements({ clientSecret: clientSecret, appearance }); // Pass clientSecret here
+            elements = stripe.elements({ clientSecret: clientSecret, appearance }); 
             console.log("Stripe Elements created with clientSecret.");
             const paymentElement = elements.create('payment');
-            paymentElementContainer.innerHTML = ''; // Clear placeholder
+            paymentElementContainer.innerHTML = ''; 
             paymentElement.mount('#payment-element'); console.log("Payment Element mounted successfully.");
         } catch (elementsError) {
             console.error("Stripe Elements creation/mounting error:", elementsError); showMessage("Failed to load the payment form. Please refresh.", true);
             paymentElementContainer.innerHTML = '<p class="text-sm text-red-500 text-center p-4">Error loading payment form.</p>';
             setLoading(false); return;
         }
-
-        // --- *** STEP 4: Confirm Payment *** ---
         if (clientSecret && stripe && elements) {
             console.log("Attempting stripe.confirmPayment...");
             const formattedBaseUrl = baseUrl.endsWith('/') ? baseUrl : baseUrl + '/';
             const returnUrl = `${window.location.origin}${formattedBaseUrl}index.php?page=checkout&action=confirmation`;
             console.log("Stripe return_url:", returnUrl);
-
             const { error: stripeError, paymentIntent } = await stripe.confirmPayment({
-                elements, // Pass the initialized elements group
-                confirmParams: { return_url: returnUrl },
-                redirect: 'if_required' // Let Stripe handle redirects if needed
+                elements, confirmParams: { return_url: returnUrl }, redirect: 'if_required'
             });
-
             if (stripeError) {
                  console.error("Stripe confirmPayment Error:", stripeError);
                  showMessage(stripeError.message || "Payment failed. Please check details or try another method.", true);
                  setLoading(false);
             } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                  console.log("Stripe confirmPayment SUCCEEDED directly:", paymentIntent);
-                 window.location.href = returnUrl; // Manually redirect if needed
+                 window.location.href = returnUrl; 
             } else if (paymentIntent) {
                  console.log("Stripe confirmPayment finished with status:", paymentIntent.status);
                  showMessage(`Payment status: ${paymentIntent.status}. You might be redirected.`, 'info');
                  setLoading(false);
             } else {
                  console.log("confirmPayment finished. Assuming redirect or error handled.");
-                 // Keep loading spinner ON if redirect is expected
             }
         } else {
             console.error("Missing clientSecret, stripe, or elements for confirmPayment.");
@@ -1682,56 +913,38 @@
             setLoading(false);
         }
     });
-
-    // Initial UI calculations
-    updateOrderSummaryUI();
-    if (shippingCountryEl?.value) {
-        updateTax();
-    }
-} // End initCheckoutPage
-
-
-// --- Admin Order Management Page ---
-function initAdminOrdersPage() {
-    // console.log("Initializing Admin Orders Page");
+    updateOrderSummaryUI(); if (shippingCountryEl?.value) updateTax();
+}
+function initAdminOrdersPage() { 
     const ordersTable = document.getElementById('ordersTable');
     const orderStatusSelects = document.querySelectorAll('.order-status-select');
-
     function updateOrderStatus(orderId, status) {
-        fetch('index.php?page=admin&action=updateOrderStatus', { // Need to ensure index.php routes this correctly
+        fetch('index.php?page=admin&action=updateOrderStatus', { 
             method: 'POST',
             headers: {
                 'Content-Type': 'application/x-www-form-urlencoded',
-                // 'X-```javascript
                 'X-Requested-With': 'XMLHttpRequest',
-                'X-CSRF-Token': document.getElementById('csrf-token-value')?.value // Include CSRF token
+                'X-CSRF-Token': document.getElementById('csrf-token-value')?.value 
             },
-            body: `order_id=${encodeURIComponent(orderId)}&status=${encodeURIComponent(status)}&csrf_token=${encodeURIComponent(document.getElementById('csrf-token-value')?.value || '')}` // Send CSRF token
+            body: `order_id=${encodeURIComponent(orderId)}&status=${encodeURIComponent(status)}&csrf_token=${encodeURIComponent(document.getElementById('csrf-token-value')?.value || '')}`
         })
         .then(response => response.json())
         .then(data => {
             if (data.success) {
                 showFlashMessage('Order status updated successfully.', 'success');
-                 // Maybe visually update the status in the table without full reload
                  const selectElement = document.querySelector(`.order-status-select[data-order-id="${orderId}"]`);
                  if (selectElement) {
-                     // Optionally add a visual cue like a temporary background color change
                      selectElement.closest('tr')?.classList.add('bg-green-100');
                      setTimeout(() => selectElement.closest('tr')?.classList.remove('bg-green-100'), 2000);
                  }
             } else {
                 showFlashMessage('Failed to update order status. Please try again.', 'error');
-                 // Optionally revert the select dropdown if the update failed
-                 // location.reload(); // Or force reload on failure
             }
         })
         .catch(error => {
-            console.error('Error updating order status:', error);
             showFlashMessage('An error occurred while updating the order status.', 'error');
-             // location.reload(); // Or force reload on failure
         });
     }
-
     orderStatusSelects.forEach(select => {
         select.addEventListener('change', function() {
             const orderId = this.dataset.orderId;
@@ -1740,68 +953,36 @@
                 if (confirm(`Change order #${orderId} status to "${this.options[this.selectedIndex].text}"?`)) {
                      updateOrderStatus(orderId, newStatus);
                 } else {
-                    this.value = this.dataset.currentStatus; // Revert dropdown if cancelled
+                    this.value = this.dataset.currentStatus; 
                 }
             }
         });
-         // Store initial status for potential revert
          select.dataset.currentStatus = select.value;
     });
 }
 
-
-// --- Page Initializer Dispatcher ---
 document.addEventListener('DOMContentLoaded', function() {
-    // Initialize AOS globally
-    if (typeof AOS !== 'undefined') {
-        AOS.init({ duration: 800, offset: 120, once: true });
-    } else {
-        console.warn('AOS library not loaded.');
-    }
-
+    if (typeof AOS !== 'undefined') AOS.init({ duration: 800, offset: 120, once: true });
+    else console.warn('AOS library not loaded.');
     const body = document.body;
-    // Map body class names to initializer functions
     const pageInitializers = {
-        'page-home': initHomePage,
-        'page-products': initProductsPage,
-        'page-product-detail': initProductDetailPage,
-        'page-cart': initCartPage,
-        'page-login': initLoginPage,
-        'page-register': initRegisterPage,
-        'page-forgot-password': initForgotPasswordPage,
-        'page-reset-password': initResetPasswordPage,
-        'page-quiz': initQuizPage,
-        'page-quiz-results': initQuizResultsPage,
-        'page-admin-quiz-analytics': initAdminQuizAnalyticsPage,
-        'page-admin-coupons': initAdminCouponsPage,
-        'page-checkout': initCheckoutPage, // Ensure this is called
-        'page-admin-orders': initAdminOrdersPage,
+        'page-home': initHomePage, 'page-products': initProductsPage, 'page-product-detail': initProductDetailPage,
+        'page-cart': initCartPage, 'page-login': initLoginPage, 'page-register': initRegisterPage,
+        'page-forgot-password': initForgotPasswordPage, 'page-reset-password': initResetPasswordPage,
+        'page-quiz': initQuizPage, 'page-quiz-results': initQuizResultsPage,
+        'page-admin-quiz-analytics': initAdminQuizAnalyticsPage, 'page-admin-coupons': initAdminCouponsPage,
+        'page-checkout': initCheckoutPage, 'page-admin-orders': initAdminOrdersPage,
     };
-
-    let initialized = false;
     for (const pageClass in pageInitializers) {
-        if (body.classList.contains(pageClass)) {
-            pageInitializers[pageClass]();
-            initialized = true;
-            // console.log(`Initialized: ${pageClass}`);
-            break;
-        }
-    }
-
-    // Fetch mini cart content on initial load
-    if (document.getElementById('mini-cart-content') && typeof fetchMiniCart === 'function') {
-         fetchMiniCart();
+        if (body.classList.contains(pageClass)) { pageInitializers[pageClass](); break; }
     }
+    if (document.getElementById('mini-cart-content') && typeof fetchMiniCart === 'function') fetchMiniCart();
 });
 
-
-// --- Mini Cart AJAX Update Function ---
 function fetchMiniCart() {
     const miniCartContent = document.getElementById('mini-cart-content');
     if (!miniCartContent) return;
-
     miniCartContent.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin text-gray-400"></i></div>';
-
     fetch('index.php?page=cart&action=mini', {
         method: 'GET',
         headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
