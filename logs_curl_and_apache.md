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
100 29633    0 29633    0     0   990k      0 --:--:-- --:--:-- --:--:--  997k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  2573k      0 --:--:-- --:--:-- --:--:-- 2729k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4520k      0 --:--:-- --:--:-- --:--:-- 4605k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1203k      0 --:--:-- --:--:-- --:--:-- 1222k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4803k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25580    0 25580    0     0  2610k      0 --:--:-- --:--:-- --:--:-- 2775k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1847k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   902k      0 --:--:-- --:--:-- --:--:--  974k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  2183k      0 --:--:-- --:--:-- --:--:-- 2337k

$ cat apache_logs/apache-error.log 
[Sat May 03 12:57:35.208633 2025] [ssl:warn] [pid 631265] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sat May 03 12:57:35.246293 2025] [ssl:warn] [pid 631266] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [03/May/2025:12:59:16 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET / HTTP/1.1" 200 32691 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 31012 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=products HTTP/1.1" 200 50364 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12920 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50364 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28637 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=about HTTP/1.1" 200 17608 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=login HTTP/1.1" 200 15882 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:12:59:43 +0800] "GET /index.php?page=register HTTP/1.1" 200 19740 "-" "curl/8.5.0"
127.0.0.1 - - [03/May/2025:13:01:58 +0800] "GET / HTTP/1.1" 200 8396 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:01:58 +0800] "GET /css/style.css HTTP/1.1" 200 8399 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:01:58 +0800] "GET /js/main.js HTTP/1.1" 200 19371 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:01:58 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2808 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:01:58 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [03/May/2025:13:02:06 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [03/May/2025:13:02:12 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4421 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:12 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:12 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:14 +0800] "GET /index.php?page=faq HTTP/1.1" 200 4132 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:14 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:14 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:15 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 4062 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:20 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 4062 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:20 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:21 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3989 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:21 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:21 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:24 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 4045 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:24 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:25 +0800] "GET /index.php?page=about HTTP/1.1" 200 5459 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:25 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2808 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:26 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:31 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5762 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:31 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:31 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:34 +0800] "GET /index.php HTTP/1.1" 200 6588 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:34 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:34 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [03/May/2025:13:02:41 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [03/May/2025:13:02:47 +0800] "GET /index.php?page=products HTTP/1.1" 200 6681 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:02:59 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 7253 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:00 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:00 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:08 +0800] "GET /index.php HTTP/1.1" 200 6912 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:08 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:08 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:12 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6736 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:12 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:12 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:18 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2854 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:18 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:24 +0800] "GET /index.php?page=products HTTP/1.1" 200 6678 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:24 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:30 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2852 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:30 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1376 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:46 +0800] "GET /index.php HTTP/1.1" 200 6909 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:46 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 3041 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:03:46 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:04:11 +0800] "GET /index.php?page=cart HTTP/1.1" 200 7180 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:04:11 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1376 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:04:11 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:04:46 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1378 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:04:46 +0800] "GET /index.php?page=login HTTP/1.1" 200 4766 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:04:46 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1376 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:04:46 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:05:23 +0800] "POST /index.php?page=login HTTP/1.1" 200 2946 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:05:23 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 10211 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [03/May/2025:13:05:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

