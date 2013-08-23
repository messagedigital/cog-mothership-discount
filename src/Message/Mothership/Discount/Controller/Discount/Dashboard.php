<?php

namespace Message\Mothership\Discount\Controller\Discount;

use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;

class Dashboard extends Controller
{

	public function index()
	{
		return $this->render('::discount:dashboard');
	}
}
