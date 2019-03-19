<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Findpwd extends HomeCommon
{

	public function check_moble(){
        $moble = input('moble/s', 0);
		if (!check($moble, 'moble2')) {
		    exit(json_encode(['status'=>0,'msg'=>'手机格式错误']));
		}
		
		if (Db::name('User')->where(['moble' => $moble])->find()) {
            exit(json_encode(['status'=>0,'msg'=>'手机已存在']));
		}
        exit(json_encode(['status'=>1,'msg'=>'验证通过']));
	}

	public function check_pwdmoble(){
        $moble = input('moble/s', 0);
		if (!check($moble, 'moble2')) {
            exit(json_encode(['status'=>0,'msg'=>'手机格式错误']));
		}
		
		if (!Db::name('User')->where(['moble' => $moble])->find()) {
            exit(json_encode(['status'=>0,'msg'=>'手机不存在']));
		}

        exit(json_encode(['status'=>1,'msg'=>'验证通过']));
	}
	


	public function paypassword(){
		if (!session('reguserId')) {
			redirect('/#login');
		}
		return $this->fetch();
	}
	
	public function info()
	{
		if (!session('reguserId')) {
			redirect('/#login');
		}
		
		$user = Db::name('User')->where(['id' => session('reguserId')])->find();
		if(!$user){
			$this->error('请先注册');
		}
		if($user['regaward']==0){
			if(config::get('reg_award')==1 && config::get('reg_award_num')>0){
				Db::name('UserCoin')->where(['id' => session('reguserId')])->setInc(config::get('reg_award_coin'),config::get('reg_award_num'));
				Db::name('User')->where(['id' => session('reguserId')])->update(['regaward'=>1]);
			}
		}	

		session('userId', $user['id']);
		session('userName', $user['username']);
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function findpwd()
	{

		if ($this->request->isPost()) {

			$input = input('post.');

			$findpwd = json_decode(session('findpwd'));
            if (!check($input['moble'], 'moble2')) {
                exit(json_encode(['status'=>0,'msg'=>'手机格式错误']));
            }

			if ($input['moble'] != $findpwd->moble) {
                exit(json_encode(['status'=>0,'msg'=>'当前手机号与验证码不匹配']));
			}
            $user = Db::name('User')->where(['moble' => $input['dial_code'].$input['moble']])->find();
            if(!$user){
                exit(json_encode(['status'=>0,'msg'=>'不存在该手机']));
            }
            
            if (!check($input['moble_verify'], 'd')) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码格式错误']));
            }

            if ($input['moble_verify'] != $findpwd->findpwd_verify) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码错误']));
            }
            session("findpaypwdmoble",$user['moble']);
            exit(json_encode(['status'=>1,'msg'=>'验证成功']));
		} else {
			return $this->fetch();
		}
	}

	public function findpwdconfirm(){
		if(empty(session('findpaypwdmoble'))){
			return redirect('/Findpwd/findpwd');
		}
		
		return $this->fetch();
	}
	
	public function password_up(){
        $password = input('password');
        $repassword = input('repassword');

		if(empty(session('findpaypwdmoble'))){
            exit(json_encode(['status'=>0,'msg'=>'请返回第一步重新操作']));
		}
		
		if (!check($password, 'password')) {
            exit(json_encode(['status'=>0,'msg'=>'新交易密码格式错误']));
		}
		
		if (!check($repassword, 'password')) {
            exit(json_encode(['status'=>0,'msg'=>'确认密码格式错误']));
		}
		
		
		if ($password != $repassword) {
            exit(json_encode(['status'=>0,'msg'=>'确认新密码错误']));
		}
		
		$user = Db::name('User')->where(array('moble' => session('findpaypwdmoble')))->find();

		if(!$user){
            exit(json_encode(['status'=>0,'msg'=>'不存在该手机']));
        }

		if($user['password']==md5($password)){
            exit(json_encode(['status'=>0,'msg'=>'交易密码不能和登录密码一样']));
		}

		$rs= Db::table('weike_user')->where(array('moble' => $user['moble']))->update(array('paypassword' => md5($password)));

		if (!($rs===false)) {
			exit(json_encode(['status'=>1,'msg'=>'操作成功']));
		} else {
            exit(json_encode(['status'=>0,'msg'=>'操作失败']));
		}
	}
	
	public function findpwdinfo(){
		
		if(empty(session('findpaypwdmoble'))){
			redirect('/');
		}
		session('findpaypwdmoble',"");
		return $this->fetch();
	}
	
	public function findpaypwd()
	{
		if ($this->request->isPost()) {
			$input = input('post./a');

			if (!check($input['username'], 'username')) {
                exit(json_encode(['status'=>0,'msg'=>'用户名格式错误']));
			}

			if (!check($input['moble'], 'moble2')) {
                exit(json_encode(['status'=>0,'msg'=>'手机格式错误']));
			}

			if (!check($input['moble_verify'], 'd')) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码格式错误']));
			}

			if ($input['moble_verify'] != session('findpaypwd_verify')) {
                exit(json_encode(['status'=>0,'msg'=>'手机验证码错误']));
			}

			$user = Db::name('User')->where(array('username' => $input['username']))->find();

			if (!$user) {
                exit(json_encode(['status'=>0,'msg'=>'用户名不存在']));
			}

			if ($user['moble'] != $input['moble']) {
                exit(json_encode(['status'=>0,'msg'=>'用户名或手机错误']));
			}

			if (!check($input['password'], 'password')) {
                exit(json_encode(['status'=>0,'msg'=>'新交易密码格式错误']));
			}

			if ($input['password'] != $input['repassword']) {
                exit(json_encode(['status'=>0,'msg'=>'确认交易密码错误']));
			}

			if (Db::name('user')->where(['id' => $user['id']])->update(['paypassword' => md5($input['password'])])) {
                exit(json_encode(['status'=>1,'msg'=>'操作成功']));
			} else {
                exit(json_encode(['status'=>0,'msg'=>'操作失败' . Db::table('weike_user')->getLastSql()]));
			}
		} else {
			return $this->fetch();
		}
	}
}

?>