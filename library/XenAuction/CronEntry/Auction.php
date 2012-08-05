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
		$dwAuction = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dwAuction->setExistingData($auction);
		$dwAuction->bulkSet(array(
			'status'			=> XenAuction_Model_Auction::STATUS_EXPIRED,
			'expiration_date'	=> XenForo_Application::$time
		));
		
		// Check if this auction had a top bidder
		if ($auction['top_bidder'])
		{
			// Retrieve top bid data
			$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
			$bid 			= $auctionModel->getWinningBid($auction['auction_id']);
				
			if ($auction['availability'] === 0)
			{
				// Prepare auction data to be updated
				$dwAuction->set('top_bidder',	0); // Set top_bidder to 0 as a workaround
				
				// Parse notification title and message
				$title 		= new XenForo_Phrase('lost_auction_x', $auction);
				$message	= new XenForo_Phrase('lost_auction_message', $auction);
				
				// Send notification
				XenAuction_Helper_Notification::sendNotification($bid['bid_user_id'], $title, $message);
				
				// Update status of old winning bid
				$dwBid = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
				$dwBid->setExistingData($bid);
				$dwBid->set('bid_status', XenAuction_Model_Auction::BID_STATUS_OUTBID);
				$dwBid->save();
			}
			else
			{
				// Update sales amount for auction
				$dwAuction->set('sales',	$auction['sales'] + 1); 
				
				// Set phrase params
				$args 			= array_merge($auction, $bid);
				
				// Get user configured payment address
				$fieldModel 			= XenForo_Model::create('XenForo_Model_UserField');
				$args['payment_address']= $fieldModel->getUserFieldValue('auctionPaymentAddress', $auction['user_id']);
				$args['payment_address']= str_replace('{bidid}', $bid['bid_id'], $args['payment_address']);
				
				// Parse notification title and message
				$title 		= new XenForo_Phrase('won_auction_x', $auction);
				$message	= new XenForo_Phrase('won_auction_message', $args);
				
				// Send notification
				XenAuction_Helper_Notification::sendNotification($bid['bid_user_id'], $title, $message);
				
				// Set sale date of bid
				$dwBid = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
				$dwBid->setExistingData($bid);
				$dwBid->set('sale_date', XenForo_Application::$time);
				$dwBid->save();
			}
			
		}
		
		// Update auction (expire it)
		$dwAuction->save();
		
	}

}