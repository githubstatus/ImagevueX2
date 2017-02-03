<?php
/**
 * Return the definition for the given IMAGETYPE_XXX constant.
 *
 * @param  integer $type
 * @return string
 */
function imageTypeToString($type)
{
	$stringTypes = array(
		1 => 'GIF',
		2 => 'JPG',
		3 => 'PNG',
		4 => 'SWF',
		5 => 'PSD',
		6 => 'BMP',
		7 => 'TIFF (intel byte order)',
		8 => 'TIFF (motorola byte order)',
		9 => 'JPC',
		10 => 'JP2',
		11 => 'JPX',
		12 => 'JB2',
		13 => 'SWC',
		14 => 'IFF',
		15 => 'WBMP',
		16 => 'XBM'
	);
	return (isset($stringTypes[$type]) ? $stringTypes[$type] : false);
}

/**
 * Returns content from the given path
 *
 * @param  string $path
 * @return array
 */
function getContent($path)
{
	if (!file_exists($path) || !is_dir($path)) {
		return array();
	}

	$list = array();
	if ($handle = opendir($path)) {
		while (false !== ($file = readdir($handle))) {
			if (substr($file, 0, 1) != '.' && substr($file, 0, 4) != '_vti') {
				$list[] = $file;
			}
		}
		closedir($handle);
	}
	return $list;
}

/**
 * Split a string by string and remove empty strings
 *
 * @param  string $delimiter	The boundary string
 * @param  string $string    The input string
 * @return array
 */
function array_explode_trim($delimiter, $string)
{
	$in = explode($delimiter, $string);
	$out = array();
	if (count($in)) {
		foreach ($in as $el) {
			$el = trim($el);
			if ($el && $el != '' || (is_string($el) && '0' == $el)) {
				$out[] = $el;
			}
		}
	}
	return (count($out) ? $out : array());
}

if (!function_exists('array_fill_keys')) {
	/**
	 * Fill an array with values, specifying keys
	 *
	 * @param  array $keys  Array of values that will be used as keys
	 * @param  mixed $value Value to use for filling
	 * @return array
	 */
	function array_fill_keys($keys, $value = '')
	{
		$filledArray = array();
		if (is_array($keys)) {
			foreach($keys as $key => $val) {
				$filledArray[$val] = is_array($value) ? $value[$key] : $value;
			}
		}
		return $filledArray;
	}
}

/**
 * Validates email
 *
 * @param  string $email
 * @return boolean
 */
function checkMail($email)
{
	return (boolean) preg_match('/^[a-z0-9][-_a-z0-9]*(\.[a-z0-9][-_a-z0-9]*)*@[a-z0-9][-_a-z0-9]*(\.[a-z0-9][-_a-z0-9]+)+$/i', $email);
}

function secureVar($var)
{
	return nl2br(strip_tags($var));
}

function xFireDebug($var) {
	if (!headers_sent() && function_exists('usleep')) {
		usleep(1); /* Ensure microtime() increments with each loop. Not very elegant but it works */
		$mt = explode(' ',microtime());
		$mt = substr($mt[1],7).substr($mt[0],2);
		usleep(1);
		header('X-FirePHP-Data-3' . $mt . ': ["LOG","' . $var . '"],');
	}
}

function getGenTime() {
	$startTime = explode(' ', START_TIME);
	$endTime = explode(' ', microtime());
	$genTime = $endTime[1] - $startTime[1] + $endTime[0] - $startTime[0];
	return $genTime;
}

/**
 * Transforms the php.ini notation for numbers (like '2M') to an integer (2 * 1024 * 1024 in this case)
 *
 * @param  string  $value
 * @return integer
 */
function realFilesize($value)
{
	$l = substr($value, -1);
	$result = (integer) $value;
	switch (strtoupper($l)) {
		case 'P':
			$result *= 1024;
		case 'T':
			$result *= 1024;
		case 'G':
			$result *= 1024;
		case 'M':
			$result *= 1024;
		case 'K':
			$result *= 1024;
			break;
	}
	return $result;
}

/**
 * Formats date accordingly to dateformat setting
 *
 * @param  integer $timestamp
 * @return string
 */
function formatDate($timestamp)
{
	$conf = ivPool::get('conf');
	return date($conf->get('/config/imagevue/settings/dateformat'), (integer) $timestamp);
}

/**
 * Formats file size
 *
 * @param  integer $bytesize
 * @return string
 */
function formatFilesize($bytesize)
{
	if ($bytesize > 1048576) {
		return round($bytesize / 1048576, 1) . 'Mb';
	}
	if ($bytesize > 1024) {
		return round($bytesize / 1024, 1) . 'kb';
	}
	return $bytesize . 'b';
}

/**
 * Calculates great common divisor
 *
 * Realise binary GCD algorithm
 *
 * @param  integer $int1
 * @param  integer $int2
 * @return integer
 */
function greatCommonDivisor($int1, $int2)
{
	// GCD(0, n) = n
	if (0 == $int1) {
		// GCD(0, 0) = 1
		return 0 == $int2 ? 1 : $int2;
	}
	// GCD(m, 0) = m
	if (0 == $int2) {
		return 1;
	}
	// GCD(m, m) = m
	if ($int1 == $int2) {
		return $int1;
	}
	// If m and n is even, GCD(m, n) = 2 * GCD(m / 2, n / 2);
	if (0 == $int1 % 2 && 0 == $int2 % 2) {
		return 2 * greatCommonDivisor((integer) ($int1 / 2), (integer) ($int2 / 2));
	}
	// If m is even, n is odd, GCD(m, n) = GCD(m / 2, n);
	if (0 == $int1 % 2) {
		return greatCommonDivisor((integer) ($int1 / 2), $int2);
	}
	// If m is odd, n is even, GCD(m, n) = GCD(m, n / 2);
	if (0 == $int2 % 2) {
		return greatCommonDivisor((integer) ($int2 / 2), $int1);
	}
	// If m and n is odd, GCD(m, n) = GCD(|m - n|, n);
	return greatCommonDivisor($int1, abs($int2 - $int1));
}

/**
 * Recursively makes directory, returns TRUE if exists or made
 *
 * @param  string  $path The directory path
 * @param  integer $mode
 * @return boolean       TRUE if exists or made or FALSE on failure
 */
function mkdirRecursive($path, $mode = 0777)
{
	$result = true;
	if (!file_exists($path)) {
		$result &= @mkdir($path, $mode, true);
	}
	$result &= iv_chmod($path, $mode);
	return $result;
}

/**
 * Recursively removes directory with it's content
 *
 * @param  string  $path The directory path
 * @return boolean
 */
function rmdirRecursive($path)
{
	if (!file_exists($path)) {
		return false;
	}
	if (!is_dir($path)) {
		trigger_error("Given path '$path' is not a directory", E_USER_ERROR);
	}
	$handle = opendir($path);
	while (false !== ($file = readdir($handle))) {
		if (!in_array($file, array('.', '..'))) {
			$filepath = $path . DIRECTORY_SEPARATOR . $file;
			if (is_file($filepath) || is_link($filepath)) {
				unlink($filepath);
			} else {
				rmdirRecursive($filepath);
			}
		}
	}
	closedir($handle);
	return @rmdir($path);
}

/**
 * Recursively un-quotes a quoted variable
 *
 * @param  mixed $var
 * @return mixed
 */
function stripslashes_recursive($var)
{
	if (is_array($var)) {
		$unquoted = array();
		foreach ($var as $key => $value) {
			$unquoted[$key] = stripslashes_recursive($value);
		}
		return $unquoted;
	} elseif (is_scalar($var)) {
		return stripslashes($var);
	} else {
		return $var;
	}
}

function encodeFilenameBase64($string)
{
	return str_replace(array('/', '=', '+'), array('.', '_', '-'), base64_encode($string));
}

function decodeFilenameBase64($string)
{
	return base64_decode(str_replace(array('.', '_', '-'), array('/', '=', '+'), $string));
}

function intcmp($a, $b)
{
	if ($a == $b) {
		return 0;
	}
	return ($a < $b) ? -1 : 1;
}

function smartStripTagsSubstr($string, $length = 32)
{
	return htmlspecialchars(mb_substr(preg_replace('/\s+/u', ' ', preg_replace('/\<\w.*?\>/', ' ', $string)), 0, $length, 'UTF-8')) . (mb_strlen($string, 'UTF-8') > $length ? '&hellip;' : '');
}

/**
 * Cleans a UTF-8 string for well-formedness and SGML validity
 *
 * It will parse according to UTF-8 and return a valid UTF8 string, with
 * non-SGML codepoints excluded.
 *
 * @note Just for reference, the non-SGML code points are 0 to 31 and
 *       127 to 159, inclusive.  However, we allow code points 9, 10
 *       and 13, which are the tab, line feed and carriage return
 *       respectively. 128 and above the code points map to multibyte
 *       UTF-8 representations.
 *
 * @note Fallback code adapted from utf8ToUnicode by Henri Sivonen and
 *       hsivonen@iki.fi at <http://iki.fi/hsivonen/php-utf8/> under the
 *       LGPL license.  Notes on what changed are inside, but in general,
 *       the original code transformed UTF-8 text into an array of integer
 *       Unicode codepoints. Understandably, transforming that back to
 *       a string would be somewhat expensive, so the function was modded to
 *       directly operate on the string.  However, this discourages code
 *       reuse, and the logic enumerated here would be useful for any
 *       function that needs to be able to understand UTF-8 characters.
 *       As of right now, only smart lossless character encoding converters
 *       would need that, and I'm probably not going to implement them.
 *       Once again, PHP 6 should solve all our problems.
 */
function stripNonUtf8Chars($str, $force_php = false) {

    // UTF-8 validity is checked since PHP 4.3.5
    // This is an optimization: if the string is already valid UTF-8, no
    // need to do PHP stuff. 99% of the time, this will be the case.
    // The regexp matches the XML char production, as well as well as excluding
    // non-SGML codepoints U+007F to U+009F
    if (preg_match('/^[\x{9}\x{A}\x{D}\x{20}-\x{7E}\x{A0}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]*$/Du', $str)) {
        return $str;
    }

    $mState = 0; // cached expected number of octets after the current octet
                 // until the beginning of the next UTF8 character sequence
    $mUcs4  = 0; // cached Unicode character
    $mBytes = 1; // cached expected number of octets in the current sequence

    // original code involved an $out that was an array of Unicode
    // codepoints.  Instead of having to convert back into UTF-8, we've
    // decided to directly append valid UTF-8 characters onto a string
    // $out once they're done.  $char accumulates raw bytes, while $mUcs4
    // turns into the Unicode code point, so there's some redundancy.

    $out = '';
    $char = '';

    $len = strlen($str);
    for($i = 0; $i < $len; $i++) {
        $in = ord($str{$i});
        $char .= $str[$i]; // append byte to char
        if (0 == $mState) {
            // When mState is zero we expect either a US-ASCII character
            // or a multi-octet sequence.
            if (0 == (0x80 & ($in))) {
                // US-ASCII, pass straight through.
                if (($in <= 31 || $in == 127) &&
                    !($in == 9 || $in == 13 || $in == 10) // save \r\t\n
                ) {
                    // control characters, remove
                } else {
                    $out .= $char;
                }
                // reset
                $char = '';
                $mBytes = 1;
            } elseif (0xC0 == (0xE0 & ($in))) {
                // First octet of 2 octet sequence
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 0x1F) << 6;
                $mState = 1;
                $mBytes = 2;
            } elseif (0xE0 == (0xF0 & ($in))) {
                // First octet of 3 octet sequence
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 0x0F) << 12;
                $mState = 2;
                $mBytes = 3;
            } elseif (0xF0 == (0xF8 & ($in))) {
                // First octet of 4 octet sequence
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 0x07) << 18;
                $mState = 3;
                $mBytes = 4;
            } elseif (0xF8 == (0xFC & ($in))) {
                // First octet of 5 octet sequence.
                //
                // This is illegal because the encoded codepoint must be
                // either:
                // (a) not the shortest form or
                // (b) outside the Unicode range of 0-0x10FFFF.
                // Rather than trying to resynchronize, we will carry on
                // until the end of the sequence and let the later error
                // handling code catch it.
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 0x03) << 24;
                $mState = 4;
                $mBytes = 5;
            } elseif (0xFC == (0xFE & ($in))) {
                // First octet of 6 octet sequence, see comments for 5
                // octet sequence.
                $mUcs4 = ($in);
                $mUcs4 = ($mUcs4 & 1) << 30;
                $mState = 5;
                $mBytes = 6;
            } else {
                // Current octet is neither in the US-ASCII range nor a
                // legal first octet of a multi-octet sequence.
                $mState = 0;
                $mUcs4  = 0;
                $mBytes = 1;
                $char = '';
            }
        } else {
            // When mState is non-zero, we expect a continuation of the
            // multi-octet sequence
            if (0x80 == (0xC0 & ($in))) {
                // Legal continuation.
                $shift = ($mState - 1) * 6;
                $tmp = $in;
                $tmp = ($tmp & 0x0000003F) << $shift;
                $mUcs4 |= $tmp;

                if (0 == --$mState) {
                    // End of the multi-octet sequence. mUcs4 now contains
                    // the final Unicode codepoint to be output

                    // Check for illegal sequences and codepoints.

                    // From Unicode 3.1, non-shortest form is illegal
                    if (((2 == $mBytes) && ($mUcs4 < 0x0080)) ||
                        ((3 == $mBytes) && ($mUcs4 < 0x0800)) ||
                        ((4 == $mBytes) && ($mUcs4 < 0x10000)) ||
                        (4 < $mBytes) ||
                        // From Unicode 3.2, surrogate characters = illegal
                        (($mUcs4 & 0xFFFFF800) == 0xD800) ||
                        // Codepoints outside the Unicode range are illegal
                        ($mUcs4 > 0x10FFFF)
                    ) {

                    } elseif (0xFEFF != $mUcs4 && // omit BOM
                        // check for valid Char unicode codepoints
                        (
                            0x9 == $mUcs4 ||
                            0xA == $mUcs4 ||
                            0xD == $mUcs4 ||
                            (0x20 <= $mUcs4 && 0x7E >= $mUcs4) ||
                            // 7F-9F is not strictly prohibited by XML,
                            // but it is non-SGML, and thus we don't allow it
                            (0xA0 <= $mUcs4 && 0xD7FF >= $mUcs4) ||
                            (0x10000 <= $mUcs4 && 0x10FFFF >= $mUcs4)
                        )
                    ) {
                        $out .= $char;
                    }
                    // initialize UTF8 cache (reset)
                    $mState = 0;
                    $mUcs4  = 0;
                    $mBytes = 1;
                    $char = '';
                }
            } else {
                // ((0xC0 & (*in) != 0x80) && (mState != 0))
                // Incomplete multi-octet sequence.
                // used to result in complete fail, but we'll reset
                $mState = 0;
                $mUcs4  = 0;
                $mBytes = 1;
                $char ='';
            }
        }
    }
    return $out;
}

function pageURL()
{
	$qPathArray = array_explode_trim('/', $_SERVER['PHP_SELF']);
	array_pop($qPathArray);
	return (getHost() . (count($qPathArray) ? '/' : '') . implode('/', $qPathArray) . '/');
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

function authCheck()
{
	if (false == strpos($_SERVER['SERVER_NAME'], '.') || preg_match('/^[\d\.]+$/', $_SERVER['SERVER_NAME'])) {
		return true;
	}

	if (!isset($_COOKIE['authCheck'])) {
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

		ivErrors::disable();
		$response = file_get_contents('http://auth.imagevuex.com/check.php', false, $context);
		ivErrors::enable();

		if (!headers_sent()) {
			setcookie('authCheck', $response);
		}
		$_COOKIE['authCheck'] = $response;
	}

	return in_array($_COOKIE['authCheck'], array('good', false));
}

function getCssPath($type, $savePath = false)
{
	if ($savePath) {
		mkdirRecursive(USER_DIR . 'css' . DS);
	}
	$path = "iv-config/css/imagevue.$type.css";
	if (file_exists(ROOT_DIR . $path) || $savePath) {
		return $path;
	}
}

function getEmailTemplatePath($name, $savePath = false)
{
	if ($savePath) {
		mkdirRecursive(USER_DIR . 'templates' . DS . 'default' . DS );
	}
	$path = "iv-config/templates/$name.html";
	if (file_exists(ROOT_DIR . $path) || $savePath) {
		return ROOT_DIR . $path;
	}

	$path = BASE_DIR . 'include' . DS . "$name.html";
	if (file_exists($path)) {
		return $path;
	}
}

if (!function_exists('mb_substr')) {
	/**
	 * Get part of string
	 *
	 * @param  string  $str      The string being checked
	 * @param  integer $start    The first position used in str
	 * @param  integer $length   The maximum length of the returned string
	 * @param  string  $encoding The encoding parameter is the character encoding. If it is omitted, the internal character encoding value will be used
	 * @return string
	 */
	function mb_substr($str, $start)
	{
		$args = func_get_args();

		if (count($args) > 2) {
			return substr($str, $start, $args[2]);
		}

		return substr($str, $start);
	}
}

if (!function_exists('mb_strlen')) {
	/**
	 * Get string length
	 *
	 * @param  string  $str      The string being checked for length
	 * @param  string  $encoding The encoding parameter is the character encoding. If it is omitted, the internal character encoding value will be used
	 * @return integer
	 */
	function mb_strlen($str)
	{
		return strlen($str);
	}
}

if (!function_exists('mb_strtoupper')) {
	/**
	 * Make a string uppercase
	 *
	 * @param  string $str      The string being uppercased
	 * @param  string $encoding The encoding parameter is the character encoding. If it is omitted, the internal character encoding value will be used
	 * @return string
	 */
	function mb_strtoupper($str)
	{
		return strtoupper($str);
	}
}

if (!function_exists('mb_strtolower')) {
	/**
	 * Make a string lowercase
	 *
	 * @param  string $str      The string being lowercased
	 * @param  string $encoding The encoding parameter is the character encoding. If it is omitted, the internal character encoding value will be used
	 * @return string
	 */
	function mb_strtolower($str)
	{
		return strtolower($str);
	}
}

if (!function_exists('json_decode')){
	/**
	 * Decodes a JSON string
	 *
	 * @param  string  $json  The json string being decoded
	 * @param  boolean $assoc When TRUE, returned objects will be converted into associative arrays
	 * @return mixed
	 */
	function json_decode($json, $assoc = false)
	{
		if ($assoc) {
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		} else {
			$json = new Services_JSON();
		}
		return $json->decode($json);
	}
}

if (!function_exists('json_encode')) {
	/**
	 * Returns the JSON representation of a value
	 *
	 * @param  mixed  $value The value being encoded. Can be any type except a resource
	 * @return string
	 */
	function json_encode($value)
	{
		$json = new Services_JSON();

		return $json->encode($value);
	}
}

function idfy($path) {
	return 'f'.str_replace('/','',str_replace('+','_', smart_urlencode($path)));
}

function smart_urlencode($str)
{
	return str_replace('%2F', '/', urlencode($str));
}

if (!function_exists('sys_get_temp_dir')) {
	function sys_get_temp_dir() {
		if (!empty($_ENV['TMP'])) {
			return realpath($_ENV['TMP']);
		}

		if (!empty($_ENV['TMPDIR'])) {
			 return realpath($_ENV['TMPDIR']);
		}

		if (!empty($_ENV['TEMP'])) {
			return realpath($_ENV['TEMP']);
		}

		$tempfile = tempnam(__FILE__, '');
		if (file_exists($tempfile)) {
			unlink($tempfile);
			return realpath(dirname($tempfile));
		}

		return false;
	}
}

function html2rgb($color)
{
    if ($color[0] == '#')
        $color = substr($color, 1);

    if (strlen($color) == 6)
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    elseif (strlen($color) == 3)
        list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
    else
        return false;

    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

    return array($r, $g, $b);
}

function currentURL() {
	 $pageURL = 'http';
	 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	 $pageURL .= "://";
	 if ($_SERVER["SERVER_PORT"] != "80") {
	  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	 } else {
	  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	 }
	 return $pageURL;
}

function getPageLink($item) {

		$target=$rel='';
		if ($item->isLink()) {
			if (IS_MOBILE) $rel='external';
			preg_match('/lightbox\s*\(\s*[\'\"]?(.*?)[\'\"]?\s*[\,\)]/', $item->pageContent, $m);
      $target = '_blank';

			if (IS_MOBILE && isset($m[1])) {
				$link = $m[1];

				}	else {

				$linkParams = explode('*', $item->pageContent, 2);
				$link = $linkParams[0];
				$target = isset($linkParams[1]) ? $linkParams[1] : '';
				$link =  htmlspecialchars(t($link));

			}
		} else {
			$link = '?/' . smart_urlencode($item->getPrimary());
		}

		return array($link, $target, $rel);
}


function fittobox($videoWidth, $videoHeight, $boxWidth=900, $boxHeight=500) {
		$boxAspect = $boxWidth / $boxHeight;
		$originalAspect = $videoWidth / $videoHeight;
		list($width, $height)=array($videoWidth, $videoHeight);
		if ($boxAspect < $originalAspect) {
			$width = $boxWidth;
			$height = (integer) ($boxWidth / $originalAspect);
		} else {
			$width = (integer) ($boxHeight * $originalAspect);
			$height = $boxHeight;
		}
		if ($videoWidth>$boxWidth or $videoHeight>$boxHeight) {
			return array($width, $height);
		}
		return array($videoWidth, $videoHeight);
}

function startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return (substr($haystack, 0, $length) === $needle);
}

function br2nl($string) {
  return(preg_replace('#<br\s*/?>#i', "\n", $string));
}