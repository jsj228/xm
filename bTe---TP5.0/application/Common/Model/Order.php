<?php
namespace app\common\model;

use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Model;

class Order extends Model
{
	function get_order_info($id){
		$data = $this->where(['id'=>$id])->field("id,uid,emailAdd as email,attrimg,requestSub title,requestType type,requestDescription as description,addtime,huifudesc,wt_type,adminuser,huifutime")->find();

        $data['description'] = explode(',',$data['description']);
        $data['addtime'] = explode(',',$data['addtime']);
        $data['huifudesc'] = explode(',',$data['huifudesc']);
        $data['huifutime'] = $data['huifutime']?explode(',',$data['huifutime']):array();
        $data['adminuser'] = explode(',',$data['adminuser']);
        $m = $ms = array();
        for($i=0;$i<count($data['addtime']);$i++){
            $msg['username'] = $data['email'];
            $msg['addtime'] = $data['addtime'][$i];
            $msg['description'] = $data['description'][$i];
            $msg['type'] = 0;
            $m[] = $msg;
        }
        if($data['huifutime'])
        for($j=0;$j<count($data['huifutime']);$j++){
            $msgs['username'] = $data['adminuser'][$j]?$data['adminuser'][$j]:$data['adminuser'][count($data['adminuser'])-1];
            $msgs['addtime'] = $data['huifutime'][$j];
            $msgs['description'] = $data['huifudesc'][$j];
            $msgs['type'] = 1;
            $ms[] = $msgs;
        }
        $data['msg'] = array_merge($m,$ms);
        foreach ($data['msg'] as $k => $v) {
            $time[] = intval($v['addtime']); 
        }
        array_multisort($time, SORT_ASC, $data['msg']);
        return $data;
	}
}