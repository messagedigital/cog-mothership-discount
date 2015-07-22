<?php

namespace Message\Mothership\Discount\Bundle;

/**
 * Class ProductRow
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class containing data for a product requirement on a bundle
 */
class ProductRow
{
	/**
	 * @var int
	 */
	private $_id;

	/**
	 * @var int
	 */
	private $_productID;

	/**
	 * @var array
	 */
	private $_options;

	/**
	 * @var int
	 */
	private $_quantity;

	/**
	 * @param $productID
	 * @param array $options
	 * @param $quantity
	 */
	public function __construct($productID, array $options, $quantity)
	{
		$this->_validateWholeNumber($productID);
		$this->_validateWholeNumber($quantity);

		ksort($options);

		$this->_productID = (int) $productID;
		$this->_options   = $options;
		$this->_quantity  = (int) $quantity;
	}

	/**
	 * @param $id
	 */
	public function setID($id)
	{
		$this->_id = $id;
	}

	/**
	 * @return int
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @return int
	 */
	public function getProductID()
	{
		return $this->_productID;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * @return int
	 */
	public function getQuantity()
	{
		return $this->_quantity;
	}

	/**
	 * @param int $quantity
	 */
	public function increaseQuantity($quantity)
	{
		$this->_validateWholeNumber($quantity);

		$this->_quantity += $quantity;
	}

	/**
	 * Ensure that the submitted value is a whole number
	 *
	 * @param $value
	 */
	private function _validateWholeNumber($value)
	{
		if (!is_numeric($value) || (int) $value != $value) {
			throw new \InvalidArgumentException('Value must be a whole number');
		}
	}
}