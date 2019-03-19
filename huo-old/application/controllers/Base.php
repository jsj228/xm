<?php
/**
 * view 控制器
 */
class BaseController extends Ctrl_Base
{
  
    public function init()
    {
        //设置cookie
        $this->setCookie();

        parent::init();
        //选择模板语言
        $this->selectLang();
        #language choice,set view folder
        $this->setViewPath(PATH_TPL);
        //活动控制开关
        //注册有礼
        $activeId = Orm_Base::getInstance()->query("select * from activity where name='赠送dob'");
        if ($activeId[0]['status'] == 1 && $_SERVER['REQUEST_TIME'] > $activeId[0]['start_time'] && $_SERVER['REQUEST_TIME'] < $activeId[0]['end_time'])
        {
            $activeButton=1;
        }
        else{
            $activeButton = 0;
        }
        $this->layout('activeButton', $activeButton);
        $areamo=new PhoneAreaCodeModel();
        $lang=LANG;
        if($lang!='cn'){
            $lang='en';
        }
        $areadata=$areamo->where(array('langue'=> $lang))->fList();//區號
        $this->layout('areadata', $areadata);
   
        //from uid
        if(isset($_GET['regfrom']))
        {
            setcookie('regfrom', $_GET['regfrom'], time()+864000, '/');
        }

        if(!$_COOKIE['WSTK'])
        {
            setcookie('WSTK', md5(uniqid('WSTK')), 0, '/');
        }

  
        //清除页面缓存
        $clear_cache = '5.66';
        $this->layout('clear_cache', $clear_cache);
        $this->redefineView();

        $this->layout('controller', strtolower($this->_request->controller));
        $this->layout('action', strtolower($this->_request->action));
        $this->layout('version', 'v0.0.14');
    }


    /**
     * 重新定义视图
     */
    private function redefineView()
    {
        //手机访问
        if(Tool_Fnc::isMobile() && (!isset($_SERVER["HTTP_X_REQUESTED_WITH"]) || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])!="xmlhttprequest"))
        {
            //exit($this->render($this->getViewPath().'/mobileEndDevice'));
            //微信瀏覽器
            $isWechatBrowser = stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false;
            if($isWechatBrowser && stripos(REDIRECT_URL, 'wechat')===false)
            {
                header('location:/wechat.htm');die;
            }
            elseif(!$isWechatBrowser && stripos(REDIRECT_URL, 'wechat.htm')!==false)
            {
                header('location:/');die;
            }
        }


        preg_match('/MSIE (\d+?\.\d)/i', $_SERVER['HTTP_USER_AGENT'], $ieVersion);
        //当前非browser_upgrade页面
        if(stripos(REDIRECT_URL, 'browser_upgrade')===false)
        {
            //低版本浏览器(非蜘蛛)访问
            if($ieVersion && $ieVersion[1]<11 && stripos($_SERVER['HTTP_USER_AGENT'], 'spider') === false)
            {
                header('location:/browser_upgrade.htm');die;
            }
        }
        //高版本浏览器自动从browser_upgrade页跳到首页
        elseif(!$ieVersion || $ieVersion[1]>=11)
        {
            header('location:/');die;
        }
    }



    /**
     * 特殊页面
     */
    protected function page($code)
    {
        switch ($code)
        {
            case 404:
                header("status: 404 Not Found");
                break;
            case 403:
                header("status: 403 Forbidden");
                break;
        }
        $this->display('../error/error' . $code);
        exit;
    }

}
