<?php

class ivMapperXmlFolder extends ivMapperXmlAbstract
{

	private $_root;

	private $_foldersXml;

	private $_totalFoldersCount = 0;

	private $_totalFilesCount = 0;

	public function __construct()
	{
		parent::__construct();

		$foldersXmlPath = ROOT_DIR . $this->_getContentDirPath() . 'folders.xml';

		$deletedFolders = array();
		$changedFolders = array();

		$dropCache = true;
		if (file_exists($foldersXmlPath)) {
			try {
				ivErrors::disable();
				$xmlIterator = new SimpleXMLIterator($foldersXmlPath, null, true);
				ivErrors::enable();
				if (count($xmlIterator)) {
					$dropCache = false;

					$recursiveIterator = new RecursiveIteratorIterator($xmlIterator, RecursiveIteratorIterator::SELF_FIRST);
					foreach ($recursiveIterator as $n) {
						$relativePath = (string) $n['path'];

						$path = ROOT_DIR . $this->_getContentDirPath() . $relativePath;

						if (!file_exists($path)) {
							$deletedFolders[] = $path;
						} else if (iv_filemtime($path) != (integer) $n['mTime']) {
							$changedFolders[] = $path;
						}
					}
				}
			} catch (Exception $e) {}

			try {
				$xml = new ivSimpleXMLElement($foldersXmlPath, null, true);
				if (ivPath::canonizeRelative($xml->folder['path']) != '') {
					$dropCache = true;
				}
			} catch (Exception $e) {
				$dropCache = true;
			}
		}

		if (!$dropCache) {
			if (empty($deletedFolders) && empty($changedFolders)) {
				$this->_foldersXml = $xml;
			}
			$data = $this->_buildFolderTreeFromCache($foldersXmlPath);

			foreach ($deletedFolders as $deletedFolderPath) {
				unset($data[$deletedFolderPath]);
			}

			if (!empty($changedFolders)) {
				$knownPathes = array_keys($data);
				foreach ($knownPathes as $path) {
					if (in_array($path, $changedFolders)) {
						foreach (new ivFilterIteratorDot(new DirectoryIterator($path)) as $subFolder) {
							if ($subFolder->isDir() && !array_key_exists($path . $subFolder->getFilename() . DS, $data)) {
								$data = array_merge($data, $this->_buildFolderTree($path . $subFolder->getFilename() . DS));
							}
						}
					}
				}

				ksort($data);
			}
		} else {
			if (file_exists($foldersXmlPath)) {
				ivMessenger::add(ivMessenger::NOTICE, 'Created new folders.xml');
			}
			$data = $this->_buildFolderTree(ROOT_DIR . $this->_getContentDirPath());
		}

		foreach ($data as $path => $rows) {
			if (in_array($path, $changedFolders)) {
				$record = $this->_createFolder($path);
			} else {
				$record = $this->_createFolder($path, $rows['properties'], $rows['attributes']);
			}
			$this->_collection[$record->getPrimary()]['record'] = $record;
			$record->Folders = new ivRecordCollection();
			if ('' != $record->getPrimary()) {
				$parent = $this->find(ivPath::canonizeRelative(dirname($record->getPrimary() . DS)), true);
				if ($parent) {
					$parent->Folders->append($record);
				}
			}

			$this->_totalFoldersCount++;
			$this->_totalFilesCount += $record->fileCount;
		}

		foreach ($this->_collection as $v) {
			$childFolders = array();
			foreach ($v['record']->Folders as $f) {
				$childFolders[] = $f;
			}
			usort($childFolders, array($this, '_manualSort'));

			foreach ($childFolders as $key => $f) {
				if ($key + 1 != $f->order) {
					$f->order = $key + 1;
					$f->save();
				}
			}

			$collection = new ivRecordCollection();
			foreach ($childFolders as $f) {
				$collection->append($f);
			}
			$v['record']->Folders = $collection;
		}

		$this->_root = $this->find('');
	}

	private function _buildFolderTreeFromCache($foldersXmlPath)
	{
		$data = array();

		$xmlIterator = new SimpleXMLIterator($foldersXmlPath, null, true);
		$recursiveIterator = new RecursiveIteratorIterator($xmlIterator, RecursiveIteratorIterator::SELF_FIRST);
		foreach ($recursiveIterator as $n) {
			$path = ROOT_DIR . $this->_getContentDirPath() . (string) $n['path'];

			$data[$path] = array(
				'properties' => array(),
				'attributes' => array(),
			);

			foreach($n->attributes() as $name => $value) {
				$data[$path]['properties'][$name] = (string) $value;
				$data[$path]['attributes'][$name] = (string) $value;
			}
		}

		return $data;
	}

	private function _buildFolderTree($contentDirPath)
	{
		$contentDir = new SplFileInfo($contentDirPath);

		if (!$contentDir->isDir()) {
			return array();
		}

		$data = array();
		$data[$contentDirPath] = array(
			'properties' => array(
				'name' => basename(rtrim($contentDirPath, DS)),
				'date' => $contentDir->getCTime(),
				'fileCount' => 0,
				'totalFileCount' => 0,
			),
			'attributes' => null,
		);

		$excludeFilePrefixes = $this->_conf->get('/config/imagevue/settings/excludefilesprefix');
		$includeFileExtensions = $this->_conf->get('/config/imagevue/settings/includefilesext');
		$allowedExtensions = $this->_conf->get('/config/imagevue/settings/allowedext');

		$iterator = new RecursiveDirectoryIterator($contentDirPath);
		$dotFilter = new ivRecursiveFilterIteratorDot($iterator);
		foreach (new RecursiveIteratorIterator($dotFilter, RecursiveIteratorIterator::SELF_FIRST) as $path => $cur) {
			if ($cur->isDir()) {
				if (stripNonUtf8Chars($path) != $path) {
					ivMessenger::add(ivMessenger::ERROR, 'Folder ' . substr($path . DS, strlen(ROOT_DIR)) . ' has non-utf8 char in it\'s name and was skipped');
				} else {
					$data[$path . DS] = array(
						'properties' => array(
							'name' => $cur->getFilename(),
							'date' => $cur->getCTime(),
							'fileCount' => 0,
							'totalFileCount' => 0,
						),
						'attributes' => null,
					);
				}
			} else {
				if (!ivFilepath::matchPrefix($cur->getFilename(), $excludeFilePrefixes)) {
					if (ivFilepath::matchSuffix($cur->getFilename(), $allowedExtensions)) {
						if (ivFolder::DEFAULT_PREVIEW_FILE != strtolower($cur->getFilename())) {
							$data[$cur->getPath() . DS]['properties']['totalFileCount']++;
							if (ivFilepath::matchSuffix($cur->getFilename(), $includeFileExtensions)) {
								$data[$cur->getPath() . DS]['properties']['fileCount']++;
							}
						}
					}
				}
			}
		}

		return $data;
	}

	public function __destruct()
	{
		$foldersXmlPath = ivPath::canonizeAbsolute(ROOT_DIR . $this->_getContentDirPath()) . 'folders.xml';

		$writeFoldersXml = false;
		if (!file_exists($foldersXmlPath) || !isset($this->_foldersXml)) {
			$writeFoldersXml = true;
		}

		foreach ($this->_collection as $path => $v) {
			if (ivRecord::STATE_DIRTY == $v['record']->getState()) {
				if (!$v['record']->fileCount || !$v['record']->totalFileCount) {
					$this->_initFileCounts($v['record'], ROOT_DIR . $v['record']->getPath());
				}
				if (!$this->save($v['record'])) {
					$this->refresh($v['record']);
				}
				$writeFoldersXml = true;
				break;
			}
		}

		if (!$writeFoldersXml) {
			return;
		}

		if ($this->_root) {
			$this->_foldersXml = new ivSimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><folders></folders>');

			$this->_buildFoldersXml($this->_foldersXml, $this->_root);

			$this->_foldersXml->asXML($foldersXmlPath);
			iv_chmod($foldersXmlPath, 0666);
		}
	}

	private function _buildFoldersXml(ivSimpleXMLElement $node, ivFolder $folder)
	{
		$child = $node->addChild('folder');

		$child->setAttribute('mTime', iv_filemtime(ROOT_DIR . $this->_getContentDirPath() . $folder->getPrimary()));
		foreach ($folder->getPropertyNames() as $name) {
			$child->setAttribute($name, $folder->$name);
		}
		$child->setAttribute('path', $folder->getPrimary());
		foreach ($folder->getAttributeNames() as $name) {
			if (('previewimage' != $name) || (null !== $folder->$name)) {
				$child->setAttribute($name, $folder->$name);
			}
		}
		foreach ($folder->getUserAttributeNames() as $name) {
			$child->setAttribute($name, $folder->$name);
		}

		foreach ($folder->Folders as $childFolder) {
			$this->_buildFoldersXml($child, $childFolder);
		}
	}

	private function _createFolder($path, $properties = null, $attributes = null)
	{
		$item = new ivFolder($this);
		$id = str_replace('//', '/', str_replace('\\', '/', substr($path, strlen(ROOT_DIR))));

		$id = str_replace('//', '/', str_replace('\\', '/', substr($id, strlen($this->_getContentDirPath()))));

		$item->setPrimary($id);

		if (is_array($properties)) {
			foreach ($item->getPropertyNames() as $name) {
				if (isset($properties[$name]) && 'thumbnail' != $name) {
					$item->$name = $properties[$name];
				}
			}
		} else {
			$item->name = basename(rtrim($path, DS));
			$item->date = filectime($path);

			$this->_initFileCounts($item, $path);
		}

		if (is_array($attributes)) {
			if (!isset($attributes['showInHtml']) || ('0' !== $attributes['showInHtml'])) {
				$attributes['showInHtml'] = 1;
			}
			if (!isset($attributes['showInFlash']) || ('0' !== $attributes['showInFlash'])) {
				$attributes['showInFlash'] = 1;
			}
			if (!isset($attributes['showOnMobile']) || ('0' !== $attributes['showOnMobile'])) {
				$attributes['showOnMobile'] = 1;
			}

			foreach ($item->getAttributeNames() as $name) {
				if (isset($attributes[$name])) {
					$item->$name = $attributes[$name];
				}
			}
			foreach ($item->getUserAttributeNames() as $name) {
				if (isset($attributes[$name])) {
					$item->$name = $attributes[$name];
				}
			}
		} else {
			$item->showInHtml = 1;
			$item->showInFlash = 1;
			$item->showOnMobile = 1;

			$item->viewAs = $this->_conf->get('/config/imagevue/settings/defaultViewAs');
			$item->hidden = null;
			$item->sort = ivFolder::SORT_ORDER_AUTO;

			$folderNode = ivMapperXmlAbstract::_getFolderdataXml($path);
			foreach ($item->getAttributeNames() as $name) {
				if ($folderNode[$name] || (('' === (string) $folderNode[$name]) && !in_array($name, array('previewimage', 'showInHtml', 'showInFlash', 'showOnMobile')))) {
					$item->$name = (string) $folderNode[$name];
				}
			}
			foreach ($item->getUserAttributeNames() as $name) {
				if ($folderNode[$name] || ('' === (string) $folderNode[$name])) {
					$item->$name = (string) $folderNode[$name];
				}
			}

			if ('_altimage' == $item->name) {
				$item->hidden = 'true';
			}
		}

		$item->setState(ivRecord::STATE_CLEAN);

		// Remove non-existent files from folderdata.xml
		if (!is_array($attributes) && $folderNode->file) {
			$nodesToRemove = array();

			foreach ($folderNode->file as $fileNode) {
				if (!file_exists($path . $fileNode['name'])) {
					$nodesToRemove[] = $fileNode;
				}
			}

			if (!empty($nodesToRemove)) {
				foreach ($nodesToRemove as $nodeToRemove) {
					$folderNode->removeNode($nodeToRemove);
				}

				$item->setState(ivRecord::STATE_DIRTY);
			}
		}

		if (!empty($item->previewimage) && !file_exists($path . $item->previewimage)) {
			$item->previewimage = null;
		}

		if (file_exists($path . ivFolder::DEFAULT_PREVIEW_FILE) && (empty($item->previewimage) || ivFolder::DEFAULT_PREVIEW_FILE == $item->previewimage)) {
			$item->previewimage = ivFolder::DEFAULT_PREVIEW_FILE;
		} else {
			if ($item->fileCount > 0) {
				if (null === $item->previewimage) {
					foreach (getContent($path) as $file) {
						if (!ivFilepath::matchPrefix($file, array(ivMapperXmlAbstract::THUMBNAIL_PREFIX))
							&& in_array(strtolower(ivFilepath::suffix($path . $file)), array('gif', 'jpeg', 'jpg', 'tif', 'tiff', 'png'))) {
							$item->previewimage = $file;
							break;
						}
					}
				}
			} elseif ($item->previewimage) {
				$item->previewimage = null;
			}
		}

		if (!in_array($item->page, array('gallery', 'html', 'link', 'filemod'))) {
			$item->page = 'gallery';
		}

		if (!in_array($item->viewAs, array('grid', 'list', 'text'))) {
			$item->viewAs = $this->_conf->get('/config/imagevue/settings/defaultViewAs');
		}

		return $item;
	}

	public function find($id, $absolute = false)
	{
		$path = str_replace('//', '/', str_replace('\\', '/', substr(ivPath::canonizeAbsolute(ROOT_DIR . $id), strlen(ROOT_DIR))));

		if (!isset($this->_collection[$path])) {
			return false;
		}

		return $this->_collection[$path]['record'];
	}

	/**
	 * Initialize file counts for given folder
	 *
	 */
	private function _initFileCounts(ivFolder $record, $id)
	{
		$excludefilesprefix = $this->_conf->get('/config/imagevue/settings/excludefilesprefix');
		$includefilesext = $this->_conf->get('/config/imagevue/settings/includefilesext');
		$allowedext = $this->_conf->get('/config/imagevue/settings/allowedext');

		$fileCount = 0;
		$totalFileCount = 0;
		foreach (getContent($id) as $item) {
			if (!ivFilepath::matchPrefix($item, $excludefilesprefix)) {
				if (ivFilepath::matchSuffix($item, $allowedext)) {
					if (ivFolder::DEFAULT_PREVIEW_FILE != strtolower($item)) {
						$totalFileCount++;
						if (ivFilepath::matchSuffix($item, $includefilesext)) {
							$fileCount++;
						}
					}
				}
			}
		}

		$record->fileCount = $fileCount;
		$record->totalFileCount = $totalFileCount;
	}

	public function save(ivRecord $folder)
	{
		$folderNode = ivMapperXmlAbstract::_getFolderdataXml(ROOT_DIR . $folder->getPath());

		$attributes = array();
		foreach ($folder->getAttributeNames() as $name) {
			if (('previewimage' != $name) || (null !== $folder->$name)) {
				$folderNode->setAttribute($name, $folder->$name);
			}
		}
		foreach ($folder->getUserAttributeNames() as $name) {
			$folderNode->setAttribute($name, $folder->$name);
		}

		return ivMapperXmlAbstract::_saveFolderdataXml(ROOT_DIR . $folder->getPath());
	}

	public function refresh(ivRecord $folder)
	{
		$folderNode = ivMapperXmlAbstract::_getFolderdataXml(ROOT_DIR . $folder->getPath());

		foreach ($folder->getAttributeNames() as $name) {
			if ($folderNode[$name] || ('' === (string) $folderNode[$name])) {
				$folder->$name = $folderNode[$name];
			}
		}
		foreach ($folder->getUserAttributeNames() as $name) {
			if ($folderNode[$name] || ('' === (string) $folderNode[$name])) {
				$folder->$name = $folderNode[$name];
			}
		}

		$folder->setState(ivRecord::STATE_CLEAN);
	}

	public function delete(ivRecord $record)
	{
		$toRemove = array();
		$iterator = new ivRecursiveFolderIterator($record);
		foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST) as $folder) {
			$toRemove = $folder->getPrimary();
		}

		foreach ($toRemove as $id) {
			unset($this->_collection[$id]);
		}

		return rmdirRecursive(ROOT_DIR . $this->_getContentDirPath() . $record->getPrimary());
	}

	public function getFilesProxy(ivFolder $folder)
	{
		$files = array();
		$foundUnorderedFiles = false;
		$orphanedThumbs = array();
		foreach (new ivFilterIteratorDot(new DirectoryIterator(ROOT_DIR . ivPath::canonizeRelative($this->_conf->get('/config/imagevue/settings/contentfolder')) . $folder->getPrimary())) as $item) {
			if (ivFolder::DEFAULT_PREVIEW_FILE == strtolower($item->getFilename())) {
				//
			} elseif ($file = ivMapperFactory::getMapper('file')->find($folder->getPrimary() . ivFilepath::basename($item->getPathname()))) {
				if (stripNonUtf8Chars($item->getPathname()) != $item->getPathname()) {
					ivMessenger::add(ivMessenger::ERROR, 'File ' . substr($item->getPathname(), strlen(ROOT_DIR)) . ' has non-utf8 char in it\'s name and was skipped');
				} else {
					if (!$file->order) {
						$foundUnorderedFiles = true;
					}
					$files[] = $file;
				}
			} elseif ($item->isFile() && ivFilepath::matchPrefix(ivFilepath::basename($item->getPathname()), array(ivMapperXmlAbstract::THUMBNAIL_PREFIX)) && ivFilepath::matchSuffix(ivFilepath::basename($item->getPathname()), array('jpg'))) {
				$orphanedThumbs[] = ivFilepath::basename($item->getPathname());
			}
		}

		foreach ($files as $file) {
			$orphanedThumbs = array_diff($orphanedThumbs, array(ivMapperXmlAbstract::THUMBNAIL_PREFIX . ivFilepath::filename($file->name) . '.jpg'));
		}

		$orphanedThumbs = array_diff($orphanedThumbs, array(ivMapperXmlAbstract::THUMBNAIL_PREFIX . ivFolder::DEFAULT_PREVIEW_FILE));

		foreach ($orphanedThumbs as $thumb) {
			@unlink(ROOT_DIR . $folder->getPath() . $thumb);
		}

		$sort = $folder->sort;
		if (ivFolder::SORT_ORDER_AUTO == $sort) {
			$sort = $this->_conf->get('/config/imagevue/settings/defaultSortFiles');
		}

		switch ($sort) {
			case ivFolder::SORT_ORDER_MANUAL:
				usort($files, array($this, '_manualSort'));
				break;
			case ivFolder::SORT_ORDER_NAME_ASC:
				usort($files, create_function('$f1, $f2', 'return strcmp(mb_strtolower($f1->name, "UTF-8"), mb_strtolower($f2->name, "UTF-8"));'));
				break;
			case ivFolder::SORT_ORDER_NAME_DESC:
				usort($files, create_function('$f1, $f2', 'return strcmp(mb_strtolower($f2->name, "UTF-8"), mb_strtolower($f1->name, "UTF-8"));'));
				break;
			case ivFolder::SORT_ORDER_TITLE_ASC:
				usort($files, create_function('$f1, $f2', 'return strcmp(mb_strtolower($f1->getTitle(), "UTF-8"), mb_strtolower($f2->getTitle(), "UTF-8"));'));
				break;
			case ivFolder::SORT_ORDER_TITLE_DESC:
				usort($files, create_function('$f1, $f2', 'return strcmp(mb_strtolower($f2->getTitle(), "UTF-8"), mb_strtolower($f1->getTitle(), "UTF-8"));'));
				break;
			case ivFolder::SORT_ORDER_DATE_ASC:
				usort($files, create_function('$f1, $f2', 'return intcmp($f1->date, $f2->date);'));
				break;
			case ivFolder::SORT_ORDER_DATE_DESC:
				usort($files, create_function('$f1, $f2', 'return intcmp($f2->date, $f1->date);'));
				break;
			case ivFolder::SORT_ORDER_SIZE_ASC:
				usort($files, create_function('$f1, $f2', 'return intcmp($f1->size, $f2->size);'));
				break;
			case ivFolder::SORT_ORDER_SIZE_DESC:
				usort($files, create_function('$f1, $f2', 'return intcmp($f2->size, $f1->size);'));
				break;
			case ivFolder::SORT_ORDER_RANDOM:
				usort($files, create_function('$f1, $f2', 'return rand(-1, 1);'));
				break;
		}

		if (ivFolder::SORT_ORDER_RANDOM !== $sort) {
			foreach ($files as $key => $file) {
				if ($key + 1 != $file->order) {
					$file->order = $key + 1;
					$file->save();
				}
			}
		}

		$collection = new ivRecordCollection();
		foreach ($files as $file) {
			$collection->append($file);
		}

		return $collection;
	}

	private function _manualSort(ivRecord $f1, ivRecord $f2)
	{
		if (!$f1->order && !$f2->order) {
			return strcmp($f1->name, $f2->name);
		}
		if (!$f1->order) {
			return 1;
		}
		if (!$f2->order) {
			return -1;
		}
		return intcmp($f1->order, $f2->order);
	}

	public function initMaxThumbnailSizes(ivFolder $record)
	{
		$maxThumbX = 0;
		$maxThumbY = 0;
		$hasFiles = false;

		foreach (new ivFilterIteratorDot(new DirectoryIterator(ROOT_DIR . ivPath::canonizeRelative($this->_conf->get('/config/imagevue/settings/contentfolder')) . $record->getPrimary())) as $item) {
			if (ivFilepath::matchSuffix($item->getFilename(), $this->_conf->get('/config/imagevue/settings/allowedext')) && !ivFilepath::matchPrefix($item->getFilename(), array(ivMapperXmlAbstract::THUMBNAIL_PREFIX))) {
				$hasFiles = true;
				if (file_exists($item->getPath() . DS . ivMapperXmlAbstract::THUMBNAIL_PREFIX . ivFilepath::filename($item->getFilename()) . '.jpg')) {
					$data = iv_getimagesize($item->getPath() . DS . ivMapperXmlAbstract::THUMBNAIL_PREFIX . ivFilepath::filename($item->getFilename()) . '.jpg');
					if (isset($data[2])) {
						$maxThumbX = isset($data[0]) && $data[0] > $maxThumbX ? $data[0] : $maxThumbX;
						$maxThumbY = isset($data[1]) && $data[1] > $maxThumbY ? $data[1] : $maxThumbY;
					}
				}
			}
		}

		$record->maxThumbWidth = ($maxThumbX > 0 ? $maxThumbX : ($hasFiles ? ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/boxwidth') : 0));
		$record->maxThumbHeight = ($maxThumbY > 0 ? $maxThumbY : ($hasFiles ? ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/boxheight') : 0));
	}

	public function sanityFileName(ivFolder $folder, $name)
	{
		$nameWOExt = ivFilepath::filename($name);
		// Letters A–Z, a-z
		// Numbers 0–9
		// Space
		// ! # $ % & ' ( ) - @ ^ _ ` { } ~
		// + , . ; = [ ]
		$sanitiedName = trim(preg_replace('/[^a-zA-Z0-9\s\!\#\$\%\&\'\(\)\-\@\^\_\`\{\}\~\+\,\.\;\=\[\]]/u', '_', $nameWOExt));
		$ext = ivFilepath::suffix($name);
		if (!trim($sanitiedName, '_')) {
			$sanitiedName = '';
			$newName = "00001";
			$i = 2;
		} else {
			$newName = "$sanitiedName";
			$i = 1;
		}
		while (($fc = glob(ROOT_DIR . $folder->getPrimary() . $newName . '.*')) && count($fc)) {
			$newName = sprintf("$sanitiedName%05d", $i++);
		}
		return "$newName.$ext";
	}

	public function getFolderdataXml(ivFolder $folder)
	{
		return ivMapperXmlAbstract::_getFolderdataXml(ROOT_DIR . $folder->getPath());
	}

	public function getTotalFoldersCount()
	{
		return $this->_totalFoldersCount - 1;
	}

	public function getTotalFilesCount()
	{
		return $this->_totalFilesCount;
	}

}