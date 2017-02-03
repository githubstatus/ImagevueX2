<?php

class IndexController extends ivController
{

	/**
	 * Initialization method
	 *
	 */
	public function _preDispatch()
	{
		parent::_preDispatch();

		$this->view->assign('contentPath', ivPath::canonizeRelative($this->conf->get('/config/imagevue/settings/contentfolder')));
	}

	/**
	 * Display gallery
	 *
	 */
	public function galleryAction()
	{
		if (IS_MOBILE) {
			$this->_forward('html', 'index');
		}

		$this->_disableLayout();
		$vars = $this->_getParams($this->conf->get('/config/imagevue/settings/url_params'));

		if (!isset($_GET['theme'])) {
			unset($vars['theme']);
			$theme = ivThemeMapper::getInstance()->find($this->conf->get('/config/imagevue/settings/theme'));
		} elseif ($theme = ivThemeMapper::getInstance()->find($_GET['theme'])) {
			$vars['theme'] = $_GET['theme'];
		} else {
			unset($vars['theme']);
			$theme = ivThemeMapper::getInstance()->find($this->conf->get('/config/imagevue/settings/theme'));
		}

		$themeXml = $theme->getConfig();

		$bkGrColor = $themeXml->findByXPath('/config/imagevue/style/background_color');
		$this->view->assign('bkGrColor', substr($bkGrColor->getValue(), -6));

		$frGrColor = $themeXml->findByXPath('/config/imagevue/style/foreground_color');
		$this->view->assign('frGrColor', substr($frGrColor->getValue(), -6));

		if (isset($_GET['stylesheet'])) {
			$vars['stylesheet'] = ivFilepath::basename($_GET['stylesheet']);
			setcookie('stylesheet', '', time() - 3600);
		} elseif (isset($vars['stylesheet'])) {
			unset($vars['stylesheet']);
		}

		if (isset($_GET['language']) && in_array(mb_strtolower($_GET['language'], 'UTF-8'), ivLanguage::getAllLanguageNames())) {
			$vars['language'] = mb_strtolower($_GET['language'], 'UTF-8');
		} else {
			$config = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE);
			ivPool::get('conf')->set('/config/imagevue/settings/language', mb_strtolower($config->get('/config/imagevue/settings/language'), 'UTF-8'));
			$vars['language'] = mb_strtolower($config->get('/config/imagevue/settings/language'), 'UTF-8');
		}

		$this->view->assign('vars', $vars);
		$this->view->assign('siteTitle', $this->conf->get('/config/imagevue/settings/sitetitle'));
		$this->view->assign('enabledHTML', $this->conf->get('/config/imagevue/settings/enableHTML'));

		$firstImage = false;
		$contentFolder = ivMapperFactory::getMapper('folder')->find('');
		if ($contentFolder) {
			$iterator = new ivRecursiveFolderIterator($contentFolder);
			$filter1 = new ivRecursiveFolderIteratorVisible($iterator);
			$filter2 = new ivRecursiveFolderIteratorPassword($filter1, (isset($_SESSION['imagevue']['password']) ? (string) $_SESSION['imagevue']['password'] : null));
			foreach (new RecursiveIteratorIterator($filter2, RecursiveIteratorIterator::SELF_FIRST) as $folder) {
				if ($folder->previewimage) {
					$firstImage = ivMapperFactory::getMapper('file')->find($folder->getPrimary() . $folder->previewimage);
					break;
				}
			}
		}
		$this->view->assign('firstImage', $firstImage);
	}

	/**
	 * Display folder's images
	 *
	 */
	public function htmlAction()
	{
		if ($this->conf->get('/config/imagevue/settings/enableHTML') || IS_MOBILE) {
			if (isset($_POST['password'])) {
				$_SESSION['imagevue']['password'] = (string) $_POST['password'];
			}
			if (strlen($this->_getParam('path')) > 0 || IS_MOBILE) {
				$path = ivPath::canonizeRelative($this->_getParam('path', '/', 'path'));
			} else {
				$path = ivPath::canonizeRelative($this->conf->get('/config/imagevue/settings/htmlstartpath'));
			}
			$folder = ivMapperFactory::getMapper('folder')->find(substr($path, 0, -1));
			if (!$folder) {
				$file = ivMapperFactory::getMapper('file')->find(substr($path, 0, -1));
				if ($file) {
					$this->_forward('image', 'index');
					return;
				}
				$this->_redirect('?/');
			}
			$this->view->assign('folder', $folder);

			$this->view->assign('wrongPassword', (isset($_POST['password']) && !$folder->checkPassword($_POST['password'])));

			$crumbs = ivPool::get('breadCrumbs');
			$brCrumbsKeys = array_explode_trim('/', $folder->getPrimary());
			if (!empty($brCrumbsKeys)) {
				if ($homeFolder = ivMapperFactory::getMapper('folder')->find('')) {
					$crumbs->push($homeFolder->getTitle(), '?' . smart_urlencode('//'), '');
				}

				$lastCrumbKey = end($brCrumbsKeys);
				$path = '';
				foreach ($brCrumbsKeys as $key) {
					$path .= $key . '/';
					$folder = ivMapperFactory::getMapper('folder')->find($path);
					if (!$folder) {
						continue;
					}
					if ($lastCrumbKey == $key) {
						$suffix='';
						if ($numOfFiles = $folder->fileCount) {
							$suffix = '[' . $numOfFiles . ']';
						}

						$crumbs->push($folder->getTitle(), '?' . smart_urlencode($path), $suffix, 'active');
					} else {
						$crumbs->push($folder->getTitle(), '?' . smart_urlencode($path), '');
					}
				}
			}
			$this->view->assign('crumbs', $crumbs);
			$this->view->assign('useLightview', $this->conf->get('/config/imagevue/settings/useLightview'));
		} else {
			$this->_disableLayout();
			$this->_setNoRender();
			header('HTTP/1.1 404 Not Found');
			exit(0);
		}
	}

	/**
	 * Display image
	 *
	 */
	public function imageAction()
	{
		if ($this->conf->get('/config/imagevue/settings/enableHTML') || IS_MOBILE) {
			$path = ivPath::canonizeRelative($this->_getParam('path', null, 'path'), true);
			$file = ivMapperFactory::getMapper('file')->find($path);
			if (!$file) {
				$this->_redirect('?' . $this->conf->get('/config/imagevue/settings/contentfolder'));
			}
			$siblings = $file->getSiblings();

			$this->view->assign('file', $file);
			$this->view->assign('nextFile', $siblings->next);
			$this->view->assign('prevFile', $siblings->previous);
			$this->view->assign('current', $siblings->current);
			$this->view->assign('count', $siblings->count);

			$crumbs = ivPool::get('breadCrumbs');
			$brCrumbsKeys = array_explode_trim('/', $file->getPrimary());
			if ($brCrumbsKeys !== false) {
				if ($homeFolder = ivMapperFactory::getMapper('folder')->find('')) {
					$crumbs->push($homeFolder->getTitle(), '?' . smart_urlencode('//'), '');
				}

				$lastCrumbKey = end($brCrumbsKeys);
				$path = '';
				foreach ($brCrumbsKeys as $key) {
					if ($lastCrumbKey == $key) {
						$path .= $key;
						$file = ivMapperFactory::getMapper('file')->find($path);
						if (!$file) {
							continue;
						}
						$crumbs->push($file->getTitle(), '?' . smart_urlencode($path), '', 'active');
					} else {
						$path .= $key . '/';
						$folder = ivMapperFactory::getMapper('folder')->find($path);
						if (!$folder) {
							continue;
						}
						$crumbs->push($folder->getTitle(), '?' . smart_urlencode($path), '');
					}
				}
			}
			$this->view->assign('crumbs', $crumbs);
			ivPool::set('bodyClass','image');
		} else {
			$this->_disableLayout();
			$this->_setNoRender();
			header('HTTP/1.1 404 Not Found');
			exit(0);
		}
	}

	/**
	 * Display image in popup
	 *
	 */
	public function popupAction()
	{
		$this->_disableLayout();
		$path = ivPath::canonizeRelative($this->_getParam('path', null, 'path'), true);
		$file = ivMapperFactory::getMapper('file')->find($path);

		if (!is_a($file, 'ivFileImage')) {
			$this->_setNoRender();
			header('HTTP/1.1 404 Not Found');
			exit(0);
		}

		$this->view->assign('file', $file);
	}

	/**
	 * Display sitemap
	 *
	 */
	public function sitemapAction()
	{
		$this->_getSitemap();
	}

	/**
	 * Display sitemap.xml
	 *
	 */
	public function sitemapXMLAction()
	{
		$this->_getSitemap();
	}

	/**
	* Actual sitemap thing
	*
	*/

	private function _getSitemap() {
		$this->_disableLayout();
		if (!$this->conf->get('/config/imagevue/seo/disableSitemap')) {
			$this->view->assign('siteTitle', $this->conf->get('/config/imagevue/settings/sitetitle'));

			if ($contentFolder = ivMapperFactory::getMapper('folder')->find('')) {
				$iterator = new ivRecursiveFolderIterator($contentFolder);
				$filter1 = new ivRecursiveFolderIteratorVisible($iterator);
				$filter2 = new ivRecursiveFolderIteratorPassword($filter1, (isset($_SESSION['imagevue']['password']) ? (string) $_SESSION['imagevue']['password'] : null));
				$filter3 = new ivRecursiveFolderIteratorShare($filter2);
				$folderTreeIterator = new RecursiveIteratorIterator($filter3, RecursiveIteratorIterator::SELF_FIRST);
				$this->view->assign('folderTreeIterator', $folderTreeIterator);
			}
		} else {
			$this->_setNoRender();
			header('HTTP/1.1 404 Not Found');
			exit(0);
		}
	}


	/**
	 * Share page
	 *
	 */
	public function shareAction()
	{
		$this->_disableLayout();

		$path = preg_replace('/^'.preg_quote(ivPath::canonizeRelative($this->conf->get('/config/imagevue/settings/contentfolder'),'/')) . '/', '', substr(ivPath::canonizeRelative($this->_getParam('path')), 0, -1));

		$item = ivMapperFactory::getMapper('folder')->find($path);
		if (!$item) {
			$item = ivMapperFactory::getMapper('file')->find($path);
		}
		if (!$item) {
			$this->_redirect('?');
		}

		if (is_a($item, 'ivFolder')) {
			if ($item->previewimage && ($file = ivMapperFactory::getMapper('file')->find($item->getPrimary() . $item->previewimage))) {
				$firstImage = htmlspecialchars($file->thumbnail);
			} else {
				$firstImage = false;
				$iterator = new ivRecursiveFolderIterator($item);
				$filter1 = new ivRecursiveFolderIteratorVisible($iterator);
				$filter2 = new ivRecursiveFolderIteratorPassword($filter1, (isset($_SESSION['imagevue']['password']) ? (string) $_SESSION['imagevue']['password'] : null));
				foreach (new RecursiveIteratorIterator($filter2, RecursiveIteratorIterator::SELF_FIRST) as $folder) {
					if ($folder->previewimage) {
						$firstImage = ivMapperFactory::getMapper('file')->find($folder->getPrimary() . $folder->previewimage)->getPath();
						break;
					}
				}
			}
			$this->view->assign('firstImage', $firstImage);
		}

		$this->view->assign('item', $item);
	}

	/**
	 * Map page
	 *
	 */
	public function mapAction()
	{
		$this->_disableLayout();

		$path = ivPath::canonizeRelative($this->_getParam('path'));
		$item = ivMapperFactory::getMapper('folder')->find(substr($path, 0, -1));
		if (!$item) {
			$item = ivMapperFactory::getMapper('file')->find(substr($path, 0, -1));
		}
		if (!$item) {
			$this->_redirect('?');
		}

		$this->view->assign('item', $item);
	}

/**
	 * Video page
	 *
	 */
	public function videoAction()
	{
		$this->_disableLayout();

		$path = ivPath::canonizeRelative($this->_getParam('path'));
		$item = ivMapperFactory::getMapper('folder')->find(substr($path, 0, -1));
		if (!$item) {
			$item = ivMapperFactory::getMapper('file')->find(substr($path, 0, -1));
		}
		if (!$item) {
			$this->_redirect('?');
		}
		$this->view->assign('item', $item);
	}


}