<?php
/**
 * Created by PhpStorm.
 * User: longbijia
 * Date: 2017/9/13
 * Time: 19:21
 */
/**
 * 用户操作
*/
class NewsController extends Ctrl_Base
{
    protected $_auth = 1;
    /**
     * 公告列表
     */
    public function indexAction($id='', $page=1)
    {
        $category = intval($id);
        $catMo = new News_CategoryModel();
        $catedata=$catMo->where('status=1')->fList();

        if(!$category)
        {
            $category=$catedata[0]['id'];
            header('location:/news/category/'.$category);
        }

        if(!$catMo->fRow($category))
        {
            $this->page(404);
        }

        // 分类导航
        
        isset($_GET['p']) or $_GET['p'] = intval($page);
        //$data['catelist'] =  $catMo->field('id,cate_name')->where('status = 1')->fList();
        $lang = LANG;
        if($lang!='cn'){
            $lang='en';
        }
        $langMo = new News_LanguageModel();
        $data['catelist'] = $langMo->query("select a.id,b.title cate_name from news_category a left join news_language b on a.id=b.category_id where a.status=1 and b.language='$lang'");

        // 当前总记录条数
        $nMo = new NewsModel();
       // $data['total']  =  $nMo->where("sort>0 and category =$category and language_code='$lang'")->count();
        $data['total'] = $nMo->where(sprintf("sort>0 and category =%d and language_code='%s'", $category, $lang))->count();

        // 分页
        $tPage = new Tool_Page($data['total'], 11);

        $data['pageinfo'] = $tPage->show();

        // content 公告列表
        $data['list'] = $nMo->field('id,title,created,sort,is_top,click,source')
            ->where(sprintf("sort>0 and category =%d and language_code='%s'", $category, $lang))
            ->limit($tPage->limit())
            ->order('is_top desc,sort desc,created desc')
            ->fList();

        // 右侧公告列表
        $data['hot'] = $nMo->field('id,title,click')
            ->where(sprintf("sort>0 and language_code='%s'", $lang))
            ->limit(8)
            ->order('click desc')
            ->fList();



        $this->assign('top',$data['list'][0]['id']);
        $this->assign('data',$data);
        $this->assign('category',$category);
         //活动title
        foreach ($data['catelist'] as $k => $v){
            if($v['id']==$category){
                $this->seo($v['cate_name'].'_'.$GLOBALS['MSG']['MAIN_TITLE_INDEX'], $v['cate_name']);
            }

        }

    }

    /**
     * 公告详情
     */
    public function detailAction($id)
    {

        if($id&&is_numeric($id))
        {
            $id = addslashes(intval($id));

            $nMo = new NewsModel();

            $lang = LANG;
            if($lang!='cn'){
                $lang='en';
            }

            // 公告详情信息
           /* $data = $nMo->field('id,title,content,created,sort,is_top,click,source,category')
                ->where("id =$id and language_code='$lang'")
                ->fRow();*/
            $data = $nMo->field('id,title,content,created,sort,is_top,click,source,category')
                ->where(sprintf("id =%d and language_code='$lang'", $id))
                ->fRow();

            if(!$data)
            {
                header('location:/news');
                die;
                //$this->page(404);
            }
            // 阅读量+1
            if(!$nMo->where("id = ".$id)->exec("update news set click=click+1 where id = ".$id))
            {

            }

            
            
            //上下篇
            //$arr=$nMo->field('id,title,category')->where('sort>0 and category='. $data['category'])->order('is_top desc,sort desc,created desc')->fList();

            $arr = $nMo->field('id,title,category')->where("sort>0 and category ={$data[category]} and language_code='$lang'")->order('is_top desc,sort desc,created desc')->fList();
            $as=array_column($arr, 'id');
            $idkey= array_search($id, $as);
            if(count($as)>1)//2篇以上
            {
                if ($idkey == 0)
                {//表示第一篇，就没有上一篇
                    $prev = 0;
                    $next = $as[$idkey + 1];
                }else if($idkey ==(count($as)-1)){//表示最后一篇，就没有下一篇
                    $prev = $as[$idkey-1];
                    $next =0;
                }else{
                    $prev = $as[$idkey - 1];
                    $next = $as[$idkey + 1];
                }
            }else{//一篇，没有上下篇
                $prev = 0;
                $next =0;
            }
            $this->assign('prev', $prev);
            $this->assign('next', $next);
            //上下篇
            
            //}

            // 分类导航
            $catMo = new News_CategoryModel();
            //$category =  $catMo->field('id,cate_name')->where('status = 1')->fList();
            $langMo = new News_LanguageModel();
            $category = $langMo->query("select a.id,b.title cate_name from news_category a left join news_language b on a.id=b.category_id where a.status=1 and b.language='$lang'");

            $this->seo($data['title'].'_'.$GLOBALS['MSG']['MAIN_TITLE_INDEX'], $data['title'], mb_substr(strip_tags($data['content']), 0, 100, 'utf8'));

            $this->assign('data',$data);
            $this->assign('id',$id);
            $this->assign('category',$category);
        }
        else
        {
            $this->page(404);
        }
    }

    //首页弹出层信息
    public function getNewsTopInfoAction(){
        $lang = LANG;
        $nMo = new NewsModel();
        $newInfo = $nMo->field('id,title,created,sort,is_top,click,source')
            ->where(sprintf("sort>0 and category =%d and language_code='%s' and is_top = 1 ", 4, $lang))
            ->fRow();
        $this->ajax("",1,$newInfo);
    }

}
