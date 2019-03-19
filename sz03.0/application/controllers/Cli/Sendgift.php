<?php
/**
 * obtc充值大比拼  交易送币
 *
 */
class Cli_SendgiftController extends Ctrl_Cli
{
   //qian20名
	public  function  lishiAction(){

		$coin = 'obtc';
		$activity = ActivityModel::getInstance()->where(['name' => '赠送' . $coin, 'status' => 1])->fRow();

		$time = $activity['start_time'];
		$time1 = $activity['end_time'];
		$exchange = new  Exchange_ObtcModel;
		//查询出活动期间转入和买入的人
		$sql = "SELECT a.uid, b.buy_uid from exchange_obtc a LEFT JOIN order_obtccoin b ON a.uid=b.buy_uid where a.updated >$time and a.updated <$time1 GROUP BY a.uid";
		$exchange1 =   $exchange->query( $sql);

		//活动期间（转入-转出）+（买入-卖出）
		$c = array();
		foreach($exchange1 as $k=>$v)
		{
			$he =   " select(
                (select ifnull(sum(number),0) from exchange_obtc where opt_type='in' and status='成功' and uid=".$v['uid'].")
		 -
               (select ifnull(sum(number),0) from exchange_obtc where opt_type='out' and status='成功' and uid=".$v['uid'].")
	     +
                (select ifnull(sum(number),0) from order_obtccoin where  buy_uid=".$v['uid'].")
		 -
                (select ifnull(sum(number),0) from order_obtccoin where sale_uid=".$v['uid'].")
         ) as balance  ";

			$list =   $exchange->query($he);
			//组装数据
			foreach ($list as $v1)
				$c[] = array('uid'=>$v['uid'],'number'=>$v1['balance']);
		}
		//（转入-转出）+（买入-卖出）进行排序
		$arr1 = array_map(create_function('$n', 'return $n["number"];'), $c);
		array_multisort($arr1,SORT_DESC,$c );

		$userMo = UserModel::getInstance();
		$now = time();
		$ip = Tool_Fnc::realip();

		foreach ($c as $k=>$v){

			if($v['number']>=100){  //（转入-转出）+（买入-卖出）必须大于或等于100

				if($k >= 20) //循环完前20终止程序
				{
					exit("----finish----");
				}
				else
				{
					if($k==0)       //第一名
					{
						$getCoin = 50;

					}else if($k==1) //第二名
					{
						$getCoin = 30;

					}else if($k==2) //第三名
					{
						$getCoin = 20;

					}else if($k==3||$k==4||$k==5||$k==6||$k==7||$k==8||$k==9) //第4 - 10名
					{
						$getCoin = 10;

					}else if($k==10||$k==11||$k==12||$k==13||$k==14||$k==15||$k==16||$k==17||$k==18||$k==19) //第11 - 20名
					{
						$getCoin = 5;

					}else{//用户不在前20 跳出循环
						break;
					}
				}

			}
			else//用户不满足>=100 跳出循环
			{
				break;
			}
			//更新用户余额
			$r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip,$v['uid']));
			if(!$r){
				$userMo->back();
				Tool_Log::wlog(sprintf("obtc前20送币失败, uid:%s", $v['uid']), APPLICATION_PATH . '/log/obtc', true);
				return;
			}
			//更新来源用户余额
			$r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $this->mCurUser['uid']));
			if (!$r) {
				$userMo->back();
				Tool_Log::wlog(sprintf("obtc跟新用户来源送币失败, uid:%s", $v['uid']), APPLICATION_PATH . '/log/obtc', true);
				return;
			}
			//领取记录
			$urData = array(
				'uid' =>$v['uid'],
				'aid' => $activity['id'],
				'coin' => $coin,
				'created' => $now,
				'updated' => $now,
				'number' => $getCoin,
			);

			$r = UserRewardModel::getInstance()->save($urData);

			if (!$r) {
				$userMo->back();
				Tool_Log::wlog(sprintf("obtc前20添加送币记录失败, uid:%s", $v['uid']), APPLICATION_PATH . '/log/obtc', true);
				return;
			}
			//记录前20人员
			$ranking = $k+1;
			$phone = "select mo from user where uid = {$v['uid']}";
			$phone =   $userMo->query($phone);
			Tool_Log::wlog(sprintf("obtc第{$ranking}名,号码:{$phone[0]['mo']} uid:%s", $v['uid']), APPLICATION_PATH . '/log/obtc', true);

		}

		// show($exchange->getLastSql());

	}



//交易送币
	public function sendobtcAction()
	{
		$obtcmo=new Order_CoinModel;
		$obtcmo->designTable('obtc');
		$starttime=strtotime('2018-3-8 10:00');
		$endtime = strtotime('2018-3-15 10:00');
		$table='order_obtccoin';
		$sql="select sum(number) total,uid from (select id,number,buy_uid uid from $table where created between $starttime and $endtime union select id,number,sale_uid uid from $table where created between $starttime and $endtime) as a group by a.uid order by total desc limit 1000";
		$data=$obtcmo->query($sql);
		foreach($data as $k=>$v){
			$this->add($v['uid']);
		}
		exit("----finish----");
	}

	//单人交易送币
	public function oneAction($uid)
	{
		$userMo = UserModel::getInstance();
		$uid=(int)$uid;
		$dd=$userMo->where("uid=$uid")->fList();
		if(empty($dd)){
			exit("--uid $uid 不存在--");
		}else{
			$this->add($uid);
			exit('--finish--');
		}

	}

	//obtc 活动一
	public function add($uid){
		$coin='obtc';
		$ip = Tool_Fnc::realip();
		$getCoin=rand(10,20)/100;//0.1-0.2
		$this->mCurUser['uid']= $uid;
		$now=time();
		$moName = 'Exchange_' . ucfirst($coin) . 'Model';
		$exchangeMo = $moName::getInstance();
		$userMo = UserModel::getInstance();
		//赠送配置
		$activity = ActivityModel::getInstance()->where(['name' => '赠送' . $coin, 'status' => 1])->fRow();
		$userMo->begin();
		$exData = array(
			'uid' => $this->mCurUser['uid'],
			'admin' => 6,
			'email' => '',
			'wallet' => '',
			'opt_type' => 'in',
			'number' => $getCoin,
			'created' => $now,
			'updated' => $now,
			'is_out' => 1,
			'createip' => $ip,
			'bak' => '领取' . $coin,
			'status' => '成功',
			'txid' => '',
		);
		if (!$exchangeMo->save($exData)) {
			$userMo->back();
			Tool_Log::wlog(sprintf("obtc交易送币失败, uid:%s", $uid), APPLICATION_PATH . '/log/obtc', true);
			return;
		}

		//更新用户余额
		$r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip, $this->mCurUser['uid']));

		if (!$r) {
			$userMo->back();
			$dd = $userMo->where("uid=$uid")->fList();
			if (empty($dd)) {
				Tool_Log::wlog(sprintf("obtc交易送币失败, uid:%s,用户不存在", $uid), APPLICATION_PATH . '/log/obtc', true);
			}else{
				Tool_Log::wlog(sprintf("obtc交易送币失败, uid:%s", $uid), APPLICATION_PATH . '/log/obtc', true);
			}
			return;
		}
		//更新来源用户余额
		$r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $this->mCurUser['uid']));
		if (!$r) {
			$userMo->back();
			Tool_Log::wlog(sprintf("obtc交易送币失败, uid:%s", $uid), APPLICATION_PATH . '/log/obtc', true);
			return;
		}

		//领取记录
		$urData = array(
			'uid' => $this->mCurUser['uid'],
			'aid' => $activity['id'],
			'coin' => $coin,
			'created' => $now,
			'updated' => $now,
			'number' => $getCoin,
		);

		$r = UserRewardModel::getInstance()->save($urData);
		if (!$r) {
			$userMo->back();
			Tool_Log::wlog(sprintf("obtc交易送币失败, uid:%s", $uid), APPLICATION_PATH . '/log/obtc', true);
			return;

		}
		$userMo->commit();
		Tool_Session::mark($this->mCurUser['uid']);
	}



//read 活动一 时间要改
	public function sendreadAction()
	{
		$usermo = new UserModel();
		$starttime = 1527213600;
		$endtime= 1527577200;
		//$starttime = 0;
		//$endtime = time();
		$sql = "select * from (select f.uid,f.mo,f.innumber,f.outnumber,f.buynumber,f.salenumber,sum(f.innumber-f.outnumber+f.buynumber-f.salenumber) total from
(select a.uid,a.mo,IFNULL(b.innumber,0) innumber,IFNULL(c.outnumber,0) outnumber,IFNULL(d.buynumber,0) buynumber,IFNULL(e.salenumber,0) salenumber from
(select uid,mo from user where (read_over+read_lock)>0 and uid>10 order by (read_over+read_lock) desc,uid asc) a LEFT JOIN
(select uid,sum(number) innumber from exchange_read where opt_type='in' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) b on a.uid=b.uid left join
(select uid,sum(number) outnumber from exchange_read where opt_type='out' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) c on a.uid=c.uid left join
(select buy_uid,sum(number) buynumber from order_readcoin where created BETWEEN $starttime and $endtime group by buy_uid) d on a.uid=d.buy_uid left join
(select sale_uid,sum(number) salenumber from order_readcoin where created BETWEEN $starttime and $endtime group by sale_uid) e on a.uid=e.sale_uid) as f GROUP BY f.uid) as e where e.total>0 order by e.total desc,e.uid asc  limit 0,50";
		$data = $usermo->query($sql);
		foreach ($data as $kk => $v) {
			$k=$kk+1;
			if($k==1){
				$number=20000;
			}elseif($k==2){
				$number = 10000;
			} elseif ($k == 3) {
				$number = 5000;

			} elseif (4<=$k && $k<= 10) {
				$number = 1000;
			} elseif (11 <= $k && $k <= 50) {
				$number = 500;
			}
			$this->readadd($v['uid'], $number,1);

		}
		exit("----finish----");
	}

	//read 活动二 时间要改
	public function sendreadtwoAction()
	{

		$obtcmo = $usermo = new UserModel();
		$starttime = 1527213600;
		$endtime = 1527577200;
		//$starttime = 0;
		//$endtime = time();
		$sql = "select * from (select f.uid,f.mo,f.innumber,f.outnumber,f.buynumber,f.salenumber,sum(f.buynumber-f.salenumber) buytotal from
(select a.uid,a.mo,IFNULL(b.innumber,0) innumber,IFNULL(c.outnumber,0) outnumber,IFNULL(d.buynumber,0) buynumber,IFNULL(e.salenumber,0) salenumber from
(select uid,mo from user where (read_over+read_lock)>0 and uid>10 order by (read_over+read_lock) desc,uid asc) a LEFT JOIN
(select uid,sum(number) innumber from exchange_read where opt_type='in' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) b on a.uid=b.uid left join
(select uid,sum(number) outnumber from exchange_read where opt_type='out' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) c on a.uid=c.uid left join
(select buy_uid,sum(number) buynumber from order_readcoin where created BETWEEN $starttime and $endtime group by buy_uid) d on a.uid=d.buy_uid left join
(select sale_uid,sum(number) salenumber from order_readcoin where created BETWEEN $starttime and $endtime group by sale_uid) e on a.uid=e.sale_uid) as f GROUP BY f.uid) as e where e.buytotal>100000  order by e.buytotal desc";
		$data = $obtcmo->query($sql);
		$total=count($data);
		$number = bcdiv(20000, $total, 20);
		/*if ($total < 10) {
			$number = bcdiv(100000, 100, 20);
		} else {
			$number = bcdiv(100000, $total, 20);
		}*/
		foreach ($data as $kk => $v) {

			$this->readadd($v['uid'], $number,2);

		}
		exit("----finish----");
	}

	//read 单人送币
	public function onesendAction($uid,$number,$act)
	{
		$userMo = UserModel::getInstance();
		$uid = (int)$uid;
		$dd = $userMo->where("uid=$uid")->fList();
		if (empty($dd)) {
			exit("--uid $uid 不存在--");
		} else {
			$this->readadd($uid, $number, $act);
			exit('--finish--');
		}

	}
	//read 活动一 加余额逻辑 $act 1,2 表示活动一还是活动二
	public function readadd($uid,$number,$act)
	{
		$coin = 'read';
		$ip = Tool_Fnc::realip();
		$getCoin = $number;
		$now = time();
		$moName = 'Exchange_' . ucfirst($coin) . 'Model';
		$exchangeMo = $moName::getInstance();
		$userMo = UserModel::getInstance();
		//赠送配置
		$activity = ActivityModel::getInstance()->where(['name' => '赠送' . $coin, 'status' => 1])->fRow();

		$exData = array(
			'uid' =>$uid,
			'admin' => 6,
			'email' => '',
			'wallet' => '',
			'opt_type' => 'in',
			'number' => $getCoin,
			'created' => $now,
			'updated' => $now,
			'is_out' => 1,
			'createip' => $ip,
			'bak' => '领取' . $coin.'_'. $act,
			'status' => '成功',
			'txid' => '',
		);
		$oldlist=$exchangeMo->where("bak='$exData[bak]' and uid=$exData[uid] and admin=6 and opt_type='in' and number=$exData[number]")->fList();
		if(!empty($oldlist)){//已送过 返回
			return;
		}
		$userMo->begin();
		if (!$exchangeMo->save($exData)) {
			$userMo->back();
			Tool_Log::wlog(sprintf("read活动 $act 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/read', true);
			return;
		}

		//更新用户余额
		$r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip, $uid));

		if (!$r) {
			$userMo->back();
			$dd = $userMo->where("uid=$uid")->fList();
			if (empty($dd)) {
				Tool_Log::wlog(sprintf("read活动 $act 送币失败, uid:%s,用户不存在", $uid), APPLICATION_PATH . '/log/read', true);
			} else {
				Tool_Log::wlog(sprintf("read活动 $act 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/read', true);
			}
			return;
		}
		//更新来源用户余额
		$r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $uid));
		if (!$r) {
			$userMo->back();
			Tool_Log::wlog(sprintf("read活动 $act 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/read', true);
			return;
		}

		//领取记录
		$urData = array(
			'uid' =>$uid,
			'aid' => $activity['id'],
			'coin' => $coin,
			'created' => $now,
			'updated' => $now,
			'number' => $getCoin,
		);

		$r = UserRewardModel::getInstance()->save($urData);
		if (!$r) {
			$userMo->back();
			Tool_Log::wlog(sprintf("read活动 $act 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/read', true);
			return;

		}
		if(!$userMo->commit()){
			$userMo->back();
			Tool_Log::wlog(sprintf("read活动 $act 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/read', true);
			return;
		}
		Tool_Session::mark($uid);
	}


	//ait 活动 邀请排行榜送币
	public function sendaitAction()
	{
		$coin='ait';
		$actmo=new ActivityModel();
		$activity = $actmo->where("name='赠送$coin' and status=1")->fRow();
		$starttime = $activity['start_time'];
		$endtime = $activity['end_time'];
		$sql = "select * from (
select Inviter_uid,Inviter_mo,count(be_invited_uid) uidtotal,count(status) autototal from (
 select a.from_uid Inviter_uid,b.mo Inviter_mo,b.area Inviter_area,a.uid be_invited_uid,a.mo be_invited_mo
,a.area be_invited_area,a.created be_invited_created ,e.name Inviter_name,f.name be_invited_name,f.status
from user a
left join user b on a.from_uid=b.uid
left join autonym e on a.from_uid=e.uid and e.status=2
left join autonym f on a.uid=f.uid and f.status=2
where a.created>$starttime and a.created<$endtime and a.from_uid!=0) as g group by g.Inviter_uid ) as h where h.autototal>=10 order by h.autototal desc,h.uidtotal desc,h.Inviter_uid asc limit 50";
	/*	$sql = "select * from
(select Inviter_uid,inviter_mo,count(be_invited_uid) invited_total ,sum(number) number from
(select c.*,d.number,d.coin,e.name Inviter_name,e.status Inviter_status,f.name be_invited_name,f.status be_invited_status,f.updated be_invited_updated from
(select a.from_uid Inviter_uid,b.mo Inviter_mo,b.area Inviter_area,a.uid be_invited_uid,a.mo be_invited_mo,a.area be_invited_area,a.created be_invited_created from user a
left join user b on a.from_uid=b.uid where a.created>$starttime and a.created<$endtime and a.from_uid!=0) as c
left join user_reward d on c.be_invited_uid=d.be_invited and (d.type=3 and d.coin='$coin')
left join autonym e on c.Inviter_uid=e.uid left join autonym f on c.be_invited_uid=f.uid) as g GROUP BY g.inviter_mo) as h where h.number>=150 order by number desc,invited_total desc,Inviter_uid asc limit 50";
		*/
		$data = $actmo->query($sql);
		foreach ($data as $kk => $v) {
			$order=$kk+1;
		if($order==1){
			$number=50000;
		}elseif($order == 2){
			$number = 30000;
		} elseif ($order ==3) {
			$number = 20000;
		} elseif ($order >= 4&& $order<=10) {
			$number = 8000;
		} elseif ($order >= 11 && $order <= 50) {
			$number = 5000;
		} /*elseif ($order >= 51 && $order <= 100) {
			$number = 1000;
		} elseif ($order >= 101 && $order <= 200) {
			$number = 500;
		}*/
			$this->aitadd($v['Inviter_uid'], $number);

		}

		exit("----finish----");
	}
	//ait 活动 加余额逻辑
	public function aitadd($uid, $number)
	{
		$coin = 'ait';
		$ip = Tool_Fnc::realip();
		$getCoin = $number;
		$now = time();
		$moName = 'Exchange_' . ucfirst($coin) . 'Model';
		$exchangeMo = $moName::getInstance();
		$userMo = UserModel::getInstance();
		//赠送配置
		$activity = ActivityModel::getInstance()->where(['name' => '赠送' . $coin, 'status' => 1])->fRow();

		$exData = array(
			'uid' => $uid,
			'admin' => 6,
			'email' => '',
			'wallet' => '',
			'opt_type' => 'in',
			'number' => $getCoin,
			'created' => $now,
			'updated' => $now,
			'is_out' => 1,
			'createip' => $ip,
			'bak' => '领取' . $coin,
			'status' => '成功',
			'txid' => '',
		);
		$oldlist = $exchangeMo->where("bak='$exData[bak]' and uid=$exData[uid] and admin=6 and opt_type='in' and number=$exData[number]")->fList();
		if (!empty($oldlist)) {//已送过 返回
			return;
		}
		$userMo->begin();
		if (!$exchangeMo->save($exData)) {
			$userMo->back();
			Tool_Log::wlog(sprintf("ait活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/ait', true);
			return;
		}

		//更新用户余额
		$r = $userMo->exec(sprintf('update user set ' . $coin . '_lock = ' . $coin . '_lock+%s, updated=%d, updateip="%s" where uid=%d', $getCoin, $now, $ip, $uid));

		if (!$r) {
			$userMo->back();
			$dd = $userMo->where("uid=$uid")->fList();
			if (empty($dd)) {
				Tool_Log::wlog(sprintf("ait活动  送币失败, uid:%s,用户不存在", $uid), APPLICATION_PATH . '/log/ait', true);
			} else {
				Tool_Log::wlog(sprintf("ait活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/ait', true);
			}
			return;
		}
		//更新来源用户余额
		$r = $userMo->exec(sprintf('update user set ' . $coin . '_over = ' . $coin . '_over-%s, updated=%d, updateip="%s" where uid=6', $getCoin, $now, $ip, $uid));
		if (!$r) {
			$userMo->back();
			Tool_Log::wlog(sprintf("ait活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/ait', true);
			return;
		}

		//领取记录
		$urData = array(
			'uid' => $uid,
			'aid' => $activity['id'],
			'coin' => $coin,
			'created' => $now,
			'updated' => $now,
			'number' => $getCoin,
			'type'=>0
		);

		$r = UserRewardModel::getInstance()->save($urData);
		if (!$r) {
			$userMo->back();
			Tool_Log::wlog(sprintf("ait活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/ait', true);
			return;

		}
		if (!$userMo->commit()) {
			$userMo->back();
			Tool_Log::wlog(sprintf("ait活动 送币失败, uid:%s,数量：%d", $uid, $getCoin), APPLICATION_PATH . '/log/ait', true);
			return;
		}
		Tool_Session::mark($uid);
	}

}