<?php
/**
 * 后台首页
 */
class Manage_IndexController extends Ctrl_Admin {

	static public $arr = array(
		'admin'=>array(
			'管理员' => array(100800, 100801, 100806, 100807, 100814, 100865,100906),
			'角色管理' => array(100800, 100801, 100806, 100807, 100814, 100865,100906),
		)
	);

	public function indexAction() {
	}
}
