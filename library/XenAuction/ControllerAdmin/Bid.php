<?php

/**
 * Admin controller for bid related actions
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_ControllerAdmin_Bid extends XenForo_ControllerAdmin_Abstract
{
	
	public function actionIndex()
	{
		
		// Prepare visitor object and options
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		
		// Parse user input
		$input = $this->_input->filter(array(
			'page' 		=> XenForo_Input::UINT,
			'search' 	=> XenForo_Input::STRING
		));
		
		// Set fetch conditions
		$fetchConditions = array(
			'is_buyout' 		=> 0,
			'auction_id_search'	=> $input['search'],
			'bid_id_search'		=> $input['search'],
			'title'				=> $input['search'],
			'username'			=> $input['search']
		);
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'order'		=> 'bid_date',
			'direction'	=> 'DESC'
		);
		
		// Prepare database models
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		
		// Retrieve bid data
		$auctions		= $auctionModel->getUserBids($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getUserBidCount($fetchConditions, $fetchOptions);
		
		// Parse user id's from auctions and get relevant user profiles
		$userIds 		= array_map( create_function('$a', 'if (!empty($a["bid_user_id"])) return $a["bid_user_id"];'), $auctions );
		$users 			= $userModel->getUsersByIds($userIds);
		
		// All done
		return $this->responseView('XenForo_ViewAdmin_Base', 'auction_bids', array(
			'auctions'	=> $auctions,
			'users'		=> $users,
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'total'		=> $total,
			'search'	=> $input['search']
		));
		
	}
	
	/**
	 * Rejects the specified bid
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionReject()
	{
		$id = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction 		= $auctionModel->getAuction(array('bid_id' => $id), array('join' => XenAuction_Model_Auction::FETCH_BID));
		
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$user 			= $userModel->getUserById($auction['bid_user_id']);
		
		if ($auction['bid_status'] != XenAuction_Model_Auction::BID_STATUS_WINNING)
		{
			return $this->responseError(new XenForo_Phrase('can_only_reject_winning_bids'));
		}
		
		if ($auction['status'] != XenAuction_Model_Auction::STATUS_ACTIVE)
		{
			return $this->responseError(new XenForo_Phrase('can_not_reject_bids_on_expired_auctions'));
		}
		
		if ($this->isConfirmedPost())
		{
			// Update status of old winning bid
			$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
			$dw->setExistingData($auction);
			$dw->set('bid_status', XenAuction_Model_Auction::BID_STATUS_REJECTED);
			$dw->save();
			
			// Prepare auction datawriter for update
			$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
			$dw->setExistingData($auction);
			
			// Set fetch conditions to retrieve the previous winning bid
			$fetchConditions 	= array(
				'auction_id'	=> $auction['auction_id'],
				'amount_under'	=> $auction['top_bid']
			);
			$fetchOptions 		= array(
				'join'		=> XenAuction_Model_Auction::FETCH_BID,
				'order'		=> 'amount',
				'direction'	=> 'DESC'
			);
			
			// Retrieve previous winning bid
			$bid = $auctionModel->getAuction($fetchConditions, $fetchOptions);
			
			if ($bid)
			{
				// Set new auction data and save it to db
				$dw->set('top_bidder', $bid['bid_user_id']);
				$dw->set('top_bid', $bid['amount']);
				$dw->save();
				
				// Update status of previous winning bid
				$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Bid');
				$dw->setExistingData($bid);
				$dw->set('bid_status', XenAuction_Model_Auction::BID_STATUS_WINNING);
				$dw->save();
			}
			else
			{
				// Set new auction data and save it to db
				$dw->setNull('top_bidder');
				$dw->setNull('top_bid');
				$dw->save();
			}
			
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('auction-bids', '', array('search' => $auction['auction_id']))
			);
			
		}
		else // show confirmation dialog
		{
			$viewParams = array(
				'auction' 	=> $auction,
				'user'		=> $user
			);

			return $this->responseView('XenForo_ViewAdmin_Base',
				'auction_reject_bid', $viewParams
			);
		}
	}
	
}