<?php

class ConfigController extends ivController
{

	/**
	 * Default action (edit main config)
	 *
	 */
	public function indexAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Settings', 'index.php?c=config');
		$configFile = CONFIG_FILE;

		if (isset($_POST['save']) && isset($_POST['config'])) {
			ivMessenger::add(ivMessenger::NOTICE, 'Configuration successfully saved');
		}

		$xml = ivXml::readFromFile($configFile, DEFAULT_CONFIG_FILE);

// SMTP dirty hack goes here
		$smtpConfig = ivMail::getSmtpConfig();

		$emailNode = $xml->findByXPath('/config/imagevue/settings/email');
		if ($emailNode) {
			$smtpNode = new ivXmlNode('smtp');

			$emailNode->addChild($smtpNode);

			$enabledNode = new ivXmlNodeBoolean('enabled', array('description' => $smtpConfig['enabled']['description']));
			$enabledNode->setValue($smtpConfig['enabled']['value']);
			$smtpNode->addChild($enabledNode);

			$hostNode = new ivXmlNodeString('host', array('description' => $smtpConfig['host']['description']));
			$hostNode->setValue($smtpConfig['host']['value']);
			$smtpNode->addChild($hostNode);

			$portNode = new ivXmlNodeInteger('port', array('description' => $smtpConfig['port']['description']));
			$portNode->setValue($smtpConfig['port']['value']);
			$smtpNode->addChild($portNode);

			$authNode = new ivXmlNodeBoolean('auth', array('description' => $smtpConfig['auth']['description']));
			$authNode->setValue($smtpConfig['auth']['value']);
			$smtpNode->addChild($authNode);

			$usernameNode = new ivXmlNodeString('username', array('description' => $smtpConfig['username']['description']));
			$usernameNode->setValue($smtpConfig['username']['value']);
			$smtpNode->addChild($usernameNode);

			$passwordNode = new ivXmlNodePassword('password', array('description' => $smtpConfig['password']['description']));
			$passwordNode->setValue($smtpConfig['password']['value']);
			$smtpNode->addChild($passwordNode);

			$secureNode = new ivXmlNodeEnum('secure', array('description' => $smtpConfig['secure']['description'], 'options' => 'none,tls,ssl'));
			$secureNode->setValue($smtpConfig['secure']['value']);
			$smtpNode->addChild($secureNode);
		}
// --

		$sections = array();
		$rootNode = $xml->findByXPath('/config/imagevue');
		if ($rootNode) {
			foreach ($rootNode->getChildren() as $k => $child) {
				$sections[$child->getName()] = $child->toFlatTree();
			}
		}

		$this->view->assign('sections', $sections);

		$openedPanels = array();
		if (isset($_COOKIE['ivconf'])) {
			$openedPanels = array_unique(array_explode_trim(',', $_COOKIE['ivconf']));
		}
		$this->view->assign('openedPanels', $openedPanels);
	}

	public function templatesAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Templates', 'index.php?c=config&a=templates');

		if (isset($_POST['contactTemplate']) || isset($_POST['sendlinkTemplate'])) {
			ivMessenger::add(ivMessenger::NOTICE, 'Templates successfully saved');
			$this->_redirect($_SERVER['REQUEST_URI']);
		}

		$this->view->assign('contactTemplate', file_get_contents(getEmailTemplatePath('contact')));
		$this->view->assign('sendlinkTemplate', file_get_contents(getEmailTemplatePath('sendlink')));
	}

	public function cssAction()
	{
		$stub = '/* Put your css here */';
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Custom CSS', 'index.php?c=config&a=css');

		if (!empty($_POST)) {
			ivMessenger::add(ivMessenger::NOTICE, 'Stylesheets successfully saved');
			$this->_redirect($_SERVER['REQUEST_URI']);
		}

		$this->view->assign('htmlCss', (getCssPath('html') ? file_get_contents(ROOT_DIR . getCssPath('html')) : $stub));
		$this->view->assign('flashCss', (getCssPath('flash') ? file_get_contents(ROOT_DIR . getCssPath('flash')) : $stub));
		$this->view->assign('mobileCss', (getCssPath('mobile') ? file_get_contents(ROOT_DIR . getCssPath('mobile')) : $stub));
	}

}