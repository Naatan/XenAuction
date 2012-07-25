<?php

/**
 * History controller
 *
 * Used for most actions related to the history pages
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_ControllerPublic_History extends XenForo_ControllerPublic_Abstract
{

	/**
	 * Main index page, shows the tabs to each individual history section
	 *
	 * REQUEST params:
	 *
	 *  - page
	 *  - search
	 * 
	 * @return XenForo_ViewPublic_Base    Template auction_history_list
	 */
	public function actionIndex()
	{
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_list', array(
			'page'	=> $this->_input->filterSingle('page', XenForo_Input::UINT),
			'search'=> $this->_input->filterSingle('search', XenForo_Input::STRING)
		));	
	}
	
	/**
	 * Show auctions that were created by the visitor
	 *
	 * REQUEST params:
	 *
	 *  - page
	 *  - search		Search queries apply to title and auction id
	 *  - archived 		boolean determining whether we are viewing archived auctions or not
	 * 
	 * @return XenForo_ViewPublic_Base    Template auction_history_auctions
	 */
	public function actionAuctions()
	{
		// Prepare visitor object and options
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		
		// Parse user input
		$input = $this->_input->filter(array(
			'page' 		=> XenForo_Input::UINT,
			'archived' 	=> XenForo_Input::UINT,
			'search' 	=> XenForo_Input::STRING
		));
		
		// Set fetch conditions
		$fetchConditions = array(
			'user_id' 	=> $visitor->user_id,
			'archived'	=> $input['archived']? true : false,
			'title'		=> $input['search'],
			'auction_id_search'=> $input['search']
		);
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'order'		=> 'expiration_date',
			'direction'	=> 'desc'
		);
		
		// Prepare user models
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		
		// Retrieve auction data
		$auctions 		= $auctionModel->getAuctions($fetchConditions, $fetchOptions);
		$total 			= $auctionModel->getAuctionCount($fetchConditions, $fetchOptions);
		
		// Parse user id's from auctions and retrieve relevant user profiles
		$userIds 		= array_map( create_function('$a', 'if (!empty($a["top_bidder"])) return $a["top_bidder"];'), $auctions );
		$users 			= $userModel->getUsersByIds($userIds);
		
		// All done
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_auctions', array(
		   	'auctions'	=> $auctions,
			'users'		=> $users,
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'total'		=> $total,
			'search'	=> $input['search'],
			'visitor' 	=> $visitor->toArray(),
		));	
	}
	
	/**
	 * Show bids that were placed by the visitor
	 *
	 *	REQUEST params:
	 *
	 *	 - page
	 *	 - search				Search queries apply to title, auction id and bid id
	 *
	 * @param bool $isBuyout 	If true return buyouts, otherwise return bids
	 * 
	 * @return XenForo_ViewPublic_Base    Template auction_history_buyouts or auction_history_bids
	 */
	public function actionBids($isBuyout = false)
	{
		// Prepare visitor object and options
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		
		// Parse user input
		$input = $this->_input->filter(array(
			'page' 		=> XenForo_Input::UINT,
			'search' 	=> XenForo_Input::STRING
		));
		
		// Set fetch conditions
		$fetchConditions = array(
			'bid_user_id' 		=> $visitor->user_id,
			'is_buyout' 		=> $isBuyout,
			'title'				=> $input['search'],
			'auction_id_search'	=> $input['search'],
			'bid_id_search'		=> $input['search']
		);
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage
		);
		
		// Prepare database models
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		
		// Retrieve bid data
		$auctions  		= $auctionModel->getUserBids($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getUserBidCount($fetchConditions, $fetchOptions);
		
		// Parse user id's from auctions and get relevant user profiles
		$userIds 		= array_map( create_function('$a', 'if (!empty($a["top_bidder"])) return $a["top_bidder"];'), $auctions );
		$users 			= $userModel->getUsersByIds($userIds);
		
		// All done
		return $this->responseView('XenForo_ViewPublic_Base', $isBuyout ? 'auction_history_buyouts' : 'auction_history_bids', array(
			'auctions'	=> $auctions,
			'users'		=> $users,
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'total'		=> $total,
			'search'	=> $input['search']
		));	
	}
	
	/**
	 * Show buyouts that were placed by the user
	 *
	 * Identical to self::actionBuyouts except that it gets buyouts instead of bids,
	 * and it uses a different template to view them.
	 * 
	 * @return self::actionBids    
	 */
	public function actionBuyouts()
	{
		return $this->actionBids(true);
	}
	
	/**
	 * Show sales that the current user has made
	 *
	 * REQUEST params:
	 *
	 *  - page
	 *  - search		Applies to title, bid ID and username of purchaser
	 * 
	 * @return XenForo_ViewPublic_Base    Template auction_history_sales
	 */
	public function actionSales()
	{
		// Prepare visitor object and options
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		
		// Parse user input
		$input = $this->_input->filter(array(
			'page' 		=> XenForo_Input::UINT,
			'search' 	=> XenForo_Input::STRING
		));
		
		// Set fetch conditions
		$fetchConditions = array(
			'user_id'	=> $visitor->user_id,
			'title'			=> $input['search'],
			'bid_id_search'	=> $input['search'],
			'username'		=> $input['search']
		);
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage
		);
		
		// Prepare database models
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		
		// Retrieve sale data
		$auctions  		= $auctionModel->getSales($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getSalesCount($fetchConditions, $fetchOptions);
		
		// Parse sale data and retrieve relevant user profiles
		$userIds 		= array_map( create_function('$a', 'if (!empty($a["bid_user_id"])) return $a["bid_user_id"];'), $auctions );
		$users 			= $userModel->getUsersByIds($userIds);
		
		// All done
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_sales', array(
			'auctions'	=> $auctions,
			'users'		=> $users,
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'total'		=> $total,
			'search'	=> $input['search']
		));	
	}
	
	/**
	 * Session activity details.
	 * 
	 * @see XenForo_Controller::getSessionActivityDetailsForList()
	 */
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('viewing_auctions');
	}
	
	/**
	 * Enforce viewAuctions permission for all actions in this controller
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