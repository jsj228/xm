<?php

namespace app\home\controller;

use think\Controller;
use think\Db;

class Home extends Controller
{
    protected $daohang = [];

	protected function _initialize()
	{

	    //请求过滤
		if (!session('userId')) {
			session('userId', 0);
		} else if (CONTROLLER_NAME != 'Login') {
			$user = cache('userinfo'.session('userId'));
			if(!$user){
				$user = Db::name('User')->where(['id' => session('userId')])->find();
				cache('userinfo'.session('userId'),$user);
			}
			
			if (!$user['paypassword']) {
				$this->redirect('/Login/paypassword');
			}

			if (!$user['truename']) {
				$this->redirect('/Login/truename');
			}
			
		}

		if (userid()) {
			$userCoin_top = Db::name('UserCoin')->where(array('userid' => userid()))->find();
			$new_price = Db::name('market')->where(['status' => 1])->field('name,new_price')->select();
			foreach ($new_price as $k => $v){
			    $xnb = explode('_', $v['name'])[0];
                $btc = explode('_', $v['name'])[1];
                if($btc == 'btc') {
                    $coin_sum[$k] = round($userCoin_top[$xnb] * $v['new_price'], 6);
                }
            }

            //币种转换 BTC 后的总额累计
            $sum = 0;
			if(isset($coin_sum)){
                foreach ($coin_sum as $k => $v){
                    $sum += $v;
                }
            }
            if(isset($userCoin_top)){
                $userCoin_top['hkd'] = round($userCoin_top['hkd'], 2);
                $userCoin_top['hkdd'] = round($userCoin_top['hkdd'], 2);
                $userCoin_top['allhkd'] = round($userCoin_top['hkd']+$userCoin_top['hkdd'],2);
                $userCoin_top['btc'] = round($userCoin_top['btc'], 6);
                $userCoin_top['btcd'] = round($userCoin_top['btcd'], 6);
                $userCoin_top['allbtc'] = round($userCoin_top['btc'] + $userCoin_top['btcd'] + $sum, 6);
            }


			$this->assign('userCoin_top', $userCoin_top);
		}

		if (input('param.invit')) {
			session('invit', input('param.invit/s'));
		}


		$config = cache('home_config');
		if (!$config) {
			$config = Db::name('Config')->where(array('id' => 1))->find();
			cache('home_config', $config);
		}


		if (!$config['web_close']) {
			exit($config['web_close_cause']);
		}

		config($config);
		config('contact_qq', explode('|', config('contact_qq')));
		config('contact_qqun', explode('|', config('contact_qqun')));
		config('contact_bank', explode('|', config('contact_bank')));

		$coin = cache('home_coin');
		if (!$coin) {
			$coin = Db::name('Coin')->where(array('status' => 1))->select();
			cache('home_coin', $coin);
		}
		
		$coinList = array();
		foreach ($coin as $k => $v) {
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

		config($coinList);
		$market = cache('home_market');
		$market_type = array();
		$coin_on = array();
		if (!$market) {
			$market = Db::name('Market')->where(array('status' => 1))->order('sort desc')->select();
			cache('home_market', $market);
		}

		foreach ($market as $k => $v) {
			if(!$v['round']){
				$v['round'] = 4;
			}
			
			$v['new_price'] = round($v['new_price'], $v['round']);
			$v['buy_price'] = round($v['buy_price'], $v['round']);
			$v['sell_price'] = round($v['sell_price'], $v['round']);
			$v['min_price'] = round($v['min_price'], $v['round']);
			$v['max_price'] = round($v['max_price'], $v['round']);
			$v['xnb'] = explode('_', $v['name'])[0];
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

		config('market_type',$market_type);
		config('coin_on',$coin_on);
		config($marketList);
		$C = config();

		foreach ($C as $k => $v) {
			$C[strtolower($k)] = $v;
		}

		$this->assign('C', $C);
		$this->kefu = APP_PATH.'home/view/kefu/' . $C['kefu'] . '/index.html';
		

		if (!cache('daohang')) {
			$this->daohang = Db::name('Daohang')->where(array('status' => 1))->order('sort asc')->select();
			cache('daohang', $this->daohang);
		} else {
			$this->daohang = cache('daohang');
		}

		$footerArticleType = cache('footer_indexArticleType');
		if (!$footerArticleType) {
			$footerArticleType = Db::name('ArticleType')->where(array('status' => 1, 'footer' => 1, 'shang' => ''))->order('sort asc ,id desc')->limit(3)->select();
			cache('footer_indexArticleType', $footerArticleType);
		}

		$this->assign('footerArticleType', $footerArticleType);
		$footerArticle = cache('footer_indexArticle');
		if (!$footerArticle) {
			foreach ($footerArticleType as $k => $v) {
				$footerArticle[$v['name']] = Db::name('ArticleType')->where(array('shang' => $v['name'], 'footer' => 1, 'status' => 1))->order('id asc')->limit(4)->select();
			}

			cache('footer_indexArticle', $footerArticle);
		}

        //从语言包取首页数据
        config('web_name',lang('WEB_NAME'));
        config('web_title', lang('WEB_TITLE'));
        config('web_keywords', lang('WEB_KEYWORDS'));
        config('top_name', lang('TOP_NAME'));
        config('web_description', lang('WEB_DESCRIPTION'));

		$this->assign('footerArticle', $footerArticle);
        $this->assign('daohang', $this->daohang);

        if (!cookie('small_tip')){
            $this->assign('article_66', Db::name('Article')->where(['type' => 'aaa'])->order('id desc')->limit(1)->value('content'));
        }else{
            $this->assign('article_66','');
        }
    }

    /**
     * 请求处理
     */
    protected function request_filter($type = '')
    {
        $ip = $this->request->ip();//获取当前访问者的ip
        $logFilePath = dirname(THINK_PATH) . '/Runtime/';//日志记录文件保存目录
        $fileht = $logFilePath . 'forbidden.dat';//被禁止的ip记录文件
        $allowtime = 60;//防刷新时间
        $allownum = 10;//防刷新次数
        $allowRefresh = 120;//在允许刷新次数之后加入禁止ip文件中
        if (!file_existcache($fileht)) {
            file_put_contentcache($fileht, '');
        }
        $filehtarr = @file($fileht);
        if (in_array($ip . "\r\n", $filehtarr)) {
            $message = '警告：你的IP已经被禁止了！';
            if ($type === 'API') {
                return $message;
            }
            $this->error($message);
        }

        //加入禁止ip
        $time = time();
        $fileforbid = $logFilePath . 'forbidchk.dat';
        if (file_existcache($fileforbid)) {
            if ($time - filemtime($fileforbid) > 30) {
                @unlink($fileforbid);
            } else {
                $fileforbidarr = @file($fileforbid);
                if ($ip == substr($fileforbidarr[0], 0, strlen($ip))) {
                    if ($time - substr($fileforbidarr[1], 0, strlen($time)) > 120) {
                        @unlink($fileforbid);
                    } else if ($fileforbidarr[2] > $allowRefresh) {
                        file_put_contentcache($fileht, $ip . "\r\n", FILE_APPEND);
                        @unlink($fileforbid);
                    } else {
                        $fileforbidarr[2]++;
                        file_put_contentcache($fileforbid, $fileforbidarr);
                    }
                }
            }
        }

        //防刷新
        $str = '';
        $file = $logFilePath . 'ipdate.dat';
        if (!file_existcache($logFilePath) && !is_dir($logFilePath)) {
            mkdir($logFilePath, 0777);
        }

        if (!file_existcache($file)) {
            file_put_contentcache($file, '');
        }

        $uri = $_SERVER['REQUEST_URI'];//获取当前访问的网页文件地址
        $checkip = md5($ip);
        $checkuri = md5($uri);
        $yesno = true;
        $ipdate = @file($file);
        foreach ($ipdate as $k => $v) {
            $iptem = substr($v, 0, 32);
            $uritem = substr($v, 32, 32);
            $timetem = substr($v, 64, 10);
            $numtem = substr($v, 74);
            if ($time - $timetem < $allowtime) {
                if ($iptem != $checkip) {
                    $str .= $v;
                } else {
                    $yesno = false;
                    if ($uritem != $checkuri) {
                        $str .= $iptem . $checkuri . $time . "\r\n";
                    } else if ($numtem < $allownum) {
                        $str .= $iptem . $uritem . $timetem . ($numtem + 1) . "\r\n";
                    } else {
                        if (!file_existcache($fileforbid)) {
                            $addforbidarr = array($ip . "\r\n", time() . "\r\n", 1);
                            file_put_contentcache($fileforbid, $addforbidarr);
                        }
                        file_put_contentcache($logFilePath . 'forbided_ip.log', $ip . '--' . date('Y-m-d H:i:s', time()) . '--' . $uri . "\r\n", FILE_APPEND);
                        //$timepass = $timetem + $allowtime - $time;
                        $message = '警告：不要刷新的太频繁！';
                        if ($type === 'API') {
                            return $message;
                        }
                        $this->error($message);
                    }
                }
            }
        }

        if ($yesno) {
            $str .= $checkip . $checkuri . $time . "\r\n";
        }

        file_put_contentcache($file, $str);
    }

    public function _empty() {

        header('HTTP/1.1 404 Not Found' );
        // 确保FastCGI模式下正常
        header('Status:404 Not Found');

        $this->error();
        echo '模块不存在！';
        die();
    }
	
}

?>