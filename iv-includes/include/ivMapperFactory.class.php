<?php

class ivMapperFactory
{

	private static $_mappers = array();

	public static final function getMapper($name)
	{
		if (!isset(self::$_mappers[$name])) {
			$className = 'ivMapperXml' . ucfirst($name);
			self::$_mappers[$name] = new $className;
		}
		return self::$_mappers[$name];
	}

}