<?php

return;

if (!function_exists('exec') || substr(strtoupper(PHP_OS),0,3) == "WIN") {
	return;
}

exec('convert -version', $output, $result);

if ($result || !isset($output[0]) || !preg_match('/ImageMagick/', $output[0])) {
	return;
}

class ivImageAdapterImagemagick implements ivImageAdapterInterface
{

	/**
	 * Image width
	 * @var integer
	 */
	private $_width;

	/**
	 * Image height
	 * @var integer
	 */
	private $_height;

	/**
	 * Image path
	 * @var string
	 */
	private $_path;

	/**
	 * Quality
	 * @var integer
	 */
	private $_quality = 90;

	/**
	 * Array of transforms
	 * @var integer
	 */
	private $_transforms = array();

	/**
	 * Constructor
	 *
	 * @param mixed $files
	 */
	public function __construct($files)
	{
		$imageSize = @getimagesize($files);
		if (!is_array($imageSize)) {
			return;
		}

		$this->_width = $imageSize[0];
		$this->_height = $imageSize[1];
		$this->_path = $files;
	}

	/**
	 * Returns the image width
	 *
	 * @return integer
	 */
	public function getImageWidth()
	{
		return $this->_width;
	}

	/**
	 * Returns the image height
	 *
	 * @return integer
	 */
	public function getImageHeight()
	{
		return $this->_height;
	}

	/**
	 * Rotates an image
	 *
	 * @param  mixed   $background
	 * @param  float   $degrees
	 * @return boolean
	 */
	public function rotateImage($background, $degrees)
	{
		$this->_transforms[] = '-rotate ' . escapeshellarg(intval(-$degrees));

		if (in_array($degrees, array(90, -90))) {
			$newHeight = $this->_width;
			$this->_width = $this->_height;
			$this->_height = $newHeight;
		}

		return true;
	}

	/**
	 * Scales an image
	 *
	 * @param  integer $columns
	 * @param  integer $rows
	 * @param  integer $filter
	 * @param  float   $blur
	 * @param  boolean $bestfit
	 * @return boolean
	 */
	public function resizeImage($columns, $rows, $filter, $blur, $bestfit = false)
	{
		$this->_transforms[] = '-resize ' . escapeshellarg($columns . 'x' . $rows . '!') . ' -flatten -background \'#'. ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/backgroundColor') . '\'';
		$this->_width = $columns;
		$this->_height = $rows;

		return true;
	}

	/**
	 * Extracts a region of the image
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @param  integer $x
	 * @param  integer $y
	 * @return boolean
	 */
	public function cropImage($width, $height, $x, $y)
	{
		$this->_transforms[] = '-crop ' . escapeshellarg("{$width}x{$height}!{$this->_prepareInteger($x)}{$this->_prepareInteger($y)}");

		$this->_width = $columns;
		$this->_height = $rows;

		return true;
	}

	/**
	 * Sets the page geometry of the image
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @param  integer $x
	 * @param  integer $y
	 * @return boolean
	 */
	public function setImagePage($width, $height, $x, $y)
	{
		return true;
	}

	/**
	 * Sets the format of a particular image
	 *
	 * @param  string $format
	 * @return boolean
	 */
	public function setImageFormat($format)
	{
		return true;
	}

	/**
	 * Sets default compression type
	 *
	 * @param  integer $compression
	 * @return boolean
	 */
	public function setCompression($compression)
	{
		return true;
	}

	/**
	 * Sets default compression type
	 *
	 * @param  integer $quality
	 * @return boolean
	 */
	public function setCompressionQuality($quality)
	{
		$this->_quality = $quality;
		return true;
	}

	/**
	 * Writes an image to the specified filename
	 *
	 * @param mixed $filename
	 */
	public function writeImage($filename = null)
	{
		if (!isset($filename)) {
			$filename = $this->_path;
		}

		$command = 'convert ' . escapeshellarg($this->_path) . ' ' . implode(' ', $this->_transforms);
		$command .= ' -quality ' . escapeshellarg($this->_quality) . ' ';
		$command .= escapeshellarg($filename);

		exec($command, $output, $result);

		return (!$result);
	}

	/**
	 * Clears all associated resources
	 *
	 * @return boolean
	 */
	public function clear()
	{
		return true;
	}

	/**
	 * Destroys related resourses
	 *
	 * @return boolean
	 */
	public function destroy()
	{
		return true;
	}

	/**
	 * Converts integer value to it's IM string representation
	 *
	 * @param  integer $value
	 * @return string
	 */
	private function _prepareInteger($value)
	{
		return (string) ($value >= 0 ? '+' . $value : $value);
	}

}