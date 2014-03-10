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
				if ($order->discounts->codeExists($code)) {
					throw new Discount\OrderValidityException($this->trans('ms.discount.add.error.used'));
				}
				$orderDiscount = $discountValidator->validate($code);
			} catch (Discount\OrderValidityException $e) {
				$this->addFlash('error', $this->trans('ms.discount.add.error.message', array(
					'%code%' => $code,
					'%message%' => $e->getMessage()
				)));
			}

			if ($orderDiscount) {
				$this->get('basket')->addEntity('discounts', $orderDiscount);
				$this->addFlash('success', $this->trans('ms.discount.add.success'));
			}
		} else {
			$this->addFlash('error', $this->trans('ms.discount.add.error.invalid'));
		}

		return $this->redirectToReferer();
	}

	protected function _getDiscountForm()
	{
		$form = $this->get('form');
		$form->setName('discount_form')
			->setAction($this->generateUrl('ms.discount.process'))
			->setMethod('post');
		$form->add('code', 'text', $this->trans('ms.discount.add.label'));

		return $form;
	}
}