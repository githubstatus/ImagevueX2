<?php

require_once(INCLUDE_DIR . 'ivPhpdocParser.class.php');

class XmlController extends ivController
{

	/**
	 * Just lists all actions and their descriptions
	 *
	 */
	public function indexAction()
	{
		$this->_disableLayout();
		$actions = array();
		$parser = new ivPhpdocParser();
		$handle = opendir(CONTROLLERS_DIR);
		while (false !== ($file = readdir($handle))) {
			if (is_file(CONTROLLERS_DIR . $file) && $file != "IndexController.php") {
				$fileContents = file_get_contents(CONTROLLERS_DIR . $file);
				$matches = array();
				preg_match('/^.*?class\s+(\w+)/m', $fileContents, $matches);
				$controllerName = strtolower(substr($matches[1], 0, -10));
				$methods = $parser->getMethodsData($fileContents);
				foreach ($methods as $methodName => $methodDesc) {
					if ('Controller' == substr($matches[1], -10) && 'Action' == substr($methodName, -6)) {
						$actions[$controllerName][substr($methodName, 0, -6)] = $methodDesc;
					}
				}
			}
		}
		closedir($handle);
		$this->view->assign('actions', $actions);
	}

	/**
	 * Downloads given file
	 *
	 */
	public function downloadAction()
	{
		$this->_setNoRender();
		$file = ivMapperFactory::getMapper('file')->find(ivPath::canonizeRelative($this->_getParam('path', null, 'path'), true));
		if ($file) {
			$path = ROOT_DIR . $this->conf->get('/config/imagevue/settings/contentfolder') . $file->getPrimary();
			$data = @getimagesize($path);

			// FIXME Debug data
			xFireDebug('Generation Time ' . getGenTime() . ' sec');

			// Fix for IE From http://php.net/manual/en/function.header.php#83384
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Content-Type: application/force-download");
			header("Content-Type: application/octet-stream");
			header("Content-Type: application/download");
			if (isset($data['mime'])) {
				header("Content-Type: {$data['mime']}");
			}
			header('Content-Disposition: attachment; filename=' . basename($path) . ';');
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . filesize($path));
			readfile($path);
		}
	}

	/**
	 * Return information on given file
	 *
	 */
	public function fileinfoAction()
	{
		$this->_setNoRender();
		$file = ivMapperFactory::getMapper('file')->find(ivPath::canonizeRelative($this->_getParam('path', null, 'path'), true));
		if ($file) {
			$xml = new ivXml();
			$fileNode = $file->asXml();
			$xml->setNodeTree($fileNode);
			// FIXME Debug data
			xFireDebug('Generation Time ' . getGenTime() . ' sec');
			header('Content-type: text/xml; charset=utf-8');
			echo $xml->toString();
		}
	}

	/**
	 * Returns random image from given folder
	 *
	 */
	public function rndimgAction()
	{
		$this->_setNoRender();
		$path = ivPath::canonizeRelative($this->_getParam('path', null, 'path'));
		$folder = ivMapperFactory::getMapper('folder')->find($path);
		if ($folder && $folder->checkPassword($this->_getParam('password'))) {
			$collection = $folder->Files;
			if (isset($_GET['ext'])) {
				$collection = $collection->filter(new ivFilterExtension($_GET['ext']));
			} else {
				$collection = $collection->filter(new ivFilterExtension($this->conf->get('/config/imagevue/settings/includefilesext')));
			}
			if (isset($_GET['excludefilesprefix'])) {
				$collection = $collection->filter(new ivFilterPrefix($_GET['excludefilesprefix']));
			}
			if (count($collection)) {
				$selected = $collection[rand(0, count($collection) - 1)];
				$xml = new ivXml();
				$fileNode = $selected->asXml();
				$xml->setNodeTree($fileNode);
				// FIXME Debug data
				xFireDebug('Generation Time ' . getGenTime() . ' sec');
				header('Content-type: text/xml; charset=utf-8');
				echo $xml->toString();
			}
		}
	}

	/**
	 * Return thumbnail for given path
	 *
	 */
	public function thumbAction()
	{
		$errorReporting = error_reporting(0);
		$this->_setNoRender();

		$contentFolder = ivPath::canonizeRelative($this->conf->get('/config/imagevue/settings/contentfolder'));

		$path = $this->_getParam('path', $contentFolder, 'path');

		$record = ivMapperFactory::getMapper('file')->find($path);
		if (!$record) {
			$record = ivMapperFactory::getMapper('folder')->find($path);
		}

		if ($record) {
			$defaultThumbPath = ROOT_DIR . $record->thumbnail;

			if ((ivPath::canonizeRelative(substr($record->thumbnail, 0, strlen($contentFolder))) !== $contentFolder)) {
				$record->generateThumbnail();
			}

			$thumbPath = ROOT_DIR . $record->thumbnail;
			if (!file_exists($thumbPath)) {
				$thumbPath = $defaultThumbPath;
			}
			$data = @getimagesize($thumbPath);
			if (isset($data['mime'])) {
				// FIXME Debug data
				xFireDebug('Generation Time ' . getGenTime() . ' sec');
				header('Cache-Control: public');
				header('Expires: Fri, 30 Dec 2099 19:30:56 GMT');
				header('Content-Type: ' . $data['mime']);
				readfile($thumbPath);
			}
		}
		error_reporting($errorReporting);
	}

	/**
	 * Return config
	 *
	 */
	public function configAction()
	{
		$this->_setNoRender();
		if ('link' == $this->_getParam('path')) {
			if (file_exists(ROOT_DIR . 'mylink.ini')) {
				echo file_get_contents(ROOT_DIR . 'mylink.ini');
			}
		} else {
			$themeName = $this->_getParam('theme', $this->conf->get('/config/imagevue/settings/theme'), 'alnum');
			$theme = ivThemeMapper::getInstance()->find($themeName);
			if (!$theme) {
				$theme = ivThemeMapper::getInstance()->find($this->conf->get('/config/imagevue/settings/theme'));
			}
			if ($theme) {
				$xml = $theme->getFullConfig();
				$xml->set('/config/imagevue/style/stylesheet', $theme->getRelativePath() . $xml->get('/config/imagevue/style/stylesheet'));
				$xml->set('/config/imagevue/style/background_image/url', $theme->getRelativePath() . $xml->get('/config/imagevue/style/background_image/url'));
				$xml->set('/config/imagevue/style/background_image_2/url', $theme->getRelativePath() . $xml->get('/config/imagevue/style/background_image_2/url'));
				$xml->set('/config/imagevue/style/background_image_3/url', $theme->getRelativePath() . $xml->get('/config/imagevue/style/background_image_3/url'));
				// FIXME Debug data
				xFireDebug('Generation Time ' . getGenTime() . ' sec');
				header('Content-type: text/xml; charset=UTF-8');
				$xmlString = $xml->toString(true);
				$xmlString = preg_replace('/\>[\s\r\n]*\</', '><', $xmlString);
				echo $xmlString;
			}
		}
	}

	/**
	 * Returns language
	 *
	 */
	public function languageAction()
	{
		$this->_setNoRender();
		$langName = mb_strtolower($this->_getParam('name', $this->conf->get('/config/imagevue/settings/language'), 'alnum'), 'UTF-8');
		if (in_array($langName, ivLanguage::getAllLanguageNames())) {
			setcookie('language', $langName, 0, '/');

			$xml = ivLanguage::getLanguage($langName);
			// FIXME Debug data
			xFireDebug('Generation Time ' . getGenTime() . ' sec');
			header('Content-type: text/xml; charset=UTF-8');
			$xmlString = $xml->toString(true);
			$xmlString = preg_replace('/\>[\s\r\n]*\</', '><', $xmlString);
			echo $xmlString;
		}
	}

	/**
	 * Returns an XML files list from the given folder path parameter
	 *
	 */
	public function filesAction()
	{
		$this->_setNoRender();
		$path = ivPath::canonizeRelative($this->_getParam('path', $this->conf->get('/config/imagevue/settings/contentfolder'), 'path'));
		$folder = ivMapperFactory::getMapper('folder')->find($path);

		if ($folder) {
			$xml = new ivXml();
			$folderNode = $folder->asXml();
			$folderNode->setAttribute('maxThumbWidth', $folder->maxThumbWidth);
			$folderNode->setAttribute('maxThumbHeight', $folder->maxThumbHeight);
			if ($folder->checkPassword($this->_getParam('password'))) {
				$folderNode->setAttribute('pageContent', preg_replace('/(?<=\&gt\;|\>)[\r\n]+?(?=\&lt\;|\<)/ims', '', $this->_parseLinksForFlash(preg_replace('/(\&lt\;|\<)\/p(\&gt\;|\>)\s*(\&lt\;|\<)p(\&gt\;|\s|\>)/ims', "$1/p$2\r\n$3p$4", preg_replace('/[\r\n]+/', "\n", $folderNode->getAttribute('pageContent'))))));
				$folderNode->removeAttribute('password');
			} else {
				$folderNode->removeAttribute('pageContent');
				$folderNode->setAttribute('password', 'true');
			}
			$folderNode->setAttribute('description', $this->_parseLinksForFlash($folderNode->getAttribute('description')));
			$titlePath = urlencode(t($folder->getTitle()));
			$parentFolder = $folder->Parent;
			while ($parentFolder) {
				$titlePath = urlencode(t($parentFolder->getTitle())) . '/' . $titlePath;
				$parentFolder = $parentFolder->Parent;
			}
			$folderNode->setAttribute('titlePath', $titlePath);

			$errors = ivMessenger::get(ivMessenger::ERROR);
			if (!empty($errors)) {
				$errorString = trim(implode(',', array_unique($errors)));
				if (!empty($errorString)) {
					$folderNode->setAttribute('errors', $errorString);
				}
			}

			$xml->setNodeTree($folderNode);

			if ($folder->checkPassword($this->_getParam('password'))) {
				$collection = $folder->Files;
				if (isset($_GET['ext'])) {
					$collection = $collection->filter(new ivFilterExtension($_GET['ext']));
				} else {
					$collection = $collection->filter(new ivFilterExtension($this->conf->get('/config/imagevue/settings/includefilesext')));
				}
				if (isset($_GET['excludefilesprefix'])) {
					$collection = $collection->filter(new ivFilterPrefix($_GET['excludefilesprefix']));
				}
				if ('rnd' == $this->_getParam('sort')) {
					$collection = $collection->shuffle();
				}
				$contentPath = ivPath::canonizeRelative($this->conf->get('/config/imagevue/settings/contentfolder'));
				foreach ($collection as $file) {
					$fileNode = $file->asXml(false);

					if ((ivPath::canonizeRelative(substr($file->thumbnail, 0, strlen($contentPath))) !== $contentPath)) {
						$fileNode->setAttribute('thumbnail', '?a=thumb&path=' . smart_urlencode($file->getPrimary()));
					}

					$fileNode->setAttribute('description', $this->_parseLinksForFlash($fileNode->getAttribute('description')));
					$folderNode->addChild($fileNode);
				}
			}

			// FIXME Debug data
			xFireDebug('Generation Time ' . getGenTime() . ' sec');
			header ('Content-type: text/xml; charset=utf-8');
			echo $xml->toString();
		}
	}

	/**
	 * Returns an XML folder hierarchy reference from the given path parameter.
	 *
	 */
	public function foldersAction()
	{
		$this->_setNoRender();

		$path = ivPath::canonizeRelative($this->_getParam('path', '', 'path'));
		$folder = ivMapperFactory::getMapper('folder')->find($path);
		if ($folder) {
			$tree = $this->_getFolderTreeXml($folder, $this->_getParam('showhidden', null, 'bool'), $this->_getParam('password'));

			$errors = ivMessenger::get(ivMessenger::ERROR);
			if (!empty($errors)) {
				$errorString = trim(implode(',', array_unique($errors)));
				if (!empty($errorString)) {
					$tree->setAttribute('errors', $errorString);
				}
			}

			$xml = new ivXml();
			$xml->setNodeTree($tree);
			// FIXME Debug data
			xFireDebug('Generation Time ' . getGenTime() . ' sec');
			header('Content-type: text/xml; charset=utf-8');
			echo $xml->toString();
		}
	}

	/**
	 * Returns folder tree starts from given
	 *
	 * Recursive
	 *
	 * @param  ivFolder $folder
	 * @param  boolean  $showHidden
	 * @param  string   $password
	 * @return ivXmlNode
	 */
	private function _getFolderTreeXml(ivFolder $folder, $showHidden = false, $password = '')
	{
		$node = $folder->asXml();
		$node->removeAttribute('pageContent');
		foreach ($folder->Folders as $childFolder) {
			if (false === $showHidden && $childFolder->isHidden() || !$childFolder->showInFlash) {
				continue;
			}
			$childTree = $this->_getFolderTreeXml($childFolder, $showHidden, $password);
			$node->addChild($childTree);
		}
		return $node;
	}

	/**
	 * Sends a contact email and returns status of operation
	 *
	 */
	public function contactAction()
	{
		$lang = ivPool::get('lang');
		$this->_setNoRender();
		if ($this->conf->get('/config/imagevue/settings/email/allowEmail')) {

			if ( !( $this->conf->get('/config/imagevue/settings/email/ownerEmail') ) ) {
				echo 'success=' . $lang->get('/lang/empty_owneremail');
				exit(0);
			}

			$phs = array('link' => '');

			$path = substr(ivPath::canonizeRelative($this->_getParam('path')),0, -1);
			$image=ivMapperFactory::getMapper('file')->find($path);
			if ($image) {

				$uri = getenv('REQUEST_URI');
				if (false !== strpos($uri, '?')) {
					$uri = substr($uri, 0, strpos($uri, '?'));
				}
				if (false !== strrpos($uri, '/')) {
					$uri = substr($uri, 0, strrpos($uri, '/') + 1);
				}
				$galleryURL = 'http://' . getenv('HTTP_HOST') . $uri;
				$directory = $this->conf->get('/config/imagevue/settings/contentfolder') . ivFilepath::directory($path);
				$filename=ivFilepath::filename($path);
					$phs['link']="<a href='{$galleryURL}imagevue.php?share={$path}'><img style='border: 1px solid #ccc; padding: 1px;' src='{$galleryURL}{$directory}tn_{$filename}.jpg'/></a><br/>";

			}

			if (strip_tags($this->_getParam('messageBody')) != $this->_getParam('messageBody') || preg_match('/\[\w/', $this->_getParam('messageBody')) || strlen(trim($this->_getParam('lastname')))) {
				// spam
				echo 'success=true';
				exit(0);
			}

			$messageBody = secureVar($this->_getParam('messageBody'));
			if (empty($messageBody)) {
				echo 'success=' . $lang->get('/lang/empty_message');
				exit(0);
			}
			$phs['messageBody'] = $messageBody;

			$senderName = secureVar($this->_getParam('senderName'));
			$phs['senderName'] = $senderName;

			$senderEmail = secureVar($this->_getParam('senderEmail'));
			if (!checkMail($senderEmail)) {
				echo 'success=' . $lang->get('/lang/bad_email');
				exit(0);
			}
			$phs['senderEmail'] = $senderEmail;

			$template = file_get_contents(getEmailTemplatePath('contact'));
			if (false === $template) {
				echo 'success=' . $lang->get('/lang/cannot_open_template');
				exit(0);
			}

			$subject = $this->conf->get('/config/imagevue/settings/email/contactSubj');

			$mail = new ivMail();
			$mail->setFrom($senderEmail, $senderName);
			$mail->setForceFrom($this->conf->get('/config/imagevue/settings/email/forceFrom'));

			foreach ($this->conf->get('/config/imagevue/settings/email/ownerEmail') as $ownerEmail) {

				$mail->addTo($ownerEmail);
			}
			$mail->setSubject($this->_fillPlaceholders($subject, $phs));
			$mail->setBody($this->_fillPlaceholders($template, $phs));

			if ($mail->send()) {
				echo 'success=true';
			} else {
				echo 'success=' . ($mail->getLastError() ? $mail->getLastError() : $lang->get('/lang/could_not_mail'));
			}
		} else {
			echo 'success=' . $lang->get('/lang/email_disabled');
		}
	}

	/**
	 * Sends a link to image and returns status of operation
	 *
	 */
	public function sendlinkAction()
	{
		$lang = ivPool::get('lang');
		$this->_setNoRender();
		if ($this->conf->get('/config/imagevue/settings/email/allowEmail')) {
			$uri = getenv('REQUEST_URI');
			if (false !== strpos($uri, '?')) {
				$uri = substr($uri, 0, strpos($uri, '?'));
			}
			if (false !== strrpos($uri, '/')) {
				$uri = substr($uri, 0, strrpos($uri, '/') + 1);
			}
			$phs = array('galleryURL' => 'http://' . getenv('HTTP_HOST') . $uri);

			$path = ivPath::canonizeRelative($this->_getParam('path', null, 'path'), true);
			if (!empty($path)) {
				$phs['path'] = $path;
				$phs['directory'] = $this->conf->get('/config/imagevue/settings/contentfolder') . ivFilepath::directory($path);
				$phs['file'] = ivFilepath::basename($path);
				$phs['filename'] = ivFilepath::filename($path);

				$senderName = secureVar($this->_getParam('senderName'));
				$phs['senderName'] = $senderName;

				$senderEmail = secureVar($this->_getParam('senderEmail'));
				$phs['senderEmail'] = $senderEmail;

				$receiverName = secureVar($this->_getParam('receiverName'));
				$phs['receiverName'] = $receiverName;

				$receiverEmail = secureVar($this->_getParam('receiverEmail'));
				$phs['receiverEmail'] = $receiverEmail;

				if (strip_tags($this->_getParam('messageBody')) != $this->_getParam('messageBody') || preg_match('/\[\w/', $this->_getParam('messageBody')) || strlen(trim($this->_getParam('lastname')))) {
					// spam
					echo 'success=true';
					exit(0);
				}

				$messageBody = secureVar($this->_getParam('messageBody'));
				$phs['messageBody'] = $messageBody;

				if (!checkMail($senderEmail) || !checkMail($receiverEmail)) {
					echo 'success=' . $lang->get('/lang/bad_email');
					exit(0);
				}
				$file = ivMapperFactory::getMapper('file')->find($path);
				if ($file) {
					$template = file_get_contents(getEmailTemplatePath('sendlink'));
					if (false === $template) {
						echo 'success=' . $lang->get('/lang/cannot_open_template');
						exit(0);
					}

					$subject = $this->conf->get('/config/imagevue/settings/email/sendlinkSubj');

					$phs['galleryURL'] = str_replace(' ', '%20', $phs['galleryURL']);
					$phs['path'] = str_replace(' ', '%20', $phs['path']);
					$phs['directory'] = str_replace(' ', '%20', $phs['directory']);
					$phs['file'] = str_replace(' ', '%20', $phs['file']);
					$phs['filename'] = str_replace(' ', '%20', $phs['filename']);

					$mail = new ivMail();
					$mail->setFrom($senderEmail, $senderName);
					$mail->setForceFrom($this->conf->get('/config/imagevue/settings/email/forceFrom'));
					$mail->addTo($receiverEmail, $receiverName);
					$mail->setSubject($this->_fillPlaceholders($subject, $phs));
					$mail->setBody($this->_fillPlaceholders($template, $phs));

					if ($mail->send()) {
						echo 'success=true';
					} else {
						echo 'success=' . ($mail->getLastError() ? $mail->getLastError() : $lang->get('/lang/could_not_mail'));
					}

					if ($this->conf->get('/config/imagevue/settings/email/ownerBcc')) {
						$mail = new ivMail();
						$mail->setFrom($senderEmail, $senderName);
						$mail->addTo($this->conf->get('/config/imagevue/settings/email/ownerEmail'));
						$mail->setSubject($this->_fillPlaceholders($subject, $phs));
						$mail->setBody($this->_fillPlaceholders($template, $phs));
						$mail->send();
					}
				} else {
					echo 'success=' . $lang->get('/lang/no_such_pic');
				}
			} else {
				echo 'success=' . $lang->get('/lang/path_is_empty');
			}
		} else {
			echo 'success=' . $lang->get('/lang/email_disabled');
		}
	}

	public function checkpassAction()
	{
		$this->_setNoRender();
		$path = ivPath::canonizeRelative($this->_getParam('path', $this->conf->get('/config/imagevue/settings/contentfolder'), 'path'));
		$folder = ivMapperFactory::getMapper('folder')->find($path);

		if ($folder && $folder->checkPassword((isset($_POST['password']) ? $_POST['password'] : null))) {
			echo 'true';
		} else {
			echo 'false';
		}
		exit(0);
	}

	/**
	 * Replace placeholders for emailing
	 *
	 * @param  string $string
	 * @param  array  $phs
	 * @return string
	 */
	private function _fillPlaceholders($string, $phs)
	{
		foreach ($phs as $ph => $value) {
			$string = str_replace("[$ph]", $value, $string);
		}
		return $string;
	}

	/**
	 * Replace internal hyperlinks for swfaddress
	 *
	 * @param  string $html
	 * @return string
	 */
	private function _parseLinksForFlash($html)
	{
		// Parse links
		$html = preg_replace('/href\=\"([\#]\/)(.*?)\"/', 'href="asfunction:_root.link,$2"', $html);

		// Parse paragraphs

		$html = preg_replace_callback('/\<p[^\>]*\>/', array($this, '_callback'), $html);
		$html = preg_replace('/\<\/p([^\>]*)>/', '</span></p$1>', $html);
		$html = str_replace (array('<ol>','</ol>','<ul>','</ul>','<li>', '<font style="color: '), array('<p class="textpage"><ol>','</ol></p>','<p class="textpage"><ul>','</ul></p>','<li class="textpage_body">', '<font color="'), $html);

		return $html;
	}

	private function _callback($m)
	{
		$tag = $m[0];
		if (!preg_match('/^\<p[\W]/', $tag)) {
			return $tag;
		}
		if (!preg_match('/^\<p.*?class\=\"?.*?/', $tag)) {
			$tag = preg_replace('/^\<p(.*?)\>/', '<p$1 class="textpage"><span class="textpage_body">', $tag);
			return $tag;
		}
		$tag = preg_replace('/^\<p(.*?)class\=\"(.*?)\>/', '<p$1class="textpage $2><span class="textpage_body">', $tag);
		return $tag;
	}

}