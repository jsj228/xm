import '@/styles/userCenter/coinOut.scss';
import Flatpickr from "flatpickr";
import Zh from "flatpickr/dist/l10n/zh.js";
import math from 'mathjs';
import nav from '@/components/nav';
import navVue from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import http from '@/tools/http';
import is from '@/tools/is';
import Alert from "@/tools/alert/alert";
import setTableStyle from '@/components/makeTableHead';
import dialog from '@/tools/dialog';
import DialogBox from '@/tools/dialogBox/dialogBox';
import eNumToStrNum from '@/tools/eNumToStrNum';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import HoverWin from '@/components/hoverWin';
import common from '@/pcAndMob/user.coinout';
import talking from '@/tools/talking/talking';
import cookie from '@/tools/cookie';

// process.env.NODE_ENV
// 我的賬戶 轉幣 提幣 委托 成交 頁面js
$(document).ready(function() {
  const moNum = $('#moStyle').text() || '';
  const navNew = navVue();
  //打開聊天室接受數據功能
  talking();
  math.config({
    number: 'BigNumber', // Default type of number:
    // 'number' (default), 'BigNumber', or 'Fraction'
    precision: 64        // Number of significant digits for BigNumbers
  });
  // 切換 請求處理 語言包
  chkhttpLang("#baseLang", http);
  console.log(getLanguagePack());
  let phpLang = getLanguagePack() || {
    sureBtn: "確定",
    coinOut: "提幣",
    fozRich: "凍結餘額",
    nowCoin: "當前可用",
    coinBalance: "餘額",
    coinOutAddr: "提幣地址",
    placeWriteAddr: "請填寫接收地址",
    usefullAddr: "請輸入有效的接收地址",
    coinOutNum: "轉出數額",
    coinOutNumPHold: "請輸入轉出數額",
    tradePwd: "交易密碼",
    tradePwdPHold: "請輸入交易密碼",
    imgCode: "圖形驗證碼",
    imgCodePHold: "請輸入圖形驗證碼",
    phoneCode: "手機驗證碼",
    phoneCodePHold: "請輸入手機驗證碼",
    getPhoneCode: "獲取驗證碼",
    coinOutTil: "提幣",
    coinOutTipsSubTit: "轉出須知",
    downTipsOne: "每筆轉出最低額度為",
    downTipsTwo: "最高限額為",
    tradeOut: "轉出",
    coinFee: "手續費為",
    tradeOutOver: "轉出額度超過",
    peopleCheck: "時需人工審核",
    balanceNotEnth: "可用余額不足",
    clickGet: "點擊獲取",
    voiceCode: "語音驗證碼",
    canGetAt: "可以在",
    afterGetVoiceCode: "後獲取語音驗證碼",
    afterGet: "後重新獲取",
    coinOutSuccTips: "發起提幣成功，請等待確認！",
    canNotCopy: '您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。',
    copyRight: '複制成功，請通過 ctrl + v 鍵粘貼。'
  };
  const myAlert = new Alert("");
  nav();
  activeNav();
  common();
  let nowLang = cookie.getItem('LANG');

  // Flatpickr.localize(Zh.zh);
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
    dialog.css({ width: "452px", height: "260px", borderRadius: "8px", position: "relative" });
    dialog.show(true);
    $('[data-diclose="btn"]').click(function() {
      // 重定向
      if (callback && is(callback, "Function")) {
        callback();
      }
      dialog.hide(true);
    });
  }
  let myHover = new HoverWin($('[data-tips]'), 'tips');
  // 人民币提示框设置
  myHover.offsetY = 18;
  myHover.offsetX = -50;
  myHover.css({width: '200px'});
  // myHover.css({height: '43px'});
  myHover.contentCss({lineHeight: '14px', padding: '8px 0', whiteSpace: 'nowrap'});

  // 提幣 form 彈框
  const myBox = new DialogBox();
  myBox.css({ height: 'auto' });
  // 切換語言
  let regs = phpLang.regs || {
    wallet: {
      msg: "請輸入有效的接收地址",
      errMsg: "請輸入有效的接收地址"
    },
    number: {
      msg: "請輸入轉出數額",
      errMsg: "請輸入轉出數額",
      overMsg: "轉出數額不符合要求",
      less: ""
    },
    pwdtrade: {
      msg: "請輸入交易密碼",
      errMsg: "交易密碼錯誤"
    },
    captcha: {
      msg: "看不清？點擊圖片更換驗證碼",
      errMsg: "位數不正確"
    },
    code: {
      msg: "請輸入手機驗證碼",
      errMsg: "手機驗證碼錯誤"
    }
  };

  // 提幣彈框
  function coinOutBox(coin) {
    // moNum 1 需要验证码  2免验证码
    let coinOutBoxTpl = ''
    if (moNum == 1) {
      coinOutBoxTpl = `<div class="coin-out-form">
        <div class="dialog-title">${coin.name + phpLang.coinOut}</div>
        <p>${phpLang.nowCoin + coin.name + phpLang.coinBalance}:&nbsp;&nbsp;&nbsp;<span>${coin.balance}</span> <br><p>${phpLang.fozRich.replace("BTC", coin.name)}:&nbsp;&nbsp;${coin.fozRich}</p></p>
        <div class="form-tab top-tab parent-dom">
          <span class="coin-out-sp">${phpLang.coinOutAddr}</span>
          <input type="text" name="wallet" placeholder="${phpLang.placeWriteAddr}" />
          <p class="warn-tips-lf">${phpLang.usefullAddr}</p>
        </div>
        <div class="form-tab parent-dom">
          <span class="coin-out-sp">${phpLang.coinOutNum}</span>
          <input type="text" id="showRealRMB" maxlength="19" data-tips="tips123444" name="number" autocomplete="new-password" placeholder="${phpLang.coinOutNumPHold}" />
          <p class="warn-tips-lf">${phpLang.coinOutNumPHold}</p>
        </div>
        <div class="form-tab parent-dom">
          <span class="coin-out-sp">${phpLang.tradePwd}</span>
          <input type="password" name="pwdtrade" autocomplete="new-password" minlength="6" maxlength=20 placeholder="${phpLang.tradePwdPHold}" />
          <p class="warn-tips-lf">${phpLang.tradePwdPHold}</p>
        </div>
        <div class="mut-tab parent-dom">
          <div class="form-tab shot-tab">
            <span class="coin-out-sp">${phpLang.imgCode}</span>
            <input type="text" name="captcha" maxlength="4" placeholder="${phpLang.imgCodePHold}" />
            <p class="warn-tips-lf">${phpLang.imgCodePHold}</p>
          </div>
          <img src="/index/captcha?v=${(new Date()).getTime()}" data-imgcode="code" />
        </div>
        <div class="mut-tab parent-dom">
          <div class="form-tab shot-tab">
            <span class="coin-out-sp">${phpLang.phoneCode}</span>
            <input type="text" name="code" placeholder="${phpLang.phoneCodePHold}"  maxlength="6" />
            <p class="warn-tips-lf">${phpLang.phoneCodePHold}</p>
          </div>
          <button class="voice-btn" data-phcode="ph" data-msgtype="7">${phpLang.getPhoneCode}</button>
          <p class="voice-msg-line" data-voicemsg="voice"></p>
        </div>
        <div class="mut-tab last-tab"><button type="button" class="coin-out-submit loading-svg-bg" id="formCoinOutBtn">${phpLang.coinOutTil}</button><u class="loading-svg" data-loading="svg" style="display:none"></u></div>
        <p class="dot-line"></p>
        <div class="friendly-warn">
          <h4>${phpLang.coinOutTipsSubTit}</h4>
          <p>1. ${phpLang.downTipsOne}<span>${eNumToStrNum(parseFloat(coin.minout))}${coin.name}</span>，${phpLang.downTipsTwo}<span>${eNumToStrNum(parseFloat(coin.maxout))}${coin.name}</span></p>
          <p>2. ${phpLang.tradeOut}<span>${coin.name}</span>${phpLang.coinFee}<span>${coin.rateout * 100}%</span></p>
          ${ parseFloat(coin.outlimit) <= 0 ? "" : `<p>3. ${phpLang.tradeOutOver}<span>${eNumToStrNum(parseFloat(coin.outlimit))}${coin.name}</span>${phpLang.peopleCheck}</p>` }
        </div>
        </div>`;
    } else {
      coinOutBoxTpl = `<div class="coin-out-form">
        <div class="dialog-title">${coin.name + phpLang.coinOut}</div>
        <p>${phpLang.nowCoin + coin.name + phpLang.coinBalance}:&nbsp;&nbsp;&nbsp;<span>${coin.balance}</span> <br><p>${phpLang.fozRich.replace("BTC", coin.name)}:&nbsp;&nbsp;${coin.fozRich}</p></p>
        <div class="form-tab top-tab parent-dom">
          <span class="coin-out-sp">${phpLang.coinOutAddr}</span>
          <input type="text" name="wallet" placeholder="${phpLang.placeWriteAddr}" />
          <p class="warn-tips-lf">${phpLang.usefullAddr}</p>
        </div>
        <div class="form-tab parent-dom">
          <span class="coin-out-sp">${phpLang.coinOutNum}</span>
          <input type="text" id="showRealRMB" maxlength="19" data-tips="tips123444" name="number" autocomplete="new-password" placeholder="${phpLang.coinOutNumPHold}" />
          <p class="warn-tips-lf">${phpLang.coinOutNumPHold}</p>
        </div>
        <div class="form-tab parent-dom">
          <span class="coin-out-sp">${phpLang.tradePwd}</span>
          <input type="password" name="pwdtrade" autocomplete="new-password" minlength="6" maxlength=20 placeholder="${phpLang.tradePwdPHold}" />
          <p class="warn-tips-lf">${phpLang.tradePwdPHold}</p>
        </div>
        <div class="mut-tab last-tab"><button type="button" class="coin-out-submit loading-svg-bg" id="formCoinOutBtn">${phpLang.coinOutTil}</button><u class="loading-svg" data-loading="svg" style="display:none"></u></div>
        <p class="dot-line"></p>
        <div class="friendly-warn">
          <h4>${phpLang.coinOutTipsSubTit}</h4>
          <p>1. ${phpLang.downTipsOne}<span>${eNumToStrNum(parseFloat(coin.minout))}${coin.name}</span>，${phpLang.downTipsTwo}<span>${eNumToStrNum(parseFloat(coin.maxout))}${coin.name}</span></p>
          <p>2. ${phpLang.tradeOut}<span>${coin.name}</span>${phpLang.coinFee}<span>${coin.rateout * 100}%</span></p>
          ${ parseFloat(coin.outlimit) <= 0 ? "" : `<p>3. ${phpLang.tradeOutOver}<span>${eNumToStrNum(parseFloat(coin.outlimit))}${coin.name}</span>${phpLang.peopleCheck}</p>` }
        </div>
        </div>`;
    }

    //<button type="button" class="coin-out-submit loading-svg-bg" id="formCoinOutBtn">
    // 點擊獲取語<span data-msgtype="8" data-phcode="ph">音驗證碼</span>
    // 修改提示內容
    // regs.number.overMsg = `轉出數額不能小於${coin.minout}${coin.name}或者大於${coin.maxout}${coin.name}`;
    //
    myBox.show(coinOutBoxTpl, function() {
      regs.number.less = `${coin.name + phpLang.balanceNotEnth}`;
      // 短信驗證碼 setInterval 標記
      let timesCount = null;
      function ramdomNum() {
        return parseInt(Math.random() * 100000);
      }
      // update img code 更新图形验证码
      function updateImgCode() {
        $('img[data-imgcode="code"]').attr({ src: `/index/captcha?v=${ramdomNum()}`});
      }
      // 充值倒計時
      function resetCodeBtn() {
        clearTimeout(timesCount);
        phoneCodeBusy = false;
        $('p[data-voicemsg="voice"]').addClass('orange-f').html(`${phpLang.clickGet}<span data-msgtype="8" data-phcode="ph">${phpLang.voiceCode}</span>`);
        $('button[data-phcode="ph"]').html(phpLang.getPhoneCode);
        $('button[data-phcode="ph"]').removeAttr("disabled").removeClass('disabled-btn');
        // 重新綁定數據
        $(".voice-msg-line [data-phcode='ph']").click(function() {

          getPhoneCode(this);
        });
      }
      // 獲取驗證碼 防止多次點擊
      let phoneCodeBusy = false;
      // 倒計時
      function sixTime() {
        // setInterval
        timesCount = setInterval(function() {
          const elem = $("b[data-times]");
          let oldTime = elem.data('times');
          if (oldTime === 0) {
            resetCodeBtn();
          } else {
            oldTime--;
            elem.data('times', oldTime).html(oldTime);
          }
        }, 1000);
      }
      // 提示框
      function tipWinCtrl($input, isHide, tipsType, dataMsg) {
        const name = $input.attr('name');
        if (!isHide) {
          // 顯示 提示 類型
          switch (tipsType) {
            case 'err':
              $input.siblings('.warn-tips-lf').addClass("err-tips").html(regs[name].errMsg).show();
              $input.parents('.form-tab').addClass('warn-outline');
              break;
            case 'over':
              $input.siblings('.warn-tips-lf').addClass("err-tips").html(regs[name].overMsg).show();
              $input.parents('.form-tab').addClass('warn-outline');
              break;
            case 'less':
              $input.siblings('.warn-tips-lf').addClass("err-tips").html(regs[name].less).show();
              $input.parents('.form-tab').addClass('warn-outline');
              break;
            case 'dataMsg': {
              $input.siblings('.warn-tips-lf').addClass("err-tips").html(dataMsg).show();
              $input.parents('.form-tab').addClass('warn-outline');
              break;
            }
            default:
              $input.siblings('.warn-tips-lf').html(regs[name].msg).show();
              $input.parents('.form-tab').removeClass('warn-outline');
          }
        } else {
          $input.siblings('.warn-tips-lf').hide();
          $input.parents('.warn-outline').removeClass('warn-outline');
        }
      }
      // 保存验证码类型
      let phoneAction = '';
      // 手機短信 驗證碼
      function getPhoneCode($this) {
        if (phoneCodeBusy) return;
        $($this).addClass('disabled-btn');
        phoneCodeBusy = true;
        // 驗證碼類型
        const msgtype = $($this).data('msgtype');
        // 圖形驗證碼
        const $imgDom = $(".coin-out-form input[name='captcha']");
        if (!$imgDom.val().trim()) {
          phoneCodeBusy = false;
          return tipWinCtrl($imgDom, false, 'err');
        }
        // 點擊獲取語<span>音驗證碼</span>
        $('p[data-voicemsg="voice"]').addClass('orange-f').html(`${phpLang.canGetAt}<b data-times="60" id="sixTime">60</b>s${phpLang.afterGetVoiceCode}`);
        $('button[data-phcode="ph"]').attr("disabled", "disabled").html('<b data-times="60" id="sixTime">60</b>' + phpLang.afterGet);
        // 可以在" + time + "s後獲取語音驗證碼
        // return;
        sixTime();
        // 短信默認為 7   語音 8
        http({
          url: "/ajax_user/sms",
          method: "POST",
          data: {
            action: msgtype,
            captcha: $imgDom.val()
          },
          success(req) {
            // phoneCodeBusy = false;
            if (req && parseInt(req.status) === 1) {
              phoneAction = msgtype;
              // postSucceDialog('提幣成功');
            } else {
              if (req.data == 'captcha') {
                // update img code
                updateImgCode();
              }
              tipWinCtrl($(`input[name="${req.data}"]`), false, 'dataMsg', req.msg);
              resetCodeBtn();
            }
          },
          error(err) {
            // phoneCodeBusy = false;
            if (err) {
              myAlert.show(err);
            }
          }
        }, true);
      }
      $(".coin-out-form [data-phcode='ph']").click(function() {
        // disabled-btn
        if ($('#showRealRMB').next('.err-tips').css('display') === 'block') {
          return  false;
        }
        else {
          getPhoneCode(this);
        }
        // getPhoneCode(this);
      });
      // 檢驗輸入結果
      function checkInput($input, name, val, isSubmit) {
        // 是否符合规则
        let result = true;
        // 輸入為 空
        if (!val) {
          // 按提交按鈕 顯示錯誤提示
          if (isSubmit) {
            tipWinCtrl($input, false, 'err');
          } else {
            tipWinCtrl($input, false, '');
          }
          result = false;
          // return false;
        } else {
          let minout = parseFloat(coin.minout);
          let maxout = parseFloat(coin.maxout);
          let balance = parseFloat(coin.balance);

          if (name === 'number') {
            // console.log(math.compare(balance, parsNum));
            // 转换成相同类型的数字比较
            let parsNum = parseFloat(val);
            // 判断是否在规则范围内
            if (parsNum - minout < 0 || maxout - parsNum < 0) {
              tipWinCtrl($input, false, 'over');
              result = false;
            }
            // 余額不足
            else if (balance - parsNum < 0) {
              tipWinCtrl($input, false, 'less');
              result = false;
            } else {
              if (isSubmit) {
                tipWinCtrl($input, true);
              } else {
                tipWinCtrl($input, false);
              }
            }
          } else if (name === 'captcha') {
            if (val.length < 4) {
              tipWinCtrl($input, false, 'err');
              result = false;
            } else if (isSubmit) {
              tipWinCtrl($input, 'hide', '');
            } else {
              tipWinCtrl($input, false, 'msg');

            }
          } else if (name === 'code') {
            if (val.length < 6) {
              result = false;
              tipWinCtrl($input, false, 'err');
            } else if (isSubmit) {
              tipWinCtrl($input, 'hide', '');
            } else {
              tipWinCtrl($input, false, 'msg');
            }
          } else if (name === 'wallet') {
            const coinType = $('.sel-coin').text().toLowerCase().trim();
            if (!/^[0-9a-zA-Z]*$/.test(val)) {
              tipWinCtrl($input, false, 'err');
              result = false;
            }
            else {
              if (coinType === 'htc' || coinType === 'bvt' || coinType === 'eos' || coinType === 'etc' || coinType === 'mac' || coinType === 'eth') {
                const sli = val.slice(0,2);
                if (sli.length == 2 && sli !== '0x') {
                  tipWinCtrl($input, false, 'err');
                  result = false;
                }
                else if (val <= 1) {
                  tipWinCtrl($input, false, 'err');
                  result = false;
                }
              }
            }
          } else {
            if (isSubmit) {
              tipWinCtrl($input, true);
            } else {
              if (name === 'pwdtrade') {
                tipWinCtrl($input, true);
              } else {
                tipWinCtrl($input, false);
              }
              result = false;
            }
          }
        }
        return result;
      }
      // 展示 计算 实际到账 人民币
      function showRealRMB(money, smallLength, $elem, coin) {
        if (math.compare(money, 0) <= 0) return;
        // let compstr = `${money} * ( 1 - ${coin.rateout})`;
        // let realMoney = math.eval(compstr).toFixed(20);
        // 大数据 精确计算，去除 e
        if (!isNaN(money)) {
          let catchNum = math.multiply(math.bignumber(money), math.bignumber(`${1 - coin.rateout}`));
          let realMoney = catchNum.toString();
          // 小数 去E
          if (realMoney.match(/[eE-]/g) !== null) {
            realMoney = (new Number(realMoney)).toFixed(20);
          }
          // 最终结果
          let lastNum = '';
          // 小数部分
          let smallNum = '';
          // 张数部分
          let intNum = '';
          // 拆分 整数和 小数
          if (realMoney.indexOf('.') > -1) {
            smallNum = realMoney.split('.')[1];
            intNum = realMoney.split('.')[0];
          } else {
            intNum = realMoney;
          }
          // 保留小数后 （設定位數） 8 位
          if (smallNum.length > smallLength) {
            smallNum = smallNum.slice(0, smallLength);
          }
          // 用於判斷全是零 的情況
          let Allzero = '000000000000000'.slice(0, smallLength)
          // 如果有小数 组合
          if (smallNum && smallNum != Allzero) {
            // 计算最终结果
            lastNum = intNum + '.'+ smallNum;
          }
          // 没有小数
          else {
            lastNum = intNum;
          }
          // 首次保存計算值 '實際到賬: '
          lastNum = phpLang.REAL_TIME + lastNum;

          // 等于零 提示无法提取
          if (lastNum === '0' && smallNum === Allzero) {
            lastNum = phpLang.LESS_NUM; // '數額過小，無法轉出';
          }
          // 輸入值小於最小值 提示輸入不符合規則
          if (math.compare(parseFloat(money), parseFloat(coin.minout)) < 0) {
            lastNum = regs.number.overMsg;
          }
          // 輸入值大於 最大值 提示輸入不符合規則
          if (math.compare(parseFloat(coin.maxout), parseFloat(money)) < 0) {
            lastNum = regs.number.overMsg;
          }
          // 设置 展示框
          myHover.setHover($elem, 'tips');
          // 显示 展示框
          myHover.showMsg(lastNum);
        }
      }
      // 输入框 事件
      function inputEvent($this, isBlur) {
        const $input = $($this);
        const name = $input.attr('name');
        const val = $input.val();
        if (checkInput($input, name, val) && isBlur) {
          tipWinCtrl($input, 'isHide');
        }
        // return checkInput($input, name, val);
      }
      $("input").on('focus', function() {
        let $this = $(this);
        $this.siblings('.warn-tips-lf').removeClass("err-tips");
        //
        inputEvent(this);
        let val = $this.val();
        // 保留小數 位數
        let nfloat = parseInt(coin.nfloat);
        // 只有 輸入數量時 計算人民幣
        if (val && $this.attr('name') === "number") {
          if (isNaN(nfloat)) {
            nfloat = 8;
          }
          // 显示 真实人民币
          showRealRMB(val, nfloat, $this, coin);
        }
      });
      $("input").on('blur', function() {
        const $input = $(this);
        // 為空 隱藏提示框
        if (!$(this).val().trim()) {
          tipWinCtrl($input, 'isHide');
        } else {
          inputEvent(this, true);
        }
        // 隐藏 计算 实际 人民币 提示框
        myHover.hide();

        // $(this).siblings('.warn-tips-lf').hide();
      });

      $('input').on('input', function(event) {
        let id = $(this).attr('id');
        if (id != 'showRealRMB') {
          let val = $(this).val();
          let chReg = /([\u4e00-\u9fa5]*)|([\x00-\xff]*)/g;
          val = val.replace(chReg, '');
          $(this).val(val);
        }
      });
      // 10位 整数 允许小数
      let realMoneyRule = /^((([1-9]([0-9]{0,9})?)|0)(?:\.([0-9]*))?)$/;
      $("#showRealRMB").on("input", function () {
        let $input = $(this);
        let val = $input.val();
        let nfloat = parseInt(coin.nfloat);
        if (val){
          // 输入部分是否有数字部分
          if (!realMoneyRule.test(val)) {
            if (!parseFloat(val)) {
              return $input.val('');
            } else {
              // 提取数字部分
              val.replace(/^((([1-9]([0-9]{0,9})?)|0)(?:\.([0-9]*))?)/g, function(num) {
                $input.val(num);
              });
            }
          }
          // 限制小数长度
          let smalNumIdx = val.indexOf('.');
          if (smalNumIdx > -1 && parseInt(coin.nfloat) > 0) {
            // 小数部分 长度 超出限制 长度
            let smalNumPart = val.split('.')[1];
            if (smalNumPart.length > coin.nfloat) {
              val = val.slice(0, smalNumIdx + coin.nfloat + 1);
              $input.val(val);
            }
          }
          // 获取 最新 的值 数字
          val = $input.val();
          // 確保保留小數位數 不是空 不是 NAN
          if (isNaN(nfloat)) {
            nfloat = 8;
          }
          // 显示 真实人民币
          showRealRMB(val, nfloat, $(this), coin);
        } else {
          // 隐藏 人民币 展示
          myHover.hide();
        }
      });
      $('img[data-imgcode="code"]').click(function() {
        $(this).attr({ src: `/index/captcha?v=${ramdomNum()}`});
      });
      let coinOutBusy = false;
      function coinOutBusyDone() {
        coinOutBusy = false;
        $("#formCoinOutBtn").removeClass('submit-ing').find('u').html('');
      }
      // 显示输入币数量 提示用户到账人民
      // addHoverWin($('input[data-tips]'), 'tips', 'top');
      // 提幣 提交表單
      $("#formCoinOutBtn").click(function() {
        if (coinOutBusy) return;
        // $(this).addClass('submit-ing').find('u').html('...');
        coinOutBusy = true;
        const inputs = [...myBox.content.find('input')];
        const postData = {};
        let isComplete = true;
        inputs.forEach((input) => {
          const $input = $(input);
          const name = $input.attr('name');
          const val = $input.val();
          if (checkInput($input, name, val, 'isSubmit')) {
            postData[name] = val;
          } else {
            isComplete = false;
          }
        });
        if (isComplete) {
          postData.coin = coin.coinCode;
          if (moNum == 1) {
            postData.action = phoneAction;
          }
          $('.loading-svg').show();
          http({
            url: '/ajax_user/coinOut',
            method: "POST",
            data: postData,
            success(req) {
              let { status , msg} = req;
              if (status && parseInt(status) === 1) {
                postSucceDialog(phpLang.coinOutSuccTips, function() {
                  window.location.reload();
                });
                myBox.hide();
              } else {
                $('.loading-svg').hide();
                if (req.data == 'captcha') {
                  // update img code
                  updateImgCode();
                }
                tipWinCtrl($(`input[name="${req.data}"]`), false, 'dataMsg', msg);
                // 其他情况，弹框提示
                if (!req.data) {
                  myAlert.show(msg);
                }
                // 重置表單 按鈕 條件
                resetCodeBtn();
                coinOutBusyDone();
              }
            },
            error(err) {
              $('.loading-svg').hide()
              coinOutBusyDone();
              myBox.hide();
            }
          }, true);
        } else {
          coinOutBusyDone();
        }
      });
    });
  }
  //
  $("#coinOutBtn").click(function() {
    const coinDom = $("#coinList .sel-coin");
    // 獲取幣信息
    const coins = {
      coinCode: coinDom.data("coincode"),
      name: coinDom.html().trim(),
      balance:  $("#uerList").data("coinover"), // 余額
      minout: coinDom.data("minout"),
      maxout: coinDom.data("maxout"),
      rateout: coinDom.data("rateout"),
      outlimit: coinDom.data("outlimit"),
      fozRich: $("#fozRich").html(),
      // 小数位数限制
      nfloat: coinDom.data("nfloat")
    }
    coinOutBox(coins);
  });
  // coinOutBtn
  if (nowLang === 'cn') {
    Flatpickr.localize(Zh.zh);
  }
  // 日歷
  const startTimtOptions = {
    enableTime: true,
    // defaultDate: "today",
    // maxDate: $("#endTime").val() || "",
    dateFormat: "Y-m-d H:i:S"
  };
  const startTime = new Flatpickr("#startTime", startTimtOptions);
  const endTimtOptions = {
    enableTime: true,
    // defaultDate: "today",
    // minDate: $("#startTime").val() || "",
    dateFormat: "Y-m-d H:i:S"
  };
  const endTime = new Flatpickr("#endTime", endTimtOptions);
  setTableStyle();
  //
  window.onresize = function() {
    setTableStyle();
  };
  /* eslint-disable */
  // 幣篩選條件
  $('#coinList span[data-coincode]').click(function() {
    $("#coinList .sel-coin").removeClass("sel-coin");
    const coinCode = $(this).data('coincode');
    $(this).addClass("sel-coin");
    $("#coinPost input[name='coin']").val(coinCode);
    $("#coinPost").submit();
  });
  // 快捷方式提交
  function quTypePost($this) {
    const type = $($this).data("days");
    $("#coinPost input[name='days']").val(type);
    $("#coinPost").submit();
  }
  // 全部
  $("#allCoin").click(function(){
    quTypePost(this);
  });
  // 當天
  $("#todayCoin").click(function(){
    quTypePost(this);
  });
  // 30天
  $("#monthCoin").click(function(){
    quTypePost(this);
  });
  $("#reset").click(function() {
    const inputs = [...$("#coinPost input")];
    inputs.forEach((input) => {
      if ($(input).attr('name') !== 'coin') {
        $(input).val("");
      }
    });
    quTypePost($("#allCoin"));
  });
  $("#timeselect").mousedown(function() {
    // 選擇時間區間，更換 快捷鍵狀態 -1 表示不在 全部，當天，30天 中任意壹個
    $("#coinPost input[name='days']").val('-1');
  });
  // 復制粘貼
  $('[data-copy="ele"]').click(function (e) {
    if (!document.execCommand) {
      alert(phpLang.canNotCopy);
    }
    const text = $("#coinAddr").html();
    var transfer = document.getElementById('J_CopyTransfer');
    if (!transfer) {
      transfer = document.createElement('textarea');
      transfer.id = 'J_CopyTransfer';
      transfer.style.position = 'absolute';
      transfer.style.opacity = '0';
      transfer.style.width = '0';
      transfer.style.left = '0px';
      transfer.style.bottom = '0px';
      document.body.appendChild(transfer);
    }
    transfer.value = text || '';
    transfer.focus();
    transfer.select();
    try {
      var succ = document.execCommand('Copy', false, null);
      if (succ) {
        myAlert.show(phpLang.copyRight);
      } else {
        myAlert.show(phpLang.canNotCopy);
      }
    } catch (e) {
      if (e) {
        myAlert.show(phpLang.canNotCopy);
      }
    }
  });

  // 判断时间 开始 不大于 结束
  $("#coinPost").on("submit", function() {
    let startTime = $('#startTime').val();
    let endTime = $('#endTime').val();
    if (startTime && endTime) {
      if (startTime >= endTime) {
        // '開始時間不能大于等于結束時間'
        myAlert.show(phpLang.TIME_OVER_TIPS);
        return false;
      }
    }
  });
});
