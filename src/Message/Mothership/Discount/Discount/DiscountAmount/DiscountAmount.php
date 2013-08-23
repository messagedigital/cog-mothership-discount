<?php

namespace Message\Mothership\Discount\Discount\DiscountAmount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;

class DiscountAmount
{
	public $discount;
	public $locale;
	public $currency_id;
	public $amount;	
}