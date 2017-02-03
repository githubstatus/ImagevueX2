<?php

abstract class ivRecord
{

	const STATE_DIRTY = 'STATE_DIRTY';
	const STATE_CLEAN = 'STATE_CLEAN';

	protected $_mapper;

	protected $_state;

	protected $_primary;

	protected $_propertyNames = array();

	protected $_attributeNames = array();

	protected $_userAttributeNames = array();

	protected $_data = array();

	public final function __construct($mapper = null)
	{
		if ($mapper) {
			$this->_mapper = $mapper;
		} else {
			$this->_mapper = ivMapperFactory::getMapper(strtolower(substr(get_class($this), 2)));
		}

		$this->_init();

		$this->_state = self::STATE_CLEAN;
	}

	public final function getMapper()
	{
		return $this->_mapper;
	}

	protected function _init()
	{}

	public final function getPropertyNames()
	{
		return $this->_propertyNames;
	}

	public final function getAttributeNames()
	{
		return $this->_attributeNames;
	}

	public final function getUserAttributeNames()
	{
		return $this->_userAttributeNames;
	}

	public final function setState($state)
	{
		if (in_array($state, array(self::STATE_DIRTY, self::STATE_CLEAN))) {
			$this->_state = $state;
		}
		return $this;
	}

	public final function getState()
	{
		return $this->_state;
	}

	public final function setPrimary($id)
	{
		if (!isset($this->_primary)) {
			$this->_primary = $id;
		}
		return $this;
	}

	public final function getPrimary()
	{
		return $this->_primary;
	}

	public final function getLink($forceMobile = false) {
		if ($forceMobile) {
			return preg_replace('/^\/?#\/?/', '?/', $this->link);
		} else {
			return $this->link;
		}
	}

	public final function __set($name, $value)
	{
		if (in_array($name, $this->_propertyNames) && isset($this->_data[$name]) && !empty($this->_data[$name])) {
			return;
		}

		$oldValue = isset($this->_data[$name]) ? $this->_data[$name] : null;
		$this->_data[$name] = $value;

		if (substr($name, 0, 1) != strtoupper(substr($name, 0, 1)) && $oldValue != $value) {
			$this->setState(self::STATE_DIRTY);
		}
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->_data)) {
			return $this->_data[$name];
		}

		$mapperMethodName = 'get' . $name . 'Proxy';
		if (method_exists($this->getMapper(), $mapperMethodName)) {
			$this->_data[$name] = $this->getMapper()->$mapperMethodName($this);
			return $this->_data[$name];
		}
	}

	public final function __isset($name)
	{
		return isset($this->_data[$name]);
	}

	public function save()
	{
		if (method_exists($this->getMapper(), 'save')) {
			return $this->getMapper()->save($this);
		}

		return false;
	}

	public function refresh()
	{
		if (method_exists($this->getMapper(), 'refresh')) {
			return $this->getMapper()->refresh($this);
		}

		return false;
	}

	public function delete()
	{
		if (method_exists($this->getMapper(), 'delete')) {
			return $this->getMapper()->delete($this);
		}

		return false;
	}

	public final function toArray($fieldsOnly = false)
	{
		$array = array();
		foreach ($this->_data as $name => $value) {
			if (is_a($value, 'ivRecord') || is_a($value, 'ivRecordCollection')) {
				if (!$fieldsOnly) {
					$array[$name] = $value->toArray();
				}
			} else {
				$array[$name] = $value;
			}
		}
		return $array;
	}

	public function getTitle()
	{
		$title = $this->title;
		if (!$title) {
			$title = ivFilepath::filename($this->name);
		}
		return stripslashes($title);
	}

	public function getCleanTitle() {

		return htmlspecialchars(strip_icon(strip_tags(t($this->getTitle()))));

	}

	/**
	 * Remove unnecessary elements from data
	 *
	 * @return array
	 */
	protected function _getCleanData()
	{
		$result = array();
		foreach ($this->_data as $key => $value) {
			if (!is_object($value) && (!empty($value) || '0' == $value)) {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Converts record to XML node
	 *
	 * @param  boolean   $expanded
	 * @return ivXmlNode
	 */
	abstract public function asXml($expanded = true);

	public function getPath()
	{
		return $this->getMapper()->getPathProxy($this);
	}

}