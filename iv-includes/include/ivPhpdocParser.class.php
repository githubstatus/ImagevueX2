<?php

/**
* Provides basic parser functions to extract phpdoc blocks
*
* @author McArrow
*/
class ivPhpdocParser
{

	public function getMethodsData($fileContents)
	{
		$actions = array();

		// cut one class definition
		$matches = array();
		$classMethods = preg_replace('/.*?class.*?\{/is', '', $fileContents, 1);

		// find method's definition with phpdoc block
		$matches = array();
		preg_match_all('/\/\*\*(.*?)\*\/.*?function\s+(\w+).*?\{/is', $classMethods, $matches);

		// parse phpdoc block
		foreach($matches[0] as $key => $value) {
			$actions[$matches[2][$key]] = trim(preg_replace('/(\n)+[\s]*\*[\s]*/i', "\n", preg_replace('/(\n[\s]*\*[\s]*){2,}/i', "\n\n", preg_replace('/^[\s]*\*[\s]*\@[a-z]+.*$/im', '', $matches[1][$key]))));
		}

		return $actions;
	}

}