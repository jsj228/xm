<?php
class ReqFailedLogModel extends Orm_Base{
    public $_config = 'log';
    public $table = 'req_failed_log';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'uid' => array('type' => "int(11)", 'comment' => 'uid'),
        'req_url' => array('type' => "varchar", 'comment' => '请求url'),
        'param' => array('type' => "varchar", 'comment' => '参数'),
        'response' => array('type' => "varchar", 'comment' => '请求响应结果'),
        'sql' => array('type' => "varchar", 'comment' => ''),
        'session' => array('type' => "varchar", 'comment' => 'PHPsession值'),
        'req_time' => array('type' => "datetime", 'comment' => '请求时间'),
        'req_ip' => array('type' => "varchar(255)", 'comment' => '请求来源ip'),
        'created' => array('type' => "timestamp", 'comment' => '创建时间'),
    );
    public $pk = 'id';
}
