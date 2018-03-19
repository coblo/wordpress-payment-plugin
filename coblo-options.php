<?php

require 'curl-call.php';

function coblo_create_menu()
{
	add_menu_page(
		'Content Blockchain Paywall Settings',
		'Content Blockchain Paywall Settings',
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
	register_setting('my-plugin-settings-group', 'coblo_host');
	register_setting('my-plugin-settings-group', 'coblo_port');
	register_setting('my-plugin-settings-group', 'coblo_user');
	register_setting('my-plugin-settings-group', 'coblo_password');
	register_setting('my-plugin-settings-group', 'coblo_amount');
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
			<?php settings_fields('my-plugin-settings-group'); ?>
			<?php do_settings_sections('my-plugin-settings-group'); ?>
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
				<tr>
					<th style="width: 100px; padding: 0; white-space: nowrap;">
						<h2 style="margin-bottom: 0;">Amount:</h2>
						<p style="font-weight: 400;">Amount for one of your articles.</p>
					</th>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_amount">Amount</label></th>
					<td style="padding: 10px 10px;">
						<input type="number" id="coblo_amount" name="coblo_amount"
						       value="<?php echo esc_attr(get_option('coblo_amount')); ?>"
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
			</script>

			<?php submit_button(); ?>

		</form>
	</div>
	<?php
} ?>
