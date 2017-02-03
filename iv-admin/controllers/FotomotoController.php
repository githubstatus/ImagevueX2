<?php

class FotomotoController extends ivController
{

	/**
	 * Pre-dispatching
	 *
	 */
	public function _preDispatch()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Fotomoto', '?c=fotomoto');

		if (!ivAcl::isAdmin()) {
			$this->_forward('login', 'cred');
			if (ivAuth::getCurrentUserLogin()) {
				ivMessenger::add(ivMessenger::ERROR, "You don't have access to this page");
			}
			return;
		}
	}

	/**
	 * Create fotomoto account
	 *
	 */
	public function indexAction()
	{
		if ($this->conf->get('/config/imagevue/fotomoto/siteKey')) {
			$this->_forward('edit');
		}

		if (!empty($_POST)) {
			$options = array(
				'http' => array(
					'method' => 'POST',
					'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
					'content' => http_build_query($_POST),
					'timeout' => 25,
					'ignore_errors' => true
				)
			);

			$context = stream_context_create($options);

			ivErrors::disable();
			$r = file_get_contents('http://affiliate.fotomoto.com/osignup.json', false, $context);
			if (false !== $r) {
				$response = json_decode($r);
				ivErrors::enable();

				if (isset($response->status) && 200 == $response->status && isset($response->site_key)) {
					$xml = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE);

					$node = $xml->findByXPath('/config/imagevue/fotomoto/siteKey');
					if ($node) {
						$node->setValue((string) $response->site_key);
						$xml->writeToFile();
					}

					$this->_redirect('?c=fotomoto');
				} else if (isset($response->error)) {
					ivMessenger::add(ivMessenger::ERROR, $response->error);
				} else {
					ivMessenger::add(ivMessenger::ERROR, 'Fill all form fields please');
				}
			} else {
				ivErrors::enable();
				ivMessenger::add(ivMessenger::ERROR, 'Responce timeout, try again please');
			}
		}
	}

	/**
	 * Edit fotomoto account
	 *
	 */
	public function editAction()
	{
		$crumbs = ivPool::get('breadCrumbs');

		if ($crumbs->count() > 1) {
			$crumbs->pop();
		}

		$crumbs->push('Edit', '?c=fotomoto&a=edit');

		if (isset($_POST['fotomoto'])) {
			$xml = ivXml::readFromFile(CONFIG_FILE, DEFAULT_CONFIG_FILE);

			$node = $xml->findByXPath('/config/imagevue/fotomoto/siteKey');
			if ($node) {
				$node->setValue((string) $_POST['fotomoto']['siteKey']);
			}

			$node = $xml->findByXPath('/config/imagevue/fotomoto/enabled');
			if ($node) {
				$node->setValue((string) $_POST['fotomoto']['enabled']);

				if (!in_array('fotomoto', $this->conf->get('/config/imagevue/controls/maincontrols/items')) && !in_array('fotomoto', $this->conf->get('/config/imagevue/image/imagebuttons/buttons'))) {
					$node1 = $xml->findByXPath('/config/imagevue/controls/maincontrols/items');
					if ($node1) {
						$node1->setValue(array_merge($this->conf->get('/config/imagevue/controls/maincontrols/items'), array('fotomoto')));
					}

					$node2 = $xml->findByXPath('/config/imagevue/image/imagebuttons/buttons');
					if ($node2) {
						$node2->setValue(array_merge($this->conf->get('/config/imagevue/image/imagebuttons/buttons'), array('fotomoto')));
					}
				}
			}

			$xml->writeToFile();

			ivMessenger::add(ivMessenger::NOTICE, 'Fotomoto settings changed');

			$this->_redirect('?c=fotomoto&a=edit');
		}

		$this->view->assign('siteKey', $this->conf->get('/config/imagevue/fotomoto/siteKey'));
		$this->view->assign('enabled', $this->conf->get('/config/imagevue/fotomoto/enabled'));
	}

}