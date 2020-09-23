(function($) {
	$(document).ready(function(){
		$('.nasa-gallery-items').slick({
		  infinite: true,
		  slidesToShow: 1,
		  slidesToScroll: 1,
		  centerMode: true,
		  centerPadding: 0,
		  variableWidth: false,
		});

		$('.slick-slide img').css('margin', 'auto');
	});
}(jQuery));