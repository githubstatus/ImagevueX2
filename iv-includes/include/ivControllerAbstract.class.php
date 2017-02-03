<?php

/**
 * Abstract controller class
 *
 * @abstract
 * @author McArrow
 */
class ivControllerAbstract
{

	/**
	 * Make redirect
	 *
	 * @param string $url Target URL
	 */
	protected function _redirect($url)
	{
		header('Location: ' . $url);
		exit(0);
	}

	/**
	 * Returns a parameter from $_REQUEST array
	 *
	 * @param  string $name
	 * @param  mixed  $defaultValue
	 * @param  string $constraint
	 * @return mixed
	 */
	protected function _getParam($name, $defaultValue = null, $constraint = null)
	{
		if (!is_null($constraint)) {
			$vars = $this->_getParams(array($name => $constraint));
		} else {
			$vars = $this->_getParams($name);
		}
		$vars[$name] = (isset($vars[$name]) ? $vars[$name] : null);
		if (is_null($vars[$name])) {
			$vars[$name] = $defaultValue;
		}
		return $vars[$name];
	}

	/**
	 * Returns set of parameters from $_REQUEST array and does some checkups
	 *
	 * Supported types:
	 * num, path, bool, arr
	 *
	 * @param  string $varlist
	 * @return mixed
	 */
	protected function _getParams($varlist)
	{
		if (!is_array($varlist)) {
			$vlst = array($varlist);
		} else {
			$vlst = $varlist;
		}
		$out = array();
		foreach ($vlst as $v => $t) {
			if ($t && !is_numeric($v)) {
				$z = isset($_REQUEST[$v]) ? $_REQUEST[$v] : null;
				switch ($t) {
					case 'alnum':
						$z = empty($z) ? null : preg_replace('/[^\w\d\.]/i', '', $z);
						break;
					case 'num':
						$z = (int) $z;
						break;
					case 'path':
						$z = empty($z) ? null : ivPath::canonizeRelative(trim($z));
						break;
					case 'bool':
						$z = (boolean) $z;
						break;
					case 'arr':
						$z = array_explode_trim('|', $z);
						break;
				}
				$out[$v] = $z;
			} else {
				if (isset($_REQUEST[$t])) {
					$out[$t] = $_REQUEST[$t];
				}
			}
		}
		return $out;
	}

}