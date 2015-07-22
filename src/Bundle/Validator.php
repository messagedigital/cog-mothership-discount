<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Commerce\Order;
use Message\Cog\Localisation\Translator;

/**
 * Class Validator
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 */
class Validator
{
	use Helpers\ItemCounterTrait {
		getCounts as private _getCounts;
	}

	/**
	 * @var Translator
	 */
	private $_translator;

	/**
	 * @var array
	 */
	private $_alreadyInBundle = [];

	/**
	 * @param Translator $translator
	 */
	public function __construct(Translator $translator)
	{
		$this->_translator = $translator;
	}

	/**
	 * Validate a bundle against an order, and trigger an exception if the bundle is not valid.
	 * Bundles will be marked as invalid if:
	 *   - The order has a discount code assigned to it and the bundle is set to not work in conjunction with
	 *     discount codes
	 *   - An order does not have the minimum number of items needed for the bundle
	 *
	 * @param Bundle $bundle
	 * @param Order\Order $order
	 * @throws \LogicException        Throws exception if product row ID not set in count arrays
	 *
	 * @return bool
	 */
	public function validate(Bundle $bundle, Order\Order $order)
	{
		list($expectedCounts, $currentCounts) = $this->_getCounts($bundle);

		if (false === $bundle->allowCodes()) {
			foreach ($order->discounts as $discount) {
				if (null !== $discount->code) {
					$this->_error('ms.discount.bundle.validation.codes', [
						'%name%' => $bundle->getName(),
						'%code%' => $discount->code,
					]);
				}
			}
		}

		foreach ($order->items as $item) {
			foreach ($bundle->getProductRows() as $row) {

				if (!array_key_exists($row->getID(), $expectedCounts) || !array_key_exists($row->getID(), $currentCounts)) {
					throw new \LogicException(
						'Expected counts arrays to have a value with a key of `' . $row->getID() . '` but it doesn\'t'
					);
				}

				// Do not increment current counts beyond the expected count
				if ($currentCounts[$row->getID()] >= $expectedCounts[$row->getID()]) {
					continue;
				}

				// If the item fits the requirements of the product row, increment the current count
				if ($this->itemIsApplicable($item, $row)) {
					$currentCounts[$row->getID()]++;
					$item->id = uniqid();
					$this->_alreadyInBundle[] = $item->id;
					break;
				}
			}
		}

		foreach ($expectedCounts as $key => $value) {
			if ($currentCounts[$key] != $value) {
				$this->_error('ms.discount.bundle.validation.items', ['%name%' => $bundle->getName()]);
			}
		}

		return true;
	}

	/**
	 * Check if an item matches the criteria set by the product row
	 *
	 * @param Order\Entity\Item\Item $item
	 * @param ProductRow $row
	 *
	 * @return bool
	 */
	public function itemIsApplicable(Order\Entity\Item\Item $item, ProductRow $row)
	{
		if (in_array($item->id, $this->_alreadyInBundle, true)) {
			return false;
		}

		if ((int) $item->getProduct()->id !== $row->getProductID()) {
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

	/**
	 * Translate a string and throw an exception with that string as the message.
	 *
	 * @param $message
	 * @param array $params
	 * @throws Exception\BundleValidationException     Will always throw a validation exception
	 */
	private function _error($message, $params = [])
	{
		throw new Exception\BundleValidationException(
			$this->_translator->trans($message, $params)
		);
	}
}