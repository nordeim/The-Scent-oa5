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
100 29633    0 29633    0     0   555k      0 --:--:-- --:--:-- --:--:--  567k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  2713k      0 --:--:-- --:--:-- --:--:-- 3033k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4581k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1206k      0 --:--:-- --:--:-- --:--:-- 1397k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4794k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25580    0 25580    0     0  2574k      0 --:--:-- --:--:-- --:--:-- 2775k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1844k      0 --:--:-- --:--:-- --:--:-- 2051k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   906k      0 --:--:-- --:--:-- --:--:--  974k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  2227k      0 --:--:-- --:--:-- --:--:-- 2337k

$ cat logs/security.log 
[SECURITY] Event: error_show_product_list | Details: {"timestamp":"2025-05-04 02:09:53 UTC","user_id":null,"ip":"127.0.0.1","user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36","request_uri":"\/index.php?page=products&category=11","request_method":"GET"}
[SECURITY] Event: error_show_product_list | Details: {"timestamp":"2025-05-04 02:10:10 UTC","user_id":null,"ip":"127.0.0.1","user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36","request_uri":"\/index.php?page=products&category=11","request_method":"GET"}
[SECURITY] Event: error_show_product_list | Details: {"timestamp":"2025-05-04 02:21:50 UTC","user_id":6,"ip":"127.0.0.1","user_agent":"Mozilla\/5.0 (X11; Linux x86_64) AppleWebKit\/537.36 (KHTML, like Gecko) Chrome\/135.0.0.0 Safari\/537.36","request_uri":"\/index.php?page=products&category=11","request_method":"GET"}

$ cat apache_logs/apache-error.log 
[Sun May 04 10:06:31.871357 2025] [ssl:warn] [pid 667467] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sun May 04 10:06:31.910006 2025] [ssl:warn] [pid 667468] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Sun May 04 10:09:32.180375 2025] [proxy_fcgi:error] [pid 667761] [client 127.0.0.1:39114] AH01071: Got error 'PHP message: Error saving quiz result: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'recommended_products' in 'field list'; PHP message: Redirect URL validation failed after constructing: /index.php?page=quiz&action=results', referer: https://the-scent.com/index.php?page=quiz
[Sun May 04 10:09:41.303993 2025] [proxy_fcgi:error] [pid 667763] [client 127.0.0.1:39256] AH01071: Got error 'PHP message: Error saving quiz result: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'recommended_products' in 'field list'; PHP message: Redirect URL validation failed after constructing: /index.php?page=quiz&action=results', referer: https://the-scent.com/index.php?page=quiz
[Sun May 04 10:09:53.193051 2025] [proxy_fcgi:error] [pid 667760] [client 127.0.0.1:55510] AH01071: Got error 'PHP message: Error loading product list: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'p.p.category_id' in 'where clause'; PHP message: Redirect URL validation failed after constructing: /index.php?page=error', referer: https://the-scent.com/index.php?page=products
[Sun May 04 10:10:10.499912 2025] [proxy_fcgi:error] [pid 667474] [client 127.0.0.1:45112] AH01071: Got error 'PHP message: Error loading product list: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'p.p.category_id' in 'where clause'; PHP message: Redirect URL validation failed after constructing: /index.php?page=error', referer: https://the-scent.com/index.php?page=products
[Sun May 04 10:12:27.503132 2025] [proxy_fcgi:error] [pid 667758] [client 127.0.0.1:47292] AH01071: Got error 'PHP message: Error fetching quiz results for user ID 6: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'recommended_products' in 'field list'', referer: https://the-scent.com/index.php?page=checkout
[Sun May 04 10:13:57.869882 2025] [proxy_fcgi:error] [pid 667760] [client 127.0.0.1:44826] AH01071: Got error 'PHP message: Error fetching quiz results for user ID 6: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'recommended_products' in 'field list'', referer: https://the-scent.com/index.php?page=account
[Sun May 04 10:14:36.788307 2025] [proxy_fcgi:error] [pid 667473] [client 127.0.0.1:38896] AH01071: Got error 'PHP message: Error fetching quiz results for user ID 6: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'recommended_products' in 'field list''
[Sun May 04 10:21:50.564719 2025] [proxy_fcgi:error] [pid 667763] [client 127.0.0.1:55726] AH01071: Got error 'PHP message: Error loading product list: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'p.p.category_id' in 'where clause'; PHP message: Redirect URL validation failed after constructing: /index.php?page=error', referer: https://the-scent.com/index.php?page=products

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [04/May/2025:10:09:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:41 +0800] "POST /index.php?page=quiz&action=submit HTTP/1.1" 302 825 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:41 +0800] "GET / HTTP/1.1" 200 6377 "https://the-scent.com/index.php?page=quiz" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:41 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2599 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:41 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:43 +0800] "GET /index.php?page=products HTTP/1.1" 200 6143 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:43 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:44 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
::1 - - [04/May/2025:10:09:51 +0800] "OPTIONS * HTTP/1.0" 200 126 "-" "Apache/2.4.58 (Ubuntu) OpenSSL/3.0.13 (internal dummy connection)"
127.0.0.1 - - [04/May/2025:10:09:53 +0800] "GET /index.php?page=products&category=11 HTTP/1.1" 302 2490 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:53 +0800] "GET / HTTP/1.1" 200 6544 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:53 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:09:53 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:10:07 +0800] "GET /index.php?page=products HTTP/1.1" 200 6467 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:10:08 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:10:08 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:10:10 +0800] "GET /index.php?page=products&category=11 HTTP/1.1" 302 825 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:10:10 +0800] "GET / HTTP/1.1" 200 6544 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:10:10 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 2599 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:10:10 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:37 +0800] "GET /index.php HTTP/1.1" 200 6701 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:38 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:38 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:43 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 8190 "https://the-scent.com/index.php" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:43 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 934 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:44 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:45 +0800] "POST /index.php?page=cart&action=add HTTP/1.1" 200 980 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:45 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1051 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:52 +0800] "GET /index.php?page=products HTTP/1.1" 200 7805 "https://the-scent.com/index.php?page=product&id=1" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:52 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1051 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:52 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:55 +0800] "GET /index.php?page=cart HTTP/1.1" 200 5052 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:56 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1051 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:56 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:58 +0800] "GET /index.php?page=checkout HTTP/1.1" 302 845 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:58 +0800] "GET /index.php?page=login HTTP/1.1" 200 4555 "https://the-scent.com/index.php?page=cart" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:58 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1051 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:11:58 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:12:20 +0800] "POST /index.php?page=login HTTP/1.1" 200 1396 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:12:20 +0800] "GET /index.php?page=checkout HTTP/1.1" 200 10067 "https://the-scent.com/index.php?page=login" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:12:20 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:12:27 +0800] "GET /index.php?page=account HTTP/1.1" 200 6041 "https://the-scent.com/index.php?page=checkout" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:12:27 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1281 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:12:27 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:13:57 +0800] "GET /index.php?page=account HTTP/1.1" 200 4700 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:13:58 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1281 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:13:58 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:14:36 +0800] "GET /index.php?page=account HTTP/1.1" 200 6041 "-" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:14:36 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:21:47 +0800] "GET /index.php?page=products HTTP/1.1" 200 7803 "https://the-scent.com/index.php?page=account" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:21:48 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1281 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:21:48 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:21:50 +0800] "GET /index.php?page=products&category=11 HTTP/1.1" 302 825 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:21:50 +0800] "GET / HTTP/1.1" 200 6543 "https://the-scent.com/index.php?page=products" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:21:50 +0800] "GET /index.php?page=cart&action=mini HTTP/1.1" 200 1281 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:21:50 +0800] "GET /favicon.ico HTTP/1.1" 200 653 "https://the-scent.com/" "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36"
127.0.0.1 - - [04/May/2025:10:22:10 +0800] "-" 408 1664 "-" "-"

