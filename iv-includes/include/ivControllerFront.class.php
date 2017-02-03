<?php

/**
 * Abstract controller class
 *
 * @author McArrow
 */
class ivControllerFront extends ivControllerAbstract
{

	/**
	 * Dispatcher instance
	 * @var ivControllerDispatcher
	 */
	private $_dispatcher;

	private static $_instance;

	private $_view;

	/**
	 * Constructor
	 *
	 */
	private function __construct()
	{
		$this->_dispatcher = new ivControllerDispatcher();
	}

	public static function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function getView()
	{
		return $this->_view;
	}

	/**
	 * Return dispatcher
	 *
	 */
	public function getDispatcher()
	{
		return $this->_dispatcher;
	}

	/**
	 * Dispatch method
	 *
	 * @param string $basePath
	 */
	public function dispatch($basePath)
	{
// FIXME Debug data
if (!headers_sent()) {
	header('X-FirePHP-Data-100000000001: {');
	header('X-FirePHP-Data-300000000001: "FirePHP.Firebug.Console":[');
	header('X-FirePHP-Data-399999999999: ["__SKIP__"]],');
	header('X-FirePHP-Data-999999999999: "__SKIP__":"__SKIP__"}');
}

		if (get_magic_quotes_gpc()) {
			$_GET = stripslashes_recursive($_GET);
			$_POST = stripslashes_recursive($_POST);
			$_REQUEST = stripslashes_recursive($_REQUEST);
		}

		// Basic routing
		$routingRules = array();
		include($basePath . 'routing.inc.php');
		foreach ($routingRules as $rule) {
			$matched = 0;
			foreach ($rule['match'] as $k => $v) {
				if ((isset($_GET[$k]) && $_GET[$k] == $v)
					|| ('__empty' == $v && (!isset($_GET[$k]) || empty($_GET[$k])))
					|| ('__any' == $v && (isset($_GET[$k]) || !empty($_GET[$k])))) {
					$matched++;
				}
			}
			if (count($rule['match']) == $matched && !isset($controllerName) && !isset($actionName)) {
				$controllerName = (string) $rule['routeTo']['controller'];
				$actionName = (string) $rule['routeTo']['action'];
			}
		}

		if (!isset($controllerName) || !isset($actionName)) {
			$controllerName = (string) ivControllerFront::_getParam('c', 'index');
			$actionName = (string) ivControllerFront::_getParam('a', 'index');
		}

		$this->_dispatcher->setControllerName($controllerName);
		$this->_dispatcher->setActionName($actionName);

		$this->_view = new ivView();
		$this->_view->assign('isXmlHttpRequest', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ('XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'])));
		$this->_view->addTemplatesPath($basePath . 'templates/');
		$this->_view->addTemplatesPath($basePath . 'templates/default/');
		$this->_view->addTemplatesPath($basePath . 'templates/' . SKIN . '/');

		do {
			$this->_dispatcher->dispatch($basePath, $this->_view);
		} while (!$this->_dispatcher->isComplete());

		$controller = $this->_dispatcher->getController();
		$controllerName = $this->_dispatcher->getControllerName();
		$actionName = $this->_dispatcher->getActionName();

		if ($controller->needRender()) {
			header('Content-Type: text/html; charset=utf-8');
			// FIXME Debug data
			xFireDebug('Generation Time ' . getGenTime() . ' sec');
			$pageContent = $this->_view->fetch("{$controllerName}.{$actionName}");

			if ($controller->needLayout()) {
				$layout = new ivLayout();

				$layout->addTemplatesPath($basePath . 'templates/');
				$layout->addTemplatesPath($basePath . 'templates/default/');
				$layout->addTemplatesPath($basePath . 'templates/' . SKIN . '/');

				$layout->assign('moduleName', $controllerName);
				$layout->assign('isXmlHttpRequest', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ('XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'])));
				$layout->setPageContent($pageContent);
				echo $layout->fetch("layout");
			} else {
				echo $pageContent;
			}
		}
	}

}