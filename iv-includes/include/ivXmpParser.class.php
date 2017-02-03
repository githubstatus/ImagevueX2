<?php

/**
 * Xmp data parser
 *
 * @author McArrow
 */
class ivXmpParser
{

	/**
	 * List of allowed xmp tags and it's names
	 *
	 * You can add or remove XMP tags by commeting or de-commenting them with //
	 *
	 * @var array
	 */
	private $_allowedTags = array(
//		'dc:contributor' => 'Other Contributor(s)',
//		'dc:coverage' => 'Coverage (scope)',
//		'dc:creator' => 'Creator(s) (Authors)',
//		'dc:date' => 'Date',
		'dc:description' => 'Description (Caption)',
//		'dc:format' => 'MIME Data Format',
//		'dc:identifier' => 'Unique Resource Identifer',
//		'dc:language' => 'Language(s)',
//		'dc:publisher' => 'Publisher(s)',
//		'dc:relation' => 'Relations to other documents',
//		'dc:rights' => 'Rights Statement',
//		'dc:source' => 'Source (from which this Resource is derived)',
		'dc:subject' => 'Subject and Keywords',
		'dc:title' => 'Title',
		'dc:type' => 'Resource Type',

//		'xmp:Advisory' => 'Externally Editied Properties',
//		'xmp:BaseURL' => 'Base URL for relative URL\'s',
//		'xmp:CreateDate' => 'Original Creation Date',
//		'xmp:CreatorTool' => 'Creator Tool',
//		'xmp:Identifier' => 'Identifier(s)',
//		'xmp:Label' => 'Label',
//		'xmp:MetadataDate' => 'Metadata Last Modify Date',
//		'xmp:ModifyDate' => 'Resource Last Modify Date',
//		'xmp:Nickname' => 'Nickname',
//		'xmp:Rating' => 'Rating',
//		'xmp:Thumbnails' => 'Thumbnails',

//		'xmpidq:Scheme' => 'Identification Scheme',

		// These are not in spec but Photoshop CS seems to use them
//		'xap:Advisory' => 'Externally Editied Properties',
//		'xap:BaseURL' => 'Base URL for relative URL\'s',
//		'xap:CreateDate' => 'Original Creation Date',
//		'xap:CreatorTool' => 'Creator Tool',
//		'xap:Identifier' => 'Identifier(s)',
//		'xap:MetadataDate' => 'Metadata Last Modify Date',
//		'xap:ModifyDate' => 'Resource Last Modify Date',
//		'xap:Nickname' => 'Nickname',
//		'xap:Thumbnails' => 'Thumbnails',
//		'xapidq:Scheme' => 'Identification Scheme',

//		'xapRights:Certificate' => 'Certificate',
		'xapRights:Copyright' => 'Copyright',
//		'xapRights:Marked' => 'Marked',
//		'xapRights:Owner' => 'Owner',
		'xapRights:UsageTerms' => 'Legal Terms of Usage',
//		'xapRights:WebStatement' => 'Web Page describing rights statement (Owner URL)',

//		'xapBJ:JobRef' => 'Job Reference',

//		'xmpTPg:MaxPageSize' => 'Largest Page Size',
//		'xmpTPg:NPages' => 'Number of pages',

		'pdf:Keywords' => 'Keywords',
//		'pdf:PDFVersion' => 'PDF file version',
//		'pdf:Producer' => 'PDF Creation Tool',

//		'photoshop:AuthorsPosition' => 'Authors Position',
//		'photoshop:CaptionWriter' => 'Caption Writer',
//		'photoshop:Category' => 'Category',
//		'photoshop:City' => 'City',
//		'photoshop:Country' => 'Country',
//		'photoshop:Credit' => 'Credit',
//		'photoshop:DateCreated' => 'Creation Date',
//		'photoshop:Headline' => 'Headline',
//		'photoshop:History' => 'History',                       // Not in XMP spec
//		'photoshop:Instructions' => 'Instructions',
//		'photoshop:Source' => 'Source',
//		'photoshop:State' => 'State',
//		'photoshop:SupplementalCategories' => 'Supplemental Categories',
//		'photoshop:TransmissionReference' => 'Technical (Transmission) Reference',
//		'photoshop:Urgency' => 'Urgency',

//		'crs:AutoBrightness' => 'Camera Auto Brightness',
//		'crs:AutoContrast' => 'Camera Auto Contrast',
//		'crs:AutoExposure' => 'Camera Auto Exposure',
//		'crs:AutoShadows' => 'Camera Auto Shadows',
//		'crs:BlueHue' => 'Camera Blue Hue',
//		'crs:BlueSaturation' => 'Camera Blue Saturation',
//		'crs:Brightness' => 'Camera Brightness',
//		'crs:CameraProfile' => 'Camera Camera Profile',
//		'crs:ChromaticAberrationB' => 'Camera Chromatic Aberration B',
//		'crs:ChromaticAberrationR' => 'Camera Chromatic Aberration R',
//		'crs:ColorNoiseReduction' => 'Camera Color Noise Reduction',
//		'crs:Contrast' => 'Camera Contrast',
//		'crs:CropTop' => 'Camera Crop Top',
//		'crs:CropLeft' => 'Camera Crop Left',
//		'crs:CropBottom' => 'Camera Crop Bottom',
//		'crs:CropRight' => 'Camera Crop Right',
//		'crs:CropAngle' => 'Camera Crop Angle',
//		'crs:CropWidth' => 'Camera Crop Width',
//		'crs:CropHeight' => 'Camera Crop Height',
//		'crs:CropUnits' => 'Camera Crop Units',
//		'crs:Exposure' => 'Camera Exposure',
//		'crs:GreenHue' => 'Camera Green Hue',
//		'crs:GreenSaturation' => 'Camera Green Saturation',
//		'crs:HasCrop' => 'Camera Has Crop',
//		'crs:HasSettings' => 'Camera Has Settings',
//		'crs:LuminanceSmoothing' => 'Camera Luminance Smoothing',
//		'crs:RawFileName' => 'Camera Raw File Name',
//		'crs:RedHue' => 'Camera Red Hue',
//		'crs:RedSaturation' => 'Camera Red Saturation',
//		'crs:Saturation' => 'Camera Saturation',
//		'crs:Shadows' => 'Camera Shadows',
//		'crs:ShadowTint' => 'Camera Shadow Tint',
//		'crs:Sharpness' => 'Camera Sharpness',
//		'crs:Temperature' => 'Camera Temperature',
//		'crs:Tint' => 'Camera Tint',
//		'crs:ToneCurve' => 'Camera Tone Curve',
//		'crs:ToneCurveName' => 'Camera Tone Curve Name',
//		'crs:Version' => 'Camera Version',
//		'crs:VignetteAmount' => 'Camera Vignette Amount',
//		'crs:VignetteMidpoint' => 'Camera Vignette Midpoint',
		'crs:WhiteBalance' => 'Camera White Balance',

//		'tiff:ImageWidth' => 'Image Width',
//		'tiff:ImageLength' => 'Image Length',
//		'tiff:BitsPerSample' => 'Bits Per Sample',
//		'tiff:Compression' => 'Compression',
//		'tiff:PhotometricInterpretation' => 'Photometric Interpretation',
//		'tiff:Orientation' => 'Orientation',
//		'tiff:SamplesPerPixel' => 'Samples Per Pixel',
//		'tiff:PlanarConfiguration' => 'Planar Configuration',
//		'tiff:YCbCrSubSampling' => 'YCbCr Sub-Sampling',
//		'tiff:YCbCrPositioning' => 'YCbCr Positioning',
//		'tiff:XResolution' => 'X Resolution',
//		'tiff:YResolution' => 'Y Resolution',
//		'tiff:ResolutionUnit' => 'Resolution Unit',
//		'tiff:TransferFunction' => 'Transfer Function',
//		'tiff:WhitePoint' => 'White Point',
//		'tiff:PrimaryChromaticities' => 'Primary Chromaticities',
//		'tiff:YCbCrCoefficients' => 'YCbCr Coefficients',
//		'tiff:ReferenceBlackWhite' => 'Black & White Reference',
//		'tiff:DateTime' => 'Date & Time',
//		'tiff:ImageDescription' => 'Image Description',
//		'tiff:Make' => 'Make',
//		'tiff:Model' => 'Model',
//		'tiff:Software' => 'Software',
//		'tiff:Artist' => 'Artist',
//		'tiff:Copyright' => 'Copyright',

		'aux:Lens' => 'Lens',
		'aux:LensInfo' => 'Lens Info',
//		'aux:LensID' => 'Lens ID',
		'aux:ApproximateFocusDistance' => 'Approximate Focus Distance',
//		'aux:SerialNumber' => 'Serial Number of Camera',

//		'stDim:w' => 'Width',
//		'stDim:h' => 'Height',
//		'stDim:unit' => 'Units',

//		'xapGImg:height' => 'Height',
//		'xapGImg:width' => 'Width',
//		'xapGImg:format' => 'Format',
//		'xapGImg:image' => 'Image',

//		'stVer:comments' => '',
//		'stVer:event' => '',
//		'stVer:modifyDate' => '',
//		'stVer:modifier' => '',
//		'stVer:version' => '',

//		'stJob:name' => 'Job Name',
//		'stJob:id' => 'Unique Job ID',
//		'stJob:url' => 'URL for External Job Management File',
	);

	/**
	 * Parses xmp data
	 *
	 * @param  string $xmpData
	 * @return array
	 */
	public function parse($xmpData)
	{
		if (is_string($xmpData)) {
			$parsedData = array();
			foreach ($this->_allowedTags as $name => $title) {
				if (preg_match('/\<' . preg_quote($name, '-') . '\>([^\<\>]*?)\<\/' . preg_quote($name, '-') . '\>/', $xmpData, $m)) {
					if (!empty($m[1]) && (strlen(trim($m[1]))) > 0) {
						$parsedData[$title] = $m[1];
					}
				} else if (preg_match('/(?<=\s)' . preg_quote($name, '-') . '\=\"(.*?)\"/', $xmpData, $m)) {
					if (!empty($m[1]) && (strlen(trim($m[1]))) > 0) {
						$parsedData[$title] = $m[1];
					}
				}
			}
			return $parsedData;
		} else {
			return false;
		}
	}

}