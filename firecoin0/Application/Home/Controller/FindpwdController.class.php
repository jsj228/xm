<?php
namespace Home\Controller;

class FindpwdController extends HomeController
{

	public function check_moble(){
        $moble = I('moble/s', 0);
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		
		if (M('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
		}
		$this->success('');
	}

	public function check_pwdmoble(){
        $moble = I('moble/s', 0);
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		
		if (!M('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机不存在！');
		}
		$this->success('');
	}
	
//	public function real()
//	{
//        $moble = I('moble/s', 0);
//        $verify = I('verify/s');
//		if (check_verify(strtoupper($verify))) {
//			$this->error('图形验证码错误!');
//		}
//
//		if (!check($moble, 'moble2')) {
//			$this->error('手机格式错误！');
//		}
//
//		if (M('User')->where(array('moble' => $moble))->find()) {
//			$this->error('手机已存在！');
//		}
//
//        $code = rand(111111, 999999);
//        session('real_verify', $code);
//        $title = C('web_name');
//        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
//        if (send_moble($moble, $title, $message)) {
//            if(MOBILE_CODE == 0){
//                $this->success('目前是演示模式,请输入'.$code);
//            }else{
//                $this->success('验证码已发送');
//            }
//        } else {
//            $this->error('验证码发送失败,请重发');
//        }
//	}

	public function paypassword(){
		if (!session('reguserId')) {
			redirect('/#login');
		}
		$this->display();
	}
	
	public function info()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		
		$user = M('User')->where(array('id' => session('reguserId')))->find();
		if(!$user){
			$this->error('请先注册');
		}
		if($user['regaward']==0){
			if(C('reg_award')==1 && C('reg_award_num')>0){
				M('UserCoin')->where(array('id' => session('reguserId')))->setInc(C('reg_award_coin'),C('reg_award_num'));
				M('User')->where(array('id' => session('reguserId')))->save(array('regaward'=>1));
			}
		}	

		session('userId', $user['id']);
		session('userName', $user['username']);
		$this->assign('user', $user);
		$this->display();
	}

	public function findpwd()
	{
		if (IS_POST) {
			$input = I('post./a');

			$findpwd = json_decode(session('findpwd'));
            if (!check($input['moble'], 'moble2')) {
                $this->error('手机格式错误！');
            }

			if ($input['moble'] != $findpwd->moble) {
				$this->error('当前手机号与验证码不匹配');
			}
            $user = M('User')->where(array('moble' => $input['dial_code'].$input['moble']))->find();
            if(!$user){
                $this->error('不存在该手机');
            }
            
            if (!check($input['moble_verify'], 'd')) {
                $this->error('手机验证码格式错误！');
            }

            if ($input['moble_verify'] != $findpwd->findpwd_verify) {
                $this->error('手机验证码错误！');
            }
            session("findpaypwdmoble",$user['moble']);
            $this->success('验证成功');
		} else {
			$this->display();
		}
	}

	public function findpwdconfirm(){
		if(empty(session('findpaypwdmoble'))){
			redirect('/');
		}
		
		$this->display();
	}
	
	public function password_up(){
        $password = I('password/s');
        $repassword = I('repassword/s');

		if(empty(session('findpaypwdmoble'))){
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
		
		$user = M('User')->where(array('moble' => session('findpaypwdmoble')))->find();
		if(!$user){
			$this->error('不存在该手机');
		}

		if($user['password']==md5($password)){
			$this->error('交易密码不能和登录密码一样');
		}
		
		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables weike_user write , weike_user_log write ');
		$rs= $mo->table('weike_user')->where(array('moble' => $user['moble']))->save(array('paypassword' => md5($password)));

		if (!($rs===false)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			$this->success('操作成功');
		} else {
			$mo->execute('rollback');
			$this->error('操作失败');
		}
	}
	
	public function findpwdinfo(){
		
		if(empty(session('findpaypwdmoble'))){
			redirect('/');
		}
		session('findpaypwdmoble',"");
		$this->display();
	}
	
	public function findpaypwd()
	{
		if (IS_POST) {
			$input = I('post./a');

			if (!check($input['username'], 'username')) {
				$this->error('用户名格式错误！');
			}

			if (!check($input['moble'], 'moble2')) {
				$this->error('手机格式错误！');
			}

			if (!check($input['moble_verify'], 'd')) {
				$this->error('手机验证码格式错误！');
			}

			if ($input['moble_verify'] != session('findpaypwd_verify')) {
				$this->error('手机验证码错误！');
			}

			$user = M('User')->where(array('username' => $input['username']))->find();

			if (!$user) {
				$this->error('用户名不存在！');
			}

			if ($user['moble'] != $input['moble']) {
				$this->error('用户名或手机错误！');
			}

			if (!check($input['password'], 'password')) {
				$this->error('新交易密码格式错误！');
			}

			if ($input['password'] != $input['repassword']) {
				$this->error('确认交易密码错误！');
			}

			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables weike_user write , weike_user_log write ');
			$rs = array();
			$rs[] = $mo->table('weike_user')->where(array('id' => $user['id']))->save(array('paypassword' => md5($input['password'])));

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$this->success('操作成功');
			} else {
				$mo->execute('rollback');
				$this->error('操作失败' . $mo->table('weike_user')->getLastSql());
			}
		} else {
			$this->display();
		}
	}
}

?>