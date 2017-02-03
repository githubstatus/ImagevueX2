<?php

class LangController extends ivController
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
	 * Default action (edit language)
	 *
	 */
	public function indexAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Languages', 'index.php?c=lang');
		$lang = mb_strtolower($this->_getParam('name', 'english'), 'UTF-8');
		if (!preg_match('/^[\w\d\_]+$/', $lang)) {
			ivMessenger::add(ivMessenger::ERROR, 'Use only alphanumeric symbols and "_" symbol in language name');
			$this->_redirect('index.php?c=lang');
		}
		$this->view->assign('lang', $lang);
		$crumbs->push(ucfirst($lang), 'index.php?c=lang&amp;name=' . $lang);
		if (isset($_POST['Save']) && isset($_POST['lang'])) {
			$xml = ivLanguage::getLanguage($lang, true);
			foreach ($_POST['lang'] as $path => $value) {
				$node = $xml->findByXPath($path);
				if ($node) {
					$node->setValue((string) $value);
				}
			}
			$result = $xml->writeToFile();
			if ($result) {
				ivMessenger::add(ivMessenger::NOTICE, "Language file $lang.xml succesfully saved");
			} else {
				ivMessenger::add(ivMessenger::ERROR, "Can't save language file $lang.xml");
			}
		}

		$xml = ivLanguage::getLanguage($lang);

		$this->view->assign('flatConfig', $xml->toFlatTree());
		$this->view->assign('languages', ivLanguage::getAllLanguageNames());
	}

	/**
	 * Set default language
	 *
	 */
	public function useAction()
	{
		$lang = mb_strtolower($this->_getParam('name', 'english'), 'UTF-8');
		if (!preg_match('/^[\w\d\_]+$/', $lang)) {
			ivMessenger::add(ivMessenger::ERROR, 'Use only alphanumeric symbols and "_" symbol in language name');
			$this->_redirect('index.php?c=lang');
		}
		if (!is_null($lang)) {
			$xml = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE);
			$node = $xml->findByXPath('/config/imagevue/settings/language');
			if ($node) {
				$node->setValue((string) $lang);
			}
			$result = $xml->writeToFile();
			if ($result) {
				ivMessenger::add(ivMessenger::NOTICE, 'Configuration successfully saved');
			} else {
				ivMessenger::add(ivMessenger::ERROR, "Can't save configuration file " . substr(CONFIG_FILE, strlen(ROOT_DIR)));
			}
		}
		$this->_redirect('index.php?c=lang&name=' . $lang);
	}

}
