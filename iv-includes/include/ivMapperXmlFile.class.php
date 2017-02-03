<?php

class ivMapperXmlFile extends ivMapperXmlAbstract
{

	public function find($id)
	{
		$id = ivPath::canonizeAbsolute(ROOT_DIR . ivPath::canonizeRelative($this->_conf->get('/config/imagevue/settings/contentfolder')) . $id, true);

		$contentDir = ivPath::canonizeAbsolute(ROOT_DIR . ivPath::canonizeRelative($this->_conf->get('/config/imagevue/settings/contentfolder')));
		if (substr($id, 0, strlen($contentDir)) != $contentDir) {
			return false;
		}

		$sId = str_replace('//', '/', str_replace('\\', '/', substr($id, strlen(ROOT_DIR))));
		if (isset($this->_collection[$sId])) {
			$item = $this->_collection[$sId]['record'];
		} elseif (is_file($id) && (ivFilepath::matchSuffix($id, $this->_conf->get('/config/imagevue/settings/allowedext')) && !ivFilepath::matchPrefix($id, $this->_conf->get('/config/imagevue/settings/excludefilesprefix')))) {
			if (self::isImage($id)) {
				$item = new ivFileImage($this);
			} else if ('mp3' == strtolower(ivFilepath::suffix($id))) {
				$item = new ivFileMP3($this);
			} else if (in_array( strtolower(ivFilepath::suffix($id)), $this->_conf->get('/config/imagevue/settings/videofilesext'))) {
				$item = new ivFileVideo($this);
			} else {
				$item = new ivFile($this);
			}

			$item->setPrimary(str_replace('//', '/', str_replace('\\', '/', substr($id, strlen(ROOT_DIR . $this->_getContentDirPath())))));

			$this->_initImageProperties($item, $id);

			$this->_initVideoProperties($item, $id);

			$item->name = ivFilepath::basename($id);
			$item->date = filectime($id);
			$item->size = filesize($id);

			$thumbnailPath = $this->_getThumbnailPath($item);
			if (!file_exists($thumbnailPath)) {
				$thumbnailPath = BASE_DIR . 'images/thumbs/' . strtolower(ivFilepath::suffix($item->name)) . '.jpg';
				if (!file_exists($thumbnailPath)) {
					$thumbnailPath = BASE_DIR . 'images/defaultThumb.jpg';
				}
			}

			$item->thumbnail = str_replace('//', '/', str_replace('\\', '/', substr($thumbnailPath, strlen(ROOT_DIR))));

			$xml = ivMapperXmlAbstract::_getFolderdataXml(dirname($id) . '/');

			$fileNode = $this->_getXmlNode($xml, $item->name);

			foreach ($item->getAttributeNames() as $name) {
				if ($fileNode[$name] || (null !== $fileNode[$name])) {
					$item->$name = (string) $fileNode[$name];
				}

			}

			foreach ($item->getUserAttributeNames() as $name) {
				if ($fileNode[$name] || (null !== $fileNode[$name])) {
					$item->$name = (string) $fileNode[$name];
				}
			}

			$this->_initImageAttributes($item, $id);

			$item->setState(ivRecord::STATE_CLEAN);

			$this->_collection[$item->getPrimary()] = array('record' => $item);
		} else {
			$item = false;
		}
		return $item;
	}

	public static function isImage($path)
	{
		if (!self::isImagePath($path)) return false;

		$supportedTypes = array(
			'image/gif',
			'image/jpeg',
			'image/tiff',
			'image/png'
		);

		$data = iv_getimagesize($path);
		return in_array($data['mime'], $supportedTypes);
	}

	public static function isImagePath($path)
	{
		if (in_array(strtolower(ivFilepath::suffix($path)), array('gif', 'jpeg', 'jpg', 'tif', 'tiff', 'png'))) {
			return true;
		}

		return false;

	}

	private function _initImageProperties(ivFile $record, $id)
	{
		if (!is_a($record, 'ivFileImage')) {
			return;
		}

		$imageSizeData = iv_getimagesize($id);
		$record->width = $imageSizeData[0];
		$record->height = $imageSizeData[1];
		$record->type = imageTypeToString($imageSizeData[2]);

		$exifData = $record->getRawExifData();
		if (is_array($exifData)) {
			$pattern = '/^(\d{4})\:(\d{2})\:(\d{2})\s+?(\d{2})\:(\d{2})\:(\d{2})$/';
			if (isset($exifData['DateTimeOriginal']) && preg_match($pattern, $exifData['DateTimeOriginal'], $m)) {
				$record->date = mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
			} else if (isset($exifData['DateTime']) && preg_match($pattern, $exifData['DateTime'], $m)) {
				$record->date = mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
			}
		}
	}

	private function _initVideoProperties(ivFile $record, $id)
	{
		if (is_a($record, 'ivFileVideo')) {
	    $getID3 = new getID3();
	    $data = $getID3->analyze(ROOT_DIR . $record->getPath());
	    if (isset($data['video']['resolution_x']) && isset($data['video']['resolution_x'])) {
	    	$record->width = $data['video']['resolution_x'];
				$record->height = $data['video']['resolution_y'];
	    }
	  }
	}

	private function _getThumbnailPath(ivFile $record)
	{
		return dirname(ROOT_DIR . $this->_getContentDirPath() . $record->getPrimary()) . DS . ivMapperXmlAbstract::THUMBNAIL_PREFIX . ivFilepath::filename($record->name) . '.jpg';
	}

	private function _initImageAttributes(ivFile $record, $id)
	{
		if (!is_a($record, 'ivFileImage')) {
			return;
		}

		if ((is_null($record->title) || is_null($record->description))) {
			$iptcData = $record->getRawIptcData();
			if (is_array($iptcData)) {
				if (is_null($record->title) && isset($iptcData['2#005'][0]) && $this->_conf->get('/config/imagevue/settings/autoTitling')) {
					$title = trim($iptcData['2#005'][0]);
					if (!empty($title)) {
						$record->title = stripNonUtf8Chars($title);
					}
				}
				if (is_null($record->title) && isset($iptcData['2#105'][0]) && $this->_conf->get('/config/imagevue/settings/autoTitling')) {
					$title = trim($iptcData['2#105'][0]);
					if (!empty($title)) {
						$record->title = stripNonUtf8Chars($title);
					}
				}
				if (is_null($record->description) && isset($iptcData['2#120'][0])) {
					$description = trim($iptcData['2#120'][0]);
					if (!empty($description)) {
						$record->description = stripNonUtf8Chars($description);
					}
				}
			}
		}
		if ((is_null($record->title) || is_null($record->description))) {
			$exifData = $record->getRawExifData();
			if (is_array($exifData)) {
				if (is_null($record->title) && isset($exifData['DocumentName']) && $this->_conf->get('/config/imagevue/settings/autoTitling')) {
					$title = trim($exifData['DocumentName']);
					if (!empty($title)) {
						$record->title = stripNonUtf8Chars($title);
					}
				}
				if (is_null($record->description) && isset($exifData['ImageDescription'])) {
					$description = trim($exifData['ImageDescription']);
					if (!empty($description) && 'IF' != $description) {
						$record->description = stripNonUtf8Chars($description);
					}
				}
			}
		}
	}

	/**
	 * Generates thumbnail
	 *
	 */
	public function generateThumbnail(ivFile $record, $boxWidth, $boxHeight, $resizeType)
	{
		$thumbPath = $this->_getThumbnailPath($record);

		if (is_a($record, 'ivFileImage')) {

			$img = new ivImage(ROOT_DIR . $record->getPath());
			if ($img->thumbnail($boxWidth, $boxHeight, $resizeType)) {
				$img->write($thumbPath);
				iv_touch($thumbPath);
				iv_touch(dirname($thumbPath));
			}
		} else {
			$defaultThumbPath = BASE_DIR . 'images/thumbs/' . strtolower(ivFilepath::suffix($record->name)) . '.jpg';
			if (!is_file($defaultThumbPath)) {
				$defaultThumbPath = BASE_DIR . 'images/defaultThumb.jpg';
			}

	    if (ivFilepath::matchSuffix($record->name, ivPool::get('conf')->get('/config/imagevue/settings/videofilesext'))) {
	    	$ffmpeg = ivPool::get('conf')->get('/config/imagevue/settings/ffmpegPath');
	    	$ffmpeg = $ffmpeg?$ffmpeg:'ffmpeg';
	    	exec($ffmpeg . ' -i "' . addcslashes(ROOT_DIR . $record->getPath() ,'"') . '" -an -f mjpeg -y -r 1 -vframes 1 -ss 00:00:03 -s '.$boxWidth.'x'.$boxHeight.' -an "'. addcslashes($thumbPath,'"') .'"  2>&1');

	      if (!file_exists($thumbPath)) @copy($defaultThumbPath, $thumbPath);
	     }else{
	      @copy($defaultThumbPath, $thumbPath);
	    }

		}
		return str_replace('//', '/', str_replace('\\', '/', substr($thumbPath, strlen(ROOT_DIR))));
	}

	/**
	 * Crop thumbnail with given params
	 *
	 */
	public function cropThumbnail(ivFileImage $record, $x, $y, $width, $height, $thumbWidth, $thumbHeight)
	{
		$thumbPath = $this->_getThumbnailPath($record);

		$img = new ivImage(ROOT_DIR . $this->_getContentDirPath() . $record->getPrimary());
		if ($img->cropToSize($x, $y, $width, $height, $thumbWidth, $thumbHeight)) {
			$img->write($thumbPath);
			iv_touch($thumbPath);
			iv_touch(dirname($thumbPath));
		}
		return str_replace('//', '/', str_replace('\\', '/', substr($thumbPath, strlen(ROOT_DIR))));
	}

	public function save(ivRecord $file)
	{
		$xml = ivMapperXmlAbstract::_getFolderdataXml(ROOT_DIR . dirname($file->getPath()) . '/');

		$fileNode = $this->_getXmlNode($xml, $file->name);

		$attributes = array();
		foreach ($file->getAttributeNames() as $name) {
			$fileNode->setAttribute($name, $file->$name);
		}
		foreach ($file->getUserAttributeNames() as $name) {
			$fileNode->setAttribute($name, $file->$name);
		}

		return ivMapperXmlAbstract::_saveFolderdataXml(ROOT_DIR . dirname($file->getPath()) . '/');
	}

	private function _getXmlNode(ivSimpleXMLElement $xml, $name)
	{
		if (isset($xml->file)) {
			foreach ($xml->file as $fNode) {
				if ($fNode['name'] == $name) {
					$fileNode = $fNode;
					break;
				}
			}
		}

		if (!isset($fileNode)) {
			$fileNode = $xml->addChild('file');
			$fileNode->setAttribute('name', $name);
		}

		return $fileNode;
	}

	public function delete(ivRecord $record)
	{
		$this->deleteThumbnail($record);

		$xml = ivMapperXmlAbstract::_getFolderdataXml(ROOT_DIR . dirname($record->getPath()) . '/');

		$fileNode = $this->_getXmlNode($xml, $record->name);

		if ($xml->removeNode($fileNode)) {
			ivMapperXmlAbstract::_saveFolderdataXml(ROOT_DIR . dirname($record->getPath()) . '/');
		}

		unset($this->_collection[$record->getPrimary()]);

		return @unlink(ROOT_DIR . $record->getPath());
	}

	public function deleteThumbnail(ivRecord $record)
	{
		@unlink($this->_getThumbnailPath($record));
	}

	/**
	 * Parses and returns id3 data
	 *
	 * @return array
	 */
	public function getRawId3Data(ivFileMP3 $record)
	{
		if (ini_get('safe_mode') || !function_exists('iconv')) {
			return array();
		}

		$disallowedTags = array(
			'music_cd_identifier'
		);

		$getID3 = new getID3();
		$getID3->setOption(array('encoding' => 'UTF-8', 'encoding_id3v1' => 'UTF-8'));
		$data = $getID3->analyze(ROOT_DIR . $record->getPath());
		$tags = array();
		if (isset($data['tags']['id3v1'])) {
			foreach ($data['tags']['id3v1'] as $tag => $value) {
				if (!in_array($tag, $disallowedTags)) {
					$tags[ucwords(str_replace('_', ' ', $tag))] = $value[0];
				}
			}
		}
		if (isset($data['tags']['id3v2'])) {
			foreach ($data['tags']['id3v2'] as $tag => $value) {
				if (!in_array($tag, $disallowedTags)) {
					$tags[ucwords(str_replace('_', ' ', $tag))] = $value[0];
				}
			}
		}
		return $tags;
	}

	public function getRawExifData(ivFileImage $record)
	{
		if (function_exists('exif_read_data')) {
			ivErrors::disable();
			$data = @exif_read_data(ROOT_DIR . $this->_getContentDirPath() . $record->getPrimary());
			ivErrors::enable();
			return $this->_filterExifData($data);
		}
		return array();
	}

	private function _filterExifData($data)
	{
		if (!is_array($data)) {
			return $data;
		}

		$allowedTags = array(
			'Make',
			'Model',
			'ExposureTime',
			'FNumber',
			'FocalLength',
			'ISOSpeedRatings',
			'ExposureBiasValue',
			'Flash',
			'ImageDescription',
			'Orientation',
			'ResolutionUnit',
			'XResolution',
			'YResolution',
			'Software',
			'DateTime',
			'YCbCrPositioning',
			'Copyright',
			'ExposureProgram',
			'DateTimeOriginal',
			'DateTimeDigitized',
			'CompressedBitsPerPixel',
			'ShutterSpeedValue',
			'BrightnessValue',
			'MaxApertureValue',
			'SubjectDistance',
			'MeteringMode',
			'ColorSpace',
			'FocalPlaneResolutionUnit',
			'FocalPlaneXResolution',
			'FocalPlaneYResolution',
			'ExposureIndex',
			'SensingMethod',
			'Quality',
			'JPEGQuality',
			'ColorMode',
			'ImageAdjustment',
			'Focus',
			'DigitalZoom',
			'WhiteBalance',
			'Sharpness',
			'FlashMode',
			'FlashStrength',
			'PictureMode',
			'SoftwareRelease',
			'ImageType',
			'ImageNumber',
			'OwnerName',
			'ExifImageWidth',
			'ExifImageLength',
			'DocumentName',
			'GPSLatitudeRef',
			'GPSLatitude',
			'GPSLongitudeRef',
			'GPSLongitude',
		);

		$filteredData = array();
		foreach ($data as $key => $value) {
			if (in_array($key, $allowedTags)) {
				$filteredData[$key] = $value;
			}
		}

		if (isset($data['THUMBNAIL']) && is_array($data['THUMBNAIL'])) {
			$filteredData['THUMBNAIL'] = array();

			$allowedThumbTags = array(
				'BitsPerSample',
				'Compression',
				'PhotometricInterpretation',
				'StripOffsets',
				'SamplesPerPixel',
				'RowsPerStrip',
				'StripByteCounts',
			);

			foreach ($data['THUMBNAIL'] as $key => $value) {
				if (in_array($key, $allowedThumbTags)) {
					$filteredData['THUMBNAIL'][$key] = $value;
				}
			}
		}

		return $filteredData;
	}

	public function getRawIptcData(ivFileImage $record)
	{
		if ($this->_conf->get('/config/imagevue/settings/disableImageMeta')) {
			return array();
		}

		if (function_exists('iptcparse') && function_exists('getimagesize')) {
			ivErrors::disable();
			$data = array();
			iv_getimagesize(ROOT_DIR . $this->_getContentDirPath() . $record->getPrimary(), $info);
			if (isset($info['APP13'])) {
				$data = @iptcparse($info['APP13']);
			}
			ivErrors::enable();
			return $data;
		}
		return array();
	}

	public function getRawXmpData(ivFileImage $record)
	{
		$adobeXmpData = false;

		// Attempt to open the jpeg file - the at symbol supresses the error message about
		// not being able to open files. The file_exists would have been used, but it
		// does not work with files fetched over http or ftp.
		$handle = @fopen(ROOT_DIR . $this->_getContentDirPath() . $record->getPrimary(), 'rb');

		// Check if the file opened successfully
		if (!$handle) {
			return false;
		}

		// Read the first two characters
		$data = fread($handle, 2);

		// Check that the first two characters are 0xFF 0xDA (SOI - Start of image)
		if ($data != "\xFF\xD8") {
			// No SOI (FF D8) at start of file - This probably isn't a JPEG file - close file and return;
			fclose($handle);
			return false;
		}

		// Read the third character
		$data = fread($handle, 2);

		// Check that the third character is 0xFF (Start of first segment header)
		if ($data{0} != "\xFF") {
			// NO FF found - close file and return - JPEG is probably corrupted
			fclose($handle);
			return false;
		}

		// Flag that we havent yet hit the compressed image data
		$hit_compressed_image_data = FALSE;

		// Cycle through the file until, one of: 1) an EOI (End of image) marker is hit,
		//                                       2) we have hit the compressed image data (no more headers are allowed after data)
		//                                       3) or end of file is hit
		while (($data{1} != "\xD9") && (!$hit_compressed_image_data) && (!feof($handle))) {
			// Found a segment to look at.
			// Check that the segment marker is not a Restart marker - restart markers don't have size or data after them
			if ((ord($data{1}) < 0xD0) || (ord($data{1}) > 0xD7)) {
				// Segment isn't a Restart marker
				// Read the next two bytes (size)
				$sizestr = fread($handle, 2);

				// convert the size bytes to an integer
				$decodedsize = unpack("nsize", $sizestr);

				// Save the start position of the data
				$segdatastart = ftell($handle);

				// Read the segment data with length indicated by the previously read size
				$segdata = fread($handle, $decodedsize['size'] - 2);

				// Store the segment information in the output array
				if (0xE1 == ord($data{1}) && strncmp($segdata, "http://ns.adobe.com/xap/1.0/\x00", 29) == 0) {
					$adobeXmpData = substr($segdata, 29);
					fclose($handle);
					return $adobeXmpData;
				}
			}

			// If this is a SOS (Start Of Scan) segment, then there is no more header data - the compressed image data follows
			if ($data{1} == "\xDA") {
				// Flag that we have hit the compressed image data - exit loop as no more headers available.
				$hit_compressed_image_data = TRUE;
			} else {
				// Not an SOS - Read the next two bytes - should be the segment marker for the next segment
				$data = fread($handle, 2);

				// Check that the first byte of the two is 0xFF as it should be for a marker
				if ($data{0} != "\xFF") {
					// NO FF found - close file and return - JPEG is probably corrupted
					fclose($handle);
					return false;
				}
			}
		}

		fclose($handle);
		return false;
	}

	public function copyFile(ivFile $file, ivFolder $folder)
	{
		$mode = 0666;

		$result = copy(ROOT_DIR . $file->getPath(), ROOT_DIR . $folder->getPath() . $file->name);
		iv_chmod(ROOT_DIR . $folder->getPath() . $file->name, $mode);

		if (!$newFile = $this->find($folder->getPrimary() . $file->name)) {
			return false;
		}

		@copy($this->_getThumbnailPath($file), $this->_getThumbnailPath($newFile));
		iv_chmod($this->_getThumbnailPath($newFile), $mode);

		foreach ($file->getAttributeNames() as $name) {
			$newFile->$name = $file->$name;
		}
		foreach ($file->getUserAttributeNames() as $name) {
			$newFile->$name = $file->$name;
		}
		$newFile->save();

		return $result;
	}

	public function rename(ivFile $file, $newName)
	{
		$mode = 0666;

		if (ivFilepath::matchSuffix($newName, array('php', 'phtml', 'htm', 'html', 'js'))) return false;

		$result = copy(ROOT_DIR . $file->getPath(), ROOT_DIR . $file->Parent->getPath() . $newName);
		iv_chmod(ROOT_DIR . $file->Parent->getPath() . $newName, $mode);

		if (!$newFile = $this->find($file->Parent->getPrimary() . $newName)) {
			return false;
		}

		@copy($this->_getThumbnailPath($file), $this->_getThumbnailPath($newFile));
		iv_chmod($this->_getThumbnailPath($newFile), $mode);

		foreach ($file->getAttributeNames() as $name) {
			$newFile->$name = $file->$name;
		}
		foreach ($file->getUserAttributeNames() as $name) {
			$newFile->$name = $file->$name;
		}
		$newFile->save();

		$result &= $file->delete();

		return $result;
	}

}