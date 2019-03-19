<?php
namespace app\common\model;

use think\Model;
class Category extends Model
{
    protected $key = 'home_category';

	public function info($id, $field = true)
	{
		$map = array();

		if (is_numeric($id)) {
			$map['id'] = $id;
		}
		else {
			$map['name'] = $id;
		}

		return $this->field($field)->where($map)->find();
	}

	public function getTree($id = 0, $field = true)
	{
		if ($id) {
			$info = $this->info($id);
			$id = $info['id'];
		}

		$map = array(
			'status' => array('gt', -1)
			);
		$list = $this->field($field)->where($map)->order('sort')->select();
		$list = $this->list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);

		if (isset($info)) {
			$info['_'] = $list;
		}
		else {
			$info = $list;
		}

		return $info;
	}

	public function getSameLevel($id, $field = true)
	{
		$info = $this->info($id, 'pid');
		$map = array('pid' => $info['pid'], 'status' => 1);
		return $this->field($field)->where($map)->order('sort')->select();
	}


    private function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
    {
        $tree = array();

        if (is_array($list)) {
            $refer = array();

            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }

            foreach ($list as $key => $data) {
                $parentId = $data[$pid];

                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }

        return $tree;
    }
}

?>