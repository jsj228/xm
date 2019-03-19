<?php
class ActivityModel extends Orm_Base{
    public $table = 'activity';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'name' => array('type' => "varchar(14)", 'comment' => '活动名称'),
        'type' => array('type' => "varchar(10)", 'comment' => '活動類型'),
        'status' => array('type' => "tinyint(1)", 'comment' => '状态：0：关闭，1：开启'),
        'start_time' => array('type' => "int(11) unsigned", 'comment' => '开始时间'),
        'end_time' => array('type' => "int(11) unsigned", 'comment' => '结束时间'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '修改时间'),
        'bak' => array('type' => "varchar(255)", 'comment' => '备注'),
        'admin' => array('type' => "int(11) unsigned", 'comment' => '操作管理员id'),
        'conf' => array('type' => "varchar", 'comment' => '配置'),
    );
    public $pk = 'id';
}
