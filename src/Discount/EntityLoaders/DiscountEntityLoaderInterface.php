<?php

namespace Message\Mothership\Discount\Discount\EntityLoaders;

use Message\Mothership\Discount\Discount\Discount;
use Message\Cog\DB\Entity\EntityLoaderInterface;

interface DiscountEntityLoaderInterface extends EntityLoaderInterface
{
	public function getByDiscount(Discount $discount);
}