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
//        $this->checkProcessRunningLock();

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

        $i=0;
        //重复劳动
        while (true) {
//            $trustMo  = $this->model['Trust_Coin']->designTable($coin['coin_from']);
//            $userMo   = $this->model['User'];
//            try{
                $this->coin2coin($coin,$i);
//            }catch (Exception $e){
//
//            }
            $i++;
            //处理信号
            pcntl_signal_dispatch();
        }
    }

    /**
     * 刷单交易主要逻辑
     */
    public function coin2coin($coin,$i)
    {

        $trustMo = $this->model['Trust_Coin']->designTable($coin['coin_from']);
//        $userMo = $this->model['User'];
        $orderMo = $this->model['Order_Coin']->designTable($coin['coin_from']);


        $trade = $trustMo->getTradeOnePrice($coin);

//        $diff_price = ($trade['sale_price']-$trade['buy_price'])/10;



        Tool_Out::p($trade);
        echo PHP_EOL;
        $orderMo->in_order($coin,$trade);

//        Tool_Out::p($coin);

        echo '1---'.$coin['robot_space'];

        //等待
        $sleep = mt_rand($coin['robot_space']- 30, $coin['robot_space'] + 30);//
        sleep($sleep);
        return true;
    }


}