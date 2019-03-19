define(function (){
	var dis = 0;
	var autoSroll = function(name,fangfa,speed){
		var name = setInterval(fangfa,speed);
	}

	var carouselSroll = function(){
		/*var length = $('.jinDanUl li').length;
		var str = '';
		for(let i = 0; i < 3 ; i++){
			str += $('.jinDanUl').children().eq(i).prop("outerHTML");
			$('.jinDanUl').children().eq(i).remove();		
		}
		$('.jinDanUl').append(str);
		$('.jinDanUl').animate({'right':'100%'});
*/
	}


	var autoWinsList = function(){
		dis++;
		$('.all_List_Div').scrollTop(dis);
		if ($('.all_List_Div').scrollTop()>=$('.ul_List1').height()) {
			dis = 0;
			$('.all_List_Div').scrollTop(dis);
		}
	}


	var carouselAutoPlay= function(){
		var width = $('.list_EggsDiv').width();
		$(".list_EggsDiv ul").eq(0).animate({left:'-'+width},1200,function(){
			$(this).css({
						left: width,
						zIndex: 0
					}).parent().appendTo($('.list_EggsDiv'));
		}).parent().next().find('ul').animate({left:0},1200);
	}
	

	return{
		autoSroll:autoSroll,  //自动滚动
		carouselSroll:carouselSroll,  //滚动效果
		autoWinsList:autoWinsList,  //中奖用户名单
		carouselAutoPlay:carouselAutoPlay  //金蛋幻灯片滚动效果
	}
});