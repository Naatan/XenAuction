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
				'auction_id'		=> array('type' => self::TYPE_UINT, 'autoIncrement' => true, 'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyAuctionid')),
				'title'				=> array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 150),
				'message'			=> array('type' => self::TYPE_STRING, 'required' => true),
				'tags'				=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 255),
				'image'				=> array('type' => self::TYPE_STRING, 'default' => NULL, 'maxLength' => 50),
				'min_bid'			=> array('type' => self::TYPE_UINT, 'default' => NULL),
				'buy_now'			=> array('type' => self::TYPE_UINT, 'default' => NULL),
				'bids'				=> array('type' => self::TYPE_UINT, 'default' => 0),
				'availability'		=> array('type' => self::TYPE_UINT, 'default' => NULL),
				'top_bid'			=> array('type' => self::TYPE_UINT, 'default' => NULL),
				'top_bidder'		=> array('type' => self::TYPE_UINT, 'default' => NULL),
				'placement_date'	=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time),
				'expiration_date'	=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time + 86400)
			)
		);
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