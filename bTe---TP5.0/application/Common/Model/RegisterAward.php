<?php
namespace app\common\model;

use think\Request;
use think\Db;
use think\Cache;
use think\session;
use think\Model;
class RegisterAward extends Model {

    public function search($param){
        $where = '';
        if(intval($param['coin']) > 0){
            $where .= 'r.coin = '.$param['coin']." ";
        }
        if(intval($param['type']) > 1){
            $where .= " and r.type = ".strval($param['type']-1)." ";
        }
        if(intval($param['status']) >= 2){
            $where .= " and r.status = ".strval($param['status']-2)." ";
        }
        if($param['name']){
            $where .= " and r.one = ".trim($param['name'])." or r.two = ".trim($param['name']);
        }
        $return['count'] = $this->alias('r')->where($where)->count();
        if(trim($where)){
            $where_num .= $where.' and status = 1 ';
        }else{
            $where_num .= 'status = 1 ';
        }
        $return['nums'] = $this->alias('r')->where($where_num)->sum('nums');
        // $Page = new \Think\Page($return['count'], 15);
        // $return['page'] = $Page->show();

        $return['data'] = $this
                ->alias('r')
                ->join('weike_coin c','c.id = r.coin')
                ->join('weike_admin a','r.admin_id = a.id')
                ->field("r.id,r.users,r.one,r.two,r.nums,c.title,FROM_UNIXTIME(r.active_time) as active_time,a.username,r.times,FROM_UNIXTIME(r.add_time) as add_time,r.status,(case when r.status = 1  then '已奖励' else '未奖励' end) as award_status,r.type,(case when r.type =1 then '认证奖励' when r.type = 2 then '邀请充值奖励' when r.type = 3 then '分享奖励' else '其它' end) as award_type,r.status,r.type")
                ->order('r.times desc,r.add_time desc')
                ->where($where)
                ->paginate(15);
        return $return;
    }

    public function get_coins(){
        return $this->alias('r')->join('weike_coin c','r.coin = c.id')->field('c.id,c.title')->group('r.coin')->select();

    }

    public function test(){
        return 'hell';
    }
}

?>