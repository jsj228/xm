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
#    curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/frc   
    curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/eth
    curl -k $url/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php/Queue0xfef9b906ff28a016c33ed6b058bb2c4a93292439/qianbaob8c3b3d94512472db8/coin/etc
    sleep $step
done

exit 0
