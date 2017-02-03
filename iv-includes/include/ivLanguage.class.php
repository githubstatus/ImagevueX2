<?php

class ivLanguage
{

	/**
	 * Return list of language files
	 *
	 * @return array
	 */
	public static function getAllLanguageNames()
	{
		$list = array();

		foreach (new ivFilterIteratorDot(new DirectoryIterator(self::_getLanguagesDir())) as $item) {
			if (ivFilepath::matchSuffix($item->getFilename(), array('xml'))) {
				$list[] = mb_strtolower(substr($item->getFilename(), 0, -4), 'UTF-8');
			}
		}

		if (file_exists(self::_getCustomLanguagesDir())) {
			foreach (new ivFilterIteratorDot(new DirectoryIterator(self::_getCustomLanguagesDir())) as $item) {
				if (ivFilepath::matchSuffix($item->getFilename(), array('xml'))) {
					$list[] = mb_strtolower(substr($item->getFilename(), 0, -4), 'UTF-8');
				}
			}
		}

		$list = array_unique($list);
		sort($list);
		return $list;
	}

	/**
	 * Return language XML by name
	 *
	 * @return ivXml
	 */
	public static function getLanguage($name, $forceCustom = false)
	{
		if ($forceCustom || file_exists(self::_getCustomLanguagesDir() . $name . '.xml')) {
			if (!($res = mkdirRecursive(self::_getCustomLanguagesDir(), 0777))) {
				ivMessenger::add(ivMessenger::ERROR, 'Cannot create folder iv-config/language/');
			}
			$configFile = self::_getCustomLanguagesDir() . $name . '.xml';
		} else {
			$configFile = self::_getLanguagesDir() . $name . '.xml';
		}
		return ivXml::readFromFile($configFile, self::_getDefaultLanguageFile());
	}

	/**
	 * Return default language XML
	 *
	 * @return ivXml
	 */
	public static function getDefaultLanguage()
	{
		return ivXml::readFromFile(self::_getDefaultLanguageFile());
	}

	/**
	 * Return languages directory path
	 *
	 * @return string
	 */
	private static function _getLanguagesDir()
	{
		return BASE_DIR . 'language' . DS;
	}

	/**
	 * Return custom languages directory path
	 *
	 * @return string
	 */
	private static function _getCustomLanguagesDir()
	{
		return USER_DIR . 'language' . DS;
	}

	/**
	 * Return default language file path
	 *
	 * @return string
	 */
	private static function _getDefaultLanguageFile()
	{
		return BASE_DIR . 'include' . DS . 'lang.xml';
	}

}