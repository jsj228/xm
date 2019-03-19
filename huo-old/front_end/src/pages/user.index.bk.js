import '@/styles/common/index.scss';
import '@/styles/userCenter/index.scss';
import "@/styles/userCenter/userDefault.scss";
import '@/styles/userCenter/sureDialog.scss';
import "@/styles/userCenter/leftNav.scss";
import nav from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import http from '@/tools/http';
import is from '@/tools/is';
import DialogBox from '@/tools/dialogBox/dialogBox';
import dialog from '@/tools/dialog';
import HoverWin from '@/components/hoverWin';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import resetPwd from '@/pcAndMob/resetTradePwd';
import talking from '@/tools/talking/talking';
//币币弹窗
import "@/styles/common/userAlert.scss";
import userAlert from '@/components/userAlert';


// process.env.NODE_ENV
$(document).ready(function() {
  //打開聊天室接受數據功能
  talking();
  // 切換 請求處理 語言包
  chkhttpLang("#baseLang", http);
  let phpLang = getLanguagePack() || {
    sureBtn: '確定',
    tradePwdti: '您尚未設置交易密碼，請先設置！（交易密碼是在多比進行交易時需要輸入的密碼，不同于登錄密碼。為確保您的財産安全，請牢記交易密碼，防止丟失!）',
    setTradePwd: "設置交易密碼",
    setTradePwdPHold: "請輸入交易密碼",
    setPwdAgain: "再次輸入密碼",
    setPwdAgainPHold: "請確認交易密碼",
    saveTradePwd: "保存",
    openSwitch: "開",
    closedSwitch: "關",
    saveSucc: "保存成功！",
    saving: "保存",
    warnTips: {
      pwd1: {
        msg: "請輸入您的交易密碼",
        err: "交易密碼長度需在6-16個字符之間"
      },
      pwd2: {
        msg: "請輸入您的交易密碼",
        err: "重複密碼長度需在6-16個字符之間",
        noSame: "兩次輸入的密碼不一致"
      }
    }
  };
  nav();
  activeNav();
  let hrefNow = window.location.search;
  //從法幣跳過來
  if (hrefNow.indexOf('platform') != -1) {
    $('.user_bibi').hide();
    $('.user_fabi').show();
    //总资产
    $('#allAssets').hide();
    $('#yibiallAssets').show();
    $('[data-account="bi"]').removeClass('user_account_active');
    $('[data-account="fa"]').addClass('user_account_active');
    $('[data-id="tableNoData"]').show();
    setTableStyle(1);
  }
  // 提交成功 提示彈框
  function postSucceDialog(msg, callback) {
    const template = `<div class="au_dialog">
      <u class="close_btn" data-diclose="btn"></u>
      <div>
        <p>${msg}</p>
        <button class="submit_btn" data-diclose="btn">${phpLang.sureBtn}</button>
      </div>
    </div>`;
    // 清空顯示內容
    dialog.html(template);
    // dialog.html()
    dialog.css({ width: "452px", height: "274px", borderRadius: "8px", position: "relative" });
    dialog.show(true);
    $('[data-diclose="btn"]').click(function() {
      // 重定向
      if (callback && is(callback, "Function")) {
        callback();
      }
      dialog.hide(true);
    });
  }
  // 彈框 模板
  const myBox = new DialogBox();
  const setTradePassWordTpls = `<div id="surePwd" class="trade-pwd"><div class="dialog-title">${phpLang.setTradePwdTitle}</div>
  <div class="top-tips">${phpLang.tradePwdti}</div>
  <div class="form-tab first-mar">
    <span>${phpLang.setTradePwd}</span>
    <input type="password" minlength="6" maxlength="20" name="pwd1" placeholder="${phpLang.setTradePwdPHold}" />
    <p class="warn-tips-lf" data-tips="pwd1"></p>
  </div>
  <div class="form-tab">
    <span>${phpLang.setPwdAgain}</span>
    <input type="password" minlength="6" maxlength="20" name="pwd2" placeholder="${phpLang.setPwdAgainPHold}" />
    <p class="warn-tips-lf" data-tips="pwd2"></p>
  </div>
  <div class="form-tab"><button class="box-submit-btn" id="tradePassWord">${phpLang.saveTradePwd}</button><u class="loading-svg" data-loading="svg" style="display:none"></u></div></div>`;
  // 設置交易密碼方法
  function setTradePwd() {
    myBox.show(setTradePassWordTpls, function() {
      // 獲取 input 框值
      let isBusy = false;
      let warnTips = {
        pwd1: {
          msg: "請輸入您的交易密碼",
          err: "交易密碼長度需在6-20個字符之間"
        },
        pwd2: {
          msg: "請輸入您的交易密碼",
          err: "重複密碼長度需在6-20個字符之間",
          noSame: "兩次輸入的密碼不一致"
        }
      };

      if (phpLang) {
        warnTips = phpLang.warnTips;
      }
      const inputReg = /^([0-9a-zA-Z]{6,20})$/;
      //
      function chkInput(val) {
        let result = true;
        if (val.length < 6 || val.length > 20) {
          result = false;
        }
        if (!inputReg.test(val)) {
          result = false;
        }
        return result;
      }
      $("#surePwd input").on('focus', function() {
        const $input = $(this);
        $input.siblings('p[data-tips]').removeClass('err-tips');
        const name = $input.attr("name");
        $input.siblings('p[data-tips]').html(warnTips[name].msg).show();
      });
      $("#surePwd input").on('blur', function() {
        const $input = $(this);
        const name = $input.attr('name');
        if ($input.val().trim()) {
          if (!chkInput($input.val())) {
            $input.siblings('p[data-tips]').html(warnTips[name].err).addClass('err-tips').show();
          } else if (name === 'pwd2') {
            if ($input.val() !== $('input[name="pwd1"]').val()) {
              $input.siblings('p[data-tips]').html(warnTips[name].noSame).addClass('err-tips').show();
            }
          } else {
            $input.siblings('p[data-tips]').hide();
          }
        } else {
          $input.siblings('p[data-tips]').hide();
        }
      });

      $("#surePwd input").on('input', function() {
        let $inp = $(this);
        let val = $inp.val();
        if (/\s+/.test(val)) {
          val = val.replace(/\s/, '');
          $inp.val(val);
        }
      });
      //
      $("#tradePassWord").click(() => {
        if (isBusy) return;
        isBusy = true;
        $(this).addClass('on-submiting').text(`${phpLang.saving}...`);
        const postData = {};
        let isComplete = true;
        const inputs = [...myBox.content.find('input')];
        // 驗證是否為空
        inputs.forEach((input) => {
          const $input = $(input);
          const name = $input.attr('name');
          const val = $input.val().trim();
          if (val) {
            if (!chkInput(val)) {
              isComplete = false;
            }
            postData[name] = val;
          } else {
            $(input).siblings('p[data-tips]').html(warnTips[name].err).addClass('err-tips').show();
            isComplete = false;
            isBusy = false;
          }
        });
        // 驗證是否 一致
        if (postData['pwd1'] !== postData['pwd2']) {
          isComplete = false;
          isBusy = false;
          $('[data-tips="pwd"]').show();
        }
        // 通過校驗
        if (isComplete) {
          $('.loading-svg').show();
          http({
            url: '/ajax_user/setTradePwd',
            method: "POST",
            data: postData,
            success(data) {
              isBusy = false;
              if (parseInt(data.status) === 1) {
                // '保存成功！'
                myBox.hide();
                postSucceDialog(phpLang.saveSucc, function() {
                  // 清除路由參數 刷新頁面
                  window.location.href = '/user';
                });
              }
              else {
                $('.loading-svg').hide();
              }
              //
            },
            error(err) {
              $('.loading-svg').hide()
              isBusy = false;
              if (err) {
                myBox.hide();
              }
            }
          });
        }
      });
    });
  }
  // 交易中心跳過來，設置交易密碼
  if (location.href.indexOf('set=tradepwd') > -1) {
    if ($('[data-tradepwd="pwd"]').length > 0) {
      setTradePwd();
    }
    if ($('[data-reset="tradepwd"]')) {
      resetPwd.setTradePwd = true;
    }
  }
  if (location.href.indexOf('Tset=pwd') > -1) {
    setTradePwd();
  }
  // 設置交易密碼
  $('[data-tradepwd="pwd"]').click(function() {
    // 交易密碼 彈框
    setTradePwd();
  });
  // 重置交易密碼
  $('[data-reset="tradepwd"]').click(function() {
    // 交易密碼 彈框
    resetPwd.setTradePwd = true;
  });

  // 重置表格顯示
  function setTableStyle(tableNum) {
    const tarEle = $(`[data-table="${tableNum}"]`);
    // copy thead
    if (tarEle.find(`[data-thead='${tableNum}']`).length === 0) {
      // 創建 head div 和 table
      const myTbHead = document.createElement('div');
      myTbHead.id = "tHead";
      myTbHead.className = "t-head";
      myTbHead.setAttribute("data-thead", tableNum);
      myTbHead.innerHTML = "<table></table>";
      // 添加到 當前表格前面
      tarEle.eq(0).prepend(myTbHead);
      // 複制 表頭到 新建的 表格
      tarEle.find(`[data-thead="${tableNum}"] table`).append(tarEle.find("[data-tbody] thead").clone());
      // set style
      const tbodyTr = tarEle.find("[data-tbody] tbody tr").eq(0);
      const tbodyTds = [...tbodyTr.find("td")];
      const tHeadTds = tarEle.find("[data-thead] thead tr").eq(0).find("td");
      tbodyTds.forEach((td, key) => {
        const bodyTdWidth = $(td).css("width");
        tHeadTds.eq(key).css("width", bodyTdWidth);
      });
    } else {
      let tbody = tarEle.find('[data-tbody] tbody');
      [...tbody].forEach((tb) => {
        if ($(tb).is(":visible")) {
          tbody = $(tb);
        }
      });
      const tbodyTr = tbody.find("tr:eq(0)");

      const tbodyTds = [...tbodyTr.find("td")];
      const tHeadTds = tarEle.find("[data-thead] thead tr").eq(0).find("td");
      tbodyTds.forEach((td, key) => {
        const bodyTdWidth = window.getComputedStyle(td).width;
        tHeadTds.eq(key).css("width", bodyTdWidth);
      });

    }
    // 滾動情況
    if (parseInt($("#tBody").css("height")) < parseInt($("#tBody table").css("height"))) {
      $("#tHead table td:last-child").css("paddingRight", "45px");
    }
  }
  // 初始化
  setTableStyle(1);
  setTableStyle(2);

  // 顯示有資金幣種  開 關 操作
  function isShowTable(sw, coinNowType) {
    if (sw) { // 開 操作
      $("#swichTable .no-val").show();
    } else { // 關 操作
      $("#swichTable .no-val").hide();
    }
  }
  // 開關按鈕
  $("[data-swich='btn']").click(function () {
    const onState = { right: "33px" };
    const offState = { right: "2px" };
    let coinNowType = $('.user_account_active').attr('data-account');
    if ($(this).attr("data-state") === 'on') {
      isShowTable(false, coinNowType);
      // 開
      $(this).find('span').text(phpLang.openSwitch);
      $(this).css({ background: "#01b21a", textAlign: "right" }).find('b').animate(onState, () => {
        $(this).attr("data-state", "off");
      });
    } else {
      isShowTable(true, coinNowType);
      // 關
      $(this).find('span').text(phpLang.closedSwitch);
      $(this).css({ background: "#d2d2d2", textAlign: "left" }).find('b').animate(offState, () => {
        $(this).attr("data-state", "on");
      });
    }
  });
  //法币 币币 列表切换
  $('[data-account]').click(function(event) {
    $('.user_account_active').removeClass('user_account_active');
    let coinType = $(this).attr('data-account');
    let switchType = $("[data-swich='btn']").attr("data-state");
    //币种type切花
    if (coinType === 'bi') {
      $('.user_bibi').show();
      $('.user_fabi').hide();
      //总资产
      $('#allAssets').show();
      $('#yibiallAssets').hide();
      $('[data-id="tableNoData"]').hide();
    } else {
      $('.user_bibi').hide();
      $('.user_fabi').show();
      //总资产
      $('#allAssets').hide();
      $('#yibiallAssets').show();
      $('[data-id="tableNoData"]').show();
    }
    setTableStyle(1);
    $(this).addClass('user_account_active');
  });
  //点击转至xx
  $("#swichTable [data-account-btn]").click(function() {
      let btnType = $(this).attr('data-account-btn');
      let maxRange = $(this).attr('data-coinmax');
      let coinName = $(this).parents('tr').find('.coin_name').text().toLowerCase();
      let priceOverfa = $('.'+coinName+"_name").parents('tr.fabi').find('.price_over').text();
      let priceOverbi = $('.'+coinName+"_name").parents('tr.bibi').find('.price_over').text();
      let uid = $(this).attr('data-uid');
      let max = $(this).attr('data-max');
      if (btnType === 'bi') {
        //币币到法币
        let alertData = {
          coinName: coinName.toLocaleUpperCase(),
          uid: uid,
          priceOverbi : priceOverbi,
          priceOverfa: priceOverfa,
          maxRange: maxRange,
          max: max,
          opt_type: 'out'
        }
        userAlert.showUserAlert(alertData);
      } else {
        //法币到币币
        let alertData = {
          coinName: coinName.toLocaleUpperCase(),
          uid: uid,
          priceOverbi : priceOverbi,
          priceOverfa: priceOverfa,
          maxRange: maxRange,
          max: max,
          opt_type: 'in'
        }
        userAlert.showUserFaAlert(alertData);
      }
  });
  // 點擊 提幣按鈕 轉跳
  function coinOut($this) {
    const coin = $($this).data("coin");
    $("#coinOutForm input[name='coin']").val(coin);
    $("#coinOutForm").submit();
  }
  $("#swichTable [data-coinout='coinout']").click(function() {
    coinOut(this);
  });
  $("#notUseCoin [data-coinout='coinout']").click(function() {
    coinOut(this);
  });
  //法币账户 充币跳转
  $("#swichTable [data-coinout='faout']").click(function() {
    let url = $(this).attr('data-url');
    // window.location.href = url;
    window.open(url)
  });
  // 點擊充幣按鈕
  function coinIn($this) {
    const coin = $($this).data("coin");
    window.location.href = `/user/coinin?coin=${coin}`;
  }
  $("#swichTable [data-coinin]").click(function() {
    coinIn(this);
  });
  $("#notUseCoin [data-coinin]").click(function () {
    coinIn(this);
  });
  // 显示人民币
  const myHover = new HoverWin($('[data-tormb]'), 'tormb');

  myHover.loadingTips = $('[data-rmbziro]').attr('data-rmbziro');
  function addHoverWin($elem, attr, direction) {
    $($elem).hover(function() {
      myHover.setHover(this, attr, direction);
    });
    $($elem).mouseleave(function() {
      myHover.hide();
    });
  }
  //
  let delayTime = 60000;
  let mccRmbPrice = 0;
  let coinRmbPrice = {
    mcc: 0,
    eth: 0,
    btc: 0
  }
  // 获取mcc价格
  function getMccPrice(coin) {
    http({
      url:`/ajax_market/coinPrice?coin=${coin}`,
      method: 'GET',
      success({status, data}) {
        let intStatus = parseInt(status);
        if (intStatus === 1 && data) {
          coinRmbPrice[coin] = data;
        }
        setTimeout(() => {
          getMccPrice(coin);
        }, delayTime);
      },
      error() {
        getMccPrice(coin);
      }
    })
  }
  getMccPrice('mcc');
  getMccPrice('eth');
  var rmbPrice = 0;

  //获取人民币汇率
  const getRmbRatio = new Promise(function(resolve, reject) {
    var rmbRatioGetter = {
      url: '/ajax_market/coinPrice?coin=btc',
      type: 'get',
      success(res) {
        if (res && res. status == 1) {
          rmbPrice = res.data;
          coinRmbPrice.btc = rmbPrice;
          window.rmbPrice = rmbPrice;
        } else {
          window.rmbPrice = '';
        }
        setTimeout(function() { $.ajax(rmbRatioGetter); }, delayTime);
        resolve();
      },
      error(err) {
        rmbPrice = 0;
        reject();
      }
    };
    $.ajax(rmbRatioGetter);
    return rmbPrice;
  });

   //计算tbody下的tr to-rmb属性
  function calcRmb($tbody) {
    const $trs = $tbody.find('tr');
    [...$trs].forEach((tr) => {
      const $tr = $(tr);
      // 币种名称
      const $availName = $tr.find('td:eq(0)');
      // 可用余额DOM
      const $availCoin = $tr.find('td:eq(1)');
      // 冻结额DOM
      const $lockCoin = $tr.find('td:eq(2)');
      // 总额DOM
      const $totalCoin = $tr.find('td:eq(3)');
      // 交易区
      const tradeArea = $tr.attr('data-trade');
      // 价格
      const price = $tr.attr('data-price');

      let completeRMBPrice = coinRmbPrice[tradeArea];
      // console.log(completeRMBPrice);

      // if (tradeArea === 'mcc') {
      // } else
      // if (tradeArea === 'btc') {
      //   completeRMBPrice = rmbPrice;
      // }
      // console.log(price + "   " + $availCoin.text());
      if($availName.text() === "BTC"){
        $availCoin.attr('data-tormb', rmbPrice * $availCoin.text());
        $lockCoin.attr('data-tormb', rmbPrice * $lockCoin.text());
        $totalCoin.attr('data-tormb', rmbPrice * $totalCoin.text());
      }
      else if ($availName.text() === "DOB") {
        $availCoin.attr('data-tormb',  $availCoin.text());
        $lockCoin.attr('data-tormb', $lockCoin.text());
        $totalCoin.attr('data-tormb', $totalCoin.text());
      }
      else{
        let avail = price * completeRMBPrice * $availCoin.text();
        let lock = price * completeRMBPrice * $lockCoin.text();
        let total = price * completeRMBPrice * $totalCoin.text();
        //
        $availCoin.attr('data-tormb', avail);
        $lockCoin.attr('data-tormb', lock);
        $totalCoin.attr('data-tormb', total);
      }

    });
  }
  // 获取到人民币汇率后再赋 data-tormb
  getRmbRatio.then(function(resolve) {
    const $allAssets = $('#allAssets');
    const $yibiallAssets = $('#yibiallAssets');
    const assetsNum = $allAssets.text().replace(/[^\d.]/, '');
    const yibiassetsNum = $yibiallAssets.text().replace(/[^\d.]/, '');
    $allAssets.attr('data-tormb', rmbPrice * assetsNum);
    $yibiallAssets.attr('data-tormb', rmbPrice * yibiassetsNum);
    calcRmb($('div[data-table="1"] tbody.user_bibi'));
    calcRmb($('div[data-table="1"] tbody.user_fabi'));
    //总资产添加人民币悬浮框
    addHoverWin($('#allAssets'), 'tormb', 'top');
    addHoverWin($('#yibiallAssets'), 'tormb', 'top');
    //表格tr添加人民币悬浮框
    addHoverWin($('div[data-table="1"] tbody.user_bibi tr td'), 'tormb', 'left');
    addHoverWin($('div[data-table="1"] tbody.user_fabi tr td'), 'tormb', 'left');
  }).catch(function(reject) {

  })

});
