<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;
use Message\Mothership\Commerce\Product\Product;

class Discount
{
	public $id;
	public $authorship;
	public $code;
	public $name;
	public $description;

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
		$appliesToOrder = true;
	}

	public function addThreshold(Threshold $threshold)
	{
		$threshold->discount = $this;
		$this->thresholds[$threshold->currencyID] = $threshold;
	}

	public function addDiscountAmount(DiscountAmount $amount)
	{
		$amount->discount = $this;
		$this->discountAmounts[$amount->currencyID] = $amount;
	}

	public function getThresholdForCurrencyID($currencyID)
	{
		return array_key_exists($currencyID, $this->thresholds) ? $this->thresholds[$currencyID]->threshold : null;
	}

	public function getDiscountAmountForCurrencyID($currencyID)
	{
		return array_key_exists($currencyID, $this->discountAmounts) ? $this->discountAmounts[$currencyID]->amount : null;
	}

	public function isActive()
	{
		$curTime = new \DateTime;
		return (!$this->start || $this->start < $curTime) && (!$this->end || $this->end > $curTime);
	}
}