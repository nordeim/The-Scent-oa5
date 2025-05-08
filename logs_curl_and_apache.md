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
100 29710    0 29710    0     0   770k      0 --:--:-- --:--:-- --:--:--  784k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28032    0 28032    0     0  2059k      0 --:--:-- --:--:-- --:--:-- 2105k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4164k      0 --:--:-- --:--:-- --:--:-- 4612k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10093    0 10093    0     0  1193k      0 --:--:-- --:--:-- --:--:-- 1232k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47235    0 47235    0     0  4394k      0 --:--:-- --:--:-- --:--:-- 4612k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25657    0 25657    0     0  2394k      0 --:--:-- --:--:-- --:--:-- 2505k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14780    0 14780    0     0  1700k      0 --:--:-- --:--:-- --:--:-- 1804k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13054    0 13054    0     0   914k      0 --:--:-- --:--:-- --:--:--  980k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16834    0 16834    0     0  1944k      0 --:--:-- --:--:-- --:--:-- 2054k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 28240    0 28240    0     0  2950k      0 --:--:-- --:--:-- --:--:-- 3064k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 13686    0 13686    0     0  1410k      0 --:--:-- --:--:-- --:--:-- 1485k

$ cat apache_logs/apache-error.log 
[Thu May 08 11:16:12.892149 2025] [ssl:warn] [pid 775305] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Thu May 08 11:16:12.929819 2025] [ssl:warn] [pid 775306] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ tail -100 apache_logs/apache-access.log | egrep -v 'GET \/images|GET \/videos'
127.0.0.1 - - [08/May/2025:11:16:35 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:16:40 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:16:45 +0800] "GET /includes/clear_apcu_cache.php HTTP/1.1" 200 2689 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET / HTTP/1.1" 200 32879 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 31195 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=products HTTP/1.1" 200 50552 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=contact HTTP/1.1" 200 13108 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50552 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28825 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=about HTTP/1.1" 200 17796 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=login HTTP/1.1" 200 16070 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=register HTTP/1.1" 200 19928 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=products&category=1 HTTP/1.1" 200 31403 "-" "curl/8.5.0"
127.0.0.1 - - [08/May/2025:11:17:01 +0800] "GET /index.php?page=quiz HTTP/1.1" 200 16702 "-" "curl/8.5.0"

