<?php

class XenAuction_ViewPublic_Auction_View extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		
		if (isset($this->_params['auctions']))
		{
			XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($this->_params['auctions'], $bbCodeParser);
		}
		
		if (isset($this->_params['auction'], $this->_params['auction']['message']))
		{
			$this->_params['auction']['messageHtml'] = XenForo_ViewPublic_Helper_Message::getBbCodeWrapper($this->_params['auction'], $bbCodeParser);
		}
	}
	
	public function renderJson()
	{
		$this->renderHtml();
	}
}