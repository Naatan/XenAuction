<?php

class XenAuction_Listen
{

	public static function init_dependecies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		XenForo_Template_Helper_Core::$helperCallbacks += array(
			'auction' => array('XenAuction_TemplateHelpers_Base', 'helper')
		);
	}

	public static function navigation_tabs(array &$extraTabs, $selectedTabId)
	{
		if ( ! XenForo_Visitor::getInstance()->hasPermission('auctions', 'viewAuctions'))
		{
			return;
		}
		
		$extraTabs['auctions'] = array(
			'title' 		=> new XenForo_Phrase('auctions'),
			'href'  		=> XenForo_Link::buildPublicLink('auctions'),
			'linksTemplate' => 'navigation_tab_auctions',
			'position'  	=> 'middle'
		);
	}

}