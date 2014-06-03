<?php

namespace Message\Mothership\Discount;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;
use Message\Mothership\Commerce\Order\Events as OrderEvents;
use Message\Mothership\Commerce\Order\Event\Event as OrderEvent;
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
			OrderEvents::CREATE_COMPLETE => array(
				array('recordDiscountRevenue'),
			),
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

	/**
	 * Record the additional discount statistics if the order was discounted.
	 *
	 * @param  Event\Event $event
	 */
	public function recordDiscountRevenue(OrderEvent $event)
	{
		$order = $event->getOrder();

		if ($order->totalDiscount > 0) {
			$this->get('statistics')->get('discounted.sales.gross')
				->counter->increment($order->totalGross);

			$this->get('statistics')->get('discount.gross')
				->counter->increment($order->totalDiscount);
		}
	}
}