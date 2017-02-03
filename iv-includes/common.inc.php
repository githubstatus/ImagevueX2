<?php

if (!defined('IV_PATH')) {
	exit(0);
}

define('START_TIME', microtime());
define('DS', '/');

// php 5.2 stuff
if (!defined('E_RECOVERABLE_ERROR')) {
	define('E_RECOVERABLE_ERROR', 4096);
}
// php 5.3 stuff
if (!defined('E_DEPRECATED')) {
	define('E_DEPRECATED', 8192);
}
if (!defined('E_USER_DEPRECATED')) {
	define('E_USER_DEPRECATED', 16384);
}

if (version_compare('5.3.0', phpversion()) > 0) {
	set_magic_quotes_runtime(0);
}
//error_reporting(0);
error_reporting(E_ALL);
ini_set('display_errors','On');
ini_set('display_startup_errors','On');
if (!ini_get('date.timezone')) {
	ini_set('date.timezone', 'UTC');
}

umask(0);

define('BASE_DIR',						dirname(__FILE__) . DS);
define('INCLUDE_DIR',					BASE_DIR . 'include' . DS);
define('CONTROLLERS_DIR',				BASE_DIR . 'controllers' . DS);
define('DEFAULT_CONFIG_FILE', 			BASE_DIR . 'include' . DS . 'config.xml');

// Is mobile phone?
if (isset($_GET['mobile'])) {
	if ($mobile=$_GET['mobile']) {
		setcookie('IS_MOBILE', true); define('IS_MOBILE', true);
	} else {
		setcookie('IS_MOBILE', null, -1); define('IS_MOBILE', false);
	}
}
if (isset($_COOKIE['IS_MOBILE']) && !defined('IS_MOBILE')) define('IS_MOBILE', true);

if (!defined('IS_MOBILE'))
	define('IS_MOBILE', preg_match('/android|bada|blackberry|ipad|iphone|ipod|phone|mobile/i', $_SERVER['HTTP_USER_AGENT']));

define('IS_IPHONE', preg_match('/ipad|iphone|ipod/i', $_SERVER['HTTP_USER_AGENT']));

if (IS_MOBILE) {
	define('SKIN', 				'mobile');
} else {
	define('SKIN', 				'default');
}

require_once(INCLUDE_DIR . 'functions.inc.php');
require_once(INCLUDE_DIR . 'ivfunctions.inc.php');
require_once(INCLUDE_DIR . 'ivAcl.class.php');
require_once(INCLUDE_DIR . 'ivAuth.class.php');

require_once(INCLUDE_DIR . 'ivMapperFactory.class.php');
require_once(INCLUDE_DIR . 'ivMapperAbstract.class.php');
require_once(INCLUDE_DIR . 'ivMapperXmlAbstract.class.php');
require_once(INCLUDE_DIR . 'ivMapperXmlFolder.class.php');
require_once(INCLUDE_DIR . 'ivMapperXmlFile.class.php');
require_once(INCLUDE_DIR . 'ivRecord.class.php');
require_once(INCLUDE_DIR . 'ivRecordCollection.class.php');
require_once(INCLUDE_DIR . 'ivFolder.class.php');
require_once(INCLUDE_DIR . 'ivFile.class.php');
require_once(INCLUDE_DIR . 'ivFileImage.class.php');
require_once(INCLUDE_DIR . 'ivFileMP3.class.php');
require_once(INCLUDE_DIR . 'ivFileVideo.class.php');

require_once(INCLUDE_DIR . 'ivControllerAbstract.class.php');
require_once(INCLUDE_DIR . 'ivController.class.php');
require_once(INCLUDE_DIR . 'ivControllerDispatcher.class.php');
require_once(INCLUDE_DIR . 'ivControllerFront.class.php');
require_once(INCLUDE_DIR . 'ivCrumbs.class.php');
require_once(INCLUDE_DIR . 'ivErrors.class.php');
require_once(INCLUDE_DIR . 'ivExifParser.class.php');
require_once(INCLUDE_DIR . 'ivFilepath.class.php');
require_once(INCLUDE_DIR . 'ivImage.class.php');
require_once(INCLUDE_DIR . 'ivImageAdapterInterface.class.php');
require_once(INCLUDE_DIR . 'ivImageAdapterGd.class.php');
require_once(INCLUDE_DIR . 'ivImageAdapterImagemagick.class.php');
require_once(INCLUDE_DIR . 'ivImageAdapterImagick.class.php');
require_once(INCLUDE_DIR . 'ivIptcParser.class.php');
require_once(INCLUDE_DIR . 'ivView.class.php');
require_once(INCLUDE_DIR . 'ivLayout.class.php');
require_once(INCLUDE_DIR . 'ivLanguage.class.php');
require_once(INCLUDE_DIR . 'ivMail.class.php');
require_once(INCLUDE_DIR . 'ivMessenger.class.php');
require_once(INCLUDE_DIR . 'ivPath.class.php');
require_once(INCLUDE_DIR . 'ivPhpdocParser.class.php');
require_once(INCLUDE_DIR . 'ivPlaceholder.class.php');
require_once(INCLUDE_DIR . 'ivPool.class.php');
require_once(INCLUDE_DIR . 'ivStack.class.php');
require_once(INCLUDE_DIR . 'ivTheme.class.php');
require_once(INCLUDE_DIR . 'ivThemeMapper.class.php');
require_once(INCLUDE_DIR . 'ivUrl.class.php');
require_once(INCLUDE_DIR . 'ivUserManager.class.php');
require_once(INCLUDE_DIR . 'ivXml.class.php');
require_once(INCLUDE_DIR . 'ivXmlNode.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeArray.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeBoolean.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeColor.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeDir.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeEnum.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeInteger.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeLanguage.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeString.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodePassword.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeText.class.php');
require_once(INCLUDE_DIR . 'ivXmlNodeTheme.class.php');
require_once(INCLUDE_DIR . 'ivXmlParser.class.php');
require_once(INCLUDE_DIR . 'ivXmpParser.class.php');
require_once(INCLUDE_DIR . 'getid3/getid3.php');
require_once(INCLUDE_DIR . 'ivSimpleXMLElement.class.php');
require_once(INCLUDE_DIR . 'ivFilterIteratorDot.class.php');
require_once(INCLUDE_DIR . 'ivFilter.class.php');
require_once(INCLUDE_DIR . 'ivRecursiveFilterIteratorDot.class.php');
require_once(INCLUDE_DIR . 'ivRecursiveFolderIterator.class.php');

require_once(INCLUDE_DIR . 'PHPMailer/php5/class.phpmailer.php');
require_once(INCLUDE_DIR . 'JSON.php');

define('ROOT_DIR', ivPath::canonizeAbsolute(dirname(dirname(__FILE__))));

define('USER_DIR',    ROOT_DIR . 'iv-config' . DS);
define('CONFIG_FILE', USER_DIR . 'config.xml');
define('USERS_FILE',  USER_DIR . 'users.php');

// Migration script goes here
include_once(dirname(__FILE__) . DS . 'migration.php');
// ---

set_error_handler(array('ivErrors', 'add'), E_ALL & ~E_STRICT & ~E_DEPRECATED & ~E_USER_DEPRECATED);

ivErrors::clear();

// Seek for troubles with some php extensions
// iconv
define('ICONV_INSTALLED', function_exists('iconv'));

// gd
define('GD_INSTALLED', extension_loaded('gd'));

// exif
define('EXIF_INSTALLED', function_exists('exif_read_data'));

// xml
define('XML_INSTALLED', extension_loaded('xml'));

// mbstring
define('MBSTRING_INSTALLED', extension_loaded('mbstring'));

// safe_mode
define('SAFE_MODE_ENABLED', (bool) ini_get('safe_mode'));

// open_basedir
define('OPEN_BASEDIR_ENABLED', (bool) ini_get('open_basedir'));

// suhosin
$suhosinInstalled = function_exists('get_loaded_extensions') ? in_array('suhosin', get_loaded_extensions()) : false;
if (function_exists('phpinfo') && !$suhosinInstalled) {
	ob_start();
	phpinfo(INFO_MODULES);
	$modules = ob_get_contents();
	ob_end_clean();
	if (false !== stristr($modules, 'suhosin')) {
		$suhosinInstalled = true;
	}
}
define('SUHOSIN_INSTALLED', $suhosinInstalled);

ivPool::set('placeholder', new ivPlaceholder());
ivPool::set('userManager', new ivUserManager());
ivPool::set('breadCrumbs', new ivCrumbs());

if (!headers_sent()) {
	header('Cache-Control: no-store, no-cache, must-revalidate');
	header('Expires: ' . date('r'));
}

// IIS REQUEST_URI fix
if (!isset($_SERVER['REQUEST_URI'])) {
	$requestUri = $_SERVER['SCRIPT_NAME'];
	if (!empty($_SERVER['QUERY_STRING'])) {
		$requestUri .= '?' . $_SERVER['QUERY_STRING'];
	}
	$_SERVER['REQUEST_URI'] = $requestUri;
}

