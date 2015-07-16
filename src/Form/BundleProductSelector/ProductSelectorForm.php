<?php

namespace Message\Mothership\Discount\Form\BundleProductSelector;

use Message\Mothership\Commerce\Product\Product;
use Symfony\Component\Form;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints;

class ProductSelectorForm extends Form\AbstractType
{
	public function getName()
	{
		return 'bundle_product_selector';
	}

	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$this->_validateOptions($options);

		$choices = $this->_getChoices($options);

		$this->add('unit_id', 'unit_choice', [
			'label' => 'ms.commerce.product.selector.unit.label',
			'choices' => $choices,
			'oos' => $options['out_of_stock'],
			'empty_value' => 'ms.commerce.product.selector.unit.label',
			'show_pricing' => !empty($options['show_variable_pricing']) && $options['product']->hasVariablePricing(),
		]);

	}

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

	private function _getChoices(array $options)
	{
		$choices = [];

		foreach ($options['units'] as $unit) {
			$unitOptions = $options['unit_options'] ? array_diff_assoc($unit->options, $options['unit_options']) : $unit->options;
			$choices[$unit->id] = implode(', ', array_filter($unitOptions));
		}

		return $choices;
	}

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