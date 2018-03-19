<?php

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
