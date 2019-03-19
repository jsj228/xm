<?php
class UserCommentModel extends Orm_Base
{

	public $table = 'user_comment';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'content' => array('type' => "varchar(255) unsigned", 'comment' => '用户内容'),
		'status' => array('type' => "tinyint(1) unsigned", 'comment' => '状态,0：未标记，1：有用反馈, 2：无用反馈, 3: 待分析反馈'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
		'admin' => array('type' => "int", 'comment' => '管理员id'),

	);
	public $pk = 'id';

}
