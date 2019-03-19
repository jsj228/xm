<?php


class  ChatModel extends Orm_Base
{
    protected $_config = 'chat';
    protected $table = 'chat';

    public $field = array(
        'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
        'mo' => array('type' => "int(11) unsigned", 'comment' => '平台用户'),
        'themessage' => array('type' => "varchar(255) unsigned", 'comment' => '消息内容'),
        'img' => array('type' => "varchar(255) unsigned", 'comment' => '聊天用户图片'),
        'created' => array('type' => "int(11) unsigned", 'comment' => '消息发送时间'),
    );
    public $pk = 'id';

}