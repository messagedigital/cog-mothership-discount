<?php

namespace Message\Mothership\Discount\Controller;

use Message\Mothership\Discount\Discount;
use Message\Cog\Controller\Controller;
use Message\Cog\ValueObject\DateTimeImmutable;
use Symfony\Component\Validator\Constraints;

use Message\Mothership\Discount\Form\Type\DiscountType;

class Create extends Controller
{
	public function index()
	{
		$maxCodeLength = $this->get('cfg')->discount->maxCodeLength;
		return $this->render('::create', array(
			'form'  => $this->createForm(new DiscountType($maxCodeLength)),

		));
	}

	public function process()
	{
		$maxCodeLength = $this->get('cfg')->discount->maxCodeLength;
		$form = $this->createForm(new DiscountType($maxCodeLength));

		$form->handleRequest();

		if ($form->isValid()) {
			$discount = $form->getData();
			$discount->authorship->create(new DateTimeImmutable, $this->get('user.current')->id);

            $discount = $this->get('discount.create')->create($discount);

            if ($discount->id) {
                $this->addFlash('success', sprintf('You successfully added discount "%s"!', $discount->name));
                return $this->redirectToRoute('ms.cp.discount.edit', array('discountID' => $discount->id));
            }
		}

		return $this->render('::create', array(
			'form'  => $form,
		));
	}

	protected function _getForm()
	{
		$maxCodeLength = $this->get('cfg')->discount->maxCodeLength;

		$form = $this->createFormBuilder()
			->setAction($this->generateUrl('ms.cp.discount.create.action'))
			->setMethod('post')
			->setAttribute('errors_with_fields', true);

		$form->add('name', 'text', [
			'required' => false,
			'constraints' => [
				new Constraints\NotBlank,
				new Constraints\Length(['max' => 255]),
			]
		])
			// ->titlecase()
			;

		$form->add('description', 'textarea', [
			'required' => false,
		]);

		$form->add('code', 'text', [
			'constraints' => [
				new Constraints\Length(['max' => $maxCodeLength]),
				new Constraints\NotBlank,
			],
			'attr' => ['maxlength' => $maxCodeLength],
			'required' => false,
			// UPPERCASE filter!
			]
		);

		$form->add('start', 'datetime', [
	    		'data' => new \DateTime,
				'required' => false,
    		]
    	);

		$form->add('end', 'datetime', [
	    		'data' => new \DateTime,
	    		'required' => false,
    		]
    	);

		return $form->getForm();
	}
}
