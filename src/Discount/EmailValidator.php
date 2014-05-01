<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\DB\Query;
use Message\Mothership\Commerce\Order\Order;

class EmailValidator
{
	/**
	 * @var \Message\Cog\DB\Query
	 */
	protected $_query;

	/**
	 * @var Order
	 */
	protected $_order;

	public function __construct(Query $query)
	{
		$this->_query = $query;
	}

	public function setOrder(Order $order)
	{
		$this->_order = $order;

		return $this;
	}

	public function validate(Discount $discount)
	{
		$result = $this->_query->run("
			SELECT
				discount_id
			FROM
				discount_email
			WHERE
				used_at != NULL
			AND
				discount_id = :id?i
			AND
				email = :email?s
		", [
			'id'    => $discount->id,
			'email' => $this->_order->userEmail,
		]);

		if ($result->count()) {
			throw new OrderValidityException('This code has already been used by this email address.');
		}

		return true;
	}
}