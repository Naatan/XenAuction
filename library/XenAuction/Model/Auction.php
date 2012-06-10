<?php

/**
 * Model for xensso_master_assoc table
 */
class XenAuction_Model_Auction extends XenForo_Model
{
	
	const FETCH_USER     		= 0x01;

	/**
	 * Get by Auction ID
	 * 
	 * @param	int			$idAuction
	 * 
	 * @return	array|bool
	 */
	public function getAuctionById($idAuction)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM xf_auction
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
	 * Get list of auctions
	 * 
	 * @param	array			$fetchOptions
	 * 
	 * @return	array|bool
	 */
	public function getAuctions(array $fetchOptions = array())
	{
		$limitOptions 	= $this->prepareLimitFetchOptions($fetchOptions);
		$joinOptions 	= $this->prepareAuctionFetchOptions($fetchOptions);

		$orderClause 	= $this->prepareAuctionOrderOptions($fetchOptions, 'auction.expiration_date');
		$whereClause 	= (!empty($fetchOptions['validOnly']) ? 'WHERE auction.expiration_date > \''.time().'\'' : '');

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT auction.*
					' . $joinOptions['selectFields'] . '
				FROM xf_auction AS auction
				' . $joinOptions['joinTables'] . '
				' . $whereClause . '
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'auction_id');
	}
	
	/**
	 * Get list of bids for user
	 * 
	 * @param	array			$fetchOptions
	 * 
	 * @return	array|bool
	 */
	public function getUserBids(array $fetchOptions = array())
	{
		$limitOptions 	= $this->prepareLimitFetchOptions($fetchOptions);
		$orderClause 	= $this->prepareAuctionOrderOptions($fetchOptions, 'auction.expiration_date');

		return $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT bid.*
				FROM xf_auction_bid AS bid
				JOIN xf_auction AS auction ON
					bid.auction_id = auction.auction_id
				' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'bid_id');
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

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_USER)
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