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
	 * @return self::actionAuctions
	 */
	public function actionIndex()
	{
		if (XenForo_Visitor::getInstance()->hasPermission('auctions', 'createAuctions'))
		{
			return $this->actionAuctions();
		}
		else
		{
			return $this->actionBids();
		}
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
			'archived'	=> $input['archived'] ? true : false,
			'title'		=> $input['search'],
			'auction_id_search'=> $input['search']
		);
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'order'		=> 'expiration_date',
			'direction'	=> 'ASC'
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
			'archived'	=> $input['archived'],
			'pageNavParams'	=> XenAuction_Helper_Base::pageNavParams($input, array('page'))
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
			'perPage'	=> $perPage,
			'order'		=> 'bid_date',
			'direction'	=> 'DESC'
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
			'search'	=> $input['search'],
			'pageNavParams'	=> XenAuction_Helper_Base::pageNavParams($input, array('page'))
		));
	}
	
	/**
	 * Show the users purchases
	 * 
	 * @return XenForo_ViewPublic_Base    Template auction_history_purchases
	 */
	public function actionPurchases()
	{
		// Prepare visitor object and options
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		
		// Parse user input
		$input = $this->_input->filter(array(
			'page' 		=> XenForo_Input::UINT,
			'search' 	=> XenForo_Input::STRING,
			'completed' => XenForo_Input::UINT
		));
		
		// Set fetch conditions
		$fetchConditions = array(
			'bid_user_id' 		=> $visitor->user_id,
			'auction_id_search'	=> $input['search'],
			'bid_id_search'		=> $input['search'],
			'title'				=> $input['search'],
			'sale_date_notnull'	=> true,
			'completed'			=> $input['completed']
		);
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'order'		=> 'sale_date',
			'direction'	=> 'DESC'
		);
		
		// Prepare database models
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		
		// Retrieve bid data
		$auctions  		= $auctionModel->getUserBids($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getUserBidCount($fetchConditions, $fetchOptions);
		
		// Parse user id's from auctions and get relevant user profiles
		$userIds 		= array_map( create_function('$a', 'if (!empty($a["user_id"])) return $a["user_id"];'), $auctions );
		$users 			= $userModel->getUsersByIds($userIds);
		
		// All done
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_purchases', array(
			'auctions'	=> $auctions,
			'users'		=> $users,
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'total'		=> $total,
			'search'	=> $input['search'],
			'pageNavParams'	=> XenAuction_Helper_Base::pageNavParams($input, array('page'))
		));
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
			'search' 	=> XenForo_Input::STRING,
			'completed' => XenForo_Input::UINT
		));
		
		// Set fetch conditions
		$fetchConditions = array(
			'user_id'		=> $visitor->user_id,
			'title'			=> $input['search'],
			'bid_id_search'	=> $input['search'],
			'username'		=> $input['search'],
			'completed'		=> $input['completed']
		);
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'order'		=> 'sale_date',
			'direction'	=> 'DESC'
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
			'search'	=> $input['search'],
			'completed'	=> $input['completed'],
			'pageNavParams'	=> XenAuction_Helper_Base::pageNavParams($input, array('page'))
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