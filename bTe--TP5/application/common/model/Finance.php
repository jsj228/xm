<?php
namespace app\common\model;

use think\Model;
class Finance extends Model
{
	protected $key = 'home_finance';

    //获取用户剩余总计金额
    public function getMum($userId, $coinname = 'cny'){
        $mum = Db::name('Finance')->where(array('userid' => $userId, 'coinname' => $coinname))->order('id desc')->value('mum');
        return $mum;
    }

    public function getInfoByField($field,$value) {
        $result = Db::name('Finance')->where([$field=>$value])->find();
        return $result;
    }

    //获取提现记录分页列表
    public function getPage($where = array(), $order = '', $page = 10){
        $oList = Db::name('Finance')->where($where)->order($order)->paginate($page);
        $page = $oList->render();
        $list = $oList->all();
        return array(0 => $list, 1 => $page);
    }

    //添加充值记录
    public function add($fields, $getLastInsID = false){
        return Db::name('Finance')->insert($fields, false, $getLastInsID);
    }
}

?>