<?php

class ivImageAdapterGd implements ivImageAdapterInterface
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
	 * Image resource
	 * @var resource
	 */
	private $_resource;

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

		switch ($imageSize[2]) {
			case IMAGETYPE_GIF:
				$this->_resource = imagecreatefromgif($files);
				break;
			case IMAGETYPE_JPEG:
				$this->_resource = imagecreatefromjpeg ($files);
				break;
			case IMAGETYPE_PNG:
				$this->_resource = imagecreatefrompng($files);
				break;
			default:
				return;
				break;
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
		$newImage = imagerotate($this->_resource, $degrees, 0xFFFFFF);

		if ($newImage) {
			imagedestroy($this->_resource);

			$this->_resource = $newImage;

			if (in_array($degrees, array(90, -90))) {
				$newHeight = $this->_width;
				$this->_width = $this->_height;
				$this->_height = $newHeight;
			}

			return true;
		}

		return false;
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
		$newImage = imagecreatetruecolor($columns, $rows);

		$color=html2rgb(ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/backgroundColor'));
		imagefill($newImage, 0, 0, imagecolorallocate($newImage, $color[0], $color[1], $color[2]));

		if (imagecopyresampled($newImage, $this->_resource, 0, 0, 0, 0, $columns, $rows, $this->_width, $this->_height)) {
			imagedestroy($this->_resource);

			$this->_resource = $newImage;

			$this->_width = $columns;
			$this->_height = $rows;

			return true;
		}

		return false;
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
		$newImage = imagecreatetruecolor($width, $height);

		if (imagecopy($newImage, $this->_resource, 0, 0, $x, $y, $width, $height)) {
			imagedestroy($this->_resource);

			$this->_resource = $newImage;

			$this->_width = $width;
			$this->_height = $height;

			return true;
		}

		return false;
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
	 * Writes an image to the specified filename
	 *
	 * @param mixed $filename
	 */
	public function writeImage($filename = null)
	{
		if (!isset($filename)) {
			$filename = $this->_path;
		}

		return imagejpeg($this->_resource, $filename, $this->_quality);
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
		return imagedestroy($this->_resource);
	}

	public function cropToSize($x, $y, $width, $height, $thumbWidth, $thumbHeight)
	{
		$newImage = imagecreatetruecolor($thumbWidth, $thumbHeight);

		if (imagecopyresampled($newImage, $this->_resource, 0, 0, $x, $y, $thumbWidth, $thumbHeight, $width, $height)) {
			imagedestroy($this->_resource);

			$this->_resource = $newImage;

			$this->_width = $thumbWidth;
			$this->_height = $thumbHeight;

			return true;
		}

		return false;
	}

}