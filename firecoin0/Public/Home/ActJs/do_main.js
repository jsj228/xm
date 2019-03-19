require.config({
　　　　baseUrl: "js",
　　　　paths: {
			"jquery": "jquery-2.1.0.min",
　　　　　　"require": "require",
			"resize":"resize",
			"carousel":"jinDanCarousel",
			"alert":'alert',
　　　　}
　　});

requirejs(['jquery','require','carousel','alert'],
	function($,require,carousel,alert){
	$(document).ready(function(){
		//加载完成之后关闭loading
		$('.loadingDiv').hide();

		var t = setInterval(function() {
				carousel.carouselAutoPlay()
			},5000);

		//点击抽奖
		$('body').on('click','.list_EggsDiv ul li',function(){
			alert.clickDan();   //砸蛋
		});

		//关闭抽奖结果
		$('body').on('click','.colseJiangPin',function(){
			$('.bg-mask').remove();
			$('.jiangPinResult').remove();
			$('body').css({"overflow":"visible"});
		});

		//登录
		$('body').on('click','.tishiChouJiang .loginSpan',function(){
			alert.alertLogin();
		});

		//退出登录
		$('body').on('click','.tishiChouJiang .loginOutSpan',function(){
			$('.tishiChouJiang').html('您好,请<span class="loginSpan">登录</span>');
		});

		//关闭登录窗口
		$('body').on('click','.colseLogin',function(){
			$('.bg-mask').remove();
			$('.inputInfo').remove();
			$('body').css({"overflow":"visible"});
		});

		//关闭默认的事件动作
		$('body').on('touchmove','.bg-mask',function(event) { event.preventDefault(); }, false);
		$('body').on('touchmove','.loadingDiv',function(event) { event.preventDefault(); }, false);
	});
});

requirejs(['jquery','resize'],
	function($,resize){
		resize.resizeCarousel();
});

// requirejs(['jquery','require','carousel'],
// 	function($,require,carousel){
// 		carousel.autoSroll(carousel.carouselSroll(),2000);
// 		carousel.autoSroll(carousel.autoWinsList(),50);
// });
