<?php
namespace app\common\model;

use think\Model;
use think\Db;

class Market extends Model
{
    protected $key = 'home_market';
    public function getMarket($flush = false){
        $marketList = (config('app_debug') || $flush) ? null : cache($this->key);
        if (empty($marketList)) {
            $market = Db::name('market')->where(array('status' => 1))->select();
            foreach ($market as $k => $v) {
                $v['new_price'] = round($v['new_price'], $v['round']);
                $v['buy_price'] = round($v['buy_price'], $v['round']);
                $v['sell_price'] = round($v['sell_price'], $v['round']);
                $v['min_price'] = round($v['min_price'], $v['round']);
                $v['max_price'] = round($v['max_price'], $v['round']);
                list($v['xnb'], $v['rmb']) = explode('_', $v['name']);
                $v['xnbimg'] = isset(config('coin')[$v['xnb']]['img']) ? config('coin')[$v['xnb']]['img'] : '';
                $v['rmbimg'] = isset(config('coin')[$v['rmb']]['img']) ? config('coin')[$v['rmb']]['img'] : '';
                $v['volume'] = $v['volume'] * 1;
                $v['change'] = $v['change'] * 1;
                $v['title'] = isset(config('coin')[$v['xnb']]['title']) ? config('coin')[$v['xnb']]['title'] : '' . '(' . strtoupper($v['xnb']) . '/' . strtoupper($v['rmb']) . ')';
                $marketList[$v['name']] = $v;
            }
            cache($this->key, $marketList);
        }

        return $marketList;
    }

	public function get_new_price($market = NULL)
	{
		if (empty($market)) {
			return null;
		}

		$get_new_price = cache('get_new_price_' . $market);
		if (!$get_new_price) {
			$get_new_price = Db::name('Market')->where(['name' => $market])->value('new_price');
			cache('get_new_price_' . $market, $get_new_price);
		}

		return $get_new_price;
	}

	public function get_title($market = NULL)
	{
		$xnb = explode('_', $market)[0];
		$rmb = explode('_', $market)[1];
		$coin = new Coin();
		$xnb_title = $coin->get_title($xnb);
		$rmb_title = $coin->get_title($rmb);
		return $xnb_title . '/' . $rmb_title;
	}
}

?>