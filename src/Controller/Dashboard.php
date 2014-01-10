<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Mothership\Discount\Discount;

class Dashboard extends Controller
{

	public function index()
	{
		return $this->render('::dashboard');
	}
}
