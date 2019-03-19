<?php
/**
 * 计算脚本运行时间
 */
class Tool_Runtime {
  public $start = 0;
  static $instance;

  /**
   * 单例
   * @return Cache_File
   */
  static function &instance() {
    if (self::$instance === null) {
      self::$instance = new Tool_Runtime();
    }
    return self::$instance;
  }

  /**
   * 当前时间
   */
  function get_microtime() {
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
  }

  /**
   * 开始计时
   */
  static function start(){
    $instance = &self::instance();
    $instance->start = $instance->get_microtime();
  }

  /**
   * 结束计时，并返回执行时间
   */
  static function stop() {
    $instance = &self::instance();
    return round(($instance->get_microtime() - $instance->start) * 1000, 1);
  }
}