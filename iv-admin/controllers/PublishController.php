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
			$this->_forward('done', 'publish');
		}
	}

	/**
	 * End of gallery publishing
	 *
	 */
	public function doneAction()
	{
		$publishData = $this->_getParam('publishData');

		$folder = ivMapperFactory::getMapper('folder')->find(ivPath::canonizeRelative($publishData['path']));
		if (!$folder) {
			$this->_redirect('index.php');
		}

		if (isset($publishData['width']) && ((integer) $publishData['width'] > 0)) {
			$this->view->assign('width', (integer) $publishData['width']);
		}

		if (isset($publishData['height']) && ((integer) $publishData['height'] > 0)) {
			$this->view->assign('height', (integer) $publishData['height']);
		}

		if (isset($publishData['resizetype']) && in_array($publishData['resizetype'], array(ivImage::IMAGE_RESIZETYPE_RESIZETOBOX, ivImage::IMAGE_RESIZETYPE_CROPTOBOX))) {
			$this->view->assign('resizetype', $publishData['resizetype']);
		}

		$iterator = new ivRecursiveFolderIterator($folder);
		$this->view->assign('folderTreeIterator', new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST));
		$this->view->assign('missingOnly', (($publishData['thumbnails']=='create') ? '1' : '0'));
		$this->view->assign('contentPath', $this->_getContentDirPath());
	}

}