<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Commerce\Order;

class Validator
{
	public function isValid(Bundle $bundle, Order\Order $order)
	{
		list($expectedCounts, $currentCounts) = $this->_getCounts($bundle);

		foreach ($order->items as $item) {
			foreach ($bundle->getProductRows() as $row) {
				if ($currentCounts[$row->getID()] >= $expectedCounts[$row->getID()]) {
					continue;
				}

				if ($this->_isApplicable($item, $row)) {
					$expectedCounts[$row->getID()]++;
					break;
				}
			}
		}

		foreach ($expectedCounts as $key => $value) {
			if ($currentCounts[$key] != $value) {
				return false;
			}
		}

		return true;
	}

	private function _isApplicable(Order\Entity\Item\Item $item, ProductRow $row)
	{
		if ((int) $item->getProduct()->id !== $row->getID()) {
			return false;
		}

		if (count($row->getOptions()) <= 0) {
			return true;
		}

		$unit  = $item->getUnit();

		foreach ($row->getOptions() as $name => $value) {
			if (!($unit->hasOption($name) && $unit->getOption($name) === $value)) {
				return false;
			}
		}

		return true;
	}

	private function _getCounts(Bundle $bundle)
	{
		$expectedCounts = [];
		$currentCounts = [];

		foreach ($bundle->getProductRows() as $row) {
			$expectedCounts[$row->getID()] = $row->getQuantity();
			$currentCounts[$row->getID()]  = 0;
		}

		return [$expectedCounts, $currentCounts];
	}
}