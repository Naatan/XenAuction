<?php

class XenAuction_CronEntry_Auction
{

	public static function runExpireAuction($auctionId) 
	{
		
		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($auctionId);

		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->bulkSet(array(
			'status'			=> XenAuction_Model_Auction::STATUS_EXPIRED,
			'expiration_date' 	=> XenForo_Application::$time
		));
		$dw->save();

		if ($auction['top_bidder'])
		{
			$title 		= new XenForo_Phrase('won_auction_x', $auction);
			$message	= new XenForo_Phrase('won_auction_message', $auction);

			XenAuction_Helper_Notification::sendNotification($auction['top_bidder'], $title, $message);
		}

	}

}