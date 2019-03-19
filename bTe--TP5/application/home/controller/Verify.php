<?php
namespace app\home\controller;

use think\Db;

class Verify extends Home
{
	public function __construct()
	{
		parent::__construct();
	}
	

	//真实验证
	public function real()
	{
        $moble = input('moble/s');
        $verify = input('verify/s');

		if (!userid()) {
			$this->redirect('/#login');
		}

		if (!captcha_check($verify)) {
			$this->error('图形验证码错误!');
		}

		if (!check($moble, 'moble2')) {
			$this->error('短信格式错误！');
		}
		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('短信已存在！');
		}

        $code = rand(111111, 999999);
        session('real_verify', $code);
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";

        if(MOBILE_CODE ==0 ) {
            $this->success('目前是演示模式,请输入' . $code);
        }
        $send_rs = send_moble($moble,config('web_name'),$message);
        if($send_rs){
            if($send_rs['code']){
                $this->success('验证码已发送');
            }else{
                $this->error($send_rs['msg']);
            }
        }else{
            $this->error('验证码发送失败,请重发');
        }


	}

	//提现
	public function mytx()
	{
		if (!userid()) {
			$this->error('请先登录');
		}

		$moble = Db::name('User')->where(array('id' => userid()))->value('moble');
		if (!$moble) {
			$this->error('你的手机没有认证');
		}

        $code = rand(111111, 999999);
        session('mytx_verify', $code);
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";

        if(MOBILE_CODE ==0 ) {
            $this->success('目前是演示模式,请输入' . $code);
        }
        $send_rs = send_moble($moble,config('web_name'),$message);
        if($send_rs){
            if($send_rs['code']){
                $this->success('验证码已发送');
            }else{
                $this->error($send_rs['msg']);
            }
        }else{
            $this->error('验证码发送失败,请重发');
        }

	}

	//找回密码
	public function moble_findpwd()
	{
		if (IS_POST) {
			$input_val = input('post.');
			if (!captcha_check($input_val['verify'],'find_passwd')) {
				$this->error('图形验证码错误!');
			}

			if (!check($input_val['moble'], 'moble2')) {
				$this->error('手机格式错误！');
			}

			$user = Db::name('User')->where(array('moble' => $input_val['dial_code'].$input_val['moble']))->value('id');
			$user_tow = Db::name('User')->where(array('moble' => $input_val['moble']))->value('id');
			if (!$user && !$user_tow) {
				$this->error('手机号不存在！');
			}

            $code = rand(111111, 999999);
            session('findpwd', json_encode(['findpwd_verify'=> $code,'moble'=> $input_val['moble']]));
            $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";

            if(MOBILE_CODE ==0 ) {
                $this->success('目前是演示模式,请输入' . $code);
            }
            $send_rs = send_moble($input_val['dial_code'].$input_val['moble'], config('web_name'), $message);
            if($send_rs){
                if($send_rs['code']){
                    $this->success('验证码已发送');
                }else{
                    $this->error($send_rs['msg']);
                }
            }else{
                $this->error('验证码发送失败,请重发');
            }


		}
	}

    //登录发手机验证码
    public function moble_login_code()
    {
        if (IS_POST) {
            $moble = input('moble/s', '');

            if(!$moble){
                $this->error('用户名不空！');

            }
            if (check($moble, 'email')) {
                $user = Db::name('User')->where(array('email' => $moble))->find();
            }

            if (!isset($user) && check($moble, 'moble')) {
                $user = Db::name('User')->where(array('moble' => $moble))->find();
            }

            if (!isset($user)) {
                $user = Db::name('User')->where(array('username' => $moble))->find();

            }

            if (!isset($user)) {
                $this->error('用户不存在！');
            }

            if(!$user['moble']){
                $this->error('绑定手机号不存在！请联系客服！');
            }

            if (!check($user['moble'], 'moble3')) {
                $this->error('绑定的手机号格式错误！请联系客服！');
            }


            $code = rand(111111, 999999);

            session('moble_login_code', json_encode(['moble_login_code_verify'=> $code,'moble'=> $user['moble']]));
            $message = "您的验证码是{$code}，在5分钟内输入有效。如非本人操作请忽略此短信。";

            if(MOBILE_CODE ==0 ) {
                $this->success('目前是演示模式,请输入' . $code);
            }
            $send_rs = send_moble($user['moble'], config('web_name'), $message);
            if($send_rs){
                if($send_rs['code']){
                    $this->success('验证码已发送');
                }else{
                    $this->error($send_rs['msg']);
                }
            }else{
                $this->error('验证码发送失败,请重发');
            }

        }
    }

	//找回交易密码
	public function findpaypwd()
	{
        if (IS_POST) {
            $input_val = input('post.');
            if (!captcha_check($input_val['verify'])) {
                $this->error('图形验证码错误!');
            }

            if (!check($input_val['username'], 'username')) {
                $this->error('用户名格式错误！');
            }

            if (!check($input_val['moble'], 'moble')) {
                $this->error('短信格式错误！');
            }

            $user = Db::name('User')->where(array('moble' => $input_val['moble']))->find();

            if (!$user) {
                $this->error('手机不存在！');
            }

            $code = rand(111111, 999999);
            session('findpaypwd_verify', $code);
            $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";

            if(MOBILE_CODE ==0 ) {
                $this->success('目前是演示模式,请输入' . $code);
            }
            $send_rs = send_moble($input_val['moble'], config('web_name'), $message);
            if($send_rs){
                if($send_rs['code']){
                    $this->success('验证码已发送');
                }else{
                    $this->error($send_rs['msg']);
                }
            }else{
                $this->error('验证码发送失败,请重发');
            }

        }
	}

    //币种转出
	public function myzc()
	{
		if (!userid()) {
			$this->error('您没有登录请先登录!');
		}

		$moble = Db::name('User')->where(array('id' => userid()))->value('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('myzc_verify', $code);
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";

        if(MOBILE_CODE ==0 ) {
            $this->success('目前是演示模式,请输入' . $code);
        }
        $send_rs = send_moble($moble,config('web_name'), $message);
        if($send_rs){
            if($send_rs['code']){
                $this->success('验证码已发送');
            }else{
                $this->error($send_rs['msg']);
            }
        }else{
            $this->error('验证码发送失败,请重发');
        }

	}
	
	//币种转入
	public function myzr()
	{
		if (!userid()) {
			$this->error('您没有登录请先登录!');
		}

		$moble = Db::name('User')->where(array('id' => userid()))->value('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('myzr_verify', $code);
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";

        if(MOBILE_CODE ==0 ) {
            $this->success('目前是演示模式,请输入' . $code);
        }
        $send_rs = send_moble($moble, config('web_name'), $message);
        if($send_rs){
            if($send_rs['code']){
                $this->success('验证码已发送');
            }else{
                $this->error($send_rs['msg']);
            }
        }else{
            $this->error('验证码发送失败,请重发');
        }

	}

	//异步生成令牌
	public function getToken(){
        $str = token_generate();
        if($str){
            $this->success('',  null, $str);
        }else{
            $this->getToken();
        }
    }



}

?>