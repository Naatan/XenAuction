<?php

/**
 * Auction Controller
 *
 * Used for displaying auction related information
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_ControllerPublic_Auction extends XenForo_ControllerPublic_Abstract
{
	/**
	 * Auction List
	 *
	 * REQUEST params:
	 *
	 *  - page		
	 *  - search 		Keyword to search for, acts only on the auction title field
	 *  - tags			Simple array with multiple tag ID's
	 *  - tag			Single tag ID, overrides tags if both are supplied 
	 * 
	 * @return XenAuction_ViewPublic_Auction_View	Template auction_list
	 */
	public function actionIndex()
	{
		// Prepare options
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		
		// Parse user input
		$input = $this->_input->filter(array(
			'page' 		=> XenForo_Input::UINT,
			'search'	=> XenForo_Input::STRING,
			'tags'		=> XenForo_Input::ARRAY_SIMPLE,
			'tag'		=> XenForo_Input::STRING
		));
		
		// Override tags input with single tag id if provided
		if ( ! empty($input['tag']))
		{
			$input['tags'] = array($input['tag']);
		}
		
		// Set fetch conditions (WHERE clause)
		$fetchConditions = array(
			'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE,
			'title'		=> $input['search']
		);
		
		// Add tags to conditions if any have been provided
		if ( ! empty($input['tags']))
		{
			$fetchConditions['tag'] = $input['tags'];
		}
		
		// Set fetch options
		$fetchOptions 	= array(
			'page'		=> $input['page'],
			'perPage'	=> $perPage
		);
		
		// Prepare database models
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$tagModel 		= XenForo_Model::create('XenAuction_Model_Tag');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		
		// Retrieve data from database
		$auctions 		= $auctionModel->getAuctions($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getAuctionCount($fetchConditions, $fetchOptions);
		
		// Parse retrieved data so they can be easily accessed by the template engine
		$userIds 		= array_map( create_function('$a', 'if (!empty($a["top_bidder"])) return $a["top_bidder"];'), $auctions );
		$auctionIds 	= array_map( create_function('$a', 'return $a["auction_id"];'), $auctions );
		$selTags 		= array_flip($input['tags']);
		$selTags 		= array_map( create_function('$a', 'return true;'), $selTags);
		
		// Retrieve relevant user profiles from database
		$users 			= $userModel->getUsersByIds($userIds);
		$tags 			= $tagModel->getTagsByAuctions($auctionIds);
		$allTags 		= $tagModel->getTags();
		
		// All done
		return $this->responseView('XenAuction_ViewPublic_Auction_View', 'auction_list', array(
			'allTags'	=> $allTags,
			'tags'		=> $tags,
			'selTags'	=> $selTags,
			'tagMode'	=> $options->auctionTagMode, // tag display mode (list / select)
			
		   	'auctions'	=> $auctions,
			'users'		=> $users,
			
			'search'	=> $input['search'],
			'page'		=> $input['page'],
			'perPage'	=> $perPage,
			'total'		=> $total,
			
			'visitor' 	=> XenForo_Visitor::getInstance()->toArray()
		));
	}

	/**
	 * Show details for specific auction
	 *
	 * REQUEST params:
	 *
	 *  - id		Auction ID
	 *  - bid_id	Bid ID, used to display "complete sale" button when relevant
	 * 
	 * @return XenAuction_ViewPublic_Auction_View    Template auction_details
	 */
	public function actionDetails() 
	{
		// Parse user input
		$input = $this->_input->filter(array(
			'id' 		=> XenForo_Input::UINT,
			'bidId' 	=> XenForo_Input::UINT
		));

		// Prepare database models
		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$tagModel 		= XenForo_Model::create('XenAuction_Model_Tag');

		// Retrieve auction, bid and tag details from database
		$auction     	= $auctionModel->getAuctionById($input['id']);
		$bid 			= $input['bidId'] ? $auctionModel->getBidById($input['bidId']) : false;
		$tags 			= $tagModel->getTagsByAuction($auction['auction_id']);

		// All done
		return $this->responseView('XenAuction_ViewPublic_Auction_View', 'auction_details', array(
		   	'auction'	=> $auction,
			'bid'		=> $bid,
			'tags'		=> $tags
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
	 * Enforce viewAuctions permission for all visitors accessing this controller (regardless of method)
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