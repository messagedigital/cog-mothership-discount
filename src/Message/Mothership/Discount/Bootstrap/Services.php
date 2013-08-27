<?php

namespace Message\Mothership\Discount\Bootstrap;

use Message\Mothership\Discount;
use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($services)
	{

		$services['discount.loader'] = function($c) {
			return new Discount\Discount\Loader($c['db.query'], $c['product.loader']);
		};

		$services['discount.create'] = function($c) {
			return new Discount\Discount\Create($c['db.query'], $c['user.current']);
		};
	}
}