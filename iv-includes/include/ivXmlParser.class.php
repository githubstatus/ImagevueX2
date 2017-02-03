<?php

/**
 * XML parser
 *
 * @author McArrow
 */
class ivXmlParser
{

	/**
	 * Parse XML string
	 *
	 * @param  string $file
	 * @return ivXml
	 */
	public static function parse($file)
	{
		if (!file_exists($file) || !is_file($file)) {
			trigger_error('File ' . substr($file, strlen(ROOT_DIR) - 1) . ' not found', E_USER_ERROR);
		}
		$parser = xml_parser_create('UTF-8');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		xml_set_element_handler($parser, 'ivXmlParser_startElementHandle', 'ivXmlParser_endElementHandle');
		xml_set_character_data_handler($parser, 'ivXmlParser_elementDataHandle');

		$GLOBALS['ivXmlParser_nodeStack'] = new ivStack();

		$fileContents = file_get_contents($file);
		if (!trim($fileContents)) {
			// Empty file
			$result = false;
			return $result;
		}
		if (!xml_parse($parser, $fileContents)) {
			// Processing error
			$message = sprintf('An error "%s" occured while parse file %s at line %d, character %d',
				xml_error_string(xml_get_error_code($parser)),
				substr($file, strlen(ROOT_DIR) - 1),
				xml_get_current_line_number($parser),
				xml_get_current_column_number($parser) + 1
			);
			trigger_error($message, E_USER_ERROR);
		}

		xml_parser_free($parser);
		$result = $GLOBALS['ivXmlParser_head'];
		unset($GLOBALS['ivXmlParser_nodeStack']);
		unset($GLOBALS['ivXmlParser_head']);
		return $result;
	}

}

/**
 * Handle start element
 *
 * @param resource $parser
 * @param string   $name   Node name
 * @param array    $attrs  Node attributes
 */
function ivXmlParser_startElementHandle($parser, $name, $attrs)
{
	$currentNode = ivXmlNode::create($name, $attrs);
	// Need to use container for node because of bug in xmlparser with multibyte encodings
	$container = new stdClass();
	$container->node = $currentNode;
	$container->value = null;
	$last = $GLOBALS['ivXmlParser_nodeStack']->tail();
	if ($last) {
		$last->node->addChild($currentNode);
	} else {
		$GLOBALS['ivXmlParser_head'] = $currentNode;
	}
	$GLOBALS['ivXmlParser_nodeStack']->push($container);
}

/**
 * Handle end element
 *
 * @param resource $parser
 * @param string   $name   Node name
 */
function ivXmlParser_endElementHandle($parser, $name)
{
	$last = $GLOBALS['ivXmlParser_nodeStack']->tail();
	$last->node->setValue($last->value);
	$GLOBALS['ivXmlParser_nodeStack']->pop();
}

/**
 * Handle element's value
 *
 * @param resource $parser
 * @param string   $data   Node value
 */
function ivXmlParser_elementDataHandle($parser, $data)
{
	$last = $GLOBALS['ivXmlParser_nodeStack']->tail();
	$last->value .= (string) $data;
}