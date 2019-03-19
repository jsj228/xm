<?php
class User_AdminModel extends Orm_Base{
	public $table = 'user_admin';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '')
	);
	public $pk = 'id';

	const COIN_FEE = 1;
	const OUT_FEE  = 2;
	const TRADE_1  = 100101;
	const TRADE_2  = 100102;
	const TRADE_3  = 100103;
	const TRADE_4  = 100104;
	const TRADE_5  = 100105;
	const TRADE_103161  = 103161;
	const TRADE_103162  = 103162;
	public static $email = array(
		//self::COIN_FEE 	=> 'trade_fee@qqibtc.com',
		//self::OUT_FEE 	=> 'out_fee@qqibtc.com',
		self::TRADE_1	=> 'trade1@qqibtc.com',
		self::TRADE_2	=> 'trade2@qqibtc.com',
		self::TRADE_3	=> 'trade3@qqibtc.com',
		self::TRADE_4	=> 'trade4@qqibtc.com',
		self::TRADE_5	=> 'trade5@qqibtc.com',
		self::TRADE_103161 => '1448475532@qq.com',
		self::TRADE_103162 => '2131047857@qq.com',
    );	
}
