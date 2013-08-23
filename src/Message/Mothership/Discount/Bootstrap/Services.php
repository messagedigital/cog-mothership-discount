<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Mothership\Discount;
use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($services)
	{

		$services['discount.loader'] = function($c) {
			return new Discount\Discount\Loader(
				$c['db.query'],
				$c['product.loader'],
				new Discount\Discount\DiscountAmount\Loader($c['db.query']),
				new Discount\Discount\Threshold\Loader($c['db.query'])			
			);
		};
	}
}