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
	
}