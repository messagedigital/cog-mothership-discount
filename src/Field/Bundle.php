<?php

namespace Message\Mothership\Discount\Field;

use Message\Cog\Field\Field;
use Message\Mothership\Discount\Bundle as BundleNamespace;

class Bundle extends Field
{
	/**
	 * @var BundleNamespace\Loader
	 */
	private $_loader;
	private $_bundle;
	private $_bundleOptions;

	public function __construct(BundleNamespace\Loader $loader)
	{
		$this->_loader = $loader;
	}

	public function getFieldType()
	{
		return 'discount_bundle';
	}

	public function getFormField()
	{
		return 'choice';
	}

	public function getFieldOptions()
	{
		$defaults = [
			'choices'     => $this->_getBundleOptions(),
			'empty_value' => 'Please select a bundle...',
			'expanded'    => false,
			'multiple'    => false,
		];

		return array_merge($defaults, parent::getFieldOptions());
	}

	public function setValue($value)
	{
		if (!is_numeric($value) || (int) $value != $value) {
			throw new \InvalidArgumentException('Value must be a Bundle ID and therefore a whole number');
		}

		parent::setValue((int) $value);
	}

	public function getValue()
	{
		if ($this->_bundle instanceof BundleNamespace\Bundle && $this->_bundle->getID() === $this->_value) {
			return $this->_bundle;
		}

		if (null !== $this->_value) {
			$this->_bundle = $this->_loader->getByID($this->_value);

			return $this->_bundle;
		}

		return null;
	}

	private function _getBundleOptions()
	{
		if (null !== $this->_bundleOptions) {
			return $this->_bundleOptions;
		}

		$bundles = $this->_loader->getAll();

		array_walk($bundles, function (&$bundle) {
			$bundle = $bundle->getName();
		});

		asort($bundles);

		$this->_bundleOptions = $bundles;

		return $this->_bundleOptions;
	}
}