<?php

/**
 * Install class, used for installs, upgrades and uninstalls
 */
class XenAuction_Install
{
	
	/**
	 * Perform install or update
	 * 
	 * @param	bool|array			$existingAddOn	
	 * @param	array				$addOnData
	 * 
	 * @return	void
	 */
	public static function install($existingAddOn, $addOnData)
	{
		
		if ( ! $existingAddOn)
		{
			self::createStructure();
		}
		
		if ($existingAddOn AND $existingAddOn['version_id'] < 4)
		{
			self::update4();
		}
		
		if ($existingAddOn AND $existingAddOn['version_id'] < 6)
		{
			self::update6();
		}
		
		if ($existingAddOn AND $existingAddOn['version_id'] < 11)
		{
			self::update11();
		}
		
		if ($existingAddOn AND $existingAddOn['version_id'] < 12)
		{
			//self::update12();
		}
		
		if ($existingAddOn AND $existingAddOn['version_id'] < 13)
		{
			self::update13();
		}
		
		if ($existingAddOn AND $existingAddOn['version_id'] < 14)
		{
			self::update14();
		}
		
		if ($existingAddOn AND $existingAddOn['version_id'] < 15)
		{
			self::update15();
		}
		
	}
	
	/**
	 * 1.0 Beta 2 (build 3) Update
	 * 
	 * @return	void							
	 */
	protected static function update4()
	{
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_auction` ADD `archived` SMALLINT(1)  NOT NULL  DEFAULT '0'  AFTER `status`
		");
	}
	
	/**
	 * 1.0 Beta 2 (build 5) Update
	 * 
	 * @return	void							
	 */
	protected static function update6()
	{
		XenForo_Application::getDb()->query("
			INSERT INTO `xf_user_field` (`field_id`, `display_group`, `display_order`, `field_type`, `field_choices`, `match_type`, `match_regex`, `match_callback_class`, `match_callback_method`, `max_length`, `required`, `show_registration`, `user_editable`, `viewable_profile`, `viewable_message`, `display_template`)
			VALUES
				('auctionConfirmMessage', 'preferences', 5001, 'textarea', X'613A303A7B7D', 'none', '', '', '', 0, 0, 0, 'yes', 0, 0, ''),
				('auctionEnableConfirm', 'preferences', 5000, 'checkbox', X'613A313A7B693A313B733A373A22456E61626C6564223B7D', 'none', '', '', '', 0, 0, 0, 'yes', 0, 0, '');
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_auction` ADD `sales` SMALLINT(5)  UNSIGNED  NOT NULL  DEFAULT '0'  AFTER `bids`
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_auction_bid` ADD `completed` TINYINT(1)  UNSIGNED  NOT NULL  DEFAULT '0'  AFTER `amount`
		");
		
		XenForo_Application::getDb()->query("
			UPDATE `xf_auction` SET `sales`=1 WHERE `auction_id` IN (SELECT `auction_id` FROM `xf_auction_bid`)
		");

	}
	
	/**
	 * 1.0 Beta 3 (build 1) Update
	 * 
	 * @return	void							
	 */
	public static function update11()
	{
		self::createStructureTags();
		
		$auctions = XenForo_Application::getDb()->fetchAll("
			SELECT auction_id, tags
			FROM xf_auction
		");
		
		if ($auctions)
		{
			foreach ($auctions AS $auction)
			{
				$tags = explode(',', $auction['tags']);
				foreach ($tags AS $tag)
				{
					if (empty($tag))
					{
						continue;
					}
					
					$modelTag = XenForo_Model::create('XenAuction_Model_Tag');
					$modelTag->addTagToAuction($tag, $auction['auction_id']);
				}
			}
		}
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_auction` DROP `tags`
		");
	}
	
	/**
	 * 1.0 Beta 4 (build 1) Update
	 * 
	 * @return	void							
	 */
	public static function update12()
	{
		$images = XenForo_Application::getDb()->fetchAll("
			SELECT image
			FROM xf_auction
			WHERE image IS NOT NULL
			GROUP BY image
		");
		
		if ( ! $images)
		{
			return;
		}
		
		foreach ($images AS $_image)
		{
			$fileName = sprintf('%s/xenauction/%s_%s.jpg',
				XenForo_Helper_File::getExternalDataPath(),
				$_image['image'],
				'n'
			);
			
			$fileNameOut = sprintf('%s/xenauction/%s_%s.jpg',
				XenForo_Helper_File::getExternalDataPath(),
				$_image['image'],
				'm'
			);
			
			$image = XenForo_Image_Abstract::createFromFile($fileName, IMAGETYPE_JPEG);
			
			if ( ! $image)
			{
				continue;
			}
			
			$image->thumbnailFixedShorterSide(120);
			$image->output(IMAGETYPE_JPEG, $fileNameOut, 85);
		}
		
	}
	
	/**
	 * 1.0 Beta 5 (build 1) Update
	 * 
	 * @return	void							
	 */
	public static function update13()
	{
		$auctions = XenForo_Application::getDb()->fetchAll("
			SELECT *
			FROM xf_auction
		");
		
		$controller = new XenAuction_ControllerPublic_Auction(
			new Zend_Controller_Request_Http,
			new Zend_Controller_Response_Http,
			new XenForo_RouteMatch
		);
		$helper = new XenForo_ControllerHelper_Editor($controller);
		
		foreach ($auctions AS $auction)
		{
			$message = $helper->getMessageText('message', new XenForo_Input(array('message_html' => $auction['message'])));
			$message = XenForo_Helper_String::autoLinkBbCode($message);
		
			$auctionDw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
			$auctionDw->setExistingData($auction);
			$auctionDw->set('message', $message);
			$auctionDw->save();
		}
		
		XenForo_Application::getDb()->query("
			INSERT INTO `xf_user_field` (`field_id`, `display_group`, `display_order`, `field_type`, `field_choices`, `match_type`, `match_regex`, `match_callback_class`, `match_callback_method`, `max_length`, `required`, `show_registration`, `user_editable`, `viewable_profile`, `viewable_message`, `display_template`)
			VALUES
				('auctionPaymentAddress', 'preferences', 5002, 'textarea', X'613A303A7B7D', 'none', '', '', '', 0, 0, 0, 'yes', 1, 0, '');
		");
		
		XenForo_Application::getDb()->query("
			DELETE FROM `xf_user_field` WHERE `field_id` = 'auctionEnableConfirm'
		");
	}
	
	/**
	 * 1.0 RC 1 Update
	 * 
	 * @return void    
	 */
	public static function update14()
	{
		XenForo_Application::getDb()->query("
			UPDATE xf_auction SET min_bid = NULL, top_bid = NULL, top_bidder = NULL, bids = 0 WHERE min_bid = 0
		");
		
		XenForo_Application::getDb()->query("
			UPDATE xf_auction SET buy_now = NULL WHERE buy_now = 0
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_auction` ADD INDEX (`status`, `title`, `expiration_date`)
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_auction_bid` ADD INDEX (`is_buyout`, `auction_id`)
		");
		
		XenForo_Application::getDb()->query("
			ALTER TABLE `xf_auction_bid` ADD `sale_date` INT(10)  UNSIGNED  NULL  DEFAULT NULL  AFTER `bid_date`
		");
		
		XenForo_Application::getDb()->query("
			UPDATE `xf_auction_bid` SET `sale_date` = `bid_date`
		");
		
		XenForo_Application::getDb()->query("
			DELETE FROM `xf_data_registry` WHERE `data_key` = 'auctionTags'
		");
		
		$images = XenForo_Application::getDb()->fetchAll("
			SELECT image
			FROM xf_auction
			WHERE image IS NOT NULL AND image != ''
			GROUP BY image
		");
		
		if ($images)
		{
			foreach ($images AS $_image)
			{
				$fileName = sprintf('%s/xenauction/%s_',
					XenForo_Helper_File::getExternalDataPath(),
					$_image['image']
				);
				
				@unlink($fileName . 'm.jpg');
				@unlink($fileName . 't.jpg');
				@rename($fileName . 'n.jpg', $fileName . 'm.jpg');
			}
		}
	}
	
	/**
	 * 1.1 Beta 1 Update
	 * 
	 * @return void    
	 */
	public static function update15()
	{
		$db = XenForo_Application::getDb();
		$new = $db->fetchRow("
			SELECT bid_id + 10000 AS newIncrement FROM xf_auction_bid ORDER BY bid_id DESC LIMIT 1
		");
		
		if ($new)
		{
			$db->query("ALTER TABLE xf_auction_bid AUTO_INCREMENT = " . $db->quote($new['newIncrement']));	
		}
		
		$db->query("ALTER TABLE `xf_auction_bid` ADD `bid_status` ENUM('winning','outbid','rejected')  NOT NULL  DEFAULT 'winning'  AFTER `auction_id`");
		
		$db->query("UPDATE xf_auction_bid SET bid_status = 'outbid' WHERE is_buyout = 0");
		
		$db->query("
			UPDATE xf_auction_bid bid
			JOIN xf_auction auction ON
				bid.auction_id = auction.auction_id AND
				bid.amount = auction.top_bid 
			SET bid_status = 'winning'
		");
		
	}
	
	/**
	 * Perform uninstall (wipe stored data)
	 * 
	 * @return	void							
	 */
	public static function uninstall()
	{
		self::dropStructure();
	}
	
	/**
	 * Create database tables
	 * 
	 * @return	void							
	 */
	protected static function createStructure()
	{
		XenForo_Application::getDb()->query("
			CREATE TABLE `xf_auction` (
			  `auction_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `user_id` int(10) unsigned NOT NULL,
			  `title` varchar(150) NOT NULL DEFAULT '',
			  `message` mediumtext NOT NULL,
			  `status` enum('active','canceled','expired') DEFAULT NULL,
			  `archived` smallint(1) NOT NULL DEFAULT '0',
			  `image` varchar(50) DEFAULT NULL,
			  `min_bid` int(10) unsigned DEFAULT NULL,
			  `buy_now` int(10) unsigned DEFAULT NULL,
			  `bids` smallint(5) unsigned NOT NULL DEFAULT '0',
			  `sales` smallint(5) unsigned NOT NULL DEFAULT '0',
			  `availability` smallint(5) unsigned DEFAULT NULL,
			  `top_bid` int(10) unsigned DEFAULT NULL,
			  `top_bidder` int(10) unsigned DEFAULT NULL,
			  `placement_date` int(10) unsigned DEFAULT NULL,
			  `expiration_date` int(10) unsigned DEFAULT NULL,
			  PRIMARY KEY (`auction_id`),
			  KEY `status` (`status`,`title`,`expiration_date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		XenForo_Application::getDb()->query("
			CREATE TABLE `xf_auction_bid` (
			  `bid_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `auction_id` int(10) unsigned NOT NULL,
			  `bid_user_id` int(10) unsigned NOT NULL,
			  `is_buyout` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `quantity` smallint(5) unsigned DEFAULT NULL,
			  `amount` int(10) unsigned NOT NULL,
			  `completed` tinyint(1) unsigned NOT NULL DEFAULT '0',
			  `bid_date` int(10) unsigned NOT NULL,
			  `sale_date` int(10) unsigned DEFAULT NULL,
			  PRIMARY KEY (`bid_id`),
			  KEY `is_buyout` (`is_buyout`,`auction_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
		
		XenForo_Application::getDb()->query("
			INSERT INTO `xf_user_field` (`field_id`, `display_group`, `display_order`, `field_type`, `field_choices`, `match_type`, `match_regex`, `match_callback_class`, `match_callback_method`, `max_length`, `required`, `show_registration`, `user_editable`, `viewable_profile`, `viewable_message`, `display_template`)
			VALUES
				('auctionConfirmMessage', 'preferences', 5001, 'textarea', X'613A303A7B7D', 'none', '', '', '', 0, 0, 0, 'yes', 0, 0, ''),
				('auctionPaymentAddress', 'preferences', 5002, 'textarea', X'613A303A7B7D', 'none', '', '', '', 0, 0, 0, 'yes', 1, 0, '');

		");
		
		self::createStructureTags();
	}
	
	protected static function createStructureTags()
	{
		XenForo_Application::getDb()->query("
			CREATE TABLE `xf_auction_tag` (
			  `tag_id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
			  `name` varchar(50) NOT NULL DEFAULT '',
			  PRIMARY KEY (`tag_id`),
			  UNIQUE KEY `name` (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
		
		XenForo_Application::getDb()->query("
			CREATE TABLE `xf_auction_tag_rel` (
			  `tag_id` smallint(6) unsigned NOT NULL,
			  `auction_id` int(10) unsigned NOT NULL,
			  UNIQUE KEY `tag_id` (`tag_id`,`auction_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1
		");
	}
	
	/**
	 * Drop database tables
	 * 
	 * @return	void							
	 */
	protected static function dropStructure()
	{
		XenForo_Application::getDb()->query("
			DROP TABLE IF EXISTS `xf_auction`
		");
		
		XenForo_Application::getDb()->query("
			DROP TABLE IF EXISTS `xf_auction_bid`
		");
		
		XenForo_Application::getDb()->query("
			DROP TABLE IF EXISTS `xf_auction_tag`
		");
		
		XenForo_Application::getDb()->query("
			DROP TABLE IF EXISTS `xf_auction_tag_rel`
		");
		
		XenForo_Application::getDb()->query("
			DELETE FROM `xf_user_field` WHERE `field_id` IN ('auctionConfirmMessage', 'auctionEnableConfirm', 'auctionPaymentAddress')
		");
		
		XenForo_Model::create('XenForo_Model_DataRegistry')->delete('auctionTags');
	}
	
}