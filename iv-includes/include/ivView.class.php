<?php

/**
 * View class
 *
 * @author McArrow
 */
class ivView
{

	const EXTENSION = '.phtml';

	/**
	 * Templates paths stack
	 * @var array
	 */
	private $_templatePaths = array();

	private $_data = array();

	/**
	 * Placeholder object
	 * @var ivPlaceholder
	 */
	public $placeholder = null;

	/**
	 * Constructor
	 *
	 * @param string $path
	 */
	public function __construct()
	{
		$this->placeholder = ivPool::get('placeholder');
	}

	public function addTemplatesPath($path)
	{
		array_unshift($this->_templatePaths, $path);
	}

	public function getTemplatesPaths()
	{
		return $this->_templatePaths;
	}

	/**
	 * Assigns a variable
	 *
	 * @param string $name  Variable's name
	 * @param mixed  $value Variable's value
	 */
	public function assign($name, $value = null)
	{
		if (is_string($name)) {
			$this->_data[$name] = $value;
		}
	}

	public function &__get($name)
	{
		if (isset($this->_data[$name])) {
			return $this->_data[$name];
		}
		$result = null;
		return $result;
	}

	public function __isset($name)
	{
		return isset($this->_data[$name]);
	}

	/**
	 * Returns rendered template
	 *
	 * @param  string $template Template filename
	 * @return string
	 */
	public function fetch($template)
	{
		foreach ($this->_templatePaths as $templatePath) {
			$templateFile = $templatePath . $template . self::EXTENSION;
			if (file_exists($templateFile) && is_file($templateFile)) {
				ob_start();
				include $templateFile;
				$result = ob_get_contents();
				ob_end_clean();
				return $result;
			}
		}
	}

	/**
	 * Renders a partial
	 *
	 * @param string $partial Partial file name
	 * @param array  $args
	 */
	public function partial($partial, $args = array())
	{
		$partialView = new ivView();
		foreach ($this->_templatePaths as $templatePath) {
			$partialView->addTemplatesPath($templatePath . 'partials/');
		}
		foreach ($args as $name => $value) {
			$partialView->assign($name, $value);
		}
		return $partialView->fetch($partial);
	}

	/**
	 * Renders an url
	 *
	 * @param array $attrs
	 */
	public function url($attrs = array())
	{
		return ivUrl::url($attrs);
	}

	/**
	 * Replaces snippets in html page text
	 *
	 * @param  string $text
	 * @return string
	 */
	public function replaceSnippets($text)
	{
		foreach ($this->_templatePaths as $templatePath) {
			$contactFormTemplateFile = $templatePath . 'contactform.html';
			if (file_exists($contactFormTemplateFile) && is_file($contactFormTemplateFile)) {
				ob_start();
				include($contactFormTemplateFile);

				$text = preg_replace('/\<img\s+src\=\"contactform\".*?\>/i', ob_get_clean(), $text);
			}
		}

		$text = str_replace ('href="#', 'href="?', $text);

		$qPathArray = array_explode_trim('/', $_SERVER['PHP_SELF']);
		array_pop($qPathArray);
		$documentBaseUrl = (count($qPathArray) ? '/' : '') . implode('/', $qPathArray) . '/';
		$galleryPath = getHost() . $documentBaseUrl;

		$text = str_replace ("href=\"$galleryPath#", "href=\"$galleryPath?", $text);
		$text = str_replace ("href=\"{$galleryPath}index.php#", "href=\"{$galleryPath}index.php?", $text);
		$text = str_replace ("href=\"{$galleryPath}imagevue.php#", "href=\"{$galleryPath}imagevue.php?", $text);
		return $text;
	}

}