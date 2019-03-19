/*** Btcd1 Website ***/
#!/bin/bash

curl -k http://120.77.70.15/socket/bitcoin/coin/btc
curl -k http://120.77.70.15/socket/bitcoin/coin/blc
curl -k http://120.77.70.15/socket/bitcoin/coin/doge
curl -k http://120.77.70.15/socket/bitcoin/coin/er
curl -k http://120.77.70.15/socket/bitcoin/coin/bcc

#间隔的秒数，不能大于60
step=2

for (( i = 0; i < 60; i=(i+step) )); do
        curl -k http://120.77.70.15/socket/ethereum/coin/eth
        curl -k http://120.77.70.15/socket/ethereum/coin/etc
        sleep $step
done

exit 0


/*** Babycoin Website ***/
#!/bin/bash

url="http://127.0.0.1"
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/btc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/doge
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/blc
curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db7/coin/er

#间隔的秒数，不能大于60
step=3

for (( i = 0; i < 60; i=(i+$step) )); do
    curl -k $url/phpinfo.php
    curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/eth
    curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/etc
    sleep $step
done

exit 0


/*** Crontab 定时任务 ***/
* * * * * ~/crontab.sh
* * * * * php /home/wwwroot/babycoin/e02b2943b3c6265b12da8f385f7cab75.php


/*** Clear Cache 清理缓存区 ***/
sync
echo 3 > /proc/sys/vm/drop_caches