<?php

/**
 * Color XML node class
 *
 * @author McArrow
 */
class ivXmlNodeColor extends ivXmlNode
{

	/**
	 * Set node's value
	 *
	 * @param string $value
	 */
	public function setValue($value)
	{
		if (preg_match('/^[0-9A-Fa-f]{6}$/', $value)) {
			$this->_value = strtr($value, 'abcdef', 'ABCDEF');
		} elseif (in_array(strtolower($value), array('foreground_color', 'background_color', 'custom_color'))) {
			$this->_value = strtolower($value);
		}
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
		$html = '<input class="text color" name="' . $name . '" type="text" value="' . htmlspecialchars($this->getValue()) . '" />';
		return $html;
	}

}