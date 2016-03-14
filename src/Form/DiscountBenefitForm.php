<?php

namespace Message\Mothership\Discount\Form;

use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Message\Mothership\Discount\Discount\Discount;
use Symfony\Component\Validator\Constraints;
use Message\Cog\ValueObject\DateTimeImmutable;

class DiscountBenefitForm extends Form\AbstractType
{
	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$builder->add('percentage', 'percent', [
			'label'    => 'ms.discount.discount.benefit.percentage.label',
			'type'     => 'integer',
			'constraints' => [
				new Constraints\LessThanOrEqual(['value' => 100]),
			]
		]);

		$builder->add('discountAmounts', 'currency_set', [
			'label' => 'ms.discount.discount.benefit.discount-amounts.label',
			'options' => [
				'label' => false,
			],
		]);

		$builder->add('freeShipping', 'checkbox', [
			'label'           => 'ms.discount.discount.benefit.free-shipping.label',
			'contextual_help' => 'ms.discount.discount.benefit.free-shipping.help',
		]);

		$builder->addEventListener(Form\FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
	}

	public function onPostSubmit(Form\FormEvent $event)
	{
		$this->validate($event->getForm());
	}

	public function validate(Form\FormInterface $form)
	{
		$discountAmounts = false;
		foreach ($form->get('discountAmounts')->getData() as $currency => $amount) {
			if ($amount) {
				$discountAmounts = true;
				break;
			}
		}

		if ($form->get('percentage')->getData() && $discountAmounts) {
			$form->addError(new Form\FormError('Please only fill in either a percentage OR a fixed discount.'));
		} elseif (!$form->get('percentage')->getData() && !$discountAmounts && false === $form->get('freeShipping')->getData()) {
			$form->addError(new Form\FormError('Neither a percentage discount, nor a fixed discount amount, nor free shipping have been added to this discount.'));
		}
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'Message\\Mothership\\Discount\\Discount\\Discount',
		]);
	}

	public function getName()
	{
		return 'discount_benefit';
	}
}