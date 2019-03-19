<?php
# 短信到账表
class SmsPayModel extends Orm_Base
{
    public $table = 'sms_pay';
    public $field = array(
        'id'				=> array('type' => "int", 'comment' => 'id'),
        'from'      		=> array('type' => "varchar", 'comment' => '来自手机'),
        'message'   		=> array('type' => "text", 'comment' => '信息内容'),
        'sent_timestamp'    => array('type' => "int", 'comment' => '发送时间'),
        'sent_to'           => array('type' => "int", 'comment' => '接收手机'),
        'message_id' 		=> array('type' => "int", 'comment' => '信息ID'),
        'status'			=> array('type' => "tinyint", 'comment' => '充值状态 1. 等待 2.成功'),
        'created'			=> array('type' => "int", 'comment' => '创建时间'),
        'updated'			=> array('type' => "int", 'comment' =>'更新时间'),
        'bak'    			=> array('type' => "text", 'comment' => '备注')
    );

    public $pk = 'id';
}

