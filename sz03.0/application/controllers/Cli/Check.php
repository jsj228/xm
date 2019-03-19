<?php


class Cli_CheckController extends Ctrl_Cli{
    public static $log_file = '';

    //将master进程id保存到这个文件中
    public static $pid_file = '';

    //保存worker进程的状态
    public static $status_file = '';

    //是否使用守护进程模式启动
    public static $deamonize = false;

    public static $master_pid = 0;

    public static $stdoutFile = '/dev/null';

    public static $workers = [];

    //worker实例
    public static $instance = null;

    //worker数量
    public $count = 2;

    //worker启动时的回调方法
    public $onWorkerStart = null;

    public function __construct(){
        static::$instance = $this;
    }

    //记录当前进程的状态
    public static $status = 0;

    //运行中
    const STATUS_RUNNING = 1;
    //停止
    const STATUS_SHUTDOWN = 2;


    public static function runAction(){
        static::checkEnv();
        static::inits();
        static::parseCommand();
        static::deamonize();
        static::saveMasterPid();
        static::installSignal();
        static::resetStd();
        static::forkWorkers();
        static::monitorWorkers();

        exit('哈哈哈');
    }



    public static function monitorWorkers(){
        //设置当前状态为运行中
        static::$status = static::STATUS_RUNNING;
        while (1) {
            pcntl_signal_dispatch();
            $status = 0;
            //阻塞，等待子进程退出
            $pid = pcntl_wait($status, WUNTRACED);

            self::log("worker[ $pid ] exit with signal:".pcntl_wstopsig($status));

            pcntl_signal_dispatch();
            //child exit
            if ($pid > 0) {
                //意外退出时才重新fork，如果是我们想让worker退出，status = STATUS_SHUTDOWN
                if (static::$status != static::STATUS_SHUTDOWN) {
                    unset(static::$workers[$pid]);
                    static::forkOneWorker(static::$instance);
                }
            }
        }
    }



    public static function forkWorkers(){

        $worker_count = static::$instance->count;

        while(count(static::$workers) < $worker_count ){
            static::forkOneWorker(static::$instance);
        }
    }

    public static function forkOneWorker($instance){
        $pid = pcntl_fork();
        if($pid > 0){
            static::$workers[$pid] = $pid;
        }elseif($pid == 0){
            static::log('创建了一个worker');
            static::setProcessTitle('myworker process');
            //运行
            $instance->run();
        }else{
            throw new Exception('fork one worker fail');
        }
    }

    public function run(){
        if($this->onWorkerStart){
            try {
                //worker启动，调用onWorkerStart回调
                call_user_func($this->onWorkerStart, $this);
            } catch (\Exception $e) {
                static::log($e);
                sleep(1);
                exit(250);
            } catch (\Error $e) {
                static::log($e);
                sleep(1);
                exit(250);
            }
        }
        //死循环，保持worker运行，并且一有信号来了就调用信号处理函数
        while (1) {
            pcntl_signal_dispatch();
            sleep(1);
        }
    }







    public static function resetStd(){
        if(static::$deamonize == false){
            return;
        }
        global $STDOUT, $STDERR;
        $handle = fopen(self::$stdoutFile, "a");
        if ($handle) {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen(self::$stdoutFile, "a");
            $STDERR = fopen(self::$stdoutFile, "a");
        } else {
            throw new Exception('can not open stdoutFile ' . self::$stdoutFile);
        }
    }




    public static function installSignal()
    {
        pcntl_signal(SIGINT, array(__CLASS__, 'signalHandler'), false);
        pcntl_signal(SIGUSR2, array(__CLASS__, 'signalHandler'), false);
        //SIG_IGN表示忽略该信号，不做任何处理。SIGPIPE默认会使进程退出
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    }

    public static function signalHandler($signal){
        switch ($signal) {
            case SIGINT: // Stop.
                //static::stopAll();
                break;
            case SIGUSR2: // Show status.
                //static::writeStatus();
                break;
        }
    }


    public static function saveMasterPid(){
        static::$master_pid = posix_getpid();
        if(false === @file_put_contents(static::$pid_file, static::$master_pid)){
            throw new Exception('fail to save master pid ');
        }
    }


    public static function deamonize(){

        if(static::$deamonize == false){
            return;
        }

        umask(0);

        $pid = pcntl_fork();

        if($pid > 0){
            exit(0);
        }elseif($pid == 0){
            if(-1 === posix_setsid()){
                throw new Exception("setsid fail");
            }
            static::setProcessTitle('myworker: master');
        }else{
            throw new Exception("fork fail");
        }
    }

    public static function setProcessTitle($title){
        //设置进程名
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        }
    }





























    public static function parseCommand(){
        global $argv;

        if(!isset($argv[1]) || !in_array($argv[1],['start','stop','status'])){
            exit('usage: php your.php start | stop | status !' . PHP_EOL);
        }

        $command1 = $argv[1]; //start , stop , status
        $command2 = $argv[2]; // -d

        //检测master是否正在运行
        $master_id = @file_get_contents(static::$pid_file);
        //向master进程发送0信号，0信号比较特殊，进程不会响应，但是可以用来检测进程是否存活
        $master_alive = $master_id && posix_kill($master_id,0);

        if($master_alive){
            //不能重复启动
            if($command1 == 'start' && posix_getpid() != $master_id){
                exit('worker is already running !'.PHP_EOL);
            }
        }else{
            //项目未启动的情况下，只有start命令有效
            if ($command1 != 'start') {
                exit('worker not run!' . PHP_EOL);
            }
        }

        switch($command1){
            case 'start':
                if($command2 == '-d'){
                    static::$deamonize = true;
                }
                break;
            case 'stop':
                //停止进程
                //必须exit退出
                exit(0);
                break;
            case 'status':
                //查看状态
                //必须exit退出
                exit(0);
                break;
            default:
                exit('usage: php your.php start | stop | status !' . PHP_EOL);
                break;
        }

    }

    public static function inits(){

        //$temp_dir = sys_get_temp_dir() . '/myworker/';
        $temp_dir = APPLICATION_PATH.'/log/tmp/';

        if (!is_dir($temp_dir) && !mkdir($temp_dir)) {
            exit('mkdir runtime fail');
        }
        $test_file = $temp_dir . 'test';
        //尝试创建文件
        if(touch($test_file)){
            @unlink($test_file);
        }else{
            exit('permission denied: dir('.$temp_dir.')');
        }

        if (empty(static::$status_file)) {
            static::$status_file = $temp_dir . 'status_file.status';
        }

        if (empty(self::$pid_file)) {
            static::$pid_file = $temp_dir . 'master.pid';
        }

        if (empty(self::$log_file)) {
            static::$log_file = $temp_dir . 'worker.log';
        }

        static::log('初始化完成');
    }

    public static function log($message)
    {
        $message = '['.date('Y-m-d H:i:s') .']['. $message . "]\n";
        file_put_contents((string)self::$log_file, $message, FILE_APPEND | LOCK_EX);
    }

    public static function checkEnv(){

        if (php_sapi_name() != 'cli') {
            exit('请使用命令行模式运行!');
        }

        if(!function_exists('posix_kill')){
            exit('请先安装posix扩展'."\n");
        }

        if(!function_exists('pcntl_fork')){
            exit('请先安装pcntl扩展'."\n");
        }
    }

}


