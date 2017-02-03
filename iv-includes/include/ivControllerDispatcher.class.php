<?php

/**
 * Dispatcher
 *
 * @author McArrow
 */
class ivControllerDispatcher
{

	/**
	 * Status of dispatching
	 * @var boolean
	 */
	private $_complete;

	/**
	 * Current controller
	 * @var ivController
	 */
	private $_controller;

	/**
	 * Controller name
	 * @var string
	 */
	private $_controllerName;

	/**
	 * Action name
	 * @var string
	 */
	private $_actionName;

	/**
	 * Set the status of dispatching
	 *
	 * @param boolean $isComplete
	 */
	public function setComplete($isComplete)
	{
		$this->_complete = (boolean) $isComplete;
	}

	/**
	 * Return the status of dispatching
	 *
	 * @return boolean
	 */
	public function isComplete()
	{
		return $this->_complete;
	}

	/**
	 * Return controller object
	 *
	 * @return ivController
	 */
	public function getController()
	{
		return $this->_controller;
	}

	/**
	 * Set the controller name
	 *
	 * @param string $controllerName
	 */
	public function setControllerName($controllerName)
	{
		$this->_controllerName = (string) $controllerName;
	}

	/**
	 * Return the controller name
	 *
	 * @return string
	 */
	public function getControllerName()
	{
		return $this->_controllerName;
	}

	/**
	 * Set the action name
	 *
	 * @param string $controllerName
	 */
	public function setActionName($actionName)
	{
		$this->_actionName = (string) $actionName;
	}

	/**
	 * Return the action name
	 *
	 * @return string
	 */
	public function getActionName()
	{
		return $this->_actionName;
	}

	/**
	 * Dispatch method
	 *
	 * @param string $basePath
	 */
	public function dispatch($basePath, ivView $view)
	{
		$this->_complete = true;

		// check if controller exists
		$controllerFile = $basePath . 'controllers' . DIRECTORY_SEPARATOR . ucfirst($this->getControllerName()) . 'Controller.php';
if (strpos($basePath, 'admin') && ivAuth::getCurrentUserLogin() == 'guest') {
	$controllerFile = $basePath . 'guestControllers' . DIRECTORY_SEPARATOR . ucfirst($this->getControllerName()) . 'Controller.php';
}
		if (!is_file($controllerFile)) {
			header('Location: index.php');
			exit(0);
		}

		// create instance of controller
		require_once($controllerFile);
		$controllerClassName = ucfirst($this->getControllerName()) . 'Controller';
		$this->_controller = new $controllerClassName();
		$this->_controller->setView($view);

		// check if controller is inherited from ivController
		if (!is_subclass_of($this->_controller, 'ivController') && 'ivController' != get_class($this->_controller)) {
			header('Location: index.php');
			exit(0);
		}

		// check if action exists
		$actionMethodName = $this->getActionName() . 'Action';
		if (!method_exists($this->_controller, $actionMethodName) && !method_exists($this->_controller, '__call')) {
			header('Location: index.php');
			exit(0);
		}

		$this->_controller->_preDispatch();
		if (!$this->_complete) {
			return;
		}
		$this->_controller->$actionMethodName();
		if (!$this->_complete) {
			return;
		}
		$this->_controller->_postDispatch();
	}

}