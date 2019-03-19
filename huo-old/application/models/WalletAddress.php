<?php
class WalletAddressModel extends Orm_Base
{
	public $table = 'wallet_address';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'email' => array('type' => "char(60)", 'comment' => '邮箱'),
		'name' => array('type' => "char(60)", 'comment' => '用户名'),
		'wallet' => array('type' => "char(120)", 'comment' => '钱包地址'),
		'coin' => array('type' => "char(20)", 'comment' => '币种'),
		'status' => array('type' => "tinyint(1) ", 'comment' => '0 显示 , 1 删除'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
        'bak' => array('type' => "char(255)", 'comment' => '备注'),
		'checking'=>array('type'=>"tinyint(1)",'comment'=>'1已审核2未审核')
	);
	public $pk = 'id';

    const STATUS_NORMAL = 0;
    const STATUS_DEL = 1;
    const STATUS_AUDIT = 2;
}


