import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import 'nStyle/user/common.scss';
import 'nStyle/user_tables.scss';
import 'nStyle/user/bonusinvite/bonusinvite.scss';


import '@/tools/page/page.scss';
import pages from '@/tools/page/page';

import Vue from 'vue';
import http from 'plugins/http';
Vue.use(http);
import nav from 'nComponents/nav';
import axios from 'axios';
const qs = require('qs'); 
const candy = new Vue({
	data() {
		return {
			//tab的状态
			tab: 1,
			tabChnage: true,
			//展示用数据
			dataList: [],
			//没数据
			no_data: false,
			//logding
			tabLoging: false,
	
			nowStatus: false,
			errMesAlert: '',
			//弹窗类型
			alertype: '',
			//币种详情数据
			coinList: {},
			moverClick: false,

            list:{},
			//分页数据
			currentpage:'',
			all: '',
		};
	},
	mounted() {
		nav();
		this.getcoindata(1);
	},
	methods: {
		//分页数据
		getcoindata(num){
			axios.post("/ajax_user/inviteBonus",qs.stringify({page:num}))
			.then(function (response) {
			candy.list=response.data.data.list;
			candy.all=response.data.data.pages;
			}).catch(function (error) {
			　　console.log('error');
			});
		}
	},
	components: {
	}
}).$mount('#candy');
export default candy;