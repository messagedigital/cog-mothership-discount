<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Commerce\Order;

/**
 * Class OrderDiscountFactory
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for creating a discount order entity from a bundle
 */
class OrderDiscountFactory
{
	use Helpers\ItemCounterTrait {
		getCounts as private _getCounts;
	}

	/**
	 * @var Validator
	 */
	private $_validator;

	/**
	 * @param Validator $validator
	 */
	public function __construct(Validator $validator)
	{
		$this->_validator = $validator;
	}

	/**
	 * Create a discount order entity to apply to the
	 *
	 * @param Order\Order $order
	 * @param Bundle $bundle
	 *
	 * @return Order\Entity\Discount\Discount
	 */
	public function createOrderDiscount(Order\Order $order, Bundle $bundle)
	{
		$discount = new Order\Entity\Discount\Discount;
		$discount->order = $order;

		$discount->amount = $this->_calculateAmount($order, $bundle);
		$discount->name = $bundle->getName();
		$discount->description = $bundle->getName() . ' (Bundle ' . $bundle->getID() . ')';
		$discount->order = $order;

		return $discount;
	}

	/**
	 * Calculate the accumulated value of all items in the bundle, and return the difference between that and
	 * the value of the bundle
	 *
	 * @param Order\Order $order
	 * @param Bundle $bundle
	 *
	 * @return int
	 */
	private function _calculateAmount(Order\Order $order, Bundle $bundle)
	{
		$total = 0;

		list($expectedCounts, $currentCounts) = $this->_getCounts($bundle);

		foreach ($order->items as $item) {
			foreach ($bundle->getProductRows() as $row) {

				// Do not increment current counts beyond the expected count
				if ($currentCounts[$row->getID()] >= $expectedCounts[$row->getID()]) {
					continue;
				}

				// If the item fits the requirements of the product row, increment the current count
				if ($this->_validator->itemIsApplicable($item, $row)) {

					$currentCounts[$row->getID()]++;

					$total += $item->getUnit()->getPrice('retail', $order->currencyID);;

					break;
				}
			}
		}

		foreach ($expectedCounts as $key => $value) {
			if ($currentCounts[$key] != $value) {
				throw new \LogicException('Number of items does not match that of bundle, so should have failed validation');
			}
		}

		$discount = ($total - $bundle->getPrice($order->currencyID));

		if ($discount < 0) {
			$discount = 0;
		}

		return $discount;
	}
}