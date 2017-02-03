<?php

/**
 * Error-free file_put_contents function
 *
 * @param  string $filename
 * @param  mixed  $data
 * @return int
 */
function iv_file_put_contents($filename, $data)
{
	$isWritable = file_exists($filename) && is_writable($filename);
	$notExistsAndIsWritable = !file_exists($filename) && is_writable(dirname($filename));

	if ($isWritable || $notExistsAndIsWritable) {
		ivErrors::disable();
		$result = @file_put_contents($filename, $data);
		ivErrors::enable();
		return $result;
	}

	ivMessenger::add(ivMessenger::ERROR, "Can't write to file " . substr($filename, strlen(ROOT_DIR)));

	return false;
}

/**
 * Error-free chmod function
 *
 * @param  string  $filename
 * @param  integer $mode
 * @return boolean
 */
function iv_chmod($filename, $mode)
{
	if (file_exists($filename) && is_writable($filename)){
		return true;
	} 
	elseif (file_exists($filename)) 
	{
		ivErrors::disable();
		$result = @chmod($filename, $mode);
		ivErrors::enable();
		return $result;
	}

	return false;
}

/**
 * Error-free mkdir function
 *
 * @param  string  $pathname
 * @param  integer $mode
 * @return boolean
 */
function iv_mkdir($pathname, $mode)
{
	if (file_exists(dirname($pathname)) && is_writable(dirname($pathname))) {
		return @mkdir($pathname, $mode);
	}

	ivMessenger::add(ivMessenger::ERROR, "Can't create folder " . substr($pathname, strlen(ROOT_DIR)));

	return false;
}

/**
 * touch() function wrapper
 *
 * @param  string  $filename
 * @return boolean
 */
function iv_touch($filename)
{
	if (!file_exists($filename)) {
		return false;
	}

	if (is_file($filename)) {
		return touch($filename);
	}

	// stupid touch() dirty hack
	$tmpName = md5(microtime());
	$result = touch($filename . DS . $tmpName);
	unlink($filename . DS . $tmpName);
	return $result;

}

function iv_getimagesize($filename, &$imageinfo = array())
{
	ivErrors::disable();
	$result = @getimagesize($filename, $imageinfo);
	ivErrors::enable();
	if (!$result && in_array(strtolower(ivFilepath::suffix($filename)), array('gif', 'jpeg', 'jpg', 'tif', 'tiff', 'png'))) {
		ivMessenger::add(ivMessenger::ERROR, 'Image corrupt: ' . $filename);
	}
	return $result;
}

function t($string)
{
	$language = mb_strtolower(ivPool::get('conf')->get('/config/imagevue/settings/language'), 'UTF-8');

	$result = '';
	foreach (preg_split('/(\[(\w+)\].*?\[\/\2\])/ims', $string, -1, PREG_SPLIT_DELIM_CAPTURE) as $i => $s) {
		switch ($i % 3) {
			case 0:
				$result .= $s;
				break;
			case 1:
				if (0 === strpos(mb_strtolower($s, 'UTF-8'), "[$language]")) {
					$firstCloseBracketPos = strpos($s, ']');
					$result .= substr($s, $firstCloseBracketPos + 1, strrpos($s, '[') - $firstCloseBracketPos - 1);
				}
				break;
			default:
				break;
		}
	}

	return $result;
}

function strip_icon($string)
{
	return preg_replace('/^\[[\w\-\.]+\]/', '', $string);
}

function get_icon($string)
{
	$result = false;
	if (preg_match('/\[[\w\-\.]+\]/', $string)) {
		list($image) = explode(']', $string);
		$result = substr($image, 1);
	}
	return $result;
}

function iv_filemtime($filename)
{
	$time = filemtime($filename);

	$isDST = (date('I', $time) == 1);
	$systemDST = (date('I') == 1);

	$adjustment = 0;

	if ($isDST == false && $systemDST == true) {
		$adjustment = 3600;
	} else if ($isDST == true && $systemDST == false) {
		$adjustment = -3600;
	} else {
		$adjustment = 0;
	}

	return ($time + $adjustment);
}