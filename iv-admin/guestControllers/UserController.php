<?php

class UserController extends ivController
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
		ivPool::get('breadCrumbs')->push('Users', 'index.php?c=user');
		$login = $this->_getParam('login');
		$this->view->assign('user', ivAuth::getCurrentUserLogin());
		$this->view->assign('users', ivPool::get('userManager')->getUsers());
	}

	/**
	 * Add/edit user
	 *
	 */
	public function editAction()
	{
		if (isset($_POST['save']) && is_array($_POST['user'])) {
			$newUser = $_POST['user'];
			if (empty($newUser['access'])) {
				ivMessenger::add(ivMessenger::ERROR, "Set access level please");
				$user = new stdClass();
				$user->login = $_POST['login'];
				$user->access = $newUser['access'];
				$this->view->assign('user', $user);
			} elseif (($newUser['password'] && $newUser['password'] == $_POST['password_confirm']) || empty($newUser['password'])) {
				ivMessenger::add(ivMessenger::NOTICE, "User's data succesfully saved");
				$this->_redirect('index.php?c=user');
			} else {
				ivMessenger::add(ivMessenger::ERROR, "Password doesn't match the confirm password");
				$user = new stdClass();
				$user->login = $_POST['login'];
				$user->access = $newUser['access'];
				$this->view->assign('user', $user);
			}
		}

		ivPool::get('breadCrumbs')->push('Users', 'index.php?c=user');
		if (isset($_GET['login']) && $user = ivPool::get('userManager')->getUser($_GET['login'])) {
			$login = $_GET['login'];
			$this->view->assign('user', $user);
			ivPool::get('breadCrumbs')->push($login, 'index.php?c=user&amp;a=edit&amp;login=' . $login);
		} elseif (isset($_GET['login'])) {
			$login = $_GET['login'];
			ivMessenger::add(ivMessenger::ERROR, "User $login not found");
			$this->_redirect('index.php?c=user');
		} else {
			ivPool::get('breadCrumbs')->push('add', 'index.php?c=user&amp;a=edit');
		}
		$contentFolder = ivMapperFactory::getMapper('folder')->find('');
		$iterator = new ivRecursiveFolderIterator($contentFolder);
		$this->view->assign('folderTreeIterator', new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST));
	}

	/**
	 * Default action
	 *
	 */
	public function deleteAction()
	{
		$login = $this->_getParam('login');
		if (ivAcl::isAdmin($login)) {
			$adminCount = 0;
			foreach (ivPool::get('userManager')->getUsers() as $user) {
				$adminCount += (int) ivAcl::isAdmin($user->login);
			}
			if ($adminCount < 2) {
				ivMessenger::add(ivMessenger::ERROR, "User $login is the last admin user, you can't delete him");
				$this->_redirect('index.php?c=user');
			}
		}
		
		ivMessenger::add(ivMessenger::NOTICE, "User $login succesfully deleted");
	
		$this->_redirect('index.php?c=user');
	}

}
