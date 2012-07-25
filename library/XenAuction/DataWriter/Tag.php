<?php

/**
 * DataWriter for xf_auction_tag table
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_DataWriter_Tag extends XenForo_DataWriter
{
	
	/**
	 * Get fields managed by this datawriter
	 * 
	 * @return	array							
	 */
	protected function _getFields()
	{
		return array(
			'xf_auction_tag' => array(
				
				'tag_id' 	=> array( 'type' => self::TYPE_UINT, 'autoIncrement' => true ),
					
				'name'		=> array( 'type' => self::TYPE_STRING )
				
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
		if ( ! $name = $this->getExisting($data, 'name'))
		{
			return false;
		}

		return array(
			'xf_auction_tag' 		=> $this->getModelFromCache('XenAuction_Model_Tag')->getTagByName($name)
		);
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
		return 'tag_id = ' . $this->_db->quote($this->getExisting('tag_id'));
	}

}