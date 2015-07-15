<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Entity\EntityLoaderInterface;
use Message\Mothership\FileManager\File\FileLoader as BaseFileLoader;

class FileLoader implements EntityLoaderInterface
{
	private $_fileLoader;

	public function __construct(BaseFileLoader $fileLoader)
	{
		$this->_fileLoader = $fileLoader;
	}

	public function getImage(BundleProxy $bundle)
	{
		return $this->_fileLoader->getByID($bundle->getImageID());
	}
}