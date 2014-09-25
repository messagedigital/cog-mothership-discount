<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Order;
use Message\Mothership\Commerce\Product\Collection as ProductCollection;

class Discount
{
	public $id;
	public $authorship;
	public $code;
	public $name;
	public $description;
	public $emails;

	public $start;
	public $end;

	public $freeShipping;

	public $percentage;
	public $thresholds = array();
	public $discountAmounts = array();

	private $_products;

	public function __construct()
	{
		$this->authorship = new Authorship;
		$this->_products = new ProductCollection;
	}

	public function addProduct(Product $product)
	{
		$this->getProducts()->add($product);

		return $this;
	}

	public function removeProduct(Product $product)
	{
		$products = $this->getProducts();
		$products->remove($product);

		return $this;
	}

	public function addThreshold($currencyID, $amount)
	{
		$this->thresholds[$currencyID] = $amount;

		return $this;
	}

	public function addDiscountAmount($currencyID, $amount)
	{
		$this->discountAmounts[$currencyID] = $amount;

		return $this;
	}

	public function getThresholdForCurrencyID($currencyID)
	{
		return array_key_exists($currencyID, $this->thresholds) ? $this->thresholds[$currencyID] : null;
	}

	public function getDiscountAmountForCurrencyID($currencyID)
	{
		return array_key_exists($currencyID, $this->discountAmounts) ? $this->discountAmounts[$currencyID] : null;
	}

	public function isActive()
	{
		$curTime = new \DateTime;
		return (!$this->start || $this->start < $curTime) && (!$this->end || $this->end > $curTime);
	}

	public function appliesToOrder()
	{
		return $this->getProducts()->count() !== 0;
	}

    /**
     * Gets the value of products.
     *
     * @return mixed
     */
    public function getProducts()
    {
        return $this->_products;
    }
    
    /**
     * Sets the value of products.
     *
     * @param mixed $products the products 
     *
     * @return self
     */
    protected function setProducts($products)
    {
        $this->_products = $products;

        return $this;
    }
}