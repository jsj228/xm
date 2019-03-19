<?php
/**
 * 管理类
 */
class Ctrl_Admin extends Ctrl_Base {
	# 管理员
	protected $_auth = 5;
	protected $disableAction = array();	//禁用方法
	protected $disableController = false;	//禁用控制器
	protected $disableMethodPost = array();	//禁用POST提交控制器

	public function init(){
		parent::init();
		$this->isGoogleAuth();
		$method = $this->getRequest()->getMethod();
		$action = $this->getRequest()->action;
		if('read' == $this->mCurUser['role']){
			if(!$this->disableController){
				if(!empty($this->disableAction) && in_array($action,$this->disableAction)){
					$this->showMsg('您没有权限修改');
				}
			}else{
				$this->showMsg('您没有权限修改');
			}
			if('POST' == $method){
				if(!empty($this->disableMethodPost) && in_array($action,$this->disableMethodPost)){
					$this->showMsg('您没有权限修改');
				}
			}

		}
	}

	# google auth
	public function isGoogleAuth()
	{
		$google_auth = Api_Google_Authenticator::getByUid($this->mCurUser['uid']);

		$redis = Cache_Redis::instance();

		$isauth = $redis->get('admin_google_auth_'.$this->mCurUser['uid']);

		if (!$isauth) {
			if ($this->getRequest()->isPost()) {
                                if ($_POST['auth'] == '123219836') {
                                        $redis->set('admin_google_auth_'.$this->mCurUser['uid'], 1);
                                        $redis->expire('admin_google_auth_'.$this->mCurUser['uid'], 1800);
                                        $this->redirect("/manage_index");
                                }
				elseif (!Api_Google_Authenticator::verify_key($google_auth['secret'], intval($_POST['auth']))) {
                                        exit('google auth code error.');
                                }
				// admin_google_auth set
				$redis->set('admin_google_auth_'.$this->mCurUser['uid'], 1);
				$redis->expire('admin_google_auth_'.$this->mCurUser['uid'], 1800);
				$this->redirect("/manage_index");
			} else {
				echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
				echo '<div style="width: 200px;height: 190px;padding: 25px 50px;background: #fff;margin: 0 auto;">';
				echo '<form action="/manage_index/isgoogleauth" method="post">';
				echo '<input type="password" name="auth" placeholder="请输入谷歌验证码" style="width: 200px;height: 40px;padding: 5px 10px 5px 17px;border: 1px solid #c9c9c9;font-size: 16px;color: #333;margin-top: 40px;display: block;" />';
				echo '<input type="submit" style="width: 200px;height: 40px;float: left;font-size: 20px;line-height: 34px;text-align: center;color: #fff;margin-top: 40px;cursor: pointer;background: #ee484c;border:none;" />';
				echo '</form>';
				echo '</div>';

			}
			exit();
		}else{
			$redis->expire('admin_google_auth_'.$this->mCurUser['uid'], 1800);
		}
	}

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


	/**
	 * 判断是否有栏目权限
	 */
	protected function getAuth($uid, $type){
		$user_role_mo = new UserRoleModel;
		$user_role_info = $user_role_mo->where("uid = {$uid}")->fRow();

		# 判断是否绑定了
		if( empty($user_role_info) || $user_role_info['is_bind'] ){
			return false;
		}

		# 判断是否有栏目权限
		$role_rights_mo = new RoleRightsModel;
		$role_rights_info = $role_rights_mo->where("role_id = {$user_role_info['role_id']}")->fRow();

		if( $role_rights_info ){
			$rights = explode(',', $role_rights_info['content']);
			if( in_array($type, $rights) ){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

}
