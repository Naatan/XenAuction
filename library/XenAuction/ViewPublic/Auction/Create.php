<?php

/**
 * View for creating / editing auctions
 *
 * This seems to be the established way of embedding the wysiwyg editor
 *
 * It's a roundabout and unintuitive way of doing things, but established none the less
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_ViewPublic_Auction_Create extends XenForo_ViewPublic_Base
{
	public function renderHtml()
	{
		$message = '';
		
		if (isset($this->_params['auction']))
		{
			$message = $this->_params['auction']['message'];
		}
		
		$this->_params['editorTemplate'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate($this, 'message', $message);
	}
}