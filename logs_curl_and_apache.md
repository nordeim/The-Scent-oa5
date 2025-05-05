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
100 29633    0 29633    0     0   883k      0 --:--:-- --:--:-- --:--:--  904k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  1939k      0 --:--:-- --:--:-- --:--:-- 2099k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  3770k      0 --:--:-- --:--:-- --:--:-- 3837k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1341k      0 --:--:-- --:--:-- --:--:-- 1397k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4602k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25580    0 25580    0     0  2535k      0 --:--:-- --:--:-- --:--:-- 2775k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1477k      0 --:--:-- --:--:-- --:--:-- 1595k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   931k      0 --:--:-- --:--:-- --:--:--  974k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  1954k      0 --:--:-- --:--:-- --:--:-- 2045k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28163    0 28163    0     0  3092k      0 --:--:-- --:--:-- --:--:-- 3437k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13609    0 13609    0     0  1195k      0 --:--:-- --:--:-- --:--:-- 1329k

$ cat apache_logs/apache-error.log 
[Mon May 05 18:39:00.347923 2025] [ssl:warn] [pid 713899] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Mon May 05 18:39:00.384809 2025] [ssl:warn] [pid 713900] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ cat apache_logs/apache-access.log 
127.0.0.1 - - [05/May/2025:18:40:01 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:01 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET / HTTP/1.1" 200 32482 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30803 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=products HTTP/1.1" 200 50155 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12711 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50155 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28428 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=about HTTP/1.1" 200 17399 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=login HTTP/1.1" 200 15673 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=register HTTP/1.1" 200 19531 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=products&category=1 HTTP/1.1" 200 31011 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:18:40:32 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 16305 "-" "curl/8.5.0"

