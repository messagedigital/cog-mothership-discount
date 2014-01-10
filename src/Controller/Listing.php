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

		return $this->render('::listing', array(
			'discounts' => $discounts,
		));
	}

	public function active($fromTimestamp = null, $toTimestamp = null)
	{
		$from = ($fromTimestamp !== null ? new \DateTime(date('c', $fromTimestamp)) : new \DateTime);
		$to = ($toTimestamp !== null ? new \DateTime(date('c', $toTimestamp)) : new \DateTime);
		
		$discounts = $this->get('discount.loader')->getByDateRange($from, $to);

		return $this->render('::listing', array(
			'discounts' => $discounts,
			'title' 	=> 'Active Discounts',
		));
	}

	public function inactive()
	{
		$discounts = $this->get('discount.loader')->getInactive();

		return $this->render('::listing', array(
			'discounts' => $discounts,
			'title' 	=> 'Inactive Discounts',
		));
	}
}
