<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Commerce\Order;
use Message\Cog\Localisation\Translator;

class Validator
{
	use Helpers\ItemCounterTrait {
		getCounts as private _getCounts();
	}

	private $_translator;

	private $_alreadyInBundle = [];

	public function __construct(Translator $translator)
	{
		$this->_translator = $translator;
	}

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

	public function itemIsApplicable(Order\Entity\Item\Item $item, ProductRow $row)
	{
		if (in_array($item->id, $this->_alreadyInBundle, true)) {
			return false;
		}

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

	private function _error($message, $params = [])
	{
		throw new Exception\BundleValidationException(
			$this->_translator->trans($message, $params)
		);
	}
}