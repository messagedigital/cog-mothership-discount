<?php

namespace Message\Mothership\Discount\Discount;

use Message\Mothership\Commerce\Order\Events as OrderEvents;
use Message\Mothership\Commerce\Order\Event;
use Message\Mothership\Commerce\Order\Status\Status as BaseStatus;

use Message\Cog\Event\SubscriberInterface;

/**
 * Discount event listener.
 */
class EventListener implements SubscriberInterface
{
	/**
	 * {@inheritdoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			OrderEvents::CREATE_START => array(
				array('setDiscountItems', 300),
			),
			OrderEvents::ASSEMBLER_UPDATE => array(
				array('validateDiscount'),
			)
		);
	}

	/**
	 * Set the items the discount is applicable to.
	 *
	 * @todo once the discounts system is built, inspect this to find out which
	 *       products a discount should apply to (if discount code recognised)
	 *
	 * @param Event $event The event object
	 */
	public function setDiscountItems(Event\Event $event)
	{
		foreach ($event->getOrder()->discounts as $orderDiscount) {
			$discount = $this->get('discount.loader')->getByCode($orderDiscount->code);
			foreach($event->getOrder()->items->all() as $item) {
				foreach($this->products as $product) {
					if($item->productID === $product->id) {
						$orderDiscount->addItem($item);
						continue;
					}
				}
			}
		}
	}

	public function validateDiscount(Event\Event $event)
	{
		$order = $event->getOrder();
		$discountValidator = $this->get('discount.validator')->setOrder($order);
		foreach($order->discounts as $orderDiscount) {
			try {
				$discountValidator->validate($orderDiscount->code);
			} catch(OrderValidityException $e) {
				$order->discounts->remove($orderDiscount->id);
				$this->addFlash(
					'warning',
					sprintf(
						'Discount `%s` had to be removed from your order as it is not valid anymore: %s',
						$orderDiscount->name,
						$e->getMessage()
					)
				);
			}
		}
	}
}