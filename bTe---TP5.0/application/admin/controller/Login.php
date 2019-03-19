<?php
namespace app\admin\controller;
use app\admin\controller\AdminCommon;
use think\Controller;
use think\Db;
use think\Request;
use think\Cache;
use think\sessoin;

class Login extends Controller
{
    public function adminlogin()
    {
       return $this->fetch();
    }
    public function index()
    {
        $username = strval(input('username'));
        $password = strval(input('password'));
        $code = strval(input('logincode'));
        $verify = input('verify');

        if ($this->request->isPost()) {
            if (!captcha_check($verify)) {
               return array('status'=>0,'msg'=>'验证码输入错误！');
            }

            $admin = DB::name('Admin')->where(array('username' => $username))->find();
            if ($admin['password'] != md5($password)) {
                $this->error('用户名或密码错误！');
            } else {
                $is_code = DB::name('AdminCode')->where(array('admin_id' => $admin['id'], 'code' => $code, 'status' => 0, 'expiration_time' => ['egt', time()]))->find();
                if (!empty($is_code)) {
                    DB::name('AdminCode')->where(array('id' => $is_code['id']))->update(['status' => 1]);
                    DB::name('AdminLog')->insert([
                        'userid' => $admin['id'],
                        'type' => '登录',
                        'remark' => '后台用户名登陆',
                        'addtime' => time(),
                        'addip' => $this->request->ip(),
                        'addr' => $this->request->ip(),
                        'status' => 1
                    ]);
                    session('admin_id', $admin['id']);
                    Cache::store('redis')->set('5df4g5dsh8shnfsf', $admin['id']);
                    session('admin_username', $admin['username']);
                    session('admin_password', $admin['password']);
                    return array('status'=>1,'msg'=>'登陆成功','url'=>url('user/index'));

                } else {
                     return array('status'=>0,'msg'=>'手机验证码输入错误！');
                }
            }

        }else {
            if (session('admin_id')) {
               return redirect(url('user/index'));
            } else {
                return $this->fetch('adminlogin');
            }
        }

    }

    public function send_login_code()
    {
        //发送公共短信
        $mainmoble = config('MAINMOBLE');
        //短信验证码
        $username = strval(input('post.username'));
        $admin = DB::name('Admin')->where(array('username' => $username))->find();
        if (empty($admin)) die(json_encode(array('code' => 400, 'msg' => '未查询到用户', 'data' => array())));
        $code_data = DB::name('AdminCode')->where(array('admin_id' => $admin['id'], 'status' => 0, 'create_time' => ['gt', 0], 'expiration_time' => ['gt', time() + 5 * 60]))->find();
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
                'ip_addr' =>$this->request->ip(),
                'status' => 0,
            );
            if (!DB::name('AdminCode')->insert($data)) {
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
        Cache::rm('5df4g5dsh8shnfsf');

        return $this->redirect('/admin/Login');
    }

    public function lockScreen()
    {
        if (!$this->request->isPost()) {
           return $this->fetch();
        } else {
            $pass = trim(input('post.pass'));

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
            $this->error('登录已经失效,请重新登录...',request()->module(). '/login');
        }

        if (session('LockScreenTime') < 0) {
            session(null);
            $this->error('密码错误过多,请重新登录...',request()->module() . '/login');
        }

        $pass = trim(input('post.pass'));

        if ($pass == session('LockScreen')) {
            session('LockScreen', null);
            $this->success('解锁成功', request()->module() . '/index');
        }

        $admin = DB::name('Admin')->where(array('id' => session('admin_id')))->find();

        if ($admin['password'] == md5($pass)) {
            session('LockScreen', null);
            $this->success('解锁成功', request()->module() . '/index');
        }

        session('LockScreenTime', session('LockScreenTime') - 1);
        $this->error('用户名或密码错误！');
    }
}

?>