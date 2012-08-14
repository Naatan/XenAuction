<?php

/**
 * Helper methods for general XenAuction usage
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_Helper_Base
{
	
	public static function pageNavParams(array $params, array $strip = null)
	{
		if ($strip == null)
		{
			$strip = array();
		}
		
		foreach ($params AS $param => $value)
		{
			if ($value === null OR in_array($param, $strip))
			{
				unset($params[$param]);
				continue;
			}
			
			if (is_array($value))
			{
				foreach ($value AS $k => $v)
				{
					$params[$param . '['.$k.']'] = $v;
				}
				unset($params[$param]);
			}
		}
		
		return $params;
	}
	
}