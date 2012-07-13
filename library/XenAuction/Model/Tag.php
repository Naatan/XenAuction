<?php

/**
 * Model for xenauction tag tables
 */
class XenAuction_Model_Tag extends XenForo_Model
{

	public function getTagById($tagId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction_tag 
			WHERE tag_id = ?
		', $tagId);
	}
	
	public function getTagByName($name)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction_tag 
			WHERE name = ?
		', $name);
	}
	
	public function getTags()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_auction_tag
			ORDER BY name ASC
		', 'name');
	}
	
	public function getTagsByAuction($auctionId)
	{
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM xf_auction_tag_rel rel 
			JOIN xf_auction_tag tag ON
				tag.tag_id = rel.tag_id
			WHERE rel.auction_id = ?
		', $auctionId);
	}
	
	public function getTagsByAuctions(array $auctionIds)
	{
		if (empty($auctionIds))
		{
			return false;
		}
		
		return $this->fetchAllKeySorted('
			SELECT *
			FROM xf_auction_tag_rel rel 
			JOIN xf_auction_tag tag ON
				tag.tag_id = rel.tag_id
			WHERE rel.auction_id IN (' . $this->_getDb()->quote($auctionIds) . ')
		', 'auction_id');
	}
	
	public function addTagToAuction($tags, $auctionId)
	{
		if ( ! is_array($tags))
		{
			$tags = array($tags);
		}
		
		$allTags 	= $this->getTags();
		
		foreach ($tags AS $tagName)
		{
			
			if ( ! is_numeric($tagName) AND ! isset($allTags[$tagName]))
			{
				$dwTag 	= XenForo_DataWriter::create('XenAuction_DataWriter_Tag');
				$dwTag->set('name', trim($tagName));
				$dwTag->save();
				
				$tagData 	= $dwTag->getMergedData();
				$tagId 		= $tagData['tag_id'];
			}
			else
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
			
			try // don't care about duplicate entry error
			{
				$dwRel = XenForo_DataWriter::create('XenAuction_DataWriter_TagRel');
				$dwRel->set('tag_id', $tagId);
				$dwRel->set('auction_id', $auctionId);
				$dwRel->save();
			} catch (Zend_Db_Statement_Exception $e) {}	
			
		}
	}
	
	protected function fetchAllKeySorted($sql, $key)
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

		return $results;
	}
	
}