<?php

class XenAuction_ControllerPublic_Auction extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		$page 			= $this->_input->filterSingle('page', XenForo_Input::UINT);
		
		$search 		= $this->_input->filterSingle('search', XenForo_Input::STRING);
		$tags 			= $this->_input->filterSingle('tags', XenForo_Input::ARRAY_SIMPLE);
		$tag 			= $this->_input->filterSingle('tag', XenForo_Input::STRING);
		
		if ( ! empty($tag))
		{
			$tags = array($tag);
		}
		
		$fetchConditions = array(
			'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE,
			'title'		=> $search
		);
		
		if ( ! empty($tags))
		{
			$fetchConditions['tag'] = $tags;
		}
		
		$fetchOptions 	= array(
			'page'		=> $page,
			'perPage'	=> $perPage
		);
		
		$userModel 		= XenForo_Model::create('XenForo_Model_User');
		$tagModel 		= XenForo_Model::create('XenAuction_Model_Tag');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		
		$auctions 		= $auctionModel->getAuctions($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getAuctionCount($fetchConditions, $fetchOptions);
		
		$userIds 		= array_map( create_function('$a', 'if (!empty($a["top_bidder"])) return $a["top_bidder"];'), $auctions );
		$auctionIds 	= array_map( create_function('$a', 'return $a["auction_id"];'), $auctions );
		$tags 			= array_flip($tags);
		$tags 			= array_map( create_function('$a', 'return true;'), $tags);
		
		$users 			= $userModel->getUsersByIds($userIds);
		
		return $this->responseView('XenAuction_ViewPublic_Auction_View', 'auction_list', array(
			'allTags'	=> $tagModel->getTags(),
			'tags'		=> $tagModel->getTagsByAuctions($auctionIds),
			'selTags'	=> $tags,
		   	'auctions'	=> $auctions,
			'users'		=> $users,
			'search'	=> $search,
			'page'		=> $page,
			'perPage'	=> $perPage,
			'total'		=> $total,
			'visitor' 	=> XenForo_Visitor::getInstance()->toArray(),
			'tagMode'	=> $options->auctionTagMode
		));
	}

	public function actionDetails() 
	{
		$id 	= $this->_input->filterSingle('id', XenForo_Input::UINT);
		$bidId 	= $this->_input->filterSingle('bid_id', XenForo_Input::UINT);

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);
		$bid 			= $bidId ? $auctionModel->getBidById($bidId) : false;

		$tagModel 		= XenForo_Model::create('XenAuction_Model_Tag');
		$tags 			= $tagModel->getTagsByAuction($auction['auction_id']);

		return $this->responseView('XenAuction_ViewPublic_Auction_View', 'auction_details', array(
		   	'auction'	=> $auction,
			'bid'		=> $bid,
			'tags'		=> $tags
		));	
	}
	
	/**
	 * Session activity details.
	 * @see XenForo_Controller::getSessionActivityDetailsForList()
	 */
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('viewing_auctions');
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