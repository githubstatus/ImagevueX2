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
			$this->_redirect('index.php');
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

		if (is_string($this->_getParam('save')) && is_array($this->_getParam('newdata'))) {
			ivMessenger::add(ivMessenger::NOTICE, 'File data successfully saved');
		}

		$siblings = $file->getSiblings();

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
		
		ivMessenger::add(ivMessenger::NOTICE, 'File succesfully deleted');
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
			
			ivMessenger::add(ivMessenger::NOTICE, 'File succesfully renamed');
			
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
			
			ivMessenger::add(ivMessenger::NOTICE, 'File succesfully rotated');
		}
		$this->_redirect('index.php?c=file&path=' . smart_urlencode($this->path));
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
?>
