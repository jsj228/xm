<?php
class C2ctradeModel extends Orm_Base{
	public $table = 'c2c_trade';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '挂单者ID'),
		'price' => array('type' => "decimal(20,8)", 'comment' => '价格'),
		'num' => array('type' => "decimal(20,8)", 'comment' => '匹配价格'),
		'deal' => array('type' => "decimal(20,8)", 'comment' => '剩余数量'),
		'type' => array('type' => "tinyint(2)", 'comment' => '1，为买 2，为卖'),
		'coin'=> array('type' => "varchar(15)", 'comment' => '币种'),
		'fee'=>array('type' => "decimal(20,8)", 'comment' => '手续费'),
		'moble' => array('type' => "varchar(11)", 'comment' => '手机号'),
		'tradeno'=> array('type' => "varchar(15)", 'comment' => '流水号'),
		'addtime' => array('type' => "int(10)", 'comment' => '时间'),
		'appeal' => array('typepe' => "tinyint(2)", 'comment' => '0取消 1申诉'),
		'matching'=>array('typepe' => "int(11)", 'comment' => '匹配者ID'),
		'matchtime'=>array('typepe' => "int(10)", 'comment' => '匹配时间'),
		'bank'=>array('typepe' => "tinyint(2)", 'comment' => '1网银'),
		'wechat'=>array('typepe' => "tinyint(2)", 'comment' => '2微信'),
		'alipay'=>array('typepe' => "tinyint(2)", 'comment' => '3支付宝'),
		'status'=>array('typepe' => "tinyint(2)", 'comment' => '0，交易中 1，完成'),
	);
	public $pk = 'uid';
	//撮合买卖
	public function c2cmarket($tradeno)
	{
		$this->_ajax_islogin();
		$C2ctrade = new C2ctradeModel();
		$UserModel = new UserModel();
		$C2ctradelog = new C2ctradelogModel();
		$trade =$C2ctrade->where(['tradeno' => $tradeno])->fRow();
		while (!$trade){
			$trade = $C2ctrade->where(['tradeno' => $tradeno])->fRow();
			sleep(1);
		}
		if ($trade['matching'] != 0) {
			$this->showMsg('订单已成功匹配!');
		}

		//手续费计算
		$fee_sell = 10;
		if ($trade['type'] == 1) {
			$where="status=0 and type=2 and matching=0 and (bank=1 or wechat=2 or alipay=3)";
			$C2ctrade->begin();
			$sell =$C2ctrade->where($where)->order('addtime asc,id asc')->fRow();

			if ($sell) {
				$coin=$UserModel->where(['uid'=>$sell['uid']])->fRow();
				//判断卖家资产
				if($fee_sell>floatval($coin['cnyx_over'])){
					$this->showMsg('卖家资产不足!');
				}
				//匹配处理用户金额
				if($sell['deal']>$trade['deal']){

					$rs[] = $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['deal' =>$sell['deal']-$trade['deal']]);
					$rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' =>0]);
				}else{
					$rs[] = $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['deal' =>0]);
					$rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' =>$trade['deal']-$sell['deal']]);

				}
				$rs[]= $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['num'=>$sell['deal']>$trade['deal']? $trade['deal']:$sell['deal']]);
				$rs[]= $C2ctrade->where(['uid' => $trade['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['num'=>$sell['deal']>$trade['deal']? $trade['deal']:$sell['deal']]);
				$rs[] = $C2ctrade->where(['uid' => $sell['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $sell['tradeno']])->update(['fee'=>$fee_sell,'matching' => $this->mCurUser['uid'], 'matchtime' => time()]);
				$rs[] = $C2ctrade->where(['uid' => $this->mCurUser['uid'], 'type' => 1, 'status' => 0,'tradeno' => $tradeno])->update(['matching' =>$sell['uid'], 'matchtime' => time()]);
				$buyname=$UserModel->where(['uid'=>$this->mCurUser['uid']])->fRow();
				$sellname=$UserModel->where(['uid'=> $sell['uid']])->fRow();
				$datalog=[
					'buyid' => $this->mCurUser['uid'],
					'sellid' => $sell['uid'],
					'coinname' => $trade['coin'],
					'price' =>$sell['deal']>$trade['deal']? $sell['deal']-$trade['deal']:$trade['deal']-$sell['deal'],
					'buytruename' => 0,
					'buymoble' => $buyname['mo']?$buyname['mo']:$buyname['email'],
					'buytradeno' => $tradeno,
					'selltruename' => 0,
					'sellmoble' => $sellname['mo']?$sellname['mo']:$sellname['email'],
					'selltradeno' => $sell['tradeno'],
					'addtime' => time(),
					'bank' =>$sell['bank'] ,
					'wechat' =>$sell['wechat'] ,
					'alipay' =>$sell['alipay'] ,
					'type' => 2,
					'feesell'  =>  $fee_sell,
					'status' => 0,
				];
				$rs[]= $C2ctradelog->insert($datalog);
				if ($rs) {
					$C2ctrade->commit();
					$this->showMsg('匹配成功!');
				}
			}else{
				$C2ctrade->rollback();
				$this->showMsg('下单成功!');
			}
		} else if ($trade['type'] == 2) {
			$where="status=0 and type=1 and matching=0  and (bank=1 or wechat=2 or alipay=3)";
			$C2ctrade->begin();
			$buy =$C2ctrade->where($where)->order('addtime asc,id asc')->fRow();

			if ($buy) {
				$cnyx =  $UserModel->where("uid={$this->mCurUser['uid']}")->fRow();//用户实际财产
				$usercnyx=floatval($cnyx['cnyx_over']);
				if (floatval($fee_sell) >$usercnyx ) {
					$this->showMsg('您的余额不足!');
				}

				//匹配处理用户金额
				if($buy['deal']>$trade['deal']){
					//匹配处理用户金额
					$rs[] = $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['deal' =>$buy['deal']-$trade['deal']]);
					$rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' =>0]);
				}else{
					//匹配处理用户金额
					$rs[] = $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['deal' =>0]);
					$rs[] = $C2ctrade->where(['uid' => $trade['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['deal' =>$trade['deal']-$buy['deal']]);
				}
				$rs[]= $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['num'=>$buy['deal']>$trade['deal']? $trade['deal']:$buy['deal']]);
				$rs[]= $C2ctrade->where(['uid' => $trade['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $trade['tradeno']])->update(['num'=>$buy['deal']>$trade['deal']? $trade['deal']:$buy['deal']]);
				$rs[] = $C2ctrade->where(['uid' => $buy['uid'], 'type' => 1, 'status' => 0, 'tradeno' => $buy['tradeno']])->update(['matching' => $this->mCurUser['uid'], 'matchtime' => time()]);
				$rs[] = $C2ctrade->where(['uid' => $this->mCurUser['uid'], 'type' => 2, 'status' => 0, 'tradeno' => $tradeno])->update(['fee'=>$fee_sell,'matching' =>$buy['uid'], 'matchtime' => time()]);
				$sellname=$UserModel->where(['uid'=>$this->mCurUser['uid']])->fRow();
				$buyname=$UserModel->where(['uid'=> $buy['uid']])->fRow();
				//判断卖家资产
				$Usercoin = new UsercoinModel();
				$usercoin=$Usercoin->where(['uid'=> $trade['uid']])->fRow();
				if($usercoin['balance']<$fee_sell){
					$this->showMsg('卖家资产不足!');
				}
				$datalog=[
					'buyid' =>$buy['uid'],
					'sellid' =>$this->mCurUser['uid'] ,
					'coinname' => $trade['coin'],
					'price' =>$buy['deal']>$trade['deal']? $buy['deal']-$trade['deal']:$trade['deal']-$buy['deal'],
					'buytruename' => 0,
					'buymoble' => $buyname['mo']?$buyname['mo']:$buyname['email'],
					'buytradeno' => $buy['tradeno'],
					'selltruename' => 0,
					'sellmoble' => $sellname['mo']?$sellname['mo']:$sellname['email'],
					'selltradeno' =>$tradeno ,
					'addtime' => time(),
					'bank' =>$buy['bank'] ,
					'wechat' =>$buy['wechat'] ,
					'alipay' =>$buy['alipay'] ,
					'type' => 2,
					'feesell'  =>  $fee_sell,
					'status' => 0,
				];

				$rs[]= $C2ctradelog->insert($datalog);
				if ($rs) {
					$C2ctrade->commit();
					$this->showMsg('匹配成功!');
				}
			}else{
				$C2ctrade->rollback();
				$this->showMsg('下单成功!');
			}
		}
	}
}
