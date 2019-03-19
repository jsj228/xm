<?php
class SessionidModel extends Orm_Base{
	public $table = 'session_id';
	public $field = array(
		'id' => array('type' => "int(11) unsigned", 'comment' => 'id'),
		'eid' => array('type' => "int", 'comment' => 'id'),
		'uid' => array('type' => "int", 'comment' => 'uid'),
		'ekey' => array('type' => "char", 'comment' => ''),
		'created' => array('type' => "int", 'comment' => ''),
        'updated' => array('type' => "int", 'comment' => ''),
		'type' => array('type' => "tinyint", 'comment' => '类型'),
		'status' => array('type' => "tinyint", 'comment' => ''),
	);
	public $pk = 'id';

	public function getInfoByKey($key){
        $tStr  = "`ekey`='{$key}' and status=0";
		if(!$tResult = $this->where($tStr)->fRow()){
			return array();
		}
		return $tResult;
	}

    public function getList($where = array()){
        return $this->fList($where);
    }

    /**
     *
     */
    public function createInfo($data){
		$this->begin();
        $result = $this->insert($data);
        if($result){
            $this->commit();
        } else {
            $this->back();
        }
        return $result;
    }


    /**
     * 
     */
    public function commonUpdate($data){
        $result = $this->update($data);
		if(!$result){
			return $this->setError('系统错误，请通知管理员 [错误编号:S_U_003]');
		}
        return $result;
    }

}
