<?php
class UrgentModel extends Orm_Base{
	public $table = 'urgent';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'adminid' => array('type' => "int(11) unsigned", 'comment' => '用户ID'),
        'in_lock' => array('type' => "tinyint(2) unsigned", 'comment' => '0 关闭 1开启'),
		'addtime' => array('type' => "int(10) unsigned", 'comment' => '添加时间'),
	);
	public $pk = 'id';
	
}
