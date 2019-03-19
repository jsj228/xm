<?php
//为避免文件被下载使用 PHP 格式，
//为避免文件被查看输出 Hello World,
exit('Hello World!');
?>

<html>
<head>
    <title>Something Need.</title>
</head>
<body>

$CoinClient = CoinClient('user', 'pass', '192.168.0.22', '22222');
$json = $CoinClient->getinfo();

if (!isset($json['version']) || !$json['version']) {
$this->error('钱包对接失败！');
}

$info = $CoinClient->getinfo();
dd($info);

listen=1
server=1
daemon=1
rpcuser=user
rpcpassword=pass
rpcallowip=127.0.0.1
rpcport=22222
port=8888

</body>
</html>


<script type="text/javascript">
    // 假设服务端ip为127.0.0.1
    ws = new WebSocket("wss://wss.btchkgj.com:2122/first/two@555");
    ws.onopen = function() {
        ws.send('{"type":"pong"}');
        alert("给服务端发送一个字符串：tom");
    };
    ws.onmessage = function(e) {
        alert("收到服务端的消息：" + e.data);
    };
</script>