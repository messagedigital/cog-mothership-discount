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

	public function __construct(Query $query, Product\Loader $productLoader)
	{
		$this->_query 		  = $query;
		$this->_productLoader = $productLoader;
	}

	public function getByID($discountID)
	{
		return $this->_load($discountID);
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

		return (count($result) === 1 ? $this->_load($result->first()) : false);
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

		return $this->_load($result->flatten(), true);
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
					start IS NULL
					OR NOT start > :to?d
				)
			AND
				(
					end IS NULL
					OR NOT end < :from?d 
				)
			',
			array(
				'to' 	=> $to,
				'from'  => $from,
			)
		);

		return $this->_load($result->flatten(), true);
	}

	public function getActive()
	{
		$result = $this->_query->run(
			'SELECT
				discount_id
			FROM
				discount
			WHERE
				(
					start IS NULL
					OR start < :now?d
				)
			AND
				(
					end IS NULL
					OR end > :now?d
				)
			', array(
				"now" => new \DateTime(),
			)
		);

		return $this->_load($result->flatten(), true);
	}

	public function getInactive()
	{
		$result = $this->_query->run(
			'SELECT
				discount_id
			FROM
				discount
			WHERE
			(
				start > :now?d
				AND	start IS NOT NULL
			)
			OR
			(
				end < :now?d
				AND	end IS NOT NULL
			)
				
			', array(
				"now" => new \DateTime(),
			)
		);

		return $this->_load($result->flatten(), true);
	}

	public function getAll()
	{
		$result = $this->_query->run(
			'SELECT
				discount_id
			FROM
				discount
			'
		);

		return $this->_load($result->flatten(), true);
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

			if ($row->updated_at) {
				$discounts[$key]->authorship->update(
					new DateTimeImmutable(
						date('c', $row->updated_at)
					),
					$row->updated_by
				);
			}

			if ($row->deleted_at) {
				$discounts[$key]->authorship->delete(
					new DateTimeImmutable(
						date('c', $row->deleted_at)
					),
					$row->deleted_by
				);
			}

			$products = $this->_loadProducts($row->id);
			$appliesToOrder = (0 === count($products));

			$discounts[$key]->percentage 		= ($row->percentage !== null ? (float) $row->percentage : null);

			$discounts[$key]->products 			= $products;
			$discounts[$key]->appliesToOrder 	= $appliesToOrder;

			$discounts[$key]->start 			= ($row->start ? new DateTimeImmutable(date('c', $row->start)) : null);
			$discounts[$key]->end 				= ($row->end ? new DateTimeImmutable(date('c', $row->end)) : null);
			$discounts[$key]->freeShipping  	= (bool) $row->freeShipping;

			$discounts[$key]->thresholds 		= $this->_loadThresholds($discounts[$key]);
			$discounts[$key]->discountAmounts 	= $this->_loadDiscountAmounts($discounts[$key]);

			$return[$row->id] = $discounts[$key];
		}

		return $alwaysReturnArray || count($return) > 1 ? $return : reset($return);
	}

	protected function _loadThresholds($discount)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id,
				locale,
				currency_id AS currencyID,
				threshold
			FROM
				discount_threshold
			WHERE
				discount_id  = ?i
		', 	array(
				$discount->id,
			)
		);

		$thresholds = $result->bindTo('Message\\Mothership\\Discount\\Discount\\Threshold');
		$return = array();

		foreach ($result as $key => $data) {
			$thresholds[$key]->discount = $discount;
			$thresholds[$key]->threshold = (float) $data->threshold;

			// TODO Maybe load Locale-Object??

			$return[$data->currencyID] = $thresholds[$key];
		}

		return $return;
	}

	protected function _loadDiscountAmounts($discount)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id,
				locale,
				currency_id AS currencyID,
				amount
			FROM
				discount_amount
			WHERE
				discount_id  = ?i
		', 	array(
				$discount->id,
			)
		);

		$discountAmounts = $result->bindTo('Message\\Mothership\\Discount\\Discount\\DiscountAmount');
		$return = array();

		foreach ($result as $key => $data) {
			$discountAmounts[$key]->discount = $discount;
			$discountAmounts[$key]->amount = (float) $data->amount;

			// TODO Maybe load Locale-Object??
			$return[$data->currencyID] = $discountAmounts[$key];
		}

		return $return;
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
