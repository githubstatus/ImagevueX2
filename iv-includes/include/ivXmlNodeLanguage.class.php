<?php

/**
 * Languages XML node class
 *
 * @author McArrow
 */
class ivXmlNodeLanguage extends ivXmlNodeEnum
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
		$this->_values = ivLanguage::getAllLanguageNames();
	}

}