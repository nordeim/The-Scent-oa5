$ curl -Lk https://the-scent.com/ -o current_landing_page.html                
curl -Lk 'https://the-scent.com/index.php?page=product&id=1' -o view_details_product_id-1.html                
curl -Lk 'https://the-scent.com/index.php?page=products' -o shop_products.html                
curl -Lk 'https://the-scent.com/index.php?page=contact' -o contact_page.html                
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=1' -o products_page_1.html                
curl -Lk 'https://the-scent.com/index.php?page=products&page_num=2' -o products_page_2.html          
curl -Lk 'https://the-scent.com/index.php?page=about' -o about_page.html          
curl -Lk 'https://the-scent.com/index.php?page=login' -o login_page.html        
curl -Lk 'https://the-scent.com/index.php?page=register' -o register_page.html 
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 29633    0 29633    0     0   962k      0 --:--:-- --:--:-- --:--:--  997k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  2665k      0 --:--:-- --:--:-- --:--:-- 2729k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4610k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1175k      0 --:--:-- --:--:-- --:--:-- 1222k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4942k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25580    0 25580    0     0  2604k      0 --:--:-- --:--:-- --:--:-- 2775k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1804k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   869k      0 --:--:-- --:--:-- --:--:--  905k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  2169k      0 --:--:-- --:--:-- --:--:-- 2337k

$ cat logs/security.log 
[2025-05-03 12:55:32 UTC] [WARNING] Security-related error detected | Context: {
    "ip": "127.0.0.1",
    "user_id": null,
    "type": "Exception",
    "message": "CSRF token validation failed",
    "file": "/cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php",
    "line": 276,
    "trace": "#0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()\n#1 {main}",
    "context": {
        "url": "/index.php?page=quiz&action=submit",
        "method": "POST",
        "ip": "127.0.0.1",
        "timestamp": "2025-05-03 12:55:32 UTC",
        "user_id": null
    }
}
[2025-05-03 12:55:32 UTC] [WARNING] Potentially security-related exception caught | Context: {
    "ip": "127.0.0.1",
    "user_id": null,
    "type": "Exception",
    "message": "CSRF token validation failed",
    "file": "/cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php",
    "line": 276,
    "trace": "#0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()\n#1 {main}",
    "context": {
        "url": "/index.php?page=quiz&action=submit",
        "method": "POST",
        "ip": "127.0.0.1",
        "timestamp": "2025-05-03 12:55:32 UTC",
        "user_id": null
    }
}

$ cat apache_logs/apache-error.log 
[Sat May 03 20:51:33.315572 2025] [ssl:warn] [pid 653175] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sat May 03 20:51:33.353959 2025] [ssl:warn] [pid 653177] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sat May 03 20:55:32.456594 2025] [proxy_fcgi:error] [pid 653181] [client 127.0.0.1:59400] AH01071: Got error 'PHP message: General error/exception in index.php: CSRF token validation failed Trace: #0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()\n#1 {main}; PHP message: [2025-05-03 12:55:32 UTC] [Exception] CSRF token validation failed in /cdrom/project/The-Scent-oa5/includes/SecurityMiddleware.php on line 276\nStack trace:\n#0 /cdrom/project/The-Scent-oa5/index.php(43): SecurityMiddleware::validateCSRF()\n#1 {main}\nContext: {\n    "url": "/index.php?page=quiz&action=submit",\n    "method": "POST",\n    "ip": "127.0.0.1",\n    "timestamp": "2025-05-03 12:55:32 UTC",\n    "user_id": null\n}', referer: https://the-scent.com/index.php?page=quiz
[Sat May 03 20:58:35.010014 2025] [proxy_fcgi:error] [pid 653181] [client 127.0.0.1:35530] AH01071: Got error 'PHP message: General error/exception in index.php: BaseController::logSecurityEvent(): Argument #2 ($details) must be of type array, null given, called in /cdrom/project/The-Scent-oa5/controllers/ProductController.php on line 157 Trace: #0 /cdrom/project/The-Scent-oa5/controllers/ProductController.php(157): BaseController->logSecurityEvent()\n#1 /cdrom/project/The-Scent-oa5/index.php(66): ProductController->showProductList()\n#2 {main}; PHP message: [2025-05-03 12:58:35 UTC] [TypeError] BaseController::logSecurityEvent(): Argument #2 ($details) must be of type array, null given, called in /cdrom/project/The-Scent-oa5/controllers/ProductController.php on line 157 in /cdrom/project/The-Scent-oa5/controllers/BaseController.php on line 480\nStack trace:\n#0 /cdrom/project/The-Scent-oa5/controllers/ProductController.php(157): BaseController->logSecurityEvent()\n#1 /cdrom/project/The-Scent-oa5/index.php(66): ProductController->showProductList()\n#2 {main}\nContext: {\n    "url": "/index.php?page=products&category=3",\n    "method": "GET",\n    "ip": "127.0.0.1",\n    "timestamp": "2025-05-03 12:58:35 UTC",\n    "user_id": null\n}', referer: https://the-scent.com/index.php?page=products&page_num=2
[Sat May 03 21:01:02.625225 2025] [proxy_fcgi:error] [pid 653183] [client 127.0.0.1:54900] AH01071: Got error 'PHP message: Error fetching quiz results for user ID 6: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'recommended_products' in 'field list'', referer: https://the-scent.com/index.php?page=checkout

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [03/May/2025:20:54:51 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:52 +0800] "GET /index.php?page=faq HTTP/1.1" 200 3921 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:53 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:53 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:57 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 3852 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:57 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:57 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:58 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3774 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:59 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:54:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:00 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 3834 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:00 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:01 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:03 +0800] "GET /index.php?page=about HTTP/1.1" 200 5248 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:03 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:03 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:11 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5552 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:11 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:11 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:14 +0800] "GET /index.php?page=about HTTP/1.1" 200 5248 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:14 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:14 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:21 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 5967 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:21 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:21 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [03/May/2025:20:55:28 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [03/May/2025:20:55:32 +0800] "POST /index.php?page=quiz&action=submit HTTP/1.1" 500 5293 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:55:32 +0800] "GET /favicon.ico HTTP/1.1" 200 2318 "https://the-scent.com/index.php?page=quiz&action=submit" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:57:59 +0800] "GET / HTTP/1.1" 200 6701 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:00 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2599 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:00 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:08 +0800] "GET /index.php?page=products HTTP/1.1" 200 7810 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:08 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:08 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:29 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 5700 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:29 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:29 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:35 +0800] "GET /index.php?page=products&category=3 HTTP/1.1" 500 6634 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:58:35 +0800] "GET /favicon.ico HTTP/1.1" 200 977 "https://the-scent.com/index.php?page=products&category=3" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:39 +0800] "GET / HTTP/1.1" 200 8042 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:39 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2599 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:39 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:46 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6851 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:46 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:48 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 980 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1051 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:56 +0800] "GET /index.php HTTP/1.1" 200 8040 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:56 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1051 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:20:59:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:00 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 975 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:00 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1164 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:09 +0800] "GET /index.php?page=cart HTTP/1.1" 200 6839 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:09 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1164 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:09 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:15 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1169 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:15 +0800] "GET /index.php?page=login HTTP/1.1" 200 4558 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1164 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:46 +0800] "POST /index.php?page=login HTTP/1.1" 200 2737 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:46 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 10061 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:00:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:01:02 +0800] "GET /index.php?page=account HTTP/1.1" 200 4700 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:01:02 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1281 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:21:01:02 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

