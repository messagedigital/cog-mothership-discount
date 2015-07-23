<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Cog\DB;
use Message\User\UserInterface;

/**
 * Class Create
 * @package Message\Mothership\Discount\Bundle
 *
 * @author  Thomas Marchant <thomas@mothership.ec>
 *
 * Class for saving newly created bundles to the database
 */
class Create
{
	/**
	 * @var DB\Query
	 */
	private $_query;

	/**
	 * @var BundleProductCreate
	 */
	private $_bundleProductCreate;

	/**
	 * @var BundlePriceCreate
	 */
	private $_bundlePriceCreate;

	/**
	 * @var BundleImageCreate
	 */
	private $_bundleImageCreate;

	/**
	 * @var UserInterface
	 */
	private $_user;

	/**
	 * @param DB\Query $query
	 * @param BundleProductCreate $bundleProductCreate
	 * @param BundlePriceCreate $bundlePriceCreate
	 * @param BundleImageCreate $bundleImageCreate
	 * @param UserInterface $user
	 */
	public function __construct(
		DB\Query $query,
		BundleProductCreate $bundleProductCreate,
		BundlePriceCreate $bundlePriceCreate,
		BundleImageCreate $bundleImageCreate,
		UserInterface $user
	)
	{
		$this->_query               = $query;
		$this->_bundleProductCreate = $bundleProductCreate;
		$this->_bundlePriceCreate   = $bundlePriceCreate;
		$this->_bundleImageCreate   = $bundleImageCreate;
		$this->_user                = $user;
	}

	/**
	 * Save bundle to the database
	 *
	 * @param Bundle $bundle
	 *
	 * @return Bundle           Return Bundle with ID and authorship details updated.
	 */
	public function save(Bundle $bundle)
	{
		if (!$bundle->getAuthorship()->createdAt()) {
			$bundle->getAuthorship()->create(
				new DateTimeImmutable,
				$this->_user->id
			);
		}

		$result = $this->_query->run("
			INSERT INTO
				discount_bundle
				(
					`name`,
					allow_codes,
					start,
					`end`,
					created_at,
					created_by
				)
			VALUES
				(
					:name?s,
					:allowsCodes?b,
					:start?dn,
					:end?dn,
					:createdAt?d,
					:createdBy?i
				)
		", [
			'name'        => $bundle->getName(),
			'allowsCodes' => $bundle->allowsCodes(),
			'start'       => $bundle->getStart(),
			'end'         => $bundle->getEnd(),
			'createdAt'   => new \DateTime,
			'createdBy'   => $this->_user->id,
		]);

		$bundle->setID($result->id());

		$this->_bundleProductCreate->save($bundle);
		$this->_bundlePriceCreate->save($bundle);
		$this->_bundleImageCreate->save($bundle);

		return $bundle;
	}
}