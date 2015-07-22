<?php

namespace Message\Mothership\Discount\Form\BundleProductSelector;

use Message\Mothership\Commerce\Product\Product;
use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

/**
 * Class ProductSelectorForm
 * @package Message\Mothership\Discount\Form\BundleProductSelector
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Product selector form for an individual item as part of a bundle
 */
class ProductSelectorForm extends Form\AbstractType
{
	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'bundle_product_selector';
	}

	/**
	 * {@inheritDoc}
	 */
	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$this->_validateOptions($options);

		$choices = $this->_getChoices($options);

		$builder->add('unit_id', 'unit_choice', [
			'label'        => $options['product']->displayName ?: $options['product']->name,
			'choices'      => $choices,
			'oos'          => $options['out_of_stock'],
			'empty_value'  => 'ms.commerce.product.selector.unit.label',
			'show_pricing' => !empty($options['show_variable_pricing']) && $options['product']->hasVariablePricing(),
		]);

	}

	/**
	 * {@inheritDoc}
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults([
			'units'                 => [],
			'out_of_stock'          => [],
			'unit_options'          => [],
			'product'               => null,
			'show_variable_pricing' => true,
		]);
	}

	/**
	 * Get an array of available units defined by their options
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	private function _getChoices(array $options)
	{
		$choices = [];

		foreach ($options['units'] as $unit) {
			$unitOptions = $options['unit_options'] ? array_diff_assoc($unit->options, $options['unit_options']) : $unit->options;
			$choices[$unit->id] = implode(', ', array_filter($unitOptions));
		}

		return $choices;
	}

	/**
	 * Validate form options
	 *
	 * @param array $options
	 * @throws \LogicException      Throws exception if options are not valid
	 */
	private function _validateOptions(array $options)
	{
		if (empty($options['product'])) {
			throw new \LogicException('Please assign a product to the form options');
		}

		if (!$options['product'] instanceof Product) {
			throw new \InvalidArgumentException('`product` option must be an instance of Product');
		}

		if (!array_key_exists('units', $options)) {
			throw new \LogicException('Units not set in form options');
		}

		if (!array_key_exists('unit_options', $options)) {
			throw new \LogicException('Unit options not set in form options');
		}

		if (!array_key_exists('out_of_stock', $options)) {
			throw new \LogicException('Out of stock items not set in form options');
		}
	}
}