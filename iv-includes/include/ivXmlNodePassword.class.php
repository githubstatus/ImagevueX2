<?php

/**
 * Password XML node class
 *
 * @author McArrow
 */
class ivXmlNodePassword extends ivXmlNodeString
{

	/**
	 * Returns HTML form element for current node
	 *
	 * @param  string $name
	 * @param  string $id
	 * @return string
	 */
	public function toFormElement($name, $id)
	{
		$html = '<input name="' . $name . '" type="password" class="password" value="' . htmlspecialchars($this->_getSerializedValue()) . '" />';
		return $html;
	}

}