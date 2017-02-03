<?php

class IndexController extends ivController
{

	/**
	 * Path
	 * @var string
	 */
	var $path;

	/**
	 * Pre-dispatching
	 *
	 */
	public function _preDispatch()
	{
		$path = ivPath::canonizeRelative($this->_getParam('path', '', 'path'));
		if (!ivAcl::isAllowedPath($path) && !ivAcl::isAllowedPath(ivPath::canonizeRelative($path))) {
			if (false == ivAcl::getAllowedPath()) {
				$this->_forward('login', 'cred');
				return;
			} else {
				$path = ivAcl::getAllowedPath();
			}
		}

		$fullPath = ROOT_DIR . $this->_getContentDirPath() . $path;
		if (is_dir($fullPath)) {
			$path = ivPath::canonizeRelative($path);
		} elseif (is_file(rtrim($fullPath, DS))) {
			$this->_redirect('index.php?c=file&path=' . smart_urlencode($path));
		} else if (ivFilepath::directory($path)) {
			$this->_redirect('index.php?path=' . smart_urlencode(ivFilepath::directory($path)));
		} elseif (is_dir(ROOT_DIR . ivAcl::getAllowedPath())) {
			$this->_redirect('index.php?path=' . smart_urlencode(ivAcl::getAllowedPath()));
		} else {
			$this->_redirect('index.php?c=config');
		}
		$this->path = $path;
	}

	/**
	 * Default action
	 *
	 */
	public function indexAction()
	{
		$folder = ivMapperFactory::getMapper('folder')->find($this->path);
		if (!$folder) {
			$this->_redirect('index.php');
		}

		// Save folder data
		$newdata = $this->_getParam('newdata');

		if (is_array($newdata)) {
			ivMessenger::add(ivMessenger::NOTICE, 'Folder data saved');
			$this->_redirect($_SERVER['REQUEST_URI']);
		}

		$contentFolder = ivMapperFactory::getMapper('folder')->find(ivAcl::getAllowedPath());
		$iterator = new ivRecursiveFolderIterator($contentFolder);
		$this->view->assign('folderTreeIterator', new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST));

		$this->view->assign('path', $this->path);
		$this->placeholder->set('path', $this->path);

		$this->view->assign('folder', $folder);
		$this->view->assign('sorts', ivFolder::getSortTypes());
		$this->view->assign('contentPath', $this->_getContentDirPath());
		$this->view->assign('uploaderType', $this->conf->get('/config/imagevue/settings/uploader/type'));

		$viewsNode = $this->conf->findByXPath('/config/imagevue/settings/defaultViewAs');
		$this->view->assign('views', $viewsNode->getValues());

		$_SESSION['imagevue']['lastManageLink'] = '?path=' . smart_urlencode($this->path);
	}

	/**
	 * Create new folder
	 *
	 */
	public function createAction()
	{
		ivMessenger::add(ivMessenger::NOTICE, 'Folder created');
		
		$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
	}

	/**
	 * Rename folder
	 *
	 */
	public function renameAction()
	{
		ivMessenger::add(ivMessenger::NOTICE, 'Folder renamed');
		
		$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');

	}

	/**
	 * Delete folder
	 *
	 */
	public function deleteAction()
	{
		ivMessenger::add(ivMessenger::NOTICE, 'Folder deleted');
		
		$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
	}

	public function moveFileByIdAction()
	{
		$folderName = decodeFilenameBase64($this->_getParam('folderId'));
		if ('..' == $folderName) {
			$_REQUEST['target'] = dirname($this->path) . DS;
		} else {
			$_REQUEST['target'] = $this->path . $folderName . DS;
		}
		if (!isset($_REQUEST['selected']) || !is_array($_REQUEST['selected'])) {
			$_REQUEST['selected'] = array(decodeFilenameBase64($this->_getParam('fileId')));
		}
		$this->moveFilesAction(false);
		exit(0);
	}

	/**
	 * Move files
	 *
	 */
	public function moveFilesAction($redirectAfter = true)
	{
		
		$_SESSION['imagevue']['move'] = false;
		$targetPath = ivPath::canonizeRelative($this->_getParam('target', null, 'path'));
		
		$targetFolder = ivMapperFactory::getMapper('folder')->find($targetPath);
		if ($targetFolder) {
			ivMessenger::add('Files moved to <a href="index.php?path=' . smart_urlencode($targetFolder->getPrimary()) . '">' . htmlspecialchars($targetFolder->getTitle()) . '</a>' );
		}
			
		$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
	}

	/**
	 * Copy files
	 *
	 */
	public function copyFilesAction()
	{
		$_SESSION['imagevue']['move'] = false;
		$targetPath = ivPath::canonizeRelative($this->_getParam('target', null, 'path'));
		
		$targetFolder = ivMapperFactory::getMapper('folder')->find($targetPath);
		if ($targetFolder) {
			ivMessenger::add(ivMessenger::NOTICE, $copied . ' files copied to <a href="index.php?path=' . smart_urlencode($targetFolder->getPrimary()) . '">' . htmlspecialchars($targetFolder->getTitle()) . '</a>' . ($skipped?', ' . $skipped . ' files skipped':''));
		}
			
		$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
	}

	/**
	 * Delete files
	 *
	 */
	public function deleteFilesAction()
	{
		ivMessenger::add(ivMessenger::NOTICE, 'Files deleted');
		
		$this->_redirect('index.php?path=' . smart_urlencode($this->path));
	}

	/**
	 * Sets given file as preview image for given folder
	 *
	 */
	public function setPreviewAction()
	{
		$folder = ivMapperFactory::getMapper('folder')->find($this->path);
		$file = ivMapperFactory::getMapper('file')->find($this->path . decodeFilenameBase64($this->_getParam('id')));
		if ($folder && $file) {
			

			if ($this->_isXmlHttpRequest()) {
				$this->_setNoRender();
				echo 'File ' . $file->name . ' is set as preview';
			} else {
				ivMessenger::add(ivMessenger::NOTICE, 'File ' . $file->name . ' is set as preview');
				$this->_redirect('index.php?path=' . smart_urlencode($folder->getPrimary()));
			}
		} else if (!$this->_getParam('id') && !$this->_isXmlHttpRequest()) {

			ivMessenger::add(ivMessenger::NOTICE, 'Folder preview dropped');
			$this->_redirect('index.php?path=' . smart_urlencode($folder->getPrimary()));
		} else {
			if ($this->_isXmlHttpRequest()) {
				$this->_setNoRender();
				echo 'File not found';
			} else {
				ivMessenger::add(ivMessenger::NOTICE, 'File not found');
				$this->_redirect('index.php');
			}
		}
	}

	/**
	 * Sort files
	 *
	 */
	public function sortFilesAction()
	{
		$folder = ivMapperFactory::getMapper('folder')->find($this->path);
		if ($folder) {
			foreach ($_POST['sort'] as $order => $id) {
				$file = ivMapperFactory::getMapper('file')->find($this->path . decodeFilenameBase64($id, 5));
				if ($file) {
					$file->order = $order + 1;
				
				}
			}
			if (ivFolder::SORT_ORDER_MANUAL != $folder->sort) {
				$folder->sort = ivFolder::SORT_ORDER_MANUAL;
			
			}
		}

		if ($this->_isXmlHttpRequest()) {
			$this->_setNoRender();
			echo 'OK';
		} else {
			$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
		}
	}

	/**
	 * Sort folders
	 *
	 */
	public function sortFoldersAction()
	{
		$folder = ivMapperFactory::getMapper('folder')->find($this->path);
		if ($folder) {
			foreach ($_POST['sort'] as $order => $id) {
				$folder = ivMapperFactory::getMapper('folder')->find($this->path . decodeFilenameBase64($id) . DS);
				if ($folder) {
				
				}
			}
		}

		if ($this->_isXmlHttpRequest()) {
			$this->_setNoRender();
			echo 'OK';
		} else {
			$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
		}
	}

	/**
	 * Hide folder
	 *
	 */
	public function hideAction()
	{
		ivMessenger::add(ivMessenger::NOTICE, 'Folder is hidden');

		$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
	}

	/**
	 * Unhide folder
	 *
	 */
	public function unhideAction()
	{
		ivMessenger::add(ivMessenger::NOTICE, 'Folder revealed');

		$this->_redirect($_SERVER['HTTP_REFERER']?$_SERVER['HTTP_REFERER']:'index.php');
	}

	/**
	 * Upload file
	 *
	 */
	public function uploadAction()
	{
		$this->_setNoRender();
		if (!isset($_FILES['Filedata'])) {
			header("HTTP/1.1 500 Internal Server Error");
			echo "Error. File not found";
			return;
		}
		$imageData = $_FILES['Filedata'];
		$imageName = $imageData['name'];
		if (get_magic_quotes_gpc()) {
			$imageName = stripslashes($imageName);
		}
		if (!ivFilepath::matchSuffix($imageName, $this->conf->get('/config/imagevue/settings/allowedext'))) {
			header("HTTP/1.1 403 Forbidden");
			echo "Error. Wrong extention";
		} else {
			echo "File {$imageName} uploaded";
		}
	}

	/**
	 * move_uploaded_file wrapper (for test purposes)
	 *
	 * @param  string    $filename
	 * @param  string    $destination
	 * @return boolean
	 */
	protected function _moveUploadedFile($filename, $destination)
	{
		return @unlink($filename);
	}

	public function mceImageListAction()
	{
		$this->_setNoRender();

		header('Content-type: text/javascript');
		header('pragma: no-cache');
		header('expires: 0');

		echo "var tinyMCEImageList = [\n";

		$files = array();

		$folder = ivMapperFactory::getMapper('folder')->find($this->path);
		if ($folder) {
			foreach ($folder->Files as $file) {
				if (is_a($file, 'ivFileImage')) {
					$files[] = "['" . $file->getTitle() . "', '" . $this->conf->get('/config/imagevue/settings/contentfolder') . $file->getPrimary() . "']";
				}
			}
		}

		echo implode(",\n", $files);
		echo "\n];";
	}

	public function removePasswordAction()
	{
		$folder = ivMapperFactory::getMapper('folder')->find($this->path);
		
		$this->_redirect('index.php?path=' . smart_urlencode($this->path));
	}

	/**
	 * Post-dispatching
	 *
	 */
	public function _postDispatch()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$brCrumbsKeys = array_explode_trim('/', $this->path);
		if ($brCrumbsKeys !== false) {
			$folder = ivMapperFactory::getMapper('folder')->find('');
			$suffix = '';
			$numOfFiles = $folder->fileCount;
			if ($totalNumOfFiles = $folder->totalFileCount) {
				$suffix =  '[' . (($numOfFiles == $totalNumOfFiles) ? $numOfFiles : $numOfFiles . '/' . $totalNumOfFiles . '') . ']';
			}
			$crumbs->push((strip_icon($folder->getTitle()) ? $folder->getTitle() : $folder->name), 'index.php', $suffix, ($folder->isHidden() ? 'hidden' : ''));

			$lastCrumbKey = end($brCrumbsKeys);
			$path = '';
			foreach ($brCrumbsKeys as $key) {
				$path .= $key . '/';
				$folder = ivMapperFactory::getMapper('folder')->find($path);
				if (!$folder) {
					continue;
				}
				$suffix = '';
				if ($lastCrumbKey == $key) {
					$numOfFiles = $folder->fileCount;
					if ($totalNumOfFiles = $folder->totalFileCount) {
						$suffix =  '[' . (($numOfFiles == $totalNumOfFiles) ? $numOfFiles : $numOfFiles . '/' . $totalNumOfFiles . '') . ']';
					}
					$crumbs->push((strip_icon($folder->getTitle()) ? $folder->getTitle() : $folder->name), 'index.php?path=' . smart_urlencode($path), $suffix, ($folder->isHidden() ? 'hidden' : ''));
				} else {
					$crumbs->push((strip_icon($folder->getTitle()) ? $folder->getTitle() : $folder->name), 'index.php?path=' . smart_urlencode($path), '', ($folder->isHidden() ? 'hidden' : ''));
				}
			}
		}
	}

}