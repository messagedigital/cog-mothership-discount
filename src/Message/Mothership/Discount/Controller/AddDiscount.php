<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Mothership\Discount\Discount;

/**
 * Controller to manage adding discounts to the order in the basket
 */
class AddDiscount extends Controller
{
	public function index()
	{
		return $this->render('Message:Mothership:Discount::discount-input', array(
			'form' => $this->_getDiscountForm(),
		));

	}

	public function discountProcess()
	{
		$form = $this->_getDiscountForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$code = strtoupper($data["code"]);
			$order = $this->get('basket')->getOrder();
			$discountValidator = $this->get('discount.validator')->setOrder($order);
			$orderDiscount = null;

			try {
				if($order->discounts->codeExists($code)) {
					throw new Discount\OrderValidityException('This discount has already been used on this order.');
				}
				$orderDiscount = $discountValidator->validate($code);
			} catch (Discount\OrderValidityException $e) {
				$this->addFlash('error', sprintf('The discount `%s` could not be added: %s', $code, $e->getMessage()));
			}

			if($orderDiscount) {
				$this->get('basket')->addDiscount($orderDiscount);
				$this->addFlash('success', 'You successfully added a discount');
			}
		} else {
			$this->addFlash('error', 'Please enter a valid discount code');
		}

		return $this->redirectToReferer();
	}

	protected function _getDiscountForm()
	{
		$form = $this->get('form');
		$form->setName('discount_form')
			->setAction($this->generateUrl('ms.discount.add.action'))
			->setMethod('post');
		$form->add('code', 'text', 'I have a discount code');

		return $form;
	}
}