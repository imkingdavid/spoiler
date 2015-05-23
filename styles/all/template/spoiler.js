(function($) {  // Avoid conflicts with other libraries
	'use strict';
	$(function() {
		$('div.spoiler_title input[type=button]').on('click', function (e) {
			e.preventDefault();
			var spoiler_content_div = $(this).parent('div.spoiler_title').siblings('div.spoiler_content');
			if (spoiler_content_div.css('display') == 'none') {
				if ($(this).attr('value') == $('.spoiler_show').text()) {
					$(this).attr('value', $('.spoiler_hide').text());
				}
				spoiler_content_div.css('display', 'block');
			} else {
				if ($(this).attr('value') == $('.spoiler_hide').text()) {
					$(this).attr('value', $('.spoiler_show').text());
				}
				spoiler_content_div.css('display', 'none')
			}
		});
	});
})(jQuery); // Avoid conflicts with other libraries
