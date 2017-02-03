<?php

/**
 * Stack class
 *
 */
class ivStack
{

	/**
	 * Stack storage
	 * @var array
	 */
	private $_list = array();

	/**
	 * Pushes element to stack
	 *
	 * @param mixed $element
	 */
	public function push($element)
	{
		$this->_list[count($this->_list)] = $element;
	}

	/**
	 * Pops element from stack
	 *
	 * @return mixed
	 */
	public function pop()
	{
		$result = $this->_list[count($this->_list) - 1];
		unset($this->_list[count($this->_list) - 1]);
		return $result;
	}

	/**
	 * Returns first element of stack
	 *
	 * @return mixed
	 */
	public function head()
	{
		return $this->_list[0];
	}

	/**
	 * Returns last element of stack (without popping)
	 *
	 * @return mixed
	 */
	public function tail()
	{
		if (isset($this->_list[count($this->_list) - 1])) {
			return $this->_list[count($this->_list) - 1];
		}
	}

}