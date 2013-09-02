<?php

namespace Message\Mothership\Discount\Controller;

use Message\Cog\Controller\Controller;
use Message\Moethership\Discount\Discount;

/**
 * Controller to manage adding gift vouchers to orders
 */
class AddDiscount extends Controller
{
	public function index()
	{
		return $this->render('Message:Mothership:Discount::discount-input', array(
			'form' => $this->discountForm(),
		));

	}

	public function discountForm()
	{
		$form = $this->get('form');
		$form->setName('discount_form')
			->setAction($this->generateUrl('ms.discount.process'))
			->setMethod('post');
		$form->add('code', 'text', 'I have a discount token / camapign code');

		return $form;
	}

	public function discountProcess()
	{
		$form = $this->discountForm();
		if ($form->isValid() && $data = $form->getFilteredData()) {
			$discountValidator = $this->get('discount.validator')->setOrder($this->get('basket')->getOrder());
			try {
				$discountValidator->validate($data["code"]);
			} catch (Discount\OrderValidityException $e) {
				$this->addFlash('error', $e->getMessage());
			}
		} else {
			$this->addFlash('error', 'Please enter a valid discount code');
		}

		return $this->redirectToReferer();
	}
}