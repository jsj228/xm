<?php
/**
 * 文件缓存
 * Cache_File::set('adf/aer/b', 123);
 * Cache_File::get('adf/aer/b');
 */
class Cache_File {
  static $instance;
  public $path = '../cache/serialize/';
  public $time;

  /**
   * 单例
   * @return Cache_File
   */
  static function &instance() {
    if (self::$instance === null) {
      self::$instance = new Cache_File();
      self::$instance->time = time();
    }
    return self::$instance;
  }

  /**
   * 得到缓存
   * @param $pKey
   * @param int $pExp false:不作判断直接返回, 0:不判断有效期, 1+:判断有效期
   *
   * @return array
   */
  static function get($pKey, $pExp = 3600) {
    $instance = &Cache_File::instance();
    # 不作判断，直接返回
    if(false === $pExp) return unserialize(file_get_contents($instance->path . $pKey));
    # 判断有效性
    if (is_file($instance->path . $pKey) && (!$pExp || (filemtime($instance->path . $pKey) > $instance->time - $pExp))) {
      return unserialize(file_get_contents($instance->path . $pKey));
    }
    return false;
  }

  /**
   * 写缓存
   * @param $pKey
   * @param $pVal
   */
  static function set($pKey, $pVal) {
    $instance = &Cache_File::instance();
    # 更新缓存
    if (!is_file($pKey) && false !== strpos($pKey, '/')) {
      $tPath = explode('/', $pKey);
      if (!$tPath[0]) unset($tPath[0]);
      array_pop($tPath);
      $tPath = $instance->path . implode('/', $tPath);
      file_exists($tPath) || @mkdir($tPath, 0777, true);
    }
    # 写缓存
    file_put_contents($instance->path . $pKey, serialize($pVal));
  }

  static function del($pKey){
    $instance = &Cache_File::instance();
    @unlink($instance->path . $pKey);
  }

  /**
   * 文件修改时间
   * @param $pKey
   * @return int
   */
  static function mtime($pKey){
    $instance = &Cache_File::instance();
    return is_file($instance->path . $pKey)? filemtime($instance->path . $pKey): 0;
  }
}
