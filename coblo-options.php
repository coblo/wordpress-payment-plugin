<?php

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
						<input type="text" id="coblo_host" name="coblo_host" value="<?php echo esc_attr(get_option('coblo_host')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_port">RPC Port</label></th>
					<td style="padding: 10px 10px;">
						<input type="number" id="coblo_port" name="coblo_port" value="<?php echo esc_attr(get_option('coblo_port')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_user">RPC User</label></th>
					<td style="padding: 10px 10px;">
						<input type="text" id="coblo_user" name="coblo_user" value="<?php echo esc_attr(get_option('coblo_user')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row" style="padding: 15px 10px 15px 0; width: 150px;"><label for="coblo_password">RPC Password</label></th>
					<td style="padding: 10px 10px;">
						<input type="text" id="coblo_password" name="coblo_password" value="<?php echo esc_attr(get_option('coblo_password')); ?>"
						       style="width: 300px;"/>
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
						<input type="number" id="coblo_amount" name="coblo_amount" value="<?php echo esc_attr(get_option('coblo_amount')); ?>"
						       style="width: 300px;"/>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>

		</form>
	</div>
	<?php
} ?>
