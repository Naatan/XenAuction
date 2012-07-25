<?php

/**
 * Extend account preferences view to make our custom fields contextual to specific permissions
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_ViewPublic_Account_Preferences extends XFCP_XenAuction_ViewPublic_Account_Preferences
{
	
	/**
	 * Extend prepareParams and remove our custom fields if the user does not have the relevant permissions
	 * 
	 * @return void    
	 */
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