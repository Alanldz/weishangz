
$(document).ready(function(){global_obj.page_init()});

var global_obj={
	page_init:function(){
		//document.addEventListener('WeixinJSBridgeReady', function onBridgeReady(){
			//WeixinJSBridge.call('hideToolbar');
		//}, false);
		
		$('a').each(function(){
			var url=$(this).attr('href');
			var rel = $(this).attr('rel');
			var urlSearch = window.location.search;
			var aid = "0";
			if (urlSearch.indexOf("?") != -1) {
			    var str = urlSearch.substr(1)
			    strs = str.split("&");
			    for (i = 0; i < strs.length; i++) {
			        var searchName = strs[i].split("=")[0];
			        if (searchName == "AID" || searchName == "aid") {
			            if (aid == "0") {
			                aid = strs[i].split("=")[1];
			            }
			        }
			    }
			}
			//if (urlSearch.indexOf("&") != -1) {
			//    var str = urlSearch.substr(1);
			//    strs = str.split("&");
			//    aid = strs[1].split("=")[1];
			//}
			if(url && url.indexOf('tel:')==-1 && url.indexOf('javascript:')==-1 && url.indexOf('wxref=mp.weixin.qq.com')==-1 && url.indexOf('#')==-1 && !rel){
				if(url.charAt(url.length-1)=='/'){
				    $(this).attr('href', url + '?wxref=mp.weixin.qq.com' );
				}else if(url.indexOf('?')==-1){
				    $(this).attr('href', url + '?wxref=mp.weixin.qq.com' );
				}else{
				    $(this).attr('href', url + '&wxref=mp.weixin.qq.com' );
				}
			}
		});
	},
	
	win_alert:function(tips, handle){
		$('body').prepend('<div id="global_win_alert"><div>'+tips+'</div><h1>好</h1></div>');
		$('#global_win_alert').css({
			position:'fixed',
			left:$(window).width()/2-125,
			top:'30%',
			background:'#fff',
			border:'1px solid #ccc',
			opacity:0.95,
			width:250,
			'z-index':10000,
			'border-radius':'8px'
		}).children('div').css({
			'text-align':'center',
			padding:'30px 10px',
			'font-size':16
		}).siblings('h1').css({
			height:40,
			'line-height':'40px',
			'text-align':'center',
			'border-top':'1px solid #ddd',
			'font-weight':'bold',
			'font-size':20
		});
		$('#global_win_alert h1').click(function(){
			$('#global_win_alert').remove();
		});
		if($.isFunction(handle)){
			$('#global_win_alert h1').click(handle);
		}
	},
	
	check_form:function(obj){
		var flag=false;
		obj.each(function () {
		 
		    if ($(this).val() == '') {
				$(this).css('border', '1px solid red');
				flag==false && ($(this).focus());
				flag=true;
			}else{
				$(this).removeAttr('style');
			}
		});
		return flag;
	},
	check_formByNum: function (obj) {
	    var flag = false;
	    obj.each(function () {
	        var thisVal = $(this).val();
	        
	        if (thisVal == '' || thisVal==0) {
	            $(this).css('border', '1px solid red');
	            flag == false && ($(this).focus());
	            flag = true;
	        } else {
	            $(this).removeAttr('style');
	        }
	    });
	    return flag;
	}


}