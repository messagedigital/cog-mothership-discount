<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Discount\Bundle\Events as BundleEvents;

use Message\Mothership\Commerce\Order;
use Message\Mothership\Commerce\Order\Events as OrderEvents;
use Message\Mothership\Commerce\Order\Event\Event as OrderEvent;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;

class EventListener extends BaseListener implements SubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return [
			BundleEvents::ADD_BUNDLE => [
				['validateBundle']
			],
			OrderEvents::ASSEMBLER_UPDATE => [
				['validateBundle']
			],
			OrderEvents::CREATE_VALIDATE => [
				['validateBundle']
			],
		];
	}

	public function validateBundle(OrderEvent $event)
	{
		$bundleIDs = $this->_getBundleIDs($event->getOrder());

		if (count($bundleIDs) <= 0) {
			$this->_removeBundleDiscounts($event->getOrder());

			return false;
		}

		$bundles = $this->get('discount.bundle_loader')->getByID($bundleIDs);

		foreach ($bundles as $bundle) {
			if (false === $this->get('discount.bundle_validator')->isValid($bundle, $event->getOrder())) {
				$this->_removeDiscount($bundle, $event->getOrder());
			} else {
				$discountFactory = $this->get('discount.bundle.order_discount_factory');
				$discountFactory->setBundle($bundle);
				$discountFactory->setOrder($event->getOrder());
				$discountFactory->createDiscount();
			}
		}
	}

	private function _getBundleIDs(Order\Order $order)
	{
		$bundleIDs = [];

		foreach ($order->metadata->all() as $name => $value) {
			if (preg_match('/^bundle_[0-9]+$', $name)) {
				$bundleIDs[] = (int) $value;
			}
		}

		return $bundleIDs;
	}
}