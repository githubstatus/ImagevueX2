<?php

/**
 * Abstract xml mapper
 *
 * @author McArrow
 */
abstract class ivMapperXmlAbstract extends ivMapperAbstract
{

	const THUMBNAIL_PREFIX = 'tn_';

	/**
	 * folderdata.xml cache
	 * @var array
	 */
	private static $_folderDatas = array();

	private static $_corruptedXmls = array();

	/**
	 * Read folderdata.xml
	 *
	 * @param  string $path
	 * @return ivSimpleXMLElement
	 */
	protected static function _getFolderdataXml($path)
	{
		if (!isset(self::$_folderDatas[$path])) {
			if (is_file($path . 'folderdata.xml')) {
				ivErrors::disable();
				try {
					self::$_folderDatas[$path] = new ivSimpleXMLElement($path . 'folderdata.xml', null, true);
				} catch (Exception $e) {
					self::$_corruptedXmls[] = $path;
					ivMessenger::add(ivMessenger::ERROR, 'Problems with ' . substr($path . 'folderdata.xml', strlen(ROOT_DIR)));
					self::$_folderDatas[$path] = new ivSimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><folder />');
				}
				ivErrors::enable();
			} else {
				self::$_folderDatas[$path] = new ivSimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><folder />');
			}
		}
		return self::$_folderDatas[$path];
	}

	/**
	 * Write folderdata.xml
	 *
	 * @param  string $path
	 * @return boolean
	 */
	protected static function _saveFolderdataXml($path)
	{
		if (in_array($path, self::$_corruptedXmls)) {
			return false;
		}
		return self::_getFolderdataXml($path)->asXML($path . 'folderdata.xml');
	}

	/**
	 * Save record
	 *
	 * @param ivRecord $record
	 */
	abstract public function save(ivRecord $record);

	/**
	 * Delete record
	 *
	 * @param ivRecord $record
	 */
	abstract public function delete(ivRecord $record);

	/**
	 * Return record's parent
	 *
	 * @param  ivRecord $record
	 * @return ivRecord|false
	 */
	public function getParentProxy(ivRecord $record)
	{
		if ('' == $record->getPrimary()) {
			return false;
		}
		$parentPath = ivPath::canonizeRelative(dirname($record->getPrimary()));
		if ($parent = ivMapperFactory::getMapper('folder')->find($parentPath, true)) {
			return $parent;
		}
		return false;
	}

	public function getThumbnailMTime(ivRecord $record)
	{
		if (file_exists(ROOT_DIR . $record->thumbnail)) {
			return iv_filemtime(ROOT_DIR . $record->thumbnail);
		}
	}

	public function getPathProxy(ivRecord $record)
	{
		return ivPath::canonizeRelative($this->_conf->get('/config/imagevue/settings/contentfolder')) . $record->getPrimary();
	}

}