$(function(){
	$('#filter_new a').click(function(){
		if ( $(this).parent().hasClass('active') )
		{
			$(this).parent().removeClass('active');
			$('.events li').show()();
		}
		else
		{
			$(this).parent().addClass('active');
			$('.events li:not(.new)').hide();
		}

		return false;
	});
});