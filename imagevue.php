<?php
define('IV_PATH', 'iv-includes/');
define ('IS_FIRSTTIME', ((file_exists(dirname(__FILE__) .'/iv-config/users.php') && md5_file(dirname(__FILE__) .'/iv-config/users.php') != 'cb8cc88eb762d5b75594a64dcc506782'))?false:true);
if (!session_id()) {
	session_start();
}

include_once(IV_PATH . 'common.inc.php');
include_once(IV_PATH . 'config.inc.php');
require_once(IV_PATH . 'include/ivControllerFront.class.php');

ivAuth::authenticateByCookie();

if (1 == count($_GET) && (false === strpos($_SERVER['QUERY_STRING'], '='))) {
	$_GET['p'] = 'html';
	$_GET['path'] = urldecode($_SERVER['QUERY_STRING']);
	$_REQUEST['path'] = urldecode($_SERVER['QUERY_STRING']);
} else if ('/?' == $_SERVER['REQUEST_URI']) {
	$_GET['p'] = 'html';
	$_GET['path'] = '';
	$_REQUEST['path'] = '';
}

if (1 <= count($_GET) && isset($_GET['share'])) {

	$_GET['p'] = 'share';
	$_GET['path'] = $_GET['share'];
	$_REQUEST['path'] = $_GET['share'];
}

set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . DIRECTORY_SEPARATOR . IV_PATH . 'include/');

$cache_dir=dirname(__FILE__) . DIRECTORY_SEPARATOR . 'iv-config/cache/';

if (is_writable($cache_dir)) {
	require_once 'Zend/Loader/Autoloader.php';
	$autoloader = Zend_Loader_Autoloader::getInstance();
	$autoloader->setFallbackAutoloader(true);

	$frontendOptions = array(
		'lifetime' => 7200,
		'default_options' => array(
			'cache_with_get_variables' => true,
			'cache_with_session_variables' => true,
			'cache_with_cookie_variables' => true,
		),
		'memorize_headers' => array(
			'Content-Type',
		),
	);

	$backendOptions = array(
		'cache_dir' => $cache_dir
	);

	$cache = Zend_Cache::factory('Page',
	                             'File',
	                             $frontendOptions,
	                             $backendOptions);

	$cache->start();
}

ivControllerFront::getInstance()->dispatch(dirname(__FILE__) . DIRECTORY_SEPARATOR . IV_PATH);