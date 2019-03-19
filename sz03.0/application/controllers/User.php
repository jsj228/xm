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
        Tool_Session::mark($this->mCurUser['uid']);
        $pUser = UserModel::getInstance()->lock()->fRow($this->mCurUser['uid']);
        $_SESSION['user'] = $pUser;
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
        // Tool_Out::p($coinList);die;
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

        //全部币都换算成btc
        foreach ($newPrice as $coin => $area) {
            $coinPrice = [];
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
        // Tool_Out::p($coinPrice);die;
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
        $coinList   = $coinMo->field('name,type,display')->getList();

        $data       = array('coin'      => $coinList[0]['name'],
            'coinType'  => 'in',
            'type'      => 'all',
            'startTime' => '',
            'endTime'   => ''
        );
        $this->assign('formdata', $data);
        $this->setCoinList('in');
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
            $tSql = sprintf('SELECT `id`,`created`,`wallet`,`label`,`txid`,`is_out`,`number`,`status`,`confirm` FROM `exchange_%s` WHERE %s ORDER BY id desc limit %d,%d', $coin, $where, ($page - 1) * $pageSize, $pageSize);
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
        // Tool_Out::p($pageinfo);die;
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
                $list = $trustCoinMo->query($tSql);//, $pData['values']

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
        $coinList   = $coinPairMo->field('coin_from, display')->fList();//group('coin_from')->
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

        $host = $_SERVER['SERVER_NAME'];
        $inviteUrl =$host.'?regfrom='.Tool_Code::idEncode($this->mCurUser['uid']).'&alert=register';

        //邀请人数
        $inviteNum = UserModel::getInstance()->where("from_uid={$this->mCurUser['uid']}")->fOne("count(uid)");

        $this->assign('url', $inviteUrl);
        $this->assign('inviteNum', $inviteNum);
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
        $dobi_used_key = "EIML%CcrtqVwXzrT4s8%F5YaDdZ1F^6A";//火网传给法币
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
    //用户充值
    public function rechargeAction($page=1){
        $this->_ajax_islogin();
        $myczType = new RechargetypeModel();
        $method= $myczType->where('status=1')->fList();

        $this->assign('method', $method);
        // Tool_Out::p($method);die;
    }
    //充值提交
    public function rechargeupAction()
    {
        $this->_ajax_islogin();
        $type =$_POST["type"]?$_POST["type"]:'';//支付方式
        $num =$_POST["num"];
        if(!is_numeric($num)||strpos($num,".")!==false){
            $this->ajax('价格只能是正整数', 0); //价格只能是正整数

        }
        $myczType = RechargetypeModel::getRechargetype($type,1);
        if (!$myczType) {
            $this->ajax('充值方式不存在', 0); //充值方式不存在
        }

        if ($myczType['status']!= 1) {
            $this->ajax('充值方式没有开通', 0); //充值方式没有开通
        }

        $mycz_min = ($myczType['min'] ? $myczType['min'] : 1);
        $mycz_max = ($myczType['max'] ? $myczType['max'] : 100000);

        if ($num < $mycz_min) {
            $this->ajax('充值金额不能小于'. $mycz_min . '元！', 0); //充值金额不能小于
        }

        if ($mycz_max < $num) {
            $this->ajax('充值金额不能大于'. $mycz_max . '元！', 0); //充值金额不能大于
        }

        //用户信息
        $userType = RechargeModel::getRecharge($this->mCurUser['uid'],0);
        if ($userType) {
            $this->ajax('您还有未付款的订单', 0); //您还有未付款的订单

        }


        for (; true; ) {

            $tradeno = RechargeModel::tradeno();

            if (!RechargeModel::getInstance()->where(array('tradeno' => $tradeno))->fRow()) {
                break;
            }

        }
        //平台账号
        $userbank=RechargetypeModel::getInstance()->where(array('name' => $type,'status'=>1))->fRow();
        //用户信息
        $usermodel=new UserModel();
        $user= $usermodel->where(['uid'=>$this->mCurUser['uid']])->fRow();
        //支付方式
        $new = RechargetypeModel::getRechargetype($type,1);


        $arr = ['uid' =>$this->mCurUser['uid'],'username'=>$user['mo']?$user['mo']:$user['email'],'bankname'=>$userbank['username'], 'num' => $num, 'type' => $type, 'tradeno' => $tradeno, 'addtime' => time(), 'status' => 0,'bank_id'=>$new['id']];
        $Re = new RechargeModel();

        $bank=$Re->insert($arr);
        // var_dump($bank);die;
        if ($bank) {
            $this->ajax('创单成功', 1,$bank);
        } else {
            $this->ajax('提现订单创建失败', 0); //提现订单创建失败
        }

    }
    //充值撤销
    public function chexiaoAction()
    {
        $this->_ajax_islogin();
        $id =$_POST['id']; //$_GET('id');
        if (!$id) {
            $this->ajax('参数错误', 0); //参数错误
        }
        $mycz = RechargeModel::getInstance()->where(array('id' =>$id))->fRow();

        if (!$mycz) {
            $this->ajax('充值订单不存在', 0); //充值订单不存在
        }

        if ($mycz['uid'] != $this->mCurUser['uid']) {
            $this->ajax('非法操', 0); //非法操
        }

        if ($mycz['status'] == 1 || $mycz['status'] == 3 ) {
            $this->ajax('订单不能撤销！',0);
        }

        //限定每天只能撤销两次
        $beginToday=strtotime(date('Y-m-d'));
        $mycznum = new RechargeModel();
        //用户ID
        $where ="uid={$this->mCurUser['uid']} and status=4 and addtime>{$beginToday}";

        $chexiao_num = $mycznum->where($where)->count();//总条数

        if ($chexiao_num >= 5){
            $this->ajax('您当天撤销操作过于频繁，请明天再进行尝试。', 0); //您当天撤销操作过于频繁，请明天再进行尝试。

        }
        $rs = $mycznum->where(array('id' => $id))->update(array('status' => 3));
        if ($rs) {
            $this->ajax('撤销成功', 1); //操作失败
        } else {
            $this->ajax('操作失败', 0); //操作失败
        }
    }
    //充值汇款
    public function HuikuanAction(){
        $this->_ajax_islogin();
        $id = $_POST['id'];
        if (!$id) {
            $this->ajax('参数错误', 0); //参数错误
        }
        $rechargeModel = new RechargeModel();
        $mycz = $rechargeModel->where(array('id' => $id))->fRow();
        if (!$mycz) {
            $this->ajax('充值订单不存在', 0); //充值订单不存在
        }
        if ($mycz['uid'] != $this->mCurUser['uid']) {
            $this->ajax('非法操作', 0); //非法操作
        }
        if ($mycz['status'] != 0) {
            $this->ajax('订单已经处理过', 0); //订单已经处理过
        }
        $rs = $rechargeModel->where(['id' => $id])->update(array('status' => 2));
        if ($rs) {
            $this->ajax($GLOBALS['MSG']['SMS_SUCCESS'],1);
        } else {
            $this->ajax('操作失败！',0);
        }
    }
    // 用戶綁定銀行卡初始化
    public function bankAction(){
        $user = new UserModel();
        $auto = $user->table('autonym')->where(['uid' => $this->mCurUser['uid']])->fOne("status");
        $this->assign("auto",$auto);
    }
    // 显示支付方式
    public function bindbankAction(){
        $this->_ajax_islogin();
        $type=$_POST['type'];
        $Userbank = new UserbankModel();
        $bank['bank']=$Userbank->where(['uid' => $this->mCurUser['uid'],'type'=>$type])->fList();
        $usermodel=new AutonymModel();
        $bank['user']=$usermodel->field('name')->where(['uid'=>$this->mCurUser['uid']])->fRow();

        if($bank['bank']){$this->ajax('',1,$bank);}
        else{$this->ajax('',0,$bank);}
    }
    //查看支付方式
    public function myseeAction()
    {
        $id = $_POST['id'];
        $mysee = new RechargeModel();
        $myseetype = new RechargetypeModel();
        if ($id) {
            $mycz =$mysee->where(array('id' => $id))->fRow();
            if (!$mycz) {
                $this->ajax('支付方式不存在', 0);
            }
            $myczType =$myseetype->where(array('id' => $mycz['bank_id']))->fRow();

            $data[]= [
                'id' => $mycz['id'],
                'uid'=>$mycz['uid'],
                'username'=>$myczType['username'],
                'kaihu'=>$myczType['kaihu'],
                'truename' => $myczType['truename'],
                'num' => $mycz['num'],
                'mum' => $mycz['mum'],
                'type' => $mycz['type'],
                'tradeno' => $mycz['tradeno'],
                'remark' => $mycz['remark'],
                'beizhu' => $mycz['beizhu'],
                'bank_id' => $mycz['bank_id'],//商家支付ID
                'addtime' => date('Y-m-d H:i:s', $mycz['addtime']),
                'status' => $mycz['status'],
                'image'=>$myczType['img'],

            ];

            $this->ajax('', 1,$data);
        } else {
            $this->ajax('错误', 0);
        }

    }
    // 显示支付方式
    public function userbankAction(){
        $type=$_POST['type'];
        $this->_ajax_islogin();
        $Userbank = new UserbankModel();
        $bank=$Userbank->where(['uid' => $this->mCurUser['uid'],'type'=>$type])->fRow();
        $bank ? $this->ajax('',1,$bank) : $this->ajax('',0,$bank);
    }
    //绑定支付方式
    public function bankaddAction(){
        $uid = $this->mCurUser['uid'];
        $this->_ajax_islogin();
        $Userbank = new UserbankModel();
        $bankdata= Tool_Request::post();
        if(!$bankdata['data']['moblePhone']){
            $this->ajax('请输入驗證碼!',0);
        }
        if(intval($bankdata['data']['moblePhone'])!=intval($_SESSION["moblesend".$uid])){
            $this->ajax('驗證碼錯誤!',0);
        }
        //执行Oss上传
        $path = 'upload/public/';
        $tMO = new Tool_Oss();
        $frontFace = $tMO->uploadOne($path.date('Ymd') . '/' . uniqid().md5($this->mCurUser['uid']) . '.'.'jpg',$bankdata['data']['img']);
        if($bankdata['data']['index']==1){
            $userbank=$Userbank->where(['uid'=>$this->mCurUser['uid'],'type'=>1])->count();
            if($userbank>5){
                $this->ajax('最多绑定五张银行卡!',0);
            }
            $usertype=$Userbank->where(['uid'=>$this->mCurUser['uid'],'type'=>1,'status'=>1])->fRow();
            if($usertype){$status=0; }
            else{
                $status=1;
            }
            $data=[
                'uid'=>$this->mCurUser['uid'],
                'type'=>$bankdata['data']['index'],
                'name'=>$bankdata['data']['name'],
                'bankcard'=>$bankdata['data']['bankcard'],
                'username'=>$bankdata['data']['username'],
                'bankcard'=>$bankdata['data']['bankcard'],
                'addtime'=>time(),
                'status'=>$status,
            ];
        }else{
            $data=[
                'uid'=>$this->mCurUser['uid'],
                'type'=>$bankdata['data']['index'],
                'bankcard'=>$bankdata['data']['bankcard'],
                'addtime'=>time(),
                'img'=>$frontFace,//$frontFace,
                'status'=>1,
            ];
        }

        if($bankdata["data"]["id"]){
            $savebank = $Userbank->where(['id'=>$bankdata["data"]["id"]])->update($data);
            if($savebank){
                $this->ajax('編輯成功!',1);
            }else{
                $this->ajax('編輯失敗!',0);
            }
        }else{
            $addbank = $Userbank->insert($data);
            if($addbank){
                $this->ajax('添加成功!',1);
            }else{
                $this->ajax('添加失敗!',0);
            }
        }
    }
    //开启关闭
    public function mysaveAction(){
        $this->_ajax_islogin();
        $Userbank = new UserbankModel();
        $id=$_REQUEST['id'];
        $vals=$_REQUEST['status'];
        // var_dump($vals==1,$vals==0);
        if (!$id && $vals==0) {
            $this->ajax('关闭绑定不存在!',0);
        }
        if (!$id && $vals==1) {
            $this->ajax('开启绑定不存!',0);
        }
        $userbank=$Userbank->where(['uid'=>$this->mCurUser['uid'],'status'=>1])->count();
        if($vals==0){
            if($userbank<=1){
                $this->ajax('收款方式至少开启一个',0);
            }
        }
        if($vals==1){
            $bank=$Userbank->where(array('uid'=>$this->mCurUser['uid'],'type'=>1,'status'=>1))->count();
            $userbank = $Userbank->where(array('id'=>$id,'type'=>1))->fRow();
            if($bank>=1 && $userbank){
                $this->ajax('只能开启一张网银',0);
            }
        }
        $bank=$Userbank->where(array('uid' =>$this->mCurUser['uid'], 'id' => $id))->fRow();
        if (!$bank) {
            $this->ajax('非法访问',0);
        }
        if ($vals==0 || $vals==1) {
            $savebank=$Userbank->where(['id' => $id])->update(['status'=>$vals]);
            $this->ajax('',1,$savebank);
        }else{
            $this->ajax('修改失敗',0);
        }
    }
    //删除银行
    public function delbankAction()
    {
        $Userbank= new UserbankModel();
        $id = $_REQUEST['id'];
        $paypassword = $_REQUEST['paypassword'];
        $this->_ajax_islogin();
        if (!$id) {
            $this->ajax('参数错误！');
        }
        //用户
        $user = new UserModel();
        $sell_paypassword = $_POST['val'];
        $userpassword = $user->where("uid={$this->mCurUser['uid']}")->fRow();
        $userMo   = UserModel::getInstance();
        $userInfo = $userMo->field('email,pwd,mo,pwdtrade,prand')->fRow($this->mCurUser['uid']);
        // $pwdtrade = Tool_Md5::encodePwdTrade($sell_paypassword,$userInfo['prand']);
        // if ($pwdtrade!= $userpassword['pwdtrade']) {
        //     $this->ajax('交易密码错误');
        // }

        if (!$Userbank->where(['id' => $this->mCurUser['uid'], 'id' => $id])->fRow()) {
            $this->ajax('非法访问！');
        } else if ($Userbank->where(['uid' =>$this->mCurUser['uid'], 'id' => $id])->del()) {
            $this->ajax('删除成功！',1);
        } else {
            $this->ajax('删除失败！');
        }
    }
    //发送接口
    public function sendSMS($username,$password_md5,$apikey,$mobile,$contentUrlEncode,$encode)
    {
        //发送链接（用户名，密码，apikey，手机号，内容）
        $url = "http://m.5c.com.cn/api/send/index.php?";  //如连接超时，可能是您服务器不支持域名解析，请将下面连接中的：【m.5c.com.cn】修改为IP：【115.28.23.78】
        $data=array
        (
            'username'=>$username,
            'password_md5'=>$password_md5,
            'apikey'=>$apikey,
            'mobile'=>$mobile,
            'content'=>$contentUrlEncode,
            'encode'=>$encode,
        );
        $result = $this->curlSMS($url,$data);

        return $result;
    }
    private function curlSMS($url,$post_fields=array())
    {
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);//用PHP取回的URL地址（值将被作为字符串）
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//使用curl_setopt获取页面内容或提交数据，有时候希望返回的内容作为变量存储，而不是直接输出，这时候希望返回的内容作为变量
        curl_setopt($ch,CURLOPT_TIMEOUT,30);//30秒超时限制
        curl_setopt($ch,CURLOPT_HEADER,1);//将文件头输出直接可见。
        curl_setopt($ch,CURLOPT_POST,1);//设置这个选项为一个零非值，这个post是普通的application/x-www-from-urlencoded类型，多数被HTTP表调用。
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);//post操作的所有数据的字符串。
        $data = curl_exec($ch);//抓取URL并把他传递给浏览器
        curl_close($ch);//释放资源
        $res = explode("\r\n\r\n",$data);//explode把他打散成为数组
        return $res[2]; //然后在这里返回数组。
    }
    public function sendmobleAction(){
        $uid = $this->mCurUser['uid'];
        $username = 'xzgr';  //用户名
        $password_md5 = '48bc19c3d2e6763b31c0583aae5e457d';  //32位MD5密码加密，不区分大小写
        $apikey = 'b943b645e1cc2fb7850abc06aaff975b';  //apikey秘钥（请登录 http://m.5c.com.cn 短信平台-->账号管理-->我的信息 中复制apikey）
        $code = rand(111111, 999999);
        session_start();
        $_SESSION["moblesend".$uid] =$code;
        $message = "您的验证码是{$_SESSION["moblesend".$uid]}，在10分钟内输入有效。如非本人操作请忽略此短信。";
        $UserModel = new UserModel();
        $phonecode = new PhoneCodeModel();
        $user = $UserModel->where(['uid'=>$this->mCurUser['uid']])->fRow();
        $data =array(
            'code'=>$_SESSION["moblesend".$uid],
            'action'=>1,
            'mo'=>$user['mo'],
            'message'=>$message,
            'ctime'=>time(),
            'utime'=>time(),
            'area'=>$user['area'],
            'status'=>1,
        );

        if($sms = $phonecode->insert($data)){
            $moble=$this->sendSMS($username, $password_md5, $apikey, $user['area'] . $user['mo'], $message, 'UTF-8');  //进行发送
            if(strpos($moble,"success")>-1) $this->ajax('发送成功',1);
        }

        $this->ajax('',1,$code);
    }
    // 用戶充值數據
    public function rechargeajaxAction(){
        $this->_ajax_islogin();
        $status=$_POST["status"];
        $where="uid={$this->mCurUser['uid']}";

        if($status!='all'){
            $status=$_POST["status"];
            $where.=" and status={$status}";
        }else{
            $where.='';
        }
        $Mo = new RechargeModel();
        // 获取分页显示
        $page = $_REQUEST['page']?:1;
        $pageSize = 10;
        //总记录
        $count = $Mo->where($where)->count();
        $list = $Mo->where($where)->page($page, $pageSize)->order('id desc')->fList();
        $re_ids = array_column($list,'id');
        $re_ids = implode($re_ids,',');
        $mo = Orm_Base::getInstance();
        $bonus = $mo->table("fee_bonus_recharge")->where("id in({$re_ids})")->fList();
        $bonus = array_column($bonus,'fee','id');
//        $this->ajax(1,0,[$bonus,$mo->getLastSql()]);

        $data = array('list'=>$list, 'totalPage'=>ceil($count/$pageSize));
        foreach ($list as &$v) {
            $v['addtime']=date("Y-m-d H:i:s",$v['addtime']);
            $v['fee'] = $bonus[$v['id']];
        }
        $data['list'] = $list;
        $this->ajax('', 1,$data);
    }
    // 用戶綁定銀行卡初始化
    public function otcAction(){
        $user = new UserModel();
        $auto = $user->table('autonym')->where(['uid' => $this->mCurUser['uid']])->fOne("status");
        $this->assign("auto",$auto);
    }
    //好友充值初始化
    public function bonusinviteAction(){}
    //好友初始化
    public function bonusdetailsAction(){}
}
