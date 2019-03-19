<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class User extends HomeCommon
{
	public function index()
	{
	    $uid = userid();
		if (!$uid) {
			 return redirect('/#login');
		}

		$user = Db::name('User')->where(['id' => $uid])->find();	
		
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function nameauth()
	{
	    $uid = userid();
		if (!$uid) {
			return redirect('/#login');
		}

		$user = Db::name('User')->where(['id' => $uid])->find();
		if ($user['idcard']) {
			$user['idcard'] = substr_replace($user['idcard'], '********', 6, 8);
		}
		$imgstr = "";
		$imgnum=0;
		if($user['idcardimg1']){
			$img_arr = explode("_",$user['idcardimg1']);

			foreach($img_arr as $k=>$v){
				$imgstr .='<li><ul style="position: relative;left: -210px;width: 150%;height: auto;"><li style="float: left;margin-left: 2px;"><img style="width:280px;height:180px;" src="'.config('TMPL_PARSE_STRING.__DOMAIN__').'/Upload/idcard/'.$v.'" /></li></ul></li>';
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
		$this->assign('allowImg', $allowImg);
		return $this->fetch();
	}

	public function password()
	{
	    $uid = userid();
		if (!$uid) {
			return redirect('/#login');
		}
		return $this->fetch();
	}

	public function uppassword()
	{
        $uid = userid();
        $oldpassword = input('oldpassword/s');
        $newpassword = input('newpassword/s');
        $repassword = input('repassword/s');
        $moble_verify = input('moble_verify/s');

		if (!$uid) {
			$this->error('请先登录！');
		}
		
		if (!session('real_moble')) {
			$this->error('验证码已失效！');
		}

		if ($moble_verify != session('real_moble')) {
			$this->error('手机验证码错误！');
		} else {
			Cache::rm('real_moble');
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

		$password = Db::name('User')->where(['id' => $uid])->value('password');
		if (md5($oldpassword) != $password) {
			$this->error('旧登录密码错误！');
		}

		$rs = Db::name('User')->where(['id' => $uid])->update(['password' => md5($newpassword)]);
		if ($rs) {
			$this->success('修改成功');
		} else {
			$this->error('修改失败');
		}
	}
	
	
	public function uppassword_qq()
	{
        $uid = userid();
        $oldpassword = input('oldpassword/s');
        $newpassword = input('newpassword/s');
        $repassword = input('repassword/s');

		if (!$uid) {
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

		$password = Db::name('User')->where(['id' => $uid])->value('password');

		if (md5($oldpassword) != $password) {
			$this->error('旧登录密码错误！');
		}
		$paypassword = Db::name('User')->where(['id' => $uid])->value('paypassword');

		if(md5($newpassword) == $paypassword){
			$this->error("新密码不能和交易密码一样");
		}

		
		$rs = Db::name('User')->where(['id' => $uid])->update(['password' => md5($newpassword)]);
		if (!($rs===false)) {
			session('userId',null);
			return array('status'=>1,'msg'=>'修改成功');
		} else {
			$this->error('修改失败');
		}
	}

	public function paypassword()
	{
	    $uid = userid();
		if (!$uid) {
			return redirect('/#login');
		}

		$user = Db::name('User')->where(['id' => $uid])->find();
		$this->assign('user', $user);
		return $this->fetch();
	}
	
	public function uppaypassword_qq()
	{
	    $uid = userid();
        $oldpaypassword = input('oldpaypassword/s');
        $newpaypassword = input('newpaypassword/s');
        $repaypassword = input('repaypassword/s');

		if (!$uid) {
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

		$user = Db::name('User')->where(['id' => $uid])->find();

		if (md5($oldpaypassword) != $user['paypassword']) {
			$this->error('旧交易密码错误！');
		}

		if (md5($newpaypassword) == $user['password']) {
			$this->error('交易密码不能和登录密码相同！');
		}

		$rs = Db::name('User')->where(['id' => $uid])->update(['paypassword' => md5($newpaypassword)]);

		if ($rs) {
			return array('status'=>1,'msg'=>'修改成功');
		} else {
			$this->error('修改失败');
		}
	}

	public function uppaypassword()
	{
	    $uid = userid();
	    $oldpaypassword = input('oldpaypassword/s');
        $newpaypassword = input('newpaypassword/s');
        $repaypassword = input('repaypassword/s');
        $moble_verify = input('moble_verify/s');

		if (!$uid) {
			$this->error('请先登录！');
		}

		if (!session('real_moble')) {
			$this->error('验证码已失效！');
		}

		if ($moble_verify != session('real_moble')) {
			$this->error('手机验证码错误！');
		} else {
			Cache::rm('real_moble');
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

		$user = Db::name('User')->where(['id' => $uid])->find();
		if (md5($oldpaypassword) != $user['paypassword']) {
			$this->error('旧交易密码错误！');
		}

		if (md5($newpaypassword) == $user['password']) {
			$this->error('交易密码不能和登录密码相同！');
		}

		$rs = Db::name('User')->where(['id' => $uid])->update(['paypassword' => md5($newpaypassword)]);
		if ($rs) {
			$this->success('修改成功');
		} else {
			$this->error('修改失败');
		}
	}

	public function ga()
	{
		if (empty($_POST)) {
			if (!userid()) {
				return redirect('/#login');
			}

			$this->assign('prompt_text', Db::name('Text')->get_content('user_ga'));
            $user = Db::name('User')->where(array('id' => userid()))->find();
            $is_ga = ($user['ga'] ? 1 : 0);
            $this->assign('is_ga', $is_ga);

			if (!$is_ga) {
				$ga = new \Common\Ext\GoogleAuthenticator();
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

			$ga = new \Common\Ext\GoogleAuthenticator();
			if ($ga->verifyCode($secret, $gacode, 1)) {
				$ga_val = ($delete == '' ? $secret . '|' . $ga_login . '|' . $ga_transfer : '');
				Db::name('User')->save(array('id' => userid(), 'ga' => $ga_val));
				$this->success('操作成功');
			} else {
				$this->error('验证失败');
			}
		}
	}

	public function moble()
	{
		if (!userid()) {
			return redirect('/#login');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		if ($user['moble']) {
			$user['moble'] = substr_replace($user['moble'], '****', 3, 4);
		} else {
            $this->error('请先认证手机！', url('Home/Order/index'));
        }

		$this->assign('user', $user);
		// $this->assign('prompt_text', Db::name('Text')->get_content('user_moble'));
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

		$rs = Db::name('User')->where(array('id' => userid()))->save(array('moble' => $moble, 'mobletime' => time()));

		if ($rs) {
			$this->success('手机认证成功！');
		} else {
			$this->error('手机认证失败！');
		}
	}

	public function upmoble_qq()
	{
        $moble_new = input('moble_new');
        $moble_verify_new = input('moble_verify_new');

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

		$rs = Db::name('User')->where(array('id' => userid()))->update(array('moble' => $moble_new,'username'=>$moble_new, 'mobletime' => time()));

		if (!($rs===false)) {
			$this->success('手机绑定成功！');
		} else {
			$this->error('手机绑定失败！');
		}
	}

	public function alipay()
	{
		if (!userid()) {
			return redirect('/#login');
		}

		Db::name('User')->check_update();
		// $this->assign('prompt_text', D('Text')->get_content('user_alipay'));
		$user = Db::name('User')->where(array('id' => userid()))->find();
		$this->assign('user', $user);
		return $this->fetch();
	}

	public function upalipay()
	{
        $alipay = input('alipay');
        $paypassword = input('paypassword');

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

		$rs = Db::name('User')->where(array('id' => userid()))->update(array('alipay' => $alipay));

		if ($rs) {
			$this->success('支付宝认证成功！');
		} else {
			$this->error('支付宝认证失败！');
		}
	}

	public function tpwdset()
	{
		if (!userid()) {
			return redirect('/#login');
		}

		$user = Db::name('User')->where(array('id' => userid()))->find();
		// $this->assign('prompt_text',D('Text')->get_content('user_tpwdset'));
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
        $paypassword = input('paypassword');
        $tpwdsetting = input('tpwdsetting');

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

		$rs = Db::name('User')->where(array('id' => userid()))->update(array('tpwdsetting' => $tpwdsetting));

		if (!($rs===false)) {
			return array('status'=>1,'msg'=>'操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function bank()
	{
		if (!userid()) {
			return redirect('/#login');
		}

		$UserBankType = Db::name('UserBankType')->where(array('status' => 1))->order('id desc')->select();
		$this->assign('UserBankType', $UserBankType);
		$truename = Db::name('User')->where(array('id' => userid()))->value('truename');
		$this->assign('truename', $truename);
		//$UserBank = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->limit(1)->select();
		$UserBank = Db::name('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
		
		$this->assign('UserBank', $UserBank);
		// $this->assign('prompt_text', D('Text')->get_content('user_bank'));
		return $this->fetch();
	}

	public function upbank()
	{
        $name = input('name');
        $bank = input('bank');
        $bankprov = input('bankprov');
        $bankcity = input('bankcity');
        $bankaddr = input('bankaddr');
        $bankcard = input('bankcard');
        $paypassword = input('paypassword');

		if (!userid()) {
			return redirect('/#login');
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

		if (Db::name('UserBank')->insert(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1))) {
			$this->success('银行添加成功！');
		} else {
			$this->error('银行添加失败！');
		}
	}

	public function delbank()
	{
		if (!userid()) {
			 return $this->error('请登录','/#login');
		}
        $id = input('id');
        $paypassword = input('paypassword');

		

		if (!check($paypassword, 'password')) {
			$this->error('交易密码格式错误！');
		}
		if (!$id) {
			$this->error('参数错误！');
		}

		$user_paypassword = Db::name('User')->where(array('id' => userid()))->value('paypassword');

		if (md5($paypassword) != $user_paypassword) {
			$this->error('交易密码错误！');
		}

		if (!Db::name('UserBank')->where(array('userid' => userid(), 'id' => $id))->find()) {
			$this->error('非法访问！');
		} else if (Db::name('UserBank')->where(array('userid' => userid(), 'id' => $id))->delete()) {
			return array('status'=>1,'msg'=>'删除成功！');
		} else {
			$this->error('删除失败！');
		}
	}

	public function qianbao()
	{
        $coin = input('coin');

		if (!userid()) {
			return redirect('/#login');
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
		// $this->assign('prompt_text', D('Text')->get_content('user_qianbao'));
		return $this->fetch();
	}

	public function upqianbao()
	{
        $coin = input('coin');
        $name = input('name');
        $addr = input('addr');
        $paypassword = input('paypassword');

		if (!userid()) {
			return redirect('/#login');
		}

		if (!check($name, 'a')) {
			$this->error('备注名称格式错误！');
		}

		if (!check($addr, 'dw')) {
			$this->error('钱包地址格式错1215555521！');
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

		if (Db::name('UserQianbao')->insert(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'coinname' => $coin, 'addtime' => time(), 'status' => 1))) {
			return array('status'=>1,'msg'=>'添加成功！');
		} else {
			$this->error('添加失败！');
		}
	}

	public function delqianbao()
	{
        $id = input('id');
        $paypassword = input('paypassword');

		if (!userid()) {
			return redirect('/#login');
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
			return array('status'=>1,'msg'=>'删除成功！');
		} else {
			$this->error('删除失败！');
		}
	}



	public function log()
	{
	    $uid = userid();
		if (!$uid) {
			return redirect('/#login');
		}

		$where['status'] = ['egt', 0];
		$where['userid'] = $uid;
		
		$list = Db::name('UserLog')->where($where)->order('id desc')->paginate(10,false, []);
		$page = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $page);
		// 模板输出
        return $this->fetch();
	}

	public function ranking(){
		return $this->fetch();
	}
	//交易排行旁
	public function rankingajax()
	{
		$type=input('type');

        $jqren_id = Db::name('user')->field('id')->where('usertype=1')->select();
        $jqren_id_arr = [];
        if (count($jqren_id)>0){
            foreach ($jqren_id as $k=>$v){
                array_push($jqren_id_arr,$v['id']);
            }
        }
        $where = '';
		if($type==2){
            $where = 't.status=1 and u.usertype=0';
		}

		if($type==1){
			//当天
			$year = date("Y");
			$month = date("m");
			$day = date("d");
			$addtime = mktime(0,0,0,$month,$day,$year);//当天开始时间戳
			$endtime= mktime(23,59,59,$month,$day,$year);//当天结束时间戳

            $where = 't.addtime between '.$addtime.' and '.$endtime.' and u.usertype=0';
		}
		/*******/
		//总手续费
        $c_field = 'sum(t.mum) as zongshu,sum(t.fee_buy) as buy,u.id,u.username,u.truename,t.release';
        if ($type == 1){
            $weike_fee = round(Db::name('trade_log')
                ->where('(userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')) and addtime between '.$addtime.' and '.$endtime)
                ->sum('fee_buy+fee_sell'),4);
        }else{
            $weike_fee = round(Db::name('trade_log')
                ->where('(userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).'))' )
                ->sum('fee_buy+fee_sell'),4);
        }

        if ($type == 1){
            $weike_mum=round(Db::name('trade_log')
                ->where('(userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')) and addtime between '.$addtime.' and '.$endtime)
                ->sum('mum'),4);
        }else{
            $weike_mum=round(Db::name('trade_log')
                ->where('(userid not in('.implode(',',$jqren_id_arr).') or peerid not in('.implode(',',$jqren_id_arr).')) ')
                ->sum('mum'),4);
        }

		//买
        $sql = 'SELECT sum(a.zongshu) as zongshu,sum(a.buy) as buy,a.id,a.username,a.truename,a.`release` FROM(
                  SELECT '.$c_field.' FROM weike_user AS u LEFT JOIN weike_trade_log t ON u.id = t.userid WHERE '.$where.' GROUP BY u.id
                  UNION
	              SELECT '.$c_field.' FROM  weike_user AS u	LEFT JOIN weike_trade_log t ON u.id = t.peerid	WHERE '.$where.' GROUP BY u.id) a  
	              GROUP BY a.id order by zongshu desc limit 0,20';
        $list = Db::name()->query($sql);


		foreach($list as $key=>$value) {
			//前二十名手续费
			$tow+=$value['buy'];
		}


		foreach($list as $key=>$value) {
			$list[$key]['userfee'] = ($weike_fee * 0.7)*($list[$key]['buy']/$tow);//前二十名
		}

		$this->assign('weike_fee',$weike_fee);
		$this->assign('weike_mum',$weike_mum);
		$this->assign('list', $list);
		$this->assign('type', $type);
		return $this->fetch();
	}
	//数据排序
	/**
	 *二维数组排序
	 * SORT_ASC - 默认，按升序排列。(A-Z)
	 * SORT_DESC - 按降序排列。(Z-A)
	 *
	 * SORT_REGULAR - 默认。将每一项按常规顺序排列
	 * SORT_NUMERIC - 将每一项按数字顺序排列
	 * SORT_STRING - 将每一项按字母顺序排列
	 * $arrays - 需要排序的二维数组
	 * $sort_key - 需要排序的键名
	 */
	function my_sort($list,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC  ){
		if(is_array($list)){
			foreach ($list as $array){
				if(is_array($array)){
					$key_arrays[] = $array[$sort_key];
				}else{
					return false;
				}
			}
		}else{
			return false;
		}
		array_multisort($key_arrays,$sort_order,$sort_type,$list);
		return $list;
	}

    //实名上传图片
    public function userImage()
    {
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->ajaxReturn(array('status'=>0,'msg'=>'上传图片过大'));
//			$this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/idcard/';
        $filename = md5($_FILES['upload_file0']['name']) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);
        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
    }
}

?>