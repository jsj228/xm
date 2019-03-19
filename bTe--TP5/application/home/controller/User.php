<?php
namespace app\home\controller;

use think\Db;
use think\Exception;

class User extends Home
{
	public function index()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		
		$this->assign('user', $user);
		$this->assign('prompt_text', model('Text')->get_content('user_index'));
		return $this->fetch();
	}

	public function nameauth()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		if ($user['idcard']) {
			$user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
		}
		
		$imgstr = "";
		$imgnum=0;
		if($user['idcardimg1']){
			$img_arr = explode("_",$user['idcardimg1']);

			foreach($img_arr as $k=>$v){
				$imgstr = $imgstr.'<li style="height:100px;"><img style="width:300px;height:100px;" src="'.config('view_replace_str.__DOMAIN__').'/Upload/newcard/'.$v.'" /></li>';
				$imgnum++;
			}

			unset($img_arr);
		}
		$allowImg = false;
		if( ($user['idcardauth']==0 && $imgnum<3) || ($user['idcardauth']==0 && $imgnum==3 && !empty($user['idcardinfo']))){
			$allowImg = true;
		}

		$this->assign('user', $user);
		$this->assign('userimg', $imgstr);
		$this->assign('imgnum', $imgnum);
		$this->assign('allowImg', $allowImg);
		$this->assign('prompt_text', model('Text')->get_content('user_nameauth'));
		return $this->fetch();
	}

	public function password()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$this->assign('prompt_text', model('Text')->get_content('user_password'));
		return $this->fetch();
	}

	public function uppassword()
	{
        $oldpassword = input('oldpassword/s');
        $newpassword = input('newpassword/s');
        $repassword = input('repassword/s');
        $moble_verify = input('moble_verify/s');

		if (!userid()) {
			$this->error('请先登录！');
		}

        if (!captcha_check($moble_verify,'reset_passwd')) {
            $this->error('图形验证码错误!');
        }

		if (!check($oldpassword, 'password')) {
			$this->error('旧登录密码格式错误！');
		}

		if (!check($newpassword, 'password')) {
			$this->error('新登录密码格式错误！');
		}

		if ($newpassword != $repassword) {
			$this->error('确认新密码错误！');
		}

		$password = Db::name('User')->where(array('id' => userid()))->value('password');
		if (md5($oldpassword) != $password) {
			$this->error('旧登录密码错误！');
		}
        Db::startTrans();
        try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('password' => md5($newpassword)));
            if ($rs !== false) {
                Db::commit();
                $this->success('修改成功');
            } else {
                Db::rollback();
                $this->error('修改失败');
            }

        }catch (Exception $e){
            Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('修改失败');
        }

	}
	
	
	public function uppassword_qq()
	{
        $oldpassword = input('oldpassword/s');
        $newpassword = input('newpassword/s');
        $repassword = input('repassword/s');

		if (!userid()) {
			$this->error('请先登录！');
		}

		if ($oldpassword == $newpassword) {
			$this->error('新修改的密码和原密码一样！');
		}
		if (!check($oldpassword, 'password')) {
			$this->error('旧登录密码格式错误！');
		}

		if (!check($newpassword, 'password')) {
			$this->error('新登录密码格式错误！');
		}

		if ($newpassword != $repassword) {
			$this->error('确认新密码错误！');
		}

		$password = Db::name('User')->where(array('id' => userid()))->value('password');

		if (md5($oldpassword) != $password) {
			$this->error('旧登录密码错误！');
		}
		$paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

		if(md5($newpassword) == $paypassword){
			$this->error("新密码不能和交易密码一样");
		}
        Db::startTrans();
		try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('password' => md5($newpassword)));
            if ($rs!==false) {
                Db::commit();
                $this->success('修改成功');
            } else {
                Db::rollback();
                $this->error('修改失败');
            }

        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('修改失败');
        }

	}

	public function paypassword()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		$this->assign('user', $user);
		$this->assign('prompt_text', model('Text')->get_content('user_paypassword'));
		return $this->fetch();
	}
	
	public function uppaypassword_qq()
	{
        $oldpaypassword = input('oldpaypassword/s');
        $newpaypassword = input('newpaypassword/s');
        $repaypassword = input('repaypassword/s');

		if (!userid()) {
			$this->error('请先登录！');
		}


		if (!check($oldpaypassword, 'password')) {
			$this->error('旧交易密码格式错误！');
		}

		if (!check($newpaypassword, 'password')) {
			$this->error('新交易密码格式错误！');
		}

		if ($newpaypassword != $repaypassword) {
			$this->error('确认新密码错误！');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();

		if (md5($oldpaypassword) != $user['paypassword']) {
			$this->error('旧交易密码错误！');
		}

		if (md5($newpaypassword) == $user['password']) {
			$this->error('交易密码不能和登录密码相同！');
		}

        Db::startTrans();
        try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('paypassword' => md5($newpaypassword)));
            if (false !==$rs) {
                Db::commit();
                $this->success('修改成功');
            } else {
                Db::rollback();
                $this->error('修改失败');
            }

        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('修改失败');

        }


	}

	public function uppaypassword()
	{
	    $oldpaypassword = input('oldpaypassword/s');
        $newpaypassword = input('newpaypassword/s');
        $repaypassword = input('repaypassword/s');
        $moble_verify = input('moble_verify/s');

		if (!userid()) {
			$this->error('请先登录！');
		}

        if (!captcha_check($moble_verify,'pay_passwd')) {
            $this->error('图形验证码错误!');
        }

		if (!check($oldpaypassword, 'password')) {
			$this->error('旧交易密码格式错误！');
		}

		if (!check($newpaypassword, 'password')) {
			$this->error('新交易密码格式错误！');
		}

		if ($newpaypassword != $repaypassword) {
			$this->error('确认新密码错误！');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		if (md5($oldpaypassword) != $user['paypassword']) {
			$this->error('旧交易密码错误！');
		}

		if (md5($newpaypassword) == $user['password']) {
			$this->error('交易密码不能和登录密码相同！');
		}
        Db::startTrans();
        try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('paypassword' => md5($newpaypassword)));
            if ($rs !== false) {
                Db::commit();
                $this->success('修改成功');
            } else {
                Db::rollback();
                $this->error('修改失败');
            }

        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('修改失败');
        }

	}

	private function ga()
	{
		if (empty($_POST)) {
			if (!userid()) {
				$this->redirect('/#login');
			}

			$this->assign('prompt_text', model('Text')->get_content('user_ga'));
			$user = Db::name('User')->where(array('id' => userid()))->find();
			$is_ga = ($user['ga'] ? 1 : 0);
			$this->assign('is_ga', $is_ga);

			if (!$is_ga) {
				$ga = new \org\net\GoogleAuthenticator();
				$secret = $ga->createSecret();
				session('secret', $secret);
				$this->assign('Asecret', $secret);
				$qrCodeUrl = $ga->getQRCodeGoogleUrl($user['username'] . '%20-%20' . $_SERVER['HTTP_HOST'], $secret);
				$this->assign('qrCodeUrl', $qrCodeUrl);
				return $this->fetch();
			} else {
				$arr = explode('|', $user['ga']);
				$this->assign('ga_login', $arr[1]);
				$this->assign('ga_transfer', $arr[2]);
				return $this->fetch();
			}
		} else {
			if (!userid()) {
				$this->error('登录已经失效,请重新登录!');
			}

			$delete = '';
			$gacode = trim(input('ga/s'));
			$type = trim(input('type/s'));
			$ga_login = (input('ga_login') == false ? 0 : 1);
			$ga_transfer = (input('ga_transfer') == false ? 0 : 1);

			if (!$gacode) {
				$this->error('请输入验证码!');
			}

			if ($type == 'add') {
				$secret = session('secret');

				if (!$secret) {
					$this->error('验证码已经失效,请刷新网页!');
				}
			} else if (($type == 'update') || ($type == 'delete')) {
				$user = Db::name('User')->where(['id' => userid()])->find();

				if (!$user['ga']) {
					$this->error('还未设置谷歌验证码!');
				}

				$arr = explode('|', $user['ga']);
				$secret = $arr[0];
				$delete = ($type == 'delete' ? 1 : 0);
			} else {
				$this->error('操作未定义');
			}

			$ga = new \org\net\GoogleAuthenticator();
			if ($ga->verifyCode($secret, $gacode, 1)) {
				$ga_val = ($delete == '' ? $secret . '|' . $ga_login . '|' . $ga_transfer : '');
				Db::name('User')->update(array('id' => userid(), 'ga' => $ga_val));
				$this->success('操作成功');
			} else {
				$this->error('验证失败');
			}
		}
	}

	public function moble()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		if ($user['moble']) {
			$user['moble'] = substr_replace($user['moble'], '****', 3, 4);
		} else {
            $this->error('请先认证手机！', url('Home/Order/index'));
        }

		$this->assign('user', $user);
		$this->assign('prompt_text', model('Text')->get_content('user_moble'));
		return $this->fetch();
	}

	public function upmoble()
	{
        $moble = input('moble/d');
        $moble_verify = input('moble_verify/d');

		if (!userid()) {
			$this->error('您没有登录请先登录！');
		}

		if (!check($moble, 'moble2')) {
			$this->error('手机号码格式错误！');
		}

		if (!check($moble_verify, 'd')) {
			$this->error('短信验证码格式错误！');
		}

		if ($moble_verify != session('real_verify')) {
			$this->error('短信验证码错误！');
		}

		if (Db::name('User')->where(array('moble' => $moble))->find()) {
			$this->error('手机号码已存在！');
		}
        Db::startTrans();
        try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('moble' => $moble, 'mobletime' => time()));
            if ($rs !== false) {
                Db::commit();
                $this->success('手机认证成功！');
            } else {
                Db::rollback();
                $this->error('手机认证失败！');
            }

        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('手机认证失败！');
        }

	}

	public function upmoble_qq()
	{
        $moble_new = input('moble_new/d');
        $moble_verify_new = input('moble_verify_new/d');

		if (!userid()) {
			$this->error('您没有登录请先登录！');
		}

		if (!check($moble_new, 'moble2')) {
			$this->error('手机号码格式错误！');
		}

		if (!check($moble_verify_new, 'd')) {
			$this->error('短信验证码格式错误！');
		}

		if ($moble_verify_new != session('real_verify')) {
			$this->error('短信验证码错误！');
		}

		if (Db::name('User')->where(array('moble' => $moble_new))->find()) {
			$this->error('手机号码已存在！');
		}

        Db::startTrans();
        try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('moble' => $moble_new,'username'=>$moble_new, 'mobletime' => time()));
            if ($rs!==false) {
                Db::commit();
                $this->success('手机绑定成功！');
            } else {
                Db::rollback();
                $this->error('手机绑定失败！');
            }

        }catch (Exception $e){
            Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('手机绑定失败！');
        }

	}

	public function alipay()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		model('User')->check_update();
		$this->assign('prompt_text', model('Text')->get_content('user_alipay'));
		$user = Db::name('User')->where(array('id' => userid()))->find();
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function upalipay()
	{
        $alipay = input('alipay/d', NULL);
        $paypassword = input('paypassword/s', NULL);

		if (!userid()) {
			$this->error('您没有登录请先登录！');
		}

		if (!check($alipay, 'moble')) {
			if (!check($alipay, 'email')) {
				$this->error('支付宝账号格式错误！');
			}
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();

		if (md5($paypassword) != $user['paypassword']) {
			$this->error('交易密码错误！');
		}
        Db::startTrans();
		try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('alipay' => $alipay));
            if ($rs !== false) {
                Db::commit();
                $this->success('支付宝认证成功！');
            } else {
                Db::rollback();
                $this->error('支付宝认证失败！');
            }
        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('支付宝认证失败！');
        }

	}

	public function tpwdset()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		$this->assign('prompt_text', model('Text')->get_content('user_tpwdset'));
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function tpwdsetting()
	{
		if (userid()) {
			$tpwdsetting = Db::name('User')->where(array('id' => userid()))->value('tpwdsetting');
			exit($tpwdsetting);
		}
	}

	public function uptpwdsetting()
	{
        $paypassword = input('paypassword/s', NULL);
        $tpwdsetting = input('tpwdsetting/d', NULL);

		if (!userid()) {
			$this->error('请先登录！');
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if (($tpwdsetting != 1) && ($tpwdsetting != 2) && ($tpwdsetting != 3)) {
			$this->error('选项错误！' . $tpwdsetting);
		}

		$user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}

        Db::startTrans();
		try{
            $rs = Db::name('User')->where(array('id' => userid()))->update(array('tpwdsetting' => $tpwdsetting));
            if (false !== $rs) {
                Db::commit();
                $this->success('操作成功！');
            } else {
                Db::rollback();
                $this->error('操作失败！');
            }

        }catch (Exception $e){
		    Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('操作失败！');
        }


	}

	public function bank()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$UserBankType = Db::name('UserBankType')->where(array('status' => 1))->order('id desc')->select();
		$this->assign('UserBankType', $UserBankType);
		$truename = Db::name('User')->where(array('id' => userid()))->value('truename');
		$this->assign('truename', $truename);
		//$UserBank = Db::name('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->limit(1)->select();
		$UserBank = Db::name('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
		
		$this->assign('UserBank', $UserBank);
		$this->assign('prompt_text', model('Text')->get_content('user_bank'));
		return $this->fetch();
	}

	public function upbank()
	{
        $name = input('name/s');
        $bank = input('bank/s');
        $bankprov = input('bankprov/s');
        $bankcity = input('bankcity/s');
        $bankaddr = input('bankaddr/s');
        $bankcard = input('bankcard/d');
        $paypassword = input('paypassword/s');

		if (!userid()) {
			$this->redirect('/#login');
		}

		if (!check($name, 'a')) {
			$this->error('备注名称格式错误！');
		}

		if (!check($bank, 'a')) {
			$this->error('开户银行格式错误！');
		} 
		
		if (!check($bankprov, 'c')) {
			$this->error('开户省市格式错误！');
		}

		if (!check($bankcity, 'c')) {
			$this->error('开户省市格式错误2！');
		}

		if (!check($bankaddr, 'a')) {
			$this->error('开户行地址格式错误！');
		}

		if (!check($bankcard, 'd')) {
			$this->error('银行账号格式错误！');
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		$user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}

 		if (!Db::name('UserBankType')->where(array('title' => $bank))->find()) {
			$this->error('开户银行错误！');
		} 

		$userBank = Db::name('UserBank')->where(array('userid' => userid()))->select();

 		foreach ($userBank as $k => $v) {
			if ($v['name'] == $name) {
				$this->error('请不要使用相同的备注名称！');
			}

			if ($v['bankcard'] == $bankcard) {
				$this->error('银行卡号已存在！');
			}
		} 

		if (10 <= count($userBank)) {
			$this->error('每个用户最多只能添加10个银行卡账户！');
		}
		$rs = Db::name('UserBank')->insert(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1));
		if (false !== $rs) {
			$this->success('银行添加成功！');
		} else {
			$this->error('银行添加失败！');
		}
	}

	public function delbank()
	{
        $id = input('id/d');
        $paypassword = input('paypassword/s');

		if (!userid()) {
			$this->redirect('/#login');
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		$user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}
        $userc2c = Db::name('UserC2cTrade')->where(['bankid' => $id ,'status' =>array('in','0,3')])->find();
		if ($userc2c){
		    $this->error('您在点对点交易中用到了该账号，现在不可以解绑');
        }
		if (!Db::name('UserBank')->where(array('userid' => userid(), 'id' => $id))->find()) {
			$this->error('非法访问！');
		} else if (Db::name('UserBank')->where(array('userid' => userid(), 'id' => $id))->delete()) {
			$this->success('删除成功！');
		} else {
			$this->error('删除失败！');
		}
	}

	public function qianbao()
	{
        $coin = input('coin/s', NULL);

		if (!userid()) {
			$this->redirect('/#login');
		}

		$Coin = Db::name('Coin')->where(array(
			'status' => 1,
			'name'   => array('neq', 'cny')
			))->select();

		if (!$coin) {
			$coin = $Coin[0]['name'];
		}

		$this->assign('xnb', $coin);

		foreach ($Coin as $k => $v) {
			$coin_list[$v['name']] = $v;
		}

		$this->assign('coin_list', $coin_list);
		$userQianbaoList = Db::name('UserQianbao')->where(array('userid' => userid(), 'status' => 1, 'coinname' => $coin))->order('id desc')->select();
		$this->assign('userQianbaoList', $userQianbaoList);
		$this->assign('prompt_text', model('Text')->get_content('user_qianbao'));
		return $this->fetch();
	}

	public function upqianbao()
	{
        if (IS_POST) {
            $coin = input('coin/s');
            $name = input('name/s');
            $addr = input('addr/s');
            $paypassword = input('paypassword/s');
            $memo = trim(input('memo/s'));
            if (!userid()) {
                $this->redirect('/#login');
            }

            if ($_SERVER['HTTP_X_CSRF_TOKEN'] !== session('csrf_token')) {
                $this->error('出现未知错误！');
            }

            if (!check($name, 'a')) {
                $this->error('备注名称格式错误！');
            }

            if (!check($addr, 'dw')) {
                $this->error('钱包地址格式错！');
            }

            if (!check($paypassword, 'password')) {
                $this->error('交易密码格式错误！');
            }

            $user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

            if (md5($paypassword) != $user_paypassword) {
                $this->error('交易密码错误！');
            }

            if (!Db::name('Coin')->where(array('name' => $coin))->find()) {
                $this->error('币种错误！');
            }

            $userQianbao = Db::name('UserQianbao')->where(array('userid' => userid(), 'coinname' => $coin))->select();
            foreach ($userQianbao as $k => $v) {
                if ($v['name'] == $name) {
                    $this->error('请不要使用相同的钱包标识！');
                }

                if ($v['addr'] == $addr) {
                    $this->error('钱包地址已存在！');
                }
            }

            if (10 <= count($userQianbao)) {
                $this->error('每个人最多只能添加10个地址！');
            }
            $rs =Db::name('UserQianbao')->insert(array('userid' => userid(), 'name' => $name, 'memo' =>$memo,'addr' => $addr, 'coinname' => $coin, 'addtime' => time(), 'status' => 1));
            if (false !== $rs) {
                $this->success('添加成功！');
            } else {
                $this->error('添加失败！');
            }
        }
	}
    public function ldqianbao(){
        $radar_add =  input('radar_add/s');
        $paypassword = input('paypassword/s');
        $moble_verify= input('moble_verify/d');
        if (!userid()) {
            $this->error('请先登录！');
        }
        if (!check($radar_add, 'dw')) {
            $this->error('钱包地址格式错1215555521！');
        }
        if (!check($moble_verify, 'd')) {
            $this->error('短信验证码格式错误！');
        }
        if ($moble_verify != session('mytx_verify')) {
            $this->error('短信验证码错误！');
        }
        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }
        if (Db::name('UserCoin')->where(['vbcb'=>$radar_add])->value('userid')) {
            $this->error('一个雷达币钱包地址不可以绑定多个用户');
        }

        Db::startTrans();
        try{
            $rs = Db::name('UserCoin')->where(['userid'=>userid()])->update(array('vbcb' => $radar_add));
            if (false !== $rs) {
                Db::commit();
                $this->success('添加成功！');
            } else {
                Db::rollback();
                $this->error('添加失败！');
            }

        }catch (Exception $e){
            Db::rollback();
            exception_log($e,__FUNCTION__);
            $this->error('添加失败！');
        }

    }
	public function delqianbao()
	{
        $id = input('id/d');
        $paypassword = input('paypassword/s');

		if (!userid()) {
			$this->redirect('/#login');
		}

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}

		if (!check($id, 'd')) {
			$this->error('参数错误！');
		}

		$user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}

		if (!Db::name('UserQianbao')->where(array('userid' => userid(), 'id' => $id))->find()) {
			$this->error('非法访问！');
		} else if (Db::name('UserQianbao')->where(array('userid' => userid(), 'id' => $id))->delete()) {
			$this->success('删除成功！');
		} else {
			$this->error('删除失败！');
		}
	}

	public function log()
	{
		if (!userid()) {
			$this->redirect('/#login');
		}

		$where['status'] = array('egt', 0);
		$where['userid'] = userid();
		$Model = Db::name('UserLog');

		$list = $Model->where($where)->order('id desc')->paginate(10);
        $show = $list->render();

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->assign('prompt_text', model('Text')->get_content('user_log'));
		return $this->fetch();
	}
}

?>