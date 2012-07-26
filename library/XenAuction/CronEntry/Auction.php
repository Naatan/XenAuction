<?php

/**
 * Cron job for expiring auctions
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_CronEntry_Auction
{

	/**
	 * Expire auctions
	 *
	 * Scans for auctions that have exceeded their expiration date and expires them
	 *
	 * @todo: 	Account for situations where there would be a huge amount of auctions to expire
	 * 
	 * @return void    
	 */
	public static function runExpireAuctions()
	{
		// Set fetch conditions
		$fetchConditions = array(
			'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE,
			'expired'	=> true
		);
		
		// Retrieve auctions
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions($fetchConditions);
		
		// Loop through auctions and expire them individually (as special actions need to be performed)
		foreach ($auctions AS $auction)
		{
			self::runExpireAuction($auction);
		}
	}

	/**
	 * Expire a specific auction
	 *
	 * Sends "auction won" notification when applicable
	 * 
	 * @param array   $auction 
	 * 
	 * @return void    
	 */
	public static function runExpireAuction(array $auction) 
	{
		
		// Prepare datawriter
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->bulkSet(array(
			'status'			=> XenAuction_Model_Auction::STATUS_EXPIRED
		));
		
		// Update auction (expire it)
		$dw->save();
		
		// Check if this auction had a top bidder
		if ($auction['top_bidder'])
		{
			// Retrieve top bid data
			$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
			$bid 			= $auctionModel->getTopBid($auction['auction_id']);
				
			if ($auction['availability'] == 0)
			{
				// Prepare auction data to be updated
				$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
				$dw->setExistingData($auction);
				$dw->set('top_bidder',	0); // Set top_bidder to 0 as a workaround
				
				// TODO: properly implement the top bidder being "outbid" when an auction has been "bought"
				// Update auction DB entry
				$dw->save();
				
				// Parse notification title and message
				$title 		= new XenForo_Phrase('lost_auction_x', $auction);
				$message	= new XenForo_Phrase('lost_auction_message', $auction);
				
				// Send notification
				XenAuction_Helper_Notification::sendNotification($bid['bid_user_id'], $title, $message);
			}
			else
			{
				// Set phrase params
				$args 			= array_merge($auction, $bid);
				
				// Get user configured payment address
				$fieldModel 	= XenForo_Model::create('XenForo_Model_UserField');
				$paymentAddress	= $fieldModel->getUserFieldValue('auctionPaymentAddress', $auction['user_id']);
				
				// Parse notification title and message
				$title 		= new XenForo_Phrase('won_auction_x', $auction);
				$complete	= new XenForo_Phrase('complete_purchase', array_merge($args, $paymentAddress));
				$message	= new XenForo_Phrase('won_auction_message', array_merge($args, array('complete_purchase' => $complete)));
				
				// Send notification
				XenAuction_Helper_Notification::sendNotification($bid['bid_user_id'], $title, $message);
			}
		}

	}

}