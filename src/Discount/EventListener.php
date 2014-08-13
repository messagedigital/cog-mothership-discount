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
			OrderEvents::CREATE_START => [
				['validateDiscount', 250],
				['updateOrderDiscounts', 200],
			],
			OrderEvents::ASSEMBLER_UPDATE => [
				['validateDiscount', 250],
				['updateOrderDiscounts', 200],
			],
			OrderEvents::CREATE_COMPLETE => [
				['updateDiscountEmail', 250],
			],
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
			if ($orderDiscount->code) {
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
		$order             = $event->getOrder();
		$discountValidator = $this->get('discount.validator')->setOrder($order);

		foreach ($order->discounts as $orderDiscount) {
			try {
				if ($orderDiscount->code) {
					$orderDiscount = $discountValidator->validate($orderDiscount->code, false);
				}
			} catch (OrderValidityException $e) {
				$order->discounts->remove($orderDiscount);

				if ($event instanceof Event\Event\AssemblerEvent) {
					$event->getAssembler()->dispatchEvent();
				}

				$this->get('http.session')->getFlashBag()->add(
					'info',
					sprintf('Discount `%s` was removed: %s', $orderDiscount->code, $e->getMessage())
				);
			}
		}
	}

	public function updateDiscountEmail(Event\Event $event)
	{
		$order = $event->getOrder();

		foreach ($order->discounts as $orderDiscount) {
			if ($orderDiscount->code) {
				$discount = $this->get('discount.loader')->getByCode($orderDiscount->code);
				if (in_array($order->userEmail, $discount->emails)) {
					$this->get('discount.edit')->markEmailAsUsed($discount, $order->userEmail);
				}
			}
		}
	}
}