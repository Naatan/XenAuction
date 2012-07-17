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
		
		$fetchConditions = array(
			'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE,
			'title'		=> $search
		);
		
		if ( ! empty($tags))
		{
			$fetchConditions['tag'] = $tags;
		}
		
		$fetchOptions 	= array(
			'join'		=> array(XenAuction_Model_Auction::FETCH_USER),
			'page'		=> $page,
			'perPage'	=> $perPage
		);
		
		$tagModel 		= XenForo_Model::create('XenAuction_Model_Tag');
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getAuctionCount($fetchConditions, $fetchOptions);
		$auctionIds 	= array_map( create_function('$a', 'return $a["auction_id"];'), $auctions );
		$tags 			= array_flip($tags);
		$tags 			= array_map( create_function('$a', 'return true;'), $tags);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_list', array(
			'allTags'	=> $tagModel->getTags(),
			'tags'		=> $tagModel->getTagsByAuctions($auctionIds),
			'selTags'	=> $tags,
		   	'auctions'	=> $auctions,
			'search'	=> $search,
			'page'		=> $page,
			'perPage'	=> $perPage,
			'total'		=> $total,
			'visitor' 	=> XenForo_Visitor::getInstance()->toArray()
		));
	}
	
	public function actionRandom()
	{
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getRandomAuctions();
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_widget', array(
		   	'auctions'	=> $auctions,
		));
	}

	public function actionDetails() 
	{
		$id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);

		$tagModel 		= XenForo_Model::create('XenAuction_Model_Tag');
		$tags 			= $tagModel->getTagsByAuction($auction['auction_id']);

		return $this->responseView('XenForo_ViewPublic_Base', 'auction_details', array(
		   	'auction'	=> $auction,
			'tags'		=> $tags
		));	
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