<?php

use Message\Cog\Migration\Adapter\MySQL\Migration;

class _1394460078_RemoveLocale extends Migration
{
	public function up()
	{
		$this->run('
			ALTER TABLE `discount_amount` DROP PRIMARY KEY;
			ALTER TABLE `discount_amount` PRIMARY KEY (`discount_id`,`currency_id`);
			ALTER TABLE `discount_amount` DROP `locale`;
		');

		$this->run('
			ALTER TABLE `discount_threshold` DROP PRIMARY KEY;
			ALTER TABLE `discount_threshold` PRIMARY KEY (`discount_id`,`currency_id`);
			ALTER TABLE `discount_threshold` DROP `locale`;
		');
	}

	public function down()
	{
		$this->run('
			ALTER TABLE `discount_amount` ADD `locale` varchar(50) NOT NULL;
			ALTER TABLE `discount_amount` DROP PRIMARY KEY;
			ALTER TABLE `discount_amount` PRIMARY KEY (`discount_id`,`currency_id`,`locale`);
		');

		$this->run('
			ALTER TABLE `discount_threshold` ADD `locale` varchar(50) NOT NULL;
			ALTER TABLE `discount_threshold` DROP PRIMARY KEY;
			ALTER TABLE `discount_threshold` PRIMARY KEY (`discount_id`,`currency_id`,`locale`);
		');

	}
}
