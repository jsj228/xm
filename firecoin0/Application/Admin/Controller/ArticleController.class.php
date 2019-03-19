<?php
namespace Admin\Controller;

class ArticleController extends AdminController
{
	public function index()
	{
	    $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			}
			else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('Article')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Article')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['adminid'] = M('Admin')->where(array('id' => $v['adminid']))->getField('username');
			$list[$k]['type'] = M('ArticleType')->where(array('name' => $v['type']))->getField('title');
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function articleimage()
	{
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/article/';
        $filename = md5($_FILES['upload_file0']['name'] . uniqid() . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
	}

	public function linkimage()
	{
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/link/';
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
        $id = intval(I('id'));
        $type = I('get.type/s');
        $_POST = I('post./a');
		if (empty($_POST)) {
			$list = M('ArticleType')->select();
			foreach ($list as $k => $v) {
				$listType[$v['name']] = $v['title'];
			}
			$this->assign('list', $listType);
			if ($id) {
				$this->data = M('Article')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}
			$this->display();
		} else {
			if ($type == 'images') {
                if($_FILES['imgFile']['size'] > 3145728){
                    $this->error(['msg' => "error"]);
                }
                $ext = pathinfo($_FILES['imgFile']['name'], PATHINFO_EXTENSION);
                if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
                    $this->error(['msg' => "error"]);
                }
                $path = 'Upload/article/';
                $filename = md5($_FILES['imgFile']['name'] . uniqid() . session('admin_id')) . '.' . $ext;
                $info = oss_upload($path.$filename, $_FILES['imgFile']['tmp_name']);
				if ($info) {
					$data = array('url' => C('TMPL_PARSE_STRING.__DOMAIN__') . '/Upload/article/' . $filename, 'error' => 0);
					exit(json_encode($data));
				} else {
					$error['error'] = 1;
					$error['message'] = '';
					exit(json_encode($error));
				}
			} else {
				if ($_POST['addtime']) {
					if (addtime(strtotime($_POST['addtime'])) == '---') {
						$this->error('添加时间格式错误');
					} else {
						$_POST['addtime'] = strtotime($_POST['addtime']);
					}
				} else {
					$_POST['addtime'] = time();
				}

				if ($_POST['endtime']) {
					if (addtime(strtotime($_POST['endtime'])) == '---') {
						$this->error('编辑时间格式错误');
					}
					else {
						$_POST['endtime'] = strtotime($_POST['endtime']);
					}
				} else {
					$_POST['endtime'] = time();
				}

				if ($_POST['id']) {
					$rs = M('Article')->save($_POST);
				} else {
					$_POST['addtime'] = time();
					$_POST['adminid'] = session('admin_id');
					$rs = M('Article')->add($_POST);
				}

				if ($rs) {
					$this->success('编辑成功！');
				} else {
					$this->error('编辑失败！');
				}
			}
		}
	}

	public function status()
	{
        $type = I('get.type/s');
        $id = I('id/a');
        $moble = I('get.moble/s','Article');

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function type()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(['username' => $name])->getField('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}
		$count = M('ArticleType')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('ArticleType')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();

		foreach ($list as $k => $v) {
			$list[$k]['adminid'] = M('Admin')->where(array('id' => $v['adminid']))->getField('username');
			$list[$k]['shang'] = M('ArticleType')->where(array('name' => $v['shang']))->getField('title');

			if (!$list[$k]['shang']) {
				$list[$k]['shang'] = '顶级';
			}
		}

		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function typeEdit()
	{
        $id = intval(I('id'));
        $type =I('get.type/s');
		$list = M('ArticleType')->select();
		foreach ($list as $k => $v) {
			$listType[$v['name']] = $v['title'];
		}

		$this->assign('list', $listType);

		if (empty($_POST)) {
			if ($id) {
				$this->data = M('ArticleType')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			$this->display();
		} else {
			if ($type == 'images') {
                if($_FILES['imgFile']['size'] > 3145728){
                    $this->error(['msg' => "error"]);
                }

                $ext = pathinfo($_FILES['imgFile']['name'], PATHINFO_EXTENSION);
                if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
                    $this->error(['msg' => "error"]);
                }

                $path = 'Upload/article/';
                $filename = md5($_FILES['imgFile']['name'] . uniqid() . session('admin_id')) . '.' . $ext;
                $info = oss_upload($path.$filename, $_FILES['imgFile']['tmp_name']);

                if ($info) {
                    $data = array('url' => C('TMPL_PARSE_STRING.__DOMAIN__') . '/Upload/article/' . $filename, 'error' => 0);
                    exit(json_encode($data));
                } else {
                    $error['error'] = 1;
                    $error['message'] = '';
                    exit(json_encode($error));
                }
			} else {
				if ($_POST['addtime']) {
					if (addtime(strtotime($_POST['addtime'])) == '---') {
						$this->error('添加时间格式错误');
					} else {
						$_POST['addtime'] = strtotime($_POST['addtime']);
					}
				} else {
					$_POST['addtime'] = time();
				}

				if ($_POST['endtime']) {
					if (addtime(strtotime($_POST['endtime'])) == '---') {
						$this->error('编辑时间格式错误');
					} else {
						$_POST['endtime'] = strtotime($_POST['endtime']);
					}
				} else {
					$_POST['endtime'] = time();
				}

				if ($_POST['id']) {
					$rs = M('ArticleType')->save($_POST);
				} else {
					$_POST['adminid'] = session('admin_id');
					$rs = M('ArticleType')->add($_POST);
				}

				if ($rs) {
					$this->success('编辑成功！');
				} else {
					$this->error('编辑失败！');
				}
			}
		}
	}

	public function typeStatus()
	{
        $id = intval(I('id'));
        $type = I('get.type/s');
        $moble = I('moble','ArticleType');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
		case 'forbid':
			$data = array('status' => 0);
			break;

		case 'resume':
			$data = array('status' => 1);
			break;

		case 'repeal':
			$data = array('status' => 2, 'endtime' => time());
			break;

		case 'delete':
			$data = array('status' => -1);
			break;

		case 'del':
			if (M($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
			break;

		default:
			$this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function adver()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('Adver')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Adver')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function adverEdit()
	{
        $id = intval(I('id'));
        $_POST = I('post./a');
		if (empty($_POST)) {
			if ($id) {
				$this->data = M('Adver')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			$this->display();
		} else {
			if ($_POST['addtime']) {
				if (addtime(strtotime($_POST['addtime'])) == '---') {
					$this->error('添加时间格式错误');
				} else {
					$_POST['addtime'] = strtotime($_POST['addtime']);
				}
			} else {
				$_POST['addtime'] = time();
			}

			if ($_POST['endtime']) {
				if (addtime(strtotime($_POST['endtime'])) == '---') {
					$this->error('编辑时间格式错误');
				} else {
					$_POST['endtime'] = strtotime($_POST['endtime']);
				}
			} else {
				$_POST['endtime'] = time();
			}

			if ($_POST['id']) {
				$rs = M('Adver')->save($_POST);
			} else {
				$_POST['adminid'] = session('admin_id');
				$rs = M('Adver')->add($_POST);
			}

			if ($rs) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function adverStatus()
	{
        $id = I('id');
        $type = I('get.type/s');
        $moble = I('moble','Adver');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (M($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function adverImage()
	{
        if($_FILES['upload_file0']['size'] > 3145728){
            $this->error(['msg' => "error"]);
        }

        $ext = pathinfo($_FILES['upload_file0']['name'], PATHINFO_EXTENSION);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'jpeg'])){
            $this->error(['msg' => "error"]);
        }

        $path = 'Upload/ad/';
        $filename = md5($_FILES['upload_file0']['name'] . uniqid() . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
	}

	public function link()
	{
        $name = strval(I('name'));
        $field = strval(I('field'));
        $status = intval(I('status'));
        $where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = M('User')->where(array('username' => $name))->getField('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$count = M('Link')->where($where)->count();
		$Page = new \Think\Page($count, 15);
		$show = $Page->show();
		$list = M('Link')->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}

	public function linkEdit()
	{
        $id = intval(I('id'));
        $_POST = I('post./a');
		if (empty($_POST)) {
			if ($id) {
				$this->data = M('Link')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}

			$this->display();
		} else {
			if ($_POST['addtime']) {
				if (addtime(strtotime($_POST['addtime'])) == '---') {
					$this->error('添加时间格式错误');
				} else {
					$_POST['addtime'] = strtotime($_POST['addtime']);
				}
			} else {
				$_POST['addtime'] = time();
			}

			if ($_POST['endtime']) {
				if (addtime(strtotime($_POST['endtime'])) == '---') {
					$this->error('编辑时间格式错误');
				} else {
					$_POST['endtime'] = strtotime($_POST['endtime']);
				}
			} else {
				$_POST['endtime'] = time();
			}

			if ($_POST['id']) {
				$rs = M('Link')->save($_POST);
			} else {
				$rs = M('Link')->add($_POST);
			}

			if ($rs) {
				$this->success('编辑成功！');
			} else {
				$this->error('编辑失败！');
			}
		}
	}

	public function linkStatus()
	{
        $id = intval(I('id'));
        $type = I('get.type/s');
        $moble = I('moble','Link');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (strpos(',', $id)) {
			$id = implode(',', $id);
		}

		$where['id'] = array('in', $id);

		switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                //$data = array('status' => -1);
                //break;

                if (M($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            case 'del':
                if (M($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
		}

		if (M($moble)->where($where)->save($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
}

?>