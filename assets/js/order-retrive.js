( function(jQuery) {
	"use strict";

	var interval = 10000;

	var loaded = false;

	function order__update_status() {

		if(loaded) return;

		jQuery.ajax({
			type: 'POST',
			url: frontend_ajax_object.ajaxurl,
			data : {
				action : 'order__update_status',
			},
			success: function (data) {
				if( data == 0 ) {
					loaded = true;
				}
			}
		});
		setTimeout(order__update_status, interval);
	}
	setTimeout(order__update_status, interval);

} )( jQuery );