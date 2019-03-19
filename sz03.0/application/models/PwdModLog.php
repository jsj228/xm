<?php
class PwdModLogModel extends Orm_Base{
    public $table = 'pwd_mod_log';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'type' => array('type' => "tinyint(1)", 'comment' => '1 登录密码，2 交易密码'),
        'uid' => array('type' => "int(11) unsigned", 'comment' => 'uid'),
        'createdip' => array('type' => "varchar", 'comment' => 'ip'),
        'created' => array('type' => "timestamp", 'comment' => '创建时间'),
    );
    public $pk = 'id';
}
