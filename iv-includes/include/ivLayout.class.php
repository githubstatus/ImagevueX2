<?php
/**
 * Layout
 *
 * @author McArrow
 */
class ivLayout extends ivView
{

	/**
	 * Page content
	 * @var string
	 */
	private $_content;

	/**
	 * Sets page content
	 *
	 * @param string $content
	 */
	public function setPageContent($content)
	{
		$this->_content = (string) $content;
	}

	/**
	 * Returns page content
	 *
	 * @return unknown
	 */
	public function getPageContent()
	{
		return $this->_content;
	}

}