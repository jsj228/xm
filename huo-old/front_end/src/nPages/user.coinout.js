// 也
import "flatpickr/dist/flatpickr.min.css";
import 'nStyle/user/common.scss';
import "styles/userCenter/coinInTable.scss";
import 'styles/userCenter/index.scss';
import 'nStyle/user/coinout/coinout.scss';

import math from 'mathjs';
import navVue from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import http from '@/tools/http';
import is from '@/tools/is';
import setTableStyle from '@/components/makeTableHead';
import dialog from '@/tools/dialog';
import DialogBox from '@/tools/dialogBox/dialogBox';
import eNumToStrNum from '@/tools/eNumToStrNum';
import getLanguagePack from '@/components/tradeLanguagePack';
import HoverWin from '@/components/hoverWin';
import common from '@/pcAndMob/user.coinout';
import condition from '@/nComponents/user.condition';
import talking from '@/tools/talking/talking';
import cookie from '@/tools/cookie';
import https from 'plugins/http';
import Vue from 'vue';
import Alert from 'nComponents/Alert';
import qs from 'qs';

/* eslint-disable */

// 我的賬戶 轉幣 提幣 委托 成交 頁面js
$(document).ready(function() {
  const moNum = parseInt($('#moStyle').text()) || null;
  // nav();
  const navNew = navVue();
  let myHover = new HoverWin($('[data-tips]'), 'tips');
  // 人民币提示框设置
  myHover.offsetY = 18;
  myHover.offsetX = -50;
  myHover.css({ width: '200px' });
  // myHover.css({height: '43px'});
  myHover.contentCss({ lineHeight: '14px', padding: '8px 0', whiteSpace: 'nowrap' });
  // 注册插件
  Vue.use(https);

  //打開聊天室接受數據功能
  // talking();
  math.config({
    number: 'BigNumber', // Default type of number:
    // 'number' (default), 'BigNumber', or 'Fraction'
    precision: 64        // Number of significant digits for BigNumbers
  });

  // 切換 請求處理 語言包
  // chkhttpLang("#baseLang", http);
  // {
  //   sureBtn: "確定",
  //   coinOut: "提幣",
  //   fozRich: "凍結餘額",
  //   nowCoin: "當前可用",
  //   coinBalance: "餘額",
  //   coinOutAddr: "提幣地址",
  //   placeWriteAddr: "請填寫接收地址",
  //   usefullAddr: "請輸入有效的接收地址",
  //   coinOutNum: "轉出數額",
  //   coinOutNumPHold: "請輸入轉出數額",
  //   tradePwd: "交易密碼",
  //   tradePwdPHold: "請輸入交易密碼",
  //   imgCode: "圖形驗證碼",
  //   imgCodePHold: "請輸入圖形驗證碼",
  //   phoneCode: "手機驗證碼",
  //   phoneCodePHold: "請輸入手機驗證碼",
  //   getPhoneCode: "獲取驗證碼",
  //   coinOutTil: "提幣",
  //   coinOutTipsSubTit: "轉出須知",
  //   downTipsOne: "每筆轉出最低額度為",
  //   downTipsTwo: "最高限額為",
  //   tradeOut: "轉出",
  //   coinFee: "手續費為",
  //   tradeOutOver: "轉出額度超過",
  //   peopleCheck: "時需人工審核",
  //   balanceNotEnth: "可用余額不足",
  //   clickGet: "點擊獲取",
  //   voiceCode: "語音驗證碼",
  //   canGetAt: "可以在",
  //   afterGetVoiceCode: "後獲取語音驗證碼",
  //   afterGet: "後重新獲取",
  //   coinOutSuccTips: "發起提幣成功，請等待確認！",
  //   canNotCopy: '您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。',
  //   copyRight: '複制成功，請通過 ctrl + v 鍵粘貼。'
  // };
  let phpLang = getLanguagePack();
  condition(phpLang);
  activeNav();
  common();
  // 切換語言
  let regs = phpLang.regs;

  // 提币
  const coinoutBox = new Vue({
    data: {
      show: false,
      containStatus: false,
      // 彈框
      nowStatus: false,
      // 弹框 内容显示
      errMesAlert: "",
      // 币信息
      coin: {
        coin: null,
        name: 'btc',
        balance: "200",
        freezeBalance: '600',
        minout: 1,
        maxout: 100,
        rateout: 0.002,
        nfloat: 8,
        fee: "1",
        outlimit: 100
      },
      timescamp: '',
      hasBindEmail: null,
      form: {
        wallet: "",
        label: "",
        number: null,
        pwdtrade: "",
        captcha: "",
        code: "",
        coin: "",
        google_code: "",
        action: 7, // 7 短信， 8 語音
        regtype: "phone" // 手机 phone  ，  邮箱  email
      },
      // 0 默认， 1 正确， 2 错误， 3 php返回
      errTips: {
        wallet: null,
        label: null,
        number: null,
        pwdtrade: null,
        captcha: null,
        code: null,
        google_code: null
      },
      formCompleted: {
        wallet: false,
        label: false,
        number: false,
        pwdtrade: false,
        captcha: false,
        code: false,
        google_code: false
      },
      isSubmiting: false,
      getVcCode: false,
      // countdown
      countdown: 0,
      cDownTime: null,
      hasFocusInp: 'text',
      //has google or not
      isGoo: false
    },
    created() {
      // 获取 加密 公钥
      // this.$getPublicKey();
      this.updateCaptcha();
    },
    methods: {
      init(coin) {
        this.hasFocusInp = 'text';
        // 结构 赋值
        this.coin = Object.assign({}, coin);
        this.show = true;
        let coiName = coin.name.toLowerCase();
        this.form = {
          wallet: "",
          label: "",
          number: null,
          pwdtrade: "",
          captcha: "",
          code: "",
          google_code: '',
          coin: coiName,
          action: 7,
          regtype: this.hasBindEmail ? "email" : "phone"
        };
        this.errTips = {
          wallet: null,
          label: null,
          number: null,
          pwdtrade: null,
          captcha: null,
          code: null,
          google_code: null
        };
        this.formCompleted = {
          wallet: false,
          label: false,
          number: false,
          pwdtrade: false,
          captcha: false,
          code: false,
          google_code: false
        };
        this.countdown = 0;
        this.cDownTime = null;
        this.getVcCode = false;
        this.$nextTick(() => {
          this.hover();
        })
      },
      // has phone
      confirmRegtype(hasEmail) {
        if (hasEmail) {
          this.hasBindEmail = hasEmail;
        } else {
          this.form.regtype =='phone'
        }
        // if (hasPhone) {
        //   this.hasBindEmail = hasPhone;
        // }
      },
      getGoogle(hasGoo) {
        if (hasGoo) {
          this.isGoo = true;
        } else {
          this.isGoo = false;
        }
      },
      hover() {
        // 禁止汉字输入
        $('#coinOutDom input').on('input', function(event) {
          let id = $(this).attr('id');
          if (id != 'showRealRMB') {
            let val = $(this).val();
            let chReg = /([\u4e00-\u9fa5]*)|([\x00-\xff]*)/g;
            val = val.replace(chReg, '');
            $(this).val(val);
          }
        });
      },
      // 展示 计算 实际到账 人民币
      showRealRMB(money, smallLength) {
        let coin = this.coin;
        if (math.compare(money, 0) <= 0) return;
        // let compstr = `${money} * ( 1 - ${coin.rateout})`;
        // let realMoney = math.eval(compstr).toFixed(20);
        // 大数据 精确计算，去除 e
        if (!isNaN(money)) {
          let catchNum = math.multiply(math.bignumber(money), math.bignumber(`${1 - coin.rateout}`));
          let realMoney = catchNum.toString();
          // 小数 去E
          if (realMoney.match(/[eE-]/g) !== null) {
            realMoney = (realMoney - 0).toFixed(20);
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
          let Allzero = '000000000000000'.slice(0, smallLength);
          // 如果有小数 组合
          if (smallNum && smallNum != Allzero) {
            // 计算最终结果
            lastNum = intNum + '.' + smallNum;
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
          myHover.setHover($("#coinOutDom input[data-tips]"), 'tips');
          // 显示 展示框
          myHover.showMsg(lastNum);
        }
      },
      updateCaptcha() {
        this.timescamp = (new Date()).getTime();
      },
      fozRichText(str) {
        let coinName = this.coin.name.toUpperCase();
        return str ? str.replace('BTC', coinName) : '';
      },
      chkWallet() {
        this.form.wallet = this.form.wallet.replace(/[\u4E00-\u9FA5]+/, "");
        let nVal = this.form.wallet;
        let result = true;
        // 地址 length > 2
        if (nVal.length > 2) {
          let coinReg = /(htc|bvt|eos|etc|mac|eth)/ig;
          let addReg = /^(0x)/;
          // eth 系列 地址判断 不以 0x开头的 地址错误
          if (coinReg.test(this.coin.name) && !addReg.test(nVal)) {
            result = false;
          }
          // eth
          else if (nVal.length <= 1) {
            result = false;
          }
          //
          if (!result) {
            this.errTips.wallet = 2;
          }
        } else {
          result = false;
        }
        this.formCompleted.wallet = result;
        return result;
      },
      chkNumber() {
        let nVal = this.form.number;
        let result = true;
        if (nVal) {
          // 超出 余额
          if (nVal - this.coin.balance > 0) {
            result = false;
            this.errTips.number = 3;
          }
          // 小于最小转出 or 大于最大转出
          else if (nVal - this.coin.minout < 0 || nVal - this.coin.maxout > 0) {
            result = false;
            this.errTips.number = 2;
          } else {
            this.errTips.number = null;
          }
        } else {
          result = false;
          this.errTips.number = null;
        }
        this.formCompleted.number = result;
        return result;
      },
      chkPwd() {
        let nVal = this.form.pwdtrade;
        if (!nVal) {
          this.formCompleted.pwdtrade = false;
        } else {
          this.formCompleted.pwdtrade = true;
        }
      },
      chkCaptcha() {
        let nVal = this.form.captcha;
        let result = true;
        if (nVal) {
          if (nVal.length != 4) {
            result = false;
          }
        } else {
          result = false;
        }
        // 本输入框 完成 状态
        this.formCompleted.captcha = result;
        return result;
      },
      chkCode(type) {
        let nVal = '';
        if (type === 'code') {
          nVal = this.form.code;
        }
        else {
          nVal = this.form.google_code;
        }

        let result = true;
        if (nVal) {
          if (nVal.length != 6) {
            result = false;
          }
        } else {
          result = false;
        }
        // this.formCompleted[type] = result;
        // 本输入框 完成 状态
        if (type === 'code') {
          this.formCompleted.code = result;
          this.formCompleted.google_code = true;
        }
        else {
          this.formCompleted.code = true;
          this.formCompleted.google_code = result;
        }
        return result;
      },
      chklabel(type) {
        let nVal = this.form.label;
        let result = true;
        if (!nVal) {
          result = false;
        }
        // 本输入框 完成 状态
        this.formCompleted.label = result;
        return result;
      },
      // input blur
      blur(opt) {
        let val = this.form[opt];
        let chkResult = true;
        switch (opt) {
          case 'wallet': {
            if (val && !this.chkWallet()) {
              chkResult = false;
            }
            break;
          }
          case "number": {
            if (val && !this.chkNumber()) {
              chkResult = false;
            }
            myHover.hide();
            break;
          }
          case "pwdtrade": {
            // if (!this.chkPwd) {
            //   this.errTips.number = 2;
            // }
            break;
          }
          case "captcha": {
            if (val && !this.chkCaptcha()) {
              chkResult = false;
            }
            break;
          }
          case "code": {
            if (val && !this.chkCode(opt)) {
              chkResult = false;
            }
            break;
          }
          case "google_code": {
            if (val && !this.chkCode(opt)) {
              chkResult = false;
            }
            break;
          }
          case "label": {
            if (val && !this.chklabel(opt)) {
              chkResult = false;
            }
          }
            break;
          default:
            break;

        }
        // 隐藏提示 輸入正確，或沒輸入內容
        if (!val || chkResult) {
          this.errTips[opt] = null;
        } else if (!chkResult) {
          if (opt != 'number') {
            this.errTips[opt] = 2;
          }
        }
        if (!val) chkResult = false;
        // 用于提交判断
        return chkResult;
      },
      focus(opt) {
        this.errTips[opt] = 0;
        if (opt === 'pwdtrade' && this.hasFocusInp === 'text') {
          this.hasFocusInp = 'password';
        }
        if (opt === 'wallet') {
          this.form.wallet = this.form.wallet.replace(/[\u4E00-\u9FA5]+/, "");
        }
        if (opt === 'number' && this.form.number) {
          this.showRealRMB(this.form.number, this.coin.nfloat);
        } else if (opt === 'captcha') {
          let val = this.form.captcha;
          this.form.captcha = val.replace(/[^\w]/ig, '');
        } else if (opt === 'code') {
          let val = this.form.code;
          this.form.code = val.replace(/\D/g, '');
        } else if (opt === 'google_code') {
          let val = this.form.google_code;
          this.form.google_code = val.replace(/\D/g, '');
        }
      },
      // int num limit
      numInput() {
        let nVal = this.form.number;
        let nfloat = parseInt(this.coin.nfloat);
        let realMoneyRule = new RegExp(`^((([1-9]([0-9]{0,9})?)|0)(?:\.([0-9]{1, ${nfloat}}))?)$`, 'g');
        let getNumReg = new RegExp(`^([0-9]{1,${nfloat}})(?:\.([0-9]{1,${nfloat}})?)`, 'g');
        // 输入部分是否有数字部分
        if (!realMoneyRule.test(nVal) && nVal) {
          let useNum = nVal.match(getNumReg);
          if (useNum) {
            this.form.number = useNum[0];
          } else {
            this.form.number = '';
          }
        }
        this.showRealRMB(this.form.number, nfloat);
      },
      spaceInput() {
        this.form.pwdtrade = this.form.pwdtrade.replace(/(^\s+)|(\s+$)/g, '')
      },
      // 倒计时
      daleyTime() {
        this.countdown = 60;
        this.cDownTime = setInterval(() => {
          if (this.countdown > 0) {
            this.countdown--;
            // 显示 语音验证码 按钮
            if (!this.getVcCode) {
              this.getVcCode = true;
            }
          } else {
            this.clearCtDown();
          }
        }, 1000);
      },
      clearCtDown() {
        this.countdown = 0;
        clearTimeout(this.cDownTime);
      },
      // 获取验证码
      getCode() {
        this.daleyTime();
        if (!this.blur('captcha')) {
          this.errTips.captcha = 1;
          this.clearCtDown();
        } else {
          this.$http.post("/ajax_user/sms", {
            action: this.form.action,
            captcha: this.form.captcha
          })
          .then(({ status, data, msg }) => {
            if (parseInt(status) != 1) {
              this.clearCtDown();
              this.updateCaptcha();
              this.errTips[data] = msg;
            }
          })
          .catch(err => {
            // 显示 语音验证码 按钮
            if (!this.getVcCode) {
              this.getVcCode = true;
            }
            this.clearCtDown();
          });
        }
      },
      // 获取 手机验证码
      phoneCode(vAction) {
        // 7 短信， 8 語音
        this.form.action = 7;
        this.getCode();
      },
      // 语音验证码
      voiceCode() {
        this.form.action = 8;
        this.getCode();
      },
      // 邮箱验证码
      emailCode() {
        this.daleyTime();
        this.$http.post('/Emailverify/findpwd', { regtype: 'trust' })
          .then(({ status, data, msg }) => {
            if (parseInt(status) != 1) {
              this.errTips[data] = msg;
              this.clearCtDown();
            }
          })
          .catch(err => {
            this.clearCtDown();
          });
      },
      succCallBack() {
        window.location.reload();
      },
      //
      submit() {
        if (this.isSubmiting) return;
        this.isSubmiting = true;
        // 未完成
        if (!this.fCompleted) {
          for (let key in this.formCompleted) {
            if (this.isGoo && key === 'code') {
              continue;
            } else if (!this.isGoo && key === 'google_code') {
              continue;
            } else if (this.coin.name != 'EOS' && key === 'label') {
              continue;
            }
            if (!this.blur(key)) {
              if (key === 'code' || key === 'captcha' || key === 'google_code') {
                // 為空
                if (!this.form[key])  {
                  this.errTips[key] = 1;
                }
                // 輸入長度 不正確
                else {
                  this.errTips[key] = 2;
                }
              } else {
                this.errTips[key] = 1;
              }
            }
          }
          this.isSubmiting = false;
        } else {
          let postData = Object.assign({}, this.form);
          this.$http.post('/ajax_user/coinOut', postData)
          .then(({ status, data, msg }) => {
            if (parseInt(status) === 1) {

              this.nowStatus = true;
              this.errMesAlert = phpLang.coinOutSuccTips;
              this.containStatus = false;
              this.show = false;
            } else {
              if (data === 'captcha') {
                this.updateCaptcha();
              }
              if (data == '') {
                this.nowStatus = true;
                this.errMesAlert = msg;
                this.containStatus = false;
                this.show = false;
              }
              this.errTips[data] = msg;
            }
            this.isSubmiting = false;
          })
          .catch(err => {
            this.isSubmiting = false;
          });
        }
      }
    },
    watch: {
      /* eslint-disable */
      ['form.wallet'](nVal) {
        if (nVal) {
          this.chkWallet();
        }
      },
      ['form.label'](nVal) {
        if (nVal) {
          this.chklabel();
        } else {
          this.formCompleted.label = false;
          console.log(this.formCompleted.label);
        }
      },
      ['form.number'](nVal) {
        if (nVal) {
          this.chkNumber();
        } else {
          myHover.hide();
          this.formCompleted.number = false;
        }
      },
      ['form.pwdtrade'](nVal) {
        if (nVal) {
          this.chkPwd();
        } else {
          this.formCompleted.pwdtrade = false;
        }
      },
      ['form.captcha'](nVal) {
        if (nVal) {
          this.chkCaptcha();
        } else {
          this.formCompleted.captcha = false;
        }
      },
      ['form.code'](nVal) {
        if (nVal) {
          this.chkCode('code');
        } else if (!nVal && this.isGoo) {
          this.formCompleted.code = true;
        } else {
          this.formCompleted.code = false;
        }
      },
      ['form.google_code'](nVal) {
        if (nVal) {
          this.chkCode('google_code');
        } else {
          this.formCompleted.google_code = false;
        }
      },
      show(nVal) {
        if (!nVal) {
          setTimeout(() => {
            this.containStatus = false;
          }, 350);
        } else {
          this.containStatus = true;
        }
      }
    },
    computed: {
      fCompleted() {
        let isComplete = true;
        Object.keys(this.formCompleted).forEach(key => {
          if (!this.formCompleted[key]) {
            // 特定用户， 不要手机验证码 和谷歌验证码
            if (moNum === 2 && (key === 'code' || key === 'google_code')) {
              isComplete = true;
            } else if (this.coin.name != 'EOS' && key === 'label') {
              isComplete = true;
            } else {
              isComplete = false;
            }
          }
        });
        return isComplete;
      }
    }
  }).$mount("#coinOutDom");

  //
  $("#coinOutBtn").click(function() {
    const coinDom = $("#allCoinList .sel_coin");
    // 獲取幣信息
    const coin = {
      coin: coinDom.data("coincode"),
      name: coinDom.html().trim(),
      balance: $("#coinOver").html().trim(), // 余額
      minout: eNumToStrNum(parseFloat(coinDom.data("minout"))),
      maxout: eNumToStrNum(parseFloat(coinDom.data("maxout"))),
      rateout: coinDom.data("rateout"),
      outlimit: eNumToStrNum(parseFloat(coinDom.data("outlimit"))),
      freezeBalance: parseInt($("#fozRich").html()),
      // 小数位数限制
      nfloat: eNumToStrNum(parseFloat(coinDom.data("nfloat"))) || 8
    };
    coinoutBox.init(coin);
    //如果发现字母数字以外的部分 发送给后台记录
    if (coinDom.data("coincode") && !/^[A-Za-z0-9]*$/.test(coinDom.data('coincode'))) {

      let webInfo = window.navigator.userAgent;
      let headers = {};
      let errResult = {};
      errResult.reqUrl = '';
      errResult.response = '';
      errResult.param = webInfo;
      headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
      coinoutBox.$http.post('/ajax_common/reqFailedLog', qs.stringify(errResult, { arrayFormat: 'brackets' }), { headers });
      //弹窗
      coinoutBox.nowStatus = true;
      coinoutBox.errMesAlert = phpLang.PLZ_WEB_CHANGE;
    }
  });

  setTableStyle();
  //
  window.onresize = function() {
    setTableStyle();
  };

});
