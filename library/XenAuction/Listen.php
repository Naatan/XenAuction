<?php

class XenAuction_Listen
{
	
	public static function init_dependecies(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		
		XenForo_Template_Helper_Core::$helperCallbacks += array(
			'auction' => array('XenAuction_TemplateHelpers_Base', 'helper')
		);
		
	}
	
}