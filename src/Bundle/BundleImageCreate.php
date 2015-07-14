<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\DB\Query;

class BundleImageCreate
{
	private $_query;

	public function __construct(Query $query)
	{
		$this->_query = $query;
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