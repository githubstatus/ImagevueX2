<?php

/**
 * Messenger
 *
 * @author McArrow
 */
class ivMessenger
{

	const NOTICE = 'notice';
	const WARNING = 'warning';
	const ERROR = 'error';

	/**
	 * Adds new message by type
	 *
	 * @param string $type
	 * @param string $message
	 */
	public static function add($type, $message)
	{
		$_SESSION['imagevue']['messages'][$type][] = $message;
	}

	/**
	 * Returns an array of messages by type
	 *
	 * @param string $type
	 * @return array
	 */
	public static function get($type, $clear = true)
	{
		if (isset($_SESSION['imagevue']['messages'][$type])) {
			$m = $_SESSION['imagevue']['messages'][$type];
			if ($clear) {
				unset($_SESSION['imagevue']['messages'][$type]);
			}
			return array_unique($m);
		} else {
			return array();
		}
	}

	/**
	 * Returns message count
	 * @return integer
	 */
	public static function count()
	{
		return isset($_SESSION['imagevue']['messages']) ? count($_SESSION['imagevue']['messages']) : 0;
	}

}