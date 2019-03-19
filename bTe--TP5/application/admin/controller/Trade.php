<?php

namespace app\admin\controller;

use think\Db;
use think\Exception;

class Trade extends Admin
{
    public function index()
    {
        $name = input('name/s', null);
        $field = input('field/s', null);
        $market = input('market/s', null);
        $status = input('status/s', null);
        $type = input('type/d', 0);
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }
        if ($market) {
            $where['market'] = $market;
        }
        if ($status) {
            $where['status'] = $status;
        }

        if ($status == 0 && $status != null) {
            $where['status'] = 0;
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }
        $weike_getSum = Db::name('Trade')->where($where)->sum('mum');

        //获取已成交总数量，已成交总额额
        $weike_num = Db::name('Trade')->where($where)->sum('deal');

        $weike_total = round(Db::name('Trade')->where($where)->sum('price * deal'), 8);

        $list = Db::name('Trade')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['usertype'] = Db::name('User')->where(array('id' => $item['userid']))->value('usertype');
            //获取已成交总额
            $item['deal_mum'] = $item['deal'] * $item['price'];
            return $item;
        });
        $show = $list->render();
        $count = $list->total();

        $this->assign('list', $list);
        $this->assign('weike_count', $count);
        $this->assign('weike_getSum', $weike_getSum);
        $this->assign('page', $show);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_total', $weike_total);
        return $this->fetch();
    }

    //撤单
    public function chexiao()
    {
        $id = input('id/d');

        if (!check($id, 'd')) {
            $this->error('请选择要撤销的委托！');
        }
        $trade = Db::name('Trade')->where(array('id' => $id))->find();
        if (!$trade) {
            $this->error('撤销委托参数错误！');
        }

        $rs = model('Trade')->chexiao($id);
        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    //一键撤单
    public function autochexiao()
    {
        $ids = input('ids/s');
        $trade = input('trade/d');
        $ids = explode(',', $ids);
        $count = count($ids);
        if ($count > 15) {
            $this->error('不能撤销数量超过 15 的委托！');
        }

        if ($trade == 1) {
            foreach ($ids as $k => $v) {
                $rs = model('Trade')->chexiao($v);
            }
        } else if ($trade == 2) {
            foreach ($ids as $k => $v) {
                $rs = Db::name('UserC2cTrade')->where(['id' => $v])->update(['status' => 2]);
                if (check_arr($rs)) {
                    $rs = array('1', '撤销成功');
                } else {
                    $rs = array('0', '撤销失败4|' . implode('|', $rs));
                }
            }
        } else {
            $this->error('非法操作');
        }


        if ($rs[0]) {
            $this->success($rs[1]);
        } else {
            $this->error($rs[1]);
        }
    }

    public function log()
    {
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');
        $market = input('market/s');
        $type = input('type');
        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else if ($field == 'peername') {
                $where['peerid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }
        if ($type == 1 || $type == 2) {
            $where['type'] = $type;
        }

        if ($market) {
            $where['market'] = $market;
        }

        $count = Db::name('TradeLog')->where($where)->count();
        $weike_getSum = Db::name('TradeLog')->where($where)->sum('mum');
        $weike_num = Db::name('TradeLog')->where($where)->sum('num');
        $list = Db::name('TradeLog')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['peername'] = Db::name('User')->where(array('id' => $item['peerid']))->value('username');
            $item['usertype'] = Db::name('User')->where(array('id' => $item['peerid']))->value('usertype');
            return $item;
        });
        $show = $list->render();


        $this->assign('weike_count', $count);
        $this->assign('weike_getSum', $weike_getSum);
        $this->assign('weike_num', $weike_num);
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function chat()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $list = Db::name('Chat')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function chatStatus()
    {
        $id = input('id/d');
        $type = input('type');
        $moble = input('moble', 'Chat');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function comment()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $coinname = strval(input('coinname'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($coinname) {
            $where['coinname'] = $coinname;
        }

        $list = Db::name('CoinComment')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function commentStatus()
    {
        $id = input('id/d');
        $type = input('type');
        $moble = input('moble', 'CoinComment');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function market()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $list = Db::name('Market')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            if ($item['begintrade']) {
                $begintrade = substr($item['begintrade'], 0, 5);
            } else {
                $begintrade = "00:00";
            }
            if ($item['endtrade']) {
                $endtrade = substr($item['endtrade'], 0, 5);
            } else {
                $endtrade = "23:59";
            }

            $item['tradetimeweike'] = $begintrade . "-" . $endtrade;
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    public function marketEdit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = null;

                $beginshi = "00";
                $beginfen = "00";
                $endshi = "23";
                $endfen = "59";

            } else {
                $market_weike = Db::name('Market')->where(array('id' => $id))->find();
                $auto_trade = Db::name('AutoTrade')->where(['market' => $market_weike['name']])->find();
                $market_weike['auto_volume'] = $auto_trade['volume'];
                $market_weike['auto_min'] = $auto_trade['min'];
                $market_weike['auto_max'] = $auto_trade['max'];
                $market_weike['auto_kaiguan'] = $auto_trade['status'];
                $this->data = $market_weike;

                if ($market_weike['begintrade']) {
                    $beginshi = explode(":", $market_weike['begintrade'])[0];
                    $beginfen = explode(":", $market_weike['begintrade'])[1];
                } else {
                    $beginshi = "00";
                    $beginfen = "00";
                }

                if ($market_weike['endtrade']) {
                    $endshi = explode(":", $market_weike['endtrade'])[0];
                    $endfen = explode(":", $market_weike['endtrade'])[1];
                } else {
                    $endshi = "23";
                    $endfen = "59";
                }
            }
            $this->assign('weike_getCoreConfig', ["港币交易区"]);
            $this->assign('beginshi', $beginshi);
            $this->assign('beginfen', $beginfen);
            $this->assign('endshi', $endshi);
            $this->assign('endfen', $endfen);
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {

            $_POST = input('post.');
            $round = array(0, 1, 2, 3, 4, 5, 6, 7, 8);
            if (!in_array($_POST['round'], $round)) {
                $this->error('小数位数格式错误！');
            }
            //编辑市场
            if (!empty($_POST['id'])) {
                $market = Db::name('Market')->where(['id' => $_POST['id']])->find();
                $new_price = Db::name('TradeLog')->where(['market' => $market['name']])->order('id desc')->value('price');
                if($new_price && $_POST['auto_volume']){
                    $deal_toble =$new_price * $_POST['auto_volume'];
                }else{
                    $deal_toble = 0;
                }
                //判断市场有无委托订单
                $trade = Db::name('Trade')->where(['market' => $market['name'], 'status' => 0])->find();
                if ($trade){
                    $buy_fee = $market['fee_buy'];
                    if ($buy_fee != $_POST['fee_buy']){
                        $this->error($market['name'].'市场有委托订单，请撤销订单后在设置买入手续费');
                    }
                    $sell_fee = $market['fee_sell'];
                    if ($sell_fee != $_POST['fee_sell']){
                        $this->error($market['name'].'市场有委托订单，请撤销订单后在设置卖出手续费');
                    }
                    $invit_1 = $market['invit_1'];
                    if ($invit_1 != $_POST['invit_1']){
                        $this->error($market['name'].'市场有委托订单，请撤销订单后在设置一代赠送比例');
                    }
                    $invit_2 = $market['invit_2'];
                    if ($invit_2 != $_POST['invit_2']){
                        $this->error($market['name'].'市场有委托订单，请撤销订单后在设置二代赠送比例');
                    }
                    $invit_3 = $market['invit_3'];
                    if ($invit_3 != $_POST['invit_3']){
                        $this->error($market['name'].'市场有委托订单，请撤销订单后在设置三代赠送比例');
                    }
                }
                $auto = Db::name('AutoTrade')->where(['market' => $market['name']])->update(['deal_toble' => $deal_toble, 'min' => $_POST['auto_min'], 'max' => $_POST['auto_max'], 'volume' => $_POST['auto_volume'], 'status' => $_POST['auto_kaiguan']]);
                $rs = Db::name('Market')->update(["jiaoyiqu" => $_POST['jiaoyiqu'], "round" => $_POST["round"], "fee_buy" => $_POST["fee_buy"], "fee_sell" => $_POST["fee_sell"],
                    "buy_min" => $_POST["buy_min"], "buy_max" => $_POST["buy_max"], "sell_min" => $_POST["sell_min"], "sell_max" => $_POST["sell_max"],
                    "trade_min" => $_POST["trade_min"], "trade_max" => $_POST["trade_max"], "invit_1" => $_POST["invit_1"], "invit_2" => $_POST["invit_2"],
                    "invit_3" => $_POST["invit_3"], "invit_buy" => $_POST["invit_buy"], "invit_sell" => $_POST["invit_sell"], "weike_faxingjia" => $_POST["weike_faxingjia"],
                    "zhang" => $_POST["zhang"], "die" => $_POST["die"], "hou_price" => $_POST["hou_price"], "change" => $_POST["change"], "begintrade" => $_POST["begintrade"],
                    "endtrade" => $_POST["endtrade"], "trade" => $_POST["trade"], "status" => $_POST["status"], "id" => $_POST["id"]]);
            } else {//添加市场
                $_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($_POST['buyname']);
                unset($_POST['sellname']);

                if (Db::name('Market')->where(array('name' => $_POST['name']))->find()) {
                    $this->error('市场存在！');
                }
                $rs = Db::name('Market')->insert(['name' => $_POST['name'],
                    "jiaoyiqu" => $_POST['jiaoyiqu'], "round" => $_POST["round"], "fee_buy" => $_POST["fee_buy"], "fee_sell" => $_POST["fee_sell"],
                    "buy_min" => $_POST["buy_min"], "buy_max" => $_POST["buy_max"], "sell_min" => $_POST["sell_min"], "sell_max" => $_POST["sell_max"],
                    "trade_min" => $_POST["trade_min"], "trade_max" => $_POST["trade_max"], "invit_1" => $_POST["invit_1"], "invit_2" => $_POST["invit_2"],
                    "invit_3" => $_POST["invit_3"], "invit_buy" => $_POST["invit_buy"], "invit_sell" => $_POST["invit_sell"], "weike_faxingjia" => $_POST["weike_faxingjia"],
                    "zhang" => $_POST["zhang"], "die" => $_POST["die"], "hou_price" => $_POST["hou_price"], "change" => $_POST["change"], "begintrade" => $_POST["begintrade"],
                    "endtrade" => $_POST["endtrade"], "trade" => $_POST["trade"], "status" => $_POST["status"]]);
                $auto = Db::name('AutoTrade')->insert(['market' => $_POST['name'], 'status' => $_POST['auto_kaiguan'], 'min' => $_POST['auto_min'], 'max' => $_POST['auto_max']]);
            }


            if ($rs || $auto || ($rs && $auto)) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    public function marketStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s', 'Market');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        $where['id'] = array('in', $id);

        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2, 'endtime' => time());
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }

                break;

            default:
                $this->error('操作失败！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    public function invit()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $where = array();
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        $list = Db::name('Invit')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['invit'] = Db::name('User')->where(array('id' => $item['invit']))->value('username');
            return $item;
        });
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //marketControl page
    public function marketControl()
    {
        $name = strval(input('name'));
        $field = strval(input('field'));
        $where = array();
        if ($field && $name) {
            $where[$field] = $name;
        }

        $list = Db::name('MarketControl')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $rmb = explode('_', $item['name'])[1];
            if ($rmb === 'btc') {
                $item['unit'] = $rmb;
                $item['api_unit'] = $rmb;
            } else {
                $item['unit'] = $rmb;
                $item['api_unit'] = 'usdt';
            }
            return $item;
        });
        $show = $list->render();


        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //marketControl modify
    public function marketControlEdit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = null;
            } else {
                $market_weike = Db::name('MarketControl')->where(['id' => $id])->find();
                $rmb = explode('_', $market_weike['name'])[1];
                if ($rmb === 'btc') {
                    $market_weike['unit'] = $rmb;
                    $market_weike['api_unit'] = $rmb;
                } else {
                    $market_weike['unit'] = $rmb;
                    $market_weike['api_unit'] = 'usdt';
                }
                $this->data = $market_weike;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');

            //验证接口地址
            if (!empty($_POST['api_url'])) {
                $data = mCurl($_POST['api_url']);
                if ($data['result'] !== 'true' && $data['btc_usdt']['result'] !== 'true') {
                    $this->error('请填写比特尔接口地址！');
                }
            }

            // 非 BOSS 不能操作市场
            if (session('admin_id') != 11) {
                $this->error('非 BOSS 不能操作市场！');
            }

            // 判断 BOSS 密码是否正确
            $password = Db::name('Admin')->where(array('id' => 11))->value('password');
            if (md5($_POST['pass']) != $password) {
                $this->error('BOSS 密码不正确！');
            }
            unset($_POST['pass']);

            if (!empty($_POST['id'])) {
                $rs = Db::name('MarketControl')->update($_POST);
            } else {
                if ($_POST['sellname'] === $_POST['buyname']) {
                    $this->error('市场错误！');
                }

                $_POST['name'] = $_POST['sellname'] . '_' . $_POST['buyname'];
                unset($_POST['buyname']);
                unset($_POST['sellname']);

                if (Db::name('MarketControl')->where(['name' => $_POST['name']])->find()) {
                    $this->error('市场存在！');
                }

                $rs = Db::name('MarketControl')->insert($_POST);
            }

            if ($rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    //marketControl Status
    public function marketControlStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s', 'MarketControl');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        // 非 BOSS 不能操作市场
        if (session('admin_id') != 11) {
            $this->error('非 BOSS 不能操作市场！');
        }

        $where['id'] = array('in', $id);
        switch (strtolower($type)) {
            case 'forbid':
                $data = array('type' => 0);
                break;

            case 'resume':
                $data = array('type' => 1);
                break;

            case 'repeal':
                $data = array('type' => 2);
                break;

            case 'delete':
                $data = array('type' => -1);
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('操作失败！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    //c2c business page
    public function c2c()
    {
        $name = input('name/s');
        $field = input('field/s');
        $status = input('status/d');

        $where = [];
        if ($status) {
            $where['status'] = $status - 1;
        }
        if ($field && $name) {
            if ($field == 'username') {
                $where['name'] = $name;
            } else {
                $where[$field] = $name;
            }
        }

        $list = Db::name('UserC2c')->where($where)->order('sort desc, id desc')->paginate(15);
        $show = $list->render();

        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }

    //c2c business modify
    public function c2cEdit()
    {
        $id = input('id/d');
        if (empty($_POST)) {
            $_POST = input('post.');
            if (empty($id)) {
                $this->data = null;
            } else {
                $market_weike = Db::name('UserC2c')->where(['id' => $id])->find();
                $market_weike['username'] = Db::name('User')->where(['id' => $market_weike['userid']])->value('username');
                $this->data = $market_weike;
            }
            $this->assign('data',$this->data);
            return $this->fetch();
        } else {
            $_POST = input('post.');

            // 非 BOSS 不能操作交易
//            if (session('admin_id') != 11) {
//                $this->error('非 BOSS 不能操作！');
//            }
            if (empty($_POST['name']) || empty($_POST['bankaddr']) || empty($_POST['bankcard'])) {
                $this->error('商户必须绑定银行卡！');
            }

            $_POST['addtime'] = time();
            if (!empty($_POST['id'])) {
                $rs = Db::name('UserC2c')->update($_POST);
            } else {
                $_POST['deal'] = $_POST['num'];
                $rs = Db::name('UserC2c')->insert($_POST);
            }

            if ($rs) {
                $this->success('操作成功!');
            } else {
                $this->error('操作失败!');
            }
        }
    }

    //c2c business Status
    public function c2cStatus()
    {
        $id = input('id/a');
        $type = input('param.type/s');
        $moble = input('moble/s', 'UserC2c');
        if (empty($id)) {
            $this->error('参数错误！');
        }

        if (empty($type)) {
            $this->error('参数错误1！');
        }

        if (is_string($id) && strpos(',', $id)) {
            $id = implode(',', $id);
        }

        // 非 BOSS 不能操作交易
//        if (session('admin_id') != 11) {
//            $this->error('非 BOSS 不能操作！');
//        }

        $where['id'] = array('in', $id);
        switch (strtolower($type)) {
            case 'forbid':
                $data = array('status' => 0);
                break;

            case 'resume':
                $data = array('status' => 1);
                break;

            case 'repeal':
                $data = array('status' => 2);
                break;

            case 'delete':
                $data = array('status' => -1);
                break;

            case 'del':
                if (Db::name($moble)->where($where)->delete()) {
                    $this->success('操作成功！');
                } else {
                    $this->error('操作失败！');
                }
                break;

            default:
                $this->error('操作失败！');
        }

        if (Db::name($moble)->where($where)->update($data)) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    //c2c trade page
    public function c2cTrade()
    {
        $name = input('name/s');
        $field = input('field/s');
        $type = input('type/d');
        $status = input('status/d');
        $addtime = strtotime(urldecode(input('addtime/s')));
        $endtime = strtotime(urldecode(input('endtime/s')));

        $where = [];
        if ($field && $name) {
            if ($field == 'username') {
                $where['userid'] = Db::name('User')->where(array('username' => $name))->value('id');
            } else {
                $where[$field] = $name;
            }
        }

        if ($type) {
            $where['type'] = $type;
        }
        if ($status) {
            $where['status'] = $status - 1;
        }
        if ($addtime) {
            $where['endtime'] = ['gt', $addtime];
        }
        if ($endtime) {
            $where['endtime'] = ['lt', $endtime];
        }
        if ($addtime && $endtime) {
            $where['endtime'] = ['between', "$addtime,$endtime"];
        }
        $count = Db::name('UserC2cTrade')->where($where)->count();
        $list = Db::name('UserC2cTrade')->where($where)->order('sort desc, id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            $item['username'] = Db::name('User')->where(array('id' => $item['userid']))->value('username');
            $item['truename'] = Db::name('User')->where(array('id' => $item['userid']))->value('truename');
            $user_bank = Db::name('UserBank')->where(array('id' => $item['bankid']))->find();
            $item['bank'] = $user_bank['bank'];
            $item['bank_name'] = $user_bank['bankcard'];
            $item['bid'] = $user_bank['id'];
            return $item;
        });
        $show = $list->render();

        if ($status) {
            $where['status'] = $status - 1;
        }else {
            $where['status'] = 1;
        }
        //
        $weike_buy=0;
        $weike_sell=0;

        if ($type == 1) {
            $weike_buy = Db::name('UserC2cTrade')->where($where)->sum('num');
        }elseif ($type == 2) {
            $where['type'] = 2;
            $weike_sell = Db::name('UserC2cTrade')->where($where)->sum('num');
        }else{
            $where['type'] = 1;
            $weike_buy = Db::name('UserC2cTrade')->where($where)->sum('num');
            $where['type'] = 2;
            $weike_sell = Db::name('UserC2cTrade')->where($where)->sum('num');
        }
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->assign('weike_buy', $weike_buy);
        $this->assign('weike_sell', $weike_sell);
        $this->assign('weike_count', $count);
        return $this->fetch();
    }

    //operate order
    public function c2cOperate()
    {
        $id = input('id/d');
        //判断订单号是否正确
        $c2c_trade = Db::name('UserC2cLog')->where(['id' => $id])->find();
        $buyid = Db::name('UserC2cTrade')->where(['tradeno' => $c2c_trade['buytradeno']])->value('id');
        if ($c2c_trade['order'] == 1){
            if ($c2c_trade['type'] == 1){
                $status = Db::name('UserC2cLog')->where(['id' =>$id])->value('status');
                if ($status != 0 && $status != 3){
                    $this->error('订单已处理，不可以撤销');
                }

                Db::startTrans();
                try {
                    $re = [];
                    $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $c2c_trade['buyid']))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $c2c_trade['buyid']))->find();
                    $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['buytradeno']])->update(['status' => 1, 'endtime' => time()]);
                    $re[] = Db::table('weike_user_c2c_log')->where(['id' => $id])->update(['status' => 1, 'czr' => session('admin_username'), 'endtime' => time()]);
                    $re[] = Db::table('weike_user_coin')->where(['userid' => $c2c_trade['buyid']])->setInc('hkd', $c2c_trade['num']);
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $c2c_trade['buyid']))->find();
                    $finance_hash = md5($c2c_trade['buyid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $c2c_trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                    $re[] = Db::table('weike_finance')->insert(array('userid' => $c2c_trade['buyid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $c2c_trade['num'], 'type' => 1, 'name' => 'c2c', 'nameid' => $buyid, 'remark' => '点对点交易-买入充值', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                    if (check_arr($re)) {
                        Db::commit();
                        $this->success('操作成功！');
                    } else {

                        Db::rollback();
                        $this->error('操作失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('操作失败！');
                }
            }else{
                Db::startTrans();
                try {
                    $re = [];
                    $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['selltradeno']])->update(['status' => 1, 'endtime' => time()]);
                    $re[] = Db::table('weike_user_c2c_log')->where(['id' => $id])->update(['status' => 1, 'czr' => session('admin_username'), 'endtime' => time()]);
                    if (check_arr($re)) {
                        Db::commit();
                        $this->success('操作成功！');
                    } else {
                        Db::rollback();
                        $this->error('操作失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('操作失败！');
                }
            }
        }else{
            $status = Db::name('UserC2cLog')->where(['id' =>$id])->value('status');
            if ($status != 0 && $status != 3){
                $this->error('订单已处理！');
            }
            //查找卖家

            Db::startTrans();
            try {
                $re = [];
                $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $c2c_trade['buyid']))->order('id desc')->find();
                $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $c2c_trade['buyid']))->find();
                $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['buytradeno']])->setInc('deal', $c2c_trade['num']);
                $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['selltradeno']])->setInc('deal', $c2c_trade['num']);
                $sell = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['selltradeno']])->find();
                if ($sell['num'] - $sell['deal'] == 0) {
                    $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['selltradeno']])->update(['status' => 1, 'endtime' => time(), 'czr' => session('admin_username')]);
                } else {
                    if ($sell['num'] - $sell['deal'] > 100 && $sell['num'] - $sell['deal'] > $sell['min_num']) {
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $sell['tradeno']])->update(['businessid' => 0, 'status' => 0]);
                    } else if ($sell['num'] - $sell['deal'] > 100 && $sell['num'] - $sell['deal'] <= $sell['min_num']) {
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $sell['tradeno']])->update(['businessid' => 0, 'status' => 0, 'min_num' => $sell['num'] - $sell['deal']]);
                    } else if ($sell['num'] - $sell['deal'] <= 100 && $sell['num'] - $sell['deal'] <= $sell['min_num']) {
                        $rs[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $sell['tradeno']])->update(['businessid' => 0, 'status' => 0, 'min_num' => $sell['num'] - $sell['deal']]);
                    }
                }
                $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['buytradeno']])->update(['status' => 1, 'endtime' => time(), 'czr' => session('admin_username')]);
                $re[] = Db::table('weike_user_coin')->where(['userid' => $c2c_trade['buyid']])->setInc('hkd', $c2c_trade['num']);
                $re[] = Db::table('weike_user_c2c_log')->where(['selltradeno' => $c2c_trade['selltradeno'], 'buytradeno' => $c2c_trade['buytradeno']])->update(['status' => 1, 'czr' => session('admin_username'), 'endtime' => time()]);
                $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $c2c_trade['buyid']))->find();
                $finance_hash = md5($c2c_trade['buyid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $c2c_trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                if ($finance['mum'] < $finance_num) {
                    $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                } else {
                    $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                }

                $re[] = Db::table('weike_finance')->insert(array('userid' => $c2c_trade['buyid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $c2c_trade['num'], 'type' => 1, 'name' => 'c2c', 'nameid' => $buyid, 'remark' => '点对点交易-买入充值', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                if (check_arr($re)) {
                    Db::commit();
                    $this->success('操作成功！');
                } else {
                    Db::rollback();
                    $this->error('操作失败！');
                }
            }catch (Exception $e){
                Db::rollback();
                exception_log($e,__FUNCTION__);
                $this->error('操作失败！');
            }
        }
    }

    //客服对C2C充值订单备注
    public function c2cbz()
    {
        $id = input('post.id/d');
        $text = input('post.text/s');
        $type = input('type/s');

        if (empty($id)) {
            die(json_encode(array('code' => 401, 'msg' => '请选择要操作的数据!', 'data' => [])));
        }
        if (empty($text)) {
            die(json_encode(array('code' => 402, 'msg' => '请填写备注', 'data' => [])));
        }
        //判断撤销单独订单还是匹配后的订单
        if ($type === 'trade') {
            $data = Db::name('UserC2cTrade')->where(['id' => $id])->find();
            if ($data['businessid'] == 0) {
                $beizhu = Db::name('UserC2cTrade')->where(['id' => $id])->update(['beizhu' => $text]);
                if ($beizhu) {
                    die(json_encode(array('code' => 200, 'msg' => '备注成功', 'data' => [])));
                } else {
                    die(json_encode(array('code' => 403, 'msg' => '备注失败', 'data' => [])));
                }
            } else {
                if ($data['type'] == 1) {
                    $buy = Db::name('UserC2cTrade')->where(['id' => $id])->update(['beizhu' => $text]);
                    $sell = Db::name('UserC2cTrade')
                        ->where(['type' => 2, 'status' => $data['status'], 'bankid' => $data['businessid'], 'num' => $data['num'], 'paytype' => $data['paytype']])
                        ->update(['beizhu' => $text]);
                } else {
                    $sell = Db::name('UserC2cTrade')->where(['id' => $id])->update(['beizhu' => $text]);
                    $buy = Db::name('UserC2cTrade')
                        ->where(['type' => 1, 'status' => $data['status'], 'bankid' => $data['businessid'], 'num' => $data['num'], 'paytype' => $data['paytype']])
                        ->update(['beizhu' => $text]);
                }
                if ($buy && $sell) {
                    die(json_encode(array('code' => 200, 'msg' => '备注成功', 'data' => [])));
                } else {
                    die(json_encode(array('code' => 403, 'msg' => '备注失败', 'data' => [])));
                }
            }
        } else if ($type === 'log') {
            $beizhu = Db::name('UserC2cLog')->where(['id' => $id])->update(['beizhu' => $text]);
            if ($beizhu) {
                die(json_encode(array('code' => 200, 'msg' => '备注成功', 'data' => [])));
            } else {
                die(json_encode(array('code' => 403, 'msg' => '备注失败', 'data' => [])));
            }
        }


    }

    //cancel  order
    public function c2cCancel()
    {
        $id = input('id/d');
        $type = input('type/s');
        if ($type === 'trade') {
            Db::startTrans();
            $c2c_trade = Db::name('UserC2cTrade')->lock(true)->where(['id' => $id])->find();
            if ($c2c_trade['order'] == 1){
                if ($c2c_trade['type'] == 1){
                    $status = Db::name('UserC2cTrade')->where(['id' =>$id])->value('status');
                    if ($status != 0 && $status != 3){
                        Db::rollback();
                        $this->error('订单已处理，不可以撤销');
                    }
                    $c2c_log = Db::name('UserC2cLog')->where(['buytradeno' => $c2c_trade['tradeno']])->find();

                    try {
                        $re = [];
                        $re[] = Db::table('weike_user_c2c')->where(['id' => $c2c_log['sellid']])->setDec('deal', $c2c_trade['num']);
                        $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['tradeno']])->update(['status' => 2]);
                        $re[] = Db::table('weike_user_c2c_log')->where(['buytradeno' => $c2c_trade['tradeno']])->update(['status' => 2]);
                        if (check_arr($re)) {
                            Db::commit();
                            $this->success('撤销成功！');
                        } else {
                            Db::rollback();
                            $this->error('撤销失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('撤销失败！');
                    }
                }else{
                    $status = Db::name('UserC2cTrade')->where(['id' =>$id])->value('status');
                    if ($status != 0 && $status != 3){
                        $this->error('订单已处理，不可以撤销');
                    }
                    $fee = $c2c_trade['num'] * 0.01 < 10 ? 10 :$c2c_trade['num'] * 0.01;
                    $total = $c2c_trade['num'] + $fee;
                    $c2c_log = Db::name('UserC2cLog')->where(['selltradeno' => $c2c_trade['tradeno']])->find();
                    try {
                        $re = [];
                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $c2c_trade['userid']))->order('id desc')->find();
                        $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $c2c_trade['userid']))->find();
                        $re[] = Db::table('weike_user_coin')->where(['userid' => $c2c_trade['userid']])->setInc('hkd', $total);
                        $re[] = Db::table('weike_user_c2c')->where(['id' => $c2c_log['buyid']])->setDec('deal', $c2c_trade['num']);
                        $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['tradeno']])->update(['status' => 2]);
                        $re[] = Db::table('weike_user_c2c_log')->where(['selltradeno' => $c2c_trade['tradeno']])->update(['status' => 2]);
                        $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $c2c_trade['userid']))->find();
                        $finance_hash = md5($c2c_trade['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $c2c_trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }

                        $re[] = Db::table('weike_finance')->insert(array('userid' => $c2c_trade['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $total, 'type' => 2, 'name' => 'c2c', 'nameid' => $id, 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        if (check_arr($re)) {
                            Db::commit();
                            $this->success('撤销成功！');
                        } else {
                            Db::rollback();
                            $this->error('撤销失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('撤销失败！');
                    }
                }
            }else{
                $sell_num = $c2c_trade['num'] - $c2c_trade['deal'];
                if ($c2c_trade['deal'] == 0){
                    $fee = $c2c_trade['num'] * 0.005;
                }else{
                    if ($c2c_trade['num'] * 0.005 <= 5){
                        $fee = 5 - ($c2c_trade['deal'] * 0.005);
                    }else{
                        $fee = ($c2c_trade['num'] * 0.005) - ($c2c_trade['deal'] * 0.005 < 5 ? 5 : $c2c_trade['deal'] * 0.005);
                    }
                }
                $total = $sell_num + $fee;
                if ($c2c_trade['type'] == 2) {
                    $status = Db::name('UserC2cTrade')->where(['id' =>$id])->value('status');
                    if ($status != 0){
                        Db::rollback();
                        $this->error('订单已处理，不可以撤销');
                    }
                    //如果当前是卖单，撤回用户的余额，更改订单状态
                    if ($c2c_trade['status'] == 3) {
                        Db::rollback();
                        $this->error('买家已付款订单无法撤销');
                    }
                    if ($c2c_trade['businessid'] != 0) {
                        Db::rollback();
                        $this->error('订单已成功匹配,不可以撤销');
                    }

                    try {
                        $re = [];
                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $c2c_trade['userid']))->order('id desc')->find();
                        $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $c2c_trade['userid']))->find();
                        $re[] = Db::table('weike_user_coin')->where(['userid' => $c2c_trade['userid']])->setInc('hkd', $total);
                        $re[] = Db::table('weike_user_c2c_trade')->where(['id' => $id])->update(['status' => 2, 'endtime' => time(), 'czr' => session('admin_username')]);
                        $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $c2c_trade['userid']))->find();
                        $finance_hash = md5($c2c_trade['userid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $c2c_trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }

                        $re[] = Db::table('weike_finance')->insert(array('userid' => $c2c_trade['userid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $total, 'type' => 2, 'name' => 'c2c', 'nameid' => $id, 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        if (check_arr($re)) {
                            Db::commit();
                            $this->success('撤销成功！');
                        } else {
                            Db::rollback();
                            $this->error('撤销失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('撤销失败！');
                    }
                } elseif ($c2c_trade['type'] == 1) {
                    $status = Db::name('UserC2cTrade')->where(['id' =>$id])->value('status');
                    if ($status != 0){
                        Db::rollback();
                        $this->error('订单已处理，不可以撤销');
                    }
                    //如果当前是买单，更改订单状态
                    if ($c2c_trade['businessid'] == 0) {
                        $re = Db::name('UserC2cTrade')->where(['id' => $id])->update(['status' => 2, 'endtime' => time(), 'czr' => session('admin_username')]);
                        if ($re) {
                            Db::commit();
                            $this->success('撤销成功！');
                        } else {
                            Db::rollback();
                            $this->error('撤销失败！');
                        }
                    }
                }
            }
        } else if ($type === 'log') {
            Db::startTrans();
            $c2c_trade = Db::name('UserC2cLog')->lock(true)->where(['id' => $id])->find();
            $sell = Db::name('UserC2cTrade')->lock(true)->where(['tradeno' => $c2c_trade['selltradeno']])->find();
            if ($c2c_trade['order'] == 1){
                if ($c2c_trade['type'] == 1){
                    $status = Db::name('UserC2cLog')->where(['id' =>$id])->value('status');
                    if ($status != 0 && $status != 3){
                        Db::rollback();
                        $this->error('订单已处理，不可以撤销');
                    }

                    try {
                        $re = [];
                        $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['buytradeno']])->update(['status' => 2]);
                        $re[] = Db::table('weike_user_c2c_log')->where(['id' => $id])->update(['status' => 2]);
                        $re[] = Db::table('weike_user_c2c')->where(['id' => $c2c_trade['sellid']])->setInc('deal', $c2c_trade['num']);

                        if (check_arr($re)) {
                            Db::commit();
                            $this->success('撤销成功！');
                        } else {
                            Db::rollback();
                            $this->error('撤销失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('撤销失败！');
                    }
                }else{
                    $status = Db::name('UserC2cLog')->where(['id' =>$id])->value('status');
                    if ($status != 0){
                        Db::rollback();
                        $this->error('订单已处理，不可以撤销');
                    }
                    $fee = $c2c_trade['num'] * 0.01 < 10 ? 10 : $c2c_trade['num'] * 0.01;
                    $total = $c2c_trade['num'] + $fee;
                    try {
                        $re = [];
                        $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $c2c_trade['sellid']))->order('id desc')->find();
                        $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $c2c_trade['sellid']))->find();
                        $re[] = Db::table('weike_user_coin')->where(['userid' => $c2c_trade['sellid']])->setInc('hkd', $total);
                        $re[] = Db::table('weike_user_c2c')->where(['id' => $c2c_trade['buyid']])->setInc('deal', $c2c_trade['num']);
                        $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['selltradeno']])->update(['status' => 2, 'endtime' => time()]);
                        $re[] = Db::table('weike_user_c2c_log')->where(['id' => $id])->update(['status' => 2, 'endtime' => time()]);
                        $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $c2c_trade['sellid']))->find();
                        $finance_hash = md5($c2c_trade['sellid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $c2c_trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                        $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                        if ($finance['mum'] < $finance_num) {
                            $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                        } else {
                            $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                        }

                        $re[] = Db::table('weike_finance')->insert(array('userid' => $c2c_trade['sellid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $total, 'type' => 2, 'name' => 'c2c', 'nameid' => $sell['id'], 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));
                        if (check_arr($re)) {
                            Db::commit();
                            $this->success('撤销成功！');
                        } else {
                            Db::rollback();
                            $this->error('撤销失败！');
                        }
                    }catch (Exception $e){
                        Db::rollback();
                        exception_log($e,__FUNCTION__);
                        $this->error('撤销失败！');
                    }
                }
            }else{
                $status = Db::name('UserC2cLog')->where(['id' => $id])->value('status');
                if ($status != 0 && $status != 3){
                    Db::rollback();
                    $this->error('订单已处理，不可以撤销');
                }
                $sell_num = $sell['num'] - $sell['deal'];
                if ($sell['deal'] == 0){
                    $fee = $sell['num'] * 0.005;
                }else{
                    if ($sell['num'] * 0.005 <= 5){
                        $fee = 5 - ($sell['deal'] * 0.005);
                    }else{
                        $fee = $sell['num'] * 0.005 - ($sell['deal'] * 0.005 < 5 ? 5 :  $sell['deal'] * 0.005);
                    }
                }
                $total = $sell_num + $fee;
                try {
                    $re = [];
                    $finance = Db::table('weike_finance')->lock(true)->where(array('userid' => $c2c_trade['sellid']))->order('id desc')->find();
                    $finance_num_user_coin = Db::table('weike_user_coin')->lock(true)->where(array('userid' => $c2c_trade['sellid']))->find();
                    $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['buytradeno']])->update(['status' => 2, 'endtime' => time(), 'czr' => session('admin_username')]);
                    $re[] = Db::table('weike_user_c2c_trade')->where(['tradeno' => $c2c_trade['selltradeno']])->update(['status' => 2, 'endtime' => time(), 'czr' => session('admin_username')]);
                    $re[] = Db::table('weike_user_coin')->where(['userid' => $c2c_trade['sellid']])->setInc('hkd', $total);
                    $re[] = Db::table('weike_user_c2c_log')->where(['id' => $id])->update(['status' => 2, 'czr' => session('admin_username')]);
                    $finance_mum_user_coin = Db::table('weike_user_coin')->where(array('userid' => $c2c_trade['sellid']))->find();
                    $finance_hash = md5($c2c_trade['sellid'] . $finance_num_user_coin['hkd'] . $finance_num_user_coin['hkdd'] . $c2c_trade['num'] . $finance_mum_user_coin['hkd'] . $finance_mum_user_coin['hkdd'] . MSCODE . 'auth.weike.com');
                    $finance_num = $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'];

                    if ($finance['mum'] < $finance_num) {
                        $finance_status = (1 < ($finance_num - $finance['mum']) ? 0 : 1);
                    } else {
                        $finance_status = (1 < ($finance['mum'] - $finance_num) ? 0 : 1);
                    }

                    $re[] = Db::table('weike_finance')->insert(array('userid' => $c2c_trade['sellid'], 'coinname' => 'hkd', 'num_a' => $finance_num_user_coin['hkd'], 'num_b' => $finance_num_user_coin['hkdd'], 'num' => $finance_num_user_coin['hkd'] + $finance_num_user_coin['hkdd'], 'fee' => $total, 'type' => 2, 'name' => 'c2c', 'nameid' => $sell['id'], 'remark' => '点对点交易-卖出撤销', 'mum_a' => $finance_mum_user_coin['hkd'], 'mum_b' => $finance_mum_user_coin['hkdd'], 'mum' => $finance_mum_user_coin['hkd'] + $finance_mum_user_coin['hkdd'], 'move' => $finance_hash, 'addtime' => time(), 'status' => $finance_status));

                    if (check_arr($re)) {
                        Db::commit();
                        $this->success('撤销成功！');
                    } else {
                        Db::rollback();
                        $this->error('撤销失败！');
                    }
                }catch (Exception $e){
                    Db::rollback();
                    exception_log($e,__FUNCTION__);
                    $this->error('撤销失败！');
                }
            }

        }
    }

    //c2c成交记录
    public function c2clog()
    {
        $name = input('name/s');
        $field = input('field/s');
        $order = input('order/d');
        $status = input('status/s');
        $addtime = strtotime(urldecode(input('addtime/s')));
        $endtime = strtotime(urldecode(input('endtime/s')));
        $where = [];
        if ($order != null){
            if ($order == 1){
                if ($field && $name) {
                    if ($field == 'buyuser') {
                        $where['buyid'] = Db::name('User')->where(array('username' => $name))->value('id');
                    } else if ($field == 'selluser') {
                        $where['sellid'] = Db::name('User')->where(array('username' => $name))->value('id');
                    } else {
                        $where[$field] = $name;
                    }
                }
                $where['order'] = $order - 1;
            }else{
                if ($field && $name) {
                    if ($field == 'buyuser') {
                        $where['buyid'] = Db::name('UserC2c')->where(array('moble' => $name))->value('id');
                    } else if ($field == 'selluser') {
                        $where['sellid'] = Db::name('UserC2c')->where(array('moble' => $name))->value('id');
                    } else {
                        $where[$field] = $name;
                    }
                }
                $where['order'] = $order - 1;
            }
        }else{
            if ($field && $name) {
                if ($field == 'buyuser') {
                    $where['buyid'] = Db::name('User')->where(array('username' => $name))->value('id');
                } else if ($field == 'selluser') {
                    $where['sellid'] = Db::name('User')->where(array('username' => $name))->value('id');
                } else {
                    $where[$field] = $name;
                }
            }
        }


        if ($status) {
            $where['status'] = $status;
        }

        if ($status == 0 && $status != null) {
            $where['status'] = 0;
        }
        if ($addtime) {
            $where['endtime'] = ['gt', $addtime];
        }
        if ($endtime) {
            $where['endtime'] = ['lt', $endtime];
        }
        if ($addtime && $endtime) {
            $where['endtime'] = ['between', "$addtime,$endtime"];
        }

        $count = Db::name('UserC2cLog')->where($where)->count();

        $list = Db::name('UserC2cLog')->where($where)->order('id desc')->paginate(15,false,['query'=>request()->param()])->each(function($item, $key){
            if ($item['order'] == 0){
                $item['buyuser'] = Db::name('User')->where(array('id' => $item['buyid']))->value('username');
                $item['selluser'] = Db::name('User')->where(array('id' => $item['sellid']))->value('username');
            }else{
                if ($item['type'] == 1){
                    $item['buyuser'] = Db::name('User')->where(array('id' => $item['buyid']))->value('username');
                    $item['selluser'] = Db::name('UserC2c')->where(array('id' => $item['sellid']))->value('moble');
                }else{
                    $item['buyuser'] = Db::name('UserC2c')->where(array('id' => $item['buyid']))->value('moble');
                    $item['selluser'] = Db::name('User')->where(array('id' => $item['sellid']))->value('username');
                }
            }
            return $item;
        });
        $show = $list->render();


        if ($status){
            $where['status'] = $status;
        }else {
            $where['status'] = 1;
        }
        $weike_num = Db::name('UserC2cLog')->where($where)->sum('num');
        //$weike_fee = Db::name('UserC2cLog')->where([$where,'order' => 0])->sum('num') * 0.005 + Db::name('UserC2cLog')->where([$where,'type' => 2,'order' => 1])->sum('num') * 0.01;
        $weike_fee = Db::name('UserC2cLog')->where(array_merge($where,['order'=>0]))->sum('num') * 0.005 + Db::name('UserC2cLog')->where(array_merge($where,['type' => 2],['order' => 1]))->sum('num') * 0.01;
        $this->assign('weike_count', $count);
        $this->assign('weike_num', $weike_num);
        $this->assign('weike_fee', $weike_fee);
        $this->assign('list', $list);
        $this->assign('page', $show);
        return $this->fetch();
    }
}

?>