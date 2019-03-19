import 'nStyle/init.scss';
// import 'nStyle/theme.scss';
// import "nStyle/user/common.scss";
// import "nStyle/user_tables.scss";
import 'nStyle/user/bank/bank.scss';

import Vue from 'vue';
import axios from 'axios';
import nav from 'nComponents/nav';

//table
// 图片压缩
import lrz from "lrz";
const qs = require('qs');

// import getLanguagePack from '@/components/tradeLanguagePack';
import getLanguagePack from '@/components/tradeLanguagePack';
let language = getLanguagePack();
console.dir('language',language);


const bank = new Vue({
	data(){
		return {
			realName:'',//真實姓名
			reset:'',//重置二維碼
			postData: { //wz提交数据
				index: 1,
				moblePhone:'', 
				id:'',//支付宝或微信二维码的id
				//支付宝或微信号
				bankcard:'',
				img: "/imgs/zw.png",
				status:true,//wz开关
			},
			errStatus:{ 
				imgErr:'√',
				name:'',
				username:'',
				bankcard:'',
				moblePhone:'',
			},
			// 倒計時
			numT:60,
			timeErr:"",
			//添加銀行卡
			bankShow:false,
			alertMsg:'',
			BankData:{
				index:1,
				name:'',	//備註名稱
				// bankProv:'-1',	//開戶省
				// bankCity:'',	//開戶市
				username:'',	//開戶支行
				bankcard:'',	//銀行卡號
				moblePhone:'',	//短信驗證
				id:''		//id
			},
			bankList:[],
		};
	},
	created() {
		this.tabChange(1)
	},
	mounted() {
		nav();
	},
	methods: {
		//tab 切换
		tabChange(index) {
			this.numT=-1;
			this.alertMsg=this.errStatus.moblePhone=this.BankData.moblePhone=this.postData.moblePhone="";
			//初始化目前状态
			axios.post('/user/bindbank',
				qs.stringify({
					type:index,
				})
			).then((res)=>{
				this.realName=res.data.data.user.name;
				if(index==1){//銀行卡部分    
					this.bankList=res.data.data.bank;
				}else{
					// 微信 + 支付寶
					if(res.data.status){
						this.postData=res.data.data.bank[0];
					}else{
						this.postData.bankcard='';
						this.postData.id='';
						this.postData.status=0;
						this.postData.img='/imgs/zw.png';
					}
					this.imgErr='√';
					this.reset='';
				}
				this.postData.index = index;
			}).catch((err)=>{
				this.alertMsg="請求超時!"
				setTimeout(()=>{this.alertMsg="";},2000);
			});
			
		},
		//数据校验//不同状态的时候 通过 now传 在不同情况下进行判断
		verify(reg,pData,itime,errMsg,) {
			let regs={
				regStr: /(^[\u4e00-\u9fa5]{2,}$)|(^[a-zA-Z]{3,}$)/,
				wx:/(^[1-9]{1}[0-9]{6,10}$)|(^[a-zA-Z0-9]{1,}[\-||\!||\@||\?||\_||\#||\$||\%||a-zA-Z0-9]*$)/,
				zfb:/(^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$)|(^[1-9]{1}[0-9]{6,10}$)/,
				bank: /^[0-9]{16,19}$/,
				moble:/^[0-9]{6}/,
			}
			if (regs[reg].test(this[pData][itime])) {
				this.errStatus[itime]="";
				return true;
			}else{
				this.errStatus[itime]=errMsg;
				setTimeout(()=>{this.errStatus[itime]="";},2000);
				return false;
			}
		},
		//数据校验//不同状态的时候 通过 now传 在不同情况下进行判断
		//图片压缩
		lrzImg(file) {
			return new Promise((resolve) => {
				// 压缩 图片
				lrz(file, {width: 800,quality: 0.6}
					).then(function (rst) {
						resolve(rst);
					})
					.catch(function (err){
						this.errStatus.imgErr ='上传超時!';
					});
			});
		},
		//图片本地上传
		uploadFile(e) {
			const imgMaxSize = 2097152;
			let trageFile = e.target.files[0];//文件
			let size = trageFile.size;//文件大小
			this.lrzImg(trageFile).then(
				(result) => {
					if (result.base64Len > imgMaxSize) {
						this.nowStatus = true;
						this.errStatus.imgErr ='文件过大!';
						return false;
					}else {
						this.errStatus.imgErr ='√';
						//转换为base64 进行输出
						this.postData.img=result.base64;
						this.reset=true;
					}
				}
			);
		},
		// 点击提交
		async submit(){
			//提交前进行短信的验证
			if(this.postData.index==1
				&&!this.verify('regStr','BankData','username','請輸入正確的開戶支行')
				&&!this.verify('regStr','BankData','name','請輸入正確的備註名稱')
				&&!this.verify('bank','BankData','bankcard','請輸入正確的銀行卡號')
				&&!this.verify('moble','BankData','moblePhone','請輸入正確的短信驗證碼!')
			){return false;}
			else if(this.postData.index==2){
				this.verify('moble','postData','moblePhone','請輸入正確的短信驗證碼!');
				this.verify('wx','postData','bankcard','請輸入正確的支付寶賬號！');
			}else if(this.postData.index==3){
				this.verify('moble','postData','moblePhone','請輸入正確的短信驗證碼!');
				this.verify('zfb','postData','bankcard','請輸入正確的支付寶賬號！');
			}
			//使用ajax Post方法提交的數據
			let data =this.postData.index!=1?this.postData:this.BankData;
			// console.log(data);
			axios.post("/user/bankadd",
				qs.stringify({data:data}),
					{rsa: false}
				).then((res) => {
					if(res.data.status){
						//銀行卡只有添加走这里
						if(this.postData.index==1){
							// this.BankData.id=res.data.status;
							this.BankData.status=1;
							this.bankList.push(this.BankData);
							this.bankShow=false;
						}else{
							this.postData.moblePhone='';
							this.reset='';
							this.postData.status=1;
						}
					}
					this.alertMsg=res.data.msg;
					setTimeout(()=>{this.alertMsg="";},2000)
				}).catch((err) => {
					this.alertMsg="請求超時！";
					setTimeout(()=>{this.alertMsg="";},2000)
				});
		},
		//获取短信
		getmoble() {
			//進行手機號和 圖形驗證碼的驗證
			axios.post('/user/sendmoble').then(
				(res)=>{
					if(res.data.status){
						this.numT=60;
						this.time60();
					}else{
						bank.errStatus.moblePhone="发送驗證碼失败！";
						setTimeout(()=>{bank.errStatus.moblePhone=""},2000);
					}
				}).catch((err)=>{

					bank.errStatus.moblePhone="发送驗證碼失败！";
					setTimeout(()=>{bank.errStatus.moblePhone=""},2000);

					bank.errStatus.moblePhone="失败！";
					setTimeout(()=>{bank.errStatus.moblePhone=""},3000);

			});
		},
		//60s倒計時
		time60() {
			let interval = setInterval(function () {
				if (bank.numT < 0) {
					clearInterval(interval);
					return false;
				};
				bank.numT--;
			}, 1000);
		},
		//銀行卡的
		alertFun(close){
			close=="close"?this.alertMsg="":this.bankShow=!this.bankShow;
		},
		//銀行卡解綁
		openBind(id,i,url){
			var status='';
			if(this.postData.index==1 && url=='mysave'){
				status=parseInt(this.bankList[i].status)?0:1;
			}else if(url=='delbank'){
				status='';
			}else{
				status=parseInt(this.postData.status)?0:1;
			}
			axios.post('/user/'+url,
				qs.stringify({id,status})
			).then(res=>{
				if(res.data.status){
					if(this.postData.index==1&&url=='mysave'){
						this.bankList[i].status=parseInt(this.bankList[i].status)?0:1;
					}else if(url=='delbank'){
						this.bankList.splice(i,1);
					}else{
						this.postData.status=parseInt(this.postData.status)?0:1;
					}
				}else{
					this.alertMsg="修改失敗！";
					setTimeout(()=>{this.alertMsg="";},2000)
				}
				this.alertMsg=res.data.msg;
			}).catch(err=>{
				setTimeout(()=>{this.alertMsg="";},2000)
			})
		}
	},
	components: {
	}
}).$mount('#bank');
export default bank;