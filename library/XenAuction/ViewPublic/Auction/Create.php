<?php

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