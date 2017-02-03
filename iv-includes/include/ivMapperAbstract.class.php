<?php

abstract class ivMapperAbstract
{

	protected $_conf;

	protected $_collection = array();

	private $_contentDirPath;

	public function __construct()
	{
		$this->_conf = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE, true);

		$this->_contentDirPath = ivPath::canonizeRelative($this->_conf->get('/config/imagevue/settings/contentfolder'));
	}

	abstract public function find($id);

	abstract public function getParentProxy(ivRecord $record);

	protected function _getContentDirPath()
	{
		return $this->_contentDirPath;
	}

}