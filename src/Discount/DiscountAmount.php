<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;

class DiscountAmount
{
	public $discount;
	public $locale;
	public $currencyID;
	public $amount;	
}