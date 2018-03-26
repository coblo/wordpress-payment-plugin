<?php

require 'curl-call.php';

function coblo_create_menu()
{
	add_menu_page(
		'Content Blockchain Paywall Settings',
		'CoBlo Settings',
		'administrator',
		__FILE__,
		'coblo_settings_page'
	);

	add_action('admin_init', 'register_coblo_settings');
}

add_action('admin_menu', 'coblo_create_menu');
add_action('wp_ajax_coblo_test_connection', 'test_connection');
add_action('wp_ajax_nopriv_coblo_test_connection', 'test_connection');

function test_connection()
{
	$postfields = array("jsonrpc" => "1.0", "id" => "curltest", "method" => "getinfo", "params" => []);

	$answer = curl_post('http://' . $_POST['coblo_host'] . ':' . $_POST['coblo_port'], json_encode($postfields), array(
		'username' => $_POST['coblo_user'],
		'password' => $_POST['coblo_password'],
		'timeout' => 20
	));

	die(json_encode(array('success' => !(!$answer || json_decode($answer)->error))));
}

function register_coblo_settings()
{
	//register our settings
	register_setting('coblo-settings-group', 'coblo_host');
	register_setting('coblo-settings-group', 'coblo_port');
	register_setting('coblo-settings-group', 'coblo_user');
	register_setting('coblo-settings-group', 'coblo_password');
	register_setting('coblo-settings-group', 'coblo_amount');
	register_setting('coblo-settings-group', 'coblo_paywall_color');
	register_setting('coblo-settings-group', 'coblo_paywall_header');
}

function coblo_settings_page()
{
	?>
	<script type="javascript">
		document.getElementById('coblo-test-connection')
	</script>
	<div class="wrap">
		<h1>Content Blockchain Paywall Settings</h1>

		<h2 style="margin-bottom: 0;">Connection Settings:</h2>
		<p>Connection settings for the node you want to get your payments to. Please remember to always have your node
			running.</p>

		<form method="post" action="options.php">
			<?php settings_fields('coblo-settings-group'); ?>
			<?php do_settings_sections('coblo-settings-group'); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_host">RPC Host</label></th>
					<td style="padding: 10px 10px;">
						<input type="text" id="coblo_host" name="coblo_host"
						       value="<?php echo esc_attr(get_option('coblo_host')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_port">RPC Port</label></th>
					<td style="padding: 10px 10px;">
						<input type="number" id="coblo_port" name="coblo_port"
						       value="<?php echo esc_attr(get_option('coblo_port')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_user">RPC User</label></th>
					<td style="padding: 10px 10px;">
						<input type="text" id="coblo_user" name="coblo_user"
						       value="<?php echo esc_attr(get_option('coblo_user')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_password">RPC
							Password</label></th>
					<td style="padding: 10px 10px;">
						<input type="text" id="coblo_password" name="coblo_password"
						       value="<?php echo esc_attr(get_option('coblo_password')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr>
					<th></th>
					<td>
						<button type="button" id="coblo-test-connection" class="button button-secondary"
						        style="margin-left: 185px;">Test Connection
						</button>
						<p id="coblo-connection-info" style="font-weight: 600; width: 300px; text-align: right;"></p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_amount">Amount</label>
						<p style="font-weight: 400;">Amount for one of your articles.</p></th>
					<td style="padding: 10px 10px;">
						<input type="number" id="coblo_amount" name="coblo_amount"
						       value="<?php echo esc_attr(get_option('coblo_amount')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_paywall_color">Paywall
							color</label></th>
					<td style="padding: 10px 10px;">
						<input type="text" id="coblo_paywall_color" name="coblo_paywall_color" placeholder="#FFF3B2"
						       value="<?php echo esc_attr(get_option('coblo_paywall_color')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_paywall_header">Paywall
							header</label></th>
					<td style="padding: 10px 10px;">
						<input type="text" id="coblo_paywall_header" name="coblo_paywall_header"
						       placeholder="Thank you for the interest in our article"
						       value="<?php echo esc_attr(get_option('coblo_paywall_header')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
			</table>

			<script type="text/javascript">
				var testConnectionButton = document.getElementById('coblo-test-connection');
				testConnectionButton.onclick = function () {
					var data = {
						'action': 'coblo_test_connection',
						'coblo_host': document.getElementById('coblo_host').value,
						'coblo_port': document.getElementById('coblo_port').value,
						'coblo_user': document.getElementById('coblo_user').value,
						'coblo_password': document.getElementById('coblo_password').value
					};
					var coblo_connection_info = document.getElementById('coblo-connection-info');
					coblo_connection_info.innerHTML = "Testing connection...";
					coblo_connection_info.style.color = 'black';
					jQuery.post(
						"<?php echo admin_url('admin-ajax.php') ?>",
						data,
						function (data) {
							if (data["success"])
							{
								coblo_connection_info.innerHTML = "Connection successfull!";
								coblo_connection_info.style.color = 'green';
							} else
							{
								coblo_connection_info.innerHTML = "Connection Error!";
								coblo_connection_info.style.color = 'red';
							}
						},
						'json'
					);
				};
				var paywall_color_input = document.getElementById("coblo_paywall_color");
				var paywall_header_input = document.getElementById("coblo_paywall_header");
				var coblo_amount_input = document.getElementById("coblo_amount");
				paywall_color_input.addEventListener("input", function () {
					var paywall_preview = document.getElementById("coblo-preview");
					if (paywall_color_input.value === '')
						paywall_preview.style.background = '#FFF3B2';
					else
						paywall_preview.style.background = paywall_color_input.value;
				});
				paywall_header_input.addEventListener("input", function () {
					var paywall_preview_header = document.getElementById('coblo-preview-header');
					if (paywall_header_input.value === '')
						paywall_preview_header.innerHTML = "Thank you for the interest in our article";
					else
						paywall_preview_header.innerHTML = paywall_header_input.value;
				});
				coblo_amount_input.addEventListener("input", function () {
					var paywall_preview_header = document.getElementById('coblo-preview-amount');
					paywall_preview_header.innerHTML = coblo_amount_input.value;
				});
			</script>

			<?php submit_button(); ?>

			<h3>Paywall Preview:</h3>
			<div id="coblo-preview"
			     style="background: <?php echo(esc_attr(get_option('coblo_paywall_color')) ?: '#FFF3B2'); ?>;
				     padding: 1rem; border-radius: 1rem; width: fit-content;">
				<h3 style="font-size: 22px; margin-top: 0;" id="coblo-preview-header">
					<?php echo(esc_attr(get_option('coblo_paywall_header')) ?: "Thank you for the interest in our article"); ?>
				</h3>
				<p style="font-size: 16px; margin-bottom: 0">Please
					send <span id="coblo-preview-amount"><?php echo(esc_attr(get_option('coblo_amount')) ?: "1"); ?></span> CHM to
					-address-</p>
			</div>

		</form>
	</div>
	<?php
} ?>
