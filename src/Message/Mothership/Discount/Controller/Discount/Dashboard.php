<?php

namespace Message\Mothership\Discount\Controller\Discount;

use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;

class Dashboard extends Controller
{

	public function index()
	{
		$product = $this->get('product.loader')->getByID(1);

		$discount = $this->get('discount.loader')->getByCode('FREE2013');
		de($discount);
		return $this->render('::discount:dashboard');
	}
}
