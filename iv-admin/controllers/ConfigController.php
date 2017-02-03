<?php

class ConfigController extends ivController
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
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Settings', 'index.php?c=config');
	}

	/**
	 * Default action (edit main config)
	 *
	 */
	public function indexAction()
	{
		$configFile = CONFIG_FILE;

		if (isset($_POST['save']) && isset($_POST['config'])) {
			$xml = ivXml::readFromFile($configFile, DEFAULT_CONFIG_FILE);

			$fotomotoNode = $xml->findByXPath('/config/imagevue/fotomoto/enabled');
			if ($fotomotoNode) {
				$oldFotomotoEnabled = $fotomotoNode->getValue();
			}

			$shareNode = $xml->findByXPath('/config/imagevue/misc/sharing/enabled');
			if ($shareNode) {
				$oldShareEnabled = $shareNode->getValue();
			}

			foreach ($_POST['config'] as $path => $value) {
				$node = $xml->findByXPath($path);
				if ($node) {
					$node->setValue(is_array($value) ? implode(',', $value): (string) $value);
				}
			}

// SMTP dirty hack goes here
			$smtp = array();
			if (isset($_POST['config']['/config/imagevue/settings/email/smtp/enabled'])) {
				$smtp['enabled'] = 'true' == $_POST['config']['/config/imagevue/settings/email/smtp/enabled'];
			}
			if (isset($_POST['config']['/config/imagevue/settings/email/smtp/host'])) {
				$smtp['host'] = $_POST['config']['/config/imagevue/settings/email/smtp/host'];
			}
			if (isset($_POST['config']['/config/imagevue/settings/email/smtp/port'])) {
				$smtp['port'] = $_POST['config']['/config/imagevue/settings/email/smtp/port'];
			}
			if (isset($_POST['config']['/config/imagevue/settings/email/smtp/auth'])) {
				$smtp['auth'] = 'true' == $_POST['config']['/config/imagevue/settings/email/smtp/auth'];
			}
			if (isset($_POST['config']['/config/imagevue/settings/email/smtp/username'])) {
				$smtp['username'] = $_POST['config']['/config/imagevue/settings/email/smtp/username'];
			}
			if (isset($_POST['config']['/config/imagevue/settings/email/smtp/password'])) {
				$smtp['password'] = $_POST['config']['/config/imagevue/settings/email/smtp/password'];
			}
			if (isset($_POST['config']['/config/imagevue/settings/email/smtp/secure'])) {
				$smtp['secure'] = ('none' == $_POST['config']['/config/imagevue/settings/email/smtp/secure'] ? '' : $_POST['config']['/config/imagevue/settings/email/smtp/secure']);
			}
			ivMail::saveSmtpConfig($smtp);
// --

			if ($fotomotoNode) {
				$newFotomotoEnabled = $fotomotoNode->getValue();
			}

			if ($fotomotoNode && !$oldFotomotoEnabled && $newFotomotoEnabled && !in_array('fotomoto', $this->conf->get('/config/imagevue/controls/maincontrols/items')) && !in_array('fotomoto', $this->conf->get('/config/imagevue/image/imagebuttons/buttons'))) {
				$node1 = $xml->findByXPath('/config/imagevue/controls/maincontrols/items');
				if ($node1) {
					$node1->setValue(array_merge($this->conf->get('/config/imagevue/controls/maincontrols/items'), array('fotomoto')));
				}

				$node2 = $xml->findByXPath('/config/imagevue/image/imagebuttons/buttons');
				if ($node2) {
					$node2->setValue(array_merge($this->conf->get('/config/imagevue/image/imagebuttons/buttons'), array('fotomoto')));
				}
			}

			if ($shareNode) {
				$newShareEnabled = $shareNode->getValue();
			}

			if ($shareNode && !$oldShareEnabled && $newShareEnabled && !in_array('share', $this->conf->get('/config/imagevue/controls/maincontrols/items')) && !in_array('share', $this->conf->get('/config/imagevue/image/imagebuttons/buttons'))) {
				$node1 = $xml->findByXPath('/config/imagevue/controls/maincontrols/items');
				if ($node1) {
					$node1->setValue(array_merge($this->conf->get('/config/imagevue/controls/maincontrols/items'), array('share')));
				}

				$node2 = $xml->findByXPath('/config/imagevue/image/imagebuttons/buttons');
				if ($node2) {
					$node2->setValue(array_merge($this->conf->get('/config/imagevue/image/imagebuttons/buttons'), array('share')));
				}
			}

			// Check for valid content folder
			$path = ROOT_DIR . ivPath::canonizeRelative($xml->get('/config/imagevue/settings/contentfolder'));
			if (!file_exists($path) || !is_dir($path)) {
				ivMessenger::add(ivMessenger::ERROR, "Wrong value applied for contentfolder: folder " . $xml->get('/config/imagevue/settings/contentfolder') . " does not exists");
			} else {
				$result = $xml->writeToFile();
				if ($result) {
					ivMessenger::add(ivMessenger::NOTICE, 'Configuration successfully saved');
				} else {
					ivMessenger::add(ivMessenger::ERROR, "Can't save configuration file " . substr(CONFIG_FILE, strlen(ROOT_DIR)));
				}
				$this->_redirect($_SERVER['REQUEST_URI']);
			}
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
			$result = false;
			if (isset($_POST['contactTemplate'])) {
				if (strlen(trim($_POST['contactTemplate'])) > 0) {
					$result |= iv_file_put_contents(getEmailTemplatePath('contact', true), $_POST['contactTemplate']);
				} else {
					$result |= (!file_exists(getEmailTemplatePath('contact', true)) || @unlink(getEmailTemplatePath('contact', true)));
				}
			}

			if (isset($_POST['sendlinkTemplate'])) {
				if (strlen(trim($_POST['sendlinkTemplate'])) > 0) {
					$result |= iv_file_put_contents(getEmailTemplatePath('sendlink', true), $_POST['sendlinkTemplate']);
				} else {
					$result |= (!file_exists(getEmailTemplatePath('sendlink', true)) || @unlink(getEmailTemplatePath('sendlink', true)));
				}
			}

			if ($result) {
				ivMessenger::add(ivMessenger::NOTICE, 'Templates successfully saved');
			} else {
				ivMessenger::add(ivMessenger::ERROR, "Can't save templates");
			}
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
			$result = true;

			if (isset($_POST['htmlCss'])) {
				if ((strlen(trim($_POST['htmlCss'])) > 0) && (trim($_POST['htmlCss']) != $stub)) {
					$result &= (boolean) iv_file_put_contents(ROOT_DIR . getCssPath('html', true), $_POST['htmlCss']);
				} else {
					$result &= (!file_exists(ROOT_DIR . getCssPath('html', true)) || @unlink(ROOT_DIR . getCssPath('html', true)));
				}
			}
			if (isset($_POST['flashCss'])) {
				if ((strlen(trim($_POST['flashCss'])) > 0) && (trim($_POST['flashCss']) != $stub)) {
					$result &= (boolean) iv_file_put_contents(ROOT_DIR . getCssPath('flash', true), $_POST['flashCss']);
				} else {
					$result &= (!file_exists(ROOT_DIR . getCssPath('flash', true)) || @unlink(ROOT_DIR . getCssPath('flash', true)));
				}
			}
			if (isset($_POST['mobileCss'])) {
				if ((strlen(trim($_POST['mobileCss'])) > 0) && (trim($_POST['mobileCss']) != $stub)) {
					$result &= (boolean) iv_file_put_contents(ROOT_DIR . getCssPath('mobile', true), $_POST['mobileCss']);
				} else {
					$result &= (!file_exists(ROOT_DIR . getCssPath('mobile', true)) || @unlink(ROOT_DIR . getCssPath('mobile', true)));
				}
			}

			if ($result) {
				ivMessenger::add(ivMessenger::NOTICE, 'Stylesheets successfully saved');
			} else {
				ivMessenger::add(ivMessenger::ERROR, "Can't save stylesheets");
			}

			$this->_redirect($_SERVER['REQUEST_URI']);
		}

		$this->view->assign('htmlCss', (getCssPath('html') ? file_get_contents(ROOT_DIR . getCssPath('html')) : $stub));
		$this->view->assign('flashCss', (getCssPath('flash') ? file_get_contents(ROOT_DIR . getCssPath('flash')) : $stub));
		$this->view->assign('mobileCss', (getCssPath('mobile') ? file_get_contents(ROOT_DIR . getCssPath('mobile')) : $stub));
	}

}