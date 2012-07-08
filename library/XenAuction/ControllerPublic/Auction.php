<?php

class XenAuction_ControllerPublic_Auction extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		$page 			= $this->_input->filterSingle('page', XenForo_Input::UINT);
		
		$search 		= $this->_input->filterSingle('search', XenForo_Input::STRING);
		$tag 			= $this->_input->filterSingle('tag', XenForo_Input::STRING);
		
		$fetchConditions = array(
			'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE,
			'title'		=> $search,
			'tags'		=> $search
		);
		
		if ( ! empty($tag))
		{
			$fetchConditions['tags'] = $tag;
		}
		
		$fetchOptions 	= array(
			'join'		=> XenAuction_Model_Auction::FETCH_USER,
			'page'		=> $page,
			'perPage'	=> $perPage
		);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getAuctionCount($fetchConditions, $fetchOptions);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_list', array(
			'tags'		=> XenAuction_Helper_Tags::get(),
		   	'auctions'	=> $auctions,
			'search'	=> $search,
			'page'		=> $page,
			'perPage'	=> $perPage,
			'total'		=> $total,
			'visitor' 	=> XenForo_Visitor::getInstance()->toArray()
		));	
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