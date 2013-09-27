<?php

use Message\Cog\Migration\Adapter\MySQL\Migration;

class _1379414517_SetUp extends Migration
{
	public function up()
	{
		$this->run("
			CREATE TABLE IF NOT EXISTS `discount` (
			  `discount_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `code` varchar(100) DEFAULT '',
			  `created_at` int(11) unsigned NOT NULL,
			  `created_by` int(11) unsigned DEFAULT NULL,
			  `updated_at` int(11) unsigned DEFAULT NULL,
			  `updated_by` int(11) unsigned DEFAULT NULL,
			  `deleted_at` int(11) unsigned DEFAULT NULL,
			  `deleted_by` int(11) unsigned DEFAULT NULL,
			  `name` varchar(255) NOT NULL DEFAULT '',
			  `description` text,
			  `start` int(11) unsigned DEFAULT NULL,
			  `end` int(11) unsigned DEFAULT NULL,
			  `percentage` int(3) unsigned DEFAULT NULL,
			  `free_shipping` int(1) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`discount_id`),
			  UNIQUE KEY `code` (`code`),
			  KEY `created_at` (`created_at`),
			  KEY `created_by` (`created_by`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->run("
			CREATE TABLE IF NOT EXISTS `discount_amount` (
			  `discount_id` int(11) unsigned NOT NULL,
			  `currency_id` char(3) NOT NULL,
			  `locale` varchar(50) NOT NULL,
			  `amount` decimal(10,2) unsigned NOT NULL,
			  PRIMARY KEY (`discount_id`,`currency_id`,`locale`),
			  KEY `discount_id` (`discount_id`),
			  KEY `currency_id` (`currency_id`),
			  KEY `locale` (`locale`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->run("
			CREATE TABLE IF NOT EXISTS `discount_product` (
			  `discount_id` int(11) unsigned NOT NULL,
			  `product_id` int(11) unsigned NOT NULL DEFAULT '0',
			  PRIMARY KEY (`discount_id`,`product_id`),
			  KEY `discount_id` (`discount_id`),
			  KEY `product_id` (`product_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");

		$this->run("
			CREATE TABLE IF NOT EXISTS `discount_threshold` (
			  `discount_id` int(11) unsigned NOT NULL,
			  `currency_id` char(3) NOT NULL,
			  `locale` varchar(50) NOT NULL,
			  `threshold` decimal(10,2) unsigned NOT NULL,
			  PRIMARY KEY (`discount_id`,`currency_id`,`locale`),
			  KEY `discount_id` (`discount_id`),
			  KEY `currency_id` (`currency_id`),
			  KEY `locale` (`locale`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
	}

	public function down()
	{
		$this->run('
			DROP TABLE IF EXISTS
				`discount`
		');

		$this->run('
			DROP TABLE IF EXISTS
				`discount_amount`
		');

		$this->run('
			DROP TABLE IF EXISTS
				`discount_product`
		');

		$this->run('
			DROP TABLE IF EXISTS
				`discount_threshold`
		');
	}
}
