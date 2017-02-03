<?php

$newConfigPath = dirname(dirname(__FILE__)) . DS . 'iv-config' . DS;
$oldConfigPath = dirname(__FILE__) . DS . 'config' . DS;
if (file_exists($oldConfigPath) && is_dir($oldConfigPath)) {
	ob_start();

	$handle = opendir($oldConfigPath);
	while (false !== ($file = readdir($handle))) {
		if ('.' != substr($file, 0, 1)) {
			$fullOldPath = $oldConfigPath . $file;
			if ('configUser.xml' == $file) {
				$fullNewPath = $newConfigPath . 'config.xml';
			} else {
				$fullNewPath = $newConfigPath . $file;
			}

			if (is_file($fullOldPath)) {
				if (!@copy($fullOldPath, $fullNewPath)) {
					migrationError("Cannot copy <b>iv-includes/config/{$file}</b> to <b>iv-config/" . ('configUser.xml' == $file ? 'config.xml' : $file) . "</b>. Are permissions ok?");
					echo "<li>Copying <b>iv-includes/config/{$file}</b>... <em>Ok<em></li>";
				}
			} else if (is_dir($fullOldPath)) {
				@mkdir($fullNewPath, 0777, true);
				echo "<li>Creating <b>iv-includes/config/{$file}/</b> folder... <em>Ok</em></li>";
				$subHandle = opendir($fullOldPath);
				while (false !== ($subFile = readdir($subHandle))) {
					if ('.' != substr($subFile, 0, 1)) {
						if (is_file($fullOldPath . DS . $subFile)) {
							if (!@copy($fullOldPath . DS . $subFile, $fullNewPath . DS . $subFile)) {
								migrationError("Cannot copy <b>iv-includes/config/{$file}/{$subFile}</b> to <b>iv-config/{$file}/{$subFile}</b>. Are permissions ok?");
								echo "<li>Copying <b>iv-includes/config/{$file}/{$subFile}</b>... <em>Ok</em></li>";
							}
						}
					}
				}
				closedir($subHandle);
			}
		}
	}
	closedir($handle);

	migrationRmdir($oldConfigPath);
	ob_end_clean();

}

$userThemesPath = dirname(dirname(__FILE__)) . DS . 'iv-config' . DS . 'themes' . DS;
$defaultThemesPath = dirname(__FILE__) . DS . 'themes' . DS;

$defaultThemes = ivThemeMapper::getInstance()->getDefaultThemes();

$nonDefaultThemes = array();

if (file_exists($defaultThemesPath)) {
	if ($handle = opendir($defaultThemesPath)) {
		while (false !== ($file = readdir($handle))) {
			if ((substr($file, 0, 1) != '.') && is_dir($defaultThemesPath . $file) && !in_array($file, $defaultThemes)) {
				$nonDefaultThemes[] = $file;
			}
		}
		closedir($handle);
	}
}


if (count($nonDefaultThemes)) {
	foreach ($nonDefaultThemes as $nonDefaultTheme) {
		if (ctype_alnum($nonDefaultTheme)) {
			$fullOldPath = $defaultThemesPath . $nonDefaultTheme . DS;
			$fullNewPath = $userThemesPath . $nonDefaultTheme . DS;
			@mkdir($fullNewPath, 0777, true);
			$handle = opendir($fullOldPath);
			while (false !== ($file = readdir($handle))) {
				if ('.' != substr($file, 0, 1) && ('themeConfig.xml' != $file)) {
					if (is_file($fullOldPath . $file)) {
						if (!@copy($fullOldPath . $file, $fullNewPath . $file)) {
							migrationError("Cannot copy <b>iv-includes/themes/{$nonDefaultTheme}/{$file}</b> to <b>iv-config/themes/{$nonDefaultTheme}/{$file}</b>. Are permissions ok?");
							echo "<li>Copying <b>iv-includes/themes/{$nonDefaultTheme}/{$file}</b>... <em>Ok</em></li>";
						}
					}
				}
			}
			closedir($handle);
			$xml = ivXml::readFromFile($fullOldPath . 'themeConfig.xml', dirname(__FILE__) . DS . 'include' . DS . 'theme.xml');
			file_put_contents($fullNewPath . 'config.xml', $xml->toString(false));
			migrationRmdir($fullOldPath);
			migrationRmdir($newConfigPath . $nonDefaultTheme . DS);
		} else {
			migrationError("Cannot copy <b>iv-includes/themes/{$nonDefaultTheme}</b> to <b>iv-config/themes/{$nonDefaultTheme}</b>. Not alphanumeric theme name.");
		}
	}
}

function migrationRmdir($path)
{
	if (!file_exists($path)) {
		return false;
	}
	$handle = opendir($path);
	while (false !== ($file = readdir($handle))) {
		if (!in_array($file, array('.', '..'))) {
			$filepath = $path . DS . $file;
			if (is_file($filepath) || is_link($filepath)) {
				if (!@unlink($filepath)) {
					migrationError("Cannot remove <b>{$path}</b>. Please delete it manually.");
				}
			} else {
				migrationRmdir($filepath);
			}
		}
	}
	closedir($handle);

	if (!@rmdir($path)) {
		migrationError("Cannot remove <b>{$path}</b>. Please delete it manually.");
	}
}

function migrationError($text) {
	$buffer = ob_get_clean();
	?>

	<html>
		<head>
			<title><?php echo strip_tags($text) ?></title>
			<link rel="stylesheet" type="text/css" href="iv-admin/assets/css/imagevue.admin.css" media="all">
			<link rel="stylesheet" type="text/css" href="assets/css/imagevue.admin.css" media="all">

			<style type="text/css">
				.note, ul, h2 { color: #f0f0f0; font-weight: normal;}
				b { color: white;}
			</style>
		</head>
		<body>
			<div class="page">
				<div class="pageContent" style="text-align: center; margin: 100px 0">
					<h2>Migrating <b>/iv-includes/config</b> to <b>/iv-config</b>:</h2>

					<?php if ($buffer) :?>
						<div class="note" style="margin: 0 100px 20px; display: inline-block; background-image: none; padding-left: 27px">
							<ul>
								<?php echo $buffer ?>
							</ul>
						</div>
					<?php endif ?>
					<br>
					<div class="note error" style="margin: 0 100px 100px; display: inline-block">
						<?php echo ($text) ?>
					</div>
				</div>
			</div>
		</body>
	</html>

	<?php

	die;
}

