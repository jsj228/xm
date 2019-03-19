<?php
class TicketlogModel extends Orm_Base{
    public $table = 'ticket_log';
    public $field = array(
        'id' => array('type'  => "int(11) unsigned", 'comment' => 'id'),
        'uid' => array('type' => "int(11) unsigned", 'comment' => 'uid'),
        'expire' => array('type' => "tinyint(3)", 'comment' => '有限期(天)'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '创建时间'),
        'type' => array('type' => "tinyint(3) unsigned", 'comment' => '类型'),
        'gift' => array('type' => "varchar", 'comment' => '礼品名'),
    );
    public $pk = 'id';
}
