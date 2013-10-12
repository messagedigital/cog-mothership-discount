<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Order\Order;

/**
 * Validator to check whether a discount (identified by it's code)
 * can be applied to a given order.
 */
class Validator
{
	/**
	 * The order-object of the order-discount to validate
	 */
	protected $_order;
	protected $_discountLoader;
	protected $_orderDiscountFactory;


	public function __construct(Loader $discountLoader, OrderDiscountFactory $orderDiscountFactory)
	{
		$this->_discountLoader = $discountLoader;
		$this->_orderDiscountFactory = $orderDiscountFactory;
	}

	public function setOrder(Order $order)
	{
		$this->_order = $order;
		$this->_orderDiscountFactory->setOrder($order);

		return $this;
	}

	public function getOrder()
	{
		return $this->_order;
	}

	/**
	 * Validates a discount by it's discountCode and an order
	 * and returns an order-discount-object.
	 * Checks validity of the code, whether it is active and
	 * (if the discount applies to specific products) whether
	 * the at least one of the items in the order matches one
	 * of the discount's products.
	 *
	 * @param string $discountCode The code of the discount validated
	 *
	 * @throws OrderValidityException if the validation failed
	 *
	 * @return Order\Entity\Discount\Discount the order-discount-object for the given discountCode
	 */
	public function validate($discountCode)
	{
		if (null === $this->_order) {
			throw new \Exception('Order must be set before discount code can be validated');
		}

		if(0 === $this->_order->items->count()) {
			throw new OrderValidityException('Your basket is empty');
		}

		$discount = $this->_discountLoader->getByCode($discountCode);
		if (!$discount) {
			throw new OrderValidityException('The entered code was not recognised.');
		}

		if (!$discount->isActive()) {
			$message = ($discount->start < new \DateTime ? 'The discount has expired.' : 'The discount is not active yet.');
			throw new OrderValidityException($message);
		}

		// check whether discount-threshold is reached
		if (0 !== count($discount->thresholds)) {
			foreach ($discount->thresholds as $threshold) {
				if ($this->_order->locale === $threshold->locale && $this->_order->currencyID === $threshold->currencyID) {
					if ($this->_order->productGross < $threshold->threshold) {
						throw new OrderValidityException('Your order value is less than the discount threshold.');
					}
				}
			}
		}

		// check whether order has at least one of the products the discount applies to
		if (!$discount->appliesToOrder) {
			$appliesToItem = false;

			foreach ($discount->products as $product) {
				foreach ($this->_order->items->all() as $item) {
					if ($item->productID === $product->id) {
						$appliesToItem = true;
						break;
					}
				}
				if ($appliesToItem) {
					break;
				}
			}

			if (!$appliesToItem) {
				throw new OrderValidityException('Your order does not include any of the products the discount applies to.');
			}
		}

		$this->_orderDiscountFactory
			->setOrder($this->_order)
			->setDiscount($discount);

		return $this->_orderDiscountFactory->createOrderDiscount();
	}
}