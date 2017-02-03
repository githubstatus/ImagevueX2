<?php
/**
 * Base controller
 *
 * @author McArrow
 */
class ivController extends ivControllerAbstract
{

	/**
	 * Instance of view
	 * @var ivView
	 */
	public $view;

	/**
	 * Config object
	 * @var ivXml
	 */
	public $conf = null;

	/**
	 * Placeholder object
	 * @var ivPlaceholder
	 */
	public $placeholder = null;

	/**
	 * Need render template?
	 * @var boolean
	 */
	private $_needRender = true;

	/**
	 * Need render layout?
	 * @var boolean
	 */
	private $_needLayout = true;

	private $_contentDirPath;

	/**
	 * Constructor
	 *
	 * @param string $path
	 */
	public final function __construct()
	{
		$this->conf = ivPool::get('conf');
		$this->_contentDirPath = ivPath::canonizeRelative($this->conf->get('/config/imagevue/settings/contentfolder'));
		$this->placeholder = ivPool::get('placeholder');
		$this->_init();
	}

	protected function _getContentDirPath()
	{
		return $this->_contentDirPath;
	}

	/**
	 * Initialization method
	 *
	 */
	protected function _init()
	{}

	/**
	 * Sets instance of view
	 *
	 * @return
	 */
	public function setView(ivView $view)
	{
		$this->view = $view;

		return $this;
	}

	/**
	 * Pre-dispatch method. Must be called after constructor
	 *
	 */
	public function _preDispatch()
	{}

	/**
	 * Post-dispatch method. Must be called after action
	 *
	 */
	public function _postDispatch()
	{}

	/**
	 * Disable rendering of template
	 *
	 */
	protected function _setNoRender()
	{
		$this->_needRender = false;
	}

	/**
	 * Return need of rendering template
	 *
	 */
	public function needRender()
	{
		return $this->_needRender;
	}

	/**
	 * Disable rendering of layout
	 *
	 */
	protected function _disableLayout()
	{
		$this->_needLayout = false;
	}

	/**
	 * Return need of rendering layout
	 *
	 */
	public function needLayout()
	{
		return $this->_needLayout;
	}

	/**
	 * Do forward
	 *
	 */
	protected function _forward($actionName, $controllerName = null)
	{
		$frontController = ivControllerFront::getInstance();
		$dispatcher = $frontController->getDispatcher();
		$dispatcher->setActionName($actionName);
		if (!empty($controllerName)) {
			$dispatcher->setControllerName($controllerName);
		}
		$dispatcher->setComplete(false);
	}

	protected function _isXmlHttpRequest()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && ('XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH']));
	}

}