<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Entity\EntityLoaderInterface;
use Message\Cog\DB\QueryBuilderFactory;

class ProductRowLoader implements EntityLoaderInterface
{
	const PRODUCT_TABLE = 'discount_bundle_product_row';
	const OPTION_TABLE = 'discount_bundle_product_option';

	private $_queryBuilderFactory;

	private $_columns = [
		'p.product_row_id as id',
		'p.product_id',
		'p.quantity',
		'o.option_name',
		'o.option_value',
	];

	public function __construct(QueryBuilderFactory $queryBuilderFactory)
	{
		$this->_queryBuilderFactory = $queryBuilderFactory;
	}

	public function getProductRows(BundleProxy $bundle)
	{
		$result = $this->_queryBuilderFactory
			->getQueryBuilder()
			->select($this->_columns)
			->from(self::PRODUCT_TABLE)
			->leftJoin(self::OPTION_TABLE, 'p.product_row_id = o.product_row_id')
			->where('p.bundle_id = ?i', $bundle->getID())
		;

		$productRowData = [];
		$productRows = [];

		// Reorganise data into mutlidimensional array split into product rows to allow for multiple options per row
		foreach ($result as $row) {
			if (!array_key_exists($row->id, $productRowData)) {
				$productRowData[$row->id] = [
					'product_id' => $row->product_id,
					'options' => [$row->option_name => $row->option_value],
					'quantity' => $row->quantity
				];
			}

			$productRowData[$row->id]['options'] = $productRowData[$row->id]['options'] + [$row->option_name => $row->option_value];
		}

		foreach ($productRowData as $data) {
			$productRows[] = new ProductRow(
				$data['product_id'],
				$data['options'],
				$data['quantity']
			);
		}

		return $productRows;
	}
}