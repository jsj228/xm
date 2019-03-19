<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 'on');

# 全局
define("APPLICATION_PATH", realpath((phpversion() >= "5.3"? __DIR__: dirname(__FILE__)).'/../'));
define("CONF_PATH", APPLICATION_PATH.'/conf/');
date_default_timezone_set("Asia/Shanghai");

initLang();

# 加载配置文件
$app = new Yaf_Application(APPLICATION_PATH . "/conf/application.ini", 'common');
# 数据库配置
Yaf_Registry::set("config", $config = Yaf_Application::app()->getConfig());
# request_uri
$app->getDispatcher()->dispatch(new Yaf_Request_Simple());


function initLang()
{
	$langpg_path = CONF_PATH . 'ResponseMsg.json';
    $langpg      = file_get_contents($langpg_path);
    $langpg      = preg_replace('#//.+?\n#', '', $langpg);
    $langpg      = json_decode($langpg, true);
    if (!$langpg)
    {
        throw new Exception('cannot decode ' . $langpg_path);
    }

    foreach ($langpg as $k => &$v)
    {
        $v = $v['cn'];
    }

    $GLOBALS['MSG'] = $langpg;
}

function show($values, $vardump=false)
{
    if($vardump)
        var_dump($values);
    print_r($values);
    die;
}

