<?php
/**
 * Created by PhpStorm.
 * User: 95776
 * Date: 1/26/2018
 * Time: 10:56 AM
 */

namespace Home\Controller;

use Think\Exception;

class ActController extends HomeController
{
    //活动
    public function index()
    {
        $uid = userid();//?userid():32424;
        $xm_arr = M('act_xm')->where(['uid'=>$uid])->find();

        $prize_list = M('act_xm_prize')->where(['uid'=>$uid])->order('addtime desc,id desc')->select();

        $this->assign('is_login',$uid?1:0);
        $this->assign('prize_list',$prize_list);
        $this->assign('xm_arr',$xm_arr);
        $this->display();
    }
    //领取
    public function receive(){
        $id = I('id');
        $uid = userid();
        if(!$id) $this->ajaxReturn(['code'=>0,'msg'=>'领取失败']);
        if(!$uid) $this->ajaxReturn(['code'=>0,'msg'=>'请先登录']);

        $prize = [
            5=>['num'=>'15','mak'=>'wcg'],
            6=>['num'=>'8','mak'=>'eos'],
            7=>['num'=>'100','mak'=>'xrp'],
            8=>['num'=>'5','mak'=>'wcg'],
            9=>['num'=>'2','mak'=>'wcg'],
            11=>['num'=>'10','mak'=>'xrp'],
            12=>['num'=>'20','mak'=>'etc'],
        ];

        $xm_prize = M('act_xm_prize')->where(['id'=>$id,'uid'=>$uid,'is_take'=>0])->find();
        if(!$xm_prize) $this->ajaxReturn(['code'=>0,'msg'=>'领取失败']);
        $mak = $prize[$xm_prize['prize_id']]['mak'];
        if(!isset($mak))$this->ajaxReturn(['code'=>0,'msg'=>'领取失败']);
        $num = $prize[$xm_prize['prize_id']]['num'];

        M()->startTrans();
        try{
            $save = M('user_coin')->where(['userid'=>$uid])->setInc($mak,$num);
            $xm_save = M('act_xm_prize')->where(['id'=>$id,'uid'=>$uid,'is_take'=>0])->save(['is_take'=>1]);
            if($save && $xm_save){
                M()->commit();
                $this->ajaxReturn(['code'=>1,'msg'=>'领取成功']);
            }else{
                M()->rollback();
                $this->ajaxReturn(['code'=>0,'msg'=>'领取失败']);
            }
        }catch (Exception $e){
            M()->rollback();
            $this->ajaxReturn(['code'=>0,'msg'=>'领取失败']);
        }
    }

    //点击抽奖
    public function prize(){
        $uid  = userid();
        if(!$uid) return $this->ajaxReturn(['status'=>0,'msg'=>'请先登录']);
        $xm_arr = M('act_xm')->where(['uid'=>$uid])->find();
        if($xm_arr['xm_number']<=0)  return $this->ajaxReturn(['status'=>0,'msg'=>'您的圣诞果已用完,请先充值至少100克华克金，再来抽奖！']);
        //prize表示奖项内容，v表示中奖几率(若数组中七个奖项的v的总和为100，如果v的值为1，则代表中奖几率为1%，依此类推)
        $prize_arr = array(
            '0' => array('id' => 1, 'prize' => '1个比特币', 'v' => 0),
            '1' => array('id' => 2, 'prize' => '5个比太币', 'v' => 0),
            '2' => array('id' => 3, 'prize' => '10个优里米', 'v' => 0),
            '3' => array('id' => 4, 'prize' => '谢谢参与', 'v' => 200),
            '4' => array('id' => 5, 'prize' => '15个华克金', 'v' => 5),
            '5' => array('id' => 6, 'prize' => '8个柚子币', 'v' => 10),
            '6' => array('id' => 7, 'prize' => '100个瑞波', 'v' => 10),
            '7' => array('id' => 8, 'prize' => '5个华克金', 'v' => 10),
            '8' => array('id' => 9, 'prize' => '2个华克金', 'v' => 10),
            '9' => array('id' => 10, 'prize' => '谢谢参与', 'v' => 264),
            '10' => array('id' => 11, 'prize' => '10个瑞波币', 'v' => 400),
            '11' => array('id' => 12, 'prize' => '20个以太经典', 'v' => 1),
        );
        foreach ($prize_arr as $k=>$v) {
            $arr[$v['id']] = $v['v'];

        }

        $prize_id = $this->getRand($arr); //根据概率获取奖项id
        foreach($prize_arr as $k=>$v){ //获取前端奖项位置
            if($v['id'] == $prize_id){
                $prize_site = $k;
                break;
            }
        }
        $res = $prize_arr[$prize_id - 1]; //中奖项

        $data['prize_name'] = $res['prize'];
        $data['prize_site'] = $prize_site;//前端奖项从-1开始
        $data['prize_id'] = $prize_id;

        if($data['prize_id']==1 || $data['prize_id']==2 || $data['prize_id']==3){
            $data['prize_name'] = "谢谢参与";
            $data['prize_id'] = 12;
            $data['xm_status'] = 0;
        }

        if($data['prize_name']=="谢谢参与"){
            $data['xm_status'] = 0;
        }else{
            $data['xm_status'] = 1;
        }

            $in_data = [
            'uid'=>$uid,
            'prize_id'=>$data['prize_id'],
            'prize'=>$data['prize_name'],
            'xm_status'=>$data['xm_status'],
            'addtime'=>time(),
        ];

        M()->startTrans();
        try{
            $id = M('act_xm_prize')->add($in_data);
            $dele_id = M('act_xm')->where(['uid'=>$uid])->setDec('xm_number',1);
        }catch (\Exception $e){
            M()->rollback();
        }

        if($id && $dele_id){
            M()->commit();
        }else{
            M()->rollback();
        }

        echo json_encode($data);

    }

    function getRand($proArr) {

        $data = '';
        $proSum = array_sum($proArr); //概率数组的总概率精度

        foreach ($proArr as $k => $v) { //概率数组循环
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $data = $k;
                break;
            } else {
                $proSum -= $v;
            }
        }
        unset($proArr);

        return $data;
    }
}


