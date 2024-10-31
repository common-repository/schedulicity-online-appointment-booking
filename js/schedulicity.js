jQuery( document ).ready(function( $ ) {
	$("iframe[src*='?bookingSource=widget']").parent().css({"width":"auto","max-width":"621px"});
	/* temp fix for display issue. Remove with next release */
	if($('.schedulicity-embed').parent().width() < 800){
		$('.schedulicity-embed').css('max-width','660px');
	}
});