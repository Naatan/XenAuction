<?php

class XenAuction_ControllerPublic_Bid extends XenForo_ControllerPublic_Abstract
{
	
	public function actionBid() 
	{
		if ( ! XenForo_Visitor::getInstance()->hasPermission('auctions', 'bidOnAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		$id 			= $this->_input->filterSingle('id', XenForo_Input::UINT);

		$visitor 		= XenForo_Visitor::getInstance();

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);
		
		if ($auction['user_id'] == $visitor->user_id)
		{
			return $this->responseError(new XenForo_Phrase('cant_buy_own_auction'));
		}

		return $this->responseView('XenForo_ViewPublic_Base', 'auction_bid', array(
		   	'auction'	=> $auction
		));	
	}

	public function actionBuyout() 
	{
		if ( ! XenForo_Visitor::getInstance()->hasPermission('auctions', 'buyoutAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		$id 			= $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		$visitor 		= XenForo_Visitor::getInstance();

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);
		
		if ($auction['user_id'] == $visitor->user_id)
		{
			return $this->responseError(new XenForo_Phrase('cant_buy_own_auction'));
		}

		return $this->responseView('XenForo_ViewPublic_Base', 'auction_buyout', array(
		   	'auction'	=> $auction
		));	
	}

	public function actionPlaceBid() 
	{
		if ( ! XenForo_Visitor::getInstance()->hasPermission('auctions', 'bidOnAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		$input = $this->_input->filter(array(
			'id'		=> XenForo_Input::UINT,
			'bid'		=> XenForo_Input::UINT
		));
		
		$visitor = XenForo_Visitor::getInstance();

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($input['id']);
		
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
		$dw->bulkSet(array(
			'auction_id' 	=> $auction['auction_id'],
			'bid_user_id' 	=> $visitor->user_id,
			'amount'		=> $input['bid']
		));
		
		$dw->preSave();

		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		$dw->save();
		
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->set('top_bid', 	$input['bid']);
		$dw->set('top_bidder',	$visitor->user_id);

		$dw->preSave();

		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}

		$dw->save();
		
		if ($auction['top_bidder'])
		{
			$userModel 	= XenForo_Model::create('XenForo_Model_User');
			$outbidUser = $userModel->getUserById($auction['top_bidder']);

			$title 		= new XenForo_Phrase('outbid_on_x', $auction);
			$message	= new XenForo_Phrase('outbid_message', $outbidUser);

			XenAuction_Helper_Notification::sendNotification($outbidUser['user_id'], $title, $message);
		}

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}

	public function actionPlaceBuyout() 
	{
		if ( ! XenForo_Visitor::getInstance()->hasPermission('auctions', 'buyoutAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		$input = $this->_input->filter(array(
			'id'		=> XenForo_Input::UINT,
			'quantity'	=> XenForo_Input::UINT
		));

		$visitor = XenForo_Visitor::getInstance();

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($input['id']);

		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
		$dw->bulkSet(array(
			'auction_id' 	=> $auction['auction_id'],
			'bid_user_id' 	=> $visitor->user_id,
			'amount'		=> $input['quantity'] * $auction['buy_now'],
			'quantity'		=> $input['quantity'],
			'is_buyout' 	=> 1
		));
		
		$dw->preSave();

		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		$dw->save();

		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->set('availability', 	$auction['availability'] - $input['quantity']);
		
		$dw->preSave();

		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}

		$dw->save();

		$title 		= new XenForo_Phrase('bought_auction_x', $auction);
		$message	= new XenForo_Phrase('bought_auction_message', $auction);

		XenAuction_Helper_Notification::sendNotification($visitor['user_id'], $title, $message);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}
	
	/**
	 * Enforce registered-users only for all actions in this controller
	 *
	 * @see library/XenForo/XenForo_Controller#_preDispatch($action)
	 */
	protected function _preDispatch($action)
	{
		if ( ! XenForo_Visitor::getInstance()->hasPermission('auctions', 'viewAuctions'))
		{
			throw new XenForo_ControllerResponse_Exception($this->responseNoPermission());
		}
	}
	
}