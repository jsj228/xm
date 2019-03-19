<?php
class Otcorder_UserModel extends Orm_Base{
    protected $_config='otc';
	public $table = 'user';
	public $field = array(
		'uid' => array('type' => "int(11) unsigned", 'comment' => '用户id'),
		'email' => array('type' => "char(60)", 'comment' => '邮箱'),
		'mo' => array('type' => "char(11)", 'comment' => '手机'),
		'nickname' => array('type' => "char(32)", 'comment' => '名字'),
		'sex' => array('type' => "tinyint(1)", 'comment' => '性别：1：男，2女'),
		'birthday' => array('type' => "varchar(30)", 'comment' => '生日'),
		'logo' => array('type' => "varchar(80)", 'comment' => '头像'),
		'pwd' => array('type' => "char(32)", 'comment' => '密码'),
		'pwdtrade' => array('type' => "char(32)", 'comment' => '交易密码'),
		'prand' => array('typepe' => "varchar", 'comment' => '随机加密串'),
		'btc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => '剩余比特币'),
		'btc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => '冻结比特币'),
		'ext_over' => array('type' => "decimal(20,8) unsigned", 'comment' => '剩余比特币'),
		'ext_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => '冻结比特币'),
		'mcc_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'mcc_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'eth_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'eth_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dcon_over' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'dcon_lock' => array('type' => "decimal(20,8) unsigned", 'comment' => ''),
		'credit' => array('type' => "int(11)", 'comment' => '积分'),
		'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
		'createip' => array('type' => "char(15)", 'comment' => '创建ip'),
		'updated' => array('type' => "int(11) unsigned", 'comment' => '更新时间'),
		'updateip' => array('type' => "char(15)", 'comment' => '修改ip'),
		'source'    => array('type'=>'int','comment'=>'来源'),
		'registertype' => array('type' => "tinyint(1)", 'comment' => '1.邮箱 2.手机注册'),
		'reward_logo' => array('type' => "varchar(80)", 'comment' => ''),
		'reward_url' => array('type' => "varchar(200)", 'comment' => '邀请url'),
		'order_rate' => array('type' => "decimal(10,3) unsigned", 'comment' => '成功率'),
		'order_history' => array('type' => "decimal(20,8) unsigned", 'comment' => '历史交易次数'),
		'order_total' => array('type' => "int(11)", 'comment' => '总的交易次数'),
		'area' => array('type' => "char(11)", 'comment' => '区号'),

	);
	public $pk = 'uid';


}
