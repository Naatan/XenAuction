<?php

class XenAuction_DataWriter_Helper_Auction
{
	
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
	
	public static function verifyExpirationDate(&$time, XenForo_DataWriter $dw, $fieldName = false)
	{
		if ($time > time() OR (time() - $time) < 3600)
		{
			return true;
		}
		
		$dw->error(new XenForo_Phrase('date_is_in_past'), $fieldName);
		return false;
	}
	
	public static function verifyAvailability(&$availability, XenForo_DataWriter $dw, $fieldName = false)
	{
		if ($dw->isInsert() AND $dw->get('buy_now') != NULL AND (int) $availability <= 0)
		{
			$dw->error(new XenForo_Phrase('enter_availability_higher_than_zero'), $fieldName);
			return false;	
		}
		
		if ($dw->isInsert() AND $dw->get('min_bid') != NULL AND $dw->get('buy_now') != NULL AND (int) $availability > 1)
		{
			$dw->error(new XenForo_Phrase('availability_too_high'), $fieldName);
			return false;	
		}
		
		return true;
	}
	
	protected static function assertValidImage(XenForo_Upload $upload)
	{
		if ( ! $upload->isValid())
		{
			throw new XenForo_Exception($upload->getErrors(), true);
		}

		if ( ! $upload->isImage())
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		};

		$imageType = $upload->getImageInfoField('type');
		if ( ! in_array($imageType, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG)))
		{
			throw new XenForo_Exception(new XenForo_Phrase('uploaded_file_is_not_valid_image'), true);
		}
		
		$fileName 	= $upload->getTempFile();
		$imageInfo 	= getimagesize($fileName);
		
		if ( ! $imageInfo)
		{
			throw new XenForo_Exception('Non-image passed in to ' . __CLASS__ . '::' . __METHOD__);
		}
		
		$width 		= $imageInfo[0];
		$height 	= $imageInfo[1];
		
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
	
	public static function saveImage(XenForo_Upload $upload)
	{
		self::assertValidImage($upload);
		
		$fileName 	= $upload->getTempFile();

		$imageType 	= $upload->getImageInfoField('type');
		$width 		= $upload->getImageInfoField('width');
		$height 	= $upload->getImageInfoField('height');
		
		$sizes 		= array('l' => 768, 'n' => 150, 's' => 48, 't' => 32);
		
		$outputFiles= array();
		$outputType = $imageType;
		
		reset($sizes);
		list($sizeCode, $maxDimensions) = each($sizes);
		$shortSide = ($width > $height ? $height : $width);
		
		if ($shortSide > $maxDimensions)
		{
			$newTempFile 	= tempnam(XenForo_Helper_File::getTempDir(), 'xf');
			$image 			= XenForo_Image_Abstract::createFromFile($fileName, $imageType);
			
			if ( ! $image)
			{
				throw new XenForo_Exception(new XenForo_Phrase('image_could_be_processed_try_another_contact_owner'), true);
			}
			
			$image->thumbnailFixedShorterSide($maxDimensions);
			$image->output($outputType, $newTempFile, 85);

			$width 	= $image->getWidth();
			$height = $image->getHeight();

			$outputFiles[$sizeCode] = $newTempFile;
		}
		else
		{
			$outputFiles[$sizeCode] = $fileName;
		}
		
		$crop = array(
			'x' => array('m' => 0),
			'y' => array('m' => 0),
		);

		while (list($sizeCode, $maxDimensions) = each($sizes))
		{
			$newTempFile 	= tempnam(XenForo_Helper_File::getTempDir(), 'xf');
			$image 			= XenForo_Image_Abstract::createFromFile($fileName, $imageType);
			
			if ( ! $image)
			{
				throw new XenForo_Exception(new XenForo_Phrase('image_could_be_processed_try_another_contact_owner'), true);
			}

			$image->thumbnailFixedShorterSide($maxDimensions);

			if ($image->getOrientation() != XenForo_Image_Abstract::ORIENTATION_SQUARE)
			{
				$crop['x'][$sizeCode] = floor(($image->getWidth() - $maxDimensions) / 2);
				$crop['y'][$sizeCode] = floor(($image->getHeight() - $maxDimensions) / 2);
				$image->crop($crop['x'][$sizeCode], $crop['y'][$sizeCode], $maxDimensions, $maxDimensions);
			}

			$image->output($outputType, $newTempFile, 85);
			unset($image);

			$outputFiles[$sizeCode] = $newTempFile;
		}
		
		$imagePath = sprintf('%d/%s',
			date('Ymd'),
			md5(uniqid())
		);
		
		foreach ($outputFiles AS $sizeCode => $tempFile)
		{
			$filePath = sprintf('%s/xenauction/%s_%s.jpg',
				XenForo_Helper_File::getExternalDataPath(),
				$imagePath,
				$sizeCode
			);
			
			$directory = dirname($filePath);
	
			if (XenForo_Helper_File::createDirectory($directory, true) && is_writable($directory))
			{
				if (file_exists($filePath))
				{
					unlink($filePath);
				}
	
				if (rename($tempFile, $filePath))
				{
					XenForo_Helper_File::makeWritableByFtpUser($filePath);
				}
	
			}
		}
		
		return $imagePath;
	}
	
}