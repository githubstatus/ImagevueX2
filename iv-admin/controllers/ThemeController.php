<?php

class ThemeController extends ivController
{

	/**
	 * Pre-dispatching
	 *
	 */
	public function _preDispatch()
	{
		if (!ivAcl::isAdmin()) {
			$this->_forward('login', 'cred');
			if (ivAuth::getCurrentUserLogin()) {
				ivMessenger::add(ivMessenger::ERROR, "You don't have access to this page");
			}
			return;
		}
	}

	/**
	 * Default action
	 *
	 */
	public function indexAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Themes', 'index.php?c=theme');

		$selectedTheme = $this->conf->get('/config/imagevue/settings/theme');
		$defaultThemes = ivThemeMapper::getInstance()->getDefaultThemes();
		$themes = array_diff(ivThemeMapper::getInstance()->getAllThemes(), array($selectedTheme), $defaultThemes);
		sort($themes);
		array_unshift($themes, $selectedTheme);
		foreach (array_diff($defaultThemes, array($selectedTheme)) as $name) {
			$themes[] = $name;
		}

		$this->view->assign('theme', $selectedTheme);
		$this->view->assign('themes', $themes);
		$this->view->assign('defaultThemes', $defaultThemes);
	}

	/**
	 * Edit theme cascade stylesheet
	 *
	 */
	public function editcssAction()
	{
		$themeName = $this->_getParam('name', 'default', 'alnum');

		$file = $this->_getParam('file', 'imagevue.css', 'alnum');

		if (in_array($themeName, ivThemeMapper::getInstance()->getDefaultThemes())) {
			$this->_redirect('?c=theme');
		}

		$theme = ivThemeMapper::getInstance()->find($themeName);
		if (!$theme) {
			ivMessenger::add(ivMessenger::ERROR, "Theme named '$themeName' not found");
			$this->_redirect('?c=theme');
		}

		if (isset($_POST['css'])) {
			$css = (string) $_POST['css'];
			if ($theme->setStyle($css, $file)) {
				ivMessenger::add(ivMessenger::NOTICE, 'CSS file succesfully saved');
				$this->_redirect($_SERVER['REQUEST_URI']);
			}
		}

		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Themes', 'index.php?c=theme');
		$crumbs->push(ucfirst($themeName), 'index.php?c=theme&amp;a=editconfig&amp;name=' . $themeName);
		$crumbs->push('Stylesheet', 'index.php?c=theme&amp;a=editcss&amp;name=' . $themeName);

		$this->view->assign('theme', $theme);
		$this->view->assign('themes', ivThemeMapper::getInstance()->getAllThemes());
		$this->view->assign('cssfiles', $theme->getCssList());
		$this->view->assign('file', $file);

	}

	/**
	 * Edit theme configuration
	 *
	 */
	public function editconfigAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Themes', 'index.php?c=theme');

		$themeName = $this->_getParam('name', 'default', 'alnum');
		if (in_array($themeName, ivThemeMapper::getInstance()->getDefaultThemes())) {
			$this->_redirect('?c=theme');
		}

		$crumbs->push(ucfirst($themeName), 'index.php?c=theme&amp;a=editconfig&amp;name=' . $themeName);

		$theme = ivThemeMapper::getInstance()->find($themeName);
		if (!$theme) {
			ivMessenger::add(ivMessenger::ERROR, "Theme named '$themeName' not found");
			$this->_redirect('?c=theme');
		}

		if (isset($_POST['save']) && isset($_POST['config'])) {
			$xml = $theme->getConfig();
			foreach ($_POST['config'] as $path => $value) {
				$node = $xml->findByXPath($path);
				if ($node) {
					$node->setValue(is_array($value) ? implode(',', $value): (string) $value);
				}
			}
			$result = $xml->writeToFile();
			if ($result) {
				ivMessenger::add(ivMessenger::NOTICE, 'Theme configuration file succesfully saved');
			}
		}

		$xml = $theme->getConfig();

		$themeCssNode = $xml->findByXPath('/config/imagevue/style/stylesheet');
		$newThemeCssNode = ivXmlNode::create('stylesheet', array('options' => implode(',', $theme->getCssList()), 'type' => 'enum'));
		$newThemeCssNode->setValue($themeCssNode->getValue());
		$xml->replace($themeCssNode, $newThemeCssNode);


		$bgImageUrlNode = $xml->findByXPath('/config/imagevue/style/background_image/url');
		$newBgImageUrlNode = ivXmlNode::create('url', array('options' => implode(',', $theme->getBackgroundsList()), 'type' => 'enum'));
		$newBgImageUrlNode->setValue($bgImageUrlNode->getValue());
		$xml->replace($bgImageUrlNode, $newBgImageUrlNode);

		$bgImage2UrlNode = $xml->findByXPath('/config/imagevue/style/background_image_2/url');
		$newBgImage2UrlNode = ivXmlNode::create('url', array('options' => implode(',', $theme->getBackgroundsList()), 'type' => 'enum'));
		$newBgImage2UrlNode->setValue($bgImage2UrlNode->getValue());
		$xml->replace($bgImage2UrlNode, $newBgImage2UrlNode);

		$bgImage3UrlNode = $xml->findByXPath('/config/imagevue/style/background_image_3/url');
		$newBgImage3UrlNode = ivXmlNode::create('url', array('options' => implode(',', $theme->getBackgroundsList()), 'type' => 'enum'));
		$newBgImage3UrlNode->setValue($bgImage3UrlNode->getValue());
		$xml->replace($bgImage3UrlNode, $newBgImage3UrlNode);

		$sections = array();
		$rootNode = $xml->findByXPath('/config/imagevue');
		if ($rootNode) {
			foreach ($rootNode->getChildren() as $k => $child) {
				$sections[$child->getName()] = $child->toFlatTree();
			}
		}

		$this->view->assign('sections', $sections);

		$this->view->assign('themes', ivThemeMapper::getInstance()->getAllThemes());
		$this->view->assign('themeName', $themeName);

		$openedPanels = array();
		if (isset($_COOKIE['ivconf'])) {
			$openedPanels = array_unique(array_explode_trim(',', $_COOKIE['ivconf']));
		}
		$this->view->assign('openedPanels', $openedPanels);
	}

	/**
	 * Set default theme
	 *
	 */
	public function useAction()
	{
		$value = $this->_getParam('name', 'default', 'alnum');
		if (!is_null($value)) {
			$xml = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE);
			$node = $xml->findByXPath('/config/imagevue/settings/theme');
			if ($node) {
				$node->setValue((string) $value);
			}
			$result = $xml->writeToFile();
			if ($result) {
				ivMessenger::add(ivMessenger::NOTICE, 'Configuration successfully saved');
			} else {
				ivMessenger::add(ivMessenger::ERROR, "Can't save configuration file " . substr(CONFIG_FILE, strlen(ROOT_DIR)));
			}
		}
		$this->_redirect('index.php?c=theme');
	}

	/**
	 * Copy theme
	 *
	 */
	public function copyAction()
	{
		$themeName = $this->_getParam('name', 'default', 'alnum');
		$newThemeName = mb_strtolower($this->_getParam('new'), 'UTF-8');
		if (!ctype_alnum($newThemeName)) {
			ivMessenger::add(ivMessenger::ERROR, 'Use only alphanumeric symbols in theme name');
			$this->_redirect('index.php?c=theme');
		}
		if (!$themeName || !$newThemeName) {
			$this->_redirect('index.php?c=theme&a=editconfig&name=' . $themeName);
		}
		if (in_array($newThemeName, ivThemeMapper::getInstance()->getAllThemes())) {
			ivMessenger::add(ivMessenger::ERROR, 'Theme named ' . $newThemeName . ' already exists');
			$this->_redirect('index.php?c=theme&a=editconfig&name=' . $themeName);
		}

		$theme = ivThemeMapper::getInstance()->find($themeName);
		if (!$theme) {
			ivMessenger::add(ivMessenger::ERROR, "Theme named '$themeName' not found");
			$this->_redirect('?c=theme');
		}

		if (ivThemeMapper::getInstance()->copy($theme, $newThemeName)) {
			ivMessenger::add(ivMessenger::NOTICE, "Theme $newThemeName succesfully created");
		} else {
			ivMessenger::add(ivMessenger::ERROR, "Theme $newThemeName wasn't created");
		}
		$this->_redirect('index.php?c=theme');
	}

	/**
	 * Delete theme
	 *
	 */
	public function deleteAction()
	{
		$themeName = $this->_getParam('name', null, 'alnum');
		if (in_array($themeName, ivThemeMapper::getInstance()->getDefaultThemes())) {
			$this->_redirect('?c=theme');
		}

		$theme = ivThemeMapper::getInstance()->find($themeName);
		if (!$theme) {
			ivMessenger::add(ivMessenger::ERROR, "Theme named '$themeName' not found");
			$this->_redirect('?c=theme');
		}

		if (ivThemeMapper::getInstance()->delete($theme)) {
			ivMessenger::add(ivMessenger::NOTICE, "Theme $themeName succesfully deleted");
		} else {
			ivMessenger::add(ivMessenger::ERROR, "Theme $themeName wasn't deleted");
		}
		$this->_redirect('index.php?c=theme');
	}

	/**
	 * Upload background file
	 *
	 */
	public function uploadAction()
	{
		$themeName = $this->_getParam('name', null, 'alnum');
		if (in_array($themeName, ivThemeMapper::getInstance()->getDefaultThemes())) {
			$this->_redirect($_SERVER['HTTP_REFERER']);
		}

		if (!$themeName) {
			$this->_redirect($_SERVER['HTTP_REFERER']);
		}
		$this->_setNoRender();
		if (!isset($_FILES['Filedata'])) {
			ivMessenger::add(ivMessenger::ERROR, 'File not found');
			$this->_redirect($_SERVER['HTTP_REFERER']);
		}
		$imageData = $_FILES['Filedata'];
		if (!@getimagesize($imageData['tmp_name'])) {
			ivMessenger::add(ivMessenger::ERROR, 'Incompatible file');
			$this->_redirect($_SERVER['HTTP_REFERER']);
		}

		$theme = ivThemeMapper::getInstance()->find($themeName);
		if (!$theme) {
			ivMessenger::add(ivMessenger::ERROR, "Theme named '$themeName' not found");
			$this->_redirect('?c=theme');
		}

		$fullpath = $theme->getAbsolutePath() . $imageData['name'];
		$result = @move_uploaded_file($imageData['tmp_name'], $fullpath);
		if ($result) {
			iv_chmod($fullpath, 0777);
			ivMessenger::add(ivMessenger::NOTICE, "File {$imageData['name']} succesfully uploaded");
		} else {
			ivMessenger::add(ivMessenger::NOTICE, "File {$imageData['name']} wasn't uploaded");
		}
		$this->_redirect($_SERVER['HTTP_REFERER']);
	}

}