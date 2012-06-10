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
	
	public static function helperTime()
	{
		return time();
	}
	
}