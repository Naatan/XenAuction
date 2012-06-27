<?php

class XenAuction_DataWriter_Helper_Bid
{
	
	protected static $_auctionCache = array();
	
	protected static function getAuctionForBid(XenForo_DataWriter $dw)
	{
		$auctionId = $dw->get('auction_id');
		
		if (isset(self::$_auctionCache[$auctionId]))
		{
			return self::$_auctionCache[$auctionId];
		}
		
		$db = XenForo_Application::getDb();
		self::$_auctionCache[$auctionId] = $db->fetchRow('
			SELECT *
			FROM xf_auction
			WHERE auction_id = ?
		', $auctionId);
		
		return self::$_auctionCache[$auctionId];
	}

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
	
	public static function verifyIsBuyout()
	{
		$auction = self::getAuctionForBid($dw);
		
		if (
			($dw->get('is_buyout') == 1 AND $auction['buy_now'] == NULL) OR
			($dw->get('is_buyout') == 0 AND $auction['min_bid'] == NULL)
		)
		{
			$dw->error(new XenForo_Phrase('invalid_buy_action'), $fieldName);
			return false;
		}
		
		return true;
	}
	
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
	
	public static function verifyQuantity(&$quantity, XenForo_DataWriter $dw, $fieldName = false)
	{
		if ($dw->get('is_buyout') == 0)
		{
			return true;
		}
		
		$auction = self::getAuctionForBid($dw);
		
		if ($quantity > $auction['availability'])
		{
			$dw->error(new XenForo_Phrase('quantity_not_available'), $fieldName);
			return false;
		}
		
		return true;
	}
	
}