<?php
/**
 * Exif data parser
 *
 */
class ivExifParser
{

	/**
	 * List of allowed exif tags
	 * @var array
	 * You can add or remove EXIF tags by commenting or de-commenting them with //
	 */
	private $_allowedTags = array(
		//'Brand',
		'Model',
		'Exposure',
		'Aperture',
		'FocalLength',
		'ISOSpeed',
		//'ExposureBias',
		'Flash',
		//'ImageDescription',
		//'Orientation',
		//'XYResolution',
		//'Software',
		//'DateAndTime',
		//'YCbCrPositioning',
		//'Copyright',
		//'ExposureProgram',
		'DateAndTimeOriginal',
		//'DateAndTimeDigitized',
		//'CompressedBitsPerPixel',
		//'ShutterSpeed',
		//'Brightness',
		//'MaximumLensAperture',
		//'SubjectDistance',
		//'MeteringMode',
		//'ColorSpace',
		//'FocalPlaneXYResolution',
		//'BitsPerSample',
		//'ExposureIndex',
		//'SensingMethod',
		//'Compression',
		//'Quality',
		//'JPEGQuality',
		//'ColorMode',
		//'ImageAdjustment',
		//'Focus',
		//'DigitalZoom',
		//'WhiteBalance',
		//'Sharpness',
		//'FlashMode',
		//'FlashStrength',
		//'PictureMode',
		//'FirmwareVersion',
		//'PhotometricInterpretation',
		//'StripOffsets',
		//'SamplesPerPixel',
		//'RowsPerStrip',
		//'StripByteCounts',
		//'ImageType',
		//'ImageNumber',
		//'OwnerName',
		//'Dimensions',
		//'DocumentName',
	);

	/**
	 * Read exif data
	 *
	 * @param  array $exifData
	 * @return array
	 */
	public function parse($exifData)
	{
		if (is_array($exifData)) {
			$filtered = array();
			foreach ($this->_allowedTags as $name) {
				if (method_exists($this, $name)) {
					$this->$name($filtered, $exifData);
				} else {
					if (isset($exifData[$name])) {
						$filtered[trim(preg_replace('/([A-Z])/', ' $1', $name))] = $exifData[$name];
					}
				}
			}
			return $filtered;
		} else {
			return false;
		}
	}

	/**
	 * Evaluates rational value
	 *
	 * @param  string $value
	 * @return float
	 */
	private function _evalRational($value)
	{
		if (preg_match('/^(\d+)\/(\d+)$/', $value, $matches)) {
			$value = $matches[2] ? ($matches[1] / $matches[2]) : 0;
		}
		return (float) $value;
	}

	/**
	 * Simplifies fractions like 10/2170
	 *
	 * @param  string $fraction
	 * @return string
	 */
	private function _simplifyFraction($fraction)
	{
		if (preg_match('/^(\d+)\/(\d+)$/', $fraction, $matches)) {
			$numerator = $matches[1];
			$denominator = isset($matches[2]) && $matches[2] ? $matches[2] : 1;
			$gcd = greatCommonDivisor($numerator, $denominator);
			return sprintf('%d/%d', $numerator / $gcd, $denominator / $gcd);
		}
		return $fraction;
	}

	/**
	 * Format Brand field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Brand(&$filtered, $exifData)
	{
		if (isset($exifData['Make'])) {
			$filtered['Brand'] = $exifData['Make'];
		}
	}

	/**
	 * Format Exposure field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Exposure(&$filtered, $exifData)
	{
		if (isset($exifData['ExposureTime'])) {
			$filtered['Exposure'] = sprintf('%01.3f sec (%s)',
				$this->_evalRational($exifData['ExposureTime']),
				$this->_simplifyFraction($exifData['ExposureTime'])
			);
		}
	}

	/**
	 * Format Aperture field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Aperture(&$filtered, $exifData)
	{
		if (isset($exifData['FNumber'])) {
			$filtered['Aperture'] = sprintf('f/%01.1f',
				$this->_evalRational($exifData['FNumber'])
			);
		}
	}

	/**
	 * Format Focal Length field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function FocalLength(&$filtered, $exifData)
	{
		if (isset($exifData['FocalLength'])) {
			$filtered['Focal Length'] = sprintf('%01.1f mm',
				$this->_evalRational($exifData['FocalLength'])
			);
		}
	}

	/**
	 * Format ISO Speed field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function ISOSpeed(&$filtered, $exifData)
	{
		if (isset($exifData['ISOSpeedRatings'])) {
			$filtered['ISO Speed'] = $exifData['ISOSpeedRatings'];
		}
	}

	/**
	 * Format Exposure Bias field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function ExposureBias(&$filtered, $exifData)
	{
		if (isset($exifData['ExposureBiasValue'])) {
			$filtered['Exposure Bias'] = $exifData['ExposureBiasValue'] . ' EV';
		}
	}

	/**
	 * Format Flash field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Flash(&$filtered, $exifData)
	{
		$flash = array(
			0 => 'Flash did not fire',
			1 => 'Flash fired',
			5 => 'Strobe return light not detected',
			7 => 'Strobe return light detected'
		);
		if (isset($exifData['Flash']) && is_scalar($exifData['Flash']) && isset($flash[$exifData['Flash']])) {
			$filtered['Flash'] = $flash[$exifData['Flash']];
		}
	}

	/**
	 * Format Image Description field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function ImageDescription(&$filtered, $exifData)
	{
		if (isset($exifData['ImageDescription'])) {
			$description = trim($exifData['ImageDescription']);
			if (!empty($description)) {
				$filtered['Image Description'] = $description;
			}
		}
	}

	/**
	 * Format Orientation field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Orientation(&$filtered, $exifData)
	{
		$orientations = array(
			1 => 'Horizontal (normal)'
		);
		if (isset($exifData['Orientation']) && isset($orientations[$exifData['Orientation']])) {
			$filtered['Orientation'] = $orientations[$exifData['Orientation']];
		}
	}

	/**
	 * Format X-Resolution and Y-Resolution fields
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function XYResolution(&$filtered, $exifData)
	{
		$units = array(
			2 => 'dpi',
			3 => 'dpcm'
		);
		$unit = isset($exifData['ResolutionUnit']) && isset($units[$exifData['ResolutionUnit']]) ? $units[$exifData['ResolutionUnit']] : $units[2];
		if (isset($exifData['XResolution'])) {
			$filtered['X-Resolution'] = sprintf('%d %s',
				$this->_evalRational($exifData['XResolution']),
				$unit
			);
		}
		if (isset($exifData['YResolution'])) {
			$filtered['Y-Resolution'] = sprintf('%d %s',
				$this->_evalRational($exifData['YResolution']),
				$unit
			);
		}
	}

	/**
	 * Format Software field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Software(&$filtered, $exifData)
	{
		if (isset($exifData['Software'])) {
			$description = trim($exifData['Software']);
			if (!empty($description)) {
				$filtered['Software'] = $description;
			}
		}
	}

	/**
	 * Format Date and Time field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function DateAndTime(&$filtered, $exifData)
	{
		if (isset($exifData['DateTime'])) {
			$filtered['Date and Time'] = $exifData['DateTime'];
		}
	}

	private function YCbCrPositioning(&$filtered, $exifData)
	{
		$positionings = array(
			1 => 'Centered',
			2 => 'Co-Sited'
		);
		if (isset($exifData['YCbCrPositioning']) && isset($positionings[$exifData['YCbCrPositioning']])) {
			$filtered['YCbCr Positioning'] = $positionings[$exifData['YCbCrPositioning']];
		}
	}

	/**
	 * Format Exposure Program field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function ExposureProgram(&$filtered, $exifData)
	{
		$programs = array(
			1 => 'Manual',
			2 => 'Normal',
			3 => 'Aperture priority',
			4 => 'Shutter priority',
			5 => 'Creative',
			6 => 'Action',
			5 => 'Portrait mode',
			6 => 'Landscape mode'
		);
		if (isset($exifData['ExposureProgram']) && isset($programs[$exifData['ExposureProgram']])) {
			$filtered['Exposure Program'] = $programs[$exifData['ExposureProgram']];
		}
	}

	/**
	 * Format Date and Time (Original) field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function DateAndTimeOriginal(&$filtered, $exifData)
	{
		if (isset($exifData['DateTimeOriginal'])) {
			$filtered['Date and Time'] = $exifData['DateTimeOriginal'];
		}
	}

	/**
	 * Format Date and Time (Digitized) field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function DateAndTimeDigitized(&$filtered, $exifData)
	{
		if (isset($exifData['DateTimeDigitized'])) {
			$filtered['Date and Time (Digitized)'] = $exifData['DateTimeDigitized'];
		}
	}

	/**
	 * Format Compressed Bits per Pixel field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function CompressedBitsPerPixel(&$filtered, $exifData)
	{
		if (isset($exifData['CompressedBitsPerPixel'])) {
			$filtered['Compressed Bits per Pixel'] = sprintf('%01.1f bits',
				$this->_evalRational($exifData['CompressedBitsPerPixel'])
			);
		}
	}

	/**
	 * Format Shutter Speed field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function ShutterSpeed(&$filtered, $exifData)
	{
		if (isset($exifData['ShutterSpeedValue'])) {
			$filtered['Shutter Speed'] = sprintf('%01.3f sec',
				$this->_evalRational($exifData['ShutterSpeedValue'])
			);
		}
	}

	/**
	 * Format Brightness field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Brightness(&$filtered, $exifData)
	{
		if (isset($exifData['BrightnessValue'])) {
			$filtered['Brightness'] = $exifData['BrightnessValue'];
		}
	}

	/**
	 * Format Maximum Lens Aperture field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function MaximumLensAperture(&$filtered, $exifData)
	{
		if (isset($exifData['MaxApertureValue'])) {
			$filtered['Maximum Lens Aperture'] = $exifData['MaxApertureValue'];
		}
	}

	/**
	 * Format Metering Mode field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function MeteringMode(&$filtered, $exifData)
	{
		$modes = array(
			1 => 'Average',
			2 => 'Center Weighted Average',
			3 => 'Spot',
			4 => 'Multispot',
			5 => 'Pattern',
			6 => 'Partial',
			255 => 'Other'
		);
		if (isset($exifData['MeteringMode']) && isset($modes[$exifData['MeteringMode']])) {
			$filtered['Metering Mode'] = $modes[$exifData['MeteringMode']];
		}
	}

	/**
	 * Format Color Space field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function ColorSpace(&$filtered, $exifData)
	{
		$spaces = array(
			1 => 'sRGB'
		);
		if (isset($exifData['ColorSpace']) && isset($spaces[$exifData['ColorSpace']])) {
			$filtered['Color Space'] = $spaces[$exifData['ColorSpace']];
		}
	}

	/**
	 * Format Focal Plane X-Resolution and Focal Plane Y-Resolution fields
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function FocalPlaneXYResolution(&$filtered, $exifData)
	{
		$units = array(
			2 => 'dpi',
			3 => 'dpcm'
		);
		$unit = isset($exifData['FocalPlaneResolutionUnit']) && isset($units[$exifData['FocalPlaneResolutionUnit']]) ? $units[$exifData['FocalPlaneResolutionUnit']] : $units[2];
		if (isset($exifData['FocalPlaneXResolution'])) {
			$filtered['Focal Plane X-Resolution'] = sprintf('%d %s',
				$this->_evalRational($exifData['FocalPlaneXResolution']),
				$unit
			);
		}
		if (isset($exifData['FocalPlaneYResolution'])) {
			$filtered['Focal Plane Y-Resolution'] = sprintf('%d %s',
				$this->_evalRational($exifData['FocalPlaneYResolution']),
				$unit
			);
		}
	}

	/**
	 * Format Bits per Sample field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function BitsPerSample(&$filtered, $exifData)
	{
		if (isset($exifData['THUMBNAIL']['BitsPerSample']) && is_array($exifData['THUMBNAIL']['BitsPerSample'])) {
			$filtered['Bits per Sample'] = implode(', ', $exifData['THUMBNAIL']['BitsPerSample']);
		}
	}

	/**
	 * Format Sensing Method field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function SensingMethod(&$filtered, $exifData)
	{
		$methods = array(
			2 => 'One-chip colour area sensor',
			3 => 'Two-chip colour area sensor',
			4 => 'Three-chip colour area sensor',
			5 => 'Color sequential area sensor',
			7 => 'Trilinear sensor',
			8 => 'Color sequential linear sensor',
		);
		if (isset($exifData['SensingMethod']) && isset($methods[$exifData['SensingMethod']])) {
			$filtered['Sensing Method'] = $methods[$exifData['SensingMethod']];
		}
	}

	/**
	 * Format Compression field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Compression(&$filtered, $exifData)
	{
		$compressions = array(
			1 => 'Uncompressed',
			6 => 'JPEG'
		);
		if (isset($exifData['THUMBNAIL']['Compression']) && isset($compressions[$exifData['THUMBNAIL']['Compression']])) {
			$filtered['Compression'] = $compressions[$exifData['THUMBNAIL']['Compression']];
		}
	}

	/**
	 * Format Firmware Version field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function FirmwareVersion(&$filtered, $exifData)
	{
		if (isset($exifData['SoftwareRelease'])) {
			$filtered['Firmware Version'] = $exifData['SoftwareRelease'];
		}
	}

	/**
	 * Format Photometric Interpretation field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function PhotometricInterpretation(&$filtered, $exifData)
	{
		$interpretations = array(
			2 => 'RGB',
			6 => 'YCbCr'
		);
		if (isset($exifData['THUMBNAIL']['PhotometricInterpretation']) && isset($interpretations[$exifData['THUMBNAIL']['PhotometricInterpretation']])) {
			$filtered['Photometric Interpretation'] = $interpretations[$exifData['THUMBNAIL']['PhotometricInterpretation']];
		}
	}

	/**
	 * Format Strip Offsets field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function StripOffsets(&$filtered, $exifData)
	{
		if (isset($exifData['THUMBNAIL']['StripOffsets'])) {
			$filtered['Strip Offsets'] = $exifData['THUMBNAIL']['StripOffsets'];
		}
	}

	/**
	 * Format Samples per Pixel field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function SamplesPerPixel(&$filtered, $exifData)
	{
		if (isset($exifData['THUMBNAIL']['SamplesPerPixel'])) {
			$filtered['Samples per Pixel'] = $exifData['THUMBNAIL']['SamplesPerPixel'];
		}
	}

	/**
	 * Format Rows per Strip field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function RowsPerStrip(&$filtered, $exifData)
	{
		if (isset($exifData['THUMBNAIL']['RowsPerStrip'])) {
			$filtered['Rows per Strip'] = $exifData['THUMBNAIL']['RowsPerStrip'];
		}
	}

	/**
	 * Format Strip Byte Counts field
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function StripByteCounts(&$filtered, $exifData)
	{
		if (isset($exifData['THUMBNAIL']['StripByteCounts'])) {
			$filtered['Strip Byte Counts'] = $exifData['THUMBNAIL']['StripByteCounts'];
		}
	}

	/**
	 * Format Width and Height fields
	 *
	 * @param array $filtered
	 * @param array $exifData
	 */
	private function Dimensions(&$filtered, $exifData)
	{
		if (isset($exifData['ExifImageWidth'])) {
			$filtered['Width'] = sprintf('%d pixels',
				$this->_evalRational($exifData['ExifImageWidth'])
			);
		}
		if (isset($exifData['ExifImageLength'])) {
			$filtered['Height'] = sprintf('%d pixels',
				$this->_evalRational($exifData['ExifImageLength'])
			);
		}
	}

}