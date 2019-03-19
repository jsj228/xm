<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 2019/2/6
 * Time: 12:32
 */

//刷单机器人 (多进程)
class Cli_BrushrobotController extends Ctrl_Cli
{
    //启动标识文件
    private $processRunning = 'brushrobot.lock';

    //model实例
    private $model = [];

    //延迟处理的trust_id
    private $delayId = 0;

    //空数据查询间隔秒
    private $sleep = 1;

    //进程列表
    private $process = [];

    //错误
    private $error = [];

    //日志文件
    private $logDir = [
        'process' => 'process',
        'trade' => 'trade',
    ];

    public function init()
    {
        $this->mo = new Orm_Base();
    }

    //程序入口
    public function runAction()
    {
        //如果进程正在运行，需要删掉‘启动标识文件’
        $this->checkProcessRunningLock();

        $coinPairMo = new Coin_PairModel;
        $coinList = $coinPairMo->where('status=' . Coin_PairModel::STATUS_ON . " and robot='on'")->fList();
        if ($coinList) $this->distribute($coinList);//分发任务

    }

    /**
     * 分发任务
     */
    private function distribute($list)
    {
        //创建子进程
        foreach ($list as $v) {
            //检查是否有重复任务
            if (isset($this->process[$v['name']])) {
                echo 'duplicate process name ' . $v['name'] . PHP_EOL;
                $this->killAll();
            } else {
                $this->process[$v['name']] = $v;
            }

            $pid = pcntl_fork();
            if ($pid == 0) {
                $this->setProcessTitle($v['name']);
                $this->process[$v['name']]['process_id'] = posix_getpid();
                break;
            } else {
                $this->process[$v['name']]['process_id'] = $pid;
            }
        }

        //工作
        if ($pid == 0) {
            $myPid = posix_getpid();

            //根据当前进程id，找到自己的任务
            $namePidMap = array_column($this->process, 'name', 'process_id');

            //Tool_Log::wlog(sprintf("child process pid:%d, name:%s ", $myPid, $namePidMap[$myPid]), $this->logDir['process'], true, '[m-d H:i:s]');
            //开始工作
            $this->work($this->process[$namePidMap[$myPid]]);
        } else {
            //这里是父进程，监控所有子进程状态
            pcntl_signal(SIGTERM, [&$this, "sigHandler"], false);
            pcntl_signal(SIGUSR2, [&$this, "sigHandler"], false);
            //Tool_Log::wlog(sprintf("father process pid:%d ", posix_getpid()), $this->logDir['process'], true, '[m-d H:i:s]');
            $this->oversee();

        }
    }

    /**
     * 结束所有进程
     */
    protected function killAll($msg = '')
    {
        foreach ($this->process as $v) {
            posix_kill($v['process_id'], SIGKILL);
        }

        //删除启动标识
        unlink($this->processRunning);

        $msg = $msg ? sprintf('(%s)', $msg) : '';
        Tool_Log::wlog('kill all process' . $msg, $this->logDir['process'], true, '[m-d H:i:s]');
        exit(0);
    }

    /**
     *  进程命名
     */
    private function setProcessTitle($processName)
    {
        //命名
        $title = sprintf('├─Cli_brushrobot───%s', $processName);
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        }
    }

    /**
     * 监控子进程
     */
    private function oversee()
    {
        while (true) {
            //阻塞等待任意子进程退出
            $pid = pcntl_wait($status, WUNTRACED);
            //处理信号
            pcntl_signal_dispatch();
            //查找具体退出进程
            if ($pid > 0) {
                $namePidMap = array_column($this->process, 'name', 'process_id');
                Tool_Log::wlog(sprintf("%s die; pid: %d ", $namePidMap[$pid], $pid), $this->logDir['process'], true, '[m-d H:i:s]');
                unset($this->process[$namePidMap[$pid]]['process_id']);
                //重建
                $this->createProcess($namePidMap[$pid]);
            }
        }
    }

    /**
     * 创建新进程
     */
    private function createProcess($processName)
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            //给父进程更新监听列表
            $this->process[$processName]['process_id'] = $pid;
        } elseif ($pid == 0) {
            $this->setProcessTitle($processName);
            //子进程投入工作
            Tool_Log::wlog(sprintf("created %s; pid: %d ", $processName, posix_getpid()), $this->logDir['process'], true, '[m-d H:i:s]');
            //信号委托
            pcntl_signal(SIGTERM, function () {
                exit;
            });
            $this->work($this->process[$processName]);
        }
    }

    /**
     * 避免重复启动
     */
    private function checkProcessRunningLock()
    {
        if (file_exists($this->processRunning)) {
            throw new Exception('the process is already running, check process and the lock file' . PHP_EOL);
        }
        file_put_contents($this->processRunning, posix_getpid());
    }

    /**
     * 用到的model实例(注意：每个进程一定要使用不同的数据库连接)
     */
    private function initModels($connect = 'default')
    {
        $models = ['Order_Coin', 'Trust_Coin', 'User', 'UserForbidden', 'Coin_Pair', 'Address'];
        foreach ($models as $v) {
            $m = $v . 'Model';
            $this->model[$v] = new $m('', 'default', $connect);
        }
    }

    /**
     * 开始刷单
     */
    private function work($coin)
    {
        //日志分币存放
//        $this->logDir['trade'] .= '/'.$coin['name'];
        $this->initModels($coin['name']);

        //记录当前的mysql thread_id，便于出现问题后定位

        //初始化行情数据

        //重复劳动
        while (true) {
//            $trustMo  = $this->model['Trust_Coin']->designTable($coin['coin_from']);
//            $userMo   = $this->model['User'];
            $this->coin2coin($coin, $coin['robot_space']);
            //处理信号
            pcntl_signal_dispatch();
        }
    }

    /**
     * 刷单交易主要逻辑
     */
    public function coin2coin($coin, $sleep = 60)
    {

        try {
            $trustMo = $this->model['Trust_Coin']->designTable($coin['coin_from']);
            $userMo = $this->model['User'];

            $buy_where = "coin_from='{$coin['coin_from']}' and coin_to='{$coin['coin_to']}' and flag='buy' and status=0 and numberover>0";
            $buy_price = $trustMo->where($buy_where)->order("price desc")->fOne('price');

            $sale_where = "coin_from='{$coin['coin_from']}' and coin_to='{$coin['coin_to']}' and flag='sale' and status=0 and numberover>0";
            $sale_price = $trustMo->where($sale_where)->order("price asc")->fOne('price');

            if (in_array($coin['coin_from'], ['doge', 'eac', 'oioc', 'ifc', 'bcx'])) {
                $holdF = mt_rand(5, 7);
            } elseif (in_array($coin['coin_from'], ['sie', 'btm'])) {
                $holdF = mt_rand(2, 4);
            } else {
                $holdF = mt_rand(1, 4);
            }

            $buyPrice = round($buy_price + ($sale_price - $buy_price) * mt_rand(6, 10) / 10, $holdF);
            $buy_num = round(mt_rand($coin['robot_min_num'], $coin['robot_max_num']) - mt_rand() / mt_getrandmax(), mt_rand(0, 4));

            $sale_num = round(mt_rand($coin['robot_min_num'], $coin['robot_max_num']) - mt_rand() / mt_getrandmax(), mt_rand(0, 4));
            $salePrice = round($buy_price + ($sale_price - $buy_price) * mt_rand(1, 5) / 10, $holdF);

            if ($buyPrice >= $sale_price || $buyPrice <= $buy_price) {
                $buyPrice = $buy_price + ($sale_price - $buy_price) * mt_rand(6, 8) / 10;
            }
            if ($salePrice <= $buy_price || $salePrice >= $sale_price) {
                $salePrice = $buy_price + ($sale_price - $buy_price) * mt_rand(3, 5) / 10;
            }

            $buy_input = ['type' => 'in', 'price' => $buyPrice, 'number' => $buy_num, 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];
            $sale_input = ['type' => 'out', 'price' => $salePrice, 'number' => $sale_num, 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];

            //机器人账号
            $user = $userMo->where(['mo' => '18800000000'])->fRow();

            $random = mt_rand(1, 10);

            if ($random % 2) {
                //下成交买卖单
                $buy = $this->setTrust($buy_input, $user);
                $sale = $this->setTrust($sale_input, $user);
            } else {
                $sale = $this->setTrust($sale_input, $user);
                $buy = $this->setTrust($buy_input, $user);
            }

            //暂停10毫秒 使订单成交
            usleep(10000);

            //撤销订单
            if ($buy['code'] == 1000) {
                echo '下买单成功' . $sale['id'];
                $buy_cancel_input = ['id' => $buy['id'], 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];
                $buy_cancel_id = $this->trustcancel($buy_cancel_input, $user);
                echo "撤销买单成功" . $buy_cancel_id;
                Tool_Out::p($buy_cancel_id);
            }
            if ($sale['code'] == 1000) {
                echo '下卖单成功' . $sale['id'];
                $sale_cancel_input = ['id' => $sale['id'], 'coin_from' => $coin['coin_from'], 'coin_to' => $coin['coin_to']];
                $sale_cancel_id = $this->trustcancel($sale_cancel_input, $user);
                echo "撤销买单成功" . $sale_cancel_id;
                Tool_Out::p($sale_cancel_id);
            }

            echo "\n" . $coin['coin_from'] . "\n";
            echo '买一价' . $buy_price . "\n";
            echo '卖一价' . $sale_price . "\n";
            echo '成交买一价:' . $buyPrice . "数量:" . $buy_num . "\n";
            echo '成交卖一价:' . $salePrice . "数量:" . $sale_num . "\n";
            echo '等待时长:' . $sleep;
            echo "结束\n\n\n\n";


        } catch (Exception $e) {
            echo '异常';
        }

        //等待
        $sleep = mt_rand($sleep - 30, $sleep + 30);
        sleep($sleep);
        return true;
    }


    //判断用户是买还是卖
    public function setTrust($input, $user)
    {

        $_POST = $input;
        $this->mCurUser = $user;

        $coin_pairMo = $this->model['Coin_Pair'];
        $orderMo = $this->model['Order_Coin']->designTable($_POST['coin_from']);
        $Trust_CoinMo = $this->model['Trust_Coin']->designTable($_POST['coin_from']);

        //验证参数
        if (!isset($_POST['type'], $_POST['price'], $_POST['number'], $_POST['coin_from'], $_POST['coin_to'])) {
            $this->ajax($GLOBALS['MSG']['PARAM_ERROR'], 2);
        }

        if (!Tool_Validate::az09($_POST['coin_from']) || !$pair = $coin_pairMo->getPair($_POST['coin_from'] . '_' . $_POST['coin_to'])) {
            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
        }

        //未開放交易
        if ($pair['start'] > 0 && time() < $pair['start']) {
            $this->ajax($GLOBALS['MSG']['NOT_OPEN_YET']);
        }

        //验证输入价格
        if (0 >= ($_POST['price'] = (float)Tool_Str::format($_POST['price'], $pair['price_float'], 2))) {
            $this->ajax($GLOBALS['MSG']['PRICE_ERROR']);
        }

        //验证输入数量
        $_POST['number'] = (float)Tool_Str::format($_POST['number'], $pair['number_float'], 2);
        if (($pair['max_trade'] > 0 && ($_POST['number'] > $pair['max_trade']) || $_POST['number'] < $pair['min_trade'])) {
            $this->ajax(sprintf($GLOBALS['MSG']['TRAND_NUM_RANGE'], $pair['min_trade'], $pair['max_trade']));
        }

        // 闭市
        if ($pair['rule_open'] == 1) {
            //周末休市
            $week = date('w');
            if (in_array($week, explode(',', $pair['open_week']))) {
                $this->ajax($GLOBALS['MSG']['DAY_OFF']);
            }
            //节假日休市
            $day = date('md');

            if (false !== strpos($pair['open_date'], $day)) {
                $this->ajax($GLOBALS['MSG']['HOLIDAY_OFF']);
            }

            $nowHI = intval(date('Hi'));
            //开盘时间段
            if ($nowHI < intval($pair['open_start']) || $nowHI > intval($pair['open_end'])) {
                $this->ajax($GLOBALS['MSG']['TRANS_TIME'] . trim(chunk_split($pair['open_start'], 2, ':'), ':') . ' - ' . trim(chunk_split($pair['open_end'], 2, ':'), ':'));
            }
        }

        //价格限制
        if ($pair['price_limit'] == 1) {
            //涨跌幅限制
            $redis = Cache_Redis::instance();
            $hKey = sprintf('OpenPrice_%s_%s', $pair['name'], date('Ymd'));
            $openPrice = $redis->get($hKey);
            if (!$openPrice) {
                $openOrder = $orderMo->where("opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}' and created<$prevEndTime")->order('id DESC')->fRow();
                if (!$openPrice) {
                    $openPrice = $orderMo->where("opt=1 and coin_from='{$pair['coin_from']}' and coin_to='{$pair['coin_to']}'")->order('id')->fRow();
                }
                $openPrice = $openOrder['price'];
                $redis->set($hKey, $openPrice, 86400);
            }

            $price_up = bcmul($openPrice, $pair['up_percent'], $pair['price_float']);
            $price_down = bcmul($openPrice, $pair['down_percent'], $pair['price_float']);

            //挂单价格超出限制
            if (($price_up > 0 && (float)$_POST['price'] > $price_up) || (float)$_POST['price'] < $price_down) {
                $this->ajax(sprintf($GLOBALS['MSG']['PRICE_RANGE'], $price_down, $price_up));
            }
        }


        //是否冻结禁止交易
        $fData = $this->getTradeStatus($this->mCurUser['uid']);
        if ($fData && $fData['canbuy'] == 0 && $fData['cansale'] == 0) {
            $this->ajax($GLOBALS['MSG']['TRADE_FROZEN']);
        }

        if (!$this->mCurUser['pwdtrade']) {
            $this->ajax($GLOBALS['MSG']['NEED_SET_TRADEPWD'], 0, ['need_set_tpwd' => 1]);
        }

        //买入
        if ('in' == $_POST['type']) {
            //冻结禁止买入
            if ($fData && $fData['canbuy'] == 0) {
                $this->ajax($GLOBALS['MSG']['TRADE_BUY_FROZEN']);
            }

            $trustmoney = $_POST['number'] * $_POST['price'];
            //余额不足
            if ($this->mCurUser[$_POST['coin_to'] . '_over'] < $trustmoney) {
                $this->ajax($_POST['coin_to'] . ' ' . $GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        } elseif ('out' == $_POST['type']) {
            //冻结禁止卖出
            if ($fData && $fData['cansale'] == 0) {
                $this->ajax($GLOBALS['MSG']['TRADE_SALE_FROZEN']);
            }

            if ($this->mCurUser[$pair['coin_from'] . '_over'] < $_POST['number']) {
                $this->ajax($pair['coin_from'] . ' ' . $GLOBALS['MSG']['COIN_NOT_ENOUGH']);
            }

        } else {
            $this->ajax($GLOBALS['MSG']['ILLEGAL'], 2);
        }


        //入库
        $tId = $this->btc($_POST, $this->mCurUser, $Trust_CoinMo) or $this->ajax($Trust_CoinMo->getError(2));

        return ['code' => 1000, 'message' => $GLOBALS['MSG']['ORDER_SUCCESS'], 'id' => $tId];
//        return ['code'=>1,msg]
//        $this->ajax($GLOBALS['MSG']['ORDER_SUCCESS'], 1, $this->getUserCoinInfo($coinFrom, $_POST['coin_to']));
    }

    /**
     * 查询用户是否冻结禁止交易
     */
    public function getTradeStatus($uid)
    {

        $UserForbiddenMo = $this->model['UserForbidden'];
        $fdata = $UserForbiddenMo->lock()->where("uid = {$uid} and status = 0")->fRow();//->lock()

        if ($fdata) {
            return $fdata;
        } else {
            return false;
        }
    }

    /**
     * 交易
     */
    public function btc($pData, &$pUser, $api = false)
    {
        $Trust_CoinMo = $this->model['Trust_Coin']->designTable($_POST['coin_from']);
        $UserMo = $this->model['User'];

        # 保存DB
        $Trust_CoinMo->begin();
        # 买入
        if ($pData['type'] == 'in') {
            $totalPrice = Tool_Math::mul($pData['price'], $pData['number']);
            $coinData = [$pData['coin_to'] . '_lock' => $totalPrice, $pData['coin_to'] . '_over' => Tool_Math::mul('-1', $totalPrice)];
            $pData['type'] = 'buy';
        } # 卖出
        else {
            $number = $pData['number'];
            $coinData = [$pData['coin_from'] . '_lock' => $number, $pData['coin_from'] . '_over' => Tool_Math::mul('-1', $number)];
            $pData['type'] = 'sale';
        }
        # 写入
        $userMo = $UserMo;
        if (!$this->safeUpdate($pUser, $coinData, $api, $pData['coin_from'] . '_' . $pData['coin_to'])) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($userMo->error[2]);
        }
        # 写入委托
        if (!$tId = $Trust_CoinMo->insert([
            'uid' => $pUser['uid'],
            'price' => $pData['price'],
            'number' => $pData['number'],
            'numberover' => $pData['number'],
            'flag' => $pData['type'],
            'status' => 0,
            'coin_from' => $pData['coin_from'],
            'coin_to' => $pData['coin_to'],
            'created' => time(),
            'createip' => Tool_Fnc::realip(),
        ])) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($GLOBALS['MSG']['SYS_ERROR']);
        }

        //写入队列
        $r = $Trust_CoinMo->pushInQueue($pData['coin_from'] . '_' . $pData['coin_to'], [
            'id' => $tId,
        ], 'new');

        if (!$r) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($GLOBALS['MSG']['SYS_ERROR'] . '[2]');
        }

        # 提交数据
        $Trust_CoinMo->commit();
        return $tId;
    }


    /**
     * 委托撤销
     */
    public function trustcancel($input, $user)
    {
        $_POST = $input;
        $this->mCurUser = $user;
        $Trust_CoinMo = $this->model['Trust_Coin']->designTable($_POST['coin_from']);

//        $this->_ajax_islogin();
        //安全校验
//        $this->checkReqToken();

        if (!$_POST['id'] || !$_POST['coin_from'] || !$_POST['coin_to']) {
            return ['msg' => $GLOBALS['MSG']['PARAM_ERROR'], 'status' => 2];
        }

        $id = intval($_POST['id']);


        $result = $this->cancel($id, $this->mCurUser, 1);
        if (!$result) {
            return ['msg' => $Trust_CoinMo->getError(2)];
        }
        return ['code' => 1000, 'message' => $GLOBALS['MSG']['SUCCESS']];
    }

    /**
     * 撤销委托
     * @param $pId
     * @param $pUser
     * @param $ctype 1富途币，2融资
     */
    public function cancel($pId, &$pUser, $ctype = 1, $api = false)
    {
        $Trust_CoinMo = $this->model['Trust_Coin']->designTable($_POST['coin_from']);
        $UserMo = $this->model['User'];

        # 开始事务
        $Trust_CoinMo->begin();
        # 查询委托
        if (!$tTrust = $Trust_CoinMo->field('uid,number,numberover,price,flag,isnew,status,coin_from,coin_to')->lock()->fRow($pId)) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($GLOBALS['MSG']['SYS_BUSY']);
        }

        # 用户验证
        if ($tTrust['uid'] != $pUser['uid']) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($GLOBALS['MSG']['HAVE_NO_RIGHT']);
        }

        # 状态查询
        if ($tTrust['status'] > 1) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($GLOBALS['MSG']['CANCEL_FAILED']);
        }

        //买卖
        if ($tTrust['flag'] == 'buy') {
            $tMoney = Tool_Math::mul($tTrust['numberover'], $tTrust['price']);
            $tUserData = [$tTrust['coin_to'] . '_lock' => Tool_Math::mul('-1', $tMoney), $tTrust['coin_to'] . '_over' => $tMoney];
        } else {
            $tUserData = [$tTrust['coin_from'] . '_lock' => Tool_Math::mul('-1', $tTrust['numberover']), $tTrust['coin_from'] . '_over' => $tTrust['numberover']];
        }
        # 更新用户
        $tMO = $UserMo;
        if (TRUE !== $this->safeUpdate($pUser, $tUserData, $api, $tTrust['coin_from'] . '_' . $tTrust['coin_to'])) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($tMO->error[2]);
        }
        # 更新委托
        if (!$Trust_CoinMo->update(['id' => $pId, 'numberover' => 0, 'isnew' => 'N', 'status' => 3, 'updated' => time(), 'updateip' => Tool_Fnc::realip()])) {
            $Trust_CoinMo->back();
            return $Trust_CoinMo->setError($GLOBALS['MSG']['SYS_ERROR']);
        }
        $Trust_CoinMo->commit();

        //刷新委托列表
        $Trust_CoinMo->pushInQueue($tTrust['coin_from'] . '_' . $tTrust['coin_to'], [
            'n' => Tool_Math::mul($tTrust['numberover'], -1),
            'f' => $tTrust['flag'],
            'p' => $tTrust['price'],
            'uid' => $tTrust['uid'],
            's' => 3,
            'o' => 0,
            'id' => $pId,
        ]);

        Tool_Session::mark($pUser['uid']);

        return true;
    }


    /**
     * 保存 富途币
     * @param $pUser 用户数组
     * @param array $pData : rmb_lock, rmb_over, btc_lock, btc_over
     * @return bool
     */
    public function safeUpdate(&$pUser, $pData, $forced = false, $pushChannel = '')
    {
        $UserMo = $this->model['User'];
        $AddressMo = $this->model['Address'];


        if (!$pUser = $UserMo->lock()->fRow($pUser['uid'])) {
            return $UserMo->setError($GLOBALS['MSG']['SYS_BUSY']);
        }

        /*操作间隔*/
        if (!$forced && isset($_SESSION['last_user_updated'])) {
            $tTime = max($_SERVER['REQUEST_TIME'] - $_SESSION['last_user_updated'], 0);
            $tMinTime = Yaf_Registry::get("config")->opt_mintime;
            if ($tTime < $tMinTime) {
                return $UserMo->setError($GLOBALS['MSG']['WAIT_SEC'], $tMinTime - $tTime);
            }
            //记录本次操作时间
            $_SESSION['last_user_updated'] = time();
        }
        # 必更新字段
        $tData = ['uid' => $pUser['uid'], 'updated' => time(), 'updateip' => Tool_Fnc::realip()];
        $pushData = [];


        # 重要数据更新
        foreach ($pData as $k1 => $v1) {
            if (strpos($k1, "lock")) {//判断是冻结还是解冻 c++
                $ars = explode('_', $k1);
                $ccoin = $ars[0];
                $cnumber = $pData[$k1];
                if ($pData[$k1] > 0) {
                    $command = 'lock';
                } else {
                    $command = 'unlock';
                }
            }

            $tData[$k1] = Tool_Math::add($pUser[$k1], $v1, 20);
            $pushData[$k1] = $tData[$k1];
            if (Tool_Math::comp(0, $tData[$k1]) == 1) {
                $tBName = explode('_', $k1);
                $errorMsg = sprintf($GLOBALS['MSG']['THIS_COIN_NOT_ENOUGH'], $tBName[0]);
                return $UserMo->setError($errorMsg);
            }
        }

        # 更新数据库
        if (!$UserMo->update($tData)) {
            # 出现错误回滚
            return $UserMo->setError($GLOBALS['MSG']['SYS_ERROR'], 550, $UserMo->getLastSql() . ' ' . json_encode($tData));
        }
        # 合并用户数据
        $pUser = array_merge($pUser, $tData);
        Tool_Session::mark($pUser['uid']);

        //PUSH
        if ($pushChannel) {
            Tool_Push::one2nSend($pushChannel, ['t' => 'balance', 'c' => $pushData], [$pUser['uid']]);
        }
        //找到钱包地址
        $addrMo = $AddressMo;
        $addr = $this->getAddrMap($pUser['uid'], $ccoin);
        if (!empty($addr)) {
            $addr = $addr[$pUser['uid']][$ccoin];
        }

        if ($command) {
            //找到钱包地址
            $c_data = [
                'command' => $command,
                'coin' => $ccoin,
                'number' => Tool_Math::format(abs($cnumber)),
                'addr' => $addr,
            ];
            $response = Api_Trans_Client::request($c_data);//调c++
            if ($response['code'] != 0) {//失败记录日志
                $errlogdir = APPLICATION_PATH . '/log/cc/' . date('Ymd');//日志文件
                Tool_Log::wlog(sprintf("c++操作失败,uid： %s,发送数据：%s,响应结果：%s", $pUser['uid'], json_encode($c_data), json_encode($response)), $errlogdir, true);
            }
        }

        return TRUE;
    }

    /*
	 * 获取uid,钱包地址映射
	*/
    public function getAddrMap($uid, $coin)
    {
        $AddressMo = $this->model['Address'];
        //查询缓存
        static $cache;
        $cKey = md5(json_encode($uid) . json_encode($coin));
        if (isset($cache[$cKey])) {
            return $cache[$cKey];
        }

        $where = '';
        if (is_array($uid))
            $where .= sprintf('uid in (%s) ', implode(',', $uid));
        else
            $where .= sprintf('uid = %s ', $uid);

        if (is_array($coin))
            $where .= sprintf(' and coin in ("%s") ', implode('","', $coin));

        else
            $where .= sprintf(' and coin = "%s" ', $coin);


        $address = $AddressMo->field('address, uid, coin')->where($where)->fList();
        $addrMap = [];
        $uid = (array)$uid;
        foreach ($uid as $uidOne) {
            $addrMap[$uidOne] = [];
        }

        foreach ($address as $v) {
            $addrMap[$v['uid']][$v['coin']] = $v['address'];
        }

        //没有地址的新生成地址
        $coin = (array)$coin;
        foreach ($addrMap as $uid => $v) {
            foreach ($coin as $coinOne) {
                if (!isset($v[$coinOne])) {
                    $newAddrOne = $this->createAddr($uid, $coinOne);
                    if (!$newAddrOne)
                        return false;

                    $addrMap[$uid][$coinOne] = $newAddrOne;
                }
            }
        }
        //缓存
        $cache[$cKey] = $addrMap;
        return $addrMap;

    }


    /*
	* 创建新地址
	*/
    public function createAddr($uid, $coin)
    {
        $AddressMo = $this->model['Address'];

        $reqData = [
            'command' => 'apply_addr',
            "coin" => $coin,
        ];

        $result = Api_Trans_Client::request($reqData);
        if ($result && $result['code'] == 0) {
            $r = $AddressMo->insert([
                'uid' => $uid,
                'coin' => $coin,
                'address' => $result['result']['addr'],
                'created' => time(),
            ]);
            if ($r)
                return $result['result']['addr'];
        } else {
            $AddressMo->setError(sprintf('coin:%s, errorCode:%s, msg:%s', $coin, $result['code'], $result['msg']));
        }
        return false;
    }


}