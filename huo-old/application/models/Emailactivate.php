<?php
class EmailactivateModel extends Orm_Base{
   public $table = 'email_activate';
   public $field = array(
    'id' => array('type' => 'int(11) unsigned' , 'comment' => 'id'),
    'uid' => array('type' => 'int(11) unsigned' , 'comment' => 'id'),
    'email' => array('type' => 'char(50)' , 'comment' => '邮箱'),
    'reg_time' => array('type' => 'int(11) unsigned' , 'comment' => '注册时间'),
    'senttime' => array('type' => 'int(11) unsigned' , 'comment' => '发送时间'),
    'activate_time' => array('type' => 'int(11) unsigned' , 'comment' => '激活时间'),
    'key' => array('type' => 'char(40)' , 'comment' => '激活密钥'),
   );


   private function sentemail($pData){
		$pUrlparam = array(
            'uid' => $pData['uid'],
            'email' => $pData['email'],
            'key' => $pData['key'],
        );
        $pActivateurl = 'http://' . $_SERVER['HTTP_HOST']
                        . '/user/emailactivate?id=' .base64_encode(serialize($pUrlparam));

        if($pHtml = Tool_Fnc::emailTemplate(array('name' => empty($pData['name'])?'尊敬的会员':$pData['name'], 'activate_url' => $pActivateurl) , 'activate')){

            $pData = array(
                'title' => '欢迎注册币交所',
                'body' => $pHtml,
                'email' => $pData['email'],
            );
			if(!Tool_Fnc::mailto($pData['email'] , $pData['title'] , $pData['body'])){
				//email_log($pData->email . ' sent failed' , 'error');
			}
            //$eRedis = Cache_Redis::instance('default');
            ///$eRedis->lpush('sentemaillist' , json_encode($eData));
        }
	}
}
