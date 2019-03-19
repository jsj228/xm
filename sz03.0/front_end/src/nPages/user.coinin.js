import "flatpickr/dist/flatpickr.min.css";
import 'nStyle/user/common.scss';
import "styles/userCenter/coinInTable.scss";
import 'nStyle/user/coinin/coinin.scss';

import Flatpickr from "flatpickr";
import Zh from "flatpickr/dist/l10n/zh.js";
import Vue from 'vue';
import navVue from 'nComponents/nav';
import activeNav from '@/components/userNav.js';
import http from '@/tools/http';
import Alert from '@/tools/alert/alert';
import is from '@/tools/is';
import setTableStyle from '@/components/makeTableHead';
import getLanguagePack from '@/components/tradeLanguagePack';
import chkhttpLang from '@/tools/chgHpLg';
import '@/tools/page/page.scss';
import pages from '@/tools/page/page';
import Promise from 'promise';
import talking from '@/tools/talking/talking';
import cookie from '@/tools/cookie';
import HoverWin from 'components/hoverWin';


// process.env.NODE_ENVs
// 我的賬戶 轉幣 提幣 委托 成交 頁面js
$(document).ready(function () {
	const navNew = navVue();
	//打開聊天室接受數據功能
	talking();
	// 切換 請求處理 語言包
	chkhttpLang("#baseLang", http);
	const lang = getLanguagePack()|| {
		tablePlatformIn: "平台內",
		tablePlatformOut: "平台外",
		COPY_NOT_SUPPORT: "您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。",
		COPY_NOT_SUPPORT_UPDATE: "您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。",
		COPY_SUCCESS: "複制成功，請通過 ctrl + v 鍵粘貼。",
		GET_DATA_FAIL: "獲取數據失敗",
		START_TIME: "請選擇開始時間",
		END_TIME: "請選擇結束時間",
		START_GT_END: "開始時間不能大於等於結束時間",
		LOADING_RMB: "加载中..."
	};


	//人民币悬浮框
	const myHover = new HoverWin($('[data-msg]'), 'msg', 'top', 'string');
	// myHover.loadingTips = lang.LOADING_RMB;
	//问号部分弹窗
	let canHoverRMB = true;
	//调用悬浮框
	// function addHoverWin($elem, attr, direction) {
	//   $($elem).off('mouseenter');
	//   $($elem).mouseenter(function(e) {
	//     if (!canHoverRMB) return;
	//     if (e.stopPropagation) e.stopPropagation();
	//     myHover.setHover(this, attr, direction, 'string');
	//   });
	//   $($elem).mouseleave(function() {
	//     if (!canHoverRMB) return;
	//     myHover.hide();
	//   });
	// }

	// 彈框
	const myAlert = new Alert("");
	activeNav();
	// cookie 切换语言
	let nowLang = document.getElementById('LANG').innerHTML;
	if (nowLang === 'cn') {
		Flatpickr.localize(Zh.zh);
	}

	/* eslint-disable */
	// 日曆
	const startTimtOptions = {
		enableTime: true,
		dateFormat: "Y-m-d H:i:S"
	};
	const startTime = new Flatpickr("#startTime", startTimtOptions);
	const endTimtOptions = {
		enableTime: true,
		dateFormat: "Y-m-d H:i:S"
	};
	const endTime = new Flatpickr("#endTime", endTimtOptions);
	/* eslint-enable */
	// 初始化
	setTableStyle();
	//
	window.onresize = function () {
		setTableStyle();
	};
	// 防止币列表 多次点击
	let isUpdate = false;
	// 複制粘貼
	$('[data-copy="ele"]').click(function (e) {
		if (!document.execCommand) {
			alert(lang['COPY_NOT_SUPPORT']); //您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。
		}
		let text = '';
		if ($(this).attr('data-type') === 'addr') {
			text = $("#coinAddr").html();
		} else {
			text = $("#eos_labels").html();
		}
		let transfer = document.getElementById('J_CopyTransfer');
		if (!transfer) {
			transfer = document.createElement('textarea');
			transfer.id = 'J_CopyTransfer';
			transfer.style.position = 'absolute';
			transfer.style.opacity = '0';
			transfer.style.width = '0';
			transfer.style.left = '0px';
			transfer.style.bottom = '0px';
			document.body.appendChild(transfer);
		}
		transfer.value = text || '';
		transfer.focus();
		transfer.select();
		try {
			let succ = document.execCommand('Copy', false, null);
			if (succ) {
				myAlert.show(lang['COPY_SUCCESS']); //複制成功，請通過 ctrl + v 鍵粘貼。
			} else {
				myAlert.show(lang['COPY_NOT_SUPPORT']); //您的浏覽器不支持快速複制功能，請手動選擇需要複制的內容按下 ctrl + c 鍵複制。
			}
		} catch (e) {
			if (e) {
				myAlert.show(lang['COPY_NOT_SUPPORT_UPDATE']); //您的浏覽器不支持複制功能呢，請安裝最新版本浏覽器後再試試。
			}
		}
	});

	// 幣篩選條件
	function showSelCoinName($this) {
		if (isUpdate) return;
		$("#allCoinList .sel_coin").removeClass("sel_coin");
		const coinCode = $(this).data('coincode');
		$(this).addClass("sel_coin");
		$("#coinName").html($($this).html());

	}
	// 初始化
	$("#coinName").html($("#allCoinList .sel_coin").html());
	$('#allCoinList span[data-coincode]').click(function () {
		showSelCoinName(this);
	});


	// 一句話
	function getcoinmessage(coin) {
		http({
			url: '/ajax_user/coinRecordMessage?coin=' + coin,
			method: 'GET',
			dataType: 'json',
			success({data})
			 {
				//
				if (is(data, "Object")) {
					if (data.message) {
						$('#userTips').show().html(data.message);
						// no-tips-cond-time
						$('#userTips').siblings(".cond-time").removeClass("no-tips-cond-time");
					} else {
						$('#userTips').hide();
						$('#userTips').siblings(".cond-time").addClass("no-tips-cond-time");
					}
				}
			},
			error(err) {
				if (err) console.log(err);
			}
		});
	}


	// 分頁 vue object
	let pageVue = new Vue({
		data: {
			all: '',
			currentpage: '',
			paswadd: ''

		},
		mounted() {

		},
		methods: {
			getTabs(num) {
				
				let coinsData = {};
				Object.assign(coinsData, JSON.parse(sessionStorage.oldData));
				Object.assign(coinsData, {
					page: num
				});
				/* eslint-disable no-use-before-define */
				getcoindata(coinsData);
				/* eslint-enable */
			},
		}
	}).$mount('#tabsPages');
	//
	var listData = [];
	function coinShow(coin) {
		if (coin === 'wcg' || coin === 'WCG' || coin === 'mtr'
		 || coin === 'MTR' || coin === 'drt' || coin === 'DRT' 
		 || coin === 'mat' || coin === 'MAT' || coin === 'eqt' 
		 || coin === 'EQT' || coin === 'unih' || coin === 'UNIH' 
		 || coin === 'wos' || coin === 'WOS' || coin === 'ctm' 
		 || coin === 'CTM' || coin === 'gvm' || coin === 'GVM') {
			$('.displayhiding').show();
			$('.frequency').hide();
		} else {
			$('.frequency').show();
			$('.displayhiding').hide();
		}
	}
	function getcoindata({
		coin,
		coinType,
		type,
		startTime,
		endTime,
		page,
		in_type,
		callback
	}, determineData) {
		const obj = {
			coin,
			coinType,
			type,
			startTime,
			endTime,
			in_type,
			page,
			callback
		};
		sessionStorage.oldData = JSON.stringify(obj);
		if (page == undefined) {
			page = 1;
		}
		http({
			url: '/ajax_user/coinRecord',
			method: 'POST',
			dataType: 'json',
			data: {
				coin,
				coinType,
				type,
				startTime,
				endTime,
				page,
				in_type
			},
			success(req){
				if (req.status && parseInt(req.status) === 1){
					let {
						data
					} = req;
					let html = '';
					if (is(data.list, 'Array')) {
						// 有數據 <td>${coin.bid}</td>
						if (data.list.length >= 0) {
							$('#tableNoData').hide();
							data.list.forEach((coin) => {
								html += `<tr>
									<td>${coin.id}</td>
									<td>${coin.type}</td>
									<td>${coin.time}</td>
									<td class="frequency">${coin.txid}</td>
									<td class="displayhiding" >${coin.wallet}</td>
									<td>${coin.number}</td>
									<td class="frequency">${coin.confirm}</td>
									<td class="displayhiding" style="color:red">${coin.bak}</td>
									<td class="${coin.colour == 1? 'green-font': coin.colour == 3 ? 'red560_f': 'orange-font'}" 
										 ${coin.colour == 3 ? ('data-msg=" ' + coin.thaw_time) + '"': ''} 
										 flex="cross:center">${coin.status} ${coin.colour == 3 ?'<i class="forzen_question"></i>': ''
										}
									</td>
									<td><button coinid=${coin.id} class="minBtn 
										 ${coin.status=="待審批"||coin.status=="Pending audit"?'activeBg' :'bg999'}
										 ${coin.status=="待審批"||coin.status=="Pending audit"?'rgbCancel' :''}"
										 >${coin.status=='待審批'? '撤销':coin.status=="Pending audit"?'cancel':coin.status}</button></td>
								</tr>`;
							});
							$('#dataBody').html(html);
							// 重置表頭
							setTableStyle();
							coinShow(coin);
						} else {
							$('#tableNoData').show();
						}
					} else {
						$('#tableNoData').show();
					}
					setTableStyle();
					// addHoverWin($('#dataBody [data-msg]'), 'msg', 'top');
					pageVue.all = data.pagetotal;
					pageVue.currentpage = data.currentpage;
					
				}
                $('.rgbCancel').on('click',cancelFun);
			},
			error(err) {
				if (err) myAlert.show(lang['GET_DATA_FAIL']);
			}
		});
	}
	// rgb系列的撤单
	function cancelFun(){
		let el =$(this);
		let id=$(this).attr('coinid');
		let coin= $('#allCoinList').find('.sel_coin').attr('data-coincode');
		el.attr('disabled',true);
		el.css('cursor','wait');

		$.ajax({
			url: '/ajax_user/rgbCancel',
			method: 'POST',
			dataType: 'json',
			data: {
				coin,id
			},
			success(res){
				el.attr('disabled',false);
				el.css('cursor','pointer');
				if(res.status){
                    el.removeClass('rgbCancel activeBg').addClass('bg999').html('已撤销');
					el.parent('td').siblings('td.orange-font').html('已撤销');
                }else{
                    el.attr("data-err","撤销失败!");
                    setTimeout(function(){el.attr("data-err","");},2000);
                }
			},error(err) {
				el.attr('disabled',false);
				el.css('cursor','pointer');
				console.log(err);
			}
		});
	}
	// 生産二維碼
	function makeCodeImg(addrText) {
		let img = document.createElement('img');
		img.src = `/Ajax_user/qrimages?text=${addrText}`;
		img.className = 'succ-img';
		$("#addrShow").hide();
		$("#addrLoading").show();
		img.onload = function () {
			$('#qrcodeImg').html('').append(img);
			$("#addrShow").show();
			$("#addrLoading").hide();
			$('#coinAddr').html(addrText);
			img.onload = '';
			isUpdate = false;
		};
	}
	//eos标签二维码
	function makeCodeImgEos(addrText) {
		let img = document.createElement('img');
		img.src = `/Ajax_user/qrimages?text=${addrText}`;
		img.className = 'succ-img';
		$("#addrShowEos").hide();
		$('#addrLoadingEos').show();
		img.onload = function () {
			$('#addrLoadingEos').hide();
			$("#addrShowEos").show();
			$('#img_eos').html('').append(img);
			$('#eos_labels').html(addrText);
			img.onload = '';
			isUpdate = false;
		};
	}
	// 獲取錢包地址
	function getCoinAddress(coin) {
		/* eslint-disable */
		// return;
		if (isUpdate) return;
		isUpdate = true;
		$('#qrcodeImg img').attr('src', '/imgs/creating_qrCode.png').removeClass('succ-img');
		$('#coinAddr').html("");
		// 生成中顯示
		// 不能充幣顯示
		$("#canNotIn").hide();
		// return;
		http({
			url: '/ajax_user/getCoinAddress?coin=' + coin,
			method: 'GET',
			success(req) {
				if ('[name=rgbname]') {
					if (req.data.wallet.address && req.data.wallet.label) {
						$('.es_show').show();
					} else {
						$('.es_show').hide();
					}
				}

				let currCoin = $("#allCoinList").find(".sel_coin");
				let tagNow = '';
				if (currCoin.length > 0) {
					tagNow = currCoin.html().trim().toUpperCase();
				}
				// 获取数据成功
				if (parseInt(req.status) === 1) {
					const {
						data
					} = req;
					if (!data['wallet']) {
						// $("#qrcodeImg").html("");
						$('#coinAddr').html("");
						$("#linkToOut").hide();
						isUpdate = false;
					} else {
						//钱包地址
						if (data['wallet']['address']) {
							makeCodeImg(data['wallet']['address']);
						} else {
							makeCodeImg(data['wallet']);
						}
						//eos标签地址
						if (data['wallet']['label']) {
							makeCodeImgEos(data['wallet']['label']);
						}
						// 显示跳转BTC场外交易
						if (tagNow == "BTC") {
							$("#linkToOut").show();
						} else {
							$("#linkToOut").hide();
						}
					}
				} else {
					$("#addrShow").hide();
					$("#addrLoading").show();
					isUpdate = false;
				}
				// 防止重复地址
			},
			error(err) {
				if (err) {
					// console.log(err);
					// alert('獲取錢包數據失敗');
				}
				isUpdate = false;
				// 防止重复地址
			}
		});
	}



	function selClick(num) {
		
		// 地址 加载中
		$("#addrLoading").show();
		$("#addrShow").hide();
		if (isUpdate) return;
		//
		let parent = $(this).parent('div');
		let coin = "";
		let canCoinIn = "";
		let canCoinType = '';
	
		if (parent.attr('id') == 'allCoinList') {
			coin = $(this).attr('data-coincode');
			canCoinIn = $(this).data('instatus');
		} else {
			coin = $('#allCoinList>span.sel_coin').attr('data-coincode');
			canCoinIn = $('#allCoinList>span.sel_coin').data('instatus');
			canCoinType = 1;
		}
		$('#dataBody').html('');
		// 是否可以充值
		if (canCoinIn === 0 && canCoinType != 1) {
			// 獲取錢包地址
			getCoinAddress(coin);
			getcoinmessage(coin);
		} else if (canCoinIn === 1) {
			// 不能充值
			$("#addrLoading").hide();
			$("#addrShow").hide();

			//eos标签
			$("#addrLoadingEos").hide();
			//
			$("#canNotIn").show();
			$("#canNotInEos").show();
			$("#img_eos img").removeClass('succ-img').attr("src", '/imgs/can_not_in.png');
			$("#qrcodeImg img").removeClass('succ-img').attr("src", '/imgs/can_not_in.png');
			getcoinmessage(coin);
		} else if (canCoinType === 1 && canCoinIn != 1) {
			$("#addrLoading").hide();
			$("#addrShow").show();
		}
		//标签地址是否显示
		if (coin === 'eos' || coin === 'wcg' || coin === 'xrp' ) {
			$('.es_show').show();
		} else {
			$('.es_show').hide();

		}

		if (coin === 'eos' || coin === 'xrp' || coin === 'oioc' || coin === 'cple' ) {
			$('.Label').show();
			$('.addres').hide();
		} else {
			$('.Label').hide();
			$('.addres').show();
		}


		let reg = /^[WCG]/,
			name = $(this).html().trim();
		if (reg.test(name)) {
			$('.toggleEl').show();
			if(coin === 'cple'){
                $('.toggleEl').hide();
			}
		}else {
			$('.toggleEl').hide();
		}
		if (num == 2) $('span[data-ntcoin]').html($(this).html().trim());
		$('[name=coin]').val(coin);
		$(this).addClass("sel_coin");
		$("#qkBtn .act_btn").removeClass('act_btn');
		$('#allCoin').addClass('act_btn');
		const in_type = $('#changeType>span.sel_coin').attr('in-type');

		let coinsData = {
			coin: coin,
			coinType: 'in',
			type: 'all',
			in_type: in_type
		};
		getcoindata(coinsData);

	}
	//點擊轉入類型
	$('#changeType>span').click(function (e) {

		if (e.stopPropagation) e.stopPropagation();
		if (e.preventDefault) e.preventDefault();
		$("#changeType .sel_coin").removeClass("sel_coin");
		let num = 1;
		selClick.call(this, num);
	});
	// 點擊幣種
	$('#allCoinList>span').click(function (e) {
		$(this).attr('name') ? $('.rgbClass').show(100) : $('.rgbClass').hide(100);
		if (e.stopPropagation) e.stopPropagation();
		if (e.preventDefault) e.preventDefault();
		let num = 2;
		selClick.call(this, num);
	});
	//华克金单独处理有弹框
	$('#allCoinList>span.wcgClass').click(function () {
		$(this).addClass("sel_coin");
	});
	// 點擊全部
	$('#allCoin').click(function () {
		$("#qkBtn .act_btn").removeClass('act_btn');
		$(this).addClass("act_btn");
		let coin = $('.sel_coin').attr('data-coincode');
		$('#dataBody').html('');
		$('[name=type]').val('all');
		const in_type = $('#changeType>span.sel_coin').attr('in-type');
		let coinsData = {
			coin: coin,
			coinType: 'in',
			type: 'all',
			in_type: in_type
		};
		console.log('0000', in_type);
		getcoindata(coinsData);
	});
	// 點擊今天
	$('#todayCoin').click(function () {
		$("#qkBtn .act_btn").removeClass('act_btn');
		$(this).addClass("act_btn");
		let coin = $('.sel_coin').attr('data-coincode');
		$('#dataBody').html('');
		$('[name=type]').val(1);
		const in_type = $('#changeType>span.sel_coin').attr('in-type');
		let coinsData = {
			coin: coin,
			coinType: 'in',
			type: 1,
			in_type: in_type
		};
		getcoindata(coinsData);
	});
	// 點擊30天
	$('#monthCoin').click(function () {
		$("#qkBtn .act_btn").removeClass('act_btn');
		$(this).addClass("act_btn");
		let coin = $('.sel_coin').attr('data-coincode');
		$('#dataBody').html('');
		$('[name=type]').val(2);
		const in_type = $('#changeType>span.sel_coin').attr('in-type');
		let coinsData = {
			coin: coin,
			coinType: 'in',
			type: 2,
			in_type: in_type
		};
		getcoindata(coinsData);
	});
	// 點擊篩選
	$('#timeselect').click(function () {
		let startTime = $('#startTime').val();
		let endTime = $('#endTime').val();
		$('[name=type]').val(3);
		$('[name=startTime]').val(startTime);
		$('[name=endTime]').val(endTime);
		if (startTime == '') {
			myAlert.show(lang["START_TIME"]); //請選擇開始時間
			return false;
		}
		if (endTime == '') {
			myAlert.show(lang["END_TIME"]); //請選擇結束時間
			return false;
		}
		if (startTime && endTime) {
			if (startTime >= endTime) {
				myAlert.show(lang['START_GT_END']); //開始時間不能大于等于結束時間
				return false;
			}
		}
		$('#dataBody').html('');
		let coin = $('.sel_coin').attr('data-coincode');
		const in_type = $('#changeType>span.sel_coin').attr('in-type');

		let coinsData = {
			coin: coin,
			coinType: 'in',
			type: 3,
			startTime: startTime,
			endTime: endTime,
			in_type: in_type
		};
		getcoindata(coinsData);
	});
	// 點擊重置
	$('#reset').click(function () {
		let coin = $('.sel_coin').attr('data-coincode');
		$('#dataBody').html('');
		$("#startTime").val('');
		$("#endTime").val('');
		$('[name=type]').val('all');
		$("#qkBtn .act_btn").removeClass('act_btn');
		$('#allCoin').addClass('act_btn');
		const in_type = $('#changeType>span.sel_coin').attr('in-type');
		let coinsData = {
			coin: coin,
			coinType: 'in',
			type: 'all',
			in_type: in_type
		};
		getcoindata(coinsData);
	});
	// 點擊導出excel
	$('#excel').click(function () {
		let coin = $("#allCoinList .sel_coin").html().trim().toLowerCase();
		let coinType = $('[name=coinType]').val();
		let type = $('[name=type]').val();
		let startTime = $('[name=startTimeex]').val();
		let endTime = $('[name=endTimeex]').val();
		location.href = '/user/coinRecordCsvOut?coin=' + coin + '&coinType=' + coinType + '&type=' + type + '&startTime=' + startTime + '&endTime=' + endTime;
	});
	//初始化
	function inisetdata() {
		let coin = $('.sel_coin').attr('data-coincode');
		let canCoinIn = $('#allCoinList').find('.sel_coin').attr('data-instatus');
		const in_type = $('#changeType>span.sel_coin').attr('in-type');
		let coinsData = {
			coin: coin,
			coinType: 'in',
			type: 'all',
			in_type: in_type
		};
		if (canCoinIn == 0) {
			// 獲取錢包地址
			getCoinAddress(coin);
		} else {
			// 不能充值
			$("#addrLoading").hide();
			$("#addrShow").hide();
			$("#canNotIn").show();
			$("#qrcodeImg img").removeClass('succ-img').attr("src", '/imgs/can_not_in.png');
		}
		getcoindata(coinsData);
		getcoinmessage(coin);

		$('span[data-ntcoin]').html($('.sel_coin').html().trim());
		// 獲取錢包地址
	}
	// 獲取路由參數
	function GetQueryString(name) {
		const reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
		const r = window.location.search.substr(1).match(reg);
		if (r != null) return unescape(r[2]);
		return null;
	}

	

	// 默認選擇全部篩選
	$('#allCoin').addClass("act_btn");
	// 是否通過賬戶中心點擊跳轉過來
	const coin = GetQueryString('coin');
	// console.log(coin);
	if (coin) {
		$('#dataBody').html('');
		$(`#allCoinList .sel_coin`).removeClass("sel_coin");
		$(`#allCoinList span[data-coincode="${coin}"]`).addClass("sel_coin");
		// 模拟点击选择的币 点击事件
		$('#allCoinList span.sel_coin').click();
	} else {
		inisetdata();
	}

	//WCG交易密码弹窗
	// $(".coinin_up").click(function(){
	//   $(".set_trade_pwd").show();
	// })

	$(".close_icon").click(function () {
		$(".set_trade_pwd").hide();
	})

	var cnresu = false;
	$(function () {

		var spanInnerText, oneText, twoText;

		$('#allCoinList span').click(function () {
			spanInnerText = $(this).attr('data-coincode');
			coinShow(spanInnerText);
		})
		$('.coinin_up').on('click', function () {
			oneText = $(".addressa").val();
			twoText = $(".numbers").val();
			if (!oneText == "" && !twoText == "") {
				$(".set_trade_pwd").show();
			}
		});


		$(".addressa").blur(function () { //blur从链接上移开焦点(鼠标离开框时)
			var addressa = $(this).val();
			var regstr = /(^[0-9a-zA-Z]+[\-]*[0-9a-zA-Z]$)||(^[0-9]$)||(^[a-zA-Z]$)/;

			if (!regstr.test(addressa)) {
				$(".adress").text("请输入钱包地址");
				cnresu = false;
				return;
			}
			cnresu = true;
		});
		$(".addressa").focus(function () { //focus给予链接焦点(鼠标点中框时)
			$(".adress").text(" ");
		});
		$(".numbers").blur(function () { //blur从链接上移开焦点(鼠标离开框时)
			var numbers = $(this).val();
			var regstr = /^\d+(?=\.{0,1}\d+$|$)/;
			if (!regstr.test(numbers)) {
				$(".nmbers").text("请输入转入数量");
				cnresu = false;
				return;
			}
			cnresu = true;
		});
		$(".numbers").focus(function () { //focus给予链接焦点(鼠标点中框时)
			$(".nmbers").text(" ");
		});
		$(".paswadd").focus(function () { //focus给予链接焦点(鼠标点中框时)
			$(".tips").text("");
		});
		
		$('#Determine').on('click', function (e) {
			$(this).attr('disabled',true);
			$(this).css({'background':'#ccc','cursor':'wait'});
			e.preventDefault();
			var threeText = $(".paswadd").val();
			http({
				url: '/ajax_user/rgbCoinIn',
				method: 'POST',
				dataType: 'json',
				data: {
					"tradePwd": threeText,
					"number": twoText,
					"wallet": oneText,
					"coin": spanInnerText
				},
				success(data){
					$('#Determine').css({'background':'#ffbd09','cursor':'pointer'});
					$('#Determine').removeAttr('disabled');

					if (data.status == 1) {
						$(".tips").text(data.msg);
						$(".set_trade_pwd").hide();
						$(".addressa").val('');
						$(".numbers").val('');
						$(".paswadd").val('');
						$(".tips").text("");
						var type = '普通轉入';
						// if (parseInt(data.data.type) == 1) {
						// 	type = '普通轉入';
						// } else if (parseInt(data.data.type) == 2) {
						// 	type = '交易挖矿';
						// } else {
						// 	type = '持续分红';
						// }
						var str = ` <tr >
							<td>` + data.data.id + `</td>
							<td>` + type + `</td>
							<td>` + data.data.time + `</td>
							<td class="frequency">` + data.data.txid + `</td>
							<td class="displayhiding" >` + data.data.wallet + `</td>
							<td>` + parseFloat(data.data.number) + `</td>
							<td class="frequency">` + data.data.confirm + `</td>
							<td class="displayhiding" style="color:red">` + data.data.bak + `</td>
							<td class="orange-font">待审批</td>
							<td><button  coinid=`+ data.data.id +` class="rgbCancel activeBg minBtn">撤销</button></td>
						</tr>`;
						var dataBody = $('#dataBody');
						var boxList = $('#dataBody>tr');
						if (boxList.length >= 7) {
							$('#dataBody>tr:last-child').remove();
						}
						dataBody.prepend(str);
						coinShow(spanInnerText);
						$('.rgbCancel').on('click',cancelFun);
					} else {
						$(".tips").text(data.msg);
						$(".set_trade_pwd").show();
						$(".forgetPwd").show();
					}
				},
				error(err) {
					console.log('请求失败！');
					$('#Determine').css({'background':'#ffbd09','cursor':'pointer'});
					$('#Determine').removeAttr('disabled');
				}
			});
		});
	
	})

});




export default {};