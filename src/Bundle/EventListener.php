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
	 * Array for keeping track of which bundles are being processed in this request. This is to prevent
	 * recursive loops as a result of removing and re-adding the discount entity firing the ASSEMBLER_UPDATE
	 * for within the listener that responds to that event.
	 *
	 * @var array
	 */
	private $_bundleLog = [];

	/**
	 * {@inheritDoc}
	 */
	static public function getSubscribedEvents()
	{
		return [
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

			if (array_key_exists($bundleID, $this->_bundleLog)) {
				// If the ID exists in the bundle log, the only reason this method is being called against is because it
				// has just been added to the order, so we don't need to revalidate the bundle. Instead, we remove it
				// from the bundle log and break out of the loop.
				unset($this->_bundleLog[$bundleID]);
				break;
			}

			$bundle = $bundles[$bundleID];
			$bundleExists = $this->get('basket.order')->discounts->exists($metadataKey);

			if ($bundleExists) {
				$this->get('basket.order')->discounts->remove($this->get('basket.order')->discounts->get($metadataKey));
			}

			// Validator will throw an exception if the bundle is not valid for the order. Remove the discount if it
			// has already been set and show a flash message.
			try {
				$validator->validate($bundle, $event->getOrder());

				$discount = $this->get('discount.bundle.order_discount_factory')
					->createOrderDiscount($event->getOrder(), $bundle);

				// Temporarily set ID to keep track of bundles that have had their discounts applied
				$discount->id = $metadataKey;

				// Add the bundle ID to the log to prevent an infinite loop
				$this->_bundleLog[$bundleID] = $bundleID;
				$this->get('basket')->addEntity('discounts', $discount);

			} catch (Exception\BundleValidationException $e) {
				if ($bundleExists) {
					$this->get('http.session')->getFlashBag()->add(
						'warning',
						$e->getMessage()
					);

				}

				return false;
			}
		}

		return true;
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