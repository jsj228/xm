<?php
namespace Admin\Controller;

class IssueController extends AdminController
{
	public function index()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
		$where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else if ($field == 'name') {
				$where['name'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('Issue')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Issue')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function issueimage()
	{
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/issue/';
        $filename = md5($_FILES['upload_file0']['name'] . uniqid() . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
	}

	public function edit()
	{
	    $id = I('get.id/d');
		if (empty($id)) {
			$this->data = false;
		} else {
			$this->data = M('Issue')->where(['id' => $id])->find();
		}

		$this->display();
	}

	public function save()
	{
        $_POST = I('post./a');
		$_POST['addtime'] = time();

		if (strtotime($_POST['time']) != strtotime(addtime(strtotime($_POST['time'])))) {
			$this->error('开启时间格式错误！');
		}

		if($_POST['tuijian']==1){
			//推荐的话 先把其它的推荐修改成不推荐
			M('Issue')-> where(['tuijian'=>1])->setField('tuijian','2');
		}

		if ($_POST['id']) {
			$rs = M('Issue')->save($_POST);
		} else {
			
			$rs = M('Issue')->add($_POST);
		}

		if ($rs) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function status()
	{
        $_POST = I('post./a');
		if (IS_POST) {
			$id = implode(',', $_POST['id']);
		} else {
			$id = I('get.id');
		}

		if (empty($id)) {
			$this->error('请选择要操作的数据!');
		}

		$where['id'] = array('in', $id);
		$method = I('get.method');

		switch (strtolower($method)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'delete':
                if (M('Issue')->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('参数非法');
		}

		if (M('Issue')->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function log()
	{
        $name = I('name/s');
		if ($name && check($name, 'username')) {
			$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
		} else {
			$where = [];
		}

		$count = M('IssueLog')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('IssueLog')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['username'] = M('User')->where(array('id' => $v['userid']))->getField('username');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
}

?>