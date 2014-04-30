<?php

use Message\Cog\Migration\Adapter\MySQL\Migration;

class _1398866595_DiscountEmail extends Migration
{
	public function up()
	{
		$this->run("
			CREATE TABLE
				discount_email
				(
					discount_id INT(11),
					email varchar(255)
				)
			;
		");
	}

	public function down()
	{
		$this->run("
			DROP discount_email
		");
	}
}