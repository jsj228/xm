<?php

class Ajax_AuthController extends Ajax_BaseController
{
    //1111
    # 启用 SESSION
    protected $_auth = 1;
    # 注册发送短信接口key
    protected $_sendMsgKey = 'regsendcode';



    //登入发送短信或语音$GLOBALS['MSG']['MESSAGE_USED_QUCKLY']
    public function sendregmsgAction()
    {
        $data = Tool_Request::post();
        if(isset($_SESSION['phone']) && $_SESSION['phone']!=$data['account'])
        {
            $this->ajax($GLOBALS['MSG']['ILLEGAL']);
        }
        $area= $this->mCurUser['area']?:$_SESSION['area'];
        $data['account']= $this->mCurUser['mo']?: $data['account'];
        $key = 'resetPwdsError' .$_SESSION['uid'];
        $redis = Cache_Redis::instance();
        $count = $redis->get($key);
        //  show($count);
        if($count >= 5)
        {
            $this->ajax($GLOBALS['MSG']['FILEERROR_MIN'],0,'vcode');//輸入錯誤次數過多，請稍後再試
        }


        if ($_POST['action'] == 8 && $area != '+86') {
            $this->ajax($GLOBALS['MSG']['GUOJI_VOICE'], 0, 'vcode');//国际语音暂不支持
        }
        // 实例化
        $validatelogicModel = new ValidatelogicModel();
        if(!isset($data['back']))
        {
           $data = [
               'phone'     =>$data['account'],
               'captcha'     =>$data['captcha'],
               'area'       =>$area
           ];
            // 调用login场景，check方法验证
            $result  = $validatelogicModel->scene('phone')->check($data);

        }
        elseif($_SESSION['confirm']== 'confirm'||isset($data['back']))
        {
            $data = [
                'phone'   =>$data['account'],
                'area' => $area
            ];
            // 调用login场景，check方法验证
            $result  = $validatelogicModel->scene('back')->check($data);
        }

        // 如果为空 ，则报错，并输出错误信息
        if(!$result||$validatelogicModel->getError())
        {


            foreach ($validatelogicModel->getError() as $k => $v)
            {
                // 错误信息
                $errorData  = $v;
                // 错误字段
                $errorM  = $k;
            }
            $this->ajax($errorData,0,$errorM);
        }

        $userMo = new UserModel();
        $user = $userMo->where(['mo'=>$data['phone']])->fRow();

        // action==11 是找回密码 action==8语音
        if ($_POST['action'] && $_POST['action'] == 11)
        {
            $type = '11';
        }
        else if ($_POST['action'] && $_POST['action'] == 8)
        {
            $type = '8';//语音
        }
        else
        {
            $type = '1';//短信
        }
        $num = 0;//$_POST['num'];
        if (!$type = abs($type))
        {

            $this->ajax($GLOBALS['MSG']['PARAM_ERROR']);//参数错误
        }
        # 验证登录
        $name  = '登入或';
        $time  = time();
        $start = $time - 3600;
        $count = PhoneCodeModel::getInstance()->where("mo = {$data['phone']} and area='$area' and ctime >= {$start} and ctime <= {$time} and action = {$type}")->count();
        if ($count >= 15) $this->ajax($GLOBALS['MSG']['SMS_TO_VOICE_LATER'],0);//短信过于频繁，请稍後再試

        if (PhoneCodeModel::regverifiTime($data['phone'], $type,$area))
        {
            $sms = PhoneCodeModel::sendregCode($data['phone'], $type, $area, $num);//短信
            $code = $sms['code'];

            if ($code == '200')
            {  //发送成功删除关联
                unset($_SESSION['confirm']);
                if(MOBILE_CODE){
                    $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'], 1, ''); //发送成功
                }
                $this->ajax($sms['msg'], 0, 'vcode'); //演示模式发送成功
            }
            else
            {
                $this->ajax($GLOBALS['MSG']['SMS_FAIL'],0);//发送失败
            }

        }
        else
        {
            $this->ajax($GLOBALS['MSG']['SMS_WAIT_SEC'],0);//请您过60秒再点击发送
        }
    }

    //手机实名
    public function phoneAction()
    {
        //是否登入
        if (empty($this->mCurUser))
        {
            $data['reUrl'] = '/?login';
            $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        $AutonymMO = new AutonymModel;
        $user = $AutonymMO->where("uid = '{$this->mCurUser['uid']}'")->fRow();
        if($user && ($user['status']==1||$user['status']==2||$user['status']==3))
        {
            $this->ajax($GLOBALS['MSG']['ALREADY_FINISH_MESSAGE'], 1);//你已完善过资料
        }else
        {
            $postData = Tool_Request::post();

            $validateMo  = new ValidatelogicModel();
            $result = $validateMo->scene('telphone')->check($postData);
            if(!$result||$validateMo->getError())
            {
                foreach ($validateMo->getError() as $v)
                {
                    $this->ajax($v,0);
                }
            }
            $result1 = $validateMo->checkCard($postData['idcard'],$this->mCurUser['uid']);
            if(!$result1)
            {
                 $this->ajax($GLOBALS['MSG']['CARD_ALREADY_IS_USED'],0);//證件號已被使用
            }
            //验证身份证
            if(empty($postData['idcard']) || ($postData['cardtype'] == 1 && !$validateMo->Idcard($postData['idcard']))){
                 $this->ajax($GLOBALS['MSG']['CARD_TYPE_ERROR'],0);//證件格式不正確
            }
            //上传照片
          /*  if(!empty($postData['base']))
            {
                $tMO =new Tool_UploadOne();
                $frontFace=$tMO->uploadOne($postData['base']);
            }*/
            if(!empty($postData['baseyi']) && !empty($postData['baseer']) && !empty($postData['basesan']))
            {
                //执行Oss上传
                $path = 'upload/project/';
                $tMO = new Tool_Oss();

                $frontFace = $tMO->uploadOne($path.date('Ymd') . '/' . uniqid().md5($this->mCurUser['uid']) . '.'.'jpg',$postData['baseyi']);
                $backFace=$tMO->uploadOne($path.date('Ymd') . '/' . uniqid().md5($this->mCurUser['uid']) . '.'.'jpg',$postData['baseer']);
                $handkeep=$tMO->uploadOne($path.date('Ymd') . '/' . uniqid().md5($this->mCurUser['uid']) . '.'.'jpg',$postData['basesan']);

//                $tMO =new Tool_UploadOne();
//                $url = '';
//                $frontFace=$tMO->uploadOne($postData['baseyi'],$url);
//                $backFace=$tMO->uploadOne($postData['baseer'],$url);
//                $handkeep=$tMO->uploadOne($postData['basesan'],$url);

                if(!$frontFace || !$backFace || !$handkeep)
                {
                    $this->ajax($tMO->getError(),0);
                }

                $AutonymMO = new AutonymModel;
                $time=time();
                $tData = array(
                    'uid' => $this->mCurUser['uid'],
                    'name' => $postData['name'],
                    'cardtype' => $postData['cardtype'],
                    'idcard' => $postData['idcard'],
                    'frontFace' => $frontFace,
                    'backFace' => $backFace,
                    'handkeep' =>  $handkeep,
                    'created' =>$time,
                    'status'  =>1
                );
                $activeId = $AutonymMO->query("select * from activity where name='注册有礼'");

                $actmo = new UserRewardModel;
                $actRecord = $actmo->where(array('uid'  => $this->mCurUser['uid'], 'type' =>1))->fList();//注册赠送记录

                if (!empty($actRecord)&&$activeId[0]['status'] == 1 && $_SERVER['REQUEST_TIME'] > $activeId[0]['start_time'] && $_SERVER['REQUEST_TIME'] < $activeId[0]['end_time'])
                {
                    //实名有礼
                    $actmo = new UserRewardModel;
                    $actId=$actmo->where(array('uid'=> $this->mCurUser['uid'],'type'=>2))->fList();
                    if($actId){//有,不加mcc
                        if (!$AutonymMO->insert($tData))
                        {
                            //show($AutonymMO->getLastSql());
                            $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                        }
                        else
                        {
                            $this->ajax($GLOBALS['MSG']['SMS_SHENHE'],1);
                        }
                    }else{//无,加10mcc
                        $AutonymMO->begin();
                        if (!$AutonymMO->insert($tData))
                        {
                            $AutonymMO->back();
                            $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                        }
                        else{
                            $forwarddata = array(
                                'uid'        => $this->mCurUser['uid'],
                                'number_au' => 10,
                                'type'       => 2,
                                'updated'    => $_SERVER['REQUEST_TIME']
                            );
                            if (!$actmo->where(array('uid' => $this->mCurUser['uid']))->update($forwarddata))
                            {
                                $AutonymMO->back();
                                $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                            }
                            else{
                                $userMO = new UserModel();
                                $coin_data = array(
                                    'mcc_over' => 10
                                );
                                if (!$userMO->safeUpdate($this->mCurUser, $coin_data))
                                {
                                    $AutonymMO->back();
                                    $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                                }
                                $AutonymMO->commit();
                                $this->ajax($GLOBALS['MSG']['SMS_SHENHE'],1);
                            }
                        }
                    }
                }
                else{//普通实名
                    if (!$AutonymMO->insert($tData))
                    {
                        //show($AutonymMO->getLastSql());
                        $this->ajax( $GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                    }
                    else
                    {
                        $this->ajax($GLOBALS['MSG']['SMS_SHENHE'],1);
                    }
                }
            }
            else
            {
                $this->ajax( $GLOBALS['MSG']['CARD_PHOTO_NEED_ALL'],0);//證件照片需上传完整
            }
        }

    }

    /**
       *   手机重新实名认证
       */
     public  function mobilephoneAction(){
        if (empty($this->mCurUser))
        {
            $data['reUrl'] = '/?login';
            $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        //权限||上一次提交的数据
        $AutonymMO = new AutonymModel;
        $time=time();
        $user = $AutonymMO->where("uid = {$this->mCurUser['uid']}")->fRow();

      //  print_r($user);die;
        if($user && ($user['status']==2))
        {
             $this->ajax($GLOBALS['MSG']['ALREADY_CERTIFIED_SUCCESSFUL'], 1);//你已认证成功
        }
        else
        {
            //审核中和认证失败进来
            $postData = Tool_Request::post();
            // 检验数据
            $validateMo  = new ValidatelogicModel();
            $result = $validateMo->scene('sphone')->check($postData);
            if(!$result||$validateMo->getError())
            {
                foreach ($validateMo->getError() as $v)
                {
                     $this->ajax($v,0);
                }
            }
            $result1 = $validateMo->checkCard($postData['idcard'],$this->mCurUser['uid']);
            if(!$result1)
            {
                 $this->ajax($GLOBALS['MSG']['CARD_ALREADY_IS_USED'],0);//證件號已被使用
            }
            //验证身份证
            if(empty($postData['idcard']) || ($postData['cardtype'] == 1 && !$validateMo->Idcard($postData['idcard'])))
            {
                 $this->ajax($GLOBALS['MSG']['CARD_TYPE_ERROR'],0);//證件格式不正确
            }

            //执行Oss上传
            $path = 'upload/project/';
            $tMO = new Tool_Oss();

            $frontFace = $postData['baseyi']?$tMO->uploadOne($path.date('Ymd') . '/' . uniqid().md5($this->mCurUser['uid']) . '.'.'jpg',$postData['baseyi']):0;
            $backFace=$postData['baseer']?$tMO->uploadOne($path.date('Ymd') . '/' . uniqid().md5($this->mCurUser['uid']) . '.'.'jpg',$postData['baseer']):0;
            $handkeep=$postData['basesan']?$tMO->uploadOne($path.date('Ymd') . '/' . uniqid().md5($this->mCurUser['uid']) . '.'.'jpg',$postData['basesan']):0;

//            $tMO =new Tool_UploadOne();
//            $frontFace=$tMO->uploadOne($postData['baseyi'],$url='');
//            $backFace=$tMO->uploadOne($postData['baseer'],$url='');
//            $handkeep=$tMO->uploadOne($postData['basesan'],$url='');

            if((!$frontFace&&$postData['baseyi']) || (!$backFace&&$postData['baseer'])  ||  (!$handkeep&&$postData['basesan']))
            {
                $this->ajax($tMO->getError(),0);
            }
            //print_r($_FILES);die;
            //重新认证没有选择图片进来
            if($postData['baseyi']==''&& $postData['baseer']=='' && $postData['basesan']=='')
            {

                $tData = array(
                    'uid' => $this->mCurUser['uid'],
                    'name' => $postData['name'],
                    'cardtype' => $postData['cardtype'],
                    'idcard' => $postData['idcard'],
                    'created' =>$time,
                    'status'  =>1
                );
            }else{
                //只要上传一张就进来
                $time=time();
                if($user && ($user['status']==1||$user['status']==3))
                {
                    //如果只更改一张或两张 没有跟改的就把路径重新update
                    //正面

                    if(empty($frontFace)&& empty($postData['baseyi']))
                    {
                        $frontFace =$user['frontFace'];
                    }
                    //背面
                    if(empty($backFace)&& empty($postData['baseer']))
                    {
                        $backFace = $user['backFace'];

                    }
                    //手持
                    if(empty($handkeep)&& empty($postData['basesan']))
                    {
                        $handkeep =$user['handkeep'];
                    }
                    //组装数据
                    $tData = array(
                        'uid' => $this->mCurUser['uid'],
                        'name' => $postData['name'],
                        'cardtype' => $postData['cardtype'],
                        'idcard' => $postData['idcard'],
                        'frontFace' => $frontFace,
                        'backFace' => $backFace,
                        'handkeep' =>  $handkeep,
                        'created' =>$time,
                        'status'  =>1
                    );
                }

            }
            if(!$AutonymMO->where("id={$user['id']}")->update($tData))
            {
                $this->ajax($GLOBALS['MSG']['CARD_PHOTO_NEED_ALL'],0);//證件照片需上传完整
            }
              $this->ajax($GLOBALS['MSG']['SMS_SHENHE'],1);
        }


        //$this->showMsg('资料提交成功,审核时间为1-3个工作日，请您耐心等待!', '/user/index');
    }

     /*
      *
      * 短连接
      */

    public function short_urlAction(){
        $url = "http://www.local.huocoin.com/?regfrom=yp979yp77bx&alert=register";
        $short = new Tool_ShortUrl();
        $url = $short->short($url);
        $this->ajax($url,1);
    }


    private function setCoinList($type)
    {
        $coinPairMo = User_CoinModel::getInstance();
        $coinList   = $coinPairMo->field('name coin_from, display,in_status,out_status')->getList();
        $this->assign('coinList', $coinList);
        return $coinList;
    }

    private function coinStatistic($coin, $type='out')
    {
        $exchangeMo = Exchange_BaseModel::getInstance();

        $pSql      = "SELECT MIN(`updated`) date, COUNT(*) times, SUM(`number`) number FROM exchange_{$coin} where status='成功' and uid =? and opt_type='{$type}' ";
        $tVals     = array($this->mCurUser['uid']);
        $statistic = $exchangeMo->query($pSql, $tVals);
        $statistic[0]['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', $statistic[0]['number']), '.');
        if ($statistic[0]['times']>0)
        {
            $data = sprintf($GLOBALS['MSG']['USER_COIN_STATISTIC_MSG'],$_SESSION['user']['realInfo']['name'], date('Y-m-d H:i:s', $statistic[0]['date']), $coin, $statistic[0]['times'], $statistic[0]['number']);
        }
        else
        {
            $data = '';
        }
        return $data;
    }

    //提币
    public function coinoutAction($page=1)
    {
        $this->_ajax_islogin();
        $coinList = $this->setCoinList('out');

        $_POST     = array_map('strip_tags', $_POST);
        $start    = $_POST['startTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_POST['startTime'])?$_POST['startTime']:'';
         $end   =   $_POST['endTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_POST['endTime'])?$_POST['endTime']:'';

        $coin     = $_POST['coin'];
        $days     = intval($_POST['days']);

        $page     = max(intval($page), 1);
        $pageSize = isset($_POST['size']) ? intval($_POST['size']) : 8;

        if (!$coin || !preg_match('/^[a-z]+$/i', $coin))
        {
            $coin = $coinList[0]['coin_from'];
        }

        $where = array('uid='.$this->mCurUser['uid'], 'opt_type="out"');

        if($days && $days>0)
        {
            $where[] = 'created >= ' . (strtotime(sprintf('-%d days', $days-1))-time()+strtotime('today'));
        }

        if (trim($start))
        {
            $where[] = 'created >= ' . strtotime($start);
        }

        if (trim($end))
        {
            $where[] = 'created <= ' . strtotime($end);
        }

        $where = implode(' and ', $where);

        $exchangeMo = Exchange_BaseModel::getInstance();
        if (!$_POST['excel'] ) {
            $tSql = sprintf('SELECT `id`,`created`,`wallet`,`txid`,`is_out`,`number`,`status`,`confirm` FROM `exchange_%s` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
        }else{
            $tSql = sprintf('SELECT `id`,`created`,`wallet`,`txid`,`is_out`,`number`,`status`,`confirm` FROM `exchange_%s` WHERE %s ORDER BY id desc ', $coin, $where);
        }
        $list  = $exchangeMo->query($tSql);


        $curCoinIdx = array_search($coin, array_column($coinList, 'coin_from'));
        $coinInfo[$curCoinIdx] = User_CoinModel::getInstance()->where(array('name'=>$coin))->fRow();

        //页码
        $mo = 'Exchange_'.ucfirst($coin).'Model';
        $count = $mo::getInstance()->where($where)->count();
       // show($mo::getInstance()->getlastsql());
        $tPage = new Tool_Page($count, $pageSize);

        $pageinfo = $tPage->show();

        $statusMap = array(
            '待审批'=>[0, $GLOBALS['MSG']['PENDING_AUDIT']],
            '等待'=>[1, $GLOBALS['MSG']['WAIT_FOR_COINOUT']],
            '成功'=>[2, $GLOBALS['MSG']['SUCCEED']],
            '已取消'=>[3, $GLOBALS['MSG']['CANCELED']],
            '确认中' => [4, $GLOBALS['MSG']['CONFORM']],
        );

        foreach ($list as &$v)
        {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
            $v['is_out'] = $v['is_out']? $GLOBALS['MSG']['IN_PLATFORM']: $GLOBALS['MSG']['OUT_PLATFORM'];//'平台内':'平台外'

            if($v['confirm']==0 && $v['status']=='等待' && Tool_Math::comp($v['number'], $coinInfo[$curCoinIdx]['out_limit'])==1)
            {
                $v['statusCode'] = $statusMap['待审批'][0];
                $v['status'] = $statusMap['待审批'][1];
            }
            elseif(isset($statusMap[$v['status']]))
            {
                $v['statusCode'] = $statusMap[$v['status']][0];
                $v['status'] = $statusMap[$v['status']][1];
            }


        }
        unset($v);



        //导出excel
        if($_POST['excel'])
        {
            $excelHeader = array($GLOBALS['MSG']['RECORD_ID'], $GLOBALS['MSG']['COINOUT_TIME'], $GLOBALS['MSG']['RECEIVE_ADDRESS'], 'TXID', $GLOBALS['MSG']['ADDRESS_OWNER'], $GLOBALS['MSG']['NUMBER'], $GLOBALS['MSG']['STATUS']);
            Tool_Fnc::excel($excelHeader, $list, array('filename'=>$GLOBALS['MSG']['COINOUT_RECORD'], 'title'=>$GLOBALS['MSG']['COINOUT_RECORD']));
        }


        //统计
        $statistic = $this->coinStatistic($coin, 'out');

        //格式化用户信息
        foreach ($this->mCurUser as $k => &$v)
        {
            if(stripos($k, '_over') || stripos($k, '_lock'))
            {
                $v = trim(preg_replace('/(\.\d*?)0+$/', '$1', $v), '.');
            }
        }



        //实名信息
        $realInfo = AutonymModel::getInstance()->where(array('uid'=>$this->mCurUser['uid']))->fRow();
        $userInfo = $this->mCurUser;
        $userInfo['realInfo'] = $realInfo;


        //赠送币
        $giveCoin =  UserModel::getInstance()->query("SELECT sum(number_au) auTotal, sum(number_reg) regTotal FROM user_reward WHERE uid={$this->mCurUser['uid']} and coin ='$coin'");
        $userInfo['giveCoin'] = $giveCoin?Tool_Math::add($giveCoin['auTotal'], $giveCoin['regTotal']):0;

        $data = array(
            'statistic'=>  $statistic,
            'coinInfo' =>  $coinInfo,
            'pageinfo' =>  $pageinfo,
            'list'     =>  $list,
            'coin'     =>  $coin,
            'user'     =>  $userInfo
            );
        $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'],1,$data);

    }

   //委托币名
    private function setTrustCoinList()
    {
        $coinPairMo = Coin_PairModel::getInstance();
        $coinList   = $coinPairMo->field('coin_from, display')->group('coin_from')->fList();
        $this->assign('coinList', $coinList);
        return $coinList;
    }
   //委托接口
    public function trustAction($page=1)
    {
        $coinList = $this->setTrustCoinList();
        $_POST     = array_map('strip_tags', $_POST);
        $days     = $_POST['days'] = intval($_POST['days']);
        $start    = $_POST['startTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_POST['startTime'])?$_POST['startTime']:'';
        $end      = $_POST['endTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_POST['endTime'])?$_POST['endTime']:'';
        $coin     = &$_POST['coin'];
        $flag     = $_POST['flag'] = intval($_POST['flag']);
        $page     = max(intval($page), 1);
        $pageSize = isset($_POST['size']) ? intval($_POST['size']) :10;

        if (!$coin || !preg_match('/^[a-z]+$/i', $coin))
        {
            $coin = $coinList[0]['coin_from'];
        }

        $where = array('uid='.$this->mCurUser['uid']);

        if($days && $days>0)
        {
            $where[] = 'created >= ' . (strtotime(sprintf('-%d days', $days-1))-time()+strtotime('today'));
        }

        if (trim($start))
        {
            $where[] = 'created >= ' . strtotime($start);
        }

        if (trim($end))
        {
            $where[] = 'created <= ' . strtotime($end);
        }


        switch ($flag)
        {
            case 2:$where[] = 'flag="buy"';
                break; //买
            case 3:$where[] = 'flag="sale"';
                break; //卖
            case 4:$where[] = 'status=2';
                break; //全部成交
            case 5:$where[] = 'status=1';
                break; //部分成交
            case 6:$where[] = 'status=0';
                break; //未成交
            case 7:$where[] = 'status=3';
                break; //已撤銷
        }
        $where = implode(' and ', $where);
        $trustCoinMo = Order_CoinModel::getInstance();
        $pData = $trustCoinMo->getPrepareData(array('coin_from' => $coin));
        $where .= ' and '. $pData['str'];
        if (!$_POST['excel']) {
            $tSql = sprintf('SELECT * FROM `trust_%scoin` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
        }else{
            $tSql = sprintf('SELECT * FROM `trust_%scoin` WHERE %s ORDER BY id desc ', $coin, $where);

        }
        $list = $trustCoinMo->query($tSql, $pData['values']);
        //show($list);
        foreach ($list as $k=>&$v)
        {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);

            //异常数据(针对屏蔽数据)
            if($v['id']=='861' && $this->mCurUser['uid'] == '13232513')
            {
                unset($list[$k]);
            }
        }
        unset($v);
        //页码
        $count = $trustCoinMo->query(sprintf('SELECT count(*) cc FROM `trust_%scoin` WHERE %s', $coin, $where), $pData['values']);
        $tPage = new Tool_Page($count[0]['cc'], $pageSize);
       //   show($trustCoinMo->getlastsql());
        $pageinfo = $tPage->show();

        //导出excel
        if($_POST['excel'])
        {
            $excelHeader = array($GLOBALS['MSG']['TRUST_TIME'],
                $GLOBALS['MSG']['COIN_TYPE'],
                $GLOBALS['MSG']['FLAG_TYPE'],
                $GLOBALS['MSG']['TRUST_PRICE'],
                $GLOBALS['MSG']['TRANSACTION_NUMBER'],
                $GLOBALS['MSG']['NOT_TRANSACTION_NUMBER'],
                $GLOBALS['MSG']['STATUS']);
            //$excelHeader = array('委託時間', '幣種', '類型', '委託價格', '成交數量', '尚未成交數量', '狀態');
            $outData = array();
            foreach($list as $v)
            {
                $outData[] = array(
                    $v['created'],
                    $v['coin_from'],
                    $v['flag']=='buy'? $GLOBALS['MSG']['BUY']: $GLOBALS['MSG']['SALE'],//'买入':'卖出'
                    $v['price'].strtoupper($v['coin_to']),
                    $v['numberdeal'],
                    $v['numberover'],
                    //$v['status']==0?'未成交':($v['status']==1?'部分成交':($v['status']==2?'全部成交':'已经撤销'))
                    $v['status'] == 0 ? $GLOBALS['MSG']['NOT_FINISH_TRANSACTION'] : ($v['status'] == 1 ? $GLOBALS['MSG']['PART_FINISH_TRANSACTION'] : ($v['status'] == 2 ? $GLOBALS['MSG']['ALL_FINISH_TRANSACTION'] : $GLOBALS['MSG']['ALREADY_CANCEL']))
                );
            }
            Tool_Fnc::excel($excelHeader, $outData, array('filename'=> $GLOBALS['MSG']['TRUST_RECORD'], 'title'=> $GLOBALS['MSG']['TRUST_RECORD']));
        }
        $coinname=Coin_PairModel::getInstance()->where(array('coin_from'=> $coin))->fList();

        $data = array(

            'pageinfo' =>  $pageinfo,
            'coinda'   =>  $coinname[0]['coin_from'],
            'list'     =>  $list,
            'post'     =>  $_POST,

        );
       $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'],1,$data);

    }

    //成交
    public function dealAction($page=1)
    {
        $coinList = $this->setTrustCoinList();
        $_GET     = array_map('strip_tags', $_GET);
        $days     = $_POST['days'] = intval($_POST['days']);
        $start    = $_POST['startTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_POST['startTime'])?$_POST['startTime']:'';
        $end      = $_POST['endTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_POST['endTime'])?$_POST['endTime']:'';
        $coin     = &$_POST['coin'];
        $flag     = $_POST['flag'] = $_POST['flag']?intval($_POST['flag']):1;
        $page     = max(intval($page), 1);
        $pageSize = isset($_POST['size']) ? intval($_POST['size']) : 10;

        if (!$coin || !preg_match('/^[a-z]+$/i', $coin))
        {
            $coin = $coinList[0]['coin_from'];
        }

        $where = array();

        if($days && $days>0)
        {
            $where[] = 'created >= ' . (strtotime(sprintf('-%d days', $days-1))-time()+strtotime('today'));
        }

        if (trim($start))
        {
            $where[] = 'created >= ' . strtotime($start);
        }

        if (trim($end))
        {
            $where[] = 'created <= ' . strtotime($end);
        }



        switch ($flag)
        {
            case 2:$where[] = 'buy_uid=' . $this->mCurUser['uid'];
                break; //买
            case 3:$where[] = 'sale_uid=' . $this->mCurUser['uid'];
                break; //卖
            default:
                $where[] = sprintf('(buy_uid=%d or sale_uid=%d)', $this->mCurUser['uid'], $this->mCurUser['uid']);

        }


        $where = implode(' and ', $where);

        $trustCoinMo = Order_CoinModel::getInstance();
        if (!$_POST['excel']) {
            $tSql = sprintf('SELECT * FROM `order_%scoin` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
        }else{
            $tSql = sprintf('SELECT * FROM `order_%scoin` WHERE %s ORDER BY id desc ', $coin, $where);
        }
        $list = $trustCoinMo->query($tSql);
        $arr=array();
        foreach ($list as $k=>&$v)
        {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
            $v['flag'] = $v['buy_uid']==($this->mCurUser['uid'])&& $flag<>3? $GLOBALS['MSG']['BUY'] : $GLOBALS['MSG']['SALE']; //'买入':'卖出'
            //异常数据(针对屏蔽数据)
            if($v['sale_uid']=='13232513' && $v['created']=='1511530237' && $this->mCurUser['uid'] == '13232513')
            {
                unset($list[$k]);
            }
            if($flag==1) {
                array_push($arr, $list[$k]);
                if ($v['buy_uid'] == $v['sale_uid']) {
                    $da = $list[$k];
                    $da['flag'] = $GLOBALS['MSG']['SALE'];
                    array_push($arr, $da);
                }
            }

        }
        unset($v);
        unset($da);
        if ($flag == 1) {
            $list = $arr;
        }
        unset($arr);
        $count = $trustCoinMo->designTable($coin)->where($where)->count();
        $tPage = new Tool_Page($count, $pageSize);

        $pageinfo = $tPage->show();


        //导出excel
        if($_POST['excel'])
        {
            $excelHeader = array($GLOBALS['MSG']['ORDER_NUMBER'],
                $GLOBALS['MSG']['ORDER_SUCCESS_TIME'],
                $GLOBALS['MSG']['COIN_TYPE'],
                $GLOBALS['MSG']['FLAG_TYPE'],
                $GLOBALS['MSG']['ORDER_SUCCESS_PRICE'],
                $GLOBALS['MSG']['TRANSACTION_NUMBER'],
                $GLOBALS['MSG']['ORDER_SUCCESS_MONEY']);
            //$excelHeader = array('訂單號', '成交時間', '幣種', '類型', '成交價格', '成交數量', '成交金額');
            $outData = array();
            foreach($list as $v)
            {
                $outData[] = array(
                    $v['id'],
                    $v['created'],
                    $v['coin_from'],
                    $v['flag'],//'买入':'卖出'
                    // $v['buy_uid']==$this->mCurUser['uid']? $GLOBALS['MSG']['BUY'] : $GLOBALS['MSG']['SALE'], //'买入':'卖出'
                    $v['price'].strtoupper($v['coin_to']),
                    $v['number'],
                    Tool_Math::mul($v['price'], $v['number']).strtoupper($v['coin_to'])
                );
            }
            Tool_Fnc::excel($excelHeader, $outData, array('filename'=> $GLOBALS['MSG']['ORDER_SUCCESS_RECORD'], 'title'=> $GLOBALS['MSG']['ORDER_SUCCESS_RECORD']));
        }
        $coinname = Coin_PairModel::getInstance()->where(array('coin_from' => $coin))->fList();

        //数据返回
        $data = array(
            'coinda'   =>  $coinname[0]['coin_from'],
            'pageinfo'   =>  $pageinfo,
            'user'       =>  $this->mCurUser,
            'list'       =>  $list,
            'post'       =>  $_POST,
        );
        $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'],1,$data);

    }

    //小程序第一次实名
    /*public function nophoneAction()
    {
        $postData = Tool_Request::post();

        if(!$postData['key']||$postData['key']!='nFq4Xv6bjcu7BbPq')
        {
            $this->ajax('非法操作', 0);
        }
        $host = Yaf_Registry::get("config")->sm->host;
        $AutonymMO = new AutonymModel;
        $user = $AutonymMO->where("uid = '{$postData['uid']}'")->fRow();

        if($user && ($user['status']==1||$user['status']==2||$user['status']==3))
        {
            $this->ajax($GLOBALS['MSG']['ALREADY_FINISH_MESSAGE'], 1);//你已完善过资料
        }else
        {
             if(($postData['name'])&&$postData['cardtype']&&$postData['idcard'])
            {
                if(ctype_space($postData['name'])){
                   $this->ajax('请勿输入非法字符',0);
                }

                $validateMo  = new ValidatelogicModel();
                $result = $validateMo->scene('sphone')->check($postData);
                if(!$result||$validateMo->getError())
                {
                    foreach ($validateMo->getError() as $v)
                    {
                        $this->ajax($v,0);
                    }
                }
                $result1 = $validateMo->checkCard($postData['idcard'],$postData['uid']);

                if(!$result1)
                {
                    $this->ajax($GLOBALS['MSG']['CARD_ALREADY_IS_USED'],0);//證件號已被使用
                }
                //验证身份证
                if(empty($postData['idcard']) || ($postData['cardtype'] == 1 && !$validateMo->Idcard($postData['idcard']))){
                    $this->ajax($GLOBALS['MSG']['CARD_TYPE_ERROR'],0);//證件格式不正確
                }

            }

            $frontFace=$_FILES["frontFace"];//正面
            $backFace=$_FILES["backFace"];
            $handkeep=$_FILES["handkeep"];
             //有传图片
            if($frontFace['name']||$backFace['name']||$handkeep['name'])
            {
                $tMO = new Tool_Uploadimg();

                if ($frontFace)
                {
                        $frontFace = $tMO->autony($frontFace, $postData['extension']);

                        if (!$frontFace)
                        {
                            $this->ajax($tMO->getError(), 0);
                        }
                        $frontFace = substr($frontFace, 1);
                        $this->ajax('', 1, $host.$frontFace);
                }
                else if ($backFace)
                {

                        $backFace = $tMO->autony($backFace, $postData['extension']);
                        if (!$backFace) {
                            $this->ajax($tMO->getError(), 0);
                        }
                        $frontFace = substr($backFace, 1);
                        $this->ajax('', 1, $host.$frontFace);
                }
                else if ($handkeep)
                {

                        $handkeep = $tMO->autony($handkeep, $postData['extension']);
                        if (!$handkeep)
                        {
                            $this->ajax($tMO->getError(), 0);
                        }
                        $handkeep = substr($handkeep, 1);
                        $this->ajax('', 1, $host.$handkeep);
                }
            }
                //组装数据
                $AutonymMO = new AutonymModel;
                $time=time();
                $tData = array(
                    'uid' => $postData['uid'],
                    'name' => trim($postData['name']),
                    'cardtype' => $postData['cardtype'],
                    'idcard' => $postData['idcard'],
                    'frontFace' => $postData['frontFace'],
                    'backFace' => $postData['backFace'],
                    'handkeep' => $postData['handkeep'],
                    'created' =>$time,
                    'status'  =>1
                );

                $activeId = $AutonymMO->query("select * from activity where name='注册有礼'");
                $actmo = new UserRewardModel;
                $actRecord = $actmo->where(array('uid'  => $postData['uid'], 'type' =>1))->fList();//注册赠送记录

                if (!empty($actRecord)&&$activeId[0]['status'] == 1 && $_SERVER['REQUEST_TIME'] > $activeId[0]['start_time'] && $_SERVER['REQUEST_TIME'] < $activeId[0]['end_time'])
                {
                    //实名有礼
                    $actmo = new UserRewardModel;
                    $actId=$actmo->where(array('uid'=> $postData['uid'],'type'=>2))->fList();
                    if($actId){//有,不加mcc
                        if (!$AutonymMO->insert($tData))
                        {
                            //show($AutonymMO->getLastSql());
                            $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                        }
                        else
                        {
                            $this->ajax($GLOBALS['MSG']['SMS_CHONG'],1);
                        }
                    }else{//无,加10mcc
                        $AutonymMO->begin();
                        if (!$AutonymMO->insert($tData))
                        {
                            $AutonymMO->back();
                            $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                        }
                        else{
                            $forwarddata = array(
                                'uid'        => $postData['uid'],
                                'number_au' => 10,
                                'type'       => 2,
                                'updated'    => $time
                            );
                            if (!$actmo->where(array('uid' => $postData['uid']))->update($forwarddata))
                            {
                                $AutonymMO->back();
                                $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                            }
                            else{
                                $userMO = new UserModel();
                                $coin_data = array(
                                    'mcc_over' => 10
                                );
                                if (!$userMO->safeUpdate($this->mCurUser, $coin_data))
                                {
                                    $AutonymMO->back();
                                    $this->ajax($GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                                }
                                $AutonymMO->commit();
                                $this->ajax($GLOBALS['MSG']['SMS_CHONG'],1);
                            }
                        }
                    }
                }
                else{//普通实名
                    if (!$AutonymMO->insert($tData))
                    {

                        $this->ajax( $GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL'],0);//资料提交失败,请重試
                    }
                    else
                    {
                        $this->ajax($GLOBALS['MSG']['SMS_SHENHE'],1);
                    }
                }


        }

    }*/

    //小程序重新实名
   /* public  function xcxphoneAction()
    {
        $postData = Tool_Request::post();
        if(!$postData['key']||$postData['key']!='nFq4Xv6bjcu7BbPq')
        {
            $this->ajax('非法操作', 0);
        }
        $host = Yaf_Registry::get("config")->sm->host;
        //权限||上一次提交的数据
        $AutonymMO = new AutonymModel;
        $time=time();
        $user =$AutonymMO->where("uid ={$postData['uid']}")->fRow();

        if($user && ($user['status']==2))
        {
            $this->ajax($GLOBALS['MSG']['ALREADY_CERTIFIED_SUCCESSFUL'], 1);//你已认证成功
        }
        else
        { //审核中和认证失败进来

            // 检验数据
            if($postData['name']&&$postData['cardtype']&&$postData['idcard'])
            {
                if(ctype_space($postData['name'])){
                    $this->ajax('请勿输入非法字符',0);
                }
                $validateMo  = new ValidatelogicModel();
                $result = $validateMo->scene('sphone')->check($postData);
                if(!$result||$validateMo->getError())
                {
                    foreach ($validateMo->getError() as $v)
                    {
                        $this->ajax($v,0);
                    }
                }
                $result1 = $validateMo->checkCard($postData['idcard'],$postData['uid']);

                if(!$result1)
                {
                    $this->ajax($GLOBALS['MSG']['CARD_ALREADY_IS_USED'],0);//證件號已被使用
                }
                //验证身份证
                if(empty($postData['idcard']) || ($postData['cardtype'] == 1 && !$validateMo->Idcard($postData['idcard']))){
                    $this->ajax($GLOBALS['MSG']['CARD_TYPE_ERROR'],0);//證件格式不正確
                }

            }
            $frontFace=$_FILES["frontFace"];  //正面
            $backFace=$_FILES["backFace"];    //背面
            $handkeep=$_FILES["handkeep"];    //手持
            //重新有提交图片
            if($frontFace['name']||$backFace['name']||$handkeep['name'])
            {
                $tMO =new Tool_Uploadimg();

                if($frontFace)
                {
                    $frontFace=$tMO->autony($frontFace,$postData['extension']);

                    if(!$frontFace)
                    {
                        $this->ajax($tMO->getError(),0);
                    }
                    $frontFace= substr($frontFace,1);
                    $this->ajax('',1,$host.$frontFace);
                }
                else if($backFace)
                {

                    $backFace=$tMO->autony($backFace,$postData['extension']);
                    if(!$backFace)
                    {
                        $this->ajax($tMO->getError(),0);
                    }
                    $frontFace= substr($backFace,1);
                    $this->ajax('',1,$host.$frontFace);
                }
                else if($handkeep)
                {
                    $handkeep=$tMO->autony($handkeep,$postData['extension']);
                    if(!$handkeep)
                    {
                        $this->ajax($tMO->getError(),0);
                    }
                    $handkeep= substr($handkeep,1);
                    $this->ajax('',1,$host.$handkeep);
                }
                }
                    //组装数据
                    $tData = array(
                        'uid' => $postData['uid'],
                        'name' => trim($postData['name']),
                        'cardtype' => $postData['cardtype'],
                        'idcard' => $postData['idcard'],
                        'frontFace' => $postData['frontFace'],
                        'backFace' => $postData['backFace'],
                        'handkeep' => $postData['handkeep'],
                        'created' =>$time,
                        'status'  =>1
                    );


            if(!$AutonymMO->where("id={$user['id']}")->update($tData))
            {
                $this->ajax('提交失败',0);//證件照片需上传完整
            }
        }
        $this->ajax($GLOBALS['MSG']['SMS_SHENHE'],1);
        //$this->showMsg('资料提交成功,审核时间为1-3个工作日，请您耐心等待!', '/user/index');
    }*/
















}
