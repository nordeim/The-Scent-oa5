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
100 29633    0 29633    0     0   917k      0 --:--:-- --:--:-- --:--:--  933k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  2005k      0 --:--:-- --:--:-- --:--:-- 2099k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44394    0 44394    0     0  3102k      0 --:--:-- --:--:-- --:--:-- 3334k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1038k      0 --:--:-- --:--:-- --:--:-- 1086k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44394    0 44394    0     0  4104k      0 --:--:-- --:--:-- --:--:-- 4335k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22816    0 22816    0     0  2284k      0 --:--:-- --:--:-- --:--:-- 2475k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1845k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   865k      0 --:--:-- --:--:-- --:--:--  905k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  2230k      0 --:--:-- --:--:-- --:--:-- 2337k

$ cat logs/security.log 
[SECURITY] Event: rate_limit_exceeded | Details: {"timestamp":"2025-05-01 13:21:37 UTC","user_id":null,"ip":"127.0.0.1","user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36","request_uri":"\/index.php?page=login","request_method":"POST","action":"login","limit":5,"window":300}

$ cat apache_logs/apache-error.log 
[Thu May 01 21:18:31.539089 2025] [ssl:warn] [pid 569714] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu May 01 21:18:31.574712 2025] [ssl:warn] [pid 569715] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu May 01 21:21:37.950395 2025] [proxy_fcgi:error] [pid 569720] [client 127.0.0.1:58842] AH01071: Got error 'PHP message: Login failed for email 'abc@def.com' from IP 127.0.0.1: Rate limit exceeded. Please try again later.', referer: https://the-scent.com/index.php?page=login

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [01/May/2025:21:18:48 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:18:50 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:04 +0800] "GET / HTTP/1.1" 200 32691 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:04 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 31012 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:04 +0800] "GET /index.php?page=products HTTP/1.1" 200 47599 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:04 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12920 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:04 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47599 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:04 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25795 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:05 +0800] "GET /index.php?page=about HTTP/1.1" 200 17608 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:05 +0800] "GET /index.php?page=login HTTP/1.1" 200 15882 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:19:05 +0800] "GET /index.php?page=register HTTP/1.1" 200 19740 "-" "curl/8.5.0"
127.0.0.1 - - [01/May/2025:21:20:01 +0800] "GET / HTTP/1.1" 200 8398 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:01 +0800] "GET /css/style.css HTTP/1.1" 200 8395 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:01 +0800] "GET /js/main.js HTTP/1.1" 200 19371 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:01 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2808 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:01 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [01/May/2025:21:20:09 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
::1 - - [01/May/2025:21:20:10 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [01/May/2025:21:20:10 +0800] "GET /index.php?page=products HTTP/1.1" 200 6584 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:10 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:11 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:15 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 4464 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:15 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:16 +0800] "GET /index.php?page=about HTTP/1.1" 200 5461 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:16 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:16 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:20 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4100 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:20 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:22 +0800] "GET /index.php HTTP/1.1" 200 6590 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:22 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2808 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:22 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:28 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 7058 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:28 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:28 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:35 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 2854 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:35 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:36 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5388 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:36 +0800] "GET /js/main.js HTTP/1.1" 200 19371 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:36 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:36 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:47 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1378 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:47 +0800] "GET /index.php?page=login HTTP/1.1" 200 4768 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1260 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:20:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [01/May/2025:21:21:37 +0800] "POST /index.php?page=login HTTP/1.1" 429 2835 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

