<?php

/**
 * Datawriter for XenAuction tables
 */
class XenAuction_DataWriter_Auction extends XenForo_DataWriter
{
	
	/**
	 * Get fields managed by this datawriter
	 * 
	 * @return	array							
	 */
	protected function _getFields()
	{
		return array(
			'xf_auction' => array(
				'auction_id'		=>
					array(	'type' => self::TYPE_UINT, 	'autoIncrement' => true,
							'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyAuctionid')),
					
				'user_id'			=> array('type' => self::TYPE_UINT,			'required' => true),
				'title'				=> array('type' => self::TYPE_STRING, 		'required' => true, 		'maxLength' => 150),
				'message'			=> array('type' => self::TYPE_STRING, 		'required' => true),
				'status'			=> array('type' => self::TYPE_STRING, 		'default'  => XenAuction_Model_Auction::STATUS_ACTIVE),
				'archived'			=> array('type' => self::TYPE_UINT, 		'default'  => 0),
				'tags'				=> array('type' => self::TYPE_STRING, 		'default'  => '', 			'maxLength' => 255),
				'image'				=> array('type' => self::TYPE_STRING, 		'default'  => NULL, 		'maxLength' => 50),
				'min_bid'			=> array('type' => self::TYPE_UINT_FORCED, 	'default'  => NULL),
				'buy_now'			=> array('type' => self::TYPE_UINT_FORCED, 	'default'  => NULL),
				'bids'				=> array('type' => self::TYPE_UINT, 		'default'  => 0),
				
				'availability'		=>
					array(	'type' => self::TYPE_UINT, 		'default'  => NULL,
							'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyAvailability')),
				
				'top_bid'			=> array('type' => self::TYPE_UINT, 		'default'  => NULL),
				'top_bidder'		=> array('type' => self::TYPE_UINT, 		'default'  => NULL),
				'placement_date'	=> array('type' => self::TYPE_UINT, 		'default'  => XenForo_Application::$time),
				
				'expiration_date'	=>
					array(	'type' => self::TYPE_UINT, 'default'  => XenForo_Application::$time + 86400,
							'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyExpirationDate'))
			)
		);
	}
	
	protected function _preSave()
	{
		if ($this->get('min_bid') == NULL AND $this->get('buy_now') == NULL)
		{
			$this->error(new XenForo_Phrase('must_use_bid_or_buyout'));
		}
		
		$tags = trim($this->get('tags'));
		
		if ( ! empty($tags))
		{
			if (substr($tags,0,1) != ',')
			{
				$tags = ',' . $tags;
			}
			
			if (substr($tags,-1) != ',')
			{
				$tags .= ',';
			}
		}
		
		$this->set('tags', $tags);
	}

	/**
	 * Get existing data
	 * 
	 * @param	int			$data
	 * 
	 * @return	array							
	 */
	protected function _getExistingData($data)
	{
		if ( ! $idAuction = $this->_getExistingPrimaryKey($data, 'auction_id'))
		{
			return false;
		}

		return array('xf_auction' => $this->getModelFromCache('XenAuction_Model_Auction')->getAuctionById($idAuction));
	}

	/**
	 * Get update condition
	 * 
	 * @param	string			$tableName
	 * 
	 * @return	string							
	 */
	protected function _getUpdateCondition($tableName)
	{
		return 'auction_id = ' . $this->_db->quote($this->getExisting('auction_id'));
	}

}