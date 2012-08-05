<?php

/**
 * Various template helpers
 *
 * Mostly to account for XenForo's shortcomings
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_TemplateHelpers_Base
{
	
	/**
	 * Overloader so we don't need to manually define helpers every time
	 * 
	 * @param string $method 
	 * 
	 * @return mixed    
	 */
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
	
	/**
	 * Get basePath
	 * 
	 * @return string    
	 */
	public static function helperBasePath() 
	{
		$paths = XenForo_Application::getRequestPaths(new Zend_Controller_Request_Http);
		return $paths['basePath'];
	}
	
	/**
	 * Get current unix timestamp
	 * 
	 * @return int    
	 */
	public static function helperTime()
	{
		return time();
	}

	/**
	 * Strip HTML from string
	 * 
	 * @param string $text 
	 * 
	 * @return string    
	 */
	public static function helperStripHtml($text) 
	{
		$text = html_entity_decode($text);
		return utf8_encode(strip_tags($text));
	}
	
	/**
	 * Validate that user has the given permission
	 * 
	 * @param string $permission 
	 * 
	 * @return bool    
	 */
	public static function helperHasPermission($permission)
	{
		$visitor = XenForo_Visitor::getInstance();
		
		// Account for OR and AND operators
		if (strpos($permission, '||') OR strpos($permission, '&&'))
		{
			$isOr 			= strpos($permission, '||') !== false;
			$permissions 	= explode($isOr ? '||' : '&&', $permission);
			
			foreach ($permissions AS $permission)
			{
				if ($visitor->hasPermission('auctions', $permission))
				{
					if ($isOr)
					{
						return true;
					}
				}
				else if ( ! $isOr)
				{
					return false;
				}
			}
		}
		
		return $visitor->hasPermission('auctions', $permission);
	}
	
	/**
	 * Parse auction tags
	 *
	 * Translate an xf_auction_tag result array to a comma separated list of tags
	 * 
	 * @param array|bool $tags 
	 * 
	 * @return string    
	 */
	public static function helperTags($tags)
	{
		if (empty($tags))
		{
			return '';
		}
		
		$tags = array_map( create_function('$a', 'return $a["name"];'), $tags);
		return implode(', ', $tags);
	}
	
	/**
	 * Parse auction image from xf_auction entry
	 * 
	 * @param array  	 $auction   	the xf_auction entry
	 * @param string	 $size      	What image size to render
	 * @param bool		 $link      	Whether to link the image to the full size 
	 * @param bool		 $showEmpty 	Whether to show the image if no image is defined
	 * 
	 * @return string    
	 */
	public static function helperImage(array $auction, $size = 'n', $link = false, $showEmpty = true)
	{
		if ($showEmpty == false AND empty($auction['image']))
		{
			return '';
		}
		
		// Prepare image path
		$path = XenForo_Application::get('config')->externalDataPath;
		if ( ! preg_match('#^/|\\|[a-z]:#i', $path))
		{
			$paths 	= XenForo_Application::getRequestPaths(new Zend_Controller_Request_Http);
			$path 	= $paths['basePath'] . $path;
		}
		
		// Set image name
		$image 	= empty($auction['image']) ? 'image' : $auction['image'];
		
		$realPath = sprintf('%s/xenauction/%s_%s.jpg', XenForo_Helper_File::getExternalDataPath(), $image, $size);
		if ( ! file_exists($realPath))
		{
			$image = 'image';
		}
		
		// Parse image
		if ($size != 'l')
		{
			$out 	= sprintf('<div style="background-image: url(%s/xenauction/%s_%s.jpg)" class="auctionImage size_%s"></div>', $path, $image, $size, $size);
		}
		else // if it's the full size image use an img tag
		{
			$out 	= sprintf('<img src="%s/xenauction/%s_%s.jpg" class="auctionImage" />', $path, $image, $size);
		}
		
		// Wrap link around the image if requested
		if ($link AND ! empty($auction['image']))
		{
			$link 	= sprintf("%s/xenauction/%s_l.jpg", $path, $image);
			$out 	= sprintf('<a href="%s" class="auctionImageLink" target="_blank" rel="lightbox">%s</a>', $link, $out);
		}
		
		return $out;
	}
	
	/**
	 * Create public link with multiple query params
	 *
	 * For some reason the standard XenForo xen:link helper only supports one query param
	 * It might support more but the implementation is neither documented nor obvious
	 * and I got tired of trial and error
	 * 
	 * @param string $link 
	 * @param string $args 
	 * 
	 * @return string    	XenForo_Link::buildPublicLink
	 */
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
	
	/**
	 * Show user avatar
	 *
	 * Because the default xen:avatar helper does not work with arrays that are generated inside the template
	 * 
	 * @param array 	$user 
	 * @param string 	$size 
	 * 
	 * @return string    XenForo_Template_Helper_Core::helperAvatarHtml
	 */
	public static function helperAvatar($user, $size = 'n')
	{
		// workaround for template compiler bug
		return XenForo_Template_Helper_Core::helperAvatarHtml($user, false, array('size' => $size));
	}
	
}