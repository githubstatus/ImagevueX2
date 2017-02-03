<?php

/**
 * BreadCrumbs class
 *
 * @author McArrow
 */
class ivCrumbs
{

	/**
	 * Array of crumbs
	 * @var    array
	 */
	private $_crumbs = array();

	/**
	 * Adds a new crumb
	 *
	 * @param string $title
	 * @param string $url
	 * @param string $suffix
	 * @param string $className
	 */
	public function push($title, $url = '', $suffix = '', $className = '')
	{
		$crumb = new stdClass();
		$crumb->title = $title;
		$crumb->url = $url;
		$crumb->suffix = $suffix;
		$crumb->className = $className;
		$this->_crumbs[] = $crumb;
	}

	/**
	 * Returns title of last crumb
	 *
	 * @return string
	 */
	public function tail()
	{
		$tail = $this->_crumbs[count($this->_crumbs) - 1];
		return $tail;
	}

	/**
	 * Returns array of crumbs
	 *
	 * @return array
	 */
	public function get()
	{
		return $this->_crumbs;
	}

	/**
	 * Returns array count
	 *
	 * @return array
	 */
	public function count()
	{
		return count($this->_crumbs);
	}

	/**
	 * Clears array
	 */
	public function clear()
	{
		$this->_crumbs = array();
	}

	/**
	 * Clears array
	 */
	public function pop()
	{
		return array_pop($this->_crumbs);
	}

}