<?php

interface ivFilter
{
	public function accept($value);
}

class ivFilterExtension implements ivFilter
{

	private $_extensions;

	public function __construct($extensions)
	{
		if (is_array($extensions)) {
			$this->_extensions = array_intersect($this->_getAllowedExtensions(), $extensions);
		} else {
			if ('!' == substr($extensions, 0, 1)) {
				$extensionArray = array_explode_trim(',', substr($extensions, 1));
				if (!empty($extensionArray)) {
					$this->_extensions = array_diff($this->_getAllowedExtensions(), $extensionArray);
				} else {
					$this->_extensions = $this->_getAllowedExtensions();
				}
			} else {
				$extensionArray = array_explode_trim(',', $extensions);
				if (!empty($extensionArray)) {
					$this->_extensions = array_intersect($this->_getAllowedExtensions(), $extensionArray);
				} else {
					$this->_extensions = $this->_getAllowedExtensions();
				}
			}
		}
	}

	public function accept($value)
	{
		return ivFilepath::matchSuffix($value->name, $this->_extensions);
	}

	private function _getAllowedExtensions()
	{
		return ivPool::get('conf')->get('/config/imagevue/settings/allowedext');
	}

}

class ivFilterPrefix implements ivFilter
{

	private $_prefixes;

	public function __construct($prefixes)
	{
		$prefixesArray = array_explode_trim(',', $prefixes);
		if (!empty($prefixesArray)) {
			$this->_prefixes = array_intersect($this->_getExcludedPrefixes(), $prefixesArray);
		} else {
			$this->_prefixes = $this->_getExcludedPrefixes();
		}
	}

	public function accept($value)
	{
		return !ivFilepath::matchPrefix($value->name, $this->_prefixes);
	}

	private function _getExcludedPrefixes()
	{
		return ivPool::get('conf')->get('/config/imagevue/settings/excludefilesprefix');
	}

}