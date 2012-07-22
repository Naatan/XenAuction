<?php

class XenAuction_ViewPublic_Account_Preferences extends XFCP_XenAuction_ViewPublic_Account_Preferences
{
	
	public function prepareParams()
	{
		parent::prepareParams();
		
		if (XenForo_Visitor::getInstance()->hasPermission('auctions', 'createAuctions'))
		{
			return;
		}
		
		foreach ($this->_params['customFields'] AS $param => $value)
		{
			if (in_array($param, array('auctionConfirmMessage', 'auctionPaymentAddress')))
			{
				unset($this->_params['customFields'][$param]);
			}
		}
	}
	
}