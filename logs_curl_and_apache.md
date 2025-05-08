$ curl -Lk https://the-scent.com/ -o current_landing_page.html                
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html                
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html                
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html                
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html                
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html          
curl -Lk 'https://the-scent.com/index.php?page=about' -o about_page.html          
curl -Lk 'https://the-scent.com/index.php?page=login' -o login_page.html        
curl -Lk 'https://the-scent.com/index.php?page=register' -o register_page.html 
curl -Lk 'https://the-scent.com/index.php?page=products&category=1' -o products_category=1.html
curl -Lk 'https://the-scent.com/index.php?page=quiz' -o quiz.html
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 29710    0 29710    0     0   612k      0 --:--:-- --:--:-- --:--:--  617k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28032    0 28032    0     0  2616k      0 --:--:-- --:--:-- --:--:-- 2737k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4458k      0 --:--:-- --:--:-- --:--:-- 4612k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10093    0 10093    0     0  1231k      0 --:--:-- --:--:-- --:--:-- 1408k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4782k      0 --:--:-- --:--:-- --:--:-- 5125k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25657    0 25657    0     0  2628k      0 --:--:-- --:--:-- --:--:-- 2783k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14780    0 14780    0     0  1757k      0 --:--:-- --:--:-- --:--:-- 1804k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13054    0 13054    0     0   787k      0 --:--:-- --:--:-- --:--:--  796k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16834    0 16834    0     0  2212k      0 --:--:-- --:--:-- --:--:-- 2348k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28240    0 28240    0     0  2835k      0 --:--:-- --:--:-- --:--:-- 3064k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13686    0 13686    0     0  1569k      0 --:--:-- --:--:-- --:--:-- 1670k

$ cat apache_logs/apache-error.log 
[Thu May 08 20:03:18.258012 2025] [ssl:warn] [pid 787355] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu May 08 20:03:18.294625 2025] [ssl:warn] [pid 787356] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ cat apache_logs/apache-access.log 
127.0.0.1 - - [08/May/2025:20:03:37 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:03:39 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:03:45 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:03:52 +0800] "GET /includes/clear_apcu_cache.php HTTP/1.1" 200 2689 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:08 +0800] "GET / HTTP/1.1" 200 32879 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:08 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 31195 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:08 +0800] "GET /index.php?page=products HTTP/1.1" 200 50552 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:08 +0800] "GET /index.php?page=contact HTTP/1.1" 200 13108 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:08 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50552 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:08 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28825 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:08 +0800] "GET /index.php?page=about HTTP/1.1" 200 17796 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:09 +0800] "GET /index.php?page=login HTTP/1.1" 200 16070 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:09 +0800] "GET /index.php?page=register HTTP/1.1" 200 19928 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:09 +0800] "GET /index.php?page=products&category=1 HTTP/1.1" 200 31403 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:04:09 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 16702 "-" "curl/8.5.0"

---
$ cat logs/error.log 
[08-May-2025 12:10:40 UTC] Checkout Info: Found 1 distinct item types for User ID: 1
[08-May-2025 12:10:40 UTC] Tax calculation error: Invalid tax calculation parameters
[08-May-2025 12:10:40 UTC] Checkout processing error: User 1 - SQLSTATE[42S22]: Column not found: 1054 Unknown column 'subtotal' in 'field list'
[08-May-2025 12:14:15 UTC] General error/exception in index.php: CSRF token validation failed Trace: #0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()
#1 {main}
[08-May-2025 12:14:15 UTC] [2025-05-08 12:14:15 UTC] [Exception] CSRF token validation failed in /cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php on line 276
Stack trace:
#0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()
#1 {main}
Context: {
    "url": "/index.php?page=checkout&action=calculateTax",
    "method": "POST",
    "ip": "127.0.0.1",
    "timestamp": "2025-05-08 12:14:15 UTC",
    "user_id": 6
}

$ cat logs/security.log 
[2025-05-08 12:14:15 UTC] [WARNING] Security-related error detected | Context: {
    "ip": "127.0.0.1",
    "user_id": 6,
    "type": "Exception",
    "message": "CSRF token validation failed",
    "file": "/cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php",
    "line": 276,
    "trace": "#0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()\n#1 {main}",
    "context": {
        "url": "/index.php?page=checkout&action=calculateTax",
        "method": "POST",
        "ip": "127.0.0.1",
        "timestamp": "2025-05-08 12:14:15 UTC",
        "user_id": 6
    }
}
[2025-05-08 12:14:15 UTC] [WARNING] Potentially security-related exception caught | Context: {
    "ip": "127.0.0.1",
    "user_id": 6,
    "type": "Exception",
    "message": "CSRF token validation failed",
    "file": "/cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php",
    "line": 276,
    "trace": "#0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()\n#1 {main}",
    "context": {
        "url": "/index.php?page=checkout&action=calculateTax",
        "method": "POST",
        "ip": "127.0.0.1",
        "timestamp": "2025-05-08 12:14:15 UTC",
        "user_id": 6
    }
}

$ cat apache_logs/apache-error.log 
[Thu May 08 20:09:26.718340 2025] [ssl:warn] [pid 788029] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu May 08 20:09:26.758688 2025] [ssl:warn] [pid 788030] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [08/May/2025:20:09:45 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:09:49 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:09:55 +0800] "GET /includes/clear_apcu_cache.php HTTP/1.1" 200 2689 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:20:10:34 +0800] "GET /stripe_test_v4.php HTTP/1.1" 200 7097 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:10:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/stripe_test_v4.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:10:40 +0800] "POST /index.php?page=checkout&action=processCheckout HTTP/1.1" 500 935 "https://the-scent.com/stripe_test_v4.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:11:03 +0800] "GET /stripe_test_v4.php HTTP/1.1" 200 5613 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:11:03 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/stripe_test_v4.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:12:11 +0800] "GET /stripe_test_v4.php HTTP/1.1" 200 6954 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:12:12 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/stripe_test_v4.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:12:19 +0800] "GET /stripe_test_v4.php HTTP/1.1" 200 5613 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:12:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/stripe_test_v4.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:06 +0800] "GET / HTTP/1.1" 200 8123 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:06 +0800] "GET /css/style.css HTTP/1.1" 200 8399 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:06 +0800] "GET /js/main.js HTTP/1.1" 200 20276 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:07 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1157 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:07 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:11 +0800] "GET /index.php?page=account HTTP/1.1" 200 4874 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:12 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1157 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:12 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:36 +0800] "GET /index.php?page=account HTTP/1.1" 200 5198 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:36 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1157 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:36 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:39 +0800] "GET /index.php?page=logout HTTP/1.1" 302 857 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:39 +0800] "GET /index.php?page=login&loggedout=1 HTTP/1.1" 200 4788 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:39 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=login&loggedout=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:39 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login&loggedout=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:58 +0800] "POST /index.php?page=login HTTP/1.1" 200 2736 "https://the-scent.com/index.php?page=login&loggedout=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:58 +0800] "GET /index.php?page=account HTTP/1.1" 200 4876 "https://the-scent.com/index.php?page=login&loggedout=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:58 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1594 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:13:58 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:05 +0800] "GET /index.php?page=cart HTTP/1.1" 200 7030 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:06 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1594 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:06 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:12 +0800] "POST /index.php?page=cart&action=update HTTP/1.1" 200 1266 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:12 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1594 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:14 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 10549 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:15 +0800] "POST /index.php?page=checkout&action=calculateTax HTTP/1.1" 500 4970 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 3259 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [08/May/2025:20:14:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

