<?php

/**
 * Placeholder class
 *
 */
class ivPlaceholder
{

	private $_placeholders = array();

	/**
	 * Set placeholder
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function set($name, $value)
	{
		$this->_placeholders[$name] = (string) $value;
	}

	/**
	 * Return placeholder
	 *
	 * @param string $name
	 */
	public function get($name)
	{
		return (isset($this->_placeholders[$name]) ? $this->_placeholders[$name] : '');
	}

}