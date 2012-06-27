<?php

class XenAuction_ControllerPublic_History extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_list');	
	}
	
	public function actionAuctions()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions(
			array(
				'user_id' 	=> $visitor->user_id
			),
			array(
				'join'		=> XenAuction_Model_Auction::FETCH_USER
			)
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_auctions', array(
		   	'auctions'	=> $auctions
		));	
	}
	
	public function actionBids()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions  		= $auctionModel->getUserBids(
			array('bid_user_id' => $visitor->user_id, 'is_buyout' => 0),
			array('join'	=> XenAuction_Model_Auction::FETCH_USER)
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_bids', array(
			'auctions'	=> $auctions
		));	
	}
	
	public function actionBuyouts()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions  		= $auctionModel->getUserBids(
			array('bid_user_id' => $visitor->user_id, 'is_buyout' => 1),
			array('join'	=> XenAuction_Model_Auction::FETCH_USER)
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_buyouts', array(
			'auctions'	=> $auctions
		));	
	}
	
}