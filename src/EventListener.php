<?php

namespace Message\Mothership\Discount;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;

use Message\Mothership\ControlPanel\Event\Dashboard\DashboardEvent;

/**
 * Event listener for core Mothership Discount functionality.
 *
 * @author Laurence Roberts <laurence@message.co.uk>
 */
class EventListener extends BaseListener implements SubscriberInterface
{
	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			DashboardEvent::DASHBOARD_INDEX => array(
				'buildDashboardIndex'
			),
			'dashboard.commerce.discounts' => array(
				'buildDashboardDiscounts',
			),
		);
	}

	/**
	 * Add controller references to the dashboard index.
	 *
	 * @param  DashboardEvent $event
	 */
	public function buildDashboardIndex(DashboardEvent $event)
	{
		$event->addReference('Message:Mothership:Discount::Controller:Module:Dashboard:DiscountRevenue#index');
	}

	/**
	 * Add controller references to the discounts dashboard.
	 *
	 * @param  DashboardEvent $event
	 */
	public function buildDashboardDiscounts(DashboardEvent $event)
	{
		$event->addReference('Message:Mothership:Discount::Controller:Module:Dashboard:DiscountRevenue#index');
	}
}