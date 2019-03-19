<?php

namespace Admin\Controller;

class LoginController extends \Think\Controller
{
    public function adminlogin()
    {
        $this->display();
    }
    public function index()
    {
        $username = strval(I('username'));
        $password = strval(I('password'));
        $code = strval(I('logincode'));
        $verify = I('verify');

        if (IS_POST) {
            if (!check_verify($verify)) {
                $this->error('验证码输入错误！');
            }

            $admin = M('Admin')->where(array('username' => $username))->find();
            if ($admin['password'] != md5($password)) {
                $this->error('用户名或密码错误！');
            } else {
                $is_code = M('AdminCode')->where(array('admin_id' => $admin['id'], 'code' => $code, 'status' => 0, 'expiration_time' => ['egt', time()]))->find();
                if (!empty($is_code)) {
                    M('AdminCode')->where(array('id' => $is_code['id']))->save(['status' => 1]);
                    M('AdminLog')->add([
                        'userid' => $admin['id'],
                        'type' => '登录',
                        'remark' => '后台用户名登陆',
                        'addtime' => time(),
                        'addip' => get_client_ip(),
                        'addr' => get_city_ip(get_client_ip()),
                        'status' => 1
                    ]);
                    session('admin_id', $admin['id']);
                    S('5df4g5dsh8shnfsf', $admin['id']);
                    session('admin_username', $admin['username']);
                    session('admin_password', $admin['password']);
                    $this->success('登陆成功!', U('User/index'));

                } else {
                    $this->error('手机验证码输入错误！');
                }
            }

        }else {
            if (session('admin_id')) {
                $this->redirect(__MODULE__ . '/User/index');
            } else {
                $this->redirect(__MODULE__ . '/Login/adminlogin');
            }
        }

    }

    public function send_login_code()
    {
        //发送公共短信
        $mainmoble = C('MAINMOBLE');
        //短信验证码
        $username = strval(I('post.username'));
        $admin = M('Admin')->where(array('username' => $username))->find();
        if (empty($admin)) die(json_encode(array('code' => 400, 'msg' => '未查询到用户', 'data' => array())));
        $code_data = M('AdminCode')->where(array('admin_id' => $admin['id'], 'status' => 0, 'create_time' => ['gt', 0], 'expiration_time' => ['gt', time() + 5 * 60]))->find();
        if (!empty($code_data)) {
            if(MOBILE_CODE == 0){
                die(json_encode(array('code' => 200, 'msg' => '演示模式验证码： '.$code_data['code'].' ！', 'data' => array())));
            }else{
                send_moble($mainmoble, '管理员登录验证', '管理员：' . $admin["username"] . '；昵称：' . $admin["nickname"] . '，登录验证码为：' . $code_data['code'] . ';验证码30分钟失效！');
                die(json_encode(array('code' => 200, 'msg' => '请联系负责人获取验证码，有效期为30分钟！', 'data' => array())));
            }

        } else {
            $code = self::get_code();
            $data = array(
                'id' => 0,
                'admin_id' => $admin['id'],
                'code' => $code,
                'create_time' => time(),
                'expiration_time' => time() + 30 * 60,
                'ip_addr' => get_client_ip(0, true),
                'status' => 0,
            );
            if (!M('AdminCode')->add($data)) {
                die(json_encode(array('code' => 401, 'msg' => '数据添加失败', 'data' => array())));
            }
            if(MOBILE_CODE == 0){
                die(json_encode(array('code' => 200, 'msg' => '演示模式验证码： '.$code.' ！', 'data' => array())));
            }else{
                send_moble($mainmoble, '管理员登录验证', '管理员：' . $admin["username"] . '；昵称：' . $admin["nickname"] . '，登录验证码为：' . $code . ';验证码30分钟失效！');
                die(json_encode(array('code' => 200, 'msg' => '请联系负责人获取验证码，有效期为30分钟！', 'data' => array())));
            //发送短信存储数据,对比验证码
            }
        }
    }

    static function get_code()
    {
        $code = '';
        $code_str = '2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,i,j,k,m,n,p,q,r,s,t,u,v,w,x,y,z,A,B,C,D,E,F,G,H,K,L,M,N,P,Q,R,S,T,U,V,W,X,Y,Z';
        $len = rand(5, 8);
        $code_arr = explode(',', $code_str);
        for ($i = 1; $i <= $len; $i++) {
            $code .= $code_arr[rand(0, count($code_arr) - 1)];
        }
        return $code;
    }

    public function loginout()
    {
        session(null);
        S('5df4g5dsh8shnfsf', null);
        $this->redirect('Login/index');
    }

    public function lockScreen()
    {
        if (!IS_POST) {
            $this->display();
        } else {
            $pass = trim(I('post.pass'));

            if ($pass) {
                session('LockScreen', $pass);
                session('LockScreenTime', 3);
                $this->success('锁屏成功,正在跳转中...');
            } else {
                $this->error('请输入一个锁屏密码');
            }
        }
    }

    public function unlock()
    {
        if (!session('admin_id')) {
            session(null);
            $this->error('登录已经失效,请重新登录...', __MODULE__ . '/login');
        }

        if (session('LockScreenTime') < 0) {
            session(null);
            $this->error('密码错误过多,请重新登录...', __MODULE__ . '/login');
        }

        $pass = trim(I('post.pass'));

        if ($pass == session('LockScreen')) {
            session('LockScreen', null);
            $this->success('解锁成功', __MODULE__ . '/index');
        }

        $admin = M('Admin')->where(array('id' => session('admin_id')))->find();

        if ($admin['password'] == md5($pass)) {
            session('LockScreen', null);
            $this->success('解锁成功', __MODULE__ . '/index');
        }

        session('LockScreenTime', session('LockScreenTime') - 1);
        $this->error('用户名或密码错误！');
    }
}

?>