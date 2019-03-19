/* old js */
// import navTrade from 'components/nav-trade';
import nav from 'nComponents/nav';
// import register from 'components/register';
import HoverWin from 'components/hoverWin';
// import ResetPwd from 'components/resetPassword';
import getLanguagePack from 'components/tradeLanguagePack';

/* eslint-disable */
// import 'styles/common/navDefault.scss';
// import 'flex.css';
import 'flex.css';
import 'nStyle/init.scss';
import 'nStyle/theme.scss';
// import 'flex.css/dist/data-flex.css';
import 'nStyle/trade/trade.scss';

import Vue from 'vue';
import FastClick from 'fastclick';
import is from 'tools/is';
import https from 'plugins/http';
import filters from 'filters';
import directives from 'directives';
import Alert from 'nComponents/Alert';
import scrollTable from 'nComponents/scrollTable';
import ElCollapse from 'nComponents/ElCollapse';
import register from 'nComponents/register';
import publicFn from 'plugins/publicKey';
import { numUseNoDot, toFixedNum } from 'filters/filters';
import _ from 'lodash';
import math from 'mathjs';
import talking from 'tools/talking/talking';
import sock from 'tools/sock';
import cookie from 'tools/cookie';
import eNumToStri from 'tools/stringNum';
import eNumToStrNum from 'tools/eNumToStrNum';
import isMob from '@/utils/isMob';
import tradingView from 'nComponents/tradingView';

function forcVerShow() {
  if (isMob()) {
    let body = document.documentElement || document.body;
    let minWidth = body.clientWidth;
    let minHeight = body.clientHeight;
    // 竖屏，翻转
    if (minWidth < minHeight) {
      document.getElementsByClassName('trade_content_top')[0].style.height = "100%";;
    }
  }
}
window.onresize = function() {
  forcVerShow();
}
forcVerShow();

// 32672845.46350198

// 包含 **** 表示需要优化的地方
window.TradingView.onready(function() {


  // ios 加速
  FastClick.attach(document.body);
  // set reurl
  cookie.setReUrl();

  // 导航
  const navVue = nav();
  //
  math.config({
    // Default type of number:
    number: 'BigNumber',
    // Number of significant digits for BigNumbers
    precision: 64
  });

  // 乘法计算
  function multiply(numA, numB) {
    if (numA === null || numA === undefined) {
      return 0;
    }
    if (numB === null || numB === undefined) {
      return 0;
    }
    return math.format(math.multiply(numA, numB), 10);
    // return math.format(math.multiply(math.bignumber(numA), math.bignumber(numB)), 10);
  }

  // 加法
  function accurateAdd(numA, numB) {
    if (numA === null || numA === undefined) {
      return 0;
    }
    if (numB === null || numB === undefined) {
      return 0;
    }
    return math.format(math.add(numA, numB));
  }
  // window.dda = accurateAdd;
  // window.eNum = eNumToStrNum;
  function noIntENum(num) {
    let result = null;
    if (is(num, 'Number')) {
      let nStr = num + '';
      result = parseFloat(nStr);
    } else if (is(num, 'String')) {
      result = parseFloat(num);
    }
    return result + '';
  }


  let talkWs = talking();

  // 获取语言包
  let phpLang = getLanguagePack();
  // {
  //   CANCEL_FIAL: "撤單未成功",
  //   NET_ERROR: "網絡繁忙",
  //   TRUST_STATUS_ALL: "全部成交",
  //   TRUST_STATUS_PART: "部分成交",
  //   TRUST_STATUS_NO: "未成交",
  //   TRUST_STATUS_CANCEL: "已撤銷",
  //   TRUST_STATUS_FIND: "查詢中",
  //   INPUT_NEED_VALID: "請輸入數字,小數點後最多8位",
  //   FLOATNUM_A: "價格必須大於$min,小於$max,最多",
  //   FLOATNUM_B: "最多$float位小數",
  //   MAX_TRADE_NUM: "最大交易數量為:",
  //   MIN_TRADE_NUM: "最小交易數量為:",
  //   AMOUNT_MAX: "最多",
  //   TRADE_BUY_IN: "買入",
  //   TRADE_SELL_OUT: "賣出",
  //   TRADE_CANCEL: "撤銷",
  //   OPEN_SOON: "即將開放",
  //   LOADING: "加载中...",
  //   TRADE_AREA_TITLE: "交易區",
  //   PRICE_HOLDE: "請輸入價格",
  //   NUMBER_HOLDE: "請輸入數量",
  //   TRADE_MIN: "交易數量不能小於",
  //   TRADE_MAX: "交易數量不能大於",
  //   BUY_OVER: "超出可買入額，請檢查後重新輸入",
  //   SELL_OVER: "超出可賣出額，請檢查後重新輸入"
  // };

  // 人民币悬浮框
  const myHover = new HoverWin($('[data-tormb]'), 'tormb');
  myHover.loadingTips = phpLang.LOADING;

  let canHoverRMB = true;
  //调用悬浮框
  function addHoverWin($elem, attr, direction) {
    $($elem).off('mouseenter');
    $($elem).mouseenter(function(e) {
      if (!canHoverRMB) return;
      if (e.stopPropagation) e.stopPropagation();
      myHover.setHover(this, attr, direction);
    });
    $($elem).mouseleave(function() {
      if (!canHoverRMB) return;
      myHover.hide();
    });
  }

  // websocket 链接 url
  let wsUrl = document.getElementById('wsurl').value;

  // 注册插件
  Vue.use(publicFn);
  Vue.use(https);
  const tradeVue = new Vue({
    data: {
      // 弹窗开关
      alertShow: false,
      // 弹窗回掉类型
      alertType: 0,
      // 980屏幕 切换k线图
      klineShow: true,
      buySaleShow: true,
      // 是否允许轮询 获取数据
      canTimeout: false,
      // socket 链接次数
      linkSockTime: 0,
      // 当前交易场 全名 ltc_btc
      curArea: '',
      // 即将开发 交易场
      newArea: '',
      areaSearch: '',
      // 所有交易场
      areas: {},
      hasArea: false,
      // 轮询 时长
      delayT: 2000,
      // 当前币种 名字
      coinFrom: '',
      // 交易区 名字
      coinTo: '',
      // 创新区 交易对
      realArea: '',
      // 昨天收盘价格
      yestodayPrice: '--',
      // 交易区显示状态
      areaShow: false,
      areaTouchShow: false,
      // 我的 自选 币种列表
      mySelCoinList: [],
      // 行情排序 规则
      areaSortRule: {
        type: 'money',
        dir: -1
      },
      /*
      'i'=>$trust['id'],
      'n'=>$trust['number'],
      'd'=>$trust['numberdeal'],
      'o'=>$trust['numberover'],
      'p'=>$trust['price'],
      's'=>$trust['status'],
      'f'=>$trust['flag'],
      'cf'=>$trust['coin_from'],
      'ct'=>$trust['coin_to'],
      't'=>date('Y-m-d H:i:s', $trust['created']),
      */
      // 我的委托当前页
      mytrustPage: 1,
      // 我的委托
      mytrust: {
        list: [],
        totalPage: 0
      },
      // 成交 记录
      orders: {
        d: [],
        price: '--',
        max: '--',
        min: '--',
        money: '--',
        ratio: '--',
        sum: '--'
      },
      // 买 卖 价格
      trust: {
        buy: [],
        sale: []
      },
      // btc人民币价格
      rmbPrice: 0,
      // btc（交易区 单位币种余额） 可用余额 （btc） _ 为了 根后端字段名 统一
      area_over: '--',
      area_lock: '--',
      // 当前币 可用余额
      coin_over: '--',
      coin_lock: '--',
      // 最新價格人民幣
      newPriceRMB: '--',
      // sell窗口最多显示条数
      sellMaxNum: 0,
      // 买入表单
      buyForm: {
        price: '',
        number: '',
        type: 'in',
        coin_from: '',
        coin_to: '',
        pwdtrade: '',
        reqToken: ''
      },
      // 买入 规则
      buyFormRule: {
        // 价格小数 位数
        pfloat: 8,
        // 数量小数 位数
        nfloat: 8,
        // 交易数量 最大值
        max: '',
        // 交易数量 小於最小值提示
        min: ''
      },
      // 卖出表单
      sellForm: {
        price: '',
        number: '',
        type: 'out',
        coin_from: '',
        pwdtrade: '',
        coin_to: '',
        reqToken: ''
      },
      // 卖出 规则
      sellFormRule: {
        // 价格小数 位数
        pfloat: 8,
        // 数量小数 位数
        nfloat: 8,
        // 交易数量 最大值
        max: '',
        // 交易数量 小於最小值提示
        min: ''
      },

      // 表单校验 提示 框 开关
      buyTips: false,
      // 0 红色 失败； 1绿色，成功
      buyTipsTheme: 0,
      sellTips: false,
      // 0 红色 失败； 1绿色，成功
      sellTipsTheme: 0,
      // 滑杆
      buyRang: 0,
      sellRang: 0,
      // 滑竿长度
      trackWidth: 0,
      // 鼠标状态 up or down
      mouseStatu: 'up',
      // 鼠标所在当前位置
      mouseStartX: 0,
      // 滑竿左边边缘位置
      edgeLeft: 0,
      edgeRight: 0,
      // 交易密码
      pwdTrade: '',
      // 下单成功状态
      buyOrderOk: false,
      sellOrderOk: false,
      // 当前交易类型 buy or sell
      orderType: '',
      // 设置交易密码 开关
      setPwd: false,
      // 交易密码 提示内容
      pwdTips: '',
      // 提示忘記交易密碼
      forgetPwd: false,
      // 交易密碼提交狀態
      pwdSubmitting: false,
      // 提交状态
      submitting: false,
      buySubmitting: false,
      sellSubmitting: false,
      // 正在撤销 列表
      cancellingQueue: [],
      // 登录状态
      isLogin: true,
      // 手机端 未登录显示 只有币列表
      isMobUnlogin: false,
      // 750屏幕 切換买卖 表单
      buyFormStatus: true,
      sellFormStatus: true,
      // 750屏幕显示委托详情
      isDealTrust: false,
      // scroll top
      scrollTop: 0,
      // 正在处理 我的委托
      deTrust: {},
      // 判断移动端是 click  or  move
      isTchClick: true,
      // 免手续费 剩余时间
      noFeeAllTime: 0,
      noFee: {
        day: 0,
        h: 0,
        min: 0
      }
    },
    created() {
      // this.$getPublicKey();
    },
    updated() {
      if (canHoverRMB) {
        // 示例 擁有 data-tormb 屬性的元素，2. 需要顯示的 內容在data- 的 tormb 的值中 3. 顯示方向 在 (可以選擇 "left", "top", "right", "bottom" 四個方向 默認上方)
        addHoverWin($('#day-volumn'), 'tormb', 'bottom');
        addHoverWin('.amount-box [data-tormb]', 'tormb', 'top');

        addHoverWin($('#newestPrice'), 'tormb', 'bottom');
        addHoverWin($('#tradeSum'), 'tormb', 'bottom');
        // 买入
        addHoverWin($('#buyin-list li'), 'tormb', 'left');
        // 卖出
        addHoverWin($('#sellout-list li'), 'tormb', 'left');
        // 成交
        addHoverWin($('#deal-list li'), 'tormb', 'left');
        // mytrust 我的委托
        addHoverWin($('#mytrust-list tr'), 'tormb', 'top');
        // 买卖
        addHoverWin($('div[id^="form"] [data-tormb]'), 'tormb', 'top');
      }
    },
    mounted() {
      //jsj 测试 alert('11111');
      // console.log('202222');

      this.bodyClick();
      let coinFrom = document.getElementById("coin_from").innerHTML;
      let coinTo = document.getElementById("coin_to").innerHTML;
      let symbolType = 'mcc_btc';
      if (coinFrom && coinTo) {
        symbolType = `${coinFrom}_${this.realArea}`;
      }
      tradingView(symbolType);
      // 鼠标释放 时，关闭滑竿 计算
      document.body.onmouseup = () => {
        this.mouseStartX = 0;
        this.mouseStatu = '';
      };
      document.body.onmousemove = (e) => {
        this.moveDot(e, this.mouseStatu);
      };
      document.body.ontouchend = () => {
        this.mouseStartX = 0;
        this.mouseStatu = '';
      };
      document.body.ontouchmove = (e) => {
        this.moveDot(e, this.mouseStatu);
      };
      // 获取滑竿 width
      let style = window.getComputedStyle(this.$refs.track, null);
      this.trackWidth = parseInt(style.width);

      // 獲取 當前 幣信息
      let token = document.getElementById('hahaha').innerHTML;
      // $("#hahaha").html().trim();
      //
      this.buyForm.coin_from = this.coinFrom;
      this.buyForm.coin_to = this.realArea;
      this.buyForm.reqToken = token;
      this.sellForm.coin_from = this.coinFrom;
      this.sellForm.coin_to = this.realArea;
      this.sellForm.reqToken = token;
      // 建立长连接 同时 判断是否需要轮询
      this.socketTrade();
      // 我的自选列表
      this.getMySelected();
      // 获取 交易区数据
      this.getAreaData();
      // 获取 交易中心列表数据
      this.tradeDataFnV2();
      // 获取用户信息
      this.getUserInfo();
      this.getRmbPrice();
      // 交易区 表头样式
      // this.resetTHead();

      // 读取本地缓存 自选 记录
      let mySel = sessionStorage.getItem('mySelCoinList');
      if (mySel) {
        this.mySelCoinList = mySel.split(',');
      }

      // klineDom
      let winWidth = document.body.clientWidth;
      if (winWidth > 1199) {
        canHoverRMB = true;
        this.klineShow = true;
        this.buySaleShow = true;
      } else {
        canHoverRMB = false;
        this.klineShow = false;
        this.buySaleShow = true;
        //
        if (winWidth < 980) {
          this.buyFormStatus = true;
          this.sellFormStatus = false;
        } else {
          this.buyFormStatus = true;
          this.sellFormStatus = true;
        }
      }
    },
    methods: {
      bodyClick() {
        let t = this;
        document.getElementById('body_all').addEventListener('click', function(e) {
          t.areaShow = false;
        }, false);
      },
      reload() {
        // 获取 交易区数据
        this.getAreaData();
        // 获取 交易中心列表数据
        this.tradeDataFnV2();
        // 获取用户信息
        this.getUserInfo();
      },
      setCoinFromTo(from, to, coin_to) {
        this.coinFrom = from;
        this.coinTo = to;
        this.realArea = coin_to;
      },
      // 数据初始化
      initData() {
        // 获取 交易区数据
        this.getAreaData();
        // 获取 交易中心列表数据
        this.tradeDataFnV2();
        // 获取用户信息
        this.getUserInfo();
        // this.getRmbPrice();
      },
      // 弹窗回掉
      alertCB() {
        switch (this.alertType) {
          case 1:
            break;
          default:
        }
      },
      // login
      login() {
        register.loginAlert();
      },
      register() {
        register.registerAlert();
      },
      // 获取币 价格
      getRmbPrice() {
        this.$http.get(`/ajax_market/coinPrice?coin=${this.realArea}`)
        .then(({ status, data }) => {
          //
          if (parseInt(status) === 1) {
            this.rmbPrice = data;
            this.$forceUpdate();
          }
          setTimeout(() => {
            this.getRmbPrice();
          }, this.delayT);
        })
        .catch(() => {
          if (this.canTimeout) {
            setTimeout(() => {
              this.getRmbPrice();
            }, this.delayT);
          }
        });
      },
      // computed price
      compPrice(coin) {
        let price = '--';
        if (this.rmbPrice && coin - 0 > 0) {
          price = toFixedNum((coin * this.rmbPrice), 2);
        }
        return price;
      },
      // 获取 后台配置规则
      getPhpRule(json, type) {
        // 解构赋值
        (this[`${type}FormRule`] = json);
      },
      // 获取用户信息
      getUserInfo() {
        this.$http.get(`/ajax_user/getUserInfo?coin=${this.coinFrom}_${this.realArea}`)
        .then(({ status, data }) => {
          // 如果后台 返回正常json 正常处理
          if (status != undefined) {
            let intStatus = parseInt(status);
            // status 一直是1 data 可能是null（未登录）
            if (intStatus === 1 && data) {
              this.area_over = data[`${this.buyForm.coin_to}_over`];
              this.area_lock = data[`${this.buyForm.coin_to}_lock`];
              this.coin_over = data[`${this.coinFrom}_over`];
              this.coin_lock = data[`${this.coinFrom}_lock`];
            }
            // 是否登录
            if (data) {
              this.isLogin = true;
              if (data.realInfo === 0) {
                $('.talking_up').hide();
                $('.talking_down').hide();
              }
            } else {
              this.isLogin = false;
            }
          }
          // 轮询
          if (this.canTimeout) {
            setTimeout(() => {
              this.getUserInfo();
            }, this.delayT);
          }
        })
        .catch(() => {
          this.getUserInfo();
        });
      },
      // 处理  交易  键值对  coin_market
      getCoinMarket(str) {
        let result = null;
        if (str) {
          let keyVal = str.split("_");
          result = { name: keyVal[0], market: keyVal[1], key: str };
        }
        return result;
      },
      // 我的自选列表
      getMySelected() {
        this.$http.post('/ajax_user/selected')
          .then(({ status, data, msg }) => {
            if (parseInt(status) === 1) {
              this.mySelCoinList = data.split(',');
              // 更新 自选列表
              sessionStorage.setItem('mySelCoinList', this.mySelCoinList.join(','));
            }
          })
          .catch(err => {
            console.log(err);
          });
      },
      // 添加 or 取消 自选
      setSelected(coin, area) {
        let type = null;
        let keyVal = this.getCoinMarket(coin.coin);
        // 取消 选择
        if (coin.selected === 1) {
          type = 'del';
          coin.selected = 0;
          // 删除 对应的 自选币
          _.remove(this.mySelCoinList, key => (key === coin.coin));
        }
        // 添加 自选
        else {
          type = 'add';
          // 更新 币的 自选状态
          for (let key = 0,len = this.areas[area].length; key < len; key++) {
            if (this.areas[area][key].coin === keyVal.key) {
              this.areas[area][key].selected = 1;
              break;
            }
          }
          // 更新 自选 数据
          this.mySelCoinList.push(keyVal.key);
        }
        sessionStorage.setItem('mySelCoinList', this.mySelCoinList.join(','));

        // 服务器缓存记录
        this.$http.post('/ajax_user/setSelected', { coins: keyVal.key, type})
          .then(({ status, data }) => {

          })
          .catch(err => {
            console.log(err);
          });
      },
      // 初始化 自选列表 添加状态
      initMySel(data) {
        let result = _.forEach(data, (area) => {
          _.forEach(area, coin => {
            if (this.areaSearch) {
              let coinName = coin.coin.replace(/(_[a-zA-Z]+)$/g, '');
              if (coinName.indexOf(this.areaSearch) > -1) {
                coin.search = 1;
              } else {
                coin.search = 0;
              }
            } else {
              coin.search = 1;
            }
            // 查询 自选列表 是否 包含 此 币种
            if (this.mySelCoinList.length > 0 && _.indexOf(this.mySelCoinList, coin.coin) > -1) {
              coin.selected = 1;
            } else {
              coin.selected = 0;
            }
          });
        });
        return result;
      },
      // 行情 排序
      sortMarket(type) {
        // -1 从大到小； 1 从小到大
        let dir = -1;
        //
        if (type) {
          // 点击了 相同的排序类型 切换 方向
          if (type === this.areaSortRule.type) {
            dir = -1 * this.areaSortRule.dir;
          }
          // 切换 不同的排序类型
          else {
            this.areaSortRule.type = type;
          }
          this.areaSortRule.dir = dir;
        } else {
          type = this.areaSortRule.type;
          dir = this.areaSortRule.dir;
        }
        // 排序
        _.forEach(this.areas, (area, key) => {
          this.areas[key] = _.sortBy(area, coin => {
            if (type != 'coin') {
              return dir * coin[type];
            } else {
              return coin[type];
            }
          });
        });
      },
      // 切换 交易场
      changeArea(areaName) {
        this.curArea = areaName;
        this.areaSearch = '';
      },
      // 切换币
      changeCoin(coin) {
        window.location.href = `/trade/${coin}`;
      },
      tipsLogin() {
        /* eslint-disable */
        this.login();
        /* eslint-enable */
      },
      // 获取 交易场 数据
      getAreaData() {
        this.$http.get('/ajax_market/getAllQuoteV2')
          .then(({ status, data }) => {
            let intStatus = parseInt(status);
            if (intStatus === 1) {
              let newData = this.initMySel(data);
              this.hasArea = true;
              // 释放资源
              this.areas = null;
              this.areas = Object.assign({}, data);
              this.sortMarket();
            }
            if (this.canTimeout) {
              setTimeout(() => {
                this.getAreaData();
              }, this.delayT);
            }
          })
          .catch(() => {
            setTimeout(() => {
              this.getAreaData();
            }, this.delayT);
          });
      },
      // 重置
      resetAreaSearch() {
        if (this.curArea != 'my') {
          this.areas[this.curArea].forEach(coin => (coin.search = 1));
        }
        //
        else {
          _.forEach(this.areas, area => {
            area.forEach(coin => (coin.search = 1));
          });
        }
      },
      // 行情 搜索
      searchingArea() {
        let keyword = this.areaSearch;
        if (keyword) keyword = keyword.toLocaleLowerCase();
        if (this.curArea != 'my') {
          this.areas[this.curArea].forEach(coin => {
            let coinName = coin.coin.replace(/(_[a-zA-Z]+)$/g, '');
            if (coinName.indexOf(keyword) > -1) {
              coin.search = 1;
            } else {
              coin.search = 0;
            }
          });
        } else {
          _.forEach(this.areas, area => {
            area.forEach(coin => {
              let coinName = coin.coin.replace(/(_[a-zA-Z]+)$/g, '');
              if (coinName.indexOf(keyword) > -1) {
                coin.search = 1;
              } else {
                coin.search = 0;
              }
            });
          });
        }
      },

      // 重置 表头 样式
      resetTHead() {
        let tbodys = this.$refs.element;
        let thead = this.$refs.thead;
        const setThead = (thead, tbodys) => {
          // return;
          let theadTds = '';
          if (this.curArea == 'usdt') return;
          // 表头
          if (thead) {
            theadTds = thead.children;
          }
          // 表格
          if (tbodys) {
            let firstTr = tbodys[0].firstChild;
            let tds = [...firstTr.children];
            if (theadTds) {
              tds.forEach((td, key) => {
                let tdStyle = window.getComputedStyle(td);
                if (tdStyle.width != 'auto') {
                  theadTds[key].style.minWidth = tdStyle.width;
                }
              });
            }
          }
        };
        //
        setThead(thead, tbodys);
        //
        tbodys = this.$refs.element750;
        thead = this.$refs.thead750;
        setThead(thead, tbodys);
        //
        tbodys = this.$refs.elementUnLogin750;
        thead = this.$refs.theadUnLogin750;
        setThead(thead, tbodys);

      },
      // 我的委托 分页
      loadMoreMytrust() {
        this.mytrustPage++;
        this.$http.get(`/ajax_trade/getMyTrustV2?coin=${this.coinFrom}_${this.coinTo}&page=${this.mytrustPage}`)
          .then(({ data }) => {
            if (data && data.list) {
              this.mytrust.list = [...this.mytrust.list, ...data.list];
            }
          });
      },
      // mytrust(我的委托): content 更新数据;  ation(类型)：add(新增) ,  update(更新)
      mytrustUpdate(mytrust) {
        if (mytrust) {
          this.mytrust.list = [...mytrust];
        }
      },
      // 更新成交单 数据
      ordersUpdate(orders) {
        // 成交单： 最新价格， 24小时成交量
        if (orders) {
          this.orders = Object.assign({}, orders);
        }
      },
      // 更新委托单 数据
      trustUpdate(trust) {
        // return;
        // 委托单
        if (trust) {
          this.trust = Object.assign({}, trust);
          // if (this.trust.sale.length > 11) {
          //   this.trust.sale = this.trust.sale.slice(-11);
          // }
        }
      },

      // 买卖 我的委托 表格 新接口
      tradeDataFnV2() {
        this.$http.get(`/ajax_trade/tradeDataV2?coin=${this.coinFrom}_${this.realArea}`)
          .then(({ status, data }) => {
            let intStatus = parseInt(status);
            if (intStatus === 1 && data) {
              // this.tradeData = Object.assign({}, data);
              let { mytrust, orders, trust } = data;
              // 我的委托 数组
              if (mytrust && mytrust.list && is(mytrust.list, 'Array')) {

                this.mytrust.list = [...mytrust.list];
                this.mytrust.totalPage = mytrust.totalPage;
                // 重置表頭
                // this.$refs.mytrust.getTbodyTds();
              }
              // 成交单： 最新价格， 24小时成交量
              if (orders) {
                this.orders = Object.assign({}, orders);

                // (昨天收盘价格) =
                // price / (1 - （正跌幅 / 100) )
                this.yestodayPrice = this.orders.prevClose;
              }
              // 委托单
              if (trust) {
                this.trust = Object.assign({}, trust);
              }
            }
            // 计算最大显示条数
            if (this.sellMaxNum === 0) {
              this.compBuySellWin();
            }
            if (this.canTimeout) {
              // 轮询
              setTimeout(() => {
                this.tradeDataFnV2();
              }, this.delayT);
            }
          })
          .catch((err) => {
            if (err) console.log(err);
            this.tradeDataFnV2();
          });
      },
      // t： type(操作哪个版面 trust, orders, mytrust)； c： content(数据主体 data); a: action（数据更新类型）
      dealSocketData({ t, c, a }) {
        let type = t;
        let content = c;
        let action = a;
        switch (type) {
          // mytrust(我的委托): content 更新数据;  ation(类型)：add(新增) ,  update(更新)
          case 'mytrust': {
            //
            switch (action) {
              case 'add':
                this.mytrust.list.unshift(content);
                break;
              case 'update': {
                // let id = content.i;
                this.mytrust.list.forEach((trust, key) => {
                  // 找到对应 的单 (id唯一标识)
                  if (trust.i == content.i) {
                    // update 指定 key 的值
                    Object.keys(content).forEach((name, val) => {
                      // id 不需要更新
                      if (name != 'i') {
                        this.mytrust.list[key][name] = content[name];
                      }
                    });
                  }
                });
                break;
              }
              default:
            }
            // 重新排序
            this.mytrust.list = _.sortBy(this.mytrust.list, (key) => (key.s));
            break;
          }
          // 买卖 盘面
          case 'trust': {
            this.trust = Object.assign({}, content);
            break;
          }
          // 成功交易 盘面
          case 'orders': {
            // 只添加，不修改
            if (this.orders.d.length > 29) {
              this.orders.d.length = 29;
            }

            // 方向顺序
            if (is(content, "Array")) {
              content.reverse();
            }

            // 寻找最大值
            let maxOrder = _.maxBy(content, (ord) => (ord.p));
            // 今日最高 = 原来的  跟 新的成交价格 比较 取 较大的价格
            if (this.orders.max - maxOrder.p < 0) {
              this.orders.max = maxOrder.p;
            }
            // min
            let minOrder = _.minBy(content, (ord) => (ord.p));
            if (this.orders.min - minOrder.p > 0) {
              this.orders.min = minOrder.p;
            }

            // 价格 取第一个 最新价格
            this.orders.price = content[0].p;
            // 计算涨跌幅  【（（最新价格 - 收盘价） / 收盘价 ） * 100】  （保留两位小数）
            let ratio = ((this.orders.price - this.yestodayPrice) / this.yestodayPrice * 100).toFixed(2);
            // 去除多余的零 和 小数点
            this.orders.ratio = numUseNoDot(ratio);

            // 24小时 成交量  = 原来的 + 新成交的 成交量
            let sum = 0;

            // this.orders.money = math.add(this.orders.money, multiply(content.p, content.n));
            let money = 0;
            // 遍历  获取所有委托单 的 总额 => 成交额度 = 原来的 + （新成交的价格 * 新成交的数量）
            content.forEach((ord) => {
              // 总额 => 成交额度 = 原来的 + （新成交的价格 * 新成交的数量） 保留6位小数
              money = accurateAdd(this.orders.money, multiply(ord.p, ord.n));
              // 24小时 成交量  = 原来的 + 新成交的 成交量
              sum = accurateAdd(sum, ord.n);
            });

            if (money.match(/e\+/i)) {
              money = noIntENum(money);
            }
            money = numUseNoDot(toFixedNum(money, 6));
            this.orders.money = money;

            let sumStr = accurateAdd(this.orders.sum, sum);
            //
            if (sumStr.match(/e\+/i)) {
              sum = math.add(this.orders.sum, sum);
            } else {
              sum = sumStr;
            }
            //
            this.orders.sum = noIntENum(sum);

            // 插入一条新纪录
            this.orders.d = [...content, ...this.orders.d];
            break;
          }
          case 'balance': {
            let coinToOver = this.coinTo + '_over';
            // 交易区余额
            if (_.has(content, coinToOver)) {
              this.area_over = content[coinToOver];
            }
            // 冻结 余额
            let coinToLock = this.coinTo + '_lock';
            if (_.has(content, coinToLock)) {
              this.area_lock = content[coinToLock];
            }
            // 当前币余额
            let coinFromOver = this.coinFrom + '_over';
            if (_.has(content, coinFromOver)) {
              this.coin_over = content[this.coinFrom + '_over'];
            }
            let coinFromLock = this.coinFrom + '_lock';
            if (_.has(content, coinFromLock)) {
              this.coin_lock = content[coinFromLock];
            }
            break;
          }
          default:
            // this[`${type}Update`](content);
        }
      },
      // 行情
      dealAreaSocket({ c }) {
        if (c) {
          // 寻找对应的 币
          let targKey = _.findIndex(this.areas[c.area], key => (key.coin === c.market));
          if (targKey > -1) {
            // 辅助
            Object.keys(c).forEach(key => {
              if (Object.prototype.hasOwnProperty.call(this.areas[c.area][targKey], key)) {
                this.areas[c.area][targKey][key] = c[key] + '';
              }
            });
          }
          // 更新行情
          this.areas[c.area][c.market] = Object.assign({}, c);
          this.resetTHead();
          // 当前交易对
          if (c.market === this.curArea) {
            // 更新 orders
            let { price, max, min, money, ratio, sum } = c; // [this.coinTo][this.coinFrom + '_' + this.coinTo];
            // 赋新值
            if (price) {
              this.orders.price = price;
            }
            if (max) {
              this.orders.max = max;
            }
            if (min) {
              this.orders.min = min;
            }
            if (money) {
              this.orders.money = money;
            }
            if (ratio) {
              this.orders.ratio = ratio;
            }
            if (sum) {
              this.orders.sum = sum;
            }
          }
        }
      },
      // trade data socket
      socketTrade() {
        // let channel = 'mcc_btc';
        let token = cookie.getItem('WSTK'); // this.buyForm.reqToken;
        if (wsUrl) {
          let linkSockTime = 0;
          //
          let mySock = sock({
            url: wsUrl,
            open: () => {
              if (mySock) {
                mySock.send(`{"token": "${token}", "channel": "${this.coinFrom + '_' + this.coinTo}"}`);
                // 比列表
                mySock.send(`{"token": "${token}", "channel": "public"}`);
              }
              if (talkWs) {
                talkWs.open(mySock);
              }
              // 定时发送空消息，防止掉线
              setInterval(() => {
                if (mySock) {
                  mySock.send('');
                }
              }, 20000);
            },
            message: (response) => {
              let { data } = response;
              if (data && is(data, 'String') && data.indexOf('{') > -1) {
                let res = JSON.parse(data);

                // 聊天频道
                if (res.channel === 'Thechatroom') {
                  if (talkWs) {
                    talkWs.msg(response);
                  }
                }
                else if (res.channel === 'public') {
                  this.dealAreaSocket(res.data);
                }
                // 交易 频道
                else {
                  this.dealSocketData(res.data);
                }
              }
            },
            close: (data) => {
              // 释放资源
              mySock = null;
              let time = 3000;
              let intData = parseInt(data);
              if (data && !isNaN(intData)) {
                time = intData;
              }

              this.linkSockTime++;
              // 掉线重连 超过3次 socket 不稳定 不使用socket
              setTimeout(() => {
                if (this.linkSockTime < 3) {
                  this.socketTrade();
                } else {
                  this.canTimeout = true;
                  this.reload();
                }
              }, time);
            }
          });

          // 兼容不支持 socket 浏览器
          if (!mySock) {
            this.canTimeout = true;
          }
        }
      },
      // 点击 卖出 盘面（列表）
      sellClick(coinData) {
        // 填入价格 买卖 form 都填入
        this.autoFixPrice(coinData);
        // 填入数量 只填 **买入**（是买，没有错，反过来的） form
        this.autoFixNum(coinData, 'buy');
      },
      sellTouch(coinData) {
        if (document.body.clientWidth < 980) {
          this.sellFormStatus = false;
          this.buyFormStatus = true;
          this.sellClick(coinData);
        }
      },
      // 点击 买入 盘面（列表）
      buyClick(coinData) {
        this.autoFixPrice(coinData);
        // 填入数量 只填 **卖出**（是 卖 ，没有错，反过来的） form
        this.autoFixNum(coinData, 'sell');
      },
      buyTouch(coinData) {
        if (document.body.clientWidth < 980) {
          this.sellFormStatus = true;
          this.buyFormStatus = false;
          this.buyClick(coinData);
        }
      },
      // 自动填入买卖价格 买卖
      autoFixPrice(coinData) {
        if (coinData.p) {
          // 去除多余的零 和 多余的 .
          let price = numUseNoDot(coinData.p);
          this.buyForm.price = price;
          this.sellForm.price = price;
        }
      },
      // 自动填入 数量
      autoFixNum(coinData, type) {
        // 买入 form 填充数量``
        if (type === 'buy') {
          let formNum = '';
          // 如果下单数量 比可买数量大 ，则填入 可买数量
          formNum = coinData.l - this.canBuyNum > 0 ? this.canBuyNum : coinData.l;
          // 去除多余的零 和 多余的 .
          formNum = numUseNoDot(formNum);
          this.buyForm.number = formNum;
        }
        // 卖出 form 填充数量
        else {
          let formNum = '';
          // 如果下单数量 比 當前币 数量大 ，则填入 當前币 数量
          formNum = coinData.l - this.coin_over > 0 ? this.coin_over : coinData.l;
          // 去除多余的零 和 多余的 .
          formNum = numUseNoDot(formNum);
          this.sellForm.number = formNum;
        }
      },
      // 校验 交易 价格  限制只输入数字 包括小数
      checkNum(val, type, priceOrNum) {
        let result = '';
        let needChange = false;
        if (val) {
          // 转换 字符串
          if (!is(val, 'String')) {
            val += '';
          }

          // 限制小数 位数
          let float = '';
          // this[`${type}FormRule`].nfloat;
          // 價格 或 數量
          if (priceOrNum === 'price') {
            float = this[`${type}FormRule`].pfloat;
          } else {
            float = this[`${type}FormRule`].nfloat;
          }
          // 输入 是否是数字
          const numReg = /^(\d+(?:\.[0-9]*)?)/g;
          // 截取规则 有效数字 规则
          const mathRule = new RegExp(`^([0-9]+(?:\\.[0-9]{0,${float}})?)`, 'g');

          // 是否存在 有效数字
          if (numReg.test(val)) {
            // 截取规则内的数据
            let usefullNum = val.match(mathRule);
            if (usefullNum) {
              needChange = true;
              result = usefullNum[0];
            }
          } else {
            needChange = true;
            result = '';
          }
        }
        return { needChange, result };
      },
      // 限制 输入价格 规则
      priceInput(type) {
        let val = this[`${type}Form`].price;
        let checkNum = this.checkNum(val, type, 'price');
        // 是否需要 修改 输入值
        if (checkNum.needChange) {
          this[`${type}Form`].price = checkNum.result;
        }
      },
      // 数量 输入
      numInput(type) {
        let val = this[`${type}Form`].number;
        let checkNum = this.checkNum(val, type);
        if (checkNum.needChange) {
          this[`${type}Form`].number = checkNum.result;
        }
      },
      // 表单 校验 提示
      showTips(type, msg, callback, times = 3000) {
        this[`${type}Tips`] = msg;
        // 是否需要 回掉
        if (callback && is(callback, 'Function')) {
          callback();
        }
        setTimeout(() => {
          this[`${type}Tips`] = '';
          this[`${type}TipsTheme`] = 0;
          //
          // setTimeout(() => {
          //   // this[`${type}OrderOk`] = false;
          // }, 100);
        }, times);
      },
      // 买入规则校验 判断最大值 最小值;  是否超出: (buy)可买约 或者 （sell）当前币 余额
      checkForm(type) {
        let isCompleted = true;
        let { number } = this[`${type}Form`];
        // 价格判断 小于最小值 交易數量不能小於
        if (this[`${type}FormRule`].min - 0 > 0 && number - this[`${type}FormRule`].min < 0) {
          this.showTips(type, 'short');
          isCompleted = false;
        }
        // 大于最大值  msg:交易數量不能大於
        if (this[`${type}FormRule`].max - 0 > 0 && number - this[`${type}FormRule`].max > 0) {
          this.showTips(type, 'over');
          isCompleted = false;
        }

        return isCompleted;
      },
      // 下单 公共判断 是否为空 是否为 0
      submitOrder(type) {
        // 防止重复提交
        if (this.submitting) return;
        this.submitting = true;
        let isCompleted = true;
        let { number, price } = this[`${type}Form`];

        // 重置 提示颜色状态
        this[`${type}OrderOk`] = false;
        // 价格 为空 或 0  msg: 請輸入價格
        if (price === '' || price - 0 <= 0) {
          isCompleted = false;
          this.showTips(type, 'inputN');
        }
        // 数量 为空 或 0  msg: 請輸入數量
        else if (number === '' || number - 0 <= 0) {
          isCompleted = false;
          this.showTips(type, 'inputP');
        }
        // 买入buy，交易额＞BTC可用余额  超出可买入额，请检查后重新输入  msg:超出可買入額，请检查后重新输入
        else if (type === 'buy' && this.buySettle - this.area_over > 0) {
          isCompleted = false;
          this.showTips(type, 'overB');
        }
        // 卖出sell 按钮，数量 > 当前币余额 提示;  交易额＞MCC可用余额X卖出价=可卖约  msg：超出可賣出額，請檢查后重新輸入
        else if (type === 'sell' && number - this.coin_over > 0) {
          isCompleted = false;
          this.showTips(type, 'overS');
        }
        if (!isCompleted) {
          return this.submitting = false;
        }
        // 下单 买卖
        if (this.checkForm(type)) {
          // 记录当前交易类型 buy or sell
          this.orderType = type;
          // let postData = this.$jsencrypt(Object.assign({}, this[`${type}Form`]));

          // 下单
          this.$http.post('/ajax_trade/setTrust', this[`${type}Form`])
            .then(({ status, data, msg }) => {
              // 异常
              if (status === undefined || data === undefined) {
                this.showTips(type, 'The server is busy, please try again later');
              }
              // 下单成功
              else if (parseInt(status) === 1) {
                this[`${type}TipsTheme`] = 1;
                this[`${type}OrderOk`] = true;
                this.showTips(type, msg, () => {
                  // 清空数量
                  this[`${type}Form`].number = '';
                });
              }
              // 0.是否 登陸 下单失败
              else if (data.need_login && parseInt(data.need_login) === 1) {
                this.showTips(type, msg, () => {
                  this.login();
                });
              }
              // 1.用户还未设置实名认证 toast提示【请实名认证后再操作】
              else if (data.need_real_auth && parseInt(data.need_real_auth) === 1) {
                // 请实名认证后再操作
                this.showTips(type, msg, () => {
                  setTimeout(() => {
                    // 转跳
                    window.location.href = "/user/realinfo";
                  }, 1000);
                });
              }
              // 2.请设置交易密码后再操作
              else if (data.need_set_tpwd && parseInt(data.need_set_tpwd) === 1) {
                // 交易密码后再操作
                this.showTips(type, msg, () => {
                  //
                  setTimeout(() => {
                    // 输入 交易密码 提示 显示 输入框
                    window.location.href = '/user?set=tradepwd';
                  }, 1000);
                });
              }
              // 3.输入交易密码 status
              else if (data.need_trade_pwd) {
                // 请实名认证后再操作
                this.setPwd = true;
              }
              // 4. 其他情况
              else {
                this.showTips(type, msg);
              }

              // 防止按按键速度过快，不显示下单成功提示
              setTimeout(() => {
                // 再次提交开关
                this.submitting = false;
              }, 100);
            })
            .catch(() => {
              this.showTips(type, 'The server is busy, please try again later');
              this.submitting = false;
            });

        } else {
          this.submitting = false;
        }
      },
      closePwd() {
        this.setPwd = !this.setPwd;
        this.pwdSubmitting = false;
        this.pwdTips = '';
        this.pwdTrade = '';
      },
      // 确认交易密码
      surePwd() {
        if (this.pwdSubmitting) return;
        this.pwdSubmitting = true;
        // 為空
        if (!this.pwdTrade) {
          this.pwdTips = 1;
          this.pwdSubmitting = false;
          return;
        }

        let postData = Object.assign({ pwdtrade: this.pwdTrade }); // this.$jsencrypt({ pwdtrade: this.pwdTrade });
        this.$http.post('/ajax_trade/pwdtradeAuth', postData)
          .then(({ status, msg }) => {
            if (status === undefined) {
              this.pwdTips = 'The server is busy, please try again later';
            }
            // // 交易密码正确
            if (parseInt(status) === 1) {
              this.setPwd = false;
              this.pwdTips = '';
              this.submitOrder(this.orderType);
            }
            // 交易密码 输入 有误
            else {
              this.forgetPwd = true;
              this.pwdTips = msg;
            }

            this.pwdSubmitting = false;
          })
          .catch(() => {
            this.pwdTips = 'The server is busy, please try again later';
            this.pwdSubmitting = false;
          });
      },
      // 正在撤销 列表
      canceling(id) {
        let status = false;
        if (_.indexOf(this.cancellingQueue, id) > -1) {
          status = true;
        }
        return status;
      },
      // 撤销
      cancelOrder(order, agrStatus) {
        if (order.s - 2 >= 0) {
          return;
        }
        //  此单 在取消订单列表  是否在取消队列里 不在订单，执行取消 在，就返回
        if (_.indexOf(this.cancellingQueue, order.i) > -1) {
          return;
        }
        // 不在撤銷列表 加入 列表
        else {
          this.cancellingQueue.push(order.i);
        }
        // 更改状态 撤銷中 ...
        // order.status = 5;

        // 记录原来的状态
        let oldStatus = agrStatus;
        // 缓存订单号  用于 移除取消成功 从取消列表移除
        let id = order.i;
        let postData = {
          id,
          coin_from: this.coinFrom,
          coin_to: this.coinTo,
          reqToken: this.sellForm.reqToken
        };
        this.$http.post('/ajax_trade/trustcancel', postData)
          .then(({ status }) => {
            // 撤销 成功
            if (parseInt(status) === 1) {
              order.s = 3;
              // 重新排序
              this.mytrust.list = _.sortBy(this.mytrust.list, (key) => (key.s));
              // 750屏幕 委托详情 界面 点击撤销 撤销成功，隐藏委托详情，显示我的委托列表
              if (this.isDealTrust) {
                this.isDealTrust = false;
              }
            } else {
              order.s = oldStatus;
            }
            _.remove(this.cancellingQueue, key => key === id);
          })
          .catch(() => {
            order.s = oldStatus;
            _.remove(this.cancellingQueue, key => key === id);
          });
      },
      // formNum 赋值
      formNumValue(type, percen) {
        let number = '';
        // sell numer = 当前币 余额 * 百分比
        if (type === 'sell') {
          number = this.coin_over * percen;
          if (percen - 1 < 0) {
            number = `${number}`.slice(0, 10);
          }
        }
        // buy number = 可买约 * 百分比
        else if (this.canBuyNum && this.canBuyNum != '--') {
          number = this.canBuyNum * percen;
          if (percen - 1 < 0) {
            number = `${number}`.slice(0, 10);
            number = numUseNoDot(number);
          }
        }
        let strNum = number + '';
        if (strNum.indexOf('e-') > -1) {
          number = eNumToStri(strNum);
        }
        else if (strNum.indexOf('e+')) {
          number = Number(strNum);
        }
        // 赋值
        this[`${type}Form`].number = number;
      },
      // 点击 滑杆 圆点
      rangeOn($event, type) {
        if ($event.pageX) {
          this.mouseStartX = $event.pageX;
        } else {
          this.mouseStartX = $event.targetTouches[0].pageX;
        }

        this.mouseStatu = type;
        // **** 这里要改成原生写法 计算 元素在屏幕的位置
        this.edgeLeft = $($event.target).parent().offset().left;
      },
      // 移动 滑竿
      moveDot($event, type) {
        // 如果点击了 圆点
        if (this.mouseStartX > 0) {
          let pageX = $event.pageX || $event.targetTouches[0].pageX;
          // 鼠标相对移动位置 x方向
          let offsetX = pageX - this.edgeLeft;

          // 鼠标位置是否超出滑竿左边边缘位置
          if (pageX - this.edgeLeft <= 0) {
            offsetX = 0;
          }
          // 超出范围
          if (offsetX - this.trackWidth >= 0) {
            offsetX = this.trackWidth;
          }

          // 计算百分百
          let percen = 0;
          if (offsetX != 0) {
            percen = (offsetX / this.trackWidth).toFixed(3);
          }

          // 赋值
          this.formNumValue(type, percen);
        }
      },
      // 点击 滑竿
      trackClick($event, type) {
        // **** 这里要改成原生写法 计算 元素在屏幕的位置
        this.edgeLeft = $($event.target).offset().left;
        let pageX = $event.pageX || $event.targetTouches[0].pageX;
        let offsetX = pageX - this.edgeLeft;
        let percen = (offsetX / this.trackWidth).toFixed(1);
        // 赋值
        this.formNumValue(type, percen);
      },
      // 980 屏幕切换k线图
      tradeKline() {
        let klineDom = document.getElementsByClassName('kline_contain')[0];
        if (klineDom) {
          // 判断当前kline是否显示
          let klineStyle = window.getComputedStyle(klineDom);
          if (klineStyle.display === 'none') {
            this.klineShow = true;
            this.buySaleShow = false;
          } else {
            this.klineShow = false;
            this.buySaleShow = true;
          }
        }
      },
      // click
      buySellChange(type) {
        if (type === 'buy') {
          this.buyFormStatus = true;
          this.sellFormStatus = false;
        } else {
          this.buyFormStatus = false;
          this.sellFormStatus = true;
        }
      },
      tStart() {
        this.isTchClick = true;
      },
      tMove() {
        this.isTchClick = false;
      },
      showCancelPane(trust) {
        if (document.body.clientWidth >= 980) {
          return;
        }
        if (!this.isTchClick) {
          this.isTchClick = false;
          return;
        }
        this.getScrollY();
        //
        this.isDealTrust = !this.isDealTrust;
        this.deTrust = trust;
      },
      closeCancelPane() {
        this.isDealTrust = !this.isDealTrust;
        // 滚动到原来的位置
        this.resetScrollTop();
        // document.body.scrollTop = this.scrollTop;
      },
      // 获取滚动距离
      getScrollY() {
        let body = document.body;
        let scrollY = body.scrollTop + body.clientHeight;
        this.scrollTop = scrollY;
        return scrollY;
      },
      // 滚动到顶部
      scrollTopFn() {
        document.body.scrollTop = 0;
      },
      // 滚动到原来的位置
      resetScrollTop() {
        document.body.scrollTop = this.scrollTop;
      },
      // pc 端点击显示交易区按钮
      areaTouchFn() {
        // if (!isMob()) {
        //   this.areaTouchShow = !this.areaTouchShow;
        // }
      },
      // time 单位秒
      setFeeTime(time) {
        this.noFeeAllTime = time;
        this.noFeeCountTime();
        this.countFeeTime();
      },
      // 时间倒计时
      noFeeCountTime() {
        let h = parseInt((this.noFeeAllTime / 60 / 60));
        let min = parseInt((this.noFeeAllTime - 60 * 60 * h) / 60);
        this.noFee = {
          h, min
        };
      },
      countFeeTime() {
        let time = setInterval(() => {
          if (this.noFeeAllTime > 0) {
            this.noFeeAllTime -= 60;
            this.noFeeCountTime();
          } else {
            clearTimeout(time);
          }
        }, 60000);
      },
      // 计算 买卖 窗口显示条数
      compBuySellWin() {
        let win = this.$refs.sellList;
        if (win) {
          let winStyle = window.getComputedStyle(win);
          let ul = win.firstChild;
          let lineHeight = 22;
          if (ul) {
            let li = ul.firstChild;
            if (li) {
              let listyle = window.getComputedStyle(li);
              lineHeight = parseFloat(listyle.height);
            }
          }
          let num = parseInt(winStyle.height) / lineHeight;
          let maxNum = Math.ceil(parseInt(winStyle.height) / lineHeight);
          this.sellMaxNum = maxNum;
        }
      },
      //显示交易区域
      changeTrade(e) {
        this.areaShow = true;
      }
    },
    computed: {
      // 计算 可买入 约
      canBuyNum() {
        let result = '--';
        if (this.buyForm.price && parseFloat(this.buyForm.price) > 0) {
          let canBuy = this.area_over / this.buyForm.price;
          // 保证有足够20位小数
          canBuy = canBuy.toFixed(20);
          canBuy.replace(/[0-9]*(?:\.[0-9]{8})/g, (key) => {
            result = key;
          });
        }
        return result;
      },
      // 计算 可卖出 约
      canSellNum() {
        let result = '--';
        if (this.sellForm.price && parseFloat(this.coin_over) > 0) {
          result = multiply(this.coin_over, this.sellForm.price);
        }
        let strRes = result + '';
        if (strRes.indexOf('e-') > -1) {
          result = eNumToStri(strRes);
        }
        else if (strRes.indexOf('e+') > -1) {
         result = Number(strRes);
        }
        //
        strRes = result + '';
        if (strRes.length > 19) {
          strRes = strRes.slice(0, 19);
        }
        if (strRes != '--') {
          strRes = numUseNoDot(strRes);
        }
        return strRes;
      },
      // 买入 交易额 结算
      buySettle() {
        let settle = multiply(this.buyForm.price, this.buyForm.number);
        // 是否有e
        if (settle.indexOf('e-') > -1) {
          settle = eNumToStri(settle);
        }
        else if (settle.indexOf('e+') > -1) {
         settle = Number(settle);
        }
        // 保留指定位小数
        let resSel = toFixedNum(settle);
        return numUseNoDot(resSel);
      },
      // 卖出 交易额 结算
      sellSettle() {
        // if (this.sellForm.price&& )
        let settle = multiply(this.sellForm.price, this.sellForm.number);
        // 是否有e
        if (settle.indexOf('e-') > -1) {
          settle = eNumToStri(settle);
        } else if (settle.indexOf('e+') > -1) {
          settle = Number(settle);
        }

        settle = numUseNoDot(settle);
        // 保留指定 (8默认) 位小数
        return settle;
      },
      // 买入 下单比例 = (买入数量 / 可买入约) * 100;
      buyRatio() {
        let ratio = '0';
        if (this.buyForm.number && this.canBuyNum && this.canBuyNum != '--') {
          ratio = ((this.buyForm.number / this.canBuyNum) * 100).toFixed(1);
        }
        // 去除多余的 0 和 .
        ratio = numUseNoDot(ratio);
        // 超出 100 只显示 100
        if (ratio - 100 > 0) {
          ratio = '100';
        }
        // 不满 100
        if (ratio === '100' && this.canBuyNum - this.buyForm.number > 0) {
          ratio = '99.9';
        }

        if (!ratio) {
          ratio = '0';
        }
        this.buyRang = ratio;
        return ratio;
      },
      // 卖出 下单比例 = (卖出数量 / 当前 币余额 ) * 100
      sellRatio() {
        let ratio = '0';
        if (this.sellForm.number && this.coin_over) {
          ratio = ((this.sellForm.number / this.coin_over) * 100).toFixed(1);
        }
        // 去除多余的 0 和 .
        ratio = numUseNoDot(ratio);
        // 超出 100 只显示 100
        if (ratio - 100 > 0) {
          ratio = '100';
        }
        // 不满 100
        if (ratio === '100' && this.coin_over - this.sellForm.number > 0) {
          ratio = '99.9';
        }

        if (!ratio) {
          ratio = '0';
        }
        this.sellRang = ratio;
        return ratio;
      },
      // buy 滑竿  原点 left 位置
      buyDotLeft() {
        return this.trackWidth * (this.buyRatio / 100) - 5 + 'px';
      },
      // sell 滑竿  原点 left 位置
      sellDotLeft() {
        return this.trackWidth * (this.sellRatio / 100) - 5 + 'px';
      }
    },
    watch: {
      pwdTrade(newPwd) {
        this.pwdTips = '';
        // 赋值
        this.buyForm.pwdTrade = newPwd;
        this.sellForm.pwdTrade = newPwd;
      },
      // 用于区分下单状态管理
      submitting(newStatus) {
        this[`${this.orderType}Submitting`] = newStatus;
      },
      setPwd(pwdstatus) {
        let body = document.body;
        let oldClass = body.className;
        // 输入密码时 body 不滚动  添加 class  no_scroll_body
        if (pwdstatus) {
          //
          this.getScrollY();
          // 滚动到顶部
          this.scrollTopFn();
          body.className = oldClass + ' no_scroll_body';
        } else {
          body.className = oldClass.replace('no_scroll_body', '');
          this.resetScrollTop();
        }
      },
      isLogin(logStatus) {
        // if (!logStatus && isMob()) {
        //   this.isMobUnlogin = false;
        // }
      },
      areaSearch(nKey) {
        if (nKey) {
          let reg = /^([0-9a-zA-Z]+)$/;
          if (reg.test(nKey)) {
            // 在当前交易区，搜索指定的币
            this.searchingArea();
          } else {
            let str = nKey.match(/^([0-9a-zA-Z]+)/g);
            if (str) {
              this.areaSearch = str[0];
            } else {
              this.areaSearch = '';
            }
          }
        }
        // 重置状态
        else {
          this.resetAreaSearch();
        }
      }
    },
    components: {
      Alert, scrollTable, ElCollapse
    }
  })
  .$mount("#app");

  // k线图 显示控制 注意函数节流
  function klineCtrl() {
    let winWidth = document.body.clientWidth;
    if (winWidth > 1199) {
      canHoverRMB = true;
      tradeVue.klineShow = true;
      tradeVue.buySaleShow = true;
      // 买卖 form
      tradeVue.buyFormStatus = true;
      tradeVue.sellFormStatus = true;
    } else {
      canHoverRMB = false;
      tradeVue.klineShow = false;
      tradeVue.buySaleShow = true;
      // 移动端 不重置窗口大小 横屏翻转
      // if (!isMob()) {
      //   if (winWidth < 980) {
      //     tradeVue.buyFormStatus = true;
      //     tradeVue.sellFormStatus = false;
      //   } else {
      //     tradeVue.buyFormStatus = true;
      //     tradeVue.sellFormStatus = true;
      //   }
      // }
    }
    tradeVue.compBuySellWin();
  }
  window.addEventListener('resize', klineCtrl, false);
});

export default {};
