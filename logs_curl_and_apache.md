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
100 29710    0 29710    0     0   769k      0 --:--:-- --:--:-- --:--:--  784k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28032    0 28032    0     0  2538k      0 --:--:-- --:--:-- --:--:-- 2737k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4436k      0 --:--:-- --:--:-- --:--:-- 4612k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10093    0 10093    0     0  1122k      0 --:--:-- --:--:-- --:--:-- 1232k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4770k      0 --:--:-- --:--:-- --:--:-- 5125k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25657    0 25657    0     0  2667k      0 --:--:-- --:--:-- --:--:-- 2783k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14780    0 14780    0     0  1781k      0 --:--:-- --:--:-- --:--:-- 2061k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13054    0 13054    0     0   797k      0 --:--:-- --:--:-- --:--:--  849k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16834    0 16834    0     0  1932k      0 --:--:-- --:--:-- --:--:-- 2054k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28240    0 28240    0     0  2517k      0 --:--:-- --:--:-- --:--:-- 2757k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13686    0 13686    0     0  1512k      0 --:--:-- --:--:-- --:--:-- 1670k

$ cat logs/error.log 
[09-May-2025 06:41:09 UTC] Tax calculation error: Invalid tax calculation parameters
[09-May-2025 06:41:09 UTC] [2025-05-09 06:41:09 UTC] [E_WARNING (Warning)] Undefined property: TaxController::$pdo in /cdrom/project/The-Scent-oa5/controllers/TaxController.php on line 62
Context: {
    "url": "/index.php?page=checkout&action=calculateTax",
    "method": "POST",
    "ip": "127.0.0.1",
    "timestamp": "2025-05-09 06:41:09 UTC",
    "user_id": 6
}
[09-May-2025 06:41:09 UTC] General error/exception in index.php: Call to a member function prepare() on null Trace: #0 /cdrom/project/The-Scent-oa5/controllers/CheckoutController.php(134): TaxController->getTaxRate()
#1 /cdrom/project/The-Scent-oa5/index.php(102): CheckoutController->calculateTax()
#2 {main}
[09-May-2025 06:41:09 UTC] [2025-05-09 06:41:09 UTC] [Error] Call to a member function prepare() on null in /cdrom/project/The-Scent-oa5/controllers/TaxController.php on line 62
Stack trace:
#0 /cdrom/project/The-Scent-oa5/controllers/CheckoutController.php(134): TaxController->getTaxRate()
#1 /cdrom/project/The-Scent-oa5/index.php(102): CheckoutController->calculateTax()
#2 {main}
Context: {
    "url": "/index.php?page=checkout&action=calculateTax",
    "method": "POST",
    "ip": "127.0.0.1",
    "timestamp": "2025-05-09 06:41:09 UTC",
    "user_id": 6
}
[09-May-2025 06:41:09 UTC] ErrorHandler Warning: Cannot set HTTP 500 status code for exception, headers already sent. Exception: Call to a member function prepare() on null

$ cat apache_logs/apache-error.log 
[Fri May 09 14:37:05.595809 2025] [ssl:warn] [pid 821139] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Fri May 09 14:37:05.678134 2025] [ssl:warn] [pid 821140] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [09/May/2025:14:37:28 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:37:30 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:37:44 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:37:45 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET / HTTP/1.1" 200 32879 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 31195 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET /index.php?page=products HTTP/1.1" 200 50552 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET /index.php?page=contact HTTP/1.1" 200 13108 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50552 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28825 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET /index.php?page=about HTTP/1.1" 200 17796 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:25 +0800] "GET /index.php?page=login HTTP/1.1" 200 16070 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:26 +0800] "GET /index.php?page=register HTTP/1.1" 200 19928 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:26 +0800] "GET /index.php?page=products&category=1 HTTP/1.1" 200 31403 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:38:26 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 16702 "-" "curl/8.5.0"
127.0.0.1 - - [09/May/2025:14:39:46 +0800] "GET / HTTP/1.1" 200 8591 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:39:46 +0800] "GET /css/style.css HTTP/1.1" 200 8399 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:39:46 +0800] "GET /js/main.js HTTP/1.1" 200 20405 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:39:47 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2919 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:39:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [09/May/2025:14:39:54 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
::1 - - [09/May/2025:14:40:05 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [09/May/2025:14:40:19 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 7258 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:19 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2919 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:19 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:23 +0800] "GET /index.php?page=cart HTTP/1.1" 200 4168 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:23 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1254 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:23 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:31 +0800] "GET /index.php?page=login HTTP/1.1" 200 5289 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:31 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1254 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:49 +0800] "POST /index.php?page=login HTTP/1.1" 200 3056 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:49 +0800] "GET /index.php?page=account HTTP/1.1" 200 5198 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:49 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1914 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:40:49 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:41:08 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 12536 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:41:09 +0800] "POST /index.php?page=checkout&action=calculateTax HTTP/1.1" 500 9519 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:41:09 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2238 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [09/May/2025:14:41:09 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
