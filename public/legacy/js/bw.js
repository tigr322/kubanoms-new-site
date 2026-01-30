$(function() {
	setSetting();
});

 

function setSetting(){
	
	var strClassFontSize = '';
	var strClassFontFamily = '';
	var strClassColor = '';
    var strClassInterval = '';
	
	$('.font-size a').each(function(){
		strClassFontSize += $(this).data("font-size")+" ";
	});
	$('.font-family a').each(function(){
		strClassFontFamily += $(this).data("font-family")+" ";
	});
	$('.color a').each(function(){
		strClassColor += $(this).data("color")+" ";
	});
    $('.char-interval a').each(function(){
		strClassInterval += $(this).data("interval")+" ";
	});

	
	/* font size */
	
	$('.font-size a').on("click", function(){
		$('.font-size a').removeClass("active");
		var attr = $(this).addClass('active').data("font-size");
		$('body').removeClass(strClassFontSize).addClass(attr);
		$.cookie('special_fz', attr, { path: '/'});
	});
	
	/* font family */
	
	$('.font-family a').click(function(){
		$('.font-family a').removeClass("active");
		var attr = $(this).addClass('active').data("font-family");
        $('a[data-font-family="'+attr+'"]').addClass('active');
		$('body').removeClass(strClassFontFamily).addClass(attr);
		$.cookie('special_ff', attr, { path: '/'});
	});
	

	/* modify color */
	
	$('.color a').click(function(){
		$('.color a').removeClass("active");
		var attr = $(this).addClass('active').data("color");
        $('a[data-color="'+attr+'"]').addClass('active');
		$('body').removeClass(strClassColor).addClass(attr);
		$.cookie('special_color', attr, { path: '/'});
	});
	
	/* modify img-show */
	
	$('.img-show a').click(function(){
		if($(this).hasClass('active')){	
			$(this).removeClass("active");
			$.cookie('special_hi', 'off', { path: '/'});
		} else{
		        $(this).addClass("active");
			$.cookie('special_hi', 'on', { path: '/'});
		}
		$('body').toggleClass("hide-img");
	});
    
    $('#link_settings').click(function(){
        $('.settings-panel').slideToggle("slow");
    });
    
    /* modify char-interval */
	
	$('.char-interval a').click(function(){
		$('.char-interval a').removeClass("active");
		var attr = $(this).addClass('active').data("interval");
		$('body').removeClass(strClassInterval).addClass(attr);
		$.cookie('special_char_interval', attr, { path: '/'});
	});

	setHistorySitting();
}


function setHistorySitting(){

	var cookie = $.cookie('special_color');
	if(cookie !== null) {
		$('a[data-color='+cookie+']').trigger("click");
	}
	
 	cookie = $.cookie('special_fz');
	if(cookie !== null) {
		$('a[data-font-size='+cookie+']').trigger("click");
	}
	
	cookie = $.cookie('special_ff');
	if(cookie !== null) {
		$('a[data-font-family='+cookie+']').trigger("click");
	} 
	
	cookie = $.cookie('special_hi');
	if(cookie !== null && cookie == "off") {
		$('.img-show a').trigger("click");
	} 
    
    cookie = $.cookie('special_char_interval');
	if(cookie !== null) {
		$('a[data-interval='+cookie+']').trigger("click");
	} 

}