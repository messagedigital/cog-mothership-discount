<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Order;

class Discount
{
	public $id;
	public $authorship;
	public $code;
	public $name;
	public $description;
	public $emailTo;

	public $start;
	public $end;

	public $freeShipping;

	public $percentage;
	public $thresholds = array();
	public $discountAmounts = array();

	public $appliesToOrder = true;
	public $products = array();

	public function __construct()
	{
		$this->authorship = new Authorship;
	}

	public function addProduct(Product $product)
	{
		$this->products[$product->id] = $product;
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
}