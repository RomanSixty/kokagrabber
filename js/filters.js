$(function(){
	$('#filter_new').click(function(){
		if ( $(this).hasClass('active') )
		{
			$(this).removeClass('active');
			$('.events li').show()();
		}
		else
		{
			$(this).addClass('active');
			$('.events li:not(.new)').hide();
		}
	});
});