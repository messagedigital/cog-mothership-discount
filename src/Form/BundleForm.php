<?php

namespace Message\Mothership\Discount\Form;

use Message\Mothership\FileManager\File;
use Message\Cog\Localisation\Translator;
use Symfony\Component\Form;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class BundleForm extends Form\AbstractType
{
	const NAME = 'name';
	const START = 'start';
	const END = 'end';
	const PRICE_PREFIX = 'price_';
	const PRODUCT = 'product';
	const CODES = 'allow_codes';
	const IMAGE = 'image';

	private $_fileLoader;
	private $_currencies;
	private $_productForm;
	private $_translator;

	public function __construct(
		File\FileLoader $fileLoader,
		Translator $translator,
		BundleProductForm $productForm,
		array $currencies
	)
	{
		if (count($currencies) === 0) {
			throw new \LogicException('No currencies passed to bundle form');
		}

		$this->_fileLoader = $fileLoader;
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
		$builder->add(self::NAME, 'text', [
			'label' => 'ms.discount.bundle.name.label',
			'contextual_help' => 'ms.discount.bundle.name.help',
			'constraints' => [
				new Constraints\NotBlank,
			]
		]);

		$builder->add(self::START, 'date', [
			'label' => 'ms.discount.bundle.start.label',
			'contextual_help' => 'ms.discount.bundle.start.help',
		]);

		$builder->add(self::END, 'date', [
			'label' => 'ms.discount.bundle.end.label',
			'contextual_help' => 'ms.discount.bundle.end.help',
		]);

		foreach ($this->_currencies as $currency) {
			$builder->add(self::PRICE_PREFIX . $currency, 'money', [
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

		$builder->add(self::IMAGE, 'ms_file', [
			'label' => 'ms.discount.bundle.image.label',
			'contextual_help' => 'ms.discount.bundle.image.help',
			'choices' => $this->_getImageChoices(),
		]);

		$builder->add(self::CODES, 'checkbox', [
			'label' => 'ms.discount.bundle.allow_codes.label',
			'contextual_help' => 'ms.discount.bundle.allow_codes.help'
		]);

		$builder->add(self::PRODUCT, 'collection', [
			'type' => $this->_productForm,
			'label' => 'ms.discount.bundle.product.label',
			'contextual_help' => 'ms.discount.bundle.product.help',
			'allow_add' => true,
			'allow_delete' => true,
		]);
	}


	private function _getImageChoices()
	{
		$images = (array) $this->_fileLoader->getByType(File\Type::IMAGE);

		$choices = [];

		foreach ($images as $image) {
			$choices[$image->id] = $image->name;
		}

		asort($choices);

		return $choices;
	}
}