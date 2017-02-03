<?php

/**
 * Errors storage wrapper
 *
 * @author McArrow
 */
class ivErrors
{

	private static $_enabled = true;

	/**
	 * Adds new error
	 *
	 * @param integer $severity
	 * @param string  $message
	 * @param string  $filepath
	 * @param integer $line
	 */
	public static function add($severity, $message, $filepath, $line)
	{
		if (!isset($_SESSION['imagevue']['errors'])) {
			ivErrors::clear();
		}

		if (preg_match('/\Wgetid3\W/i', $filepath)) {
			if (in_array($severity, array(E_NOTICE, E_USER_NOTICE))) {
				return;
			}
		}

		if (!self::$_enabled) {
			return;
		}

		$filepath = ivPath::canonizeAbsolute($filepath, true);

		// For safety reasons we do not show the full file path
		if (false !== strpos($filepath, '/')) {
			$filepath = substr($filepath, strlen(ROOT_DIR) - 1);
		}

		$levels = array(
			E_ERROR => 'Error',
			E_WARNING => 'Warning',
			E_PARSE => 'Parsing Error',
			E_NOTICE => 'Notice',
			E_CORE_ERROR => 'Core Error',
			E_CORE_WARNING => 'Core Warning',
			E_COMPILE_ERROR => 'Compile Error',
			E_COMPILE_WARNING => 'Compile Warning',
			E_USER_ERROR => 'User Error',
			E_USER_WARNING => 'User Warning',
			E_USER_NOTICE => 'User Notice',
			E_STRICT => 'Strict',
			E_RECOVERABLE_ERROR  => 'Recoverable Error',
			E_DEPRECATED => 'Deprecated',
			E_USER_DEPRECATED => 'User Deprecated',
		);

		$severity = (!isset($levels[$severity])) ? $severity : $levels[$severity];

		$newError = array(
			'severity' => $severity,
			'message' => $message,
			'filepath' => $filepath,
			'line' => $line,
		);
		foreach ($_SESSION['imagevue']['errors'] as $error) {
			if ($error == $newError) {
				return;
			}
		}
		$_SESSION['imagevue']['errors'][] = $newError;
	}

	/**
	 * Returns all errors and empties storage
	 *
	 * @param  integer $severity
	 * @return array
	 */
	public static function get()
	{
		$errors = array();
		if (isset($_SESSION['imagevue']['errors'])) {
			$errors = $_SESSION['imagevue']['errors'];
		}
		ivErrors::clear();
		return $errors;
	}

	/**
	 * Clears error storage
	 *
	 */
	public static function clear()
	{
		$_SESSION['imagevue']['errors'] = array();
	}

	/**
	 * Checks if error storage is empty
	 *
	 * @return boolean
	 */
	public static function isEmpty()
	{
		if (isset($_SESSION['imagevue']['errors'])) {
			return (count($_SESSION['imagevue']['errors']) == 0);
		}
		return true;
	}

	/**
	 * Enables error logging
	 *
	 */
	public static function enable()
	{
		self::$_enabled = true;
	}

	/**
	 * Disables error logging
	 *
	 */
	public static function disable()
	{
		self::$_enabled = false;
	}

}