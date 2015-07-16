<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB;

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

	public function __construct(DB\QueryableInterface $query, DB\QueryParser $queryParser)
	{
		$this->_query = $query;
		$this->_queryParser = $queryParser;
	}

	public function setTransaction(DB\Transaction $transaction)
	{
		$this->_query = $transaction;
	}

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