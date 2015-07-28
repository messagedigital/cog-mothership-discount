<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB;

/**
 * Class BundleImageCreate
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for saving bundle image assignments to the database. The query can be overridden with a Transaction object
 * to allow the class to handle both the creation and editing of a bundle efficiently.
 */
class BundleImageCreate implements DB\TransactionalInterface
{
	/**
	 * @var DB\QueryableInterface
	 */
	private $_query;

	/**
	 * @param DB\QueryableInterface $query
	 */
	public function __construct(DB\QueryableInterface $query)
	{
		$this->_query = $query;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setTransaction(DB\Transaction $transaction)
	{
		$this->_query = $transaction;
	}

	/**
	 * Save the image assignment to the database
	 *
	 * @param Bundle $bundle     The bundle the image is assigned to
	 * @param bool $delete       If set to true, existing images will be deleted from the database. Defaults to true.
	 */
	public function save(Bundle $bundle, $delete = true)
	{
		if ($delete) {
			$this->_query->run("
				DELETE FROM
					discount_bundle_image
				WHERE
					bundle_id = :id?i
			", [
				'id' => $bundle->getID()
			]);
		}

		if ($bundle->getImage()) {
			$this->_query->run("
				INSERT INTO
					discount_bundle_image
					(
						bundle_id,
						file_id
					)
				VALUES
					(
						:bundleID?i,
						:fileID?i
					)
			", [
				'bundleID' => $bundle->getID(),
				'fileID'   => $bundle->getImage()->id,
			]);
		}
	}
}