<?php
/**
 * 管理类
 */
class Ctrl_Admin extends Ctrl_Base {
  # 管理员
  protected $_auth = 5;

  /**
   * Ajax 保存字段
   */
  public function ajaxsaveAction($table) {
    if ('POST' == $_SERVER['REQUEST_METHOD']) {
      $this->_table2obj($table);
      $table->update($_POST);
    }
    exit;
  }

  /**
   * 删除记录
   */
  public function delAction($table, $id) {
    $this->_del($table, $id);
  }
}
