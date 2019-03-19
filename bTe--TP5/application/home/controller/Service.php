<?php
/**
 * Created by PhpStorm.
 * User: deliang
 * Date: 10/14/16
 * Time: 8:33 AM
 */
namespace app\home\controller;

use think\Db;

class Service extends Home
{
    public function ourService()
    {
        $indexArticleType = cache('index_indexArticleType');
        if (!$indexArticleType) {
            $indexArticleType = Db::name('ArticleType')->where(array('status' => 1, 'index' => 1))->order('sort asc ,id desc')->limit(3)->select();
            cache('index_indexArticleType', $indexArticleType);
        }
        $this->assign('indexArticleType', $indexArticleType);

        $indexArticle = cache('index_indexArticle');
        if (!$indexArticle) {
            foreach ($indexArticleType as $k => $v) {
                $indexArticle[$k] = Db::name('Article')->where(array('type' => $v['name'], 'status' => 1, 'index' => 1))->order('id desc')->limit(6)->select();
            }

            cache('index_indexArticle', $indexArticle);
        }
        $this->assign('indexArticle', $indexArticle);

        return $this->fetch();
    }

}