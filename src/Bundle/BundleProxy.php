<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Entity\EntityLoaderCollection;

class BundleProxy extends Bundle
{
	private $_loaders;
	private $_imageID;

	public function __construct(EntityLoaderCollection $loaders)
	{
		$this->_loaders = $loaders;
	}

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

	public function getProductRows()
	{
		if (null === parent::getProductRows()) {
			$productRows = $this->_loaders->get('product_row')->getProductRows($this);

			foreach ($productRows as $productRow) {
				$this->addProductRow($productRow);
			}
		}

		return parent::getProductRows();
	}

	public function getPrice($currencyID)
	{
		if (null === parent::getPrices()) {
			$this->_loadPrices();
		}

		return parent::getPrice($currencyID);
	}

	public function getPrices()
	{
		if (null === parent::getPrices()) {
			$this->_loadPrices();
		}

		return parent::getPrices();
	}

	public function setImageID($id)
	{
		if (!is_int($id)) {
			throw new \InvalidArgumentException('Image ID must be an integer');
		}

		$this->_imageID = $id;
	}

	public function getImageID()
	{
		return $this->_imageID;
	}

	private function _loadPrices()
	{
		$prices = $this->_loaders->get('price')->getPrices($this);

		foreach ($prices as $currencyID => $price) {
			$this->getPrice($price, $currencyID);
		}
	}
}