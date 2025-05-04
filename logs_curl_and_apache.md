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
100 29633    0 29633    0     0   782k      0 --:--:-- --:--:-- --:--:--  803k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  2673k      0 --:--:-- --:--:-- --:--:-- 2729k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4542k      0 --:--:-- --:--:-- --:--:-- 4605k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1226k      0 --:--:-- --:--:-- --:--:-- 1397k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4757k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25580    0 25580    0     0  2663k      0 --:--:-- --:--:-- --:--:-- 2775k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1868k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   905k      0 --:--:-- --:--:-- --:--:--  974k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  2221k      0 --:--:-- --:--:-- --:--:-- 2337k

$ cat apache_logs/apache-error.log 
[Sun May 04 16:13:55.053272 2025] [ssl:warn] [pid 674746] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sun May 04 16:13:55.090612 2025] [ssl:warn] [pid 674747] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [04/May/2025:16:14:12 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:13 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:18 +0800] "GET /includes/clear_apcu_cache.php HTTP/1.1" 200 2689 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:24 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET / HTTP/1.1" 200 32691 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 31012 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=products HTTP/1.1" 200 50364 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12920 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50364 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28637 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=about HTTP/1.1" 200 17608 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=login HTTP/1.1" 200 15882 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:14:49 +0800] "GET /index.php?page=register HTTP/1.1" 200 19740 "-" "curl/8.5.0"
127.0.0.1 - - [04/May/2025:16:15:44 +0800] "GET / HTTP/1.1" 200 8394 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:44 +0800] "GET /css/style.css HTTP/1.1" 200 8399 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:44 +0800] "GET /js/main.js HTTP/1.1" 200 19371 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:44 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:44 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:50 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 5087 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:50 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:50 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [04/May/2025:16:15:51 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [04/May/2025:16:15:57 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 5087 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:57 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:15:57 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [04/May/2025:16:16:05 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [04/May/2025:16:16:11 +0800] "GET /index.php HTTP/1.1" 200 8251 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:12 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:12 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:21 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1513 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:21 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:23 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5262 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:23 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:23 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:26 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1054 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:26 +0800] "GET /index.php?page=login HTTP/1.1" 200 4765 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:26 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:27 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:50 +0800] "POST /index.php?page=login HTTP/1.1" 200 2946 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:50 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 10267 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:16:51 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:17:04 +0800] "GET /index.php HTTP/1.1" 200 6907 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:17:04 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 3156 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:16:17:04 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

