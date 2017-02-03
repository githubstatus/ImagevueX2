<?php

/**
 * Mail class
 *
 * @author McArrow
 */
class ivMail
{

	/**
	 * Email body
	 * @var string
	 */
	private $_body = '';

	/**
	 * Array of recipients
	 * @var array
	 */
	private $_to = array();

	/**
	 * Sender name and email
	 * @var array
	 */
	private $_from = null;

	/**
	 * Email subject
	 * @var string
	 */
	private $_subject = '';

	/**
	 * Last error
	 * @var string
	 */
	private $_lastError = false;

	/**
	 * Force from email
	 * @var string
	 */
	private $_forceFrom;

	private static $_defaultConfig = array(
		'enabled' => array(
			'value' => false,
			'type' => 'boolean',
			'description' => '<strong>Note:</strong> Do not enable SMTP if your email work without it. SMTP support is provided mainly for servers that are unable to send email normally.',
		),
		'host' => array(
			'value' => 'smtp.yourdomain.com',
			'type' => 'string',
			'description' => 'SMTP hosts. All hosts must be separated by a semicolon. You can also specify a different port for each host by using this format: [hostname:port]	(e.g. "smtp1.example.com:25;smtp2.example.com"). Hosts will be tried in order',
		),
		'username' => array(
			'value' => 'yourname@yourdomain',
			'type' => 'string',
			'description' => 'SMTP username',
		),
		'password' => array(
			'value' =>'yourpassword',
			'type' => 'string',
			'description' => 'SMTP password',
		),
		'port' => array(
			'value' => 25,
			'type' => 'integer',
			'description' => 'Default SMTP server port, usually 25. (Gmail SSL: 465 / TLS: 587)',
		),
		'secure' => array(
			'value' => '',
			'type' => 'string',
			'description' => 'Connection prefix. Options are "", "SSL" or "TLS"',
		),
		'auth' => array(
			'value' => true,
			'type' => 'boolean',
			'description' => 'SMTP authentication. Utilizes the username and password values',
		),
		'timeout' => array(
			'value' => 10,
			'type' => 'integer',
			'description' => 'SMTP server timeout in seconds. This function will not work with the win32 version',
		),
		'helo' => array(
			'value' => '',
			'type' => 'string',
			'description' => 'SMTP HELO of the message (host used by default)',
		),
	);

	/**
	 * Returns email body
	 *
	 * @return string
	 */
	public function getBody()
	{
		return $this->_body;
	}

	/**
	 * Sets email body
	 *
	 * @param string $body
	 */
	public function setBody($body)
	{
		$this->_body = $body;
	}

	/**
	 * Returns recipients
	 *
	 * @return array
	 */
	public function getTo()
	{
		return $this->_to;
	}

	/**
	 * Adds recipient
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function addTo($email, $name = '')
	{
		$this->_to[$email] = $name;
	}

	/**
	 * Clears recipients
	 *
	 */
	public function clearTo()
	{
		$this->_to = array();
	}

	/**
	 * Returns sender
	 *
	 * @return array|null
	 */
	public function getFrom()
	{
		return $this->_from;
	}

	/**
	 * Sets sender
	 *
	 * @param string $email
	 * @param string $name
	 */
	public function setFrom($email, $name = '')
	{
		if (is_null($this->_from)) {
			$this->_from = array('name' => $name, 'email' => $email);
		} else {
			trigger_error('From field is set twice', E_USER_ERROR);
		}
	}

	/**
	 * Sets sender
	 *
	 * @param string $email
	 */
	public function setForceFrom($email)
	{
		$this->_forceFrom = $email;
	}

	/**
	 * Returns email subject
	 *
	 * @return string
	 */
	public function getSubject()
	{
		return $this->_subject;
	}

	/**
	 * Sets email subject
	 *
	 * @param string $subject
	 */
	public function setSubject($subject)
	{
		$this->_subject = $subject;
	}

	/**
	 * Sends email
	 *
	 * @return boolean Operation status
	 */
	public function send()
	{
		$from = $this->getFrom();
		if (is_null($from)) {
			trigger_error('From field is not set', E_USER_ERROR);
		}

		$phpMailer = new PHPMailer();

		$smtp = ivMail::getSmtpConfig();

		if ($smtp['enabled']['value']) {
			$phpMailer->IsSMTP();
			$phpMailer->Host = $smtp['host']['value'];
			$phpMailer->Port = $smtp['port']['value'];
			$phpMailer->Helo = $smtp['helo']['value'];
			$phpMailer->SMTPSecure = $smtp['secure']['value'];
			$phpMailer->SMTPAuth = $smtp['auth']['value'];
			$phpMailer->Username = $smtp['username']['value'];
			$phpMailer->Password = $smtp['password']['value'];
			$phpMailer->Timeout = $smtp['timeout']['value'];
		} else {
			if (false !== strpos(ini_get('sendmail_path'), 'qmail')) {
				$phpMailer->IsQmail();
			}
		}

		$phpMailer->IsHTML(true);

		if (empty($this->_forceFrom)) {
			$phpMailer->From = $from['email'];
			$phpMailer->FromName = $from['name'];
			$phpMailer->Sender = $from['email'];
		} else if ($this->_forceFrom=='none') {

		} else {
			$phpMailer->From = $this->_forceFrom;
			$phpMailer->Sender = $this->_forceFrom;
		}

		$phpMailer->AddReplyTo($from['email'], $from['name']);
		$phpMailer->CharSet = 'UTF-8';
		$phpMailer->Subject = $this->getSubject();
		$phpMailer->Body = $this->getBody();

		$result = true;
		foreach ($this->getTo() as $toEmail => $toName) {
			$phpMailer->ClearAddresses();
			$phpMailer->AddAddress($toEmail, $toName);
			ob_start();
			$result &= $phpMailer->Send();
			ob_clean();
			if ($phpMailer->IsError()) {
				$this->_lastError = $phpMailer->ErrorInfo;
			}
		}

		return $result;
	}

	public function getLastError()
	{
		return $this->_lastError;
	}

	/**
	 * Returns SMTP config
	 *
	 * @return array
	 */
	public static function getSmtpConfig()
	{
		$config = self::$_defaultConfig;

		if (file_exists(USER_DIR . 'smtp.php')) {
			include(USER_DIR . 'smtp.php');

			if (isset($smtp)) {
				foreach ($smtp as $name => $value) {
					if (array_key_exists($name, $config)) {
						$config[$name]['value'] = $value;
					}
				}
			}
		}

		return $config;
	}

	/**
	 * Saves SMTP config
	 *
	 * @param  array   $smtp
	 * @return boolean Operation status
	 */
	public static function saveSmtpConfig($smtp)
	{
		$config = ivMail::getSmtpConfig();
		$defaultConfig = self::$_defaultConfig;

		$notDefault = false;
		foreach ($smtp as $name => $value) {
			if (array_key_exists($name, $config)) {
				if ($defaultConfig[$name]['value'] != $value) {
					$notDefault = true;
				}
				$config[$name]['value'] = $value;
			}
		}

		$fileContent = "<?php\n\n";
		foreach ($config as $name => $data) {
			$fileContent .= "/**
 * {$data['description']}
 */
\$smtp['$name'] = " . ivMail::_configValueToString($data) . ";

";
		}

		if ($notDefault) {
			$result = iv_file_put_contents(USER_DIR . 'smtp.php', $fileContent);
		} else {
			$result = (!file_exists(USER_DIR . 'smtp.php') || @unlink(USER_DIR . 'smtp.php'));
		}

		return ($result !== false);
	}

	/**
	 * Returns string representation of smtp config value
	 *
	 * @param  array  $data
	 * @return string
	 */
	private static function _configValueToString($data)
	{
		switch ($data['type']) {
			case 'boolean':
				return $data['value'] ? 'true' : 'false';
				break;
			case 'integer':
				return $data['value'];
				break;
			default:
				return "'{$data['value']}'";
				break;
		}
	}

}