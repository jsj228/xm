<?php
namespace app\home\controller;
use think\Controller;
use think\Db;
use think\Request;
use think\Log;
use think\Cache;

class HomeCommon extends Controller{

    protected $daohang = [];

    public function _initialize()
    {
        $lang = $this->request->get('lang');
        if ($lang){
            cookie('language','');
            cookie('think_var', '');
            cookie('language',$lang);
            cookie('think_var', $lang);
        }else{
            $cookie_lang = cookie('language');
            if (!$cookie_lang){
                cookie('language','');
                cookie('think_var', '');
                cookie('language','zh-cn');
                cookie('think_var', 'zh-cn');
            }
        }
        
       //请求过滤
        $uid = session('userId');
        if (!$uid){
            session('userId',0);
        }else{
            //是否是登录控制
            if ($this->request->controller() != 'Login'){
                $user = Cache::store('redis')->get('userinfo'.$uid);
                if (!$user){
                    $user = Db::name('user')->where('id',$uid)->find();
                    Cache::store('redis')->set('userinfo'.$uid,$user);
                }

                if (!$user['paypassword']){
                    redirect(url('/login/paypassword'));
                }

                if (!$user['truename']) {
                    redirect(url('/login/truename'));
                }
            }

            $userCoin_top = Db::name('user_coin')->where('userid',$uid)->find();
            $new_price = Db::name('market')->field('name,new_price')->where(['jiaoyiqu'=>0,'status'=>1])->select();
            $xnbzj = '';
            if (count($new_price)>0){
                //获取用户各个币种的资产
                foreach ($new_price as $k=>$v){
                    $xnb = explode('_',$v['name'])[0];
                    $mfc = explode('_',$v['name'])[1];
                    if ($mfc == 'cny'){
                        //全部货币+冻结*价格
                        $xnbzj = ($userCoin_top[$xnb] + $userCoin_top[$xnb.'d'])*$v['new_price'];
                    }
                }

                //用户的人民币资产 人民币+人民币冻结+可用（全部）+冻结（全部）
                $userCoin_top['cny'] = round($userCoin_top['cny'], 2);
                $userCoin_top['cnyd'] = round($userCoin_top['cnyd'], 2);
                //总资产
                $userCoin_top['zzc'] = round($userCoin_top['cny']+$userCoin_top['cnyd']+$xnbzj,2);
                $this->assign('userCoin_top', $userCoin_top);
            }
        }

        if ($this->request->get('invit')){
            session('invit',$this->request->get('invit'));
        }

        $config = Cache::store('redis')->get('home_config');
        if (!$config) {
            $config = Db::name('Config')->where('id',1)->find();
            Cache::store('redis')->set('home_config', $config);
        }

        if (!$config['web_close']) {
            exit($config['web_close_cause']);
        }

        unset($config['id']);
        config($config);
        config('contact_qq', explode('|', config('contact_qq')));
        config('contact_qqun', explode('|', config('contact_qqun')));
        config('contact_bank', explode('|', config('contact_bank')));

        $coin = Cache::store('redis')->get('home_coin');
        if (!$coin){
            $coin = Db::name('coin')->where('status',1)->select();
            Cache::store('redis')->set('home_coin',$coin);
        }

        $coinList = [];
        if ($coin){
            foreach ($coin as $k=>$v){
                $coinList['coin'][$v['name']] = $v;
                if ($v['type'] != 'rmb') {
                    $coinList['coin_list'][$v['name']] = $v;
                }
                if ($v['type'] == 'rmb') {
                    $coinList['rmb_list'][$v['name']] = $v;
                } else {
                    $coinList['xnb_list'][$v['name']] = $v;
                }
                if ($v['type'] == 'rgb') {
                    $coinList['rgb_list'][$v['name']] = $v;
                }
                if ($v['type'] == 'bit') {
                    $coinList['bit_list'][$v['name']] = $v;
                }
                if ($v['type'] == 'eth') {
                    $coinList['eth_list'][$v['name']] = $v;
                }
                if ($v['type'] == 'token') {
                    $coinList['token_list'][$v['name']] = $v;
                }
            }
        }

        config($coinList);
        $market = Cache::store('redis')->get('home_market');
        $market_type = array();
        $coin_on = array();
        if (!$market) {
            $market = Db::name('market')->where('status',1)->select();
            Cache::store('redis')->set('home_market', $market);
        }

        if ($market){
            foreach ($market as $k => $v) {
                if(!$v['round']){
                    $v['round'] = 4;
                }

                $v['new_price'] = round($v['new_price'], $v['round']);
                $v['buy_price'] = round($v['buy_price'], $v['round']);
                $v['sell_price'] = round($v['sell_price'], $v['round']);
                $v['min_price'] = round($v['min_price'], $v['round']);
                $v['max_price'] = round($v['max_price'], $v['round']);
                $v['xnb'] = explode('_',$v['name'])[0];
                $v['rmb'] = explode('_', $v['name'])[1];
                $v['xnbimg'] = config('coin')[$v['xnb']]['img'];
                $v['rmbimg'] = config('coin')[$v['rmb']]['img'];
                $v['volume'] = $v['volume'] * 1;
                $v['change'] = $v['change'] * 1;
                $v['title'] = config('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
                $v['navtitle'] = config('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']). ')';
                if($v['begintrade']){
                    $v['begintrade'] = $v['begintrade'];
                }else{
                    $v['begintrade'] = "00:00:00";
                }
                if($v['endtrade']){
                    $v['endtrade']    = $v['endtrade'];
                }else{
                    $v['endtrade']    = "23:59:59";
                }

                $market_type[$v['xnb']]=$v['name'];
                $coin_on[]= $v['xnb'];
                $marketList['market'][$v['name']] = $v;
            }
        }

        config('market_type',$market_type);
        config('coin_on',$coin_on);
        config($marketList);
        $C = config();

        if ($C){
            foreach ($C as $k => $v) {
                $C[strtolower($k)] = $v;
            }
        }
    
        $this->assign('C', $C);
        $this->kefu = './application/home/view/kefu/' . $C['kefu'] . '/index.html';

        if (!Cache::store('redis')->get('daohang')) {
            $this->daohang = Db::name('daohang')->where('status',1)->order('sort asc')->select();
            Cache::store('redis')->set('daohang', $this->daohang);
        } else {
            $this->daohang = Cache::store('redis')->get('daohang');
        }

        $footerArticleType = Cache::store('redis')->get('footer_indexArticleType');
        if (!$footerArticleType) {
            $footerArticleType = Db::name('article_type')->where(['status'=>1,'footer'=>1,'shang'=>''])->order('sort asc ,id desc')->limit(3)->select();
            Cache::store('redis')->set('footer_indexArticleType', $footerArticleType);
        }
        $this->assign('footerArticleType', $footerArticleType);
        $footerArticle = Cache::store('redis')->get('footer_indexArticle');
        if (!$footerArticle) {
            foreach ($footerArticleType as $k => $v) {
                $footerArticle[$v['name']] = Db::name('article_type')->where(['status'=>1,'footer'=>1,'shang'=>$v['name']])->order('id asc')->limit(4)->select();
            }

            Cache::store('redis')->set('footer_indexArticle', $footerArticle);
        }

        //判断语言包
        if(cookie('language') === 'en-us'){
            config('web_name',lang('WEB_NAME'));
            config('web_title', lang('WEB_TITLE'));
            config('web_keywords', lang('WEB_KEYWORDS'));
            config('top_name', lang('TOP_NAME'));
            config('web_description', lang('WEB_DESCRIPTION'));
            $this->daohang[0]['title'] = 'Finance';
            $this->daohang[1]['title'] = 'Safe';
            $this->daohang[2]['title'] = 'Article';
            $this->daohang[3]['title'] = 'Contact';
        }
        if(cookie('language') === 'ko'){
            config('web_name',lang('WEB_NAME'));
            config('web_title', lang('WEB_TITLE'));
            config('web_keywords', lang('WEB_KEYWORDS'));
            config('top_name', lang('TOP_NAME'));
            config('web_description', lang('WEB_DESCRIPTION'));
            $this->daohang[0]['title'] = '재무 센터';
            $this->daohang[1]['title'] = '안전 센터';
            $this->daohang[2]['title'] = '공고 / 안내';
            $this->daohang[3]['title'] = '질문';
        }
        $this->assign('footerArticle', $footerArticle);
        $this->assign('daohang', $this->daohang);

        if (!cookie('small_tip')){
            $this->assign('article_66',DB::name('Article')->where('type="aaa"')->order('id desc')->value('content'));
        }
    }
}