import 'nStyle/init.scss';
import 'nStyle/theme.scss';
import 'nStyle/user/common.scss';
import 'nStyle/user_tables.scss';
import 'nStyle/user/bonusdetails/bonusdetails.scss';


import '@/tools/page/page.scss';
import pages from '@/tools/page/page';
import Vue from 'vue';
import http from 'plugins/http';
Vue.use(http);
import nav from 'nComponents/nav';
import axios from 'axios';
const candy = new Vue({
	data() {
		return {
			//tab的状态
			tab: 1,
			no_data: false,//没数据
			tabLoging: false,//logding
			//分页数据
		 	list:{},
			all: '',
			currentpage:'',
		};
	},
	mounted() {
		nav();
		this.coinClick(this.tab);
	},
	methods: {
		coinClick(nums) {
			this.tab = nums;
			this.no_data = false;
			this.getcoindata(1);
		},
		//分页数据
		getcoindata(page){
			axios.post("/ajax_user/inviteBonusDetail?origin_uid="+this.getId('id')+'&type='+this.tab+'&page='+ page)
			.then(function (response) {
				candy.list=response.data.data.list;
				candy.all=response.data.data.pages;
			}).catch(function (error) {
			　　console.log('error');
			});
		},
		getId(name) { 
			var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); 
			var r = window.location.search.substr(1).match(reg); 
			if (r != null) return unescape(r[2]); 
			return null; 
		},
	},
	components: {
	}
}).$mount('#candy');
export default candy;