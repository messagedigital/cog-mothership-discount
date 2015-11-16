<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Entity\EntityLoaderInterface;
use Message\Mothership\FileManager\File\FileLoader as BaseFileLoader;

/**
 * Class FileLoader
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for lazy loading image file for bundle
 */
class FileLoader implements EntityLoaderInterface
{
	/**
	 * @var BaseFileLoader
	 */
	private $_fileLoader;

	/**
	 * @param BaseFileLoader $fileLoader
	 */
	public function __construct(BaseFileLoader $fileLoader)
	{
		$this->_fileLoader = $fileLoader;
	}

	/**
	 * Lazy load image assigned to bundle using the FileLoader
	 *
	 * @param BundleProxy $bundle
	 *
	 * @return array|\Message\Mothership\FileManager\File\File
	 */
	public function getImage(BundleProxy $bundle)
	{
		return $this->_fileLoader->getByID($bundle->getImageID());
	}
}