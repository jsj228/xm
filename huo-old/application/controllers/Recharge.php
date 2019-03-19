<?php
/**
 * Created by PhpStorm.
 * User: longbijia
 * Date: 2017/9/13
 * Time: 19:21
 */
/**
 * 用户操作
 */
class RechargeController extends Ctrl_Base
{
    protected $_auth = 1;
    //用户充值
    public function indexAction($page=1)
    {
        $this->_ajax_islogin();
        $myczType = new RechargetypeModel;
        $method= $myczType->where('status=1')->fList();
        $this->assign('method', $method);
        $Mo = new RechargeModel();
        // 当前总记录条数
        $page = $_GET['page']?:1;
        $pageSize = 15;
        //总记录
        $count = $Mo->where(sprintf("userid={$this->mCurUser['uid']}"))->count();
        // 获取分页显示
        $data = array('list'=>array(), 'totalPage'=>ceil($count/$pageSize));
        $list = $Mo->where(sprintf("uid={$this->mCurUser['uid']}"))->page($page, $pageSize)->order('id desc')->fList();

        foreach ($list as $v) {
            $data['list'][] = array(
                'id' => $v['id'],
                'uid'=>$v['uid'],
                'num' => $v['num'],
                'mum' => $v['mum'],
                'type' => $v['type'],
                'tradeno' => $v['tradeno'],
                'remark' => $v['remark'],
                'beizhu' => $v['beizhu'],
                'bank_id' => $v['bank_id'],//商家支付ID
                'addtime' => date('Y-m-d H:i:s', $v['addtime']),
                'status' => $v['status'],//0 未付款 1 充值成功 2 处理中 3 已撤销
            );
        }

//        echo json_encode($data);
        $this->ajax('', 1, $data);
//        $this->ajax('list',$data['list']);
//        $this->assign('Page',$data['pageinfo']);
    }
    //充值提交
    public function rechargeupAction()
    {
        $this->_ajax_islogin();
        $type =$_GET["type"]?$_GET["type"]:'alipay';//支付方式
        $num =1000;//$_GET["num"]

//        if(!is_numeric($num)||strpos($num,".")!==false){
//            $this->ajax($GLOBALS['MSG']['SMS_FAIL'], 0, 'vcode'); //价格只能是正整数
//
//        }
//
//        $myczType = RechargetypeModel::getRechargetype($type,1);
//        if (!$myczType) {
//            $this->ajax($GLOBALS['MSG']['SMS_FAIL'], 0, 'vcode'); //充值方式不存在
//        }
//
//        if ($myczType['status']!= 1) {
//            $this->ajax($GLOBALS['MSG']['SMS_FAIL'], 0, 'vcode'); //充值方式没有开通
//        }
//
//        $mycz_min = ($myczType['min'] ? $myczType['min'] : 1);
//        $mycz_max = ($myczType['max'] ? $myczType['max'] : 100000);
//
//        if ($num < $mycz_min) {
//            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'] . $mycz_min . '元！', 0); //充值金额不能小于
//        }
//
//        if ($mycz_max < $num) {
//            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'] . $mycz_max . '元！', 0); //充值金额不能大于
//        }
//
//        //用户信息
//        if (RechargeModel::getRecharge($this->mCurUser['uid'],0)) {
//            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //您还有未付款的订单
//
//        }
//
//        if (RechargeModel::getRecharge($this->mCurUser['uid'],3)) {
//            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //您还有未处理的订单
//        }

        for (; true; ) {
            //网银充值生成5位订单号
            if ($type == 'bank'){
                $tradeno = substr(tradeno(),0, 5);
            }else{
                $tradeno = RechargeModel::tradeno();
            }
            if (!RechargeModel::getInstance()->where(array('tradeno' => $tradeno))->fRow()) {
                break;
            }

        }
       //平台账号
        $userbank=RechargetypeModel::getInstance()->where(array('name' => $type,'status'=>1))->fRow();

        //支付方式
        $new = RechargetypeModel::getRechargetype($type,1);
        $arr = ['userid' =>$this->mCurUser['uid'],'username'=>$_SESSION['user']['mo'],'bankname'=>$userbank['username'], 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0,'bank_id'=>$new['id']];
        $Re = new RechargeModel();
        $bank=$Re->insert($arr);
        if ($bank) {
            Tool_Fnc::ajaxMsg($GLOBALS['MSG']['SMS_SUCCESS'],1,array('id' => $bank));

        } else {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //提现订单创建失败
        }
        $this->dispaly();
    }
    //充值撤销
    public function chexiaoAction()
    {
        $this->_ajax_islogin();
        $id =$_GET; //$_GET('id');
        if (!$id) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //参数错误
        }
        $mycz = RechargeModel::getInstance()->where(array('id' =>$id))->fRow();
        if (!$mycz) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //充值订单不存在
        }

        if ($mycz['userid'] != $this->mCurUser['uid']) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //非法操
        }

        if ($mycz['status'] == 1 || $mycz['status'] == 3 ) {
            $this->error('订单不能撤销！');
        }

        //限定每天只能撤销两次
        $beginToday=strtotime(date('Y-m-d'));
        $mycznum = new RechargeModel();
        //用户ID
        $where ="userid={$this->mCurUser['uid']} and status=3 and addtime>{$beginToday}";

        $chexiao_num = $mycznum->where($where)->count();//总条数

        if ($chexiao_num >= 5){
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //您当天撤销操作过于频繁，请明天再进行尝试。

        }
        $rs = $mycznum->where(array('id' => $id))->update(array('status' => 3));
        if ($rs) {
            Tool_Fnc::ajaxMsg($GLOBALS['MSG']['SMS_SUCCESS'],1,array('id' => $rs));//操作成功
        } else {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //操作失败
        }
    }
    //充值汇款
    public function HuikuanAction()
    {
        $id = '37820';//$_GET['id'];
        $this->_ajax_islogin();

        if (!$id) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //参数错误
        }
        $re = new RechargeModel();
        $mycz = $re->where(array('id' => $id))->fRow();
        if (!$mycz) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //充值订单不存在
        }

        if ($mycz['userid'] != $this->mCurUser['uid']) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //非法操作
        }

        if ($mycz['status'] != 0) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //订单已经处理过
        }

        $rs = $re->where(array('id' => $id))->update(array('status' => 2));
        if ($rs) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1); //操作成功
        } else {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0); //操作失败
        }
    }
    //查看支付方式
    public function myseeAction()
    {
        $id =$_GET['id'];
        $mysee = new RechargeModel();
        $myseetype = new RechargetypeModel();
        if ($id) {
            $mycz =$mysee->where(array('id' => $id))->fRow();
            if (!$mycz) {
                $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0);
            }
            $myczType =$myseetype->where(array('id' => $mycz['bank_id']))->fRow();
            foreach($myczType as $k=>$v){
                $myczType[$k]['image']= "https://firecoin.oss-cn-shenzhen.aliyuncs.com".$v['img'];
            }
            $this->assign('myczType', $myczType);
            $this->assign('mycz', $mycz);
            $this->display($mycz['type']);
        } else {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 0);

        }
    }
}
