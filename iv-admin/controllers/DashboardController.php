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
		$this->view->assign('rss_feed', $this->getRSSFeed());
	}

	private function getRSSFeed($feedURL='http://imagevuex.com/feed/') {
		$xmlDoc = new DOMDocument();
		$xmlDoc->load($feedURL);
		$items=array();
		$x=$xmlDoc->getElementsByTagName('item');
		if($x->length>0)
			for ($i=0; $i<4; $i++)
			{
				$item['title']=$x->item($i)->getElementsByTagName('title')
				->item(0)->childNodes->item(0)->nodeValue;
				$item['date']=date('d M', strtotime($x->item($i)->getElementsByTagName('pubDate')
				->item(0)->childNodes->item(0)->nodeValue));
				$item['link']=$x->item($i)->getElementsByTagName('link')
				->item(0)->childNodes->item(0)->nodeValue;
				$item['desc']=$x->item($i)->getElementsByTagName('description')
				->item(0)->childNodes->item(0)->nodeValue;
				$items[]=$item;
			}
		return $items;
	}

}