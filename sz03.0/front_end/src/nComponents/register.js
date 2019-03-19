import 'flex.css';
import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import "nStyle/register/register.scss";
import Vue from 'vue';
import cookie from '@/tools/cookie';
import Promise from 'Promise';
import http from 'plugins/http';
import is from 'tools/is';
import forget from 'nComponents/restPwd';
import isMob from '@/utils/isMob.js';
import Alert from 'nComponents/Alert';
import Alertheader from 'nComponents/alert.header';

// Vue.use(publicFn);
Vue.use(http);
let forgets = null;
const alerts = new Vue({
	data() {
		return {
			// emailActiveLink激活链接过来的状态 0激活失败  1 成功 2链接失效
			emailStatus: {
				showActiveModal: false,
				emailActiveLink: ''
			},
			// 重設密碼成功
			repwdSucc: false,
			// 是否顯示重發郵件的div
			showReMail: false,
			// 賬戶所選登錄或註冊的方式  phone或email
			accessType: "",
			//数据
			//注册用数据
			queryRe: {
				phone: {
					account: '',
					pwd: '',
					repwd: '',
					captcha: '',
					code: '',
					area: '+86',
					regtype: 'phone'
				},
				email: {
					account: '',
					pwd: '',
					repwd: '',
					regtype: 'email'
				}
			},
			//登录用数据
			queryLo: {
				phone: {
					account: "",
					pwd: '',
					captcha: '',
					area: '+86',
					regtype: 'phone'
				},
				email: {
					account: "",
					pwd: '',
					captcha: '',
					regtype: 'email'
				}
			},
			//google数据
			googleData: {
				regtype: "",
				code: ''
			},
			//区域选择fn_active所在数字
			register_index: 0,
			//区域选择是否出现
			area_show: false,
			//图形验证码地址
			ver_src: '',
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
			//地区号码
			area_num: '+86',
			//用户协议
			checkeds: true,
			//注册 re or 登录 lo
			now_type: '',
			// $http: http.install,
			//发送短信ajax
			message_data: {
				//语音8  短信 0
				action: '0',
				captcha: '',
				phone: '',
				area: ""
			},
			//错误提示
			errorArr: {
				//0 为默认情况 不展示，1为提示 2为空白提示 3为格式错误 4为后台放回
				//密码 5 弱 6 中 7 强 8 两次对比
				//10过审
				// phoneStatus: 0,
				// emailStatus: 0,
				accStatus: 0,
				pwdStatus: 0,
				repwdStatus: 0,
				captchaStatus: 0,
				codeStatus: 0
			},
			//php返回提示
			errMes: '',
			//登录注册展示
			showAlert: false,
			//提交按钮可否点击
			disable: true,
			//加载
			submit_now: false,
			//提示彈窗
			errMesAlert: "",
			//彈窗顯示控制
			nowStatus: false,
			//登錄註冊成功后的回調函數
			successFn: "",
			//登錄 註冊成功判斷
			isLogin: false,
			isSuccess: false,
			//聚焦变色
			accountFocus: false,
			pwdFocus: false,
			repwdFocus: false,
			captchaFocus: false,
			codeFocus: false,
			iviTis: '',
			//发送成功
			sentSuccess: false,
			//手机端 1 pc 0
			isMob: 0,
			//cookie里储存的语言
			now_langs: "+86",
			googleShow: false
		};
	},
	created() {
		let langs = cookie.getItem('LANG');
		this.now_langs = langs;
		// this.$getPublicKey();
		//首先判断是否有手机号
		forgets = forget();
	},
	mounted() {
		this.getHref();
		this.img_ver();
		this.getIviInfo();
		// this.forgetAlert();
		this.isMob = isMob();
	},
	methods: {
		hasArea(area) {
			if (area) {
				this.area_num = area;
			}
		},
		init() {
			//初始化
			//google close
			this.googleShow = false;
			this.accessType = "phone";
			this.queryRe = {
				phone: {
					account: '',
					pwd: '',
					repwd: '',
					captcha: '',
					code: '',
					area: '+86',
					regtype: 'phone'
				},
				email: {
					account: '',
					pwd: '',
					repwd: '',
					regtype: 'email'
				}
			};
			this.queryLo = {
				phone: {
					account: "",
					pwd: '',
					captcha: '',
					area: '+86',
					regtype: 'phone'
				},
				email: {
					account: "",
					pwd: '',
					captcha: '',
					regtype: 'email'
				}
			};
			// this.area_num = '+86';

			this.register_index = 0;
			this.errorArr = {
				accStatus: 0,
				pwdStatus: 0,
				repwdStatus: 0,
				captchaStatus: 0,
				codeStatus: 0
			};
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
		getHref() {
			let hrefData = window.location.href;
			//判断当前页面为登录页面还是注册页面
			if (hrefData.indexOf('alert=register') != '-1') {
				this.now_type = 're';
				this.registerAlert();
			} else if (hrefData.indexOf('?login') != '-1') {
				this.now_type = 'lo';
				this.loginAlert();
				// 从邮箱激活链接跳转过来
			} else if (hrefData.indexOf('emailactive') != '-1') {
				this.emailAlert();
			}
		},
		registerAlert(fn) {
			//数据初始化
			this.init();
			let hrefData = window.location.href;

			if (hrefData.indexOf('emailactive') != "-1") {
				window.location.href = '/?alert=register';
			} else {
				this.accessType = "phone";
				// this.accessType = 'phone';
				this.now_type = 're';
				this.showAlert = true;
				this.forgetPwd = false;
				// 隱藏郵件彈窗
				this.emailStatus.showActiveModal = false;
			}
		},
		loginAlert(fn) {
			//数据初始化
			this.init();
			this.accessType = "phone";
			// this.accessType = 'phone';
			this.now_type = 'lo';
			this.showAlert = true;
			//成功页面关闭
			// 隱藏郵件彈窗
			this.emailStatus.showActiveModal = false;
		},
		// 郵箱激活鏈接彈框
		emailAlert() {
			this.now_type = '';
			this.showAlert = false;
			this.emailStatus.showActiveModal = true;
			this.emailStatus.emailActiveLink = this.getUrlQuery('emailactive');
		},
		//忘记密码
		forgetAlert(type, step, isGoogle) {
			//数据初始化
			const vm = this;

			let showAlert = new Promise((resolve) => {
				vm.init();
				resolve();
			});

			showAlert.then(() => {
				this.init();
				forgets.forgetAlert(type, step, isGoogle);
				this.showAlert = false;
				//关闭成功页面
				// 关闭邮箱提示类的弹窗
				this.emailStatus.showActiveModal = false;
			});

		},
		//调用userInfo
		getUserInfos() {
			return forgets.isRegister();
		},
		//pc端关闭按钮
		close_alert() {
			//注册登录为showAlert
			this.showAlert = false;
			if (this.time_id) {
				clearTimeout(this.time_id);
			}
			//进行初始化
			this.init();
		},
		switchAccessType(e) {
			let acctype = null;
			if (e && e.target) {
				acctype = (e.target).getAttribute('data-acctype') || 'phone';
			} else {
				acctype = e;
			}

			this.accessType = acctype;
			// this.errorArr
			for (let i in this.errorArr) {
				this.errorArr[i] = 0;
			}
			clearTimeout(this.time_id);
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
			this.queryRe.phone.area = this.area_num;
			this.queryLo.phone.area = this.area_num;
			this.area_show = false;
			//切换后对手机号进行验证
			if (this.now_type === 're') {
				if (this.accessType === 'phone') {
					console.log(this.queryRe.phone.account, 'account')
					this.verify(this.queryRe.phone.account, 'account');
				} else {
					this.verify(this.queryRe.email.account, 'account');
				}
			}
		},
		toggleShowReMail() {
			this.showReMail = !this.showReMail;
		},
		remail() {
			this.$http.post('/Emailverify/retrysent', this.query)
				.then(res => {
					if (res.status == 1) {
						// 發送成功
						this.sentSuccess = true;
					} else if (res.status == 0) {
						// 發送失敗
						this.sentSuccess = false;
						this.nowStatus = true;
						this.errMesAlert = res.msg;
					}
				})
				.catch(err => {

				});
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
		//图形验证码
		img_ver() {
			this.ver_src = `/index/captcha?v=${parseInt(Math.random() * 10000000)}`;
		},
		//获取验证码
		get_verify(mes_type) {
			//進行手機號和 圖形驗證碼的驗證
			console.log('准备。。。');

			if (this.now_type === 're') {
				this.verify(this.queryRe.phone.account, 'account', 'submit');
				this.verify(this.queryRe.phone.captcha, 'captcha', 'submit');
			}


			this.sent_message();

			if (this.errorArr.accStatus === 10 && this.errorArr.captchaStatus === 10) {
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
							this.times--;
						}
					}, 1000);
					// console.log('准备。。。')
					// this.sent_message();
				} else {
					return false;
				}
			} else {
				return false;
			}
		},
		//jsj--不要注冊   获取语音验证码
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
			console.log('sent_message', '....');
			let url = '';
			this.errorArr.codeStatus = 0;
			//显示语音验证码
			if (!this.voice_warn) {
				this.voice_set = true;
			}

			//re 注册， lo 登录 fo忘记密码
			if (this.now_type === 're') {
				url = '/ajax_user/sendregmsg';
			} else if (this.now_type === 'lo' && this.accessType === 'phone') {
				url = '/Ajax_Auth/sendregmsg';
			}

			this.message_data.phone = this.queryRe.phone.account;
			this.message_data.captcha = this.queryRe.phone.captcha;
			this.message_data.area = this.area_num;

			console.log('message', this.message_data);

			this.$http.post(url, this.message_data)
				.then(({
					data,
					status,
					msg
				}) => {
					// let { status, msg } = data;
					if (status == 0) {
						if (this.voice_set) {
							this.voice_set = false;
							this.voice_warn = true;
						}

						//刷新图形验证码
						this.img_ver();
						this.errMes = msg;
						//按钮初始化
						clearTimeout(this.time_id);
						this.setTimes = 'out';
						this.times = '60';
						//php返回提示 data的内容来判断错误地方 短信验证码接口只判读手机和图形验证码
						if (data === 'mo') {
							this.errorArr.accStatus = 4;
						} else if (data === 'captcha') {
							this.errorArr.captchaStatus = 4;
						} else if (this.now_type === 'fo' || data === 'vcode') {
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

		//验证
		verify(val, type, method) {
				let u = navigator.userAgent;
				let isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
				let isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
				let el=document.querySelector('[name=viewport]');
				if(method="focus"){el.setAttribute('content','width=device-width,height=device-height,maximum-scale=2.0,initial-scale=1.0,user-scalable=no');
					// document.querySelector('#boxp').innerHTML='get1111==='+el.getAttribute('content');
				}else{
					el.setAttribute('content','width=device-width,height=device-height,maximum-scale=2.0,initial-scale=1.0');
					// document.querySelector('#boxp').innerHTML='222222222==='+el.getAttribute('content');
				}
			
			//10 标识过审核
			//method  作为标识有 submit 提交 enter 回车点击 focus聚焦 来判断不同情况
			let allnum = /^[0-9]*$/;
			let nums = /\d/g;
			let sign = /[~\!@#%\$\^&\*()\+\-\=\|:\;\,\_\'\"\.<>\/?*]/g;
			let en = /[a-zA-Z]/g;
			let emailReg = /^[A-Za-z0-9\u4e00-\u9fa5-_\.]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/;
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
								this.errorArr.accStatus = 10;
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
						} else if (this.now_type === 're') {
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
								case 1:
									{
										this.errorArr.pwdStatus = 5;
										return false;
										break;
									}
								case 2:
									{
										this.errorArr.pwdStatus = 6;
										if (method === 'enter') {
											this.register_submit();
										}
										return true;
										break;
									}
								case 3:
									{
										this.errorArr.pwdStatus = 7;
										if (method === 'enter') {
											this.register_submit();
										}
										return true;
										break;
									}
								default:

							}

						} else {
							this.errorArr.pwdStatus = 10;
							if (method === 'enter') {
								this.register_submit();
							}
							return true;
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
				case 'repwd':
					{
						let toVal = '';
						toVal = this.accessType === 'phone' ? this.queryRe.phone.pwd : this.queryRe.email.pwd;
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
				case 'code':
					if (val) {
						if (method === 'focus') this.codeFocus = true;
						else this.codeFocus = false;
						if (val.length < 6) {
							this.errorArr.codeStatus = 3;
							return false;
						} else {
							this.errorArr.codeStatus = 10;
							if (method === 'enter') {
								this.register_submit();
							}
							return true;
						}
					} else if (method === 'submit') {
						this.errorArr.codeStatus = 2;
						return false;
					} else if (method === 'focus') {
						this.errorArr.codeStatus = 1;
						this.codeFocus = true;
						return true;
					} else if (method === 'enter') {
						this.errorArr.codeStatus = 2;
						return false;
					} else {
						this.errorArr.codeStatus = 0;
						this.codeFocus = false;
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

							if (this.now_type === 're') {
								this.queryRe.phone.account = vals;
							}
						}
						break;
					}
				case 'pwd':
					{
						if (!val) return;
						//数字 字母 和部分字符
						let vals = val.replace(/[^\a-\z\A-\Z0-9\~\!@#%\$\^&\*()\+\-\_\"\'\=\|:\;\,\.<>\/?*]/g, '');
						/* eslint-disable no-unused-expressions */
						if (this.accessType === 'phone') {
							this.now_type === 're' ? this.queryRe.phone.pwd = vals : this.queryLo.phone.pwd;
						} else {
							this.now_type === 're' ? this.queryRe.email.pwd = vals : this.queryLo.email.pwd;
						}
						break;
					}
				case 'repwd':
					{
						let vals = val.replace(/[^\a-\z\A-\Z0-9\~\!@#%\$\^&\*()\+\-\_\"\'\=\|:\;\,\.<>\/?*]/g, '');
						this.accessType === 'phone' ? this.queryRe.phone.repwd = vals : this.queryRe.email.repwd;
						break;
					}
				case 'captcha':
					{
						//字母数字
						let vals = val.replace(/[^\a-\z\A-\Z0-9]/g, '');
						if (this.accessType === 'phone') {
							switch (this.now_type) {
								case 're':
									this.queryRe.phone.captcha = vals;
									break;
								case 'lo':
									this.queryLo.phone.captcha = vals;
									break;
								default:
							}
						} else {
							switch (this.now_type) {
								case 're':
									this.queryRe.email.captcha = vals;
									break;
								case 'lo':
									this.queryLo.email.captcha = vals;
									break;
								default:
							}
						}
						break;
					}
				case 'code':
					{
						//只让输入数字
						let vals = val.replace(/[^\d]/g, '');
						if (this.queryRe.phone.code) this.queryRe.phone.code = vals;
						else if (this.googleData.code) this.googleData.code = vals;
						break;
					}
				default:
					break;
			}
		},
		//登录注册提交
		register_submit() {
			let url = '';
			//判断协议是否被勾选
			if (this.checkeds === false) {return false;}
			//对数据进行验证
			let resultData = true;
			let data = {};
			let resultPromise = new Promise((resolve) => {
				if (this.now_type === 're') {
					url = '/ajax_user/register';
					//登錄和註冊部分
					let val = '';
					if (this.accessType === 'phone') {
						for (let i in this.queryRe.phone) {
							// this.verify(this.queryRe.phone[i], i, 'submit');
							if (this.verify(this.queryRe.phone[i], i, 'submit') === false) {
								resultData = false;
								resolve(resultData);
							}
						}
						data = this.queryRe.phone;
					} else {
						for (let i in this.queryRe.email) {
							// this.verify(this.queryRe.email[i], i, 'submit');
							if (this.verify(this.queryRe.email[i], i, 'submit') === false) {
								resultData = false;
								resolve(resultData);
							}
						}
						data = this.queryRe.email;
					}
				} else if (this.now_type === 'lo') {
					url = '/Ajax_User/login';
					if (this.accessType === 'phone') {
						//把下一步google验证码的regtype改变
						this.googleData.regtype = 'phone';
						//数据验证
						for (let i in this.queryLo.phone) {
							// this.verify(this.queryLo.phone[i], i, 'submit');
							if (this.verify(this.queryLo.phone[i], i, 'submit') === false) {
								resultData = false;
								resolve(resultData);
							}
						}
						data = this.queryLo.phone;
					} else {
						//把下一步google验证码的regtype改变
						this.googleData.regtype = 'email';
						for (let i in this.queryLo.email) {
							if (this.verify(this.queryLo.email[i], i, 'submit') === false) {
								resultData = false;
								resolve(resultData);
							}
						}
						data = this.queryLo.email;
					}
				} else if (this.now_type === 'google') {
					url = '/Ajax_User/logintwo';
					data = this.googleData;
					resultData = this.verify(this.googleData.code, 'code', 'submit');
					resolve(resultData);
				}
				resolve(resultData);
			});
			resultPromise.then((result) => {
				if (result) {
					//提交
					this.subajax(url, data);
				}
			});
		},
		subajax(url, data) {
			this.submit_now = true;
			this.disable = false;
			this.$http.post(url, data)
				.then((req) => {
					let {
						data,
						status,
						msg
					} = req;
					if (status == 1) {
						if (data === 'success') {
							this.showAlert = false;
							this.googleShow = true;
							this.now_type = 'google';
						} else {
							// 强制重置登录密码 重新登录使用
							if (url === '/Ajax_User/login') {
								sessionStorage.removeItem('rssucc');
							}
							if (this.now_type === 're') {
								this.showAlert = false;
								this.isSuccess = true;
								this.emailStatus.showActiveModal = true;
							} else {
								this.showAlert = false;
								this.isLogin = true;
								if (cookie.getItem('reurl')) {
									window.location.href = cookie.getItem('reurl');
								} else {
									window.location.href = '/';
								}
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
		jump(type) {
			switch (type) {
				case 'reg':
					this.registerAlert();
					break;
				case 'lo':
					this.loginAlert();
					break;
				case 'forget':
					this.forgetAlert();
					break;
				default:
			}
		},
		//彈窗回調
		callfn() {
			this.nowStatus = false;
		},
		//註冊成功關閉按鈕
		close_btn() {
			this.emailStatus.showActiveModal = false;
		},
		alertfn() {
			this.showAlert = false;
			if (this.time_id) {
				clearTimeout(this.time_id);
			}
		},
		getUrlQuery(query) {
			let reg = new RegExp("(^|&)" + query + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
			let r = window.location.search.substr(1).match(reg); //匹配目标参数
			if (r != null) return unescape(r[2]);
			else return null; //返回参数值
		},
		// 获取邀请人信息
		getIviInfo() {
			this.$http.get('/ajax_user/getRf')
				.then((res) => {
					if (parseInt(res.status) === 1 && res.data) {
						this.iviTis = res.data;
					}
				})
				.catch(err => {
					console.log(err);
				});
		}
	},
	watch: {
		//监听弹窗是否弹出
		//弹出式需要给body增加样式
		showAlert() {
			if (this.showAlert) {
				document.body.classList.add('no_scroll_body');
			} else {
				document.body.classList.remove('no_scroll_body');
			}
		}

	}
}).$mount('#registerDom');
export default alerts;