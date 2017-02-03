<?php

/**
 * Themes XML node class
 *
 * @author McArrow
 */
class ivXmlNodeTheme extends ivXmlNodeEnum
{

	/**
	 * Constructor
	 *
	 * @param  string    $name
	 * @param  array     $attrs
	 */
	public function __construct($name, $attrs = array())
	{
		parent::__construct($name, $attrs);
		$this->_values = ivThemeMapper::getInstance()->getAllThemes();
	}

}