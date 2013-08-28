<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\DB;
use Message\Cog\DB\Result;

/**
 * Class for updating the attributes of a given Product object to the DB
 */
class Edit implements DB\TransactionalInterface
{
	protected $_query;

	public function __construct(DB\Transaction $query)
	{
		$this->_query  = $query;
	}

	public function setTransaction(DB\Transaction $transaction)
	{
		$this->_query = $transaction;
	}

	/**
	 * Handles the bulk updating of most of the product properties
	 *
	 * @param  Product $product Updated Product object to save
	 *
	 * @return Product          Saved Product object
	 */
	public function save(Discount $discount)
	{
		$result = $this->_query->run(
			'UPDATE
				discount
			 SET
				code 		  = :code?s,
				name		  = :name?s,
				description   = :description?sn,
				start		  = :start?dn,
				end 		  = :end?dn,
				percentage    = :percentage?fn,
				free_shipping = :freeShipping?b
			WHERE
				discount_id = :discountID?i
			', array(
				'code' 			=> $discount->code,
				'name'			=> $discount->name,
				'description'	=> $discount->description,
				'start' 		=> $discount->start,
				'end'			=> $discount->end,
				'percentage' 	=> $discount->percentage,
				'freeShipping' 	=> $discount->freeShipping,
				'discountID' 	=> $discount->id,
			)
		);

		$this->_saveDiscountAmounts($discount);
		$this->_saveThresholds($discount);
		$this->_saveProducts($discount);

		$this->_query->commit();

		return $discount;
	}

	protected function _saveProducts(Discount $discount)
	{
		$this->_query->run(
			'DELETE FROM
				discount_product
			WHERE
				discount_id = ?i',
			array(
				$discount->id
			)
		);

		if(count($discount->products) !== 0) {
			$options = array();
			$inserts = array();
			foreach ($discount->products as $product) {
				$options[] = $discount->id;
				$options[] = $product->id;
				$inserts[] = '(?i,?i)';
			}

			$result = $this->_query->run(
				'INSERT INTO
					discount_product
					(
						discount_id,
						product_id
					)
				VALUES
					'.implode(',',$inserts).' ',
				$options
			);
		}
	}

	protected function _saveThresholds(Discount $discount)
	{
		$this->_query->run(
			'DELETE FROM
				discount_threshold
			WHERE
				discount_id = ?i',
			array(
				$discount->id
			)
		);

		if(count($discount->thresholds) !== 0) {
			$options = array();
			$inserts = array();
			foreach ($discount->thresholds as $threshold) {
				$options[] = $discount->id;
				$options[] = $threshold->currencyID;
				$options[] = $threshold->locale;
				$options[] = $threshold->threshold;
				$inserts[] = '(?i, ?s, ?s, ?f)';
			}

			$result = $this->_query->run(
				'INSERT INTO
					discount_threshold
					(
						discount_id,
						currency_id,
						locale,
						threshold
					)
				VALUES
					'.implode(',',$inserts).' ',
				$options
			);
		}
	}

	protected function _saveDiscountAmounts(Discount $discount)
	{
		$this->_query->run(
			'DELETE FROM
				discount_amount
			WHERE
				discount_id = ?i',
			array(
				$discount->id
			)
		);

		if(count($discount->discountAmounts) !== 0) {
			$options = array();
			$inserts = array();
			foreach ($discount->discountAmounts as $discountAmount) {
				$options[] = $discount->id;
				$options[] = $discountAmount->currencyID;
				$options[] = $discountAmount->locale;
				$options[] = $discountAmount->amount;
				$inserts[] = '(?i, ?s, ?s, ?f)';
			}

			$result = $this->_query->run(
				'INSERT INTO
					discount_amount
					(
						discount_id,
						currency_id,
						locale,
						amount
					)
				VALUES
					'.implode(',', $inserts).' ',
				$options
			);
		}
	}

}
