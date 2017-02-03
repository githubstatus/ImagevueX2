<?php

// if (class_exists('Imagick')) {
//
// 	// do Imagick version check
//
// 	class ivImageAdapterImagick extends Imagick // implements ivImageAdapterInterface
// 	{}
//
// }
//
if (!class_exists('Imagick')) {

	class Imagick
	{
		const COMPRESSION_JPEG = 8;
		const FILTER_CATROM = 11;
	}

}

if (!class_exists('ImagickPixel')) {

	class ImagickPixel
	{}

}