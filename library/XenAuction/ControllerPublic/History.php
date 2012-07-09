<?php

class XenAuction_ControllerPublic_History extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_list', array(
			'page'	=> $this->_input->filterSingle('page', XenForo_Input::UINT)
		));	
	}
	
	public function actionAuctions()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		$page 			= $this->_input->filterSingle('page', XenForo_Input::UINT);
		$archived 		= $this->_input->filterSingle('archived', XenForo_Input::UINT);
		
		$fetchConditions = array(
			'user_id' 	=> $visitor->user_id,
			'archived'	=> $archived ? true : false
		);
		
		$fetchOptions 	= array(
			'join'		=> XenAuction_Model_Auction::FETCH_USER,
			'page'		=> $page,
			'perPage'	=> $perPage
		);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions($fetchConditions, $fetchOptions);
		$total 			= $auctionModel->getAuctionCount($fetchConditions, $fetchOptions);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_auctions', array(
		   	'auctions'	=> $auctions,
			'page'		=> $page,
			'perPage'	=> $perPage,
			'total'		=> $total
		));	
	}
	
	public function actionBids()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		$page 			= $this->_input->filterSingle('page', XenForo_Input::UINT);
		
		$fetchConditions = array(
			'bid_user_id' 	=> $visitor->user_id,
			'is_buyout' 	=> 0
		);
		
		$fetchOptions 	= array(
			'join'		=> XenAuction_Model_Auction::FETCH_USER,
			'page'		=> $page,
			'perPage'	=> $perPage
		);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions  		= $auctionModel->getUserBids($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getUserBidCount($fetchConditions, $fetchOptions);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_bids', array(
			'auctions'	=> $auctions,
			'page'		=> $page,
			'perPage'	=> $perPage,
			'total'		=> $total
		));	
	}
	
	public function actionBuyouts()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		$page 			= $this->_input->filterSingle('page', XenForo_Input::UINT);
		
		$fetchConditions = array(
			'bid_user_id' 	=> $visitor->user_id,
			'is_buyout' 	=> 1
		);
		
		$fetchOptions 	= array(
			'join'		=> XenAuction_Model_Auction::FETCH_USER,
			'page'		=> $page,
			'perPage'	=> $perPage
		);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions  		= $auctionModel->getUserBids($fetchConditions, $fetchOptions);
		$total 		 	= $auctionModel->getUserBidCount($fetchConditions, $fetchOptions);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_buyouts', array(
			'auctions'	=> $auctions,
			'page'		=> $page,
			'perPage'	=> $perPage,
			'total'		=> $total
		));	
	}
	
	public function actionSales()
	{
		$visitor 		= XenForo_Visitor::getInstance();
		$options 		= XenForo_Application::get('options');
		$perPage 	 	= $options->auctionsPerPage;
		$page 			= $this->_input->filterSingle('page', XenForo_Input::UINT);
		
		$fetchOptions 	= array(
			'join'		=> array(XenAuction_Model_Auction::FETCH_USER),
			'page'		=> $page,
			'perPage'	=> $perPage
		);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions  		= $auctionModel->getSales($visitor->user_id, $fetchOptions);
		$total 		 	= $auctionModel->getSalesCount($visitor->user_id, $fetchOptions);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_history_sales', array(
			'auctions'	=> $auctions,
			'page'		=> $page,
			'perPage'	=> $perPage,
			'total'		=> $total
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