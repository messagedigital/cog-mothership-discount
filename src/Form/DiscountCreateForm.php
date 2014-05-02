<?php

namespace Message\Mothership\Discount\Form;

use Message\Mothership\Discount\Discount\Discount;
use Message\Mothership\Discount\Discount\Loader;

use Symfony\Component\Form;
use Symfony\Component\Validator\Constraints;

class DiscountCreateForm extends DiscountAttributesForm
{
	protected $_loader;

	public function __construct($maxCodeLength, Loader $loader)
	{
		parent::__construct($maxCodeLength);
		$this->_loader        = $loader;

		return $this;
	}

	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		parent::buildForm($builder, $options);

		$builder->add('code', 'text', [
			'constraints' => [
				new Constraints\Length(['max' => $this->_maxCodeLength]),
				new Constraints\NotBlank,
			],
			'attr'            => ['maxlength' => $this->_maxCodeLength],
			'label'           => 'ms.discount.discount.attributes.code.label',
			'contextual_help' => 'ms.discount.discount.attributes.code.help',
		]);
	}

	public function onPostSubmit(Form\FormEvent $event)
	{
		parent::onPostSubmit($event);

		$this->validateCode($event->getForm());
	}

	public function validateCode(Form\FormInterface $form)
	{
		$discount = $form->getData();

		if (false !== $this->_loader->getByCode($discount->code)) {
			$form->get('code')->addError(new Form\FormError('A discount with this code already exists.'));
		}
	}

	public function getName()
	{
		return 'discount_create';
	}
}