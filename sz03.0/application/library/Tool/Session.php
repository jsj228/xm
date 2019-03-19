<?php
class Tool_Session {
    private $redis;
    private $expire_time = 30;

    private $oldData;

    public function __construct(){
        $this->redis = Cache_Redis::instance('session');
		$this->expire_time = Yaf_Registry::get("config")->session['timeout']?:3600;
        $handle = session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        );
        if(!isset($_SESSION))
        {
            session_start();
        }
    }

    public function open($path, $name){
        return true;
    }

    public function close(){
        return true;
    }

    public function read($id){
        $val = $this->redis->get($id);
        
        if($val){ 
            return $this->oldData = json_decode($val);
        } else {
            return '';
        } 
    }

    public function write($id, $data){
        if(empty($data)){
            return false;
        }

        //数据没变动，只更新时间
        if($this->oldData == $data)
        {
            $this->redis->expire($id, $this->expire_time);
            return true;
        }

        $data   = json_encode($data);
        if($this->redis->set($id, $data)){
            $this->redis->expire($id, $this->expire_time);
            return true;
        }
        return false;
    }

    public function destroy($id){
        if($this->redis->delete($id)){
            return true;
        }
        return false;
    }

    public function gc($lifetime){
        return true;
    }

    public function __destruct(){
        //@session_write_close();
    }

     /**
     * user余额，各平台刷新管理,标记需更新session
     *
     */
    public static function mark($uid)
    {
        $redis = Cache_Redis::instance();
        $redis->select(0);
        $client = $redis->hGet('usersession', $uid)?:'[]';
        $client = (array)json_decode($client, true);
        foreach ($client as &$v) 
        {
            is_array($v) and $v['status'] = 1;
        }
        $client = json_encode($client);
        $redis->hSet('usersession', $uid, $client);
        return true;
    }
}
