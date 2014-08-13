<?php

namespace Message\Mothership\Discount\Discount;

use Message\Cog\Service\Container;
use Message\Cog\ValueObject\Authorship;
use Message\Cog\DB\Query;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Order\Order;
use Message\Cog\Localisation\Translator;

/**
 * Validator to check whether a discount (identified by it's code)
 * can be applied to a given order.
 */
class Validator
{
	const ALREADY_USED  = 'This code has already been used by this email address.';
	const INVALID_EMAIL = 'This discount applies to certain email addresses only, please ensure you are allowed to use this discount code';

	/**
	 * @var Order
	 */
	protected $_order;

	/**
	 * @var Loader
	 */
	protected $_discountLoader;

	/**
	 * @var OrderDiscountFactory
	 */
	protected $_orderDiscountFactory;

	/**
	 * @var \Message\Cog\DB\Query
	 */
	protected $_query;

	/**
	 * @var \Message\Cog\Localisation\Translator
	 */
	protected $_trans;

	public function __construct(
		Loader $discountLoader,
		OrderDiscountFactory $orderDiscountFactory,
		Query $query,
		Translator $trans
	)
	{
		$this->_discountLoader       = $discountLoader;
		$this->_orderDiscountFactory = $orderDiscountFactory;
		$this->_query                = $query;
		$this->_trans                = $trans;
	}

	public function setOrder(Order $order)
	{
		$this->_order = $order;
		$this->_orderDiscountFactory->setOrder($order);

		return $this;
	}

	public function getOrder()
	{
		return $this->_order;
	}

	/**
	 * Validates a discount by it's discountCode and an order
	 * and returns an order-discount-object.
	 * Checks validity of the code, whether it is active and
	 * (if the discount applies to specific products) whether
	 * the at least one of the items in the order matches one
	 * of the discount's products.
	 *
	 * @param string $discountCode The code of the discount validated
	 *
	 * @throws OrderValidityException if the validation failed
	 *
	 * @return Order\Entity\Discount\Discount the order-discount-object for the given discountCode
	 */
	public function validate($discountCode, $adding = true)
	{
		$adding = (bool) $adding;

		if (null === $this->_order) {
			throw new \Exception('Order must be set before discount code can be validated');
		}

		$this->_validateMaxNumberDiscounts($adding);
		
		if ($adding) {
				$this->_validateAlreadyUsed($discountCode);
		}

		if(0 === $this->_order->items->count()) {
			throw new OrderValidityException('Your basket is empty');
		}

		$discount = $this->_discountLoader->getByCode($discountCode);
		if (!$discount) {
			throw new OrderValidityException('The entered code was not recognised.');
		}

		if (!$discount->isActive()) {
			$message = ($discount->start < new \DateTime ? 'The discount has expired.' : 'The discount is not active yet.');
			throw new OrderValidityException($message);
		}

		// check whether discount-threshold is reached
		if (0 !== count($discount->thresholds)) {
			foreach ($discount->thresholds as $currencyID => $threshold) {
				if ($this->_order->currencyID === $currencyID) {
					if ($this->_order->productGross < $threshold) {
						throw new OrderValidityException('Your order value is less than the discount threshold.');
					}
				}
			}
		}

		// check whether order has at least one of the products the discount applies to
		if (!$discount->appliesToOrder) {
			$appliesToItem = false;

			foreach ($discount->products as $product) {
				foreach ($this->_order->items->all() as $item) {
					if ($item->productID === $product->id) {
						$appliesToItem = true;
						break;
					}
				}
				if ($appliesToItem) {
					break;
				}
			}

			if (!$appliesToItem) {
				throw new OrderValidityException('Your order does not include any of the products the discount applies to.');
			}
		}

		$this->_validateEmail($discount);

		$this->_orderDiscountFactory
			->setOrder($this->_order)
			->setDiscount($discount);

		return $this->_orderDiscountFactory->createOrderDiscount();
	}

	protected function _validateEmail(Discount $discount)
	{
		if (!empty($discount->emails)) {

			if ($this->_order->userEmail || $this->_order->user) {

				$email = $this->_order->userEmail ?: $this->_order->user->email;

				if (!$email) {
					throw new OrderValidityException(self::INVALID_EMAIL);
				}

				$result = $this->_query->run("
					SELECT
						used_at
					FROM
						discount_email
					WHERE
						discount_id = :id?i
					AND
						LOWER(email) = :email?s
				", [
					'id'    => $discount->id,
					'email' => strtolower($email),
				])->flatten();

				if (empty($result)) {
					throw new OrderValidityException(self::INVALID_EMAIL);
				}

				$usedAt = array_shift($result);

//				if ($usedAt) {
//					throw new OrderValidityException(self::ALREADY_USED);
//				}
			}
		}
	}

	protected function _validateMaxNumberDiscounts($adding)
	{
		$numDiscounts = count($this->getOrder()->discounts);
		$invalid = ($adding) ? $numDiscounts >= $this->_getMaxDiscounts() : $numDiscounts > $this->_getMaxDiscounts();

		if ($invalid) {
			throw new OrderValidityException($this->_trans->trans('ms.discount.discount.add.error.max', [
				'%max%'    => $this->_getMaxDiscounts(),
				'%plural%' => ($this->_getMaxDiscounts() === 1) ? '' : 's',
			]));
		}

		return $this;
	}

	protected function _validateAlreadyUsed($code)
	{
		if ($this->getOrder()->discounts->codeExists($code)) {
			throw new OrderValidityException($this->_trans->trans('ms.discount.discount.add.error.used'));
		}

		return $this;
	}

	/**
	 * @todo Make able to set in config
	 */
	protected function _getMaxDiscounts()
	{
		return 1;
	}

}