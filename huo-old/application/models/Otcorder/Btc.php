<?php
class Otcorder_BtcModel extends Orm_Base{
    protected $_config='otc';
	public $table = 'order_btc';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'm_id' => array('type' => "int(11) unsigned", 'comment' => '发布交易广告id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'price' => array('type' => "decimal(20,8) unsigned", 'comment' => '交易价格'),
		'order_price' => array('type' => "decimal(20,8) unsigned", 'comment' => '订单金额价格'),
		'number' => array('type' => "decimal(20,8) unsigned", 'comment' => '订单数量'),
		'opt' => array('type' => "int", 'comment' => '0其他，1ybc交易，2ybc提现，3交易费，4融资风险金，5rmb提现'),
		'status' => array('type' => "tinyint", 'comment' => '状态.0:待付款，1:待确认，2:已完成，3:已关闭'),
		'flag' => array('type' => "enum('buy','sale')", 'comment' => '买卖标志'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
		'pay_type' => array('type' => "tinyint(1)", 'comment' => '支付方式：1.微信，2支付宝，3银行卡'),
		'pay_time' => array('type' => "int(11) unsigned", 'comment' => '付款时间'),
		'fee' => array('type' => "decimal(40,20)", 'comment' => '手续费'),
	);
	public $pk = 'id';

	const OPT_TRADE = 1; #交易;
	const OPT_FEE_BUY = 2; #买入手续费;
	const OPT_FEE = 3; #卖出手续费;
/**
	 * 指定表名
	 */
	public function designTable($name)	//chen
	{
		$this->table= 'order_'.$name.'coin';
	}
	/**
	 * 更新委托单
	 */
	public function ajaxcoinTrustList($pair){
		// 交易统计
		$pair_arr = explode('_', $pair);
		$table= 'trust_'.$pair_arr[0].'coin';	//chen
		$tSql = "SELECT price p,sum(numberover) n FROM $table WHERE coin_from='%s' and coin_to='%s' and status < 2 and numberover>0 and flag='%s' AND isnew='N' GROUP BY price ORDER BY price %s LIMIT 30";
		$tJson = array(
			'buy'=>$this->query(sprintf($tSql, $pair_arr[0], $pair_arr[1], 'buy', 'DESC')),
			'sale'=>$this->query(sprintf($tSql, $pair_arr[0], $pair_arr[1], 'sale', 'ASC'))
		);
		Cache_Memcache::set($pair.'_sum', json_encode($tJson));
	}

	// 更新交易
	public function ajaxcoinOrder($pair2){
		if(!$pair = Coin_PairModel::getByName($pair2)){
			return false;
		}
		$pair_arr = explode('_', $pair2);	//chen
		$table    = 'order_' . $pair_arr[0] . 'coin';    //chen  'order_coin'替换为$table
		// 订单记录
		$tData = array('max'=>0, 'min'=>0, 'sum'=>0);
		# 最新50个订单
		if($tOrders = $this->query("SELECT * FROM $table WHERE opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' ORDER BY id DESC LIMIT 50")){
			$tTrades = array();
			foreach($tOrders as $k1 => $v1){
				if($k1 < 30){
					$tData['d'][] = array('t'=>date('H:i:s', $v1['created']), 'p'=>Tool_Str::format($v1['price'], $pair['price_float']), 'n'=>Tool_Str::format($v1['number'], $pair['number_float']), 's'=>$v1['buy_tid']>$v1['sale_tid']?'buy':'sell');
				}
				// 全屏交易取50条数据
				/*if($k1 < 50){
					$tFullData['d'][] = array('t'=>date('H:i:s', $v1['created']), 'p'=>Tool_Str::format($v1['price'], $pair['price_float']), 'n'=>Tool_Str::format($v1['number'], $pair['number_float']), 's'=>$v1['buy_tid']>$v1['sale_tid']?'buy':'sell');
				}*/
				$tTrades[] = array('date'=>$v1['created'], 'price'=>Tool_Str::format($v1['price'], $pair['price_float']), 'amount'=>Tool_Str::format($v1['number'], $pair['number_float']), 'tid'=>$v1['id'], 'type'=>$v1['buy_tid']>$v1['sale_tid']?'buy':'sell');
			}
			# 接口数据
			//file_put_contents('../public/json/ybc_trades.js', json_encode(array_reverse($tTrades)));
		} else {
			$tData['d'] = array(array('t'=>'00:00:00', 'p'=>0, 'n'=>0, 's'=>'sell'));
			$tFullData['d'] = array(array('t'=>'00:00:00', 'p'=>0, 'n'=>0, 's'=>'sell'));
		}
		# 24小时最大、最小
		if($tMPrice = $this->fRow("SELECT max(price) max, min(price) min, sum(number) sum FROM $table WHERE created > ".(time()-86400)." AND opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' ")){
			$tData['max'] = Tool_Str::format($tMPrice['max'], $pair['price_float']);
			$tData['min'] = Tool_Str::format($tMPrice['min'], $pair['price_float']);
			$tData['sum'] = Tool_Str::format($tMPrice['sum'], $pair['number_float']);
		}
		Cache_Memcache::set($pair2.'_order', json_encode($tData));
		// Cache_Memcache::set($pair2.'_full_order', json_encode($tFullData)); // 全屏交易中的订单数据，50条
		Cache_Memcache::set($pair2.'_rmb', $tData['d'][0]['p']);
	}
}
