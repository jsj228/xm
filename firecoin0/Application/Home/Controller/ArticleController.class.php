<?php
namespace Home\Controller;

class ArticleController extends HomeController
{
	public function index()
	{
	    $id = I('id/d', 19);
		if (empty($id)) {
			redirect(U('Article/detail'));
		}

		if (!check($id, 'd')) {
			redirect(U('Article/detail'));
		}

		$Articletype = S('Articletype'.$id);
		if(!$Articletype){
			$Articletype = M('ArticleType')->where(array('id' => $id))->find();
			S('Articletype'.$id,$Articletype);
		}
		
		
		$ArticleTypeList = S('ArticleTypeList_list'.$Articletype['shang']);
		if(!$ArticleTypeList){
			$ArticleTypeList = M('ArticleType')->where(array('status' => 1, 'index' => 1, 'shang' => $Articletype['shang']))->order('sort asc ,id asc')->select();
			S('ArticleTypeList_list'.$Articletype['shang'],$ArticleTypeList);
		}
		
		$Articleaa = S('Articleaa'.$ArticleTypeList[0]['id']);
		if(!$Articleaa){
			$Articleaa = M('Article')->where(array('status'=>1,'id' => $ArticleTypeList[0]['id']))->find();
			S('Articleaa'.$ArticleTypeList[0]['id'],$Articleaa);
		}
		
		$this->assign('shang', $Articletype);

		foreach ($ArticleTypeList as $k => $v) {
			$ArticleTypeLista[$v['name']] = $v;
		}

		$this->assign('ArticleTypeList', $ArticleTypeLista);
		$this->assign('data', $Articleaa);
		$where = array('type' => $Articletype['name'],'status'=>1);
		$Model = M('Article');
		
		$count = S('count'.$Articletype['id']);
		if(!$count){
			$count = $Model->where($where)->count();
			S('count'.$Articletype['id'],$count);
		}
		
		
		$Page = new \Think\Page($count, 10);
		$show = $Page->show();
		
		$list = S('list'.$Articletype['id'].I('p/d', 1));
		if(!$list){
			$list = $Model->where($where)->order('id desc')->limit($Page->firstRow . ',' . $Page->listRows)->select();
			S('list'.$Articletype['id'],$list);
		}
//		 dump($count);
		$this->assign('list', $list);
		$this->assign('page', $show);
		$this->display();
	}
	
	public function detail()
	{   
		$id = I('id/d', 1);
		if (!check($id, 'd')) {
			$id = 1;
		}
		
		$data = S('ArticleDetail_'.$id);
		if(!$data){
			$data = M('Article')->where(array('id' => $id))->find();
			 S('ArticleDetail_'.$id,$data);
		}
		
		$ArticleTypeList = S('ArticleTypeList');
		if(!$ArticleTypeList){
		
			$ArticleType = M('ArticleType')->where(array('status' => 1, 'index' => 1))->order('sort asc ,id desc')->select();

			foreach ($ArticleType as $k => $v) {
				$ArticleTypeList[$v['name']] = $v;
			}
			S('ArticleTypeList',$ArticleTypeList);
		}
		
		$this->assign('ArticleTypeList', $ArticleTypeList);
		$this->assign('data', $data);
		$this->assign('type', $data['type']);
		$this->display();
	}

	public function type()
	{
        $id = I('id/d', 1);
		if (!check($id, 'd')) {
			$id = 1;
		}
	
		$Article = S('ArticleTypePage'.$id);
		if(!$Article){
			$Article = M('ArticleType')->where(array('id' => $id))->find();
			S('ArticleTypePage'.$id,$Article);
		}
		
		if ($Article['shang']) {
			$shang = S('ArticleTypeshang'.$Article['shang']);
			if(!$shang){
				$shang = M('ArticleType')->where(array('name' => $Article['shang']))->find();
				S('ArticleTypeshang'.$Article['shang'],$shang);
			}
			$ArticleType = S('ArticleTypeType'.$Article['shang']);
			if(!$ArticleType){
				$ArticleType = M('ArticleType')->where(array('status' => 1, 'shang' => $Article['shang']))->order('sort asc ,id desc')->select();
				S('ArticleTypeType'.$Article['shang'],$ArticleType);
			}
			$Articleaa = $Article;
		} else {
			$shang = S('ArticleTypeshang'.$id);
			if(!$shang){
				$shang = M('ArticleType')->where(array('id' => $id))->find();
				S('ArticleTypeshang'.$id,$shang);
			}	
			$ArticleType = S('ArticleTypeType'.$Article['name']);
			if(!$ArticleType){
				$ArticleType = M('ArticleType')->where(array('status' => 1, 'shang' => $Article['name']))->order('sort asc ,id desc')->select();
				S('ArticleTypeType'.$Article['name'],$ArticleType);
			}
			$Articleaa = S('ArticleTypeTypeaa'.$ArticleType[0]['id']);
			if(!$Articleaa){
				$Articleaa = M('ArticleType')->where(array('id' => $ArticleType[0]['id']))->find();
				S('ArticleTypeTypeaa'.$ArticleType[0]['id'],$Articleaa);
			}
		}

		$this->assign('shang', $shang);
		foreach ($ArticleType as $k => $v) {
			$ArticleTypeList[$v['name']] = $v;
		}
		
		$this->assign('ArticleTypeList', $ArticleTypeList);
		$this->assign('data', $Articleaa);
		$this->display();
	}
}

?>