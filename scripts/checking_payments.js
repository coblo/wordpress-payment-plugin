"use strict";

function check_paid() {
	var data = {
		'action': 'check_paid',
		'coblo_post_ids': window.coblo_post_ids
	};

	jQuery.post(
		window.coblo_payment_check_url,
		data,
		function(data) {
			if ((data["has_error"] !== window.coblo_node_has_error) || data["is_paid"])
				location.reload();
			else
				setTimeout(check_paid, 1000);
		},
		'json'
	);
}

check_paid();
