<?php

/**
 * Datawriter for xf_auction table
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
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
				'image'				=> array('type' => self::TYPE_STRING, 		'default'  => NULL, 		'maxLength' => 50),
				
				'min_bid'			=> array('type' => self::TYPE_UINT_FORCED, 	'default'  => NULL,
							'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyMinBid')),
				
				'buy_now'			=> array('type' => self::TYPE_UINT_FORCED, 	'default'  => NULL,
							'verification' => array('XenAuction_DataWriter_Helper_Auction', 'verifyBuyNow')),
				
				'bids'				=> array('type' => self::TYPE_UINT, 		'default'  => 0),
				'sales'				=> array('type' => self::TYPE_UINT, 		'default'  => 0),
				
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
	
	/**
	 * Support setting field values to NULL
	 * 
	 * @param string $field 
	 * 
	 * @return void    
	 */
	protected function setNull($field)
	{
		$fields 	= $this->_fields['xf_auction'];
		$validField = isset($fields[$field]) && is_array($fields[$field]);
		
		if ( ! $validField)
		{
			$this->error("The field '$field' was not recognised.", $field, false);
		}
		else
		{
			$this->_setInternal('xf_auction', $field, NULL);
		}
	}
	
	/**
	 * Actions performed right before saving data to database
	 * 
	 * @return void    
	 */
	protected function _preSave()
	{
		if ($this->get('min_bid') === NULL AND $this->get('buy_now') === NULL)
		{
			$this->error(new XenForo_Phrase('must_use_bid_or_buyout'));
		}
		
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