<?php

class ivFile extends ivRecord
{

	/**
	 * Property names
	 * @var array
	 */
	protected $_propertyNames = array(
		'name',
		'date',
		'size',
		'thumbnail',
	);

	/**
	 * Attribute names
	 * @var array
	 */
	protected $_attributeNames = array(
		'title',
		'description',
		'order',
	);

	/**
	 * Converts record to XML node
	 *
	 * @param  boolean   $expanded
	 * @return ivXmlNode
	 */
	public function asXml($expanded = true)
	{
		$data = $this->_getCleanData();

		if (!isset($data['title']) && ivPool::get('conf')->get('/config/imagevue/settings/autoTitling')) {
			$data['title'] = $this->getTitle();
		}

		$data['title'] = t($data['title']);
		$data['description'] = t($data['description']);

		foreach (ivPool::get('conf')->get('/config/imagevue/settings/attributes/image') as $name) {
			$data[$name] = t($data[$name]);
		}

		$data['date'] = formatDate($this->date);
		$data['path'] = $this->getPrimary();
		unset($data['order']);
		return ivXmlNode::create('file', array_filter($data, create_function('$a', 'return (strlen(trim($a)) > 0);')));
	}

	/**
	 * Generates thumbnail
	 *
	 */
	public function generateThumbnail($boxWidth = null, $boxHeight = null, $resizeType = null)
	{
		if (empty($boxWidth)) {
			$boxWidth = ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/boxwidth');
		}
		if (empty($boxHeight)) {
			$boxHeight = ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/boxheight');
		}
		if (empty($resizeType)) {
			$resizeType = ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/resizetype');
		}

		$thumbnailPath = $this->_mapper->generateThumbnail($this, $boxWidth, $boxHeight, $resizeType);
		$this->_data['thumbnail'] = $thumbnailPath;
	}

	/**
	 * Return siblings of this file
	 *
	 * @return array
	 */
	public function getSiblings()
	{
		$folderFiles = $this->Parent->Files;
		foreach ($folderFiles as $key => $item) {
			if ($this->name == $item->name) {
				$prev = $key - 1;
				$current = $key + 1;
				$next = $key + 1;
			}
		}
		if ($next >= count($folderFiles)) {
			$next = 0;
		}
		if ($prev < 0) {
			$prev = count($folderFiles) - 1;
		}

		$result = new stdClass();
		$result->next = $folderFiles[$next];
		$result->previous = $folderFiles[$prev];
		$result->current = $current;
		$result->count = count($folderFiles);

		return $result;
	}

	/**
	 * Checks if current file can be shown on front-end
	 *
	 * @return boolean
	 */
	public function isVisibleOnFrontEnd()
	{
		return ivFilepath::matchSuffix($this->name, ivPool::get('conf')->get('/config/imagevue/settings/includefilesext'));
	}

	/**
	 * Return file metadata
	 *
	 * @return array
	 */
	public function getMetaData()
	{
		return array();
	}

	public function getThumbnailMTime()
	{
		return $this->_mapper->getThumbnailMTime($this);
	}

	public function deleteThumbnail()
	{
		return $this->_mapper->deleteThumbnail($this);
	}

}