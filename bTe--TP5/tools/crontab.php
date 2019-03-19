<?php
//为避免文件被下载使用 PHP 格式，
//为避免文件被查看输出 Hello World,
exit('Hello World!');
?>

crontab -e
*       3       1,15    *       *       ~/clearcache.sh
* * * * *   /var/www/xxx/tools/crontab.sh 1>/dev/null 2>>/var/www/xxx/runtime/log/crontab_coin_err.log &
* * * * *   php /var/www/xxx/public/e02b2943b3c6265b12da8f385f7cab75.php 1>/dev/null 2>>/var/www/xxx/runtime/log/crontab_err.log &
0       16      *       *       *       ~/nginx_log.sh


~/clearcache.sh
#!/bin/bash
#####--------清理缓存--------#####

echo "3">/proc/sys/vm/drop_caches


~/crontab.sh
#!/bin/bash

url="http://127.0.0.1"
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/btc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/doge
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/blc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/erc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/ltc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/ejf
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/tcc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/bhc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/bdc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db9/coin/eos
#间隔的秒数，不能大于60
step=12

for (( i = 0; i < 60; i=(i+$step) )); do
curl -k $url/phpinfo.php
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/uicc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/eth
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/etc
sleep $step
done

exit 0


~/nginx_log.sh
#!/bin/bash
#Rotate the Nginx logs to prevent a single logfile from consuming too much disk space.
LOGS_PATH=/home/wwwlogs
YESTERDAY=$(date -d "yesterday" +%Y-%m-%d)
mv ${LOGS_PATH}/access.log ${LOGS_PATH}/access_${YESTERDAY}.log
#mv ${LOGS_PATH}/error.log ${LOGS_PATH}/error_${YESTERDAY}.log
## 向 Nginx 主进程发送 USR1 信号。USR1 信号是重新打开日志文件
kill -USR1 $(cat /usr/local/nginx/logs/nginx.pid)
find /home/wwwlogs -mtime +7 -name access_*.log -print -delete


~/sqlback.sh
#!/bin/sh

#Back Database

mysqldump  -u root -p'u@Q-gYQ.)0s-e8ZJtZbg' --databases weike > /mysql/weike`date +%Y%m%d.%T`.sql
find /mysql/ -mtime +2 -name weike*.sql -print -delete