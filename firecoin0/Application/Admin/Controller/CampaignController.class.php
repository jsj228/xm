<?php
/**
 * Created by Sublime.
 * User: dream
 * Date: 4/23/2018
 * Time: 15:16 PM
 */

namespace Admin\Controller;

class CampaignController extends AdminController
{
    const id = 1;
    const coin =26;
    //新用户认证设置
    public function set(){
        $param = M('Campaign')->where(array('id'=>self::id))->find();
        $coin = M('Coin')->where(array('status'=>1,'zc_min'=>['gt',0]))->field('id,title,zc_min')->select();
        $this->assign('coin',$coin);
        $this->assign('data',$param);
        $this->display();
    }


    public function edit_award(){
        $id = I('get.id/d');
        $data = M('RegisterAward')->where(array('id'=>$id,'type'=>3))->find();
        if(empty($data)){
            $this->error('未查询到记录');
        }
        $coin = M('Coin')->where(array('status'=>1,'zc_min'=>['gt',0]))->field('id,title,zc_min')->select();
        $this->assign('coin',$coin);
        $this->assign('data',$data);
        $this->display('add');
    }

    public function do_edit(){
        $param = I('post.');
        $award = M('RegisterAward')->where(array('id'=>$param['id'],'type'=>3))->find();
        if(empty($award)){
            die(json_encode(array('code'=>400,'msg'=>'未查询到奖励','data'=>'')));
        }
         $coin = M('Coin')->where(array('status'=>1,'id'=>$award['coin'],'zc_min'=>['gt',0]))->find();
        if(empty($coin)){
            die(json_encode(array('code'=>401,'msg'=>'币种错误','data'=>'')));
        }
        $param['award_num'] = round((float)$param['award_num'],4);
        if(!($param['award_num'] > 0 && $param['award_num'] <= $coin['zc_min'])){
            die(json_encode(array('code'=>402,'msg'=>'数量错误','data'=>'')));
        }
        $update_data['nums'] = $param['award_num'];
        if(!M('RegisterAward')->where(array('id'=>$param['id']))->save($update_data)){
            die(json_encode(array('code'=>403,'msg'=>'修改失败','data'=>'')));
        }
        die(json_encode(array('code'=>200,'msg'=>'修改成功','data'=>'')));
    }


    public function send_award(){
        $id = I('get.id/d');
        $award = M('RegisterAward')->where(array('id'=>$id,'type'=>3,'status'=>0))->find();
        if(empty($award)){
            $this->error('未查询到记录');
        }
        $coin = M('Coin')->where(array('status'=>1,'id'=>$award['coin'],'zc_min'=>['gt',0]))->find();
        if(empty($coin)){
            $this->error('币种错误');
        }
        $award['nums'] = round((float)$award['nums'],4);
        if(!($award['nums'] > 0 && $award['nums'] <= $coin['zc_min'])){
            $this->error('数量错误');
        }
        $user = M('User')->where(array('username'=>$award['one'],'status'=>1))->field('id')->find();
        if(empty($user)){
            $this->error('未查询到用户');
        }
        M()->startTrans();
        $tag = true;
        $sql = "update weike_user_coin set  ".$coin['name']." = ".$coin['name']." + ".$award['nums']."  where userid = ".$user['id'];
        if(!M()->execute($sql)){
            $tag = ($tag && false);
        }
        $update_data['status'] = 1;
        if(!M('RegisterAward')->where(array('id'=>$id))->save($update_data)){
            $tag = ($tag && false);
        }
        if($tag){
            M()->commit();
            return $this->success('发送成功');
        }else{
            M()->rollback();
            return $this->error('发送失败');
        }
    }

    //奖励日志
    public function log(){
        $search_data['coin'] = I('post.coin') ? I('post.coin'):session('search_data')['coin'];
        $search_data['type'] = I('post.type') ? I('post.type'):session('search_data')['type'];
        $search_data['name'] = I('post.name') !== null ? I('post.name'):session('search_data')['name'];
        $search_data['status'] = I('post.status') ? I('post.status'):session('search_data')['status'];
        if(!empty($search_data)){
            session('search_data',$search_data);
        }else{
            session('search_data',array('coin'=>self::coin));
        }
        $wa = D('RegisterAward');
        $this->assign('coins',$wa->get_coins());
        $this->assign($wa->search(session('search_data')));
        $this->display();
    }

    public function add(){
        $coin = M('Coin')->where(array('status'=>1,'zc_min'=>['gt',0]))->field('id,title,zc_min')->select();
        $this->assign('coin',$coin);
        $this->display();
    }

    public function do_add(){
        $param = I('post.');
        //{username: "123", coin: "2", award_num: "12"}
        $user = M('User')->where(array('username'=>trim($param['username']),'status'=>1))->field('id')->find();
        if(empty($user)){
            die(json_encode(array('code'=>400,'msg'=>'未查询到用户','data'=>'')));
        }
        $coin = M('Coin')->where(array('status'=>1,'id'=>$param['coin'],'zc_min'=>['gt',0]))->field('id,title,zc_min')->find();
        if(empty($coin)){
            die(json_encode(array('code'=>401,'msg'=>'币种错误','data'=>'')));
        }
        $param['award_num'] = round((float)$param['award_num'],4);
        if(!($param['award_num'] > 0 && $param['award_num'] <= $coin['zc_min'])){
            die(json_encode(array('code'=>402,'msg'=>'奖励数量不能大于最小转出数量','data'=>array((float)$coin['zc_min']))));
        }
        $add_data['users'] = trim($param['username']);
        $add_data['one'] = trim($param['username']);
        $add_data['two'] = '';
        $add_data['n'] = 1;
        $add_data['nums'] = $param['award_num'];
        $add_data['coin'] = $param['coin'];
        $add_data['active_time'] = time();
        $add_data['admin_id'] = session('admin_id');
        $add_data['times'] = 0;
        $add_data['add_time'] = time();
        $add_data['type'] = 3;
        $add_data['status'] = 0;
        if(!M('RegisterAward')->add($add_data)){
            die(json_encode(array('code'=>403,'msg'=>'添加错误','data'=>'')));
        }
        die(json_encode(array('code'=>200,'msg'=>'添加成功','data'=>'')));
    }

    //奖励
    public function add_reward_log($userid){
        //判断奖励是否开启
        $campaign = M('Campaign')->where(array('id'=>self::id))->find();
        if(empty($campaign)){
            return json_encode(array('code'=>400,'msg'=>'未查询到活动','data'=>''));
        }
        //状态
        if((int)$campaign['status']!=0){
            return json_encode(array('code'=>401,'msg'=>'活动已被禁用','data'=>''));
        }
        //时间
        if(self::get_status($campaign) !=2){
            return json_encode(array('code'=>402,'msg'=>'不在活动时间内','data'=>''));
        }
        //查询认证用户信息
        $user = M('User')->where(array('id'=>$userid,'idcardauth'=>1,'status'=>1))->field('invit_1,id,username')->find();
        if(empty($user)){
            return json_encode(array('code'=>403,'msg'=>'未查询到用户','data'=>''));
        }
        $userarr = $usernamearr = array();
        $userarr[] = $userid;
        $usernamearr[] = $user['username'];
        if((int)$user['invit_1'] > 0){
             $reuser = M('User')->where(array('id'=>$user['invit_1'],'status'=>1))->field('id,username')->find();
             if((int)$reuser['id'] > 0){
                $userarr[] = $reuser['id'];
                $usernamearr[] = $reuser['username'];
             }
        }
        //获取币种
        $coin = M('Coin')->where(array('status'=>1,'id'=>$campaign['coin'],'zc_min'=>['gt',0]))->field('id,name')->find();
        if(empty($coin)){
            return json_encode(array('code'=>405,'msg'=>'币种错误','data'=>''));
        }

        $users = implode(',',$userarr);
        
        //奖励
        $coinname = $coin['name'];
        $num = $campaign['num'];
        if(!M('RegisterAward')->field("id")->where(['active_time'=>$campaign['start_time']])->find()){
            $level = M('RegisterAward')->field("count(1) as c")->group('active_time')->find();
            if($level['c']!=0){
                $times['times'] = (int)$campaign['times'] + 1;
                if(M('Campaign')->where(array('id'=>self::id))->save($times)){
                    $campaign['times'] = $times['times'];
                }else{
                    return json_encode(array('code'=>406,'msg'=>M('Campaign')->getLastSql(),'data'=>''));
                }
            }
            
        }
        M()->startTrans();
        $tag = true;
        //重复提交
        if(M('RegisterAward')->where(['users'=>implode(',', $usernamearr),'times'=>$campaign['times']])->find()){
            $tag = ($tag && false); 
        }else{
            $tag = ($tag && true);
        }
        $sql = "update weike_user_coin set  $coinname = $coinname + $num  where userid in ($users)";
        if(!M()->execute($sql)){
            $tag = ($tag && false);
        }
        
        if(count($usernamearr)==1){
            $add_data['users'] = implode(',',$usernamearr);
            $add_data['one'] = $usernamearr[0];
            $add_data['two'] = '';
            $add_data['n'] = 1;
            $add_data['nums'] = $num;
            $add_data['coin'] = $campaign['coin'];
            $add_data['active_time'] = $campaign['start_time'];
            $add_data['admin_id'] = $campaign['admin_id'];
            $add_data['times'] = $campaign['times'];
            $add_data['add_time'] = time();
            $add_data['type'] = 1;
            $add_data['status'] = 1;
            if(M('RegisterAward')->add($add_data)){
                $tag =($tag && true);
            }
        }else{
            $add_data['users'] = implode(',',$usernamearr);
            $add_data['one'] = $usernamearr[0];
            $add_data['two'] = $usernamearr[1];
            $add_data['nums'] = $num * 2;
            $add_data['n'] = 2;
            $add_data['coin'] = $campaign['coin'];
            $add_data['active_time'] = $campaign['start_time'];
            $add_data['admin_id'] = $campaign['admin_id'];
            $add_data['times'] = $campaign['times'];
            $add_data['add_time'] = time();
            $add_data['type'] = 1;
            $add_data['status'] = 1;
            if(M('RegisterAward')->add($add_data)){
                $tag = ($tag && true);
            }
        }
        if($tag){
            M()->commit();
            return json_encode(array('code'=>200,'msg'=>'事务提交成功','data'=>''));
        }else{
            M()->rollback();
            return json_encode(array('code'=>407,'msg'=>'事务回滚，添加失败','data'=>''));
        }
    }


    public function set_status(){
        $param = I('post.');
        if($param['id'] != self::id){
            die(json_encode(array('code'=>400,'msg'=>'参数错误',data=>array())));
        }
        $old_status = M('Campaign')->where(array('id'=>self::id))->field('status')->find();
        if($param['status'] == $old_status['status']){
            die(json_encode(array('code'=>401,'msg'=>'状态错误',data=>array())));
        }
        if(M('Campaign')->where(array('id'=>self::id))->save(['status'=>$param['status']])){
            die(json_encode(array('code'=>200,'msg'=>'修改成功',data=>array('status'=>$param['status']))));
        }
        die(json_encode(array('code'=>402,'msg'=>'修改失败',data=>array())));
    }


    public function set_value(){
        $param = I('post.');
        if($param['campaign_id'] != self::id){
            die(json_encode(array('code'=>400,'msg'=>'参数错误',data=>array())));
        }
        $param['start_time'] = strtotime($param['start_time']);
        $param['end_time'] = strtotime($param['end_time']);
        $param['num'] = round((float)$param['num'],4);
        //{coin: "4", num: "0.0000", start_time: 1524469620, end_time: 1524728820, campaign_id: "1"}
        $data = M('Campaign')->where(array('id'=>self::id))->find();
        if($data['status']==1){
            die(json_encode(array('code'=>401,'msg'=>'禁用状态无法修改',data=>array())));
        }
        //验证数据
        $coin = M('Coin')->where(array('status'=>1,'id'=>(int)$param['coin'],'zc_min'=>['gt',0]))->field('id,title,zc_min')->find();
        if(empty($coin)){
            die(json_encode(array('code'=>404,'msg'=>'币种错误',data=>array())));
        }
        if(!($param['num'] > 0 && $param['num'] <= $coin['zc_min'])){
            die(json_encode(array('code'=>405,'msg'=>'数量填写错误',data=>array())));
        }
        $game_status = self::get_status($data);
        if($game_status==2){
            if($data['start_time'] != $param['start_time']){
                die(json_encode(array('code'=>402,'msg'=>'活动已开启，请勿修改开始时间',data=>array())));
            }
            if($data['coin'] != $param['coin']){
                die(json_encode(array('code'=>403,'msg'=>'活动已开启，请勿修改奖励币种',data=>array())));
            }
            if($param['end_time'] < time()){
                die(json_encode(array('code'=>406,'msg'=>'结束时间填写错误',data=>array())));
            }
            $save_data['num'] = $param['num']; 
            $save_data['end_time'] = $param['end_time'];
            $save_data['admin_id'] = session('admin_id');
            $save_data['admin_time'] = time();
            if(!M('Campaign')->where(array('id'=>self::id))->save($save_data)){
                die(json_encode(array('code'=>407,'msg'=>'修改错误',data=>array())));
            } 
        }else{
            if($param['add_time'] > time() && $param['end_time'] > $param['start_time'] ){
                die(json_encode(array('code'=>408,'msg'=>'时间填写错误',data=>array())));
            }
            $save_data['coin'] = $param['coin'];
            $save_data['num'] = $param['num'];
            $save_data['start_time'] = $param['start_time'];
            $save_data['end_time'] = $param['end_time'];
            $save_data['admin_id'] = session('admin_id');
            $save_data['admin_time'] = time();
            if(!M('Campaign')->where(array('id'=>self::id))->save($save_data)){
                die(json_encode(array('code'=>407,'msg'=>'修改错误',data=>array())));
            } 
        }
        die(json_encode(array('code'=>200,'msg'=>'修改成功',data=>array())));
    }

    static function get_status($data){
        $time = time();
        if($data['start_time'] >= $time){
            return 1; //未开始
        }elseif($data['start_time'] < $time && $data['end_time'] > $time){
            return 2; //进行中
        }else{
            return 3; //已结束
        }
    }
}