<?php

class DiagController extends ivController
{

	/**
	 * Pre-dispatching
	 *
	 */
	public function _preDispatch()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Diagnostics', '?c=diag');

		if (!ivAcl::isAdmin()) {
			$this->_forward('login', 'cred');
			if (ivAuth::getCurrentUserLogin()) {
				ivMessenger::add(ivMessenger::ERROR, "You don't have access to this page");
			}
			return;
		}
	}

	/**
	 * File system diagnostic
	 *
	 */
	public function indexAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('File system');

		clearstatcache();
		$iterator = new RecursiveDirectoryIterator(ROOT_DIR);
		$dotFilter = new ivRecursiveFilterIteratorDot($iterator);
		$excludeFilter = new ivRecursiveFilterIteratorExclude($dotFilter);
		$iteratorIterator = new RecursiveIteratorIterator($excludeFilter, RecursiveIteratorIterator::SELF_FIRST);

		$this->view->assign('iterator', $iteratorIterator);
	}

	/**
	 * Error diagnostic
	 *
	 */
	public function errorsAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('PHP check', '?c=diag&amp;a=errors');
	}

	/**
	 * Apache modules check
	 *
	 */
	public function apachemodAction()
	{
		$modules = function_exists('apache_get_modules') ? apache_get_modules() : array();
		$this->view->assign('modules', $modules);

		$badModules = array(
			'mod_security' => "With enabled 'mod_security' apache module you will be unavailable to use some functions in admin panel",
			'mod_security2' => "With enabled 'mod_security2' apache module you will be unavailable to use some functions in admin panel",
		);
		$this->view->assign('badModules', $badModules);

		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('Apache modules check', '?c=diag&amp;a=apachemod#error');
	}

	/**
	 * PHPInfo page
	 *
	 */
	public function phpinfoAction()
	{
		$crumbs = ivPool::get('breadCrumbs');
		$crumbs->push('PHP Info', '?c=diag&amp;a=phpinfo');
	}

	/**
	 * Post dispatching
	 *
	 */
	public function _postDispatch()
	{}

}

class ivRecursiveFilterIteratorExclude extends RecursiveFilterIterator
{

	private $_excludeFolders = array(
		'iv-includes/assets',
		'iv-includes/com',
		'iv-includes/controllers',
		'iv-includes/css',
		'iv-includes/dtree',
		'iv-includes/images',
		'iv-includes/include',
		'iv-includes/js',
		'iv-includes/lightbox',
		'iv-includes/swf',
		'iv-includes/templates',
		'iv-includes/tests',
	);

	private $_folders = array(
		'content',
		'iv-includes',
		'iv-config',
	);

	public function __construct(RecursiveIterator $iterator)
	{
		parent::__construct($iterator);
		$this->_excludeFolders[] = trim(ivPool::get('conf')->get('/config/imagevue/settings/adminfolder'), DS);
		$this->_folders[] = trim(ivPool::get('conf')->get('/config/imagevue/settings/adminfolder'), DS);
	}

	public function accept()
	{
		$foldersRegexp = '#^(' . implode('|', array_map(create_function('$s', 'return preg_quote($s, "#");'), $this->_folders)) . ')(\/|$)#';
		if ($this->current()->isFile()) {
			if ('users.php' === $this->current()->getFilename() || '.xml' === substr($this->current()->getFilename(), -4)) {
				return true;
			}
			return false;
		} else {
			$path = str_replace('//', '/', str_replace('\\', '/', substr($this->current()->getPathname(), strlen(ROOT_DIR))));
			if (!preg_match($foldersRegexp, $path)) {
				return false;
			}
			foreach ($this->_excludeFolders as $curPath) {
				if (substr($path, 0, strlen($curPath)) === $curPath) {
					return false;
				}
			}
			return true;
		}
	}

}

?>