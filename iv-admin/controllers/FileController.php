<?php

class FileController extends ivController
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
		$path = ivPath::canonizeRelative($this->_getParam('path', '', 'path'), true);
		if (!ivAcl::isAllowedPath($path)) {
			if (false == ivAcl::getAllowedPath()) {
				$this->_forward('login', 'cred');
				return;
			} else {
				$this->_redirect('index.php');
			}
		}

		$fullPath = ROOT_DIR . $this->_getContentDirPath() . $path;
		if (is_dir($fullPath)) {
			$this->_redirect('index.php?path=' . smart_urlencode($path));
		} elseif (!is_file($fullPath)) {
			if (ivFilepath::directory($path)) {
				$this->_redirect('index.php?path=' . smart_urlencode(ivFilepath::directory($path)));
			} else {
				$this->_redirect('index.php');
			}
		} elseif (!in_array(strtolower(ivFilepath::suffix($fullPath)), $this->conf->get('/config/imagevue/settings/allowedext'))) {
			$this->_redirect('index.php?path=' . smart_urlencode(ivFilepath::directory($path)));
		}
		$this->path = $path;
	}

	/**
	 * Default action
	 *
	 */
	public function indexAction()
	{
		$file = ivMapperFactory::getMapper('file')->find($this->path);
		if (!$file) {
			$this->_redirect('index.php');
		}

		$siblings = $file->getSiblings();

		// Save file data
		$newdata = $this->_getParam('newdata');
		if (is_string($this->_getParam('save')) && is_array($newdata)) {
			foreach ($newdata as $name => $value) {
				$file->$name = $value;
			}
			if ($file->save()) {
				ivMessenger::add(ivMessenger::NOTICE, 'File data succesfully saved');
			} else {
				ivMessenger::add(ivMessenger::ERROR, "File data wasn't saved");
			}
			if ($this->_getParam('editNext', false)) {
				$this->_redirect('index.php?c=file&path=' . smart_urlencode($siblings->next->getPrimary()));
			} else {
				$this->_redirect($_SERVER['REQUEST_URI']);
			}
		}

		$contentFolder = ivMapperFactory::getMapper('folder')->find(ivAcl::getAllowedPath());
		$iterator = new ivRecursiveFolderIterator($contentFolder);
		$this->view->assign('folderTreeIterator', new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST));

		$this->view->assign('path', $this->path);
		$this->placeholder->set('path', $this->path);

		$this->view->assign('file', $file);
		$this->view->assign('nextFile', $siblings->next);
		$this->view->assign('prevFile', $siblings->previous);
		$this->view->assign('current', $siblings->current);
		$this->view->assign('count', $siblings->count);

		$_SESSION['imagevue']['lastManageLink'] = '?c=file&amp;path=' . smart_urlencode($this->path);
	}

	/**
	 * Copy/move file
	 *
	 */
	public function moveAction()
	{
		$isMove = !$this->_getParam('copy', false);
		$targetPath = ivPath::canonizeRelative($this->_getParam('target', null, 'path'));
		$result = false;
		if (!empty($targetPath) && ($folder = ivMapperFactory::getMapper('folder')->find($targetPath)) && ($file = ivMapperFactory::getMapper('file')->find($this->path))) {
			$result = ivMapperFactory::getMapper('file')->copyFile($file, $folder);
			if ($isMove) {
				$parentFolder = $file->Parent;
				$result &= $file->delete();
				$_SESSION['imagevue']['move'] = true;
			} else {
				$_SESSION['imagevue']['move'] = false;
			}
		}
		if ($isMove) {
			ivMessenger::add(ivMessenger::NOTICE, ($result ? "File succesfully moved to <a href=\"index.php?path=" . smart_urlencode($folder->getPrimary()) . "\">" . htmlspecialchars($folder->getTitle()) . "</a>" : "File wasn't moved"));
			$this->_redirect('index.php?path=' . smart_urlencode($parentFolder->getPrimary()));
		} else {
			ivMessenger::add(ivMessenger::NOTICE, ($result ? "File succesfully copied to <a href=\"index.php?path=" . smart_urlencode($folder->getPrimary()) . "\">" . htmlspecialchars($folder->getTitle()) . "</a>" : "File wasn't copied"));
		}
		$this->_redirect('index.php?path=' . smart_urlencode($this->path));
	}

	/**
	 * Delete file
	 *
	 */
	public function deleteAction()
	{
		$redirect = 'index.php?path=' . smart_urlencode(ivFilepath::directory($this->path));

		$file = ivMapperFactory::getMapper('file')->find($this->path);
		if ($file) {

			if (preg_match('/c\=file/', $_SERVER['HTTP_REFERER'])) {
				$siblings = $file->getSiblings();
				if (isset($siblings->next) && ($siblings->next->getPrimary() != $file->getPrimary())) {
					$redirect = 'index.php?c=file&path=' . smart_urlencode($siblings->next->getPrimary());
				}
			}

			$result = $file->delete();
			ivMessenger::add(ivMessenger::NOTICE, ($result ? 'File succesfully deleted' : "File wasn't deleted"));
		} else {
			ivMessenger::add(ivMessenger::NOTICE, 'File not found');
		}
		$this->_redirect($redirect);
	}

	/**
	 * Rename file
	 *
	 */
	function renameAction()
	{
		$file = ivMapperFactory::getMapper('file')->find($this->path);
		$newFileName = (string) $this->_getParam('name');
		if ($file && !empty($newFileName)) {
			$result = ivMapperFactory::getMapper('file')->rename($file, $newFileName);

			if ($result) {
				ivMessenger::add(ivMessenger::NOTICE, 'File succesfully renamed');
				$this->_redirect('index.php?c=file&path=' . smart_urlencode($file->Parent->getPrimary() . $newFileName));
			} else {
				ivMessenger::add(ivMessenger::NOTICE, "File wasn't renamed");
				$this->_redirect('index.php?path=' . smart_urlencode($file->Parent->getPrimary()));
			}
		}

		$this->_redirect('index.php?path=' . smart_urlencode($this->path));
	}

	/**
	 * Rotate image
	 *
	 */
	public function rotateAction()
	{
		if ($file = ivMapperFactory::getMapper('file')->find($this->path)) {
			$direction = $this->_getParam('dir', ivImage::IMAGE_ROTATE_CW, 'alnum');
			if (!in_array($direction, array(ivImage::IMAGE_ROTATE_CW, ivImage::IMAGE_ROTATE_CCW))) {
				$direction = ivImage::IMAGE_ROTATE_CW;
			}
			$image = new ivImage(ROOT_DIR . $file->getPath());
			$image->rotate($direction);
			$image->write();

			$file->deleteThumbnail();

			$file->generateThumbnail();

			ivMessenger::add(ivMessenger::NOTICE, 'File succesfully rotated');
		}
		$this->_redirect('index.php?c=file&path=' . smart_urlencode($this->path));
	}

	public function getthumbAction()
	{
		$errorReporting = error_reporting(0);
		$this->_setNoRender();
		if ($file = ivMapperFactory::getMapper('file')->find($this->path)) {
			$defaultThumbPath = ROOT_DIR . $file->thumbnail;

			$file->generateThumbnail($this->_getParam('width'), $this->_getParam('height'), $this->_getParam('resizetype'));

			$thumbPath = ROOT_DIR . $file->thumbnail;
			if (!file_exists($thumbPath)) {
				$thumbPath = $defaultThumbPath;
			}
			$data = @getimagesize($thumbPath);
			if (isset($data['mime'])) {
				// FIXME Debug data
				xFireDebug('Generation Time ' . getGenTime() . ' sec');
				header('Cache-Control: public');
				header('Expires: Fri, 30 Dec 1999 19:30:56 GMT');
				header('Content-Type: ' . $data['mime']);
				readfile($thumbPath);
			}
			error_reporting($errorReporting);
		}
	}

	public function thumbareaAction()
	{
		$file = ivMapperFactory::getMapper('file')->find($this->path);
		if (!$file) {
			ivMessenger::add(ivMessenger::NOTICE, 'File not found');
			$this->_redirect('index.php');
		}

		if (!is_a($file, 'ivFileImage')) {
			$this->_forward('thumbselect');
		}

		$this->_disableLayout();

		if (!empty($_POST)) {
			$errorReporting = error_reporting(0);

			$file->cropThumbnail($this->_getParam('x'), $this->_getParam('y'), $this->_getParam('width'), $this->_getParam('height'), $this->_getParam('thumbWidth'), $this->_getParam('thumbHeight'));
			$this->view->assign('done', true);
		}

		$this->view->assign('file', $file);
	}

	public function thumbselectAction()
	{
		$file = ivMapperFactory::getMapper('file')->find($this->path);
		if (!$file) {
			ivMessenger::add(ivMessenger::NOTICE, 'File not found');
			$this->_redirect('index.php');
		}

		if (is_a($file, 'ivFileImage')) {
			$this->_forward('thumbarea');
		}

		$this->_disableLayout();

		$thumbnails = array();
		foreach (new ivFilterIteratorDot(new DirectoryIterator(ROOT_DIR . $file->Parent->getPath())) as $item) {
			if (ivFilepath::matchSuffix($item->getFilename(), $this->conf->get('/config/imagevue/settings/allowedext')) && ivFilepath::matchPrefix($item->getFilename(), array(ivMapperXmlAbstract::THUMBNAIL_PREFIX))) {
				$thumbnails[] = $file->Parent->getPath() . $item->getFilename();
			}
		}

		$thumbnail = $this->_getParam('thumbnail');
		if (!empty($thumbnail) && in_array($thumbnail, $thumbnails)) {
			@copy(ROOT_DIR . $thumbnail, ROOT_DIR . $file->Parent->getPath() . ivMapperXmlAbstract::THUMBNAIL_PREFIX . ivFilepath::filename($file->getPrimary()) . '.jpg');
			$this->view->assign('done', true);
		}

		$this->view->assign('file', $file);
		$this->view->assign('thumbnails', $thumbnails);
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
					if ($lastCrumbKey == $key) {
						$path .= $key;
						$file = ivMapperFactory::getMapper('file')->find($path);
						if (!$file) {
							continue;
						}
						$crumbs->push($file->getTitle(), 'index.php?c=file&amp;path=' . smart_urlencode($path));
					} else {
						$path .= $key . '/';
						$folder = ivMapperFactory::getMapper('folder')->find($path);
						if (!$folder) {
							continue;
						}
						$crumbs->push((strip_icon($folder->getTitle()) ? $folder->getTitle() : $folder->name), 'index.php?path=' . smart_urlencode($path), '', ($folder->isHidden() ? 'hidden' : ''));
					}
				}
			}
		}
	}

}
?>
