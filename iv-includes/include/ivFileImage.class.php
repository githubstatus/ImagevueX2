<?php

/**
 * Image file
 *
 * @author McArrow
 */
class ivFileImage extends ivFile
{

	/**
	 * Exif data cache
	 * @var array
	 */
	private $_exifData;

	/**
	 * Iptc data cache
	 * @var array
	 */
	private $_iptcData;

	/**
	 * Xmp data cache
	 * @var array
	 */
	private $_xmpData;

	/**
	 * Properties names
	 * @var array
	 */
	protected $_propertyNames = array(
		'name',
		'date',
		'size',
		'thumbnail',
		'width',
		'height',
		'type',
	);

	protected function _init()
	{
		$this->_userAttributeNames = ivPool::get('conf')->get('/config/imagevue/settings/attributes/image');
	}

	/**
	 * Parses and returns exif data
	 *
	 * @return array
	 */
	public function getExifData()
	{
		if (ivPool::get('conf')->get('/config/imagevue/settings/disableImageMeta')) {
			return array();
		}

		$exifParser = new ivExifParser();
		return $exifParser->parse($this->getRawExifData());
	}

	/**
	 * Parses and returns iptc data
	 *
	 * @return array
	 */
	public function getIptcData()
	{
		if (ivPool::get('conf')->get('/config/imagevue/settings/disableImageMeta')) {
			return array();
		}

		$iptcParser = new ivIptcParser();
		return $iptcParser->parse($this->getRawIptcData());
	}

	/**
	 * Parses and returns xmp data
	 *
	 * @return array
	 */
	public function getXmpData()
	{
		if (ivPool::get('conf')->get('/config/imagevue/settings/disableImageMeta')) {
			return array();
		}

		$xmpParser = new ivXmpParser();
		return $xmpParser->parse($this->getRawXmpData());
	}

	/**
	 * Returns raw Exif data
	 *
	 * @return array
	 */
	public function getRawExifData()
	{
		if (!isset($this->_exifData)) {
			$this->_exifData = $this->_mapper->getRawExifData($this);
		}
		return $this->_exifData;
	}

	/**
	 * Returns raw Iptc data
	 *
	 * @return array
	 */
	public function getRawIptcData()
	{
		if (!isset($this->_itpcData)) {
			$this->_iptcData = $this->_mapper->getRawIptcData($this);
		}
		return $this->_iptcData;
	}

	/**
	 * Returns raw Xmp data
	 *
	 * @return array
	 */
	public function getRawXmpData()
	{
		if (!isset($this->_itpcData)) {
			$this->_xmpData = $this->_mapper->getRawXmpData($this);
		}
		return $this->_xmpData;
	}

	/**
	 * Converts record to XML node
	 *
	 * @param  boolean   $expanded
	 * @return ivXmlNode
	 */
	public function asXml($expanded = true)
	{
		$node = parent::asXml();
		if ($expanded) {
			$exifData = $this->getExifData();
			if (is_array($exifData)) {
				$exifNode = ivXmlNode::create('exif');
				foreach ($exifData as $key => $value) {
					$tag = ivXmlNode::create(str_replace(array(' ', '(', ')'), array('_', '', ''), $key));
					$tag->setValue($value);
					$tag->setAttribute('title', $key);
					$exifNode->addChild($tag);
					unset($tag);
				}
				$node->addChild($exifNode);

				if (is_array($this->getGPSCoords(true))) {
					$gpsNode = ivXmlNode::create('gps');
					foreach ($this->getGPSCoords(true) as $key => $value) {
						$tag = ivXmlNode::create($key);
						$tag->setValue(str_replace(',', '.', $value));
						$gpsNode->addChild($tag);
						unset($tag);
					}

					$node->addChild($gpsNode);
				}
			}
			$iptcData = $this->getIptcData();
			if (is_array($iptcData)) {
				$iptcNode = ivXmlNode::create('iptc');
				foreach ($iptcData as $key => $value) {
					if (is_array($value)) {
						foreach ($value as $v) {
							$tag = ivXmlNode::create(str_replace(array(' ', '(', ')', '/'), array('_', '', ''), $key));
							$tag->setValue($v);
							$tag->setAttribute('title', $key);
							$iptcNode->addChild($tag);
						}
					} else {
						$tag = ivXmlNode::create(str_replace(array(' ', '(', ')', '/'), array('_', '', ''), $key));
						$tag->setValue($value);
						$tag->setAttribute('title', $key);
						$iptcNode->addChild($tag);
					}
					unset($tag);
				}
				$node->addChild($iptcNode);
			}
			$xmpData = $this->getXmpData();
			if (is_array($xmpData)) {
				$xmpNode = ivXmlNode::create('xmp');
				foreach ($xmpData as $key => $value) {
					if (is_array($value)) {
						foreach ($value as $v) {
							$tag = ivXmlNode::create(str_replace(array(' ', '(', ')', '/'), array('_', '', ''), $key));
							$tag->setValue($v);
							$tag->setAttribute('title', $key);
							$xmpNode->addChild($tag);
						}
					} else {
						$tag = ivXmlNode::create(str_replace(array(' ', '(', ')', '/'), array('_', '', ''), $key));
						$tag->setValue($value);
						$tag->setAttribute('title', $key);
						$xmpNode->addChild($tag);
					}
					unset($tag);
				}
				$node->addChild($xmpNode);
			}
		}
		return $node;
	}

	/**
	 * Return file metadata
	 *
	 * @return array
	 */
	public function getMetaData()
	{
		$exif = $this->getExifData();
		$iptc = $this->getIptcData();
		$xmp = $this->getXmpData();

		$metaData = is_array($exif) ? $exif : array();
		$metaData = is_array($iptc) ? array_merge($metaData, $iptc) : $metaData;
		$metaData = is_array($xmp) ? array_merge($metaData, $xmp) : $metaData;

		return $metaData;
	}

	public function cropThumbnail($x, $y, $width, $height, $thumbWidth = null, $thumbHeight = null)
	{
		if (empty($thumbWidth)) {
			$thumbWidth = ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/boxwidth');
		}

		if (empty($thumbHeight)) {
			$thumbHeight = ivPool::get('conf')->get('/config/imagevue/thumbnails/thumbnail/boxheight');
		}

		$this->_data['thumbnail'] = $this->_mapper->cropThumbnail($this, $x, $y, $width, $height, $thumbWidth, $thumbHeight);
	}

	public function getGPSCoords($asArray = false)
	{
		$rawExifData = $this->getRawExifData();

		if (!isset($rawExifData['GPSLatitudeRef']) || !isset($rawExifData['GPSLatitude']) || !isset($rawExifData['GPSLongitudeRef']) || !isset($rawExifData['GPSLongitude'])) {
			return;
		}

		$lat = $this->_evalRational($rawExifData['GPSLatitude'][0]) + $this->_evalRational($rawExifData['GPSLatitude'][1]) / 60 + $this->_evalRational($rawExifData['GPSLatitude'][2]) / 3600;
		if ('S' == $rawExifData['GPSLatitudeRef']) {
			$lat = -$lat;
		}

		$long = $this->_evalRational($rawExifData['GPSLongitude'][0]) + $this->_evalRational($rawExifData['GPSLongitude'][1]) / 60 + $this->_evalRational($rawExifData['GPSLongitude'][2]) / 3600;
		if ('W' == $rawExifData['GPSLongitudeRef']) {
			$long = -$long;
		}

		if ($asArray) {
			$result = array(
				'latitude' => $lat,
				'longitude' => $long,
			);
		} else {
			$result = str_replace(',', '.', $lat) . ',' . str_replace(',', '.', $long);
		}
		return $result;
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

}