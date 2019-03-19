 <?php
class Bootstrap extends Yaf_Bootstrap_Abstract
{

    /**
     * SEO
     */
    public function _initSeo()
    {
        //蜘蛛
        define('IS_SPIDER', preg_match('/bot|crawl|spider|slurp|sohu-search|lycos|robozilla/i', $_SERVER['HTTP_USER_AGENT']));
        //百度
        if(IS_SPIDER && stripos($_SERVER['HTTP_USER_AGENT'], 'Baiduspider') !== false)
        {
            $_COOKIE['LANG'] = 'zh-cn';
        }

        //301跳转
        $rUrl = array(
            '#/news/detail\?id=(\d+)#i'=>'/news/detail/$1.html',
            '#/news\?cate=(\d+)#i'=>'/news/category/$1',
            '#/news\?cate=(\d+)&p=(\d+)#i'=>'/news/category/$1/page/$2',
        );
        foreach($rUrl as $k=>$v)
        {
            if(preg_match($k, $_SERVER['REQUEST_URI']))
            {
                header('HTTP/1.1 301 Moved Permanently');
                header('location:' . preg_replace($k, $v, $_SERVER['REQUEST_URI']));
            }
        }


    }

    /**
     * 把配置存到注册表
     */
    public function _initConfig(Yaf_Dispatcher $dispatcher)
    {
        //语言
        $defLang = 'cn';
        $lang = $defLang;
        if(isset($_COOKIE['LANG']))
        {
            $lang = $_COOKIE['LANG'];
        }
        elseif(isset($_GET['LANG']))
        {
            $lang = $_GET['LANG'];
        }
        elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'zh-') === false)
        {
            $lang = 'en';
        }
        elseif(isset($_SERVER['HTTP_LANGUAGE']) && stripos($_SERVER['HTTP_LANGUAGE'], 'zh-') === false)
        {
            $lang = 'en';
        }

        $lang = preg_match('/^[a-z]+$/i', $lang)?$lang:$defLang;
        define('LANG',  $lang);

        $config = Yaf_Application::app()->getConfig();
        $config = $config->toArray();
        //动态修改配置
        $config['yibi']['ip'] = (strpos($_SERVER['SERVER_NAME'], 'https://')?'https://':'http://') . 'otc.' . preg_replace('/^.+\.([^\.]+?\.[^\.]+$)/i', '$1', $_SERVER['SERVER_NAME']);
        $config['yibi']['url'] = $config['yibi']['ip'];
        $config = new Yaf_Config_Simple($config);
        
        Yaf_Registry::set("config", $config);

        define('PATH_APP', $config->application->directory);
        define('IS_MOBILE', Tool_Fnc::isMobile());
        define('REDIRECT_URL', empty($_SERVER['REQUEST_URI']) ? '/' : strtolower($_SERVER['REQUEST_URI']));

        define('PATH_TPL', PATH_APP . '/views/pc');

        define('USER_IP', Tool_Fnc::realip());
        //session 和 cookie 过期时间
        define('SESSION_TIME', $config->session->time);
        define('SESSION_NAME', $config->session->name);
        define('PHONE_TIME', $config->phone_time);

        ini_set("session.save_handler", "redis");
        ini_set('session.name', SESSION_NAME);

    }



    public function _initMsgPackage()
    {
        $lFile = CONF_PATH . 'language/global/'. LANG . '.json';
        if(!file_exists($lFile))
        {
            $lFile = CONF_PATH . 'language/global/en.json';
        }

        if(file_exists($lFile))
        {
            $langpg = json_decode(file_get_contents($lFile), true);
            if(!$langpg)
            {
                throw new Exception('cannot decode '.$lFile );
            }
        }

        $GLOBALS['MSG'] = $langpg;
    }


    /**
     * 类自动加载
     */
    public function _initAutoLoad()
    {
        spl_autoload_register(function ($name) {
            //Logic类
            if(preg_match('/.+Logic$/', $name))
            {
                require_once(PATH_APP . '/Logic/' . preg_replace('/(.+)Logic$/', '$1', $name) . '.php');
            } 
            //Exception 类
            elseif(preg_match('/.+Exception$/', $name))
            {   
                require_once(PATH_APP . '/library/Exception/' . preg_replace('/(.+)Exception$/', '$1', $name) . '.php');
            }
            //Exchange 类
            elseif(preg_match('/Exchange_.+?Model/', $name))
            {
                if(class_exists('ExchangeBaseModel'))
                {
                    class_alias('ExchangeBaseModel', $name);
                    ExchangeBaseModel::$calledClass = $name;
                }
                
            }
        }, true, true);
    }


    /**
     * route
     */
    public function _initRoute()
    {
        # 路由
        $router = Yaf_Dispatcher::getInstance()->getRouter();

        # 图片
        $router->addRoute('img', new Yaf_Route_Regex('#^(/upload/[a-z_\d/]+\.[a-z]+?)$#i', array('controller' => 'Res', 'action' => 'img'), array(1 => 'filePath')));

        $router->addRoute('userimg', new Yaf_Route_Regex('#^(/upload/[a-z_\d]+\.[a-z]+?)$#i', array('controller' => 'Res', 'action' => 'userImg'), array(1 => 'filePath')));
        $router->addRoute('userimgNew', new Yaf_Route_Regex('#^(/uploadtime/\d+/[a-z_\d]+\.[a-z]+?)$#i', array('controller' => 'Res', 'action' => 'userImg'), array(1 => 'filePath')));

        # 静态页面
        $router->addRoute('c2c', new Yaf_Route_Rewrite('C2c/\/([a-z]+?_[a-z]+)$/', array('controller' => 'C2c', 'action' => 'index')));
        $router->addRoute('c2c', new Yaf_Route_Rewrite('c2c/page/:page', array('controller' => 'c2c', 'action' => 'Index')));
        $router->addRoute('html', new Yaf_Route_Regex('/([a-z]+)\.html$/', array('controller' => 'Index', 'action' => 'html'), array(1 => 'page')));

        $router->addRoute('trade', new Yaf_Route_Regex('/trade\/([a-z]+?_[a-z]+)$/', array('controller' => 'Trade', 'action' => 'index'), array(1 => 'name')));
        $router->addRoute('c2c', new Yaf_Route_Regex('/c2c\/([a-z]+?_[a-z]+)$/', array('controller' => 'c2c', 'action' => 'index'), array(1 => 'name')));
        $router->addRoute('c2c', new Yaf_Route_Rewrite('c2c/page/:page', array('controller' => 'c2c', 'action' => 'Index')));
        $router->addRoute('out', new Yaf_Route_Regex('/out\/([a-z]+?_[a-z]+)$/', array('controller' => 'Out', 'action' => 'index'), array(1 => 'name')));
        $router->addRoute('test', new Yaf_Route_Regex('/test\/([a-z]+?_[a-z]+)$/', array('controller' => 'Test', 'action' => 'index'), array(1 => 'name')));
        $router->addRoute('sql', new Yaf_Route_Regex('/sql\/([a-z]+?_[a-z]+)$/', array('controller' => 'Sql', 'action' => 'index'), array(1 => 'name')));

        $router->addRoute('password_upgrade', new Yaf_Route_Rewrite('password_upgrade.html/', array('controller' => 'Index', 'action' => 'passwordUpgrade')));
        $router->addRoute('wechat', new Yaf_Route_Rewrite('wechat.htm/', array('controller' => 'Index', 'action' => 'wechat')));
        $router->addRoute('login', new Yaf_Route_Rewrite('login/', array('controller' => 'Index', 'action' => 'login')));
        $router->addRoute('register', new Yaf_Route_Rewrite('register/', array('controller' => 'Index', 'action' => 'register')));
        $router->addRoute('policy', new Yaf_Route_Rewrite('policy/', array('controller' => 'Index', 'action' => 'policy')));
        $router->addRoute('browser_upgrade', new Yaf_Route_Rewrite('browser_upgrade/', array('controller' => 'Index', 'action' => 'browser_upgrade')));
        $router->addRoute('lose', new Yaf_Route_Rewrite('lose/', array('controller' => 'Index', 'action' => 'lose')));
        //news
        $router->addRoute('news', new Yaf_Route_Regex('#news/detail/(\d+?).html#', array('controller' => 'News', 'action' => 'detail'), array(1 => 'id')));
        $router->addRoute('newcate', new Yaf_Route_Rewrite('news/category/:id', array('controller' => 'News', 'action' => 'Index')));
        $router->addRoute('newcate_page', new Yaf_Route_Rewrite('news/category/:id/page/:page', array('controller' => 'News', 'action' => 'Index')));

        //API站
        if(stripos($_SERVER['SERVER_NAME'], 'api.') === 0)
        {

            $router->addRoute('Sms', new Yaf_Route_Rewrite('/m/sms', array('controller' => 'Api_Index', 'action' => 'sms')));
            $router->addRoute('secretkey', new Yaf_Route_Rewrite('/m/secretkey', array('controller' => 'Api_Index', 'action' => 'secretkey')));
            $router->addRoute('ipMod', new Yaf_Route_Rewrite('/m/ipmod', array('controller' => 'Api_Index', 'action' => 'ipMod')));
            $router->addRoute('userinfo', new Yaf_Route_Rewrite('/m/userinfo', array('controller' => 'Api_Index', 'action' => 'userinfo')));
            $router->addRoute('user', new Yaf_Route_Regex('#^\/user/([a-z\d]+)/?(.*)$#i', array('controller' => 'Api_User', 'action' => 'call'), array(1 => 'action', 2 => 'param1')));
            $router->addRoute('mini', new Yaf_Route_Regex('#^\/mini/([a-z\d]+)/?(.*)$#i', array('controller' => 'Api_Mini', 'action' => 'call'), array(1 => 'action', 2 => 'param1')));
            $router->addRoute('coin', new Yaf_Route_Regex('#^\/([a-z_]+)/?$#', array('controller' => 'Api_Quote', 'action' => 'coinQuote'), array(1 => 'coinPair')));
            $router->addRoute('allQuote', new Yaf_Route_Rewrite('/all/', array('controller' => 'Api_Quote', 'action' => 'allQuote')));
            $router->addRoute('quotes', new Yaf_Route_Rewrite('/quotes/', array('controller' => 'Api_Quote', 'action' => 'quotes')));
            $router->addRoute('btc2rmb', new Yaf_Route_Rewrite('/btc2rmb', array('controller' => 'Api_Quote', 'action' => 'btc2rmb')));
            $router->addRoute('line', new Yaf_Route_Regex('#^\/([a-z]+)/line/(\d+[mhd])$#', array('controller' => 'Api_Quote', 'action' => 'line'), array(1 => 'coin', 2=>'span')));
            $router->addRoute('trade', new Yaf_Route_Regex('#^/trade/([a-z\d]+)#i', array('controller' => 'Api_Trade', 'action' => 'call'), array(1 => 'action')));
            $router->addRoute('market', new Yaf_Route_Regex('#^/market/([a-z\d]+)#i', array('controller' => 'Api_Market', 'action' => 'call'), array(1 => 'action')));
            $router->addRoute('third', new Yaf_Route_Regex('#^/third/([a-z\d]+)#i', array('controller' => 'Api_Third', 'action' => 'call'), array(1 => 'action')));
            $router->addRoute('Index', new Yaf_Route_Regex('#^/$#', array('controller' => 'Api_Index', 'action' => 'index'), array()));
            $router->addRoute('User', new Yaf_Route_Regex('#^/m$#', array('controller' => 'Api_Index', 'action' => 'user'), array()));
        }

        //APP
        if(preg_match('#/app/(.+?)/([^/\?]+)?#', $_SERVER['REQUEST_URI'], $match))
        {
            $router->addRoute('app', new Yaf_Route_Regex('/app\/.+/', array('controller' => 'App_'.ucfirst($match[1]), 'action' => $match[2]), []));
        }

    }


    /**
     * 采用布局
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initLayout(Yaf_Dispatcher $dispatcher)
    {

        if (preg_match('/user(\/.+)?/i', REDIRECT_URL))
        {
            $layout = new LayoutPlugin('user/base.phtml');
            Yaf_Registry::set('layout', $layout);
            $dispatcher->registerPlugin($layout);
        }

    }
}
