<?php
namespace app\home\controller;
use app\home\controller\HomeCommon;
use think\Request;
use think\Db;
use think\Cache;
use think\session;
class Trade extends HomeCommon
{
    public function index()
    {

         $indexAdver = Cache::store('redis')->get('index_indexAdver');
        if (!$indexAdver) {
            $indexAdver = Db::name('Adver')->where(['status' => 1])->order('id asc')->select();
            Cache::store('redis')->set('index_indexAdver', $indexAdver);
        }
        $indexArticleType =  Cache::store('redis')->get('index_indexArticleType');

        if (!$indexArticleType) {
            $indexArticleType =  Db::name('ArticleType')->where(['status' => 1, 'index' => 1])->order('sort asc ,id desc')->limit(3)->select();
             Cache::store('redis')->set('index_indexArticleType', $indexArticleType);
        }
        $indexArticle=  Cache::store('redis')->get('index_indexArticle');
        if (!$indexArticle) {
            foreach ($indexArticleType as $k => $v) {
                $indexArticle[$k] =  Db::name('Article')->where(['type' => $v['name'], 'status' => 1, 'index' => 1])->order('id desc')->limit(6)->select();
            }
             Cache::store('redis')->set('index_indexArticle', $indexArticle);
        }

        $this->assign('indexArticleType', $indexArticleType);
        $this->assign('indexArticle', $indexArticle);
        $this->assign('indexAdver',$indexAdver);

        $market = input('market');
        if (!$market ||  $market=='market') {
            $market = config('market_mr');
        }

        $showPW = 1;
        if (userid()) {

            $user = Db::name('User')->where(['id' => userid()])->find();
            if ($user['tpwdsetting'] == 3) {
                $showPW = 0;
            }

            if ($user['tpwdsetting'] == 1) {
                if (session(userid() . 'tpwdsetting')) {
                    $showPW = 2;
                }
            }
        }


        //获取最佳买入价、卖出价
        $buy_best_price = Db::name('Trade')->where(['market' => $market , 'status' => 0 , 'type' => 2])->order('price asc')->find();
        $sell_best_price = Db::name('Trade')->where(['market' => $market , 'status' => 0 , 'type' => 1])->order('price desc')->find();

        $sell_best_price['price'] =round($sell_best_price['price'] , 4) ;
        $buy_best_price['price'] =round($buy_best_price['price'] , 4) ;
        $this->assign('buy_best_price', $buy_best_price);
        $this->assign('sell_best_price', $sell_best_price);
        $market_time_weike = config('market')[$market]['begintrade']."-".config('market')[$market]['endtrade'];
        $this->assign('market_time', $market_time_weike);
        $this->assign('showPW', $showPW);
        $this->assign('market', $market);
        $this->assign('xnb', explode('_', $market)[0]);
        $this->assign('rmb', explode('_', $market)[1]);
        return $this->fetch();
    }

    public function chart()
    {
        $market = input('market');
        if (!$market) {
            $market = config('market_mr');
        }

        $this->assign('market', $market);
        $this->assign('xnb', explode('_', $market)[0]);
        $this->assign('rmb', explode('_', $market)[1]);
        return $this->fetch();
    }

    public function chartweike(){
        $weike = input('weike');
        if (!$weike) {
            $weike = config('market_mr');
        }

        $weike_getCoreConfig = weike_getCoreConfig();
        if(!$weike_getCoreConfig){
            $this->error('');
        }
        Cache::store('redis')->get('jiaoyiqu');
        if(!$jiaoyiqu){
            foreach(config('market') as $k => $v){
                $jiaoyiqu[$v['jiaoyiqu']][] = $k;
            }
            Cache::store('redis')->set('jiaoyiqu',$jiaoyiqu);
        }

        $this->assign('weike', $weike);
        $this->assign('weike_jiaoyiqu', $weike_getCoreConfig['weike_indexcat']);
        $this->assign('weike_marketjiaoyiqu', $jiaoyiqu);
        $this->assign('weike_xnb', explode('_', $weike)[0]);
        $this->assign('weike_rmb', explode('_', $weike)[1]);
        return $this->fetch();

    }

    public function info()
    {
        $market = input('market');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!$market) {
            $market = config::get('market_mr');
        }

        $this->assign('market', $market);
        $this->assign('xnb', explode('_', $market)[0]);
        $this->assign('rmb', explode('_', $market)[1]);
         return $this->fetch();
    }

    public function comment()
    {
        $market = input('market');
        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!$market) {
            $market = config('market_mr');
        }

        if (!$market) {
            $market = config('market_mr');
        }

        $this->assign('market', $market);
        $this->assign('xnb', explode('_', $market)[0]);
        $this->assign('rmb', explode('_', $market)[1]);
        $where['coinname'] = explode('_', $market)[0];
        $Moble = Db::name('CoinComment');
        $list = $Moble->where($where)->order('id desc')->paginate(10, false,[]);
        $page = $list->render();

        if ($list){
            foreach ($list as $k => $v) {
                $data = $v;
                $data['username'] = Db::name('User')->where(['id' => $v['userid']])->value('username');
                $list->offsetSet($k,$data);
            }
        }


        $this->assign('list', $list);
        $this->assign('page', $page);
        return $this->fetch();
    }

    public function ordinary()
    {
        $market = input('market');
        if (!$market) {
            $market = config('market_mr');
        }
        $this->assign('market', $market);
       return $this->fetch();
    }

    public function specialty()
    {
        $market = input('market');
        if (!$market) {
            $market = config('market_mr');
        }

        $this->assign('market', $market);
        return $this->fetch();
    }

    //判断用户是买还是卖
    public function upTrade()
    {
        $paypassword = input('post.paypassword');
        $market = input('post.market');
        $price = input('post.price');
        $num = input('post.num');
        $type = input('post.type');
        $uid = userid();
        if (!$uid) {
            exit(json_encode(array('code'=>0,'msg'=>'请先登录！')));
        }else {


            $user_status = Db::name('user')->where('id='.$uid)->value('status');

            if (!$user_status){
                $this->error('用户被冻结，请联系客服人员!');
            }
            if (!config('market')[$market]['trade']) {
                $usertype = Db::name('user')->where(['id' => $uid])->find();
                if ($usertype['usertype'] == 0) {
                    $this->error('非交易时间段，禁止交易');
                }
            }

            if(strlen($price) > 10 || strlen($num) > 10){
                $this->error('价格或者数量超过预定长度！');
            }

            if (config('market')[$market]['begintrade']) {
                $begintrade = config('market')[$market]['begintrade'];
            } else {
                $begintrade = "00:00:00";
            }

            if (config('market')[$market]['endtrade']) {
                $endtrade = config('market')[$market]['endtrade'];
            } else {
                $endtrade = "23:59:59";
            }


            $trade_begin_time = strtotime(date("Y-m-d") . " " . $begintrade);
            $trade_end_time = strtotime(date("Y-m-d") . " " . $endtrade);
            $cur_time = time();

            if ($cur_time < $trade_begin_time || $cur_time > $trade_end_time) {
                $this->error('当前市场禁止交易,交易时间为每日' . $begintrade . '-' . $endtrade);
            }

            if (!check($price, 'double')) {
                $this->error('交易价格格式错误');
            }

            if (!check($num, 'double')) {
                $this->error('交易数量格式错误');
            }

            if (($type != 1) && ($type != 2)) {
                $this->error('交易类型格式错误');
            }

            $user = Db::name('User')->where(['id' => $uid])->find();

            //每次交易必须要整交易密码
            if ($user['tpwdsetting'] == 2) {
                if (md5($paypassword) != $user['paypassword']) {
                    $this->error('交易密码错误！');
                }
            }

            //登陆只需验证一次（$user['tpwdsetting']设置交易密码方式）
            if ($user['tpwdsetting'] == 1) {
                if (!session($uid . 'tpwdsetting')) {
                    if (md5($paypassword) != $user['paypassword']) {
                        $this->error('交易密码错误！');
                    } else {
                        session(userid() . 'tpwdsetting', 1);
                    }
                }
            }

            if (!config('market')[$market]) {
                $this->error('交易市场错误');
            } else {
                $xnb = explode('_', $market)[0];//要交易的币
                $rmb = explode('_', $market)[1];
            }

            // TODO: SEPARATE
            $price = round(floatval($price), 8);
            if (!$price) {
                $this->error('交易价格错误' . $price);
            }

            $num = round($num, 8);


            if (!check($num, 'double')) {
                $this->error('交易数量错误');
            }

            //$type的值来判断用户 是买 还是卖
            if ($type == 1) {
                $min_price = (config('market')[$market]['buy_min'] ? config('market')[$market]['buy_min'] : 1.0E-8);//买入最低价
                $max_price = (config('market')[$market]['buy_max'] ? config('market')[$market]['buy_max'] : 10000000);//买入最高价
            } else if ($type == 2) {
                $min_price = (config('market')[$market]['sell_min'] ? config('market')[$market]['sell_min'] : 1.0E-8);//卖出最低价
                $max_price = (config('market')[$market]['sell_max'] ? config('market')[$market]['sell_max'] : 10000000);//卖出最高价
            } else {
                $this->error('交易类型错误');
            }

            if ($max_price < $price) {
                $this->error('交易价格超过最大限制！');
            }

            if ($price < $min_price) {
                $this->error('交易价格低过最小限制！');
            }

            $hou_price = config('market')[$market]['hou_price'];   //每天 最后的价格
            if (!$hou_price) {
                $hou_price = config('market')[$market]['weike_faxingjia'];
            }

            if ($hou_price) {
                if (config('market')[$market]['zhang']) {
                    $zhang_price = round(($hou_price / 100) * (100 + config('market')[$market]['zhang']), 8);

                    if ($zhang_price < $price) {
                        $this->error('交易价格超过今日涨幅限制！');
                    }
                }

                if (config('market')[$market]['die']) {
                    $die_price = round(($hou_price / 100) * (100 - config('market')[$market]['die']), config('market')[$market]['round']);

                    if ($price < $die_price) {
                        $this->error('交易价格超过今日跌幅限制！');
                    }
                }
            }

            $user_coin = Db::name('UserCoin')->where(['userid' => $uid])->find();
            if ($type == 1) {
                $trade_fee = username() === '18985818487' ? 0 : config('market')[$market]['fee_buy'];//交易手续费

                if ($trade_fee) {
                    $fee = round((($num * $price) / 100) * $trade_fee, 8);//买入的手续费
                    $mum = round((($num * $price) / 100) * (100 + $trade_fee), 8);//用户买入时产生的费用
                } else {
                    $fee = 0;
                    $mum = round($num * $price, 8);
                }

                if ($user_coin[$rmb] < $mum) {//用户可用资产 与  用户买入时产生的费用 比较
                    $this->error(config('coin')[$rmb]['title'] . '余额不足！');
                }
            } else if ($type == 2) {

                $trade_fee = username() === '18985818487' ? 0 : config('market')[$market]['fee_sell'];

                if ($trade_fee) {
                    $fee = round((($num * $price) / 100) * $trade_fee, 8);//卖出的手续费
                    $mum = round((($num * $price) / 100) * (100 - $trade_fee), 8);//？
                } else {
                    $fee = 0;
                    $mum = round($num * $price, 8);
                }

                if ($user_coin[$xnb] < $num) {//判断用户该币可用数量
                    $this->error(config('coin')[$xnb]['title'] . '余额不足！');
                }
            } else {
                $this->error('交易类型错误');
            }

            //挂单比例
            if (config('coin')[$xnb]['fee_bili']) {
                if ($type == 2) {
                    // TODO: SEPARATE
                    $bili_user = round($user_coin[$xnb] + $user_coin[$xnb . 'd'], config('market')[$market]['round']);

                    if ($bili_user) {
                        // TODO: SEPARATE
                        $bili_keyi = round(($bili_user / 100) * config('coin')[$xnb]['fee_bili'], config('market')[$market]['round']);

                        if ($bili_keyi) {
                            $bili_zheng = Db::name('Trade')->field('id,price,sum(num-deal)as nums')->where(['userid' => $uid, 'status' => 0, 'type' => 2, 'market' => ['like', "%$xnb%"]])->select();
                            if (!$bili_zheng[0]['nums']) {
                                $bili_zheng[0]['nums'] = 0;
                            }

                            $bili_kegua = $bili_keyi - $bili_zheng[0]['nums'];
                            if ($bili_kegua < 0) {
                                $bili_kegua = 0;
                            }

                            if ($bili_kegua < $num) {
                                $this->error('您的挂单总数量超过系统限制，您当前持有' . config('coin')[$xnb]['title'] . $bili_user . '个，已经挂单' . $bili_zheng[0]['nums'] . '个，还可以挂单' . $bili_kegua . '个', '', 5);
                            }
                        } else {
                            $this->error('可交易量错误');
                        }
                    }
                }
            }

            //每日交易限制
            if (config('coin')[$xnb]['fee_meitian']) {
                if ($type == 2) {
                    $bili_user = round($user_coin[$xnb] + $user_coin[$xnb . 'd'], 8);

                    if ($bili_user < 0) {
                        $this->error('可交易量错误');
                    }

                    $kemai_bili = ($bili_user / 100) * config('coin')[$xnb]['fee_meitian'];
                    if ($kemai_bili < 0) {
                        $this->error('您今日只能再卖' . config('coin')[$xnb]['title'] . 0 . '个', '', 5);
                    }

                    $kaishi_time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                    $jintian_sell = Db::name('Trade')->where(array(
                        'userid' => $uid,
                        'addtime' => array('egt', $kaishi_time),
                        'type' => 2,
                        'status' => array('neq', 2),
                        'market' => array('like', '%' . $xnb . '%')
                    ))->sum('num');

                    if ($jintian_sell) {
                        $kemai = $kemai_bili - $jintian_sell;
                    } else {
                        $kemai = $kemai_bili;
                    }

                    if ($kemai < $num) {
                        if ($kemai < 0) {
                            $kemai = 0;
                        }

                        $this->error('您的挂单总数量超过系统限制，您今日只能再卖' . config('coin')[$xnb]['title'] . $kemai . '个', '', 5);
                    }
                }
            }

            //最小交易数量
            if (config('market')[$market]['trade_min']) {
                if ($mum < config('market')[$market]['trade_min']) {
                    $this->error('交易总额不能小于' . config('market')[$market]['trade_min']);
                }
            }

            //最大交易数量
            if (config('market')[$market]['trade_max']) {
                if (config('market')[$market]['trade_max'] < $mum) {
                    $this->error('交易总额不能大于' . config('market')[$market]['trade_max']);
                }
            }

            if (!$rmb) {
                $this->error('数据错误1');
            }

            if (!$xnb) {
                $this->error('数据错误2');
            }

            if (!$market) {
                $this->error('数据错误3');
            }

            if (!$price) {
                $this->error('数据错误4');
            }

            if (!$num) {
                $this->error('数据错误5');
            }

            if (!$mum) {
                $this->error('数据错误6');
            }

            if (!$type) {
                $this->error('数据错误7');
            }

            $mo = Db::name('');

            $mo->startTrans();
            $flag = false;
            $user_coin = Db::table('weike_user_coin')->where(['userid' => $uid])->find();

            if ($type == 1) {

                if ($user_coin[$rmb] < $mum) {
                    $this->error(config('coin')[$rmb]['title'] . '余额不足！');
                }

                try{
                    $finance =  $mo->table('weike_finance')->where(['userid' => $uid])->order('id desc')->find();//用户资产
                    $finance_num_user_coin = $mo->table('weike_user_coin')->where(['userid' => $uid])->find();
                    $mo->table('weike_user_coin')->where(['userid' => $uid])->setDec($rmb, $mum);//可用资产   剩余的
                    $mo->table('weike_user_coin')->where(['userid' => $uid])->setInc($rmb . 'd', $mum);
                    $finance_nameid = $mo->table('weike_trade')->insertGetId(['userid' => $uid, 'market' => $market, 'price' => $price, 'num' => $num, 'mum' => $mum, 'fee' => $fee, 'type' => 1, 'addtime' => time(), 'status' => 0]);

                    if ($rmb == "cny") {//cny 换成  cny
                        $finance_mum_user_coin =  $mo->table('weike_user_coin')->where(array('userid' => userid()))->find();
                        $finance_hash = md5($uid . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }
                       $mo->table('weike_finance')->insert([
                           'userid' => $uid,
                           'coinname' => 'cny',
                           'num_a' => $finance_num_user_coin['cny'],
                           'num_b' => $finance_num_user_coin['cnyd'],
                           'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'],
                           'fee' => $mum,
                           'type' => 2,
                           'name' => 'trade',
                           'nameid' => $finance_nameid,
                           'remark' => '交易中心-委托买入-市场' . $market,
                           'mum_a' => $finance_mum_user_coin['cny'],
                           'mum_b' => $finance_mum_user_coin['cnyd'],
                           'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
                           'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status
                       ]);
                    }
                    $flag = true;
                   $mo->commit();
                }catch (\Exception $e){
                    $flag = false;
                    $mo->rollback();
                }
            } else if ($type == 2) {

                if ($user_coin[$xnb] < $num) {

                    $this->error(config('coin')[$xnb]['title'] . '余额不足2！');
                }

                try{
                    $mo->table('weike_user_coin')->where(['userid' => $uid])->setDec($xnb, $num);
                    $mo->table('weike_user_coin')->where(['userid' => $uid])->setInc($xnb . 'd', $num);
                    $finance_nameid =$mo->table('weike_trade')->insertGetId([
                        'userid' => userid(),
                        'market' => $market,
                        'price' => $price,
                        'num' => $num,
                        'mum' => $mum,
                        'fee' => $fee,
                        'type' => 2,
                        'addtime' => time(),
                        'status' => 0
                    ]);
                    $flag = true;
                    $mo->commit();
                }catch (\Exception $e){
                    $flag = false;
                    $mo->rollback();
                }
            } else {
                $this->error('交易类型错误');
            }

            if ($flag) {
                Cache::rm('getDepth');
                $this->initMatchingTrade($market);
                $this->success('挂单成功！');
            } else {
                $this->error('交易失败！');
            }
        }
    }

    // 写入队列
    private static function write_queue($id){
        $trade = model('Trade');
        $param = $trade->group_id_get_data($id);
        switch($param['type']){
            case 1:
                $obj = new BuyRedis(config('redis'), $param['market']);
                break;
            case 2:
                $obj = new SellRedis(config('redis'), $param['market']);
                break;
        }
        $obj->init();
        $trade->check_redis($id);
    }

    private static function map_data($v){
        return json_decode($v);
    }

    //撮合交易
    public function matchingTrade($market = NULL)
    {
        $uid = userid();
        if (!$market) {
            return false;
        } else {
            $xnb = explode('_', $market)[0];
            $rmb = explode('_', $market)[1];
        }
        //特殊用户不需要买卖手续费
        $user_type = Db::name('User')->where(['id' =>$uid])->find();
        if ($user_type['usertype'] == 3){
            $fee_buy = 0 ;
            $fee_sell = 0;
        }else{
            $fee_buy = config('market')[$market]['fee_buy'];
            $fee_sell = config('market')[$market]['fee_sell'];
        }

        $invit_buy = config('market')[$market]['invit_buy'];
        $invit_sell = config('market')[$market]['invit_sell'];
        $invit_1 = config('market')[$market]['invit_1']?config('market')[$market]['invit_1']:0;
        $invit_2 = config('market')[$market]['invit_2']?config('market')[$market]['invit_2']:0;
        $invit_3 = config('market')[$market]['invit_3']?config('market')[$market]['invit_3']:0;
        //事务处理
        $mo = Db::name('');
        $new_trade_weike = 0;

        for (; true; ) {
            $buy =  $mo->table('weike_trade')->where(['market' => $market, 'type' => 1,'userid' => ['gt',0], 'status' => 0])->order('price desc,id asc')->find();
            $sell =  $mo->table('weike_trade')->where(['market' => $market, 'type' => 2,'userid' => ['gt',0],'status' => 0])->order('price asc,id asc')->find();
            if ($sell['id'] < $buy['id']) {
                $type = 1;
            } else {
                $type = 2;
            }

            //买的价格大于卖的价格
            if ($buy && $sell && (0 <= floatval($buy['price']) - floatval($sell['price']))) {
                $rs = [];
                $amount = min(round($buy['num'] - $buy['deal'], 8), round($sell['num'] - $sell['deal'], 8));
                $amount = round($amount, 8);
                if ($amount <= 0) {
                    $log = '错误1交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . "\n";
                    $log .= 'ERR: 成交数量出错，数量是' . $amount;
                    mlog($log);
                    Db::name('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                    Db::name('Trade')->where(['id' => $sell['id']])->setField('status', 1);
                    break;
                }

                if ($type == 1) {
                    $price = $sell['price'];
                } else if ($type == 2) {
                    $price = $buy['price'];
                } else {
                    break;
                }

                if (!$price) {
                    $log = '错误2交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                    $log .= 'ERR: 成交价格出错，价格是' . $price;
                    mlog($log);
                    break;
                } else {
                    // TODO: SEPARATE
                    $price = round($price, 8);
                }

                $mum = round($price * $amount, 8);
                if (!$mum) {
                    $log = '错误3交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . "\n";
                    $log .= 'ERR: 成交总额出错，总额是' . $mum;
                    mlog($log);
                    break;
                } else {
                    $mum = round($mum, 8);
                }

                if ($fee_buy) {
                    $buy_fee = round(($mum / 100) * $fee_buy, 8);
                    $buy_save = round(($mum / 100) * (100 + $fee_buy), 8);
                } else {
                    $buy_fee = 0;
                    $buy_save = $mum;
                }

                if (!$buy_save) {
                    $log = '错误4交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新数量出错，更新数量是' . $buy_save;
                    mlog($log);
                    break;
                }

                if ($fee_sell) {
                    $sell_fee = round(($mum / 100) * $fee_sell, 8);
                    $sell_save = round(($mum / 100) * (100 - $fee_sell), 8);
                } else {
                    $sell_fee = 0;
                    $sell_save = $mum;
                }

                if (!$sell_save) {
                    $log = '错误5交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家更新数量出错，更新数量是' . $sell_save;
                    mlog($log);
                    break;
                }

                $user_buy = Db::name('UserCoin')->where(['userid' => $buy['userid']])->find();
                if (!$user_buy[$rmb . 'd']) {
                    $log = '错误6交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家财产错误，冻结财产是' . $user_buy[$rmb . 'd'];
                    mlog($log);
                    break;
                }elseif ($user_buy[$rmb . 'd'] <= 0){
                    $log = '错误66交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家财产错误，冻结财产是' . $user_buy[$rmb . 'd'];
                    mlog($log);
                    break;
                }

                $user_sell = Db::name('UserCoin')->where(['userid' => $sell['userid']])->find();
                if (!$user_sell[$xnb . 'd']) {
                    $log = '错误7交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家财产错误，冻结财产是' . $user_sell[$xnb . 'd'];
                    mlog($log);
                    break;
                }elseif ($user_sell[$xnb . 'd'] <= 0){
                    $log = '错误77交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家财产错误，冻结财产是' . $user_sell[$xnb . 'd'];
                    mlog($log);
                    break;
                }

                if ($user_buy[$rmb . 'd'] < 1.0E-8) {
                    $log = '错误88交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新冻结人民币出现错误,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '进行错误处理';
                    mlog($log);
                    Db::name('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    break;
                }

                if ($buy_save <= round($user_buy[$rmb . 'd'], 8)) {
                    $save_buy_rmb = $buy_save;
                } else if ($buy_save <= round($user_buy[$rmb . 'd'], 8) + 1) {
                    $save_buy_rmb = $user_buy[$rmb . 'd'];
                    $log = '错误8交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新冻结人民币出现误差,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '实际更新' . $save_buy_rmb;
                    mlog($log);
                } else {
                    $log = '错误9交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新冻结人民币出现错误,应该更新' . $buy_save . '账号余额' . $user_buy[$rmb . 'd'] . '进行错误处理';
                    mlog($log);
                    Db::name('Trade')->where(array('id' => $buy['id']))->setField('status', 1);
                    break;
                }
                // TODO: SEPARATE

                if ($amount <= round($user_sell[$xnb . 'd'], 8)) {
                    $save_sell_xnb = $amount;
                } else {
                    // TODO: SEPARATE
                    if ($amount <= round($user_sell[$xnb . 'd'], 8) + 1) {
                        $save_sell_xnb = $user_sell[$xnb . 'd'];
                        $log = '错误10交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 卖家更新冻结虚拟币出现误差,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '实际更新' . $save_sell_xnb;
                        mlog($log);
                    } else {
                        $log = '错误11交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                        $log .= 'ERR: 卖家更新冻结虚拟币出现错误,应该更新' . $amount . '账号余额' . $user_sell[$xnb . 'd'] . '进行错误处理';
                        mlog($log);
                        Db::name('Trade')->where(array('id' => $sell['id']))->setField('status', 1);
                        break;
                    }
                }

                if (!$save_buy_rmb) {
                    $log = '错误12交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 买家更新数量出错错误,更新数量是' . $save_buy_rmb;
                    mlog($log);
                    Db::name('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                    break;
                }

                if (!$save_sell_xnb) {
                    $log = '错误13交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount . '成交价格' . $price . '成交总额' . $mum . "\n";
                    $log .= 'ERR: 卖家更新数量出错错误,更新数量是' . $save_sell_xnb;
                    mlog($log);
                    Db::name('Trade')->where(['id' => $sell['id']])->setField('status', 1);
                    break;
                }

                $mo->startTrans();
                $buy =$mo->table('weike_trade')->lock(true)->where(['id' => $buy['id']])->find();
                $sell =$mo->table('weike_trade')->lock(true)->where(['id' => $sell['id']])->find();
                if($buy['status'] != 0 || $sell['status'] != 0){
                    $mo->rollback();
                    break;
                }

                $rs[] = $mo->table('weike_trade')->where(['id' => $buy['id']])->setInc('deal', $amount);
                $rs[] = $mo->table('weike_trade')->where(['id' => $sell['id']])->setInc('deal', $amount);
                $rs[] = $finance_nameid = $mo->table('weike_trade_log')->insert([
                    'userid' => $buy['userid'],
                    'peerid' => $sell['userid'],
                    'market' => $market,
                    'price' => $price,
                    'num' => $amount,
                    'mum' => $mum,
                    'type' => $type,
                    'fee_buy' => $buy_fee,
                    'fee_sell' => $sell_fee,
                    'addtime' => time(),
                    'status' => 1
                ]);
                $rs[] =$mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->setInc($xnb, $amount);
                $finance = $mo->table('weike_finance')->where(['userid' => $buy['userid']])->order('id desc')->find();
                $finance_num_user_coin =$mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->find();
                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->setDec($rmb . 'd', $save_buy_rmb);
                $finance_mum_user_coin =$mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->find();
                $finance_hash = md5($buy['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                if($rmb == "cny"){
                    $rs[] = $mo->table('weike_finance')->insert([
                        'userid' => $buy['userid'],
                        'coinname' => 'cny',
                        'num_a' => $finance_num_user_coin['cny'],
                        'num_b' => $finance_num_user_coin['cnyd'],
                        'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'],
                        'fee' => $save_buy_rmb,
                        'type' => 2,
                        'name' => 'tradelog',
                        'nameid' => $finance_nameid,
                        'remark' => '交易中心-成功买入-市场' . $market,
                        'mum_a' => $finance_mum_user_coin['cny'],
                        'mum_b' => $finance_mum_user_coin['cnyd'],
                        'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
                        'move' => $finance_hash, 'addtime' => time(),
                        'status' => $finance_status
                    ]);
                }

                $finance =$mo->table('weike_finance')->where(['userid' => $sell['userid']])->order('id desc')->find();
                $finance_num_user_coin =$mo->table('weike_user_coin')->where(['userid' => $sell['userid']])->find();
                $rs[] =$mo->table('weike_user_coin')->where(['userid' => $sell['userid']])->setInc($rmb, $sell_save);
                $finance_mum_user_coin = $mo->table('weike_user_coin')->where(['userid' => $sell['userid']])->find();
                $finance_hash = md5($sell['userid'] . $finance_num_user_coin['cny'] . $finance_num_user_coin['cnyd'] . $mum . $finance_mum_user_coin['cny'] . $finance_mum_user_coin['cnyd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                if($rmb == "cny"){
                    $rs[] =$mo->table('weike_finance')->insert([
                        'userid' => $sell['userid'],
                        'coinname' => 'cny',
                        'num_a' => $finance_num_user_coin['cny'],
                        'num_b' => $finance_num_user_coin['cnyd'],
                        'num' => $finance_num_user_coin['cny'] + $finance_num_user_coin['cnyd'],
                        'fee' => $save_buy_rmb,
                        'type' => 1,
                        'name' => 'tradelog',
                        'nameid' => $finance_nameid,
                        'remark' => '交易中心-成功卖出-市场' . $market,
                        'mum_a' => $finance_mum_user_coin['cny'],
                        'mum_b' => $finance_mum_user_coin['cnyd'],
                        'mum' => $finance_mum_user_coin['cny'] + $finance_mum_user_coin['cnyd'],
                        'move' => $finance_hash,
                        'addtime' => time(),
                        'status' => $finance_status
                    ]);
                }

                $rs[] = $mo->table('weike_user_coin')->where(['userid' => $sell['userid']])->setDec($xnb . 'd', $save_sell_xnb);
                $buy_list = $mo->table('weike_trade')->where(array('id' => $buy['id'], 'status' => 0))->find();
                if ($buy_list) {
                    if ($buy_list['num'] <= $buy_list['deal']) {
                        $rs[] =$mo->table('weike_trade')->where(['id' => $buy['id']])->setField('status', 1);
                    }
                }


                $sell_list =$mo->table('weike_trade')->where(['id' => $sell['id'], 'status' => 0])->find();
                if ($sell_list) {
                    if ($sell_list['num'] <= $sell_list['deal']) {
                        $rs[] = $mo->table('weike_trade')->where(['id' => $sell['id']])->setField('status', 1);
                    }
                }

                if ($price < $buy['price']) {
                    $chajia_dong = round((($amount * $buy['price']) / 100) * (100 + $fee_buy), 8);
                    $chajia_shiji = round((($amount * $price) / 100) * (100 + $fee_buy), 8);
                    $chajia = round($chajia_dong - $chajia_shiji, 8);

                    if ($chajia) {
                        $chajia_user_buy = $mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->find();

                        if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8)) {
                            $chajia_save_buy_rmb = $chajia;
                        } else if ($chajia <= round($chajia_user_buy[$rmb . 'd'], 8) + 1) {
                            $chajia_save_buy_rmb = $chajia_user_buy[$rmb . 'd'];
                            mlog('错误91交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
                            mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现误差,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '实际更新' . $chajia_save_buy_rmb);
                        } else {
                            mlog('错误92交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '交易方式：' . $type . '成交数量' . $amount, '成交价格' . $price . '成交总额' . $mum . "\n");
                            mlog('交易市场' . $market . '出错：买入订单:' . $buy['id'] . '卖出订单：' . $sell['id'] . '成交数量' . $amount . '交易方式：' . $type . '卖家更新冻结虚拟币出现错误,应该更新' . $chajia . '账号余额' . $chajia_user_buy[$rmb . 'd'] . '进行错误处理');
                            $mo->rollback();
                            Db::name('Trade')->where(['id' => $buy['id']])->setField('status', 1);
                            break;
                        }

                        if ($chajia_save_buy_rmb) {
                            $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->setDec($rmb . 'd', $chajia_save_buy_rmb);
                            $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->setInc($rmb, $chajia_save_buy_rmb);
                        }
                    }
                }

                $you_buy =$mo->table('weike_trade')->where([
                    'market' => array('like', '%' . $rmb . '%'),
                    'status' => 0,
                    'userid' => $buy['userid']
                ])->find();
                $you_sell =$mo->table('weike_trade')->where([
                    'market' => array('like', '%' . $xnb . '%'),
                    'status' => 0,
                    'userid' => $sell['userid']
                ])->find();

                if (!$you_buy) {
                    $you_user_buy = $mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->find();

                    if (0 < $you_user_buy[$rmb . 'd']) {
                        $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->setField($rmb . 'd', 0);
                        $rs[] = $mo->table('weike_user_coin')->where(['userid' => $buy['userid']])->setInc($rmb, $you_user_buy[$rmb . 'd']);
                    }
                }

                if (!$you_sell) {
                    $you_user_sell =$mo->table('weike_user_coin')->where(['userid' => $sell['userid']])->find();

                    if (0 < $you_user_sell[$xnb . 'd']) {
                        $rs[] = $mo->table('weike_user_coin')->where(['userid' => $sell['userid']])->setField($xnb . 'd', 0);
                        $rs[] =$mo->table('weike_user_coin')->where(['userid' => $sell['userid']])->setInc($xnb, $you_user_sell[$xnb . 'd']);
                    }
                }

                $invit_buy_user = $mo->table('weike_user')->where(['id' => $buy['userid']])->find();
                $invit_sell_user = $mo->table('weike_user')->where(['id' => $sell['userid']])->find();

                if ($invit_buy) {
                    if ($invit_1) {
                        if ($buy_fee) {
                            if ($invit_buy_user['invit_1']) {
                                $invit_buy_save_1 = round(($buy_fee / 100) * $invit_1, 6);

                                if ($invit_buy_save_1) {
                                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => $invit_buy_user['invit_1']])->setInc($rmb, $invit_buy_save_1);
                                    $rs[] = $mo->table('invit')->insert(['userid' => $invit_buy_user['invit_1'], 'invit' => $buy['userid'], 'name' => '一代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_1, 'addtime' => time(), 'status' => 1]);
                                }
                            }

                            if ($invit_buy_user['invit_2']) {
                                $invit_buy_save_2 = round(($buy_fee / 100) * $invit_2, 6);

                                if ($invit_buy_save_2) {
                                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => $invit_buy_user['invit_2']])->setInc($rmb, $invit_buy_save_2);
                                    $rs[] = $mo->table('weike_invit')->insert(['userid' => $invit_buy_user['invit_2'], 'invit' => $buy['userid'], 'name' => '二代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_2, 'addtime' => time(), 'status' => 1]);
                                }
                            }

                            if ($invit_buy_user['invit_3']) {
                                $invit_buy_save_3 = round(($buy_fee / 100) * $invit_3, 6);

                                if ($invit_buy_save_3) {
                                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => $invit_buy_user['invit_3']])->setInc($rmb, $invit_buy_save_3);
                                    $rs[] =$mo->table('weike_invit')->insert(['userid' => $invit_buy_user['invit_3'], 'invit' => $buy['userid'], 'name' => '三代买入赠送', 'type' => $market . '买入交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_buy_save_3, 'addtime' => time(), 'status' => 1]);
                                }
                            }
                        }
                    }

                    if ($invit_sell) {
                        if ($sell_fee) {
                            if ($invit_sell_user['invit_1']) {
                                $invit_sell_save_1 = round(($sell_fee / 100) * $invit_1, 6);

                                if ($invit_sell_save_1) {
                                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => $invit_sell_user['invit_1']])->setInc($rmb, $invit_sell_save_1);
                                    $rs[] =$mo->table('weike_invit')->insert(['userid' => $invit_sell_user['invit_1'], 'invit' => $sell['userid'], 'name' => '一代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_1, 'addtime' => time(), 'status' => 1]);
                                }
                            }

                            if ($invit_sell_user['invit_2']) {
                                $invit_sell_save_2 = round(($sell_fee / 100) * $invit_2, 6);

                                if ($invit_sell_save_2) {
                                    $rs[] = $mo->table('weike_user_coin')->where(['userid' => $invit_sell_user['invit_2']])->setInc($rmb, $invit_sell_save_2);
                                    $rs[] = $mo->table('weike_invit')->insert(['userid' => $invit_sell_user['invit_2'], 'invit' => $sell['userid'], 'name' => '二代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_2, 'addtime' => time(), 'status' => 1]);
                                }
                            }

                            if ($invit_sell_user['invit_3']) {
                                $invit_sell_save_3 = round(($sell_fee / 100) * $invit_3, 6);

                                if ($invit_sell_save_3) {
                                    $rs[] =$mo->table('weike_user_coin')->where(['userid' => $invit_sell_user['invit_3']])->setInc($rmb, $invit_sell_save_3);
                                    $rs[] = $mo->table('weike_invit')->insert(['userid' => $invit_sell_user['invit_3'], 'invit' => $sell['userid'], 'name' => '三代卖出赠送', 'type' => $market . '卖出交易赠送', 'num' => $amount, 'mum' => $mum, 'fee' => $invit_sell_save_3, 'addtime' => time(), 'status' => 1]);
                                }
                            }
                        }
                    }
                }

                if (check_arr($rs)) {
                    $mo->commit();
                    $new_trade_weike = 1;
                    $jiaoyiqu = config('market')[$market]['jiaoyiqu'];

                    Cache::rm('weike_allcoin'.$jiaoyiqu);
                    Cache::rm('marketjiaoyie24'.$jiaoyiqu);
                    Cache::rm('allsum');
                    Cache::rm('getJsonTop' . $market);
                    Cache::rm('getTradelog' . $market);
                    Cache::rm('getDepth' . $market . '1');
                    Cache::rm('getDepth' . $market . '3');
                    Cache::rm('getDepth' . $market . '4');
                    Cache::rm('ChartgetJsonData' . $market);
                    Cache::rm('allcoin');
                    Cache::rm('trends');
                } else {
                    $mo->rollback();
                    //$mo->execute('rollback');
                    //$mo->execute('unlock tables');
                    break;
                }

            } else {
                break;
            }

            unset($rs);
        }

        if ($new_trade_weike) {
            $new_price = round(Db::name('TradeLog')->where(['market' => $market, 'status' => 1])->order('id desc')->column('price'), 6);
            $buy_price = round(Db::name('Trade')->where(['type' => 1, 'market' => $market, 'status' => 0])->max('price'), 6);
            $sell_price = round(Db::name('Trade')->where(['type' => 2, 'market' => $market, 'status' => 0])->min('price'), 6);
            $min_price = round(Db::name('TradeLog')->where([
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ])->min('price'), 6);
            $max_price = round(Db::name('TradeLog')->where([
                'market'  => $market,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ])->max('price'), 6);

            $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
            //time() - (60 * 60 * 24)
            $volume = round(Db::name('TradeLog')->where([
                'market'  => $market,
                'addtime' => array('gt',$beginToday)
            ])->sum('num'), 6);

            $sta_price = round(Db::name('TradeLog')->where([
                'market'  => $market,
                'status'  => 1,
                'addtime' => array('gt', time() - (60 * 60 * 24))
            ])->order('id asc')->column('price'), 6);
            $Cmarket = Db::name('Market')->where(['name' => $market])->find();

            if ($Cmarket['new_price'] != $new_price) {
                $upCoinData['new_price'] = $new_price;
                Cache::rm('get_new_price_'.$market);
            }

            if ($Cmarket['buy_price'] != $buy_price) {
                $upCoinData['buy_price'] = $buy_price;
            }

            if ($Cmarket['sell_price'] != $sell_price) {
                $upCoinData['sell_price'] = $sell_price;
            }

            if ($Cmarket['min_price'] != $min_price) {
                $upCoinData['min_price'] = $min_price;
            }

            if ($Cmarket['max_price'] != $max_price) {
                $upCoinData['max_price'] = $max_price;
            }

            if ($Cmarket['volume'] != $volume) {
                $upCoinData['volume'] = $volume;
            }

            $change = round((($new_price - $Cmarket['hou_price']) / $Cmarket['hou_price']) * 100, 2);
            $upCoinData['change'] = $change;

            if ($upCoinData) {
                Db::name('Market')->where(['name' => $market])->update($upCoinData);
                Db::name('Market')->execute('commit');
                Cache::rm('home_market');
            }
        }
    }

    //挂单撤销
    public function chexiao($id)
    {

        if (!userid()) {
            $this->error('请先登录！');
        }

        if (!check($id, 'd')) {
            $this->error('请选择要撤销的委托！');
        }

        $trade = Db::name('Trade')->where(['id' => $id])->find();

        if (!$trade) {
            $this->error('撤销委托参数错误！');
        }

        if ($trade['userid'] != userid()) {
            $this->error('参数非法！');
        }
        $rs = model('Trade')->chexiao($id);

        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    public function initMatchingTrade($market = null)
    {
        if ($market){
            $ht = getHttpType();
            $ch = curl_init();
            $url = $ht . $_SERVER['SERVER_NAME'] . '/home/trade/matchingTrade/market/'.$market;
            $ch = curl_init();
            if ($ht == 'https://'){
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ( $ch, CURLOPT_TIMEOUT, 1 );
            curl_exec($ch);
            curl_close($ch);
        }
    }
}

?>