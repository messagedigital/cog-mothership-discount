<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Mothership\Discount\Bundle\Events as BundleEvents;

use Message\Mothership\Commerce\Order;
use Message\Mothership\Commerce\Order\Events as OrderEvents;
use Message\Mothership\Commerce\Order\Event\Event as OrderEvent;

use Message\Mothership\Commerce\Events;
use Message\Mothership\Commerce\Event\CurrencyChangeEvent;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;

/**
 * Class EventListener
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 */
class EventListener extends BaseListener implements SubscriberInterface
{
	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return [
			Events::CURRENCY_CHANGE => [
				['removeBundles', 500],
			],
			BundleEvents::ADD_BUNDLE => [
				['validateBundle', 400],
			],
			OrderEvents::ASSEMBLER_UPDATE => [
				['validateBundle', 400],
			],
			OrderEvents::CREATE_VALIDATE => [
				['validateBundle', 400],
			],
			OrderEvents::CREATE_VALIDATE => [
				['validateBundle', 400],
			],
		];
	}

	/**
	 * Loop through bundles assigned to order and check that they are still valid. Add discount entities to the order
	 * if the bundles are valid and the discount has not already been added to the order, and remove them if they
	 * are invalid and have been set against the order.
	 *
	 * This method uses the metadata key for the bundle (i.e. 'bundle_[number]') as the ID for the discount entity.
	 * The ID is not saved to the database so this only exists on the basket. This makes it easier to keep track of
	 * which bundle is which.
	 *
	 * @param OrderEvent $event
	 *
	 * @return bool | string
	 */
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

			// Validator will throw an exception if the bundle is not valid for the order. Remove the discount if it
			// has already been set and show a flash message.
			try {
				$validator->validate($bundle, $event->getOrder());
				$discount = $discountFactory->createOrderDiscount($event->getOrder(), $bundle);

				// Temporarily set ID to keep track of bundles that have had their discounts applied
				$discount->id = $metadataKey;

				if (!$this->get('basket.order')->discounts->exists($metadataKey)) {
					$this->get('basket')->addEntity('discounts', $discount);
				}
			} catch (Exception\BundleValidationException $e) {
				if ($this->get('basket.order')->discounts->exists($metadataKey)) {
					$this->get('http.session')->getFlashBag()->add(
						'warning',
						$e->getMessage()
					);
					$this->get('basket')->removeEntity('discounts', $this->get('basket.order')->discounts->get($metadataKey));

					return false;
				}

				return $e->getMessage();
			}
		}

		return true;
	}

	public function removeBundles(CurrencyChangeEvent $event)
	{
		$order = $this->get('basket.order');
		$bundleIDs = $this->_getBundleIDs($order);

		foreach ($bundleIDs as $metadataKey => $bundleID) {
			if ($order->discounts->exists($metadataKey)) {
				$this->get('basket')->removeEntity('discounts', $order->discounts->get($metadataKey));
			}
		}

		$event = new OrderEvent($this->get('basket.order'));
		$this->validateBundle($event);
	}

	/**
	 * Loop through metadata and find bundle IDs
	 *
	 * @param Order\Order $order
	 *
	 * @return array
	 */
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