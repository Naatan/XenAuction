<?php

class XenAuction_DataWriter_Helper_Bid
{

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
	
}