<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Mothership\ControlPanel\Event\Dashboard\DashboardEvent;

class Dashboard extends Controller
{
	public function index()
	{
		$event = $this->get('event.dispatcher')->dispatch(
			'dashboard.commerce.discounts',
			new DashboardEvent
		);

		return $this->render('::dashboard', [
			'dashboardReferences' => $event->getReferences()
		]);
	}
}
