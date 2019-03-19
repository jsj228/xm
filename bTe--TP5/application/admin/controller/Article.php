<?php
namespace app\admin\controller;

use think\Db;

class Article extends Admin
{
	public function index()
	{
	    $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
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


		$list = Db::name('Article')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['adminid'] = Db::name('Admin')->where(array('id' => $item['adminid']))->value('username');
            $item['type'] = Db::name('ArticleType')->where(array('name' => $item['type']))->value('title');
            return $item;
        });
		$show = $list->render();


		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
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
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
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
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
	}

	public function edit()
	{
        $id = intval(input('id'));
        $type = input('param.type/s');
        $_POST = input('post.');
		if (empty($_POST)) {
			$list = Db::name('ArticleType')->select();
			foreach ($list as $k => $v) {
				$listType[$v['name']] = $v['title'];
			}
			$this->assign('list', $listType);
			if ($id) {
				$this->data = Db::name('Article')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}
            $this->assign('data',$this->data);
			return $this->fetch();
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
                $filename = md5($_FILES['imgFile']['name'] . session('admin_id')) . '.' . $ext;
                $info = oss_upload($path.$filename, $_FILES['imgFile']['tmp_name']);
				if ($info) {
					$data = array('url' => config('view_replace_str.__DOMAIN__') . '/Upload/article/' . $filename, 'error' => 0);
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

				if (!empty($_POST['id'])) {
					$rs = Db::name('Article')->update($_POST);
				} else {
					$_POST['addtime'] = time();
					$_POST['adminid'] = session('admin_id');
					$rs = Db::name('Article')->insert($_POST);
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
        $type = input('param.type/s');
        $id = input('id/a');
        $moble = input('param.moble/s','Article');

		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (is_string($id) && strpos(',', $id)) {
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
			if (Db::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function type()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where(['username' => $name])->value('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$list = Db::name('ArticleType')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['shang'] = Db::name('ArticleType')->where(array('name' => $item['shang']))->value('title');

            if (! isset($item['shang']) || !$item['shang']) {
                $item['shang'] = '顶级';
            }
            return $item;
        });
		$show = $list->render();



		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function typeEdit()
	{
        $id = intval(input('id'));
        $type =input('param.type/s');
		$list = Db::name('ArticleType')->select();
		foreach ($list as $k => $v) {
			$listType[$v['name']] = $v['title'];
		}

		$this->assign('list', $listType);

		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('ArticleType')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}
            $this->assign('data',$this->data);
			return $this->fetch();
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
                $filename = md5($_FILES['imgFile']['name'] . session('admin_id')) . '.' . $ext;
                $info = oss_upload($path.$filename, $_FILES['imgFile']['tmp_name']);

                if ($info) {
                    $data = array('url' => config('view_replace_str.__DOMAIN__') . '/Upload/article/' . $filename, 'error' => 0);
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

				if (input('post.id')) {
					$rs = Db::name('ArticleType')->update($_POST);
				} else {
					$rs = Db::name('ArticleType')->insert($_POST);
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
        $id = input('param.id/a');
        $type = input('param.type/s');
        $moble = input('moble','ArticleType');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (is_string($id) && is_string($id) && strpos(',', $id)) {
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
			if (Db::name($moble)->where($where)->delete()) {
				$this->success('操作成功！');
			} else {
				$this->error('操作失败！');
			}
			break;

		default:
			$this->error('操作失败！');
		}

		if (Db::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}

	public function adver()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
		$where = [];
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}


		$list = Db::name('Adver')->where($where)->order('id desc')->paginate(15);
		$show = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function adverEdit()
	{
        $id = input('id/d');
        $_POST = input('post.');
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('Adver')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}
            $this->assign('data',$this->data);
			return $this->fetch();
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

			if (!empty($_POST['id'])) {
				$rs = Db::name('Adver')->update($_POST);
			} else {
				$_POST['adminid'] = session('admin_id');
				$rs = Db::name('Adver')->insert($_POST);
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
        $id = input('param.id/a');
        $type = input('param.type/s');
        $moble = input('moble','Adver');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (is_string($id) && strpos(',', $id)) {
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
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
		}

		if (Db::name($moble)->where($where)->update($data)) {
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
        $filename = md5($_FILES['upload_file0']['name'] . session('admin_id')) . '.' . $ext;
        $info = oss_upload($path.$filename, $_FILES['upload_file0']['tmp_name']);

        if(!$info){
            $this->error(['msg' => "error"]);
        }

        echo $filename;
        exit();
	}

	public function link()
	{
        $name = strval(input('name'));
        $field = strval(input('field'));
        $status = intval(input('status'));
        $where = array();
		if ($field && $name) {
			if ($field == 'username') {
				$where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
			} else if ($field == 'title') {
				$where['title'] = array('like', '%' . $name . '%');
			} else {
				$where[$field] = $name;
			}
		}

		if ($status) {
			$where['status'] = $status - 1;
		}

		$list = Db::name('Link')->where($where)->order('id desc')->paginate(15);
		$show = $list->render();
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	public function linkEdit()
	{
        $id = intval(input('id'));
        $_POST = input('post.');
		if (empty($_POST)) {
			if ($id) {
				$this->data = Db::name('Link')->where(array('id' => trim($id)))->find();
			} else {
				$this->data = null;
			}
            $this->assign('data',$this->data);
			return $this->fetch();
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

			if (!empty($_POST['id'])) {
				$rs = Db::name('Link')->update($_POST);
			} else {
				$rs = Db::name('Link')->insert($_POST);
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
        $id = input('param.id/a');
        $type = input('param.type/s');
        $moble = input('moble','Link');
		if (empty($id)) {
			$this->error('参数错误！');
		}

		if (empty($type)) {
			$this->error('参数错误1！');
		}

		if (is_string($id) && strpos(',', $id)) {
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

                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
		}

		if (Db::name($moble)->where($where)->update($data)) {
			$this->success('操作成功！');
		} else {
			$this->error('操作失败！');
		}
	}
}

?>