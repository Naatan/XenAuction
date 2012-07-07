<?php

class XenAuction_Helper_Tags
{
	
	public static function get()
	{
		$dataModel 	= XenForo_Model::create('XenForo_Model_DataRegistry');
		$result 	= $dataModel->get('auctionTags');
		
		if ($result === null)
		{
			self::reload();
			return self::get();
		}
		
		return $result;
	}
	
	
	public static function add(array $tags)
	{
		$existingTags 	= self::get();
		$tags 			= array_merge($existingTags, $tags);
		
		self::set($tags);
	}
	
	// TODO: tags will need their own assoc tables, otherwise this method can get WAY too heavy on the system
	public static function reload()
	{
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions();
		
		$tags = array();
		foreach ($auctions AS $auction)
		{
			$tags = array_merge($tags, explode(',', $auction['tags']));
		}
		
		self::set($tags);
	}
	
	public static function set(array $tags)
	{
		$tags = array_unique(array_filter($tags));
		sort($tags);
		
		$dataModel 	= XenForo_Model::create('XenForo_Model_DataRegistry');
		$dataModel->set('auctionTags', $tags);
	}
	
}