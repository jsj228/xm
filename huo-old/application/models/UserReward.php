<?php
class UserRewardModel extends Orm_Base{
	public $table = 'user_reward';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'aid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'coin' => array('type' => "varchar(14)", 'comment' => ''),
		'type' => array('type' => "tinyint(1) unsigned", 'comment' => '类型： 0：活动 1:注册，2：实名 3:邀请'),
		'number_reg' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'number_au' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => ''),
		'created' => array('type' => "int(11) unsigned", 'comment' => ''),
		'updated' => array('type'    => "int(11) unsigned", 'comment' => ''),
		'bak' => array('type' => "varchar(255)",'comment' => ''),
		'number' => array('type' => "decimal(20,8) unsigned",'comment' => ''),
		'be_invited' => array('type' => "int(11) unsigned", 'comment' => '被邀请者UId'),

	);
	public $pk = 'id';
}
