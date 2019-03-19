<?php

namespace app\home\controller;

use think\Db;

class Article extends Home
{
	public function index()
	{
	    $id = input('id/d', 19);
		if (empty($id)) {
			$this->redirect(url('Article/detail'));
		}

		if (!check($id, 'd')) {
			$this->redirect(url('Article/detail'));
		}

		$Articletype = cache('Articletype'.$id);
		if(!$Articletype){
			$Articletype = Db::name('ArticleType')->where(array('id' => $id))->find();
			cache('Articletype'.$id,$Articletype);
		}
		
		
		$ArticleTypeList = cache('ArticleTypeList_list'.$Articletype['shang']);
		if(!$ArticleTypeList){
			$ArticleTypeList = Db::name('ArticleType')->where(array('status' => 1, 'index' => 1, 'shang' => $Articletype['shang']))->order('sort asc ,id asc')->select();
			cache('ArticleTypeList_list'.$Articletype['shang'],$ArticleTypeList);
		}
		if(isset($ArticleTypeList[0])){
            $Articleaa = cache('Articleaa'.$ArticleTypeList[0]['id']);
        }else{
            $Articleaa = 0;
        }

		if(!$Articleaa) {
            if (isset($ArticleTypeList[0])) {
            $Articleaa = Db::name('Article')->where(array('status' => 1, 'id' => $ArticleTypeList[0]['id']))->find();
            cache('Articleaa' . $ArticleTypeList[0]['id'], $Articleaa);
            }
		}
		
		$this->assign('shang', $Articletype);

		foreach ($ArticleTypeList as $k => $v) {
			$ArticleTypeLista[$v['name']] = $v;
		}

		$this->assign('ArticleTypeList', isset($ArticleTypeLista)?$ArticleTypeLista:null);
		$this->assign('data', $Articleaa);
		$where = array('type' => $Articletype['name'],'status'=>1);
		$Model = Db::name('Article');


		$list = cache('list' . $Articletype['id'] . input('p/d', 1));
		if(!$list){
			$list = $Model->where($where)->order('id desc')->paginate(10);
			cache('list'.$Articletype['id'],$list);
		}
		$show = $list->render();
		
		$this->assign('list', $list);
		$this->assign('page', $show);
		return $this->fetch();
	}
	
	public function detail()
	{   
		$id = input('id/d', 1);
		if (!check($id, 'd')) {
			$id = 1;
		}
		
		$data = cache('ArticleDetail_'.$id);
		if(!$data){
			$data = Db::name('Article')->where(array('id' => $id))->find();
			 cache('ArticleDetail_'.$id,$data);
		}
		
		$ArticleTypeList = cache('ArticleTypeList');
		if(!$ArticleTypeList){
		
			$ArticleType = Db::name('ArticleType')->where(array('status' => 1, 'index' => 1))->order('sort asc ,id desc')->select();

			foreach ($ArticleType as $k => $v) {
				$ArticleTypeList[$v['name']] = $v;
			}
			cache('ArticleTypeList',$ArticleTypeList);
		}
		
		$this->assign('ArticleTypeList', $ArticleTypeList);
		$this->assign('data', $data);
		$this->assign('type', $data['type']);
		return $this->fetch();
	}

	public function type()
	{
        $id = input('id/d', 1);
		if (!check($id, 'd')) {
			$id = 1;
		}
	
		$Article = cache('ArticleTypePage'.$id);
		if(!$Article){
			$Article = Db::name('ArticleType')->where(array('id' => $id))->find();
			cache('ArticleTypePage'.$id,$Article);
		}
		
		if ($Article['shang']) {
			$shang = cache('ArticleTypeshang'.$Article['shang']);
			if(!$shang){
				$shang = Db::name('ArticleType')->where(array('name' => $Article['shang']))->find();
				cache('ArticleTypeshang'.$Article['shang'],$shang);
			}
			$ArticleType = cache('ArticleTypeType'.$Article['shang']);
			if(!$ArticleType){
				$ArticleType = Db::name('ArticleType')->where(array('status' => 1, 'shang' => $Article['shang']))->order('sort asc ,id desc')->select();
				cache('ArticleTypeType'.$Article['shang'],$ArticleType);
			}
			$Articleaa = $Article;
		} else {
			$shang = cache('ArticleTypeshang'.$id);
			if(!$shang){
				$shang = Db::name('ArticleType')->where(array('id' => $id))->find();
				cache('ArticleTypeshang'.$id,$shang);
			}	
			$ArticleType = cache('ArticleTypeType'.$Article['name']);
			if(!$ArticleType){
				$ArticleType = Db::name('ArticleType')->where(array('status' => 1, 'shang' => $Article['name']))->order('sort asc ,id desc')->select();
				cache('ArticleTypeType'.$Article['name'],$ArticleType);
			}
			$Articleaa = cache('ArticleTypeTypeaa'.$ArticleType[0]['id']);
			if(!$Articleaa){
				$Articleaa = Db::name('ArticleType')->where(array('id' => $ArticleType[0]['id']))->find();
				cache('ArticleTypeTypeaa'.$ArticleType[0]['id'],$Articleaa);
			}
		}

		$this->assign('shang', $shang);
		foreach ($ArticleType as $k => $v) {
			$ArticleTypeList[$v['name']] = $v;
		}
		
		$this->assign('ArticleTypeList', $ArticleTypeList);
		$this->assign('data', $Articleaa);
		return $this->fetch();
	}
}

?>