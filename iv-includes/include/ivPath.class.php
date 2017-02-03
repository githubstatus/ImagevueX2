<?php

/**
 * File system paths manipulations class
 *
 * @static
 */
class ivPath
{

	/**
	 * Canonize relative path
	 *
	 * @param  string  $path
	 * @param  boolean $isFile
	 * @return string
	 */
	public static function canonizeRelative($path, $isFile = false)
	{
		$path = str_replace(array('/', '\\'), DS, $path);
		$parts = array_filter(explode(DS, $path), 'strlen');
		$safeParts = array();
		foreach ($parts as $part) {
			if (false !== strpos($part, ':')) continue;
			if (substr_count($part, '.') == strlen($part)) continue;
			$safeParts[] = $part;
		}
		return implode(DS, $safeParts) . (!$isFile && count($safeParts) ? DS : '');
	}

	/**
	 * Canonize absolute path
	 *
	 * @param  string  $path
	 * @param  boolean $isFile
	 * @return string
	 */
	public static function canonizeAbsolute($path, $isFile = false)
	{
		if ('\\\\' == substr($path, 0, 2)) {
			return '\\\\' . ivPath::canonizeRelative(substr($path, 2), $isFile);
		} else if (preg_match('/^\w\:/', $path)) {
			return substr($path, 0, 2) . DS . ivPath::canonizeRelative(substr($path, 2), $isFile);
		} else {
			return DS . ivPath::canonizeRelative($path, $isFile);
		}
	}

}
