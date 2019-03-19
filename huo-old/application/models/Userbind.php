<?php
class UserbindModel extends Orm_Base{
	public $table = 'user_bind';
	public $field = array(
		'id'           => array('type' => "int", 'comment' => 'uid'),
		'uid'           => array('type' => "int", 'comment' => 'uid'),
		'snsid'        => array('type' => "char", 'comment' => '数量'),
		'type'          => array('type' => "int", 'comment' => '1微信'),
		'status'          => array('type' => "int", 'comment' => '0生效,1已解除'),
        'created'   => array('type' => "int", 'comment' => '创建日期'),
        'updated'   => array('type' => "int", 'comment' => '结算日期')
	);
	public $pk = 'id';
}
