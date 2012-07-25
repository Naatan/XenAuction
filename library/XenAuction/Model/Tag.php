<?php

/**
 * Model for auction tag related queries
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_Model_Tag extends XenForo_Model
{

	/**
	 * Get tag by ID
	 * 
	 * @param int $tagId 
	 * 
	 * @return array|bool    Zend_Db_Adapter_Abstract::fetchRow
	 */
	public function getTagById($tagId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction_tag 
			WHERE tag_id = ?
		', $tagId);
	}
	
	/**
	 * Get tag by name
	 * 
	 * @param string $name 
	 * 
	 * @return array|bool    Zend_Db_Adapter_Abstract::fetchRow
	 */
	public function getTagByName($name)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction_tag 
			WHERE name = ?
		', $name);
	}
	
	/**
	 * Get all tags
	 * 
	 * @return array|bool    XenForo_Model::fetchAllKeyed
	 */
	public function getTags()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_auction_tag
			ORDER BY name ASC
		', 'name');
	}
	
	/**
	 * Get tags by auction ID
	 * 
	 * @param int $auctionId 
	 * 
	 * @return array|bool    XenForo_Model::fetchAllKeyed
	 */
	public function getTagsByAuction($auctionId)
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_auction_tag_rel rel 
			JOIN xf_auction_tag tag ON
				tag.tag_id = rel.tag_id
			WHERE rel.auction_id = ?
		', 'tag_id', $auctionId);
	}
	
	/**
	 * Get tags by auction id's
	 * 
	 * @param array   $auctionIds 
	 * 
	 * @return array|bool    self::fetchAllKeyGrouped
	 */
	public function getTagsByAuctions(array $auctionIds)
	{
		if (empty($auctionIds))
		{
			return false;
		}
		
		return $this->fetchAllKeyGrouped('
			SELECT *
			FROM xf_auction_tag_rel rel 
			JOIN xf_auction_tag tag ON
				tag.tag_id = rel.tag_id
			WHERE rel.auction_id IN (' . $this->_getDb()->quote($auctionIds) . ')
		', 'auction_id');
	}
	
	/**
	 * Delete tags for specified auction id
	 * 
	 * @param int $auctionId 
	 * 
	 * @return void    
	 */
	public function deleteTagsFromAuction($auctionId)
	{
		$db = $this->_getDb();
		$db->delete('xf_auction_tag_rel', 'auction_id = ' . $db->quote($auctionId));
	}
	
	/**
	 * Add tags to auction
	 * 
	 * @param array|string 	$tags      
	 * @param int 			$auctionId 
	 * 
	 * @return void    
	 */
	public function addTagToAuction($tags, $auctionId)
	{
		if ( ! is_array($tags))
		{
			$tags = array($tags);
		}
		
		$allTags 	= $this->getTags();
		
		// Loop through tags that are to be added / connected
		foreach ($tags AS $tagName)
		{
			
			// If the tag is not numeric (tag name) and is not defined already, add it
			if ( ! is_numeric($tagName) AND ! isset($allTags[$tagName]))
			{
				$dwTag 	= XenForo_DataWriter::create('XenAuction_DataWriter_Tag');
				$dwTag->set('name', trim($tagName));
				$dwTag->save();
				
				$tagData 	= $dwTag->getMergedData();
				$tagId 		= $tagData['tag_id'];
			}
			else // Otherwise just hook it up
			{
				if ( ! is_numeric($tagName) AND isset($allTags[$tagName]))
				{
					$tagId = $allTags[$tagName]['tag_id'];
				}
				else
				{
					$tagId = $tagName;
				}
			}
			
			// If the name is already in use MySQL will return an error
			// which is by design, so we don't care about it (saves us a query)
			try
			{
				$dwRel = XenForo_DataWriter::create('XenAuction_DataWriter_TagRel');
				$dwRel->set('tag_id', $tagId);
				$dwRel->set('auction_id', $auctionId);
				$dwRel->save();
			} catch (Zend_Db_Statement_Exception $e) {}	
			
		}
	}
	
	/**
	 * Helper function to fetch entries by key and group them by key
	 * 
	 * @param string $sql 
	 * @param string $key 
	 * 
	 * @return array|bool    
	 */
	protected function fetchAllKeyGrouped($sql, $key)
	{
		$results = array();
		$i = 0;

		$stmt = $this->_getDb()->query($sql, array(), Zend_Db::FETCH_ASSOC);
		while ($row = $stmt->fetch())
		{
			$i++;
			
			if ( ! isset($results[$row[$key]]))
			{
				$results[$row[$key]] = array();
			}
			
			$results[$row[$key]][] = $row;
		}

		return empty($results) ? false : $result;
	}
	
}