<?php

namespace Message\Mothership\Discount\Bundle\Helpers;

use Message\Mothership\Commerce\Order\Entity\Item\Item;
use Message\Mothership\Discount\Bundle;

trait ItemCounterTrait
{
	function getCounts(Bundle\Bundle $bundle)
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