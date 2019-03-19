<?php
/**
 * 前台首页
 */
class IndexController extends Ctrl_Base
{
    protected $_auth = 1;
    /**
     * 首页
     */
    public function indexAction()
    {
        $tMo            = new NewsModel();
        $lang=LANG;
        if($lang!='cn'){
            $lang='en';
        }
        $data['news'] = $tMo->field("id,title,FROM_UNIXTIME(created,'%m-%d') time")->where("language_code='$lang'")->order('is_top desc,sort desc,created desc')->limit(3)->fList();
        //$data['news']   = $tMo->field("id,title,FROM_UNIXTIME(created,'%Y-%m-%d') time")->where(array('category' => 2))->order('is_top desc,sort desc,created desc')->limit(1)->fList();

   /*     for ($i = 0; $i < count($data['news']); $i++)
        {
            if (mb_strlen($data['news'][$i]['title'], "utf-8") > 25){//限制标题长度20
                $string                      = mb_substr($data['news'][$i]['title'], 0, 24, 'utf-8');
                $title                       = $string . '...';
                $data['news'][$i]['title'] = $title;
            }
        }*/
        $ActivityMo = new ActivityModel();
        $actdata=$ActivityMo->where(array('name'=>'赠送mcc','admin'=>110))->fList();
        $time=time();
        if($actdata[0]['status']==1&& $time>= $actdata[0]['start_time'] && $time <= $actdata[0]['end_time']){
            $mcc_act_status=1;
        }else{
            $mcc_act_status = 0;
        }
        $this->assign('mcc_act_status', $mcc_act_status);

        //交易币种
        $coinList = Coin_PairModel::getInstance()->field('name,coin_from,coin_to')->where(['status'=>Coin_PairModel::STATUS_ON])->fList();

        $temp = array();
        foreach ($coinList as $v)
        {
            $temp[$v['coin_to']][] = $v['name'];
        }
        $coinList = $temp;

        $activity = ActivityModel::getInstance()->where(['bak'=>'zs666', 'status'=>1, 'end_time'=>['>', time()]])->order('sort asc')->fList();

        foreach($activity as $k=>&$v)
        {
            $conf = json_decode($v['conf'], true);
            if(isset($conf['index_hide']) && $conf['index_hide'])
            {
                unset($activity[$k]);
                continue;
            }

            if(isset($conf['index']))
            {
                $idxConf = &$conf['index'];
                if(isset($idxConf['left']['url'], $idxConf['left'][LANG.'_url']))
                {
                    $idxConf['left']['url'] = $idxConf['left'][LANG.'_url'];
                }
                if(isset($idxConf['right']['url'], $idxConf['right'][LANG.'_url']))
                {
                    $idxConf['right']['url'] = $idxConf['right'][LANG.'_url'];
                }

            }
            else
            {
                $conf['index'] = array('left'=>'', 'right'=>'');
            }

            $v = array(
                'coin'=>str_replace('赠送','', $v['name']),
                'conf'=>$conf['index'],
                'type'=>$v['type'],
            );
        }

        if(isset($_GET['login'], $_GET['reurl']))
        {
            setcookie('reurl', $_GET['reurl']);
        }


        $this->assign('activity', $activity);
        $this->assign('coinList', $coinList);
        $this->assign('data', $data);
        // 没有 领取币 的活动 配置
        $this->assign('noCoinactivity', []);
    }

    public function newsidAction()
    {die;
        $nid  = $this->getRequest()->get("nid", 0);
        $type = $this->getRequest()->get("type", 1);
        if ($type == 1)
        {
            $this->seo('公告详情-币交所-区块链权益资产交易平台');
        }
        else
        {
            $this->seo('新闻详情-币交所-区块链权益资产交易平台');
        }

        $tMO  = new NewsModel;
        $data = $tMO->query("select * from {$tMO->table} where id = {intval($nid)}");
        $this->assign('data', $data);
        $this->assign('type', $type);
    }

    public function newsDetailAction()//公告或新闻详情
    {
        $id         = trim(addslashes($_POST['id']));
        $tMo        = new NewsModel;
        $newsDetail = $tMo->where(array('id' => $id))->fList();
        $this->ajax('',1,$newsDetail[0]);
    }
    /**
     * 静态页面
     */
    public function htmlAction($page)
    {
        switch ($page)
        {
            case 'guide':
                $title = '新手引导';
                break;
            case 'faq':
                $title = '常见问题';
                break;
            case 'contactus':
                $title = '联系我们';
                break;
            default:
                $title = '政策说明';
                break;
        }
        $title = $title . '-币交所-区块链权益资产交易平台';
        $this->seo($title);
        $coins = User_CoinModel::getInstance()->getList();    //coin表
        $this->assign('coins', $coins);

        $pairs = Coin_PairModel::getInstance()->getList();//pair表
        $this->assign('pairs', $pairs);
        $this->assign('pages', Yaf_Registry::get("config")->page->toArray());
        isset($this->_view->pages[$page]) || exit;
        $this->assign('page', $page);
        $this->display('html/' . $page);
        exit;
    }

    /**
     * 验证码
     */
    public function captchaAction()
    {
        $captcha = new Tool_Captcha(80, 35, 4);
        ob_clean();
        $captcha->showImg();
        exit;
    }
    /**
     * 阿里云监控使用
     */
    public function httpAction()
    {
        echo '222';exit;
    }

    /**
     * 首页币种信息刷新
     */
    public function infofreshAction()
    {
        $pairs = json_decode($this->getRequest()->get("pairs"), true);

        foreach ($pairs as $v)
        {
            $info[$v] = Coin_PairModel::getInstance()->getCoInfo($v);
        }

        exit(json_encode($info));
    }

    public function clearRedisAction()
    {

        if ($_SESSION['user']['uid'] != 132727)
        {
            die('forbidden');
        }

        $name    = $_GET['name'];
        $subname = $_GET['subname'];
        $db      = $_GET['db'] ?: 2;

        if (!$name || !$subname)
        {
            die('参数错误; name，subname，db');
        }

        $redis = Cache_Redis::instance();

        $redis->select($db);

        $data = $redis->hget($name, $subname);
        $redis->hdel($name, $subname);
        die(json_encode($data));
    }

    /**
     * 用户协议
     */
    public function policyAction(){}

    /**
     * 用户註冊
     */
    public function registerAction(){}


    /**
     * 登錄
     */
    public function loginAction(){}

    /**
     * 微信頁面
     */
    public function wechatAction()
    {
        $this->display('../micromessenger');
        die;
    }

    /**
     * 浏览器升级提醒
     */
    public function browser_upgradeAction()
    {
        $this->display('../ieTips');
        exit;
    }

    public function navListAction()
    {}


    /**
     * 检查redis状态
     */
    public function redisAction()
    {
        Cache_Redis::instance()->set('redis_test', 1, 10);
        exit('ok');
    }

    /**
     * 检查mysql状态
     */
    public function mysqlAction()
    {
        Orm_Base::getInstance()->query('show tables');
        exit('ok');
    }

    /**
     * read活动
     */
    public function activityAction(){}
    /**
     *  dob 专题页
    */
    public function mccAction(){}

    public function updatepwdAction(){}

    public function passwordUpgradeAction()
    {
        if($this->mCurUser && $this->mCurUser['uid'])
        {
            $redis = Cache_Redis::instance();
            $redis->select(0);
            $doneKey = 'RESET_PWDS_DONE';
            $user = array(
                'mo'=>$this->mCurUser['mo'],
                'email'=>$this->mCurUser['email']
            );
            $this->assign('user', $user);
            if($redis->hget($doneKey, $this->mCurUser['uid']))
            {
                Tool_Md5::pwdTradeCheck($this->mCurUser['uid'], 'del');
                session_destroy();
                $redis->del('admin_google_auth_'.$this->mCurUser['uid']);
                header('location:/?login');die;
            }
        }
        else
        {
            header('location:/?login');die;
        }

    }

    public function linkUsAction(){}

}
