<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\Session;
class Login extends HomeCommon
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
        $username = input('username');
        $password = input('password');
        $repassword = input('repassword');
        $verify = input('verify');
        $invit = input('invit');
        $moble = input('moble');
        $dial_code = input('dial_code');
        $moble_verify = input('moble_verify');

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
		
		if (Db::name('User')->where(['moble' => $moble])->find()) {
			$this->error('手机已存在！');
		}

		if (Db::name('User')->where(['username' => $username])->find()) {
			$this->error('用户名已存在');
		}

		if (!$invit) {
			$invit = session('invit');
		}

		$invituser = Db::name('User')->where(['invit' => $invit])->find();
		if (!$invituser) {
			$invituser = Db::name('User')->where(['id' => $invit])->find();
		}

		if (!$invituser) {
			$invituser = Db::name('User')->where(['username' => $invit])->find();
		}

		if (!$invituser) {
			$invituser = Db::name('User')->where(['moble' => $invit])->find();
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

		$mo = Db::name('');
		$mo->startTrans();
		try{
            $regId = Db::table('weike_user')->insertGetId([
                'username' => $username,
                'moble' => $dial_code.$moble,
                'mobletime' => time(),
                'password' => md5($password),
                'invit' => $tradeno,
                'tpwdsetting' => 1,
                'invit_1' => $invit_1,
                'invit_2' => $invit_2,
                'invit_3' => $invit_3,
                'addip' =>$this->request->ip(),
                'addr' => $request = Request::instance(),
                'addtime' => time(),
                'status' => 1
            ]);
            Db::table('weike_user_coin')->insert(['userid' => $regId]);
            $flag = true;
            $mo->commit();
        }catch (\Exception $e){
		    $flag = false;
		    $mo->rollback();
        }

		if ($flag) {
			session('reguserId', $regId);
			return ['status'=>1,'msg'=>'注册成功！'];
		} else {
			$this->error('注册失败！');
		}
	}

	public function check_moble(){
        $moble = input('moble/s', 0);

        $dial_code = input('dial_code');
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		if (Db::name('User')->where(['moble' =>$dial_code.$moble])->find()) {
			exit(json_encode(['status'=>0,'msg'=>'手机已存在！']));
		}

        exit(json_encode(['status'=>1,'msg'=>'验证通过！']));
	}
    
	public function check_pwdmoble(){
        $moble = input('moble');
        $dial_code = input('dial_code');
		if (!check($moble, 'moble2')) {
			$this->error('手机格式错误！');
		}
		
		if (!Db::name('User')->where(array('moble' => $dial_code.$moble))->find()) {
			exit(json_encode(['status'=>0,'msg'=>'手机不存在！']));
		}

        exit(json_encode(['status'=>1,'msg'=>'验证通过！']));
	}
	
	public function real()
	{
        $moble = input('moble');
        $verify = input('verify');
        $dial_code = input('dial_code');

		if (!check($moble, 'moble2')) {
            exit(json_encode(['status'=>0,'msg'=>'手机格式错误！']));
		}
		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			exit(json_encode(['status'=>0,'msg'=>'手机已存在！']));
		}

		$code = rand(111111, 999999);
		//存数据
		session('real_verify', json_encode(array('moble'=>$moble,'code'=>$code)));
        $title = config('web_name');
        $message = "您的验证码是{$code}，在10分钟内输入有效。如非本人操作请忽略此短信。";
		if (send_moble($dial_code.$moble, $title, $message)) {
            if(MOBILE_CODE ==0 ){
            	exit(json_encode(['status'=>1,'msg'=>"目前是演示模式,请输入".$code]));
                // $this->success('目前是演示模式,请输入'.$code);
            }else{
            	exit(json_encode(['status'=>1,'msg'=>"目前是演示模式,请输入".$code]));
            }
		} else {
            exit(json_encode(['status'=>0,'msg'=>'验证码发送失败,请重发！']));
		}
	}

	public function register2()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		return $this->fetch();
	}
	
	public function paypassword(){
		if (!session('reguserId')) {
			redirect('/#login');
		}
		return $this->fetch();
	}

	public function upregister2()
	{
        $paypassword = input('paypassword');
        $repaypassword = input('repaypassword');

		if (!check($paypassword, 'password')) {
            exit(json_encode(['status'=>0,'msg'=>'交易密码格式错误！']));
		}

		if ($paypassword != $repaypassword) {
            exit(json_encode(['status'=>0,'msg'=>'确认密码错误！']));
		}
		if (!session('reguserId')) {
            exit(json_encode(['status'=>0,'msg'=>'非法访问！']));
		}

		if (Db::name('User')->where(['id' => session('reguserId'), 'password' => md5($paypassword)])->find()) {
            exit(json_encode(['status'=>0,'msg'=>'交易密码不能和登录密码一样！']));
		}

		if (Db::name('User')->where(['id' => session('reguserId')])->update(['paypassword' => md5($paypassword)])) {
			Cache::rm('userinfo'.session('userId'));
			// $this->success('成功！');
			exit(json_encode(['status'=>1,'msg'=>'成功！']));
		} else {
            exit(json_encode(['status'=>0,'msg'=>'失败！']));
		}
	}

	public function register3()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		return $this->fetch();
	}
	
	public function truename()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		return $this->fetch();
	}

	public function upregister3()
	{
        $truename = input('truename');
        $truename=preg_replace("/\s++/i"," ",trim($truename));
        $idcard = input('idcard/s');
        $idcard=preg_replace("/\s++/i","",trim($idcard));
		if (!check($truename, 'truename')) {
            exit(json_encode(['status'=>0,'msg'=>'真实姓名格式错误！']));
		}

        if (!preg_match('/^[0-9A-Z_()]{5,18}$/', $idcard)) {
            exit(json_encode(['status'=>0,'msg'=>'身份证号格式错误！']));
        }

        if (Db::name('User')->where(['idcard' => $idcard])->value('id')) {
            exit(json_encode(['status'=>0,'msg'=>'当前身份证已存在！']));
        }

		if (!session('reguserId')) {
            exit(json_encode(['status'=>0,'msg'=>'非法访问！']));
		}

		if (Db::name('User')->where(['id' => session('reguserId')])->update(['truename' => $truename, 'idcard' => $idcard])) {
			 Cache::rm('userinfo'.session('userId'));
            exit(json_encode(['status'=>1,'msg'=>'成功！']));
			// $this->success('成功！');
		} else {
            exit(json_encode(['status'=>0,'msg'=>'失败！']));
		}
	}

	public function register4()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		
		$user = Db::name('User')->where(['id' => session('reguserId')])->find();

		if(!$user){
            exit(json_encode(['status'=>0,'msg'=>'请先注册！']));
		}
		if($user['regaward']==0){
			if(config::get('reg_award')==1 && C('reg_award_num')>0){
				Db::name('UserCoin')->where(['userid' => session('reguserId')])->setInc(config::get('reg_award_coin'),config::get('reg_award_num'));
				Db::name('User')->where(['id' => session('reguserId')])->save(['regaward'=>1]);
			}
		}	

		session('userId', $user['id']);
		session('userName', $user['username']);
		 Cache::rm('userinfo'.session('userId'));
	
		
		$this->assign('user', $user);
		return $this->fetch();
	}
	
	public function info()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		
		$user = Db::name('User')->where(['id' => session('reguserId')])->find();
		if(!$user){
            exit(json_encode(['status'=>0,'msg'=>'请先注册！']));
		}
		if($user['regaward']==0){
			if(config('reg_award')==1 && config('reg_award_num')>0){
				Db::name('UserCoin')->where(['id' => session('reguserId')])->setInc(config('reg_award_coin'),config('reg_award_num'));
				Db::name('User')->where(['id' => session('reguserId')])->update(['regaward'=>1]);
			}
		}	

		session('userId', $user['id']);
		session('userName', $user['username']);
		Cache::rm('userinfo'.session('userId'));
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function chkUser()
	{
        $username = input('username/s');
		if (!check($username, 'username')) {
            exit(json_encode(['status'=>0,'msg'=>'用户名格式错误！']));
		}

		if (Db::name('User')->where(array('username' => $username))->find()) {
            exit(json_encode(['status'=>0,'msg'=>'用户名已存在！']));
		}

        exit(json_encode(['status'=>1,'msg'=>'验证通过！']));
	}

	public function submit()
	{
        $moble = input('moble');
        $password = input('password');
        $verify = input('verify');
        $email = input('email');

		 if (!captcha_check($verify)) {
             exit(json_encode(['status'=>0,'msg'=>'图形验证码错误！']));
	    } 
        
		if ($moble) {
            $user = Db::name('User')->where(['username' => $moble])->find();
            $remark = '通过手机号登录';
        }
        

        if ($email) {
        	 $user =  Db::name('User')->where(['email' => $moble])->find();
            $remark = '通过邮箱登录';
        }
		if (!$user) {
            exit(json_encode(['status'=>0,'msg'=>'用户不存在！']));
		}

		if (!check($password, 'password')) {
            exit(json_encode(['status'=>0,'msg'=>'登录密码格式错误！']));
		}

		if (md5($password) != $user['password']) {
            exit(json_encode(['status'=>0,'msg'=>'登录密码错误！']));
		}

		if ($user['status'] != 1) {
            exit(json_encode(['status'=>0,'msg'=>'你的账号已冻结请联系管理员！']));
		}

		$request = Request::instance();
		$request=$request->ip();
		$logintime = time();
		$token_user = md5($user['id'].$logintime);
		session('token_user' , $token_user);
		$mo = Db::name('');
		$mo->startTrans();
		try{
            Db::name('user')->where(array('id' => $user['id']))->setInc('logins', 1);
            Db::name('user')->where(array('id' => $user['id']))->update(['token'=>$token_user]);
            Db::name('user_log')->insert([
                'userid' => $user['id'],
                'type' => '登录',
                'remark' => $remark,
                'addtime' => $logintime,
                'addip' =>$request,
                'addr' => $request,
                'status' => 1
            ]);
            $flag = true;
            $mo->commit();
        }catch (\Exception $e){
		    $flag = false;
		    $mo->rollback();
        }

		if ($flag) {
			if (!$user['invit']) {
				for (; true; ) {
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
			session('weike_already',0);
			Cache::rm('userinfo'.session('userId'));
            exit(json_encode(['status'=>1,'msg'=>'登录成功！']));
		} else {

			session('weike_already',0);
            exit(json_encode(['status'=>0,'msg'=>'登录失败！']));
		}
	}

	public function loginout()
	{
		session(null);
		Cache::store('redis')->set('weike_already',0);
		$this->redirect(url('/'));
	}

	public function findpwd()
	{
		if ($this->request->isPost()) {
			$input = input('post.');

			$findpwd = json_decode(session('findpwd'));
            if (!check($input['moble'], 'moble2')) {
                exit(json_encode(['status'=>0,'msg'=>'手机格式错误！']));
            }

			if ($input['moble'] != $findpwd->moble) {
                exit(json_encode(['status'=>0,'msg'=>'当前手机号与验证码不匹配！']));
			}
            $user = Db::name('User')->where(['moble' => $input['dial_code'].$input['moble']])->find();
            if(!$user){
                exit(json_encode(['status'=>0,'msg'=>'不存在该手机！']));
            }

            if (!check($input['moble_verify'], 'd')) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码格式错误！']));
            }

            if ($input['moble_verify'] != $findpwd->findpwd_verify) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码错误！']));
            }
            session("findpwdmoble",$user['moble']);
            exit(json_encode(['status'=>1,'msg'=>'验证成功！']));
            // $this->success('验证成功');
		} else {
			return $this->fetch();
		}
	}
	
	
	public function findpwdconfirm(){
		if(empty(session('findpwdmoble'))){
			session(null);
			return redirect('/Login/findpwd');
		}
		
		return $this->fetch();
	}
	
	public function password_up(){
        $password = input('password');

		if(empty(session('findpwdmoble'))){
            exit(json_encode(['status'=>0,'msg'=>'请返回第一步重新操作！']));
		}
		
		if (!check($password, 'password')) {
            exit(json_encode(['status'=>0,'msg'=>'新登录密码格式错误！']));
		}

		$user = Db::name('User')->where(['moble' => session('findpwdmoble')])->find();
		if(!$user){
            exit(json_encode(['status'=>0,'msg'=>'不存在该手机！']));
		}
		
		if($user['paypassword']==md5($password)){
            exit(json_encode(['status'=>0,'msg'=>'登录密码不能和交易密码一样！']));
		}

		if($user['password']==md5($password)){
            exit(json_encode(['status'=>0,'msg'=>'新旧密码一样不能修改！']));
        }

		$rs = Db::name('user')->where(['moble' => $user['moble']])->update(['password' => md5($password)]);
		if ($rs) {
            exit(json_encode(['status'=>1,'msg'=>'操作成功！']));
		} else {
            exit(json_encode(['status'=>0,'msg'=>'操作失败！']));
        }
		
	}
	
	public function findpwdinfo(){
		if(empty(session('findpwdmoble'))){
			session(null);
			redirect('/');
		}
		session(null);
		return $this->fetch();
	}
	
	
	public function findpaypwd()
	{
		if (IS_POST) {
			$input = input('post./a');

			if (!check($input['username'], 'username')) {
                exit(json_encode(['status'=>0,'msg'=>'用户名格式错误！']));
			}

			if (!check($input['moble'], 'moble2')) {
                exit(json_encode(['status'=>0,'msg'=>'手机格式错误！']));
			}

			if (!check($input['moble_verify'], 'd')) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码格式错误！']));
			}

			if ($input['moble_verify'] != session('findpaypwd_verify')) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码错误！']));
			}

			$user = Db::name('User')->where(['username' => $input['username']])->find();

			if (!$user) {
                exit(json_encode(['status'=>0,'msg'=>'用户名不存在！']));
			}

			if ($user['moble'] != $input['moble']) {
                exit(json_encode(['status'=>0,'msg'=>'用户名或手机错误！']));
			}

			if (!check($input['password'], 'password')) {
                exit(json_encode(['status'=>0,'msg'=>'新交易密码格式错误！']));
			}

			if ($input['password'] != $input['repassword']) {
                exit(json_encode(['status'=>0,'msg'=>'确认密码错误！']));
			}

			if (Db::table('weike_user')->where(['id' => $user['id']])->update(['paypassword' => md5($input['password'])])) {
                exit(json_encode(['status'=>1,'msg'=>'修改成功！']));
			}
			else {
                exit(json_encode(['status'=>0,'msg'=>'修改失败' . $mo->table('weike_user')->getLastSql()]));
			}
		} else {
			return $this->fetch();
		}
	}
}

?>