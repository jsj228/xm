<?php
class ActivitySignModel extends Orm_Base{
    public $table = 'activity_sign';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'uid' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'coin' => array('type' => "char(25)", 'comment' => '币种'),
        'sign' => array('type' => "int(11) unsigned", 'comment' => '签到次数'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'updated' => array('type' => "int(11) unsigned", 'comment' => '修改时间'),

    );
    public $pk = 'id';
}
