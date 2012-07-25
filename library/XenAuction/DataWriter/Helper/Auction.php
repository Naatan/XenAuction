<?php

/**
 * Auction datawriter helpers
 *
 * Mostly used to verify data, but also to upload images (for lack of a better place to put this)
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_DataWriter_Helper_Auction
{
	
	/**
	 * Verify auction ID
	 * 
	 * @param int            		$auction_id 
	 * @param XenForo_DataWriter 	$dw         
	 * @param string|bool 			$fieldName  
	 * 
	 * @return bool               
	 */
	public static function verifyAuctionId(&$auction_id, XenForo_DataWriter $dw, $fieldName = false)
	{
		$db = XenForo_Application::getDb();
		$existing_auction_id = $db->fetchOne('
				SELECT auction_id
				FROM xf_auction
				WHERE auction_id = ?
			', $auction_id);
		
		if ($existing_auction_id == $auction_id)
		{
			return true;
		}
		
		$dw->error(new XenForo_Phrase('requested_auction_not_found'), $fieldName);
		return false;
	}
	
	/**
	 * Verify expiration date
	 * 
	 * @param int            			$time      
	 * @param XenForo_DataWriter 		$dw        
	 * @param string|bool            	$fieldName 
	 * 
	 * @return bool               
	 */
	public static function verifyExpirationDate(&$time, XenForo_DataWriter $dw, $fieldName = false)
	{
		if ($time > time() OR (time() - $time) < 3600)
		{
			return true;
		}
		
		$dw->error(new XenForo_Phrase('date_is_in_past'), $fieldName);
		return false;
	}
	
	/**
	 * Verify availability
	 * 
	 * @param int      			     	$availability 
	 * @param XenForo_DataWriter 		$dw           
	 * @param string|bool       	    $fieldName    
	 * 
	 * @return bool
	 */
	public static function verifyAvailability(&$availability, XenForo_DataWriter $dw, $fieldName = false)
	{
		// If this is a new entry with a buyout and no availability defined
		if ($dw->isInsert() AND $dw->get('buy_now') != NULL AND (int) $availability <= 0)
		{
			$dw->error(new XenForo_Phrase('enter_availability_higher_than_zero'), $fieldName);
			return false;	
		}
		
		// If this is a new entry with bids enabled and an availability higher than 1
		if ($dw->isInsert() AND $dw->get('min_bid') != NULL AND (int) $availability > 1)
		{
			$dw->error(new XenForo_Phrase('availability_too_high'), $fieldName);
			return false;	
		}
		
		return true;
	}
	
	/**
	 * Assert whether the uploaded image is valid (dimensions, filesize, filetype, etc)
	 * 
	 * @param XenForo_Upload $upload 
	 * 
	 * @return void           Throws XenForo_Exception when invalid
	 */
	protected static function assertValidImage(XenForo_Upload $upload)
	{
		// Validate using build in validation
		if ( ! $upload->isValid())
		{
			throw new XenForo_Exception($upload->getErrors(), true);
		}
		
		// Validate that the upload is an image
		if ( ! $upload->isImage())
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		};
		
		// Validate the file type
		$imageType = $upload->getImageInfoField('type');
		if ( ! in_array($imageType, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}
		
		// Parse image/file info
		$fileName 	= $upload->getTempFile();
		$imageInfo 	= getimagesize($fileName);
		
		// Validate that image info could be retrieved
		if ( ! $imageInfo)
		{
			throw new XenForo_Exception('Non-image passed in to ' . __CLASS__ . '::' . __METHOD__);
		}
		
		// Parse image info
		$width 		= $imageInfo[0];
		$height 	= $imageInfo[1];
		
		// Validate whether we are able to resize this image
		if ( ! XenForo_Image_Abstract::canResize($width, $height))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_image_is_too_big'), true);
		}
		
		// require 2:1 aspect ratio or squarer
		if ($width > 2 * $height || $height > 2 * $width)
		{
			throw new XenForo_Exception(new XenForo_Phrase('please_provide_an_image_whose_longer_side_is_no_more_than_twice_length'), true);
		}
	}
	
	/**
	 * Save and process image
	 * 
	 * @param XenForo_Upload $upload 
	 * 
	 * @return string           Returns the relevant portion of the image path (excludes xenfor data prefix and extension / size suffix)
	 */
	public static function saveImage(XenForo_Upload $upload)
	{
		// Validate the upload
		self::assertValidImage($upload);
		
		// Get image info
		$fileName 	= $upload->getTempFile();
		$imageType 	= $upload->getImageInfoField('type');
		$width 		= $upload->getImageInfoField('width');
		$height 	= $upload->getImageInfoField('height');
		
		// Prepare output information
		$sizes 		= array('l' => 768, 'n' => 150, 'm' => 120, 's' => 48, 't' => 32);
		$outputFiles= array();
		$outputType = $imageType;
		
		// Determine shortest side of the image
		reset($sizes);
		list($sizeCode, $maxDimensions) = each($sizes);
		$shortSide = ($width > $height ? $height : $width);
		
		// Determine whether the image exceeds the maximum possible dimensions
		if ($shortSide > $maxDimensions)
		{
			// Prepare new image
			$newTempFile 	= tempnam(XenForo_Helper_File::getTempDir(), 'xf');
			$image 			= XenForo_Image_Abstract::createFromFile($fileName, $imageType);
			
			// Validate if image could be created
			if ( ! $image)
			{
				throw new XenForo_Exception(new XenForo_Phrase('image_could_be_processed_try_another_contact_owner'), true);
			}
			
			// Scale down the image
			$image->thumbnailFixedShorterSide($maxDimensions);
			$image->output($outputType, $newTempFile, 85);
			
			// Set new width / height values
			$width 	= $image->getWidth();
			$height = $image->getHeight();
			
			$outputFiles[$sizeCode] = $newTempFile;
		}
		else
		{
			$outputFiles[$sizeCode] = $fileName;
		}
		
		// Prepare array used for storing crop dimensions
		$crop = array(
			'x' => array(),
			'y' => array(),
		);
		
		// Loop through the different image sizes we want to generate
		while (list($sizeCode, $maxDimensions) = each($sizes))
		{
			// Create new image
			$newTempFile 	= tempnam(XenForo_Helper_File::getTempDir(), 'xf');
			$image 			= XenForo_Image_Abstract::createFromFile($fileName, $imageType);
			
			// Validate if image could be created
			if ( ! $image)
			{
				throw new XenForo_Exception(new XenForo_Phrase('image_could_be_processed_try_another_contact_owner'), true);
			}
			
			// Resize image
			$image->thumbnailFixedShorterSide($maxDimensions);
			
			// Make image square if not already
			if ($image->getOrientation() != XenForo_Image_Abstract::ORIENTATION_SQUARE)
			{
				$crop['x'][$sizeCode] = floor(($image->getWidth() - $maxDimensions) / 2);
				$crop['y'][$sizeCode] = floor(($image->getHeight() - $maxDimensions) / 2);
				$image->crop($crop['x'][$sizeCode], $crop['y'][$sizeCode], $maxDimensions, $maxDimensions);
			}
			
			// Generate the new image
			$image->output($outputType, $newTempFile, 85);
			unset($image);
			
			$outputFiles[$sizeCode] = $newTempFile;
		}
		
		// Parse image path and name
		$imagePath = sprintf('%d/%s',
			date('Ymd'),
			md5(uniqid())
		);
		
		// Loop through output files so they can be written to the filesystem
		foreach ($outputFiles AS $sizeCode => $tempFile)
		{
			// Parse filepath
			$filePath = sprintf('%s/xenauction/%s_%s.jpg',
				XenForo_Helper_File::getExternalDataPath(),
				$imagePath,
				$sizeCode
			);
			
			// Parse file directory
			$directory = dirname($filePath);
			
			// Create the directory
			if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
			{
				// Save file in directory
				if (rename($tempFile, $filePath))
				{
					XenForo_Helper_File::makeWritableByFtpUser($filePath);
				}
			}
		}
		
		// Return relevant portion of image path
		return $imagePath;
	}
	
}