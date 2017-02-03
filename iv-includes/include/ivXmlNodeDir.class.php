<?php

/**
 * Dir XML node class
 *
 * @author McArrow
 */
class ivXmlNodeDir extends ivXmlNode
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
		$conf = ivPool::get('conf');
		$contentFolder = ivMapperFactory::getMapper('folder')->find('');
		if ($contentFolder) {
			$html = '<select name="' . $name . '">';
			if ($name!='config[/config/imagevue/settings/htmlstartpath]')
			$html .= '<option value="false" ' . ($this->getValue() == 'false' ? 'selected="selected"' : '') . '>false</option>';
			$html .= '<option value="/" ' . ($this->getValue() == '/' ? 'selected="selected"' : '') . '>/</option>';
			$iterator = new ivRecursiveFolderIterator($contentFolder);
			foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST) as $folder) {
				$value = $folder->getPrimary();
				if (!empty($value)) {
					$html .= '<option value="' . htmlspecialchars($value) . '" ' . ($this->getValue() == $value ? 'selected="selected"' : '') . '>' . htmlspecialchars($value) . '</option>';
				}
			}
			$html .= '</select>';
			return $html;
		} else {
			ivMessenger::add(ivMessenger::ERROR, 'Content folder <b>' . $conf->get('/config/imagevue/settings/contentfolder') . '</b> doesn\'t exists');
		}
	}

	/**
	 * Set node's value
	 *
	 * @param string $value
	 */
	public function setValue($value)
	{
		parent::setValue($value);

		if (!empty($this->_value) && !preg_match('/\/$/', $this->_value) && 'false' != $this->_value) {
			$this->_value .= '/';
		}
	}

	/**
	 * Return node's value
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		$value = $this->_value;

		if (!empty($value) && !preg_match('/\/$/', $value) && 'false' != $value) {
			$value .= '/';
		}

		return $value;
	}

}