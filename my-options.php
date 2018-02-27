<?php

function my_plugin_create_menu()
{
	add_menu_page(
		'Settings For My Plugin',
		'My Plugin Settings',
		'administrator',
		__FILE__,
		'my_plugin_settings_page'
	);

	add_action('admin_init', 'register_my_plugin_settings');
}
add_action('admin_menu', 'my_plugin_create_menu');

function register_my_plugin_settings()
{
	//register our settings
	register_setting( 'my-plugin-settings-group', 'coblo_host');
	register_setting( 'my-plugin-settings-group', 'coblo_port');
	register_setting( 'my-plugin-settings-group', 'coblo_user');
	register_setting( 'my-plugin-settings-group', 'coblo_password');
	register_setting( 'my-plugin-settings-group', 'coblo_amount');
}

function my_plugin_settings_page()
{
	?>
	<div class="wrap">
		<h1>Test Plugin</h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'my-plugin-settings-group' ); ?>
			<?php do_settings_sections( 'my-plugin-settings-group' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Host</th>
					<td><input type="text" name="coblo_host" value="<?php echo esc_attr( get_option('coblo_host') ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Port</th>
					<td><input type="text" name="coblo_port" value="<?php echo esc_attr( get_option('coblo_port') ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">User</th>
					<td><input type="text" name="coblo_user" value="<?php echo esc_attr( get_option('coblo_user') ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Password</th>
					<td><input type="text" name="coblo_password" value="<?php echo esc_attr( get_option('coblo_password') ); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Amount</th>
					<td><input type="text" name="coblo_amount" value="<?php echo esc_attr( get_option('coblo_amount') ); ?>" /></td>
				</tr>
			</table>

			<?php submit_button(); ?>

		</form>
	</div>
	<?php
} ?>
