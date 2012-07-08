<?php

class XenAuction_CronEntry_Auction
{

	public static function runExpireAuctions()
	{
		$fetchConditions = array(
			'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE,
			'expired'	=> true
		);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions($fetchConditions);
		
		foreach ($auctions AS $auction)
		{
			self::runExpireAuction($auction);
		}
	}

	public static function runExpireAuction(array $auction) 
	{
		
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->bulkSet(array(
			'status'			=> XenAuction_Model_Auction::STATUS_EXPIRED
		));
		$dw->save();

		if ($auction['top_bidder'])
		{
			$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
			$bid 			= $auctionModel->getTopBid($auction['auction_id']);
			$args 			= array_merge($auction, $bid);
			
			$paymentAddress = array('payment_address' => XenForo_Application::get('options')->auctionPaymentAddress);
			
			$title 		= new XenForo_Phrase('won_auction_x', $auction);
			$complete	= new XenForo_Phrase('complete_purchase', array_merge($args, $paymentAddress));
			$message	= new XenForo_Phrase('won_auction_message', array_merge($args, array('complete_purchase' => $complete)));

			XenAuction_Helper_Notification::sendNotification($bid['bid_user_id'], $title, $message);
		}

	}

}