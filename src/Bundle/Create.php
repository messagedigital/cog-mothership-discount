<?php

namespace Message\Mothership\Discount\Bundle;

use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Cog\DB;
use Message\User\UserInterface;

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
	 * @var UserInterface
	 */
	private $_user;

	public function __construct(
		DB\Query $query,
		BundleProductCreate $bundleProductCreate,
		BundlePriceCreate $bundlePriceCreate,
		BundleImageCreate $bundleImageCreate,
		UserInterface $user
	)
	{
		$this->_query = $query;
		$this->_bundleProductCreate = $bundleProductCreate;
		$this->_bundlePriceCreate   = $bundlePriceCreate;
		$this->_bundleImageCreate   = $bundleImageCreate;
		$this->_user = $user;
	}

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
					:allowCodes?b,
					:start?dn,
					:end?dn,
					:createdAt?d,
					:createdBy?i
				)
		", [
			'name'        => $bundle->getName(),
			'allowCodes'  => $bundle->allowCodes(),
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