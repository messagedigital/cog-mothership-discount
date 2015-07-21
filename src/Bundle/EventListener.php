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
			OrderEvents::CREATE_VALIDATE => [
				['validateBundle']
			],
		];
	}

	public function validateBundle(OrderEvent $event)
	{
		$bundleIDs = $this->_getBundleIDs($event->getOrder());

		if (count($bundleIDs) <= 0) {
			return false;
		}

		$bundles   = $this->get('discount.bundle_loader')->getByID($bundleIDs);
		$validator = $this->get('discount.bundle_validator');

		foreach ($bundleIDs as $metadataKey => $bundleID) {
			$bundle = $bundles[$bundleID];
			$discountFactory = $this->get('discount.bundle.order_discount_factory');
			$discount = $discountFactory->createOrderDiscount($event->getOrder(), $bundle);

			// Temporarily set ID to keep track of bundles that have had their discounts applied
			$discount->id = $metadataKey;

			// Validator will throw an exception if the bundle is not valid for the order. Remove the discount if it
			// has already been set and show a flash message.
			try {
				$validator->validate($bundle, $event->getOrder());
				if (!$this->get('basket.order')->discounts->exists($metadataKey)) {
					$this->get('basket')->addEntity('discounts', $discount);
				}
			} catch (Exception\BundleValidationException $e) {
				if ($this->get('basket.order')->discounts->exists($metadataKey)) {
					$this->get('http.session')->getFlashBag()->add(
						'warning',
						$e->getMessage()
					);
					$this->get('basket')->removeEntity('discounts', $discount);
				}
			}
		}
	}

	private function _getBundleIDs(Order\Order $order)
	{
		$bundleIDs = [];

		foreach ($order->metadata->all() as $name => $value) {
			if (preg_match('/^bundle_[0-9]+$/', $name)) {
				$bundleIDs[$name] = (int) $value;
			}
		}

		return $bundleIDs;
	}
}