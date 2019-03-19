<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
class Verify extends HomeCommon
{
	public function __construct()
	{
		parent::__construct();
	}

	public function code()
	{
        ob_clean();
		$config['useNoise'] = false;
		$config['length'] = 4;
		$config['codeSet'] = '0123456789';
		$verify = new \Think\Verify($config);
		$verify->entry(1);
	}

	//真实验证
	public function real()
	{
        $moble = input('moble/s');
        $verify = input('verify/s');

		if (!userid()) {
			redirect('/#login');
		}

		if (!check_verify(strtoupper($verify))) {
			$this->error('图形验证码错误!');
		}

		if (!check($moble, 'moble2')) {
			$this->error('短信格式错误！');
		}

		if (Db::name('User')->where(['moble' => $moble])->find()) {
			$this->error('短信已存在！');
		}

        $code = rand(111111, 999999);
        session('real_verify', $code);
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($moble, $title, $message)) {
            $this->success('验证码已发送');
        } else {
            $this->error('验证码发送失败,请重发');
        }
	}
    public function bankseng()
    {
        $moble=input('moble/s');
        $usrr_moble = DB::name('User')->where(array('moble' =>'+'.$moble))->value('moble');


        if (!$usrr_moble) {
            $this->error('手机号没有认证');
        }
        $code = rand(111111, 999999);
        session('WeChatcode', $code);
        session('WeChatmoble', $moble);
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($usrr_moble, $title, $message)) {
            if(MOBILE_CODE == 0){
                return array('status'=>1,'msg'=>'目前是演示模式,请输入'.$code);
//                $this->success('目前是演示模式,请输入'.$code);
            }
            if(MOBILE_CODE == 1){
                $this->success('验证码已发送');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
    }

	//提现
	public function mytx()
	{
	    $uid = userid();
		if (!$uid) {
			return $this->error('请先登录','/#login');
		}

		$moble = Db::name('User')->where(['id' => $uid])->value('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('mytx_verify', $code);
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($moble, $title, $message)) {
            if(MOBILE_CODE == 0){
                return array('code'=>1,'msg'=>'目前是演示模式,请输入'.$code);
            }else{
                 return array('code'=>1,'msg'=>'验证码已发送');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
	}

	//找回密码
	public function moble_findpwd()
	{
		if ($this->request->isPost()) {
			$input = input('post.');
    
			if (!captcha_check(strtoupper($input['verify']))) {
				$this->error('图形验证码错误!');
			}

			if (!check($input['moble'], 'moble')) {
				$this->error('手机号格式错误！');
			}

			$user = Db::name('User')->where(['moble' => $input['dial_code'].$input['moble']])->find();
			if (!$user) {
				$this->error('手机号不存在！');
			}


            $code = rand(111111, 999999);
            session('findpwd', json_encode(['findpwd_verify'=> $code,'moble'=> $input['moble']]));
            $title = config('web_name');
            $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
            if (send_moble($input['dial_code'].$input['moble'], $title, $message)) {
                if(MOBILE_CODE ==0 ){
                    return ['code'=>1,'msg'=>"目前是演示模式,请输入'.$code"];
                    // $this->success('目前是演示模式,请输入'.$code);
                }else{
                    $this->success('短信验证码已发送到你的手机，请查收');
                }
            } else {
                $this->error('验证码发送失败,请重发');
            }
		}
	}

	//找回交易密码
	public function findpaypwd()
	{
		$input = input('post.');
		if (!check_verify(strtoupper($input['verify']))) {
			$this->error('图形验证码错误!');
		}

		if (!check($input['username'], 'username')) {
			$this->error('用户名格式错误！');
		}

		if (!check($input['moble'], 'moble')) {
			$this->error('短信格式错误！');
		}

		$user = Db::name('User')->where(['moble' => $input['moble']])->find();

		if (!$user) {
			$this->error('短信不存在！');
		}

        $code = rand(111111, 999999);
        session('findpwd', json_encode(['findpwd_verify'=> $code,'moble'=> $input['moble']]));
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($input['moble'], $title, $message)) {
            $this->success('验证码已发送');
        } else {
            $this->error('验证码发送失败,请重发');
        }
	}

    //币种转出
	public function myzc()
	{
		if (!userid()) {
			return $this->error('请登录!','/#login');
		}

		$moble = Db::name('User')->where(['id' => userid()])->value('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('myzc_verify', $code);
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($moble, $title, $message)) {
            if(MOBILE_CODE ==0 ){
                return ['code'=>1,'msg'=>'目前是演示模式,请输入'.$code];
                // $this->success('目前是演示模式,请输入'.$code);
            }else{
                return ['status'=>1,'msg'=>'短信验证码已发送到你的手机，请查收'];
                // $this->success('短信验证码已发送到你的手机，请查收');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
	}
	
	//币种转入
	public function myzr()
	{
		if (!userid()) {
			return $this->error('您没有登录请先登录!','/#login');
		}

		$moble = Db::name('User')->where(['id' => userid()])->value('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('myzr_verify', $code);
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($moble, $title, $message)) {
            if(MOBILE_CODE ==0 ){
                $this->success('目前是演示模式,请输入'.$code);
            }else{
                $this->success('短信验证码已发送到你的手机，请查收');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
	}

    //绑定雷达钱包
    public function ldqb()
    {
        if (!userid()) {
            return $this->error('请先登录','/#login');
        }

        $moble = Db::name('User')->where(['id' => userid()])->value('moble');
        if (!$moble) {
            $this->error('你的短信没有认证');
        }

        $code = rand(111111, 999999);
        session('mytx_verify', $code);
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($moble, $title, $message)) {
            if(MOBILE_CODE == 0){
                $this->success('目前是演示模式,请输入'.$code);
            }else{
                $this->success('验证码已发送');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
    }
    //提现
    public function ulogin()
    {
        $moble=input('moble');
        $usrr_moble = Db::name('User')->where(['moble' => '+86'.$moble])->value('moble');
        if (!$usrr_moble) {
            $this->error('手机号没有认证');
        }
        $code = rand(111111, 999999);
        session('login_verify', $code);
        session('login_moble', $moble);
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($usrr_moble, $title, $message)) {
            if(MOBILE_CODE == 0){
                $this->success('目前是演示模式,请输入'.$code);
            }else{
                $this->success('验证码已发送');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
    }
}

?>
