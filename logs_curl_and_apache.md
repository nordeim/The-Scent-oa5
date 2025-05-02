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
100 29633    0 29633    0     0   827k      0 --:--:-- --:--:-- --:--:--  851k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  2184k      0 --:--:-- --:--:-- --:--:-- 2274k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44394    0 44394    0     0  3603k      0 --:--:-- --:--:-- --:--:-- 3612k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1053k      0 --:--:-- --:--:-- --:--:-- 1086k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 44394    0 44394    0     0  4339k      0 --:--:-- --:--:-- --:--:-- 4817k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 22816    0 22816    0     0  2249k      0 --:--:-- --:--:-- --:--:-- 2475k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1837k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   917k      0 --:--:-- --:--:-- --:--:--  974k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  2205k      0 --:--:-- --:--:-- --:--:-- 2337k

$ cat logs/security.log
[SECURITY] Event: rate_limit_exceeded | Details: {"timestamp":"2025-05-02 00:23:15 UTC","user_id":null,"ip":"127.0.0.1","user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36","request_uri":"\/index.php?page=login","request_method":"POST","action":"login","limit":5,"window":300}

$ cat apache_logs/apache-error.log 
[Fri May 02 08:15:44.197609 2025] [ssl:warn] [pid 581551] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Fri May 02 08:15:44.235693 2025] [ssl:warn] [pid 581552] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Fri May 02 08:23:15.530245 2025] [proxy_fcgi:error] [pid 581813] [client 127.0.0.1:39752] AH01071: Got error 'PHP message: Login failed for email 'abc@def.com' from IP 127.0.0.1: Rate limit exceeded. Please try again later.', referer: https://the-scent.com/index.php?page=login

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [02/May/2025:08:17:30 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:30 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [02/May/2025:08:17:38 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
::1 - - [02/May/2025:08:17:39 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [02/May/2025:08:17:43 +0800] "GET /index.php?page=products HTTP/1.1" 200 7923 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:43 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:43 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:48 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 5500 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:52 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4097 "https://the-scent.com/index.php?page=products&page_num=2" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:52 +0800] "GET /css/style.css HTTP/1.1" 200 8399 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:52 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:52 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:53 +0800] "GET /index.php?page=faq HTTP/1.1" 200 4131 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:53 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:53 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:55 +0800] "GET /index.php?page=shipping HTTP/1.1" 200 4061 "https://the-scent.com/index.php?page=faq" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:55 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:55 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:56 +0800] "GET /index.php?page=order-tracking HTTP/1.1" 200 3983 "https://the-scent.com/index.php?page=shipping" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:56 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:57 +0800] "GET /index.php?page=about HTTP/1.1" 200 5457 "https://the-scent.com/index.php?page=order-tracking" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:58 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2808 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:58 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:59 +0800] "GET /index.php?page=contact HTTP/1.1" 200 4097 "https://the-scent.com/index.php?page=about" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:59 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:17:59 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:01 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 4464 "https://the-scent.com/index.php?page=contact" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:01 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:01 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:03 +0800] "GET /index.php?page=products HTTP/1.1" 200 6258 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:03 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:03 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:05 +0800] "GET /index.php HTTP/1.1" 200 6587 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:05 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:05 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:09 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 6735 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:09 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:09 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:18:29 +0800] "-" 408 1664 "-" "-"
127.0.0.1 - - [02/May/2025:08:18:29 +0800] "-" 408 1664 "-" "-"
127.0.0.1 - - [02/May/2025:08:22:36 +0800] "GET / HTTP/1.1" 200 8395 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:36 +0800] "GET /css/style.css HTTP/1.1" 200 8399 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:36 +0800] "GET /js/main.js HTTP/1.1" 200 19371 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:36 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:36 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:38 +0800] "GET /index.php?page=cart HTTP/1.1" 200 3973 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:38 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:42 +0800] "GET /index.php?page=products HTTP/1.1" 200 6256 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:42 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1143 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:42 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:46 +0800] "GET /index.php?page=product&id=15 HTTP/1.1" 200 6736 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:46 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1467 "https://the-scent.com/index.php?page=product&id=15" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:46 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=15" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:51 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 1189 "https://the-scent.com/index.php?page=product&id=15" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:51 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1258 "https://the-scent.com/index.php?page=product&id=15" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:53 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5398 "https://the-scent.com/index.php?page=product&id=15" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:53 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1258 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:53 +0800] "GET /favicon.ico HTTP/1.1" 200 2318 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:56 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 1054 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:56 +0800] "GET /index.php?page=login HTTP/1.1" 200 4765 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:56 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1258 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [02/May/2025:08:22:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [02/May/2025:08:23:03 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [02/May/2025:08:23:15 +0800] "POST /index.php?page=login HTTP/1.1" 429 1494 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"

