String.prototype.format = function(args) {
	var result = this;
	if (arguments.length > 0) {
		for ( var i = 0; i < arguments.length; i++) {
			if (arguments[i] != undefined) {
				var reg = new RegExp("({[" + i + "]})", "g");
				result = result.replace(reg, arguments[i]);
			}
		}
	}
	return result;
};

function centerModals() {
	$('.modal').each(function(i) {
		var $clone = $(this).clone().css('display', 'block').appendTo('body');
		var modalHeight = $clone.find('.modal-content').height();
		var width = $clone.find('.modal-content').width();
		var top = Math.round(($clone.height() - modalHeight) / 2);
		top = top > 0 ? top : 0;
		$clone.remove();
		$(this).find('.modal-content').css("margin-top", top);
		$(this).find('.modal-mark').css("height", modalHeight + 20).css("width", width + 20);
	});
}

$('.modal').on('show.bs.modal', centerModals);
$(window).on('resize', centerModals);

util.lrFixFooter("#allFooter");

$(function() {
	var speed = 8000;
	var count = 0;
	var newstoplist = jQuery("#newsList p");
	var sumCount = jQuery("#newsList p").length;
	function Marquee() {
		jQuery("#newsList p").hide();
		if(count>sumCount){
			count=0;
		}
		jQuery("#newsList p:eq("+count+")").fadeToggle(2000);
		++count;
	}
	Marquee();
	var MyMar = setInterval(Marquee, speed);
	newstoplist.onmouseover = function() {
		clearInterval(MyMar);
	};
	newstoplist.onmouseout = function() {
		MyMar = setInterval(Marquee, speed);
	};
});

$(function(){
	$(".leftmenu-folding").on("click",function(){
		var that=$(this);
		$("."+that.data().folding).slideToggle("fast"); 
	});
});

$(function(){
if(navigator.userAgent.toLowerCase().indexOf("chrome") != -1){
    var inputers = document.getElementsByTagName("input");  
    for(var i=0;i<inputers.length;i++){  
        if((inputers[i].type !== "submit") && (inputers[i].type !== "password")){  
            inputers[i].disabled= true;  
        }  
    }  
    setTimeout(function(){  
        for(var i=0;i<inputers.length;i++){  
            if(inputers[i].type !== "submit"){  
                inputers[i].disabled= false;  
            }  
        }  
    },100);
}
});

/** *******QQ登录****************************** */	
function openqq(url){
	if(url==null||url==""){
		url=window.location.href;
	}
	window.open('/qqLogin?url='+url,'new','height='+550+',,innerHeight='+550+',width='+800+',innerWidth='+800+',top='+200+',left='+200+',toolbar=no,menubar=no,scrollbars=auto,resizeable=no,location=no,status=no');
}

function showTips(text){
	util.showerrortips("", text, {
		okbtn : function() {
			$('#alertTips').modal('hide');
		}
	});
}


function toUtf8(str) {
	var out, i, len, c;
	out = "";
	len = str.length;
	for(i = 0; i < len; i++) {
		c = str.charCodeAt(i);
		if ((c >= 0x0001) && (c <= 0x007F)) {
			out += str.charAt(i);
		} else if (c > 0x07FF) {
			out += String.fromCharCode(0xE0 | ((c >> 12) & 0x0F));
			out += String.fromCharCode(0x80 | ((c >>  6) & 0x3F));
			out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));
		} else {
			out += String.fromCharCode(0xC0 | ((c >>  6) & 0x1F));
			out += String.fromCharCode(0x80 | ((c >>  0) & 0x3F));
		}
	}
	return out;
}