<?php

/**
 * Bidding and buyout controller
 *
 * Used for actions related directly to placing bids and buyouts
 * Does not include controllers that process placed bids & buyouts
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_ControllerPublic_Bid extends XenForo_ControllerPublic_Abstract
{
	
	/**
	 * Show the interface for placing a bid on an auction
	 *
	 * REQUEST params:
	 *
	 *  - id	Auction ID
	 * 
	 * @return XenAuction_ViewPublic_Auction_View    Template auction_bid
	 */
	public function actionBid() 
	{
		// Validate that the logged in user can to place bids
		$visitor = XenForo_Visitor::getInstance();
		if ( ! $visitor->hasPermission('auctions', 'bidOnAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		// Parse user input
		$id 			= $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		// Retrieve auction details from database
		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);
		
		// Validate that the user placing the bid is not also the auctioneer
		if ($auction['user_id'] == $visitor->user_id)
		{
			return $this->responseError(new XenForo_Phrase('cant_buy_own_auction'));
		}
		
		// All done
		return $this->responseView('XenAuction_ViewPublic_Auction_View', 'auction_bid', array(
		   	'auction'	=> $auction
		));	
	}

	/**
	 * Show the interface for buying an auction immediately (place buyout / buy now)
	 *
	 * REQUEST params:
	 *
	 *  - id	Auction ID
	 * 
	 * @return XenAuction_ViewPublic_Auction_View    Template auction_buyout
	 */
	public function actionBuyout() 
	{
		// Validate that the logged in user can to place buyouts
		$visitor = XenForo_Visitor::getInstance();
		if ( ! $visitor->hasPermission('auctions', 'buyoutAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		// Parse user input
		$id 			= $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		// Retrieve auction details from database
		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);
		
		// Validate that the user placing the bid is not also the auctioneer
		if ($auction['user_id'] == $visitor->user_id)
		{
			return $this->responseError(new XenForo_Phrase('cant_buy_own_auction'));
		}
		
		// All done
		return $this->responseView('XenAuction_ViewPublic_Auction_View', 'auction_buyout', array(
		   	'auction'	=> $auction
		));	
	}

	/**
	 * Place a bid on an auction, used when submitting the form generated by self::actionBid
	 *
	 * REQUEST params:
	 *
	 *  - id		Auction ID
	 *  - bid 		Bid amount
	 * 
	 * @return parent::responseRedirect    Redirects to auction list
	 */
	public function actionPlaceBid() 
	{
		// Validate that the logged in user can to place bids
		$visitor = XenForo_Visitor::getInstance();
		if ( ! $visitor->hasPermission('auctions', 'bidOnAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		// Parse user input
		$input = $this->_input->filter(array(
			'id'		=> XenForo_Input::UINT,
			'bid'		=> XenForo_Input::UINT
		));
		
		// Retrieve auction details from database
		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($input['id']);
		
		// Prepare bid data to be written
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
		$dw->bulkSet(array(
			'auction_id' 	=> $auction['auction_id'],
			'bid_user_id' 	=> $visitor->user_id,
			'amount'		=> $input['bid'],
			'is_buyout'		=> false
		));
		
		// Validate data before writing to DB
		$dw->preSave();
		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		// Write bid to DB
		$dw->save();
		
		// Prepare auction data to be updated
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->set('top_bid', 	$input['bid']);
		$dw->set('top_bidder',	$visitor->user_id);
		$dw->set('bids',		$auction['bids'] + 1);
		$dw->set('sales',		$auction['sales'] + 1);
		
		// Validate data before writing to DB
		$dw->preSave();
		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		// Update auction DB entry
		$dw->save();
		
		// Check if this auction already had a top bidder, if so we'll need to let him know
		// that he has been outbid
		if ($auction['top_bidder'])
		{
			// Retrieve outbid user profile from database
			$userModel 	= XenForo_Model::create('XenForo_Model_User');
			$outbidUser = $userModel->getUserById($auction['top_bidder']);
			
			// Set notification phrase variables
			$args = array(
				'top_bidder'	=> $visitor->username,
				'link'			=> XenForo_Link::buildPublicLink('full:auctions/details', null, array('id' => $auction['auction_id']))
			);
			$args = array_merge($outbidUser, $args);
			$args = array_merge($auction, $args);
			
			// Parse PM title and message
			$title 		= new XenForo_Phrase('outbid_on_x', $auction);
			$message	= new XenForo_Phrase('outbid_message', $args);
			
			// Send notification to outbid user
			XenAuction_Helper_Notification::sendNotification($outbidUser['user_id'], $title, $message);
		}
		
		// All done
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}

	/**
	 * Place a buyout on an auction, used when submitting the form generated by self::actionBuyout
	 *
	 * REQUEST params:
	 *
	 *  - id		Auction ID
	 *  - quantity 	Quantity to buy
	 * 
	 * @return parent::responseRedirect    Redirects to auction list
	 */
	public function actionPlaceBuyout() 
	{
		// Validate that the logged in user can to place buyouts
		$visitor = XenForo_Visitor::getInstance();
		if ( ! $visitor->hasPermission('auctions', 'buyoutAuctions'))
		{
			return $this->responseNoPermission();
		}
		
		// Parse user input
		$input = $this->_input->filter(array(
			'id'		=> XenForo_Input::UINT,
			'quantity'	=> XenForo_Input::UINT
		));
		
		// Enforce quantity of at least 1
		if (empty($input['quantity']) || $input['quantity'] < 1)
		{
			$input['quantity'] = 1;
		}
		
		// Retrieve auction details from DB
		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($input['id']);
		
		// Prepare bid data to be written
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
		$dw->bulkSet(array(
			'auction_id' 	=> $auction['auction_id'],
			'bid_user_id' 	=> $visitor->user_id,
			'amount'		=> $input['quantity'] * $auction['buy_now'],
			'quantity'		=> $input['quantity'],
			'is_buyout' 	=> 1,
			'sale_date'		=> XenForo_Application::$time
		));
		
		// Validate data before writing to DB
		$dw->preSave();
		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		// Write buyout to DB
		$dw->save();
		
		// Retrieve written data
		$bid = $dw->getMergedData();
		
		// Prepare auction data to be updated
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->set('availability', 	$auction['availability'] - $input['quantity']);
		$dw->set('sales',			$auction['sales'] + $input['quantity']);
		
		// Validate data before writing to DB
		$dw->preSave();
		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		// Update auction DB entry
		$dw->save();
		
		// Get new auction data
		$auction = $dw->getMergedData();
		
		// Get the address field setting from the auctioneer
		$fieldModel 	= XenForo_Model::create('XenForo_Model_UserField');
		$address 		= $fieldModel->getUserFieldValue('auctionPaymentAddress', $auction['user_id']);
		$address 		= str_replace('{bidid}', $bid['bid_id'], $address);
		
		// Set notification phrase variables
		$args = array(
			'quantity' 			=> $input['quantity'],
			'amount'			=> $input['quantity'] * $auction['buy_now'],
			'bid_id'			=> $bid['bid_id'],
			'payment_address' 	=> $address
		);
		$args = array_merge($auction, $args);
		
		// set notification title and message ($complete is used in message)
		$title 		= new XenForo_Phrase('bought_auction_x', $auction);
		$message	= new XenForo_Phrase('bought_auction_message', $args);
		
		// Send notification
		XenAuction_Helper_Notification::sendNotification($visitor['user_id'], $title, $message);
		
		// If the quantity limit has been reached for this auction, expire it
		if ($auction['availability'] == 0)
		{
			XenAuction_CronEntry_Auction::runExpireAuction($auction);
		}
		
		// All done
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}
	
	/**
	 * Session activity details.
	 * 
	 * @see XenForo_Controller::getSessionActivityDetailsForList()
	 */
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('buying_auctions');
	}
	
	/**
	 * Enfore viewAuctions permission for all methods in this controller, as without the permission
	 * to view auctions you won't have the permission to buy them either
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