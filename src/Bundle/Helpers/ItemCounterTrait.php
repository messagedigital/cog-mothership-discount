<?php

namespace Message\Mothership\Discount\Bundle\Helpers;

use Message\Mothership\Discount\Bundle;

/**
 * Class ItemCounterTrait
 * @package Message\Mothership\Discount\Bundle\Helpers
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Trait to handle methods for counting bundles
 */
trait ItemCounterTrait
{
	/**
	 * Method for returning two arrays with matching keys.
	 *
	 * The first array is the expected counts array - a set of key value pairs of product row IDs and their quantity
	 * value.
	 *
	 * The second array is the current counts array - a list of key value pairs of product row IDs and zeros, intended
	 * to be incremented as the items in an order are counted against a bundle.
	 *
	 * @param Bundle\Bundle $bundle
	 *
	 * @return array         Returns array with the expected counts array as the first value and the current counts
	 *                       array as the second value
	 */
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