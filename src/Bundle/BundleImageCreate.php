<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB;

class BundleImageCreate implements DB\TransactionalInterface
{
	private $_query;

	public function __construct(DB\QueryableInterface $query)
	{
		$this->_query = $query;
	}

	public function setTransaction(DB\Transaction $transaction)
	{
		$this->_query = $transaction;
	}

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