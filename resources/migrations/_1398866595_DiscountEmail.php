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
					email VARCHAR(255),
					used_at INT(11) DEFAULT NULL,
					PRIMARY KEY (discount_id, email)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8
			;
		");
	}

	public function down()
	{
		$this->run("
			DROP TABLE discount_email;
		");
	}
}