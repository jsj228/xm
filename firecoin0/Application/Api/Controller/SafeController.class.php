<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/2
 * Time: 13:37
 */
namespace Api\Controller;

use function Sodium\crypto_pwhash_scryptsalsa208sha256;
use Think\Controller;
//use Home\Controller\HomeController;


class SafeController extends Controller
{
    public $str = "hjwevfgjh1245fgfgdgkr.jh";

    //模拟页面
    public function index()
    {
        $this->display();
    }

    //注册接口
    public function register()
    {

        $uname = trim(I('uname'));
        $uid = M('user')->where(['username' =>$uname])->getField('id');
        if (!$uid) echo json(['code' => 0, 'msg' => '请先登录']);

        $name = trim(I('name/s'));
        $ips = trim(I('ips/s'));
        if (!$name) $this->error('请填写备注名称');

        $valid_name = M('api_key')->where(['name' => $name, 'status' => 0])->find();
        if ($valid_name) $this->error('该备注名称已存在');

        $apikey_count = M('api_key')->where(['uid' => $uid, 'status' => 0])->count();
        if ($apikey_count >= 5) $this->error('最多可设置5个');

        $username = M('user')->where(['id' => $uid])->getField('username');
        $time = time();

        $username = $username >> 5;
        $AccessKey = $this->replace(md5(sha1(md5($username) . $time)));
        $SecretKey = $this->replace(md5(sha1(time() . md5($username) . $this->str)));

        $data = [
            'uid' => $uid,
            'name' => $name,
            'ips' => $ips,
            'access_key' => $AccessKey,
            'secret_key' => $SecretKey,
            'add_time' => $time
        ];
        $res = M('api_key')->add($data);
        if ($res) $this->success('创建成功');
        else $this->success('创建失败');
    }

    //添加分割符
    private function replace($str)
    {
        $str = substr_replace($str, '-', 8, 0);
        $str = substr_replace($str, '-', 13, 0);
        $str = substr_replace($str, '-', 18, 0);
        $str = substr_replace($str, '-', 23, 0);
        return $str;
    }

    //验签
    public static function valid_sign($input, $mandatory, $optional = [])
    {
        if (trim($input['method']) != ACTION_NAME) return ['code' => 0, 'msg' => 'Method error'];
        //校验参数数量和参数名
        $i = 0;
        if ($optional) {
            foreach ($optional as $v) if (isset($input[$v])) $i++;
        }
        if (count($input) != (count($mandatory)) + $i) return ['code' => 0, 'msg' => 'Incorrect number of parameters'];
        foreach ($mandatory as $v) if (!isset($input[$v])) return ['code' => 0, 'msg' => 'Lack of parameters'];

        $apikey = M('api_key')->where(['access_key' => $input['accesskey'], 'status' => 0])->field('uid,secret_key,ips')->find();
        session('userId', $apikey['uid']);

        //验证绑定IP地址
        if ($apikey['ips']) {
            $ip = $_SERVER["REMOTE_ADDR"];
            $ips_arr = explode(',', $apikey['ips']);
            if (!in_array($ip, $ips_arr)) return ['code' => 0, 'msg' => 'Request IP address error'];
        }

        $sign = $input['sign'];
        $reqTime = $input['reqTime'];
        unset($input['sign']);
        unset($input['reqTime']);

        $input = self::ASCII($input);
        if (isset($input['errCode'])) return ['code' => 0, 'msg' => 'Parameter sorting error'];

        $valid_sign = hash_hmac('md5',$input,sha1($apikey['secret_key']));

        if ($sign == $valid_sign) return ['code' => 1, 'msg' => 'Parameter validation success', 'uid' => $apikey['uid']];
        else return ['code' => 0, 'msg' => 'Parameter validation failed', 'sign' => $valid_sign];
    }

    //按照ASCII值排序
    private static function ASCII($params = array())
    {
        if (!empty($params)) {
            $p = ksort($params);
            if ($p) {
                $str = '';
                foreach ($params as $k => $val) {
                    $str .= $k . '=' . $val . '&';
                }
                $strs = rtrim($str, '&');
                return $strs;
            }
        }
        return ['errCode' => 1, 'msg' => '参数错误'];
    }

//    public function test()
//    {
//        header("Content-Type: text/html;charset=utf-8");
//
//        $se = '48939bbc-8d49-402b-b731-adadf2ea9628';
//        $se_key = sha1($se);
//
//        $canshu = 'accesskey=6d8f62fd-3086-46e3-a0ba-c66a929c24e2&method=getAccountInfo';
//
//        $sign = hash_hmac('md5',$canshu, $se_key);
//
//        echo $sign;
//        die;
//    }
}