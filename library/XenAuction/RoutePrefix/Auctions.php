<?php

class XenAuction_RoutePrefix_Auctions
{

	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		return $router->getRouteMatch('XenAuction_ControllerPublic_Auctions', $routePath, 'auctions');
	}


}