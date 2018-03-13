"use strict";

console.log("hey");

var data = {
	'action': 'coblo_set_cookies',
	'coblo_posts_need_cookie': window.coblo_posts_need_cookie
};

jQuery.post(
	window.coblo_payment_check_url,
	data,
	function(data) {
		location.reload();
	}
);
