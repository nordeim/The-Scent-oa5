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
100 29649    0 29649    0     0   658k      0 --:--:-- --:--:-- --:--:--  673k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27944    0 27944    0     0  2302k      0 --:--:-- --:--:-- --:--:-- 2480k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44381    0 44381    0     0  3791k      0 --:--:-- --:--:-- --:--:-- 3940k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0   360k      0 --:--:-- --:--:-- --:--:--  362k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44381    0 44381    0     0  4512k      0 --:--:-- --:--:-- --:--:-- 4815k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22803    0 22803    0     0  2260k      0 --:--:-- --:--:-- --:--:-- 2474k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1798k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   931k      0 --:--:-- --:--:-- --:--:--  974k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  2165k      0 --:--:-- --:--:-- --:--:-- 2337k

$ cat logs/security.log 
[SECURITY] Event: rate_limit_exceeded | Details: {"timestamp":"2025-04-29 11:46:43 UTC","user_id":null,"ip":"127.0.0.1","user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36","request_uri":"\/index.php?page=login","request_method":"POST","action":"login","limit":5,"window":300}

$ cat apache_logs/apache-error.log 
[Tue Apr 29 19:43:54.433308 2025] [ssl:warn] [pid 528970] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Tue Apr 29 19:43:54.471239 2025] [ssl:warn] [pid 528971] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Tue Apr 29 19:46:43.310723 2025] [proxy_fcgi:error] [pid 529140] [client 127.0.0.1:37920] AH01071: Got error 'PHP message: Login failed for email 'abc@def.com' from IP 127.0.0.1: Rate limit exceeded. Please try again later.', referer: https://the-scent.com/index.php?page=login

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos' 
127.0.0.1 - - [29/Apr/2025:19:44:08 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET / HTTP/1.1" 200 32658 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30953 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=products HTTP/1.1" 200 47494 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12920 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 47494 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 25804 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=about HTTP/1.1" 200 17608 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=login HTTP/1.1" 200 15882 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:44:33 +0800] "GET /index.php?page=register HTTP/1.1" 200 19740 "-" "curl/8.5.0"
127.0.0.1 - - [29/Apr/2025:19:45:03 +0800] "GET / HTTP/1.1" 200 8398 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:03 +0800] "GET /css/style.css HTTP/1.1" 200 8319 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:03 +0800] "GET /js/main.js HTTP/1.1" 200 19368 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:03 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:11 +0800] "GET /index.php?page=contact HTTP/1.1" 200 5762 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:11 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:12 +0800] "GET /index.php?page=faq HTTP/1.1" 200 4131 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:12 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:13 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 4061 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:13 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:15 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3983 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:15 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:17 +0800] "GET /index.php?page=privacy HTTP/1.1" 200 4043 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:17 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:19 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4097 "https://the-scent.com/index.php?page=privacy" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:19 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:20 +0800] "GET /index.php?page=about HTTP/1.1" 200 5459 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:24 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 4464 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:24 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:26 +0800] "GET /index.php?page=products HTTP/1.1" 200 6261 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:26 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:35 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 7170 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:36 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:42 +0800] "GET /index.php HTTP/1.1" 200 6914 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:42 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:47 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6743 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:47 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:52 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1513 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:45:52 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart&action=add" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:01 +0800] "GET / HTTP/1.1" 200 8253 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:01 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:09 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5709 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:09 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:14 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1054 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:14 +0800] "GET /index.php?page=login HTTP/1.1" 200 4765 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:14 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:43 +0800] "POST /index.php?page=login HTTP/1.1" 429 2835 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:43 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:55 +0800] "GET / HTTP/1.1" 200 6912 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [29/Apr/2025:19:46:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

