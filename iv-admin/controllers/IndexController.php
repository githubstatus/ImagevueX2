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

			if (isset($newdata['newWindow']) && isset ($newdata['pageContent'])) $newdata['pageContent'].='*_blank';

			foreach ($newdata as $name => $value) {
				$folder->$name = $value;
			}
			if (isset($_POST['folderPwd'])) {
				if (empty($_POST['folderPwd'])) {
					$folder->removePassword();
				} elseif ('******' != $_POST['folderPwd']) {
					$folder->setPassword((string) $_POST['folderPwd']);
				}
			}
			if ($folder->save()) {
				ivMessenger::add(ivMessenger::NOTICE, 'Folder data saved');
			} else {
				$folder->refresh();
				ivMessenger::add(ivMessenger::ERROR, "Folder data wasn't saved");
			}
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
		$newDirName = (string) $this->_getParam('name');
		if (!preg_match('/^[_\w\d\s\-]+$/i', $newDirName)) {
			ivMessenger::add(ivMessenger::ERROR, 'Use only latin letters, numbers and _ or - symbols in folder name');
			$this->_redirect('index.php?path=' . smart_urlencode($this->path));
		}

		$newDirPath = ivPath::canonizeRelative($this->path . $newDirName);
		if (file_exists(ROOT_DIR . $this->_getContentDirPath() . $newDirPath)) {
			ivMessenger::add(ivMessenger::WARNING, "Folder '<strong>$newDirName</strong>' already exists");
		} else if (mkdirRecursive(ROOT_DIR . $this->_getContentDirPath() . $newDirPath, 0777)) {
			ivMessenger::add(ivMessenger::NOTICE, 'Folder created');
		} else {
			ivMessenger::add(ivMessenger::ERROR, "Folder wasn't created");
			$newDirPath = null;
		}

		$this->_redirect('index.php?path=' . smart_urlencode(isset($newDirPath) ? $newDirPath : $this->path));
	}

	/**
	 * Rename folder
	 *
	 */
	public function renameAction()
	{
		if (ivAcl::getAllowedPath() == ivPath::canonizeRelative($this->path)){
			ivMessenger::add(ivMessenger::ERROR, 'Cannot rename your root folder');
			$this->_redirect('index.php?path=' . smart_urlencode($this->path));
		}

		if ('' == ivPath::canonizeRelative($this->path)) {
			ivMessenger::add(ivMessenger::ERROR, 'Cannot rename content folder');
			$this->_redirect('index.php?path=' . smart_urlencode($this->path));
		}
		$newDirName = (string) $this->_getParam('name');
		if (!preg_match('/^[_\w\d\s\-]+$/i', $newDirName)) {
			ivMessenger::add(ivMessenger::ERROR, 'Use only latin letters, numbers and _ or - symbols in folder name');
			$this->_redirect('index.php?path=' . smart_urlencode($this->path));
		}
		$newDirPath = ivPath::canonizeRelative(dirname($this->path)) . ivPath::canonizeRelative($newDirName);
		$result = @rename(ROOT_DIR . $this->_getContentDirPath() . $this->path, ROOT_DIR . $this->_getContentDirPath() . $newDirPath);
		if ($result) {
			ivMessenger::add(ivMessenger::NOTICE, 'Folder renamed');
			$this->_redirect('index.php?path=' . smart_urlencode($newDirPath));
		} else {
			ivMessenger::add(ivMessenger::ERROR, 'Folder not renamed');
			$this->_redirect('index.php?path=' . smart_urlencode($this->path));
		}
	}

	/**
	 * Delete folder
	 *
	 */
	public function deleteAction()
	{

		if (ivAcl::getAllowedPath() == ivPath::canonizeRelative($this->path)){
			ivMessenger::add(ivMessenger::ERROR, 'Cannot delete your root folder');
			$this->_redirect('index.php?path=' . smart_urlencode($this->path));
		}

		if ('' == ivPath::canonizeRelative($this->path)) {
			ivMessenger::add(ivMessenger::ERROR, 'Content folder cannot be deleted');
		} else {
			if ($folder = ivMapperFactory::getMapper('folder')->find($this->path)) {
				$redirectPath = 'index.php?path=' . smart_urlencode($folder->Parent->getPrimary());
				$folder->delete();
				ivMessenger::add(ivMessenger::NOTICE, 'Folder deleted');
			} else {
				$redirectPath = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
				ivMessenger::add(ivMessenger::ERROR, 'Folder not deleted');
			}
		}

		$this->_redirect($redirectPath);
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
		$_SESSION['imagevue']['move'] = true;
		$targetPath = ivPath::canonizeRelative($this->_getParam('target', null, 'path'));
		$moved = 0;
		$skipped = 0;
		$mapper = ivMapperFactory::getMapper('file');
		$targetFolder = ivMapperFactory::getMapper('folder')->find($targetPath);
		if ($targetFolder) {
			foreach ($this->_getParam('selected', array()) as $filename) {
				$file = ivMapperFactory::getMapper('file')->find($this->path . $filename);
				if ($file && $mapper->copyFile($file, $targetFolder) && $file->delete()) {
					$moved++;
				} else {
					$skipped++;
				}
			}
			ivMessenger::add(ivMessenger::NOTICE, $moved . ' files moved to <a href="index.php?path=' . smart_urlencode($targetFolder->getPrimary()) . '">' . htmlspecialchars($targetFolder->getTitle()) . '</a>' . ($skipped?', ' . $skipped . ' files skipped':''));
		}

		if ($redirectAfter) {
			$this->_redirect('index.php?path=' . smart_urlencode($this->path));
		}
	}

	/**
	 * Copy files
	 *
	 */
	public function copyFilesAction()
	{
		$_SESSION['imagevue']['move'] = false;
		$targetPath = ivPath::canonizeRelative($this->_getParam('target', null, 'path'));
		$copied = 0;
		$skipped = 0;
		$mapper = ivMapperFactory::getMapper('file');
		$targetFolder = ivMapperFactory::getMapper('folder')->find($targetPath);
		if ($targetFolder) {
			foreach ($this->_getParam('selected', array()) as $filename) {
				$file = $mapper->find($this->path . $filename);
				if ($file && $mapper->copyFile($file, $targetFolder)) {
					$copied++;
				} else {
					$skipped++;
				}
			}
			ivMessenger::add(ivMessenger::NOTICE, $copied . ' files copied to <a href="index.php?path=' . smart_urlencode($targetFolder->getPrimary()) . '">' . htmlspecialchars($targetFolder->getTitle()) . '</a>' . ($skipped?', ' . $skipped . ' files skipped':''));
		}
		$this->_redirect('index.php?path=' . smart_urlencode($this->path));
	}

	/**
	 * Delete files
	 *
	 */
	public function deleteFilesAction()
	{
		$deleted = 0;
		$skipped = 0;
		foreach ($this->_getParam('selected', array()) as $filename) {
			$file = ivMapperFactory::getMapper('file')->find($this->path . $filename);
			if ($file && $file->delete()) {
				$deleted++;
			} else {
				$skipped++;
			}
		}
		ivMessenger::add(ivMessenger::NOTICE, $deleted . ' files deleted' . ($skipped?', ' . $skipped . ' files skipped':''));
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
			$folder->previewimage = $file->name;
			$folder->save();

			if ($this->_isXmlHttpRequest()) {
				$this->_setNoRender();
				echo 'File ' . $file->name . ' is set as preview';
			} else {
				ivMessenger::add(ivMessenger::NOTICE, 'File ' . $file->name . ' is set as preview');
				$this->_redirect('index.php?path=' . smart_urlencode($folder->getPrimary()));
			}
		} else if (!$this->_getParam('id') && !$this->_isXmlHttpRequest()) {
			$folder->previewimage = '';
			$folder->save();

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
					$file->save();
				}
			}
			if (ivFolder::SORT_ORDER_MANUAL != $folder->sort) {
				$folder->sort = ivFolder::SORT_ORDER_MANUAL;
				$folder->save();
			}
		}

		if ($this->_isXmlHttpRequest()) {
			$this->_setNoRender();
			echo 'OK';
		} else {
			$this->_redirect($_SERVER['HTTP_REFERER']);
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
					$folder->order = $order + 1;
					$folder->save();
				}
			}
		}

		if ($this->_isXmlHttpRequest()) {
			$this->_setNoRender();
			echo 'OK';
		} else {
			$this->_redirect($_SERVER['HTTP_REFERER']);
		}
	}

	/**
	 * Hide folder
	 *
	 */
	public function hideAction()
	{
		if ('' == ivPath::canonizeRelative($this->path)) {
			ivMessenger::add(ivMessenger::ERROR, 'Content folder cannot be hid');
		} else {
			if ($folder = ivMapperFactory::getMapper('folder')->find($this->path)) {
				$folder->hidden = 'true';
				$folder->save();
			}
		}
		$this->_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	 * Unhide folder
	 *
	 */
	public function unhideAction()
	{
		if ($folder = ivMapperFactory::getMapper('folder')->find($this->path)) {
			$folder->hidden = 'false';
			$folder->save();
		}
		$this->_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	 * Upload file
	 *
	 */
	public function uploadAction()
	{
		$this->_setNoRender();
		if (!isset($_FILES['Filedata'])) {
			header("HTTP/1.1 500 No Filedata");
			echo "Error. File not found";
			return;
		}
		$imageData = $_FILES['Filedata'];
		$imageName = $imageData['name'];
		if (get_magic_quotes_gpc()) {
			$imageName = stripslashes($imageName);
		}
		if (
		    !ivFilepath::matchSuffix( $imageName, $this->conf->get('/config/imagevue/settings/allowedext') )
				|| ivFilepath::matchSuffix( $imageName, array('php', 'phtml', 'htm', 'html', 'js') )
			)
		{
			header("HTTP/1.1 403 Wrong extention");
			echo "Error. Wrong extention";
		} else {
			$folder = ivMapperFactory::getMapper('folder')->find($this->path);
			if ($folder) {
				// if (!$this->_getParam('overwrite', false)) {
				$imageName = ivMapperFactory::getMapper('folder')->sanityFileName($folder, $imageName);
				// }
				$fullpath = $folder->getPath() . $imageName;
				$result = $this->_moveUploadedFile($imageData['tmp_name'], ROOT_DIR . $fullpath);
				if ($result) {
					iv_chmod(ROOT_DIR . $fullpath, 0777);

					if (ivMapperXmlFile::isImage(ROOT_DIR . $fullpath) && $this->_getParam('resize') && (class_exists('ivImageAdapterImagick') || class_exists('ivImageAdapterImagemagick'))) {
						$width = (integer) $this->_getParam('width');
						$height = (integer) $this->_getParam('height');

						$image = new ivImage(ROOT_DIR . $fullpath);
						$image->resizeProportionally($width, $height, true);
					}

					$file = ivMapperFactory::getMapper('file')->find($folder->getPrimary() . $imageName);
					if ($file) {
						$file->generateThumbnail();
						if (!$file->title && $this->conf->get('/config/imagevue/settings/autoTitling')) {
							$file->title = ivFilepath::filename($imageData['name']);
						}
						$file->save();
						// REFACTORING How to avoid this legally?
						$folder->addFile($file);
						echo "File {$imageName} uploaded";
					} else {
						header("HTTP/1.1 500 File {$imageName} wasn't uploaded");
						echo "Error. File {$imageName} wasn't uploaded";
					}
				} else {
					header("HTTP/1.1 500 Can't move {$imageData['tmp_name']} to {$fullpath}");
					echo "Error. File {$imageName} wasn't uploaded";
				}
			} else {
				header("HTTP/1.1 500 Folder not found");
				echo 'Error. Folder not found';
			}
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
		return @move_uploaded_file($filename, $destination);
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
		if ($folder) {
			$folder->removePassword();
		}
		$this->_redirect('index.php?path=' . smart_urlencode($this->path));
	}

	/**
	 * Post-dispatching
	 *
	 */
	public function _postDispatch()
	{
		if ($this->needRender()) {
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

}