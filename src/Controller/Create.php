<?php

namespace Message\Mothership\Discount\Controller;

use Message\Mothership\Discount\Discount;
use Message\Cog\Controller\Controller;
use Symfony\Component\Validator\Constraints;

use Message\Mothership\Discount\Form\DiscountForm;

class Create extends Controller
{
	public function index()
	{
		$form = $this->createForm($this->get('discount.form.discount.attributes'));
		$form->handleRequest();

		if ($form->isValid()) {
			$discount = $form->getData();
            $discount = $this->get('discount.create')->create($discount);

            if ($discount->id) {
				$this->addFlash('success', $this->trans('ms.discount.discount.create.success', array(
					'%name%' => $discount->name,
				)));

                return $this->redirectToRoute('ms.cp.discount.edit', array('discountID' => $discount->id));
            }
		}

		return $this->render('::create', array(
			'form' => $form,
		));
	}
}
