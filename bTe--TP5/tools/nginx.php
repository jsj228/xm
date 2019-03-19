<?php
//为避免文件被下载使用 PHP 格式，
//为避免文件被查看输出 Hello World,
exit('Hello World!');
?>

nginx.conf
server {
        listen       80 default backlog=1024;
        server_name  localhost www.testcoin.com;

        set $root /var/www/testcoin.com/public;
        root    $root;
        location / {
            index  index.html index.php;
            autoindex  off;
            if (!-e $request_filename) {

                  rewrite ^(.*)$ /index.php?s=$1  last;

                    break;
             }
        }

        error_page   500 502 503 504  /50x.html;
        location = /50x.html {
            root   html;
        }
        location ~ \.php(.*)$  {
            fastcgi_pass   backend;
            fastcgi_index  index.php;
            fastcgi_split_path_info  ^((?U).+\.php)(/?.+)$;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_param  PATH_INFO  $fastcgi_path_info;
            fastcgi_param  PATH_TRANSLATED  $document_root$fastcgi_path_info;
            include        fastcgi_params;
        }
        location ~.*\.(jpg|png|jpeg)$
        {
            root  $root;
        }

       location ~.*\.(js|css)$ {
           root  $root;
       }

    }


log spilt
    log_format main '$remote_addr - $remote_user $time_local "$request" $status $body_bytes_sent [$request_body] "$http_referer" "$http_user_agent" $http_x_forwarded_for';

vhost.conf
        access_log  /home/wwwlogs/access.log main;



Websocket Nginx 配置
server {
    listen 2000;

    ssl on;
    ssl_certificate /etc/ssl/wss.btchkgj.com.crt;
    ssl_certificate_key /etc/ssl/wss.btchkgj.com.key;
    ssl_session_timeout 5m;
    ssl_session_cache shared:SSL:50m;
    ssl_protocols SSLv3 SSLv2 TLSv1 TLSv1.1 TLSv1.2;
    ssl_ciphers ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;

    location /
    {
        proxy_pass http://127.0.0.1:2001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header X-Real-IP $remote_addr;
    }
}