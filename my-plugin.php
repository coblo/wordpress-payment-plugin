<?php
/*
Plugin Name: Test Plugin
Plugin URI: content-blockchain.com
Description: First test plugin
Version: 1.0
Author: Patricia Schinke
Author URI: content-blockchain.com
License: GPLv2
*/

require 'my-options.php';

global $jal_db_version;
$jal_db_version = '1.0';

add_action('wp_ajax_check_paid', 'check_paid');
add_filter('the_content', 'replace_content');
add_action('init', 'set_cookie');
register_activation_hook(__FILE__, 'jal_install');

function check_paid()
{
	global $wpdb;
	$tablename = $wpdb->prefix . 'articlepayments';

	$cookie = $_COOKIE['coblo-id'];
	$postid = url_to_postid(wp_get_referer());

	$addresses = $wpdb->get_col('
		SELECT address
		FROM ' . esc_sql($tablename) . '
		WHERE cookie = "' . esc_sql($cookie) . '"
		  AND postid = ' . esc_sql($postid)
	);

	if (!$addresses)
		die(json_encode(array("is_paid" => false, "has_error" => true)));
	foreach ($addresses as $address)
	{
		$article_paid = article_is_paid($address);
		if ($article_paid["has_error"])
		{
			die(json_encode(array("is_paid" => false, "has_error" => true)));
		} else if ($article_paid["is_paid"])
		{
			die(json_encode(array("is_paid" => true, "has_error" => false)));
		}
	}
	die(json_encode(array("is_paid" => false, "has_error" => false)));
}

function replace_content($content_obj)
{
	global $wpdb;
	$tablename = $wpdb->prefix . 'articlepayments';

	if (!array_key_exists('coblo-id', $_COOKIE))
	{
		$text = '<h2>You should not delete your id Cookie</h2>';
		return $text;
	}
	$cookie = $_COOKIE['coblo-id'];
	$postid = get_the_ID();

	$addresses = $wpdb->get_col('
		SELECT address
		FROM ' . esc_sql($tablename) . '
		WHERE cookie = "' . esc_sql($cookie) . '"
		  AND postid = ' . esc_sql($postid)
	);

	if ($addresses)
	{
		foreach ($addresses as $address)
		{
			$article_paid = article_is_paid($address);
			if ($article_paid["has_error"])
			{
				$text = '
					<h2>Technical Problems</h2>
					<p>There was an error! Please try again in some minutes.</p>
					<script>window.coblo_payment_check_url=' . json_encode(admin_url('admin-ajax.php')) . ';</script>
					<script>window.coblo_node_has_error=true;</script>
				';
				wp_enqueue_script("checking_payments", plugins_url('/scripts/checking_payments.js', __FILE__), array('jquery'));
				return $text;
			} else if ($article_paid["is_paid"])
			{
				$cookie_link = plugins_url('/set-cookie.php?cookie=' . $cookie, __FILE__);
				$cookie_text = '<p>Save this link:<br><a href="' . $cookie_link . '">' . $cookie_link . '</a>';
				$cookie_text .= '<br>So you can get your identifier cookie back if you loose it.</p>';
				return $cookie_text . $content_obj;
			}
		}
		$address = $addresses[0];
	} else
	{
		$address = create_address();
		if ($address)
		{
			$wpdb->insert(
				esc_sql($tablename),
				array(
					'cookie' => esc_sql($cookie),
					'postid' => esc_sql($postid),
					'address' => esc_sql($address),
				)
			);
		} else
		{
			$text = '
				<h2>Technical Problems</h2>
				<p>There was an error! Please try again in some minutes.</p>
				<script>window.coblo_payment_check_url=' . json_encode(admin_url('admin-ajax.php')) . ';</script>
				<script>window.coblo_node_has_error=true;</script>
			';
			wp_enqueue_script("checking_payments", plugins_url('/scripts/checking_payments.js', __FILE__), array('jquery'));
			return $text;
		}
	}

	$text = "
		<h2>You have to pay to read this article</h2>
		<p>Please send " . get_option('coblo_amount') . " to " . $address . '.</p>
		<script>window.coblo_payment_check_url=' . json_encode(admin_url('admin-ajax.php')) . ';</script>
		<script>window.coblo_node_has_error=false;</script>
	';
	wp_enqueue_script("checking_payments", plugins_url('/scripts/checking_payments.js', __FILE__), array('jquery'));

	return $text;
}

function article_is_paid($address)
{
	$has_error = false;
	$is_paid = false;
	$postfields = array("jsonrpc" => "1.0", "id" => "curltest", "method" => "listaddresstransactions", "params" => [$address, 100000]);

	$answer = curl_post('http://' . get_option('coblo_host') . ':' . get_option('coblo_port'), json_encode($postfields), array(
		'username' => get_option('coblo_user'),
		'password' => get_option('coblo_password'),
		'timeout' => 10
	));

	if (($answer === false) || json_decode($answer)->error)
	{
		$has_error = true;
	} else
	{
		$result = json_decode($answer)->result;

		foreach ($result as $transaction)
			if ($transaction->balance->amount >= get_option('coblo_amount') && $transaction->valid)
				$is_paid = true;
	}

	return array("has_error" => $has_error, "is_paid" => $is_paid);
}

function create_address()
{
	$postfields = array("jsonrpc" => "1.0", "id" => "curltest", "method" => "getnewaddress", "params" => []);

	$answer = curl_post('http://' . get_option('coblo_host') . ':' . get_option('coblo_port'), json_encode($postfields), array(
		'username' => get_option('coblo_user'),
		'password' => get_option('coblo_password'),
		'timeout' => 10
	));

	if (($answer === false) || json_decode($answer)->error)
		return false;
	return json_decode($answer)->result;
}

function set_cookie()
{
	if (!array_key_exists('coblo-id', $_COOKIE))
	{
		$cookie = randomName(32);
		setcookie('coblo-id', $cookie, time() + 60 * 60 * 24 * 30, "/", "");
	}
}

function jal_install()
{
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'articlepayments';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE " . $table_name . " (
	id mediumint(9) NOT NULL AUTO_INCREMENT,
	cookie text NOT NULL,
	postid integer NOT NULL,
	address text NOT NULL,
	PRIMARY KEY  (id)
) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option('jal_db_version', $jal_db_version);
}

function curl_call($options = null)
{
	static $ch_singleton;

	if (!$options['return_handle'])
	{
		if (!$ch_singleton)
			$ch_singleton = curl_init();

		$ch = $ch_singleton;
	} else
	{
		// When we "return_handle" this, we always need the fresh handler
		$ch = curl_init();
	}

	$headers = array();

	if (is_array($options['additional_request_headers']))
		foreach ($options['additional_request_headers'] as $h)
			$headers [] = $h;

	curl_setopt($ch, CURLOPT_URL, $options['url']);

	// Legacy-Untersttzung
	if (!isset($options['max_time']) && isset($options['timeout']))
		$options['max_time'] = $options['timeout'];

	if (isset($options['max_time']))
		curl_setopt($ch, CURLOPT_TIMEOUT, $options['max_time']);
	else
		curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Default-Timeout: 5 Sekunden

	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Redirects erlauben
	// Nur wenn open_basedir nicht gesetzt ist, denn sonst wrde cURL es ohnehin verhindern, als Schutz vor "file://"-URLS
	if (!ini_get('open_basedir'))
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

	curl_setopt($ch, CURLOPT_HTTPGET, 1);

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, ($options['ignore_bad_certificates'] ? 0 : 2));

	curl_setopt($ch, CURLOPT_ENCODING, 'gzip');

	if ($options['username'] != '' || $options['password'] != '')
		curl_setopt($ch, CURLOPT_USERPWD, $options['username'] . ':' . $options['password']);

	if (isset($options['cookies']))
	{
		if (is_array($options['cookies']))
		{
			$cookies = '';
			foreach ($options['cookies'] as $key => $val)
				$cookies .= urlencode($key) . '=' . urlencode($val) . '; ';
			$cookies = substr($cookies, 0, -2);
		} else
			$cookies = $options['cookies'];

		curl_setopt($ch, CURLOPT_COOKIE, $cookies);
	}

	// Wegen OpenSSL-Bug #3038 kann sich cURL mit OpenSSL 0.9.8 im TLS-Modus nicht zu bestimmten Servern verbinden,
	// daher stellen wir fest den SSL3-Modus ein:
	$vi = curl_version();
	if (substr($vi['ssl_version'], 0, 13) == 'OpenSSL/0.9.8')
		curl_setopt($ch, CURLOPT_SSLVERSION, 3);

	if (isset($options['postfields']))
	{
		if (is_array($options['postfields']) && $options['no_multipart'])
		{
			$postfields = '';
			foreach ($options['postfields'] as $p => $v)
				$postfields .= urlencode($p) . '=' . urlencode($v) . '&';
			$postfields = substr($postfields, 0, -1);
		} else
			$postfields = $options['postfields'];

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
	}

	if ($headers)
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	if ($options['return_handle'])
		return $ch;

	curl_setopt($ch, CURLOPT_HEADER, !!$options['header_only']);
	curl_setopt($ch, CURLOPT_NOBODY, !!$options['header_only']);

	$result = curl_exec($ch);

	if (isset($options['header_only']))
		$result = new dfStringreturner($result, array('http_status' => curl_getinfo($ch, CURLINFO_HTTP_CODE)));

	return $result;
}

function curl_post($url, $postfields = null, $options = null)
{
	$options['url'] = $url;
	$options['postfields'] = $postfields;
	return curl_call($options);
}

function randomName($length)
{
	$pool = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9));
	$pool_length = count($pool);
	$result = '';

	for ($i = 0; $i < $length; $i++)
		$result .= $pool[rand(0, $pool_length - 1)];

	return $result;
}

