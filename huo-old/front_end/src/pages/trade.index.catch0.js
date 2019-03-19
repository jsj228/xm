/* eslint-disable */
import '@/styles/common/navDefault.scss';
import '@/styles/trade/trade.scss';
import navTrade from '@/components/nav-trade';
import range from '@/components/range';
import register from '@/components/register';
import HoverWin from '@/components/hoverWin';
import is from '@/tools/is';
import eNumToStr from '@/tools/eNumToStr';
import math from 'mathjs';
import mathFn from '@/tools/math';
import Alert from "@/tools/alert/alert";
import ResetPwd from '@/components/resetPassword';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import Promise from 'promise';
import _ from 'lodash';
import Vue from 'vue';
import { JSEncrypt } from 'jsencrypt/bin/jsencrypt';
/* eslint-disable */
import tradeVue from '@/nPages/trade.index';

// process.env.NODE_ENV
$(document).ready(function() {

  let publicKey = '';
  let encrypt = new JSEncrypt();
  // public key
  function publicKeyFn() {
    $.ajax({
      url: '/ajax_user/getCommonRsaKey',
      method: "GET",
      success({status, data}) {
        let intStatus = parseInt(status);
        if (intStatus === 1) {
          publicKey = data;
          encrypt.setPublicKey(publicKey);
        } else {
          publicKeyFn();
        }
      },
      error(err) {
        if (err) publicKeyFn();
      }
    });
  }
  // 获取public key
  publicKeyFn();

  //获取语言包
  var phpLang = getLanguagePack() || {
    CANCEL_FIAL: "撤單未成功",
    NET_ERROR: "網路故障",
    TRUST_STATUS_ALL : "全部成交",
    TRUST_STATUS_PART: "部分成交",
    TRUST_STATUS_NO : "未成交",
    TRUST_STATUS_CANCEL: "已撤銷",
    TRUST_STATUS_FIND : "查詢中",
    INPUT_NEED_VALID : "請輸入數字,小數點後最多8位",
    FLOATNUM_A : "價格必須大於0,小於1,最多",
    FLOATNUM_B: "位小數",
    MAX_TRADE_NUM: "最大交易數量為:",
    MIN_TRADE_NUM: "最小交易數量為:",
    AMOUNT_MAX: "最多",
    TRADE_BUY_IN: "買入",
    TRADE_SELL_OUT: "賣出",
    TRADE_CANCEL: "撤銷",
    OPEN_SOON: "即將開放",
    LOADING: "加载中..."
    // sell: "立即賣出",
    // form-buy: "立即買入"
  };
  //获取语言包
  // const lan = getLanguagePack();
  // if(lan.tradeJs.length > 0){   //language.json中tradeJs為空時輸出數組，不為空時輸出的是對象
  //   phpLang = lan.tradeJs;
  // }
  navTrade(phpLang);
  //警告框
  const myAlert = new Alert("");
  //滑块相关函数
  const rangeO = range();
  const rangeObj = rangeO.rangeObj;
  const rangeFunc = rangeO.rangeFunc;
  const countSettle = rangeFunc.countSettle;
  //计算方面函数
  const mathObj = mathFn();
  const fixMulti = mathObj.fixMulti;
  const accDiv = mathObj.accDiv;
  const cutFixedNum = mathObj.cutFixedNum;
  //表格列表tr高度,用于计算可填充最大的tr数量
  const tableTrHeight = 21;
  //人民币悬浮框
  const myHover = new HoverWin($('[data-tormb]'), 'tormb');
  myHover.loadingTips = phpLang.LOADING;
  //调用悬浮框
  function addHoverWin($elem, attr, direction) {
    $($elem).off('mouseenter');
    $($elem).mouseenter(function(e) {
      myHover.setHover(this, attr, direction);
    });
    $($elem).mouseleave(function() {
      myHover.hide();
    });
  }
  function addKeyUpWin($elem, attr, direction){
    myHover.setHover($elem, attr, direction);   //第一次focus就执行显示
    $($elem).keyup(function() {                 //监听按键按起再执行
      myHover.setHover(this, attr, direction);
    });
    $($elem).blur(function() {                 //失焦时隐藏
      myHover.hide();
    });
  }
  // 示例 擁有 data-tormb 屬性的元素，
  // 需要顯示的 內容在data- 的 tormb 的值中
  // 顯示方向 在 (可以選擇 "left", "top", "right", "bottom" 四個方向 默認上方)
  addHoverWin($('[data-tormb]'), 'tormb', 'left');
  addHoverWin($('span[id$="settle"]'), 'tormb', 'top');
  addHoverWin($('#day-volumn'), 'tormb', 'bottom');
  // 注册登录、交易密码悬浮框相关函数
  const registerObj = register(function(reg) {
    const resetPwdFn = new ResetPwd();
    resetPwdFn.pushPhoneNum();
    // return resetPwdFn;
  });
  // 关闭隐藏事件
  registerObj.rootCallBack = function() {
    $(`form[data-form="trade"] .is-submitting`).removeClass('is-submitting').children('input[disabled]').removeAttr('disabled');
    // $('form[data-form="trade"]').removeAttr('disabled')
  }
  const loginFn = registerObj.loginFn;
  const pwdFn = registerObj.pwdFn;
  // pwdFn();
  const warnTip = registerObj.warnTip;
  //用户是否存在标记
  let userExist = false;
  //獲取當前幣
  const coinFrom = $('.curr-mar').find('h2').data('from');
  const coinTo   = $('.curr-mar').find('h2').data('to');
  // 定義輪詢時間
  const delayT = 1500;
  // 人民幣彙率
  let rmbPrice = 0;
  // 置空transPwd
  let transPwd = '';
  // 定义最新成交價
  let newestPrice = 0;
  // 记录当前撤销单
  let calIdList = [];
  let hasCalIdList = [];
  // 判断数据是否为 null
  function nonull(data){
    if(data != null && data != undefined){
      return true;
    }else{
      return false;
    }
  }
  // 科学计算法，转字符串
  function eToNum(eNum) {
    let result = eNum;
    // 是否是一个数字类型的变量
    if (is(eNum, 'Number')) {
      let eNumStr = eNum + '';
      let numLength;
      if (eNumStr.indexOf('e') > -1) {
        numLength = eNumStr.slice(-1);
        result = eNum.toFixed(numLength);
      }
    }
    return result;
  }
  // 格式化 显示数字,前后共numLength位,返回小数整数部分、小数部分、小数点后非0部分、小数点后末尾0
  function beautyNum(num, numLength) {
    let result;
    var realNum = eToNum(num);
    if ($.trim(realNum)) {
      // 默认 8位
      let maxLength = numLength || 8;
      // 转换字符串
      let numStr = realNum + '';
      // 获取整数部分
      const numInt = parseInt(numStr) + '';
      let numSmall = '';
      // 整数部分 < 8 位
      if (numInt.length < maxLength) {
        let catchNum = parseFloat(realNum).toFixed(12);
        // 截取小數部分
        catchNum = catchNum.split('.')[1];
        // 截取 有效小数位 限制为 （总长度 - 整数部分长度）
        numSmall = catchNum.slice(0, maxLength - numInt.length + 1);
      } else {
        // 取小数部分
        numSmall = num.split('.')[1];
      }
      // 匹配小数部分的 0 （末尾 黑色 0）;
      // 取小数部分
      var darkMatch = numSmall.match(/0+?$/);   //匹配小数部分的0;
      var numDark = nonull(darkMatch) ? darkMatch[0] : '';
      var diff = numSmall.length - numDark.length;
      var numSamllLeft = numSmall.substring(0, diff);
      result = { int: numInt, small: numSmall, left: numSamllLeft,  dark: numDark};   //left表示小数部分左边不暗部分 ； dark小数暗色部分
    }
    return result;
  }
  // 处理后端字符串数字
  function fromatNum(num, len) {
    let resultNum = {int: "", left: "", dark: ""};
    if (num) {
      let catchNum = (num + '').split('.');
      // 判断整数位数长度
      resultNum.int = catchNum[0];
      // 缓存小数部分
      let left = catchNum[1];
      resultNum.left = left ? left : "";
      if (resultNum.int.length < len) {
        // 小数部分等于 限制长度 - 整数部分长度
        resultNum.left = resultNum.left.slice(0, len - resultNum.int.length + 1);
      }
      // 去除小数 所有 的 有效的 零
      if (resultNum.left == 0) {
        resultNum.left = '';
      }
      // 去除 小数部分 末尾 无效0
      resultNum.left = resultNum.left.replace(/0+?$/, "");
      // 小数和整数长度未达到限制长度，补0
      let smLen = resultNum.left.length;
      let intLen = resultNum.int.length;
      let darkLen = smLen + intLen;
      if (darkLen < len) {
        resultNum.dark = "000000000000".slice(0, len - darkLen);
      } else {
        resultNum.left = resultNum.left.slice(0, len - intLen);
      }
      // 处理如果处理的数字迷雾 末尾 0
      // let hasDark = resultNum.left.match(/0+?$/);
      // //
      // if (hasDark) {
      //   // 提取末尾的 0
      //   resultNum.dark = hasDark[0];
      //   // 删除多余 的末尾 0
      //   resultNum.left = resultNum.left.replace(/0+?$/, "");
      // }
      // resultNum.
    }
    return resultNum;
  }
  // 处理 无效小数点
  function getUseNum(text) {
    let dotSub = text.lastIndexOf('.');
    let  result = text;
    // 处理 无效小数点
    if (dotSub > -1) {
      result = parseFloat(text);
    }
    return result;
  }
  // 去除字符串形式的小数点后多余的0   @param {Number or String} str；  @returns str
  function cutZero(str){
    if(!is(str, "String")){
      str = str + "";
    }
    if(str.indexOf('.') > -1){
      str = str.replace(/0+?$/, '');
      str = str.replace(/[.]$/, '');
    }
    return str;
  }
  //鼠标移入交易规则显示全部规则
  // var isAni = false;   //正在动画标记
  $('#sidebar-catch').on('mouseenter', function(){
    // if(isAni) return;
    const $this = $(this);
    // isAni = true;
    $this.parent().stop().animate({'right': '-30px'}, 'fast', function(){
      // isAni = false;
    });
  })
  $('#sidebar-container').on('mouseleave', function(){
    // if(isAni) return;
    const $this = $(this);
    // isAni = true;
    $this.stop().animate({'right': '-330px'}, 'fast', function(){
      // isAni = false;
    });
  })

  // 判断用户是否登陆 显示/隐藏登录后再交易的遮罩层
  function tradeAfterLogin(){
    const $maskBg = $('#tradeAfterLogin');
    userExist ? $maskBg.hide() : $maskBg.show();
  }
  // 是否已经登录
  var isLogin = new Promise(function(resolve, reject){
    $.ajax({
      url:  "../ajax_user/getUserInfo?coin=" + coinFrom + '_' + coinTo,
      type: 'get',
      async: true,
      success: function(res){
        nonull(res.data) ? (userExist = true, resolve()) : (userExist = false,reject());
      },
      error: function(err){
        userExist = false;
        reject();
      }
    })
  })
  isLogin.then(function(resolve){
    tradeAfterLogin();
  }).catch(function(reject){
    tradeAfterLogin();
  })

  // 渲染用戶可用幣信息
  function setUserInfo(data) {
    const $buyAvailUse = $('#buy-availUse');    //買入區可用幣DOM
    // var $buyAvailBuy = $('#buy-availBuy');    //買入區可買幣DOM
    const $sellAvailUse = $('#sell-availUse');  //賣出區可用幣DOM
    // var $sellAvailBuy = $('#sell-availBuy');  //賣出區可買幣DOM
    const $buyLock = $('#buy-lock');  //買入 凍結額
    const $sellLock = $('#sell-lock');  //賣出 凍結額
    if(data.status == 1 && data.data){
      var coinFromOver = data.data[coinFrom + '_over'];  //買入餘額
      var coinToOver = data.data[coinTo + '_over'];     //賣出餘額
      var coinFromLock = data.data[coinFrom + '_lock'];
      var coinToLock = data.data[coinTo + '_lock'];
      nonull(coinFromOver) ? $buyAvailUse.text(cutFixedNum(coinToOver)) : $buyAvailUse.text('--');
      nonull($sellAvailUse) ? $sellAvailUse.text(cutFixedNum(coinFromOver)) : $sellAvailUse.text('--');
      nonull(coinToLock) ? $buyLock.text(cutFixedNum(coinToLock)) : $buyLock.text("--");
      nonull(coinFromLock) ? $sellLock.text(cutFixedNum(coinFromLock)) : $sellLock.text("--");
      // 成交價存在且大于0
      // if(newestPrice && newestPrice){
      //   //可買btc = 當前幣余額/最新成交價
      //   // $buyAvailBuy.text(parseFloat(accDiv(coinToOver, newestPrice)));
      //   // $sellAvailBuy.text(parseFloat(fixMulti(coinFromOver, newestPrice)));
      // }else{
      //   // $buyAvailBuy.text('--');
      //   // $sellAvailBuy.text('--');
      // }
      // 冻结额
      $buyLock.attr('data-tormb', math.multiply(rmbPrice, $buyLock.text()));
      $sellLock.attr('data-tormb', math.multiply(rmbPrice, math.multiply(newestPrice, $sellLock.text())));
      // 余额
      $buyAvailUse.attr('data-tormb', math.multiply(rmbPrice, coinToOver) || phpLang.LOADING);
      $sellAvailUse.attr('data-tormb', math.multiply(rmbPrice, math.multiply(newestPrice, coinFromOver)) || phpLang.LOADING);
      addHoverWin('.amount-box [data-tormb]','tormb', top);
      //TODO
    }
  }
  // 獲取用戶信息  1.獲取最新成交  2.獲取市場委托  3.獲取我的我的委托
  function getUserInfo() {
    return;
    var userInfoGetter = {
      url:  "../ajax_user/getUserInfo?coin=" + coinFrom + '_' + coinTo,
      type: 'get',
      async: true,
      success: function(res){
        setUserInfo(res);
        userExist = true;
      },
      error: function(err){
        if (err) {
          if (is(err, "String")) {
            // myAlert.show('獲取用戶信息失敗' + err + "继续获取中");
            console.log('獲取用戶信息失敗' + err + "继续获取中");
          }
        }
        setTimeout(function(){$.ajax(userInfoGetter);}, delayT);
        userExist = false;
      }
    }
    $.ajax(userInfoGetter);
  }
  // 合并接口调  getMyTrust ,  getTrust ,  getOrders
  function tradeData(upDateOnce) {
    return;
    var options = {
      url: "/ajax_trade/tradeData?coin=" + coinFrom + '_' + coinTo,
      method: "GET",
      success(res) {
        if (parseInt(res.status) === 1) {
          // 我的我的委托   getMyTrust
          setMyTrust(res.data.mytrust);
          // if (is(res.data.mytrust, "Array") && res.data.mytrust.length > 0) {
          // }
          // getTrust
          setTrust(res.data.trust);
          // if (is(res.data.trust, "Array") && res.data.trust.length > 0) {
          // }
          // getOrders
          setOrder(res.data.orders);
          // if (is(res.data.orders, "Array") && res.data.orders.length > 0) {
          // }
        }
        // 控制是否只更新一次
        if (!upDateOnce) {
          setTimeout(function() {
            $.ajax(options);
          }, delayT);
        }
      },
      error(err) {
        // 控制是否只更新一次
        if (!upDateOnce) {
          setTimeout(function() {
            $.ajax(options);
          }, delayT);
        }
      }
    };
    $.ajax(options);
  }
  // 处理接口数据
  tradeData();
  // 獲取當前幣兌換人民幣價格
  function getRmbRatio(){
    let coinTo = $("#curr-mar [data-to]").data('to');
    // return ;
    var rmbRatioGetter = {
      // url: 'http://api.huobi.com/staticmarket/detail_'+ coinTo +'_json.js',
      url: `/ajax_market/coinPrice?coin=${coinTo}`,
      type: 'GET',
      success: function(res){
        if (res && res. status == 1) {
          rmbPrice = res.data;
          window.rmbPrice = rmbPrice;
        } else {
          window.rmbPrice = '';
        }
      },
      error: function(err){
        // 無法獲取人民幣彙率
        rmbPrice = 0;
        setTimeout(function(){$.ajax(rmbRatioGetter)}, delayT);
      }
    }
    $.ajax(rmbRatioGetter);
    return rmbPrice;
  }

  // 允许 连续 获取 数据开关
  let canCoinListInfo = false;
  // 渲染各個幣的實時數據
  function setCoinListInfo(data) {
    // const $coinList = $('.curr-mar dl');
    if(data.status == 1 && data.data){   //返回成功有數據
      // const coinArr = data.data;
      const areaArr = data.data;
      for(let coinName in areaArr) {
        let coinArr = areaArr[coinName];
        for(let coin in coinArr){
          if(nonull(coinArr[coin])){
            let coinPrice = beautyNum(coinArr[coin].price, 9);
            const $allMar = $(`#all-mar [data-coin="${coin}"]`);
            $allMar.find('dd:first').html(`${nonull(coinArr[coin].price) ? `<b>${coinPrice.int}.${coinPrice.left}<span class="low-vision">${coinPrice.dark}</span></b>` : '--'}`);
            $allMar.find('dd:last').text(`${nonull(coinArr[coin].ratio) ? coinArr[coin].ratio + "%" : '--'}`);
            if(coinArr[coin].ratio < 0) {   //跌
              $allMar.find('dd:first').removeClass('font-g font-r').addClass('font-g');
              $allMar.find('dd:last').removeClass('market-up market-down').addClass('market-down');
            }else{                          //漲
              $allMar.find('dd:first').removeClass('font-g font-r').addClass('font-r');
              $allMar.find('dd:last').removeClass('market-up market-down').addClass('market-up');
            }
          }else{
            return;
          }
        }
      }
    } else {
      return;
    }
  }

  // 獲取 幣 列表 市場 實時 數據
  function getCoinListInfo() {
    $.ajax({
      url: '/ajax_market/getAllQuote',
      type: 'GET',
      success(res) {
        setCoinListInfo(res);
        // 是否允许再次获取数据 开关
        if (canCoinListInfo) {
          setTimeout(() => {
            getCoinListInfo();
          }, delayT);
        }
      },
      error(err) {
        // 是否允许再次获取数据 开关
        if (canCoinListInfo) {
          setTimeout(() => {
            getCoinListInfo();
          }, delayT);
        }
      }
    });
  }
  // 头部导航  獲取 全部幣種信息
  getCoinListInfo();
  // 头部币种 hover  顯示 全部幣種 数据
  $('#curr-mar').on('mouseenter', function(){
    $('.hover-bg').show();
    // 开启获取数据 开关
    canCoinListInfo = true;
    // 启动 获取数据 方法 实时更新
    getCoinListInfo();
  });
  // 头部币种 hover  隐藏 全部幣種 数据
  $('#curr-mar').on('mouseleave', function(){
    $('.hover-bg').hide();
    // 关闭 获取数据 开关
    canCoinListInfo = false;
  });
  // 渲染最新成交數據
  function setOrder(data) {
    const $dealList = $('#deal-list'); //成交DOM
    const $newestPrice = $('#newest-price');  //最新價
    const $lowestPrice = $('#lowest-price');  //最低價
    const $dayDeal = $('#day-deal');         //24小時成交量
    const $updownChange = $('#updown-change'); //漲跌幅
    const $dayVolumn = $('#day-volumn');  //24h成交额
    if(data){
      //渲染頂部數據
      getUserInfo();   //重獲取最新價格，防止為0
      newestPrice = data.price || 0;   //全局對象最新成交價
      let max = data.max || 0;
      var min = data.min;
      var sum = data.sum;
      var ratio = data.ratio;
      var orders = data.d;
      var price = nonull(data.price) ? beautyNum(data.price, 9) : beautyNum(0, 9);
      var volumn = nonull(data.money) ? data.money : '0';    //成交额
      //最新成交价
      if(data.price && nonull(price)  && price !== ''){
        $newestPrice.html(price.int + "." + price.left + "<span class='low-vision'>" + price.dark + "</span>" + '<span>&nbsp;'+coinTo.toUpperCase() +'</span>');
        $newestPrice.attr('data-tormb', math.multiply(data.price, rmbPrice) || phpLang.LOADING);
        // 最新成交价人民币悬浮框
        addHoverWin($('#newest-price'), 'tormb', 'bottom');
      }
      //最高 价
      $lowestPrice.html(max + '<span>&nbsp;' + coinTo.toUpperCase() +'</span>');
      // if(nonull(min) && min !== ''){
      // }else{
      //   $lowestPrice.html("0" + '<span>&nbsp;'+coinTo.toUpperCase() +'</span>');
      // }
      $lowestPrice.attr('data-tormb', math.multiply(max, rmbPrice));
      // 最低价人民币悬浮框
      addHoverWin($lowestPrice, 'tormb', 'bottom');
      // 成交量
      if(nonull(sum)  && sum !== ''){
        $dayDeal.html(sum + ' ' + coinFrom.toUpperCase());
      }else{
        $dayDeal.html("0" + ' ' + coinFrom.toUpperCase());
      }
      //涨跌幅
      if(nonull(ratio) && ratio!== ''){
        if(ratio < 0){   //跌
          $updownChange.removeClass('up-change down-change').addClass('down-change').html(ratio + "%");  //漲跌幅綠色
          $newestPrice.parent().removeClass("up-arrow down-arrow").addClass('down-arrow');
        }else{          //漲
          $updownChange.removeClass('up-change down-change').addClass('up-change').html(ratio + "%");    //漲跌幅紅色
          $newestPrice.parent().removeClass("up-arrow down-arrow").addClass('up-arrow');
        }
      }else{ //空
        $updownChange.removeClass('up-change down-change').addClass('up-change').html("0 %");
      }
      // 成交额
      if(nonull(volumn)){
        $dayVolumn.attr('data-tormb', math.multiply(rmbPrice, volumn)).html(volumn + '<span>&nbsp;'+coinTo.toUpperCase() +'</span>');
      }
      // 渲染每條成交數據
      if (nonull(orders)){
        const orderDataLen = orders.length;
        const $dealTrs = $dealList.find('tr');
        while($dealTrs.length < orderDataLen){
          $dealList.prepend('<tr></tr>');
          break;
        }
        orders.forEach(function(element, i) {
          const price = nonull(element.p) ? beautyNum(element.p, 9) : beautyNum(0, 9);
          const sum = nonull(element.n) ? beautyNum(element.n) : beautyNum(0);
          var dealTr = `<td>${element.t}</td>
                        <td class="${element.s == "buy" ? 'font-r' : 'font-g'}">${price.int + "." + price.left + "<span class='low-vision'>" + price.dark + "</span>"}</td>
                        <td>${sum.int + "." + sum.left + "<span class='grey-low-vision'>" + sum.dark + "</span>"}</td>
                        `;
                        $dealTrs
                        .eq(i)
                        .attr('data-tormb', math.multiply(rmbPrice, element.p) || phpLang.LOADING)
                        .html(dealTr);
        }, this);

        if($dealList.find('tr').length > orderDataLen){
          if(orderDataLen == 0){
            $dealList.children().remove();
          }else{
            $dealList.find(`tr:gt(${orderDataLen - 1 })`).remove();
          }
        }
        //添加悬浮窗事件
        addHoverWin($('#deal-list tr'), 'tormb', 'left');
        // while($dealTrs.length > orderDataLen){
        //   $dealList.find(`tr:gt(${orderDataLen.length -1})`).remove();
        //   break;
        // }
      }
    } else {
      return;
    }
  }
  // 切换 导航头部币种
  $('#curr-mar').on('click', 'dl', function() {
    window.location.href = "/trade/" + $(this).attr('data-coin');
  });
  /**为委托、成交填充固定数量的tr
    * @param {$(DOM)} $outerWrapper 包围tr的容器、用于计算总高度
    * @param {指定填充的数量} count
    * @returns maxItem为实际填充的数量；指定count时，maxItem = count
  */
  function appendTrItem($outerWrapper, count){
    const tBodyHeight = $outerWrapper.height();               //獲取總行高度
    const trHeight = $outerWrapper.find('tr').height() || tableTrHeight;       //獲取單行高度
    const maxItem = count || Math.ceil(tBodyHeight/trHeight); //得出可填充總條數
    let $trTpl = ""; // `<tr data-orderId=""></tr>`;
    // 便利 tr 模板
    for(var i = 0; i < maxItem; i++ ){
      $trTpl += '<tr data-orderId=""></tr>';
    }
    $outerWrapper.append($trTpl);
    return maxItem;
  }
  // 成交表格填充tr
  const orderItem = [] || appendTrItem($('#deal-list'));
  // 买入
  const buyItem = [] || appendTrItem($('#buyin-list'));
  // 卖出
  const sellItem = [] || appendTrItem($('#sellout-list'));
  // 撤銷訂單
  function calOrder(_this) {
    // 是否点击了 a
    let $this = $(_this);
    const id = $this.parents('tr').attr('data-orderid');
    // 撤销中，不再发起请求
    if (_.indexOf(calIdList, id) > -1) return;
    // 把撤销 id 添加到 撤销列表
    calIdList.push(id);
    // 撤銷中的 按鈕 不允許再次撤銷
    if($this.children('a').hasClass('disabled')) return;
    // 添加禁用属性
    $this.children('a').addClass('disabled');
    // 撤销单
    $.ajax({
      url: "../ajax_trade/trustcancel",
      type: 'post',
      data: {
        id: id,
        coin_from: coinFrom,
        coin_to: coinTo,
        reqToken: $("#hahaha").html().trim()
      },
      success(res) {
        if (parseInt(res.status) === 1) {
          // 记录 已撤销 成功 的订单
          hasCalIdList.push(id);
          // 更新数据
          tradeData('updateOnce');
        } else {
          // 撤單未成功
          if (res.msg) {
            myAlert.show(reg.msg);
          } else {
            myAlert.show(phpLang['CANCEL_FIAL']);
          }
        }
        // 余额不足，允许再次撤销
        if (parseInt(res.status) === 0) {
          // 清空已测单数目 (在渲染列表 清除对应订单)
          _.remove(calIdList, (n) => (n == id));
        }
      },
      error(err){
        // 清空已测单数目
        _.remove(calIdList, (n) => (n == id));
        // 網路錯誤
        myAlert.show(phpLang['NET_ERROR']);
      }
    });
  }
  // 添加悬浮窗事件
  // addHoverWin($('#deal-list tr'), 'tormb', 'left');
  addHoverWin($('#buyin-list tr'), 'tormb', 'left');
  addHoverWin($('#sellout-list tr'), 'tormb', 'left');
  // 渲染最新委托數據
  function setTrust(data) {
    if (data) {

      var $buyList = $('#buyin-list');
      var $sellList = $('#sellout-list');
      var trustBuyArr, trustSellArr;
      trustBuyArr = data.buy;
      trustSellArr = data.sale;

      if (is(trustBuyArr, "Array") && trustBuyArr.length > 0) {
        const buyDataLen = trustBuyArr.length;
        const $buyTrs = $buyList.find('tr');
        // 添加少的的tr
        while($buyTrs.length < buyDataLen) {
          $buyList.append('<tr></tr>');
          break;
        }
        // 循環買入委托數組
        trustBuyArr.forEach(function(element, i) {
          // const price = nonull(element.p) ? beautyNum(element.p, 9) : beautyNum(0, 9);
          const price = element.p ? fromatNum(element.p, 9) : {int: '0', left: '', dark: "00000000"};
          // const amount = nonull(element.n) ? beautyNum(element.n) : beautyNum(0, 9);
          const amount = element.n ? fromatNum(element.n, 9) : {int: '0', left: '', dark: "00000000"};
          // const sum = nonull(element.l) ? beautyNum(element.l) : beautyNum(0, 9);
          const sum = element.l ? fromatNum(element.l, 9) : {int: '0', left: '', dark: "00000000"};
          var trustBuyTr = `  <td>${price.int + "." + price.left + "<span class='low-vision'>" + price.dark + "</span>"}</td>
                              <td>${amount.int + "." + amount.left + "<span class='grey-low-vision'>" + amount.dark + "</span>"}</td>
                              <td>${sum.int + "." + sum.left + "<span class='grey-low-vision'>" + sum.dark + "</span>"}</td>
                            `;
          // html += trustBuyTr;
          $buyList.find('tr')
          .eq(i)
          .attr('data-l', element.l)
          .attr('data-p', element.p)
          .attr('data-n', element.n)
          .attr('data-tormb', `${rmbPrice ? math.multiply(rmbPrice, element.p) : phpLang.LOADING}}`)
          .html(trustBuyTr);
        }, this);
        if($buyList.find('tr').length > buyDataLen){
          if(buyDataLen == 0){
            $buyList.children().remove();
          }else{
            $buyList.find(`tr:gt(${buyDataLen - 1 })`).remove();
          }
        }
      } else {
        // 没有数据时清空
        $buyList.html('');
      }

      //委托卖出  trustSellArr
      if (is(trustSellArr, "Array") && trustSellArr.length > 0){
        // 判断数据是否超过限制条数
        if (trustSellArr.length > 13) {
          trustSellArr = trustSellArr.split(-13);
        }

        const sellDataLen = trustSellArr.length;
        const $sellTrs = $sellList.find('tr');
        // 添加缺少的的tr
        while ($sellTrs.length < sellDataLen) {
          $sellList.append('<tr></tr>');
          break;
        }
        trustSellArr.forEach(function(element, i) {//循環賣出委托數組
          var diff = (trustSellArr.length - sellItem) > 0 ? (trustSellArr.length - sellItem) : 0;  //传来的数组长度与表格可容纳的列的差
          if(i < diff) return;  //数组前diff个不显示(卖出委托根据累积量从大到小排序，返回的数据过多情况下，要保证最小的能显示)
          // const amount = nonull(element.n) ? beautyNum(element.n) : beautyNum(0, 9);
          const amount = element.n ? fromatNum(element.n, 9) : {int: '0', left: '', dark: "00000000"};
          // const price = nonull(element.p) ? beautyNum(element.p, 9) : beautyNum(0, 9);
          const price = element.p ? fromatNum(element.p, 9) : {int: '0', left: '', dark: "00000000"};
          // const sum = nonull(element.l) ? beautyNum(element.l) : beautyNum(0, 9);
          const sum = element.l ? fromatNum(element.l, 9) : {int: '0', left: '', dark: "00000000"};
          var trustSellTr = ` <td >${price.int + "." + price.left + "<span class='low-vision'>" + price.dark + "</span>"}</td>
                              <td >${amount.int + "." + amount.left + "<span class='grey-low-vision'>" + amount.dark + "</span>"}</td>
                              <td >${sum.int + "." + sum.left + "<span class='grey-low-vision'>" + sum.dark + "</span>"}</td>
                            `;
          // html += trustSellTr;
          $sellList.find('tr')
            .eq(i)
            // .attr('data-tormb', `${element.p ? math.multiply(rmbPrice, element.p) : phpLang.LOADING}`)
            .attr('data-tormb', `${rmbPrice ? math.multiply(rmbPrice, element.p) : phpLang.LOADING}`)
            .attr('data-l', element.l)
            .attr('data-p', element.p)
            .attr('data-n', element.n)
            .html(trustSellTr);
          }, this);

          // 刪除超出條目的行數據
          if($sellList.find('tr').length > sellDataLen){
            if(sellDataLen == 0){
              $sellList.children().remove();
            }else{
              $sellList.find(`tr:gt(${sellDataLen - 1 })`).remove();
            }
          }
      } else {
        // 没有数据时清空
        $sellList.html('');
      }
    } else {
      return false;
    }
  }

  // 渲染我的委托
  function setMyTrust(data) {
    if ($('#delegate-list tr').length > 0) {
      $('#delegate-list tr td:last-child').unbind("click");
    }
    //
    var $delegateList = $('#delegate-list');
    if(data && data.length > 0) {
      $('.no-record').hide();
      // 添加狀態
      function addStatus(status){
        var statusObj = {};
        statusObj.classStr = '';
        statusObj.wordStr = '';
        switch (parseInt(status)) {
          case 0:
          statusObj.classStr = "font-r";
          statusObj.wordStr = phpLang["TRUST_STATUS_NO"]; //未成交
            break;
          case 1:
          statusObj.classStr = "font-y";
          statusObj.wordStr = phpLang["TRUST_STATUS_PART"]; //部分成交
            break;
          case 2:
          statusObj.classStr = "font-g";
          statusObj.wordStr = phpLang["TRUST_STATUS_ALL"]; //全部成交
            break;
          case 3:
          statusObj.classStr = "";
          statusObj.wordStr = phpLang["TRUST_STATUS_CANCEL"]; //已撤銷
            break;
          default:
          statusObj.classStr = "font-r";
          statusObj.wordStr = phpLang["TRUST_STATUS_FIND"]; //查詢中
            break;
        }
        return statusObj;
      }
      // var myTrustArr = data.data;
      var myTrustArr = data;
      // 渲染新的 tr
      function newTr(element) {
        return `<tr data-orderId="${element.id}" data-tormb="${math.multiply(rmbPrice, element.price) || phpLang.LOADING}">
        <td>${element.created}</td>
        <td class="${element.flag == 'buy' ? 'font-r' : 'font-g'}">${coinFrom.toUpperCase()}${element.flag == 'buy' ? phpLang["TRADE_BUY_IN"] : phpLang["TRADE_SELL_OUT"]}</td>
        <td>${cutZero(element.price)}</td>
        <td>${cutZero(element.number)}</td>
        <td>${cutZero(element.numberdeal)}</td>
        <td>${cutZero(element.numberover)}</td>
        <td class="${addStatus(element.status).classStr}">${addStatus(element.status).wordStr}</td>
        <td><a href="javascript:void(0)" class="cancel-trust ${(element.status == 3 || element.status == 2 || _.indexOf(calIdList, element.id) > -1) ? "disabled" : ""}">${phpLang["TRADE_CANCEL"]}</a></td>
        </tr>`;
      }
      let trusTrs = $delegateList.find("tr");
      // 表格 tr 條數
      let trusTrsLen = trusTrs.length;
      // 如果列表已經存在 則不渲染 而是更新
      if (trusTrsLen > 0) {
        let overTrs = '';
        // 更新表格數據
        myTrustArr.forEach((element, i) => {
          // 數據 條數 < 現有表格 條數 更新表格
          if (i < trusTrsLen) {
            let $tr = $(trusTrs[i]);
            // 設置 訂單id
            $tr.attr('data-orderId', element.id);
            // 設置 人民幣
            // $tr.attr('data-tormb', fixMulti(rmbPrice, element.price));
            $tr.attr('data-tormb', math.multiply(rmbPrice, element.price));
            // 獲取所有 tr 下 td
            let tds = $tr.children('td');
            // 委託時間
            tds[0].innerHTML = element.created;
            // 委託 類型 （bug 紅色字體：font-r， sell: 綠色字體：font-g）
            tds[1].className = element.flag === 'buy' ? 'font-r' : 'font-g';
            tds[1].innerHTML = `${coinFrom.toUpperCase()}${element.flag == 'buy' ? phpLang["TRADE_BUY_IN"] : phpLang["TRADE_SELL_OUT"]}`;
            // 價格
            tds[2].innerHTML = cutZero(element.price);
            // 委託數量
            tds[3].innerHTML = cutZero(element.number);
            // 成交數量
            tds[4].innerHTML = cutZero(element.numberdeal);
            // 尚未成交
            tds[5].innerHTML = cutZero(element.numberover);
            // 狀態
            tds[6].className = addStatus(element.status).classStr
            tds[6].innerHTML = addStatus(element.status).wordStr;
            // 缓存订单状态码
            let eStatus = parseInt(element.status);
            // 操作 (撤銷訂單 操作) 3:撤销， 2：部分成交， 0：未成交
            if (eStatus === 3 || eStatus === 2 || _.indexOf(calIdList, element.id) > -1) {
              tds.eq(7).find("a").addClass('disabled').html(phpLang["TRADE_CANCEL"]);
            } else {
              tds.eq(7).find("a").removeClass("disabled").html(phpLang["TRADE_CANCEL"]);
            }
          }
          // 數據條數 超出  表格 現有條數
          else {
            overTrs += newTr(element);
          }
          // 臨時記錄 已移除订单号
          let remoIdArr = [];
          // 删除已撤销成功 的 订单
          hasCalIdList.forEach((id) => {
            // 删除已经 撤销 成功 的订单
            if (_.indexOf(calIdList, id)) {
              _.remove(calIdList, cid => (cid === id));
              remoIdArr.push(id);
            }
          });
          // 清除已删除订单
          remoIdArr.forEach((id) => {
            _.remove(hasCalIdList, hid => (hid === id));
          });
        });
        // 把新增加的數據添加到 表格後面
        $delegateList.append(overTrs);
        // 如果表格內容 比 返回的數據 多 則刪除表格
        if (trusTrsLen > myTrustArr.length) {
          // 刪除多餘表格
          let overTrsLength = trusTrsLen - myTrustArr.length;
          for (let okey = 1; okey <= overTrsLength; okey++) {
            // 刪除對應 多餘的  tr
            $(trusTrs[trusTrsLen - okey]).remove();
          }
        }
      }
      // 第一次加載
      else {
        // 渲染列表
        let trs = '';
        myTrustArr.forEach((element, i) => {
          // 構造 新的 tr
          trs += newTr(element);
          if (_.indexOf(calIdList, element.id) > -1) {
          }
          // 清空已撤销的 单 的 id
          if (parseInt(element.status) === 3) {
            // 删除已撤销的订单
            _.remove(calIdList, (id) => (id === (element.id)));
            _.remove(hasCalIdList, (id) => (id === element.id));
          }
        });
        // 更新 委託 表格 數據
        $('#delegate-list').html(trs);
      }
      // 撤銷訂單 重新綁定事件
      $('#delegate-list tr td:last-child').click(function(){
        calOrder(this);
      });
      // 顯示計算人民幣
      addHoverWin($('#delegate-list tr'), 'tormb', 'top');
    } else {
      $('.no-record').show();
      return;
    }
  }
  // 4.獲取用戶資産
  getUserInfo();
  // 5.獲取人民幣彙率
  getRmbRatio();
  // 限制次动画
  let limitOneTime = false;
  // 懸浮窗顯示下單交互信息 @param {str} msg {$(DOM)}， wrapper {cls}， toast-ban(红色)， toast-ok(绿色)
  function toast(msg, wrapper, cls) {
    if (limitOneTime) return;
    limitOneTime = true;
    if (wrapper.find('.toast').length > 0) {
      wrapper.find('.toast').removeClass('toast-ban toast-ok').addClass(cls).html(msg).fadeIn('fast').delay(2000).fadeOut(() => {
        limitOneTime = false;
      });
    } else {
      var $toastObj = $(`<div class="toast">${msg}</div>`);
      wrapper.append($toastObj);
      $toastObj.removeClass('toast-ban toast-ok').addClass(cls).fadeIn('fast').delay(2000).fadeOut(() => {
        limitOneTime = false;
      });
    }
  }
  // 计算 可买 (未优化代码 需优化)
  function computeMaxBuyNum(switchForm, val) {
    if (switchForm === '#form-buy') {
      if (val) {
        const balance = parseFloat($("#buy-availUse").html());
        if (!isNaN(balance) && is(balance, 'Number')) {
          let canBuyNum = balance / val;
          let canBuyNumStr = canBuyNum + '';
          // 不显示 无穷
          if (canBuyNumStr !== 'Infinity') {
            canBuyNum = parseFloat(canBuyNum);
            if (isNaN(canBuyNum)) {
              canBuyNum = '';
            }
            // 限制8位小数
            let noENum = eNumToStr(canBuyNum);
            let dotSub = noENum.indexOf('.');
            canBuyNum = noENum.slice(0, dotSub + 9);
            // $("#buy-availBuy").html(cutZero(canBuyNum));
          } else {
            // $("#buy-availBuy").html('--');
          }
        }
      } else {
        // $("#buy-availBuy").html("--");
      }
    } else {
      if (val) {
        const balance = parseFloat($("#sell-availUse").html());
        // 未登录状态不操作
        if (!isNaN(balance) && is(balance, 'Number')) {
          let canBuyNum = balance * val;
          let canBuyNumStr = canBuyNum + '';
          if (canBuyNumStr !== 'Infinity' && canBuyNumStr !== '0') {
            canBuyNum = parseFloat(canBuyNum);
            if (isNaN(canBuyNum)) {
              canBuyNum = '';
            }
            // 限制8位小数
            let noENum = eNumToStr(canBuyNum);
            let dotSub = noENum.indexOf('.');
            canBuyNum = noENum.slice(0, dotSub + 9);
            $("#sell-availBuy").html(cutZero(canBuyNum));
          } else {
            $("#sell-availBuy").html('--');
          }
        }
      } else {
        $("#sell-availBuy").html("--");
      }
    }
  }
  // 點擊數據填充到下單表格中
  $('.trade-table').on('mousedown', 'tr', function(e){
    return;
    const $this = $(this);
    const $thisTbody = $this.closest('tbody');
    let $priceTd;
    let $amountTd;
    const $inputBuy = $('#form-buy').find('input[data-input="trade"]:eq(0)');
    const $inputBuyAmount = $('#form-buy').find('input[data-input="trade"]:eq(1)');
    const $inputSell = $('#form-sell').find('input[data-input="trade"]:eq(0)');
    const $inputSellAmount = $('#form-sell').find('input[data-input="trade"]:eq(1)');
    //點擊最新委托列表數據
    $priceTd = $this.find('td:eq(0)');
    // 是用累积量
    $amountTd = $this.find('td:eq(2)');
    // 買賣數量
    let price = $this.attr('data-p');
    // 判断是否有小数
    if (is(price, "String") && price.indexOf(".") > -1) {
      price = price.replace(/0+?$/, "");
    }
    // l（不知道是什么）
    let data_l = $this.attr('data-l');
    // 判断是否有小数
    if (is(data_l, "String") && data_l.indexOf(".") > -1) {
      data_l = data_l.replace(/0+?$/, "");
    }
    price = price.replace(/(\.)$/, '');

    $inputBuy.val(price).attr('data-tormb', $this.attr('data-tormb'));
    $inputSell.val(price).attr('data-tormb', $this.attr('data-tormb'));

    // 点击 市場委托 卖出
    if($thisTbody.attr('id') === 'sellout-list') {
      computeMaxBuyNum('#form-buy', price);
      //填充价格并赋予人民币属性
      let sellAvailBuy = $("#buy-availBuy").html().trim();

      // 填充数量  累積量
      if (data_l - sellAvailBuy > 0) {
        $inputBuyAmount.val(sellAvailBuy);
      } else {
        $inputBuyAmount.val(data_l);
      }
      //计算交易额
      countSettle($inputBuy, $inputBuyAmount, $('#buy-settle'));
      //重新计算最大可买(卖)
    }
    // 市場委托买入
    else {
      computeMaxBuyNum('#form-sell', price);

      let sellAvailUse = $("#sell-availUse").html().trim();
      if (data_l - sellAvailUse > 0) {
        $inputSellAmount.val(sellAvailUse);
      } else {
        $inputSellAmount.val(data_l);
      }
      // $inputSellAmount.val(data_l);
      countSettle($inputSell, $inputSellAmount, $('#sell-settle'));
    }
  });
  /**獲取表單的值
   * 輸入合法返回表單的值，錯誤返回false
   * @param {$(formId)} wrapper
   * @returns 表單的值，錯誤返回false
   * {type: 'in',price: '12121', number: '2112', pwdtrade: '11111a'}
   */
  function getInputVal(wrapper){
    let val = {};
    let type = '';
    let inputCorrect = false;
    let inputPrice = $.trim(wrapper.find('input[name$="-price"]').val());
    let inputAmount = $.trim(wrapper.find('input[name$="-amount"]').val());
    let formType = '';
    //
    if (wrapper.attr('id') == 'form-buy') {
      type= "in";
      formType = 'buy';
    } else {
      formType = 'sell';
      type= "out";
    }
    const pattern = /^([0-9]\d*(\.\d{1,8})?|0\.\d{1,8})$/;

    val.price = inputPrice;
    val.number = inputAmount;
    val.type = type;
    val.pwdtrade = transPwd;
    val.coin_from = coinFrom;
    val.coin_to = coinTo;

    // 输入内容是否符合规则 数字
    if(pattern.test(inputPrice) && pattern.test(inputAmount)){
      inputCorrect = true;
    }
    // 不允許輸入0
    if (parseFloat(inputPrice) - 0 <= 0 || parseFloat(inputAmount) - 0 <= 0) {
      inputCorrect = false;
    }
    // 判断输入 交易价格 是否 在 交易规则范围
    if (!buySellRules.priceRule.test(inputPrice)) {
      // 價格必須大於0,小於1,最多xx位小數
      inputCorrect = false;
    }

    // 提示框
    function chkNumTips(msg) {
      toast(msg, wrapper, 'toast-ban');
    }

    // 判断输入 交易数量 是否 在 交易规则范围
    if (parseFloat(inputAmount) - buySellRules.mintrade < 0) {
      inputCorrect = false;
      // 小於最小值提示
      chkNumTips(`${phpLang["MIN_TRADE_NUM"] + parseFloat(buySellRules.mintrade)}`);  //最小交易數量
    } else if (parseFloat(inputAmount) > parseFloat(buySellRules.maxtrade)) {
      inputCorrect = false;
      // 大於最大值提示
      chkNumTips(`${phpLang["MAX_TRADE_NUM"] + parseFloat(buySellRules.maxtrade)}`);  //最大交易數量
    }

    // 交易额
    let settle = $(`#${formType}-settle`).html().trim();
    // 可买（卖） 约
    let availBuy = $(`#${formType}-availBuy`).html().trim();
    // uer 余额
    let canUseMoney = $(`#${formType}-availUse`).html().trim();

    // 卖出 交易额 <= 可买约
    if (formType === 'sell') {
      // 判断 交易额是否 大于 用户 可交易 额
      if (settle - availBuy > 0) {
        chkNumTips(`超出可賣出額，請檢查后重新輸入`);
        inputCorrect = false;
      }
    } else {
      //
      if (settle - canUseMoney > 0) {
        chkNumTips(`超出可買入額，请检查后重新输入`);
        inputCorrect = false;
      }
    }

    return inputCorrect ? val : false;
  }
  // 輸入框實時計算交易額 {$input} _this； {$input siblings} siblingsInput； {$placeholder} settleHolder
  $('input[data-input="trade"]').on('keyup', function(){
    var $this = $(this);
    var $settleHolder = $this.parent().siblings('.settle-g').find('span[id$="-settle"]');
    var $siblingsInput = $this.parent().siblings('.input-g').find('input[data-input="trade"]');
    //添加兑换人民币属性
    // $this.attr('data-tormb', fixMulti(rmbPrice, $this.val()));
    $this.attr('data-tormb', math.multiply(rmbPrice, $this.val()));
    countSettle($this, $siblingsInput, $settleHolder);
  });

  // 防止表單重複提交標記
  var PWD_SUBMIT_BUSY = false;

  // 交易密碼提交
  $('.layer-container').on('submit', '.transPwd-form', function(e) {
    e.preventDefault && e.preventDefault();
    if(PWD_SUBMIT_BUSY) return;
    PWD_SUBMIT_BUSY = true;
    const $this = $(this);
    const $inputPwd = $('#trans-pwd');
    const $tip = $inputPwd.next('p');
    const errorBorder = $inputPwd.parent();
    //
    $.ajax({
      type: 'post',
      url: '/ajax_trade/pwdtradeAuth',
      data: {
        pwdtrade: $('#trans-pwd').val(),
      },
      success: function(res){
        if(res && res.status == 1){
          $tip.hide();
          errorBorder.removeClass('form-err');
          warnTip($inputPwd, "", true);
          $('.close-layer').click();
          $submitting_form.submit();
        }else{
          errorBorder.addClass('form-err');
          $tip.addClass('error-tip').show();
          warnTip($inputPwd, res.msg);
        }
        PWD_SUBMIT_BUSY = false;
      },
      error: function(err){
        warnTip($inputPwd, err.msg);
      }
    })
  })
  // 多次点击只执行一次
  var timer_form = null;
  // 正在提交的表单
  var $submitting_form = null;
  // 提交買入賣出表單
  $('form[data-form="trade"]').on('submit', function(e){
    e.preventDefault && e.preventDefault();
    //计时器不为空就清空
    if (timer_form != null) {
      clearTimeout(timer_form);
    }
    var $this = $(this);
    var data = getInputVal($this);
    const $submitBtn = $this.find("input[type='submit']");
    //200ms内重复点击只提交一次
    timer_form = setTimeout(function() {
      //輸入合法
      if (data) {
        console.table(data);
        data.reqToken = $("#hahaha").html().trim();
        //禁用提交按鈕并顯示loading效果
        $submitBtn.attr('disabled', 'disabled').parent().addClass('is-submitting');
        if (publicKey) {
          try {
            let pukdata = '';
            let jsonStri = JSON.stringify(data);
            let jsonLength = jsonStri.length;
            let keyLength = publicKey.length;
            let asslength = 117;
            // 超出 public key 长度 - 11 分段加密
            if (jsonLength > asslength) {
              pukdata = [];
              // 分段长度
              let partLength = asslength;
              // 分段数量
              let strLeng = Math.ceil(jsonLength /asslength);

              //
              for (var i = 0; i < strLeng; i++) {
                let start = i * partLength;
                let end = (i + 1) * partLength;
                let part = jsonStri.slice(start, end);
                pukdata.push(encrypt.encrypt(part));
              }
            } else {
              pukdata = encrypt.encrypt(jsonStri);
            }

            data = {
              data: pukdata
            };
          } catch (e) {
            // data
            data = data;
            // alert('try again later.');
          }
        }


        $.ajax({
          type: 'POST',
          asyn: true,
          url: '/ajax_trade/setTrust',
          data: data,
          success(res) {
            // 下單成功
            if (res.status == 1) {
              $this.find('input[data-buynum]').val('');
              toast(res.msg, $this, 'toast-ok');
              getUserInfo();
              // getMyTrust();
              // 更新 交易 委托 数据
              tradeData("updateOnce");
              $submitBtn.removeAttr('disabled').parent().removeClass('is-submitting');
              // 重置 下單比例
              $this.find('.track-container .dot').css({left: 0});
              $this.find('.track-container .track').css({backgroundSize: 0});
            }
            //失敗
            else {
              // $submitBtn.removeAttr('disabled').html(oldBtnHTML);
              toast(res.msg, $this, 'toast-ban');
              // 是否需要登錄
              res.data.need_login == 1 ? setTimeout(function(){loginFn()}, 2000) : false;
              // 需要 去設置交易密碼
              if (res.data.need_set_tpwd && res.data.need_set_tpwd === 1) {
                // 设置提示 按钮内容
                myAlert.btnContent('去設置');
                //
                myAlert.show('請先設置交易密碼', () => {
                  window.open('/user?set=tradepwd');
                });
              }
              // 是否需要交易密碼
              if (res.data.need_trade_pwd && res.data.need_trade_pwd === 1) {
                pwdFn();
                $submitting_form = $this
              }
              // 是否需要實名
              res.data.need_real_auth === 1 ? setTimeout(function(){location.href="/user/realinfo"}, 2000) : false;
              // 隐藏 加载中
              $submitBtn.removeAttr('disabled').parent().removeClass('is-submitting');
            }
          },
          error(err) {
            // 網路故障
            toast(phpLang['NET_ERROR'], $this, 'toast-ban');
            $submitBtn.removeAttr('disabled').parent().removeClass('is-submitting');
          }
        })
      }
      //輸入不合法
      else {
        // 价格为 0
        if ($this.find('input[name$="-price"]').val() - 0 <= 0) {
          toast('請輸入價格', $this, 'toast-ban');
        }
        // 數量為零
        else if ($this.find('input[name$="-amount"]').val() - 0 <= 0) {
          toast('請輸入數量', $this, 'toast-ban');
        } else {
          //請輸入數字,小數點後最多8位
          toast(phpLang['INPUT_NEED_VALID'], $this, 'toast-ban');
        }
        $submitBtn.removeAttr('disabled').parent().removeClass('is-submitting');
      }
    }, 200);
  });

  // 点击轨道设置比例
  $('form[data-form="trade"] .track-container').on('mousedown', function(e){
    var $this = $(this),
    $thisTrack =  $this.find('.track'),
    mouseX = e.pageX,
    mouseY = e.pageY,
    trackLeft = $thisTrack.offset().left,
    setLeft = mouseX - trackLeft - rangeObj.dotLen / 2,
    diffWid = rangeObj.trackLen - rangeObj.dotLen / 2;
    const $thisForm = $thisTrack.closest('form[data-form="trade"]');
    const availUse = $thisForm.siblings('.amount-box').find('span[id$="-availUse"]').text();
    const availBuy = $thisForm.siblings('.amount-box').find('span[id$="-availBuy"]').text();
    const $firstInput = $thisForm.find('.input-g:eq(0)').find('input[data-input="trade"]');
    const $lastInput = $thisForm.find('.input-g:eq(1)').find('input[data-input="trade"]');
    const $settleHolder = $thisForm.find('span[id$="settle"]');
    $thisTrack.find('.dot').css('left', setLeft + 'px');
    var currentVal = parseInt((($this.find('.dot').position().left + rangeObj.dotLen / 2) / rangeObj.trackLen) * 100);
    var fixedVal = rangeObj.step * Math.round(currentVal / rangeObj.step);
    $this.next('p').html(fixedVal + '%');
    $thisTrack.css({
      'backgroundSize': (currentVal - rangeObj.min) * 100 / (rangeObj.max - rangeObj.min) + '% 100%'
    });
    if(!Number(availUse)){
      return;
    }else{
      //得出當前比例
      var ratio = $this.next('p').text();
      //填充對應比例的下單數量
      var ratioVal = ratio.substring(ratio.indexOf('%'), -1);
      //买入
      if($thisForm.attr('id') === 'form-buy'){
        // $firstInput.val() == '' ? $this.off('mousemove') : false; //买入表单价格未输入时不允许滑动下单比例
        if(isNaN(fixMulti((ratioVal/100), availBuy))){
          $lastInput.val('');
        }else{
          var lastInputVal = math.eval(`${ratioVal} / 100 * ${availBuy}`);
          // eToNum(fixMulti((ratioVal/100), availBuy));
          $lastInput.val(cutFixedNum(lastInputVal));
        }
      }
      //卖出
      else {
        if (isNaN(fixMulti((ratioVal/100), availUse))) {
          $lastInput.val('');
        } else {
          var lastInputVal = math.eval(`${ratioVal} / 100 * ${availUse}`);
          // eToNum(fixMulti((ratioVal/100), availUse));
          $lastInput.val(cutFixedNum(lastInputVal));
        }
      }
      //計算交易額
      countSettle($lastInput, $firstInput, $settleHolder);
    }
    countSettle($lastInput, $firstInput, $settleHolder);
  });
  // 输入数量，计算交易额
  $('#form-buy input[data-buyprice="change"]').on('input', function() {
    let $this = $(this);
    let val = $(this).val().trim();
    computeMaxBuyNum('#form-buy', val);
  });
  $('#form-sell input[data-buyprice="change"]').on('input', function() {
    let val = $(this).val().trim();
    computeMaxBuyNum('#form-sell', val);
  });
  // 获取 规则
  const rulesList = $("#form-buy");
  let priceFloat = 8;
  let priceRule = rulesList.data('pricefloat');
  if (priceRule) {
    priceFloat = parseInt(priceRule);
  }
  // 买卖交易规则
  const buySellRules = {
    // /^([0](?:\.[0-9]{0,6}))$/
    priceRule: new RegExp(`^([0-9]*(?:\\.[0-9]{0,${priceFloat - 1}}[1-9])?)$`),
    // 价格 小数位数
    priceDotNum: parseFloat(rulesList.data('pricefloat')),
    // 数量小数位
    numberfloat: parseFloat(rulesList.data('numberfloat')),
    // 最大交易 数量
    maxtrade: rulesList.data('maxtrade'),
    // 最小交易 数量
    mintrade: rulesList.data('mintrade')
  }
  // 买入价 输入 规则校验 只校验 小数位数
  function buyPriceRule() {
    let elem = $(this);
    let inputVal = elem.val().trim();
    let afterRuleNum = '';
    // 提示框
    function chkNumTips(msg) {
      toast(msg, elem.parents('form[data-form]'), 'toast-ban');
    }

    if (inputVal) {
      if (parseFloat(inputVal) - 0 <= 0) {
        return chkNumTips('請輸入價格');
      }
      let showTips = false;
      // 校验价格小数位数
      if (!buySellRules.priceRule.test(inputVal)) {
        // 價格必須大於0,小於1,最多xx位小數
        showTips = true;
      }

      // 小于 最小值
      // if (buySellRules.mintrade - 0 > 0 && inputVal - buySellRules.mintrade < 0) {
      //   showTips = true;
      // } else if (buySellRules.maxtrade - 0 > 0 && inputVal - buySellRules.maxtrade > 0) {
      //   showTips = true;
      // } else {
      //   showTips = false;
      // }
      if (showTips) {
        // let minPrice = buySellRules.mintrade.replace(/(0+)$/, '');
        // let maxPrice = buySellRules.maxtrade.replace(/(0+)$/, '');
        // tipsMsg = tipsMsg.replace(/(\$max)/, maxPrice);
        let tipsMsg = phpLang["FLOATNUM_B"].replace(/(\$float)/, priceFloat);
        chkNumTips(tipsMsg);
      }
    }
  }
  // 买卖数量 规则校验
  function buySellNumRule() {
    let elem = $(this);
    let inputVal = elem.val().trim();
    let afterRuleNum = '';
    // 小数点位置
    let dotSub = inputVal.indexOf('.');
    // 校驗錯誤提示框
    function chkNumTips(msg) {
      toast(msg, elem.parents('form[data-form]'), 'toast-ban');
    }
    if (inputVal) {
      if (parseFloat(inputVal) - 0 <= 0 ) {
        chkNumTips(`請輸入交易數量`);
      }
      // 校验 最小值
      // else if (parseFloat(inputVal) - buySellRules.mintrade < 0) {
      //   // 小於最小值提示
      //   chkNumTips(`${phpLang["MIN_TRADE_NUM"] + parseFloat(buySellRules.mintrade)}`);  //最小交易數量
      // }
      // else if (parseFloat(inputVal) > parseFloat(buySellRules.maxtrade)) {
      //   // 大於最大值提示
      //   chkNumTips(`${phpLang["MAX_TRADE_NUM"] + parseFloat(buySellRules.maxtrade)}`);  //最大交易數量
      // }
      else if (dotSub > -1) {
        // 获取小数位部分
        let smallNum = inputVal.slice(dotSub + 1);
        // 小數長度判斷 是否符合小數長度範圍
        if (smallNum.length > buySellRules.numberfloat) {
          // 超出小數位數提示
          afterRuleNum = inputVal.slice(0, dotSub + buySellRules.numberfloat + 1);
          let tipsMsg = phpLang["FLOATNUM_B"].replace(/(\$float)/, buySellRules.numberfloat);
          chkNumTips(tipsMsg);
          // chkNumTips(`${phpLang['AMOUNT_MAX'] + buySellRules.numberfloat + phpLang['FLOATNUM_B']}`);  //最大XX位小數
          elem.val(afterRuleNum);
        }
      }
    }
  }
  // 买入 输入 价格 规则 校验
  $("#form-buy input[data-buyprice]").on('blur',buyPriceRule);
  // 买入 输入 数量 规则 校验
  $("#form-buy input[data-buynum]").on('blur',buySellNumRule);

  // 卖出 输入 价格 规则 校验
  $("#form-sell input[data-buyprice]").on('blur',buyPriceRule);
  // 买入 输入 数量 规则 校验
  $("#form-sell input[data-buynum]").on('blur',buySellNumRule);
  // 限制输入类型
  $("form input[type='text']").on('input', function() {
    const reg = /^(\d+|\d+(?:\.\d*)?)$/;
    //
    let val = $(this).val();
    if (val) {
      if (!reg.test(val)) {
        let floaNum = parseFloat(val);
        val = isNaN(floaNum) ? "" : floaNum;
        $(this).val(val);
      }
    }
    if (val.length > 16) {
      val = val.slice(0, 16);
      $(this).val(val);
    }
  });
  // 输入框聚焦显示人民币悬浮窗
  $('form[data-form="trade"] input[name$="-price"]').focus(function(){
    const $this = $(this);
    addKeyUpWin($this, 'tormb', 'left');
  });

  // 点击 卖出 余额
  $('#sell-availUse').click(function() {
    let availUse = $(this).html().trim();
    $("#form-sell input[name='sellOut-amount']").val(availUse);
    let sellPrice = $(`#form-sell input[name='sellOut-price']`).val();
    console.log(sellPrice);
    if (sellPrice) {
      let settle = math.multiply(sellPrice, availUse) + '';
      if (settle.indexOf('.') > 0) {
        let dot = settle.indexOf('.');
        settle = settle.slice(0, dot + 9);
      }
      $("#sell-settle").html(settle);
    }
  });

  // 点击 卖出 余额
  $('#buy-availUse').click(function() {
    let availUse = $(this).html().trim();
    let price = $("#form-buy input[name='buyIn-price']").val();
    if (price && parseFloat(price)) {
      let buyNum = availUse / price;
      let regFloatNum = /^(([0-9]+)\.[0-9]+)$/;
      if (regFloatNum.test(buyNum)) {
        buyNum += '';
        let dot = buyNum.indexOf('.');
        buyNum = buyNum.slice(0, dot + 9);
      }
      $("#form-buy input[name='buyIn-amount']").val(buyNum);
      let settle = math.multiply(price, buyNum) + '';
      if (settle.indexOf('.') > 0) {
        let dot = settle.indexOf('.');
        settle = settle.slice(0, dot + 9);
      }
      $("#buy-settle").html(settle);
    }
  });

  // 切换交易区
  $("#curr-mar").click(function() {
    // window.location.href = '';
  });

  //聊天室
//   const ws = new WebSocket("ws://192.168.1.188:2347");
//
//   const phone = $('.getPhones').text();
//
//   //进入聊天室聊天室未弹出时计算消息数
//   var mesNum = 0;
// //聊天框弹出
//   $('.talking_down').click(function(event) {
//     mesNum = 0;
//     sessionStorage.mesNum = 0;
//     $('.talking_down span').text('0');
//     $('.talking_up').fadeIn();
//     $('.talking_down').fadeOut();
//     sessionStorage.status = 'block';
//     oldHtml();
//   });
// //聊天框收起
//   $('.talking_sj').click(function(event) {
//     $('.talking_up').fadeOut();
//     $('.talking_down').fadeIn();
//     sessionStorage.status = 'none';
//   });
// //点击退出
//   $('.talking_close').click(function(event) {
//     $('.login_btn').fadeIn();
//     $('.talking_inner').fadeOut();
//     $('.talking_text').fadeOut();
//     $('.talking_footer').fadeOut();
//     //清空数据
//     $('.talking_inner ul').html('');
//     sessionStorage.all = [];
//     sessionStorage.out = 'true';
//   });
// //点击登录
//   $('.login_btn').click(function(event) {
//     $('.login_btn').fadeOut();
//     $('.talking_inner').fadeIn();
//     $('.talking_text').fadeIn();
//     $('.talking_footer').fadeIn();
//     sessionStorage.all = [];
//     sessionStorage.out = 'false';
//   });
// //发送
//   $('.sent_btn').click(function(event) {
//     let msg = $('.talking_text textarea').val();
//     let reg = /^[\u4E00-\u9FA5a-zA-Z0-9_,，.。|！!~·%^&?？;、(=)(（）)(+)\s：:@(*)(《》)($￥)]+$/u;
//     // let reg = /<\/?[^>]*>|<\/?[^>]*/;
//     if (reg.test(msg)) {
//       msg = msg.replace(/&gt|&lt/g,'');
//     }
//     else {
//       msg = msg.replace(/<|>|\//g,'');
//     }
//     console.log(msg);
//     if (msg != '') {
//       ws.send(phone+','+msg);
//       $('.talking_text textarea').val('');
//       mesAjax({phone, msg});
//     }
//     else {
//       $('.talking_text textarea').val('');
//       return  false;
//     }
//   });
//   document.onkeydown = function(event) {
//     if (event.keyCode == 13) {
//       event.preventDefault();
//       $('.sent_btn').click();
//     }
//   }
// //点击头像
//   $('body').on('click', '.icon_peo', function() {
//     let phone = $(this).next('.peo_mes').find('.peo_phone').text();
//     sentMes(phone);
//   })
// //点击电话
//   $('body').on('click', '.peo_phone', function() {
//     let phone = $(this).text();
//     sentMes(phone);
//   })
//   //艾特对方手机号
//   function sentMes(phone) {
//     $('.talking_text textarea').val('@'+phone+"  ");
//   }
//
//   var talking_mes = [];
//   //判断刷新页面之前聊天室为弹出还是收起
//   if (sessionStorage.status == 'block') {
//     $('.talking_inner ul').text('');
//     if (sessionStorage.out && sessionStorage.out == 'true') {
//       //以判断为登出
//       $('.login_btn').show();
//       $('.talking_inner').hide();
//       $('.talking_text').hide();
//       $('.talking_footer').hide();
//     }
//     else {
//       if (sessionStorage.all) {
//         //填充历史数据
//         oldHtml();
//       }
//     }
//     $('.talking_up').show();
//     $('.talking_down').hide();
//   }
//   else {
//     //填入消息数
//     if (sessionStorage.mesNum) {
//       const mesNum = JSON.parse(sessionStorage.mesNum);
//       $(".talking_down span").text(mesNum);
//     }
//   }

  // //重新加载内容
  // function oldHtml() {
  //   if (sessionStorage.all) {
  //     const historyData = JSON.parse(sessionStorage.all);
  //     talking_mes = historyData;
  //     let historyHtml = '';
  //     historyData.forEach((el, item) => {
  //       historyHtml += talkingHtmlFn(el);
  //     });
  //     $('.talking_inner ul').html(historyHtml);
  //     // setTimeout(() => {
  //     //   const talking_height = $('.talking_inner ul').height();
  //     //   const talking_inner = $('.talking_inner').height();
  //     //   $('.talking_inner').scrollTop(talking_height-talking_inner);
  //     // });
  //     heightGet().then((value) => {
  //       const talking_height = $('.talking_inner ul').height();
  //       const talking_inner = $('.talking_inner').height();
  //       $('.talking_inner').scrollTop(talking_height-talking_inner);
  //     })
  //   }
  // }
  // function heightGet() {
  //   return new Promise(function(resolve, reject){
  //     setTimeout(resolve)
  //   });
  // }
  // ws.onopen = function () {
  //     // console.log("连接成功");
  // };
  //
  // ws.onmessage = function (e) {
  //     // console.log(e.data);
  //     if (e.data) {
  //       //聊天室是否弹出
  //       const talking_display = $('.talking_up').css('display');
  //       if (!sessionStorage.out || sessionStorage.out == 'false') {
  //         const userName = e.data.split(':')[0];
  //         const content = e.data.split(':')[1];
  //         const now_mes = {userName:userName, content:content};
  //         const talkingHtml = talkingHtmlFn(now_mes);
  //
  //
  //         $('.talking_inner ul').append(talkingHtml);
  //         //储存对话消息
  //         talking_mes.push(now_mes);
  //         sessionStorage.all = JSON.stringify(talking_mes);
  //         if (talking_display == "none") {
  //           mesNum ++;
  //           if (mesNum > 99) {
  //             mesNum ='99+';
  //           }
  //           sessionStorage.mesNum = JSON.stringify(mesNum);
  //           $(".talking_down span").text(mesNum);
  //         }
  //         else {
  //           mesNum = 0;
  //         }
  //       }
  //       sessionStorage.status = talking_display;
  //       let innerH = $('.talking_inner').height();
  //       let talkingH = $('.talking_inner ul').height();
  //       $('.talking_inner').scrollTop(talkingH-innerH);
  //     }
  //     else {
  //       return false;
  //     }
  // };
  // //后台保存消息
  // function mesAjax(now_mes) {
  //   $.ajax({
  //     url: 'Ajax_Push/push',
  //     type: 'POST',
  //     data: {
  //       mo: now_mes.phone,
  //       themessage: now_mes.msg
  //     },
  //     success(res) {
  //
  //     },
  //     error(err) {
  //
  //     }
  //   })
  // }
  // //组成聊天内容
  // function talkingHtmlFn(mes){
  //   return `<li>
  //     <div class="icon_peo">
  //       <img src="/imgs/people_default.png" alt="">
  //     </div>
  //     <div class="peo_mes">
  //       <span class="peo_phone">${mes.userName.slice(0,3)}****${mes.userName.slice(7,11)}</span>
  //       <p>${mes.content}</p>
  //     </div>
  //     <div class="clear"></div>
  //   </li>`
  // }



  // 交易 场切换
  // new Vue({
  //   data: {
  //     // 当前交易场
  //     curArea: '',
  //     // 所有交易场
  //     areas: {},
  //     hasArea: false
  //   },
  //   mounted() {
  //     this.getAreaData();
  //   },
  //   methods: {
  //     // 切换 交易场
  //     changeArea(areaName) {
  //       this.curArea = areaName;
  //     },
  //     // 切换币
  //     changeCoin(coin) {
  //       window.location.href = `/trade/${coin}`;
  //     },
  //     // 获取 交易场 数据
  //     getAreaData() {
  //       $.ajax({
  //         url: '/ajax_market/getAllQuote',
  //         type: 'GET',
  //         success: ({status, data}) => {
  //           let intStatus = parseInt(status);
  //           if (intStatus === 1) {
  //             this.hasArea = true;
  //             this.areas = Object.assign({}, data);
  //             this.resetTHead();
  //           }
  //           setTimeout(() => {
  //             this.getAreaData()
  //           }, delayT);
  //         },
  //         error: () => {
  //           setTimeout(() => {
  //             this.getAreaData();
  //           }, delayT);
  //         }
  //       });
  //     },
  //     // 重置 表头 样式
  //     resetTHead() {
  //       let tbodys = this.$refs.element;
  //       let thead = this.$refs.thead;
  //       let theadTds = '';
  //       // 表头
  //       if (thead) {
  //         theadTds = thead.children;
  //       }
  //       // 表格
  //       if (tbodys) {
  //         let firstTr = tbodys[0].firstChild;
  //         let tds = [...firstTr.children];
  //         if (theadTds) {
  //           tds.forEach((td, key) => {
  //             let tdStyle = window.getComputedStyle(td);
  //             theadTds[key].style.minWidth = tdStyle.width;
  //           })
  //         }
  //       }
  //     }
  //   }
  // }).$mount('#allMar');
  console.log('tradeVue');
});
