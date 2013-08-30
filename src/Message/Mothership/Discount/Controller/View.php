<?php

namespace Message\Mothership\Discount\Controller;

use Message\Mothership\Discount\Discount;
use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;

class View extends Controller
{
	public function orders($discountID)
	{
		$discount = $this->get('discount.loader')->getByID($discountID);
		$orderDiscounts = $this->get('order.discount.loader')->getByCode($discount->code);
		
		return $this->render('::discount:view-orders', array(
			'discount' 			=> $discount,
			'orderDiscounts' 	=> $orderDiscounts,
		));
	}
}
