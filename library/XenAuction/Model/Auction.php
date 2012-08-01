<?php

/**
 * Model for auction related data
 *
 * Include all auction & bid relates queries
 * excludes most tag related queries
 *
 * @package 		XenAuction
 * @author 			Nathan Rijksen <nathan@naatan.com>
 * @copyright		2012 Naatan.com
 */
class XenAuction_Model_Auction extends XenForo_Model
{
	
	const FETCH_BID     	= 'bid';
	
	const STATUS_ACTIVE 	= 'active';
	const STATUS_CANCELED 	= 'canceled';
	const STATUS_EXPIRED 	= 'expired';
	
	const BID_STATUS_WINNING 	= 'winning';
	const BID_STATUS_OUTBID 	= 'outbid';
	const BID_STATUS_REJECTED 	= 'rejected';

	/**
	 * Get by Auction ID
	 * 
	 * @param	int			$idAuction
	 * @param 	array 		$fetchOptions
	 * 
	 * @return	array|bool		Zend_Db_Adapter_Abstract::fetchRow
	 */
	public function getAuctionById($idAuction, array $fetchOptions = array())
	{
		$joinOptions = $this->prepareAuctionFetchOptions($fetchOptions);
		
		return $this->_getDb()->fetchRow('
			SELECT auction.*
				' . $joinOptions['selectFields'] . '
			FROM xf_auction AS auction
				' . $joinOptions['joinTables'] . '
			WHERE auction_id = ?
		', $idAuction);
	}
	
	/**
	 * Get by Auction based on fetch conditions
	 * 
	 * @param	array		$fetchConditions
	 * @param 	array 		$fetchOptions
	 * 
	 * @return	array|bool		Zend_Db_Adapter_Abstract::fetchRow
	 */
	public function getAuction(array $fetchConditions, array $fetchOptions = array())
	{
		$whereClause = $this->prepareAuctionFetchConditions($fetchConditions, $fetchOptions);
		$joinOptions = $this->prepareAuctionFetchOptions($fetchOptions);
		$orderClause = $this->prepareAuctionOrderOptions($fetchOptions, 'auction.auction_id');
		
		return $this->_getDb()->fetchRow('
			SELECT auction.*
				' . $joinOptions['selectFields'] . '
			FROM xf_auction AS auction
				' . $joinOptions['joinTables'] . '
			WHERE
			' . $whereClause . '
			' . $orderClause . '
			LIMIT 1
		');
	}
	
	/**
	 * Get bid by Bid ID
	 * 
	 * @param	int			$idBid
	 * 
	 * @return	array|bool		Zend_Db_Adapter_Abstract::fetchRow
	 */
	public function getBidById($idBid)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction_bid
			WHERE bid_id = ?
		', $idBid);
	}
	
	/**
	 * Get winning bid for auction
	 * 
	 * @param	int			$auctionId
	 * 
	 * @return	array|bool		Zend_Db_Adapter_Abstract::fetchRow
	 */
	public function getWinningBid($auctionId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction_bid
			WHERE
				auction_id = ? AND
				is_buyout = 0 AND
				bid_status = ?
			ORDER BY amount DESC
			LIMIT 1
		', $auctionId, self::BID_STATUS_WINNING);
	}
	
	/**
	 * Get a set of random auctions
	 * 
	 * @param int $amount 	Amount of auctions to retrieve (ie. limit)
	 * 
	 * @return array|bool    XenForo_Model::fetchAllKeyed
	 */
	public function getRandomAuctions($amount = 10)
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM xf_auction 
			WHERE
				`status` = ' . $this->_getDb()->quote(XenAuction_Model_Auction::STATUS_ACTIVE) . '
			ORDER BY rand()
			LIMIT ' . ((int) $amount) . '
		', 'auction_id');
	}

	/**
	 * Get list of auctions
	 * 
	 * @param	array			$fetchOptions
	 * 
	 * @return	array|bool		XenForo_Model::fetchAllKeyed
	 */
	public function getAuctions(array $conditions = array(), array $fetchOptions = array())
	{
		$limitOptions 	= $this->prepareLimitFetchOptions($fetchOptions);
		$joinOptions 	= $this->prepareAuctionFetchOptions($fetchOptions);
		
		$orderClause 	= $this->prepareAuctionOrderOptions($fetchOptions, 'auction.expiration_date');
		$whereClause 	= $this->prepareAuctionFetchConditions($conditions, $fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults('
				SELECT auction.*
					' . $joinOptions['selectFields'] . '
				FROM xf_auction AS auction
				' . $joinOptions['joinTables'] . '
				WHERE
				' . $whereClause . '
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'auction_id');
	}
	
	/**
	 * Get number of auctions resulting from query
	 * 
	 * @param array   $conditions   
	 * @param array   $fetchOptions 
	 * 
	 * @return array|bool    Zend_Db_Adapter_Abstract::fetchOne
	 */
	public function getAuctionCount(array $conditions = array(), array $fetchOptions = array())
	{
		$joinOptions 	= $this->prepareAuctionFetchOptions($fetchOptions);
		$whereClause 	= $this->prepareAuctionFetchConditions($conditions, $fetchOptions);
		
		return $this->_getDb()->fetchOne('
			SELECT COUNT(auction.auction_id)
			FROM xf_auction AS auction
			' . $joinOptions['joinTables'] . '
			WHERE
			' . $whereClause . '
		');
	}
	
	/**
	 * Get auction sales
	 *
	 * Queries the xf_auction_bid table for sales and joins the relevant auctions
	 * 
	 * @param array   $conditions   
	 * @param array   $fetchOptions 
	 * 
	 * @return array|bool    Zend_Db_Adapter_Abstract::fetchAll
	 */
	public function getSales(array $conditions = array(), array $fetchOptions = array())
	{
		// Enforce user_id as a condition
		if ( ! isset($conditions['user_id']))
		{
			throw new XenForo_Exception('user_id is a required condition');
		}
		
		// Strip userId from conditions as we want to insert it manually
		$userId = $conditions['user_id'];
		unset($conditions['user_id']);
		
		$limitOptions 	= $this->prepareLimitFetchOptions($fetchOptions);
		$whereClause 	= $this->prepareAuctionFetchConditions($conditions, $fetchOptions);
		
		$orderClause = $this->prepareAuctionOrderOptions($fetchOptions, 'bid.sale_date');
		
		return $this->_getDb()->fetchAll($this->limitQueryResults('
				SELECT bid.*, auction.*
				FROM xf_auction_bid bid
				JOIN xf_auction auction ON 
					auction.auction_id = bid.auction_id AND
					auction.user_id = ' . $this->_getDb()->quote($userId) . '
				WHERE
					' . $whereClause . ' AND
					(
						bid.is_buyout = 1 OR
						(
							auction.status = \'expired\' AND
							auction.top_bidder > 0
						)
					)
				
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		));
	}
	
	/**
	 * Get amount total amount of sales resulting from query
	 * 
	 * @param array   $conditions   
	 * @param array   $fetchOptions 
	 * 
	 * @return array|bool    Zend_Db_Adapter_Abstract::fetchOne
	 */
	public function getSalesCount(array $conditions = array(), array $fetchOptions = array())
	{
		// Enforce user_id as a condition
		if ( ! isset($conditions['user_id']))
		{
			throw new XenForo_Exception('user_id is a required condition');
		}
		
		// Strip userId from conditions as we want to insert it manually
		$userId = $conditions['user_id'];
		unset($conditions['user_id']);
		
		$whereClause 	= $this->prepareAuctionFetchConditions($conditions, $fetchOptions);
		
		return $this->_getDb()->fetchOne('
			SELECT COUNT(bid.bid_id)
			FROM xf_auction_bid bid
			JOIN xf_auction auction ON 
				auction.auction_id = bid.auction_id AND
				auction.user_id = ' . $this->_getDb()->quote($userId) . '
			WHERE
				' . $whereClause . ' AND
				(
					bid.is_buyout = 1 OR
					auction.status = \'expired\'
				)
		');
	}
	
	/**
	 * Get list of bids for user
	 *
	 * @param 	array 			$conditions
	 * @param	array			$fetchOptions
	 * 
	 * @return	array|bool		XenForo_Model::fetchAllKeyed
	 */
	public function getUserBids(array $conditions = array(), array $fetchOptions = array())
	{
		$limitOptions 	= $this->prepareLimitFetchOptions($fetchOptions);
		$joinOptions 	= $this->prepareAuctionFetchOptions($fetchOptions);
		$orderClause 	= $this->prepareAuctionOrderOptions($fetchOptions, 'auction.expiration_date');
		
		$whereClause 	= $this->prepareAuctionFetchConditions($conditions, $fetchOptions);
		
		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT *
				FROM xf_auction_bid AS bid
				JOIN xf_auction AS auction ON
					bid.auction_id = auction.auction_id
				' . $joinOptions['joinTables'] . '
				WHERE ' . $whereClause . '
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'bid_id');
	}
	
	/**
	 * Get list of bids for user
	 * 
	 * @param	array			$fetchOptions
	 * 
	 * @return	array|bool		Zend_Db_Adapter_Abstract::fetchOne
	 */
	public function getUserBidCount(array $conditions = array(), array $fetchOptions = array())
	{
		$joinOptions 	= $this->prepareAuctionFetchOptions($fetchOptions);
		$whereClause 	= $this->prepareAuctionFetchConditions($conditions, $fetchOptions);
		
		return $this->_getDb()->fetchOne('
			SELECT COUNT(bid.bid_id)
			FROM xf_auction_bid AS bid
			JOIN xf_auction AS auction ON
				bid.auction_id = auction.auction_id
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause . '
		');
	}
	
	/**
	 * Prepare fetch conditions for query
	 * 
	 * @param array   $conditions   
	 * @param array $fetchOptions 
	 * 
	 * @return string		Return the effective clause
	 */
	public function prepareAuctionFetchConditions(array $conditions, array &$fetchOptions)
	{
		// Prepare database class and variables
		$db 				= $this->_getDb();
		$sqlConditions 		= array();
		$searchConditions 	= array();
		
		// User ID (auctioneer)
		if ( isset($conditions['user_id']))
		{
			if (is_array($conditions['user_id']))
			{
				$sqlConditions[] = 'auction.user_id IN(' . $db->quote($conditions['user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'auction.user_id = ' . $db->quote($conditions['user_id']);
			}
		}
		
		// Bid User ID (purchaser)
		if ( isset($conditions['bid_user_id']))
		{
			if (is_array($conditions['bid_user_id']))
			{
				$sqlConditions[] = 'bid.bid_user_id IN(' . $db->quote($conditions['bid_user_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'bid.bid_user_id = ' . $db->quote($conditions['bid_user_id']);
			}
		}
		
		// Bid ID
		if ( isset($conditions['bid_id']))
		{
			$sqlConditions[] = 'bid.bid_id = ' . $db->quote($conditions['bid_id']);
		}
		
		// Bid Amount
		if ( isset($conditions['amount']))
		{
			$sqlConditions[] = 'bid.amount = ' . $db->quote($conditions['amount']);
		}
		
		// Bid Amount
		if ( isset($conditions['amount_under']))
		{
			$sqlConditions[] = 'bid.amount < ' . $db->quote($conditions['amount_under']);
		}
		
		// Auction ID
		if ( isset($conditions['auction_id']))
		{
			$sqlConditions[] = 'auction.auction_id = ' . $db->quote($conditions['auction_id']);
		}
		
		// Status
		if ( isset($conditions['status']))
		{
			$sqlConditions[] = 'auction.status = ' . $db->quote($conditions['status']);
		}
		
		// Tag
		if ( isset($conditions['tag']))
		{
			$tags = is_array($conditions['tag']) ? $conditions['tag'] : array($conditions['tag']);
			$sqlConditions[] = 'auction.auction_id IN (SELECT auction_id FROM xf_auction_tag_rel WHERE tag_id IN (' . $db->quote($tags) . '))';
		}
		
		// Expired
		if ( isset($conditions['expired']))
		{
			$sqlConditions[] = 'auction.expiration_date < ' . time();
		}
		
		// Has Sales
		if ( isset($conditions['has_sales']))
		{
			$sqlConditions[] = 'auction.sales > 0';
		}
		
		// Is buyout
		if ( isset($conditions['is_buyout']))
		{
			$sqlConditions[] = 'bid.is_buyout = ' . $db->quote($conditions['is_buyout']);
		}
		
		// Archived
		if ( isset($conditions['archived']))
		{
			$sqlConditions[] = 'auction.archived = ' . $db->quote($conditions['archived'] ? 1 : 0);
		}
		
		// Title
		if ( ! empty($conditions['title']))
		{
			$searchConditions[] = 'auction.title LIKE ' . $db->quote($conditions['title'] . '%');
		}
		else
		{
			$sqlConditions[] = 'auction.title = auction.title';
		}
		
		// Auction ID search
		if ( ! empty($conditions['auction_id_search']))
		{
			$searchConditions[] = 'auction.auction_id =' . $db->quote( $conditions['auction_id_search'] );
		}
		
		// Bid ID search
		if ( ! empty($conditions['bid_id_search']))
		{
			$searchConditions[] = 'bid.bid_id =' . $db->quote( $conditions['bid_id_search'] );
		}
		
		// Username
		if ( ! empty($conditions['username']))
		{
			$searchConditions[] = 'bid.bid_user_id = (SELECT user_id FROM xf_user WHERE username = ' .  $db->quote($conditions['username']) . ')';
		}
		
		// Parse search conditions
		if ($searchConditions)
		{
			$sqlConditions[] = '(' . implode(') OR (', $searchConditions) . ')';
		}
		
		// Parse SQL conditions and return
		if ($sqlConditions)
		{
			return '(' . implode(') AND (', $sqlConditions) . ')';
		}
		else
		{
			return '1=1';
		}
	}
	
	/**
	 * Prepares join-related fetch options.
	 *
	 * @param array $fetchOptions
	 *
	 * @return array Containing 'selectFields' and 'joinTables' keys.
	 */
	public function prepareAuctionFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		if ( ! empty($fetchOptions['join']))
		{
			if ( ! is_array($fetchOptions['join']))
			{
				$fetchOptions['join'] = array($fetchOptions['join']);
			}
			
			if (in_array(self::FETCH_BID, $fetchOptions['join']))
			{
				$selectFields .= ',
					bid.*';
				$joinTables .= '
					LEFT JOIN xf_auction_bid AS bid ON
						(bid.auction_id = auction.auction_id)';
			}
		}
			
		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	/**
	 * Construct 'ORDER BY' clause
	 *
	 * @param array $fetchOptions (uses 'order' key)
	 * @param string $defaultOrderSql Default order SQL
	 *
	 * @return string
	 */
	public function prepareAuctionOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'auction_id'		=> 'auction.auction_id',
			'expiration_date' 	=> 'auction.expiration_date',
			'bids' 				=> 'auction.bids',
			'top_bid' 			=> 'auction.top_bid',
			'buy_now' 			=> 'auction.buy_now',
			'sale_date' 		=> 'bid.sale_date',
			'bid_date' 			=> 'bid.bid_date',
			'amount'			=> 'bid.amount'
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

}