<?php

/**
 * Pool
 *
 */
class ivPool
{

	private static $_pool = array();

	/**
	 * Sets something to global pool
	 *
	 * @param mixed $name
	 * @param mixed $value
	 */
	public static function set($name, $value)
	{
		if (!self::isRegistered($name)) {
			self::$_pool[$name] = $value;
		}
	}

	/**
	 * Gets something from global pool
	 *
	 * @param mixed $name
	 */
	public static function get($name)
	{
		if (self::isRegistered($name)) {
			return self::$_pool[$name];
		}
	}

	/**
	 * Checks if something with given name exists in global pool
	 *
	 * @param  mixed $name
	 * @return boolean
	 */
	public static function isRegistered($name)
	{
		return array_key_exists($name, self::$_pool);
	}

}