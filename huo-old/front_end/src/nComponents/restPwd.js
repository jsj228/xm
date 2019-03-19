import 'flex.css';
import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import "nStyle/register/register.scss";
import Vue from 'vue';
import cookie from '@/tools/cookie';
import Promise from 'Promise';
import http from 'plugins/http';
import Alert from 'nComponents/Alert';
import Alertheader from 'nComponents/alert.header';
import register from 'nComponents/register';
import is from 'tools/is';
// import publicFn from 'plugins/publicKey';

// Vue.use(publicFn);
Vue.use(http);
export default () => {
  if (document.getElementById('restPwd')) {
    return new Vue({
      data() {
        return {
          //忘記密碼第一步
          forgetFirst: {
            phone: {
              area: "+86",
              account: "",
              captcha: "",
              regtype: "phone"
            },
            email: {
              account: "",
              captcha: "",
              regtype: "email"
            }
          },
          //忘記密碼第二步
          forgetTwo: {
            google_code: "",
            code: ""
          },
          //忘記密碼第三部
          forgetThree: {
            pwd: "",
            repwd: ""
          },
          //区域选择fn_active所在数字
          register_index: 0,
          //点击语音验证码出现 消失
          voice_ver: false,
          //语音验证码倒计时
          voice_set: false,
          //来电提示
          voice_warn: false,
          //进入倒计时in 退出 out 语音倒计时 mes
          setTimes: 'out',
          //倒计时
          times: '60',
          time_id: '',
          //区域选择是否出现
          area_show: false,
          //地区号码
          area_num: '+86',
          //忘记密码
          forgetPwd: false,
          ttt: 2,
          //提示彈窗
          errMesAlert: "",
          //彈窗顯示控制
          nowStatus: false,
          //聚焦变色
          accountFocus: false,
          pwdFocus: false,
          repwdFocus: false,
          captchaFocus: false,
          codeFocus: false,
          googleFocus: false,
          showActiveModal: false,
          errorArr: {
            //0 为默认情况 不展示，1为提示 2为空白提示 3为格式错误 4为后台放回
            //密码 5 弱 6 中 7 强 8 两次对比
            //10过审
            accStatus: 0,
            pwdStatus: 0,
            repwdStatus: 0,
            captchaStatus: 0,
            codeStatus: 0,
            googleStatus: 0
          },
          //提交按钮可否点击
          disable: true,
          //加载
          submit_now: false,
          //用戶信息
          userInfo: '',
          ver_src: "",
          // 重設密碼成功
          repwdSucc: false,
          // 賬戶所選登錄或註冊的方式  phone或email
          accessType: "",
          //忘记密码 步骤
          forgetStep: 1,
          //发送短信ajax
          message_data: {
            //语音8  短信 0
            action: '0',
            captcha: '',
            phone: '',
            area: ""
          },
          noStepOne: false,
          //是否有谷歌验证码
          isGoogle: true
        };
      },
      created() {
        this.img_ver();
        //判斷是否有手機號 或者郵箱號
        this.init();
        this.isRegister()
        .then((data) => {
          this.forgetFirst.phone.account = this.userInfo.phone;
          this.forgetFirst.phone.area = this.userInfo.area;
          this.forgetFirst.email.account = this.userInfo.email;
        });
      },
      methods: {
        //初始化
        init() {
          if (this.userInfo.email) this.accessType = "email";
          else if (this.userInfo.phone) this.accessType = "phone";
          else this.accessType = "email";
          this.area_num = '+86';
          this.register_index = 0;
          this.errorArr = { accStatus: 0, pwdStatus: 0, repwdStatus: 0, captchaStatus: 0, codeStatus: 0 };
          // 忘记密码部分
          if (!this.noStepOne) {
            this.forgetFirst = {
              phone: {
                area: "+86",
                account: "",
                captcha: ""
              },
              email: {
                account: "",
                captcha: ""
              }
            };
          } else {
            this.forgetFirst.phone.captcha = '';
            this.forgetFirst.email.captcha = '';
          }
          this.forgetTwo = { code: "", google_code: "" };
          this.forgetThree = { pwd: "", repwd: "" };
          //倒計時
          this.times = '60';
          //点击语音验证码出现 消失
          this.voice_ver = false;
          //语音验证码倒计时
          this.voice_set = false;
          //来电提示
          this.voice_warn = false;
          //进入倒计时in 退出 out 语音倒计时 mes
          this.setTimes = 'out';
          //区号选择关闭
          this.area_show = false;
        },
        //忘记密码
        forgetAlert(type, step, isGoogle) {
          if (type) this.noStepOne = true;
          //数据初始化
          this.init();
          //设定为第一步
          if (step) this.forgetStep = step;
          else this.forgetStep = 1;
          this.forgetPwd = true;
          //是否有google验证码
          this.isGoogle = isGoogle;
        },
        close_alert() {
          this.forgetPwd = false;
          if (this.time_id) {
            clearTimeout(this.time_id);
          }
          this.init();
        },
        //切换地区显示和消失
        area_change() {
          this.area_show = !this.area_show;
        },
        //点击列表内容 切换fn_active
        arec_click(index) {
          let arr = index.split(',');
          this.register_index = arr[0];
          this.area_num = arr[1].replace(/(^\s*)|(\s*$)/g, "");
          this.forgetFirst.phone.area = this.area_num;
          this.area_show = false;
        },
        //點擊其他位置收起區域選擇
        closeArea(e) {
          let lists = document.getElementsByClassName('fn_register_area')[0];
          let listLabel = document.getElementsByClassName('fn_register_label')[0];
          if (lists && listLabel) {
            if (!lists.contains(e.target) && !listLabel.contains(e.target)) {
              this.area_show = false;
            }
          }
        },
        switchAccessType(e) {
          this.init();
          let acctype = (e.target).getAttribute('data-acctype') || 'phone';
          this.accessType = acctype;
          // this.errorArr
          for (let i in this.errorArr) {
            this.errorArr[i] = 0;
          }
          clearTimeout(this.time_id);
          if (this.noStepOne) this.forgetStep = 2;
          else this.forgetStep = 1;
        },
        //获取验证码
        get_verify(mes_type) {
          if (mes_type) {
            this.message_data.action = mes_type;
          } else {
            this.message_data.action = '0';
          }

          //短信验证码是 ‘注意来电提示’ 如果打开 则关闭
          if (mes_type === '0' && this.voice_warn === true && !this.disable) {
            this.voice_warn = false;
            this.voice_set = true;
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
                this.times --;
              }
            }, 1000);
            this.sent_message();
          } else {
            return false;
          }
        },
        //图形验证码
        img_ver() {
          this.ver_src = `/index/captcha?v=${parseInt(Math.random() * 10000000)}`;
        },
        //获取语音验证码
        voice_verify() {
          if (this.setTimes === 'out') {
            this.get_verify('8');
            //提示语修改
            this.voice_ver = false;
            this.voice_set = false;
            this.voice_warn = true;
            this.disable = true;
          } else {
            return false;
          }
        },
        //发送短信
        sent_message() {
          let url = '';
          this.errorArr.codeStatus = 0;
          //显示语音验证码
          if (!this.voice_warn) {
            this.voice_set = true;
          }
          //忘記密碼 短信驗證碼 action為11
          let actions = this.message_data.action;
          if (this.accessType === 'phone') {
            url = '/Ajax_Auth/sendregmsg';
            this.message_data = {
              back: 1,
              account: this.forgetFirst.phone.account,
              action: actions,
              area: this.area_num
            };
          } else if (this.accessType === 'email') {
            url = '/Emailverify/findpwd';
            this.message_data = {
              account: this.forgetFirst.email.account,
              regtype: 'forget'
            };
          }

          this.$http.post(url, this.message_data)
          .then(({ data, status, msg }) => {
            // let { status, msg } = data;
            if (status == 0) {
              if (this.voice_set) {
                this.voice_set = false;
                this.voice_warn = true;
              }

              //刷新图形验证码
              this.img_ver();
              this.errMes = msg;
              //提示初始化
              // this.errorArr.accStatus = 0;
              // this.errorArr.captchaStatus = 0;
              // this.errorArr.codeStatus = 0;
              //按钮初始化
              clearTimeout(this.time_id);
              this.setTimes = 'out';
              this.times = '60';
              //php返回提示 data的内容来判断错误地方 短信验证码接口只判读手机和图形验证码
              if (data === 'mo') {
                this.errorArr.accStatus = 4;
              } else if (data === 'captcha') {
                this.errorArr.captchaStatus = 4;
              } else if (data === 'vcode') {
                this.errorArr.codeStatus = 4;
              }
              if (this.voice_warn) {
                this.voice_warn = false;
                this.voice_ver = true;
              }
            }
          })
          .catch((err) => {
            // console.log(err);
          });
        },
        //註冊成功關閉按鈕
        close_btn() {
          this.showActiveModal = false;
        },
        //验证
        verify(val, type, method) {
          //10 标识过审核
          //method  作为标识有 submit 提交 enter 回车点击 focus聚焦 来判断不同情况
          let allnum = /^[0-9]*$/;
          let nums = /\d/g;
          let sign = /[~\!@#%\$\^&\*()\+\-\=\|:\;\,\.<>\/?*]/g;
          let en = /[a-zA-Z]/g;
          let emailReg = /^[A-Za-z0-9\u4e00-\u9fa5-_]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;
          //空值时候  不提交状态置为默认0  提交就 置为错误 2
          switch (type) {
            case 'account':
            if (val) {
              // 賬號為手機
              if (method === 'focus') this.accountFocus = true;
              else this.accountFocus = false;
              if (this.accessType === 'phone') {

                if (!allnum.test(val)) {
                  this.errorArr.accStatus = 3;
                } else if (this.area_num === '+86') {
                  //中国区域限制长度为11  其他国家饿不进行设置
                  //输入框已经限制11位数以下 可不判断大于11位以上
                  if (val.length < 11) {
                    this.errorArr.accStatus = 3;
                    return false;
                  } else {
                    this.errorArr.accStatus = 10;
                    //验证通过的时候进行提交操作
                    if (method === 'enter') {
                      this.register_submit();
                    }
                    return true;
                  }
                } else {
                  this.errorArr.accStatus = 10;
                  if (method === 'enter') {
                    this.register_submit();
                  }
                  return true;
                }
              // 賬號為郵箱
              } else if (this.accessType === 'email') {
                if (!emailReg.test(val)) {
                  this.errorArr.accStatus = 3;
                  return false;
                } else {
                  // this.errorArr.accStatus = 10;
                  return true;
                }
              }

            } else if (method === 'submit') {
              //此时为提交的时候 状态为错误
              this.errorArr.accStatus = 2;
              return false;
            } else if (method === 'focus') {
              //普通情况 置为默认
              this.errorArr.accStatus = 1;
              this.accountFocus = true;
              return false;
            } else if (method === 'enter') {
              this.errorArr.accStatus = 2;
              return false;
            } else {
              this.errorArr.accStatus = 0;
              this.accountFocus = false;
              return false;
            }
              break;
            case 'pwd':
            // let val = this.queryLo.password;
            if (val) {
              if (method === 'focus') this.pwdFocus = true;
              else this.pwdFocus = false;
              if (val.length < 6) {
                this.errorArr.pwdStatus = 3;
                return false;
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
                    this.errorArr.pwdStatus = 5;
                    return false;
                    break;
                  }
                  case 2: {
                    this.errorArr.pwdStatus = 5;
                    return false;
                    break;
                  }
                  case 3: {
                    this.errorArr.pwdStatus = 7;
                    if (method === 'enter') {
                      this.register_submit();
                    }
                    return true;
                    break;
                  }
                  default:

                }
              }
            } else if (method === 'submit') {
              this.errorArr.pwdStatus = 2;
              return false;
            } else if (method === 'focus') {
              this.errorArr.pwdStatus = 1;
              this.pwdFocus = true;
              return false;
            } else if (method === 'enter') {
              this.errorArr.pwdStatus = 2;
              return false;
            } else {
              this.errorArr.pwdStatus = 0;
              this.pwdFocus = false;
              return false;
            }
              break;
            case 'repwd': {
              let toVal = '';
              toVal = this.forgetThree.pwd;
              if (val) {
                if (method === 'focus') this.repwdFocus = true;
                else this.repwdFocus = false;
                if (val.length < 6) {
                  this.errorArr.repwdStatus = 3;
                  return false;
                } else if (val !== toVal) {
                  //再次输入 和 第一次输入不相等
                  this.errorArr.repwdStatus = 8;
                  return false;

                } else {
                  this.errorArr.repwdStatus = 10;
                  if (method === 'enter') {
                    this.register_submit();
                  }
                  return true;
                }
              } else if (method === 'submit') {
                this.errorArr.repwdStatus = 2;
                return false;
              } else if (method === 'focus') {
                this.errorArr.repwdStatus = 1;
                this.repwdFocus = true;
                return false;
              } else if (method === 'enter') {
                this.errorArr.repwdStatus = 2;
                return false;
              } else {
                this.errorArr.repwdStatus = 0;
                this.repwdFocus = false;
                return false;
              }
            }
              break;
            //图形验证码和短信验证码只判断长度
            //图形验证码4  短信验证码6
            case 'captcha':
            if (val) {
              if (method === 'focus') this.captchaFocus = true;
              else this.captchaFocus = false;
              if (val.length < 4) {
                this.errorArr.captchaStatus = 3;
                return false;
              } else {
                this.errorArr.captchaStatus = 10;
                if (method === 'enter') {
                  this.register_submit();
                }
                return true;
              }
            } else if (method === 'submit') {
              this.errorArr.captchaStatus = 2;
              return false;
            } else if (method === 'focus') {
              this.errorArr.captchaStatus = 1;
              this.captchaFocus = true;
              return false;
            } else if (method === 'enter') {
              this.errorArr.captchaStatus = 2;
              return false;
            } else {
              this.errorArr.captchaStatus = 0;
              this.captchaFocus = false;
              return false;
            }
              break;

            case "email":
            if (val) {
              if (val.length < 6) {
                this.errorArr.emailStatus = 3;
                return false;
              } else {
                this.errorArr.emailStatus = 10;
                if (method === 'enter') {
                  this.register_submit();
                }
                return true;
              }
            } else if (method === 'submit') {
              this.errorArr.emailStatus = 2;
              return false;
            } else if (method === 'focus') {
              this.errorArr.emailStatus = 1;
              return false;
            } else if (method === 'enter') {
              this.errorArr.emailStatus = 2;
              return false;
            } else {
              this.errorArr.emailStatus = 0;
              return false;
            }
            break;

            default:
              if (type === 'google' || type === 'code') {
                if (val) {
                  if (method === 'focus') this[`${type}Focus`] = true;
                  else this[`${type}Focus`] = false;
                  if (val.length < 6) {
                    this.errorArr[`${type}Status`] = 3;
                    return false;
                  } else {
                    this.errorArr[`${type}Status`] = 10;
                    if (method === 'enter') {
                      this.register_submit();
                    }
                    return true;
                  }
                } else if (method === 'submit') {
                  this.errorArr[`${type}Status`] = 2;
                  return false;
                } else if (method === 'focus') {
                  this.errorArr[`${type}Status`] = 1;
                  this[`${type}Focus`] = true;
                  return false;
                } else if (method === 'enter') {
                  this.errorArr[`${type}Status`] = 2;
                  return false;
                } else {
                  this.errorArr[`${type}Status`] = 0;
                  this[`${type}Focus`] = false;
                  return false;
                }
              }
              break;
          }
        },
        //控制可否输入
        inputcan(val, type) {
          switch (type) {
            case 'account':
            {
              if (this.accessType === 'phone') {
                //只让输入数字
                if (!val) return;
                let vals = val.replace(/\D/g, '');
                this.forgetFirst.phone.account = vals;
              }
              break;
            }
            case 'pwd': {
              if (!val) return;
              //数字 字母 和部分字符
              let vals = val.replace(/[^\a-\z\A-\Z0-9\~\!@#%\$\^&\*()\+\-\=\|:\;\,\.<>\/?*]/g, '');
              /* eslint-disable no-unused-expressions */
              this.forgetThree.pwd = vals;
              break;
            }
            case 'repwd': {
              let vals = val.replace(/[^\a-\z\A-\Z0-9\~\!@#%\$\^&\*()\+\-\=\|:\;\,\.<>\/?*]/g, '');
              this.forgetThree.repwd = vals;
              break;
            }
            case 'captcha': {
              //字母数字
              let vals = val.replace(/[^\a-\z\A-\Z0-9]/g, '');
              if (this.accessType === 'phone') {
                this.forgetFirst.phone.captcha = vals;
              } else {
                this.forgetFirst.email.captcha = vals;
              }
              this.accessType === 'phone' ? this.forgetFirst.phone.captcha = vals : this.forgetFirst.email.captcha;
              break;
            }
            case 'code': {
              //只让输入数字
              let vals = val.replace(/[^\d]/g, '');
              this.forgetTwo.code = vals;
              break;
            }
            case 'google': {
              //只让输入数字
              let vals = val.replace(/[^\d]/g, '');
              this.forgetTwo.google_code = vals;
              break;
            }
            default: break;
          }
        },
        register_submit() {
          let url = '';
          //登录和注册接口判断

          //验证结果
          let results = true;
          //data
          let resultData = {};
          let resultPromise = new Promise((resolve) => {
            if (this.forgetStep === 1) {
              url = '/ajax_user/erifypPhone';
              if (this.accessType === 'phone') {
                for (let i in this.forgetFirst.phone) {
                  if (this.verify(this.forgetFirst.phone[i], i, 'submit') === false) {
                    results = false;
                    resolve(results);
                  }
                }
                this.forgetFirst.phone.regtype = this.accessType;
                resultData = this.forgetFirst.phone;
              } else if (this.accessType === 'email') {
                for (let i in this.forgetFirst.email) {
                  if (this.verify(this.forgetFirst.email[i], i, 'submit') === false) {
                    results = false;
                    resolve(results);
                  }
                }
                this.forgetFirst.email.regtype = this.accessType;
                resultData = this.forgetFirst.email;
              }

            } else if (this.forgetStep === 2) {
              url = '/ajax_user/authenticate';
              if (!this.isGoogle) delete this.forgetTwo.google_code;
              for (let i in this.forgetTwo) {
                let vi = i === 'google_code' ? 'google' : i;
                if (this.verify(this.forgetTwo[i], vi, 'submit') === false) {
                  results = false;
                  resolve(results);
                }
              }
              this.forgetTwo.regtype = this.accessType;
              resultData = this.forgetTwo;
            } else if (this.forgetStep === 3) {
              url = '/ajax_user/resetPassword';
              for (let i in this.forgetThree) {
                // this.verify(this.forgetThree[i], i, 'submit');
                if (this.verify(this.forgetThree[i], i, 'submit') === false) {
                  results = false;
                  resolve(results);
                }
              }
              this.forgetThree.regtype = this.accessType;
              resultData = this.forgetThree;
            }
            resolve(results);
          });
          resultPromise.then((result) => {
            // console.log(resultData);
            if (result === true) {
              this.subajax(url, resultData);
            }
          });
        },
        subajax(url, data) {
          this.submit_now = true;
          this.disable = false;
          this.$http.post(url, data)
          .then((req) => {
            let { data, status, msg } = req;
            // let { status, msg } = data;
            if (status == 1) {
              if (data.google_key) this.isGoogle = data.google_key;

              if (this.forgetStep === 1) {
                //進入第二步
                this.forgetStep = 2;
              } else if (this.forgetStep === 2) {
                this.forgetStep = 3;
              } else if (this.forgetStep === 3) {
                this.showActiveModal = true;
                this.repwdSucc = true;
                this.forgetPwd = false;
              } else {
                this.showAlert = false;
                if (cookie.getItem('reurl')) {
                  window.location.href = cookie.getItem('reurl');
                } else {
                  window.location.href = '/';
                }
              }

            } else {
              this.errMes = msg;
              //重置验证码
              this.img_ver();
              //对返回的错误进行展示 状态都为4
              if (data === 'captcha') {
                this.errorArr.captchaStatus = 4;
              } else if (data === 'mo' || data === 'phone' || data === 'area' || data === 'email' || data === 'email_bb') {
                this.errorArr.accStatus = 4;
              } else if (data === 'Upassword') {
                this.errorArr.pwdStatus = 4;
              } else if (data === 'smsCaptch' || data === 'code') {
                this.errorArr.codeStatus = 4;
              } else {
                this.errMesAlert = msg;
                this.nowStatus = true;
              }
            }
            this.disable = true;
            this.submit_now = false;
          })
          .catch((err) => {
            // console.log(err);
          });
        },
        loginAlert() {
          this.forgetPwd = false;
          this.showActiveModal = false;
          register.loginAlert();
        },
        //是否有手機號 和 郵箱號
        isRegister() {
          return new Promise((resolve) => {
            this.$http.post('/Ajax_User/getUserInfo', {})
            .then(({ data, status }) => {
              if (data) {
                this.userInfo = data;
                resolve(data);
                //判断当前状态
                if (data.email && data.phone != '****') {
                  this.accessType = 'email';
                } else if (data.email && data.phone === '****') {
                  this.accessType = 'email';
                } else {
                  this.accessType = 'phone';
                }
              } else {
                this.userInfo = {};
              }
            });
          });
        },
        alertfn() {

        }
      }
    }).$mount('#restPwd');
  } else {
    return {};
  }
};
