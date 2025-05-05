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
100 29633    0 29633    0     0   962k      0 --:--:-- --:--:-- --:--:--  997k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 27955    0 27955    0     0  2081k      0 --:--:-- --:--:-- --:--:-- 2099k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  1129k      0 --:--:-- --:--:-- --:--:-- 1151k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 10016    0 10016    0     0  1059k      0 --:--:-- --:--:-- --:--:-- 1086k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 47158    0 47158    0     0  4617k      0 --:--:-- --:--:-- --:--:-- 5116k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 25580    0 25580    0     0  2123k      0 --:--:-- --:--:-- --:--:-- 2270k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 14703    0 14703    0     0  1727k      0 --:--:-- --:--:-- --:--:-- 1794k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 12977    0 12977    0     0   907k      0 --:--:-- --:--:-- --:--:--  974k
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100 16757    0 16757    0     0  1904k      0 --:--:-- --:--:-- --:--:-- 2045k

$ ls -l logs/
total 0

$ cat apache_logs/apache-error.log 
[Mon May 05 12:02:37.567020 2025] [ssl:warn] [pid 692194] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)
[Mon May 05 12:02:37.641816 2025] [ssl:warn] [pid 692195] AH01906: the-scent.com:443:0 server certificate is a CA certificate (BasicConstraints: CA == TRUE !?)

$ cat apache_logs/apache-access.log 
127.0.0.1 - - [05/May/2025:12:03:16 +0800] "GET /includes/reset_cache.php HTTP/1.1" 200 2251 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:21 +0800] "GET /includes/clear_apcu_cache.php HTTP/1.1" 200 2689 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:26 +0800] "GET /includes/clear_apcu.php HTTP/1.1" 200 2669 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET / HTTP/1.1" 200 32482 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=product&id=1 HTTP/1.1" 200 30803 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=products HTTP/1.1" 200 50155 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=contact HTTP/1.1" 200 12711 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=products&page_num=1 HTTP/1.1" 200 50155 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=products&page_num=2 HTTP/1.1" 200 28428 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=about HTTP/1.1" 200 17399 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=login HTTP/1.1" 200 15673 "-" "curl/8.5.0"
127.0.0.1 - - [05/May/2025:12:03:39 +0800] "GET /index.php?page=register HTTP/1.1" 200 19531 "-" "curl/8.5.0"

