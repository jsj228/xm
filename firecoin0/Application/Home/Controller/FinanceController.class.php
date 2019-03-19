<?php
namespace Home\Controller;
use Common\Ext\BtmClient;
use Think\Page;

class financeController extends HomeController
{
    //财务中心-我的财产
    public function index()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $CoinList = M('Coin')->where(array('status' => 1))->order('sort desc,id asc')->select();
        $UserCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        $Market = M('Market')->where(array('status' => 1))->select();

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

                if ($Market[C('market_type')[$v['name']]]['new_price']) {
                    $jia = $Market[C('market_type')[$v['name']]]['new_price'];
                } else {
                    $jia = 1;
                }
                //开启市场时才显示对应的币
                if(in_array($v['name'],C('coin_on'))){
                    $coinList[$v['name']] = [
                        'id' => $v['id'],
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
//        dump($coinList);
        $count = M('Coin')->where(array('status' => 1,'name'=>array('in',C('coin_on'))))->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $coinList = array_slice($coinList,$Page->firstRow,$Page->listRows);

        $this->assign('coinList', $coinList);
        $this->assign('page', $show);
        $this->assign('prompt_text', D('Text')->get_content('finance_index'));
        $this->display();
    }
    //	二维数组排序
    function my_sort($arrays,$sort_key,$sort_order=SORT_ASC,$sort_type=SORT_NUMERIC ){
        if(is_array($arrays)){
            foreach ($arrays as $array){
                if(is_array($array)){
                    $key_arrays[] = $array[$sort_key];
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }
        array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
        return $arrays;
    }
    //c2c设置 --微信
    public function c2c_WX()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $moble=M('user')->where(array('id'=>userid()))->getField('moble');
        $weixin=M('UserBank')->where(array('userid'=>userid(),'paytype'=>1))->find();

        $this->assign('moble',$moble);
        $this->assign('weixin',$weixin);

        $this->display();
    }
    //c2c设置 --支付宝
    public function c2c_ZFB()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $moble=M('user')->where(array('id'=>userid()))->getField('moble');
        $zfb=M('UserBank')->where(array('userid'=>userid(),'paytype'=>2))->find();
        $this->assign('moble',$moble);
        $this->assign('zfb',$zfb);
        $this->display();
    }
    //分红中心
    public function fhindex()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $this->assign('prompt_text', D('Text')->get_content('game_fenhong'));
        $coin_list = D('Coin')->get_all_xnb_list_allow();
        foreach ($coin_list as $k => $v) {
            $list[$k]['img'] = D('Coin')->get_img($k);
            $list[$k]['title'] = $v;
            $list[$k]['quanbu'] = D('Coin')->get_sum_coin($k);
            $list[$k]['wodi'] = D('Coin')->get_sum_coin($k, userid());
            $list[$k]['bili'] = round(($list[$k]['wodi'] / $list[$k]['quanbu']) * 100, 2) . '%';
        }
        $this->assign('list', $list);
        $this->display();
    }

    //我的分红
    public function myfhroebx()
    {
        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('game_fenhong_log'));
        $where['userid'] = userid();
        $Model = M('FenhongLog');
        $count = $Model->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //银行
    public function bank(){
        if (!userid()) {
            redirect('/#login');
        }

        $UserBankType = M('UserBankType')->where(array('status' => 1))->order('id desc')->select();
        $this->assign('UserBankType', $UserBankType);

        $user = M('User')->where(array('id' => userid()))->find();
        if($user['idcardauth'] == 0){
            redirect('/user/nameauth');
        }

        $truename = $user['truename'];
        $this->assign('truename', $truename);
        $UserBank = M('UserBank')->where(array('userid' => userid(), 'Paytype' =>0))->order('id desc')->select();

        $this->assign('UserBank', $UserBank);
        $this->assign('prompt_text', D('Text')->get_content('user_bank'));
        $this->display();
    }
    //银行卡开启关闭
    public function savebank(){
        $id=I('userId');
        $vals=I('status');
        if (!userid()) {
            redirect('/#login');
        }
        $userbank=M('UserBank')->where(['userid'=>userid(),'status'=>2])->count();

        if($vals==1){
            if($userbank<=1){
                $this->ajaxreturn(['status'=>0,'msg'=>'收款方式至少开启一个！']);
            }
            $count = M('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();
            if ($count >= 1 && userid()!=5392) {
                $this->ajaxreturn(['status'=>0,'msg'=>'订单匹配中不可关闭!']);
            }
        }

        if (!$id) {
            $this->ajaxreturn(['status'=>0,'msg'=>'银行卡不存在!']);
        }
        $bank=M('user_bank')->where(array('userid' => userid(), 'id' => $id))->find();
        if (!$bank) {
            $this->ajaxreturn(['status'=>0,'msg'=>'非法访问！']);
        }

        if($vals==2){
            $bank=M('UserBank')->where(array('userid'=>userid(),'Paytype'=>0,'status'=>2))->count();
            if($bank>=1){
                $this->ajaxreturn(['status'=>0,'msg'=>'只能开启一张网银！']);
            }
        }

        if ($vals==1 || $vals==2) {
            $data=array(
                'status'=>$vals,
            );
            M('user_bank')->where(array('userid' => userid(), 'id' => $id))->save($data);
        }
    }
    //添加银行
    public function upbank()
    {
        $name = I('name/s');
        $bank = I('bank/s');
        $bankprov = I('bankprov/s');
        $bankcity = I('bankcity/s');
        $bankaddr = I('bankaddr/s');
        $bankcard = I('bankcard/s');
        $paypassword = I('paypassword/s');

        if (!userid()) {
            redirect('/#login');
        }

        if (!check($name, 'a')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'备注名称格式错误'));
        }

        if (!check($bank, 'a')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'开户银行格式错误'));

        }

        if (!check($bankprov, 'c')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'开户省市格式错误'));

        }

        if (!check($bankcity, 'c')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'开户省市格式错误2'));

        }

        if (!check($bankaddr, 'a')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'开户行地址格式错误'));

        }

        if (!check($bankcard, 'd')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'银行账号格式错误'));

        }

        if(strlen($bankcard) < 16 || strlen($bankcard) > 19){
            $this->ajaxReturn(array('status'=>0,'msg'=>'银行账号格式错误'));

        }

        if (!check($paypassword, 'password')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'交易密码格式错误'));
        }

        $user_paypassword = M('User')->where(array('id' => userid()))->getField('paypassword');
        if (md5($paypassword) != $user_paypassword) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'交易密码错误'));

        }

        if (!M('UserBankType')->where(array('title' => $bank))->find()) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'开户银行错误'));

        }

        $userBank = M('UserBank')->where(array('userid' => userid(),'Paytype'=>0))->select();
        foreach ($userBank as $k => $v) {
            if ($v['name'] == $name) {
                $this->ajaxReturn(array('status'=>0,'msg'=>'请不要使用相同的备注名称'));

            }

            if ($v['bankcard'] == $bankcard) {
                $this->ajaxReturn(array('status'=>0,'msg'=>'银行卡号已存在'));
            }
        }
        $Bank = M('UserBank')->where(array('userid' => userid(),'Paytype'=>0))->select();
        if (5 <= count($Bank)) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'每个用户最多只能添加5帐银行卡'));

        }
        /*暂无编辑*/
        if(!$id){
            $userbank=M('UserBank')->where(['userid'=>userid(),'Paytype'=>0,'status'=>2])->select();
            if ($userbank) {
                M('UserBank')->add(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 1,'Paytype'=>0));
                $this->ajaxReturn(array('status'=>1,'msg'=>'银行添加成功'));

            } elseif(!$userbank) {
                M('UserBank')->add(array('userid' => userid(), 'name' => $name, 'bank' => $bank, 'bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time(), 'status' => 2,'Paytype'=>0));
                $this->ajaxReturn(array('status'=>1,'msg'=>'银行添加成功'));

            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'银行添加失败'));
            }
        }else{
            $count = M('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1) {
                $this->ajaxreturn(['status'=>0,'msg'=>'订单匹配中不可修改!']);
            }
            if (M('UserBank')->where(array('id'=>$id,'userid' => userid()))->save(array('bankprov' => $bankprov, 'bankcity' => $bankcity, 'bankaddr' => $bankaddr, 'bankcard' => $bankcard, 'addtime' => time()))) {
                $this->ajaxReturn(array('status'=>1,'msg'=>'编辑成功'));
            } else {
                $this->ajaxReturn(array('status'=>0,'msg'=>'编辑失败'));
            }
        }
    }

    //删除银行
    public function delbank()
    {
        $id = I('id/d');
        $paypassword = I('paypassword/s');
        if (!userid()) {
            redirect('/#login');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $user_paypassword = M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!M('UserBank')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error('非法访问！');
        } else if (M('UserBank')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }
    //添加微信
    public  function AddWeChat(){
        if(!userid()){
            redirect('/#login');
        }
        $id=I('wId');
        $type=1;//类型
        $fileName=I('fileName');//微信二维码
        $WeChatName=I('wxNumber');//微信号
        $phoneMsg=I('phoneMsg');//验证码
        $userPWD=I('userPWD');//交易密码

        if(!M('user')->where(array('id'=>userid(),'paypassword'=>md5($userPWD)))->find()){
            $this->ajaxReturn(array('status'=>0,'msg'=>'交易密码错误'));
        }
        if ($phoneMsg != session('WeChatcode')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'手机验证码错误'));
        }
        $name=M('user')->where(array('id'=>userid()))->getField('truename');
        if(!$id){
            if(M('user_bank')->where(array('userid'=>userid(),'paytype'=>1))->find()){
                $this->ajaxReturn(array('status'=>0,'msg'=>'微信账号已存在'));
            }
            $res=M('user_bank')->add(array('name'=>$name,'bank'=>$WeChatName,'userid' => userid(), 'addtime' => time(), 'status' => 2,'Paytype'=>$type,'img'=>$fileName));
            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'微信账号添加成功'));

            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'微信账号添加失败'));
            }
        }else{
            $count = M('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1 && userid()!=5392) {
                $this->ajaxreturn(['status'=>0,'msg'=>'订单匹配中不可修改!']);
            }

            $res=M('user_bank')->where(array('id'=>$id,'userid'=>userid()))->save(array('bank'=>$WeChatName,'img'=>$fileName));

            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'编辑成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'编辑失败'));
            }
        }
    }
    //添加支付宝
    public  function AddAlipay(){
        if(!userid()){
            redirect('/#login');
        }
        $id=I('wId');//类型
        // dump( $id);die;
        $type=2;//类型
        $Alipay=I('zfbNumber');//支付宝号
        $img=I('fileName');//二维码图片
        $Alipaycode=I('phoneMsg');//验证码
        $userPWD=I('userPWD');//交易密码
        if ($Alipaycode != session('WeChatcode')) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'手机验证码错误！'));
            // $this->error('手机验证码错误！');
        }

        if (!M('user')->where(array('id'=>userid(),'paypassword'=>md5($userPWD)))->find()) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'交易密码错误！'));
            // $this->error('交易密码错误！');
        }
        if(!$id){
            $name=M('user')->where(array('id'=>userid()))->getField('truename');
            if(M('UserBank')->where(array('userid'=>userid(),'paytype'=>2))->find()){
                $this->ajaxReturn(array('status'=>0,'msg'=>'支付宝账号已存在！'));
                // $this->error('支付宝账号已存在！');
            }
            $res=M('UserBank')->add(array('userid' => userid(),'name'=>$name, 'bank' => $Alipay, 'addtime' => time(), 'status' => 2,'Paytype'=>$type,'img'=>$img));
            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'支付宝账号添加成功！'));
                // $this->success('微信账号添加成功！');
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'支付宝账号添加成功！'));
                // $this->error('微信账号添加失败！');
            }
        }else{
            $count = M('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1 && userid()!=5392) {
                $this->ajaxreturn(['status'=>0,'msg'=>'订单匹配中不可修改!']);
            }
            $res=M('UserBank')->where(array('id'=>$id,'userid'=>userid()))->save(array('bank' => $Alipay,'img'=>$img));
            if($res){
                $this->ajaxReturn(array('status'=>1,'msg'=>'编辑成功！'));
                // $this->success('编辑成功！');
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'编辑失败！'));
                // $this->error('编辑失败！');
            }
        }
    }
    //微信支付宝开启关闭
    public function savemy(){
        $id=I('userId');
        $vals=I('status');
        if (!userid()) {
            redirect('/#login');
        }
        if (!$id && $vals==1) {
            $this->ajaxreturn(['status'=>0,'msg'=>'关闭绑定不存在!']);
        }
        if (!$id && $vals==2) {
            $this->ajaxreturn(['status'=>0,'msg'=>'开启绑定不存在!']);
        }
        $userbank=M('UserBank')->where(['userid'=>userid(),'status'=>2])->count();
        if($vals==1){
            if($userbank<=1){
                $this->ajaxreturn(['status'=>0,'msg'=>'收款方式至少开启一个！']);
            }
            $count = M('UserC2cTrade')->where(['userid' => userid(), 'type' =>array('in','1,2'), 'status' => array('in',[0,3]),'businessid'=>array('neq',0)])->count();

            if ($count >= 1 && userid()!=5392) {
                $this->ajaxreturn(['status'=>0,'msg'=>'订单匹配中不可关闭!']);
            }
        }
        $bank=M('user_bank')->where(array('userid' => userid(), 'id' => $id))->find();
        if (!$bank) {
            $this->ajaxreturn(['status'=>0,'msg'=>'非法访问！']);
        }
        if ($vals==1 || $vals==2) {
            $data=array(
                'status'=>$vals,
            );
            M('user_bank')->where(array('id' => $id))->save($data);
        }
    }
    //cny充值
    public function mycz()
    {
        $status = I('status/d', NULL);
        if (!userid()) {
            redirect('/#login');
        }
        $myczs=M('tx_cz')->getField('cz');
        $this->assign('myczs',$myczs);

        $this->assign('prompt_text', D('Text')->get_content('finance_mycz'));
        $myczType = M('MyczType')->where(array('status' => 1))->select();

        foreach ($myczType as $k => $v) {
            $myczTypeList[$v['name']] = $v['title'];
        }

//        $this->assign('myczTypeList', $myczTypeList);
        $alipaymycz = M('MyczType')->where(['status' => 1 , 'name' => 'alipay'])->find();
        $weixinmycz = M('MyczType')->where(['status' => 1 , 'name' => 'weixin'])->find();
        $bankmycz = M('MyczType')->where(['status' => 1 , 'name' => 'bank'])->find();
        $this->assign('alipaymycz', $alipaymycz);
        $this->assign('weixinmycz', $weixinmycz);
        $this->assign('bankmycz', $bankmycz);
        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['cny'] = round($user_coin['cny'], 2);
        $user_coin['cnyd'] = round($user_coin['cnyd'], 2);
        $this->assign('user_coin', $user_coin);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4) || ($status == 5) || ($status == 6)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
        $count = M('Mycz')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Mycz')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        foreach ($list as $k => $v) {
            $list[$k]['type'] = M('MyczType')->where(array('name' => $v['type']))->getField('title');
            $list[$k]['typeEn'] = $v['type'];
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        if (M('config')->getField('autocz')){
            $this->display('autocz');
        }else{
            $this->display();
        }
    }

    //充值汇款
    public function myczHuikuan()
    {
        $id = I('id/d', NULL);
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mycz = M('Mycz')->where(array('id' => $id))->find();
        if (!$mycz) {
            $this->error('充值订单不存在！');
        }

        if ($mycz['userid'] != userid()) {
            $this->error('非法操作！');
        }

        if ($mycz['status'] != 0) {
            $this->error('订单已经处理过！');
        }

        $rs = M('Mycz')->where(array('id' => $id))->save(array('status' => 3));
        if ($rs) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败！');
        }
    }

    //充值撤销
    public function myczChexiao()
    {
        $id = I('id/d', NULL);
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mycz = M('Mycz')->where(array('id' => $id))->find();
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
        $chexiao_num = count(M('Mycz')->where(['userid' => userid(),'status' => 4 ,'addtime' =>array('gt' , $beginToday)])->select());
        if ($chexiao_num >= 5){
            $this->error('您当天撤销操作过于频繁，请明天再进行尝试。');
        }

        $rs = M('Mycz')->where(array('id' => $id))->save(array('status' => 4));
        if ($rs) {
            $this->success('操作成功', array('id' => $id));
        } else {
            $this->error('操作失败！');
        }
    }

    //充值提交
    public function myczUp()
    {
        $type = I('type/s');
        $num = I('num/s');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($type, 'n')) {
            $this->error('充值方式格式错误！');
        }
        if(!is_numeric($num)||strpos($num,".")!==false){
            $this->error('价格只能是正整数');
        }
//        if (!check($num, 'cny')) {
//            $this->error('充值金额格式错误！');
//        }
        $myczType = M('MyczType')->where(array('name' => $type,'status'=>1))->find();
        if (!$myczType) {
            $this->error('充值方式不存在！');
        }
        if ($myczType['status']!= 1) {
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

        if ($myczType = M('Mycz')->where(array('userid' => userid(), 'status' => 0))->find()) {
            $this->error('您还有未付款的订单！');
        }

        if (M('Mycz')->where(array('userid' => userid(), 'status' => 3))->find()) {
            $this->error('您还有未处理的订单！');
        }

        for (; true; ) {
            //网银充值生成5位订单号
            if ($type == 'bank'){
                $tradeno = substr(tradeno(),0, 5);
            }else{
                $tradeno = tradeno();
            }

            if (!M('Mycz')->where(array('tradeno' => $tradeno))->find()) {
                break;
            }
        }

        //如果是网银支付，随机选择商家
        $arr = ['userid' => userid(), 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0];
        $new = M('MyczType')->field('id')->where(['name' => $type,'status' => 1])->select();
        $arr['bank_id'] = $new[array_rand($new)]['id'];

        $mycz = M('Mycz')->add($arr);
        if ($mycz) {
            $this->success('充值订单创建成功！', array('id' => $mycz));
        } else {
            $this->error('提现订单创建失败！');
        }
    }

    public function autoczUp()
    {
        $type = I('type/s');
        $num = I('num/s');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($type, 'n')) {
            $this->error('充值方式格式错误！');
        }

        if (!check($num, 'cny')) {
            $this->error('充值金额格式错误！');
        }

        $myczType = M('MyczType')->where(array('name' => $type))->find();
        if (!$myczType) {
            $this->error('充值方式不存在！');
        }

        if ($myczType['status'] != 1) {
            $this->error('充值方式没有开通！');
        }

        /*$mycz_min = ($myczType['min'] ? $myczType['min'] : 1);
        $mycz_max = ($myczType['max'] ? $myczType['max'] : 100000);
        if ($num < $mycz_min) {
            $this->error('充值金额不能小于' . $mycz_min . '元！');
        }

        if ($mycz_max < $num) {
            $this->error('充值金额不能大于' . $mycz_max . '元！');
        }*/

        if ($myczType = M('Mycz')->where(array('userid' => userid(), 'status' => 0))->find()) {
            $this->error('您还有未付款的订单！');
        }

        if (M('Mycz')->where(array('userid' => userid(), 'status' => 3))->find()) {
            $this->error('您还有未处理的订单！');
        }

        for (; true; ) {
            //网银充值生成5位订单号
            if ($type == 'bank'){
                $tradeno = substr(tradeno(),0, 5);
            }else{
                $tradeno = tradeno();
            }

            if (!M('Mycz')->where(array('tradeno' => $tradeno))->find()) {
                break;
            }
        }

        //如果是网银支付，随机选择商家
        $arr = ['userid' => userid(), 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0];

        $mycz = M('Mycz')->add($arr);
        if ($mycz) {
            if ($type=='alipay'){
                $istype = 1;
            }elseif ($type== 'weixin'){
                $istype = 2;
            }else{
                $istype = 1;
            }

            $return_url = 'http://18.188.167.238/Finance/mycz';
            $notify_url = 'http://18.188.167.238/Finance/notifyCz';

            $orderid = $tradeno;
            $uid = "80f659fe4b2108d40f71f378";
            $token = "ace31a9cd4d738fd84abeb4036a7b9e5";
            $orderuid = "601527837@qq.com";
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
            $tradeno = I('post.tradeno');
            if ($tradeno){
                $status = M('mycz')->where('tradeno="'.$tradeno.'"')->getField('status');
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
        $file = __DIR__ . '/../../../autocz.log';
        $paysapi_id = $_POST["paysapi_id"];
        $orderid = $_POST["orderid"];
        $price = $_POST["price"];
        $realprice = $_POST["realprice"];
        $orderuid = $_POST["orderuid"];
        $key = $_POST["key"];
        //校验传入的参数是否格式正确，略

        $token = "ace31a9cd4d738fd84abeb4036a7b9e5";

        $temps = md5($orderid . $orderuid . $paysapi_id . $price . $realprice . $token);

        if ($temps == $key){

            $cz = M('mycz')->where('tradeno="'.$orderid.'"')->find();
            if ($cz){
                M()->startTrans();
                try{
                    M('mycz')->where('tradeno="'.$orderid.'"')->save(['status'=>1,'mum'=>$realprice,'beizhu'=>'自动充值']);
                    M('user_coin')->where('userid='.$cz['userid'])->setInc('cny',$realprice);
                    M()->commit();
                }catch (\Exception $e){
                    M()->rollback();
                }
            }
        }
    }

    //提现记录
    public function outlog(){
        $status = I('status/d', NULL);
        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }
        $where['userid'] = userid();
        $count = M('Mytx')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '') >5 ?(Num($v['fee']) ? Num($v['fee']) : ''):5;
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }
        $this->assign('status', $status);
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //我的提现
    public function mytx()
    {
        $status = I('status/d', NULL);
        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));
        $moble = M('User')->where(array('id' => userid()))->getField('moble');

        if ($moble) {
            $moble = substr_replace($moble, '****', 3, 4);
        } else {
            $this->error('请先认证手机！', U('Home/Order/index'));
        }

        $this->assign('moble', $moble);
        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['cny'] = round($user_coin['cny'], 2);
        $user_coin['cnyd'] = round($user_coin['cnyd'], 2);
        $this->assign('user_coin', $user_coin);
        $userBankList = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $this->assign('userBankList', $userBankList);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
        $count = M('Mytx')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //提现提交
    public function mytxUp()
    {
        $mytxup=M('tx_cz')->getField('tx');
        if($mytxup==0){
            $this->error('请前往C2C进行提现');
        }

        $moble_verify = I('moble_verify/d');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $type = I('type/d');
        $myfee = I('myfee/d');

        if (!userid()) {
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

        $userCoin = M('UserCoin')->where(array('userid' => userid()))->find();
        if ($userCoin['cny'] < $num) {
            $this->error('可用港币余额不足！');
        }

        $user = M('User')->where(array('id' => userid()))->find();
        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }

        if ($user['idcardauth'] == 0) {
            $this->error('请先进行身份认证！');
        }

        $userBank = M('UserBank')->where(array('id' => $type))->find();
        if (!$userBank) {
            $this->error('提现地址错误！');
        }

        $mytx_min = (C('mytx_min') ? C('mytx_min') : 1);
        $mytx_max = (C('mytx_max') ? C('mytx_max') : 1000000);
        $mytx_bei = C('mytx_bei');

        if($myfee==0){
            $mytx_fee = C('mytx_fee');
        }
        if($myfee==1){
            $mytx_fee =  C('mytx_bei');
        }
        /*******/
//        $now = mktime(0, 0, 0, date('m', time()), date('d', time()), date('Y', time()));
//        $count = M('mytx')->where(['userid' => userid(), 'addtime' => ['gt', $now], 'status' => ['between', [0,1]],'urgent'=>0])->count();
//        if($myfee==0 && $count < 2){
//            $mytx_fee = C('mytx_fee');
//        }elseif($myfee==0 && $count >= 2){
//            $mytx_fee =  C('mytx_fee')+0.5;
//            $mytx_fee = $mytx_fee*$count;
//        }
//
//        $count1 = M('mytx')->where(['userid' => userid(), 'addtime' => ['gt', $now], 'status' => ['between', [0,1]],'urgent'=>1])->order('id desc')->getField('fee');
//
//        if($myfee==1 && $count1 < 2){
//            $mytx_fee =  C('mytx_fee')+0.5;
//        }elseif($myfee==1 && $count1 >= 2){
//            $mytx_fee =  C('mytx_fee')+1.5;
//
//            $mytx_fee = $mytx_fee*$count1;
//
//        }
        /**/
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
        $end = mktime(23, 59, 59, date('m', time()), date('d', time()), date('Y', time()));
        $counttx = M('mytx')->where(['userid' => userid(), 'addtime' => ['gt', $now], 'status' => ['in', [0,1,3]]])->count();
        $countc2c = M('user_c2c_trade')->where('userid='.userid().' and type=2 and status!=2 and addtime between '.$now.' and '.$end)->count()
            + M('user_c2c_trade')->where('userid='.userid().' and type=2 and status=2 and is_sell=1 and addtime between '.$now.' and '.$end)->count();
        $count = $counttx+$countc2c;
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

//        $fee = round(($num / 100) * $mytx_fee, 2) > 5 ?round(($num / 100) * $mytx_fee, 2): 5;
//        $mum = round(($num / 100) * (100 - $mytx_fee), 2);
        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_mytx write , weike_user_coin write ,weike_finance write');
        $rs = array();
        $finance = $mo->table('weike_finance')->where(array('userid' => userid()))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
        $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec('cny', $num);
        $rs[] = $finance_nameid = $mo->table('weike_mytx')->add(array('userid' => userid(), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'name' => $userBank['name'], 'truename' => $user['truename'], 'bank' => $userBank['bank'], 'bankprov' => $userBank['bankprov'], 'bankcity' => $userBank['bankcity'], 'bankaddr' => $userBank['bankaddr'], 'bankcard' => $userBank['bankcard'], 'addtime' => time(), 'status' => 0,'urgent' => $myfee));
        $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
        $finance_hash = md5(userid() . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
        $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }

        $rs[] = $mo->table('weike_finance')->add(array('userid' => userid(), 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $num, 'type' => 2, 'name' => 'mytx', 'nameid' => $finance_nameid, 'remark' => '港币提现-申请提现', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        if (check_arr($rs)) {
            session('mytx_verify', null);
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->success('提现订单创建成功！');
        } else {
            $mo->execute('rollback');
            $this->error('提现订单创建失败！');
        }
    }

    //提现撤销
    public function mytxChexiao()
    {
        $id = I('id/d');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $mytx = M('Mytx')->where(array('id' => $id))->find();

        if (!$mytx) {
            $this->error('提现订单不存在！');
        }

        if ($mytx['userid'] != userid()) {
            $this->error('非法操作！');
        }

        if ($mytx['status'] != 0) {
            $this->error('订单不能撤销！');
        }

        $mo = M();
        $mo->execute('set autocommit=0');
        $mo->execute('lock tables weike_user_coin write,weike_mytx write,weike_finance write');
        $rs = array();
        $finance = $mo->table('weike_finance')->where(array('userid' => $mytx['userid']))->order('id desc')->find();
        $finance_num_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mytx['userid']))->find();
        $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $mytx['userid']))->setInc('cny', $mytx['num']);
        $rs[] = $mo->table('weike_mytx')->where(array('id' => $mytx['id']))->setField('status', 2);
        $finance_mum_user_coin = $mo->table('weike_user_coin')->where(array('userid' => $mytx['userid']))->find();
        $finance_hash = md5($mytx['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mytx['num'] . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
        $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

        if ($finance['mum'] < $finance_num) {
            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
        } else {
            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
        }
        $rs[] = $mo->table('weike_finance')->add(array('userid' => $mytx['userid'], 'coinname' => 'cny', 'num_a' => $finance_num_user_coin['cny'], 'num_b' => $finance_num_user_coin['cnyd'], 'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'], 'fee' => $mytx['num'], 'type' => 1, 'name' => 'mytx', 'nameid' => $mytx['id'], 'remark' => '港币提现-撤销提现', 'mum_a' => $finance_mum_user_coin['cny'], 'mum_b' => $finance_mum_user_coin['cnyd'], 'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

        if (check_arr($rs)) {
            $mo->execute('commit');
            $mo->execute('unlock tables');
            $this->success('操作成功！');
        } else {
            $mo->execute('rollback');
            $this->error('操作失败！');
        }
    }

    //币种转入
    public function myzr()
    {
        $coin = I('coin/s', NULL);
        if (!userid()) {
            redirect('/#login');
        }
        $this->assign('prompt_text', D('Text')->get_content('finance_myzr'));

        if (C('coin')[$coin]) {
            $coin = trim($coin);
        } else {
            $coin = C('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coin = M('Coin')->where(array(
            'status' => 1,
            'type'   => array('neq', 'rmb')
        ))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }


        $eos = $coin_list['eos'];
        unset($coin_list['eos']);
        $coin_list['eos'] = $eos;
        unset($coin_list['wcg']);
        $this->assign('coin_list', $coin_list);
        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin[$coin] = round($user_coin[$coin], 6);
        $this->assign('user_coin', $user_coin);
        $Coin = M('Coin')->where(array('name' => $coin))->find();
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
                            $rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));

                            if (!$rs) {
                                $this->error('生成钱包地址出错！');
                            }
                        }
                    }
                    if ($Coin['type'] == 'bit') {
                        $data = myCurl('http://172.66.66.32/mapi/walletadd/generate', ['coin' => $coin, 'username' => username()]);
//                         dump($data);die;
                        if($data['status'] === 200) {
                            $qianbao = $data['qianbao'];
                            $rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao));
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
                                $data = myCurl('http://172.66.66.32/mapi/walletadd/generate', ['coin' => 'eth', 'username' => username()]);
                                $qianbao = $data['qianbao'];
                                M('UserCoin')->where(array('userid' => userid()))->save(array('ethb' => $qianbao, 'ethp' => md5(username())));
                                $data = ['status' => 200, 'message' => '生成钱包地址成功！', 'qianbao' => $user_coin['ethb']];
                            }
                        } else {
                            $data = myCurl('http://172.66.66.32/mapi/walletadd/generate', ['coin' => $coin, 'username' => username()]);
                        }
                        if($data['status'] === 200) {
                            $qianbao = $data['qianbao'];
                            $rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao, $coin.'p' => md5(username())));
                            if (!$rs) {
                                $this->error('钱包地址添加出错！');
                            }
                        } else {
                            $this->error($data['message']);
                        }
                    }
                    //eos地址生成
                    if($Coin['type'] == 'eos'){
                        $qianbao = $Coin['dj_yh'];
                        $mome = '';
                        for (; true;) {
                            $mome = substr(md5(userid().time()),0,16);
                            if (!M('UserCoin')->where(array($coin . 'p' => $mome))->find()) {
                                break;
                            }
                        }
                        $rs = M('UserCoin')->where(array('userid' => userid()))->save(array($qbdz => $qianbao, $coin . 'p' => $mome));
                        $this->assign('mome', $mome);
                        if (!$rs) {
                            $this->error('钱包地址添加出错！');
                        }
                    }
                    if ($Coin['type'] == 'btm'){
                        $btmzData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
                        if ($btmzData){
                            $btmClient = new BtmClient($btmzData['dj_zj'], $btmzData['dj_dk'], reset(explode('-', $btmzData['dj_yh'])), $btmzData['dj_mm'], end(explode('-', $btmzData['dj_yh'])),$btmzData['token_address']);
                            $address = $btmClient->createAcountAddress();
                            if ($address){
                                if (M('user_coin')->where('userid='.userid())->save(['btmzb'=>$address])){
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

                    if($Coin['type'] == 'xrp'){
                        $qianbao = $Coin['dj_yh'];

                        $xrpb = M('UserCoin')->where(array('userid' => userid()))->getField('xrpb');
                        if (!$xrpb){
                            $xrp_len = 9-strlen(userid());
                            $min = pow(10 , ($xrp_len - 1));
                            $max = pow(10, $xrp_len) - 1;
                            $xrp_str =  mt_rand($min, $max);
                            $xrpb = userid().$xrp_str;
                            $rs = M('UserCoin')->where(array('userid' => userid()))->save([$coin . 'b' => $xrpb]);
                            if (!$rs) {
                                $this->error('钱包地址添加出错！');
                            }
                        }
                        $this->assign('tag', $xrpb);

                    }
                } else {
                    $qianbao = $user_coin[$coin . 'b'];
                    if ($Coin['type'] == 'eos'){
                        $mome = $user_coin[$coin . 'p'];
                        $this->assign('mome', $mome);
                    }

                    if ($Coin['type'] == 'xrp'){
                        $qianbao = $Coin['dj_yh'];
                        $tag = $user_coin[$coin . 'b'];
                        $this->assign('tag', $tag);
                    }
                }
            }
        }else{
            if (!$Coin['zr_jz']) {
                $qianbao = '当前币种禁止转入！';
            } else {
                $qianbao = $Coin['weike_coinaddress'];

                $moble = M('User')->where(array('id' => userid()))->getField('moble');

                if ($moble) {
                    $moble = substr_replace($moble, '****', 3, 4);
                }
                else {
                    redirect(U('Home/User/moble'));
                    exit();
                }

                $this->assign('moble', $moble);
            }
        }

        $this->assign('qianbao', $qianbao);
        $this->assign('tishi', $tishi);
        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Moble = M('Myzr');
        $count = $Moble->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //钱包
    public function qianbao()
    {
        $coin = I('coin/s', NULL);
        if (!userid()) {
            redirect('/#login');
        }

        $Coin = M('Coin')->where(array(
            'status' => 1,
            'type'   => array('neq', 'rmb')
        ))->select();

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

        $count = M('UserQianbao')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $userQianbaoList = M('UserQianbao')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $moble=M('user')->where(['id'=>userid()])->getField('moble');
        $this->assign('page',$show);
        $this->assign('moble',$moble);
        $this->assign('userQianbaoList', $userQianbaoList);
        $this->assign('prompt_text', D('Text')->get_content('user_qianbao'));
        $this->display();
    }

    //更新钱包地址
    public function upqianbao()
    {
        $coin = I('coin/s');
        $name = I('name/s');
        $memo = I('memo/s');
        $addr = trim(I('addr/s'));
        $paypassword = I('paypassword/s');

        if (!userid()) {
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

        $user_paypassword = M('User')->where(array('id' => userid()))->getField('paypassword');

        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!M('Coin')->where(array('name' => $coin))->find()) {
            $this->error('币种错误！');
        }

        $userQianbao = M('UserQianbao')->where(array('userid' => userid(), 'coinname' => $coin))->select();
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

        if (M('UserQianbao')->add(array('userid' => userid(), 'name' => $name, 'addr' => $addr, 'coinname' => $coin, 'addtime' => time(), 'status' => 1))) {
            $this->success('添加成功！');
        } else {
            $this->error('添加失败！');
        }
    }

    //删除钱包地址
    public function delqianbao()
    {
        $id = I('id/d');
        $paypassword = I('paypassword/s');

        if (!userid()) {
            redirect('/#login');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($id, 'd')) {
            $this->error('参数错误！');
        }

        $user_paypassword = M('User')->where(array('id' => userid()))->getField('paypassword');
        if (md5($paypassword) != $user_paypassword) {
            $this->error('交易密码错误！');
        }

        if (!M('UserQianbao')->where(array('userid' => userid(), 'id' => $id))->find()) {
            $this->error('非法访问！');
        } else if (M('UserQianbao')->where(array('userid' => userid(), 'id' => $id))->delete()) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    //币种转出记录
    public function coinoutLog(){
        $coin = I('coin/s', NULL);
        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myzc'));
        if (C('coin')[$coin]) {
            $coin = trim($coin);
        } else {
            $coin = C('xnb_mr');
        }

        $this->assign('xnb', $coin);
        $Coin = M('Coin')->where(array(
            'status' => 1,
            'type'   => array('neq', 'rmb')
        ))->select();
        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);

        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Moble = M('Myzc');
        $count = $Moble->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //币种转出
    public function myzc()
    {
        $coin = I('coin/s');
        $this->assign('coin',$coin);
        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_myzc'));

        if (C('coin')[$coin]) {
            $coin = trim($coin);
        } else {
            $coin = C('xnb_mr');
        }


        $this->assign('xnb', $coin);
        $Coin = M('Coin')->where(array(
            'status' => 1,
            'type'   => array('neq', 'rmb')
        ))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }
        $this->assign('coin_list', $coin_list);
        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin[$coin] = round($user_coin[$coin], 6);
        $this->assign('user_coin', $user_coin);

        if (!$coin_list[$coin]['zc_jz']) {
            $this->assign('zc_jz', '当前币种禁止转出！');
        } else {
            $userQianbaoList = M('UserQianbao')->where(array('userid' => userid(), 'status' => 1, 'coinname' => $coin))->order('id desc')->select();
            $this->assign('userQianbaoList', $userQianbaoList);
            $moble = M('User')->where(array('id' => userid()))->getField('moble');

            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }

        $where['userid'] = userid();
        $where['coinname'] = $coin;
        $Moble = M('Myzc');
        $count = $Moble->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        //获取转出最小数量
        $min_number = M('Coin')->where(['name' => $coin])->getField('zc_min');
        $this->assign('min_number', $min_number);

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //币种转出
    public function upmyzc()
    {
        $coin = I('coin/s');
        $num = I('num/f');
        $addr = I('addr/s');
        $paypassword = I('paypassword/s');
        $moble_verify = I('moble_verify/d');
        $wcgkey = I('wcgkey/s');
        if (!userid()) {
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

        if (!C('coin')[$coin]) {
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();
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

        $user = M('User')->where(array('id' => userid()))->find();
        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }

        if ($user['idcardauth'] == 0) {
            $this->error('请先进行身份认证！');
        }

        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
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
        $fee_user = M('UserCoin')->where(array($qbdz => $Coin['zc_user']))->find();
        if ($fee_user) {
            debug('手续费地址: ' . $Coin['zc_user'] . ' 存在,有手续费');
            $usercoin=M('coin')->where(array('name'=>$coin))->getField('zc_fee');

            if($str_len = strpos($usercoin,'%')){
                $usercoin = substr($usercoin,0,$str_len);
                $fee = round(($num / 100) * ($usercoin), 8);
            }else{
                $fee =  $usercoin;
            }


//            if($coin=='wc' || $coin=='wcg' || $coin=='oioc' || $coin=='eac' || $coin == 'sie' || $coin == 'drt' || $coin == 'mat' || $coin == 'ifc' || $coin == 'mtr' || $coin == 'xrp'){
//                $fee = round(($num / 100) * ($usercoin), 8);
//            }else{
//                $fee =  $usercoin;
//            }
            //无限币提币费率：就是500W以下 0.2%+200个.   500W以上 10000个+200 个
            /*if($coin=='ifc'){
                if($num<=5000000) {
                    $fee = round(($num * 0.002)+200,8);
                } elseif($num>5000000){
                    $fee = 10200;
                }
            }*/
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

            $mo = M();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzc_fee write');
            $rs = array();
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);

            if ($fee) {
                if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                    $rs[] = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                    debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                } else {
                    $rs[] = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    debug(array('msg' => '转出收取手续费' . $fee), 'fee');
                }
            }
            $arr = array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0);
//            if (($coin === 'wcg' && !empty($wcgkey)) || ($coin === 'drt' && !empty($wcgkey)) || ($coin === 'mat' && !empty($wcgkey))) {
//
//            }
            if(!empty($wcgkey)){
                $arr['wcgkey'] = $wcgkey;
            }else{
                $arr['wcgkey'] =0;
            }

            $rs[] = $mo->table('weike_myzc')->add($arr);
            if ($fee_user) {
                $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
            }

            if (check_arr($rs)) {
                $mo->execute('commit');
                $mo->execute('unlock tables');
                session('myzc_verify', null);
                $this->success('转账成功！');
            } else {
                $mo->execute('rollback');
                $this->error('转账失败!');
            }
        }

        if ($Coin['type'] == 'bit' || $Coin['type'] == 'eth' || $Coin['type'] == 'token' || $Coin['type'] == 'eos') {
            $mo = M();
            if ($Coin['type'] == 'eos') {
                $user_wallet =  M('UserQianbao')->where(array('memo' => $addr,'userid'=>userid(),'coinname'=>'eos'))->find();
                $addr = $user_wallet['addr'];
                $memo = $user_wallet['memo'];
            }
            if ($mo->table('weike_user_coin')->where(array($qbdz => $addr))->find() && $Coin['type'] != 'eos') {
                //禁止站内互转！
                $this->error('禁止站内互转!');
                $peer = M('UserCoin')->where(array($qbdz => $addr))->find();
                if (!$peer) {
                    $this->error('转出地址不存在！');
                }

                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write  , weike_myzr write , weike_myzc_fee write');
                $rs = array();
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                $rs[] = $mo->table('weike_user_coin')->where(array('userid' => $peer['userid']))->setInc($coin, $mum);

                if ($fee) {
                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                    } else {
                        $rs[] = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    }
                }

                $rs[] = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'txid' => md5($addr . $user_coin[$coin . 'b'] . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                $rs[] = $mo->table('weike_myzr')->add(array('userid' => $peer['userid'], 'username' => $user_coin[$coin . 'b'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $addr . time()), 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 1));

                if ($fee_user) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'txid' => md5($user_coin[$coin . 'b'] . $Coin['zc_user'] . time()), 'num' => $num, 'fee' => $fee, 'type' => 1, 'mum' => $mum, 'addtime' => time(), 'status' => 1));
                }

                if (check_arr($rs)) {
                    $mo->execute('commit');
                    $mo->execute('unlock tables');
                    session('myzc_verify', null);
                    $this->success('转账成功！');
                } else {
                    $mo->execute('rollback');
                    $this->error('转账失败!');
                }
            } else {
                $auto_status = ($Coin['zc_zd'] && ($num < $Coin['zc_zd']) ? 1 : 0); //自动转币
                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write ,weike_myzr write, weike_myzc_fee write');
                $rs = array();
                $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec($coin, $num);
                //$rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                if ( $Coin['type'] == 'eos') {
                    $addr_memo = $addr.' '.$memo;
                    $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr_memo, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                } else {
                    $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));
                }

                if ($fee && $auto_status) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $r = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc($coin, $fee);
                        debug(array('res' => $r, 'lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                    } else {
                        $rs[] = $r = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], $coin => $fee));
                    }
                }
                if (check_arr($rs)) {
                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        if ($Coin['type'] == 'bit') {
                            $data = myCurl('http://172.66.66.32/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);
                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                $this->error($data['message']);
                            }
                        } elseif ($Coin['type'] == 'eth' || $Coin['type'] == 'token') {
                            $data = myCurl('http://172.66.66.32/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num]);

                            if($data['status'] === 200) {
                                $sendrs = $data['sendrs'];
                            } else {
                                $this->error($data['message']);
                            }
                        } elseif ($Coin['type'] == 'eos') {
                            $data = myCurl('http://172.31.39.219/mapi/walletadd/withdraw', ['coin' => $coin, 'addr' => $addr, 'num' => $num , 'memo' => $memo]);

                            if ($data['status'] === 200) {
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
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        $this->success('转出成功!');
                    } else {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        $this->success('转出申请成功,请等待审核！');
                    }
                } else {
                    $mo->execute('rollback');
                    $this->error('转出失败!');
                }
            }
        }

        if ($Coin['type'] == 'btm'){
            $btmzData = M('coin')->field('dj_zj,dj_dk,dj_yh,dj_mm,token_address')->where('type="btm"')->find();
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

                $mo = M();
                $mo->execute('set autocommit=0');
                $mo->execute('lock tables  weike_user_coin write  , weike_myzc write ,weike_myzr write, weike_myzc_fee write');
                $rs = array();
                $rs[] = $r = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec('btm', $num);
                $rs[] = $aid = $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => $auto_status));

                if ($fee && $auto_status) {
                    $rs[] = $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $rs[] = $r = $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc('btm', $fee);
                        debug(array('res' => $r, 'lastsql' => $mo->table('weike_user_coin')->getLastSql()), '新增费用');
                    } else {
                        $rs[] = $r = $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], 'btm' => $fee));
                    }
                }
                if (check_arr($rs)) {
                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');

                        $res = $btmClient->outcome($addr,$mum);
                        if ($res){
                            $sigRes = $btmClient->signTransaction($res);
                            if ($sigRes){
                                $resSub = $btmClient->submitTransaction($sigRes);
                                if ($resSub){
                                    M('myzc')->where(array('id' => trim($aid)))->save(['status'=>1,'txid'=>$resSub['tx_id']]);
                                    $flag = true;
                                }else{
                                    M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                                    $flag = false;
                                }
                            }else{
                                M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                                $flag = false;
                            }
                        }else{
                            M('myzc')->where(array('id' => trim($aid)))->save(['status'=>0]);
                            $flag = false;
                        }
                    }

                    if ($auto_status) {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        if ($flag){
                            $this->success('转出成功!');
                        }else{
                            $this->error('转出成功，请等待确认');
                        }
                    } else {
                        $mo->execute('commit');
                        $mo->execute('unlock tables');
                        session('myzc_verify', null);
                        $this->success('转出申请成功,请等待审核！');
                    }
                } else {
                    $mo->execute('rollback');
                    $this->error('转出失败!');
                }

            }else{
                $this->error('转出失败！');
            }
        }

        if ($Coin['type'] == 'xrp'){
            $user_wallet =  M('UserQianbao')->where(array('addr' => $addr,'userid'=>userid(),'coinname'=>'xrp'))->find();
            $addr = $user_wallet['memo'] ? $user_wallet['addr'] . ' ' .$user_wallet['memo'] :  $user_wallet['addr'];
            $mo = M();
            $mo->startTrans();
            try{
                $mo->table('weike_user_coin')->where(array('userid' => userid()))->setDec('xrp', $num);
                $mo->table('weike_myzc')->add(array('userid' => userid(), 'username' => $addr, 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'addtime' => time(), 'status' => 0));

                if ($fee) {
                    $mo->table('weike_myzc_fee')->add(array('userid' => $fee_user['userid'], 'username' => $Coin['zc_user'], 'coinname' => $coin, 'num' => $num, 'fee' => $fee, 'mum' => $mum, 'type' => 2, 'addtime' => time(), 'status' => 1));

                    if ($mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->find()) {
                        $mo->table('weike_user_coin')->where(array($qbdz => $Coin['zc_user']))->setInc('xrp', $fee);
                    } else {
                        $mo->table('weike_user_coin')->add(array($qbdz => $Coin['zc_user'], 'xrp' => $fee));
                    }
                }
                $mo->commit();
                $flag = true;
            }catch (\Exception $e){
                $mo->rollback();
                $flag = false;
            }

            if ($flag){
                $this->success('添加成功');
            }else{
                $this->success('添加失败');
            }

        }
    }

    //钱包币转入
    /*  public function upmyzr()
      {
          $coin = I('coin/s');
          $weike_dzbz = I('weike_dzbz/s');
          $num = I('num/f');
          $paypassword = I('paypassword/s');
          $moble_verify = I('moble_verify/d');

          if (!userid()) {
              $this->error('您没有登录请先登录！');
          }

          if (!check($moble_verify, 'd')) {
              $this->error('验证码格式错误！');
          }

          if ($moble_verify != session('myzr_verify')) {
              $this->error('验证码错误！');
          }

          $num = abs($num);

          if (!check($num, 'currency')) {
              $this->error('数量格式错误！');
          }


          if (!check($paypassword, 'password')) {
              $this->error('交易密码格式错误！');
          }

          if (!check($coin, 'n')) {
              $this->error('币种格式错误！');
          }

          if (!C('coin')[$coin]) {
              $this->error('币种错误！');
          }

          $Coin = M('Coin')->where(array('name' => $coin))->find();

          if (!$Coin) {
              $this->error('币种错误！');
          }


          $user = M('User')->where(array('id' => userid()))->find();

          if (md5($paypassword) != $user['paypassword']) {
              $this->error('交易密码错误！');
          }

          $weike_zrcoinaddress = $Coin['weike_coinaddress'];

          if ($Coin['type'] == 'rgb') {
              M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0));
              $this->success('转入申请成功,等待客服处理！');

          }else{
              $this->error("钱包币不允许该操作!");
          }

      }*/

    //委托管理
    public function mywt()
    {
        $market = I('market/s', NULL);
        $type = I('type/d', NULL);
        $status = I('status/d', NULL);

        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mywt'));
        check_server();
        $Coin = M('Coin')->where(array('status' => 1))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = M('Market')->where(array('status' => 1))->select();

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
        $Moble = M('Trade');
        /**/
        $count = $Moble->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        /**/
        foreach ($list as $k => $v) {
            $list[$k]['num'] = $v['num'] * 1;
            $list[$k]['price'] = $v['price'] * 1;
            $list[$k]['deal'] = $v['deal'] * 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //成交查询
    public function mycj()
    {
        $market = I('market/s', NULL);
        $type = I('type/d', NULL);

        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mycj'));
        check_server();
        $Coin = M('Coin')->where(array('status' => 1))->select();

        foreach ($Coin as $k => $v) {
            $coin_list[$v['name']] = $v;
        }

        $this->assign('coin_list', $coin_list);
        $Market = M('Market')->where(array('status' => 1))->select();

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
            $where = ['userid' => userid(), 'market' => $market];
        } else if ($type == 2) {
            $where = ['peerid' => userid(), 'market' => $market];
        } else {
            $where = [
                '_complex' => [
                    'userid' => userid(),
                    'peerid' => userid(),
                    '_logic' => 'or',
                ],
                'market' => $market
            ];
        }

        $this->assign('market', $market);
        $this->assign('type', $type);
        $this->assign('userid', userid());
        $Moble = M('TradeLog');
        $count = $Moble->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = $Moble->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = $v['num'] * 1;
            $list[$k]['price'] = $v['price'] * 1;
            $list[$k]['mum'] = $v['mum'] * 1;
            $list[$k]['fee_buy'] = $v['fee_buy'] * 1;
            $list[$k]['fee_sell'] = $v['fee_sell'] * 1;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //邀请好友
    public function mytj()
    {
        if (!userid()) {
            redirect('/#login');
        }

        $this->assign('prompt_text', D('Text')->get_content('finance_mytj'));
        check_server();
        $user = M('User')->where(array('id' => userid()))->find();

        if (!$user['invit']) {
            for (; true; ) {
                $tradeno = tradenoa();

                if (!M('User')->where(array('invit' => $tradeno))->find()) {
                    break;
                }
            }

            M('User')->where(array('id' => userid()))->save(array('invit' => $tradeno));
            $user = M('User')->where(array('id' => userid()))->find();
        }

        $this->assign('user', $user);
        $this->display();
    }

    //我的推荐
    public function mywd()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $this->assign('prompt_text', D('Text')->get_content('finance_mywd'));
        check_server();
        $where['invit_1'] = userid();
        $Model = M('User');

        $count = $Model->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id asc')->field('id,username,moble,addtime,invit_1,idcardauth')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['invits'] = M('User')->where(array('invit_1' => $v['id']))->order('id asc')->field('id,username,moble,addtime,invit_1,idcardauth')->select();
            $list[$k]['invitss'] = count($list[$k]['invits']);

            foreach ($list[$k]['invits'] as $kk => $vv) {
                $list[$k]['invits'][$kk]['invits'] = M('User')->where(array('invit_1' => $vv['id']))->order('id asc')->field('id,username,moble,addtime,invit_1')->select();
                $list[$k]['invits'][$kk]['invitss'] = count($list[$k]['invits'][$kk]['invits']);
            }
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //我的奖励
    public function myjp()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $this->assign('prompt_text', D('Text')->get_content('finance_myjp'));

        $where['userid'] = userid();
        $Model = M('Invit');
        $count = $Model->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['invit'] = M('User')->where(array('id' => $v['invit']))->getField('username');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //活动扩展
//    public function myhuodong()
//    {
//        if (!userid()) {
//            redirect('/#login');
//        }
//        $invit = M('user')->where(array('id' => userid()))->getField('id');
//        $reuser = M('User')->where(array('id'=>$invit['invit_1'],'status'=>1))->field('id,username')->find();
//        dump($reuser);
//        $guan = M('user')->where(array('invit_1' => $invit, 'idcardauth' => 1))->select();
//        $vit = M('RegisterAward')->where(array('one' =>M('user')->where(array('id'=>userid()))->getField('username')))->find();
//        foreach ($guan as $k => $v) {
//            $arr[] = $v['username'];
//        }
//        $Campaign = M('Campaign')->where(array('status' => 0))->find();
//        if (time() >= $Campaign['start_time'] && time() <= $Campaign['end_time']) {
//            if ($guan && !$vit) {
//                echo 131323;
//                M('user_coin')->where(array('userid' => userid()))->setInc($Campaign['coin'], $Campaign['num']);
//                $data = array(
//                    'users' => implode(',', $arr),
//                    'one' =>M('user')->where(array('id'=>userid()))->getField('username'),
//                    'n' =>1,
//                    'coin' =>$Campaign['coin'],
//                    'nums' => $Campaign['num'],
//                    'add_time' => time(),
//                    'status' => 1,
//                );
//                M('RegisterAward')->add($data);
//            }
//        }
//    }
    //游戏充值奖励
    public function myaward()
    {
        if (!userid()) {
            redirect('/#login');
        }
        $this->assign('prompt_text', D('Text')->get_content('finance_myjp'));

        $id = $_POST['id'];
        $time = $_POST['time'];
        if($id && $time){
            $time = date('Y-m-d H:i:s',$time + 3600 * 24 * 365);
            $this->error('请在 ' . $time . ' 之后操作！');
        }

        $where['username'] = username();
        $Model = M('UserAward');
        $count = $Model->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['arrival_time'] = $list[$k]['addtime'] + 3600 * 24 * 365;
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //认证奖励，充值奖励，分享奖励
    public function myawardifc()
    {
        if (!userid()) {
            redirect('/#login');
        }
//        $this->myhuodong();
        $this->assign('prompt_text', D('Text')->get_content('finance_myjp'));

        $id = I('id/d');
        if($id) {
            $status = M('RegisterAward')->where(['id' => $id])->getField('status');
            if ($status == 1){
                $this->error('已经奖励！');
            } else {
                $this->error('请在一月二十五号之后操作！');
            }
        }
        $user = M('User')->where(['id'=>userid()])->field('username')->find();
        $where = "r.status = 1 and r.one = '".$user['username']."' or r.two = '".$user['username']."'";
        $count = M('RegisterAward')->alias('r')->where($where)->count();
        $Page = new \Think\Page($count, 10);
        $show = $Page->show();
        $list = M('RegisterAward')
            ->alias('r')
            ->join('weike_coin c on c.id = r.coin')
            ->join('weike_admin a on r.admin_id = a.id')
            ->field("r.id,r.one,r.nums,c.title,c.name,a.username,FROM_UNIXTIME(r.add_time) as add_time,(case when r.status = 1  then '已奖励' else '未奖励' end) as award_status,(case when r.type =1 then '认证奖励' when r.type = 2 then '邀请充值奖励' when r.type = 3 then '分享奖励' else '其它' end) as award_type,r.n")
            ->order('r.times desc')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }

    //EJF
    public function myejf()
    {
        if (IS_POST) {
            $id = I('id/d');
            if (!userid()) {
                redirect('/#login');
            }

            if (!check($id, 'd')) {
                $this->error('请选择解冻项！');
            }

            $IssueEjf = M('IssueEjf')->where(array('id' => $id))->find();

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
            $mo = M();
            $mo->execute('set autocommit=0');
            $mo->execute('lock tables weike_user_coin write  , weike_issue_ejf write ');
            $rs = array();
            $rs[] = $mo->table('weike_user_coin')->where(array('userid' => userid()))->setInc($IssueEjf['coinname'], $jd_num);
            $rs[] = $mo->table('weike_issue_ejf')->where(array('id' => $IssueEjf['id']))->save(array('unlock' => $IssueEjf['unlock'] + 1, 'endtime' => $tm));

            if ($IssueEjf['ci'] <= $IssueEjf['unlock'] + 1) {
                $rs[] = $mo->table('weike_issue_ejf')->where(array('id' => $IssueEjf['id']))->save(array('status' => 1));
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
            $count = M('IssueEjf')->where($where)->count();
            $Page = new \Think\Page($count, 10);
            $show = $Page->show();
            $list = M('IssueEjf')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

            foreach ($list as $k => $v) {
                $list[$k]['shen'] = round((($v['ci'] - $v['unlock']) * $v['num']) / $v['ci'], 6);
                $list[$k]['username'] = M('User')->where(['id' => $v['userid']])->getField('username');
            }

            $this->assign('list', $list);
            $this->assign('page', $show);
            $this->display();
        }
    }

    //雷达钱包
    public function ldqb()
    {
        $status = I('status/d', NULL);
        if (!userid()) {
            redirect('/#login');
        }

//        $this->assign('prompt_text', D('Text')->get_content('finance_mytx'));
        $moble = M('User')->where(array('id' => userid()))->getField('moble');

        if ($moble) {
            $moble = substr_replace($moble, '****', 3, 4);
        } else {
            $this->error('请先认证手机！', U('Home/Order/index'));
        }

        $this->assign('moble', $moble);
        $user_coin = M('UserCoin')->where(array('userid' => userid()))->find();
        $user_coin['cny'] = round($user_coin['cny'], 2);
        $user_coin['cnyd'] = round($user_coin['cnyd'], 2);
        $this->assign('user_coin', $user_coin);
        $userBankList = M('UserBank')->where(array('userid' => userid(), 'status' => 1))->order('id desc')->select();
        $this->assign('userBankList', $userBankList);

        if (($status == 1) || ($status == 2) || ($status == 3) || ($status == 4)) {
            $where['status'] = $status - 1;
        }

        $this->assign('status', $status);
        $where['userid'] = userid();
        $count = M('Mytx')->where($where)->count();
        $Page = new \Think\Page($count, 15);
        $show = $Page->show();
        $list = M('Mytx')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

        foreach ($list as $k => $v) {
            $list[$k]['num'] = (Num($v['num']) ? Num($v['num']) : '');
            $list[$k]['fee'] = (Num($v['fee']) ? Num($v['fee']) : '');
            $list[$k]['mum'] = (Num($v['mum']) ? Num($v['mum']) : '');
        }

        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }


    //钱包币转入
    public function upmyzr()
    {
        $coin = I('coin/s');
        $weike_dzbz = I('weike_dzbz/s');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $moble_verify = I('moble_verify/d');
        $tradeno = I('post.tradeno/s');
        $wcg_qb = I('wcg_qb/s');
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
            if ($wcg_qb == 'WCG-6UWY-QRNP-P5PH-55XCW') {
                if ($num < 0 || $num >= 500) {
                    $this->error('转入数量错误，请输入0到500之间的数');
                }
            } else if ($wcg_qb == 'WCG-QZ5U-AUC2-CCQY-237SS') {
                if ($num < 500) {
                    $this->error('转入数量错误，请输入500以上的数量');
                }
            } else {
                $this->error('转入钱包地址错误');
            }
        }


        //判断转入地址是否正确
        if ($coin == 'wcg'){
            if ($wcg_qb != 'WCG-6UWY-QRNP-P5PH-55XCW' && $wcg_qb != 'WCG-QZ5U-AUC2-CCQY-237SS'){
                $this->error('转入错误，请联系客服！');
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!C('coin')[$coin]) {
            $this->error(json_encode(C('coin')['wcg']));
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }


        if( M('Myzr')->where(array('userid' => userid() ,'coinname' => 'wcg', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (M('Myzr')->where(array('userid' => userid() ,'coinname' => 'wcg', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];

        if ($Coin['type'] == 'rgb') {
            if ($coin == 'wcg'){
                $time = time();
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => $time, 'status' =>0 , 'tradeno' => $tradeno, 'txid'=>md5($tradeno.$time)));
            }else{
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }

            $this->success('转入申请成功,等待客服处理！', '/Activity/wcgzr' , 2);

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

        $truename = M('user')->where('id='.$userid)->getField('truename');
        $count = M('fenhong_log')->where('userid='.$userid)->count();
        $page = new Page($count,20);
        $show = $page->show();
        $list = M('fenhong_log')->where('userid='.$userid)->order('addtime desc')->limit($page->firstRow,$page->listRows)->select();
        foreach ($list as $k=>$v){
            $list[$k]['mum'] = round($v['mum'],4);
            $list[$k]['truename'] = $truename;
            $list[$k]['fenhong_time'] = date('Y-m-d',$v['addtime']);
        }
        $this->assign('list',$list);
        $this->assign('page',$show);
        $this->display();
    }
    //榴莲币
    public function mbizr()
    {
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户榴莲币数量
        $user_wcg = M('UserCoin')->where(['userid' => userid()])->getField('drt');
        //获取榴莲币金配置
        $wcg_info = M('Coin')->where(['name' => 'drt'])->find();
//        dd($wcg_info);
        if(!$wcg_info['zr_jz']){
            $this->error('当前榴莲币禁止转入');
        }else {
            $moble = M('User')->where(array('id' => userid()))->getField('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        $tradeno = self::get_code();

        //用户转入记录
        $list = M('Myzr')->where(['userid' => userid() , 'coinname' => 'drt'])->order('id desc')->select();
        $this->assign('wcg_info' , $wcg_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        $this->display();
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

    public function upMbi()
    {
        $coin = I('coin/s');
        $weike_dzbz = I('weike_dzbz/s');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $moble_verify = I('moble_verify/d');
        $tradeno = I('post.tradeno/s');
        $wcg_qb = I('wcg_qb/s');
        //$tradeid = I('tradeid/s');
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

        if (!C('coin')[$coin]) {
            $this->error(json_encode(C('coin')['drt']));
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }


        if( M('Myzr')->where(array('userid' => userid() ,'coinname' => 'drt', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (M('Myzr')->where(array('userid' => userid() ,'coinname' => 'drt', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];

        if ($Coin['type'] == 'rgb') {
            if ($coin == 'drt'){
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 , 'tradeno' => $tradeno,'txid'=>md5($tradeno.$time)));
            }else{
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }

            $this->success('转入申请成功,等待客服处理！', '/Finance/mbizr' , 2);

        }else{
            $this->error("钱包币不允许该操作!", '/Finance/mbizr' , 2);
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

    public function matzr()
    {
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户榴莲币数量
        $user_wcg = M('UserCoin')->where(['userid' => userid()])->getField('mat');
        //获取榴莲币金配置
        $wcg_info = M('Coin')->where(['name' => 'mat'])->find();
//        dd($wcg_info);
        if(!$wcg_info['zr_jz']){
            $this->error('当前榴莲币禁止转入');
        }else {
            $moble = M('User')->where(array('id' => userid()))->getField('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        $tradeno = self::get_code();
        //用户转入记录
        $list = M('Myzr')->where(['userid' => userid() , 'coinname' => 'mat'])->order('id desc')->select();
        $this->assign('wcg_info' , $wcg_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        $this->display();
    }

    public function upMat()
    {
        $coin = I('coin/s');
        $weike_dzbz = I('weike_dzbz/s');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $moble_verify = I('moble_verify/d');
        $tradeno = I('post.tradeno/s');
        $wcg_qb = I('wcg_qb/s');

        //$tradeid = I('tradeid/s');
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
        if ($coin == 'mat'){
            if ($wcg_qb != 'WCG-G3MB-RCKH-3LME-2E9EW'){
                $this->error('转入错误，请联系客服！');
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!C('coin')[$coin]) {
            $this->error(json_encode(C('coin')['mat']));
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }


        if( M('Myzr')->where(array('userid' => userid() ,'coinname' => 'mat', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (M('Myzr')->where(array('userid' => userid() ,'coinname' => 'mat', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];


        if ($Coin['type'] == 'rgb') {
            if ($coin == 'mat'){
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 , 'tradeno' => $tradeno, 'txid'=>md5($tradeno.time())));
            }else{
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }
            $this->success('转入申请成功,等待客服处理！', '/Finance/matzr' , 2);
        }else{
            $this->error("钱包币不允许该操作!", '/Finance/matzr' , 2);
        }
    }

    public function mtrzr()
    {
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户榴莲币数量
        $user_wcg = M('UserCoin')->where(['userid' => userid()])->getField('mat');
        //获取榴莲币金配置
        $wcg_info = M('Coin')->where(['name' => 'mtr'])->find();
//        dd($wcg_info);
        if(!$wcg_info['zr_jz']){
            $this->error('当前魔太链禁止转入');
        }else {
            $moble = M('User')->where(array('id' => userid()))->getField('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        $tradeno = self::get_code();
        //用户转入记录
        $list = M('Myzr')->where(['userid' => userid() , 'coinname' => 'mtr'])->order('id desc')->select();
        $this->assign('wcg_info' , $wcg_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        $this->display();
    }

    public function upMtr()
    {
        $coin = I('coin/s');
        $weike_dzbz = I('weike_dzbz/s');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $moble_verify = I('moble_verify/d');
        $tradeno = I('post.tradeno/s');
        $wcg_qb = I('wcg_qb/s');

        //$tradeid = I('tradeid/s');
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
        if ($coin == 'mat'){
            if ($wcg_qb != 'WCG-YRY4-QHXN-8NAB-32Y7Y'){
                $this->error('转入错误，请联系客服！');
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!C('coin')[$coin]) {
            $this->error(json_encode(C('coin')['mtr']));
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }


        if( M('Myzr')->where(array('userid' => userid() ,'coinname' => 'mtr', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (M('Myzr')->where(array('userid' => userid() ,'coinname' => 'mtr', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];


        if ($Coin['type'] == 'rgb') {
            if ($coin == 'mtr'){
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 , 'tradeno' => $tradeno, 'txid'=>md5($tradeno.time())));
            }else{
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }
            $this->success('转入申请成功,等待客服处理！', '/Finance/matzr' , 2);
        }else{
            $this->error("钱包币不允许该操作!", '/Finance/matzr' , 2);
        }
    }
    //继承权益
    public function myInherit()
    {

        if (!userid()) {
            redirect('/#login');
        }

        if($_POST){

            if(!$_POST['name']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'请填写姓名'));
            }
            if(!$_POST['phone']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'请填写手机号'));
            }
            $user=M('User')->where(['id'=>userid()])->find();
            if($_POST['name']==$user['truename']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'继承人姓名不能跟当前账户姓名相同'));
            }


            if($_POST['phone']==$user['username']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'继承人手机号不能跟当前账户手机号相同'));
            }
            if($_POST['idCard']==$user['idcard']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'继承人身份证号码不能跟当前账户身份证号码相同'));
            }
            if(!$_POST['idCard']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'请填写身份证号码'));
            }
            if(!$_POST['msgCode']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'请填写短信验证码'));
            }
            if($_POST['msgCode']!=session('WeChatcode')){
                $this->ajaxReturn(array('status'=>0,'msg'=>'短信验证码不正确'));
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
                $Inherit= M('Inherit')->add($data);

                $userId = M('Inherit')->where(['userid'=>userid()])->getLastInsID();

                if($Inherit){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功','userid'=>$userId));
                }else{
                    $this->ajaxReturn(array('status'=>0,'msg'=>'添加失败'));
                }
            }else{
                $Inherit= M('Inherit')->where(['id'=>$_POST['isAdd'],'userid'=>userid()])->save($data);
                if($Inherit){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'编辑成功'));
                }else{
                    $this->ajaxReturn(array('status'=>0,'msg'=>'编辑失败'));
                }
            }

        }else{
            $Inherit=M('Inherit')->where(['userid'=>userid()])->find();
            // dump($Inherit);
            $moble=M('User')->where(['id'=>userid()])->getField('moble');
            $this->assign('inherit',$Inherit);
            $this->assign('moble',$moble);
            $this->display();
        }

    }

    public function unihzr()
    {
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户榴莲币数量
        $user_unih = M('UserCoin')->where(['userid' => userid()])->getField('unih');
        //获取榴莲币金配置
        $unih_info = M('Coin')->where(['name' => 'unih'])->find();
        if(!$unih_info['zr_jz']){
            $this->error('当前尤里米禁止转入');
        }else {
            $moble = M('User')->where(array('id' => userid()))->getField('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        $tradeno = self::get_code();
        //用户转入记录

        $list = M('Myzr')->where(['userid' => userid() , 'coinname' => 'unih'])->order('id desc')->select();
        $this->assign('unih_info' , $unih_info);
        $this->assign('user_wcg' , $user_unih);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        $this->display();
    }

    public function upUnih()
    {
        $coin = I('coin/s');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $tradeno = I('post.tradeno/s');
        $moble_verify = I('moble_verify/d');
        $wcg_qb = I('wcg_qb/s');

        if (!userid()) {
            $this->error('您没有登录请先登录！');
        }

        //只能保留两位小数

        if ($num < 0) {
            $this->error('数量格式错误！');
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!C('coin')[$coin]) {
            $this->error(json_encode(C('coin')['unih']));
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }

        if (M('Myzr')->where(array('userid' => userid() ,'coinname' => 'unih', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];


        if ($Coin['type'] == 'rgb') {
            if ($coin == 'unih'){
                $id = M('myzr')->add(array('userid' => userid(),'coinname' => $coin, 'tradeno' => $tradeno,'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ,'txid'=>md5($tradeno.time())));
            }else{
                $id = M('myzr')->add(array('userid' => userid(), 'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }
            exit(json_encode(['status'=>1,'id'=>$id]));
        }else{
            $this->error("钱包币不允许该操作!", '/Finance/unihzr' , 2);
        }
    }

    public function unihTxid()
    {
        $data = I('post.');

        if (!$data['id']){
            exit(json_encode(['status'=>0,'msg'=>'网络错误1']));
        }
        if (!$data['tradeno']){
            exit(json_encode(['status'=>0,'msg'=>'网络错误2']));
        }

        $tradeno = M('myzr')->where(['id'=>$data['id']])->getField('tradeno');
        if ($data['tradeno'] == $tradeno){
            exit(json_encode(['status'=>1]));
        }
        if (M('myzr')->save($data)){
            exit(json_encode(['status'=>1]));
        }else{
            exit(json_encode(['status'=>0,'msg'=>'网络错误3']));
        }

    }

    public function unihImg()
    {
        $this->display();
    }

    public function woszr()
    {
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户榴莲币数量
        $user_wcg = M('UserCoin')->where(['userid' => userid()])->getField('wos');
        //获取榴莲币金配置
        $wcg_info = M('Coin')->where(['name' => 'wos'])->find();
//        dd($wcg_info);
        if(!$wcg_info['zr_jz']){
            $this->error('当前分享通证禁止转入');
        }else {
            $moble = M('User')->where(array('id' => userid()))->getField('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        $tradeno = self::get_code();
        //用户转入记录
        $list = M('Myzr')->where(['userid' => userid() , 'coinname' => 'wos'])->order('id desc')->select();
        $this->assign('wcg_info' , $wcg_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        $this->display();
    }

    public function upWos()
    {
        $coin = I('coin/s');
        $weike_dzbz = I('weike_dzbz/s');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $moble_verify = I('moble_verify/d');
        $tradeno = I('post.tradeno/s');
        $wcg_qb = I('wcg_qb/s');

        //$tradeid = I('tradeid/s');
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
        if ($coin == 'mat'){
            if ($wcg_qb != 'WCG-N6PN-GFYU-ZM4D-6KLAP'){
                $this->error('转入错误，请联系客服！');
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!C('coin')[$coin]) {
            $this->error(json_encode(C('coin')['wos']));
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }


        if( M('Myzr')->where(array('userid' => userid() ,'coinname' => 'wos', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (M('Myzr')->where(array('userid' => userid() ,'coinname' => 'wos', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];


        if ($Coin['type'] == 'rgb') {
            if ($coin == 'wos'){
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 , 'tradeno' => $tradeno, 'txid'=>md5($tradeno.time())));
            }else{
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }
            $this->success('转入申请成功,等待客服处理！', '/Finance/woszr' , 2);
        }else{
            $this->error("钱包币不允许该操作!", '/Finance/woszr' , 2);
        }
    }

    //教育通行证
    public function eqt()
    {
        if (!userid()) {
            redirect('/#login');
        }
        //获取用户榴莲币数量
        $user_wcg = M('UserCoin')->where(['userid' => userid()])->getField('eqt');
        //获取榴莲币金配置
        $eqt_info = M('Coin')->where(['name' => 'eqt'])->find();
        if(!$eqt_info['zr_jz']){
            $this->error('当前教育通行证禁止转入');
        }else {
            $moble = M('User')->where(array('id' => userid()))->getField('moble');
            if ($moble) {
                $moble = substr_replace($moble, '****', 3, 4);
            } else {
                $this->error('请先认证手机！', U('Home/Order/index'));
            }

            $this->assign('moble', $moble);
        }
        $tradeno = self::get_code();
        //用户转入记录
        $list = M('Myzr')->where(['userid' => userid() , 'coinname' => 'eqt'])->order('id desc')->select();
        $this->assign('wcg_info' , $eqt_info);
        $this->assign('user_wcg' , $user_wcg);
        $this->assign('tradeno' , $tradeno);
        $this->assign('list' , $list);
        $this->display();
    }

    //提交
    public function eqtMbi()
    {
        $coin = I('coin/s');
        $weike_dzbz = I('weike_dzbz/s');
        $num = I('num/f');
        $paypassword = I('paypassword/s');
        $moble_verify = I('moble_verify/d');
        $tradeno = I('post.tradeno/s');
        $wcg_qb = I('wcg_qb/s');
        //$tradeid = I('tradeid/s');
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
        if ($coin == 'eqt') {
            if ($wcg_qb != 'WCG-KLJX-XUAQ-EKNB-2TTYM') {
                $this->error('转入钱包地址错误');
            }
        }else{
            $this->error('币种错误');
        }

        //判断转入地址是否正确
        if ($coin == 'eqt'){
            if ($wcg_qb != 'WCG-KLJX-XUAQ-EKNB-2TTYM'){
                $this->error('转入错误，请联系客服！');
            }
        }

        if (!check($paypassword, 'password')) {
            $this->error('交易密码格式错误！');
        }

        if (!check($coin, 'n')) {
            $this->error('币种格式错误！');
        }

        if (!C('coin')[$coin]) {
            $this->error(json_encode(C('coin')['eqt']));
            $this->error('币种错误！');
        }

        $Coin = M('Coin')->where(array('name' => $coin))->find();

        if (!$Coin) {
            $this->error('币种错误！');
        }


        $user = M('User')->where(array('id' => userid()))->find();

        if (md5($paypassword) != $user['paypassword']) {
            $this->error('交易密码错误！');
        }


        if( M('Myzr')->where(array('userid' => userid() ,'coinname' => 'eqt', 'tradeno' => $tradeno))->find()){
            $this->error('请勿重复提交订单！');
        }

        if (M('Myzr')->where(array('userid' => userid() ,'coinname' => 'eqt', 'status' => 0))->find()) {
            $this->error('您还有未处理的订单！');
        }

        $weike_zrcoinaddress = $Coin['weike_coinaddress'];

        if ($Coin['type'] == 'rgb') {
            if ($coin == 'eqt'){
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 , 'tradeno' => $tradeno,'txid'=>md5($tradeno.$time)));
            }else{
                M('myzr')->add(array('userid' => userid(), 'username'=>$weike_dzbz,'txid'=>$weike_zrcoinaddress,'coinname' => $coin, 'num' => $num, 'mum' =>0, 'addtime' => time(), 'status' =>0 ));
            }

            $this->success('转入申请成功,等待客服处理！', '/Finance/eqt' , 2);

        }else{
            $this->error("钱包币不允许该操作!", '/Finance/eqt' , 2);
        }
    }



}

?>