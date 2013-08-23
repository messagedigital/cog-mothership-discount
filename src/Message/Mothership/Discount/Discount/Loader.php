<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\DB\Query;
use Message\Cog\DB\Result;
use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Mothership\Commerce\Product;

class Loader
{
	protected $_query;
	protected $_productLoader;
	protected $_thresholdLoader;
	protected $_discountAmountLoader;

	public function __construct(Query $query, Product\Loader $productLoader, DiscountAmount\Loader $discountAmountLoader, Threshold\Loader $thresholdLoader)
	{
		$this->_query 				 = $query;

		$this->_productLoader 		 = $productLoader;
		$this->_thresholdLoader 	 = $thresholdLoader;
		$this->_discountAmountLoader = $discountAmountLoader;
	}

	public function getByDateRange(\DateTime $from, \DateTime $to)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id
			FROM
				discount
			WHERE
			(
				end IS NULL
				(
					AND
						start < :to?d
					OR
						start IS NULL
				)
			)
			OR
			(
					end < :to?d
				AND
					end > :from?d
			)
			'
			array(
				'to' 	=> $to,
				'from'  => $from,
			)
		);

		return $this->_load($result->flatten());
	}

	public function getByID($discountID)
	{
		return $this->_load($discountID, false);
	}

	public function getByCode($code)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id
			FROM
				discount
			WHERE
				code = ?s',
			array(
				$code
			)
		);

		return (count($result) === 1 ? $this->_load($result->first(), false) : false);
	}

	public function getByProduct(Product\Product $product)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id
			FROM
				discount_product
			WHERE
				product_id = ?i',
			array(
				$product->id
			)
		);

		return $this->_load($result->flatten());
	}

	protected function _load($ids, $alwaysReturnArray = false)
	{
		if (!is_array($ids)) {
			$ids = (array) $ids;
		}

		if (!$ids) {
			return $alwaysReturnArray ? array() : false;
		}

		$result = $this->_query->run('
			SELECT
				*,
				discount_id 	AS id,
				free_shipping 	AS freeShipping
			FROM
				discount
			WHERE
				discount_id IN (?ij)
		', array($ids));

		if (0 === count($result)) {
			return $alwaysReturnArray ? array() : false;
		}

		$discounts = $result->bindTo('Message\\Mothership\\Discount\\Discount\\Discount');
		$return   = array();

		foreach ($result as $key => $row) {
			$discounts[$key]->authorship->create(
				new DateTimeImmutable(
					date('c', $row->created_at)
				),
				$row->created_by
			);

			$products = $this->_loadProducts($row->id);
			$appliesToOrder = (0 === count($products));

			$discounts[$key]->percentage 		= ($row->percentage !== null ? (float) $row->percentage : null);


			$discounts[$key]->products 			= $products;
			$discounts[$key]->appliesToOrder 	= $appliesToOrder;

			$discounts[$key]->start 			= ($row->start ? new DateTimeImmutable(date('c', $row->start)) : null);
			$discounts[$key]->end 				= ($row->end ? new DateTimeImmutable(date('c', $row->end)) : null);
			$discounts[$key]->freeShipping  	= (bool) $row->freeShipping;

			$discounts[$key]->thresholds 		= $this->_thresholdLoader->getByDiscount($discounts[$key]);
			$discounts[$key]->discountAmounts 	= $this->_discountAmountLoader->getByDiscount($discounts[$key]);

			$return[$row->id] = $discounts[$key];
		}

		return $alwaysReturnArray || count($return) > 1 ? $return : reset($return);
	}

	protected function _loadProducts($discountID)
	{
		$results = $this->_query->run(
			'SELECT
				product_id	AS productID
			FROM
				discount_product
			WHERE
				discount_id = ?i
		', array(
			$discountID,
		));

		$products = array();
		foreach($results->flatten() as $productID) {
			$products[$productID] = $this->_productLoader->getByID($productID);
		}

		return $products;
	}

}
