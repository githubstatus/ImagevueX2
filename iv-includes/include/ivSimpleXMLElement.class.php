<?php

class ivSimpleXMLElement extends SimpleXMLElement
{

	public function removeNode(SimpleXMLElement $node)
	{
		$domNode = dom_import_simplexml($node);
		return (boolean) $domNode->parentNode->removeChild($domNode);
	}

	public function addChild($name, $value = null, $namespace = null)
	{
		return parent::addChild($name, is_null($value) ? null : stripNonUtf8Chars($value), $namespace);
	}

	public function setAttribute($name, $value)
	{
		$this[$name] = (is_null($value) ? null : stripNonUtf8Chars($value));
	}

}