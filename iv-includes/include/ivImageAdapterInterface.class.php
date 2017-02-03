<?php

interface ivImageAdapterInterface
{

	/**
	 * Constructor
	 *
	 * @param mixed $files
	 */
	public function __construct($files);

	/**
	 * Returns the image width
	 *
	 * @return integer
	 */
	public function getImageWidth();

	/**
	 * Returns the image height
	 *
	 * @return integer
	 */
	public function getImageHeight();

	/**
	 * Rotates an image
	 *
	 * @param  mixed   $background
	 * @param  float   $degrees
	 * @return boolean
	 */
	public function rotateImage($background, $degrees);

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
	public function resizeImage($columns, $rows, $filter, $blur, $bestfit = false);

	/**
	 * Extracts a region of the image
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @param  integer $x
	 * @param  integer $y
	 * @return boolean
	 */
	public function cropImage($width, $height, $x, $y);

	/**
	 * Sets the page geometry of the image
	 *
	 * @param  integer $width
	 * @param  integer $height
	 * @param  integer $x
	 * @param  integer $y
	 * @return boolean
	 */
	public function setImagePage($width, $height, $x, $y);

	/**
	 * Sets the format of a particular image
	 *
	 * @param  string $format
	 * @return boolean
	 */
	public function setImageFormat($format);

	/**
	 * Sets default compression type
	 *
	 * @param  integer $compression
	 * @return boolean
	 */
	public function setCompression($compression);

	/**
	 * Sets default compression type
	 *
	 * @param  integer $quality
	 * @return boolean
	 */
	public function setCompressionQuality($quality);

	/**
	 * Writes an image to the specified filename
	 *
	 * @param mixed $filename
	 */
	public function writeImage($filename = null);

	/**
	 * Clears all associated resources
	 *
	 * @return boolean
	 */
	public function clear();

	/**
	 * Destroys all associated resourses
	 *
	 * @return boolean
	 */
	public function destroy();

}