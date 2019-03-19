<?php

class AssetLogModel extends Orm_Base
{
	public $table = 'asset_log';
	public $field = array(
		'id' => array('type' => "int", 'comment' => 'id'),
		'admin' => array('type' => "int", 'comment' => '操作管理员'),
		'uid' => array('type' => "int", 'comment' => '变更资产用户'),
		'coin' => array('type' => "char", 'comment' => '资产类别'),
		'num' => array('type' => "char", 'comment' => '变更资产数量'),
		'bak' => array('type' => "int", 'comment' => '变更说明'),
		'official' => array('type' => "int", 'comment' => '回收到官方账户'),
		'created' => array('type' => "int", 'comment' => '创建时间'),
		'createip'=>array('type'=>"int",'comment'=>'创建ip')
	);
	public $pk = 'id';
}
