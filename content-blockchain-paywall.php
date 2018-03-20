<?php
/*
Plugin Name: Content Blockchain Paywall
Plugin URI: content-blockchain.org
Description: Adds a paywall to all posts. Users will have to pay on the content blockchain to read your articles.
Version: 1.0
Author: Patricia Schinke
Author URI: content-blockchain.org
License: GPLv3
*/

require 'coblo-options.php';

global $jal_db_version;
$jal_db_version = '1.0';

add_action('wp_ajax_coblo_set_cookies', 'set_cookies');
add_action('wp_ajax_nopriv_coblo_set_cookies', 'set_cookies');
add_action('wp_ajax_check_paid', 'check_paid');
add_action('wp_ajax_nopriv_check_paid', 'check_paid');
add_filter('the_content', 'replace_content');
add_action('init', 'get_cookie_back');
register_activation_hook(__FILE__, 'jal_install');

function check_paid()
{
	global $wpdb;
	$tablename = $wpdb->prefix . 'articlepayments';

	$postids = $_POST["coblo_post_ids"];

	$addresses = array();
	foreach ($postids as $postid)
	{
		$cookie = $_COOKIE['coblo-id-' . $postid];
		$addresses = array_merge($addresses, $wpdb->get_col('
		SELECT address
		FROM ' . esc_sql($tablename) . '
		WHERE cookie = "' . esc_sql($cookie) . '"
		  AND postid = ' . esc_sql($postid)
		));
	}

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
	wp_register_style("paywall-styles", plugins_url('/styles/coblo.css', __FILE__));
	wp_enqueue_style("paywall-styles");
	global $wpdb;
	$tablename = $wpdb->prefix . 'articlepayments';
	$postid = get_the_ID();
	if (is_home())
	{
		$page_for_posts = get_option('page_for_posts');
		$postid = get_post($page_for_posts)->ID;
	}

	if (!array_key_exists('coblo-id-' . $postid, $_COOKIE))
	{
		wp_enqueue_script("add_cookies", plugins_url('/scripts/add_cookies.js', __FILE__), array('jquery'));
		$text = '
			<h3>Page will reload. Please wait some seconds.</h3>
			<script>window.coblo_payment_check_url=' . json_encode(admin_url('admin-ajax.php')) . ';</script>
			<script>if (!window.coblo_posts_need_cookie) {window.coblo_posts_need_cookie=[];}</script>
			<script>window.coblo_posts_need_cookie.push(' . $postid . ');</script>
		';
		return $text;
	}

	$cookie = $_COOKIE['coblo-id-' . $postid];

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
				$text = get_text(true, $postid, null);
				wp_enqueue_script("checking_payments", plugins_url('/scripts/checking_payments.js', __FILE__), array('jquery'));
				return $text;
			} else if ($article_paid["is_paid"])
			{
				$cookie_link = home_url() . '?coblo_post_id=' . $postid . '&coblo_cookie=' . $cookie;
				$cookie_text = '
					<p>
						Save this link:<br>
						<a class="coblo-cookie-link" href="' . $cookie_link . '">' . $cookie_link . '</a><br>
						So you can read this article from other devices.
					</p>
				';
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
			$text = get_text(true, $postid, null);
			wp_enqueue_script("checking_payments", plugins_url('/scripts/checking_payments.js', __FILE__), array('jquery'));
			return $text;
		}
	}

	$text = get_text(false, $postid, $address);
	wp_enqueue_script("checking_payments", plugins_url('/scripts/checking_payments.js', __FILE__), array('jquery'));

	return $text;
}

function get_text($technical_problems, $postid = null, $address = null)
{
	$text = "";
	if ($postid && strpos(get_post($postid)->post_content, '<!--more-->'))
	{
		$text .= explode('<!--more-->', get_post($postid)->post_content)[0];
		$text .= " ...";
	}
	$text .= "<div class='content-blockchain-post' style='background: " . get_option('coblo_paywall_color') . ";'>";
	if ($technical_problems)
	{
		$text .= "
			<h3>Technical Problems</h3>
			<p>There was an error! Please try again in some minutes.</p>
		";
	} else
	{
		$text .= '<h3>' . (get_option('coblo_paywall_header') ?: 'Thank you for the interest in our article') . '</h3>';
		$text .= '<p>Please send ' . get_option('coblo_amount') . " CHM to " . $address . '</p>';
	}
	$text .= '
		<script>window.coblo_payment_check_url=' . json_encode(admin_url('admin-ajax.php')) . ';</script>
		<script>window.coblo_node_has_error=' . ($technical_problems ? 'true' : 'false') . ';</script>
		<script>if (!window.coblo_post_ids) {window.coblo_post_ids=[];}</script>
		<script>window.coblo_post_ids.push(' . $postid . ');</script>
	</div>';
	return $text;
}

function article_is_paid($address)
{
	$has_error = false;
	$is_paid = false;
	$postfields = array(
		"jsonrpc" => "1.0",
		"id" => "curltest",
		"method" => "listaddresstransactions",
		"params" => [$address, 1000000]
	);

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

function set_cookies()
{
	foreach ($_POST['coblo_posts_need_cookie'] as $postid)
	{
		if (!array_key_exists('coblo-id-' . $postid, $_COOKIE))
		{
			$cookie = randomName(32);
			setcookie('coblo-id-' . $postid, $cookie, time() + 60 * 60 * 24 * 30, "/", "");
		}
	}
}

function get_cookie_back()
{
	if ($_GET['coblo_post_id'] && $_GET['coblo_cookie'])
	{
		setcookie('coblo-id-' . $_GET['coblo_post_id'], $_GET['coblo_cookie'], time() + 60 * 60 * 24 * 30, "/", "");
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

function randomName($length)
{
	$pool = array_merge(range('a', 'z'), range('A', 'Z'), range(0, 9));
	$pool_length = count($pool);
	$result = '';

	for ($i = 0; $i < $length; $i++)
		$result .= $pool[rand(0, $pool_length - 1)];

	return $result;
}

