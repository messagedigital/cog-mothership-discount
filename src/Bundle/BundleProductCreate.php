<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB;

/**
 * Class BundleProductCreate
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for saving product rows assigned to a bundle to the database.
 */
class BundleProductCreate
{
	/**
	 * @var DB\Query
	 */
	private $_query;

	/**
	 * @var DB\QueryParser
	 */
	private $_queryParser;

	/**
	 * @param DB\Query $query
	 * @param DB\QueryParser $queryParser
	 */
	public function __construct(DB\Query $query, DB\QueryParser $queryParser)
	{
		$this->_query = $query;
		$this->_queryParser = $queryParser;
	}

	/**
	 * Save product data assigned to a bundle to the database
	 *
	 * @param Bundle $bundle   The bundle the product rows are assigned to
	 * @param bool $delete     If set to true, existing product rows for the bundle will be deleted. True by default.
	 */
	public function save(Bundle $bundle, $delete = true)
	{
		if ($delete) {
			$this->_query->run("
				DELETE FROM
					discount_bundle_product_row
				WHERE
					bundle_id = :bundleID?i
			", [
				'bundleID' => $bundle->getID(),
			]);

			$this->_query->run("
				DELETE FROM
					discount_bundle_product_option
				WHERE
					bundle_id = :bundleID?i
			", [
				'bundleID' => $bundle->getID(),
			]);
		}

		foreach ($bundle->getProductRows() as $row) {
			$result = $this->_query->run("
				INSERT INTO
					discount_bundle_product_row
					(
						bundle_id,
						product_id,
						quantity
					)
				VALUES
					(
						:bundleID?i,
						:productID?i,
						:quantity?i
					)
			", [
				'bundleID' => $bundle->getID(),
				'productID' => $row->getProductID(),
				'quantity' => $row->getQuantity(),
			]);

			$statements = [];

			if (count($row->getOptions()) > 0) {
				foreach ($row->getOptions() as $name => $value) {
					$statement = "
					(
						:rowID?i,
						:bundleID?i,
						:name?s,
						:value?s
					)";
					$params = [
						'rowID' => $result->id(),
						'bundleID' => $bundle->getID(),
						'name' => $name,
						'value' => $value,
					];


					$statements[] = $this->_queryParser->parse($statement, $params);
				}

				$statements = implode(',' . PHP_EOL, $statements);

				$this->_query->run("
					INSERT INTO
						discount_bundle_product_option
						(
							product_row_id,
							bundle_id,
							option_name,
							option_value
						)
					VALUES
				" . $statements);
			}
		}
	}
}