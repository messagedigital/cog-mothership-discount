<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Commerce\Product\Product;

class ProductRow
{
	private $_productID;
	private $_options;
	private $_quantity;

	public function __construct($productID, array $options, $quantity)
	{
		$this->_validateWholeNumber($productID);
		$this->_validateWholeNumber($quantity);

		ksort($options);

		$this->_productID = (int) $productID;
		$this->_options   = $options;
		$this->_quantity  = (int) $quantity;
	}

	public function getProductID()
	{
		return $this->_productID;
	}

	public function getOptions()
	{
		return $this->_options;
	}

	public function getQuantity()
	{
		return $this->_quantity;
	}

	public function increaseQuantity($quantity)
	{
		$this->_validateWholeNumber($quantity);

		$this->_quantity += $quantity;
	}

	private function _validateWholeNumber($value)
	{
		if (!is_numeric($value) || (int) $value != $value) {
			throw new \InvalidArgumentException('Value must be a whole number');
		}
	}
}