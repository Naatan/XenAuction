<?php

/**
 * Model for xensso_master_assoc table
 */
class XenAuction_Model_Auction extends XenForo_Model
{
	
	const FETCH_USER     		= 'user';
	const FETCH_BID     		= 'bid';
	
	const STATUS_ACTIVE 	= 'active';
	const STATUS_CANCELED 	= 'canceled';
	const STATUS_EXPIRED 	= 'expired';
	

	/**
	 * Get by Auction ID
	 * 
	 * @param	int			$idAuction
	 * @param 	array 		$fetchOptions
	 * 
	 * @return	array|bool
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
	 * Get bid by Bid ID
	 * 
	 * @param	int			$idBid
	 * 
	 * @return	array|bool
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
	 * Get top bid for auction
	 * 
	 * @param	int			$auctionId
	 * 
	 * @return	array|bool
	 */
	public function getTopBid($auctionId)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction_bid
			WHERE
				auction_id = ? AND
				is_buyout = 0
			ORDER BY amount DESC
			LIMIT 1
		', $auctionId);
	}

	/**
	 * Get list of auctions
	 * 
	 * @param	array			$fetchOptions
	 * 
	 * @return	array|bool
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
	
	public function getSales($userId, array $fetchOptions = array())
	{
		$limitOptions 	= $this->prepareLimitFetchOptions($fetchOptions);

		$selectFields 	= '';
		$joinTables 	= '';
		
		if (in_array(self::FETCH_USER, $fetchOptions['join']))
		{
			$selectFields = ',user.*';
			$joinTables .= '
				JOIN xf_user AS user ON
					(user.user_id = bid.bid_user_id)';
		}
		
		$orderClause 	= $this->prepareAuctionOrderOptions($fetchOptions, 'auction.expiration_date');
		
		return $this->fetchAllKeyed($this->limitQueryResults('
				SELECT bid.*, auction.*
					' . $selectFields . '
				FROM xf_auction_bid bid
				JOIN xf_auction auction ON 
					auction.auction_id = bid.auction_id AND
					auction.user_id = ' . $this->_getDb()->quote($userId) . '
				' . $joinTables . '
				WHERE
					bid.is_buyout = 1 OR
					auction.status = \'expired\'
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'auction_id');
	}
	
	public function getSalesCount($userId, array $fetchOptions = array())
	{
		$joinTables 	= '';
		if (in_array(self::FETCH_USER, $fetchOptions['join']))
		{
			$joinTables .= '
				JOIN xf_user AS user ON
					(user.user_id = bid.bid_user_id)';
		}
		
		return $this->_getDb()->fetchOne('
				SELECT COUNT(bid.bid_id)
				FROM xf_auction_bid bid
				JOIN xf_auction auction ON 
					auction.auction_id = bid.auction_id AND
					auction.user_id = ' . $this->_getDb()->quote($userId) . '
				' . $joinTables . '
				WHERE
					bid.is_buyout = 1 OR
					auction.status = \'expired\'
			'
		);
	}
	
	/**
	 * Get list of bids for user
	 * 
	 * @param	array			$fetchOptions
	 * 
	 * @return	array|bool
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
	 * @return	array|bool
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
	
	public function prepareAuctionFetchConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();
		$searchConditions = array();

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
		
		if ( isset($conditions['bid_id']))
		{
			$sqlConditions[] = 'bid.bid_id = ' . $db->quote($conditions['bid_id']);
		}
		
		if ( isset($conditions['status']))
		{
			$sqlConditions[] = 'auction.status = ' . $db->quote($conditions['status']);
		}
		
		if ( isset($conditions['expired']))
		{
			$sqlConditions[] = 'auction.expiration_date < ' . time();
		}
		
		if ( isset($conditions['has_sales']))
		{
			$sqlConditions[] = 'auction.sales > 0';
		}
		
		if ( isset($conditions['is_buyout']))
		{
			$sqlConditions[] = 'bid.is_buyout = ' . $db->quote($conditions['is_buyout']);
		}
		
		if ( isset($conditions['is_buyout']))
		{
			$sqlConditions[] = 'bid.is_buyout = ' . $db->quote($conditions['is_buyout']);
		}
		
		if ( isset($conditions['archived']))
		{
			$sqlConditions[] = 'auction.archived = ' . $db->quote($conditions['archived'] ? 1 : 0);
		}
		
		if ( ! empty($conditions['title']))
		{
			$searchConditions[] = 'auction.title LIKE ' . $db->quote('%'. $conditions['title'] . '%');
		}
		
		if ( ! empty($conditions['tags']))
		{
			$tags = explode(',', $conditions['tags']);
			
			foreach ($tags AS $tag)
			{
				$searchConditions[] = 'auction.tags LIKE ' . $db->quote('%,%'. $tag . '%,%');
			}
		}
		
		if ($sqlConditions)
		{
			if ($searchConditions)
			{
				$sqlConditions[] = '(' . implode(') OR (', $searchConditions) . ')';
			}
			
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
			
			if (in_array(self::FETCH_USER, $fetchOptions['join']) AND in_array(self::FETCH_BID, $fetchOptions['join']))
			{
				$selectFields .= ',
					user.*';
				$joinTables .= '
					JOIN xf_user AS user ON
						(user.user_id = bid.bid_user_id)';
			}
			else if (in_array(self::FETCH_USER, $fetchOptions['join']))
			{
				$selectFields .= ',
					user.*';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = auction.top_bidder)';
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
			'expiration_date' 	=> 'auction.expiration_date',
			'bids' 				=> 'auction.bids',
			'top_bid' 			=> 'auction.top_bid',
			'buy_now' 			=> 'auction.buy_now'
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

}