<?php

/**
 * Bid datawriter helpers, used to verify data
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_DataWriter_Helper_Bid
{
	
	/**
	 * @var array 	Cache of auctions so we don't tax the database each time we want to validate an auction field
	 */
	protected static $_auctionCache = array();
	
	/**
	 * Get auction for bid
	 *
	 * This is in the datawriter rather than in the model since it's caching is very specific 
	 * to the use of this datawriter
	 *
	 * @param XenForo_DataWriter $dw 
	 * 
	 * @return array
	 */
	protected static function getAuctionForBid(XenForo_DataWriter $dw)
	{
		$auctionId = $dw->get('auction_id');
		
		// If this auction is cached, return it from the cache
		if (isset(self::$_auctionCache[$auctionId]))
		{
			return self::$_auctionCache[$auctionId];
		}
		
		// Get auction from database and save it in the cache
		$db = XenForo_Application::getDb();
		self::$_auctionCache[$auctionId] = $db->fetchRow('
			SELECT *
			FROM xf_auction
			WHERE auction_id = ?
		', $auctionId);
		
		return self::$_auctionCache[$auctionId];
	}
	
	/**
	 * Verify that the given bid exists
	 *
	 * @param int            		$bid_id    
	 * @param XenForo_DataWriter 	$dw        
	 * @param string|bool           $fieldName 
	 * 
	 * @return bool               
	 */
	public static function verifyBidId(&$bid_id, XenForo_DataWriter $dw, $fieldName = false)
	{
		$db = XenForo_Application::getDb();
		$existing_bid_id = $db->fetchOne('
				SELECT bid_id
				FROM xf_auction_bid
				WHERE bid_id = ?
			', $bid_id);
		
		if ($existing_bid_id == $bid_id)
		{
			return true;
		}
		
		$dw->error(new XenForo_Phrase('requested_bid_not_found'), $fieldName);
		return false;
	}
	
	/**
	 * Verify that the user is not also the auction creator
	 * 
	 * @param int            		$userId    
	 * @param XenForo_DataWriter 	$dw        
	 * @param string|bool           $fieldName 
	 * 
	 * @return bool
	 */
	public static function verifyUserId(&$userId, XenForo_DataWriter $dw, $fieldName = false)
	{
		$auction = self::getAuctionForBid($dw);
		
		if ($auction['user_id'] == $userId)
		{
			$dw->error(new XenForo_Phrase('cant_buy_own_auction'), $fieldName);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Verify that the bid/buy action is allowed on this auction
	 * 
	 * @param bool            		$isBuyout  
	 * @param XenForo_DataWriter 	$dw        
	 * @param string|bool           $fieldName 
	 * 
	 * @return bool               
	 */
	public static function verifyIsBuyout(&$isBuyout, XenForo_DataWriter $dw, $fieldName = false)
	{
		$auction = self::getAuctionForBid($dw);
		
		if (
			($isBuyout == 1 AND $auction['buy_now'] === NULL) OR
			($isBuyout == 0 AND $auction['min_bid'] === NULL)
		)
		{
			$dw->error(new XenForo_Phrase('invalid_buy_action'), $fieldName);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Verify that the buyout amount is valid
	 * 
	 * @param int            		$amount    
	 * @param XenForo_DataWriter 	$dw        
	 * @param string|bool           $fieldName 
	 * 
	 * @return bool               
	 */
	public static function verifyAmount(&$amount, XenForo_DataWriter $dw, $fieldName = false)
	{
		if ($dw->get('is_buyout') == 1)
		{
			return true;
		}
		
		$auction = self::getAuctionForBid($dw);
		
		if ($amount < $auction['min_bid'] OR ( ! empty($auction['top_bid']) AND $amount <= $auction['top_bid']))
		{
			$dw->error(new XenForo_Phrase('bid_must_be_higher'), $fieldName);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Verify that the quantity given does not exceed the availability
	 * 
	 * @param int            		$quantity  
	 * @param XenForo_DataWriter 	$dw        
	 * @param string|bool           $fieldName 
	 * 
	 * @return bool               
	 */
	public static function verifyQuantity(&$quantity, XenForo_DataWriter $dw, $fieldName = false)
	{
		if ($dw->get('is_buyout') == 0)
		{
			return true;
		}
		
		$auction = self::getAuctionForBid($dw);
		
		if ($quantity <= 0)
		{
			$dw->error(new XenForo_Phrase('cant_buy_zero'), $fieldName);
			return false;
		}
		
		if ($quantity > $auction['availability'])
		{
			$dw->error(new XenForo_Phrase('quantity_not_available'), $fieldName);
			return false;
		}
		
		return true;
	}
	
}