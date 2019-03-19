<?php
/**
 * Created by PhpStorm.
 * User: 95776
 * Date: 1/26/2018
 * Time: 10:56 AM
 */

namespace Home\Controller;

class ActController extends HomeController
{


    //活动
    public function index(){


        $act = [
            strtotime('2018-12-19 12:00:00'),
            strtotime('2018-12-19 18:00:00'),
            strtotime('2018-12-20 12:00:00'),
            strtotime('2018-12-20 18:00:00'),
            strtotime('2018-12-23 12:00:00'),
            strtotime('2018-12-23 18:00:00'),
            strtotime('2018-12-24 12:00:00'),
            strtotime('2018-12-24 18:00:00'),
            strtotime('2018-12-25 12:00:00'),
            strtotime('2018-12-25 18:00:00')
            ];
        $act_xm_prize = M('act_xm_prize')->order('add_time desc,id desc')->find();

        if(I('level')>$act_xm_prize['level']) $this->error("请等待,该期活动还未开始");

        if(time()>$act[0]){
            if($act_xm_prize){
                if(time()>$act[$act_xm_prize['level']] && $act_xm_prize['level']<count($act)){
                    $level = $act_xm_prize['level']+1;
                    $prize_uids = $this->prize($level,count($act));
                    $save = M('act_xm_prize')->add(['level'=>$level,'uids'=>json_encode($prize_uids),'add_time'=>time()]);

                }else{
                    $level = $act_xm_prize['level'];
                }
            }else{
                $level = 1;
                $prize_uids = $this->prize($level,count($act));
                $save = M('act_xm_prize')->add(['level'=>$level,'uids'=>json_encode($prize_uids),'add_time'=>time()]);
            }
        }

        $level = I('level')?I('level'):$level;


        $xm_arr = M()->query('select uid,xm_number-in_xm_number number,in_xm_number,level_prize from weike_act_xm where xm_number-in_xm_number!=0');
        $prizes = [];
        foreach ($xm_arr as $v){
            if($v['level_prize']){
                $level_prize = json_decode($v['level_prize'],true);
                if(isset($level_prize[$level])){
                    $prize_uids[] = $v['uid'];
                    $prizes[] = ['uid'=>$v['uid'],'number'=>$level_prize[$level]];
                }
            }
        }

        if($prize_uids){
            $users = M('user')->where(['id'=>['in',$prize_uids]])->select();
            $usernames = array_column($users,'username','id');
            $truename = array_column($users,'truename','id');
            foreach ($prizes as $k=>$v){
                $prizes[$k]['username'] = '****'.substr($usernames[$v['uid']], -3);;
                $prizes[$k]['truename'] = mb_substr($truename[$v['uid']],0,1).'***';
            }
        }

        $uid = userid();

        if($uid){
            $xm_arr = M()->query("select uid,xm_number-in_xm_number number,in_xm_number from weike_act_xm where uid=$uid");
            $user_prize = $xm_arr[0];
        }
        if(!isset($user_prize)) $user_prize = ['number'=>0,'in_xm_number'=>0];

        $levels = [1=>'一',2=>'二',3=>'三','4'=>'四',5=>'五','6'=>'六',7=>'七',8=>'八',9=>'九',10=>'十'];

        $this->assign('act',$act);
        $this->assign('user_prize',$user_prize);
        $this->assign('prizes',$prizes);
        $this->assign('level',$level);
        $this->assign('level_cn',isset($levels[$level])?$levels[$level]:0);
        $this->display();
    }

    //活动 开始抽奖
    public function prize($level,$count)
    {
        $act_xm_prize = M('act_xm_prize')->order('add_time desc,id desc')->find();

        if($level<=$act_xm_prize['level'] || $level>$count) return false;

        $xm_arr = M()->query('select uid,xm_number-in_xm_number number,in_xm_number,level_prize from weike_act_xm where xm_number-in_xm_number!=0');

        $prize_uids = [];
        foreach ($xm_arr as $k=>$v){

            $level_prize_num = 0;//记录单期中奖次数
            for($i=0;$i<$v['number'];$i++){
                $arr = array('中奖'=>1/100,'未中奖'=>99/100);
                $ps = $this->random($arr);

                if($ps=='中奖'){
                    $level_prize_num+=1;
                    //记录中奖期数
                    if($v['level_prize']){
                        $level_jsde = json_decode($v['level_prize'],true);
                        $level_jsde[$level]+=$level_prize_num;
                        $level_prize = json_encode($level_jsde);
                    }else{
                        $level_prize = json_encode([$level=>$level_prize_num]);
                    }

                    $prize_uid = $v['uid'];
                    $save = M()->execute("update weike_act_xm SET in_xm_number=in_xm_number+1,level_prize='$level_prize'  WHERE uid = '$prize_uid'");
                    $prize_uids[] = $v['uid'];
                }
            }
        }
        return $prize_uids;
    }

    //中奖代码
    function random($ps)
    {
        static $arr = array();
        $key = md5(serialize($ps));

        if (!isset($arr[$key])) {
            $max = array_sum($ps);
            foreach ($ps as $k => $v) {
                $v = $v / $max * 10000;
                for ($i = 0; $i < $v; $i++) $arr[$key][] = $k;
            }
        }
        return $arr[$key][mt_rand(0, count($arr[$key]) - 1)];
    }
}