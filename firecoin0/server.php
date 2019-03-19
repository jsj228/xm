<?php
// 定义系统编码
header("Content-Type: text/html;charset=utf-8");
// 定义应用路径
define('APP_PATH', './Application/');
// 定义缓存路径
define('RUNTIME_PATH', './Runtime/');
// 定义备份路径
define('DATABASE_PATH', './Database/');
// 定义钱包路径
define('COIN_PATH', './Coin/');
// 定义备份路径
define('UPLOAD_PATH', './Upload/');
// 定义数据库类型
define('DB_TYPE', 'mysqli');

// 定义数据库地址，数据库名，账号，密码，端口
define('DB_HOST', '192.168.0.8');
define('DB_NAME', 'weike');
define('DB_USER', 'jishu');
define('DB_PWD', '123456');
define('DB_PORT', '3306');

// Redis Cache 配置
define('DATA_CACHE_TYPE', 'file');
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', '6379');
define('REDIS_AUTH', '0x9a3861df045ba92d3ed29c2f53eeee3a1e84a5ee');

// Redis Session 配置
define('DATA_SESSION_TYPE', '');
define('REDIS_SESSION_HOST', '127.0.0.1');
define('REDIS_SESSION_AUTH', '0x9a3861df045ba92d3ed29c2f53eeee3a1e84a5ee');

// 调试模式设置为1 不要更改
define('APP_DEBUG', 1);

// 后台禁用前台，前台禁用后台
define('DENY_AUTH', '158dxycuyyvdsb8xs5kkywczxthsb8krmi');
define('ALLOW_AUTH', ['Home', '158dxycuyyvdsb8xs5kkywczxthsb8krmi', 'Mapi']);

//定义授权码
//define('MSCODE', '95D3A7E98EE9F913B462B87C73DS');

// 短信模式 0是演示模式  1是正式模式
define('MOBILE_CODE', 0);

// 引入入口文件
require './ThinkPHP/ThinkPHP.php';


