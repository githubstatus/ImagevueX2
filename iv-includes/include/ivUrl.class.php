<?php

/**
 * Url manager
 *
 * @author McArrow
 */
class ivUrl
{

	public static function url($attrs = array())
	{
		$url = 'index.php' . (count($attrs) ? '?' : '');

		$pairs = array();

		foreach ($attrs as $key => $value) {
			$pairs[] = $key . '=' . ('path' == $key ? smart_urlencode($value) : urlencode($value));
		}

		$url .= implode('&amp;', $pairs);

		return $url;
	}

}
