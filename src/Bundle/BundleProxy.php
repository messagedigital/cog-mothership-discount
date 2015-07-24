<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Entity\EntityLoaderCollection;

/**
 * Class BundleProxy
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Proxy class for lazy loading bundle entities
 */
class BundleProxy extends Bundle
{
	/**
	 * @var EntityLoaderCollection
	 */
	private $_loaders;

	/**
	 * @var int | null
	 */
	private $_imageID;

	/**
	 * @param EntityLoaderCollection $loaders
	 * @param string $defaultCurrency
	 */
	public function __construct(EntityLoaderCollection $loaders, $defaultCurrency)
	{
		$this->_loaders = $loaders;

		parent::__construct($defaultCurrency);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getImage()
	{
		if (null === parent::getImage() && null !== $this->_imageID) {
			$image = $this->_loaders->get('file')->getImage($this);

			if ($image) {
				$this->setImage($image);
			}
		}

		return parent::getImage();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getProductRows()
	{
		if (!parent::getProductRows()) {
			$productRows = $this->_loaders->get('product_row')->getProductRows($this);

			foreach ($productRows as $productRow) {
				$this->addProductRow($productRow);
			}
		}

		return parent::getProductRows();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPrice($currencyID = null)
	{
		if (!parent::getPrices()) {
			$this->_loadPrices();
		}

		return parent::getPrice($currencyID);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getPrices()
	{
		if (!parent::getPrices()) {
			$this->_loadPrices();
		}

		return parent::getPrices();
	}

	/**
	 * Set the ID for the image file assigned to the bundle
	 *
	 * @param int $id      The file ID for the image assigned to the bundle
	 */
	public function setImageID($id)
	{
		if (!is_numeric($id) && (int) $id != $id) {
			throw new \InvalidArgumentException('Image ID must be a whole number');
		}

		$this->_imageID = (int) $id;
	}

	/**
	 * Get the ID for the image file assigned to the bundle if set
	 *
	 * @return int | null
	 */
	public function getImageID()
	{
		return $this->_imageID;
	}

	/**
	 * Lazy load the prices for the bundle
	 */
	private function _loadPrices()
	{
		$prices = $this->_loaders->get('price')->getPrices($this);

		foreach ($prices as $currencyID => $price) {
			$this->setPrice($price, $currencyID);
		}
	}
}