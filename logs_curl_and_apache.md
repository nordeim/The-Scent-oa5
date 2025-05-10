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
100 29710    0 29710    0     0   842k      0 --:--:-- --:--:-- --:--:--  853k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28032    0 28032    0     0  1832k      0 --:--:-- --:--:-- --:--:-- 1955k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4026k      0 --:--:-- --:--:-- --:--:-- 4193k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10093    0 10093    0     0  1224k      0 --:--:-- --:--:-- --:--:-- 1408k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4764k      0 --:--:-- --:--:-- --:--:-- 5125k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25657    0 25657    0     0  2623k      0 --:--:-- --:--:-- --:--:-- 2783k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14780    0 14780    0     0  1862k      0 --:--:-- --:--:-- --:--:-- 2061k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13054    0 13054    0     0   781k      0 --:--:-- --:--:-- --:--:--  796k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16834    0 16834    0     0  1928k      0 --:--:-- --:--:-- --:--:-- 2054k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28240    0 28240    0     0  2644k      0 --:--:-- --:--:-- --:--:-- 2757k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13686    0 13686    0     0  1587k      0 --:--:-- --:--:-- --:--:-- 1670k

$ cat apache_logs/apache-error.log 
[Sat May 10 09:49:54.732312 2025] [ssl:warn] [pid 876120] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sat May 10 09:49:54.769771 2025] [ssl:warn] [pid 876121] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [10/May/2025:09:50:14 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:09:50:19 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET / HTTP/1.1" 200 32559 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30875 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=products HTTP/1.1" 200 50232 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12788 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50232 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28505 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=about HTTP/1.1" 200 17476 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=login HTTP/1.1" 200 15750 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=register HTTP/1.1" 200 19608 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=products&category=1 HTTP/1.1" 200 31083 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:11:33 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 16382 "-" "curl/8.5.0"
127.0.0.1 - - [10/May/2025:10:13:50 +0800] "GET / HTTP/1.1" 200 8272 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:13:50 +0800] "GET /css/style.css HTTP/1.1" 200 8399 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:13:50 +0800] "GET /js/main.js HTTP/1.1" 200 20877 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:13:51 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:13:51 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:13:58 +0800] "GET /index.php?page=login HTTP/1.1" 200 4970 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:13:58 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:13:58 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [10/May/2025:10:13:59 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
::1 - - [10/May/2025:10:14:06 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [10/May/2025:10:14:18 +0800] "POST /index.php?page=login HTTP/1.1" 200 2736 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:14:18 +0800] "GET /index.php?page=account HTTP/1.1" 200 5172 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:14:18 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1594 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:14:18 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:15:06 +0800] "GET /index.php?page=cart HTTP/1.1" 200 7017 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:15:07 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1594 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:15:07 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:15:58 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 10872 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:15:59 +0800] "POST /index.php?page=checkout&action=calculateTax HTTP/1.1" 200 954 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:15:59 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1594 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:15:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:16:19 +0800] "-" 408 1664 "-" "-"
127.0.0.1 - - [10/May/2025:10:16:43 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 10872 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [10/May/2025:10:16:43 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

