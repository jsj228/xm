<?php
namespace Home\Controller;
use Think\Log;
class HomeController extends \Think\Controller
{
    protected $daohang = [];

	protected function _initialize()
	{
	    //请求过滤
		if (!session('userId')) {
			session('userId', 0);
		} else if (CONTROLLER_NAME != 'Login') {
			$user = S('userinfo'.session('userId'));
			if(!$user){
				$user = D('user')->where(['id' => session('userId')])->find();
				S('userinfo'.session('userId'),$user);
			}
			
			if (!$user['paypassword']) {
				redirect('/Login/paypassword');
			}

			if (!$user['truename']) {
				redirect('/Login/truename');
			}

			if (!$user['status']){
                S('userinfo'.session('userId'),null);
                session(null);
                redirect('/');
            }
		}

		if (userid()) {
			$userCoin_top = M('UserCoin')->where(array('userid' => userid()))->find();
			$new_price = M('market')->where(['jiaoyiqu' => 0,'status' => 1])->field('name,new_price')->select();
            $xnbzj = '';
			foreach ($new_price as $k => $v){
                //虚拟币
			    $xnb = explode('_', $v['name'])[0];
               //人民币
                $mfc = explode('_', $v['name'])[1];
                if($mfc == 'cny'){
                    //全部货币+冻结*价格
                    $xnbzj += ($userCoin_top[$xnb] +$userCoin_top[$xnb.'d'])*$v['new_price'];
                }
            }
			$userCoin_top['cny'] = round($userCoin_top['cny'], 2);
			$userCoin_top['cnyd'] = round($userCoin_top['cnyd'], 2);
            //人民币+人民币冻结+可用（全部）+冻结（全部）
            //总资产
			$userCoin_top['zzc'] = round($userCoin_top['cny']+$userCoin_top['cnyd']+$xnbzj,2);
			$this->assign('userCoin_top', $userCoin_top);
		}

		if (isset($_GET['invit'])) {
			session('invit', I('invit/s'));
		}

		$config = S('home_config');
		if (!$config) {
			$config = M('Config')->where(array('id' => 1))->find();
			S('home_config', $config);
		}
		if (!$config['web_close']) {
			exit($config['web_close_cause']);
		}

		C($config);
//        dump(C('contact_bank'));
		C('contact_qq', explode('|', C('contact_qq')));
		C('contact_qqun', explode('|', C('contact_qqun')));
		C('contact_bank', explode('|', C('contact_bank')));
		$coin = S('home_coin');
		if (!$coin) {
			$coin = M('Coin')->where(array('status' => 1))->select();
			S('home_coin', $coin);
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

		C($coinList);
		$market = S('home_market');
		$market_type = array();
		$coin_on = array();
		if (!$market) {
			$market = M('Market')->where(array('status' => 1))->order('sort desc,id asc')->select();
			S('home_market', $market);
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
			$v['xnbimg'] = C('coin')[$v['xnb']]['img'];
			$v['rmbimg'] = C('coin')[$v['rmb']]['img'];
			$v['volume'] = $v['volume'] * 1;
			$v['change'] = $v['change'] * 1;
			$v['title'] = C('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
			$v['navtitle'] = C('coin')[$v['xnb']]['title'] . '(' . strtoupper($v['xnb']). ')';
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

		C('market_type',$market_type);
		C('coin_on',$coin_on);
		C($marketList);
		$C = C();

		foreach ($C as $k => $v) {
			$C[strtolower($k)] = $v;
		}
		$this->assign('C', $C);
		$this->kefu = './Application/Home/View/Kefu/' . $C['kefu'] . '/index.html';
		

		if (!S('daohang')) {
			$this->daohang = M('Daohang')->where(array('status' => 1))->order('sort asc')->select();
			S('daohang', $this->daohang);
		} else {
			$this->daohang = S('daohang');
		}

		$footerArticleType = S('footer_indexArticleType');
		if (!$footerArticleType) {
			$footerArticleType = M('ArticleType')->where(array('status' => 1, 'footer' => 1, 'shang' => ''))->order('sort asc ,id desc')->limit(3)->select();
			S('footer_indexArticleType', $footerArticleType);
		}

		$this->assign('footerArticleType', $footerArticleType);
		$footerArticle = S('footer_indexArticle');
		if (!$footerArticle) {
			foreach ($footerArticleType as $k => $v) {
				$footerArticle[$v['name']] = M('ArticleType')->where(array('shang' => $v['name'], 'footer' => 1, 'status' => 1))->order('id asc')->limit(4)->select();
			}

			S('footer_indexArticle', $footerArticle);
		}



        //判断语言包
        if(cookie('language') === 'en-us'){
            C('web_name',L('WEB_NAME'));
            C('web_title', L('WEB_TITLE'));
            C('web_keywords', L('WEB_KEYWORDS'));
            C('top_name', L('TOP_NAME'));
            C('web_description', L('WEB_DESCRIPTION'));

            $this->daohang[0]['title'] = 'Finance';
            $this->daohang[1]['title'] = 'Safe';
            $this->daohang[2]['title'] = 'Article';
            $this->daohang[3]['title'] = 'Contact';
//            $this->daohang[4]['title'] = 'Trade rankings';
        }
        if(cookie('language') === 'ko'){
            C('web_name',L('WEB_NAME'));
            C('web_title', L('WEB_TITLE'));
            C('web_keywords', L('WEB_KEYWORDS'));
            C('top_name', L('TOP_NAME'));
            C('web_description', L('WEB_DESCRIPTION'));

            $this->daohang[0]['title'] = '재무 센터';
            $this->daohang[1]['title'] = '안전 센터';
            $this->daohang[2]['title'] = '공고 / 안내';
            $this->daohang[3]['title'] = '질문';
//            $this->daohang[4]['title'] = '거래 순위표';
        }
		$this->assign('footerArticle', $footerArticle);
        $this->assign('daohang', $this->daohang);

        //获取邀请排名
        $userid = M('Myzr')->field('userid')->where(['coinname' => 'wcg', 'addtime' => ['gt', '1520274600']])->group('userid')->select();
        foreach ($userid as $k => $v) {
            $ids[] = $v['userid'];
        }
	if(!$ids) $ids='';
        $invite_list = M('User')->field('invit_1, count(*) as count')->where([
            'idcardauth' => 1,
            'addtime' => ['gt', '1520274600'],
            'id' => ['in', $ids]
        ])->group('invit_1')->order('count desc')->limit(100)->select();
        foreach ($invite_list as $k => $v) {
            if ($v['invit_1'] == 0) {
                unset($invite_list[$k]);
            } else {
                $invite_list[$k]['username'] = substr_replace(M('User')->where(['id' => $v['invit_1']])->getField('username'), '****', 3, 4);
            }
        }

        $this->assign('invite_list', $invite_list);

        if (!cookie('small_tip')){
            $this->assign('article_66', M('Article')->where(['type' => 'aaa'])->order('id desc')->limit(1)->getField('content'));
        }
	}

    /**
     * 请求处理
     */
    protected function request_filter($type = '')
    {
        $ip = get_client_ip(0, true);//获取当前访问者的ip
        $logFilePath = dirname(THINK_PATH) . '/Runtime/';//日志记录文件保存目录
        $fileht = $logFilePath . 'forbidden.dat';//被禁止的ip记录文件
        $allowtime = 60;//防刷新时间
        $allownum = 10;//防刷新次数
        $allowRefresh = 120;//在允许刷新次数之后加入禁止ip文件中
        if (!file_exists($fileht)) {
            file_put_contents($fileht, '');
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
        if (file_exists($fileforbid)) {
            if ($time - filemtime($fileforbid) > 30) {
                @unlink($fileforbid);
            } else {
                $fileforbidarr = @file($fileforbid);
                if ($ip == substr($fileforbidarr[0], 0, strlen($ip))) {
                    if ($time - substr($fileforbidarr[1], 0, strlen($time)) > 120) {
                        @unlink($fileforbid);
                    } else if ($fileforbidarr[2] > $allowRefresh) {
                        file_put_contents($fileht, $ip . "\r\n", FILE_APPEND);
                        @unlink($fileforbid);
                    } else {
                        $fileforbidarr[2]++;
                        file_put_contents($fileforbid, $fileforbidarr);
                    }
                }
            }
        }

        //防刷新
        $str = '';
        $file = $logFilePath . 'ipdate.dat';
        if (!file_exists($logFilePath) && !is_dir($logFilePath)) {
            mkdir($logFilePath, 0777);
        }

        if (!file_exists($file)) {
            file_put_contents($file, '');
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
                        if (!file_exists($fileforbid)) {
                            $addforbidarr = array($ip . "\r\n", time() . "\r\n", 1);
                            file_put_contents($fileforbid, $addforbidarr);
                        }
                        file_put_contents($logFilePath . 'forbided_ip.log', $ip . '--' . date('Y-m-d H:i:s', time()) . '--' . $uri . "\r\n", FILE_APPEND);
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

        file_put_contents($file, $str);
    }

    public function _empty() {
        send_http_status(404);
        $this->error();
        echo '模块不存在！';
        die();
    }
	
    public function sign_log($msg,$level='INFO'){
        C('LOG_PATH','/tmp/tp_log/');
        Log::write(json_encode($msg),$level);
    }
	
}

?>
