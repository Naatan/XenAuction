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
			  PRIMARY KEY (`auction_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
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
			  PRIMARY KEY (`bid_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8
		");
		
		XenForo_Application::getDb()->query("
			INSERT INTO `xf_user_field` (`field_id`, `display_group`, `display_order`, `field_type`, `field_choices`, `match_type`, `match_regex`, `match_callback_class`, `match_callback_method`, `max_length`, `required`, `show_registration`, `user_editable`, `viewable_profile`, `viewable_message`, `display_template`)
			VALUES
				('auctionConfirmMessage', 'preferences', 5001, 'textarea', X'613A303A7B7D', 'none', '', '', '', 0, 0, 0, 'yes', 0, 0, ''),
				('auctionEnableConfirm', 'preferences', 5000, 'checkbox', X'613A313A7B693A313B733A373A22456E61626C6564223B7D', 'none', '', '', '', 0, 0, 0, 'yes', 0, 0, '');
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
			DELETE FROM `xf_user_field` WHERE `field_id` = 'auctionConfirmMessage' OR `field_id` = 'auctionEnableConfirm' LIMIT 2
		");
	}
	
}