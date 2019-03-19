<?php
class UserbankModel extends Orm_Base{
	public $table = 'user_bank';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户ID'),
		'username' => array('type' => "varchar(30)", 'comment' =>'用户名' ),
		'type' => array('type' => "tinyint(2)", 'comment' => '商家支付类型，1网银，2支付宝 3微信支付'),
		'bank' => array('type' => "int(11)", 'comment' => '名称'),
		'bankcard' => array('type' => "varchar(30)", 'comment' => '账号'),
		'addtime' => array('type' => "int(11)", 'comment' => '时间'),
		'img'=>array('typepe' => "tinyint", 'comment' => '支付二维码'),
		'status'=>array('typepe' => "tinyint", 'comment' => '0，关闭 1，开启'),
	);
	public $pk = 'uid';
}
