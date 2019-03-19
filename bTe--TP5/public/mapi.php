<?php
/**
 * 钱包接口入口
 *
 */

require __DIR__ . '/common_enter.php';

define('DENY_AUTH', ['home', 'admin']);

define('BIND_MODULE','mapi');

// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
