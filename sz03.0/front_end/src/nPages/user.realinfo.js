import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import "nStyle/user/common.scss";
import 'nStyle/user/realinfo/user_realinfo.scss';

import Vue from 'vue';
import http from 'plugins/http';
Vue.use(http);
import nav from 'nComponents/nav';
import getLanguagePack from '@/components/tradeLanguagePack';
import Alert from 'nComponents/Alert';
import lrz from "lrz";
import qs from 'qs';

const language = getLanguagePack() || {
	BTN_SURE: "確定",
	SUBMIT_SUCC: "實名認證資料提交成功，審核時間為1~3個工作日。",
	PIC_FRONT: "正面照片未選擇",
	PIC_BACK: "背面照片未選擇",
	PIC_HAND: "手持照片未選擇",
	FILE_READER_NO: "抱歉，你的浏覽器不支持 FileReader,請使用谷歌(chrome)或火狐(firefox)浏覽器操作！",
	PIC_SIZE_MAX: "照片大小不能超過2M，請重新上傳！",
	PIC_FORMAT_ERROR: "證件圖片支持jpg/jpeg/png格式,暫時還不支持其他格式。",
	PICK_PIC: "選擇圖片",
	PIC_FULL: "證件照片需要上傳完整"
};
const realinfo = new Vue({
	data() {
		return {
			//空 待审核 1审核中 2已认证 3审核失败
			status: null,
			//弹窗
			nowStatus: false,
			errMesAlert: '',
			imgs: {
				baseyi: '/imgs/cert_front.png',
				baseer: '/imgs/cert_back.png',
				basesan: '/imgs/cert_handkeey1.png'
			},
			postData: {
				// 姓名
				name: "",
				//证件类型 1:身份证 2 护照
				cardtype: "1",
				//证件号
				idcard: "",
				//照片正面
				baseyi: "",
				//照片背面
				baseer: "",
				//手持证件照
				basesan: ""
			},
			//错误提示
			postDataErr: {
				//0不显示 1 普通情况 2空值 & 格式错误 4后台提示错误
				name: 0,
				cardtype: 0,
				idcard: 0
			},
			//错误msg
			postDataMsg: {
				name: '',
				idcard: '',
				imgs: ''
			},
			//是否可以提交
			submitNow: true,
			//弹窗回调
			alertype: '',
			//正面图片是否上传 1 未上传 2已上传
			frontFaceStatus: 1,
			backFaceStatus: 1,
			handkeepStatus: 1,
			//返回的原始数据存放
			oldData: {},
			//失败原因
			content: '',
			//创建时间
			createTime: '',
			//失败后重新审核
			failAgain: false
			// lengths: 18
		};
	},
	created() {
		this.getInfo();
	},
	mounted() {
		nav();
		this.creatTimes();
	},
	methods: {
		//获取目前状态
		getInfo() {
			console.log('getInfo','获取开始的状态');
			this.$http.post('/Ajax_User/userstatus', {}).then(({
					data,
					status,
					msg
				}) => {
					console.log('getInfo',data,status,msg)
					if (status == 1 && data == '') {
						this.status = '';
					} else if (status == 1 && data != '') {
						//解构赋值
						let {status} = data;
						this.status = status;
						if (status == 1 || status == 2 || status == 3) {
							//解构赋值
							let {
								name,
								idcard,
								cardtype,
								frontFace,
								backFace,
								handkeep,
								idcardyi
							} = data;
							//数据填充
							this.postData.name = name;
							this.postData.cardtype = cardtype;
							this.postData.idcard = idcard;

							if (status == 2) {
								this.postData.idcard = idcardyi;
								this.createTime = data.created;
							}
							//去掉输出图片前面的 .
							this.imgs.baseyi = frontFace.replace('./', '/');
							this.imgs.baseer = backFace.replace('./', '/');
							this.imgs.basesan = handkeep.replace('./', '/');
							this.frontFaceStatus = 2;
							this.backFaceStatus = 2;
							this.handkeepStatus = 2;
							//将原始数据储存在this.oldData
							Object.assign(this.oldData, data);
							if (status == 3) {
								let {content} = data;
								this.content = content;
							}
						}

					} else {
						this.nowStatus = true;
						this.errMesAlert = msg;
					}
				});
		},
		//图片压缩
		lrzImg(file) {
			console.log('lrzImg压缩 图片',file);
			return new Promise((resolve) => {
				// 压缩 图片
				lrz(file, {
						width: 800,
						quality: 0.6
					})
					.then(function (rst) {
						resolve(rst);
					})
					.catch(function (err) {
						console.log(err);
					});
			});
		},
		//图片本地上传
		uploadFile(e) {
			console.log('uploadFile图片本地上传',e)
			const imgMaxSize = 2097152;
			let trageFile = e.target.files[0]; //图片
			let name = e.target.name; //图片名
			let size = trageFile.size;//图片大小
			this.lrzImg(trageFile) //压缩图片
				.then((result) => {
					if (result.base64Len > imgMaxSize) {
						this.nowStatus = true;
						this.errMesAlert = language['PIC_SIZE_MAX'];
						return false;
					} else {
						let type = result.file.type;
						//图片上传格式
						if (type) {
							switch (type) {
								case 'image/jpg':
									break;
								case 'image/jpeg':
									break;
								case 'image/png':
									break;
								default:
									this.nowStatus = true;
									this.errMesAlert = language['PIC_FORMAT_ERROR'];
									return false;
							}
						} else {
							this.nowStatus = true;
							this.errMesAlert = language['PIC_FORMAT_ERROR'];
							return false;
						}
						//转换为base64 进行输出
						if (name === 'frontFace') {
							this.imgs.baseyi = result.base64;
							this.frontFaceStatus = 2;
						} else if (name === 'backFace') {
							this.imgs.baseer = result.base64;
							this.backFaceStatus = 2;
						} else {
							this.imgs.basesan = result.base64;
							this.handkeepStatus = 2;
						}
					}
				});
		},
		//输入框聚焦
		focusInput(type) {
			console.log('focusInput输入框聚焦',type)
			switch (type) {
				case 'name':
					this.postDataErr.name = 1;
					break;
				case 'idcard':
					this.postDataErr.idcard = 1;
					break;
				default:
			}
		},
		//数据校验//不同状态的时候 通过 now传 在不同情况下进行判断
		
		verify(type, now) {
			console.log('verify数据校验',type);
			let regName = /(^[\u4e00-\u9fa5]{1}[\u4e00-\u9fa5·]{0,98}[\u4e00-\u9fa5]{1}$)|(^[a-zA-Z]{1}[a-zA-Z\s]{0,98}[a-zA-Z]{1}$)/;
			let idcard1 = /^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/;
			let idcard2 = /^[a-zA-Z\d\-\.]{1,20}$/;
			let idcard3 = /^[a-zA-Z\d]{1,30}$/;
			switch (type) {
				case 'name':
					if (this.postData.name && regName.test(this.postData.name)) {
						this.postDataErr.name = 0;
						return true;
					} else if (this.postData.name && !regName.test(this.postData.name)) {
						this.postDataErr.name = 2;
						return false;
					} else if (!this.postData.name && now === 'blur') {
						this.postDataErr.name = 0;
						return false;
					} else {
						this.postDataErr.name = 5;
						return false;
					}
					break;
				case 'idcard':
					// if (this.cardtype == 1) {
					//   this.lengths = 18;
					// } else {
					//   this.lengths = 20;
					// }
					if (this.postData.idcard) {
						let types = this.postData.cardtype;
						if (types == 1 && idcard1.test(this.postData.idcard)) {
							this.postDataErr.idcard = 0;
							return true;
						} else if (types == 2 && idcard2.test(this.postData.idcard)) {
							this.postDataErr.idcard = 0;
							return true;
						} else if (types == 3 && idcard3.test(this.postData.idcard)) {
							this.postDataErr.idcard = 0;
							return true;
						} else {
							this.postDataErr.idcard = 2;
							return false;
						}
					} else if (!this.postData.idcard && (now === 'blur' || now === 'select')) {
						this.postDataErr.idcard = 0;
					} else {
						this.postDataErr.idcard = 5;
					}
					break;
				case 'baseyi':
					//判断是否图片是否有上传
					if (this.frontFaceStatus == 1 && !this.nowStatus) {
						this.nowStatus = true;
						this.errMesAlert = language['PIC_FULL'];
						return false;
					} else {
						return true;
					}
					break;
				case 'baseer':
					if (this.backFaceStatus == 1 && !this.nowStatus) {
						this.nowStatus = true;
						this.errMesAlert = language['PIC_FULL'];
						return false;
					} else {
						return true;
					}
					break;
				case 'basesan':
					if (this.handkeepStatus == 1 && !this.nowStatus) {
						this.nowStatus = true;
						this.errMesAlert = language['PIC_FULL'];
						return false;
					} else {
						return true;
					}
					break;
				default:
			}
		},
		//判断数据是否发生改变
		//输入框部分
		changesInput(type) {
			console.log('changesInput输入框部分--判断数据',type);
			switch (type) {
				case 'name':
					if (this.postData.name == this.oldData.name) {
						return false;
					} else {
						return true;
					}
					break;
				case 'cardtype':
					if (this.postData.cardtype == this.oldData.cardtype) {
						return false;
					} else {
						return true;
					}
					break;
				case 'idcard':
					if (this.postData.idcard == this.oldData.idcard) {
						return false;
					} else {
						return true;
					}
					break;
				default:
			}
		},
		//图片部分
		changesImg(type) {
			console.log('changesImg 图片部分--判断数据',type)
			let yi = this.oldData.frontFace.replace('./', '/');
			let er = this.oldData.backFace.replace('./', '/');
			let san = this.oldData.handkeep.replace('./', '/');
			switch (type) {
				case 'baseyi':
					if (this.imgs.baseyi == yi) {
						return false;
					} else {
						return true;
					}
					break;
				case 'baseer':
					if (this.imgs.baseer == er) {
						return false;
					} else {
						return true;
					}
					break;
				case 'basesan':
					if (this.imgs.basesan == san) {
						return false;
					} else {
						return true;
					}
					break;
				default:
					break;
			}
		},
		//清楚上传的图片
		picClose(type) {
			console.log('picClose清楚上传的图片',type);
			switch (type) {
				case 'frontFace':
					this.imgs.baseyi = '/imgs/cert_front.png';
					this.frontFaceStatus = 1;
					break;
				case 'backFace':
					this.imgs.baseer = '/imgs/cert_back.png';
					this.backFaceStatus = 1;
					break;
				case 'handkeep':
					this.imgs.basesan = '/imgs/cert_handkeey1.png';
					this.handkeepStatus = 1;
					break;
				default:
					break;
			}
			document.getElementsByName(`${type}`)[0].value = '';
		},
		//验证提交的信息
		submitVerify(type) {
			console.log('submitVerify',type)
			return new Promise((resolve) => {
				Object.assign(this.postData, this.imgs);
				// 重新提交跳转 避免进行数据验证
				if (!type && this.submitNow) {
					//避免再次提交后 为false
					// this.submitNow = true;
					for (let i in this.postData) {
						if (this.verify(i) === false) {
							this.submitNow = false;
						}
					}
				} else {
					this.submitNow = false;
				}
				resolve();
			});
		},
		//提交选择
		submitChoose(url) {
			console.log('submitChoose提交选择',url);
			return new Promise((resolve) => {
					if (this.submitNow) {
						this.submitNow = false;
						if (this.status == '' && !this.failAgain) {
							url = '/Ajax_Auth/phone';
							this.ajaxPost(url);
						} else if (this.status == 1) {
							url = '/Ajax_Auth/mobilephone';
							let result = false;
							let str = '';
							for (let i in this.postData) {
								if (this.changesImg(i) === true || this.changesInput(i) === true) {
									result = true;
								} else if (this.changesImg(i) === false) {
									//没改变的图片传空值
									this.postData[`${i}`] = '';
								}
								str += i + ':' + this.postData[i] + ',';
							}
							resolve({
								result: result,
								url: url,
								str: str
							});
						} else if (this.status == '' && this.failAgain) {
							url = '/Ajax_Auth/mobilephone';
							this.ajaxPost(url);
						}
					}
				})
				.then(({
					result,
					url,
					str
				}) => {
					if (result) {
						this.ajaxPost(url);
					} else {
						this.submitNow = true;
					}
				});
		},
		async submit(type) {
			console.log('async submit',type);
			if (type) {
				this.status = 1;
				this.failAgain = true;
				return false;
			}
			let url = '';
			//数据验证
			await this.submitVerify(type);
			//判断提交的数据是否相同，不相同就提交
			await this.submitChoose(url);
		},
		//使用ajaxPost方法提交
		ajaxPost(url) {
			console.log('ajaxPost',url)
			this.$http({
					method: 'POST',
					url,
					data: this.postData,
					rsa: false
				})
				.then(({
					data,
					status,
					msg
				}) => {
					this.submitNow = true;
					if (status == 1) {
						this.alertype = 'realinfoSucc';
					}
					this.nowStatus = true;
					this.errMesAlert = msg;
				})
				.catch((error) => {
					this.submitNow = true;
					//记录点击
					let data = "";
					if (this.postData) {
						data = JSON.stringify(this.postData);
					}
					let resultErr = {};
					resultErr.reqUrl = url;
					resultErr.response = error;
					resultErr.param = data;
					this.writeDown(resultErr);
				});
		},
		//log
		writeDown(errResult) {
			console.log('writeDown',errResult);
			
			let errEnc = errResult ? this.$jsencrypt(errResult) : '';
			let headers = {};
			headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
			this.$http.post('/ajax_common/reqFailedLog', qs.stringify(errEnc, {
				arrayFormat: 'brackets'
			}), {
				headers
			});
		},
		//时间戳
		creatTimes(value) {
			let date = new Date(parseInt(value) * 1000);
			let Y = date.getFullYear() + '-';
			let M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
			let D = date.getDate() + ' ';
			let h = date.getHours() + ':';
			let m = date.getMinutes() < 10 ? '0' + date.getMinutes() + ':' : date.getMinutes() + ':';
			let s = date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds();
			return (Y + M + D + h + m + s);
		},
		//弹窗关闭后刷新
		alertFn() {
			if (this.alertype === 'realinfoSucc') {
				// window.location.reload();
			}
		}
	}

}).$mount('#realinfo');

export default realinfo;