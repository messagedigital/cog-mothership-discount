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

	public $appliesToOrder = true;

	private $_products;

	public function __construct()
	{
		$this->authorship = new Authorship;
		$this->_products = new ProductCollection;
	}

	public function addProduct(Product $product)
	{
		$this->getProducts->add($product);
		$this->appliesToOrder = true;

		return $this;
	}

	public function removeProduct(Product $product)
	{
		if(array_key_exists($product->id, $this->products)) {
			unset($this->products[$product->id]);
		}

		if(0 === count($this->products)){
			$this->appliesToOrder = false;
		}

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
    public function setProducts($products)
    {
        $this->_products = $products;

        return $this;
    }
}