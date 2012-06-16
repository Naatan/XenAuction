<?php

class XenAuction_ControllerPublic_Auctions extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		$auctionModel = XenForo_Model::create('XenAuction_Model_Auction');
		
		$auctions = $auctionModel->getAuctions(array(
			'join'		=> XenAuction_Model_Auction::FETCH_USER,
			'validOnly' => true
		));
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_list', array(
		   	'auctions'	=> $auctions
		));	
	}
	
	public function actionCreate()
	{
		return $this->responseView('XenAuction_ViewPublic_Auction_Create', 'auction_create', array());
	}
	
	public function actionAdd()
	{
		$input = $this->_input->filter(array(
			'title'        		=> XenForo_Input::STRING,
			'tags'         		=> XenForo_Input::STRING,
			'message_html' 		=> XenForo_Input::STRING,
			'expires'      		=> XenForo_Input::ARRAY_SIMPLE,
			'starting_bid' 		=> XenForo_Input::UINT,
			'buyout'       		=> XenForo_Input::UINT,
			'availability' 		=> XenForo_Input::UINT,
			'bid_enable'   		=> XenForo_Input::UINT,
			'buyout_enable'		=> XenForo_Input::UINT
		));
		
		$data = array(
			'title'          	=> $input['title'],
			'message'        	=> $input['message_html'],
			'tags'           	=> $input['tags'],
			'min_bid'        	=> $input['bid_enable'] ? $input['starting_bid'] : NULL,
			'buy_now'        	=> $input['buyout_enable'] ? $input['buyout'] : NULL,
			'availability'   	=> $input['buyout_enable'] ? $input['availability'] : NULL,
			'expiration_date'	=> mktime(0,0,0,$input['expires']['month'], $input['expires']['day'], $input['expires']['year'])
		);
		
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->bulkSet($data);
		
		$dw->save();
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}

	public function actionDetails() 
	{
		$id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);

		return $this->responseView('XenForo_ViewPublic_Base', 'auction_details', array(
		   	'auction'	=> $auction
		));	
	}

	public function actionBid() 
	{
		$id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);

		return $this->responseView('XenForo_ViewPublic_Base', 'auction_bid', array(
		   	'auction'	=> $auction
		));	
	}

	public function actionBuyout() 
	{
		$id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);

		return $this->responseView('XenForo_ViewPublic_Base', 'auction_buyout', array(
		   	'auction'	=> $auction
		));	
	}

	public function actionPlaceBid() 
	{
		$input = $this->_input->filter(array(
			'id'		=> XenForo_Input::UINT,
			'bid'		=> XenForo_Input::UINT
		));

		$visitor = XenForo_Visitor::getInstance();

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($input['id']);

		if ($auction['top_bidder'])
		{
			$userModel 	= XenForo_Model::create('XenForo_Model_User');
			$outbidUser = $userModel->getUserById($auction['top_bidder']);

			$title 		= new XenForo_Phrase('outbid_on_x', $auction);
			$message	= new XenForo_Phrase('outbid_message', $outbidUser);

			XenAuction_Helper_Notification::sendNotification($outbidUser['user_id'], $title, $message);
		}

		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->set('top_bid', 	$input['bid']);
		$dw->set('top_bidder',	$visitor->user_id);

		$dw->save();

		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
		$dw->bulkSet(array(
			'auction_id' 	=> $auction['auction_id'],
			'user_id' 		=> $visitor->user_id,
			'amount'		=> $input['bid']
		));
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}

	public function actionPlaceBuyout() 
	{
		$input = $this->_input->filter(array(
			'id'		=> XenForo_Input::UINT,
			'quantity'	=> XenForo_Input::UINT
		));

		$visitor = XenForo_Visitor::getInstance();

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($input['id']);

		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->set('availability', 	$auction['availability'] - $input['quantity']);

		$dw->save();

		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
		$dw->bulkSet(array(
			'auction_id' 	=> $auction['auction_id'],
			'user_id' 		=> $visitor->user_id,
			'amount'		=> $input['quantity'] * $auction['buy_now'],
			'quantity'		=> $input['quantity'],
			'is_buyout' 	=> 1
		));
		$dw->save();

		$title 		= new XenForo_Phrase('bought_auction_x', $auction);
		$message	= new XenForo_Phrase('bought_auction_message', $auction);

		XenAuction_Helper_Notification::sendNotification($visitor['user_id'], $title, $message);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}

	public function actionExpire() 
	{
		$id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		XenAuction_CronEntry_Auction::runExpireAuction($id);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}
	
}