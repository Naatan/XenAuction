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
			  `tags` varchar(255) DEFAULT NULL,
			  `image` varchar(50) DEFAULT NULL,
			  `min_bid` int(10) unsigned DEFAULT NULL,
			  `buy_now` int(10) unsigned DEFAULT NULL,
			  `bids` smallint(5) unsigned NOT NULL DEFAULT '0',
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
			  `bid_date` int(10) unsigned NOT NULL,
			  PRIMARY KEY (`bid_id`)
			) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8
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
	}
	
}