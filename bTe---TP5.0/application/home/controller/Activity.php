<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Activity extends HomeCommon
{
    //WC
    public function mywc()
    {
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        if ($this->request->isPost()) {
            //星期一开始
            if (time() < 1519747200) {
                $this->error('活动 一月二十八号之后开始！');
            }

            //用户判断
            $IssueWc = Db::name('IssueWc')->where(['userid' => $uid,'coinname'=> 'wc', 'status' => 1])->order('addtime desc')->limit(1)->find();
            if ($IssueWc && $IssueWc['userid'] != $uid) {
                $this->error('非法访问');
            }

            //每天一次判断
            if ($IssueWc) {
                if (86400 > (time() - $IssueWc['addtime'])) {
                    $this->error('一天只能获取一次 WC 利息！');
                }
            }

            //获取可用数量，冻结数量，总数量，利息数量
            $total = Db::name('UserCoin')->field('wc, wcd')->where(['userid' => $uid])->find();
            if ($total['wc'] == 0) {
                $this->error('账户余额为零，不能获取 WC 利息！');
            }
            $fee = $total['wc'] * config('wc_interest');
            $mo = Db::name('');
            $mo->startTrans();
            try{
                Db::name('user_coin')->where(['userid' => $uid])->setInc('wc', $fee);
                Db::name('issue_wc')->insert([
                    'userid' => $uid,
                    'name' => '云尊币',
                    'coinname' => 'wc',
                    'num' => $total['wc'],
                    'freeze' => $total['wcd'],
                    'interest' => $fee,
                    'count' => $IssueWc['count'] == 0 ? 1 : $IssueWc['count'] + 1,
                    'addtime' => time(),
                    'status' => 1
                ]);
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
                $flag = false;
                $mo->rollback();
            }

            if ($flag){
                $this->success('获取成功');
            }else{
                $this->error('获取失败');
            }
        } else {

            $username = Db::name('User')->where(['id'=>$uid])->value('username');
            $list = Db::name('IssueWc')->where(['userid' => $uid,'coinname'=> 'wc'])->order('id desc')->paginate(10,false,[]);
            if ($list){
                $page = $list->render();
                foreach ($list as $k => $v) {
                    $data = $v;
                    $data['username'] = $username;
                    $list->offsetSet($k,$data);
                }

                $this->assign('list', $list);
                $this->assign('page', $page);
            }
           return $this->fetch();
        }
    }

        //华克金转入分流
    public function wcg_zr(){
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }
        //获取用户华克金数量
        $user_wcg = Db::name('UserCoin')->where(['userid' => $uid])->value('wcg');

        //获取华克金配置
        $wcg_info = Db::name('Coin')->where(['name' => 'wcg'])->find();

        if($wcg_info['ze_jz'] == '0'){
            $this->error('当前华克金禁止转入');
        }else {
            $moble = Db::name('User')->where(['id' => $uid])->value('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            }
            $this->assign('moble', $moble);
        }

        $tradeno = self::get_code();
        //用户转入记录
        $list =  Db::name('Myzr')->where(['userid' => userid() , 'coinname' => 'wcg'])->order('id desc')->limit(10)->select();
        $this->assign('wcg_info' , $wcg_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        return $this->fetch();
    }

    public function wcgzr_info(){
        $id = input('id');
        $uid = userid();
        if (!$uid) {
            die(json_encode(array('code'=>400,'msg'=>'请先登录','data'=>'')));
        }
        if (!check($id, 'd')) {
            die(json_encode(array('code'=>401,'msg'=>'参数错误','data'=>'')));
        }
        $data = Db::name('Myzr')->where(['id'=>$id,'userid'=>$uid])->find();
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
        return $tradeno;
    }

    //华克金转入撤销
    public function wcgChexiao(){
        $id = input('id');
        $uid = userid();
        if (!$uid) {
            $this->error('请先登录！');
        }
        if (!$id) {
            $this->error('参数错误！');
        }

        $myzr = Db::name('Myzr')->where(array('id' => $id))->find();
        if (!$myzr) {
            $this->error('充值订单不存在！');
        }

        if ($myzr['userid'] != $uid) {
            $this->error('非法操作！');
        }
        //限定每天只能撤销两次
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $chexiao_num = count(Db::name('Myzr')->where(['userid' => $uid,'status' => 2 ,'addtime' => ['gt' , $beginToday]])->select());
        if ($chexiao_num >= 5){
            $this->error('您当天撤销操作过于频繁，请明天再进行尝试。');
        }

        $rs = Db::name('Myzr')->where(['id' => $id])->update(['status' => 2]);
        if ($rs) {
            return ['status'=>1,'msg'=>'操作成功','id'=>$id];
        } else {
            $this->error('操作失败！');
        }

    }
        //华克金
    public function mywcg(){
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        if ($this->request->isPost()) {
            //周六下午两点
            if (time() < 1533967200) {
                $this->error('活动 八月十一号十四时之后开始！');
            }

            //用户判断
            $IssueWcg = Db::name('IssueWc')->where(array('userid' => $uid,'coinname'=> 'wcg', 'status' => 1))->order('addtime desc')->limit(1)->find();
            if ($IssueWcg && $IssueWcg['userid'] != $uid) {
                $this->error('非法访问');
            }
      
            //每天一次判断
            if ($IssueWcg) {
                if (86400 > (time() - $IssueWcg['addtime'])) {
                    $this->error('一天只能获取一次 WCG 利息！');
                }
            }

            //获取可用数量，冻结数量，总数量，利息数量
            $total = Db::name('UserCoin')->field('wcg, wcgd')->where(['userid' => $uid])->find();
            if ($total['wcg'] == 0) {
                $this->error('账户可用为零，不能获取 WCG 利息！');
            }
            $fee = $total['wcg'] * config('wcg_interest');
            $mo = Db::name('');
            $mo->startTrans();
            try{
                Db::name('user_coin')->where(['userid' => $uid])->setInc('wcg', $fee);
                Db::name('issue_wc')->insert([
                    'userid' => $uid,
                    'name' => '华克金',
                    'coinname' => 'wcg',
                    'num' => $total['wcg'],
                    'freeze' => $total['wcgd'],
                    'interest' => $fee,
                    'count' => $IssueWcg['count'] == 0 ? 1 : $IssueWcg['count'] + 1,
                    'addtime' => time(),
                    'status' => 1
                ]);
                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
                $flag = false;
                $mo->rollback();
            }


            if ($flag) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！');
            }

        } else {

            $username = Db::name('User')->where(['id'=>$uid])->value('username');
            $list = Db::name('IssueWc')->where(['userid' => userid(), 'coinname' => 'wcg'])->order('id desc')->paginate([10,false,[]]);
            if ($list){
                $page = $list->render();
                foreach ($list as $k => $v) {
                    $data = $v;
                    $data['username'] = $username;
                    $list->offsetSet($k,$data);
                }

                $this->assign('list', $list);
                $this->assign('page', $page);
            }
            return $this->fetch();
        }
    }
}
