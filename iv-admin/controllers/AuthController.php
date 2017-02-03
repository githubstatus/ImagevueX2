<?php

class AuthController extends ivController
{

	/**
	 * Pre-dispatching
	 *
	 */
	public function _preDispatch()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Authorization', '?c=auth');

		if (!ivAcl::isAdmin()) {
			$this->_forward('login', 'cred');
			if (ivAuth::getCurrentUserLogin()) {
				ivMessenger::add(ivMessenger::ERROR, "You don't have access to this page");
			}
			return;
		}
	}

	/**
	 * Authorize
	 *
	 */
	public function indexAction()
	{}

	/**
	 * Done
	 *
	 */
	public function doneAction()
	{
		$this->_disableLayout();
		$this->_setNoRender();
	}

}