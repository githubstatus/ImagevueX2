<?php

/**
 * Theme
 *
 * @author McArrow
 */
class ivTheme
{

	/**
	 * Theme name
	 * @var string
	 */
	private $_name;

	/**
	 * Relative path to theme files
	 * @var string
	 */
	private $_relativePath;

	/**
	 * Backgrounds list
	 * @var array
	 */
	private $_backgrounds;

	/**
	 * Css list
	 * @var array
	 */
	private $_css;


	/**
	 * Theme config filename
	 * @var string
	 */
	private $_themeConfigFilename = 'config.xml';

	/**
	 * Constructor
	 *
	 * @param string $name
	 * @param string $path
	 */
	public function __construct($name, $relativePath)
	{
		$this->_name = $name;
		$this->_relativePath = $relativePath;
	}

	/**
	 * Return theme name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Returns relative directory path for current theme
	 *
	 * @return string
	 */
	public function getRelativePath()
	{
		return $this->_relativePath;
	}

	/**
	 * Returns absolute directory path for current theme
	 *
	 * @return string
	 */
	public function getAbsolutePath()
	{
		return ROOT_DIR . $this->getRelativePath();
	}

	/**
	 * Returns theme's CSS
	 *
	 * @return string
	 */
	public function getStyle($file='imagevue.css')
	{
		
		if (file_exists($this->getAbsolutePath() . $file)) {
			return file_get_contents($this->getAbsolutePath() . $file);
		}
	}

	/**
	 * Saves theme's CSS
	 *
	 * @param  string  $css
	 * @return boolean
	 */
	public function setStyle($css, $file='imagevue.css')
	{
		return iv_file_put_contents($this->getAbsolutePath() . $file, $css);
	}

	/**
	 * Returns list of possible background files
	 *
	 * @return string
	 */
	public function getBackgroundsList()
	{
		if (!isset($this->_backgrounds)) {
			$this->_backgrounds = array();

			foreach (new ivFilterIteratorDot(new DirectoryIterator($this->getAbsolutePath())) as $item) {
				if (
					(strtolower($item->getFilename()) != 'thumb.jpg')
					&& ivMapperXmlFile::isImagePath($item->getFilename())
					|| (strtolower(ivFilepath::suffix($item->getFilename()))=='swf')
					 ) {
					$this->_backgrounds[] = $item->getFilename();
				}
			}
		}

		return $this->_backgrounds;
	}

	/**
	 * Returns list of css files
	 *
	 * @return string
	 */
	public function getCssList()
	{

		$this->_css = array();

		foreach (new ivFilterIteratorDot(new DirectoryIterator($this->getAbsolutePath())) as $item) {
			if (strtolower(ivFilepath::suffix($item->getFilename()))=='css') {
				$this->_css[] = $item->getFilename();
			}
		}

		return $this->_css;
	}


	/**
	 * Returns theme's config
	 *
	 * @return ivXml|boolean
	 */
	public function getConfig()
	{
		$configFile = $this->getAbsolutePath() . $this->_themeConfigFilename;
		return ivXml::readFromFile($configFile, self::_getDefaultThemeFile());
	}

	/**
	 * Return default language file path
	 *
	 * @return string
	 */
	private static function _getDefaultThemeFile()
	{
		return BASE_DIR . 'include' . DS . 'theme.xml';
	}

	/**
	 * Returns full config
	 *
	 * @return ivXml|boolean
	 */
	public function getFullConfig()
	{
		$path = $this->getAbsolutePath() . $this->_themeConfigFilename;
		$descConfigXml = ivXml::readFromFile(DEFAULT_CONFIG_FILE);
		$descThemeXml = ivXml::readFromFile(self::_getDefaultThemeFile());
		$descXml = $this->_mergeXml($descConfigXml, $descThemeXml);
		$descXmlConfig = ivXml::readFromFile(CONFIG_FILE, $descXml);
		$xml = ivXml::readFromFile($path, $descXmlConfig);
		return $xml;
	}

	/**
	 * Merges two XMLs
	 *
	 * @param  ivXml $xml1
	 * @param  ivXml $xml2
	 * @return ivXml
	 */
	private function _mergeXml(ivXml $xml1, ivXml $xml2)
	{
		foreach ($xml2->toFlatTree() as $nodeItem) {
			if (!$nodeItem['node']->hasChildren()) {
				$xml1->add($nodeItem['path'], $nodeItem['node']);
			}
		}
		return $xml1;
	}

}