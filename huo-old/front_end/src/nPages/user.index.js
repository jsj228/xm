import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import 'nStyle/user/common.scss';
import "nStyle/user_tables.scss";
import 'nStyle/user/index/user_index.scss';
//币币弹窗
import "nStyle/userAlert/user_alert.scss";
//綁定手機號和郵箱
import "nStyle/bindInfo/bind_info.scss";

import Vue from 'vue';
import http from 'plugins/http';
import nav from 'nComponents/nav';
import is from 'tools/is';
import HoverWin from 'components/hoverWin';
import getLanguagePack from 'components/tradeLanguagePack';
import { toFixedNum } from 'filters/filters';
/* eslint-disable*/
import Alert from 'nComponents/Alert';
/* eslint-enable*/
//币币弹窗
import transformAccountWin from 'nComponents/userAlert';
//table
import scrollTable from 'nComponents/scrollTable';
//綁定手機和郵箱
// import forget from 'nComponents/restPwd';
import register from 'nComponents/register';
import bindInfo from 'nComponents/blindInfo';

const bindInfos = bindInfo();

window.onload = function () {

  const userAlert = transformAccountWin();

  Vue.use(http);
  let phpLang = getLanguagePack() || {
    LOADING_RMB: "加载中..."
  };
  //人民币悬浮框
  const myHover = new HoverWin($('[data-tormb]'), 'tormb');
  myHover.loadingTips = phpLang.LOADING_RMB;

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
  const user = new Vue({
    data() {
      return {
        //只显示资金币种 隐藏和出现
        coin_show_btn: true,
        //有资金列表
        havelist: true,
        //无资金列表
        nonelist: true,
        //币种详情列表
        coin: {
          //类型
          detail: '',
          //可用余额
          over: '',
          //冻结
          lock: '',
          //总计
          all: ''
        },
        //交易密码弹窗
        showPwd: false,
        // 设置交易密码
        setPwd: false,
        //重置交易密码第一步出现
        showReset: false,
        //重置交易密码第二步出现
        showResetTwo: false,
        setPwdData: {
          p1: '',
          p2: '',
          regtype: ''
        },
        //错误
        //0为默认 1普通提示 2为空白提示 3为格式错误 4为后台返回错误
        //10 过审
        errorArr: {
          p1: 0,
          p2: 0,
          code: 0,
          google: 0
        },
        //错误内容
        errorMes: '',
        submit_now: false,
        //提交按钮可否点击
        disable: true,
        //语音验证码倒计时
        voice_set: false,
        //来电提醒
        voice_warn: false,
        //重新获取
        voice_ver: false,
        //短信验证码时间
        times: '60',
        //进入倒计时in 退出 out 语音倒计时 mes
        setTimes: 'out',
        //短信验证码数据
        message_data: {
          //语音8  短信 7
          action: '7'
        },
        //第一步数据保存
        stepOneData: {
          code: '',
          action: '',
          google_code: '',
          scene: 'RESET_TRADE_PWD'
        },
        moverClick: false,
        //密码修改成功
        resest_success: false,
        //首页原始滑动高度
        oldScroll: {
          scrollTop: '',
          clientHeight: ''
        },
        //弹窗
        nowStatus: false,
        //错误提示
        errMesAlert: '',
        //领取状态
        pwdType: '',
        //法幣和幣幣交易切換 1 法幣 2幣幣
        accountBi: 1,
        //手机端充提币 转币禁止
        disableIn: false,
        disableOut: false,
        disableChange: false,
        coinDetail: '',
        //rmb汇率
        rmbPrice: '',
        //美元
        dollars: "",
        //币种列表币币 有资金
        coinListbiHave: [],
        //币种列表币币 无资金
        coinListbiNo: [],
        //币种列表 法币 有资金
        coinListfaHave: [],
        //币种列表 法币 无资金
        coinListfaNo: [],
        userInfo: '',
        userfabi: '',
        //数据加载
        tabLoging: false,
        //没数据
        no_data: false,
        //输入框焦点
        inputFocusPwd: false,
        inputFocusRePwd: false,
        inputFocusCode: false,
        inputFocusGoogle: false,
        //2邮箱 1 手机 默认邮箱
        tabIndex: 2,
        //邮箱重置
        emailData: {
          regtype: 'trust'
        },
        //邮箱验证码 控制语音验证码不出现
        emailVer: false,
        userInfos: {},
        coinPrice: '',
        //promise 判断接口调用是否over
        isPromise: '',
        //是否有google
        isGoogle: false
      };
    },
    mounted() {
      nav();
      this.getRmbBtc();
      this.getRmbBtc('dob');
      this.getTabList();

      // 重置登录密码
      if (location.href.indexOf('Tset=pwd') > -1) {
        this.showLoginChange();
      }

      // 设置 交易密码
      if (location.href.indexOf('set=tradepwd') > -1) {
        this.setPwdGo();
      }

      // 重置交易密码
      if (window.location.search.indexOf('set=ressetTpwd') > -1) {
        this.resetPwdGo();
      }
    },
    updated() {
      addHoverWin($('#dataTablefa [data-tormb]'), 'tormb', 'left');
      addHoverWin($('#dataTablebi [data-tormb]'), 'tormb', 'left');
      addHoverWin($('[data-old="bi"]'), 'tormb', 'top');
      addHoverWin($('[data-old="fa"]'), 'tormb', 'top');
    },
    methods: {
      //初始化
      init() {
        this.errorArr = {
          p1: 0,
          p2: 0,
          code: 0,
          google: 0
        };
        this.errMes = '';
        this.setPwdData = {
          p1: '',
          p2: ''
        };
      },
      getUserInfo(phone, email) {
        this.userInfos.phone = phone;
        this.userInfos.email = email;
      },
      //google
      getGoogle(hasGoogle) {
        if (hasGoogle) this.isGoogle = true;
      },
      //pc端 获取btc人民币汇率
      //dob换成美元
      getRmbBtc(rmb) {
        if (!rmb) {
          rmb = 'btc';
        }
        this.$http.post(`/ajax_market/coinPrice?coin=${rmb}`)
          .then(({
            data
          }) => {
            if (rmb === 'dob') {
              this.dollars = data;
            } else {
              this.rmbPrice = data;
            }
          });
      },
      //保留8位小数
      toFixedNums(value) {
        // if (value == null) value = 0;
        return toFixedNum(value, 8);
      },
      compPrice(coin, name) {
        let prices = '--';
        if (this.rmbPrice && coin - 0 > 0) {
          if (name === 'btc') {
            prices = toFixedNum((coin * this.rmbPrice), 2);
          } else if (name === 'dob') {
            prices = toFixedNum(coin * this.dollars, 2);
          } else if (this.coinPrice) {
            let coinName = this.coinPrice[name];
            if (!coinName) {
              prices = '--';
            } else {
              prices = toFixedNum((coinName[1] * this.rmbPrice * coin), 2);
            }
          }
        }
        return prices;
      },
      //展示币种详情
      coin_detail(type, coinData) {
        if (type != 'close' && coinData) {

          this.coin.name = coinData.name;
          this.coin.detail = this.userInfo[`${coinData.name}_over`];
          this.coin.lock = this.userInfo[`${coinData.name}_lock`];
          this.coin.all = this.userInfo[`${coinData.name}_over`] + this.userInfo[`${coinData.name}_lock`];

          if (coinData.in_status) this.disableIn = false;
          else this.disableIn = true;

          if (coinData.out_status) this.disableOut = false;
          else this.disableOut = true;

          if (coinData.otc == 0 && coinData.coin_transfer == 0) this.disableChange = false;
          else this.disableChange = true;
        }
        if (type === 'start') {
          this.moverClick = true;
        } else if (type === 'over') {
          this.moverClick = false;
        } else if ((type === 'end' && this.moverClick === true) || type === 'close') {
          this.coin_show_btn = !this.coin_show_btn;
        }
      },
      //只显示资金币种
      showHave() {
        this.nonelist = !this.nonelist;
        Vue.nextTick(() => {
          this.$refs.scrollTabbi.getTbodyTds();
          this.$refs.scrollTabfa.getTbodyTds();
        });

        if (Object.is(this.userInfo, null) && this.accountBi == 1) {
          this.no_data = true;
        }
        if (Object.is(this.userfabi, null) && this.accountBi == 2) {
          this.no_data = true;
        }
      },
      //打开设置交易密码的弹窗
      setPwdGo() {
        this.init();
        this.showPwd = true;
        this.setPwd = true;
      },
      //打开重置交易密码的弹窗
      resetPwdGo() {
        this.init();
        this.showPwd = true;
        this.showReset = true;
        // this.showResetTwo = true;
        // this.resest_success = true;
      },
      //获取短信验证码
      getVerCode(mes_type) {
        this.message_data.action = mes_type;
        //如果为邮箱验证码 语音验证一系列将不出现
        if (this.tabIndex == 2) this.emailVer = true;
        else this.emailVer = false;

        //类型为7 提示语为 ‘注意接听来电’
        if (mes_type == 7 && this.voice_warn) {
          this.voice_warn = false;
          this.voice_set = false;
        } else if (mes_type == 8 && !this.voice_warn) {
          //类型为8 提示语为‘点击获取验证码’
          this.voice_warn = true;
          this.voice_ver = false;
          this.voice_set = false;
        }

        if (this.setTimes === "out" || this.setTimes === 'mes') {
          this.setTimes = 'in';
          this.time_id = setInterval(() => {
            if (this.times <= '0') {
              //计数到0的时候停止计时
              clearTimeout(this.time_id);
              this.setTimes = 'out';
              this.times = '60';
              //如果语音验证码倒计时开始 计时完毕开启语音验证码可点击
              if (this.voice_set || this.voice_warn) {
                this.voice_ver = true;
                this.voice_set = false;
                this.voice_warn = false;
              }
            } else {
              if (!this.voice_warn) this.voice_set = true;

              this.times --;
            }
          }, 1000);
          this.sent_message();
        } else {
          return false;
        }
      },
      sent_message() {
        let url = '';
        let data = {};
        if (this.userInfos.phone && this.userInfos.email) {
          if (this.tabIndex === 1) {
            url = '/ajax_user/smsnocap';
            data = this.message_data;
          } else {
            url = '/Emailverify/findpwd';
            data = this.emailData;
          }
        } else if (!this.userInfos.phone && this.userInfos.email) {
          url = '/Emailverify/findpwd';
          data = this.emailData;
        } else if (this.userInfos.phone && !this.userInfos.email) {
          url = '/ajax_user/smsnocap';
          data = this.message_data;
        }

        this.$http.post(url, data)
          .then(({ data, status, msg }) => {
            if (status != 1) {
              this.errMes = msg;
              clearTimeout(this.time_id);
              this.setTimes = 'out';
              this.times = '60';
              //放出弹窗
              this.nowStatus = true;
              this.errMesAlert = msg;
            }
          })
          .catch((err) => {

          });
      },
      //不輸入空格
      noSpace(val, type) {
        this.setPwdData[`${type}`] = val.replace(/\s+/g, "");
      },
      //只能输入数字
      codeInput(val, type) {
        if (val && !type) {
          this.stepOneData.code = val.replace(/[^\d]/g, '');
        } else if (val && type === 'google') {
          this.stepOneData.google_code = val.replace(/[^\d]/g, '');
        }
      },
      //验证
      verifyVal(type, val, method, setType) {
        let nums = /\d/g;
        let sign = /[~\!@#%\$\^&\*()\+\-\=\|:\;\,\_\'\"\.<>\/?*]/g;
        let en = /[a-zA-Z]/g;
        switch (type) {
          case 'p1':

            if (val) {
              if (method === 'focus') {
                this.inputFocusPwd = true;
              }
              if (val.length < 6) {
                this.errorArr.p1 = 3;
              } else {

                let pwdLeval = 0;
                if (nums.test(val)) {
                  pwdLeval++;
                }
                if (sign.test(val)) {
                  pwdLeval++;
                }

                if (en.test(val)) {
                  pwdLeval++;
                }

                switch (pwdLeval) {
                  case 1: {
                    this.errorArr.p1 = 5;
                    break;
                  }
                  case 2: {
                    console.log(1111);
                    this.errorArr.p1 = 5;
                    break;
                  }
                  case 3: {
                    this.errorArr.p1 = 10;
                    if (method === 'enter') {
                      this.setPwdSubmit(setType);
                    }
                    break;
                  }
                  default:
                }
              }
            } else if ((method === 'submit' || method === 'enter') && !val) {
              this.errorArr.p1 = 2;
            } else if (method === 'focus' && !val) {
              this.inputFocusPwd = true;
              this.errorArr.p1 = 1;
            } else {
              this.inputFocusPwd = false;
              this.errorArr.p1 = 0;
            }
            break;
          case 'p2':
            {
              let p1Val = this.setPwdData.p1;
              if (val) {
                if (method === 'focus') {
                  this.inputFocusRePwd = true;
                }
                if (val.length < 6) {
                  this.errorArr.p2 = 3;
                } else if (p1Val != val) {
                  this.errorArr.p2 = 3;
                } else {
                  this.errorArr.p2 = 10;
                  if (method === 'enter') {
                    this.setPwdSubmit(setType);
                  }
                }
              } else if ((method === 'submit' || method === 'enter') && !val) {
                this.errorArr.p2 = 2;
              } else if (method === 'focus' && !val) {
                this.inputFocusRePwd = true;
                this.errorArr.p2 = 1;
              } else {
                this.inputFocusRePwd = false;
                this.errorArr.p2 = 0;
              }
            }
            break;
          default:
          if (val) {
            if (method === 'focus') {
              this[`inputFocus${type}`] = true;
            }
            if (val.length < 6) {
              this.errorArr[`${type}`] = 3;
            } else {
              this.errorArr[`${type}`] = 10;
            }
          } else if (method === 'submit' && !val) {
            this.errorArr[`${type}`] = 2;
          } else if (method === 'focus' && !val) {
            this[`inputFocus${type.charAt(0).toUpperCase() + type.slice(1)}`] = true;
            this.errorArr[`${type}`] = 1;
          } else {
            this[`inputFocus${type.charAt(0).toUpperCase() + type.slice(1)}`] = false;
            this.errorArr[`${type}`] = 0;
          }
          break;
        }
      },
      //提交设置的密码
      setPwdSubmit(type) {
        this.pwdType = type;
        if (this.tabChange == 1) this.setPwdData.regtype = 'phone';
        else this.setPwdData.regtype = 'email';
        for (let i in this.setPwdData) {
          this.verifyVal(i, this.setPwdData[i], 'submit');
        }
        if (this.errorArr.p1 != 10 || this.errorArr.p2 != 10) {
          return false;
        } else {
          let url = '';
          if (type === 'set') {
            url = '/ajax_user/setTradePwd';
          } else {
            url = '/ajax_user/resetTradePwd';
          }
          this.setPwdAjax(url, this.setPwdData, type);
        }
      },
      setPwdAjax(url, data, type) {
        this.submit_now = true;
        this.disable = false;
        this.$http.post(url, data)
          .then(({ data, msg, status }) => {
            if (status == 1) {
              if (type === 'set') {
                this.pwdType = 'set';
                // this.showPwd = false;
              } else {
                this.showResetTwo = false;
                // this.showPwd = false;
              }
              this.resest_success = true;
            } else if (data.data === 'pw1') {
              this.errorArr.p1 = 4;
              this.errorMes = msg;
            } else {
              this.nowStatus = true;
              this.errMesAlert = msg;
            }
          this.submit_now = false;
          this.disable = true;
        })
        .catch((err) => {
          this.submit_now = false;
          this.disable = true;
        });
      },
      //重置验证码第一步保存
      stepTwo() {
        this.submit_now = true;
        // 單獨顯示 手機 和 郵箱的時候 判斷
        let tabPhone = this.userInfos.phone;
        let tabEmail = this.userInfos.email;

        if (tabPhone != '****' && tabEmail) {
          if (this.tabIndex == 1) {
            this.stepOneData.action = this.message_data.action;
            this.stepOneData.regtype = 'phone';
          }
          else if (this.tabIndex == 2) this.stepOneData.regtype = 'email';
        } else if (tabPhone != '****' && !tabEmail) {
          this.stepOneData.regtype = 'phone';
          this.stepOneData.action = this.message_data.action;
        } else {
          this.stepOneData.regtype = 'email';
        }

        // this.stepOneData.action = this.message_data.action;
        // this.stepOneData.regtype = 'phone';

        this.verifyVal('code', this.stepOneData.code, 'submit');
        this.verifyVal('google', this.stepOneData.google_code, 'submit');

        if (this.errorArr.code == 10) {
          this.$http.post('/ajax_user/smsVerify', this.stepOneData)
          .then(({ status, msg }) => {
            if (status === 1) {
              this.showResetTwo = true;
              this.showReset = false;
            } else {
              this.errorMes = msg;
              this.errorArr.code = 4;
            }
            this.submit_now = false;
          })
          .catch((err) => {
            // console.log(err);
            this.submit_now = false;
          });
        } else {
          this.submit_now = false;
        }
      },
      //法幣 幣幣切換
      tabAccount(index) {
        this.accountBi = index;
        //表头重置
        // this.$refs.scrollTab.getTbodyTds();
        if (this.userInfo && Object.keys(this.userInfo).length === 0 && this.accountBi == 1) {
          this.no_data = true;
        } else {
          this.no_data = false;
        }

        if (this.userfabi && Object.keys(this.userfabi).length === 0 && this.accountBi == 2) {
          this.no_data = true;
        } else {
          this.no_data = false;
        }

        this.$nextTick(() => {
          this.$refs.scrollTabbi.getTbodyTds();
          this.$refs.scrollTabfa.getTbodyTds();
        });
      },
      //重置交易密码关闭
      alertfn() {
        this.showPwd = false;
        this.setPwd = false;
        this.showReset = false;
        this.showResetTwo = false;
        this.init();
      },
      //列表数据
      getTabList() {
        this.tabLoging = true;
        this.$http.post('/Ajax_User/accountuser')
        .then(({ data, status }) => {

          let { bibi, fabi, user, userfabi, coinPrice } = data;
          if (bibi.on) {
            this.coinListbiHave = bibi.on.owned;
            this.coinListbiNo = bibi.on.others;
          }
          if (fabi.on) {
            this.coinListfaHave = fabi.on.owned;
            this.coinListfaNo = fabi.on.others;
          }

          this.coinPrice = coinPrice;
          this.userInfo = Object.assign({}, user);
          this.userfabi = Object.assign({}, userfabi);
        })
        .then(() => {
          this.tabLoging = false;
          if (Object.keys(this.userInfo).length === 0 && this.accountBi == 1) {
            this.no_data = true;
          } else {
            this.no_data = false;
          }

          if (Object.keys(this.userfabi).length === 0 && this.accountBi == 2) {
            this.no_data = true;
          } else {
            this.no_data = false;
          }

          //表头重置
          this.$nextTick(() => {
            this.$refs.scrollTabbi.getTbodyTds();
            this.$refs.scrollTabfa.getTbodyTds();
          });
        });
      },
      //彈窗  幣幣法幣互轉
      userAlert(coin) {
        this.$http.post('/Ajax_User/accountuser', {
            coin
          })
          .then(({ data, status, msg }) => {
            if (status === 1) {
              if (this.accountBi == 1) {
                data.opt_type = 'out';
                userAlert.showUserAlert(data);
              } else {
                data.opt_type = 'in';
                userAlert.showUserFaAlert(data);
              }
            } else {
              this.nowStatus = true;
              this.errMesAlert = msg;
            }
          });
      },
      //綁定手機 郵箱彈窗
      bindInfo(type) {
        bindInfos.alertBind(type);
      },
      //修改登录密码
      showLoginChange() {
        // if (this.userInfos) forgets.forgetAlert();
        if (this.userInfos) register.forgetAlert('noStepOne', 2, this.isGoogle);
      },
      //修改成功彈窗關閉
      closeAlert() {
        this.resest_success = false;
        this.showPwd = false;
        //只有在設置交易密碼的時候才需要刷新頁面
        if (this.setPwd === 'set') {
          window.location.reload();
        }
      },
      //回调
      callfn() {
        // if (this.pwdType === 'set') {
        //   window.location.reload();
        // }
      },
      tabChange(index) {
        this.tabIndex = index;
      },
      jumpTrade() {
        window.location.href = "/trade";
      },
      jumpCoin(item, type) {
        window.location.href = `/user/coin${type}?coin=${item.name}`;
      },
      faJump(url) {
        window.location.href = url;
      }
    },
    components: {
      scrollTable
    },
    watch: {
      //监听弹窗是否弹出
      //弹出式需要给body增加样式
      showPwd() {
        if (this.showPwd) {
          document.body.classList.add('no_scroll_body');
          document.getElementsByClassName('fn_bg_content')[0].classList.remove('bg_content');
        } else {
          document.getElementsByClassName('fn_bg_content')[0].classList.add('bg_content');
          document.body.classList.remove('no_scroll_body');
        }
      }
    }
  }).$mount('#user');
};
export default {};
