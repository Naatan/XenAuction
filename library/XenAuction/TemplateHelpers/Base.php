<?php

class XenAuction_TemplateHelpers_Base
{
	
	public static function helper($method)
	{
		$method = 'helper' . ucfirst($method);
		
		if (method_exists('XenAuction_TemplateHelpers_Base', $method))
		{
			$args = func_get_args();
			array_shift($args);
			
			return call_user_func_array(array('XenAuction_TemplateHelpers_Base', $method), $args);
		}
		
	}
	public static function helperBasePath() 
	{
		$paths = XenForo_Application::getRequestPaths();
		return $paths['basePath'];
	}
	
	public static function helperTime()
	{
		return time();
	}

	public static function helperStripHtml($text) 
	{
		
		$text = html_entity_decode($text);
		return strip_tags($text);
	}
	
	public static function helperHasPermission($permission)
	{
		$visitor = XenForo_Visitor::getInstance();
		return $visitor->hasPermission('auctions', $permission);
	}
	
}