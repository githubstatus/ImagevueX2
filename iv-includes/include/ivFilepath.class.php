<?php

/**
 * File names and paths manipulations class
 *
 * @static
 */
class ivFilepath
{

	/**
	 * Returns FILENAME's prefix (tn_) up to and including first occurrence of separator '_' or false
	 *
	 * @param  string $path
	 * @param  string $separator
	 * @return string
	 */
	public static function prefix($path, $separator='_')
	{
		$filename = ivFilepath::filename($path);
		$pos = strpos($filename, $separator);
		return (false === $pos ? false : substr($filename, 0, $pos + 1));
	}

	/**
	 * Returns path suffix (extension) after last occurence of $separator = '.' or false
	 *
	 * @param  string $path
	 * @param  string $separator
	 * @return string
	 */
	public static function suffix($path , $separator = '.')
	{
		$pos = strrpos($path, $separator);
		$slashPos1 = strrpos($path, '/');
		$slashPos2 = strrpos($path, '\\');
		return ((false === $pos || $slashPos1 > $pos || $slashPos2 > $pos) ? false : substr($path, $pos + 1));
	}

	/**
	 * Returns only FILENAME from path (no extension)
	 *
	 * @param  string $path
	 * @return string
	 */
	public static function filename($path)
	{
		if ('/' != substr($path, -1) && '\\' != substr($path, -1)) {
			return basename($path, '.' . ivFilepath::suffix($path));
		}
		return false;
	}

	/**
	 * Returns BASENAME (filename.ext)
	 *
	 * @return string
	 */
	public static function basename($path)
	{
		if ('/' != substr($path, -1) && '\\' != substr($path, -1)) {
			$pathParts = pathinfo($path);
			return $pathParts['basename'];
		}
		return false;
	}

	/**
	 * Returns DIRNAME (../dir/)
	 *
	 * @return string
	 */
	public static function directory($path)
	{
		if ('/' != substr($path, -1) && '\\' != substr($path, -1) && '' != $path) {
			$pathParts = pathinfo($path);
			return ivPath::canonizeRelative($pathParts['dirname']);
		}
		return ivPath::canonizeRelative($path);
	}

	/**
	 * Checks if file suffix is in array
	 *
	 * @param  string  $filename
	 * @param  array   $extArray
	 * @param  string  $separator
	 * @return boolean
	 */
	public static function matchSuffix($filename, $extArray, $separator = '.')
	{
		return in_array(strtolower(ivFilepath::suffix($filename, $separator)), $extArray);
	}

	/**
	 * Checks if file PREFIX is in array
	 *
	 * @param  string  $filename
	 * @param  array   $prefArray
	 * @param  string  $separator
	 * @return boolean
	 */
	public static function matchPrefix($filename, $prefArray)
	{
		foreach ($prefArray as $prefix) {
			if (strtolower(ivFilepath::prefix($filename)) == $prefix) {
				return true;
			}
		}
		return false;
	}

}