<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Order;

/**
 * Class to create an Order\Entity\Discount\Discount from
 * a given Order\Order and Discount
 */
class OrderDiscountFactory
{
	/**
	 * @var Order\Order the order to create the Order\Entity\Discount\Discount from
	 */
	protected $_order;

	/**
	 * @var Discount the discount to create the Order\Entity\Discount\Discount from
	 */
	protected $_discount;

	public function setOrder(Order\Order $order)
	{
		$this->_order = $order;

		return $this;
	}

	public function setDiscount(Discount $discount)
	{
		$this->_discount = $discount;

		return $this;
	}

	/**
	 * Creates order discount from $this->_order and $this->_discount
	 *
	 * @throws \Exception if $this->_order or $this->_discount are not set
	 */
	public function createOrderDiscount()
	{
		if ($this->_order === null) {
			throw new \Exception('Order must be set to create order discount!');
		}
		if ($this->_discount === null) {
			throw new \Exception('Discount must be set to create order discount!');
		}

		$orderDiscount = new Order\Entity\Discount\Discount;
		$orderDiscount->code 		= $this->_discount->code;
		$orderDiscount->name 		= $this->_discount->name;
		$orderDiscount->description = $this->_discount->description;

		$orderDiscount->percentage 	= $this->_discount->percentage;

		$orderDiscount->order 		= $this->_order;

		// add discountAmount if it has the right locale and currencyID for the order
		foreach ($this->_discount->discountAmounts as $discountAmount) {
			if ($discountAmount->locale === $this->_order->locale && $discountAmount->currencyID === $this->_order->currencyID) {
				$orderDiscount->amount = $discountAmount->amount;
			}
		}

		if ($this->_discount->appliesToOrder) {
			$orderDiscount->items = $this->_order->items;
		} else {
			foreach ($this->_order->items->all() as $item) {
				foreach ($this->_discount->products as $product) {
					if ($item->productID === $product->id) {
						$orderDiscount->items->append($item);
						continue;
					}
				}
			}
		}

		return $orderDiscount;
	}
}