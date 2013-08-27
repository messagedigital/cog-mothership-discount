<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Mothership\Discount\Discount;

class Listing extends Controller
{

	public function all()
	{
		$discounts = $this->get('discount.loader')->getAll();

		return $this->render('::discount:overview', array(
			'discounts' => $discounts,
		));
	}
}
