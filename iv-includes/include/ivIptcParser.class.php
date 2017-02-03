<?php

/**
 * Iptc data parser
 * via http://www.sno.phy.queensu.ca/~phil/exiftool/TagNames/IPTC.html
 * via http://www.ozhiker.com/electronics/pjmt/library/list_contents.php4?show_fn=IPTC.php
 *
 * @author McArrow
 */
class ivIptcParser
{

	/**
	 * List of allowed iptc tags and it's names
	 * @var array
	 * You can add or remove IPTC tags by commeting or de-commenting them with //
	 */
	private $_allowedTags = array(
//		'1#000' => 'Envelope Record Version',
		'1#005' => 'Destination',
		'1#020' => 'File Format',
		'1#022' => 'File Version',
		'1#030' => 'Service Identifier',
		'1#040' => 'Envelope Number',
		'1#050' => 'Product ID',
		'1#060' => 'Envelope Priority',
		'1#070' => 'Date Sent',
		'1#080' => 'Time Sent',
		// '1#090' => 'Coded Character Set',
		'1#100' => 'UNO (Unique Name of Object)',
		'1#120' => 'ARM Identifier',
		'1#122' => 'ARM Version',
		// '2#000' => 'Application Record Version',
		'2#003' => 'Object Type Reference',
		'2#004' => 'Object Attribute Reference',
		'2#005' => 'Title',
		'2#007' => 'Edit Status',
		'2#008' => 'Editorial Update',
		'2#010' => 'Urgency',
		'2#012' => 'Subject Reference',
		'2#015' => 'Category',
		'2#020' => 'Supplemental Categories',
		'2#022' => 'Fixture Identifier',
		'2#025' => 'Keywords',
		'2#026' => 'Content Location Code',
		'2#027' => 'Content Location Name',
		'2#030' => 'Release Date',
		'2#035' => 'Release Time',
		'2#037' => 'Expiration Date',
		'2#038' => 'Expiration Time',
		'2#040' => 'Special Instructions',
		'2#042' => 'Action Advised',
		'2#045' => 'Reference Service',
		'2#047' => 'Reference Date',
		'2#050' => 'Reference Number',
		'2#055' => 'Date Created',
		'2#060' => 'Time Created',
		'2#062' => 'Digital Creation Date',
		'2#063' => 'Digital Creation Time',
		'2#065' => 'Originating Program',
		'2#070' => 'Program Version',
		'2#075' => 'Object Cycle',
		'2#080' => 'By-Line (Author)',
		'2#085' => 'By-Line Title (Author Position)',
		'2#090' => 'City',
		'2#092' => 'Sub-Location',
		'2#095' => 'Province/State',
		'2#100' => 'Country/Primary Location Code',
		'2#101' => 'Country/Primary Location Name',
		'2#103' => 'Original Transmission Reference',
		'2#105' => 'Headline',
		'2#110' => 'Credit',
		'2#115' => 'Source',
		'2#116' => 'Copyright Notice',
		'2#118' => 'Contact',
		'2#120' => 'Caption/Abstract',
		'2#122' => 'Writer/Editor',
		'2#125' => 'Rasterized Caption',
		'2#130' => 'Image Type',
		'2#131' => 'Image Orientation',
		'2#135' => 'Language Identifier',
		'2#150' => 'Audio Type',
		'2#151' => 'Audio Sampling Rate',
		'2#152' => 'Audio Sampling Resolution',
		'2#153' => 'Audio Duration',
		'2#154' => 'Audio Outcue',
		'2#184' => 'Job ID',
		'2#185' => 'Master Document ID',
		'2#186' => 'Short Document ID',
		'2#187' => 'Unique Document ID',
		'2#188' => 'Owner ID',
		'2#200' => 'ObjectData Preview File Format',
		'2#201' => 'ObjectData Preview File Version',
		'2#202' => 'ObjectData Preview Data',
		'2#221' => 'Prefs',
		'2#225' => 'Classify State',
		'2#228' => 'Similarity Index',
		'2#230' => 'Document Notes',
		'2#231' => 'Document History',
		'2#232' => 'Exif Camera Info',
		'7#010'  => 'Size Mode',
		'7#020'  => 'Max Subfile Size',
		'7#090'  => 'ObjectData Size Announced',
		'7#095'  => 'Maximum ObjectData Size',
		'8#010'  => 'Subfile',
		'9#010'  => 'Confirmed ObjectData Size'
	);

	/**
	 * Parses iptc data
	 *
	 * @param  array $iptcData
	 * @return array
	 */
	public function parse($iptcData)
	{
		if (is_array($iptcData)) {
			$parsedData = array();
			foreach ($iptcData as $key => $value) {
				if (array_key_exists($key, $this->_allowedTags)) {
					$value = trim(is_array($value) ? ('2#025' == $key ? implode(', ', $value) : $value[0]) : $value);
					switch ($key) {
						case '1#000':
							// break intentionally omitted
						case '1#022':
							$value = hexdec(bin2hex($value));
							break;
						case '1#020':
							// break intentionally omitted
						case '2#200':
							$value = $this->_getFileFormat(hexdec(bin2hex($value)));
							break;
						case '1#090':
							$value = $this->_getCharacterSet($value);
							break;
						case '2#000':
							$value = hexdec(bin2hex($value));
							break;
						case '1#070':
							// break intentionally omitted
						case '2#030':
							// break intentionally omitted
						case '2#037':
							// break intentionally omitted
						case '2#047':
							// break intentionally omitted
						case '2#055':
							// break intentionally omitted
						case '2#062':
							$value = $this->_parseDate($value);
							break;
						case '1#080':
							// break intentionally omitted
						case '2#035':
							// break intentionally omitted
						case '2#038':
							// break intentionally omitted
						case '2#060':
							// break intentionally omitted
						case '2#063':
							$value = $this->_parseTime($value);
							break;
						case '2#042':
							$value = $this->_getActionAdvised($value);
							break;
						case '2#075':
							$value = $this->_getObjectCycle($value);
							break;
						case '2#008':
							$value = $this->_getEditorialUpdate($value);
							break;
						case '2#131':
							$value = $this->_getImageOrientation($value);
							break;
						case '2#150':
							$value = $this->_getAudioType($value);
							break;
						case '2#130':
							$value = $this->_getImageType($value);
							break;
					}
					if ($value) {
						$parsedData[$this->_allowedTags[$key]] = $value;
					}
				}
			}
			return $this->_filter($parsedData);
		} else {
			return false;
		}
	}

	private function _filter(array $data)
	{
		if (isset($data['Date Created']) && !isset($data['Time Created'])) {
			unset($data['Date Created']);
		}

		if (!isset($data['Date Created']) && isset($data['Time Created'])) {
			unset($data['Time Created']);
		}

		if (isset($data['Digital Creation Date']) && !isset($data['Digital Creation Time'])) {
			unset($data['Digital Creation Date']);
		}

		if (!isset($data['Digital Creation Date']) && isset($data['Digital Creation Time'])) {
			unset($data['Digital Creation Time']);
		}

		if (isset($data['Expiration Date']) && !isset($data['Expiration Time'])) {
			unset($data['Expiration Date']);
		}

		if (!isset($data['Expiration Date']) && isset($data['Expiration Time'])) {
			unset($data['Expiration Time']);
		}

		if (isset($data['Release Date']) && !isset($data['Release Time'])) {
			unset($data['Release Date']);
		}

		if (!isset($data['Release Date']) && isset($data['Release Time'])) {
			unset($data['Release Time']);
		}

		if (isset($data['Date Sent']) && !isset($data['Time Sent'])) {
			unset($data['Date Sent']);
		}

		if (!isset($data['Date Sent']) && isset($data['Time Sent'])) {
			unset($data['Time Sent']);
		}

		return $data;
	}

	private function _getFileFormat($format)
	{
		$formats = array(
			'00' => 'No ObjectData',
			'01' => 'IPTC-NAA Digital Newsphoto Parameter Record',
			'02' => 'IPTC7901 Recommended Message Format',
			'03' => 'Tagged Image File Format (Adobe/Aldus Image data)',
			'04' => 'Illustrator (Adobe Graphics data)',
			'05' => 'AppleSingle (Apple Computer Inc)',
			'06' => 'NAA 89-3 (ANPA 1312)',
			'07' => 'MacBinary II',
			'08' => 'IPTC Unstructured Character Oriented File Format (UCOFF)',
			'09' => 'United Press International ANPA 1312 variant',
			'10' => 'United Press International Down-Load Message',
			'11' => 'JPEG File Interchange (JFIF)',
			'12' => 'Photo-CD Image-Pac (Eastman Kodak)',
			'13' => 'Microsoft Bit Mapped Graphics File [*.BMP]',
			'14' => 'Digital Audio File [*.WAV] (Microsoft & Creative Labs)',
			'15' => 'Audio plus Moving Video [*.AVI] (Microsoft)',
			'16' => 'PC DOS/Windows Executable Files [*.COM][*.EXE]',
			'17' => 'Compressed Binary File [*.ZIP] (PKWare Inc)',
			'18' => 'Audio Interchange File Format AIFF (Apple Computer Inc)',
			'19' => 'RIFF Wave (Microsoft Corporation)',
			'20' => 'Freehand (Macromedia/Aldus)',
			'21' => 'Hypertext Markup Language - HTML (The Internet Society)',
			'22' => 'MPEG 2 Audio Layer 2 (Musicom), ISO/IEC',
			'23' => 'MPEG 2 Audio Layer 3, ISO/IEC',
			'24' => 'Portable Document File (*.PDF) Adobe',
			'25' => 'News Industry Text Format (NITF)',
			'26' => 'Tape Archive (*.TAR)',
			'27' => 'Tidningarnas Telegrambyrå NITF version (TTNITF DTD)',
			'28' => 'Ritzaus Bureau NITF version (RBNITF DTD)',
			'29' => 'Corel Draw [*.CDR]'
		);

		return isset($formats[$format]) ? $formats[$format] : "Unknown File Format ($format)";
	}

	private function _getCharacterSet($cs)
	{
		// TODO Add other character sets
		if ("\x1b\x25\x47" == $cs) {
			return 'UTF-8';
		}
	}

	private function _getActionAdvised($action)
	{
		$actions = array(
			'01' => 'Object Kill',
			'02' => 'Object Replace',
			'03' => 'Object Append ',
			'04' => 'Object Reference',
		);

		return isset($actions[$action]) ? $actions[$action] : "Unknown Action ($action)";
	}

	private function _getObjectCycle($cycle)
	{
		$cycles = array(
			'a' => 'Morning',
			'b' => 'Both Morning and Evening',
			'p' => 'Evening',
		);

		return isset($cycles[$cycle]) ? $cycles[$cycle] : "Unknown Cycle ($cycle)";
	}

	private function _parseDate($date)
	{
		if (empty($time)) {
			return false;
		}

		// TODO Set date format from config
		$aDate = unpack('a4Y/a2m/A2d', $date);
		return "{$aDate['d']}-{$aDate['m']}-{$aDate['Y']}";
	}

	private function _parseTime($time)
	{
		if (empty($time)) {
			return false;
		}

		// TODO Set time format from config
		if (6 == strlen($time)) {
			$aTime = unpack('a2H/a2i/A2s', $time);
			$aTime['diff'] = '+';
			$aTime['O'] = '0000';
		} else {
			$aTime = unpack('a2H/a2i/A2s/Adiff/A4O', $time);
		}
		return "{$aTime['H']}:{$aTime['i']}:{$aTime['s']} {$aTime['diff']}{$aTime['O']}";
	}

	private function _getEditorialUpdate($update)
	{
		$updates = array(
			'01' => 'Additional language',
		);

		return isset($updates[$update]) ? $updates[$update] : "Unknown ($update)";
	}

	private function _getImageOrientation($orientation)
	{
		$orientations = array(
			'L' => 'Landscape',
			'P' => 'Portrait',
			'S' => 'Square',
		);

		return isset($orientations[$orientation]) ? $orientations[$orientation] : "Unknown orientation ($orientation)";
	}

	private function _getAudioType($type)
	{
		$types = array(
			'0T' => 'Text Only',
			'1A' => 'Mono Actuality',
			'1C' => 'Mono Question and Answer Session',
			'1M' => 'Mono Music',
			'1Q' => 'Mono Response to a Question',
			'1R' => 'Mono Raw Sound',
			'1S' => 'Mono Scener',
			'1V' => 'Mono Voicer',
			'1W' => 'Mono Wrap',
			'2A' => 'Stereo Actuality',
			'2C' => 'Stereo Question and Answer Session',
			'2M' => 'Stereo Music',
			'2Q' => 'Stereo Response to a Question',
			'2R' => 'Stereo Raw Sound',
			'2S' => 'Stereo Scener',
			'2V' => 'Stereo Voicer',
			'2W' => 'Stereo Wrap',
		);

		return isset($types[$type]) ? $types[$type] : "Unknown audio type ($type)";
	}

	private function _getImageType($type)
	{
		$imageTypeNames = array(
			'M' => 'Monochrome',
			'Y' => 'Yellow Component',
			'M' => 'Magenta Component',
			'C' => 'Cyan Component',
			'K' => 'Black Component',
			'R' => 'Red Component',
			'G' => 'Green Component',
			'B' => 'Blue Component',
			'T' => 'Text Only',
			'F' => 'Full colour composite, frame sequential',
			'L' => 'Full colour composite, line sequential',
			'P' => 'Full colour composite, pixel sequential',
			'S' => 'Full colour composite, special interleaving',
		);

		$objectData = substr($type, 0, 1);
		$imageType = substr($type, 1, 1);

		$result = '';
		if ('0' == $objectData) {
			$result .= 'No Objectdata';
		} elseif ('9' == $objectData) {
			$result .= 'Supplemental objects related to other objectdata';
		} else {
			$result .= "Number of Colour Components: $objectData";
		}

		if (isset($imageTypeNames[$imageType])) {
			$result .= ', ' . $imageTypeNames[$imageType];
		}

		return $result;
	}

}