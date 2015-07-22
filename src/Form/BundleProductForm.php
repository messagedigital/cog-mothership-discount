<?php

namespace Message\Mothership\Discount\Form;

use Message\Mothership\Commerce\Product\Loader as ProductLoader;
use Message\Mothership\Commerce\Product\OptionLoader;
use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

/**
 * Class BundleProductForm
 * @package Message\Mothership\Discount\Form
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 */
class BundleProductForm extends Form\AbstractType
{
	const PRODUCT = 'product';
	const OPTION_NAME = 'product_option_name';
	const OPTION_VALUE = 'product_option_value';
	const QUANTITY = 'quantity';

	/**
	 * @var ProductLoader
	 */
	private $_productLoader;

	/**
	 * @var OptionLoader
	 */
	private $_optionLoader;

	/**
	 * @param ProductLoader $productLoader
	 * @param OptionLoader $optionLoader
	 */
	public function __construct(ProductLoader $productLoader, OptionLoader $optionLoader)
	{
		$this->_productLoader = $productLoader;
		$this->_optionLoader  = $optionLoader;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'discount_bundle_product';
	}

	/**
	 * {@inheritDoc}
	 */
	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$builder->add(self::PRODUCT, 'choice', [
			'expanded' => false,
			'multiple' => false,
			'choices' => $this->_getProductChoices(),
			'constraints' => [
				new Constraints\NotBlank,
			],
			'label' => 'ms.discount.bundle.products.product.label',
			'contextual_help' => 'ms.discount.bundle.products.product.help'
		]);

		$builder->add(self::OPTION_NAME, 'choice', [
			'expanded' => false,
			'multiple' => false,
			'choices' => $this->_getOptionNames(),
			'label' => 'ms.discount.bundle.products.option_name.label',
			'contextual_help' => 'ms.discount.bundle.products.option_name.help',
		]);

		$builder->add(self::OPTION_VALUE, 'choice', [
			'expanded' => false,
			'multiple' => false,
			'choices' => $this->_getOptionValues(),
			'label' => 'ms.discount.bundle.products.option_value.label',
			'contextual_help' => 'ms.discount.bundle.products.option_value.help',
		]);

		$builder->add(self::QUANTITY, 'number', [
			'label' => 'ms.discount.bundle.products.quantity.label',
			'contextual_help' => 'ms.discount.bundle.products.quantity.help',
			'constraints' => [
				new Constraints\NotBlank
			]
		]);
	}

	/**
	 * Get an array of choices for unit option names
	 *
	 * @return array
	 */
	private function _getOptionNames()
	{
		$optionNames = $this->_optionLoader->getAllOptionNames();

		return array_combine($optionNames, $optionNames);
	}

	/**
	 * Get an array of choices for unit option values
	 *
	 * @return array
	 */
	private function _getOptionValues()
	{
		$optionValues = $this->_optionLoader->getAllOptionValues();

		return array_combine($optionValues, $optionValues);
	}

	/**
	 * Get an array of choices for product names
	 *
	 * @return array
	 */
	private function _getProductChoices()
	{
		$products = $this->_productLoader->getAll();
		$choices = [];

		foreach ($products as $product) {
			$choices[$product->id] = $product->getName();
		}

		return $choices;
	}
}