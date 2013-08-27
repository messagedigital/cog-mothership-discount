<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Cog\DB;

use Message\User\UserInterface;

class Create
{
	protected $_query;
	protected $_locale;
	protected $_currentUser;

	public function __construct(DB\Query $query, UserInterface $currentUser)
	{
		$this->_query		= $query;
		$this->_currentUser	= $currentUser;
	}

	public function create(Discount $discount)
	{
		if (!$discount->authorship->createdAt()) {
			$discount->authorship->create(
				new DateTimeImmutable,
				$this->_currentUser->id
			);
		}

		$result = $this->_query->run(
			'INSERT INTO
				discount
			SET
				code          = ?s,
				created_at 	  = ?d,
				created_by    = ?i,
				name 		  = ?s,
				description   = ?s,
				start   	  = ?dn,
				end   		  = ?dn,
				percentage    = ?in,
				free_shipping = ?b',
			array(
				$discount->code,
				$discount->authorship->createdAt(),
				$discount->authorship->createdBy(),
				$discount->name,
				$discount->description,
				$discount->start,
				$discount->end,
				$discount->percentage,
				$discount->freeShipping
			)
		);

		$discount->id = $result->id();

		foreach($discount->thresholds as $threshold) {
			$this->_query->run(
				'INSERT INTO
					discount_threshold
				SET
					discount_id = ?i,
					currency_id = ?s,
					locale    	= ?s,
					threshold 	= ?f',
				array(
					$discount->id,
					$threshold->currencyID,
					$threshold->locale,
					$threshold->threshold,
				)
			);
		}

		foreach($discount->discountAmounts as $discountAmount) {
			$this->_query->run(
				'INSERT INTO
					discount_amount
				SET
					discount_id = ?i,
					currency_id = ?s,
					locale    	= ?s,
					amount 		= ?f',
				array(
					$discount->id,
					$discountAmount->currencyID,
					$discountAmount->locale,
					$discountAmount->amount,
				)
			);
		}

		foreach($discount->products as $product) {
			$this->_query->run(
				'INSERT INTO
					discount_product
				SET
					discount_id = ?i,
					product_id 	= ?i',
				array(
					$discount->id,
					$product->id,
				)
			);
		}
		
		return $discount;
	}
}
