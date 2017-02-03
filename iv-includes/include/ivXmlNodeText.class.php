<?php

/**
 * Text XML node class
 *
 * @author McArrow
 */
class ivXmlNodeText extends ivXmlNode
{

	/**
	 * Set node's value
	 *
	 * @param string $value
	 */
	public function setValue($value)
	{
		parent::setValue(trim($value));
	}

	/**
	 * Returns HTML form element for current node
	 *
	 * @param  string $name
	 * @param  string $id
	 * @return string
	 */
	public function toFormElement($name, $id)
	{
		$html = '<div class="textarea"><textarea name="' . $name . '" rows="5" cols="20">' . htmlspecialchars($this->_getSerializedValue()) . '</textarea></div>';
		return $html;
	}

}