<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Article extends HomeCommon
{
	public function index()
	{

	    $id = input('id', 19);
		if (empty($id)) {
			redirect(url('Article/detail'));
		}

		if (!check($id, 'd')) {
			redirect(url('Article/detail'));
		}

		$Articletype = Cache::store('redis')->get('Articletype'.$id);
		if(!$Articletype){
			$Articletype = Db::name('ArticleType')->where(['id' => $id])->find();
			Cache::store('redis')->set('Articletype'.$id,$Articletype);
		}
		
		
		$ArticleTypeList = Cache::store('redis')->get('ArticleTypeList_list'.$Articletype['shang']);
		if(!$ArticleTypeList){
			$ArticleTypeList = Db::name('ArticleType')->where(['status' => 1, 'index' => 1, 'shang' => $Articletype['shang']])->order('sort asc ,id asc')->select();
			Cache::store('redis')->set('ArticleTypeList_list'.$Articletype['shang'],$ArticleTypeList);
		}
		
		$Articleaa = Cache::store('redis')->get('Articleaa'.$ArticleTypeList[0]['id']);
		if(!$Articleaa){
			$Articleaa = Db::name('Article')->where(['status'=>1,'id' => $ArticleTypeList[0]['id']])->find();
			Cache::store('redis')->set('Articleaa'.$ArticleTypeList[0]['id'],$Articleaa);
		}
		
		$this->assign('shang', $Articletype);

		if ($ArticleTypeList){
            foreach ($ArticleTypeList as $k => $v) {
                $ArticleTypeLista[$v['name']] = $v;
            }
        }

		$this->assign('ArticleTypeList', $ArticleTypeLista);
		$this->assign('data', $Articleaa);
		$where = ['type' => $Articletype['name'],'status'=>1];
		$count = Cache::store('redis')->get('count'.$Articletype['id']);
		if(!$count){
			$count = Db::name('Article')->where($where)->count();
			Cache::store('redis')->set('count'.$Articletype['id'],$count);
		}
		
		

		
		$list = Cache::store('redis')->get('list'.$Articletype['id'].input('p/d', 1));
		if(!$list){
			// 获取分页显示
			
			$list = Db::name('Article')->where($where)->order('id desc')->paginate(12,false,[]);
			$page = $list->render();
			Cache::store('redis')->set('list'.$Articletype['id'],$list);
		}
		$this->assign('page', $page);
		$this->assign('list', $list);

		 return $this->fetch();
	}
	
	public function detail()
	{   
		$id = input('id');
		if (!check($id, 'd')) {
			$id = 1;
		}
		
		$data = Cache::store('redis')->get('ArticleDetail_'.$id);

		if(!$data){
			$data = Db::name('Article')->where(['id' => $id])->find();
			Cache::store('redis')->set('ArticleDetail_'.$id,$data);
		}
		
		$ArticleTypeList = Cache::store('redis')->get('ArticleTypeList');
		if(!$ArticleTypeList){
		
			$ArticleType = Db::name('ArticleType')->where(['status' => 1, 'index' => 1])->order('sort asc ,id desc')->select();

			if ($ArticleType){
                foreach ($ArticleType as $k => $v) {
                    $ArticleTypeList[$v['name']] = $v;
                }
                Cache::store('redis')->set('ArticleTypeList',$ArticleTypeList);
            }
		}
		
		$this->assign('ArticleTypeList', $ArticleTypeList);
		$this->assign('data', $data);
		$this->assign('type', $data['type']);
		return $this->fetch();
	}

	public function type()
	{
        $id = input('id', 1);
		if (!check($id, 'd')) {
			$id = 1;
		}
	
		$Article = Cache::store('redis')->get('ArticleTypePage'.$id);
		if(!$Article){
			$Article = Db::name('ArticleType')->where(['id' => $id])->find();
			Cache::store('redis')->set('ArticleTypePage'.$id,$Article);
		}
		
		if ($Article['shang']) {
			$shang = Cache::store('redis')->get('ArticleTypeshang'.$Article['shang']);
			if(!$shang){
				$shang = Db::name('ArticleType')->where(['name' => $Article['shang']])->find();
				Cache::store('redis')->set('ArticleTypeshang'.$Article['shang'],$shang);
			}
			$ArticleType = Cache::store('redis')->get('ArticleTypeType'.$Article['shang']);
			if(!$ArticleType){
				$ArticleType = Db::name('ArticleType')->where(['status' => 1, 'shang' => $Article['shang']])->order('sort asc ,id desc')->select();
				Cache::store('redis')->set('ArticleTypeType'.$Article['shang'],$ArticleType);
			}
			$Articleaa = $Article;
		} else {
			$shang = Cache::store('redis')->get('ArticleTypeshang'.$id);
			if(!$shang){
				$shang = Db::name('ArticleType')->where(['id' => $id])->find();
				Cache::store('redis')->set('ArticleTypeshang'.$id,$shang);
			}	
			$ArticleType = Cache::store('redis')->get('ArticleTypeType'.$Article['name']);
			if(!$ArticleType){
				$ArticleType = Db::name('ArticleType')->where(['status' => 1, 'shang' => $Article['name']])->order('sort asc ,id desc')->select();
				Cache::store('redis')->set('ArticleTypeType'.$Article['name'],$ArticleType);
			}
			$Articleaa = Cache::store('redis')->get('ArticleTypeTypeaa'.$ArticleType[0]['id']);
			if(!$Articleaa){
				$Articleaa = Db::name('ArticleType')->where(['id' => $ArticleType[0]['id']])->find();
				Cache::store('redis')->set('ArticleTypeTypeaa'.$ArticleType[0]['id'],$Articleaa);
	
			}
		}

		$this->assign('shang', $shang);
		if ($ArticleType){
            foreach ($ArticleType as $k => $v) {
                $ArticleTypeList[$v['name']] = $v;
            }
        }
		$this->assign('ArticleTypeList', $ArticleTypeList);
		$this->assign('data', $Articleaa);
		 return $this->fetch();
	}
}