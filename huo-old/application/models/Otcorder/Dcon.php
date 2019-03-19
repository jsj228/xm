<?php
class Otcorder_DconModel extends Orm_Base{
    protected $_config='otc';
	public $table = 'order_dcon';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'm_id' => array('type' => "int(11) unsigned", 'comment' => '发布交易广告id'),
		'from_uid' => array('type' => "int(11) unsigned", 'comment' => '發佈廣告用戶id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'order_sn' => array('type' => "varchar(20)", 'comment' => '订单号'),
		'price' => array('type' => "decimal(20,8) unsigned", 'comment' => '交易价格'),
		'order_price' => array('type' => "decimal(20,8) unsigned", 'comment' => '订单金额价格'),
		'number' => array('type' => "decimal(20,8) unsigned", 'comment' => '订单数量'),
		'opt' => array('type' => "tinyint(1)", 'comment' => '操作类型: 1.超时关闭，2.买家申诉，3卖家申诉，4.买家撤销申诉，5.卖家撤销申诉'),
		'status' => array('type' => "tinyint(1)", 'comment' => '状态.0:待付款，1:待确认，2:已完成，3:已关闭'),
		'type'   => array('type' => "tinyint(1)", 'comment' => '类型：0：求购的广告，点击購買，1出售广告，点击出售'),
		'flag' => array('type' => "enum('buy','sale')", 'comment' => '买卖标志'),
		'reupdated' => array('type' => "int(11) unsigned", 'comment' => '释放时间'),
		'appupdated' => array('type' => "int(11) unsigned", 'comment' => '申诉时间'),
		'payupdated' => array('type' => "int(11) unsigned", 'comment' => '支付时间'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
		'pay_type' => array('type' => "tinyint(1)", 'comment' => '支付方式：1.微信，2支付宝，3银行卡'),
		'pay_time' => array('type' => "int(11) unsigned", 'comment' => '付款时间'),
		'fee' => array('type' => "decimal(40,20)", 'comment' => '手续费'),
		'userbak'=>array('type'=>"varchar(255)", "comment"=> '留言'),
		'unread'=>array('type'=>"tinyint(1)", "comment"=> '0未读，1已读'),
		'appealcontent' => array('type' => "varchar(600)", 'comment' => '申诉理由'),
		'appealphoto' => array('type' => "varchar(80)", 'comment' => '申诉圖片'),
	);

	public $pk = 'id';

	const OPT_TRADE = 1; #交易;
	const OPT_FEE_BUY = 2; #买入手续费;
	const OPT_FEE = 3; #卖出手续费;

	/**
	 * 取消交易
	 * @param $arr 参数=['id','uid']
	 * @return array
	 */
	public function orderCancel($arr,$coin='btc')
	{
		$mkMo = new Market_BtcModel();
		$time = time();
		if($arr['id'])
		{
			$data = $this->where("id=".addslashes($arr['id']))->fRow();
		}

		if(!$data)
		{
			// 该订单不存在
			$dds =  [
				$GLOBALS['MSG']['ORDER_NO_EXIST'],0,['data'=>[],'reUrl'=>'/user/userTrade']
			];
			return $dds;
		}

		$coinName = $coin;
		$coinNameUc = ucfirst($coinName);
		$coinOver = $coinName."_over";
		$coinLock = $coinName."_lock";

		if($data['status']==0)
		{
			//取消 status=3
			$updata = [
				'id'  => $arr['id'],
				'updated'  =>$time,
				'status'   => '3'
			];
			$this->begin();

			// 更新訂單狀態
			if(!$this->update($updata))
			{
				$this->back();
				$dds =  [
					$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
				];
				return $dds;
			}

			// 查看该广告信息
			$marketData = $mkMo->lock()->where(['id'=>$data['m_id']])->fRow();
			if($marketData)
			{
				// 计算交易限制价格
				// 如果是溢价
				if($marketData['pricetype']==2)
				{
					// 如果是求购信息
					if($marketData['flag']=='buy')
					{
						// 如果判断溢价价格大于最低价格（用户设置的overflowprice）,则计算采用最低价格
						if($marketData['price']>$marketData['overflowprice'])
						{
							// 取消后的剩余量
							$numberOver = bcadd($marketData['numberover'],$data['number'],8);
							// 取消后的剩余量计算出来的价格
							$priceOver = bcmul($numberOver,$marketData['overflowprice'],2);
							// cny四舍五入
//							$priceOver = sprintf("%.2f", $priceOver);
						}
						else
						{
							// 取消后的剩余量
							$numberOver = bcadd($marketData['numberover'],$data['number'],8);
							// 取消后的剩余量计算出来的价格
							$priceOver = bcmul($numberOver,$marketData['price'],2);
							// cny四舍五入
//							$priceOver = sprintf("%.2f", $priceOver);
						}
					}
					elseif($marketData['flag']=='sale') //如果是出售信息
					{
						// 如果判断溢价价格小于最低价格（用户设置的overflowprice）,则计算采用最低价格
						if($marketData['price']<$marketData['overflowprice'])
						{
							// 取消后的剩余量
							$numberOver = bcadd($marketData['numberover'],$data['number'],8);
							// 取消后的剩余量计算出来的价格
							$priceOver = bcmul($numberOver,$marketData['overflowprice'],2);
							// cny四舍五入
//							$priceOver = sprintf("%.2f", $priceOver);
						}
						else
						{
							// 取消后的剩余量
							$numberOver = bcadd($marketData['numberover'],$data['number'],8);
							// 取消后的剩余量计算出来的价格
							$priceOver = bcmul($numberOver,$marketData['price'],2);
							// cny四舍五入
//							$priceOver = sprintf("%.2f", $priceOver);
						}
					}
				}
				else //固定价格
				{
					// 取消后的剩余量
					$numberOver = bcadd($marketData['numberover'],$data['number'],8);
					// 取消后的剩余量计算出来的价格
					$priceOver = bcmul($numberOver,$marketData['price'],2);
					// cny四舍五入
//					$priceOver = sprintf("%.2f", $priceOver);
				}
			}
			else
			{
				$this->back();
				$dds =  [
					$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
				];
				return $dds;
			}

			// 如果该广告信息是已全部成交status==2,撤销了交易就要把状态改成部分成交，status==1;其他不用更改状态
			if($marketData['status']==2)
			{
				if($numberOver==$marketData['number'])
				{
					$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$marketData['last_min_price'].",max_price=".$marketData['last_max_price'].",updated=".$time.",status=1 where id=".$data['m_id'];
				}
				else
				{
					// 如果剩餘量價值大於初始最大交易限制,交易限制不用修改
					if($priceOver>=$marketData['last_max_price'])
					{
						$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
						        max_price=".$marketData['last_max_price'].",updated=".$time.",status=1 where id=".$data['m_id'];
					}
					// 如果剩余量价值，在初始最大和最小交易限制之间，则只用修改最大交易限制
					else if($priceOver>=$marketData['last_min_price']&&$priceOver<$marketData['last_max_price'])
					{
						// 返还广告剩余数量和修改交易限制
						$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        max_price=".$priceOver.",updated=".$time.",status=1 where id=".$data['m_id'];
					}
					else // 如果剩余量价值小于初始最小交易限制，则最大和最小都需要修改，且都一样
					{
						$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$priceOver.",max_price=".$priceOver.",updated=".$time.",status=1 where id=".$data['m_id'];
					}
				}
			}
			else
			{
				if($numberOver==$marketData['number'])
				{
					$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$marketData['last_min_price'].",max_price=".$marketData['last_max_price'].",updated=".$time." where id=".$data['m_id'];
				}
				else
				{
					// 如果剩餘量價值大於初始最大交易限制,交易限制不用修改
					if($priceOver>=$marketData['last_max_price'])
					{
						$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
						        max_price=".$marketData['last_max_price'].",updated=".$time." where id=".$data['m_id'];
					}
					// 如果剩余量价值，在初始最大和最小交易限制之间，则只用修改最大交易限制
					else if($priceOver>=$marketData['last_min_price']&&$priceOver<$marketData['last_max_price'])
					{
						// 返还广告剩余数量和修改交易限制
						$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        max_price=".$priceOver.",updated=".$time." where id=".$data['m_id'];
					}
					else // 如果剩余量价值小于初始最小交易限制，则最大和最小都需要修改，且都一样
					{
						$sql = "update market_btc set numberover=numberover+".$data['number'].",numberdeal=numberdeal-".$data['number'].",
					        min_price=".$priceOver.",max_price=".$priceOver.",updated=".$time." where id=".$data['m_id'];
					}
				}
			}

			if(!$this->exec($sql))
			{
				$this->back();
				$dds =  [
					$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
				];
				return $dds;
			}

			$userMo = new UserModel();

			// 如果該廣告信息是出售廣告，且還是已經停止出售的，鎖定餘額，要返回給用戶
			if($marketData['status']==3&&$marketData['flag']=='sale')
			{
				$userInfo = $userMo->lock()->field("uid,".$coinLock)->where(['uid'=>$data['from_uid']])->fRow();
				if($data['number']<=$userInfo['btc_lock'])
				{
					$sql = "update user set ".$coinOver."=".$coinOver."+".$data['number'].",".$coinLock."=".$coinLock."-".$data['number'].",updated =".$time .",updateip='".Tool_Fnc::realip()."' where uid=".$data['from_uid'];
					if(!$this->exec($sql))
					{
						$this->back();
						$dds =  [
							$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
						];
						return $dds;
					}
				}
				else
				{
					$this->back();
					$dds =  [
						$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
					];
					return $dds;
				}

			}
			//  求购广告（发布广告的用户） // 返还卖家的币剩余数量
			elseif($marketData['flag']=='buy')
			{
				$userinfo = $userMo->lock()->field('uid,'.$coinOver.','.$coinLock)->where(['uid'=>$data['uid']])->fRow();
				if($data['number']<=$userinfo['btc_lock'])
				{
					$sql = "update user set ".$coinOver."=".$coinOver."+".$data['number'].",".$coinLock."=".$coinLock."-".$data['number'].",updated=".$time.",updateip='".Tool_Fnc::realip()."' where uid=".$data['uid'];
					if(!$this->exec($sql))
					{
						$this->back();
						$dds =  [
							$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
						];
						return $dds;
					}
				}
				else
				{
					$this->back();
					$dds =  [
						$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
					];
					return $dds;
				}
			}

			if(!$this->commit())
			{
//				if($marketData['flag']=='buy')
//				{
					$mkMo->saveMarkeBuyAction($marketData['coin']);
//				}
//				elseif($marketData['flag']=='sale')
//				{
					$mkMo->saveMarkeSaleAction($marketData['coin']);
//				}

				$dds =  [
					$GLOBALS['MSG']['USER_OPERATION_FAIL'],0,['data'=>[],'reUrl'=>'']
				];
				return $dds;
			}
			else
			{
				Tool_Session::mark($arr['uid']);
//				$dds =  [
//					$GLOBALS['MSG']['USER_OPERATION_SUCCESS'],1,['data'=>[],'reUrl'=>'']
//				];
				return 1;
			}

		}
		else
		{
			$dds =  [
				$GLOBALS['MSG']['ORDER_NO_EXIST'],0,['data'=>[],'reUrl'=>'']
			];
			return $dds;
		}
	}
}
