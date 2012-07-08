<?php

class XenAuction_ControllerPublic_Process extends XenForo_ControllerPublic_Abstract
{
	
	public function actionCreate()
	{
		return $this->responseView('XenAuction_ViewPublic_Auction_Create', 'auction_create', array());
	}
	
	public function actionAdd()
	{
		$visitor = XenForo_Visitor::getInstance();
		
		$upload  = XenForo_Upload::getUploadedFile('image');
		
		if ($upload)
		{
			$imagePath = XenAuction_DataWriter_Helper_Auction::saveImage($upload);
		}
		else
		{
			$imagePath = NULL;
		}
		
		$input = $this->_input->filter(array(
			'title'        		=> XenForo_Input::STRING,
			'tags'         		=> XenForo_Input::STRING,
			'message_html' 		=> XenForo_Input::STRING,
			'expires'      		=> XenForo_Input::UINT,
			'batch'      		=> XenForo_Input::UINT,
			'starting_bid' 		=> XenForo_Input::UINT,
			'buyout'       		=> XenForo_Input::UINT,
			'availability' 		=> XenForo_Input::UINT,
			'bid_enable'   		=> XenForo_Input::UINT,
			'buyout_enable'		=> XenForo_Input::UINT
		));
		
		$batch = is_numeric($input['batch']) ? $input['batch'] : 1;
		if ($batch < 1)
		{
			$batch = 1;
		}
		
		$tags = explode(',', $input['tags']);
		$tags = array_unique(array_filter($tags));
		$tags = array_map(create_function('$a', 'return trim($a);'), $tags);
		
		$data = array(
			'user_id'			=> $visitor->user_id,
			'title'          	=> $input['title'],
			'image'				=> $imagePath,
			'message'        	=> $input['message_html'],
			'tags'           	=> implode(',', $tags),
			'min_bid'        	=> $input['bid_enable'] ? $input['starting_bid'] : NULL,
			'buy_now'        	=> $input['buyout_enable'] ? $input['buyout'] : NULL,
			'availability'   	=> $input['buyout_enable'] ? $input['availability'] : NULL,
			'expiration_date'	=> time() + ((int) $input['expires'] * 86400)
		);
		
		for ($c=0;$c<$batch; $c++)
		{
			$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
			$dw->bulkSet($data);
			$dw->save();
		}
		
		XenAuction_Helper_Tags::add(explode(',', $input['tags']));
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auctions')
		);
	}
	
	public function actionArchive()
	{
		$id 		= $this->_input->filterSingle('id', XenForo_Input::UINT);
		$visitor 	= XenForo_Visitor::getInstance();
		
		$auctionModel	= XenForo_Model::create('XenAuction_Model_Auction');
		$auction     	= $auctionModel->getAuctionById($id);
		
		if ($visitor->user_id != $auction['user_id'])
		{
			return $this->responseNoPermission();
		}
		
		$dw = XenForo_DataWriter::create('XenAuction_DataWriter_Auction');
		$dw->setExistingData($auction);
		$dw->set('archived', 1);
		
		$dw->preSave();

		if ($dwErrors = $dw->getErrors())
		{
			return $this->responseError($dwErrors);
		}
		
		$dw->save();
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('auction-history')
		);
	}
	
	/**
	 * Enforce registered-users only for all actions in this controller
	 *
	 * @see library/XenForo/XenForo_Controller#_preDispatch($action)
	 */
	protected function _preDispatch($action)
	{
		if (
			! XenForo_Visitor::getInstance()->hasPermission('auctions', 'viewAuctions') OR
			! XenForo_Visitor::getInstance()->hasPermission('auctions', 'createAuctions')
		)
		{
			throw new XenForo_ControllerResponse_Exception($this->responseNoPermission());
		}
	}
	
}