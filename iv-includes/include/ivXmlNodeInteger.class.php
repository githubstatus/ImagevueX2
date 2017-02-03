<?php

/**
 * Integer XML node class
 *
 * @author McArrow
 */
class ivXmlNodeInteger extends ivXmlNode
{

	/**
	 * Node's value
	 * @var integer
	 */
	protected $_value = 0;

	/**
	 * Minimum value
	 * @var integer
	 */
	private $_minValue = -2147483647;

	/**
	 * Maximum value
	 * @var integer
	 */
	private $_maxValue = 2147483647;

	/**
	 * Constructor
	 *
	 * @param  string    $name
	 * @param  array     $attrs
	 */
	public function __construct($name, $attrs = array())
	{
		parent::__construct($name, $attrs);
		if ($this->getAttribute('range')) {
			$range = array_explode_trim(',', $this->getAttribute('range'));
			$this->_minValue = isset($range[0]) ? (int) $range[0] : $this->_minValue;
			$this->_maxValue = isset($range[1]) ? (int) $range[1] : $this->_maxValue;
		}
	}

	/**
	 * Set node's value
	 *
	 * @param string|integer $value
	 */
	public function setValue($value)
	{
		if (is_numeric($value) || is_string($value)) {
			$value = (int) $value;
			if ($this->_minValue <= $value && $this->_maxValue >= $value) {
				$this->_value = $value;
			}
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
		$additionalClasses = '';
		if ($this->_maxValue < 2147483647) {
			$additionalClasses .= ' maxvalue_' . $this->_maxValue;
		}
		if ($this->_minValue > -2147483647) {
			$additionalClasses .= ' minvalue_' . $this->_minValue;
		}
		$html = '<input class="text integer' . $additionalClasses . '" name="' . $name . '" type="text" value="' . htmlspecialchars($this->_getSerializedValue()) . '" />';
		return $html;
	}

}