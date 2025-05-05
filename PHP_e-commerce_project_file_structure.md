# PHP e-commerce platform for "The Scent" store file structure

$ cd /cdrom/project/The-Scent-oa5

$ ls -l index.php css/style.css js/main.js particles.json .htaccess config.php includes/*php views/*php views/layout/*php controllers/*php models/*php views/admin/* | egrep -v 'test_|phpinfo'
```
-rw-rw-r-- 1 pete pete       508 Apr 18 04:44 .htaccess
-rwxr-xr-x 1 pete pete      3661 May  4 16:12 config.php
-rw-rw-r-- 1 pete pete     34039 Apr 28 11:39 controllers/AccountController.php
-rw-rw-r-- 1 pete pete     23902 Apr 28 11:24 controllers/BaseController.php
-rw-rw-r-- 1 pete pete     20312 Apr 28 13:13 controllers/CartController.php
-rw-rw-r-- 1 pete pete     33013 May  3 08:05 controllers/CheckoutController.php
-rw-rw-r-- 1 pete pete     18494 Apr 29 10:44 controllers/CouponController.php
-rw-rw-r-- 1 pete pete     15243 Apr 27 09:34 controllers/InventoryController.php
-rw-rw-r-- 1 pete pete     10374 Apr 27 09:34 controllers/NewsletterController.php
-rw-rw-r-- 1 pete pete     21931 May  3 08:08 controllers/PaymentController.php
-rw-rw-r-- 1 pete pete     23786 May  4 08:55 controllers/ProductController.php
-rw-rw-r-- 1 pete pete     19521 May  4 08:50 controllers/QuizController.php
-rwxr-xr-x 1 pete www-data  9695 Apr 14 07:16 controllers/TaxController.php
-rw-rw-r-- 1 pete pete     33421 May  2 08:14 css/style.css
-rw-rw-r-- 1 pete pete     20630 May  1 20:16 includes/EmailService.php
-rw-rw-r-- 1 pete pete     28830 May  1 22:12 includes/ErrorHandler.php
-rw-rw-r-- 1 pete pete     13475 Apr 29 13:51 includes/SecurityMiddleware.php
-rwxr-xr-x 1 pete www-data  1403 Apr 15 21:10 includes/auth.php
-rw-rw-r-- 1 pete pete       623 May  3 10:24 includes/clear_apcu.php
-rw-rw-r-- 1 pete pete      1321 May  3 10:27 includes/clear_apcu_cache.php
-rwxr-xr-x 1 pete pete       890 Apr 18 07:04 includes/db.php
-rw-rw-r-- 1 pete pete       716 Apr 28 09:30 includes/password_hash.php
-rw-rw-r-- 1 pete pete       193 Apr 27 08:24 includes/reset_cache.php
-rw-rw-r-- 1 pete pete     16595 May  3 11:46 index.php
-rw-rw-r-- 1 pete pete     88116 May  1 21:15 js/main.js
-rw-rw-r-- 1 pete pete      7065 Apr 28 14:00 models/Cart.php
-rw-rw-r-- 1 pete pete     14807 Apr 29 09:42 models/Order.php
-rw-rw-r-- 1 pete pete     21779 May  3 12:02 models/Product.php
-rw-rw-r-- 1 pete pete     20763 May  4 15:50 models/Quiz.php
-rw-rw-r-- 1 pete pete     10118 Apr 29 08:14 models/User.php
-rwxr-xr-x 1 pete pete      1401 Apr 18 04:53 particles.json
-rw-rw-r-- 1 pete pete      1111 Apr 25 07:44 views/404.php
-rw-rw-r-- 1 pete pete      6198 Apr 26 18:54 views/about.php
-rwxr-xr-x 1 pete www-data  7898 Apr 25 07:48 views/admin/coupons.php
-rw-rw-r-- 1 pete pete     13794 May  3 11:42 views/admin/product_form.php
-rw-rw-r-- 1 pete pete      6685 May  3 11:43 views/admin/products.php
-rw-rw-r-- 1 pete pete      4091 Apr 25 07:48 views/admin/quiz_analytics.php
-rw-rw-r-- 1 pete pete     11026 May  3 20:40 views/cart.php
-rw-rw-r-- 1 pete pete     29343 May  3 07:58 views/checkout.php
-rw-rw-r-- 1 pete pete      1329 Apr 23 08:16 views/contact.php
-rw-rw-r-- 1 pete pete      6326 May  1 21:54 views/error.php
-rw-rw-r-- 1 pete pete      1248 Apr 23 08:16 views/faq.php
-rwxr-xr-x 1 pete pete      1942 Apr 25 07:44 views/forgot_password.php
-rw-rw-r-- 1 pete pete     13818 Apr 25 23:21 views/home.php
-rw-rw-r-- 1 pete pete       187 Apr 14 09:07 views/layout/admin_footer.php
-rw-rw-r-- 1 pete pete      2269 May  3 11:40 views/layout/admin_header.php
-rw-rw-r-- 1 pete pete      4191 Apr 25 07:34 views/layout/footer.php
-rw-rw-r-- 1 pete pete      6705 Apr 29 09:04 views/layout/header.php
-rw-rw-r-- 1 pete pete      5406 Apr 29 19:30 views/login.php
-rw-rw-r-- 1 pete pete       975 Apr 23 08:16 views/order-tracking.php
-rw-rw-r-- 1 pete pete      7517 Apr 29 08:29 views/order_confirmation.php
-rw-rw-r-- 1 pete pete       756 Apr 23 08:16 views/privacy.php
-rwxr-xr-x 1 pete pete     23227 Apr 25 08:15 views/product_detail.php
-rwxr-xr-x 1 pete pete     13522 Apr 25 08:54 views/products.php
-rw-rw-r-- 1 pete pete      4919 May  4 08:58 views/quiz.php
-rw-rw-r-- 1 pete pete      6978 May  3 16:54 views/quiz_results.php
-rw-rw-r-- 1 pete pete      9184 Apr 29 19:19 views/register.php
-rwxr-xr-x 1 pete pete      4112 Apr 25 07:44 views/reset_password.php
-rw-rw-r-- 1 pete pete       768 Apr 23 08:16 views/shipping.php
```

$ cat .htaccess
Options +SymLinksIfOwnerMatch
RewriteEngine On

# Allow Installatron requests
RewriteCond %{REQUEST_FILENAME} deleteme\.\w+\.php
RewriteRule (.*) - [L]

# If the requested file or directory exists, do not rewrite
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Exclude all URLs like /test_xxx.php and /sample_xxx.html from being rewritten
RewriteCond %{REQUEST_URI} !^/test_.*\.php$
RewriteCond %{REQUEST_URI} !^/sample_.*\.html$
RewriteRule ^ index.php [L]

$ cat particles.json
{
  "particles": {
    "number": {
      "value": 40,
      "density": {
        "enable": true,
        "value_area": 1000
      }
    },
    "color": {
      "value": "#ffffff"
    },
    "shape": {
      "type": "circle"
    },
    "opacity": {
      "value": 0.2,
      "random": true,
      "anim": {
        "enable": true,
        "speed": 0.5,
        "opacity_min": 0.1,
        "sync": false
      }
    },
    "size": {
      "value": 2,
      "random": true,
      "anim": {
        "enable": true,
        "speed": 1,
        "size_min": 0.1,
        "sync": false
      }
    },
    "line_linked": {
      "enable": true,
      "distance": 200,
      "color": "#ffffff",
      "opacity": 0.15,
      "width": 0.5
    },
    "move": {
      "enable": true,
      "speed": 0.8,
      "direction": "none",
      "random": false,
      "straight": false,
      "out_mode": "out",
      "bounce": false,
      "attract": {
        "enable": true,
        "rotateX": 400,
        "rotateY": 800
      }
    }
  },
  "interactivity": {
    "detect_on": "canvas",
    "events": {
      "onhover": {
        "enable": true,
        "mode": "grab"
      },
      "onclick": {
        "enable": false
      },
      "resize": true
    },
    "modes": {
      "grab": {
        "distance": 180,
        "line_linked": {
          "opacity": 0.3
        }
      }
    }
  },
  "retina_detect": true
}
