<?php

namespace Message\Mothership\Discount\Discount;

use Message\Mothership\Commerce\Order\Events as OrderEvents;
use Message\Mothership\Commerce\Order\Event;

use Message\Cog\Event\SubscriberInterface;
use Message\Cog\Event\EventListener as BaseListener;


/**
 * Discount event listener.
 */
class EventListener extends BaseListener implements SubscriberInterface
{
	/**
	 * {@inheritdoc}
	 */
	static public function getSubscribedEvents()
	{
		return array(
			OrderEvents::CREATE_START => array(
				array('validateDiscount', 250),
				array('updateOrderDiscounts', 200),
			),
			OrderEvents::ASSEMBLER_UPDATE => array(
				array('validateDiscount', 250),
				array('updateOrderDiscounts', 200),
			)
		);
	}

	/**
	 * Goes through all discounts (with code) in the event's order and updates them by
	 * reloading the Discount\Discount and generating a new Order\Discount from that.
	 *
	 * @param Event $event The event object
	 */
	public function updateOrderDiscounts(Event\Event $event)
	{
		$order = $event->getOrder();
		$orderDiscountFactory = $this->get('discount.order-discount-factory')
			->setOrder($order);

		foreach ($discounts = $order->discounts as $key => $orderDiscount) {
			if($orderDiscount->code) {
				$discount = $this->get('discount.loader')->getByCode($orderDiscount->code);
				$orderDiscountFactory->setDiscount($discount);
				$orderDiscount = $orderDiscountFactory->createOrderDiscount();

				$discounts->removeByCode($orderDiscount->code);
				$discounts->append($orderDiscount);
			}
		}
	}

	/**
	 * Validates all discounts the order in the event has and removes
	 * invalid discounts from the basket.
	 *
	 * @todo  Add notification when discount is removed
	 *
	 * @param Event $event The event object
	 */
	public function validateDiscount(Event\Event $event)
	{
		$order = $event->getOrder();
		$discountValidator = $this->get('discount.validator')->setOrder($order);
		foreach($order->discounts as $orderDiscount) {
			try {
				$orderDiscount = $discountValidator->validate($orderDiscount->code);
			} catch(OrderValidityException $e) {
				$this->get('basket')->removeDiscount($orderDiscount);
				$this->get('http.session')->getFlashBag()->add(
					'warning',
					sprintf('Discount `%s` is not valid anymore and had to be removed: %s', $orderDiscount->code, $e->getMessage())
				);
			}
		}
	}
}