<?php
//为避免文件被下载使用 PHP 格式，
//为避免文件被查看输出 Hello World,
exit('Hello World!');
?>

server configuration
Address	https://amazonaws-china.com/cn/?nc2=h_lg
Username	saloprj@gmail.com
Password	p}e66EhTY{'&kQu-

Link address: 	https://amazonaws-china.com/cn/?nc2=h_lg

WebSocket Server (One):
Instance type			c5.2xlarge

Test Server (One):
Instance type			t2.micro

Currency Server (12):
Instance type			c5.xlarge
ER Currency Version		v3.0.0.0
TCC Currency	Version		v1.0.1.0-61402
DOGE Currency Version	1100000
BTC Currency	Version		150100
ETC Currency	Version		63
ETH Currency	Version		63
LTC Currency	Version		140200
EJF Currency	Version		1000000
BLC Currency	Version		BLC-v1.1.0.build-g-bdb-gcc
BHC Currency	Version		3000200
BDC Currency	Version		2000000
FRC Currency	Version		v1.0.0.1-61402

Backend Server (1):
Instance type			c5.2xlarge

Web Server (3):
Instance type			c5.2xlarge

http://buycoinex.com

Bticoin configuration
bitcoin install
sudo add-apt-repository ppa:bitcoin/bitcoin
sudo apt-get update
sudo apt-get install bitcoind -y
wget https://bitcoin.org/bin/bitcoin-core-0.16.0/bitcoin-0.16.0-x86_64-linux-gnu.tar.gz

bitcoin run and test
bitcoind -rpcbind=0.0.0.0 -conf=/root/.bitcoin/bitcoin.conf -datadir=/root/.bitcoin/datadir -daemon
bitcoin-cli -getinfo  -rpcuser=user -rpcpassword=pass

bitcoin.conf
listen=1
server=1
daemon=1
rpcuser=user
rpcpassword=pass
rpcallowip=172.31.34.12
rpcallowip=172.31.37.76
rpcallowip=127.0.0.1
rpcport=8332
port=8888
Redis configuration
Git reposity
http://www.runoob.com/git/git-server.html

Redis server
git clone https://github.com/antirez/redis.git
git checkout 4.0
make MALLOC=jemalloc
make && make install

PHP Redis Extension
Git clone https://github.com/phpredis/phpredis.git
Phpize
phpize
./configure [--enable-redis-igbinary]
make && make install


Redis.conf
# bind 127.0.0.1
protected-mode no
# requirepass slagga



ETH configuration

sudo apt-get install software-properties-common
sudo add-apt-repository -y ppa:ethereum/ethereum
sudo apt-get update
sudo apt-get install ethereum

./geth --fast --rpc=true --rpcaddr 0.0.0.0 --rpcapi eth,personal,web3



wallet test
$CoinClient = CoinClient('user', 'pass', '192.168.0.22', '22222');
$json = $CoinClient->getinfo();

if (!isset($json['walletversion']) || !$json['walletversion']) {
$this->error('钱包对接失败！');
}

$info = $CoinClient->getinfo();