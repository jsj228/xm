<?php

namespace Home\Controller;

class VerifyController extends HomeController
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
        $moble = I('moble/s');
        $verify = I('verify/s');

		if (!userid()) {
			redirect('/#login');
		}

		if (!check_verify(strtoupper($verify))) {
			$this->error('图形验证码错误!');
		}

		if (!check($moble, 'moble2')) {
			$this->error('短信格式错误！');
		}

		if (M('User')->where(array('moble' => $moble))->find()) {
			$this->error('短信已存在！');
		}

        $code = rand(111111, 999999);
        session('real_verify', $code);
        $title = C('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($moble, $title, $message)) {
            $this->success('验证码已发送');
        } else {
            $this->error('验证码发送失败,请重发');
        }
	}


	//提现
	public function mytx()
	{
		if (!userid()) {
			$this->error('请先登录');
		}

		$moble = M('User')->where(array('id' => userid()))->getField('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('mytx_verify', $code);
        $title = C('web_name');
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

	//找回密码
	public function moble_findpwd()
	{
		if (IS_POST) {
			$input = I('post./a');

			if (!check_verify(strtoupper($input['verify']))) {
				$this->error('图形验证码错误!');
			}

			if (!check($input['moble'], 'moble2')) {
				$this->error('手机号格式错误！');
			}

			$user = M('User')->where(array('moble' => $input['dial_code'].$input['moble']))->find();
			if (!$user) {
				$this->error('手机号不存在！');
			}

            $code = rand(111111, 999999);
            session('findpwd', json_encode(['findpwd_verify'=> $code,'moble'=> $input['moble']]));
            $title = C('web_name');
            $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
            if (send_moble($input['dial_code'].$input['moble'], $title, $message)) {
                if(MOBILE_CODE ==0 ){
                    $this->success('目前是演示模式,请输入'.$code);
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
		$input = I('post./a');
		if (!check_verify(strtoupper($input['verify']))) {
			$this->error('图形验证码错误!');
		}

		if (!check($input['username'], 'username')) {
			$this->error('用户名格式错误！');
		}

		if (!check($input['moble'], 'moble')) {
			$this->error('短信格式错误！');
		}

		$user = M('User')->where(array('moble' => $input['moble']))->find();

		if (!$user) {
			$this->error('短信不存在！');
		}

        $code = rand(111111, 999999);
        session('findpwd', json_encode(['findpwd_verify'=> $code,'moble'=> $input['moble']]));
        $title = C('web_name');
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
			$this->error('您没有登录请先登录!');
		}

		$moble = M('User')->where(array('id' => userid()))->getField('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('myzc_verify', $code);
        $title = C('web_name');
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
	
	//币种转入
	public function myzr()
	{
		if (!userid()) {
			$this->error('您没有登录请先登录!');
		}

		$moble = M('User')->where(array('id' => userid()))->getField('moble');
		if (!$moble) {
			$this->error('你的短信没有认证');
		}

        $code = rand(111111, 999999);
        session('myzr_verify', $code);
        $title = C('web_name');
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
            $this->error('请先登录');
        }

        $moble = M('User')->where(array('id' => userid()))->getField('moble');
        if (!$moble) {
            $this->error('你的短信没有认证');
        }

        $code = rand(111111, 999999);
        session('mytx_verify', $code);
        $title = C('web_name');
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
    public function ulogin()
    {
        $moble=I('moble/s');
        $usrr_moble = M('User')->where(array('username' =>$moble))->getField('moble');
        if (!$usrr_moble) {
            $this->error('手机号没有认证');
        }
        $code = rand(111111, 999999);
        session('login_verify', $code);
        session('login_moble', $moble);
        $title = C('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($usrr_moble, $title, $message)) {
            if(MOBILE_CODE == 0){
                $this->success('目前是演示模式,请输入'.$code);
            }
            if(MOBILE_CODE == 1){
                $this->success('验证码已发送');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
    }
    public function bankseng()
    {
        $moble=I('moble/s');
        if (!is_array('+',$moble)){
            $moble='+'.$moble;
        }
        $usrr_moble = M('User')->where(array('moble' =>$moble))->getField('moble');
        if (!$usrr_moble) {
            $this->error('手机号没有认证');
        }
        $code = rand(111111, 999999);
        session('WeChatcode', $code);
        session('WeChatmoble', $moble);
        $title = C('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if (send_moble($usrr_moble, $title, $message)) {
            if(MOBILE_CODE == 0){
                $this->success('目前是演示模式,请输入'.$code);
            }
            if(MOBILE_CODE == 1){
                $this->success('验证码已发送');
            }
        } else {
            $this->error('验证码发送失败,请重发');
        }
    }
    //阿里云短信
    public function sendSms()
    {
         // header("Content-type:text/html;charset=utf-8");    
        $mobile=I('moble/s');
        $code = rand(10000, 99999);
        $res = sendSms($mobile,$code);
        if($res['msg'] == 'OK'){
            session('login_verify', $code);
            if(MOBILE_CODE == 0){
                 $this->success('目前是演示模式,请输入'.$code);
             }
            if(MOBILE_CODE == 1){

                    $this->success('验证码已发送');
             }

        }else{
             $this->error('发送失败');
        }
    }

}

?>
