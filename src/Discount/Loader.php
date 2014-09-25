<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\DB\Query;
use Message\Cog\DB\Result;
use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Cog\DB\Entity\EntityLoaderCollection;
use Message\Mothership\Commerce\Product;

class Loader
{
	protected $_includeDeleted = false;

	protected $_query;
	protected $_productLoader;
	protected $_thresholdLoader;
	protected $_discountAmountLoader;

	private $_entityLoaders;

	public function __construct(Query $query, Product\Loader $productLoader, EntityLoaderCollection $entityLoaders)
	{
		$this->_query 		  = $query;
		$this->_productLoader = $productLoader;
		$this->_entityLoaders = $entityLoaders;
	}

	public function includeDeleted($bool)
	{
		$this->_includeDeleted = (bool)$bool;

		return $this;
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
			' . (!$this->_includeDeleted ? 'AND	deleted_at IS NULL' : '')
		, array($ids));

		if (0 === count($result)) {
			return $alwaysReturnArray ? array() : false;
		}

		$discounts = $result->bindTo('Message\\Mothership\\Discount\\Discount\\DiscountProxy',
			[$this->_entityLoaders]);

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

			$discounts[$key]->percentage      = ($row->percentage !== null ? (float) $row->percentage : null);

			$discounts[$key]->start           = ($row->start ? new DateTimeImmutable(date('c', $row->start)) : null);
			$discounts[$key]->end             = ($row->end ? new DateTimeImmutable(date('c', $row->end)) : null);
			$discounts[$key]->freeShipping    = (bool) $row->freeShipping;

			$discounts[$key]->thresholds      = $this->_loadThresholds($discounts[$key]);
			$discounts[$key]->discountAmounts = $this->_loadDiscountAmounts($discounts[$key]);
			$discounts[$key]->emails          = $this->_loadEmails($discounts[$key]);

			$return[$row->id] = $discounts[$key];
		}

		return $alwaysReturnArray || count($return) > 1 ? $return : reset($return);
	}

	protected function _loadThresholds($discount)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id,
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

		$return = array();

		foreach ($result as $key => $data) {
			$return[$data->currencyID] = (float) $data->threshold;
		}

		return $return;
	}

	protected function _loadDiscountAmounts($discount)
	{
		$result = $this->_query->run(
			'SELECT
				discount_id,
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

		$return = array();

		foreach ($result as $key => $data) {
			$return[$data->currencyID] = (float) $data->amount;
		}

		return $return;
	}

	protected function _loadEmails(Discount $discount)
	{
		$result = $this->_query->run("
			SELECT
				email
			FROM
				discount_email
			WHERE
				discount_id = :id?i
		", [
			'id' => $discount->id,
		]);

		return $result->flatten();
	}
}
