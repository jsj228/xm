<?php
class CoinModel extends Orm_Base
{
	public $table = 'coin';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'name' => array('type' => "char(50) unsigned", 'comment' => '币名称'),
		'author' => array('type' => "char(50) unsigned", 'comment' => '作者'),
		'describe' => array('type' => "char(50) unsigned", 'comment' => '描述'),
		'display' => array('type' => "char(50) unsigned", 'comment' => '中文名称'),
		'status' => array('type' => "char(50) unsigned", 'comment' => '状态'),
		'url' => array('type' => "char(50) unsigned", 'comment' => '币详情地址'),
		'block_url' => array('type' => "char(50) unsigned", 'comment' => '区块地址'),
		'minout' => array('type' => "char(50) unsigned", 'comment' => '最小转出数'),
		'maxout' => array('type' => "char(50) unsigned", 'comment' => '最大转出数'),
		'rate_out' => array('type' => "char(50) unsigned", 'comment' => '转出手续费'),
		'order_by' => array('type' => "char(50) unsigned", 'comment' => '显示顺序'),
		'in_status' => array('type' => "char(50) unsigned", 'comment' => '转入状态'),
		'out_status' => array('type' => "char(50) unsigned", 'comment' => '转出状态'),
		'created' => array('type' => "char(50) unsigned", 'comment' => '上币时间')
	);
	public $pk = 'id';

}

