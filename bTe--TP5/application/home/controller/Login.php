<?php
namespace app\home\controller;


use think\Db;
use think\Exception;

class Login extends Home
{
	public function register()
	{
		return $this->fetch();
	}

	public function webreg()
	{
		return $this->fetch();
	}

	public function upregister()
	{
        $username = input('username/s', '');
        $password = input('password/s', '');
        $repassword = input('repassword/s', '');
        $verify = input('verify/s', '');
        $invit = input('invit/s', '');
        $moble = input('moble/s', '');
        $dial_code = input('dial_code/s');
        $moble_verify = input('moble_verify/s', '');

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
		
		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
		}

		if (Db::name('User')->where(array('username' => $username))->find()) {
			$this->error('用户名已存在');
		}

		if (!$invit) {
			$invit = session('invit');
		}

		$invituser = Db::name('User')->where(array('invit' => $invit))->find();
		if (!$invituser) {
			$invituser = Db::name('User')->where(array('id' => $invit))->find();
		}

		if (!$invituser) {
			$invituser = Db::name('User')->where(array('username' => $invit))->find();
		}

		if (!$invituser) {
			$invituser = Db::name('User')->where(array('moble' => $invit))->find();
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
			if (!Db::name('User')->where(array('invit' => $tradeno))->find()) {
				break;
			}
		}
		
		Db::startTrans();
		try {
            $rs = [];
            $rs[] = Db::table('weike_user')->insertGetId(array(
                'username' => $username,
                'moble' => $dial_code . $moble,
                'mobletime' => time(),
                'password' => md5($password),
                'invit' => $tradeno,
                'tpwdsetting' => 1,
                'invit_1' => $invit_1,
                'invit_2' => $invit_2,
                'invit_3' => $invit_3,
                'addip' => $this->request->ip(),
                'addr' => get_city_ip(),
                'addtime' => time(),
                'status' => 1));
            $rs[] = Db::table('weike_user_coin')->insert(array('userid' => $rs[0]));

            if (check_arr($rs)) {
                session('reguserId', $rs[0]);
                Db::commit();
                $this->success('注册成功！');
            } else {
                Db::rollback();
                $this->error('注册失败！');
            }
        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('注册失败！');
        }
	}
    
	public function check_moble(){
        $moble = input('moble/s');
        $dial_code = input('dial_code/s');
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}

		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
		}
        if (Db::name('User')->where(array('moble' => $dial_code . $moble))->find()) {
            $this->error('手机已存在！');
        }
		
		$this->success('');
	}
    
	public function check_pwdmoble(){
        $moble = input('moble/s');
        $dial_code = input('dial_code/s');
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
        $user = Db::name('User')->where(['moble' => $dial_code.$moble])->value('id');
		$user_tow = Db::name('User')->where(['moble' => $moble])->value('id');

        if (!$user && !$user_tow) {
            $this->error('手机号不存在！');
        }
		$this->success('检测成功！');
	}

	
	public function real()
	{
        $moble = input('moble/s', 0);
        $verify = input('verify/s');
        $dial_code = input('dial_code/s');
		if (!captcha_check($verify,'reg')) {
			$this->error('图形验证码错误!');
		}

		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机已存在！');
		}
        if (Db::name('User')->where(array('moble' => $dial_code . $moble))->find()) {
            $this->error('手机已存在！');
        }

		$code = rand(111111, 999999);
        //存数据
        session('real_verify', json_encode(array('moble' => $moble,'code' => $code)));
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        if(MOBILE_CODE ==0 ) {
            $this->success('目前是演示模式,请输入' . $code);
        }
        $send_rs = send_moble($dial_code.$moble, config('web_name'), $message);
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

	public function register2()
	{
		if (!session('reguserId')) {
			$this->redirect('/#login');
		}
		return $this->fetch();
	}
	
	public function paypassword(){
		if (!session('reguserId')) {
			$this->redirect('/#login');
		}
		return $this->fetch();
	}

	public function upregister2()
	{
        $paypassword = input('paypassword/s');
        $repaypassword = input('repaypassword/s');

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if ($paypassword != $repaypassword) {
			$this->error('确认密码错误！');
		}

		if (!session('reguserId')) {
			$this->error('非法访问！');
		}
		$rs=Db::name('User')->where(array('id' => session('reguserId'), 'password' => md5($paypassword)))->find();
		if ($rs) {
			$this->error('交易密码不能和登录密码一样！');
		}
		$rs =Db::name('User')->where(array('id' => session('reguserId')))->update(array('paypassword' => md5($paypassword)));
		if ($rs) {
			cache('userinfo'.session('userId'),null);
			$this->success('成功！');
		} else {
			$this->error('失败！');
		}
	}

	public function register3()
	{
		if (!session('reguserId')) {
			$this->redirect('/#login');
		}
		return $this->fetch();
	}
	
	public function truename()
	{
		if (!session('reguserId')) {
			$this->redirect('/#login');
		}
		return $this->fetch();
	}

	public function upregister3()
	{
        $truename = input('truename/s');
        $truename = preg_replace("/\s++/i", " ", trim($truename));
        $idcard = input('idcard/s');

		if (!check($truename, 'truename')) {
			$this->error('真实姓名格式错误！');
		}

		if (!preg_match('/^[0-9A-Z_]{5,18}$/', $idcard)) {
			$this->error('身份证号格式错误！');
		}

        if (Db::name('User')->where(['idcard' => $idcard])->value('id')) {
            $this->error('当前身份证已存在！');
        }

		if (!session('reguserId')) {
			$this->error('非法访问！');
		}

		if (Db::name('User')->where(array('id' => session('reguserId')))->update(array('truename' => $truename, 'idcard' => $idcard))) {
			
			cache('userinfo'.session('userId'),null);
			
			$this->success('成功！');
		} else {
			$this->error('失败！');
		}
	}

	public function register4()
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
				Db::name('UserCoin')->where(array('userid' => session('reguserId')))->setInc(config('reg_award_coin'),config('reg_award_num'));
				Db::name('User')->where(array('id' => session('reguserId')))->update(array('regaward'=>1));
			}
		}	

		session('userId', $user['id']);
		session('userName', $user['username']);
		cache('userinfo'.session('userId'),null);
		
		$this->assign('user', $user);
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
		cache('userinfo'.session('userId'),null);
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function chkUser()
	{
        $username = input('username/s');
		if (!check($username, 'username')) {
			$this->error('用户名格式错误！');
		}

		if (Db::name('User')->where(array('username' => $username))->find()) {
			$this->error('用户名已存在');
		}

		$this->success('');
	}

	public function submit()
	{
        $moble = input('moble/s', '');
        $password = input('password/s', '');
        $verify = input('verify/s', NULL);

		if (config('login_verify')) {
            if(!captcha_check($verify,'login')){
                $this->error('图形验证码错误!');
            }
		}

        if (check($moble, 'email')) {
            $user = Db::name('User')->where(array('email' => $moble))->find();
            $remark = '通过邮箱登录';
        }

        if (!isset($user) && check($moble, 'moble')) {
            $user = Db::name('User')->where(array('moble' => $moble))->find();
            $remark = '通过手机号登录';
        }

        if (!isset($user)) {
            $user = Db::name('User')->where(array('username' => $moble))->find();
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


		$ip = $this->request->ip();
		$logintime = time();
		$token_user = md5($user['id'].$logintime);
		session('token_user' , $token_user);
		//获取上一次登录时间
        $login_time = Db::name('UserLog')->where(['userid'=>$user['id'],'status'=>1])->order('id desc')->value('addtime');
        $time = $logintime - $login_time;
        Db::startTrans();
		try {
            $rs = [];
            $rs[] = Db::table('weike_user')->where(array('id' => $user['id']))->setInc('logins', 1);
            $rs[] = Db::table('weike_user')->where(array('id' => $user['id']))->update(['token' => $token_user]);
            $rs[] = Db::table('weike_user_log')->insert(['userid' => $user['id'], 'type' => '登录', 'remark' => $remark, 'addtime' => $logintime, 'addip' => $ip, 'addr' => get_city_ip(), 'status' => 0]);

            if (check_arr($rs)) {

                if (!$user['invit']) {
                    for (; true;) {
                        $tradeno = tradenoa();
                        if (!Db::name('User')->where(array('invit' => $tradeno))->find()) {
                            break;
                        }
                    }

                    Db::name('User')->where(array('id' => $user['id']))->setField('invit', $tradeno);
                }
                session('userId', $user['id']);
                session('userName', $user['username']);
                if (!$user['paypassword']) {
                    session('regpaypassword', $rs[0]);
                    session('reguserId', $user['id']);
                }
                if (!$user['truename']) {
                    session('regtruename', $rs[0]);
                    session('reguserId', $user['id']);
                }
                session('weike_already', 0);
                cache('userinfo' . session('userId'), null);
                Db::commit();
                $this->success('登录成功');
            } else {
                session('weike_already', 0);
                Db::rollback();
                $this->error('登录失败！');
            }
        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('登录失败！');
       }
	}
	//超过24小时登录需要通过手机认证登陆
    public function moble()
    {
        $this->error('页面错误');
        $moble = input('param.moble/s');
        $this->assign('moble',$moble);
        return $this->fetch();
    }
    //手机验证登录
    public function log_code()
    {
        $this->error('页面错误');
        $code = input('code/s');
        $moble = input('moble/s');
        $user = Db::name('User')->where(['username'=>$moble])->find();
        if (!$user){
            $this->error('手机号错误');
        }
        $s_code = json_decode(session('moble_login_code'));
        if ($code == $s_code->moble_login_code_verify){
            Db::name('UserLog')->where(['status'=>0,'userid'=>$user['id']])->order('id desc')->update(['status'=>1]);
            session('userId', $user['id']);
            session('userName', $user['username']);
            session('moble_login_code', null);
            $this->success('登录成功');
        }else{
            $this->error('验证码错误');
        }
    }

	public function loginout()
	{
		cache('userinfo'.session('userId'),null);
		session(null);
		$this->redirect('/');
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
            //新老用户手机号搜索查询
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
                session("findpwdmoble", $input_val['dial_code'] . $input_val['moble']);
            } else {
                session("findpwdmoble", $input_val['moble']);
            }

            $this->success('验证成功');
		} else {
			return $this->fetch();
		}
	}
	
	
	public function findpwdconfirm(){
		if(!session('findpwdmoble')){
			session(null);
			$this->redirect('/');
		}
		
		return $this->fetch();
	}
	
	public function password_up(){
        $password = input('password/s', '');

		if(!session('findpwdmoble')){
			$this->error('请返回第一步重新操作！');
		}
		
		if (!check($password, 'password')) {
			$this->error('新登录密码格式错误！');
		}

		$user = Db::name('User')->where(array('moble' => session('findpwdmoble')))->find();
		if(!$user){
			$this->error('不存在该手机');
		}
		
		if($user['paypassword']==md5($password)){
			$this->error("登录密码不能和交易密码一样");
		}

		if($user['password']==md5($password)){
            $this->error("新旧密码一样不能修改");
        }

		$rs = Db::name('user')->where(array('moble' => $user['moble']))->update(array('password' => md5($password)));
		if ($rs) {
			$this->success('操作成功');
		} else {
			$this->error('操作失败');
		}
		
	}
	
	public function findpwdinfo(){
		if(!session('findpwdmoble')){
			session(null);
			$this->redirect('/');
		}
		session(null);
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
				$this->error('确认密码错误！');
			}

			$rs = Db::table('weike_user')->where(array('id' => $user['id']))->update(array('paypassword' => md5($input_val['password'])));
			if (false !== $rs) {
				$this->success('修改成功');
			}
			else {
				$this->error('修改失败' . Db::table('weike_user')->getLastSql());
			}
		} else {
			return $this->fetch();
		}
	}
}

?>