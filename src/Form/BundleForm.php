<?php

namespace Message\Mothership\Discount\Form;

use Message\Mothership\Discount\Bundle\BundleFactory;
use Message\Mothership\FileManager\File;
use Message\Cog\Localisation\Translator;
use Symfony\Component\Form;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class BundleForm
 * @package Message\Mothership\Discount\Form
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Form for creating and editing bundles
 */
class BundleForm extends Form\AbstractType
{
	const ID = 'id';
	const NAME = 'name';
	const START = 'start';
	const END = 'end';
	const PRICE_PREFIX = 'price_';
	const PRODUCT = 'product';
	const CODES = 'allow_codes';
	const IMAGE = 'image';

	/**
	 * @var File\FileLoader
	 */
	private $_fileLoader;

	/**
	 * @var array
	 */
	private $_currencies;

	/**
	 * @var BundleProductForm
	 */
	private $_productForm;

	/**
	 * @var Translator
	 */
	private $_translator;

	/**
	 * @var BundleFactory
	 */
	private $_factory;

	/**
	 * @param File\FileLoader $fileLoader
	 * @param Translator $translator
	 * @param BundleFactory $factory
	 * @param BundleProductForm $productForm
	 * @param array $currencies
	 *
	 * @throws \LogicException                      Throws exception if currency array is empty
	 */
	public function __construct(
		File\FileLoader $fileLoader,
		Translator $translator,
		BundleFactory $factory,
		BundleProductForm $productForm,
		array $currencies
	)
	{
		if (count($currencies) === 0) {
			throw new \LogicException('No currencies passed to bundle form');
		}

		$this->_fileLoader = $fileLoader;
		$this->_translator = $translator;
		$this->_factory = $factory;
		$this->_productForm = $productForm;
		$this->_currencies = $currencies;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'discount_bundle';
	}

	/**
	 * {@inheritDoc}
	 */
	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$builder->add(self::ID, 'hidden');

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
			'type'            => $this->_productForm,
			'label'           => 'ms.discount.bundle.product.label',
			'contextual_help' => 'ms.discount.bundle.product.help',
			'allow_add'       => true,
			'allow_delete'    => true,
		]);

		$builder->addModelTransformer(new DataTransformer\BundleTransformer($this->_factory));
	}

	/**
	 * {@inheritDoc}
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults([
			'data_class' => null, // @todo For some reason it freaks out expecting a BundleProxy if this isn't set
		]);
	}

	/**
	 * Load all image files and create an array of choices
	 *
	 * @return array
	 */
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