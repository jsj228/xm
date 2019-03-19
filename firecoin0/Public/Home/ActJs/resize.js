define(function (){
	var resizeCarousel = function(){
		$(window).resize(function(){
			var width = $('.list_EggsDiv').width();
			$('.list_EggsDiv div .jinDanUl2').css({'left':width});
		});
	}

	return{
		resizeCarousel:resizeCarousel,
	}
});