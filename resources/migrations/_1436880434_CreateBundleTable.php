<?php

use Message\Cog\Migration\Adapter\MySQL\Migration;

class _1436880434_CreateBundleTable extends Migration
{
	public function up()
	{
		$this->run("
			CREATE TABLE
				discount_bundle
				(
					bundle_id INT(11) NOT NULL AUTO_INCREMENT,
					`name` VARCHAR(255) NOT NULL,
					allow_codes TINYINT NOT NULL,
					start INT(11) DEFAULT NULL,
					`end` INT(11) DEFAULT NULL,
					created_at INT(11) NOT NULL,
					created_by INT(11) NOT NULL,
					updated_at INT(11) DEFAULT NULL,
					updated_by INT(11) DEFAULT NULL,
					deleted_at INT(11) DEFAULT NULL,
					deleted_by INT(11) DEFAULT NULL,
					PRIMARY KEY (bundle_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->run("
			CREATE TABLE
				discount_bundle_price
				(
					bundle_id INT(11) NOT NULL,
					currency VARCHAR(3) NOT NULL,
					price DECIMAL(10,2) unsigned NOT NULL,
					PRIMARY KEY (bundle_id, currency)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->run("
			CREATE TABLE
				discount_bundle_image
				(
					bundle_id INT(11) NOT NULL,
					file_id INT(11) NOT NULL,
					PRIMARY KEY (bundle_id, file_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->run("
			CREATE TABLE
				discount_bundle_product_row
				(
					product_row_id INT(11) NOT NULL AUTO_INCREMENT,
					bundle_id INT(11) NOT NULL,
					product_id INT(11) NOT NULL,
					quantity INT(11) NOT NULL,
					PRIMARY KEY (product_row_id)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->run("
			CREATE TABLE
				discount_bundle_product_option
				(
					product_row_id INT(11) NOT NULL,
					bundle_id INT(11) NOT NULL,
					option_name VARCHAR(255) NOT NULL,
					option_value VARCHAR(255) NOT NULL,
					PRIMARY KEY (product_row_id, bundle_id, option_name)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
	}

	public function down()
	{
		$this->run("
			DROP TABLE IF EXISTS discount_bundle;
		");

		$this->run("
			DROP TABLE IF EXISTS discount_bundle_price;
		");

		$this->run("
			DROP TABLE IF EXISTS discount_bundle_image;
		");

		$this->run("
			DROP TABLE IF EXISTS discount_bundle_product_row;
		");

		$this->run("
			DROP TABLE IF EXISTS discount_bundle_product_option;
		");
	}
}