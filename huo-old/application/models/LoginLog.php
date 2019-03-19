<?php
class LoginLogModel extends Orm_Base{
	public $table = 'login_log';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'uid' => array('type' => "int(11) unsigned", 'comment' => 'UID'),
		'sid' => array('type' => "char(32) unsigned", 'comment' => 'session_id'),
		'login_time' => array('type' => "int(11) unsigned", 'comment' => ''),
		'update_time' => array('type' => "int(11) unsigned", 'comment' => ''),
		'logout_time' => array('type' => "int(11) unsigned", 'comment' => ''),
		'time' => array('type' => "int(11) unsigned", 'comment' => ''),
		'ip' => array('type' => "char(15) unsigned", 'comment' => ''),
		'uuid' => array('type' => "char(36) unsigned", 'comment' => '')
	);
	public $pk = 'id';
	
	public static function addloginlog($uid, $sid, $uuid){
		$time = time();
		if(!UuidModel::getUuid($uuid))
			UuidModel::addUuid(array('uid'=>$uid, 'uuid'=>$uuid));
		$data1 = array('uid'=>$uid, 'sid'=>$sid, 'login_time'=>$time,'update_time'=>$time, 'ip'=>Tool_Fnc::realip(),'uuid'=>$uuid);
        /*
		if(IpDataModel::getIp(Tool_Fnc::realip(),0)){
            //用户异常ip登录
			//Tool_Fnc::mailto('.com', "异常IP登录", "用户uid:{$uid}, 在ip：".Tool_Fnc::realip()."({$data['data']['country']})登陆，此ip在黑名单中，时间为:".date('Y-m-d H:i:s',$time));
        }*/
		$llm = new LoginLogModel();
		$id = $llm->insert($data1);
		#Cache_Redis::instance()->hSet('userlogin', $sid, $time);
	}
	public static function updateloginlog($uid){
		$time = time();
		$llm = new LoginLogModel();
		$da = $llm->fRow("select sid, id from {$llm->table} where uid={$uid} order by id desc");
		#Cache_Redis::instance()->hSet('userlogin', $da['sid'], $time);
	}
	public static function updatelogoutlog($uid){
		$time = time();
		$llm = new LoginLogModel();
		$da = $llm->fRow("select id,sid from {$llm->table} where uid={$uid} order by id desc");
		$llm->exec("update {$llm->table} set logout_time={$time} where sid='{$da['sid']}' and logout_time=0");
		#Cache_Redis::instance()->hDel('userlogin', $da['sid']);
	}
}
