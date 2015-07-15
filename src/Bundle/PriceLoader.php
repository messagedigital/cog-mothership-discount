<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Entity\EntityLoaderInterface;
use Message\Cog\DB\QueryBuilderFactory;

/**
 * Class PriceLoader
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for lazy loading price data onto bundle
 */
class PriceLoader implements EntityLoaderInterface
{
	const TABLE_NAME = 'discount_bundle_price';

	/**
	 * @var QueryBuilderFactory
	 */
	private $_queryBuilderFactory;

	/**
	 * @var array
	 */
	private $_columns = [
		'bundle_id',
		'currency',
		'price'
	];

	/**
	 * @param QueryBuilderFactory $queryBuilderFactory
	 */
	public function __construct(QueryBuilderFactory $queryBuilderFactory)
	{
		$this->_queryBuilderFactory = $queryBuilderFactory;
	}

	/**
	 * Load prices for bundle and return as an associative array
	 *
	 * @param BundleProxy $bundle
	 *
	 * @return array
	 */
	public function getPrices(BundleProxy $bundle)
	{
		$result = $this->_queryBuilderFactory->getQueryBuilder()
			->select($this->_columns)
			->from(self::TABLE_NAME)
			->where('bundle_id = ?i', $bundle->getID())
			->getQuery()
			->run();
		;

		$prices = [];

		foreach ($result as $row) {
			$prices[$row->currency] = $row->price;
		}

		return $prices;
	}
}