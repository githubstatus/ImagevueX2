<?php

class ivThemeMapper
{

	private $_themesDir;

	private $_userThemesDir;

	private static $_instance;

	/**
	 * Array of available theme names
	 * @var array
	 */
	private $_themeList;

	private function __construct()
	{}

	private function __clone()
	{}

	public static function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
			self::$_instance->_themesDir = ivPath::canonizeAbsolute(BASE_DIR . 'themes' . DS);
			self::$_instance->_userThemesDir = ivPath::canonizeAbsolute(USER_DIR . 'themes' . DS);
		}

		return self::$_instance;
	}

	/**
	 * Factory method
	 *
	 * @param  string  $name
	 * @param  string  $altName
	 * @return ivTheme Will return false if theme not found
	 */
	public function find($name)
	{
		if (in_array($name, $this->getAllThemes())) {
			if (in_array($name, $this->getDefaultThemes())) {
				return new ivTheme($name, ivPath::canonizeRelative(substr($this->_themesDir, strlen(ROOT_DIR)) . $name));
			}

			return new ivTheme($name, ivPath::canonizeRelative(substr($this->_userThemesDir, strlen(ROOT_DIR)) . $name));
		}

		return false;
	}

	/**
	 * Returns list of all installed themes
	 *
	 * @return array
	 */
	public function getAllThemes()
	{
		if (!isset($this->_themeList)) {
			$this->_themeList = array();

			if (file_exists($this->_userThemesDir)) {
				if ($handle = opendir($this->_userThemesDir)) {
					while (false !== ($file = readdir($handle))) {
						if ((substr($file, 0, 1) != '.') && is_dir($this->_userThemesDir . $file)) {
							$this->_themeList[] = $file;
						}
					}
					closedir($handle);
				}
			}

			foreach ($this->getDefaultThemes() as $themeName) {
				if (file_exists($this->_themesDir . $themeName) && is_dir($this->_themesDir . $themeName)) {
					$this->_themeList[] = $themeName;
				}
			}

			sort($this->_themeList);
		}

		return $this->_themeList;
	}

	/**
	 * Returns list of default themes
	 *
	 * @return array
	 */
	public function getDefaultThemes()
	{
		return array(
			'abyss',
			'bluedragon',
			'carbonizer',
			'default',
			'firestarter',
			'gardener',
			'illuminati',
			'lucida',
			'lucido',
			'persimmon',
			'white',
			'zanzibar',
		);
	}

	/**
	 * Copy theme
	 *
	 * @param  ivTheme $theme
	 * @param  string  $name
	 * @return boolean
	 */
	public function copy(ivTheme $theme, $name)
	{
		if (in_array($name, $this->getAllThemes())) {
			return false;
		}

		$themeDir = $theme->getAbsolutePath();
		$newThemeDir = ivPath::canonizeAbsolute($this->_userThemesDir . mb_strtolower($name, 'UTF-8'));
		if (file_exists($themeDir) && is_dir($themeDir)) {
			$result = mkdirRecursive($newThemeDir);
			$handle = opendir($themeDir);
			while (false !== ($file = readdir($handle))) {
				if (!in_array($file, array('.', '..'))) {
					$fullPath = $themeDir . $file;
					if (is_file($fullPath)) {
						$result &= @copy($fullPath, $newThemeDir . $file);
					}
				}
			}
			closedir($handle);
		}

		return $result;
	}

	/**
	 * Delete theme
	 *
	 * @param  ivTheme $theme
	 * @return boolean
	 */
	public function delete(ivTheme $theme)
	{
		if (in_array($theme->getName(), $this->getDefaultThemes())) {
			return false;
		}

		$result = true;
		$themeDir = $theme->getAbsolutePath();
		if (file_exists($themeDir) && is_dir($themeDir)) {
			$handle = opendir($themeDir);
			while (false !== ($file = readdir($handle))) {
				if (!in_array($file, array('.', '..'))) {
					$fullPath = $themeDir . $file;
					if (is_file($fullPath)) {
						$result &= @unlink($fullPath);
					}
				}
			}
			closedir($handle);
			$result &= @rmdir($themeDir);
		}

		return $result;
	}

}