<?php
class AppversionModel extends Orm_Base{
    public $table = 'app_version';
    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'title' => array('type' => "varchar(120) unsigned", 'comment' => '标题'),
        'content' => array('type' => "varchar(255) unsigned", 'comment' => '升级说明'),
        'system' => array('type' => "varchar(30) unsigned", 'comment' => '1是Android、ios 2是Android 3是ios'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '生效时间'),
        'status' => array('type' => "tinyint(1) unsigned", 'comment' => '状态0是待开启，1是已开启'),
        'admin' => array('type' => "tinyint(1) unsigned", 'comment' => '管理员'),
        'mandatory' => array('type' => "tinyint(1) unsigned", 'comment' => '1代表强制升级'),
        'mark' => array('type' => "varchar(15) unsigned", 'comment' => '版本号'),

    );
    public $pk = 'id';

}
