<?php

class XenAuction_ControllerPublic_Auctions extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		$auctionModel = XenForo_Model::create('XenAuction_Model_Auction');
		
		$auctions = $auctionModel->getAuctions(array(
			'join' 		=> XenAuction_Model_Auction::FETCH_USER,
			'validOnly' => true
		));
		
		return $this->responseView('XenForo_ViewPublic_Base', 'auction_list', array(
			'auctions'	=> $auctions
		));	
	}
	
	public function actionCreate()
	{
		return $this->responseView('XenAuction_ViewPublic_Auction_Create', 'auction_create', array());
	}
	
	public function actionAdd()
	{
		$input = $this->_input->filter(array(
			'title' 		=> XenForo_Input::STRING,
			'tags' 			=> XenForo_Input::STRING,
			'message_html' 	=> XenForo_Input::STRING,
			'expires' 		=> XenForo_Input::ARRAY_SIMPLE,
			'starting_bid' 	=> XenForo_Input::UINT,
			'buyout' 		=> XenForo_Input::UINT,
			'availability' 	=> XenForo_Input::UINT,
			'bid_enable'	=> XenForo_Input::UINT,
			'buyout_enable' => XenForo_Input::UINT
		));
		
		$data = array(
			'title'				=> $input['title'],
			'message'			=> $input['message_html'],
			'tags'				=> $input['tags'],
			'min_bid'			=> $input['bid_enable'] ? $input['starting_bid'] : NULL,
			'buy_now'			=> $input['buyout_enable'] ? $input['buyout'] : NULL,
			'availability'		=> $input['buyout_enable'] ? $input['availability'] : NULL,
			'expiration_date'	=> mktime(0,0,0,$input['expires']['month'], $input['expires']['day'], $input['expires']['year'])
		);
		
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->bulkSet($data);
		
		$dw->save();
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}
	
}