<?php

/**
 * Image manupulations class
 *
 */
class ivImage
{

	const IMAGE_ROTATE_CW = 'cw';
	const IMAGE_ROTATE_CCW = 'ccw';

	const IMAGE_RESIZETYPE_CROPTOBOX = 'croptobox';
	const IMAGE_RESIZETYPE_RESIZETOBOX = 'resizetobox';

	const DEFAULT_QUALITY = 95;

	/**
	 * Image adapter
	 * @var ivImageAdapterInterface
	 */
	private $_adapter;

	/**
	 * Constructor
	 *
	 * @param string $path
	 */
	public function __construct($path)
	{
		if (class_exists('ivImageAdapterImagick')) {
			$adapter = new ivImageAdapterImagick($path);
		} else if (class_exists('ivImageAdapterImagemagick')) {
			$adapter = new ivImageAdapterImagemagick($path);
		} else {
			$adapter = new ivImageAdapterGd($path);
		}

		$this->_adapter = $adapter;
	}

	/**
	 * Return image adapter
	 *
	 * @return ivImageAdapterInterface
	 */
	public function getAdapter()
	{
		return $this->_adapter;
	}

	/**
	 * Rotate image in given direction
	 *
	 * @param string $direction
	 * @return boolean
	 */
	public function rotate($direction = self::IMAGE_ROTATE_CW)
	{
		$angle = (self::IMAGE_ROTATE_CW == $direction ? -90 : 90);
		return $this->getAdapter()->rotateImage(new ImagickPixel('#FFFFFF'), $angle);
	}

	/**
	 * Writes image to file
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function write($path = null)
	{
		$this->getAdapter()->setImageFormat('jpeg');
		$this->getAdapter()->setCompression(Imagick::COMPRESSION_JPEG);
		$this->getAdapter()->setCompressionQuality(self::DEFAULT_QUALITY);
		return $this->getAdapter()->writeImage($path);
	}

	/**
	 * Crops image by size
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @return boolean
	 */
	public function crop($width, $height)
	{
		$x = floor(($this->getAdapter()->getImageWidth() - $width) / 2);
		$y = floor(($this->getAdapter()->getImageHeight() - $height) / 2);

		return $this->getAdapter()->cropImage($width, $height, $x, $y);
	}

	/**
	 * Resizes image
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @return boolean
	 */
	public function resize($width, $height)
	{
		return $this->getAdapter()->resizeImage($width, $height, Imagick::FILTER_CATROM, 1);
	}

	/**
	 * Resizes image proportionally
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @param  boolean $write
	 * @return boolean
	 */
	public function resizeProportionally($width, $height, $write = false)
	{
		$result = true;
		if ($this->getAdapter()->getImageWidth() > $width && $this->getAdapter()->getImageHeight() > $height) {
			$imageAspect = $this->getAdapter()->getImageWidth() / $this->getAdapter()->getImageHeight();
			$aspect = $width / $height;
			if ($imageAspect > $aspect) {
				$result &= $this->getAdapter()->resizeImage($width, $width / $imageAspect, Imagick::FILTER_CATROM, 1);
			} else {
				$result &= $this->getAdapter()->resizeImage($height * $imageAspect, $height, Imagick::FILTER_CATROM, 1);
			}
			if ($write) {
				$result &= $this->getAdapter()->writeImage();
			}
		}
		return $result;
	}

	/**
	 * Generates thumbnail with given parameters
	 *
	 * @param  integer $boxWidth
	 * @param  integer $boxHeight
	 * @param  string  $resizeType
	 * @return boolean
	 */
	public function thumbnail($boxWidth = 120, $boxHeight = 80, $resizeType = self::IMAGE_RESIZETYPE_CROPTOBOX)
	{
		if (!$this->getAdapter()->getImageWidth() || !$this->getAdapter()->getImageHeight()) {
			return false;
		}

		if ($this->getAdapter()->getImageWidth() <= $boxWidth && $this->getAdapter()->getImageHeight() <= $boxHeight) {
			return true;
		}

		$boxAspect = $boxWidth / $boxHeight;
		$originalAspect = $this->getAdapter()->getImageWidth() / $this->getAdapter()->getImageHeight();

		if (self::IMAGE_RESIZETYPE_CROPTOBOX == $resizeType) {
			if ($this->getAdapter()->getImageWidth() > $boxWidth && $this->getAdapter()->getImageHeight() > $boxHeight) {
				if ($boxAspect < $originalAspect) {
					$width = (integer) ($boxHeight * $originalAspect);
					$height = $boxHeight;
				} else {
					$width = $boxWidth;
					$height = (integer) ($boxWidth / $originalAspect);
				}
				$this->resize($width, $height);
			}

			$this->crop(min($boxWidth, $this->getAdapter()->getImageWidth()), min($boxHeight, $this->getAdapter()->getImageHeight()));
		} else {
			if ($boxAspect < $originalAspect) {
				$width = $boxWidth;
				$height = (integer) ($boxWidth / $originalAspect);
			} else {
				$width = (integer) ($boxHeight * $originalAspect);
				$height = $boxHeight;
			}
			$this->resize($width, $height);
		}

		return true;
	}

	public function cropToSize($x, $y, $width, $height, $thumbWidth, $thumbHeight)
	{
		if (!$this->getAdapter()->getImageWidth() || !$this->getAdapter()->getImageHeight()) {
			return false;
		}

		if ($this->getAdapter()->getImageWidth() <= $thumbWidth && $this->getAdapter()->getImageHeight() <= $thumbHeight) {
			return true;
		}

		if (method_exists($this->getAdapter(), 'cropToSize')) {
			return $this->getAdapter()->cropToSize($x, $y, $width, $height, $thumbWidth, $thumbHeight);
		}

		$result = $this->getAdapter()->cropImage($width, $height, $x, $y);
		$result &= $this->getAdapter()->resizeImage($thumbWidth, $thumbHeight, Imagick::FILTER_CATROM, 1);

		return $result;
	}

}