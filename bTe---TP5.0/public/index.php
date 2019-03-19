<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 短信模式 0是演示模式  1是正式模式
define('MOBILE_CODE', 0);
// 调试模式设置为1 不要更改
define('APP_DEBUG', 1);
define('MSCODE', '95D3A7E98EE9F913B462B87C73DS');

// 后台禁用前台，前台禁用后台
// define('DENY_AUTH', '158dxycuyyvdsb8xs5kkywczxthsb8krmi');
// define('ALLOW_AUTH', ['Home', '158dxycuyyvdsb8xs5kkywczxthsb8krmi', 'Mapi']);
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
