<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Commerce\Order;

class OrderDiscountFactory
{
	use Helpers\ItemCounterTrait {
		getCounts as private _getCounts();
	}

	private $_validator;

	public function __construct(Validator $validator)
	{
		$this->_validator = $validator;
	}

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

		$discount = $total - $bundle->getPrice($order->currencyID);

		if ($discount < 0) {
			$discount = 0;
		}

		return $discount;
	}
}