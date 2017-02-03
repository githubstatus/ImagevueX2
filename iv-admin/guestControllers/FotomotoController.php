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
		
				ivErrors::enable();
				ivMessenger::add(ivMessenger::ERROR, 'Responce timeout, try again please');
		
		}
		$this->_redirect('?c=fotomoto&a=edit');
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

			ivMessenger::add(ivMessenger::NOTICE, 'Fotomoto settings changed');

			$this->_redirect('?c=fotomoto&a=edit');
		}

		$this->view->assign('siteKey', $this->conf->get('/config/imagevue/fotomoto/siteKey'));
		$this->view->assign('enabled', $this->conf->get('/config/imagevue/fotomoto/enabled'));
	}

}