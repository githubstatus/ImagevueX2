<?php

if (version_compare('5.1.3', phpversion()) > 0) {
	?>
		<html>
			<head>
				<title>Sorry, PHP 5.1.3 is required</title>
				<link rel="stylesheet" type="text/css" href="assets/css/imagevue.admin.css" media="all">
			</head>
			<body>
				<div class="page">
					<div class="pageContent" style="text-align: center">
						<div class="note warning" style="margin: 100px; display: inline-block">
							Sorry, Imagevue requires <b>PHP 5.1.3</b> to run. You have <strong>PHP <?php echo phpversion() ?></strong> installed.
						</div>
					</div>
				</div>
			</body>
		</html>
	<?php
	die();
}

define('IV_PATH', '../iv-includes/');

if (!session_id()) {
	if (isset($_POST[session_name()])) {
		session_id($_POST[session_name()]);
	}
	session_start();
}

include_once(IV_PATH . 'common.inc.php');
include_once('config.inc.php');
require_once(IV_PATH . 'include/ivControllerFront.class.php');

ivAuth::basicAuthentication();
ivAuth::authenticateByCookie();

ivControllerFront::getInstance()->dispatch(dirname(__FILE__) . DIRECTORY_SEPARATOR);
