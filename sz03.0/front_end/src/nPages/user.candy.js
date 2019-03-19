import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import 'nStyle/user/common.scss';
import 'nStyle/user_tables.scss';
import 'nStyle/user/candy/user_candy.scss';

import Vue from 'vue';
import http from 'plugins/http';
Vue.use(http);
import nav from 'nComponents/nav';
import Alert from 'nComponents/Alert';
import axios from 'axios';

//table
// import scrollTable from 'nComponents/scrollTable';
import getQuery from 'tools/getQuery.js';


const candy = new Vue({
	data() {
		return {
			//tab的状态
			// btc分叉 1 eth分叉 2 活动赠送币 3
			tab: 1,
			//btc分叉数据
			btcList: [],
			//eth分叉数据
			ethList: [],
			//展示用数据
			dataList: [],
			//logding
			tabLoging: false,
			//没数据
			no_data: false,
			nowStatus: false,
			errMesAlert: '',
			//弹窗类型
			alertype: '',
			//币种详情
			coinDetail: false,
			//币种详情数据
			coinList: {},
			moverClick: false,
			tabChnage: true,
			//多次点击
			moverClick:true,
		};
	},
	mounted() {
		nav();
		// jsj  look 初始化頁面
		if (location.href.indexOf('?tab') > -1) {	this.coinClick(parseInt(getQuery('tab')));}
		else {this.getCoinList();}
	},
	methods: {
		coinClick(nums) {
			this.tab = nums;
			this.no_data = false;
			this.getCoinList();
		},
		//獲取也main數據
		getCoinList() {
			// console.log(this.tab,'this.tab ')
			this.tabLoging = true;
			let url= '';
			if (this.tab ===1) { //持幣分紅
				url = '/ajax_user/bonus';
			}else if(this.tab === 2){
				// url = '/ajax_user/branchlist';
			}else {   // 活动赠送币
				// url = '/ajax_user/giftList';
			}
			axios.post(url).then(
			(res) => {
				let {data,status}=res.data;
				if(status == 1 && this.tab == 1){
					this.dataList =data
				}
				// console.log(candy.dataList);
			}).then(()=>{
				this.tabLoging = false;
				if (this.dataList.length > 0) {
					this.no_data = false;
				} else {
					this.no_data = true;
				}
			});
		},
		gifts(coin,key) {
			if(!this.moverClick)return;
			this.moverClick=false;

			let url='';
			if (this.tab ===1) { //持幣分紅
				url = '/ajax_user/getBonus';
			}else if(this.tab === 2){
				// url = '/ajax_user/branchlist';
			}else {   // 活动赠送币
				// url = '/ajax_user/giftList';
			}
			this.$http.post(url,{coin})
			.then((res) => {
				this.moverClick=true;
				this.nowStatus = true;
				this.errMesAlert =res.msg;
				this.alertype = 'gift';
				if(res.status&&res.data){
					this.dataList[key].number=res.data;
				}
			}).catch((err)=>{
				this.moverClick=true;
			});
			
		},
		//关闭币种详情
		// close() {
		// 	this.getCoinList();
		// 	this.coinDetail = false;
		// },
		callfn() {
			if (this.alertype === 'gifr') {
				this.getCoinList();
			}
		},
		// 币种详情
		// coin_detail(type, data) {
		// 	//移动端
		// 	if (type === 'start') {
		// 		this.moverClick = true;
		// 		this.coinList = data;
		// 	} else if (type === 'over') {
		// 		this.moverClick = false;
		// 	} else if (type === 'end' && this.moverClick === true) {
		// 		this.coinDetail = true;
		// 	}
		// },
	},
	components: {
		// scrollTable
	}
}).$mount('#candy');
export default candy;
