<?php
/**
 * Created by PhpStorm.
 * User: 95776
 * Date: 1/26/2018
 * Time: 10:56 AM
 */

namespace Home\Controller;

class ActivityController extends HomeController
{
    //WC
    public function mywc()
    {
        if (!userid()) {
            redirect('/#login');
        }

        $type = I('type');
        $where = ['userid' => userid(),'coinname'=> $type];
            $count = M('IssueWc')->where($where)->count();
            $Page = new \Think\Page($count, 10);
            $show = $Page->show();
            $list = M('IssueWc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
            foreach ($list as $k => $v) {
                $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            }
            foreach ($list as $k=>$v){
                      $list[$k]['nun'] =1;
                    if (preg_match('/E/',strtolower($v['interest']))){
                        $a = explode("e",strtolower($v['interest']));
                        $v = bcmul($a[0], bcpow(10, $a[1], 9), 9);

                    }
                    if (strlen($v['interest']) > 10){
                        $list[$k]['interest'] = substr($v['interest'],0,10);

                    }
            }

            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display();

    }

    public function accrual(){
        if (IS_POST) {
            $type = I('type/s','');
            if($type=='wc') $name = '云尊币';
            if($type=='wcg') $name = '华克金';
            if($type=='mtr') $name = '摩态链';
            if($type=='wos') $name = '分享通证';
            if($type=='unih') $name = '尤里米';

            if($type=='wc' || $type=='wcg' || $type=='mtr' || $type=='wos' || $type=='unih'){
                //用户判断
                $IssueWc = M('IssueWc')->where(array('userid' => userid(), 'coinname' => $type, 'status' => 1))->order('addtime desc')->limit(1)->find();
                if ($IssueWc && $IssueWc['userid'] != userid()) $this->error('非法访问');
                //每天一次判断
                if ($IssueWc) {
                    if (86400 > (time() - $IssueWc['addtime'])) $this->error('一天只能获取一次 '.$type.' 利息！');
                }

                //获取可用数量，冻结数量，总数量，利息数量
                $typed = $type.'d';
                $total = M('UserCoin')->field("$type, $typed, $type + $typed as sum")->where(['userid' => userid()])->find();
                if ($total[$type] == 0) $this->error('账户可用为零，不能获取 '.$type.' 利息！');
                $interest = M('Config')->where(array('id' => 1))->getField($type.'_interest');
                $fee = $total[$type] * $interest;
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables weike_user_coin write  , weike_issue_wc write ');
                $rs = array();
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setInc($type, $fee);

                $rs[] = $mo->table('weike_issue_wc')->add([
                    'userid' => userid(),
                    'name' => $name,
                    'coinname' => $type,
                    'num' => $total[$type],
                    'freeze' => $total[$type.'d'],
                    'interest' => $fee,
                    'count' => $IssueWc['count'] == 0 ? 1 : $IssueWc['count'] + 1,
                    'addtime' => time(),
                    'status' => 1
                ]);

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    $this->success('操作成功！');
                } else {
                    $mo->execute('rollback');
                    $this->error('操作失败！');
                }
            }
//            if(I('type') == 'wc'){
//
//
//
//            }
//            if(I('type') == 'wcg'){
//                //周六下午两点
//                if (time() < 1533967200) $this->error('活动 八月十一号十四时之后开始！');
//
//                //用户判断
//                $IssueWcg = M('IssueWc')->where(array('userid' => userid(),'coinname'=> 'wcg', 'status' => 1))->order('addtime desc')->limit(1)->find();
//                if ($IssueWcg && $IssueWcg['userid'] != userid()) {
//                    $this->error('非法访问');
//                }
//                //每天一次判断
//                if ($IssueWcg) {
//                    if (86400 > (time() - $IssueWcg['addtime'])) {
//                        $this->error('一天只能获取一次 WCG 利息！');
//                    }
//                }
//                //获取可用数量，冻结数量，总数量，利息数量
//                $total = M('UserCoin')->field('wcg, wcgd, wcg + wcgd as sum')->where(['userid' => userid()])->find();
//                if ($total['wcg'] == 0) {
//                    $this->error('账户可用为零，不能获取 WCG 利息！');
//                }
//                $fee = $total['wcg'] * C('wcg_interest');
////            $fee = $total['wc'] * '0.0002';
//                $mo = M();
//                $mo->execute('set autocommit=0');
//                $mo->execute('lock tables weike_user_coin write  , weike_issue_wc write ');
//                $rs = array();
//                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setInc('wcg', $fee);
//                $rs[] = $mo->table('weike_issue_wc')->add([
//                    'userid' => userid(),
//                    'name' => '华克金',
//                    'coinname' => 'wcg',
//                    'num' => $total['wcg'],
//                    'freeze' => $total['wcgd'],
//                    'interest' => $fee,
//                    'count' => $IssueWcg['count'] == 0 ? 1 : $IssueWcg['count'] + 1,
//                    'addtime' => time(),
//                    'status' => 1
//                ]);
//
//                if (check_arr($rs)) {
//                    $mo->execute('commit');
//                    $mo->execute('unlock tables');
//                    $this->success('操作成功！');
//                } else {
//                    $mo->execute('rollback');
//                    $this->error('操作失败！');
//                }
//            }
//
//            if(I('type') == 'mtr'){
//                //周六下午两点
////                if (time() < 1533967200) $this->error('活动 八月十一号十四时之后开始！');
//
//                //用户判断
//                $IssueWcg = M('IssueWc')->where(array('userid' => userid(),'coinname'=> 'mtr', 'status' => 1))->order('addtime desc')->limit(1)->find();
//                if ($IssueWcg && $IssueWcg['userid'] != userid()) {
//                    $this->error('非法访问');
//                }
//                //每天一次判断
//                if ($IssueWcg) {
//                    if (86400 > (time() - $IssueWcg['addtime'])) {
//                        $this->error('一天只能获取一次 MTR 利息！');
//                    }
//                }
//                //获取可用数量，冻结数量，总数量，利息数量
//                $total = M('UserCoin')->field('mtr, mtrd, mtr + mtrd as sum')->where(['userid' => userid()])->find();
//                if ($total['mtr'] == 0) {
//                    $this->error('账户可用为零，不能获取 MTR 利息！');
//                }
//                $mtr_interest = M('Config')->where(array('id' => 1))->getField('mtr_interest');
//                $fee = $total['mtr'] * $mtr_interest;
//
////            $fee = $total['wc'] * '0.0002';
//                $mo = M();
//                $mo->execute('set autocommit=0');
//                $mo->execute('lock tables weike_user_coin write  , weike_issue_wc write ');
//                $rs = array();
//                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setInc('mtr', $fee);
//                $rs[] = $mo->table('weike_issue_wc')->add([
//                    'userid' => userid(),
//                    'name' => '摩态链',
//                    'coinname' => 'mtr',
//                    'num' => $total['mtr'],
//                    'freeze' => $total['mtrd'],
//                    'interest' => $fee,
//                    'count' => $IssueWcg['count'] == 0 ? 1 : $IssueWcg['count'] + 1,
//                    'addtime' => time(),
//                    'status' => 1
//                ]);
//
//                if (check_arr($rs)) {
//                    $mo->execute('commit');
//                    $mo->execute('unlock tables');
//                    $this->success('操作成功！');
//                } else {
//                    $mo->execute('rollback');
//                    $this->error('操作失败！');
//                }
//            }
        }
    }

    //华克金转入分流
    public function wcg_zr(){
//        $coin = I('coin/s', NULL);
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户华克金数量
        $user_wcg = M('UserCoin')->where(['userid' => userid()])->getField('wcg');
        //获取华克金配置
        $wcg_info = M('Coin')->where(['name' => 'wcg'])->find();
//        dd($wcg_info);
        if($wcg_info['ze_jz'] == '0'){
            $this->error('当前华克金禁止转入');
        }else {
            $moble = M('User')->where(array('id' => userid()))->getField('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        //$tradeno = M('User')->where(['id' => userid()])->getField('invit');
        $tradeno = self::get_code();
        //用户转入记录
        $list = M('Myzr')->where(['userid' => userid() , 'coinname' => 'wcg'])->order('id desc')->select();
        $this->assign('wcg_info' , $wcg_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        $this->display();
    }

    public function wcgzr_info(){
        $id = I('id/d',null);
        if (!userid()) {
            die(json_encode(array('code'=>400,'msg'=>'请先登录','data'=>'')));
        }
        if (!check($id, 'd')) {
            die(json_encode(array('code'=>401,'msg'=>'参数错误','data'=>'')));
        }
        $data = M('Myzr')->where(['id'=>$id,'userid'=>userid()])->find();
        if($data){
                die(json_encode(array('code'=>200,'msg'=>'查询成功','data'=>$data)));
            }else{
                die(json_encode(array('code'=>402,'msg'=>'未查询到记录','data'=>'')));
            }
    }


    static function get_code(){
        $tradeno='';
        for ($i = 1; $i <= 8; $i++) {
            $tradeno.=chr(rand(65, 90));
        }
        $data = M('Myzr')->field('tradeno')->where(array('LENGTH(tradeno)'=>['gt',0]))->select();
        if(!empty($data)){
            foreach($data as $k=>$v){
                $arr[] = $v['tradeno'];
            }        
        }else{
            return $tradeno;
        }
        while(in_array($tradeno,$arr)){
            $tradeno='';
            for ($i = 1; $i <= 8; $i++) {
                $tradeno.=chr(rand(65, 90));
            }
        }
        return $tradeno;
    }

    //华克金转入撤销
    public function wcgChexiao(){
        $id = I('id/d', NULL);
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $myzr = M('Myzr')->where(array('id' => $id))->find();
        if (!$myzr) {
            $this->error('充值订单不存在！');
        }

        if ($myzr['userid'] != userid()) {
            $this->error('非法操作！');
        }
        //限定每天只能撤销两次
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $chexiao_num = count(M('Myzr')->where(['userid' => userid(),'status' => 2 ,'addtime' =>array('gt' , $beginToday)])->select());
        if ($chexiao_num >= 5){
            $this->error('您当天撤销操作过于频繁，请明天再进行尝试。');
        }

        $rs = M('Myzr')->where(array('id' => $id))->save(array('status' => 2));
        if ($rs) {
            $this->success('操作成功', array('id' => $id));
        } else {
            $this->error('操作失败！');
        }

    }
    //华克金
    public function mywcg(){
        if (!userid()) {
            redirect('/#login');
        }

        if (IS_POST) {
            //周六下午两点
            if (time() < 1533967200) {
                $this->error('活动 八月十一号十四时之后开始！');
            }

            //用户判断
            $IssueWcg = M('IssueWc')->where(array('userid' => userid(),'coinname'=> 'wcg', 'status' => 1))->order('addtime desc')->limit(1)->find();
            if ($IssueWcg && $IssueWcg['userid'] != userid()) {
                $this->error('非法访问');
            }

            //每天一次判断
            if ($IssueWcg) {
                if (86400 > (time() - $IssueWcg['addtime'])) {
                    $this->error('一天只能获取一次 WCG 利息！');
                }
            }

            //获取可用数量，冻结数量，总数量，利息数量
            $total = M('UserCoin')->field('wcg, wcgd, wcg + wcgd as sum')->where(['userid' => userid()])->find();
            if ($total['wcg'] == 0) {
                $this->error('账户可用为零，不能获取 WCG 利息！');
            }
            $fee = $total['wcg'] * C('wcg_interest');
//            $fee = $total['wc'] * '0.0002';
            $mo = M();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables weike_user_coin write  , weike_issue_wc write ');
            $rs = array();
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setInc('wcg', $fee);
            $rs[] = $mo->table('weike_issue_wc')->add([
                'userid' => userid(),
                'name' => '华克金',
                'coinname' => 'wcg',
                'num' => $total['wcg'],
                'freeze' => $total['wcgd'],
                'interest' => $fee,
                'count' => $IssueWcg['count'] == 0 ? 1 : $IssueWcg['count'] + 1,
                'addtime' => time(),
                'status' => 1
            ]);

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                $this->success('操作成功！');
            } else {
                $mo->execute('rollback');
                $this->error('操作失败！');
            }
        } else {
            $where = ['userid' => userid(), 'coinname' => 'wcg'];
            $count = M('IssueWc')->where($where)->count();
            $Page = new \Think\Page($count, 10);
            $show = $Page->show();
            $list = M('IssueWc')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

            foreach ($list as $k => $v) {
                $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            }
            foreach ($list as $k => $v) {
                $list[$k]['nun'] = 1;
                if (preg_match('/E/', strtolower($v['interest']))) {
                    $a = explode("e", strtolower($v['interest']));
                    $v = bcmul($a[0], bcpow(10, $a[1], 9), 9);

                }
                if (strlen($v['interest']) > 10) {
                    $list[$k]['interest'] = substr($v['interest'], 0, 10);

                }
            }
            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display();
        }
    }

}