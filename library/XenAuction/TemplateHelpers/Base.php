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
		$paths = XenForo_Application::getRequestPaths(new Zend_Controller_Request_Http);
		return $paths['basePath'];
	}
	
	public static function helperTime()
	{
		return time();
	}

	public static function helperStripHtml($text) 
	{
		$text = html_entity_decode($text);
		return utf8_encode(strip_tags($text));
	}
	
	public static function helperHasPermission($permission)
	{
		$visitor = XenForo_Visitor::getInstance();
		return $visitor->hasPermission('auctions', $permission);
	}
	
	public static function helperTags($tags)
	{
		if (empty($tags))
		{
			return '';
		}
		
		$tags = array_map( create_function('$a', 'return $a["name"];'), $tags);
		return implode(', ', $tags);
	}
	
	public static function helperImage(array $auction, $size = 'n', $link = false, $showEmpty = true)
	{
		if ($showEmpty == false AND empty($auction['image']))
		{
			return '';
		}
		
		$path = XenForo_Application::get('config')->externalDataPath;
		
		if ( ! preg_match('#^/|\\|[a-z]:#i', $path))
		{
			$paths 	= XenForo_Application::getRequestPaths(new Zend_Controller_Request_Http);
			$path 	= $paths['basePath'] . $path;
		}
		
		$image 	= empty($auction['image']) ? 'image' : $auction['image'];
		
		if ($size != 'l')
		{
			$out 	= sprintf('<div style="background-image: url(%s/xenauction/%s_%s.jpg)" class="auctionImage size_%s"></div>', $path, $image, $size, $size);
		}
		else
		{
			$out 	= sprintf('<img src="%s/xenauction/%s_%s.jpg" class="auctionImage" />', $path, $image, $size);
		}
		
		if ($link AND ! empty($auction['image']))
		{
			$link 	= sprintf("%s/xenauction/%s_l.jpg", $path, $image);
			$out 	= sprintf('<a href="%s" class="auctionImageLink" target="_blank" rel="lightbox">%s</a>', $link, $out);
		}
		
		return $out;
	}
	
	public static function helperLink($link, $args = '')
	{
		$options 	= array();
		$args 		= explode(',', $args);
		
		foreach ($args AS $arg)
		{
			$arg 				= explode('=', $arg);
			$options[$arg[0]] 	= $arg[1];
		}
		
		return XenForo_Link::buildPublicLink($link, '', $options);
	}
	
	public static function helperAvatar($user, $size = 'n')
	{
		// workaround for template compiler bug
		return XenForo_Template_Helper_Core::helperAvatarHtml($user, false, array('size' => $size));
	}
	
}