<?php

/**
 * Datawriter for XenAuction tables
 */
class XenAuction_DataWriter_Bid extends XenForo_DataWriter
{
	
	/**
	 * Get fields managed by this datawriter
	 * 
	 * @return	array							
	 */
	protected function _getFields()
	{
		return array(
			'xf_auction_bid' => array(
				'bid_id'			=> array('type' => self::TYPE_UINT, 'autoIncrement' => true, 'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyBidid')),
				'auction_id'		=> array('type' => self::TYPE_UINT, 'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyAuctionid')),
				'user_id'			=> array('type' => self::TYPE_UINT, 'autoIncrement' => true, 'verification' => array('XenForo_DataWriter_Helper_User', 'verifyUserid')),
				'amount'			=> array('type' => self::TYPE_UINT, 'required' => true),
				'placement_date'	=> array('type' => self::TYPE_UINT, 'default' => XenForo_Application::$time)
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
		if ( ! $idBid = $this->_getExistingPrimaryKey($data, 'bid_id'))
		{
			return false;
		}

		return array('xf_auction_bid' => $this->getModelFromCache('XenAuction_Model_Auction')->getBidById($idBid));
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
		return 'bid_id = ' . $this->_db->quote($this->getExisting('bid_id'));
	}

}