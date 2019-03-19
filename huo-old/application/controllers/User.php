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
class UserController extends Ctrl_Base
{
    protected $_auth = 3;

    function init()
    {
        parent::init();
    	$this->assign('pageName', $this->_request->action);
    }

    public function indexAction()
    {
        //币列表
        $coinList = User_CoinModel::getInstance()->getList();

        //用户信息
        $userInfo = UserModel::getInstance()->fRow($this->mCurUser['uid']);
        unset($userInfo['uid']);
        //实名
        $realInfo = AutonymModel::getInstance()->where(array('uid' => $this->mCurUser['uid'], 'status' => 2))->fRow();
        $userInfo['realInfo'] = $realInfo;

        //幣交易规则
        $coinPair = Coin_PairModel::getInstance()->field('coin_from')->where('status=1')->fList();
        $coinStatus = array_column($coinPair, 'coin_from');

        if ($userInfo['mo']) {
            if ($userInfo['area'] == '+86') {
                $userInfo['mo'] = preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $userInfo['mo']);
            } else {
                $userInfo['mo'] = substr_replace($_SESSION['user']['mo'], '**', -4, 2);
            }
            //$userInfo['mo'] = preg_replace('/^(\d{3})(\d{4})(\d{4})$/', '$1****$3', $userInfo['mo']);
        }
        if ($userInfo['email'])
        {
                $email_array = explode("@", $userInfo['email']);
                $not= substr_replace($email_array[0], '**', -4, 2);
                $userInfo['email']= $not.'@'.$email_array[1];
        }
        //易币数据
        $yibidata = $this->getyibiuser($this->mCurUser['area'], $this->mCurUser['mo'],$this->mCurUser['email'], '');//获取易币账户余额
        //show($yibidata);
        $yidd = $yibidata['data'];
        if ($yibidata['status'] == 1) {
            unset($yidd['mo']);
            unset($yidd['ext_over']);
            unset($yidd['ext_lock']);
        }
        if ($yibidata['status'] == 0) {//没数据
            $this->assign('if_data', 0);
        } else {
            $this->assign('if_data', 1);
        }
        $yibiuserInfo = $yidd;
        $yibicoinGroup = array();
        $yibicoin = array();
        $yi_coinList = User_CoinModel::getInstance()->where('status=0 and otc=0')->fList();
        foreach ($yi_coinList as $key => &$v) {
            if ($yibidata['status'] == 0) {//没数据
                break;
            }
            //上线的币
            if (in_array($v['name'], $coinStatus) || in_array($v['name'], array('btc','dob'))) {
                //易币数据
                $yibicoin[] = $v['name'];
                if ($yibiuserInfo[$v['name'] . '_over'] > 0 || $yibiuserInfo[$v['name'] . '_lock'] > 0 || $v['name'] == 'btc') {
                    $yibicoinGroup['on']['owned'][] = $v;
                } else {
                    $yibicoinGroup['on']['others'][] = $v;
                }
                $max = min($v['otc_max'], $yibiuserInfo[$v['name'] . '_over']);
                $yibiuserInfo[$v['name'] . '_max'] = sprintf('%.8f', $max);//最大可互转

            }
        }
        //易币数据
        $coinGroup = array();
        foreach ($coinList as $key => &$v) {
            //上线的币
            if (in_array($v['name'], $coinStatus) || in_array($v['name'], array('btc', 'dob'))) {
                if ($userInfo[$v['name'] . '_over'] > 0 || $userInfo[$v['name'] . '_lock'] > 0 || $v['name'] == 'btc') {
                    $coinGroup['on']['owned'][] = $v;
                } else {
                    $coinGroup['on']['others'][] = $v;
                }
                $max = min($v['otc_max'], $userInfo[$v['name'] . '_over']);
                $userInfo[$v['name'] . '_max'] = sprintf('%.8f', $max);//最大可互转

            } //下架的币
            else {
                $coinGroup['off'][] = $v;
            }
        }
        unset($v);
        //折算总资产
        //$allToBtc = UserModel::getInstance()->convertCoin($userInfo, 'btc');
        $newPrice = Coin_PairModel::getInstance()->getCoinPrice();
        $tradearea = Coin_PairModel::getInstance()->field('DISTINCT coin_to')->fList();
        foreach ($tradearea as &$v1) {
            $v1['coin_to'] = '_' . $v1['coin_to'];
        }

        $coinPrice = [];
        //全部币都换算成btc
        foreach ($newPrice as $coin => $area) {
            if ($coin == 'btc') {
                foreach ($area as $k => $v) {
                    $coinPrice[str_replace(array_column($tradearea, 'coin_to'), '', $k)] = array(
                        preg_replace('/.+?_/', '', $k), Tool_Math::format($v['price']),
                    );
                }
            } else {
                $transPirce = $newPrice['btc'][$coin . '_btc']['price'];
                foreach ($area as $k => $v) {
                    $cc = str_replace(array_column($tradearea, 'coin_to'), '', $k);
                    $btcarr = array_keys($coinPrice);
                    if (in_array($cc, $btcarr)) {//如果已经有跳过
                        continue;
                    }
                    $coinPrice[$cc] = array(
                        'btc', Tool_Math::mul($v['price'], $transPirce),
                    );
                }
            }
        }

        //折算总资产
        $allToBtc = Tool_Math::add($userInfo['btc_over'], $userInfo['btc_lock']);//全部折算btc 多比
        if ($yibidata['status'] == 1) {//有数据
            $yibiallToBtc = Tool_Math::add($yibiuserInfo['btc_over'], $yibiuserInfo['btc_lock']);//全部折算btc 易币
            foreach ($coinPrice as $k => $v) {
                $over_add_lock = Tool_Math::add($userInfo[$k . '_over'], $userInfo[$k . '_lock']);
                $coin_to_btc = Tool_Math::mul($over_add_lock, $v[1]);
                $allToBtc = Tool_Math::add($allToBtc, $coin_to_btc);
                if (isset($yibiuserInfo[$k . '_over'])) {
                    $yibi_over_add_lock = Tool_Math::add($yibiuserInfo[$k . '_over'], $yibiuserInfo[$k . '_lock']);
                    $yibi_coin_to_btc = Tool_Math::mul($yibi_over_add_lock, $v[1]);
                    $yibiallToBtc = Tool_Math::add($yibiallToBtc, $yibi_coin_to_btc);
                }
            }
        }
        //折算总资产
        $this->assign('allToBtc', $allToBtc);//多比總資產
        $this->assign('yibiallToBtc', $yibiallToBtc);//易币總資產
        $this->assign('coinPrice', $coinPrice);//最新成交價
        //折算总资产

        //查询黑名单
        $forbidden = UserForbiddenModel::getInstance()->checkForbidden($userInfo['uid']);
        $this->layout('seot', $GLOBALS['MSG']['INFO_USER_DOBI']);//賬戶信息-我的賬戶-多比交易平臺
        $this->assign('coinList', $coinGroup);
        $this->assign('userInfo', $userInfo);
        $this->assign('yibicoinList', $yibicoinGroup);//易币数据
        $this->assign('yibiuserInfo', $yibiuserInfo);//易币数据
        $this->assign('yibicoin', $yibicoin);//易币数据

        $this->assign('forbidden', $forbidden);
        $this->assign('secret', Api_Google_Authenticator::generate_secret_key());//谷歌验证码key
    }
    /**
     * 退出
     */
    public function logoutAction(){
        if(isset($this->mCurUser['uid']))
            Tool_Md5::pwdTradeCheck($this->mCurUser['uid'], 'del');
        session_destroy();
        $redis = Cache_Redis::instance();
        setcookie('reurl', 'del', 1, '/');
        setcookie('WSTK', 'del', 1, '/');
        $redis->del('admin_google_auth_'.$this->mCurUser['uid']);
        $this->showMsg('', '/');
    }

    /*
     * 实名提交
     **/
    /*public  function autonymAction()
    {

        if(empty($this->mCurUser))
        {
            return $this->showMsg($GLOBALS['MSG']['NEED_LOGIN'], '/?login');
        }
        $this->assign('POST', $_POST);
        $AutonymMO = new AutonymModel;
        $user = $AutonymMO->where("uid = '{$this->mCurUser['uid']}'")->fRow();
        if($user && ($user['status']==1||$user['status']==2||$user['status']==3))
        {
            $this->ajax($GLOBALS['MSG']['ALREADY_FINISH_MESSAGE'], 1);//你已完善过资料
        }
        else
        {
            $postData = Tool_Request::post();
            // 检验数据
            $validateMo  = new ValidatelogicModel();
            $result = $validateMo->scene('shiMing')->check($postData);
            if(!$result||$validateMo->getError())
            {
                foreach ($validateMo->getError() as $v)
                {
                    return $this->assign('errorTips', $v);
                }
            }
            $result1 = $validateMo->checkCard($postData['idcard'],$this->mCurUser['uid']);
            if(!$result1)
            {
                return $this->assign('errorTips', $GLOBALS['MSG']['CARD_ALREADY_IS_USED']);//證件號已被使用
            }
             //验证身份证
            if(empty($postData['idcard']) || ($postData['cardtype'] == 1 && !$validateMo->Idcard($postData['idcard']))){
                return $this->assign('errorTips', $GLOBALS['MSG']['CARD_TYPE_ERROR']);//證件格式不正確
            }

            //上传照片
            $frontFace=$_FILES["frontFace"];
            $backFace=$_FILES["backFace"];
            $handkeep=$_FILES["handkeep"];

            if(!empty($frontFace) && !empty($backFace) && !empty($handkeep))
            {
                $tMO =new Tool_Upload();
                $frontFace=$tMO->autony($frontFace);
                $backFace=$tMO->autony($backFace);
                $handkeep=$tMO->autony($handkeep);
                if(!$frontFace || !$backFace || !$handkeep)
                {
                    return $this->assign('errorTips', $tMO->getError());
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
                            $this->assign('errorTips', $GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL']);//资料提交失败,请重試
                        }
                        else
                        {
                            $this->showMsg('', '/user/success');
                        }
                    }else{//无,加10mcc
                        $AutonymMO->begin();
                        if (!$AutonymMO->insert($tData))
                        {
                            $AutonymMO->back();
                            $this->assign('errorTips', $GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL']);//资料提交失败,请重試
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
                            $this->assign('errorTips', $GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL']);//资料提交失败,请重試
                        }
                        else{
                        $userMO = new UserModel();
                        $coin_data = array(
                            'mcc_over' => 10
                        );
                        if (!$userMO->safeUpdate($this->mCurUser, $coin_data))
                        {
                            $AutonymMO->back();
                            $this->assign('errorTips', $GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL']);//资料提交失败,请重試
                        }
                        $AutonymMO->commit();
                        $this->showMsg('', '/user/success');

                        }
                        }
                    }
                }
                else{//普通实名

                    if (!$AutonymMO->insert($tData))
                    {
                        //show($AutonymMO->getLastSql());
                        $this->assign('errorTips', $GLOBALS['MSG']['MESSAGE_SUBMIT_FAIL']);//资料提交失败,请重試
                    }
                    else
                    {
                        $this->showMsg('', '/user/success');
                    }
                }

            }
            else
            {
                $this->assign('errorTips', $GLOBALS['MSG']['CARD_PHOTO_NEED_ALL']);//證件照片需上传完整
            }

        }
        /*  $this->display('realinfo');

    }*/

    /*
     * 重新实名认证
     */
    /*public  function shimingAction()
    {
        if (empty($this->mCurUser))
        {
            return $this->showMsg($GLOBALS['MSG']['NEED_LOGIN'], '/?login');
        }
        //权限||上一次提交的数据
        $AutonymMO = new AutonymModel;
        $time=time();
        $user = $AutonymMO->where("uid = {$this->mCurUser['uid']}")->fRow();
        //print_r($user);die;
        if($user && ($user['status']==2))
        {
            return $this->showMsg($GLOBALS['MSG']['ALREADY_CERTIFIED_SUCCESSFUL'], '/user/index');//你已认证成功
        }
        else
        {
            //审核中和认证失败进来
            $postData = Tool_Request::post();
            // 检验数据
            $validateMo  = new ValidatelogicModel();
            $result = $validateMo->scene('shiMing')->check($postData);
            if(!$result||$validateMo->getError())
            {
                foreach ($validateMo->getError() as $v)
                {
                    return $this->assign('errorTips', $v);
                }
            }
            $result1 = $validateMo->checkCard($postData['idcard'],$this->mCurUser['uid']);
            if(!$result1)
            {
                return $this->assign('errorTips', $GLOBALS['MSG']['CARD_ALREADY_IS_USED']);//證件號已被使用
            }
            //验证身份证
            if(empty($postData['idcard']) || ($postData['cardtype'] == 1 && !$validateMo->Idcard($postData['idcard']))){
                return $this->assign('errorTips', $GLOBALS['MSG']['CARD_TYPE_ERROR']);//證件格式不正确
            }
            $frontFace=$_FILES["frontFace"];
            $backFace=$_FILES["backFace"];
            $handkeep=$_FILES["handkeep"];
            //print_r($_FILES);die;
            //重新认证没有选择图片进来
            if($frontFace['size']==0 && $backFace['size']==0 && $handkeep['size']==0)
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
                $tMO =new Tool_Upload();
                $frontFace=$tMO->autony($frontFace);
                $backFace=$tMO->autony($backFace);
                $handkeep=$tMO->autony($handkeep);
                //只要上传一张就进来
                if(empty($frontFace) || empty($backFace) || empty($handkeep))
                {
                    return $this->assign('errorTips', $tMO->getError());
                }
                $time=time();
                if($user && ($user['status']==1||$user['status']==3))
                {
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
                 $this->assign('errorTips', $GLOBALS['MSG']['CARD_PHOTO_NEED_ALL']);//證件照片需上传完整
            }
        }
          $this->showMsg('','/user/success');
        //$this->showMsg('资料提交成功,审核时间为1-3个工作日，请您耐心等待!', '/user/index');
    }*/
    // 提交成功 页面
    public function successAction()
    {     $status=intval($_GET['status']);
          $AutonymMO = new AutonymModel;
          $list = $AutonymMO->where("uid = '{$this->mCurUser['uid']}'")->fRow();
          $this->assign('list', $list);
          $this->assign('status', $status);
    }
    //实名认证
    public function realinfoAction()
    {
      //  if (empty($this->mCurUser))
      //   {
      //       return $this->showMsg('请先登录再执行操作', '/user/index');
      //   }
        //权限||上一次提交的数据
        $AutonymMO = new AutonymModel;
        $user = $AutonymMO->where("uid = {$this->mCurUser['uid']}")->fRow();
        if($_POST){
            if($_GET['a']==1)
            {
                $this->autonymAction();//print_r($this);die;
            }
            else
            {
                $this->shimingAction();
            }

        }
        if($_GET['a']==2) {
            if($user['status']==3){
                $user['status']=1;
            }
        }
        $this->assign('user', $user);
    }

    //充币
    public function coininAction()
    {
        if (empty($this->mCurUser)){return $this->showMsg($GLOBALS['MSG']['NEED_LOGIN'], '/'); }
        $coinMo = User_CoinModel::getInstance();
        $coinList   = $coinMo->field('name,display')->getList();

        $data       = array('coin'      => $coinList[0]['name'],
                            'coinType'  => 'in',
                            'type'      => 'all',
                            'startTime' => '',
                            'endTime'   => ''
        );

        $this->assign('formdata', $data);
        $this->setCoinList('in');
    }

     //jsj 在这里写控制器
     public function rechargeAction($page=1)
     {
         $this->assign('formdata', $data);
         $this->setCoinList('recharge');
     }


    //提币
    public function coinoutAction($page=1)
    {
    	$coinList = $this->setCoinList('out');
        $_GET     = array_map('strip_tags', $_GET);
        $start    = $_GET['startTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['startTime'])?$_GET['startTime']:'';
        $end      = $_GET['endTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['endTime'])?$_GET['endTime']:'';
        $coin     = $_GET['coin'];
        $days     = intval($_GET['days']);

        $page     = max(intval($page), 1);
        $pageSize = isset($_GET['size']) ? intval($_GET['size']) : 8;

        if (!$coin || !preg_match('/^[a-z]+$/i', $coin))
        {
            $coin = $coinList[0]['coin_from'];
        }

        $where = array('uid='.$this->mCurUser['uid'], 'opt_type="out"');

        if($days && $days>0)
        {
            $where[] = 'created >= ' . (strtotime(sprintf('-%d days', $days-1))-time()+strtotime('today'));
        }
        elseif($days=='-1')
        {
            if (trim($start))
            {
                $where[] = 'created >= ' . strtotime($start);
            }

            if (trim($end))
            {
                $where[] = 'created <= ' . strtotime($end);
            }
        }


        $where = implode(' and ', $where);

        $exchangeMo = Exchange_BaseModel::getInstance();
        if (!$_GET['excel']) {
            $tSql = sprintf('SELECT `id`,`created`,`wallet`,`txid`,`is_out`,`number`,`status`,`confirm` FROM `exchange_%s` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
        }
        $list  = $exchangeMo->query($tSql);


        $curCoinIdx = array_search($coin, array_column($coinList, 'coin_from'));
        $coinInfo[$curCoinIdx] = User_CoinModel::getInstance()->where(array('name'=>$coin))->fRow();

        //页码
        $mo = 'Exchange_'.ucfirst($coin).'Model';
        $count = $mo::getInstance()->where($where)->count();
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
            if ($_GET['excel']) {//导出excel不要这两个
                unset($v['confirm']);
                unset($v['statusCode']);
            }

        }
        unset($v);

        //导出excel
        if($_GET['excel'])
        {
            $excelHeader = array(
                $GLOBALS['MSG']['RECORD_ID'],
                $GLOBALS['MSG']['COINOUT_TIME'],
                $GLOBALS['MSG']['RECEIVE_ADDRESS'],
                'TXID',
                $GLOBALS['MSG']['ADDRESS_OWNER'],
                $GLOBALS['MSG']['NUMBER'],
                $GLOBALS['MSG']['STATUS']
            );

            $options = array('filename'=>$GLOBALS['MSG']['COINOUT_RECORD'], 'title'=>$GLOBALS['MSG']['COINOUT_RECORD']);

            $ePageSize = 2000;
            $eTotalPage = min(ceil($count/$ePageSize), 50);//最多允许导出最近10万条数据

            for($i=0; $i<$eTotalPage; $i++)
            {
               $tSql = sprintf('SELECT `id`,`created`,`wallet`,`txid`,`is_out`,`number`,`status`,`confirm` FROM `exchange_%s` WHERE %s ORDER BY id desc LIMIT %d,%d ', $coin, $where, $i * $ePageSize, $ePageSize);
                $list = $mo::getInstance()->query($tSql);

                $outData = array();
                foreach($list as $v)
                {
                    $outData[] = array(
                        $v['id'],
                        date('Y-m-d H:i:s', $v['created']),
                        $v['wallet'],
                        $v['txid'],
                        $v['is_out']? $GLOBALS['MSG']['IN_PLATFORM']: $GLOBALS['MSG']['OUT_PLATFORM'],
                        $v['number'],
                        $statusMap[$v['status']][1],
                    );
                }

                $complete = false;

                if($i==$eTotalPage-1)
                {
                    $complete = true;
                }
                Tool_Fnc::csv($excelHeader, $outData, $options, $complete);
            }
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
        $userInfo = UserModel::getInstance()->where(array('uid'=>$this->mCurUser['uid']))->fRow();
        $userInfo['realInfo'] = $realInfo;


        //赠送币
        $giveCoin =  UserModel::getInstance()->query("SELECT sum(number_au) auTotal, sum(number_reg) regTotal FROM user_reward WHERE uid={$this->mCurUser['uid']} and coin ='$coin'");
        $userInfo['giveCoin'] = $giveCoin?Tool_Math::add($giveCoin['auTotal'], $giveCoin['regTotal']):0;
        //  '100' 康康 测试账号   '101' 刘武 测试账号
        $noPhoneCodeUser = [13232544, 13282497, 100, 13231491, 101];
        // show(in_array($this->mCurUser['uid'], $noPhoneCodeUser));
        if(in_array($this->mCurUser['uid'], $noPhoneCodeUser))
        {
           $nomsg=2;   // 代表免验证码和手机短信验证码

        }else
        {
            $nomsg=1; // 需验证码和短信
        }

        $this->assign('nomsg', $nomsg);
        $this->assign('statistic', $statistic);
        $this->assign('coinInfo', $coinInfo);
        $this->assign('pageinfo', $pageinfo);
        $this->assign('list', $list);
        $this->assign('get', $_GET);
        $this->assign('coin', $coin);
        $this->assign('user', $userInfo);
    }

    //委托
    public function trustAction($page=1)
    {
        $coinList = $this->setTrustCoinList();
        $_GET     = array_map('strip_tags', $_GET);
        $days     = $_GET['days'] = intval($_GET['days']);
        $start    = $_GET['startTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['startTime'])?$_GET['startTime']:'';
        $end      = $_GET['endTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['endTime'])?$_GET['endTime']:'';
        $coin     = &$_GET['coin'];
        $flag     = $_GET['flag'] = intval($_GET['flag']);
        $page     = max(intval($page), 1);
        $pageSize = isset($_GET['size']) ? intval($_GET['size']) : 10;

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
                break; //卖
            case 7:$where[] = 'status=3';
                break; //卖
        }


        $where = implode(' and ', $where);

        $trustCoinMo = Order_CoinModel::getInstance();

        $pData = $trustCoinMo->getPrepareData(array('coin_from' => $coin));
        $where .= ' and '. $pData['str'];
        if (!$_GET['excel']) {
            $tSql = sprintf('SELECT * FROM `trust_%scoin` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);

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

        }


        //页码
        $count = $trustCoinMo->query(sprintf('SELECT count(*) cc FROM `trust_%scoin`  WHERE %s ', $coin, $where), $pData['values']);
        $tPage = new Tool_Page($count[0]['cc'], $pageSize);

        $pageinfo = $tPage->show();

        //导出excel
        if($_GET['excel'])
        {
            $ePageSize = 2000;
            $eTotalPage = min(ceil($count[0]['cc']/$ePageSize), 50);//最多允许导出最近10万条数据

            $excelHeader = array($GLOBALS['MSG']['TRUST_TIME'],
                                 $GLOBALS['MSG']['COIN_TYPE'],
                                 $GLOBALS['MSG']['FLAG_TYPE'],
                                 $GLOBALS['MSG']['TRUST_PRICE'],
                                 $GLOBALS['MSG']['TRANSACTION_NUMBER'],
                                 $GLOBALS['MSG']['NOT_TRANSACTION_NUMBER'],
                                 $GLOBALS['MSG']['STATUS']);
            //$excelHeader = array('委託時間', '幣種', '類型', '委託價格', '成交數量', '尚未成交數量', '狀態');

            $options = array('filename'=> $GLOBALS['MSG']['TRUST_RECORD'], 'title'=> $GLOBALS['MSG']['TRUST_RECORD']);

            for($i=0; $i<$eTotalPage; $i++)
            {
                $tSql = sprintf('SELECT * FROM `trust_%scoin`  WHERE %s  ORDER BY id desc limit %d,%d', $coin, $where, $i * $ePageSize, $ePageSize);
                $list = $trustCoinMo->query($tSql, $pData['values']);

                $outData = array();
                foreach($list as $v)
                {
                    $outData[] = array(
                        date('Y-m-d H:i:s', $v['created']),
                        $v['coin_from'],
                        $v['flag']=='buy'? $GLOBALS['MSG']['BUY']: $GLOBALS['MSG']['SALE'],//'买入':'卖出'
                        $v['price'].strtoupper($v['coin_to']),
                        $v['numberdeal'],
                        $v['numberover'],
                        //$v['status']==0?'未成交':($v['status']==1?'部分成交':($v['status']==2?'全部成交':'已经撤销'))
                        $v['status'] == 0 ? $GLOBALS['MSG']['NOT_FINISH_TRANSACTION'] : ($v['status'] == 1 ? $GLOBALS['MSG']['PART_FINISH_TRANSACTION'] : ($v['status'] == 2 ? $GLOBALS['MSG']['ALL_FINISH_TRANSACTION'] : $GLOBALS['MSG']['ALREADY_CANCEL']))
                    );
                }

                $complete = false;

                if($i==$eTotalPage-1)
                {
                    $complete = true;
                }
                Tool_Fnc::csv($excelHeader, $outData, $options, $complete);
            }

        }

        $coinname=Coin_PairModel::getInstance()->where(array('coin_from'=> $coin))->fList();

        $this->assign('pageinfo', $pageinfo);
        $this->assign('coinda', $coinname[0]);
        $this->assign('list', $list);
        $this->assign('get', $_GET);
    }

    //成交
    public function dealAction($page=1)
    {
    	$coinList = $this->setTrustCoinList();
        $_GET     = array_map('strip_tags', $_GET);
        $days     = $_GET['days'] = intval($_GET['days']);
        $start    = $_GET['startTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['startTime'])?$_GET['startTime']:'';
        $end      = $_GET['endTime'] = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['endTime'])?$_GET['endTime']:'';
        $coin     = &$_GET['coin'];
        $flag     = $_GET['flag'] = $_GET['flag']?intval($_GET['flag']):1;

        $page     = max(intval($page), 1);
        $pageSize = isset($_GET['size']) ? intval($_GET['size']) : 10;

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
        if (!$_GET['excel']) {
            $tSql = sprintf('SELECT * FROM `order_%scoin` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
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
        if($_GET['excel'])
        {
            $ePageSize = 2000;
            $eTotalPage = min(ceil($count/$ePageSize), 50);//最多允许导出最近10万条数据

            $excelHeader = array($GLOBALS['MSG']['ORDER_NUMBER'],
                                 $GLOBALS['MSG']['ORDER_SUCCESS_TIME'],
                                 $GLOBALS['MSG']['COIN_TYPE'],
                                 $GLOBALS['MSG']['FLAG_TYPE'],
                                 $GLOBALS['MSG']['ORDER_SUCCESS_PRICE'],
                                 $GLOBALS['MSG']['TRANSACTION_NUMBER'],
                                 $GLOBALS['MSG']['ORDER_SUCCESS_MONEY']);
            //$excelHeader = array('訂單號', '成交時間', '幣種', '類型', '成交價格', '成交數量', '成交金額');

            $options = array('filename'=> $GLOBALS['MSG']['ORDER_SUCCESS_RECORD'], 'title'=> $GLOBALS['MSG']['ORDER_SUCCESS_RECORD']);

            for($i=0; $i<$eTotalPage; $i++)
            {
                $tSql = sprintf('SELECT * FROM `order_%scoin` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, $i * $ePageSize, $ePageSize);
                $list = $trustCoinMo->query($tSql, $pData['values']);

                $outData = array();
                foreach($list as $v)
                {
                    $curRow = array(
                        $v['id'],
                        date('Y-m-d H:i:s', $v['created']),
                        $v['coin_from'],
                        $v['buy_uid']==$this->mCurUser['uid']?$GLOBALS['MSG']['BUY'] : $GLOBALS['MSG']['SALE'], //'买入':'卖出'
                        $v['price'].strtoupper($v['coin_to']),
                        $v['number'],
                        Tool_Math::mul($v['price'], $v['number']).strtoupper($v['coin_to'])
                    );
                    $outData[] = $curRow;

                    if($v['buy_uid']==$v['sale_uid'])
                    {
                        $curRow[3] = $GLOBALS['MSG']['SALE'];
                        $outData[] = $curRow;
                    }
                }

                $complete = false;

                if($i==$eTotalPage-1)
                {
                    $complete = true;
                }
            }

            Tool_Fnc::csv($excelHeader, $outData, $options, $complete);
        }
        $coinname = Coin_PairModel::getInstance()->where(array('coin_from' => $coin))->fList();

//        Tool_Out::p($list);die;

        $this->assign('coinda', $coinname[0]);
        $this->assign('pageinfo', $pageinfo);
        $this->assign('user', $this->mCurUser);
        $this->assign('list', $list);
        $this->assign('get', $_GET);
    }

    private function setCoinList($type)
    {
        $coinPairMo = User_CoinModel::getInstance();
        $coinList   = $coinPairMo->field('name coin_from,type,display,in_status,out_status')->getList();
        $this->assign('coinList', $coinList);
        return $coinList;
    }

    private function setTrustCoinList()
    {
        $coinPairMo = Coin_PairModel::getInstance();
        $coinList   = $coinPairMo->field('coin_from, display')->group('coin_from')->fList();
        $this->assign('coinList', $coinList);
        return $coinList;
    }


    public function coinRecordAction($csv = 0)//转币记录
    {
        if (empty($this->mCurUser)) {
            $data['reUrl'] = '/?login';
            $this->ajax($GLOBALS['MSG']['NEED_LOGIN'], 0, $data);//请先登录
        }
        //$uid = 13231175;
        $uid = $this->mCurUser['uid'];
        if ($csv == 0) {
            $coin = $_POST['coin'] ? trim(addslashes($_POST['coin'])) : 'btc';
            $coinType = $_POST['coinType'] ? trim(addslashes($_POST['coinType'])) : 'in';
            $type = $_POST['type'] ? trim(addslashes($_POST['type'])) : 'all';
            $in_type = $_POST['in_type'] ? trim(addslashes($_POST['in_type'])) : 'all';
            $startTime = $_POST['startTime'] ? strtotime(trim(addslashes($_POST['startTime']))) : strtotime(date('Y-m-d', time()) . '00:00:00');
            $endTime = $_POST['endTime'] ? strtotime(trim(addslashes($_POST['endTime']))) : strtotime(date('Y-m-d', time()) . '23:59:59');
        } else {//导出excel
            $coin = $_GET['coin'] ? trim(addslashes($_GET['coin'])) : ($_POST['coin'] ? trim(addslashes($_POST['coin'])) : 'btc');
            $coinType = $_GET['coinType'] ? trim(addslashes($_GET['coinType'])) : ($_POST['coinType'] ? trim(addslashes($_POST['coinType'])) : 'in');
            $type = $_GET['type'] ? trim(addslashes($_GET['type'])) : ($_POST['type'] ? trim(addslashes($_POST['type'])) : 'all');
            $in_type = $_GET['in_type'] ? trim(addslashes($_GET['in_type'])) : ($_POST['in_type'] ? trim(addslashes($_POST['in_type'])) : 'all');
            $startTime = $_GET['startTime'] ? strtotime(trim(addslashes($_GET['startTime']))) : ($_POST['startTime'] ? strtotime(trim(addslashes($_POST['startTime']))) : strtotime(date('Y-m-d', time()) . '00:00:00'));
            $endTime = $_GET['endTime'] ? strtotime(trim(addslashes($_GET['endTime']))) : ($_POST['endTime'] ? strtotime(trim(addslashes($_POST['endTime']))) : strtotime(date('Y-m-d', time()) . '23:59:59'));
        }
        if (!preg_match("/^[a-zA-Z]+$/", $coin) || !preg_match("/^[a-zA-Z]+$/", $coinType)) {
            $this->ajax($GLOBALS['MSG']['SYS_BUSY'], 0);
        }
        $mo = 'Exchange_' . ucfirst($coin) . 'Model';
        //$datas['list'] = $mo->where($where)->limit($tPage->limit())->order('uid DESC')->fList();
        $table = new $mo();
        if ($type == 'all') {
            $where = "uid=$uid and opt_type='$coinType'";
        } else if ($type == 1)//当天
        {
            $startTime = strtotime(date('Y-m-d', time()));
            $endTime = time();
            $where = "uid=$uid and opt_type='$coinType' and created between $startTime and $endTime";
        } else if ($type == 2)//30天
        {
            $startTime = strtotime("-30 day");
            $endTime = time();
            $where = "uid=$uid and opt_type='$coinType' and created between $startTime and $endTime";
        } else    //筛选
        {

            $where = "uid=$uid and opt_type='$coinType' and created between $startTime and $endTime";
        }
        if ($in_type == 'all') {//转入类型
            $where .= '';
        } elseif ($in_type == 1) {//普通转入
            $where .= ' and type=1';
        } elseif ($in_type == 2) {//交易挖矿
            $where .= ' and type=2';
        } elseif ($in_type == 3) {//持币分红
            $where .= ' and type=3';
        }
        $data = null;
        $exchangetable = 'exchange_' . $coin;
        $total = $table->query("select count(a.id) total from (select id,FROM_UNIXTIME(created,'%Y-%m-%d %H:%i:%s') time,txid,number,confirm,status,type from $exchangetable where $where order by created desc) as a LEFT JOIN (select id,txid from $exchangetable where opt_type='out' and status='成功') as b on a.txid=b.txid order by a.id desc");
        if ($total[0]['total'] == 0) {
            $data['list'] = '';
            $data['pagetotal'] = 0;
            $data['prev'] = '';
            $data['next'] = '';
            $data['currentpage'] = '';
        } else {
            if ($csv == 0) {//分页
                $page = $_POST['page'] ? (int)addslashes($_POST['page']) : 1;//页码
                $pagenumber = $_POST['pagenumber'] ? (int)addslashes($_POST['pagenumber']) : 7;//每页多少条
                $data['pagetotal'] = ceil($total[0]['total'] / $pagenumber);//总页数
                if ($page > $data['pagetotal']) {
                    $page = $data['pagetotal'];
                }
                if ($page < 1) {
                    $page = 1;
                }
                $p = ($page - 1) * $pagenumber;

                $data['list'] = $table->query("select a.id,a.time,a.txid,a.number,a.confirm,a.status,a.type,b.id bid from (select id,FROM_UNIXTIME(created,'%Y-%m-%d %H:%i:%s') time,txid,number,confirm,status,type from $exchangetable where $where order by created desc) as a LEFT JOIN (select id,txid from $exchangetable where opt_type='out' and status='成功') as b on a.txid=b.txid order by a.id desc limit $p,$pagenumber");

                foreach ($data['list'] as &$v) {//去0
                    $v['number'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', $v['number']), '.');
                }
                $data['prev'] = $page - 1;//上一页
                $data['next'] = $page + 1;//下一页
                if ($data['prev'] < 1) {
                    $data['prev'] = 1;
                }
                if ($data['next'] > $data['pagetotal']) {
                    $data['next'] = $data['pagetotal'];
                }
                $data['currentpage'] = $page;//当前页
            } else {//excel
                $data['list'] = $table->query("select a.id,a.time,a.txid,a.number,a.confirm,a.status,a.type,b.id bid from (select id,FROM_UNIXTIME(created,'%Y-%m-%d %H:%i:%s') time,txid,number,confirm,status,type from $exchangetable where $where order by created desc) as a LEFT JOIN (select id,txid from $exchangetable where opt_type='out' and status='成功') as b on a.txid=b.txid order by a.id desc");

            }

        }
        $statusMap = array(
            '待审批' => $GLOBALS['MSG']['PENDING_AUDIT'],
            '等待' => $GLOBALS['MSG']['WAIT_FOR_COINOUT'],
            '成功' => $GLOBALS['MSG']['SUCCEED'],
            '已取消' => $GLOBALS['MSG']['CANCELED'],
            '确认中' => $GLOBALS['MSG']['CONFORM'],
            '冻结中' => $GLOBALS['MSG']['FREEZING'],
        );
        if($data['list'])
        {
            foreach ($data['list'] as &$v)
            {
                //$v['colour'] = $v['status'] == '成功' ? 1 : 0;//成功 1 其他0
                $v['status'] = $statusMap[$v['status']];
                $v['type'] = $v['type']==1? $GLOBALS['MSG']['IN_PUTONG']:($v['type'] == 2? $GLOBALS['MSG']['IN_MINE']: $GLOBALS['MSG']['IN_SHARE']);
                $v['thaw_time'] = $v['status'] == '冻结中' ? date('Y-m-d H:i:s', strtotime("$v[time] +30 day")) : '';//解冻时间 30天
                if ($v['txid'] == '' || $v['bak'] != '' || !empty($v['bid'])) {
                    $v['bid'] = $GLOBALS['MSG']['IN_PLATFORM'];//平台内
                } else {
                    $v['bid'] = $GLOBALS['MSG']['OUT_PLATFORM'];//平台外
                }
            }
        }

        if ($csv == 1) {
            return $data['list'];
        } else {
            $this->ajax($GLOBALS['MSG']['GET_DATA_SUCCESS'], 1, $data);//获取数据成功
            //echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }


    public function coinRecordCsvOutAction()//转币记录导出excel
    {
        if (empty($this->mCurUser))
        {
            return $this->showMsg($GLOBALS['MSG']['NEED_LOGIN'], '/');//请先登录再执行操作
        }
        $coinType = $_GET['coinType'] ? trim(addslashes($_GET['coinType'])) :($_POST['coinType']? trim(addslashes($_GET['coinType'])): 'in');
        $data     = $this->coinRecordAction(1);
        if(!$data){
            $data=array();
        }
        ob_clean();    //清除缓存
        @ini_set('memory_limit', '3000M');//设置一下使用内存，由于数据量很大，不设置不行，默认的不够
        @ini_set('output_buffering', 'On');//设置 一下缓冲区占用的内存，默认的依然不够用
        set_time_limit(6000);//设置一下执行时间，同理，默认时间不够用
        ob_start();//缓冲区开始

        require(preg_replace('/controllers/', 'library', dirname(__FILE__)) . '/PHPExcel/PHPExcel.php');

        $objPHPExcel = new PHPExcel();

        //  标题啊之类的

        $objPHPExcel->getActiveSheet()->freezePane('A1', 'ID');
        if ($coinType == 'in')
        {//转入
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', $GLOBALS['MSG']['RECORD_ID'])//'记录ID'
                ->setCellValue('B1', $GLOBALS['MSG']['IN_TYPE'])//类型
                ->setCellValue('C1', $GLOBALS['MSG']['D_TIME'])//时间
                ->setCellValue('D1', 'txid')
                ->setCellValue('E1', $GLOBALS['MSG']['ADDRESS_OWNER'])// 钱包地址所属
                ->setCellValue('F1', $GLOBALS['MSG']['COIN_IN_NUMBER'])//轉入數量
                ->setCellValue('G1', $GLOBALS['MSG']['CONFIRM_NUMBER'])//确认次数
                ->setCellValue('H1', $GLOBALS['MSG']['STATUS']);//状态
            $objPHPExcel->getActiveSheet()->setTitle($GLOBALS['MSG']['COIN_IN_RECORD']);//转入记录
        }
        else
        {//转出
            //$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', '记录ID')->setCellValue('B1', '转出时间')->setCellValue('C1', '接收地址')->setCellValue('D1', '数量')->setCellValue('E1', '确认次数')->setCellValue('F1', '状态');
            //$objPHPExcel->getActiveSheet()->setTitle('转出记录');
        }

        $num = 2;
        foreach ($data as $key => $v)
        {
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A' . $num, $v['id'])
                ->setCellValue('B' . $num, $v['type'])
                ->setCellValue('C' . $num, $v['time'])
                ->setCellValue('D' . $num, $v['txid'])
                ->setCellValue('E' . $num, $v['bid'])//'平台内':'平台外'
                ->setCellValue('F' . $num, $v['number'])
                ->setCellValue('G' . $num, $v['confirm'])
                ->setCellValue('H' . $num, $v['status']);
            $num++;
        }

        //$objPHPExcel->getActiveSheet()->setTitle('转入记录');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=coinRecord" . date('YmdHi') . ".xls");
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');

        exit;
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


    public function refreshAction()
    {
        Tool_Session::mark($this->mCurUser['uid']);
        die('success');
    }


    public function mplanAction()
    {
        $this->_ajax_islogin();
        //邀请链接
        $host = Yaf_Registry::get("config")->domain;
        $inviteUrl =$host.'?regfrom='.Tool_Code::idEncode($this->mCurUser['uid']).'&alert=register';
       // 去掉邀请人
        //$inviteUrl = $_SERVER['HTTP_HOST'];
        //邀请人数
        $inviteNum = UserModel::getInstance()->where('from_uid='. $this->mCurUser['uid'])->count();

        $userInfo = UserModel::getInstance()->field('rebate')->where('uid='.$this->mCurUser['uid'])->fRow();
        $rebate = json_decode($userInfo['rebate'], true);

        $area = Coin_PairModel::getInstance()->field('coin_to')->group('coin_to')->fList();

        //历史遗留，导致mcc差异化处理
        $rebateGroup = ['mcc_reg'=>['mcc_reg_in'=>$rebate['mcc_in'], 'mcc_reg_out'=>$rebate['mcc_out']]];
        foreach ($area as $k=>$v)
        {
            $coinKey = $v['coin_to']=='mcc'?'mcc_rebate':$v['coin_to'];
            $rebateGroup[$v['coin_to']][$v['coin_to'].'_in'] = $rebate[$coinKey.'_in'];
            $rebateGroup[$v['coin_to']][$v['coin_to'].'_out'] = $rebate[$coinKey.'_out'];
        }

        //活动 根据活动币种改币名
        $coin='mcc';//活动 根据活动币种改币名
        $activity = ActivityModel::getInstance()->where("name='赠送$coin' and status=1 and admin=110")->fRow();
        $starttime= $activity['start_time'];
        $endtime= $activity['end_time'];
        $sql = "select * from (
select Inviter_uid,Inviter_mo,count(be_invited_uid) uidtotal,count(status) autototal from (
 select a.from_uid Inviter_uid,b.mo Inviter_mo,b.area Inviter_area,a.uid be_invited_uid,a.mo be_invited_mo
,a.area be_invited_area,a.created be_invited_created ,e.name Inviter_name,f.name be_invited_name,f.status
from user a
left join user b on a.from_uid=b.uid
left join autonym e on a.from_uid=e.uid and e.status=2
left join autonym f on a.uid=f.uid and f.status=2
where a.created>$starttime and a.created<$endtime and a.from_uid!=0) as g group by g.Inviter_uid ) as h order by h.autototal desc,h.uidtotal desc,h.Inviter_uid asc limit 200";

       /* $sql="select * from
(select Inviter_uid,inviter_mo,count(be_invited_uid) invited_total ,sum(number) number from
(select c.*,d.number,d.coin,e.name Inviter_name,e.status Inviter_status,f.name be_invited_name,f.status be_invited_status,f.updated be_invited_updated from
(select a.from_uid Inviter_uid,b.mo Inviter_mo,b.area Inviter_area,a.uid be_invited_uid,a.mo be_invited_mo,a.area be_invited_area,a.created be_invited_created from user a
left join user b on a.from_uid=b.uid where a.created>$starttime and a.created<$endtime and a.from_uid!=0) as c
left join user_reward d on c.be_invited_uid=d.be_invited and (d.type=3 and d.coin='$coin')
left join autonym e on c.Inviter_uid=e.uid left join autonym f on c.be_invited_uid=f.uid) as g GROUP BY g.inviter_mo) as h order by invited_total desc,Inviter_uid asc;
";*/
       $usermo= new UserModel();
        $orderlist= $usermo->query($sql);
        $uid = $_SESSION['user']['uid'];
        $orderarr = array_column($orderlist, "Inviter_uid");
        $key = array_search($uid, $orderarr);
        if($key===false){
            $myorderdata = array(
                'myorder' => '',
                'mynumber' => ''
            );
            $this->assign('inviteNum', '');
        }else{
            $myorder = $key + 1;
            $mynumber = bcmul($orderlist[$key]['autototal'], 15, 20);
            $myorderdata = array(
                'myorder' => $myorder,
                'mynumber' => trim(preg_replace('/(\.\d*?)0+$/', '$1', $mynumber), '.')
            );
            $this->assign('inviteNum', $orderlist[$key]['uidtotal']);

           /* $mynumber = $orderlist[$key]['number'];
            $myorderdata = array(
                'myorder' => $myorder,
                'mynumber' => trim(preg_replace('/(\.\d*?)0+$/', '$1', $mynumber), '.')
            );
            $this->assign('inviteNum', $orderlist[$key]['invited_total']);*/
        }
        $this->assign('myorderdata', $myorderdata);//排行榜
        if($activity)
        {
            $activity['start'] = date('Y-m-d H:i:s', $activity['start_time']);
            $activity['end'] = date('Y-m-d H:i:s', $activity['end_time']);
        }
        //提币限额
        $pair = array(
            'mcc'=>['min'=>50, 'numLimit'=>0],
            'btc'=>['min'=>0.01, 'numLimit'=>8],
            'eth'=>['min'=>0.01, 'numLimit'=>8],
        );

        $this->assign('pair', $pair);
        $this->assign('activity', $activity);
        $this->assign('url', $inviteUrl);
        $this->assign('rebate', $rebateGroup);
    }
    public function candyAction(){}

//获取易币用户余额
    public function getyibiuser($area, $mo, $email,$coin)
    {
        $data = array(
            'area' => $area,
            'mo' => $mo,
            'email' => $email,
            'coin' => $coin,
            'timestamp' => time()
        );
        ksort($data);
        $str = '';
        foreach ($data as $key => $v) {
            $str .= $key . $v;
        }
        //$token = md5($str . 'asdcsd');
        $dobi_used_key = "EIML%CcrtqVwXzrT4s8%F5YaDdZ1F^6A";//多比传给法币
        $token = md5($str . $dobi_used_key);
        $data['token'] = $token;
        $headers = array('Content-Type:application/x-www-form-urlencoded', 'charset=utf-8');
        $yibiurl = Yaf_Registry::get("config")->yibi->ip;
        $url = $yibiurl . 'api_user/userover';
        $json = http_build_query($data);
        //show($json);
        //$strResult = $this->callInterfaceCommon($url, "POST", $json, $headers);
        $strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);

        $ddd = substr($strResult, strpos($strResult, "{"));//去掉json前面的东东
        $ars = json_decode($ddd, true);
        return $ars;
    }
   // 显示支付方式
    public function userbankAction(){

        $this->_ajax_islogin();
        $Userbank = new UserbankModel();
        $bank=$Userbank->where(['uid' => $this->mCurUser['uid']])->fList();
        $this->assign('userbank',$bank);
//        Tool_Out::p($bank);die;
    }
    //绑定支付方式
    public function bankaddAction(){
        $this->_ajax_islogin();
        $Userbank = new UserbankModel();
        $User = new UserModel();
        $username = $User->where(['uid'=>$this->mCurUser['uid']])->fRow();
        $id=$_GET['id'];
        $type=$_GET['type'];
        $name=$_GET['name'];
        $bankcard=$_GET['bankcard'];
        $img=$_GET['img'];
        $data=[
            'uid'=>$this->mCurUser['uid'],
            'username'=>$username['mo'],
            'type'=>$_GET['type'],
            'name'=>$_GET['name'],
            'bankcard'=>$_GET['bankcard'],
            'addtime'=>time(),
            'img'=>$_GET['img'],
            'status'=>1,
        ];
        if($id){
            $Userbank->insert($data);
            $this->showMsg('关闭绑定不存在!');
        }else{
            $Userbank->where(['id'=>$id])->update($data);
            $this->showMsg('关闭绑定不存在!');
        }
    }
    //开启关闭
    public function mysaveAction(){
        $this->_ajax_islogin();
        $Userbank = new UserbankModel();
        $id=$_GET['userId'];
        $vals=$_GET['status'];
        if (!$id && $vals==1) {
            $this->showMsg('关闭绑定不存在!');
        }
        if (!$id && $vals==2) {
            $this->showMsg('开启绑定不存!');
        }
        $userbank=$Userbank->where(['uid'=>$this->mCurUser['uid'],'status'=>1])->count();
        if($vals==1){
            if($userbank<=1){
                $this->ajaxreturn(['status'=>0,'msg'=>'收款方式至少开启一个！']);
            }
        }
        $bank=$Userbank->where(array('uid' =>$this->mCurUser['uid'], 'id' => $id))->fRow();
        if (!$bank) {
            $this->ajaxreturn(['status'=>0,'msg'=>'非法访问！']);
        }
        if ($vals==1 || $vals==2) {
            $data=array(
                'status'=>$vals,
            );
            $Userbank->where(array('id' => $id))->update($data);
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
}
