import "flatpickr/dist/flatpickr.min.css";
import 'nStyle/user/common.scss';
import "styles/userCenter/coinInTable.scss";
import 'nStyle/user/recharge/recharge.scss';

import Vue from 'vue';
import axios from 'axios';

import 'lodash';
const qs = require('qs');
import http from 'plugins/http';
import nav from 'nComponents/nav';
import getLanguagePack from '@/components/tradeLanguagePack';
import Alert from 'nComponents/Alert';

Vue.use(http);
let language = getLanguagePack() || {
	COPY_NOT_SUPPORT: "您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。",
	COPY_NOT_SUPPORT_UPDATE: "您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。",
	COPY_SUCCESS: "複制成功，請通過 ctrl + v 鍵粘貼。",
	BTN_SURE: "確定",
	FORM_ERROR_ADDRESS: "請輸入有效的接受地址",
	FORM_ERROR_NUMBER: "請輸入提取數額",
	FORM_ERROR_OVER_NUMBER: "提取數額不符合要求",
	FORM_MSG_TRADE_PWD: "請輸入交易密碼",
	FORM_ERROR_TRADE_PWD: "交易密碼錯誤",
	FORM_MSG_CAPTCHA: "看不清？點擊圖片更換驗證碼",
	FORM_ERROR_CAPTCHA: "位數不正確",
	FORM_MSG_CODE: "請輸入手機驗證碼",
	FORM_ERROR_CODE: "手機驗證碼錯誤",
	REWARD_SUCCESS: "獎勵提取成功"
};
let recharge = new Vue({
	data() {
		return {
			num: '',
			payType: 'weixin',
			selectData:'all',
			tableList:{},
			page:'',
			//分页
			pageSum:20,
			pageNum:3,
			activePage:1,
			//弹框
			ifshow:false,
			headerShow:true,
			headText:'',
			bodyShow:false,
			footShow:false,
			lookData:{},
			//多次点击
			moreClick:true,
		};
	},
	created(){
		nav();
		this.getTableList('all');
	},
	methods: {
		//点击分页
		//获取table数据
		getTableList(selectData) {
			this.selectData=selectData;
			axios.post('/user/rechargeajax',
				qs.stringify({status:this.selectData})
			).then(function (res) {
				recharge.tableList = res.data.data.list;
				recharge.page = res.data.data.pageinfo;
				console.log('getTableList......',recharge.selectData);
				console.log('res==',recharge.tableList[0].status,recharge.tableList[1].status);
			}).catch(function (err) {
				console.log('err');
			});
		},
		//jsj用戶充值提交
		cnyin_up() {
			if(!this.moreClick)return;
			this.moreClick=false;

			if(this.num%100!=0||this.num==0){
				recharge.lineFun("请输入100的整数倍！");
				console.log('请输入100的整数倍！');
				return false;
			}
			axios.post('/user/rechargeup',
				qs.stringify({
					num:this.num,
					type:this.payType,
				})
			).then((res)=> {
				recharge.moreClick=true;

				recharge.getTableList('all');
				if(!res.data.status){
					recharge.lineFun(res.data.msg);
					setTimeout(()=>{recharge.ifshow=false;},2000)
				}
			}).catch(function (err) {
				recharge.moreClick=true;
				recharge.lineFun("提交失败！");
			});
		},
		//查看
		look(id){
			axios.post('/user/mysee',
				qs.stringify({id:id})
			).then(function (res) {
				console.log(res);
				recharge.ifshow=true;
				recharge.headerShow=true;
				recharge.bodyShow=true;
				recharge.footShow=true;
				recharge.headText="查看用户充值！";
				recharge.lookData=res.data.data[0];
			}).catch((err)=>{
				recharge.lineFun("查看失败！")
			});
		},
		//撤销+//已付款
		undo(id,i,url){
			if(!this.moreClick)return;
			this.moreClick=false;

			axios.post('/user/'+url,
				qs.stringify({id:id})
			).then(function (res) {
				recharge.moreClick=true;
				if(res.data.status){recharge.tableList[i].status= url=="chexiao"?3:2;}
				else{
					recharge.lineFun(res.data.msg);
					setTimeout(()=>{recharge.ifshow=false;},2000)
					return false;
				}
			}).catch((err)=>{
				recharge.moreClick=true;
				recharge.lineFun("撤销失败！");
			});
		},
		ifshowFun(){
			this.ifshow=!this.ifshow;
		},
		lineFun(headText){
			this.ifshow=!this.ifshow;
			this.headerShow=true;
			this.bodyShow=false;
			this.footShow=false;
			this.headText=headText;
		},
		//分頁
		clickPage(btn, num) {
			if (btn == "e") {
				this.activePage = this.pageSum;
			} else if (btn == "f") {
				this.activePage = 1;
			} else if (btn == "l") {
				if (this.activePage <= 1) return false;
				this.activePage--;
			} else {
				if (this.activePage >= this.pageSum) return false;
				this.activePage++;
			}
			console.log(btn, num, this.activePage);
		}
	},
	components: {
		pageinfo:pageInfo
	}
}).$mount('#recharge');
//分页组件
import pageInfo from 'nComponents/pageInfo.js';
// Vue.component=pageInfo;

// Vue.component('page-v', {
// 	name:'page-v',
// 	// camelCase in JavaScript
// 	props: ['number'],
// 	template: '<span>父组件按属性传值-子组件使用props[属性名]接受--->{{number}}</span>',
// })

export default recharge;