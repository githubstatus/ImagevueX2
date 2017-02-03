<?php

if (!defined('IV_PATH')) {
	exit(0);
}

$config = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE, true);

if (isset($_GET['lightview']) && !empty($_GET['lightview'])) {
	if ('true' == $_GET['lightview'] && !IS_MOBILE) {
		setcookie('lightview', 'true', 0, '/');
		$config->set('/config/imagevue/settings/useLightview', true);
	} else {
		setcookie('lightview', 'false', 0, '/');
		$config->set('/config/imagevue/settings/useLightview', false);
	}
} elseif (isset($_COOKIE['lightview'])) {
	if ('true' == $_COOKIE['lightview'] && !IS_MOBILE) {
		$config->set('/config/imagevue/settings/useLightview', true);
	} else {
		$config->set('/config/imagevue/settings/useLightview', false);
	}
}

// End of demonstration code

if (isset($_GET['language'])) {
	setcookie('language', mb_strtolower($_GET['language'], 'UTF-8'), 0, '/');
	$_COOKIE['language'] = mb_strtolower($_GET['language'], 'UTF-8');
}

if (isset($_COOKIE['language']) && in_array(mb_strtolower($_COOKIE['language'], 'UTF-8'), ivLanguage::getAllLanguageNames())) {
	$config->set('/config/imagevue/settings/language', mb_strtolower($_COOKIE['language'], 'UTF-8'));
}

ivPool::set('conf', $config);

$langName = mb_strtolower($config->get('/config/imagevue/settings/language'), 'UTF-8');
$xml = ivLanguage::getLanguage($langName);
ivPool::set('lang', $xml);
