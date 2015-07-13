<?php

namespace Message\Mothership\Discount\Form;

use Message\Cog\Localisation\Translator;
use Symfony\Component\Form;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BundleForm extends Form\AbstractType
{
	private $_currencies;
	private $_productForm;
	private $_translator;

	public function __construct(Translator $translator, BundleProductForm $productForm, array $currencies)
	{
		if (count($currencies) === 0) {
			throw new \LogicException('No currencies passed to bundle form');
		}

		$this->_translator = $translator;
		$this->_productForm = $productForm;
		$this->_currencies = $currencies;
	}

	public function getName()
	{
		return 'discount_bundle';
	}

	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$builder->add('name', 'text', [
			'label' => 'ms.discount.bundle.name.label',
			'contextual_help' => 'ms.discount.bundle.name.help',
			'constraints' => [
				new Constraints\NotBlank,
			]
		]);

		$builder->add('start', 'date', [
			'label' => 'ms.discount.bundle.start.label',
			'contextual_help' => 'ms.discount.bundle.start.help',
		]);

		$builder->add('end', 'date', [
			'label' => 'ms.discount.bundle.end.label',
			'contextual_help' => 'ms.discount.bundle.end.help',
		]);

		foreach ($this->_currencies as $currency) {
			$builder->add('price_' . $currency, 'money', [
				'label' => $this->_translator->trans('ms.discount.bundle.price.label', [
					'%currencyID%' => $currency,
				]),
				'contextual_help' => $this->_translator->trans('ms.discount.bundle.price.help', [
					'%currencyID%' => $currency,
				]),
				'constraints' => [
					new Constraints\NotBlank,
				],
				'currency' => $currency,
			]);
		}

		$builder->add('product', 'collection', [
			'type' => $this->_productForm,
			'label' => 'ms.discount.bundle.product.label',
			'contextual_help' => 'ms.discount.bundle.product.help',
			'allow_add' => true,
			'allow_delete' => true,
		]);
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{}
}