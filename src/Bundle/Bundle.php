<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Commerce\Product;
use Message\Mothership\FileManager\File\File;
use Message\Cog\ValueObject\Authorship;

/**
 * Class Bundle
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 */
class Bundle
{
	/**
	 * @var int
	 */
	private $_id;

	/**
	 * @var string
	 */
	private $_name;

	/**
	 * @var Authorship
	 */
	private $_authorship;

	/**
	 * @var string
	 */
	private $_defaultCurrency;

	/**
	 * @var \DateTime
	 */
	private $_start;

	/**
	 * @var \DateTime
	 */
	private $_end;

	/**
	 * @var bool
	 */
	private $_allowCodes = false;

	/**
	 * @var File
	 */
	private $_image;

	/**
	 * @var array
	 */
	private $_productRows = [];

	/**
	 * @var array
	 */
	private $_prices = [];

	/**
	 * Aet an instance of Authorship (to be edited by calling `getAuthorship()`)
	 */
	public function __construct($defaultCurrency)
	{
		$this->_defaultCurrency = $defaultCurrency;
		$this->_authorship = new Authorship;
	}

	/**
	 * Set the ID of the bundle upon load
	 *
	 * @param int | string $id             ID loaded from the database
	 * @throws \InvalidArgumentException   Throws exception if ID is not a whole number
	 */
	public function setID($id)
	{
		if (!is_numeric($id) || (int) $id != $id) {
			throw new \InvalidArgumentException('ID must be a whole number');
		}

		$this->_id = (int) $id;
	}

	/**
	 * Get the ID of the bundle
	 *
	 * @return int
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * Set the name of the bundle
	 *
	 * @param $name
	 */
	public function setName($name)
	{
		if (!is_string($name)) {
			throw new \InvalidArgumentException('Bundle name must be a string, ' . gettype($name) . ' given');
		}

		$this->_name = $name;
	}

	/**
	 * Get the name of the bundle
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Gain access to the Authorship object
	 *
	 * @return Authorship
	 */
	public function getAuthorship()
	{
		return $this->_authorship;
	}

	/**
	 * Set the image to be displayed to represent the bundle
	 *
	 * @param File $image
	 */
	public function setImage(File $image)
	{
		$this->_image = $image;
	}

	/**
	 * Get the image representing the bundle
	 *
	 * @return File
	 */
	public function getImage()
	{
		return $this->_image;
	}

	public function removeImage()
	{
		$this->_image = null;
	}

	/**
	 * Add a product to the bundle.
	 * This information is grouped together in a ProductRow object to keep track of quantities and options
	 *
	 * @param Product\Product $product      The product to assign to the bundle
	 * @param array $options                The options for the product
	 * @param int $quantity                 The number of items required for this bundle to be valid
	 */
	public function addProduct(Product\Product $product, array $options = [], $quantity = 1)
	{
		$this->addProductRow(new ProductRow($product->id, $options, $quantity));
	}

	/**
	 * Add a row of product information to the bundle, determining a requirement for the bundle to be deemed as valid
	 *
	 * @param ProductRow $row
	 */
	public function addProductRow(ProductRow $row)
	{
		$key = md5(serialize([$row->getProductID(), $row->getOptions()]));

		if (array_key_exists($key, $this->_productRows)) {
			$this->_productRows[$key]->increaseQuantity($row->getQuantity());
		} else {
			$this->_productRows[$key] = $row;
		}
	}

	/**
	 * Remove all products from the bundle
	 */
	public function clearProducts()
	{
		$this->_productRows = [];
	}

	/**
	 * Get all product rows from the bundle
	 *
	 * @return array
	 */
	public function getProductRows()
	{
		return $this->_productRows;
	}

	/**
	 * Set the price for a specific currency
	 *
	 * @param int | float | string $price      The price to set
	 * @param string $currencyID               The currency ID
	 * @throws \InvalidArgumentException       Throws exception if $price is not numeric
	 * @throws \InvalidArgumentException       Throws exception if $currencyID is not a string or is more than three
	 *                                         characters
	 */
	public function setPrice($price, $currencyID)
	{
		if (!is_numeric($price)) {
			throw new \InvalidArgumentException('Price must be numeric');
		}

		if (!is_string($currencyID) || !preg_match('/^[A-Za-z]+$/', $currencyID)) {
			throw new \InvalidArgumentException('Currency ID must be a string of no more than three letters');
		}

		$this->_prices[strtoupper($currencyID)] = round($price, 2);
	}

	/**
	 * Get the price for a specific currency
	 *
	 * @param string $currencyID               The currency ID corresponding to the price
	 * @throws \InvalidArgumentException       Throws exception if $currencyID is not a string or is more than three
	 *                                         characters
	 * @throws \LogicException                 Throws exception if currency is not set in prices array
	 *
	 * @return mixed
	 */
	public function getPrice($currencyID = null)
	{
		$currencyID = $currencyID ?: $this->_defaultCurrency;

		if (!is_string($currencyID) || !preg_match('/^[A-Za-z]+$/', $currencyID)) {
			throw new \InvalidArgumentException('Currency ID must be a string of no more than three letters');
		}

		$currencyID = strtoupper($currencyID);

		if (!array_key_exists($currencyID, $this->_prices)) {
			throw new \LogicException('Currency with ID of `' . $currencyID . '` not set on bundle');
		}

		return $this->_prices[$currencyID];
	}

	/**
	 * @return array
	 */
	public function getPrices()
	{
		return $this->_prices;
	}

	/**
	 * Set whether the bundle can be used in conjunction with discount codes
	 *
	 * @param bool $allowCodes     Set to true to allow bundle to work with discount codes
	 */
	public function setAllowCodes($allowCodes)
	{
		$this->_allowCodes = (bool) $allowCodes;
	}

	/**
	 * Returns true if bundle allows codes. If `setAllowCodes()` has not be run, it will return false by default
	 *
	 * @return bool
	 */
	public function allowCodes()
	{
		return $this->_allowCodes;
	}

	/**
	 * Set the date from which the bundle will be valid
	 *
	 * @param \DateTime $start     DateTime representation of start date
	 * @throws \LogicException     Throws exception if end date has already been set and is before start date
	 */
	public function setStart(\DateTime $start)
	{
		if ($this->_end && $start->getTimestamp() > $this->_end->getTimestamp()) {
			throw new \LogicException('Cannot set start date after end date');
		}

		$this->_start = $start;
	}

	/**
	 * Get the start date of the bundle if set
	 *
	 * @return \DateTime | null
	 */
	public function getStart()
	{
		return $this->_start;
	}

	/**
	 * Get the end date of the bundle if set
	 *
	 * @return \DateTime | null
	 */
	public function getEnd()
	{
		return $this->_end;
	}

	/**
	 * Set the date from which the bundle will no longer be valid
	 *
	 * @param \DateTime $end      DateTime representation of end date
	 * @throws \LogicException    Throws exception if start date has already been set and is after end date
	 */
	public function setEnd(\DateTime $end)
	{
		if ($this->_start && $end->getTimestamp() < $this->_start->getTimestamp()) {
			throw new \LogicException('Cannot set end date before start date');
		}

		$this->_end = $end;
	}

	/**
	 * Check to see if Bundle is valid at this point in time
	 *
	 * @return bool
	 */
	public function inTimeRange()
	{
		$time = time();

		if ($this->_start && $this->_start->getTimestamp() > $time) {
			return false;
		}

		if ($this->_end && $this->_end->getTimestamp() < $time) {
			return false;
		}

		return true;
	}
}