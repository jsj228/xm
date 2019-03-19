#!/usr/bin/php
<?php
define('__ROOT__', __DIR__.'/');
define('__MODEL__', __ROOT__.'model/');
define('__CONF__', __ROOT__.'conf/');
define('__CONTROLLER__', __ROOT__.'controller/');
define('__COMMON__', __ROOT__.'comment/');
require_once __CONF__.'conf.php';
class WebsocketTest {
    public $server;
    public function __construct() {
    $this->server = new swoole_websocket_server("0.0.0.0", 443,SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
   $this->server->addlistener('0.0.0.0', 4000, SWOOLE_SOCK_TCP);
    $this->server->set(array(
    'ssl_cert_file' => __CONF__."wss.huocoin.com_bundle.crt",
    'ssl_key_file' => __CONF__.'wss.huocoin.com.key',
    'worker_num' => 8,
    'max_request' => 10000,
    'max_conn' => 50000,
    'dispatch_mode' => 2,
    'debug_mode'=> 1,
    'daemonize' => true,
    'task_ipc_mode'=>3,
    //'heartbeat_check_time' => 30,
   // 'heartbeat_idle_time' => 60,
));
        $this->server->on('open', function (swoole_websocket_server $server, $request) {
           echo "server: handshake success with fd{$request->fd}\n";
        });
        $this->server->on('message', function (swoole_websocket_server $server, $frame) {
            //echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            //$server->push($frame->fd, "this is server");
            $data = json_decode($frame->data,true);
            $encrypted = $data['encrypted'];
            $token = $data['token'];
            if($token && $encrypted){
                $privateKey = openssl_pkey_get_private('-----BEGIN RSA PRIVATE KEY-----
MIICXwIBAAKBgQC8yguaHUfg70u+ktyjn8WVsUOWa/omxw3PwvxbUioW6rLxdSRw
IxRif1j6ZsuUBsizf/YlUYNJjXpU0P+3tFZZK6X2b/lCuYeb22KnlfZQTX9bsomM
dGBZrr7GPjLFioMMZV91ljB03HJjXPT0yLQ5SEkBs/RrG+gPa8rVx/+QzwIDAQAB
AoGBALVJu+xpU72/bNf7RV7mrSD7ETEgTR3xpeStlBHJi9tn0yAk4jHArfGL4pDK
J5HlFdOw/FiHtu/pqOBLtlPdG9BrlqsI9lNevbhnhKxfS7Bhvgm4GYLwE8nZu9F5
7DlPnniecAcqbPjgAqYKd4jyaoXMu5kCm3WYqgLB9E/SojHhAkEA7hkzx5Lkzsc5
Jl3utMxpMJH1rpJZnvqKdBqoNbTDmt4ZnLnvRTVqtJGa46N6cYU04pxOcDQgUcvr
/1DjJ3sSnwJBAMr7w7iUhvDP64PWlNSmnHd+376QSm7DgPuCiFWeiGs3pzyTpvy5
LSjPwD17fiHIk5EqheIcARzXwbLYYxtCg9ECQQC7sKY+tq8jyaxlcDqRz2lEXmr7
WGbJidrGt5TN8VcYp+gswe258ufQu88Lj30gK8+Kq4ixroWjtUquE0ddggklAkEA
lu5r0yUFzawdCHQXSKP2tlft0QoDfqb6kom2DEwMTgUc4ks8ILEkpjMMU+sul7pI
F+oRkVaXcTXxPqXt04g68QJBAMj3eBT1hF2P6rG1sYqyTtTuzapkUUj+IKu4gD24
QwFK88DOiwb9nPjr7nmhsSWIw8fpTKjzLk8bXyFoL5Mt73Y=
-----END RSA PRIVATE KEY-----');
                $realPW = '';
                $ok = openssl_private_decrypt(base64_decode($encrypted), $realPW, $privateKey, OPENSSL_PKCS1_PADDING);
                if(md5($encrypted) != $token){
                    return $this->server->push($frame->fd,json_encode(array('sign'=>'stop')));	
                }
            }else{
                return $this->server->push($frame->fd,json_encode(array('sign'=>'stop')));
            }
            $param = json_decode($realPW,true);
	    //预防sql 注入
	    $param = array_map(function($val){
       		 $str = preg_replace('/\s++/', '', $val);
       		 $str = preg_replace('/[\n\r\t;]/', '', $str);
       		 return $str;
   	    }, $param);
            //添加用户验证
            require_once __CONTROLLER__.$param['c'].'.php';
            //控制器
            $controller = new $param['c']($param,1,CONFIG);
            //方法
            $m = $param['m'];
            $data = $controller -> $m($param['d']);
            $server->push($frame->fd, json_encode($data));
        });

        $this->server->on('close', function ($ser, $fd) {
                echo "client {$fd} closed\n";
        });
        $this->server->on('request', function ($request, $response) {
                // 接收http请求从get获取message参数的值，给用户推送
                // $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
            foreach ($this->server->connections as $fd) {
               @$this->server->push($fd, $request->post['key_redis']);
            }
            $response->end(true);
        });
        $this->server->start();
    }
}
new WebsocketTest();
