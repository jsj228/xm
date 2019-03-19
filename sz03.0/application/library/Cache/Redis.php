<?php
class Cache_Redis {
  const OPT_READ_TIMEOUT = 300;
  private static $obj;

  static function &instance($pConfig = 'default') {
    if (!isset(self::$obj[$pConfig])) {
		# 配置
		if(!$tConf = Yaf_Registry::get("config")->redis->$pConfig){
			exit('redis config error: '.$pConfig);
		}
		$tConf = $tConf->toArray();
      # 连接
      self::$obj[$pConfig] = new Redis();
      if (self::$obj[$pConfig]->connect($tConf['host'], $tConf['port'], 5)) 
      {
  		  if($pwd = isset($tConf['password'])?$tConf['password']:Yaf_Registry::get("config")->redis->password)
        {
  			  self::$obj[$pConfig]->auth($pwd);
  		  }
        self::$obj[$pConfig]->setOption(Redis::OPT_READ_TIMEOUT, self::OPT_READ_TIMEOUT);
        
        self::$obj[$pConfig]->select($tConf['db']);
      }
    }
    return self::$obj[$pConfig];
  }

  /**
   * 将JSON处理为PHP数组
   *
   * @param string $pJson json_encode(数组)
   * @param string $pKey 数组键值
   * @param string $pDefault 默认值
   *
   * @return mixed
   * @demo Cache_Redis::json($json, 'uid', 0)
   * @demo Cache_Redis::json($json, null)
   */
  static function json(&$pJson, $pKey = null, $pDefault = '') {
    if (!$tArray = json_decode($pJson, true)) return $pDefault;
    return isset($tArray[$pKey]) ? $tArray[$pKey] : $pDefault;
  }

  /**
   * 封装 reids::hGet 方法
   *
   * @param $pKey
   * @param string $pConn
   *
   * @return mixed
   * @demo Cache_Redis::hget('city') # 整个数组
   * @demo Cache_Redis::hget('citypy', 'beijing') # 返回json字符串
   * @demo Cache_Redis::hget('citypy', 'f=beijing&json') # 返回数组
   */
  static function hget($pKey, $pConn = false) {
    $tMem = &Cache_Redis::instance();
    if (false === $pConn) return $tMem->hGetAll($pKey);
    # 多态二参 处理
    if (strpos($pConn, '&') || strpos($pConn, '=')) {
      parse_str($pConn, $tConn);
      if (isset($tConn['f'], $tConn['json'])) {
        return json_decode($tMem->hGet($pKey, $tConn['f']), true);
      }
    }
    return $tMem->hGet($pKey, $pConn);
  }

  /**
   * 封装 reids::get 方法
   * @param $pKey
   */
  static function get($pKey) {
    return Cache_Redis::instance()->get($pKey);
  }
}
