<?php

/**
 * Array XML node class
 *
 * @author McArrow
 */
class ivXmlNodeArray extends ivXmlNode
{

	/**
	 * Node's value
	 * @var array
	 */
	protected $_value = array();

	/**
	 * Delimeter
	 * @var string
	 */
	private $_delimeter = ',';

	/**
	 * Array of allowed values
	 *
	 * @var array
	 */
	private $_values = array();

	/**
	 * Constructor
	 *
	 * @param  string    $name
	 * @param  array     $attrs
	 */
	public function __construct($name, $attrs = array())
	{
		parent::__construct($name, $attrs);
		$this->_values = array_explode_trim($this->_delimeter, $this->getAttribute('options'));
	}

	/**
	 * Set node's value
	 *
	 * @param string|array $value
	 */
	public function setValue($value)
	{
		if (is_string($value)) {
			$value = array_explode_trim($this->_delimeter, $value);
		} elseif (is_null($value)) {
			$value = array();
		} elseif (!is_array($value)) {
			return;
		}
		$this->_value = empty($this->_values) ? $value : array_intersect($this->_values, $value);
	}

	/**
	 * Return node's serialized value
	 *
	 * @return string
	 */
	protected function _getSerializedValue()
	{
		return implode($this->_delimeter, $this->getValue());
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
		if (!empty($this->_values)) {
			$html = '<input type="hidden" name="' . $name . '" style="visibility: hidden; width: 1px; height: 1px;"/>';
			foreach ($this->_values as $value) {
				$html .= '<label><input type="checkbox" name="' . $name . '[]" value="' . htmlspecialchars($value) . '" ' . (in_array($value, $this->getValue()) ? 'checked="checked"' : '') . ' />&nbsp;' . htmlspecialchars(ucfirst($value)) . '</label><br />';
			}
		} else {
			$html = parent::toFormElement($name, $id);
		}
		return $html;
	}

}