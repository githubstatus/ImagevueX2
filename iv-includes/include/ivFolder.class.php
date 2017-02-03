<?php

class ivFolder extends ivRecord
{

	const SORT_ORDER_AUTO = 'auto';
	const SORT_ORDER_RANDOM = 'rnd';
	const SORT_ORDER_NAME_ASC = 'na';
	const SORT_ORDER_NAME_DESC = 'nd';
	const SORT_ORDER_TITLE_ASC = 'ta';
	const SORT_ORDER_TITLE_DESC = 'td';
	const SORT_ORDER_DATE_ASC = 'da';
	const SORT_ORDER_DATE_DESC = 'dd';
	const SORT_ORDER_SIZE_ASC = 'sa';
	const SORT_ORDER_SIZE_DESC = 'sd';
	const SORT_ORDER_MANUAL = 'manual';

	const VIEW_AS_GRID = 'grid';
	const VIEW_AS_LIST = 'list';
	const VIEW_AS_TEXT = 'text';

	const DEFAULT_PREVIEW_FILE = '_preview.jpg';

	/**
	 * Property names
	 * @var array
	 */
	protected $_propertyNames = array(
		'name',
		'date',
		'fileCount',
		'totalFileCount',
		'maxThumbWidth',
		'maxThumbHeight',
	);

	/**
	 * Attribute names
	 * @var array
	 */
	protected $_attributeNames = array(
		'title',
		'previewimage',
		'description',
		'pageContent',
		'sort',
		'hidden',
		'page',
		'fileMod',
		'viewAs',
		'order',
		'parameters',
		'password',
		'showInHtml',
		'showInFlash',
		'showOnMobile',
	);

	private $_previewImage;

	private $_maxChildPreviewWidth;
	private $_maxChildPreviewHeight;

	protected function _init()
	{
		$this->_userAttributeNames = ivPool::get('conf')->get('/config/imagevue/settings/attributes/folder');
	}

	public function __get($name)
	{
		if (in_array($name, array('maxThumbWidth', 'maxThumbHeight')) && (!isset($this->_data[$name]) || (0 == $this->_data[$name]))) {
			$this->_mapper->initMaxThumbnailSizes($this);
		}
		if ('thumbnail' == $name) {
			return $this->getThumbnail();
		}
		return parent::__get($name);
	}

	/**
	 * Returns self XML-node
	 *
	 * @return ivXmlNode
	 */
	public function asXml($expanded = true)
	{
		$data = $this->_getCleanData();

		if ($this->isLink() && isset($data['pageContent'])) {
			$data['link'] = t($data['pageContent']);
			unset($data['pageContent']);
		}
		if (!isset($data['title']) && ivPool::get('conf')->get('/config/imagevue/settings/autoTitling')) {
			$data['title'] = $this->getTitle();
		}
		if ($this->isFilemod()) {
			unset($data['date']);
			unset($data['fileCount']);
		}
		$data['date'] = formatDate($this->date);
		$data['path'] = $this->getPrimary();
		unset($data['sort']);
		unset($data['viewAs']);
		unset($data['order']);
		if ($this->hasPassword()) {
			$data['password'] = 'true';
		}

		$data['title'] = t($data['title']);
		$data['description'] = t($data['description']);

		foreach (ivPool::get('conf')->get('/config/imagevue/settings/attributes/folder') as $name) {
			$data[$name] = t($data[$name]);
		}

		if (isset($data['pageContent'])) {
			$data['pageContent'] = t($data['pageContent']);
		}

		return ivXmlNode::create('folder', array_filter($data, create_function('$a', 'return (strlen(trim($a)) > 0);')));
	}

	/**
	 * Return true if folder is hidden
	 *
	 * @return boolean
	 */
	public function isHidden()
	{
		return ('true' === $this->hidden);
	}

	/**
	 * Return true if folder used as text page
	 *
	 * @return boolean
	 */
	public function isPage()
	{
		return ('html' === $this->page);
	}

	/**
	 * Return true if folder used as link
	 *
	 * @return boolean
	 */
	public function isLink()
	{
		return ('link' === $this->page);
	}

	/**
	 * Return true if folder used as filemod
	 *
	 * @return boolean
	 */
	public function isFilemod()
	{
		return ('filemod' === $this->page);
	}

	/**
	 * Return true if folder doesn't have any bullshit on it
	 *
	 * @return boolean
	 */
	public function isFolder()
	{
		return (!$this->isPage() && !$this->isLink() && !$this->isFilemod() && !$this->isHidden());
	}

	// CHANGED
	public static function getSortTypes()
	{
		$defaultSort = mb_strtoupper(ivPool::get('conf')->get('/config/imagevue/settings/defaultSortFiles'), 'UTF-8');

		return array(
			ivFolder::SORT_ORDER_AUTO => array('name' => "Auto ($defaultSort)"),
			ivFolder::SORT_ORDER_TITLE_ASC => array('name' => 'Title asc'),
			ivFolder::SORT_ORDER_TITLE_DESC => array('name' => 'Title desc'),
			ivFolder::SORT_ORDER_NAME_ASC => array('name' => 'Name asc'),
			ivFolder::SORT_ORDER_NAME_DESC => array('name' => 'Name desc'),
			ivFolder::SORT_ORDER_DATE_ASC => array('name' => 'Date asc'),
			ivFolder::SORT_ORDER_DATE_DESC => array('name' => 'Date desc'),
			ivFolder::SORT_ORDER_SIZE_ASC => array('name' => 'Size asc'),
			ivFolder::SORT_ORDER_SIZE_DESC => array('name' => 'Size desc'),
			ivFolder::SORT_ORDER_RANDOM => array('name' => 'Random'),
			ivFolder::SORT_ORDER_MANUAL => array('name' => 'Manual'),
		);
	}

	public function setPassword($password)
	{
		$this->password = sha1($password);
	}

	public function removePassword()
	{
		$this->password = null;
	}

	public function hasPassword()
	{
		$firstPasswordParent = $this->findFirstPasswordParent();
		return !!$firstPasswordParent;
	}

	public function checkPassword($password = null)
	{
		$firstPasswordParent = $this->findFirstPasswordParent();
		if ($firstPasswordParent) {
			return (sha1($password) == $firstPasswordParent->password);
		} else {
			return true;
		}
	}

	public function findFirstPasswordParent()
	{
		if ($this->password) {
			return $this;
		} else if ($this->Parent && $this->Parent->Parent) {
			return $this->Parent->findFirstPasswordParent();
		} else {
			return null;
		}
	}

	// REFACTORING How to avoid this legally?
	public function addFile(ivFile $file)
	{
		$this->_data['fileCount'] = null;
		$this->_data['totalFileCount'] = null;
		$this->_data['maxThumbWidth'] = null;
		$this->_data['maxThumbHeight'] = null;

		$this->_mapper->initMaxThumbnailSizes($this);

		$this->setState(ivRecord::STATE_DIRTY);
	}

	public function getThumbnailMTime()
	{
		return $this->_mapper->getThumbnailMTime($this);
	}

	public function getFolderdataXml()
	{
		return $this->_mapper->getFolderdataXml($this);
	}

	public function getPreviewImage()
	{
		if (!isset($this->_previewImage)) {
			$image = ivMapperFactory::getMapper('file')->find($this->getPrimary() . $this->previewimage);
			if ($image && is_a($image, 'ivFileImage')) {
				$this->_previewImage = $image;
			}
		}

		return $this->_previewImage;
	}

	public function getChildMaxPreviewWidth()
	{
		if (!isset($this->_maxChildPreviewWidth)) {
			$this->_maxChildPreviewWidth = 0;

			foreach ($this->Folders as $subFolder) {
				if (($previewImage = $subFolder->getPreviewImage()) && $previewImage->thumbnail) {
					$data = iv_getimagesize(ROOT_DIR . $previewImage->thumbnail);
					if (isset($data[2])) {
						$this->_maxChildPreviewWidth = max($this->_maxChildPreviewWidth, $data[0]);
					}
				}
			}
		}

		return $this->_maxChildPreviewWidth;
	}

	public function getChildMaxPreviewHeight()
	{
		if (!isset($this->_maxChildPreviewHeight)) {
			$this->_maxChildPreviewHeight = 0;

			foreach ($this->Folders as $subFolder) {
				if (($previewImage = $subFolder->getPreviewImage()) && $previewImage->thumbnail) {
					$data = iv_getimagesize(ROOT_DIR . $previewImage->thumbnail);
					if (isset($data[2])) {
						$this->_maxChildPreviewHeight = max($this->_maxChildPreviewHeight, $data[1]);
					}
				}
			}
		}

		return $this->_maxChildPreviewHeight;
	}

	public function getThumbnail($type = null)
	{
		if ($this->hasPassword()) {
			$thumbnailName = 'folder_locked.png';
		} elseif ($this->isHidden()) {
			$thumbnailName = 'folder_hidden.png';
		} elseif ($this->isLink()) {
			$thumbnailName = 'folder_link.png';
		} elseif ($this->isPage()) {
			$thumbnailName = 'folder_textpage.png';
		} elseif ($this->isFilemod()) {
			$thumbnailName = 'folder_filemod.png';
		} else {
			$thumbnailName = 'folder.png';
		}

		switch ($type) {
			case 'small':
				return str_replace('//', '/', str_replace('\\', '/', substr(BASE_DIR . 'images/foldersmall/' . $thumbnailName, strlen(ROOT_DIR))));
				break;
			case 'overlay':
				return str_replace('//', '/', str_replace('\\', '/', substr(BASE_DIR . 'images/foldersmall/' . $thumbnailName, strlen(ROOT_DIR))));
				break;
			default:
				return str_replace('//', '/', str_replace('\\', '/', substr(BASE_DIR . 'images/folder/' . $thumbnailName, strlen(ROOT_DIR))));
				break;
		}
	}

	public function getTitle()
	{
		$title = $this->title;
		if (!$title) {
			$title = $this->name;
		}
		return $title;
	}

	public function getDescription($mobile=false)
	{
		if (!$mobile) return $this->description;

		return $this->_fixLinksForMobile($this->description);
	}

	public function getPageContent($mobile=false)
	{
		if (!$mobile) return $this->pageContent;
		return $this->_fixLinksForMobile($this->pageContent);
	}

	private function _fixLinksForMobile($text) {
		$text = preg_replace('/href\s*=\s*"\/?#\/?/', 'href="?/', $text);
		$text = preg_replace("/href\s*=\s*'\/?#\/?/", "href=\'?/", $text);
		$text = preg_replace("/\<a\s*href\s*=/", '<a rel="external" href=', $text);
		return $text;
	}
}