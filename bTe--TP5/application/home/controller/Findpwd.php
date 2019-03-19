<?php
namespace app\home\controller;

use think\Db;

class Findpwd extends Home
{

	public function check_moble(){
        $moble = input('moble/s', 0);
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}

		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
		}
		$this->success('');
	}

	public function check_pwdmoble(){
        $moble = input('moble/s', 0);
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		
		if (!Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机不存在！');
		}
		
		$this->success('');
	}
	
	public function real()
	{
        $moble = input('moble/s', 0);
        $verify = input('verify/s');
		if (!captcha_check($verify)) {
			$this->error('图形验证码错误!');
		}

		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}

		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
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

	public function paypassword(){
		if (!session('reguserId')) {
			$this->redirect('/#login');
		}
		return $this->fetch();
	}
	
	public function info()
	{
		if (!session('reguserId')) {
			$this->redirect('/#login');
		}
		
		$user = Db::name('User')->where(array('id' => session('reguserId')))->find();
		if(!$user){
			$this->error('请先注册');
		}
		if($user['regaward']==0){
			if(config('reg_award')==1 && config('reg_award_num')>0){
				Db::name('UserCoin')->where(array('id' => session('reguserId')))->setInc(config('reg_award_coin'),config('reg_award_num'));
				Db::name('User')->where(array('id' => session('reguserId')))->update(array('regaward'=>1));
			}
		}	

		session('userId', $user['id']);
		session('userName', $user['username']);
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function findpwd()
	{
		if (IS_POST) {
			$input_val = input('post.');

			$findpwd = json_decode(session('findpwd'));

            if (!check($input_val['moble'], 'moble2')) {
                $this->error('手机格式错误！');
            }
			if ($input_val['moble'] != $findpwd->moble) {
				$this->error('当前手机号与验证码不匹配');
			}
            $user = Db::name('User')->where(['moble' => $input_val['dial_code'] . $input_val['moble']])->value('id');
            $user_tow = Db::name('User')->where(['moble' => $input_val['moble']])->value('id');
            if (!$user && !$user_tow) {
                $this->error('手机号不存在！');
            }
            
            if (!check($input_val['moble_verify'], 'd')) {
                $this->error('手机验证码格式错误！');
            }

            if ($input_val['moble_verify'] != $findpwd->findpwd_verify) {
                $this->error('手机验证码错误！');
            }

            //老用户会话中储存 手机号，新用户储存 +86 手机号
            if ($user) {
                session("findpaypwdmoble", $input_val['dial_code'] . $input_val['moble']);
            } else {
                session("findpaypwdmoble", $input_val['moble']);
            }

            $this->success('验证成功');
		} else {
			return $this->fetch();
		}
	}

	public function findpwdconfirm(){
		if(!session('findpaypwdmoble')){
			$this->redirect('/');
		}
		
		return $this->fetch();
	}
	
	public function password_up(){
        $password = input('password/s');
        $repassword = input('repassword/s');

		if(!session('findpaypwdmoble')){
			$this->error('请返回第一步重新操作！');
		}
		
		if (!check($password, 'password')) {
			$this->error('新交易密码格式错误！');
		}
		
		if (!check($repassword, 'password')) {
			$this->error('确认密码格式错误！');
		}
		
		
		if ($password != $repassword) {
			$this->error('确认新密码错误！');
		}
		
		$user = Db::name('User')->where(array('moble' => session('findpaypwdmoble')))->find();
		if(!$user){
			$this->error('不存在该手机');
		}

		if($user['password']==md5($password)){
			$this->error('交易密码不能和登录密码一样');
		}
		

		$rs= Db::table('weike_user')->where(array('moble' => $user['moble']))->update(array('paypassword' => md5($password)));

		if (false !==$rs) {
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
	}
	
	public function findpwdinfo(){
		
		if(!session('findpaypwdmoble')){
			$this->redirect('/');
		}
		session('findpaypwdmoble',"");
		return $this->fetch();
	}
	
	public function findpaypwd()
	{
		if (IS_POST) {
			$input_val = input('post.');

			if (!check($input_val['username'], 'username')) {
				$this->error('用户名格式错误！');
			}

			if (!check($input_val['moble'], 'moble')) {
				$this->error('手机格式错误！');
			}

			if (!check($input_val['moble_verify'], 'd')) {
				$this->error('手机验证码格式错误！');
			}

			if ($input_val['moble_verify'] != session('findpaypwd_verify')) {
				$this->error('手机验证码错误！');
			}

			$user = Db::name('User')->where(array('username' => $input_val['username']))->find();

			if (!$user) {
				$this->error('用户名不存在！');
			}

			if ($user['moble'] != $input_val['moble']) {
				$this->error('用户名或手机错误！');
			}

			if (!check($input_val['password'], 'password')) {
				$this->error('新交易密码格式错误！');
			}

			if ($input_val['password'] != $input_val['repassword']) {
				$this->error('确认交易密码错误！');
			}


			$rs = Db::table('weike_user')->where(array('id' => $user['id']))->update(array('paypassword' => md5($input_val['password'])));

			if (false !== $rs) {
				$this->success('操作成功');
			} else {
				$this->error('操作失败' . Db::table('weike_user')->getLastSql());
			}
		} else {
			return $this->fetch();
		}
	}
}

?>