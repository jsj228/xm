<?php
/**
 * Created by PhpStorm.
 * User: Slagga
 * Date: 10/27/2017
 * Time: 12:08 PM
 */

namespace Mapi\Controller;

class MapiController extends \Think\Controller
{
    protected function _empty() {
        send_http_status(404);
        $this->json(['status' => 404, 'message' => '模块不存在！']);
    }

    //auth
    protected function auth($type = 'get')
    {
        //request auth
        if($_SERVER['REQUEST_METHOD'] !== strtoupper($type)){
            $this->json(['status' => 201, 'message' => '无效的请求类型！']);
        }

        //ip auth
        $ip = get_client_ip();
        if(!in_array($ip, $this->ip)){
            $this->json(['status' => 202, 'message' => '无效的IP地址！']);
        }

        //count auth
        $data = S($ip);
        if(!$data){
            $data = ['ip' => $ip, 'count' => 1];
            S($ip, $data, 60);
        } else {
            $data = ['ip' => $ip, 'count' => ++$data['count']];
            S($ip, $data, 60);
        }

        if($data['count'] > 60){
            $this->json(['status' => 203, 'message' => '接口访问超过限制！']);
        }
    }

    //json
    protected function json($data = [])
    {
        exit(json_encode($data));
    }
}

?>