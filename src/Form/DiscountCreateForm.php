<?php

namespace Message\Mothership\Discount\Form;

use Message\User\User;
use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Message\Mothership\Discount\Discount\Discount;
use Symfony\Component\Validator\Constraints;
use Message\Cog\ValueObject\DateTimeImmutable;

class DiscountCreateForm extends DiscountAttributesForm
{

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

	public function getName()
	{
		return 'discount_create';
	}
}