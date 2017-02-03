<?php

class DashboardController extends ivController
{

	public function _preDispatch()
	{
		if (ivPool::get('conf')->get('/config/imagevue/settings/disableDashboard')) {
			$this->_redirect('index.php');
		}

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
		$crumbs->push('Dashboard', 'index.php?c=dashboard');
	}

}