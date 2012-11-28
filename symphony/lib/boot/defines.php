<?php

	/***
	 *
	 * Symphony web publishing system
	 *
	 * Copyright 2004–2006 Twenty One Degrees Pty. Ltd.
	 *
	 * @version 1.7
	 * @licence https://github.com/symphonycms/symphony-1.7/blob/master/LICENCE
	 *
	 ***/

	function precision_timer($action = 'start', $start_time = null){
		list($time, $micro) = explode(' ', microtime());

		$currtime = $time + $micro;

		if(strtolower($action) == 'stop')
			return number_format(abs($currtime - $start_time), 3, '.', ',');

		return $currtime;
	}

	define('__IN_SYMPHONY__', true);

	define('MANIFEST', 		DOCROOT . '/manifest');
	define('CONF', 			DOCROOT . '/manifest');
	define('CORE', 			DOCROOT . '/symphony');
	define('CAMPFIRE', 		DOCROOT . '/campfire');
	define('WORKSPACE', 	DOCROOT . '/workspace');

	define('LIBRARY',		CORE . '/lib');
	define('AJAX', 			CORE . '/ajax');

	define('UPLOAD', 		WORKSPACE . '/upload');
	define('DATASOURCES',	WORKSPACE . '/data-sources');
	define('EVENTS',		WORKSPACE . '/events');
	define('TEXTFORMATTERS',WORKSPACE . '/text-formatters');

	define('CACHE',			MANIFEST . '/cache');
	define('TMP',			MANIFEST . '/tmp');
	define('LOGS',			MANIFEST . '/logs');
	define('CONFIG', 		MANIFEST . '/config.php');

	define('TOOLKIT',		LIBRARY . '/toolkit');
	define('LANG',			LIBRARY . '/lang');

	define('STARTTIME', precision_timer());

	define('TWO_WEEKS',	72576000); //2 weeks in seconds. Used by cookies.
	define('CACHE_LIFETIME', TWO_WEEKS);

	define('HTTPS', $_SERVER['HTTPS']);
	define('HTTP_HOST', $_SERVER['HTTP_HOST']);
	define('REMOTE_ADDR', $_SERVER['REMOTE_ADDR']);
	define('HTTP_USER_AGENT', $_SERVER['HTTP_USER_AGENT']);

	define('__SECURE__', (HTTPS == 'on'));
	if(!defined('URL')) define('URL', 'http' . (defined('__SECURE__') && __SECURE__ ? 's' : '') . '://' . DOMAIN);

	define('_CURL_AVAILABLE_', function_exists('curl_init'));

	if(function_exists('domxml_xslt_stylesheet')){
		define('_USING_DOMXML_XSLT_', true);
		define('_XSLT_AVAILABLE_', true);

	}elseif(class_exists('XsltProcessor') || function_exists('xslt_process')){
		define('_XSLT_AVAILABLE_', true);
		define('_USING_DOMXML_XSLT_', false);

	}else
		define('_XSLT_AVAILABLE_', false);

	define('_USING_SABLOTRON_', (function_exists('xslt_backend_name') && !_USING_DOMXML_XSLT_));

	if(function_exists('apache_get_modules'))
		define('CAN_USE_CLEAN_URL', in_array('mod_rewrite', apache_get_modules()));

	define('_USING_DST_', ($settings['region']['dst'] == 'no' ? 0 : 1));

	define('__SYM_COOKIE__', $settings['symphony']['cookie_prefix'] . 'auth');
	define('__SYM_COOKIE_SAFE__', $settings['symphony']['cookie_prefix'] . 'auth_safe');

	define('CRLF', "\r\n");
?>
