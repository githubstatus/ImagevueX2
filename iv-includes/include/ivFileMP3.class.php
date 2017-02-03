<?php

/**
 * MP3 music file
 *
 * @author McArrow
 */
class ivFileMP3 extends ivFile
{

	/**
	 * Attribute names
	 * @var array
	 */
	protected $_attributeNames = array(
		'title',
		'artist',
		'description',
		'order',
	);

	/**
	 * ID3 data cache
	 * @var array
	 */
	private $_id3Data;

	/**
	 * Parses and returns ID3 data
	 *
	 * @return array
	 */
	public function getId3Data()
	{
		return $this->getRawId3Data();
	}

	/**
	 * Returns raw ID3 data
	 *
	 * @return array
	 */
	public function getRawId3Data()
	{
		if (!isset($this->_id3Data)) {
			$this->_id3Data = $this->_mapper->getRawId3Data($this);
		}
		return $this->_id3Data;
	}

	/**
	 * Returns file title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		$title = $this->title;

		if (!$title) {
			$id3Data = $this->getId3Data();
			$title = isset($id3Data['Title']) ? $id3Data['Title'] : '';
		}

		if (!$title) {
			$title = ivFilepath::filename($this->name);
		}

		return $title;
	}

	/**
	 * Converts record to XML node
	 *
	 * @param  boolean   $expanded
	 * @return ivXmlNode
	 */
	public function asXml($expanded = true)
	{
		$node = parent::asXml();
		$id3Data = $this->getRawId3Data();
		if ($expanded) {
			if (is_array($id3Data)) {
				$id3Node = ivXmlNode::create('id3');
				foreach ($id3Data as $key => $value) {
					$tag = ivXmlNode::create(str_replace(array(' ', '(', ')'), array('_', '', ''), $key));
					$tag->setValue(htmlspecialchars($value));
					$id3Node->addChild($tag);
					unset($tag);
				}
				$node->addChild($id3Node);
			}
		}
		if (!$node->getAttribute('artist') && isset($id3Data['Artist'])) {
			$node->setAttribute('artist', $id3Data['Artist']);
		}
		if (!$node->getAttribute('title') && isset($id3Data['Title'])) {
			$node->setAttribute('title', $id3Data['Title']);
		}
		return $node;
	}

	/**
	 * Return file metadata
	 *
	 * @return array
	 */
	public function getMetaData()
	{
		return $this->getId3Data();
	}

}