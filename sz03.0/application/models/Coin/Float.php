<?php
class Coin_FloatModel extends Orm_Base{
	public $table = 'coin_float';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'coin_from' => array('type' => "char", 'comment' => ''),
		'coin_to' => array('type' => "char", 'comment' => ''),
		'price_float' => array('type' => "decimal(20,8)", 'comment' => '价格浮动'),
		'price_close' => array('type' => "decimal(20,8)", 'comment' => '收盘价'),
		'percent' => array('type' => "decimal(20,8)", 'comment' => '涨跌幅'),
        'price_up' => array('type'=>"decimal(20,8)", 'comment'=>'涨停价'),
        'price_down' => array('type'=>"decimal(20,8)", 'comment'=>'跌停价'),
        'day' => array('type'=>"int", 'comment'=>'日期'),
        'updated' => array('type'=>"int", 'comment'=>'最后更新时间'),
        'perinterest' => array('type'=>"decimal(20,8)", 'comment'=>'每个trmb分得的份额')
	);

	public $pk = 'id';// 主键

	/**
	 * 当前时间，前一天的float内容
	 */
	public function getFloat($day, $coin_from, $coin_to){
        return $this->where("coin_from='".$coin_from."' and coin_to='".$coin_to."' and day=".$day)->fRow();
	}

	public function ajaxcoinOrder($name){
		$priceArr = $this->field('price_up, price_down')->where("day=".date('Ymd'))->fRow();
		Cache_Memcache::set($name.'_dayprice', json_encode($priceArr));
	}
}
