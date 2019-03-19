<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2018/11/2
 * Time: 13:37
 */


class Robot_SafeController extends Ctrl_Base
{
    protected $_auth = 0;
    public $str = "hjwevfgjh1245fgfgdgkr.jh";

    function init()
    {
        parent::init();
        $this->mo = new Orm_Base();
    }

    //模拟页面
    public function indexAction()
    {

    }

    //注册接口
    public function registerAction()
    {
        $this->_ajax_islogin();
        $name = $_POST['name'];
        $ips = $_POST['ips'];
        $uid = $this->mCurUser['uid'];

        if(!$name) $this->ajax('请填写备注名称');

        if($valid_name = $this->mo->table('api_key')->where(['name'=>$name,'status'=>0])->fRow()) $this->ajax('该备注名称已存在');

        if($apikey_count = $this->mo->table('api_key')->where(['uid' => $uid, 'status' => 0])->fOne("count(id)")>=5) $this->ajax('最多可设置5个');

        $username = $this->mo->where("id=$uid")->fOne("name");
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
        $this->mo->table = 'api_key';
        if(!$res = $this->mo->insert($data)){
            $this->ajax('创建失败',0,$this->mo->getLastSql());
        }

        $this->ajax('创建成功',1);
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
//        if (trim($input['method']) != ACTION_NAME) return ['code' => 0, 'msg' => 'Method error'];
        //校验参数数量和参数名
        $i = 0;
        if ($optional) {
            foreach ($optional as $v) if (isset($input[$v])) $i++;
        }
        if (count($input) != (count($mandatory)) + $i) return ['code' => 0, 'msg' => 'Incorrect number of parameters'];
        foreach ($mandatory as $v) if (!isset($input[$v])) return ['code' => 0, 'msg' => 'Lack of parameters'];

        $apikey = Orm_Base::getInstance()->table('api_key')->where(['access_key' => $input['accesskey'], 'status' => 0])->field('uid,secret_key,ips')->fRow();

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

        if ($sign != $valid_sign) return ['code' => 0, 'msg' => 'Parameter validation failed', 'sign' => $valid_sign];

        return ['code' => 1, 'msg' => 'Parameter validation success', 'uid' => $apikey['uid']];
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