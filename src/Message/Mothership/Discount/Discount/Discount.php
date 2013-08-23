<?php

namespace Message\Mothership\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;

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

	public $appliesToOrder;
	public $products = array();

	public function __construct()
	{
		$this->authorship = new Authorship;
	}

	public function addProduct(Product $product)
	{
		$this->products[] = $product;
	}

	public function addThreshold(DiscountThreshold $threshold)
	{
		$this->thresholds[] = $threshold;
	}

	public function addDiscountAmount(DiscountAmount $amount)
	{
		$this->discountAmounts[] = $amount;
	}
}