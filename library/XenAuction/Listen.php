<?php

class XenAuction_Listen
{

	protected static $_prefixSection = false;

	protected static $_prefixAction = false;

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

	public static function template_hook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if ( ! self::_assertShowWidget())
		{
			return;
		}
		
		$options 				= XenForo_Application::get('options');
		list($placement, $hook) = explode('|', $options->auctionWidgetPlacement);
		
		if ($hookName != $hook)
		{
			return;
		}
		
		
		$auctionModel 	= XenForo_Model::create('XenAuction_Model_Auction');
		$auctions 		= $auctionModel->getRandomAuctions();
		
		if ($placement == 'above')
		{
			$contents = $template->create('auction_widget', array('auctions' => $auctions)) . $contents;
		}
		else
		{
			$contents .= $template->create('auction_widget', array('auctions' => $auctions));
		}
	}

	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'PAGE_CONTAINER' AND self::_assertShowWidget())
		{
			$template->preloadTemplate('auction_widget');
		}
	}

	public static function front_controller_pre_dispatch(XenForo_FrontController $fc, XenForo_RouteMatch &$routeMatch)
	{
		self::$_prefixSection 	= $routeMatch->getMajorSection();
		self::$_prefixAction 	= $routeMatch->getAction();
	}

	protected static function _assertShowWidget()
	{
		$options = XenForo_Application::get('options');
		
		if ( ! $options->auctionWidgetEnabled)
		{
			return false;
		}
		
		if ($options->auctionWidgetMode == 'whitelist' AND ! self::$_prefixSection)
		{
			return false;
		}
		
		if ( ! XenForo_Visitor::getInstance()->hasPermission('auctions', 'viewAuctions'))
		{
			return false;
		}
		
		$criterias = explode("\n", $options->auctionWidgetCriteria);
		foreach ($criterias AS $criteria)
		{
			$criteria 	= explode('|', $criteria);
			$section 	= $criteria[0];
			$action  	= isset($criteria[1]) ? $criteria[1] : null;
			
			if ($section == self::$_prefixSection AND (empty($action) OR $action == self::$_prefixAction))
			{
				return ($options->auctionWidgetMode == 'whitelist');
			}
		}
		
		return ($options->auctionWidgetMode != 'whitelist');
	}

	public static function load_class_view($class, array &$extend)
	{
		/* Extend XenForo_ViewPublic_Account_Preferences */
		if ($class == 'XenForo_ViewPublic_Account_Preferences' AND ! in_array('XenAuction_ViewPublic_Account_Preferences', $extend))
		{
			$extend[] = 'XenAuction_ViewPublic_Account_Preferences';
		}
		/* Extend End */
	}

	public static function load_class_model($class, array &$extend)
	{
		/* Extend XenForo_Model_UserField */
		if ($class == 'XenForo_Model_UserField' AND ! in_array('XenAuction_Model_UserField', $extend))
		{
			$extend[] = 'XenAuction_Model_UserField';
		}
		/* Extend End */
	}


}