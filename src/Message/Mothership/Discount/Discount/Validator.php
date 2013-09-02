<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Order;

class DiscountValidator
{
	protected $_order = null;
	protected $_discountLoader;


	public function __construct(Loader $discountLoader)
	{
		$this->_discountLoader = $discountLoader;
	}

	public setOrder(Order $order)
	{
		$this->_order = $order;

		return $this;
	}

	public getOrder()
	{
		return $this->_order;
	}

	public function validate($discountCode)
	{
		if(null === $this->_order) {
			throw new \Exception("Order must be set before discount code can be validated");
		}

		$discount = $this->_discountLoader->getByCode($discountCode);
		if(!$discount) {
			throw new OrderValidityException("The entered code was not recognised.");
		}

		if(!$discount->isActive()) {
			$message = ($discount->start < new \DateTime ? "This discount has expired." : "This discount is not active yet.");
			throw new OrderValidityException($message);
		}

		// check whether discount-threshold is reached
		if(0 !== count($discount->thresholds)) {
			foreach($discount->thresholds as $threshold) {
				if($order->locale === $threshold->locale && $order->currencyID === $threshold->currencyID) {
					if($order->productTotal < $threshold->threshold) {
						throw new OrderValidityException("Your order value is less than the discount threshold.");
					}
				}
			}
		}

		// check whether order has at least one of the products the discount applies to
		$appliesToItem = false;
		if(!$discount->appliesToOrder) {
			foreach($discount->products as $product) {
				foreach($order->items->all() as $item) {
					if($item->productID === $productID) {
						$appliesToItem = true;
						break;
					}
				}
				if($appliesToItem) {
					break;
				}
			}
		}

		if(!$appliesToItem) {
			throw new OrderValidityException("Your order does not include any of the products this discount applies to.");
		}

		$discountOrder = $discount->;
	}
}