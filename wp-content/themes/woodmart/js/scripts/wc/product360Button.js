/* global woodmart_settings */
(function($) {
	woodmartThemeModule.product360Button = function() {
		if ('undefined' === typeof $.fn.magnificPopup) {
			return;
		}

		$('.product-360-button a').magnificPopup({
			type           : 'inline',
			mainClass      : 'mfp-fade',
			preloader      : false,
			tClose         : woodmart_settings.close,
			tLoading       : woodmart_settings.loading,
			fixedContentPos: true,
			removalDelay   : 500,
			callbacks      : {
				beforeOpen: function() {
					this.wrap.addClass('wd-popup-slide-from-left');
				},
				open      : function() {
					woodmartThemeModule.$window.trigger('resize');
				}
			}
		});
	};

	$(document).ready(function() {
		woodmartThemeModule.product360Button();
	});
})(jQuery);
