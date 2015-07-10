<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Cog\DB;
use Message\User\UserInterface;

class Create
{
	private $_query;
	private $_queryParser;
	private $_user;

	public function __construct(DB\Query $query, DB\QueryParser $queryParser, UserInterface $user)
	{
		$this->_query = $query;
		$this->_queryParser = $queryParser;
	}

	public function create(Bundle $bundle)
	{
		if (!$bundle->getAuthorship()->createdAt()) {
			$bundle->getAuthorship()->create(
				new DateTimeImmutable,
				$this->_user->id
			);
		}

		$result = $this->_query->run("
			INSERT INTO
				discount_bundle
				(
					`name`,
					display_name,
					allow_codes,
					start,
					`end`,
					created_at,
					created_by,
				)
			VALUES
				(
					:name?s,
					:displayName?s,
					:allowCodes?b,
					:start?dn,
					:end?dn,
					:createdAt?d,
					:createdBy?i
				)
		", [
			'name'        => $bundle->getName(),
			'displayName' => $bundle->getDisplayName(),
			'allowCodes'  => $bundle->allowCodes(),
			'start'       => $bundle->getStart(),
			'end'         => $bundle->getEnd(),
			'createdAt'   => new \DateTime,
			'createdBy'   => $this->_user->id,
		]);

		$bundle->setID($result->id());

		$this->_saveProducts($bundle);
		$this->_savePrices($bundle);

		return $bundle;
	}

	private function _saveProducts(Bundle $bundle)
	{
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
			]);

			$statements = [];

			foreach ($row->getOptions() as $name => $value) {
				$statement = "(						(
							:rowID?i,
							:name?s,
							:value?s
						)";
				$params = [
					'rowID' => $result->id(),
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
						option_name,
						option_value,
					)
				VALUES
			" . $statements);
		}
	}

	private function _savePrices(Bundle $bundle)
	{
		$statements = [];

		foreach ($bundle->getPrices() as $currency => $price) {
			$statement = '(
				bundleID?i,
				currency?s,
				price?f
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
						currency_id,
						price
					)
				VALUES
			" . $statements);
	}
}