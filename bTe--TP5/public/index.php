<?php
/**
 * 前端入口文件
 */


require __DIR__ . '/common_enter.php';

// 前台禁用后台
define('DENY_AUTH', ['admin', 'mapi']);

//绑定前端
define('BIND_MODULE','home');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';


