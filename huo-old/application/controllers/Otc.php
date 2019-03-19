<?php
class OtcController extends Ctrl_Base
{
    //用户c2c
    public function indexAction($page=1)
    {
        $this->_ajax_islogin();
        $C2ctrade = new OctModel();
        $type=$_GET['type'];//1买2卖
        // 当前总记录条数
        isset($_GET['p']) or $_GET['p'] = intval($page);
        //查找otc所有订单
        $data['total']= $C2ctrade->where(['status'=>$type])->count();
        // 获取分页显示
        $tPage = new Tool_Page($data['total'],10);
        $data['pageinfo']= $tPage->show();
        $data['list'] = $C2ctrade->field('*')
            ->where(['status'=>$type])
            ->limit($tPage->limit())
            ->order('id desc')
            ->fList();
        $this->assign('data',$data['list']);
        $this->assign('Page',$data['pageinfo']);
    }
    //用户挂单OTC 添加,编辑广告
    public function otcupAction(){
        $placing=$_GET['placing'];
        $FixedPrice=$_GET['FixedPrice'];
        $max=$_GET['max'];
        $min=$_GET['min'];
        $payTerm=$_GET['payTerm'];
        $lowest=$_GET['lowest'];
        $coin=$_GET['currency'];
        $name=$_GET['tasktime'];
        $alipay=$_GET['alipay'];
        $wxpay=$_GET['wxpay'];
        $bankpay=$_GET['bankpay'];
        $userInfo = $this->model->getUserInfo($this->mCurUser['uid']);
        if($userInfo['idcardauth']<1){
            $this->ajaxReturn(array('status'=>0,'msg'=>'你还未通过实名认证,暂时不能发广告，请先进行实名认证'));
        }
        $account=$this->model->payAccount($this->mCurUser['uid']);
        if(!$account['wechat'] && !$account['alipay'] && !$account['bank']){
            $this->ajaxReturn(array('status'=>0,'msg'=>'你还未绑定支付方式,请先绑定支付方式'));
        }
        $coinValue=$this->model->getCoinValue($name);
        $userCoin = $this->model->getUserCurrency($this->mCurUser['uid'],$name);

        switch($FixedPrice){
            case 1:
                $price=$_GET['price'];
                $coinPrice=$coinValue*0.3+$coinValue;
                if($price==""||$price==null){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'请输入价格'));
                }
                if(!is_numeric($price)){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'价格类型不正确'));
                }
                if($price>$coinPrice){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'价格不合理，不能超过市场价30%'));
                }
                $coinPrice1=$coinValue-$coinValue*0.3;
                if($price<$coinPrice1){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'价格不合理，不能低于市场价30%'));
                }

                $cny['Price'] =$price; //固定价格
                $cny['changePrice']=0;
                break;
            case 2;
                $changeprice=$_GET['changePrice'];
                if($changeprice==""||$changeprice==null){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'请输入溢价'));
                }

                if($changeprice>30){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'溢价不能超过市场价30%'));
                }
                if(!is_numeric(abs($changeprice))){
                    $this->ajaxReturn(array('status'=>0,'msg'=>'溢价只能输入数值'));
                }
                $cny['changePrice']=$changeprice;//交易溢价
                $cny['Price'] =0;
                break;
        }

        if ($payTerm == "" || $payTerm == null) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'请输入付款期限'));
        }
        if($placing==1){
            if($payTerm>30){
                $this->ajaxReturn(array('status'=>0,'msg'=>'付款期限不能超过30分钟'));
            }
            if($payTerm<10){
                $this->ajaxReturn(array('status'=>0,'msg'=>'付款期限不能低于10分钟'));
            }
        }elseif($placing==2){
            if($payTerm>60){
                $this->ajaxReturn(array('status'=>0,'msg'=>'付款期限不能超过60分钟'));
            }
            if($payTerm<30){
                $this->ajaxReturn(array('status'=>0,'msg'=>'付款期限不能低于30分钟'));
            }
        }

        if (!is_numeric($payTerm)) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'付款期限类型不正确'));
        }
        if(!$alipay && !$wxpay && !$bankpay){
            $this->ajaxReturn(array('status'=>0,'msg'=>'付款方式请选择至少一项'));
        }
        if ($max == "" || $max ==null) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'请输入最大额交易'));
        }

        if (!is_numeric($max)||strpos($max,".")!==false) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'最大额交易只能为整数'));
        }
        if($placing==1){
            if($max>'500000'){
                $this->ajaxReturn(array('status'=>0,'msg'=>'最大限额五十万'));
            }

        }
        if($placing==2){
            $maxLast=round($userCoin*$changeprice*$coinValue/100+$userCoin*$coinValue,2);
            if($max>$maxLast){
                $this->ajaxReturn(array('status'=>0,'msg'=>'您账户可用'.strtoupper($name).'最大额度'.$maxLast.$userCoin['name']));
            }
        }

        if ($min == "" || $min ==null) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'请输入最小额交易'));
        }
        if (!is_numeric($min)||strpos($min,".")!==false) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'最小额交易只能为整数'));
        }
        if($min<99){
            $this->ajaxReturn(array('status'=>0,'msg'=>'最小额不能低于100'));
        }
        if ($max < $min) {
            $this->ajaxReturn(array('status'=>0,'msg'=>'最大交易额不能小于最小交易额'));
        }
        if($lowest){
            if(!is_numeric($lowest)||strpos($lowest,".")!==false){
                $this->ajaxReturn(array('status'=>0,'msg'=>'最低价格只能为整数'));
            }
        }
        if($lowest>round($coinValue*$changeprice/100+$coinValue,2)){
            $this->ajaxReturn(array('status'=>0,'msg'=>'设置最低价格不能高于交易价格'));
        }
        $payPassword=$_GET['password'];
        if ($payPassword == "" || $payPassword ==null){
            $this->ajaxReturn(array('status'=>0,'msg'=>'请输入资金密码'));
        }
        if(md5($payPassword)!=$userInfo['paypassword']){
            $this->ajaxReturn(array('status'=>0,'msg'=>'资金密码错误'));
        }

        $arr=array();
        if($alipay){
            array_push($arr,$alipay);
        }
        if($wxpay){
            array_push($arr,$wxpay);
        }
        if($bankpay){
            array_push($arr,$bankpay);
        }
        $cny['name'] = $_GET['tasktime'];    //币种
        $cny['bank']=implode(',',$arr); //付款方式
        $cny['Country']=$coin; //国家
        $cny['userid']=$this->mCurUser['uid']; //用户ID
        $cny['Currencytype']=$_GET['coinht']; //交易货币类型
        $cny['fukuang']=$payTerm; // 付款期限
        $cny['max'] =$_GET['max']; //最大交易额度
        $cny['min'] =$_GET['min']; //最小交易额度
        $cny['lowest']=$lowest;//设置最低价格
        $cny['username']=$userInfo['ni_name']; //用户昵称
        $cny['type'] =$placing;  //交易类型
        $cny['text']=$_GET['text']; // 用户留言
        $cny['content']=$_GET['text1']; //自动回复
        $cny['truename']=$userInfo['truename']; //真实姓名
        // 接收传递的id，如果id不存在，则代表添加广告，如果存在，则代表编辑
        $listId=$_GET['id'];
        if(!$listId){
            $cny['status']=1; //状态
            $cny['time']=time();
            $cny['adnumber']=random();
            switch($placing){
                case 2;
                    $sellListId = $this->model->getListIdbyUserId($this->mCurUser['uid'],$name,2);
                    $config=$this->model->getOtcConfig($name);
                    if($userCoin<$config['limitopen']){
                        $this->ajaxReturn(array('status'=>0,'msg'=>'您的'.strtoupper($name).'不足'.round($config['limitopen'],2).',不能发布广告'));
                    }
                    if($sellListId){
                        $this->ajaxReturn(array('status'=>0,'msg'=>'您的'.strtoupper($name).'在线购买广告已存在，请勿重复发布'));
                    }
                    break;
                case 1;
                    $buyListId = $this->model->getListIdbyUserId($this->mCurUser['uid'],$name,1);
                    $config=$this->model->getOtcConfig($name);
                    if($userCoin<$config['limitopen']){
                        $this->ajaxReturn(array('status'=>0,'msg'=>'您的'.strtoupper($name).'不足'.round($config['limitopen'],2).',不能发布广告'));
                    }
                    if($buyListId){
                        $this->ajaxReturn(array('status'=>0,'msg'=>'您的'.strtoupper($name).'在线购买广告已存在，请勿重复发布'));
                    }
                    break;
            }
            $ret=$this->model->addList($cny);
            if($ret){
                $this->ajaxReturn(array('status'=>1,'msg' => '创建交易成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'创建交易失败'));
            }
        }else{
            $ret =$this->model->editList($listId,$cny);
            if($ret){
                $this->ajaxReturn(array('status'=>1,'msg' => '修改广告成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg' => '未对广告进行修改'));
            }
        }
    }
}
