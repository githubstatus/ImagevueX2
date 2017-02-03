<?php

/**
 * Enum XML node class
 *
 * @author McArrow
 */
class ivXmlNodeEnum extends ivXmlNode
{

	/**
	 * Array of values
	 *
	 * @var array
	 */
	protected $_values = array();

	/**
	 * Constructor
	 *
	 * @param  string    $name
	 * @param  array     $attrs
	 */
	public function __construct($name, $attrs = array())
	{
		parent::__construct($name, $attrs);
		$this->_values = array_explode_trim(',', $this->getAttribute('options'));
	}

	/**
	 * Return array of valid values
	 *
	 * @return array
	 */
	public function getValues()
	{
		return $this->_values;
	}

	/**
	 * Set node's value
	 *
	 * @param string $value
	 */
	public function setValue($value)
	{
		if (in_array($value, $this->getValues())) {
			$this->_value = $value;
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
		$html = '<select name="' . $name . '">';
		foreach ($this->getValues() as $value) {
			$html .= '<option value="' . htmlspecialchars($value) . '" ' . ($this->getValue() == $value ? 'selected="selected"' : '') . '>' . htmlspecialchars(ucfirst($value)) . '</option>';
		}
		$html .= '</select>';
		return $html;
	}

}