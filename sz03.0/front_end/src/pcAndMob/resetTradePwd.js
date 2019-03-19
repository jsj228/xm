import '@/styles/userCenter/resetTradePwd.scss';
import Alert from 'tools/alert/alert';
import http from 'tools/http';
import Vue from 'vue';

const myAlert = new Alert();

// 重置交易密码
const resetPwd = new Vue({
  data: {
    setTradePwd: false,
    // 步骤
    step: 1,
    // 倒计时 time
    countTimeCode: '',
    // 手机验证码类型 7 短信 8电话
    action: 7,
    // 倒计时 60s
    count: 0,
    // 显示语音验证码按钮
    voiceBtn: false,
    // 提醒来电 短信验证码
    voiceMsg: "",
    // 交易密码强度
    pwdLe: '',
    //
    formDate: {
      code: '',
      p1: '',
      p2: '',
    },
    dataTips: {
      code: '',
      p1: '',
      p2: '',
    },
    errMsg: {
      code: '',
      p1: '',
      p2: '',
    },
    // 是否正在提交
    isSubmit: false,
    loadingShow: false
  },
  mounted() {

  },
  methods: {
    close() {
      this.setTradePwd = false;
      this.step = 1;
      this.formDate = {
        code: '',
        p1: '',
        p2: '',
      };
      this.dataTips = {
        code: '',
        p1: '',
        p2: '',
      };
      this.action = 7;
      this.voiceBtn = false;
      this.count = 0;
      this.clearCount();
      this.voiceBtn = false;
    },
    inputFocus(type) {
      this.dataTips[type] = 1;
    },
    inputBlur(type) {
      let val = this.formDate[type] + '';
      let isCorrect = true;
      switch (type) {
        case 'code': {
          if (val) {
            if (val.length < 6) {
              this.dataTips[type] = 2;
              isCorrect = false;
            } else {
              this.dataTips[type] = '';
            }
          } else {
            if (this.isSubmit) {
              this.dataTips[type] = 4;
            } else {
              this.dataTips[type] = '';
            }
            isCorrect = false;
          }
          break;
        }
        case 'p1': {
          if (val) {
            if (val.length < 6) {
              this.dataTips[type] = 2;
              isCorrect = false;
            } else {
              //
              // isCorrect = this.checkPwd(type);
              this.dataTips[type] = '';
            }
          } else {
            if (this.isSubmit) {
              this.dataTips[type] = 4;
            } else {
              this.dataTips[type] = '';
            }
            isCorrect = false;
          }
          // 校验交易密码和登陆密码是否一样
          if (isCorrect) this.isTheSameAsLoginPwd();
          break;
        }
        case 'p2': {
          if (val) {
            if (val.length < 6) {
              this.dataTips[type] = 2;
              isCorrect = false;
            } else {
              if (val != this.formDate.p1) {
                this.dataTips[type] = 4;
                isCorrect = false;
              } else {
                this.dataTips[type] = '';
              }
              //
              // isCorrect = this.checkPwd(type);
            }
          } else {
            if (this.isSubmit) {
              this.dataTips[type] = 4;
            } else {
              this.dataTips[type] = '';
            }
            isCorrect = false;
          }
          break;
        }
        default:
      }
      return isCorrect;
    },
    // 校验是否跟登陆密码一样
    isTheSameAsLoginPwd() {
      http({
        method: 'POST',
        data: {
          p1: this.formDate.p1
        },
        success: ({status, data, msg}) => {
          let intStatus = parseInt(status);
          if (intStatus != 1) {
            this.dataTips.p1 = 3;
            this.errMsg.p1 = msg;
          }
        }
      })
    },
    //
    checkPwd(type) {
      let allReg = /[a-zA-Z\d~!@#$^&*()+-=|:;,.<>\/?]{6,16}/;
      let numReg = /\d/g;
      let letterReg = /[a-zA-Z]/g;
      let dotReg = /[~!@#\$\^&\*\(\)\+\-=\|:;,\.<>\/\?]/g;
      let pwdLevel = 0;
      let val = this.formDate[type];
      // 校验密码 强度
      if (allReg.test(val)) {
        // 字母
        if (val.match(numReg)) {
          pwdLevel++;
        }
        // 字母
        if (val.match(letterReg)) {
          pwdLevel++;
        }
        // 特殊字符
        if (val.match(dotReg)) {
          pwdLevel++;
        }
      }
      this.pwdLe = pwdLevel;
    },
    // 获取手机验证码
    getPhoneCode(type) {
      this.count = 60;
      this.countTime();
      this.action = type;
      // 隐藏 语音验证码按钮
      this.voiceBtn = false;
      if (type === 8) {
        this.voiceMsg = true;
      }
      // return;
      // action=7     语音8
      http({
        url: '/ajax_user/smsnocap',
        method: "POST",
        data: {
          action: type
        },
        success: ({status, data, msg}) => {
          let intStatus = parseInt(status);
          if (intStatus != 1) {
            this.clearCount();
          }
        },
        error: () => {
          this.clearCount();
        }
      })
    },
    // 倒计时
    countTime() {
      this.countTimeCode = setInterval(() => {
        this.count--;
        if (this.count === 0) {
          this.clearCount();
        }
      }, 1000);
    },
    // 停止倒计时
    clearCount() {
      clearTimeout(this.countTimeCode);
      this.count = 0;
      // 显示语音验证码 按钮
      if (!this.voiceBtn) this.voiceBtn = true;
      // 隐藏收听电话提示
      if (this.voiceMsg) this.voiceMsg = false;
    },
    // 下一步
    nextStep() {
      if (this.isSubmit) return;
      this.isSubmit = true;
      if (!this.inputBlur('code')) {
        return this.isSubmit = false;
      };
      this.loadingShow = true;
      http({
        method: "POST",
        url: '/ajax_user/smsVerify',
        data: {
          code: this.formDate.code,
          action: this.action,
          scene: 'RESET_TRADE_PWD'
        },
        success: ({status, data, msg}) => {
          let intStatus = parseInt(status);
          if (intStatus === 1) {
            this.step = 2;
          } else {
            this.loadingShow = false;
            myAlert.show(msg);
            this.formDate.code = '';
            clearTimeout(this.countTimeCode);
            this.count = 0;
            // this.clearCount();
          }
          this.isSubmit = false;
          this.loadingShow = false;
        },
        error: () => {
          this.loadingShow = false;
          this.isSubmit = false;
        }
      });
    },
    // 设置交易密码
    setTradePwdFn() {
      if (this.isSubmit) return;
      this.isSubmit = true;
      if (!this.inputBlur('p1') || !this.inputBlur('p2')) {
        return this.isSubmit = false;
      }
      // console.log(this.formDate);

      http({
        method: "POST",
        url: '/ajax_user/resetTradePwd',
        data: {
          p1: this.formDate.p1,
          p2: this.formDate.p2
        },
        success: ({status, data, msg}) => {
          let intStatus = parseInt(status);
          if (intStatus === 1) {
            this.step = 3;
          } else if (data) {
            this.formDate[data] = 3;
            this.errMsg[data] = msg;
          }
          this.isSubmit = false;
        },
        error: () => {
          this.isSubmit = false;
        }
      });
    },
    input(type) {
      let val = this.formDate[type];
      if (/\s+/.test(val)) {
        this.formDate[type] = val.replace(/\s/g, '');
      }
    }
  },
  watch: {
    // 手机验证码 只输入 数字
    'formDate.code': function(nVal) {
      let numReg = /^(\d+)&/;
      let result = '';
      if (nVal && !numReg.test(nVal)) {
        result = parseInt(nVal);

        if (!isNaN(result)) {
          this.formDate.code = result;
        } else {
          this.formDate.code = '';
        }
      }
    },
    setTradePwd: function(val) {
      if ($("#sectionBody").length > 0) {
        if (val) {
          $("#bodyContent").hide();
        } else {
          $("#bodyContent").show();
        }
      }
    }
  }
}).$mount('#resTPWD');

export default resetPwd;
