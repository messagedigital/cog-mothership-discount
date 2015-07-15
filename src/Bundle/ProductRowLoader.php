<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Entity\EntityLoaderInterface;
use Message\Cog\DB\QueryBuilderFactory;

/**
 * Class ProductRowLoader
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for lazy loading product data onto the bundle
 */
class ProductRowLoader implements EntityLoaderInterface
{
	const PRODUCT_TABLE = 'discount_bundle_product_row';
	const OPTION_TABLE = 'discount_bundle_product_option';

	/**
	 * @var QueryBuilderFactory
	 */
	private $_queryBuilderFactory;

	/**
	 * @var array
	 */
	private $_columns = [
		'p.product_row_id as id',
		'p.product_id',
		'p.quantity',
		'o.option_name',
		'o.option_value',
	];

	/**
	 * @param QueryBuilderFactory $queryBuilderFactory
	 */
	public function __construct(QueryBuilderFactory $queryBuilderFactory)
	{
		$this->_queryBuilderFactory = $queryBuilderFactory;
	}

	/**
	 * Load product data and create instances of ProductRow to assign to bundle
	 *
	 * @param BundleProxy $bundle
	 *
	 * @return array
	 */
	public function getProductRows(BundleProxy $bundle)
	{
		$result = $this->_queryBuilderFactory
			->getQueryBuilder()
			->select($this->_columns)
			->from('p', self::PRODUCT_TABLE)
			->leftJoin('o', 'p.product_row_id = o.product_row_id', self::OPTION_TABLE)
			->where('p.bundle_id = ?i', [$bundle->getID()])
			->getQuery()
			->run()
		;


		$productRowData = [];
		$productRows = [];

		// Reorganise data into mutlidimensional array split into product rows to allow for multiple options per row
		foreach ($result as $row) {
			if (!array_key_exists($row->id, $productRowData)) {
				$productRowData[$row->id] = [
					'product_id' => $row->product_id,
					'options' => $this->_getRowOptionsArray($row->option_name, $row->option_value),
					'quantity' => $row->quantity
				];
			}

			$productRowData[$row->id]['options'] = $productRowData[$row->id]['options'] + $this->_getRowOptionsArray($row->option_name, $row->option_value);
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

	private function _getRowOptionsArray($name, $value)
	{
		if ($name && $value && (!is_scalar($name) || !is_scalar($value))) {
			throw new \LogicException('Name and value must be scalar');
		}

		return ($name && $value) ? [$name => $value] : [];
	}
}