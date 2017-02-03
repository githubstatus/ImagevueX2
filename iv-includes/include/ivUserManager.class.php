<?php

/**
 * Users manipulation
 *
 * @author McArrow
 */
class ivUserManager
{

	/**
	 * Array of users
	 * @var array
	 */
	private $_users = array();

	/**
	 * Constructor
	 *
	 */
	public function __construct()
	{
		if (file_exists(USERS_FILE)) {
			include(USERS_FILE);
		}
		if (isset($users) && is_array($users)) {
			foreach ($users as $login => $savedUser) {
				$user = $this->_addUser($login);
				$user->passwordHash = $savedUser['password'];
				$user->access = $savedUser['access'];
			}
		} else {
			$users['admin'] = array(
		 		'password' => $this->_hash('admin'),
				'access' => '*'
			);
			$this->saveUser('admin', array('access' => '*', 'password' => 'admin'));
		}
	}

	/**
	 * Adds new user
	 *
	 * @param  string   $login
	 * @return stdClass
	 */
	private function _addUser($login)
	{
		$user = new stdClass();
		$user->login = $login;
		$this->_users[] = $user;
		$user = $this->_users[count($this->_users) - 1];
		return $user;
	}

	/**
	 * Returns an array of users
	 *
	 * @return array
	 */
	public function getUsers()
	{
		return $this->_users;
	}

	/**
	 * Returns user by given login
	 *
	 * @param  string $login
	 * @return stdClass
	 */
	public function getUser($login)
	{
		$result = null;
		foreach ($this->_users as $key => $user) {
			if ($user->login == $login) {
				$result = $this->_users[$key];
			}
		}
		return $result;
	}

	/**
	 * Checks if user with login and password is registered
	 *
	 * @param  string  $login
	 * @param  string  $password
	 * @return boolean
	 */
	public function isRegistered($login, $password)
	{
		$user = $this->getUser($login);
		return (!is_null($user) && $this->_hash($password) === $user->passwordHash);
	}

	/**
	 * Hash function
	 *
	 * @param  string $string
	 * @return string
	 */
	private function _hash($string)
	{
		return sha1($string);
	}

	/**
	 * Adds new or change existing user
	 *
	 * @param  string $login
	 * @param  array  $userData
	 * @return boolean
	 */
	public function saveUser($login, $userData)
	{
		$user = $this->getUser($login);
		if (is_null($user)) {
			$user = $this->_addUser($login);
		}
		$user->access = $userData['access'];
		if (!empty($userData['password'])) {
			$user->passwordHash = $this->_hash($userData['password']);
		}
		return $this->_save();
	}

	/**
	 * Deletes user
	 *
	 * @param  string $login
	 * @return boolean
	 */
	public function deleteUser($login)
	{
		foreach ($this->_users as $key => $user) {
			if ($user->login == $login) {
				unset($this->_users[$key]);
			}
		}
		return $this->_save();
	}

	/**
	 * Save users
	 *
	 * @return boolean
	 */
	private function _save()
	{
		if (!is_writable(dirname(USERS_FILE))) {
			ivMessenger::add(ivMessenger::ERROR, "Folder " . substr(dirname(USERS_FILE), strlen(ROOT_DIR)) . "/ is not writable");
			return false;
		}

		$fileContent = '<?php
/*
 * Userlist file, passwords is sha1 hash. To manually change passwords
 * and generate hashes you can use http://sha1-hash-online.waraxe.us/
 * or use any other sha1 online hash calculator
 */

 ';
		foreach ($this->_users as $user) {
			$fileContent .= sprintf("\t\$users['%s']['password'] = '%s';\r\n",
				$user->login,
				$user->passwordHash
			);
			$fileContent .= sprintf("\t\$users['%s']['access'] = '%s';\r\n",
				$user->login,
				$user->access
			);
		}
		$fileContent .= '?>';
		$result = iv_file_put_contents(USERS_FILE, $fileContent);
		return ($result !== false);
	}

}