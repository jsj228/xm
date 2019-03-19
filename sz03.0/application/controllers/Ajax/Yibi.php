<?php

class Ajax_YibiController extends Ajax_BaseController
{
    //protected $key = 'yibi##&&dobi';
    protected $logdir = '/log/toyibi';
    protected $dobi_used_key = "EIML%CcrtqVwXzrT4s8%F5Yadfgdgdfg";//火网传给法币
    protected $fabi_used_key = "afddsf3#$^d*KWN@PXh7X!NPdfgdfgda";//法币传给火网

    //查看是否设置交易密码
    public function issetTradepwdAction()
    {
        header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Credentials: true");
		header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
		header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
		header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
		header("Content-Type: application/json; charset=utf-8");
		header("Content-Type: application/x-www-form-urlencoded; charset=utf-8");
        if (!isset($_POST['token'], $_POST['mo'], $_POST['area'],$_POST['email'])) {
            $this->ajax('参数错误');
        }

        $data=array(
            'area'=> $_POST['area'],
            'mo' => $_POST['mo'],
            'email' => $_POST['email'],
        );
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . $v;
        }
        //$token = md5($str . $this->key);
        $token = md5($str . $this->fabi_used_key);
        //校验TOKEN
        if (trim($_POST['token']) != $token) {
            $this->ajax('token参数错误');
        }
        $phone = trim($_POST['mo']);
        $area = trim($_POST['area']);
        $email = trim($_POST['email']);
        if($phone=='')
        {
            $where="email='{$email}'";
        }
        else
        {
            $where="area='{$area}' and mo={$phone}";
        }
        $usermo=new UserModel();
        $data=$usermo->where($where)->fList();
        if(empty($data)){
            $this->ajax('用户不存在');
        }
        if ($data[0]['pwdtrade']) {
            $this->ajax('已设置交易密码', 1);
        } else {
            $this->ajax('未设置交易密码哦');
        }

    }

    //校验交易密码
    public function checkTradepwdAction()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies
        header("Content-Type: application/json; charset=utf-8");
        if (!isset($_POST['token'], $_POST['mo'], $_POST['pwdtrade'], $_POST['area'],$_POST['email'])) {
            $this->ajax('参数错误');
        }
        $data = array(
            'area' => $_POST['area'],
            'mo' => $_POST['mo'],
            'email' => $_POST['email'],
            'pwdtrade' => $_POST['pwdtrade']
        );
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            $str .= $k . $v;
        }
        //$token = md5($str . $this->key);
        $token = md5($str . $this->fabi_used_key);
        //校验TOKEN
        if (trim($_POST['token']) != $token) {
            $this->ajax('token参数错误');
        }
        $mo = trim($_POST['mo']);
        $email = trim($_POST['email']);
        $area = trim($_POST['area']);
        $pwdtrade = trim($_POST['pwdtrade']);
        if($mo=='')
        {
            $where="email='{$email}'";
        }
        else
        {
            $where="area='{$area}' and mo={$mo}";
        }
        $usermo = new UserModel();
        $data = $usermo->where($where)->fList();
        if (empty($data)) {
            $this->ajax('用户不存在');
        }
        //$pwd = Tool_Md5::encodePwdTrade($pwdtrade, $data[0]['prand']);
        $pwd = Tool_Md5::encodePwdTrade1($pwdtrade, $data[0]['prand']);//$pwdtrade md5加密过
        if ($pwd == $data[0]['pwdtrade']) {
            $this->ajax('交易密码正确', 1);
        } else {
            $this->ajax('交易密码错误哦');
        }
    }

    //获取用户易币余额接口
    public function getUserallcoinAction()
    {
        if (!isset( $_POST['mo'],$_POST['area'],$_POST['email'])) {
            $this->ajax('参数错误');
        }

        $mo=trim($_POST['mo']);
        $email=trim($_POST['email']);
        $area = trim($_POST['area']);

       $data=$this->getyibiuser($email,$area, $mo, '');
        if($data['status']==0){
            $this->ajax('获取用户法币余额失败');
        } else {

            $arr = $data['data'];
            unset($arr['mo']);
            foreach ($arr as &$v) {//去0
                $v = trim(preg_replace('/(\.\d*?)0+$/', '$1', $v), '.');
            }
            $this->ajax('操作成功', 1, $arr);
        }

    }

    //用户余额内转接口
    public function userInturnAction()
    {
        //show($this->yibiuserover(''));
        //show($this->dobiturnyibi('+86', '15900000000', 'btc', 1, $type =1));//1 易币加 2易币减
        //show($this->getyibiuser('+86','15113816345',''));
        $this->_ajax_islogin();
        if (!isset( $_POST['coin'], $_POST['number'], $_POST['opt_type'])
            || !is_numeric($_POST['number'])
            ||!preg_match('/^[a-z\d]+$/i', $_POST['coin'])
            ||Tool_Math::comp(0, $_POST['number'])>=0
        ) {
            $this->ajax($GLOBALS['MSG']['TEL_ZJ_CS']);//參數錯誤
        }

        $errlogdir = APPLICATION_PATH . $this->logdir . '/' . date('Ymd');//日志文件
        $uid = (int)trim($_SESSION['user']['uid']);
        $coin = strtolower(trim($_POST['coin']));
        $number = trim($_POST['number']);
        $opt_type = trim($_POST['opt_type']);
        //实例化
        $data=array(
            'captcha' => trim($_POST['captcha'])
        );
        $validatelogicModel = new ValidatelogicModel();
        $result = $validatelogicModel->scene('userinturn_captcha')->check($data);
        if (!$result) {
            foreach ($validatelogicModel->getError() as $k => $v) {
                // 错误信息
                $errorData = $v;
                // 错误字段
                $errorM = $k;
            }
            $this->ajax($errorData, '0', $errorM);
        }
        if (!in_array($opt_type, array('in', 'out'))) {
            $this->ajax('opt_type' . $GLOBALS['MSG']['TEL_ZJ_CS']);//opt_type參數錯誤
        }

        $coinmo = new CoinModel();
        $coindata = $coinmo->where(array('name' => $coin, 'status' => 0))->fList();
        if (empty($coindata)) {
            $this->ajax($GLOBALS['MSG']['COINTYPE_NOT_EXISIT']);//該幣種不存在
        }
        if ($coindata[0]['coin_transfer'] == 1) {
            $this->ajax($GLOBALS['MSG']['TANASOUT_STOP']);//划转暂停
        }
        $usermo = new UserModel();
        $usermo->begin();
        $data = $usermo->where(array('uid' => $uid))->lock()->fList();
        if (empty($data)) {
            $usermo->back();
            $this->ajax($GLOBALS['MSG']['USER_NOT_EXISIT']);//用戶不存在
        }
        $forbiddenmo = new UserForbiddenModel();
        $forbiddenlist = $forbiddenmo->where("status=0 and uid=$uid")->fList();
        if (isset($forbiddenlist[0]['otctransfer']) && $forbiddenlist[0]['otctransfer'] == 0) {//是否冻结
            $usermo->back();
            $this->ajax($GLOBALS['MSG']['INTURN_FORBEDDEN']);//该用户已冻结，暂无法互转
        }
        if (bccomp($number, $coindata[0]['otc_min'], 20) < 0) {//资金划转最小限额
            $usermo->back();
            $nb = trim(preg_replace('/(\.\d*?)0+$/', '$1', $coindata[0]['otc_min']), '.');
            $this->ajax($GLOBALS['MSG']['MIN_COINOUT'] . $nb, 0, array('min' => $nb));//资金划转最小限额
        }
        if ($opt_type == 'out') {//转出到易币
            $max = min($data[0][$coin . '_over'], $coindata[0]['otc_max']);//资金划转最大限额
        }else{
            $yibidata = $this->getyibiuser($data[0]['email'],$data[0]['area'], $data[0]['mo'], $coin);//获取易币可用余额
            if ($yibidata['status'] == 0) {
                $usermo->back();
                $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY']);//系統繁忙
            }
            $max = min($yibidata['data'][$coin . '_over'], $coindata[0]['otc_max']);//资金划转最大限额
        }
        $nb = trim(preg_replace('/(\.\d*?)0+$/', '$1', $max), '.');
        if (bccomp($number, $max, 20) > 0) {
            $usermo->back();
            $this->ajax($GLOBALS['MSG']['MAX_COINOUT'] . $nb, 0, array('max' => $nb));//资金划转最大限额
        }


        //判断余额
        if ($opt_type == 'out') {
            if (bccomp($number, $data[0][$coin . '_over'], 20) > 0) {
                $usermo->back();
                $this->ajax($GLOBALS['MSG']['DOBI_OVER_NOTENOUGH']);//幣幣賬戶可用餘額不足
            }
        } else {
            /*$yibidata=$this->getyibiuser($data[0]['area'], $data[0]['mo'], $coin);//获取易币可用余额
            if($yibidata['status']==0){
                $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY']);//系統繁忙
            }*/
            if (bccomp($number, $yibidata['data'][$coin . '_over'], 20) > 0) {
                $usermo->back();
                $this->ajax($GLOBALS['MSG']['YIBI_OVER_NOTENOUGH']);//法幣賬戶可用餘額不足
            }
        }
        $str = 'Exchange_' . ucfirst($coin) . 'Model';
        if ($coin == 'eth') {
            $txid = 'eth-' . $uid . '-' . time();
        } else {
            $txid = rand(11111, 99999) . '6666' . rand(11111, 99999) . '8888' . rand(11111, 99999);
        }
        $exchangemo = new $str();
        $exdata = array('uid' => $uid, 'number' => $number, 'txid' => $txid, 'opt_type' => $opt_type, 'status' => '成功', 'admin' => 1,
            'created' => time(), 'createip' => Tool_Fnc::realip(), 'updated' => time(), 'is_out' => 1, 'bak' => '账户互转');

        $exchange3 = array('uid' => 3, 'number' => $number, 'txid' => $txid, 'opt_type' => $opt_type == 'out' ? 'in' : 'out', 'status' => '成功',
            'admin' => 1, 'created' => time(), 'createip' => Tool_Fnc::realip(), 'updated' => time(), 'is_out' => 1, 'bak' => '账户互转');

        $coinover = "{$coin}_over";
        if ($opt_type == 'out') {//从火网转到易币
            $type = 1;
            $sql = "update user set $coinover=$coinover-$number where uid={$uid}";
            $sql2 = "update user set $coinover=$coinover+$number where uid=3";//转到易币的记录账号
        } else {////从易币转到火网
            $type = 2;
            $sql = "update user set $coinover=$coinover+$number where uid={$uid}";
            $sql2 = "update user set $coinover=$coinover-$number where uid=3";//转到易币的记录账号
        }
        $yibichange=$this->dobiturnyibi($data[0]['email'],$data[0]['area'], $data[0]['mo'], $coin, $number, $txid ,$type);//$type=1 易币加 2易币减
//        var_dump($yibichange);die;
        if($yibichange['status']==0){//操作易币失败
            $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY']);//系統繁忙
        } else {
            //$usermo->begin();
            if (!$usermo->exec($sql)) {
                $usermo->back();
                Tool_Log::wlog(sprintf("更新用户失败, sql:%s ", $usermo->getLastSql()), $errlogdir, true);
                Tool_Fnc::mailto('1010156896@qq.com', $_SERVER['HTTP_ORIGIN'] . '账户互转错误', sprintf("更新用户失败, sql:%s ", $usermo->getLastSql()));
                $this->ajax($GLOBALS['MSG']['HUZHUAN_ERROR']);//賬戶互轉錯誤，請聯繫客服
                return;
            }
            if (!$usermo->exec($sql2)) {
                $usermo->back();
                Tool_Log::wlog(sprintf("更新账户3失败, sql:%s ", $usermo->getLastSql()), $errlogdir, true);
                Tool_Fnc::mailto('1010156896@qq.com', $_SERVER['HTTP_ORIGIN'] . '账户互转错误', sprintf("更新账户3失败, sql:%s ", $usermo->getLastSql()));
                $this->ajax($GLOBALS['MSG']['HUZHUAN_ERROR']);//賬戶互轉錯誤，請聯繫客服
                return;
            }
            if (!$exchangemo->insert($exdata)) {
                $usermo->back();
                Tool_Log::wlog(sprintf("更新exchange账户失败, sql:%s ", $exchangemo->getLastSql()), $errlogdir, true);
                Tool_Fnc::mailto('1010156896@qq.com', $_SERVER['HTTP_ORIGIN'] . '账户互转错误', sprintf("更新exchange账户失败, sql:%s ", $usermo->getLastSql()));
                $this->ajax($GLOBALS['MSG']['HUZHUAN_ERROR']);//賬戶互轉錯誤，請聯繫客服
                return;
            }
            if (!$exchangemo->insert($exchange3)) {
                $usermo->back();
                Tool_Log::wlog(sprintf("更新exchange账户3失败, sql:%s ", $exchangemo->getLastSql()), $errlogdir, true);
                Tool_Fnc::mailto('1010156896@qq.com', $_SERVER['HTTP_ORIGIN'] . '账户互转错误', sprintf("更新exchange账户3失败, sql:%s ", $usermo->getLastSql()));
                $this->ajax($GLOBALS['MSG']['HUZHUAN_ERROR']);//賬戶互轉錯誤，請聯繫客服
                return;
            }
            if(!$usermo->commit()){
                $usermo->back();
                $this->ajax($GLOBALS['MSG']['SYSTEM_BUSY']);//系統繁忙
            }else{
                Tool_Session::mark($uid);
                Cache_Redis::instance()->del($_COOKIE[SESSION_NAME]);
                $this->ajax($GLOBALS['MSG']['HUZHUAN_SUCCESS'], 1);//賬戶互轉成功
            }
        }
    }

    //获取易币用户余额
    public function getyibiuser($email,$area,$mo,$coin){
        $data=array(
            'email'=>$email,
            'area'=>$area,
            'mo'=>$mo,
            'coin'=> $coin,
            'timestamp'=>time()
        );
        ksort($data);
        $str = '';
        foreach ($data as $key => $v) {
            $str .= $key . $v;
        }
        //$token = md5($str . 'asdcsd');
        $token = md5($str . $this->dobi_used_key);

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

    //向易币发起内转
    public function dobiturnyibi($email,$area, $mo, $coin,$number, $txid ,$type=1)//$type=1 从易币转出到火网 $type=2 从火網转到易币
    {

        $data = array(
            'email' => $email,
            'area' => $area,
            'mo' => $mo,
            'coin' => $coin,
            'timestamp' => time(),
            'number' => $number,
            'txid' => $txid,
            'type' => $type
        );
        ksort($data);
        $str = '';
        foreach ($data as $key => $v) {
            $str .= $key . $v;
        }
        //$token = md5($str . 'asdcsd');
        $token = md5($str . $this->dobi_used_key);

        $data['token'] = $token;
        //header("Content-Type:text/json;charset=utf-8");
        $headers = array('Content-Type:application/x-www-form-urlencoded', 'charset=utf-8');
        $yibiurl = Yaf_Registry::get("config")->yibi->ip;
        $url = $yibiurl . 'api_user/exchange';
        $json = http_build_query($data);

        // $strResult = $this->callInterfaceCommon($url, "POST", $json, $headers);
        $strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);
        $ddd = substr($strResult, strpos($strResult, "{"));//去掉json前面的东东
        $ars = json_decode($ddd, true);
        return $ars;

    }

    //查询易币平台币种总余额
    public function yibiuserover($coin)//币种不传，表示查全部币种
    {
        $data = array(
            'coin' => $coin,
            'timestamp' => time()
        );
        ksort($data);
        $str = '';
        foreach ($data as $key => $v) {
            $str .= $key . $v;
        }
        //$token = md5($str . 'asdcsd');
        $token = md5($str . $this->dobi_used_key);
        $data['token'] = $token;
        $headers = array('Content-Type:application/x-www-form-urlencoded');
        $yibiurl = Yaf_Registry::get("config")->yibi->ip;
        $url = $yibiurl . 'api_user/useroverall';
        $json = http_build_query($data);
        //show($json);
        //$strResult = $this->callInterfaceCommon($url, "POST", $json, $headers);
        $strResult = Tool_Fnc::callInterfaceCommon($url, "POST", $json, $headers);
        $ddd = substr($strResult, strpos($strResult, "{"));//去掉json前面的东东
        $ars = json_decode($ddd, true);
        return $ars;
    }

    //阅读币充值交易排名赛接口 时间要改
    public function readAction()
    {
        $this->_ajax_islogin();
        $starttime = 1527213600;
        $endtime = 1527577200;
        //$starttime = 0;
        //$endtime = time();
        $usermo = new UserModel();
        $uid = $_SESSION['user']['uid'];
        $totalsql = "select * from (select f.uid,f.mo,sum(f.innumber-f.outnumber+f.buynumber-f.salenumber) total from
(select a.uid,a.mo,IFNULL(b.innumber,0) innumber,IFNULL(c.outnumber,0) outnumber,IFNULL(d.buynumber,0) buynumber,IFNULL(e.salenumber,0) salenumber from
(select uid,mo from user where (read_over+read_lock)>0 and uid>10 order by (read_over+read_lock) desc,uid asc) a LEFT JOIN
(select uid,sum(number) innumber from exchange_read where opt_type='in' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) b on a.uid=b.uid left join
(select uid,sum(number) outnumber from exchange_read where opt_type='out' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) c on a.uid=c.uid left join
(select buy_uid,sum(number) buynumber from order_readcoin where created BETWEEN $starttime and $endtime group by buy_uid) d on a.uid=d.buy_uid left join
(select sale_uid,sum(number) salenumber from order_readcoin where created BETWEEN $starttime and $endtime group by sale_uid) e on a.uid=e.sale_uid) as f GROUP BY f.uid) as e where e.total>0 order by e.total desc,e.uid asc";
        $list = $usermo->query($totalsql);
        $arr = array_column($list, "uid");
        $key = array_search($uid, $arr);
        $totallist = array_column($list, "total", 'uid');

        $usertotal = $totallist[$uid];

        $mydata = array(
            'my_order' => !empty($usertotal)?$key + 1:'',
            'my_total' => floor($usertotal),
        );
        $data['my'] = $mydata;
        if (count($list) == 0) {
            $data['list'] = '';
            $data['pagetotal'] = 0;
            $data['prev'] = '';
            $data['next'] = '';
            $data['currentpage'] = '';
        } else {
            $page = $_POST['page'] ? (int)addslashes($_POST['page']) : 1;//页码
            $pagenumber = $_POST['size'] ? (int)addslashes($_POST['size']) : 20;//每页多少条
            $data['pagetotal'] = ceil(count($list) / $pagenumber);//总页数
            if ($page > $data['pagetotal']) {
                $page = $data['pagetotal'];
            }
            if ($page < 1) {
                $page = 1;
            }
            if($page >5){//最多5页
                $page = 5;
            }
            $p = ($page - 1) * $pagenumber;
            $sql = "select * from (select f.uid,f.mo,sum(f.innumber-f.outnumber+f.buynumber-f.salenumber) total from
(select a.uid,a.mo,IFNULL(b.innumber,0) innumber,IFNULL(c.outnumber,0) outnumber,IFNULL(d.buynumber,0) buynumber,IFNULL(e.salenumber,0) salenumber from
(select uid,mo from user where (read_over+read_lock)>0 and uid>10) a LEFT JOIN
(select uid,sum(number) innumber from exchange_read where opt_type='in' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) b on a.uid=b.uid left join
(select uid,sum(number) outnumber from exchange_read where opt_type='out' and txid<>'' and updated BETWEEN $starttime and $endtime and status='成功' group by uid) c on a.uid=c.uid left join
(select buy_uid,sum(number) buynumber from order_readcoin where created BETWEEN $starttime and $endtime group by buy_uid) d on a.uid=d.buy_uid left join
(select sale_uid,sum(number) salenumber from order_readcoin where created BETWEEN $starttime and $endtime group by sale_uid) e on a.uid=e.sale_uid) as f GROUP BY f.uid) as e where e.total>0 order by e.total desc,e.uid asc limit $p,$pagenumber";
            $rankinglist = $usermo->query($sql);
            foreach ($rankinglist as &$v) {
                unset($v['uid']);
                $v['mo']= '*******'.substr($v['mo'], -3);
                $v['total']=floor($v['total']);
                //$v['total'] = trim(preg_replace('/(\.\d*?)0+$/', '$1', $v['total']), '.');
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
            $data['list'] = $rankinglist;
        }
        $this->ajax('成功', 1, $data);
    }
}
