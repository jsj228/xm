<?php

class Otcorder_UserpayinfoModel extends Orm_Base
{
	protected $_config = 'otc';
    public $table = 'user_payinfo';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
        'label' => array('type' => "varchar(30)", 'comment' => '标签'),
        'account' => array('type' => "varchar(30)", 'comment' => '汇款账号'),
        'pay_type' => array('type' => "tinyint(2)", 'comment' => '1:微信，2:支付宝，3:银行卡'),
        'issuerId' => array('type' => "varchar(20)", 'comment' => '银行代码'),
        'name' => array('type' => "varchar(12)", 'comment' => '姓名'),
        'email' => array('type' => "varchar(60)", 'comment' => '用户名'),
        'province' => array('type' => "varchar(50)", 'comment' => '省市'),
        'city' => array('type' => "varchar(50)", 'comment' => '城市'),
        'district' => array('type' => "varchar(50)", 'comment' => '地区'),
        'bank' => array('type' => "varchar(200)", 'comment' => '银行'),
        'subbranch' => array('type' => "varchar(200)", 'comment' => '支行'),
        'created' => array('type' => "int(11)", 'comment' => '创建时间'),
        'updated' => array('type' => "int(11)", 'comment' => '更新时间'),
        'status' => array('type' => "tinyint(1)", 'comment' => '0正常 1当前使用  2已删除'),
        'is_default' => array('type' => "tinyint(1)", 'comment' => '是否是默认方式，1是')
    );
    public $pk = 'id';
}