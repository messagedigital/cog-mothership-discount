<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\ValueObject\DateTimeImmutable;

use Message\Cog\DB;
use Message\Cog\DB\Result;
use Message\User\UserInterface;

/**
 * Class for updating the attributes of a given Discount object to the DB
 */
class Edit implements DB\TransactionalInterface
{
	protected $_query;
	protected $_currentUser;

	public function __construct(DB\Transaction $query, UserInterface $currentUser)
	{
		$this->_query  		= $query;
		$this->_currentUser	= $currentUser;
	}

	public function setTransaction(DB\Transaction $transaction)
	{
		$this->_query = $transaction;
	}

	/**
	 * Handles updating the discount object and properties
	 *
	 * @param  Discount $discount Updated Discount object to save
	 *
	 * @return Discount          Saved Discount object
	 */
	public function save(Discount $discount)
	{
		$discount->authorship->update(
			new DateTimeImmutable,
			$this->_currentUser->id
		);

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
				free_shipping = :freeShipping?b,
				updated_by	  = :updatedBy?i,
				updated_at	  = :updatedAt?d
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
				'updatedBy'		=> $discount->authorship->updatedBy(),
				'updatedAt'		=> $discount->authorship->updatedAt(),
			)
		);

		$this->_saveDiscountAmounts($discount);
		$this->_saveThresholds($discount);
		$this->_saveProducts($discount);
		$this->_saveEmails($discount);

		$this->_query->commit();

		return $discount;
	}

	public function markEmailAsUsed(Discount $discount, $email)
	{
		$this->_query->run("
			UPDATE
				discount_email
			SET
				used_at = :usedAt?d
			WHERE
				discount_id = :id?s
			AND
				email = :email?s
		", [
			'usedAt' => new \DateTime(),
			'id'     => $discount->id,
			'email'  => $email,
		]);

		$this->_query->commit();
	}

	protected function _saveEmails(Discount $discount)
	{
		$this->_query->run("
			DELETE FROM
				discount_email
			WHERE
				discount_id = :id?i
			 ". (count($discount->emails) ? "
			 AND
				email NOT IN (:emails?sj)
		" : ""), [
			'id'     => $discount->id,
			'emails' => $discount->emails,
		]);

		foreach ($discount->emails as $email) {
			$this->_query->run("
				INSERT IGNORE INTO
					discount_email
					(
						discount_id,
						email
					)
				VALUES
					(
						:id?i,
						:email?s
					)
			", [
				'id' => $discount->id,
				'email' => $email,
			]);
		}

		return $discount;
	}

	/**
	 * Clears discount-project-table for $discount and
	 * adds all $discount->products.
	 *
	 * @param Discount $discount The discount holding the products to save
	 */
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

		if ($discount->getProducts()->count() !== 0) {
			$options = array();
			$inserts = array();
			foreach ($discount->getProducts() as $product) {
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



	/**
	 * Clears discount-threshold-table for $discount and
	 * adds all $discount->thresholds.
	 *
	 * @param Discount $discount The discount holding the thresholds to save
	 */
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

		if (0 !== count($discount->thresholds)) {
			$options = array();
			$inserts = array();
			foreach ($discount->thresholds as $currencyID => $threshold) {
				$options[] = $discount->id;
				$options[] = $currencyID;
				$options[] = $threshold;
				$inserts[] = '(?i, ?s, ?f)';
			}

			$result = $this->_query->run(
				'INSERT INTO
					discount_threshold
					(
						discount_id,
						currency_id,
						threshold
					)
				VALUES
					'.implode(',',$inserts).' ',
				$options
			);
		}
	}

	/**
	 * Clears discount-amount-table for $discount and
	 * adds all $discount->discountAmounts.
	 *
	 * @param Discount $discount The discount holding the DiscountAmounts to save
	 */
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

		if (count($discount->discountAmounts) !== 0) {
			$options = array();
			$inserts = array();
			foreach ($discount->discountAmounts as $currencyID => $discountAmount) {
				$options[] = $discount->id;
				$options[] = $currencyID;
				$options[] = $discountAmount;
				$inserts[] = '(?i, ?s, ?f)';
			}

			$result = $this->_query->run(
				'INSERT INTO
					discount_amount
					(
						discount_id,
						currency_id,
						amount
					)
				VALUES
					'.implode(',', $inserts).' ',
				$options
			);
		}
	}

}
