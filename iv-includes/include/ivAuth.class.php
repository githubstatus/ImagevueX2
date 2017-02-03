<?php

/**
 * Authentication and authorization class
 *
 * @static
 * @author McArrow
 */
class ivAuth
{

	/**
	 * Authenticate user
	 *
	 * @param string        $login
	 * @param string        $password
	 * @param boolean       $remember
	 * @param ivUserManager $userManager
	 */
	public static function authenticate($login, $password, $remember, $userManager = null)
	{
		if (is_null($userManager)) {
			$userManager = ivPool::get('userManager');
		}
		if (!empty($login) && !empty($password) && $userManager->isRegistered($login, $password)) {
			ivAuth::_setCurrentUserLogin($login, $remember, $userManager);
			$result = true;
		} else {
			ivAuth::_setCurrentUserLogin(null, true, $userManager);
			$result = false;
		}
		return $result;
	}

	/**
	 * Basic authentication
	 *
	 * @static
	 * @param ivUserManager $userManager
	 */
	public static function basicAuthentication($userManager = null)
	{
		if (is_null($userManager)) {
			$userManager = ivPool::get('userManager');
		}
		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && !ivAuth::getCurrentUserLogin()) {
			ivAuth::authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], false);
		}
	}

	/**
	 * Authenticate user by cookie
	 *
	 * @param ivUserManager $userManager
	 */
	public static function authenticateByCookie($userManager = null)
	{
		if (is_null($userManager)) {
			$userManager = ivPool::get('userManager');
		}
		foreach ($userManager->getUsers() as $user) {
			if (isset($_COOKIE[ivAuth::_hash('qwerty')]) && ivAuth::_makeCookie($user->login, $userManager) === $_COOKIE[ivAuth::_hash('qwerty')]) {
				ivAuth::_setCurrentUserLogin($user->login, isset($_COOKIE['remember']) && $_COOKIE['remember'], $userManager);
			}
		}
	}

	/**
	 * Returns current user's login
	 *
	 * @return string
	 */
	public static function getCurrentUserLogin()
	{
		return isset($_SESSION['imagevue']['login']) ? $_SESSION['imagevue']['login'] : null;
	}

	/**
	 * Sets current user's login
	 *
	 * @param  string  $login
	 * @param  boolean $remember
	 * @param ivUserManager $userManager
	 */
	private static function _setCurrentUserLogin($login, $remember, $userManager)
	{
		if ($login) {
			$_SESSION['imagevue']['login'] = $login;
		} elseif (isset($_SESSION['imagevue']['login'])) {
			unset($_SESSION['imagevue']['login']);
		}
		if (!empty($login)) {
			$time = $remember ? time() + 2592000 : 0;
			setcookie('remember', ($remember ? 1 : 0), $time, '/');
			setcookie(ivAuth::_hash('qwerty'), ivAuth::_makeCookie($login, $userManager), $time, '/');
		} else {
			setcookie('remember', null, time() - 3600, '/');
			setcookie(ivAuth::_hash('qwerty'), null, time() - 3600, '/');
		}
	}

	/**
	 * Makes cookie value
	 *
	 * @param string $login
	 * @param ivUserManager $userManager
	 */
	private static function _makeCookie($login, $userManager)
	{
		if (empty($login)) {
			return null;
		} else {
			$user = $userManager->getUser($login);
			return ivAuth::_hash($login . $user->passwordHash);
		}
	}

	/**
	 * Hash function
	 *
	 * @param  string $string
	 * @return string
	 */
	private static function _hash($string)
	{
		return sha1($string);
	}

}