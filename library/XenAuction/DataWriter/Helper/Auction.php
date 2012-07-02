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
	
}