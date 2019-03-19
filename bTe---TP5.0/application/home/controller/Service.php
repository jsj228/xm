<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;

class Service extends HomeCommon
{
    public function ourService()
    {
        $indexArticleType =Cache::store('redis')->get('index_indexArticleType');
        if (!$indexArticleType) {
            $indexArticleType = Db::name('ArticleType')->where(['status' => 1, 'index' => 1])->order('sort asc ,id desc')->limit(3)->select();
            Cache::store('redis')->set('index_indexArticleType', $indexArticleType);
        }
        $this->assign('indexArticleType', $indexArticleType);

        $indexArticle = Cache::store('redis')->get('index_indexArticle');
        if (!$indexArticle) {
            foreach ($indexArticleType as $k => $v) {
                $indexArticle[$k] = Db::name('Article')->where(['type' => $v['name'], 'status' => 1, 'index' => 1])->order('id desc')->limit(6)->select();
            }
             Cache::store('redis')->set('index_indexArticle', $indexArticle);
        }
        $this->assign('indexArticle', $indexArticle);

       return $this->fetch();
    }

}