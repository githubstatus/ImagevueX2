<?php
error_reporting(E_ALL);
switch ($_SERVER['QUERY_STRING']) {
	case 'ok.gif':
		header('Content-type: image/gif');
		echo base64_decode('R0lGODlhEAAQANUAAP///3XjdXDicG7ibmjhaGfhZ17fXlreWlLcUlDcUETaREPZQznXOTjXODbXNizTLBzNHA3LDRPFEwDMAA7EDhDDEAjGCAbEBg3BDQrACgm/CQPCAwDAAAC/AAa7BgC9AAO4AwC3AAC0AACyAACwAACsAACqAC2SLQCkAACjAACfAACaAACYAACSAACPAACJAACCAAB/AAB7AAB6AAB1AAByAP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEHADYALAAAAAAQABAAAAZ1QJtwSCwabYcE4wE5CgsDx2QaqWiMgcV0O92AiAQFd9wJDQXjLWA6Ehoa6QlgPvmQbIj4HHCammxwanJzfVMpNg9qe3xcLTYQEVMne4VTKC9CEluTlVMzQxQWm2MrMUQYF3EsNEYZHhxbKjUyTjYiJSkuMEVBADs=');
		exit(0);
		break;
	case 'notice.gif':
		header('Content-type: image/gif');
		echo base64_decode('R0lGODlhEAAQANUAAP/////Idf/Hc//Daf/DaP/CZv/BY//BZP+7Vf+7VP+1R/+xO/+xPf+wOv+wOf6vOf+vNv+tM/6nJf2lIfqkI/eeGP+ZAPCYFfaWB+2SCvCQAO+PAOiPCuaMBeaLAuaKAeOJAuKIAuKIAduDANmCANeBANJ+AMh4AMd3AKx5LcR2AMN1AMJ0AL1xALVtAK9pAKVjAKRiAP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAEHADIALAAAAAAQABAAAAZvQJlwKFMsiEhiAiKhJJMEi+XyJB4cUkymKhRIpR3ugGEBACyaTzUgNUtBz3H7jPYk2fMvnCjPSzV2Q15fKSlfFiFDBg2Hbl8bIkIFh2V0XyUyCBGUhZQkJw8TlKMWKi8yFRwjJigrrissLS4wMTJBADs=');
		exit(0);
		break;
	case 'error.gif':
		header('Content-type: image/gif');
		echo base64_decode('R0lGODlhEAAQANUAAP/////9/eN1deJwcOJubuFoaOFnZ99eXt5aWtxSUtxQUNpERNlDQ9c5Odc4ONc2NtMsLHZERJMvL5IuLpItLc0cHMUTE8MQEMsNDcQODsENDcYICMAKCr8JCcQGBrsGBswBAcIDA8wAALgDA78AAMAAAL0AALcAALQAALIAALAAAKwAAKoAAKQAAKMAAJ8AAJoAAJgAAJIAAI8AAIkAAIIAAH8AAHsAAHoAAHIAAHUAAP///wAAAAAAAAAAAAAAACH5BAEHADsALAAAAAAQABAAAAZ+wJ1wSCwadwhFA1I5Cg2Eh2iKuXSMAsZ0Ow2NiIUFd0w6DQdTEGC7nqaEB8cUAAiI6G2TapfYSvB0FFssO3J+eIJbLjsQXIB2WzI7FRhzdBF4Uy00QhZTFACCfxNTOEMZG59biTA2RBoeY1MxOkYcHyVbLzk3TjsoKy4zNUVBADs=');
		exit(0);
		break;
	default:
		if (isset($_GET['address']) && 'mail' == $_GET['a']) {
			$address = $_GET['address'];
			if (get_magic_quotes_gpc()) {
				$address = stripslashes($address);
			}
			$serverName = $_SERVER['SERVER_NAME'];
			$result = @mail(
				$address,
				'Email test ' . $serverName,
				'This is Imagevue email test from ' . $serverName,
				'Content-type: text/html; encoding=UTF-8'
			);
			header('Content-type: text/html');
		?>
		<style>* { margin:0; padding:0; }</style>
		<?php
			if ($result) {
		?><img src="?ok.gif" alt="" /><?php
			} else {
		?><img src="?error.gif" alt="" /><?php
			}
			exit(0);
		}
		break;
}

/**
 * Checks if given apache module loaded
 *
 * @param  string  $name
 * @return boolean
 */
function checkModule($name)
{
	$result = function_exists('get_loaded_extensions') ? in_array($name, get_loaded_extensions()) : false;
	if (function_exists('phpinfo') && !$result) {
		ob_start();
		phpinfo(INFO_MODULES);
		$modules = ob_get_contents();
		ob_end_clean();
		if (false !== stristr($modules, $name)) {
			$result = true;
		}
	}
	return $result;
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Imagevue Server Check</title>
		<style type="text/css">
			* { margin:0; padding;0; outline: 0}

			body {
				background-color: #333;
				font-family: Candara, Verdana, Arial, Helvetica, sans-serif;
				font-size: 12px/18px;
				color: #FFFFFF;
				margin: 18px 36px;
			}

			table { width: 900px }
			td,th {
				padding: 0 18px 36px 0; vertical-align: top;
			}

			h1 { font-size: 27px; font-weight: normal; margin: 0 0 18px 0}
			td a { display: block; margin: 9px 0 0 0;}
			a {
				text-decoration: none;
				color:#66CCFF;

			}

			select, input, textarea{
				font-family: Verdana, Arial, Helvetica, sans-serif;
				color:#333333;
				font-size: 12px;
				margin: 2px 0px 0px 0px;
			}
			p { margin: 0 0 18px 0; }
		</style>
	</head>
	<body>
		<h1>Imagevue Check</h1>
		<p>This script will check for issues on your server</p>
		<table>
			<?php if (checkModule('suhosin')): ?>
				<tr>
					<td><img src="?error.gif" alt="" /></td>
					<td>suhosin</td>
					<td>Oh, bad luck. This is some 'hardened php' project which went wrong. You might have to disable it.
						<a  class="iv-ext" target="_blank" href="http://imagevuex.com/forum/viewtopic.php?p=13808#13808">Related topic on imagevuex.com</a>
					</td>
				</tr>
			<?php endif; ?>
			<tr>
				<td><img src="<?php echo (version_compare('5.1.3', phpversion()) > 0 ? '?error.gif' : '?ok.gif'); ?>" alt="" /></td>
				<td>php&nbsp;version&nbsp;<?php echo phpversion(); ?></td>
				<td>
					Imagevue X2 requires a server that runs PHP ver.5.1.3+. PHP is a serverside scripting language standard on most servers these days, but if you are unsure if you have it, you still need to check.
					<a href="http://en.wikipedia.org/wiki/PHP" target="_blank" >http://en.wikipedia.org/wiki/PHP</a>
				</td>
			</tr>
			<tr>
				<td><img src="<?php echo (extension_loaded('xml') ? '?ok.gif' : '?error.gif'); ?>" alt="" /></td>
				<td>XML parser</td>
				<td>
					XML Parser is a PHP extension required by Imagevue X2 to execute many of the Imagevue scripts. Imagevue communicates with XML, so  this extension is critical for running the Imagevue X2 gallery. This extension is common on most professional PHP servers.
					<a href="http://php.net/manual/en/intro.xml.php" target="_blank" >http://php.net/manual/en/intro.xml.php</a>
				</td>
			</tr>
			<tr>
				<?php $checkResult = extension_loaded('gd') && function_exists('imagecopyresampled'); ?>
				<td><img src="<?php echo ($checkResult ? '?ok.gif' : '?notice.gif'); ?>" alt="" /></td>
				<td>GD 2</td>
				<td>
					GD is a PHP extension required by Imagevue to resize images into thumbnails. This extension is mostly standard on all servers running PHP these days. If your server does not have this extension enabled, you can still run Imagevue X2, but you will need to create thumbnails manually.
					<a href="http://en.wikipedia.org/wiki/GD_Graphics_Library" target="_blank" >http://en.wikipedia.org/wiki/GD_Graphics_Library</a>
				</td>
			</tr>
			<tr>
				<td><img src="<?php echo (extension_loaded('mbstring') ? '?ok.gif' : '?notice.gif'); ?>" alt="" /></td>
				<td>mbstring</td>
				<td>
					Multibyte string is a PHP extension required by Imagevue X2 to display international characters. This exension is usually enabled on professional PHP servers. If your server does not have this extension enabled, there will be problems displaying and formatting international characters and languages.
					<a href="http://php.net/manual/en/intro.mbstring.php" target="_blank" >http://php.net/manual/en/intro.mbstring.php</a>
				</td>
			</tr>
			<tr>
				<td><img src="<?php echo (function_exists('iconv') ? '?ok.gif' : '?notice.gif'); ?>" alt="" /></td>
				<td>iconv</td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td><img src="<?php echo (function_exists('exif_read_data') ? '?ok.gif' : '?notice.gif'); ?>" alt="" /></td>
				<td>EXIF</td>
				<td>
					EXIF is a PHP extension required by Imagevue X2 to display EXIF data from photos. EXIF data is generally information about a photo stored directly in the file, most often created by the camera. It can be camera info, data as well as keywords and description. The EXIF PHP extension is usually enabled on most professional PHP server. If your server does not have this extension enabled, EXIF data from photos will not display.
					<a href="http://php.net/manual/en/intro.exif.php" target="_blank" >http://php.net/manual/en/intro.exif.php</a>
					<a href="http://en.wikipedia.org/wiki/Exif" target="_blank" >http://en.wikipedia.org/wiki/Exif</a>
				</td>
			</tr>
			<tr>
				<?php $checkResult = (bool) ini_get('safe_mode'); ?>
				<td><img src="<?php echo ($checkResult ? '?error.gif' : '?ok.gif'); ?>" alt="" /></td>
				<td>safe mode</td>
				<td>No support on questions related to these restriction<?php echo ($checkResult ? ', just ask your hoster to disable this configuration option' : ''); ?></td>
			</tr>
			<tr>
				<?php $checkResult = (bool) ini_get('open_basedir'); ?>
				<td><img src="<?php echo ($checkResult ? '?error.gif' : '?ok.gif'); ?>" alt="" /></td>
				<td>open basedir</td>
				<td>No support on questions related to these restriction<?php echo ($checkResult ? ', just ask your hoster to disable this configuration option' : ''); ?></td>
			</tr>
			<tr>
				<td><img src="<?php echo (checkModule('mod_security') ? '?error.gif' : '?ok.gif'); ?>" alt="" /></td>
				<td>mod_security</td>
				<td>
					We recommend you to ask your hoster to remove 'mod_security' apache module.
					With enabled 'mod_security' you will be unavailable to use some functions in admin panel.
				</td>
			</tr>
			<tr>
				<td><img src="<?php echo (checkModule('mod_security2') ? '?error.gif' : '?ok.gif'); ?>" alt="" /></td>
				<td>mod_security2</td>
				<td>
					We recommend you to ask your hoster to remove 'mod_security2' apache module.
					With enabled 'mod_security2' you will be unavailable to use some functions in admin panel.
				</td>
			</tr>
			<tr>
				<td><iframe name="testEmail" frameborder="no" width="26" height="26"></iframe></td>
				<td>E-mail</td>
				<td>
					<form action="" method="get" target="testEmail">
						<div>
							<input type="hidden" name="a" value="mail" />
							<label>Enter your e-mail <input type="text" name="address" /></label>
							and press <input type="submit" value="Send" />, to check e-mail function on your server.
						</div>
					</form>
				</td>
			</tr>
			<td></td>
			<td>
				Authorization
			</td>
			<td>
				<?php

					error_reporting(E_ALL);

					if (false == strpos($_SERVER['SERVER_NAME'], '.') || preg_match('/^[\d\.]+$/', $_SERVER['SERVER_NAME'])) {
						echo ("Localhost – skipped");
					} else {

					//if (!isset($_COOKIE['authCheck'])) {
						$url = getHost() . $_SERVER['REQUEST_URI'];
						if (false !== strpos($url, '?')) {
							$url = substr($url, 0, strpos($url, '?'));
						}

						$options = array(
							'http' => array(
								'method' => 'POST',
								'header'  => array("Content-type: application/x-www-form-urlencoded"),
								'content' => http_build_query(array(
									'v' => '2.8.10.3',
									'url' => $url,
								)),
								'timeout' => 3,
								'ignore_errors' => true
							)
						);

						$context = stream_context_create($options);
						var_dump($options);
						// ivErrors::disable();
						$response = file_get_contents('http://auth.imagevuex.com/check.php', false, $context);
						// ivErrors::enable();

					 	var_dump($response);
				 	}

				function getHost()
				{
					$host = 'http';
					if (isset($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
						$host .= 's';
					}
					$host .= '://';

					if (!in_array($_SERVER['SERVER_PORT'], array(80, 443))) {
						$host .= $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'];
					} else {
						$host .= $_SERVER['HTTP_HOST'];
					}
					return $host;
				}
				?>
				</pre>
			</td>
		</table>



	</body>
</html>