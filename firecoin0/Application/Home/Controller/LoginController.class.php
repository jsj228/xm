<?php
namespace Home\Controller;

class LoginController extends HomeController
{
	public function register()
	{
		$this->display();
	}

	public function webreg()
	{
		$this->display();
	}

	public function upregister()
	{
        $username = I('username/s', '');
        $password = I('password/s', '');
        $repassword = I('repassword/s', '');
        $verify = I('verify/s', '');
        $invit = I('invit/s', '');
        $moble = I('moble/s', '');
        $dial_code = I('dial_code/s');
        $moble_verify = I('moble_verify/s', '');

        if (!check($password, 'password')) {
            $this->error('登录密码格式错误！');
        }
        $username = trim($moble);
		$code_mobile = json_decode(session('real_verify'));

		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}

		if (!check($moble_verify, 'd')) {
			$this->error('手机验证码格式错误！');
		}
		if($moble != $code_mobile->moble){
			$this->error('手机号与验证码不匹配');
		}
		if ($moble_verify != $code_mobile->code) {
			$this->error('手机验证码错误！');
		}
		
		if (M('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
		}

		if (M('User')->where(array('username' => $username))->find()) {
			$this->error('用户名已存在');
		}

		if (!$invit) {
			$invit = session('invit');
		}

		$invituser = M('User')->where(array('invit' => $invit))->find();
		if (!$invituser) {
			$invituser = M('User')->where(array('id' => $invit))->find();
		}

		if (!$invituser) {
			$invituser = M('User')->where(array('username' => $invit))->find();
		}

		if (!$invituser) {
			$invituser = M('User')->where(array('moble' => $invit))->find();
		}

		if ($invituser) {
			$invit_1 = $invituser['id'];
			$invit_2 = $invituser['invit_1'];
			$invit_3 = $invituser['invit_2'];
		} else {
			$invit_1 = 0;
			$invit_2 = 0;
			$invit_3 = 0;
		}

		for (; true; ) {
			$tradeno = tradenoa();

			if (!M('User')->where(array('invit' => $tradeno))->find()) {
				break;
			}
		}

		$mo = M();
		$mo->execute('set autocommit=0');
		$mo->execute('lock tables weike_user write , weike_user_coin write ');
		$rs = array();
		$rs[] = $mo->table('weike_user')->add(array('username' => $username, 'moble' => $dial_code.$moble, 'mobletime' => time(), 'password' => md5($password), 'invit' => $tradeno, 'tpwdsetting' => 1, 'invit_1' => $invit_1, 'invit_2' => $invit_2, 'invit_3' => $invit_3, 'addip' => get_client_ip(0, true), 'addr' => get_city_ip(), 'addtime' => time(), 'status' => 1));
		$rs[] = $mo->table('weike_user_coin')->add(array('userid' => $rs[0]));

		if (check_arr($rs)) {
			$mo->execute('commit');
			$mo->execute('unlock tables');
			session('reguserId', $rs[0]);
			$this->success('注册成功！');
		} else {
			$mo->execute('rollback');
			$this->error('注册失败！');
		}
	}
	public function check_moble(){
        $moble = I('moble/s', 0);
        $dial_code = I('dial_code/s');
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		if(M('User')->where(array('username' => $dial_code.$moble))->getField('id')) $this->error('手机号码已存在！');
//		if (M('User')->where(array('moble' => $dial_code.$moble))->find()) {
//			$this->error('手机已存在！');
//		}
		$this->success('');
	}
    
	public function check_pwdmoble(){
        $moble = I('moble/s', 0);
        $dial_code = I('dial_code/s', 0);
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		
		if (!M('User')->where(array('moble' => $dial_code.$moble))->find()) {
			$this->error('手机不存在！');
		}
		
		$this->success('');
	}
	
	public function real()
	{
        $moble = I('moble/s', 0);
        $verify = I('verify/s');

        $dial_code = I('dial_code/s');
		if (!check_verify($verify)) {
			$this->error('图形验证码错误!');
		}

		if (!check($moble, 'moble2')) {
			$this->error('手机号码格式错误！');
		}

		if (M('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
		}

		$code = rand(111111, 999999);
		//存数据
		session('real_verify', json_encode(array('moble'=>$moble,'code'=>$code)));
        $title = C('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
		if (send_moble($dial_code.$moble, $title, $message)) {
            if(MOBILE_CODE ==0 ){
                $this->success('目前是演示模式,请输入'.$code);
            }else{
                $this->success('验证码已发送');
            }
		} else {
			$this->error('验证码发送失败,请重发');
		}
	}

	public function register2()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		$this->display();
	}
	
	public function paypassword(){
		if (!session('reguserId')) {
			redirect('/#login');
		}
		$this->display();
	}

	public function upregister2()
	{
        $paypassword = I('paypassword/s');
        $repaypassword = I('repaypassword/s');

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if ($paypassword != $repaypassword) {
			$this->error('确认密码错误！');
		}

		if (!session('reguserId')) {
			$this->error('非法访问！');
		}

		if (M('User')->where(array('id' => session('reguserId'), 'password' => md5($paypassword)))->find()) {
			$this->error('交易密码不能和登录密码一样！');
		}

		if (M('User')->where(array('id' => session('reguserId')))->save(array('paypassword' => md5($paypassword)))) {
			S('userinfo'.session('userId'),null);
			$this->success('成功！');
		} else {
			$this->error('失败！');
		}
	}

	public function register3()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		$this->display();
	}
	
	public function truename()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		$this->display();
	}

	public function upregister3()
	{
        $truename = I('truename/s');
        $truename=preg_replace("/\s++/i"," ",trim($truename));
        $idcard = I('idcard/s');
        $idcard=preg_replace("/\s++/i","",trim($idcard));
		if (!check($truename, 'truename')) {
			$this->error('真实姓名格式错误！');
		}

        if (!preg_match('/^[0-9A-Z_()]{5,18}$/', $idcard)) {
            $this->error('身份证号格式错误！');
        }

        if (M('User')->where(['idcard' => $idcard])->getField('id')) {
            $this->error('当前身份证已存在！');
        }

		if (!session('reguserId')) {
			$this->error('非法访问！');
		}

		if (M('User')->where(array('id' => session('reguserId')))->save(array('truename' => $truename, 'idcard' => $idcard))) {
			
			S('userinfo'.session('userId'),null);
			
			$this->success('成功！');
		} else {
			$this->error('失败！');
		}
	}

	public function register4()
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
				M('UserCoin')->where(array('userid' => session('reguserId')))->setInc(C('reg_award_coin'),C('reg_award_num'));
				M('User')->where(array('id' => session('reguserId')))->save(array('regaward'=>1));
			}
		}	

		session('userId', $user['id']);
		session('userName', $user['username']);
		S('userinfo'.session('userId'),null);
		
		$this->assign('user', $user);
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
		S('userinfo'.session('userId'),null);
		$this->assign('user', $user);
		$this->display();
	}

	public function chkUser()
	{
        $username = I('username/s');
		if (!check($username, 'username')) {
			$this->error('用户名格式错误！');
		}

		if (M('User')->where(array('username' => $username))->find()) {
			$this->error('用户名已存在');
		}

		$this->success('');
	}

	public function submit()
	{
        $moble = I('moble/s', '');
        $password = I('password/s', '');
        $verify = I('verify/s', NULL);
		$code = I('code');

//		if (session('login_verify')!=$code){
//			$this->error('手机验证码错误!');
//		}
//		if (session('login_moble')!=$moble){
//			$this->error('手机验证码不匹配!');
//		}
//		$log = $verify;
//		mlog($log);
		if (C('login_verify')) {
			if (!check_verify(strtoupper($verify))) {
				$this->error('图形验证码错误!');
			}
		}


        if (check($moble, 'email')) {
            $user = M('User')->where(array('email' => $moble))->find();
            $remark = '通过邮箱登录';
        }

        if (!$user && check($moble, 'moble')) {
            $user = M('User')->where(array('moble' => $moble))->find();
            $remark = '通过手机号登录';
        }

        if (!$user) {
            $user = M('User')->where(array('username' => $moble))->find();
            $remark = '通过用户名登录';
        }

		if (!$user) {
			$this->error('用户不存在！');
		}

		if (!check($password, 'password')) {
			$this->error('登录密码格式错误！');
		}

		if (md5($password) != $user['password']) {
			$this->error('登录密码错误！');
		}

		if ($user['status'] != 1) {
			$this->error('你的账号已冻结请联系管理员！');
		}

		
		$ip = get_client_ip(0, true);
		$logintime = time();
		$token_user = md5($user['id'].$logintime);
		session('token_user' , $token_user);
		
		$mo = M();
		$mo->startTrans();
		try{
            $mo->table('weike_user')->where(array('id' => $user['id']))->setInc('logins', 1);
            $mo->table('weike_user')->where(array('id' => $user['id']))->save(array('token'=>$token_user,'addip'=>$ip));
            $mo->table('weike_user_log')->add(['userid' => $user['id'], 'type' => '登录', 'remark' => $remark, 'addtime' => $logintime, 'addip' => $ip, 'addr' => get_city_ip(), 'status' => 1]);
            $mo->commit();
            $flag = true;
        }catch (\Exception $e){
		    $mo->rollback();
		    $flag = false;
        }

		if ($flag) {

			if (!$user['invit']) {
				for (; true; ) {
					$tradeno = tradenoa();
					if (!M('User')->where(array('invit' => $tradeno))->find()) {
						break;
					}
				}

				M('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
			}

			session('userId', $user['id']);
			session('userName', $user['username']);
			if (!$user['paypassword']) {
				session('regpaypassword', '');
				session('reguserId', $user['id']);
			}
			if (!$user['truename']) {
				session('regtruename', '');
				session('reguserId', $user['id']);
			}
			session('weike_already',0);
			S('userinfo'.session('userId'),null);

			$this->success('登录成功！');
		} else {
			$this->error('登录失败！');
		}
	}

	public function loginout()
	{
		S('userinfo'.session('userId'),null);
		session(null);
		redirect('/');
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
            session("findpwdmoble",$user['moble']);
            $this->success('验证成功');
		} else {
			$this->display();
		}
	}
	
	
	public function findpwdconfirm(){
		if(empty(session('findpwdmoble'))){
			session(null);
			redirect('/');
		}
		
		$this->display();
	}
	
	public function password_up(){
        $password = I('password/s', '');

		if(empty(session('findpwdmoble'))){
			$this->error('请返回第一步重新操作！');
		}
		
		if (!check($password, 'password')) {
			$this->error('新登录密码格式错误！');
		}

		$user = M('User')->where(array('moble' => session('findpwdmoble')))->find();
		if(!$user){
			$this->error('不存在该手机');
		}
		
		if($user['paypassword']==md5($password)){
			$this->error("登录密码不能和交易密码一样");
		}

		if($user['password']==md5($password)){
            $this->error("新旧密码一样不能修改");
        }

		$rs = M('user')->where(array('moble' => $user['moble']))->save(array('password' => md5($password)));
		if ($rs) {
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
		
	}
	
	public function findpwdinfo(){
		if(empty(session('findpwdmoble'))){
			session(null);
			redirect('/');
		}
		session(null);
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
				$this->error('确认密码错误！');
			}

			$mo = M();
			$mo->execute('set autocommit=0');
			$mo->execute('lock tables weike_user write , weike_user_log write ');
			$rs = array();
			$rs[] = $mo->table('weike_user')->where(array('id' => $user['id']))->save(array('paypassword' => md5($input['password'])));

			if (check_arr($rs)) {
				$mo->execute('commit');
				$mo->execute('unlock tables');
				$this->success('修改成功');
			}
			else {
				$mo->execute('rollback');
				$this->error('修改失败' . $mo->table('weike_user')->getLastSql());
			}
		} else {
			$this->display();
		}
	}
}

?>