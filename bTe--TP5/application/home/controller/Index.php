<?php
namespace app\home\controller;

use think\Db;

class Index extends Home
{
    public function index()
    {
        $indexAdver = cache('index_indexAdver');
        if (!$indexAdver) {
            $indexAdver = Db::name('Adver')->where(array('status' => 1))->order('id asc')->select();
            cache('index_indexAdver', $indexAdver);
        }
        $this->assign('indexAdver', $indexAdver);

        switch (config('index_html')) {
            case "a":
                //如果a模版
                $indexArticle = cache('index_indexArticle');
                $indexArticleType = array(
                    "gonggao" => "aaa",
                    "taolun" => "币友说币",
                    "hangye" => "bbb"
                );

                if (!$indexArticle) {
                    foreach ($indexArticleType as $k => $v) {
                        $indexArticle[$k] = Db::name('Article')->where(array('type' => $v, 'status' => 1, 'index' => 1))->order('id desc')->limit(4)->select();

                        foreach ($indexArticle[$k] as $kk => $vv) {
                            $indexArticle[$k][$kk]['content'] = mb_substr(clear_html($vv['content']), 0, 40, 'utf-8');
                            if ($indexArticle[$k][$kk]['img']) {
                                $indexArticle[$k][$kk]['img'] = config('view_replace_str.__DOMAIN__') . "/Upload/article/" . $indexArticle[$k][$kk]['img'];
                            } else {
                                $indexArticle[$k][$kk]['img'] = "/comfile/default/defaultImg.jpg";
                            }
                        }
                    }
                    cache('index_indexArticle', $indexArticle);
                }
                break;
            default:
                $indexArticleType = cache('index_indexArticleType');

                if (!$indexArticleType) {
                    $indexArticleType = Db::name('ArticleType')->where(array('status' => 1, 'index' => 1))->order('sort asc ,id desc')->limit(3)->select();
                    cache('index_indexArticleType', $indexArticleType);
                }
                $indexArticle = cache('index_indexArticle');

                if (!$indexArticle) {
                    foreach ($indexArticleType as $k => $v) {
                        $indexArticle[$k] = Db::name('Article')->where(array('type' => $v['name'], 'status' => 1, 'index' => 1))->order('id desc')->limit(6)->select();
                    }
                    cache('index_indexArticle', $indexArticle);
                }
        }
        $this->assign('indexArticleType', $indexArticleType);
        $this->assign('indexArticle', $indexArticle);

        $indexLink = cache('index_indexLink');
        if (!$indexLink) {
            $indexLink = Db::name('Link')->where(array('status' => 1))->order('sort asc ,id desc')->select();
            cache('index_indexLink', $indexLink);
        }

        $weike_getCoreConfig = weike_getCoreConfig();
        if (!$weike_getCoreConfig) {
            $this->error('核心配置有误');
        }

        $this->assign('weike_jiaoyiqu', $weike_getCoreConfig['weike_indexcat']);
        $this->assign('indexLink', $indexLink);

        if (config('index_html')) {
            return $this->fetch('index/' . config('index_html') . '/index');
        } else {
            return $this->fetch();
        }
    }

    public function newPrice()
    {
        ini_set('display_errors', 'on');
        error_reporting(0);
        $data = $this->allCoinPrice();
        $last_data = cache('ajax_all_coin_last');
        $_result = array();
        if (empty($last_data)) {
            foreach (config('market') as $k => $v) {
                $_result[$v['id'] . '-' . strtoupper($v['xnb'])] = $data[$k][1] . '-0.0';
            }
        } else {
            foreach (config('market') as $k => $v) {
                $_result[$v['id'] . '-' . strtoupper($v['xnb'])] = $data[$k][1] . '-' . ($data[$k][1] - $last_data[$k][1]);
            }
        }

        cache('ajax_all_coin_last', $data);
        $data = json_encode(
            array(
                'result' => $_result,
            )
        );
        exit($data);
        //exit('{"result":{"25-BTC":"4099.0-0.0","1-LTC":"26.43--0.22650056625141082","26-DZI":"1.72-0.0","6-DOGE":"0.00151-0.0"},"totalPage":5}');
    }


    private function allCoinPrice()
    {
        $data = cache('allcoin');
        if (!$data) {
            // 市场交易记录
            $marketLogs = array();
            foreach (config('market') as $k => $v) {
                $tradeLog = Db::name('TradeLog')->where(array('status' => 1, 'market' => $k))->order('id desc')->limit(50)->select();
                $_data = array();
                foreach ($tradeLog as $_k => $v) {
                    $_data['tradelog'][$_k]['addtime'] = date('m-d H:i:s', $v['addtime']);
                    $_data['tradelog'][$_k]['type'] = $v['type'];
                    $_data['tradelog'][$_k]['price'] = $v['price'] * 1;
                    $_data['tradelog'][$_k]['num'] = round($v['num'], 6);
                    $_data['tradelog'][$_k]['mum'] = round($v['mum'], 2);
                }
                $marketLogs[$k] = $_data;
            }

            $themarketLogs = array();
            if ($marketLogs) {
                $last24 = time() - 86400;
                $_date = date('m-d H:i:s', $last24);
                foreach (config('market') as $k => $v) {
                    $tradeLog = isset($marketLogs[$k]['tradelog']) ? $marketLogs[$k]['tradelog'] : null;
                    if ($tradeLog) {
                        $sum = 0;
                        foreach ($tradeLog as $_k => $_v) {
                            if ($_v['addtime'] < $_date) {
                                continue;
                            }
                            $sum += $_v['mum'];
                        }
                        $themarketLogs[$k] = $sum;
                    }
                }
            }

            foreach (config('market') as $k => $v) {
                $data[$k][0] = $v['title'];
                $data[$k][1] = round($v['new_price'], $v['round']);
                $data[$k][2] = round($v['buy_price'], $v['round']);
                $data[$k][3] = round($v['sell_price'], $v['round']);
                $data[$k][4] = isset($themarketLogs[$k]) ? $themarketLogs[$k] : 0;//round($v['volume'] * $v['new_price'], 2) * 1;
                $data[$k][5] = '';
                $data[$k][6] = round($v['volume'], 2) * 1;
                $data[$k][7] = round($v['change'], 2);
                $data[$k][8] = $v['name'];
                $data[$k][9] = $v['xnbimg'];
                $data[$k][10] = '';
            }

            cache('allcoin', $data);
        }

        return $data;
    }

    public function enlang()
    {
        $lang = input('get.lang');
        switch ($lang) {
            case 'en-us':
                cookie('think_var', 'en-us');
                break;
            case 'zh-cn':
                cookie('think_var', 'zh-cn');
                break;
            default:
                cookie('think_var', 'zh-tw');
                break;
        }
    }

    //调试模式下进入错误报告系统
    public function bug()
    {
        if(!config('app_is_local')){
            return;
        }
        $bug_file = ROOT_PATH.'public/bug.html';
        if (file_exists($bug_file)){
            $bug_str=file_get_contents($bug_file);
            preg_match('/(http)(.)*([a-z0-9\-\.\_])+/i',$bug_str,$arr_url);
            $tt = $arr_url;
            if(isset($arr_url[0])){
                $this->redirect($arr_url[0]);
            }else{
                $this->redirect('/');
            }

        }else{
            $this->redirect('/');
        }
    }
    //进入后台
    public function backend(){
        if(!config('app_is_local')){
            return;
        }
        $this->redirect('/158dxycuyyvdsb8xs5kkywczxthsb8krmi.php');

    }
}

?>