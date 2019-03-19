<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;
class finance extends HomeCommon
{
    //财务中心-我的财产
    public function index()
    {
        $uid = userid();
        if (!$uid) {
           return redirect('/#login');
        }
        $CoinList = Db::name('Coin')->where(['status' => 1,'name'=>['in',config('coin_on')]])->paginate([10,false,[]]);
        $page = $CoinList->render();
        $UserCoin =  Db::name('UserCoin')->where(['userid' => $uid])->find();
        $Market =  Db::name('Market')->where(['status' => 1])->select();

        foreach ($Market as $k => $v) {
            $Market[$v['name']] = $v;
        }
        $cny['zj'] = 0;
        foreach ($CoinList as $k => $v) {
            if ($v['name'] == 'cny') {
                $cny['ky'] = round($UserCoin[$v['name']], 2) * 1;
                $cny['dj'] = round($UserCoin[$v['name'] . 'd'], 2) * 1;
                $cny['zj'] = $cny['zj'] + $cny['ky'] + $cny['dj'];
            } else {

                if ($Market[config('market_type')[$v['name']]]['new_price']) {

                    $jia = $Market[config('market_type')[$v['name']]]['new_price'];

                } else {
                    $jia = 1;
                }
                //开启市场时才显示对应的币
                if(in_array($v['name'],config('coin_on'))){
                    $coinList[$v['name']] = [
                        'name' => $v['name'],
                        'img' => $v['img'],
                        'title' => $v['title'] . '(' . strtoupper($v['name']) . ')',
                        'xnb' => number_format($UserCoin[$v['name']], 6),
                        'xnbd' => number_format($UserCoin[$v['name'] . 'd'], 6),
                        'xnbz' => number_format($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd'], 6),
                        'jia' => $jia,
                        'zhehe' => number_format(($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia, 2)
                    ];
                }
                $cny['zj'] = round($cny['zj'] + (($UserCoin[$v['name']] + $UserCoin[$v['name'] . 'd']) * $jia), 2);
            }
        }


        $this->assign('page', $page);
        $this->assign('coinList', $coinList);
         return $this->fetch();
    }

    //分红中心
    public function fhindex()
    {
        if (!userid()) {
            return redirect('/#login');
        }

        $this->assign('prompt_text', model('Text')->get_content('game_fenhong'));
        $coin_list = model('Coin')->get_all_xnb_list_allow();
        foreach ($coin_list as $k => $v) {
            if ($k == 'btmz'){
                continue;
            }
            $list[$k]['img'] = model('Coin')->get_img($k);
            $list[$k]['title'] = $v;
            $list[$k]['quanbu'] = model('Coin')->get_sum_coin($k);
            $list[$k]['wodi'] = model('Coin')->get_sum_coin($k, userid());
            $list[$k]['bili'] = $list[$k]['quanbu'] ? round(($list[$k]['wodi'] / $list[$k]['quanbu']) * 100, 2) . '%': '0%';
        }

        $this->assign('list', $list);
       return $this->fetch();
    }

    //我的分红
    public function myfhroebx()
    {
        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text',model('Text')->get_content('game_fenhong_log'));
        $where['userid'] = userid();
        $Model = Db::name('FenhongLog');
        
        $list = $Model->where($where)->order('id desc')->paginate([10,false,[]]);
        $page = $list->render();  
        $this->assign('list', $list);
        $this->assign('page', $page);
       return $this->fetch();
    }
//银行卡开启关闭
    public function savebank(){
        $id=input('userId');
        $vals=input('status');
        if (!userid()) {
            redirect('/#login');
        }
        $userbank=DB::name('UserBank')->where(['userid'=>userid(),'status'=>2])->count();

        if($vals==1){
            if($userbank<=1){
                return array('status'=>0,'msg'=>'收款方式至少开启一个!');
            }
            $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1) {
                return array('status'=>0,'msg'=>'订单匹配中不可关闭!');
            }
        }

        if (!$id) {
            return array('status'=>0,'msg'=>'银行卡不存在!');
        }
        $bank=DB::name('user_bank')->where(array('userid' => userid(), 'id' => $id))->find();
        if (!$bank) {
            return array('status'=>0,'msg'=>'非法访问!');
        }

        if($vals==2){
            $bank=DB::name('UserBank')->where(array('userid'=>userid(),'Paytype'=>0,'status'=>2))->count();
            if($bank>=1){
                return array('status'=>0,'msg'=>'只能开启一张网银!');

            }
        }

        if ($vals==1 || $vals==2) {
            $data=array(
                'status'=>$vals,
            );
            DB::name('user_bank')->where(array('userid' => userid(), 'id' => $id))->update($data);
        }
    }
    //添加支付宝
    public  function AddAlipay(){
        if(!userid()){
            redirect('/#login');
        }
        $id=input('wId');//类型
        $type=2;//类型
        $Alipay=input('zfbNumber');//支付宝号
        $img=input('fileName');//二维码图片
        $Alipaycode=input('phoneMsg');//验证码
        $userPWD=input('userPWD');//交易密码
        if ($Alipaycode != session('WeChatcode')) {
            return array('status'=>0,'msg'=>'手机验证码错误!');
        }

        if (!DB::name('user')->where(array('id'=>userid(),'paypassword'=>md5($userPWD)))->find()) {
            return array('status'=>0,'msg'=>'交易密码错误!');
        }
        if(!$id){
            $name=DB::name('user')->where(array('id'=>userid()))->getField('truename');
            if(DB::name('UserBank')->where(array('userid'=>userid(),'paytype'=>2))->find()){
                return array('status'=>0,'msg'=>'支付宝账号已存在!');
            }
            $res=DB::name('UserBank')->add(array('userid' => userid(),'name'=>$name, 'bank' => $Alipay, 'addtime' => time(), 'status' => 2,'Paytype'=>$type,'img'=>$img));
            if($res){
                return array('status'=>1,'msg'=>'支付宝账号添加成功!');
            }else{
                return array('status'=>0,'msg'=>'支付宝账号添加失败!');
            }
        }else{
            $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1) {
                return array('status'=>0,'msg'=>'订单匹配中不可修改!');
            }
            $res=DB::name('UserBank')->where(array('id'=>$id,'userid'=>userid()))->update(array('bank' => $Alipay,'img'=>$img));
            if($res){
                return array('status'=>1,'msg'=>'编辑成功');
            }else{
                return array('status'=>0,'msg'=>'编辑失败');
            }
        }
    }
    //添加微信
    public  function AddWeChat(){
        if(!userid()){
            redirect('/#login');
        }
        $id=input('wId');
        $type=1;//类型
        $fileName=input('fileName');//微信二维码
        $WeChatName=input('wxNumber');//微信号
        $phoneMsg=input('phoneMsg');//验证码
        $userPWD=input('userPWD');//交易密码

        if(!DB::name('user')->where(array('id'=>userid(),'paypassword'=>md5($userPWD)))->find()){
            return array('status'=>0,'msg'=>'交易密码错误');

        }
        if ($phoneMsg != session('WeChatcode')) {
            return array('status'=>0,'msg'=>'手机验证码错误');

        }
        $name=DB::name('user')->where(array('id'=>userid()))->value('truename');
        if(!$id){
            if(DB::name('user_bank')->where(array('userid'=>userid(),'paytype'=>1))->find()){
                return array('status'=>0,'msg'=>'微信账号已存在');

            }
            $res=DB::name('user_bank')->insert(array('name'=>$name,'bank'=>$WeChatName,'userid' => userid(), 'addtime' => time(), 'status' => 2,'Paytype'=>$type,'img'=>$fileName));
            if($res){
                return array('status'=>1,'msg'=>'微信账号添加成功');

            }else{
                return array('status'=>0,'msg'=>'微信账号添加失败');
            }
        }else{
            $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1) {
                return array('status'=>0,'msg'=>'订单匹配中不可修改');
            }

            $res=DB::name('user_bank')->where(array('id'=>$id,'userid'=>userid()))->update(array('bank'=>$WeChatName,'img'=>$fileName));

            if($res){
                return array('status'=>1,'msg'=>'编辑成功');
            }else{
                return array('status'=>0,'msg'=>'编辑失败');
            }
        }
    }
    //----->
    //微信支付宝开启关闭
    public function savemy(){
        $id=input('userId');
        $vals=input('status');
        if (!userid()) {
            redirect('/#login');
        }
        if (!$id && $vals==1) {
            return array('status'=>0,'msg'=>'关闭绑定不存在!');
        }
        if (!$id && $vals==2) {
            return array('status'=>0,'msg'=>'开启绑定不存在!');

        }
        $userbank=DB::name('UserBank')->where(['userid'=>userid(),'status'=>2])->count();
        if($vals==1){
            if($userbank<=1){
                return array('status'=>0,'msg'=>'收款方式至少开启一个!');
            }
            $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1) {
                return array('status'=>0,'msg'=>'订单匹配中不可关闭!');
            }
        }
        $bank=DB::name('user_bank')->where(array('userid' => userid(), 'id' => $id))->find();
        if (!$bank) {
            return array('status'=>0,'msg'=>'非法访问!');
        }
        if ($vals==1 || $vals==2) {
            $data=array(
                'status'=>$vals,
            );
            DB::name('user_bank')->where(array('id' => $id))->update($data);
        }
    }
    //上传二维码
    public function myczTypeImage()
    {
        if($_FILES['upload_file']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }
        $ext = pathinfo($_FILES['upload_file']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }
        $path = 'Upload/public/';
        $filename = md5($_FILES['upload_file']['name']. uniqid() . userid() ) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }
        // echo {'FileName':$filename,'userId':$_FILES['userId']};
        echo $filename;
        exit();
    }
    //银行
    public function bank(){

        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        $UserBankType = DB::name('UserBankType')->where(array('status' => 1))->order('id desc')->select();
        $this->assign('UserBankType', $UserBankType);

        $user = DB::name('User')->where(array('id' => userid()))->find();
        if($user['idcardauth'] == 0){
            redirect('/user/nameauth');
        }

        $truename = $user['truename'];
        $this->assign('truename', $truename);
        $UserBank = DB::name('UserBank')->where(array('userid' => userid(), 'Paytype' =>0))->order('id desc')->select();

        $this->assign('UserBank', $UserBank);
        return $this->fetch();
    }

    //添加银行
    public function upbank()
    {
        $name = input('name/s');
        $bank = input('bank/s');
        $bankprov = input('bankprov/s');
        $bankcity = input('bankcity/s');
        $bankaddr = input('bankaddr/s');
        $bankcard = input('bankcard/s');
        $paypassword = input('paypassword/s');

        if (!userid()) {
            redirect('/#login');
        }

        if (!check($name, 'a')) {
            return array('status'=>0,'msg'=>'备注名称格式错误');
        }

        if (!check($bank, 'a')) {
            return array('status'=>0,'msg'=>'开户银行格式错误');

        }

        if (!check($bankprov, 'c')) {
            return array('status'=>0,'msg'=>'开户省市格式错误');

        }

        if (!check($bankcity, 'c')) {
            return array('status'=>0,'msg'=>'开户省市格式错误2');

        }

        if (!check($bankaddr, 'a')) {
            return array('status'=>0,'msg'=>'开户行地址格式错误');


        }

        if (!check($bankcard, 'd')) {
            return array('status'=>0,'msg'=>'银行账号格式错误');

        }

        if(strlen($bankcard) < 16 || strlen($bankcard) > 19){
            return array('status'=>0,'msg'=>'银行账号格式错误');

        }

        if (!check($paypassword, 'password')) {
            return array('status'=>0,'msg'=>'交易密码格式错误');
        }

        $user_paypassword = DB::name('User')->where(array('id' => userid()))->value('paypassword');
        if (md5($paypassword) != $user_paypassword) {
            return array('status'=>0,'msg'=>'交易密码错误');

        }

        if (!DB::name('UserBankType')->where(array('title' => $bank))->find()) {
            return array('status'=>0,'msg'=>'开户银行错误');

        }

        $userBank = DB::name('UserBank')->where(array('userid' => userid(),'Paytype'=>0))->select();
        foreach ($userBank as $k => $v) {
            if ($v['name'] == $name) {
                return array('status'=>0,'msg'=>'请不要使用相同的备注名称');

            }

            if ($v['bankcard'] == $bankcard) {
                return array('status'=>0,'msg'=>'银行卡号已存在');

            }
        }
        $Bank = DB::name('UserBank')->where(array('userid' => userid(),'Paytype'=>0))->count();
        if (5 <= count($Bank)) {
            return array('status'=>0,'msg'=>'每个用户最多只能添加5帐银行卡');

        }
        /*暂无编辑*/
        if(!$id){
            $userbank=DB::name('UserBank')->where(['userid'=>userid(),'Paytype'=>0,'status'=>2])->select();
            if ($userbank) {
                DB::name('UserBank')->insert(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1,'Paytype'=>0));
                return array('status'=>1,'msg'=>'银行添加成功');

            } elseif(!$userbank) {
                DB::name('UserBank')->insert(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 2,'Paytype'=>0));
               // $this->ajaxReturn(array('status'=>1,'msg'=>'银行添加成功'));
                return array('status'=>1,'msg'=>'银行添加成功');

            }else{
                return array('status'=>0,'msg'=>'银行添加失败');
               // $this->ajaxReturn(array('status'=>0,'msg'=>'银行添加失败'));
            }
        }else{
            $count = DB::name('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1) {
                return array('status'=>0,'msg'=>'订单匹配中不可修改');
            }
            if (DB::name('UserBank')->where(array('id'=>$id,'userid' => userid()))->update(array('bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time()))) {

                return array('status'=>0,'msg'=>'编辑成功');
            } else {
                return array('status'=>0,'msg'=>'编辑失败');
            }
        }
    }

    //删除银行
    public function delbank()
    {
        $id = input('id/d');
        $paypassword = input('paypassword/s');
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $user_paypassword = Db::name('User')->where(['id' => $uid])->value('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!Db::name('UserBank')->where(['userid' => $uid, 'id' => $id])->find()) {
            $this->error('非法访问！');
        } else if (Db::name('UserBank')->where(['userid' => $uid, 'id' => $id])->delete()) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    //cny充值
    public function mycz()
    {
        $uid = userid();
        $status = input('status');
        if (!$uid) {
            redirect('/#login');
        }

        $myczType = Db::name('MyczType')->where(['status' => 1])->select();

        foreach ($myczType as $k => $v) {
            $myczTypeList[$v['name']] = $v['title'];
        }

        $alipaymycz = Db::name('MyczType')->where(['status' => 1 , 'name' => 'alipay'])->find();
        $weixinmycz = Db::name('MyczType')->where(['status' => 1 , 'name' => 'weixin'])->find();
        $bankmycz = Db::name('MyczType')->where(['status' => 1 , 'name' => 'bank'])->find();
        $this->assign('alipaymycz', $alipaymycz);
        $this->assign('weixinmycz', $weixinmycz);
        $this->assign('bankmycz', $bankmycz);
        $user_coin = Db::name('UserCoin')->where(['userid' => $uid])->find();
        $user_coin['cny'] = round($user_coin['cny'], 2);
        $user_coin['cnyd'] = round($user_coin['cnyd'], 2);
        $this->assign('user_coin', $user_coin);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4) || ($status == 5) || ($status == 6)) {
            $where['status'] = $status - 1;
        }
      
        $this->assign('status', $status);
        $where['userid'] = $uid;
        $count = Db::name('Mycz')->where($where)->count();
        
        $list = Db::name('Mycz')->where($where)->order('id desc')->paginate(10,false,[]);

        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['type'] = Db::name('MyczType')->where(['name' => $v['type']])->value('title');

                $data['typeEn'] = $v['type'];
                $data['num'] = (Num($v['num']) ? Num($v['num']) : '');
                $data['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');

                $list->offsetSet($k,$data);
            }

            $this->assign('list', $list);
            $this->assign('page', $page);
        }


        if (Db::name('config')->value('autocz')){
            return $this->fetch('autocz');
        }else{
           return $this->fetch();
        }
    }

    //充值汇款
    public function myczHuikuan()
    {
        $id = input('id');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mycz = Db::name('Mycz')->where(['id' => $id])->find();
        if (!$mycz) {
            $this->error('充值订单不存在！');
        }

        if ($mycz['userid'] != userid()) {
            $this->error('非法操作！');
        }

        if ($mycz['status'] != 0) {
            $this->error('订单已经处理过！');
        }

        $rs = Db::name('Mycz')->where(array('id' => $id))->update(array('status' => 3));
        if ($rs) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败！');
        }
    }

    //充值撤销
    public function myczChexiao()
    {
        $id = input('id');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mycz = Db::name('Mycz')->where(['id' => $id])->find();
        if (!$mycz) {
            $this->error('充值订单不存在！');
        }

        if ($mycz['userid'] != userid()) {
            $this->error('非法操作！');
        }

        if ($mycz['status'] == 1 || $mycz['status'] == 2 || $mycz['status'] == 4 || $mycz['status'] == 5) {
            $this->error('订单不能撤销！');
        }

        //限定每天只能撤销两次
        $beginToday=strtotime(date('Y-m-d'));
        $chexiao_num = count(Db::name('Mycz')->where(['userid' => userid(),'status' => 4 ,'addtime' =>array('gt' , $beginToday)])->select());
        if ($chexiao_num >= 5){
            $this->error('您当天撤销操作过于频繁，请明天再进行尝试。');
        }

        $rs = Db::name('Mycz')->where(array('id' => $id))->update(array('status' => 4));
        if ($rs) {
             $this->success('操作成功');
        } else {
            $this->error('操作失败！');
        }
    }

    //充值提交
    public function myczUp()
    {
        $type = input('type');
        $num = input('num');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($type, 'n')) {
            $this->error('充值方式格式错误！');
        }

        if (!check($num, 'cny')) {
            $this->error('充值金额格式错误！');
        }

        $myczType = Db::name('MyczType')->where(array('name' => $type))->find();
        if (!$myczType) {
            $this->error('充值方式不存在！');
        }

        if ($myczType['status'] != 1) {
            $this->error('充值方式没有开通！');
        }

        $mycz_min = ($myczType['min'] ? $myczType['min'] : 1);
        $mycz_max = ($myczType['max'] ? $myczType['max'] : 100000);
        if ($num < $mycz_min) {
            $this->error('充值金额不能小于' . $mycz_min . '元！');
        }

        if ($mycz_max < $num) {
            $this->error('充值金额不能大于' . $mycz_max . '元！');
        }

        if ($myczType = Db::name('Mycz')->where(array('userid' => userid(), 'status' => 0))->find()) {
            $this->error('您还有未付款的订单！');
        }

        if (Db::name('Mycz')->where(array('userid' => userid(), 'status' => 3))->find()) {
            $this->error('您还有未处理的订单！');
        }

        for (; true; ) {
            //网银充值生成5位订单号
            if ($type == 'bank'){
                $tradeno = substr(tradeno(),0, 5);
            }else{
                $tradeno = tradeno();
            }

            if (!Db::name('Mycz')->where(array('tradeno' => $tradeno))->find()) {
                break;
            }
        }

        //如果是网银支付，随机选择商家
        $arr = ['userid' => userid(), 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0];
        $new = Db::name('MyczType')->field('id')->where(['name' => $type,'status' => 1])->select();
        $arr['bank_id'] = $new[array_rand($new)]['id'];

        $mycz = Db::name('Mycz')->insert($arr);
        if ($mycz) {
            $this->success('充值订单创建成功！', array('id' => $mycz));
        } else {
            $this->error('提现订单创建失败！');
        }
    }

    public function autoczUp()
    {
     
        $type = input('type');
        $num = input('num');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($type, 'n')) {
            $this->error('充值方式格式错误！');
        }

        if (!check($num, 'cny')) {
            $this->error('充值金额格式错误！');
        }

        $myczType = Db::name('MyczType')->where(array('name' => $type))->find();
        if (!$myczType) {
            $this->error('充值方式不存在！');
        }

        if ($myczType['status'] != 1) {
            $this->error('充值方式没有开通！');
        }

        if ($myczType = Db::name('Mycz')->where(['userid' => userid(), 'status' => 0])->find()) {
            $this->error('您还有未付款的订单！');
        }

        if (Db::name('Mycz')->where(['userid' => userid(), 'status' => 3])->find()) {
            $this->error('您还有未处理的订单！');
        }

        for (; true; ) {
            //网银充值生成5位订单号
            if ($type == 'bank'){
                $tradeno = substr(tradeno(),0, 5);
            }else{
                $tradeno = tradeno();
            }

            if (!Db::name('Mycz')->where(['tradeno' => $tradeno])->find()) {
                break;
            }
        }

        //如果是网银支付，随机选择商家
        $arr = ['userid' => userid(), 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0];

        $mycz = Db::name('Mycz')->insert($arr);

        if ($mycz) {
            if ($type=='alipay'){
                $istype = 1;
            }elseif ($type== 'weixin'){
                $istype = 2;
            }else{
                $istype = 1;
            }

            $return_url = 'http://18.222.123.64/Finance/mycz';
            $notify_url = 'http://18.222.123.64/Finance/notifyCz';
            $orderid = $tradeno;
            $uid = "d4f450bf0e2a350e9a2Db650";
            $token = "2d224e6294c83f977bcd4e53cc9e7c1a";
            $orderuid = "958071682@qq.com";
            $price = $num;
            $goodsname = '充值';
            $key = md5($goodsname. $istype . $notify_url . $orderid . $orderuid . $price . $return_url . $token . $uid);

            $postData['goodsname'] = $goodsname;
            $postData['istype'] = $istype;
            $postData['key'] = $key;
            $postData['notify_url'] = $notify_url;
            $postData['orderid'] = $orderid;
            $postData['orderuid'] =$orderuid;
            $postData['price'] = $price;
            $postData['return_url'] = $return_url;
            $postData['uid'] = $uid;
            $url = 'https://pay.bbbapi.com/?format=json';
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            $data = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($data,true);
            if ($res['data']['qrcode']){
                exit(json_encode(['status'=>1,'data'=>$res]));
            }else{
                $this->error('自动充值连接失败');
            }
        } else {
            $this->error('提现订单创建失败！');
        }
    }

    public function ajaxCzcode()
    {
        if (IS_AJAX){
            $tradeno = input('post.tradeno');
            if ($tradeno){
                $status = Db::name('mycz')->where('tradeno="'.$tradeno.'"')->value('status');
                if ($status == 1){
                    exit(json_encode(['status'=>1,'msg'=>'充值成功!']));
                }else{
                    exit(json_encode(['status'=>0]));
                }
            }else{
                exit(json_encode(['status'=>0]));
            }
        }else{
            exit(json_encode(['status'=>-1]));
        }
    }

    public function notifyCz()
    {
        $path=__DIR__ .'/cz.log';
        $fps = fopen($path,'a+');
        $paysapi_id = $_POST["paysapi_id"];
        $orderid = $_POST["orderid"];
        $price = $_POST["price"];
        $realprice = $_POST["realprice"];
        $orderuid = $_POST["orderuid"];
        $key = $_POST["key"];
        //校验传入的参数是否格式正确，略

        $token = "2d224e6294c83f977bcd4e53cc9e7c1a";

        $temps = md5($orderid . $orderuid . $paysapi_id . $price . $realprice . $token);


        if ($temps == $key){

            $cz = Db::name('mycz')->where('tradeno="'.$orderid.'"')->find();
            if ($cz){
                Db::name('finance')->startTrans();
                $czFlag = Db::name('mycz')->where('tradeno="'.$orderid.'"')->save(['status'=>1,'mum'=>$realprice,'beizhu'=>'自动充值']);
                $userFalg = Db::name('user_coin')->where('userid='.$cz['userid'])->setInc('cny',$realprice);
                if ($czFlag && $userFalg){
                    Db::name('finance')->commit();
                }else{
                    Db::name('finance')->rollback();
                }
            }
        }
    }

    //提现记录
    public function outlog(){
        $status = input('status');
        $uid = userid();
        if (!$uid) {
           $this->redirect('/#login');
        }

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }
        $where['userid'] = $uid;
       
        $list = Db::name('Mytx')->where($where)->order('id desc')->paginate(10,false,[]);
        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['num'] = (Num($v['num']) ? Num($v['num']) : '');
                $data['fee'] = (Num($v['fee']) ? Num($v['fee']) : '') >5 ?(Num($v['fee']) ? Num($v['fee']) : ''):5;
                $data['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
                $list->offsetSet($k,$data);
            }
            $this->assign('status', $status);
            $this->assign('list', $list);
            $this->assign('page', $page);
        }

       return $this->fetch();
    }

    //我的提现
    public function mytx()
    {
        $status = input('status/d', NULL);
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        $moble = Db::name('User')->where(['id' => $uid])->value('moble');

        $this->assign('moble', $moble);
        $user_coin = Db::name('UserCoin')->where(['userid' => $uid])->find();
        $user_coin['cny'] = round($user_coin['cny'], 2);
        $user_coin['cnyd'] = round($user_coin['cnyd'], 2);
        $this->assign('user_coin', $user_coin);
        $userBankList = Db::name('UserBank')->where(['userid' => $uid, 'status' => 1])->order('id desc')->select();
        $this->assign('userBankList', $userBankList);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
        
        $list = Db::name('Mytx')->where($where)->order('id desc')->paginate(10,false,[]);
        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['num'] = (Num($v['num']) ? Num($v['num']) : '');
                $data['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
                $data['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
                $list->offsetSet($k,$data);
            }

            $this->assign('list', $list);
            $this->assign('page', $page);
        }

       return $this->fetch();
    }

    //提现提交
    public function mytxUp()
    {

        $moble_verify = input('moble_verify');
        $num = input('num');
        $paypassword = input('paypassword');
        $type = input('type');
        $myfee = input('myfee');
        $uid = userid();
        if (!$uid) {
            $this->error('请先登录！');
        }

        if (!check($moble_verify, 'd')) {
            $this->error('短信验证码格式错误！');
        }

        if (!check($num, 'd')) {
            $this->error('提现金额格式错误！');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($type, 'd')) {
            $this->error('提现方式格式错误！');
        }

        if ($moble_verify != session('mytx_verify')) {
            $this->error('短信验证码错误！');
        }

        $userCoin = Db::name('UserCoin')->where(['userid' => $uid])->find();
        if ($userCoin['cny'] < $num) {
            $this->error('可用港币余额不足！');
        }

        $user = Db::name('User')->where(['id' => $uid])->find();
        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }

        if ($user['idcardauth'] == 0) {
            $this->error('请先进行身份认证！');
        }

        $userBank = Db::name('UserBank')->where(['id' => $type])->find();
        if (!$userBank) {
            $this->error('提现地址错误！');
        }

        $mytx_min = (config('mytx_min') ? config('mytx_min') : 1);
        $mytx_max = (config('mytx_max') ? config('mytx_max') : 1000000);
        $mytx_bei = config('mytx_bei');

        if($myfee==0){
            $mytx_fee = config('mytx_fee');
        }
        if($myfee==1){
            $mytx_fee =  config('mytx_bei');
        }

        if ($num < $mytx_min) {
            $this->error('每次提现金额不能小于' . $mytx_min . '元！');
        }

        if ($mytx_max < $num) {
            $this->error('每次提现金额不能大于' . $mytx_max . '元！');
        }

        if ($mytx_bei) {
            if ($num % $mytx_bei != 0) {
                $this->error('每次提现金额必须是' . $mytx_bei . '的整倍数！');
            }
        }

        $now = mktime(0, 0, 0, date('m', time()), date('d', time()), date('Y', time()));
        $count = Db::name('mytx')->where(['userid' => $uid, 'addtime' => ['gt', $now], 'status' => ['in', [0,1,3]]])->count();
        if($count >= 2){
            $mytx_fee = $mytx_fee * $count;
        }

        if(round(($num / 100) * $mytx_fee, 2) > 5){
            $fee = round(($num / 100) * $mytx_fee, 2);
            $mum = round(($num / 100) * (100 - $mytx_fee), 2);
        }else{
            $fee = 5;
            $mum = $num - 5;
        }

        $mo = Db::name('');
        $mo->startTrans();
        try{
            $finance =Db::table('weike_finance')->where(['userid' => $uid])->order('id desc')->find();
            $finance_num_user_coin = Db::table('weike_user_coin')->where(['userid' => $uid])->find();
            Db::table('weike_user_coin')->where(['userid' => $uid])->setDec('cny', $num);
            $finance_nameid = Db::table('weike_mytx')->insert([
                'userid' => $uid,
                'num' => $num,
                'fee' => $fee,
                'mum' => $mum,
                'name' => $userBank['name'],
                'truename' => $user['truename'],
                'bank' => $userBank['bank'],
                'bankprov' => $userBank['bankprov'],
                'bankcity' => $userBank['bankcity'],
                'bankaddr' => $userBank['bankaddr'],
                'bankcard' => $userBank['bankcard'],
                'addtime' => time(),
                'status' => 0,
                'urgent' => $myfee
            ]);
            $finance_mum_user_coin =Db::table('weike_user_coin')->where(['userid' => $uid])->find();

            $finance_hash = md5($uid . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
            $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }

            Db::table('weike_finance')->insert([
                'userid' => $uid,
                'coinname' => 'cny',
                'num_a' => $finance_num_user_coin['cny'],
                'num_b' => $finance_num_user_coin['cnyd'],
                'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'],
                'fee' => $num,
                'type' => 2,
                'name' => 'mytx',
                'nameid' => $finance_nameid,
                'remark' => '港币提现-申请提现',
                'mum_a' => $finance_mum_user_coin['cny'],
                'mum_b' => $finance_mum_user_coin['cnyd'],
                'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
                'move' => $finance_hash,
                'addtime' => time(),
                'status' => $finance_status
            ]);
            $flag = true;
            $mo->commit();
        }catch (\Exception $e){
            $flag = false;
            $mo->rollback();
        }


        if ($flag) {
            session('mytx_verify', null);
            $this->success('提现订单创建成功！');
        } else {
            $this->error('提现订单创建失败！');
        }
    }

    //提现撤销
    public function mytxChexiao()
    {
        $id = input('id');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mytx = Db::name('Mytx')->where(['id' => $id])->find();

        if (!$mytx) {
            $this->error('提现订单不存在！');
        }

        if ($mytx['userid'] != userid()) {
            $this->error('非法操作！');
        }

        if ($mytx['status'] != 0) {
            $this->error('订单不能撤销！');
        }

        $mo =  Db::name('');
        $mo->startTrans();
        try{
            $mytx = Db::name('Mytx')->lock(true)->where(['id' => $id])->find();
            $finance = Db::table('weike_finance')->where(['userid' => $mytx['userid']])->order('id desc')->find();
            $finance_num_user_coin = Db::table('weike_user_coin')->where(['userid' => $mytx['userid']])->find();
            Db::table('weike_user_coin')->where(['userid' => $mytx['userid']])->setInc('cny', $mytx['num']);
            Db::table('weike_mytx')->where(['id' => $mytx['id']])->setField('status', 2);
            $finance_mum_user_coin =Db::table('weike_user_coin')->where(['userid' => $mytx['userid']])->find();
            $finance_hash = md5($mytx['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mytx['num'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
            $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

            if ($finance['mum'] < $finance_num) {
                $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
            } else {
                $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
            }
            Db::table('weike_finance')->insert([
                'userid' => $mytx['userid'],
                'coinname' => 'cny',
                'num_a' => $finance_num_user_coin['cny'],
                'num_b' => $finance_num_user_coin['cnyd'],
                'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'],
                'fee' => $mytx['num'],
                'type' => 1,
                'name' => 'mytx',
                'nameid' => $mytx['id'],
                'remark' => '港币提现-撤销提现',
                'mum_a' => $finance_mum_user_coin['cny'],
                'mum_b' => $finance_mum_user_coin['cnyd'],
                'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
                'move' => $finance_hash, 'addtime' => time(),
                'status' => $finance_status
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
    }

    //币种转入
    public function myzr()
    {
        $coin = input('coin');
        $uid = userid();
        if (!$uid) {
          return redirect('/#login');
        }
        // $this->assign('prompt_text', D('Text')->get_content('finance_myzr'));

        if (config('coin')[$coin]) {
            $coin = trim($coin);
        } else {
            $coin = config('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coin = Db::name('Coin')->where(['status' => 1,'type'   => ['neq', 'rmb']])->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }


        $eos = $coin_list['eos'];
        unset($coin_list['eos']);
        $coin_list['eos'] = $eos;
        unset($coin_list['wcg']);
        $this->assign('coin_list', $coin_list);
        $user_coin = Db::name('UserCoin')->where(['userid' => $uid])->find();
        $user_coin[$coin] = round($user_coin[$coin], 6);
        $this->assign('user_coin', $user_coin);
        $Coin = Db::name('Coin')->where(['name' => $coin])->find();
        $this->assign('zr_jz', $Coin['zr_jz']);


        $weike_getCoreConfig = weike_getCoreConfig();
        if(!$weike_getCoreConfig){
            $this->error('核心配置有误');
        }

        $this->assign("weike_opencoin",$weike_getCoreConfig['weike_opencoin']);

        if($weike_getCoreConfig['weike_opencoin'] == 1)
        {
            if (!$Coin['zr_jz']) {
                $qianbao = '当前币种禁止转入！';
            } else {

                $qbdz = $coin . 'b';

                if (!$user_coin[$qbdz]) {
                    if ($Coin['type'] == 'rgb') {

                        if($qbdz == 'wcgb') {
                            $qianbao = "WCG-LF3W-C3AN-B2KR-8PVK6";
                            $tishi = '点击查看华克金充值指南';
                        } else {
                            $qianbao = md5(username() . $coin);
                            $rs = Db::name('UserCoin')->where(['userid' => $uid])->update([$qbdz => $qianbao]);

                            if (!$rs) {
                                $this->error('生成钱包地址出错！');
                            }
                        }
                    }
                    if ($Coin['type'] == 'bit') {
                        $data = myCurl('http://172.66.88.93/mapi/walletadd/generate', ['coin' => $coin, 'username' => username()]);
                        if($data['status'] === 200) {
                            $qianbao = $data['qianbao'];
                            $rs = Db::name('UserCoin')->where(['userid' => $uid])->update([$qbdz => $qianbao]);
                            if (!$rs) {
                                $this->error('钱包地址添加出错！');
                            }
                        } else {
                            $this->error($data['message']);
                        }
                    }
                    //地址生成
                    if ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
                        if ($Coin['type'] == 'token') {
                            if ($user_coin['ethb']) {
                                $data = ['status' => 200, 'message' => '生成钱包地址成功！', 'qianbao' => $user_coin['ethb']];
                            } else {
                                $data = myCurl('http://172.66.88.93/mapi/walletadd/generate', ['coin' => 'eth', 'username' => username()]);
                                $qianbao = $data['qianbao'];
                                Db::name('UserCoin')->where(['userid' => $uid])->update(['ethb' => $qianbao, 'ethp' => md5(username())]);
                                $data = ['status' => 200, 'message' => '生成钱包地址成功！', 'qianbao' => $user_coin['ethb']];
                            }
                        } else {
                            $data = myCurl('http://172.66.88.93/mapi/walletadd/generate', ['coin' => $coin, 'username' => username()]);
                        }
                        if($data['status'] === 200) {
                            $qianbao = $data['qianbao'];
                            $rs = Db::name('UserCoin')->where(['userid' => $uid])->update([$qbdz => $qianbao, $coin.'p' => md5(username())]);
                            if (!$rs) {
                                $this->error('钱包地址添加出错！');
                            }
                        } else {
                            $this->error($data['message']);
                        }
                    }

                    if ($Coin['type'] == 'btm'){
                        $btmzData = Db::name('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
                        if ($btmzData){
                            $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                            $address = $btmClient->createAcountAddress();
                            if ($address){
                                if (Db::name('user_coin')->where('userid='.$uid)->update(['btmzb'=>$address])){
                                    $qianbao = $address;
                                }else{
                                    $this->error('钱包地址添加出错！');
                                }
                            }else{
                                $this->error('钱包地址添加出错！');
                            }
                        }else{
                            $this->error('钱包地址添加出错！');
                        }

                    }
                } else {
                    $qianbao = $user_coin[$coin . 'b'];
                }
            }
        }else{
            if (!$Coin['zr_jz']) {
                $qianbao = '当前币种禁止转入！';
            } else {
                $qianbao = $Coin['weike_coinaddress'];

                $moble = Db::name('User')->where(['id' => $uid])->value('moble');

                if ($moble) {
                    $moble = substr_replace($moble, '****', 3, 4);
                }
                else {
                    redirect(url('/User/moble'));
                    exit();
                }

                $this->assign('moble', $moble);
            }
        }

        $this->assign('qianbao', $qianbao);
        $this->assign('tishi', $tishi);
        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Moble = Db::name('Myzr');
        
        $list = $Moble->where($where)->order('id desc')->paginate(10,false,[]);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
       return $this->fetch();
    }
//榴莲币
    public function mbizr()
    {
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户榴莲币数量
        $user_wcg = DB::name('UserCoin')->where(['userid' => userid()])->value('drt');
        //获取榴莲币金配置
        $wcg_info = DB::name('Coin')->where(['name' => 'drt'])->find();
//        dd($wcg_info);
        if(!$wcg_info['zr_jz']){
            $this->error('当前榴莲币禁止转入');
        }else {
            $moble = DB::name('User')->where(array('id' => userid()))->value('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', url('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        $tradeno = self::get_code();
        //用户转入记录
        $list = DB::name('Myzr')->where(['userid' => userid() , 'coinname' => 'drt'])->order('id desc')->select();
        $this->assign('wcg_info' , $wcg_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        return $this->fetch();
    }
    static function get_code(){
        $tradeno='';
        for ($i = 1; $i <= 8; $i++) {
            $tradeno.=chr(rand(65, 90));
        }
        $data = DB::name('Myzr')->field('tradeno')->where(array('tradeno'=>['gt',0]))->select();
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
    public function upMbi()
    {
        $coin = input('coin/s');
        $weike_dzbz = input('weike_dzbz/s');
        $num = input('num/f');
        $paypassword = input('paypassword/s');
        $moble_verify = input('moble_verify/d');
        $tradeno = input('post.tradeno/s');
        $wcg_qb = input('wcg_qb/s');
        //$tradeid = input('tradeid/s');
        if (!userid()) {
            $this->error('您没有登录请先登录！');
        }

        if (strlen(trim($weike_dzbz)) != 24){
            $this->error('标志地址输入有误');
        }

        //只能保留两位小数
        if (strpos($num,'.') !== false){
            if (strlen($num)-(strpos($num , '.') + 1) >2){
                $this->error('小数点后面只能保留两位小数');
            }
        }
        $num = abs($num);

        if (!check($num, 'currency')) {
            $this->error('数量格式错误！');
        }
        //判断转入地址是否正确
        if ($coin == 'drt') {
            if ($wcg_qb == 'WCG-AMD2-M9LE-4U87-BUAVA') {
                if ($num < 0 || $num >= 500) {
                    $this->error('转入数量错误，请输入0到500之间的数');
                }
            } else if ($wcg_qb == 'WCG-QT5Q-R28U-RRRN-2SPXP') {
                if ($num < 500) {
                    $this->error('转入数量错误，请输入500以上的数量');
                }
            } else {
                $this->error('转入钱包地址错误');
            }
        }


        //判断转入地址是否正确
        if ($coin == 'drt'){
            if ($wcg_qb != 'WCG-AMD2-M9LE-4U87-BUAVA' && $wcg_qb != 'WCG-QT5Q-R28U-RRRN-2SPXP'){
                $this->error('转入错误，请联系客服！');
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!config('coin')[$coin]) {
            $this->error(json_encode(C('coin')['drt']));
            $this->error('币种错误！');
        }

        $Coin = DB::name('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = DB::name('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }


        if( DB::name('Myzr')->where(array('userid' => userid() ,'coinname' => 'drt', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (DB::name('Myzr')->where(array('userid' => userid() ,'coinname' => 'drt', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];

        if ($Coin['type'] == 'rgb') {
            if ($coin == 'drt'){
                DB::name('myzr')->insert(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 , 'tradeno' => $tradeno,'txid'=>md5($tradeno.$time)));
            }else{
                DB::name('myzr')->insert(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }
            return array('status'=>1,'msg'=>'转入申请成功,等待客服处理！');
            //$this->success('转入申请成功,等待客服处理！',url('Finance/mbizr'), 2);

        }else{
            $this->error("钱包币不允许该操作!",url('Finance/mbizr') , 2);
        }
    }
    //c2c设置 --支付宝
    public function c2czfb()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $moble=DB::name('user')->where(array('id'=>userid()))->value('moble');
        $zfb=DB::name('UserBank')->where(array('userid'=>userid(),'paytype'=>2))->find();
        $this->assign('moble',$moble);
        $this->assign('zfb',$zfb);
        return $this->fetch('c2czfb');
    }
    //c2c设置 --微信
    public function c2cWX()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $moble=DB::name('user')->where(array('id'=>userid()))->value('moble');
        $weixin=DB::name('UserBank')->where(array('userid'=>userid(),'paytype'=>1))->find();

        $this->assign('moble',$moble);
        $this->assign('weixin',$weixin);
        return $this->fetch('c2cWX');
    }
    //钱包
    public function qianbao()
    {
        $coin = input('coin');
        if (!userid()) {
            redirect('/#login');
        }

        $Coin = Db::name('Coin')->where(['status' => 1,'type'=> ['neq', 'rmb']])->select();

        if (!$coin) {
            $coin = "";
        }

        $this->assign('xnb', $coin);

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        $where['status'] = 1;
        if(!empty($coin)){
            $where['coinname'] = $coin;
        }
      
    
        $userQianbaoList = Db::name('UserQianbao')->where($where)->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
        $page = $userQianbaoList->render();
        $this->assign('page',$page);
        $this->assign('userQianbaoList', $userQianbaoList);
        return $this->fetch();
    }

    //更新钱包地址
    public function upqianbao()
    {
        $coin = input('coin/s');
        $name = input('name/s');
        $addr = trim(input('addr/s'));
        $paypassword = input('paypassword/s');
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        if (!check($name, 'a')) {
            $this->error('备注名称格式错误！');
        }

        if (!check($addr, 'dw')) {
            $this->error('钱包地址格式错误！');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        $user_paypassword = Db::name('User')->where(['id' => $uid])->value('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!Db::name('Coin')->where(['name' => $coin])->find()) {
            $this->error('币种错误！');
        }

        $userQianbao = Db::name('UserQianbao')->where(['userid' => $uid, 'coinname' => $coin])->select();
        if ($userQianbao){
            foreach ($userQianbao as $k => $v) {
                if ($v['name'] == $name) {
                    $this->error('请不要使用相同的钱包标识！');
                }

                if ($v['addr'] == $addr) {
                    $this->error('钱包地址已存在！');
                }
            }

            if (10 <= count($userQianbao)) {
                $this->error('每个人最多只能添加10个地址！');
            }
        }


        if (Db::name('UserQianbao')->insert(['userid' => $uid, 'name' => $name, 'addr' => $addr, 'coinname' => $coin, 'addtime' => time(), 'status' => 1])) {
            $this->success('添加成功！');
        } else {
            $this->error('添加失败！');
        }
    }

    //删除钱包地址
    public function delqianbao()
    {
        $id = input('id/d');
        $paypassword = input('paypassword/s');
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $user_paypassword = M('User')->where(['id' => $uid])->value('paypassword');
        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!Db::name('UserQianbao')->where(['userid' => $uid, 'id' => $id])->find()) {
            $this->error('非法访问！');
        } else if (Db::name('UserQianbao')->where(['userid' => $uid, 'id' => $id])->delete()) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    //币种转出记录
    public function coinoutLog(){
        $coin = input('coin');
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        if (config('coin')[$coin]) {
            $coin = trim($coin);
        } else {
            $coin = config('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coin = Db::name('Coin')->where(['status' => 1,'type'   => ['neq', 'rmb']])->select();
        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = $uid;
        $where['coinname'] = $coin;
        $Moble = Db::name('Myzc');
   
        $list = $Moble->where($where)->order('id desc')->paginate(15);
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
       return $this->fetch('coinoutLog');
    }

    //币种转出
    public function myzc()
    {
        $coin = input('coin/s');
        $this->assign('coin',$coin);
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }

        // $this->assign('prompt_text', D('Text')->get_content('finance_myzc'));

        if (config('coin')[$coin]) {
            $coin = trim($coin);
        } else {
            $coin = config('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coin =  Db::name('Coin')->where(['status' => 1,'type'   => ['neq', 'rmb']])->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        $this->assign('coin_list', $coin_list);
        $user_coin =  Db::name('UserCoin')->where(['userid' => $uid])->find();
        $user_coin[$coin] = round($user_coin[$coin], 6);
        $this->assign('user_coin', $user_coin);

        if (!$coin_list[$coin]['zc_jz']) {
            $this->assign('zc_jz', '当前币种禁止转出！');
        } else {
            $userQianbaoList =  Db::name('UserQianbao')->where(['userid' => $uid, 'status' => 1, 'coinname' => $coin])->order('id desc')->select();
            $this->assign('userQianbaoList', $userQianbaoList);
            $moble =  Db::name('User')->where(['id' => $uid])->value('moble');

            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } 
            else {
                $this->error('请先认证手机！', url('/Order/index'));
            }

            $this->assign('moble', $moble);
        }

        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Moble = Db::name('Myzc');
        
        $list = $Moble->where($where)->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
        $page = $list->render();
        //获取转出最小数量
        $min_number =  Db::name('Coin')->where(['name' => $coin])->value('zc_min');
        $this->assign('min_number', $min_number);

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //币种转出
    public function upmyzc()
    {
        $coin = input('coin');
        $num = input('num');
        $addr = input('addr');
        $paypassword = input('paypassword');
        $moble_verify = input('moble_verify');
        $wcgkey = input('wcgkey');
        $uid = userid();
        if (!$uid) {
            $this->error('您没有登录请先登录！');
        }

        if (!check($moble_verify, 'd')) {
            $this->error('手机验证码格式错误！');
        }

        if ($moble_verify != session('myzc_verify')) {
            $this->error('手机验证码错误！');
        }

        $num = abs($num);
        if (!check($num, 'currency')) {
            $this->error('数量格式错误！');
        }

        if (!check($addr, 'dw')) {
            $this->error('钱包地址格式错误！');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!config('coin')[$coin]) {
            $this->error('币种错误！');
        }

        $Coin = Db::name('Coin')->where(['name' => $coin])->find();
        if (!$Coin) {
            $this->error('币种错误！');
        }

        $myzc_min = ($Coin['zc_min'] ? abs($Coin['zc_min']) : 0.01);
        $myzc_max = ($Coin['zc_max'] ? abs($Coin['zc_max']) : 10000000);
        if ($num < $myzc_min) {
            $this->error('转出数量超过系统最小限制！');
        }
        if ($myzc_max < $num) {
            $this->error('转出数量超过系统最大限制！');
        }

        $user = Db::name('User')->where(['id' => $uid])->find();
        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }

        if ($user['idcardauth'] == 0) {
            $this->error('请先进行身份认证！');
        }

        $user_coin = Db::name('UserCoin')->where(['userid' => $uid])->find();
        if ($coin == 'btmz'){
            if ($user_coin['btm'] < $num) {
                $this->error('可用余额不足');
            }
        }else{
            if ($user_coin[$coin] < $num) {
                $this->error('可用余额不足');
            }
        }

        //收手续费的地址，找到后进行手续费添加
        $qbdz = $coin . 'b';
        $fee_user = Db::name('UserCoin')->where([$qbdz => $Coin['zc_user']])->find();
        if ($fee_user) {
            debug('手续费地址: ' . $Coin['zc_user'] . ' 存在,有手续费');
            $usercoin=Db::name('coin')->where(['name'=>$coin])->value('zc_fee');

             if($coin=='wc' || $coin=='wcg' || $coin=='oioc' || $coin=='eac' || $coin == 'sie'){
                 $fee = round(($num / 100) * ($usercoin), 8);
             }else{
                 $fee =  $usercoin;
             }

            $mum = round($num - $fee, 8);
            if ($mum < 0) {
                $this->error('转出手续费错误！');
            }

            if ($fee < 0) {
                $this->error('转出手续费设置错误！');
            }
        } else {
            debug('手续费地址: ' . $Coin['zc_user'] . ' 不存在,无手续费');
            $fee = 0;
            $mum = $num;
        }

        if ($Coin['type'] == 'rgb') {
            debug($Coin, '开始认购币转出');

            $mo = Db::name();
            $mo->startTrans();
            try{
                Db::table('weike_user_coin')->where(['userid' => $uid])->setDec($coin, $num);
                if ($fee) {
                    if ( Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->find()) {
                        Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->setInc($coin, $fee);
                        debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                    } else {
                        $rs[] = Db::table('weike_user_coin')->insert([$qbdz => $Coin['zc_user'], $coin => $fee]);
                        debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                    }
                }

                $arr = ['userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0];
                if ($coin === 'wcg' && !empty($wcgkey)) {
                    $arr['wcgkey'] = $wcgkey;
                }
                Db::table('weike_myzc')->insert($arr);
                if ($fee_user) {
                    Db::table('weike_myzc_fee')->insert(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                $flag = true;
                $mo->commit();
            }catch (\Exception $e){
                $flag = false;
                $mo->rollback();
            }


            if ($flag) {
                session('myzc_verify', null);
                $this->success('转账成功！');
            } else {
                $this->error('转账失败!');
            }
        }

        if ($Coin['type'] == 'bit' || $Coin['type'] == 'eth' || $Coin['type'] == 'token') {

            if ( Db::table('weike_user_coin')->where([$qbdz => $addr])->find()) {
                //禁止站内互转！
                $this->error('禁止站内互转!');

                $peer = Db::name('UserCoin')->where([$qbdz => $addr])->find();
                if (!$peer) {
                    $this->error('转出地址不存在！');
                }

                $mo = Db::name('');
                $mo->startTrans();
                try{
                    Db::table('weike_user_coin')->where(['userid' => $uid])->setDec($coin, $num);
                    Db::table('weike_user_coin')->where(['userid' => $peer['userid']])->setInc($coin, $mum);

                    if ($fee) {
                        if ( Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->find()) {
                            Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->setInc($coin, $fee);
                        } else {
                            Db::table('weike_user_coin')->insert([$qbdz => $Coin['zc_user'], $coin => $fee]);
                        }
                    }

                    Db::table('weike_myzc')->insert(['userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1]);
                    Db::table('weike_myzr')->insert(['userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1]);

                    if ($fee_user) {
                        Db::table('weike_myzc_fee')->insert(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1]);
                    }
                    $flag = true;
                    $mo->commit();
                }catch (\Exception $e){
                    $flag = false;
                    $mo->rollback();
                }


                if ($flag) {
                    session('myzc_verify', null);
                    $this->success('转账成功！');
                } else {
                    $this->error('转账失败!');
                }
            } else {
                $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币

                $mo = Db::name('');
                $mo->startTrans();
                try{
                    $r =  Db::table('weike_user_coin')->where(['userid' => $uid])->setDec($coin, $num);
                    $aid =  Db::table('weike_myzc')->insert(['userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status]);

                    if ($fee && $auto_status) {
                        Db::table('weike_myzc_fee')->insert(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1]);

                        if ( Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->find()) {
                            $r =  Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->setInc($coin, $fee);
                            debug(array('res' => $r, 'lastsql' => Db::table('weike_user_coin')->getLastSql()), '新增费用');
                        } else {
                            $r =  Db::table('weike_user_coin')->insert(array($qbdz => $Coin['zc_user'], $coin => $fee));
                        }
                    }

                    $flag = true;
                    $mo->commit();
                }catch (\Exception $e){
                    $flag = false;
                    $mo->rollback();
                }


                if ($flag) {
                    if ($auto_status) {

                        if ($Coin['type'] == 'bit') {
                            $data = myCurl('http://172.66.88.93/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);
                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                $this->error($data['message']);
                            }
                        } elseif ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
                            $data = myCurl('http://172.66.88.93/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);

                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                $this->error($data['message']);
                            }
                        }

                        if ($sendrs) {
                            $flag = 1;
                            $arr = json_decode($sendrs, true);

                            if (isset($arr['status']) && ($arr['status'] == 0)) {
                                $flag = 0;
                            }
                        } else {
                            $flag = 0;
                        }

                        if (!$flag) {
                            $this->error('钱包服务器转出币失败,请手动转出');
                        } else {
                            $this->success('转出成功!');
                        }
                    }

                    if ($auto_status) {
                        session('myzc_verify', null);
                        $this->success('转出成功!');
                    } else {
                        session('myzc_verify', null);
                        $this->success('转出申请成功,请等待审核！');
                    }
                } else {
                    $this->error('转出失败!');
                }
            }
        }

        if ($Coin['type'] == 'btm'){
            $btmzData = Db::name('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
            if ($btmzData){
                $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                $chkAddr = $btmClient->validateAddress($addr);
                if ($chkAddr){
                    if ($chkAddr['valid'] && $chkAddr['is_local']){
                        $this->error('禁止站内互转');
                    }
                }else{
                    $this->error('地址错误');
                }

                $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币

                $mo = Db::name('');
                $mo->startTrans();
                try{
                    $r =  Db::table('weike_user_coin')->where(['userid' => $uid])->setDec('btm', $num);
                    $aid =  Db::table('weike_myzc')->insert(['userid' => $uid, 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status]);

                    if ($fee && $auto_status) {
                        $r =  Db::table('weike_myzc_fee')->insert(['userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1]);

                        if ( Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->find()) {
                            $r =  Db::table('weike_user_coin')->where([$qbdz => $Coin['zc_user']])->setInc('btm', $fee);
                            debug(array('res' => $r, 'lastsql' => Db::table('weike_user_coin')->getLastSql()), '新增费用');
                        } else {
                            $r =  Db::table('weike_user_coin')->insert([$qbdz => $Coin['zc_user'], 'btm' => $fee]);
                        }
                    }
                    $flag = true;
                    $mo->commit();
                }catch (\Exception $e){
                    $flag = false;
                    $mo->rollback();
                }

                if ($flag) {
                    if ($auto_status) {

                        $res = $btmClient->outcome($addr,$mum);
                        if ($res){
                            $sigRes = $btmClient->signTransaction($res);
                            if ($sigRes){
                                $resSub = $btmClient->submitTransaction($sigRes);
                                if ($resSub){
                                    Db::name('myzc')->where(['id' => trim($aid)])->update(['status'=>1,'txid'=>$resSub['tx_id']]);
                                    $flag = true;
                                }else{
                                    Db::name('myzc')->where(['id' => trim($aid)])->update(['status'=>0]);
                                    $flag = false;
                                }
                            }else{
                                Db::name('myzc')->where(['id' => trim($aid)])->update(['status'=>0]);
                                $flag = false;
                            }
                        }else{
                            Db::name('myzc')->where(['id' => trim($aid)])->update(['status'=>0]);
                            $flag = false;
                        }
                    }

                    if ($auto_status) {
                        session('myzc_verify', null);
                        if ($flag){
                            $this->success('转出成功!');
                        }else{
                            $this->error('转出成功，请等待确认');
                        }
                    } else {
                        session('myzc_verify', null);
                        $this->success('转出申请成功,请等待审核！');
                    }
                } else {
                    $this->error('转出失败!');
                }

            }else{
                $this->error('转出失败！');
            }
        }
    }

    //委托管理
    public function mywt()
    {
        $market = input('market');
        $type = input('type');
        $status = input('status');

        if (!userid()) {
           return redirect('/#login');
        }

        // $this->assign('prompt_text', D('Text')->get_content('finance_mywt'));
        check_server();
        $Coin = Db::name('Coin')->where(['status' => 1])->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = Db::name('Market')->where(['status' => 1])->select();

        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!$market_list[$market]) {
            $market = $Market[0]['name'];
        }

        $where['market'] = $market;
        if (($type == 1) || ($type == 2)) {
            $where['type'] = $type;
        }

        if (($status == 1) || ($status == 2) || ($status == 3)) {
            $where['status'] = $status - 1;
        }

        $where['userid'] = userid();
        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('status', $status);
        $Moble = Db::name('Trade');
        
        $list = $Moble->where($where)->order('id desc')->paginate(10,false,[]);
        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['num'] = $v['num'] * 1;
                $data['price'] = $v['price'] * 1;
                $data['deal'] = $v['deal'] * 1;
                $list->offsetSet($k,$data);
            }

            $this->assign('list', $list);
            $this->assign('page', $page);
        }

       return $this->fetch();
    }

    //成交查询
    public function mycj()
    {

        $market = input('market');
        $type = input('type');
        $uid = userid();
        if (!$uid) {
           return redirect('/#login');
        }

        // $this->assign('prompt_text',D('Text')->get_content('finance_mycj'));
        check_server();
        $Coin = Db::name('Coin')->where(['status' => 1])->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = Db::name('Market')->where(['status' => 1])->select();

        foreach ($Market as $k => $v) {
            $v['xnb'] = explode('_', $v['name'])[0];
            $v['rmb'] = explode('_', $v['name'])[1];
            $market_list[$v['name']] = $v;
        }

        $this->assign('market_list', $market_list);

        if (!$market_list[$market]) {
            $market = $Market[0]['name'];
        }

        if ($type == 1) {
            $where = ['userid' => $uid, 'market' => $market];
        } else if ($type == 2) {
            $where = ['peerid' => $uid, 'market' => $market];
        } else {

            $where=[
                 'userid'=> $uid,
                 'peerid' => $uid,
                 'market'=>$market,     
                 
            ];
   
        }

        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('userid', userid());
        $Moble = Db::name('TradeLog');
        
        $list = $Moble->where($where)->order('id desc')->paginate(10,false,[]);
        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['num'] = $v['num'] * 1;
                $data['price'] = $v['price'] * 1;
                $data['mum'] = $v['mum'] * 1;
                $data['fee_buy'] = $v['fee_buy'] * 1;
                $data['fee_sell'] = $v['fee_sell'] * 1;
                $list->offsetSet($k,$data);
            }

            $this->assign('list', $list);
            $this->assign('page', $page);
        }

       return $this->fetch();
    }

    //邀请好友
    public function mytj()
    {
        $uid = userid();
        if (!$uid) {
            redirect('/#login');
        }
        check_server();
        $user = Db::name('User')->where(['id' => $uid])->find();
       
        if (!$user['invit']) {

            for (; true; ) {
                $tradeno = tradenoa();
    
                if (!Db::name('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }
           
            Db::name('User')->where(['id' => $uid])->update(['invit' => $tradeno]);

            $user = Db::name('User')->where(['id' => $uid])->find();
        }

        $this->assign('user', $user);
       return $this->fetch();
    }

    //我的推荐
    public function mywd()
    {
        if (!userid()) {
            redirect('/#login');
        }
        check_server();
        $where['invit_1'] = userid();
        $Model = Db::name('User');


        $list = $Model->where($where)->order('id asc')->field('id,username,moble,addtime,invit_1,idcardauth')->paginate(10,false,[]);
        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['invits'] = Db::name('User')->where(['invit_1' => $v['id']])->order('id asc')->field('id,username,moble,addtime,invit_1,idcardauth')->select();
                $data['invitss'] = count($data['invits']);

                foreach ($data['invits'] as $kk => $vv) {
                    $data['invits'][$kk]['invits'] = Db::name('User')->where(['invit_1' => $vv['id']])->order('id asc')->field('id,username,moble,addtime,invit_1')->select();
                    $data['invits'][$kk]['invitss'] = count($data['invits'][$kk]['invits']);
                }

                $list->offsetSet($k,$data);
            }
            $this->assign('list', $list);
            $this->assign('page', $page);
        }

        return $this->fetch();
    }

    //我的奖励
    public function myjp()
    {
        if (!userid()) {
            redirect('/#login');
        }

        $where['userid'] = userid();
        $Model = Db::name('Invit');
      
        $list = $Model->where($where)->order('id desc')->paginate(10, false,[]);
        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['invit'] = Db::name('User')->where(array('id' => $v['invit']))->value('username');
                $list->offsetSet($k,$data);
            }

            $this->assign('list', $list);
            $this->assign('page', $page);
        }

       return $this->fetch();
    }

    //游戏充值奖励
    public function myaward()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $this->assign('prompt_text', model('Text')->get_content('finance_myjp'));

        $id = $_POST['id'];
        $time = $_POST['time'];
        if($id && $time){
            $time = date('Y-m-d H:i:s',$time + 3600 * 24 * 365);
            $this->error('请在 ' . $time . ' 之后操作！');
        }

        $where['username'] = username();
        $Model = Db::name('UserAward');

        $list = $Model->where($where)->order('id desc')->paginate(10,false,[]);
        if ($list){
            $page = $list->render();
            foreach ($list as $k => $v) {
                $data = $v;
                $data['arrival_time'] = $list[$k]['addtime'] + 3600 * 24 * 365;
                $list->offsetSet($k,$data);
            }

            $this->assign('list', $list);
            $this->assign('page', $page);
        }

        return $this->fetch();
    }

    //认证奖励，充值奖励，分享奖励
    public function myawardifc()
    {
        if (!userid()) {
           return redirect('/#login');
        }

        $id = input('id');
        if($id) {
            $status = Db::name('RegisterAward')->where(['id' => $id])->value('status');
            if ($status == 1){
                $this->error('已经奖励！');
            } else {
                $this->error('请在一月二十五号之后操作！');
            }
        }
        $user = Db::name('User')->where(['id'=>userid()])->field('username')->find();
        $where = "r.status = 1 and r.one = '".$user['username']."' or r.two = '".$user['username']."'";
       
        $list = Db::table('weike_register_award','LEFT')
                 ->alias('r')
                ->join('weike_coin c','c.id = r.coin')
                ->join('weike_admin a','r.admin_id = a.id')
                ->field("r.id,r.one,r.nums,c.title,c.name,a.username,FROM_UNIXTIME(r.add_time) as add_time,(case when r.status = 1  then '已奖励' else '未奖励' end) as award_status,(case when r.type =1 then '认证奖励' when r.type = 2 then '邀请充值奖励' when r.type = 3 then '分享奖励' else '其它' end) as award_type,r.n")
                ->order('r.times desc')
                ->where($where)
                ->paginate(10,false,[]);

         $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    //EJF
    public function myejf()
    {
        if (IS_POST) {
            $id = input('id/d');
            if (!userid()) {
                redirect('/#login');
            }

            if (!check($id, 'd')) {
                $this->error('请选择解冻项！');
            }

            $IssueEjf = Db::name('IssueEjf')->where(array('id' => $id))->find();

            if (!$IssueEjf) {
                $this->error('参数错误！');
            }

            if ($IssueEjf['status']) {
                $this->error('当前解冻已完成！');
            }

            if ($IssueEjf['ci'] <= $IssueEjf['unlock']) {
                $this->error('非法访问！');
            }

            $tm = $IssueEjf['endtime'] + (60 * 60 * $IssueEjf['jian']);
            if (time() < $tm) {
                $this->error('解冻时间还没有到,请在<br>【' . addtime($tm) . '】<br>之后再次操作');
            }

            if ($IssueEjf['userid'] != userid()) {
                $this->error('非法访问');
            }

            $jd_num = round($IssueEjf['num'] / $IssueEjf['ci'], 6);
            $mo = Db::name('finance');
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables weike_user_coin write  , weike_issue_ejf write ');
            $rs = array();
            $rs[] = Db::table('weike_user_coin')->where(array('userid' => userid()))->setInc($IssueEjf['coinname'], $jd_num);
            $rs[] = Db::table('weike_issue_ejf')->where(array('id' => $IssueEjf['id']))->save(array('unlock' => $IssueEjf['unlock'] + 1, 'endtime' => $tm));

            if ($IssueEjf['ci'] <= $IssueEjf['unlock'] + 1) {
                $rs[] = Db::table('weike_issue_ejf')->where(array('id' => $IssueEjf['id']))->save(array('status' => 1));
            }

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                $this->success('解冻成功！');
            } else {
                $mo->execute('rollback');
                $this->error('解冻失败！');
            }
        } else {
            $where = ['userid' => userid()];
            
            $list = Db::name('IssueEjf')->where($where)->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
            $page = $list->render();
            foreach ($list as $k => $v) {
                $list[$k]['shen'] = round((($v['ci'] - $v['unlock']) * $v['num']) / $v['ci'], 6);
                $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            }

            $this->assign('list', $list);
            $this->assign('page', $page);
           return $this->fetch();
        }
    }

    //雷达钱包
    public function ldqb()
    {
        $status = input('status/d', NULL);
        if (!userid()) {
            redirect('/#login');
        }

//        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));
        $moble = Db::name('User')->where(array('id' => userid()))->value('moble');

        if ($moble) {
            $moble = substr_replace($moble, '****', 3, 4);
        } else {
            $this->error('请先认证手机！', U('Home/Order/index'));
        }

        $this->assign('moble', $moble);
        $user_coin = Db::name('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['cny'] = round($user_coin['cny'], 2);
        $user_coin['cnyd'] = round($user_coin['cnyd'], 2);
        $this->assign('user_coin', $user_coin);
        $userBankList = Db::name('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $this->assign('userBankList', $userBankList);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
     
        $list = Db::name('Mytx')->where($where)->order('id desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
        $page = $list->render();
        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }


        //钱包币转入
    public function upmyzr()
    {
        $coin = input('coin');
        $weike_dzbz = input('weike_dzbz');
        $num = input('num/f');
        $paypassword = input('paypassword');
        $moble_verify = input('moble_verify');
        $tradeno = input('post.tradeno/s');
        $wcg_qb = input('wcg_qb');
        //$tradeid = I('tradeid/s');
        if (!userid()) {
            $this->error('您没有登录请先登录！');
        }
//        if (!check($moble_verify, 'd')) {
//            $this->error('验证码格式错误！');
//        }
//
//        if ($moble_verify != session('myzr_verify')) {
//            $this->error('验证码错误！');
//        }

        //华克金标识地址
        if (strlen(trim($weike_dzbz)) != 24){
            $this->error('标志地址输入有误');
        }

        //只能保留两位小数
        if (strpos($num,'.') !== false){
            if (strlen($num)-(strpos($num , '.') + 1) >2){
                $this->error('小数点后面只能保留两位小数');
            }
        }
        $num = abs($num);

        if (!check($num, 'currency')) {
            $this->error('数量格式错误！');
        }
        //判断转入地址是否正确
        if ($coin == 'wcg') {
            if ($wcg_qb == 'WCG-F4NG-3W3T-2B83-3XH2A') {
                if ($num < 0 || $num >= 500) {
                    $this->error('转入数量错误，请输入0到500之间的数');
                }
            } else if ($wcg_qb == 'WCG-5RYW-82TW-7YFQ-25K4Y') {
                if ($num < 500) {
                    $this->error('转入数量错误，请输入500以上的数量');
                }
            } else {
                $this->error('转入钱包地址错误');
            }
        }


        //判断转入地址是否正确
        if ($coin == 'wcg'){
            if ($wcg_qb != 'WCG-F4NG-3W3T-2B83-3XH2A' && $wcg_qb != 'WCG-5RYW-82TW-7YFQ-25K4Y'){
                $this->error('转入错误，请联系客服！');
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!config('coin')[$coin]) {
            $this->error(json_encode(config('coin')['wcg']));
            $this->error('币种错误！');
        }

        $Coin = Db::name('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = Db::name('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }
        

        if( Db::name('Myzr')->where(array('userid' => userid() ,'coinname' => 'wcg', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (Db::name('Myzr')->where(array('userid' => userid() ,'coinname' => 'wcg', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];

        if ($Coin['type'] == 'rgb') {
            if ($coin == 'wcg'){
                Db::name('myzr')->insert(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 , 'tradeno' => $tradeno));
            }else{
                Db::name('myzr')->insert(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }
             return array('status'=>1,'msg'=>'转入申请成功,等待客服处理！');
            // $this->success('转入申请成功,等待客服处理！', '/Activity/wcgzr' , 2);

        }else{
            $this->error("钱包币不允许该操作!", '/Activity/wcgzr' , 2);
        }

    }

    public function fenhong()
    {
        $userid = userid();
        if (!$userid) {
            redirect('/#login');
        }

        $truename = Db::name('user')->where(array('id'=>$userid))->value('truename');
       
        $list = Db::name('fenhong_log')->where(array('userid'=>$userid))->order('addtime desc')->paginate(['type'=> 'bootstrap','var_page' => 'page',]);
        $page = $list->render();
       
        foreach ($list as $k=>$v){
            $list[$k]['mum'] = round($v['mum'],4);
            $list[$k]['truename'] = $truename;
            $list[$k]['fenhong_time'] = date('Y-m-d',$v['addtime']);
        }
        $this->assign('list',$list);
        $this->assign('page',$page);
       return $this->fetch();
    }
    //继承权益
    public function myinherit()
    {

        if (!userid()) {
            redirect('/#login');
        }
        $Inherit=DB::name('Inherit')->where(['userid'=>userid()])->find();
        $moble=DB::name('User')->where(['id'=>userid()])->value('moble');
        $this->assign('inherit',$Inherit);
        $this->assign('moble',$moble);
        return $this->fetch();
    }

    public function getcontent()
    {
        if (!userid()) {
            redirect('/#login');
        }
        if($this->request->isPost()){

            if(!$_POST['name']){
                return array('status'=>0,'msg'=>'请填写姓名');
            }
            if(!$_POST['phone']){
                return array('status'=>0,'msg'=>'请填写手机号');

            }
            $user=DB::name('User')->where(['id'=>userid()])->find();
            if($_POST['name']==$user['truename']){
                return array('status'=>0,'msg'=>'继承人姓名不能跟当前账户姓名相同');
            }

            if($_POST['phone']==$user['username']){
                return array('status'=>0,'msg'=>'继承人手机号不能跟当前账户手机号相同');
            }
            if($_POST['idCard']==$user['idcard']){
                return array('status'=>0,'msg'=>'继承人身份证号码不能跟当前账户身份证号码相同');
            }
            if(!$_POST['idCard']){
                return array('status'=>0,'msg'=>'请填写身份证号码');
            }
            if(!$_POST['msgCode']){
                return array('status'=>0,'msg'=>'请填写短信验证码');
            }
            if($_POST['msgCode']!=session('WeChatcode')){
                return array('status'=>0,'msg'=>'短信验证码不正确');
            }
            $data=array(
                'type' =>$_POST['relations'],
                'userid' =>userid(),
                'username' =>$_POST['name'],
                'moble' =>$_POST['phone'],
                'addtime' =>time(),
                'idcard' =>$_POST['idCard'],
                'status' =>1,
            );
            // dump($_POST['isAdd']);die;
            if(empty($_POST['isAdd'])){
                $Inherit= DB::name('Inherit')->insertGetId($data);
                if($Inherit){
                    return array('status'=>1,'msg'=>'添加成功','userid'=>$Inherit);
                }else{
                    return array('status'=>0,'msg'=>'添加失败');
                }
            }else{
                $Inherit= DB::name('Inherit')->where(['id'=>$_POST['isAdd'],'userid'=>userid()])->update($data);
                if($Inherit){
                    return array('status'=>1,'msg'=>'编辑成功');
                }else{
                    return array('status'=>0,'msg'=>'编辑失败');
                }
            }

        }
    }
}

?>