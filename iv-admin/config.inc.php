<?php
if (!defined('IV_PATH')) {
	exit(0);
}

$config = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE);

ivPool::set('conf', $config);

$currentFolderInfo = pathinfo(dirname(__FILE__));

if (($currentFolderInfo['basename'] . DS) != $config->get('/config/imagevue/settings/adminfolder')) {
	$config->set('/config/imagevue/settings/adminfolder', $currentFolderInfo['basename'] . DS);
	$config->writeToFile();
	header('Location: ' . $_SERVER['REQUEST_URI']);
	exit(0);
}

if (isset($_COOKIE['ivnotes'])) {
	ivPool::set('notes', array_unique(array_explode_trim(',', $_COOKIE['ivnotes'])));
} else {
	ivPool::set('notes', array());
}