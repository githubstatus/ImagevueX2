<?php

class PublishController extends ivController
{

	/**
	 * Publish gallery
	 *
	 */
	public function indexAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Publish', 'index.php?c=publish');

		$path = ivPath::canonizeRelative($this->_getParam('path', ivAcl::getAllowedPath(), 'path'));
		$folder = ivMapperFactory::getMapper('folder')->find($path);
		if (!$folder) {
			$this->_redirect('index.php');
		}

		$this->view->assign('path', $path);

		$contentFolder = ivMapperFactory::getMapper('folder')->find(ivAcl::getAllowedPath());
		$iterator = new ivRecursiveFolderIterator($contentFolder);
		$this->view->assign('folderTreeIterator', new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST));

		$publishData = $this->_getParam('publishData');
		if (is_array($publishData)) {
			ivMessenger::add(ivMessenger::NOTICE, "Gallery published");
		
			$this->_redirect('index.php?c=publish');
		}
	}

	/**
	 * End of gallery publishing
	 *
	 */
	public function doneAction()
	{
	
	}

}