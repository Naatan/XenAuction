<?php

class XenAuction_ControllerPublic_Auction extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions(
			array(
				'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE
			),
			array(
				'join'		=> XenAuction_Model_Auction::FETCH_USER
			)
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_list', array(
		   	'auctions'	=> $auctions
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
	
	public function actionSearch()
	{
		$search = $this->_input->filterSingle('search', XenForo_Input::STRING);
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getAuctions(
			array(
				'status' 	=> XenAuction_Model_Auction::STATUS_ACTIVE,
				'title'		=> $search,
				'tags'		=> $search
			),
			array(
				'join'		=> XenAuction_Model_Auction::FETCH_USER
			)
		);
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_list', array(
		   	'auctions'	=> $auctions,
			'search'	=> $search
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