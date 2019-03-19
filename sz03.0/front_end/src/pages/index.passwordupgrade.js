import 'nStyle/resetTradePwd/resetTradePwd.scss';
import Vue from 'vue';
import http from 'plugins/http';
import nav from 'nComponents/nav';
import cookie from 'tools/cookie';
import _ from 'lodash';

Vue.use(http);

window.onload = function() {
  let navVue = nav();

  new Vue({
    data() {
      return {
        form: {
          loginPwd1: '',
          loginPwd2: '',
          tradePwd1: '',
          tradePwd2: '',
          captcha: '',
          regtype: '',
          code: ''
        },
        /* 输入框提示状态
        * 0: 没有显示
        * 1: 默认 黑色字 提示（请输入。。。）
        * 2: 警告色 默认提示 输入为空
        * 3：长度不对提示
        * 4: 包含空格
        * 5: 强度不够
        * 6：通过
        * 7：两次密码不一致
        * 9:接口返回错误提示
        */
        formTips: {
          loginPwd1: null,
          loginPwd2: null,
          tradePwd1: null,
          tradePwd2: null,
          captcha: '',
          code: ''
        },
        // form input Type 1: password
        formVis: {
          loginPwd1: false,
          loginPwd2: false,
          tradePwd1: false,
          tradePwd2: false
        },
        captchaSrc: '',
        codeTime: 0,
        // 修改成功
        fixSucc: false,
        gettingSms: false,
        submitting: false
      }
    },
    created() {
      this.changeCap();
    },
    mounted() {

    },
    methods: {
      getRegType(regtype) {
        this.form.regtype = regtype;
      },
      // 密码强度
      pwdLev(val) {
        let lev = 0;
        if (val.match(/[0-9]+/g)) {
          lev++;
        }
        if (val.match(/[a-z]+/g)) {
          lev++;
        }
        if (val.match(/[A-Z]+/g)) {
          lev++;
        }
        if (val.match(/[~\!@#%\$\^&\*()\+\-\=\|:\;\,\_\'\"\.<>\/?*]+/g)) {
          lev++;
        }
        return lev;
      },
      hasSpace(val) {
        return val.match(/\s+/);
      },
      // 校验登录密码
      loginPwdCheck(val) {
        let verify = 0;
        // 长度不够
        if (val.length < 6) {
          verify = 3;
        }
        // 是否有空格
        else if (this.hasSpace(val)) {
          verify = 4;
        }
        // 密码强队组合不够
        else if(this.pwdLev(val) < 3) {
          verify = 5;
        }
        // 密码通过规则
        else {
          verify = 6;
        }
        return verify;
      },
      // check input
      checkInp(step) {
        let val = this.form[step];
        let verify = 0;
        // 输入 为空
        if (val.length === 0) {
          verify = 0;
        }
        // 输入不为空
        else {
          switch (step) {
            case 'loginPwd1': {
              if (this.form.loginPwd1 === this.form.tradePwd1 || this.form.loginPwd1 === this.form.tradePwd2) {
                verify = 8;
              }
              else {
                verify = this.loginPwdCheck(val);
              }
              break;
            }
            case 'loginPwd2': {
              if (this.form.loginPwd2 === this.form.tradePwd1 || this.form.loginPwd2 === this.form.tradePwd2) {
                verify = 8;
              }
              // 两次密码不一样
              else if (this.form.loginPwd1 != this.form.loginPwd2) {
                verify = 7;
              }
              // 密码常规判断
              else {
                verify = this.loginPwdCheck(val);
              }
              break;
            }
            case 'tradePwd1': {
              // 交易密码 不能跟 登录密码相同
              if (this.form.loginPwd1 === this.form.tradePwd1 || this.form.loginPwd2 === this.form.tradePwd1) {
                verify = 8;
              } else {
                verify = this.loginPwdCheck(val);
              }
              break;
            }
            case 'tradePwd2': {
              // 交易密码 不能跟 登录密码相同
              if (this.form.loginPwd1 === this.form.tradePwd2 || this.form.loginPwd2 === this.form.tradePwd2) {
                verify = 8;
              }
              // 两次密码不一样
              else if (this.form.tradePwd1 != this.form.tradePwd2) {
                verify = 7;
              }
              // 密码常规判断
              else {
                verify = this.loginPwdCheck(val);
              }
              break;
            }
            case 'captcha': {
              if (val.length < 4) {
                verify = 3;
              } else {
                verify = 6;
              }
              break;
            }
            case 'code': {
              if (val.length < 6) {
                verify = 3;
              } else {
                verify = 6;
              }
              break;
            }
            default:
          }
        }
        return verify;
      },
      focus(step) {
        this.formTips[step] = 1;
      },
      blur(step) {
        let result = true;
        let verify = this.checkInp(step);
        //
        if (verify != 6) {
          result = false;
        }
        this.formTips[step] = verify;
        return result;
      },
      ramdomNum() {
        return parseInt(Math.random() * 10000000);
      },
      changeCap() {
        this.captchaSrc = `/index/captcha?v=${this.ramdomNum()}`;
      },
      // 倒计时
      countDown() {
        this.codeTime = 60;
        this.codeMark = setInterval(() => {
          if (this.codeTime  <= 0) {
            clearTimeout(this.codeMark);
          } else {
            this.codeTime--;
          }
        }, 1000);
      },
      getVsCode() {
        // 图形验证码不正确
        if (!this.blur('captcha')) {
          if (!this.form.captcha) {
            this.formTips.captcha = 2;
          }
          return;
        }
        if (this.codeTime > 0) return;
        if (this.gettingSms) return;
        this.gettingSms = true;
        this.countDown();

        let url = null;
        let data = null;
        if (this.form.regtype === 'phone') {
          url =  '/Ajax_User/sms';
          data = { action: 7, captcha: this.form.captcha };
        } else {
          url =  'Emailverify/findpwd';
          data = {regtype: 'trust'}
        }
        this.$http.post(url, data)
          .then(({status, data, msg}) => {
            if (parseInt(status) != 1) {
              this.formTips[data] = msg;
              this.changeCap();
              this.codeTime = 0;
              clearTimeout(this.codeMark);
            }
            this.gettingSms = false;
          })
          .catch(err => {
            this.gettingSms = false;
          });
      },
      submit() {
        console.log(this.form);
        let complete = true;
        _.keys(this.form).forEach((key) => {
          if (key != 'regtype') {
            if (!this.form[key]) {
              complete = false;
              this.formTips[key] = 2;
            } else {
              let verify = this.checkInp(key)
              if (verify != 6){
                complete = false;
                this.formTips[key] = verify;
              }
            }
          }
        });
        if (!complete) return;
        if (this.submitting) return;
        this.submitting = true;
        console.log(111111);
        this.$http.post('/ajax_user/resetPwds', Object.assign({}, this.form))
          .then(({ status, data, msg }) => {
            if (data.need_login && data.need_login) {
              location.href = '/?login';
            }
            if (parseInt(status) === 1) {
              this.fixSucc = true;
              sessionStorage.setItem('rssucc', 1);
            } else {
              this.changeCap();
              if (data) {
                this.formTips[data] = msg;
              }
              else {
                this.formTips.tradePwd1 = msg;
              }
            }
            this.submitting = false;
          })
          .catch(err => {
            this.submitting = false;
            console.log(err);
          });
      }
    }
  }).$mount('#resetTradPwd');
}
