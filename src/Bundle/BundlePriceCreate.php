<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB;

/**
 * Class BundlePriceCreate
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for saving bundle prices to the database. The query can be overridden with a Transaction object to allow the
 * class to handle both the creation and editing of a bundle efficiently.
 */
class BundlePriceCreate implements DB\TransactionalInterface
{
	/**
	 * @var DB\QueryableInterface
	 */
	private $_query;

	/**
	 * @var DB\QueryParser
	 */
	private $_queryParser;

	/**
	 * @param DB\QueryableInterface $query
	 * @param DB\QueryParser $queryParser
	 */
	public function __construct(DB\QueryableInterface $query, DB\QueryParser $queryParser)
	{
		$this->_query = $query;
		$this->_queryParser = $queryParser;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setTransaction(DB\Transaction $transaction)
	{
		$this->_query = $transaction;
	}

	/**
	 * Save bundle prices to the database.
	 *
	 * @param Bundle $bundle    The bundle the prices are assigned to
	 * @param bool $delete      If set to true, will delete prices currently assigned to database. True by default.
	 */
	public function save(Bundle $bundle, $delete = true)
	{
		if ($delete) {
			$this->_query->run("
				DELETE FROM
					discount_bundle_price
				WHERE
					bundle_id = :bundleID?i
			", [
				'bundleID' => $bundle->getID(),
			]);
		}

		$statements = [];

		foreach ($bundle->getPrices() as $currency => $price) {
			$statement = '(
				:bundleID?i,
				:currency?s,
				:price?f
			)';
			$params = [
				'bundleID' => $bundle->getID(),
				'currency' => $currency,
				'price'    => $price,
			];

			$statements[] = $this->_queryParser->parse($statement, $params);
		}

		$statements = implode(',' . PHP_EOL, $statements);

		$this->_query->run("
				INSERT INTO
					discount_bundle_price
					(
						bundle_id,
						currency,
						price
					)
				VALUES
			" . $statements);
	}

}